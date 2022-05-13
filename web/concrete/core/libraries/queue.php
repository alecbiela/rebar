<?php defined('C5_EXECUTE') or die("Access Denied.");

/**
 * A library for making data/task Queues, loosely derived from Zend_Queue of the Zend Framework
 */
class Concrete5_Library_Queue implements Countable {

	protected $queue_id;
	protected $queue_name;
	protected $timeout;
	public function getName(){return $this->queue_name;}
	public function getId(){return $this->queue_id;}
	public function getTimeout(){return $this->timeout;}

	/**
	 * Instantiate a new or existing Queue
	 * @param string $nm - Name (handle) of the Queue
	 * @param int $timeout - Queue Timeout (in seconds, optional)
	 * @param int $id - ID of the Queue (optional, existing Queues only)
	 */
	public function __construct($nm, $timeout=30){
		//try to get the queue by name from the DB. If it doesn't exist, create it.
		$db = Loader::db();
		$r = $db->GetRow('SELECT * from `Queues` where `queue_name` = ?', array($nm));
		if(count($r)>0){
			$this->queue_id = $r["queue_id"];
			$this->queue_name = $r["queue_name"];
			$this->timeout = $r["timeout"];
		} else {
			$r = $db->execute('INSERT INTO `Queues` (`queue_name`, `timeout`) VALUES (?,?)', array($nm, $timeout));
			$this->queue_id = $db->Insert_ID();
			$this->queue_name = $nm;
			$this->timeout = $timeout;
		}
	}

	/**
	 * Sends a Queue Message to this Queue
	 * @param mixed $message - The message to send
	 * @return QueueMessage the created message object
	 */
	public function send($message){
        if (is_scalar($message)) {
            $message = (string) $message;
        }
        if (is_string($message)) {
            $message = trim($message);
        }

		$message = new QueueMessage(null, $this->queue_id, null, $message, md5($message), 30, time());

        try {
            $message->save();
        } catch (Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $message;
	}

	/**
	 * Receive message(s) from the Queue
	 * @param int $max - The maximum number of messages to receive (optional)
	 * @param int $timeout - The timeout for receiving messages (optional)
	 * @return QueueMessage[] of received messages
	 */
	public function receive($max=null, $timeout=null){
        if ($max === null) {
            $max = 1;
        }
        if ($timeout === null) {
            $timeout = 30;
        }
        $msgs      = array();
        $microtime = microtime(true); // cache microtime
        $db        = Loader::db();

        // start transaction handling
        try {
            if ( $max > 0 ) {
				//Begin transaction
				$db->beginTrans();

				$r = $db->getAll('SELECT * FROM `QueueMessages` WHERE `queue_id`=? AND `handle` IS NULL OR `timeout`+'.(int)$timeout.' < '.(int)$microtime.' LIMIT '.$max.' FOR UPDATE', array($this->queue_id));

				//loop through all messages
                foreach ($r as $data) {
                    // create new hash
                    $data['handle'] = md5(uniqid(rand(), true));
					
					//calculated threshold of timeout (for update statement)
					$ttime = intval($data['timeout']) - $timeout;

					// set handle and microtime
                    $update = array(
                        'handle'  => $data['handle'],
                        'timeout' => $microtime
                    );

                    // update the database
					// need the where again in case the message has already been grabbed by another thread
					$count = $db->execute('UPDATE `QueueMessages` SET `handle`=?, `timeout`=? WHERE `message_id`=? AND `handle` IS NULL OR `timeout` < '.$ttime, array($update['handle'], (int)$update['timeout'], (int)$data['message_id']));

                    // if greater than 0, we actually did something 
                    if ($count > 0) {
                        $msgs[] = $data;
                    }
                }

				//commit the transaction (also ends this transaction)
                $db->commitTrans();
            }
        } catch (Exception $e) {
			//on exception, roll back changes
            $db->rollBackTrans();

            //throw an exception, maybe log an error?
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

		//return an array of received messages
		//this static function will take the message array and make it into an array of type QueueMessage
        return QueueMessage::fromArray($msgs, $this);
	}

	/**
	 * Returns the number of messages in this Queue
	 * @param void
	 * @return int number of messages
	 */
	public function count(){
		$db = Loader::db();
		$r = $db->getOne('SELECT COUNT(*) FROM `QueueMessages` WHERE `queue_id`=?', array($this->queue_id));
		return intval($r);
	}

	/**
	 * Deletes the Queue associated with this PHP object
	 * @param void
	 * @return boolean true always
	 */
	public function deleteQueue(){
		$db = Loader::db();
		$db->execute('DELETE FROM `Queues` WHERE `queue_id`=?',array($this->queue_id));
		return true;
	}

	/**
	 * Deletes a message from the Queue
	 * @param QueueMessage|int $msg - The message ID or the QueueMessage object of the message to delete
	 * @return boolean true always
	 */
	public function deleteMessage($msg){
		$id = ($msg instanceof QueueMessage) ? $msg->getMessageId() : $msg;
		$id = intval($id);
		$db = Loader::db();
		$db->execute('DELETE FROM `QueueMessages` WHERE `message_id`=?', array($id));
		return true;
	}

	/**
	 * Returns a Queue object by its name (handle)
	 * If it doesn't exist, creates one with default params and returns it
	 * @param string $nm - Queue name
	 * @return Queue
	 */
	public static function getByName($nm, $opts){
		$timeout = (isset($opts['timeout'])) ? $opts['timeout'] : 30;
		return new Queue($nm, $timeout);
	}

	/**
	 * Synonym of getByName()
	 * @param string $name - Queue name
	 * @param array $additionalConfig - array of config (currently 'timeout' is the only supported value)
	 * @return Queue|null
	 */
	public static function get($name, $additionalConfig = array()) {
		return self::getByName($name, $additionalConfig);
	}

	/**
	 * Sees whether or not a Queue exists by its given name (handle)
	 * @param string $name - Queue name
	 * @return boolean true if exists, false if it doesn't
	 */
	public static function exists($name) {
		$db = Loader::db();
		$r = $db->GetOne('select queue_id from Queues where queue_name = ?', array($name));
		return $r > 0;
	}
}
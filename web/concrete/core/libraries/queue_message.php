<?php defined('C5_EXECUTE') or die("Access Denied.");

/**
 * A single message associated with a Queue
 */
class Concrete5_Library_QueueMessage {

    protected $message_id;
    protected $queue_id;
    protected $handle;
    protected $body;
    protected $md5;
    protected $timeout;
    protected $created;
    public function getMessageId(){return $this->message_id;}
    public function getQueueId(){return $this->queue_id;}
    public function setQueueId($qid){$this->queue_id = $qid;}
    public function getHandle(){return $this->handle;}
    public function setHandle($han){$this->handle = $han;}
    public function getBody(){return $this->body;}
    public function getTimeout(){return $this->timeout;}
    public function setTimeout($tim){$this->timeout = $tim;}


    public function __construct($id=null, $qid=null, $handle=null, $body=null, $md5=null, $timeout=null, $created=null){
        $this->message_id=$id;
        $this->queue_id=$qid;
        $this->handle=$handle;
        $this->body=$body;
        $this->md5=$md5;
        $this->timeout=$timeout;
        $this->created=$created;
    }

    /**
     * Saves a QueueMessage to the Database
     * @param void
     * @return true
     */
    public function save(){
        $db = Loader::db();
        $db->execute('INSERT INTO `QueueMessages` (`queue_id`, `handle`, `body`, `md5`, `timeout`, `created`) VALUES (?,?,?,?,?,?)', array($this->queue_id, $this->handle, $this->body, $this->md5, $this->timeout, $this->created));
    }

    /**
     * Constructs an array of QueueMessage types from an array of message data returned by Queue::receive()
     * @param array $msgArr - Incoming message array
     * @param Queue $q - The queue associated with these messages
     * @return QueueMessage[] - Populated array of Queue Messages
     */
    public static function fromArray($msgs, $q){
        $queue_messages = array();

        foreach($msgs as $m){
            $queue_messages[] = new QueueMessage($m['message_id'], $m['queue_id'], $m['handle'], $m['body'], $m['md5'], $m['timeout'], $m['created']);
        }

        return $queue_messages;
    }


}
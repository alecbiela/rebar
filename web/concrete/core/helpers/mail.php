<?php
/**
 * Functions used to send mail in Concrete.
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

defined('C5_EXECUTE') or die("Access Denied.");


class Concrete5_Helper_Mail {

	protected $headers = array();
	protected $to = array();
	protected $cc = array();
	protected $bcc = array();
	protected $from = array();
	protected $data = array();
	protected $subject = '';
	public $body = '';
	protected $template = ''; 
	protected $bodyHTML = false;
	protected $testing = false;

		
	/**
	 * Manually set the message's subject
	 * @param string $subject
	 * @return void
	 */
	public function setSubject($subject){$this->subject = $subject;}

	/**
	 * Returns the message's subject
	 * @return string
	 */
	public function getSubject(){return $this->subject;}

	/**
	 * Manually set the text body of a mail message, typically the body is set in the template + load method
	 * @param string $body
	 * @return void
	 */
	public function setBody($body){$this->body = $body;}
	
	/**
	 * Returns the message's text body
	 * @return string
	 */
	public function getBody(){return $this->body;}

	/**
	 * manually set the HTML portion of a MIME encoded message, can also be done by setting $bodyHTML in a mail template
	 * @param string $html
	 * @return void
	 */
	public function setBodyHTML($html){$this->bodyHTML = $html;}
	
	/**
	 * Returns the message's html body
	 * @return string
	 */
	public function getBodyHTML(){return $this->bodyHTML;}

	/** Set the testing state (if true the email logging never occurs and sending errors will throw an exception)
	* @param bool $testing
	*/
	public function setTesting($testing){$this->testing = $testing ? true : false;}

	/** Retrieve the testing state
	* @return boolean
	*/
	public function getTesting(){return $this->testing;}

	/** 
	 * Sets a text header on the email about to be sent out.
	 * @param string $header
	 * @param string $value
	 * @return void
	 */
	public function header($header, $value) {
		$this->headers[] = array($header, $value);
	}

	/** 
	 * Sets the from address on the email about to be sent out.
	 * @param string $email
	 * @param string $name
	 * @return void
	 */
	public function from($email, $name = '') {
		$this->from = new Address($email, $name);
	}
	
	/** 
	 * Sets to the to email address on the email about to be sent out.
	 * @param string $email
	 * @param string $name
	 * @return void
	 */
	public function to($email, $name = '') {
		if (strpos($email, ',') > 0) {
			$email = explode(',', $email);
			foreach($email as $em) {
				$this->to[] = new Address($em, $name);
			}
		} else {
			$this->to[] = new Address($email, $name);	
		}
	}
	
	/**
	 * Adds an email address to the cc field on the email about to be sent out.
	 * @param string $email
	 * @param string $name
	 * @return void
	 * @since 5.5.1
	*/
	public function cc($email, $name = '') {
		if (strpos($email, ',') > 0) {
			$email = explode(',', $email);
			foreach($email as $em) {
				$this->cc[] = new Address($em, $name);
			}
		} else {
			$this->cc[] = new Address($email, $name);	
		}
	}
	
	/**
	 * Adds an email address to the bcc field on the email about to be sent out.
	 * @param string $email
	 * @param string $name
	 * @return void
	 * @since 5.5.1
	*/
	public function bcc($email, $name = '') {
		if (strpos($email, ',') > 0) {
			$email = explode(',', $email);
			foreach($email as $em) {
				$this->bcc[] = new Address($em, $name);
			}
		} else {
			$this->bcc[] = new Address($email, $name);	
		}
	}

	/*	
	 * Sets the reply-to address on the email about to be sent out
	 * @param string $email
	 * @param string $name
	 * @return void
	 */
	public function replyto($email, $name = '') {
		if (strpos($email, ',') > 0) {
			$email = explode(',', $email);
			foreach($email as $em) {
				$this->replyto[] = new Address($em, $name);
			}
		} else {
			$this->replyto[] = new Address($email, $name);	
		}
	}
	
	/**
	 * this method is called by the Loader::helper to clean up the instance of this object
	 * resets the class scope variables
	 * @return void
	*/
	public function reset() {
		$this->headers = array();
		$this->to = array();
		$this->cc = array();
		$this->bcc = array();
		$this->replyto = array();
		$this->from = array();
		$this->data = array();
		$this->subject = '';
		$this->body = '';
		$this->template = ''; 
		$this->bodyHTML = false;
		$this->testing = false;
	}
	
	/**
	 * @todo documentation
	 * @return Symfony\Component\Mailer\Mailer
	*/
	public static function getMailerObject(){

		if (MAIL_SEND_METHOD == "SMTP") {
			$user = Config::get('MAIL_SEND_METHOD_SMTP_USERNAME');
			$pass = Config::get('MAIL_SEND_METHOD_SMTP_PASSWORD');
			$serv = Config::get('MAIL_SEND_METHOD_SMTP_SERVER');
			$port = Config::get('MAIL_SEND_METHOD_SMTP_PORT');

			/*$encr = Config::get('MAIL_SEND_METHOD_SMTP_ENCRYPTION');
			if ($encr != '') {
				//stub: implement "use SSL" checkbox here (does it matter anymore?)
			}*/

			if($user != '' && $pass != '' && $serv != '' && $port != ''){
				$dsn = 'smtp://' . $user . ':' . $pass . '@' . $serv . ':' . $port;
				$transport = Transport::fromDsn($dsn);
				return new Mailer($transport);
			}
		}

		$transport = Transport::fromDsn("native://default");
		return new Mailer($transport);		
	}
	
	/** 
	 * Adds a parameter to a mail template
	 * @param string $key
	 * @param string $val
	 * @return void
	 */
	public function addParameter($key, $val) {
		$this->data[$key] = $val;
	}
	
	/** 
	 * Loads an email template from the /mail/ directory
	 * @param string $template 
	 * @param string $pkgHandle 
	 * @return void
	 */
	public function load($template, $pkgHandle = null) {
		extract($this->data);

		// loads template from mail templates directory
		if (file_exists(DIR_FILES_EMAIL_TEMPLATES . "/{$template}.php")) {			
			include(DIR_FILES_EMAIL_TEMPLATES . "/{$template}.php");
		} else if ($pkgHandle != null) {
			if (is_dir(DIR_PACKAGES . '/' . $pkgHandle)) {
				include(DIR_PACKAGES . '/' . $pkgHandle . '/' . DIRNAME_MAIL_TEMPLATES . "/{$template}.php");
			} else {
				include(DIR_PACKAGES_CORE . '/' . $pkgHandle . '/' . DIRNAME_MAIL_TEMPLATES . "/{$template}.php");
			}
		} else {
			include(DIR_FILES_EMAIL_TEMPLATES_CORE . "/{$template}.php");
		}
		
		if (isset($from)) {
			$this->from($from[0], $from[1]);
		}
		$this->template = $template;
		$this->subject = $subject;
		$this->body = $body;
		$this->bodyHTML = $bodyHTML;
	}
	
	/**
	 * @param MailImporter $importer
	 * @param array $data
	 * @return void
	 */
	public function enableMailResponseProcessing($importer, $data) {
		foreach($this->to as $em) {
			$importer->setupValidation($em[0], $data);
		}
		$this->from($importer->getMailImporterEmail());
		$this->body = $importer->setupBody($this->body);		
	}

	/**
	 * @param array $arr
	 * @return string
	 * @todo documentation
	 */
	protected function generateEmailStrings($arr) {
		$str = '';
		for ($i = 0; $i < count($arr); $i++) {
			$v = $arr[$i];
			$nm = $v->getName();
			$em = $v->getAddress();

			if ($nm != '') {
				$str .= '"' . $nm . '" <' . $em . '>';
			} else {
				$str .= $em;
			}
			if (($i + 1) < count($arr)) {
				$str .= ', ';
			}
		}
		return $str;
	}

	/** 
	 * Sends the email
	 * @return void
	 */
	public function sendMail($resetData = true) {
		
		$this->data = array();
		$this->template = ''; 
		$this->testing = false;


		if (ENABLE_EMAILS) {
			//Create a new email object and supply it with information
			$mail=new Email();
			if(count($this->from) == 0) $this->from[] = new Address(EMAIL_DEFAULT_FROM_ADDRESS);
			$mail->from(...$this->from);
			$mail->to(...$this->to);
			if(count($this->cc) > 0) $mail->cc(...$this->cc);
			if(count($this->bcc) > 0) $mail->bcc(...$this->bcc);
			if(count($this->replyto) > 0) $mail->replyTo(...$this->replyto);
			$mail->subject($this->subject);
			$mail->text($this->body);
			if ($this->bodyHTML != false) {
				$mail->html($this->bodyHTML);
			}
			$mail->getHeaders();
			if(count($this->headers) > 0){
				foreach($this->headers as $h){
					$mail->addTextHeader($h[0],$h[1]);
				}
			}

			//Create an email transport
			$transport=self::getMailerObject();

			if(ENABLE_LOG_EMAILS){
				$toStr = $this->generateEmailStrings($this->to);
				$fromStr = $this->generateEmailStrings($this->from);
				$replyStr = $this->generateEmailStrings($this->replyto);
			}
			
			try {

				$transport->send($mail);

			} catch(TransportExceptionInterface $e) {
				if($this->getTesting()) {
					throw $e;
				}
				$l = new Log(LOG_TYPE_EXCEPTIONS, true, true);
				$l->write(t('Mail Exception Occurred. Unable to send mail: ') . $e->getMessage());
				$l->write($e->getTraceAsString());
				if (ENABLE_LOG_EMAILS) {
					$l->write(t('Template Used') . ': ' . $this->template);
					$l->write(t('To') . ': ' . $toStr);
					$l->write(t('From') . ': ' . $fromStr);
					if (isset($this->replyto)) {
						$l->write(t('Reply-To') . ': ' . $replyStr);
					}
					$l->write(t('Subject') . ': ' . $this->subject);
					$l->write(t('Body') . ': ' . $this->body);
				}				
				$l->close();
			}
		}
		
		// add email to log
		if (ENABLE_LOG_EMAILS && !$this->getTesting()) {
			$l = new Log(LOG_TYPE_EMAILS, true, true);
			if (ENABLE_EMAILS) {
				$l->write('**' . t('EMAILS ARE ENABLED. THIS EMAIL WAS SENT TO mail()') . '**');
			} else {
				$l->write('**' . t('EMAILS ARE DISABLED. THIS EMAIL WAS LOGGED BUT NOT SENT') . '**');
			}
			$l->write(t('Template Used') . ': ' . $this->template);
			$l->write(t('To') . ': ' . $toStr);
			$l->write(t('From') . ': ' . $fromStr);
			if (isset($this->replyto)) {
				$l->write(t('Reply-To') . ': ' . $replyStr);
			}
			$l->write(t('Subject') . ': ' . $this->subject);
			$l->write(t('Body') . ': ' . $this->body);
			$l->close();
		}		
		
		// clear data if applicable
		if ($resetData) {
			$this->to = array();
			$this->cc = array();
			$this->bcc = array();
			$this->replyto = array();
			$this->from = array();
			$this->template = '';
			$this->subject = '';
			$this->body = '';
			$this->bodyHTML = '';
		}
	}
	
}

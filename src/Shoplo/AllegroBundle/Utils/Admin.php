<?php

namespace Shoplo\AllegroBundle\Utils;

class Admin
{
	private $mailer;
	private $mail_from = 'allegro@shoploapp.com';
	private $mail_to = array('lech.grzegorz@gmail.com');

	public function __construct(\Swift_Mailer $mailer)
	{
		$this->mailer = $mailer;
	}

	public function notifyByEmail($subject, $body)
    {
		$message = \Swift_Message::newInstance()
			->setSubject($subject)
			->setFrom($this->mail_from)
			->setTo($this->mail_to)
			->setBody($body);
		$this->mailer->send($message);
    }
}

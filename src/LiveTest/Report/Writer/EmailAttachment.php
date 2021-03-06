<?php

namespace LiveTest\Report\Writer;

use LiveTest\Exception;

class EmailAttachment implements Writer
{
  private $emailTemplate = 'templates/email_attachment.tpl';
  
  private $to;
  private $attachmentName;
  private $from;
  private $subject;
  
  public function __construct(\Zend_Config $config)
  {
    $this->to = $config->to;
    $this->from = $config->from;
    $this->subject = $config->subject;
    $this->attachmentName = $config->attachment_name;
    
    if (!is_null($config->email_template))
    {
      $this->emailTemplate = $config->email_template;
    }
    else
    {
      $this->emailTemplate = __DIR__ . '/' . $this->emailTemplate;
    }
  }
  
  public function write($formatedText)
  {
    $mail = new \Zend_Mail();
    
    $mail->addTo($this->to);
    $mail->setFrom($this->from);
    $mail->setSubject($this->subject);
    
    $mail->setBodyHtml(file_get_contents($this->emailTemplate));
    
    $at = new \Zend_Mime_Part($formatedText);
    $at->type = 'text/html';
    $at->disposition = \Zend_Mime::DISPOSITION_INLINE;
    $at->encoding = \Zend_Mime::ENCODING_BASE64;
    $at->filename = $this->attachmentName;
    $at->description = 'LiveTest Attachment';
    
    $mail->addAttachment($at);
    
    $mail->send();
  }
}
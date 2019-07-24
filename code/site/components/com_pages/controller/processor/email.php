<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerProcessorEmail extends ComPagesControllerProcessorAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'html'       => true,
            'recipients' => [],
            'title'      => '',
            'subject'    => ''
        ]);

        parent::_initialize($config);
    }

    public function processData(array $data)
    {
        $mailer = JFactory::getMailer();

        if($this->getConfig()->html) {
            $mailer->isHtml(true);
        }

        //Set the sender
        if(isset($data['email']))
        {
            $sender[] = $data['email'];

            if(isset($data['name'])) {
                $sender[] = $data['name'];
            }

            $mailer->setSender($sender);
        }

        //Add the recipients
        $recipients = (array) $this->getRecipients();
        foreach($recipients as $recipient) {
            $mailer->addRecipient($recipient);
        }

        //Set the subject
        $subject = $this->getSubject();
        $mailer->setSubject($subject);

        //Set the body
        $body = $this->getMessage($data);
        $mailer->setBody($body);

        //Send the mail
        $mailer->send();
    }

    public function getTitle()
    {
        return $this->getConfig()->subject ?? sprintf('%s form', ucfirst($this->getChannel()));
    }

    public function getSubject()
    {
        return $this->getConfig()->subject ?? sprintf('New %s form submission', ucfirst($this->getChannel()));
    }

    public function getRecipients()
    {
        return count($this->getConfig()->recipients) ? $this->getConfig()->recipients : (array) JFactory::getConfig()->get('mailfrom');
    }

    public function getMessage($data)
    {
        $title  = $this->getTitle();
        $content = array();

        foreach($data as $key => $value)
        {
            $content[] = sprintf('<h2>%s</h2>', ucfirst($key));
            $content[] = sprintf('<p>%s</p>', $value);
        }

        $content = implode("\n", $content);

        //Using http://emailframe.work hybrid layout
        $message = <<<MESSAGE
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>$title</title>
<style type="text/css">

  @media only screen and (max-width: 600px) {
    .wrapper{width:95% !important;}
  }  
  
  h1, h2, p {
    color:#222;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
    padding:0;
  } 
  
  h1 {
    font-size:28px;
    font-weight:400;
    margin-top:0;
  }
  
  h2 {
    font-size:16px;
    font-weight:700;
    margin:30px 0 15px 0;
  }
  
  p {
    font-size:14px;
    line-height:1.5em;
    white-space:pre-line;
    margin:15px 0 15px 0;
  }

</style>
</head>
<body style="margin:0; padding:0; background-color:#F8F8F8">
<center>

<table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#F8F8F8" class="wrapper">
    
  <tr>
    <td align="center" height="100%" valign="top" width="100%">
      <!--[if (gte mso 9)|(IE)]>
      <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
      <tr>
      <td align="center" valign="top" width="600">
      <![endif]-->
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;" bgcolor="#ffffff">
          <tr>
            <td align="left" valign="top">
                <h1>$title</h1>
                $content
            </td>
          </tr>
        </table>
      <!--[if (gte mso 9)|(IE)]>
      </td>
      </tr>
      </table>
      <![endif]-->
    </td>
  </tr>

</table>
</center>
</body>
</html>
MESSAGE;

        return $message;
    }
}
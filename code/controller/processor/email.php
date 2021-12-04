<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

use PHPMailer\PHPMailer;

class ComPagesControllerProcessorEmail extends ComPagesControllerProcessorAbstract
{
    private $__mailer;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'log_file'   => $this->getObject('com:pages.config')->getLogPath().'/phpmailer.log',
            'debug'      => false, //PHPMailer\SMTP::DEBUG_LOWLEVEL
            'html'       => true,
            'title'      => '',
            'subject'    => '',
            'sender'     => [
                'email' => JFactory::getConfig()->get('mailfrom'),
                'name'  => JFactory::getConfig()->get('fromname'),
            ],
            'recipients' => null,
            'smtp' => [
                'auth'   => JFactory::getConfig()->get('smtpauth'),
                'user'   => JFactory::getConfig()->get('smtpuser'),
                'pass'   => JFactory::getConfig()->get('smtppass'),
                'host'   => JFactory::getConfig()->get('smtphost'),
                'secure' => JFactory::getConfig()->get('smtpsecure'),
                'port'   => JFactory::getConfig()->get('smtpport'),
             ],
             'mailer'   => JFactory::getConfig()->get('mailer'),
        ]);

        parent::_initialize($config);
    }

    public function getMailer()
    {
        if(class_exists('\PHPMailer\PHPMailer\PHPMailer') && !isset($this->__mailer))
        {
            $config = $this->getConfig();

            $mailer = new PHPMailer\PHPMailer();

            $from_email = $config->get('sender')->email;
            $from_name  = $config->get('sender')->name;

            if ($from_email) {
                $mailer->setFrom($from_email, $from_name, false);
            }

            if($config->html) {
                $mailer->isHtml(true);
            }

            // Default mailer is to use PHP's mail function
            switch ($config->get('mailer'))
            {
                case 'smtp':
                    $smtp = $config->get('smtp');

                    $mailer->SMTPAuth = $smtp->auth == 0 ? null : 1;
                    $mailer->Host     = $smtp->host;
                    $mailer->Username = $smtp->user;
                    $mailer->Password = $smtp->pass;
                    $mailer->Port     = $smtp->port;

                    if ($smtp->secure == 'ssl' || $smtp->secure == 'tls') {
                        $mailer->SMTPSecure = $smtp->secure;
                    } else {
                        $mailer->SMTPAutoTLS = false;
                    }

                    $mailer->isSMTP();

                    break;

                case 'sendmail':
                    $mailer->isSendmail();
                    break;

                default:
                    $mailer->isMail();
                    break;
            }

            if ($this->getConfig()->debug)
            {
                $mailer->SMTPDebug = (int) $this->getConfig()->debug;

                if(is_dir(dirname($config->log_file)))
                {
                    $mailer->Debugoutput = function ($message, $level) use ($config) {
                        error_log(sprintf('Error in Mail API: %s'."\n", $message), 3, $config->log_file);
                    };
                }
            }
            else $mailer->XMailer = ' '; // Don't disclose the PHPMailer version

            if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
                PHPMailer\PHPMailer::$validator = 'php';
            }

            $this->__mailer = $mailer;
        }

        return $this->__mailer;
    }

    public function processData(array $data)
    {
        if($mailer = $this->getMailer())
        {
            //Set the reply to
            if($email = $this->getEmail($data))
            {
                $name = $this->getName($data);
                $mailer->addReplyTo($email, $name);
            }

            //Add the recipients
            $recipients = $this->getRecipients();
            foreach($recipients as $recipient) {
                $mailer->addAddress($recipient);
            }

            //Set the subject
            $subject = $this->getSubject();
            $mailer->Subject = $subject;

            //Set the body
            $body = $this->getMessage($data);
            $mailer->Body = $body;

            $result = $mailer->send();

            if(!$result) {
                throw new RuntimeException($mailer->ErrorInfo);
            }
        }
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
        if($this->getConfig()->has('recipients')) {
            $recipients = KObjectConfig::unbox($this->getConfig()->recipients);
        }  else {
            $recipients = JFactory::getConfig()->get('mailfrom');
        }

        return (array) $recipients;
    }

    public function getEmail($data)
    {
        return $data['email'] ?? '';
    }

    public function getName($data)
    {
        $name = $data['name'] ?? '';

        if(isset($data['firstName']) && isset($data['lastName'])) {
            $name = $data['firstName'].' '.$data['lastName'];
        }

        return $name;
    }

    public function getMessage($data)
    {
        $title  = $this->getTitle();
        $content = array();

        foreach($data as $key => $value)
        {
            $content[] = sprintf('<h2>%s</h2>', implode(' ', array_map('ucfirst', KStringInflector::explode(ucfirst($key)))));
            $content[] = sprintf('<p>%s</p>', is_array($value) ? implode(', ', $value) : $value);
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

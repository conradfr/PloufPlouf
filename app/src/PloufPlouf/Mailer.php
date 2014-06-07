<?php

namespace PloufPlouf;

/**
 * Class Mailer
 */
class Mailer {

    /** @var \Swift_Mailer swift Swift Mailer */
    protected $swift;

    /** @var twig Twig */
    protected $twig;

    /** @var \Predis\Client redis Predis */
    protected $redis;

    /** @var int TTL */
    protected $ttl;

    public function __construct(\Swift_Mailer $swift, $twig, $redis, $ttl) {
        $this->swift = $swift;
        $this->twig = $twig;
        $this->redis = $redis;
        $this->ttl = $ttl;
    }

    /**
     * Send the mail
     * @note data passed has to be correct, no validation done here
     *
     * @param array $data
     */
    public function send(array $data) {

        // we don't sent any mail if user email is blacklisted
        if ($this->filterEmails($data['email']) === false) { return 0; }

        // generate & store email id for blacklist option
        $messageId = uniqid();
        $data['message_id'] = $messageId;

        // filter mails and add them as Bcc
        $emails = $this->filterEmails($data['emails']);
        $emails[] = $data['email'];

        // we send an email for each instead of Bcc to allow blacklist link

        $totalSent = 0;
        $blacklist_ids = array();

        foreach($emails as $email) {

            $data['email_id'] = uniqid();

            $message = \Swift_Message::newInstance()
                ->setSubject('PloufPlouf' . ((empty($data['question'])) ? '' : ' - ' . $data['question']))
                ->setFrom(array('noreply@funkybits.fr' => 'PloufPlouf'))
                ->setTo($email)
                ->setContentType('text/html')
                ->setBody($this->twig->render('email.html.twig', $data)
                );

            $sent = $this->swift->send($message);
            if ($sent > 0) {
                $blacklist_ids[$data['email_id']] = $email;
                $totalSent++;
            }
        }

        // if emails has been sent, store blacklist infos in redis
        if ($totalSent > 0) {
            $this->redis->hmset('mail:' . $messageId, $blacklist_ids);
            $this->redis->EXPIRE('mail:' . $messageId, $this->ttl);
        }

        // return the # of mail address it was sent to
        return $totalSent;
    }

    /**
     * Filter emails against the blacklist
     *
     * @param string|array $emails
     * @return array|string
     */
    protected function filterEmails($emails) {
        $isString = false;
        if (is_string($emails)) {
            $emails = [$emails];
            $isString = true;
        }
        elseif (!is_array($emails)) { return $emails; }

        foreach($emails as $key => $email) {
            if ($this->redis->SISMEMBER('global:blacklist', $email) === true) {
                unset($emails[$key]);
            }
        }

        // return true/false is string was given as parameter
        if ($isString) {
            if (count($emails) > 0) { return true; }
            else { return false; }
        }
        // else return filtered array
        return $emails;
    }
} 
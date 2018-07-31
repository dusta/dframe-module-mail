<?php
namespace Mail\Model\Mail;

use Dframe\MyMail\MyMail;
use Dframe\Config;

/**
 * Model wysylki maili
 *
 * @author Sławomir Kaleta
 */

class Mail extends \Model\Model
{
    /**
     * Bufor maili do przeslania do bazy
     *
     * @var array
     */

    protected $buffer = [];

    /**
     * Liczba wybranych emaili
     *
     * @param array $whereObject
     * @return array
     */
    public function mailsCount($whereObject = [])
    {

        $query = $this->db->prepareQuery('SELECT COUNT(*) AS `count` FROM `mails`');
        $query->prepareWhere($whereObject);

        $row = $this->db->pdoQuery($query->getQuery(), $query->getParams())->result();
        return $row['count'];
    }


    /**
     * Lista wybranych emaili na podstawie warunku
     *
     * @param [type] $start
     * @param [type] $limit
     * @param [type] $whereObject
     * @param string $order
     * @param string $sort
     * @return array
     */
    public function mails($start, $limit, $whereObject, $order = 'id', $sort = 'DESC')
    {

        $query = $this->db->prepareQuery(
            'SELECT 
                `mail`.*, 
                `users`.`id` 
            FROM  mail 
            LEFT JOIN users ON `mail`.`mail_address` = `users`.`email`'
        );

        $query->prepareWhere($whereObject);
        $query->prepareOrder($order, $sort);
        $query->prepareLimit($limit, $start);

        $results = $this->db->pdoQuery($query->getQuery(), $query->getParams())->results();
        return $this->methodResult(true, ['data' => $results]);
    }

    /**
     * Dodaje mail do bufora
     *
     * @param array $address
     * @param [type] $subject
     * @param [type] $body
     * @param string $sender
     * @return array
     */
    public function addToBuffer(array $address, $subject, $body = '', $sender = '')
    {

        $dateUTC = new \DateTime("now", new \DateTimeZone("UTC"));

        $mailEntry = array(
            'mail_name' => $address['name'],
            'mail_address' => $address['mail'],
            'mail_subject' => $subject,
            'mail_enqueued' => time(),
            'mail_body' => $body,
            'mail_sender' => $sender,
            'mail_status' => 0,
            'mail_buffer_date' => $dateUTC->format('Y-m-d H:i:s')
        );
        $this->buffer[] = $mailEntry;

        return $this->methodResult(true);
    }

    /**
     * Zrzuca maile z bufora
     *
     * @return array
     */
    public function execute()
    {
        //Pusty 
        if (count($this->buffer) == 0) {
            return $this->methodResult(false, ['response' => 'Buffer is empty']);
        }
        
        //print_r($this->buffer);die();
        $insertResult = $this->db->insertBatch('mails', $this->buffer, true)->getAllLastInsertId();
        if (!count($insertResult)) {
            return $this->methodResult(false, ['response' => 'Unable to add mails to spooler']);
        }

        $this->buffer = array();
        return $this->methodResult(true);
    }


    /**
     *  Wysyla wskazana ilosc maili
     *
     * @param integer $amount
     * @return array
     */
    public function sendMails($amount = 20)
    {

        $amount = (int)$amount;
        if ($amount <= 0) {
            return $this->methodResult(false, 'Incorrect amount');
        }

        $emailsToSend = $this->db->pdoQuery(
            'SELECT * 
            FROM `mails` 
            WHERE `mail_status` = ? 
            ORDER BY `mail_enqueued` ASC
            LIMIT ?',
            array('0', $amount)
        )->results();

        $data = ['sent' => 0, 'failed' => 0, 'errors' => []];
        $return = true;

        $mail = new MyMail(Config::load('myMail', __DIR__ . '/../../Config/')->get());
        $mail->mailObject->isSMTP();
        $mail->mailObject->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        //$mail->SMTPDebug  = 2; // enables SMTP debug information (for testing)
                                 // 1 = errors and messages
                                 // 2 = messages only
        $mail->mailObject->SMTPSecure = false;

        foreach ($emailsToSend as $email) {
            $dateUTC = new \DateTime("now", new \DateTimeZone("UTC"));
            try {

                $addAddress = ['mail' => $email['mail_address'], 'name' => $email['mail_name']];
                $sendResult = $mail->send($addAddress, $email['mail_subject'], $email['mail_body']);

            } catch (\Exception $e) {
                $data['errors'][] = $e->getMessage();
            }

            if (!isset($sendResult)) {
                $data['failed']++;
                $return = false;
                continue;
            }

            $this->db->update('mails', ['mail_sent' => time(), 'mail_status' => '1', 'mail_send_date' => $dateUTC->format('Y-m-d H:i:s')], ['mail_id' => $email['mail_id']]);
            $data['sent']++;
        }

        return $this->methodResult($return, array('response' => $data));
    }

    /**
     * Usuwa wszystkie maile z bazy danych
     *
     * @return array
     */
    public function clear()
    {
        $this->db->truncate('mails');
        return $this->methodResult(true);
    }

}

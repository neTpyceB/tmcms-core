<?php
declare(strict_types=1);

namespace TMCms\Network;

use TMCms\Files\MimeTypes;

defined('INC') or exit;

/**
 * Class Mailer
 */
class Mailer
{
    /**
     * @var string
     */
    private $sender_email = '';
    /**
     * @var string
     */
    private $sender_name = '';
    /**
     * @var array
     */
    private $recipient_emails = [];
    /**
     * @var string
     */
    private $subject = '';
    /**
     * @var string plain text body
     */
    private $message = '';
    /**
     * @var string prepared body for sending
     */
    private $message_body = '';
    /**
     * @var array of all attachments
     */
    private $attachments = [];
    /**
     * @var string generated string for mail with attachments
     */
    private $message_attachment;
    /**
     * @var string
     */
    private $content_type = 'text/html';
    /**
     * @var array
     */
    private $email_headers = [];

    /**
     * @return Mailer
     */
    public static function getInstance(): Mailer
    {
        return new self();
    }

    /**
     * @param string $sender_email
     * @param string $sender_name
     *
     * @return Mailer
     */
    public function setSender(string $sender_email, string $sender_name = ''): Mailer
    {
        $this->sender_email = str_replace(["\n", "\r"], '', $sender_email);
        $this->sender_name = $sender_name ?? $sender_email;

        return $this;
    }

    /**
     * @param string $recipient_mail
     * @param string $name
     *
     * @return Mailer
     */
    public function setRecipient(string $recipient_mail, string $name = ''): Mailer
    {
        $this->recipient_emails[] = ['email' => $recipient_mail, 'name' => $name];

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return Mailer
     */
    public function setSubject(string $subject): Mailer
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return Mailer
     */
    public function setMessage(string $message): Mailer
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->content_type;
    }

    /**
     * @param string $content_type
     *
     * @return Mailer
     */
    public function setContentType(string $content_type): Mailer
    {
        $this->content_type = $content_type;

        return $this;
    }

    /**
     * @param string $filename
     * @param string $content_type
     *
     * @return Mailer
     */
    public function addAttachment(string $filename, string $content_type = ''): Mailer
    {
        $this->attachments[] = [
            'filename'     => $filename,
            'content_type' => $content_type ?: MimeTypes::getMimeTypeByExt(strtolower(pathinfo($filename, PATHINFO_EXTENSION))),
        ];

        return $this;
    }

    /**
     * Prepares all headers and sends email
     */
    public function send()
    {
        if ($this->attachments) {
            // With files
            $this->prepareWithAttachments();
        } else {
            // Usual plain email
            $this->addHeader('Content-Type: ' . $this->content_type . '; charset=utf-8');
            $this->message_body = $this->message;
        }

        if ($this->sender_email) {
            $this->addHeader('From: "' . $this->mail_head_param_encode($this->sender_name) . '" <' . $this->sender_email . '>');
        }

        $so = count($this->recipient_emails);

        for ($i = 0; $i < $so; ++$i) {
            mail($this->recipient_emails[$i]['email'], '=?utf-8?B?' . base64_encode($this->subject) . '?=', wordwrap($this->message_body, 70) . $this->message_attachment, implode("\n", $this->email_headers) . ($this->recipient_emails[$i]['name'] ? "\nTo: " . $this->mail_head_param_encode($this->recipient_emails[$i]['name']) . ' <' . $this->recipient_emails[$i]['email'] . ">\n" : ''), '-r ' . $this->sender_name);
        }
    }

    private function prepareWithAttachments()
    {
        $unique_email_seed = strtoupper(md5(uniqid((string)NOW, true)));

        $this->addHeader('Mime-Version: 1.0');
        $this->addHeader('Content-Type:multipart/mixed;boundary="----------' . $unique_email_seed . '"');

        $this->message_body = '------------' . $unique_email_seed . "\nContent-Type: " . $this->content_type . ";Charset=utf-8\nContent-Transfer-Encoding: 16bit\n\n" . $this->message . "\n\n";

        foreach ($this->attachments as $attachment) {
            $filename = $attachment['filename'];

            $this->message_attachment .= '------------' . $unique_email_seed . "\nContent-Type: " . $attachment['content_type'] . ';name="' . basename($filename) . "\"\nContent-Transfer-Encoding:base64\nContent-Disposition:attachment;filename=\"" . basename($filename) . "\"\n\n" . chunk_split(base64_encode(file_get_contents($filename))) . "\n";
        }
    }

    /**
     * @param string $header
     */
    public function addHeader(string $header)
    {
        $this->email_headers[] = $header;
    }

    /**
     * @param string $string_to_encode
     * @param string $charset
     *
     * @return mixed|string
     */
    private function mail_head_param_encode(string $string_to_encode, string $charset = 'utf-8')
    {
        $end = '?=';
        $start = '=?' . $charset . '?B?';

        $spacer = $end . "\r\n " . $start;

        $length = 75 - strlen($start) - strlen($end);
        $length -= ($length % 4);

        $string_to_encode = base64_encode($string_to_encode);
        $string_to_encode = chunk_split($string_to_encode, $length, $spacer);

        $spacer = preg_quote($spacer, '');

        $string_to_encode = preg_replace('/' . $spacer . '$/', NULL, $string_to_encode);
        $string_to_encode = $start . $string_to_encode . $end;

        return $string_to_encode;
    }
}
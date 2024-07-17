<?php

namespace TradusBundle\Service\Mail;

class RequestSendGrid
{
    /**
     * @var array
     */
    private $to = [];

    /**
     * @var array
     */
    private $bcc = [];

    /**
     * @var array
     */
    private $from = [];

    /**
     * @var array
     */
    private $replyTo = [];

    /**
     * @var string
     */
    private $subject;

    /**
     * @var array
     */
    private $content = [];

    /**
     * @var array
     */
    private $attachments = [];

    /**
     * @var array
     */
    private $category = [];

    private $response;

    /**
     * @param $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * Returns the message-id from sendgrid.
     * @return null|string
     */
    public function getMessageId()
    {
        if ($this->response && isset($this->response['headers']['X-Message-Id'])) {
            return $this->response['headers']['X-Message-Id'];
        }
    }

    /**
     * @return array|null
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string $email
     * @param string|null $name
     */
    public function setTo(string $email, string $name = null)
    {
        $this->to[] = ['email' => $email, 'name' => $name];
    }

    /**
     * @param string $email
     * @param string|null $name
     */
    public function setBcc(string $email, string $name = null)
    {
        $this->bcc[] = ['email' => $email, 'name' => $name];
    }

    /**
     * @param string $email
     * @param string $name
     */
    public function setFrom(string $email, string $name = '')
    {
        $this->from = ['email' => $email, 'name' => $name];
    }

    /**
     * @param string $email
     * @param string $name
     */
    public function setReplyTo(string $email, string $name = '')
    {
        $this->replyTo = ['email' => $email, 'name' => $name];
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject)
    {
        $this->subject = mb_convert_encoding($subject, 'UTF-8', 'UTF-8');
    }

    /**
     * @param string $content
     * @param string $type
     */
    public function setContent(string $content, string $type = 'text/html')
    {
        $this->content = ['type' => $type, 'value' => $content];
    }

    /**
     * @param string $file
     * @param string $type
     */
    public function setAttachmentsFromString(string $fileData, string $fileName, string $type = 'application/html')
    {
        $this->attachments[] = ['content' => $fileData, 'filename' => $fileName, 'type' => $type, 'attachment' => 'attachment'];
    }

    /**
     * @param string $file
     * @param string $type
     */
    public function setAttachments(string $file, string $type = 'application/html')
    {
        $file_encoded = base64_encode(file_get_contents('temp/'.$file));
        $this->attachments[] = ['content' => $file_encoded, 'filename' => $file, 'type' => $type, 'attachment' => 'attachment'];
    }

    /**
     * @param $category
     * @return $this
     */
    public function addCategory($category)
    {
        if (is_array($category)) {
            $this->category = $category;
        } else {
            $this->category[] = $category;
        }

        return $this;
    }

    /**
     * Return an json string representing a request object for the SendGrid API.
     * @return string|bool
     */
    public function createPayload()
    {
        if (! count($this->to) || ! $this->from['email']) {
            return false;
        }

        $data['personalizations'][0]['to'] = $this->to;
        $data['personalizations'][0]['subject'] = $this->subject;
        $data['from'] = $this->from;

        if (! $this->content) {
            return false;
        }

        $data['content'][0] = $this->content;

        if (count($this->bcc)) {
            $data['personalizations'][0]['bcc'] = $this->bcc;
        }

        if (count($this->attachments)) {
            $data['attachments'] = $this->attachments;
        }

        if (count($this->category)) {
            $data['categories'] = $this->category;
        }

        return json_encode($data);
    }
}

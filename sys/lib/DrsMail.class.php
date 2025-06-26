<?php
/**
 * @project     DarsiPro CMS
 * @package     DrsMail class
 * @url         https://darsi.pro
 */

class DrsMail {

    public $templatePath;

    public $Viewer;

    private $to;

    private $from;

    private $subject = '';

    private $content_html = '';

    private $content_text = '';

    private $lastError = false;


    public function __construct($templatePath = false) {
        $this->templatePath = rtrim($templatePath, DS) . DS;
        $this->Viewer = new Viewer_Manager();
        $this->from = \Config::read('admin_email');
    }


    public function setTo($to) {
        $this->to = $to;
    }

    public function setFrom($from) {
        $this->from = $from;
    }

    public function setSubject($subject) {
        $this->subject = $subject;
    }

    public function setContentHtml($body) {
        $this->content_html = $body;
    }

    public function setContentText($body) {
        $this->content_text = $body;
    }

    public function sendMail( $context = array() ) {
        $context = array_merge($context, array(
            'to' => $this->to,
            'from' => $this->from,
            'subject' => $this->subject,
            'content_html' => $this->content_html,
            'content_text' => $this->content_text,
            'site_title' => \Config::read('site_title'),
            'site_desc' => \Config::read('meta_description'),
            'site_url' => (used_https() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . WWW_ROOT,
        ));

        try {

            $template_text = file_get_contents(ROOT . '/data/mail/main.text');
            $template_html = file_get_contents(ROOT . '/data/mail/main.html');

            // подстановка значений в текстовую версию письма
            $context['content'] = string_f($context['content_text'], $context);
            $body_text = $this->Viewer->parseTemplate($template_text, $context);

            // экранирование спецсимволов перед подстановкой в html версию
            if (isset($context['password']))
                $context['password'] = h($context['password']);

            // подстановка значений в html версию письма
            $context['content'] = string_f($context['content_html'], $context);
            $body_html = $this->Viewer->parseTemplate($template_html, $context);

            $boundary = uniqid('np');

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "From: " . $_SERVER['SERVER_NAME'] . " <" . $this->from . ">\r\n";
            $headers .= "Return-path: <" . $this->from . ">\r\n";
            $headers .= "Content-Type: multipart/alternative;boundary=" . $boundary;
            $message = "\r\n\r\n--" . $boundary . "\r\n";
            $message .= "Content-type: text/plain;charset=utf-8\r\n\r\n";

            // Plain text body
            $message .= $body_text;
            $message .= "\r\n\r\n--" . $boundary . "\r\n";
            $message .= "Content-type: text/html;charset=utf-8\r\n\r\n";

            // Html body
            $message .= $body_html;
            $message .= "\r\n\r\n--" . $boundary . "--";

            mail($this->to, $context['subject'], $message, $headers);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return true;
    }


    public function getError() {
        return $this->lastError;
    }
}
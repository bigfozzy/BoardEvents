<?php

/**
 * Для отправки писем с помощью функционального объекта outlook
 */
class MailerOutlook extends Mailer
{
    /**
     * Тип E-mail сообщения HTML для функционального объекта outlook
     */
    public const OUTLOOK_TYPE_MESSAGE_HTML = 0;

    /**
     * Тип E-mail сообщения rtf для функционального объекта outlook
     */
    public const OUTLOOK_TYPE_MESSAGE_RTF = 1;

    /**
     * Тип E-mail сообщения текст для функционального объекта outlook
     */
    public const OUTLOOK_TYPE_MESSAGE_TEXT = 2;


    /**
     * Отправляет сообщение с типом Plain/Text (учитывается boolIsTurnedOff)
     * @return bool
     **/
    public function sendText(): bool
    {
        if ($this->boolIsTurnedOff) {
            TOOLS::$log->warn("Отправка писем отключена! Смотри значение переменной 'boolIsTurnedOff'", __METHOD__);
            return true;
        }

        return $this->send(self::OUTLOOK_TYPE_MESSAGE_TEXT);
    }

    /**
     * Отправляет сообщение с типом Html (учитывается boolIsTurnedOff)
     * @return bool
     **/
    public function sendHtml(): bool
    {
        if ($this->boolIsTurnedOff) {
            TOOLS::$log->warn("Отправка писем отключена! Смотри значение переменной 'boolIsTurnedOff'", __METHOD__);
            return true;
        }

        return $this->send(self::OUTLOOK_TYPE_MESSAGE_HTML);
    }

    /**
     * Отправляет сообщение с типом Rtf (учитывается boolIsTurnedOff)
     * @return bool
     **/
    public function sendRtf(): bool
    {
        if ($this->boolIsTurnedOff) {
            TOOLS::$log->warn("Отправка писем отключена! Смотри значение переменной 'boolIsTurnedOff'", __METHOD__);
            return true;
        }

        return $this->send(self::OUTLOOK_TYPE_MESSAGE_RTF);
    }

    /**
     * Послать сообщение с помощью Outlook
     * @param string $from От кого
     * @param string $to Кому
     * @param string $subject Тема письма
     * @param string $msg Текст сообщения
     * @param string $cc кому отправить копию
     * @param string $bcc кому отправить скрытую копию
     * @param int $type Тип сообщения значения 0: 'Html'; 1: 'RTF''; 2: 'Text'
     * @param string[] $attachments приложенные файлы
     * @param int $timeout таймаут
     * @return bool Результат отправки
     * @throws Exception неизвестный тип сообщения
     */
    function sendMailViaOutlook(string $from,
                                string $to,
                                string $subject,
                                string $msg,
                                string $cc = '',
                                string $bcc = '',
                                int $type = 2,
                                array $attachments = array(),
                                int $timeout = 300): bool
    {
        global $outlook, $wt;

        if(!in_array($type, [0, 1, 2]))
            throw new Exception("Неизвестный тип email");
        if(!$from)
            throw new Exception("пустое параметр from");
        if(!$to)
            throw new Exception("пустое параметр to");
        if(!$subject)
            throw new Exception("пустое параметр subject");
        if(!$msg)
            throw new Exception("пустое параметр msg");

        TOOLS::$log->info("Отправка E-mail from = $from; $to = to; subj = $subject", 'functions');

        // Отключить Outlook
        $outlook->kill();
        sleep($wt);
        // создать объект
        TOOLS::$mailer::makeOutlook();
        // получить созданный объект
        $mailer = TOOLS::$mailer::getMailer();

        $mailer->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setMessage($msg)
            ->setSendTimeout($timeout);

        if($cc)
            $mailer->setCopy($cc);

        if($bcc)
            $mailer->setHiddenCopy($bcc);

        $res = false;
        if(!empty($attachments))
            $mailer->setAttachments($attachments);

        if($type == 0)
            $res = $mailer->sendHtml();
        else if($type == 1)
            $res = $mailer->sendRtf();
        else
            $res = $mailer->sendText();
        // Отключить Outlook НЕЛЬЗЯ убивать
        //$outlook->kill();
        //sleep($wt);
        return $res;
    }

    /**
     * Отправка email-сообщения через MS Outlook, для публичного API
     * см. sendText/sendHtml и т.п.
     *
     * Здесь не учитывается boolIsTurnedOff.
     *
     * @param int $intMessageType Тип почтового сообщения (можно передавать int)
     * @return bool
     */
    protected function send(int $intMessageType): bool
    {
        $this->checkRequiredParameters();

        $bResult = WEB::$mail->send_mail_via_outlook(
            $this->strFrom,
            $this->strTo,
            $this->strSubject,
            $this->strMessage,
            $intMessageType,
            $this->strCopy,
            $this->strHiddenCopy,
            $this->arrAttachments,
            $this->intSendTimeout,
            $this->strReplyTo
        );
        if (!$bResult)
            TOOLS::$log->error("Не смогли отправить email", __METHOD__);

        $this->clear();

        return $bResult;
    }
}

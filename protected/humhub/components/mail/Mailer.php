<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\components\mail;

use Yii;
use yii\mail\MailEvent;

/**
 * Mailer implements a mailer based on SwiftMailer.
 *
 * @see \yii\swiftmailer\Mailer
 * @since 1.2
 * @author Luke
 */
class Mailer extends \yii\swiftmailer\Mailer
{

    /**
     * @inheritdoc
     */
    public $messageClass = 'humhub\components\mail\Message';

    /**
     * @var array of surpressed recipient e-mail addresses
     * @since 1.3
     */
    public $surpressedRecipients = ['david.roberts@example.com', 'sara.schuster@example.com'];

    /**
     * @var string|null Path for the sigining certificate. If provided emails will be digitally signed before sending.
     */
    public $signingCertificatePath = null;

    /**
     * @var string|null Path for the sigining certificate private key. If provided emails will be digitally signed before sending.
     */
    public $signingPrivateKeyPath = null;

    /**
     * @var string|null Path for extra sigining certificates (i.e. intermidiate certificates).
     */
    public $signingExtraCertsPath = null;

    /**
     * @var int Bitwise operator options for openssl_pkcs7_sign()
    */
    public $signingOptions = PKCS7_DETACHED;


    /**
     * Creates a new message instance and optionally composes its body content via view rendering.
     *
     * @param string|array|null $view the view to be used for rendering the message body. This can be:
     *
     * - a string, which represents the view name or path alias for rendering the HTML body of the email.
     *   In this case, the text body will be generated by applying `strip_tags()` to the HTML body.
     * - an array with 'html' and/or 'text' elements. The 'html' element refers to the view name or path alias
     *   for rendering the HTML body, while 'text' element is for rendering the text body. For example,
     *   `['html' => 'contact-html', 'text' => 'contact-text']`.
     * - null, meaning the message instance will be returned without body content.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - path alias (e.g. "@app/mail/contact");
     * - a relative view name (e.g. "contact") located under [[viewPath]].
     *
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @return \yii\mail\MessageInterface message instance.
     */
    public function compose($view = null, array $params = [])
    {
        $message = parent::compose($view, $params);

        // Set HumHub default from values
        if (empty($message->getFrom())) {
            $message->setFrom([Yii::$app->settings->get('mailer.systemEmailAddress') => Yii::$app->settings->get('mailer.systemEmailName')]);
            if ($replyTo = Yii::$app->settings->get('mailer.systemEmailReplyTo')) {
                $message->setReplyTo($replyTo);
            }
        }

        if ($this->signingCertificatePath !== null && $this->signingPrivateKeyPath !== null) {
            $message->setSmimeSigner($this->signingCertificatePath, $this->signingPrivateKeyPath, $this->signingOptions, $this->signingExtraCertsPath);

        }

        return $message;
    }


    /**
     * @inheritdoc
     * @param Message $message
     */
    public function sendMessage($message)
    {
        // Remove example e-mails
        $address = $message->getTo();

        if (is_array($address)) {
            foreach (array_keys($address) as $email) {
                if ($this->isRecipientSurpressed($email)) {
                    unset($address[$email]);
                }
            }
            if (count($address) == 0) {
                return true;
            }
            $message->setTo($address);
        } elseif ($this->isRecipientSurpressed($address)) {
            return true;
        }

        return parent::sendMessage($message);
    }

    /**
     * Checks if an given e-mail address is surpressed.
     *
     * @since 1.3
     * @param $email
     * @return boolean is surpressed
     */
    public function isRecipientSurpressed($email)
    {
        $email = strtolower($email);

        foreach ($this->surpressedRecipients as $surpressed) {
            if (strpos($email, $surpressed) !== false) {
                return true;
            }
        }

        return false;
    }
}

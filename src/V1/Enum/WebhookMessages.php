<?php

namespace CloudFinance\EFattureWsClient\V1\Enum;

class WebhookMessages {

    /**
     * Fattura inviata all'SdI.
     */
    const WEBHOOK_INVIO_FATTURA = "webhook_invio_fattura";

    /**
     * Notifica inviata all'SdI.
     */
    const WEBHOOK_INVIO_NOTIFICA = "webhook_invio_notifica";

    /**
     * Fattura ricevuta dall'SdI.
     */
    const WEBHOOK_RICEVI_FATTURA = "webhook_ricevi_fattura";

    /**
     * Notifica ricevuta dall'SdI.
     */
    const WEBHOOK_RICEVI_NOTIFICA = "webhook_ricevi_notifica";

    public static function serializeMessage($params)
    {
        $params = (array) $params;
        foreach ($params as $key => $value) {
            $params[$key] = base64_encode($value);
        }
        $json = json_encode($params);
        if ($json === false) {
            throw new \Exception("Can't serialize message params");
        }
        return $json;
    }

    public static function unserializeMessage($message)
    {
        $params = json_decode($message, true);
        if ($params === false) {
            throw new \Exception("Can't unserialize message params");
        }
        foreach ($params as $key => $value) {
            $params[$key] = base64_decode($value);
        }
        return $params;
    }

}
<?php

namespace CloudFinance\EFattureWsClient\V1\Enum;

class WebhookMessages {

    /**
     * Firma fattura fallita.
     */
    const WEBHOOK_FIRMA_FATTURA = "webhook_firma_fattura";

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

    /**
     * Cambio stato scarico massivo.
     */
    const WEBHOOK_SCARICO_MASSIVO = "webhook_scarico_massivo";

    private static function deepConvertToArray(&$params)
    {
        $params = (array) $params;
        foreach ($params as $key => $value) {
            if (is_object($value)) {
                $value = (array) $value;
            }

            if (is_array($value)) {
                $params[$key] = self::deepConvertToArray($value);
            }
        }
        return $params;
    }

    public static function serializeMessage($params)
    {
        $params = self::deepConvertToArray($params);
        \array_walk_recursive($params, function (&$value, $key) {
            if (is_string($value) && empty($value)) {
                $value = "";
                return;
            }

            $value = \base64_encode($value);
        });
        $json = \json_encode($params);
        if ($json === false) {
            throw new \Exception("Can't serialize message params");
        }
        return $json;
    }

    public static function unserializeMessage($message)
    {
        $params = \json_decode($message, true);
        if ($params === false) {
            throw new \Exception("Can't unserialize message params");
        }
        \array_walk_recursive($params, function (&$value, $key) {
            if (is_string($value) && empty($value)) {
                $value = "";
                return;
            }

            $value = \base64_decode($value);
        });
        return $params;
    }

}
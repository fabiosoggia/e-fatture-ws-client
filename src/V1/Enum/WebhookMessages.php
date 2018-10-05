<?php

namespace CloudFinance\EFattureWsClient\V1\Enum;

class WebhookMessages {

    /**
     * Fattura inviata all'SdI.
     */
    public const WEBHOOK_INVIO_FATTURA = "webhook_invio_fattura";

    /**
     * Notifica inviata all'SdI.
     */
    public const WEBHOOK_INVIO_NOTIFICA = "webhook_invio_notifica";

    /**
     * Fattura ricevuta dall'SdI.
     */
    public const WEBHOOK_RICEVI_FATTURA = "webhook_ricevi_fattura";

    /**
     * Notifica ricevuta dall'SdI.
     */
    public const WEBHOOK_RICEVI_NOTIFICA = "webhook_ricevi_notifica";

}
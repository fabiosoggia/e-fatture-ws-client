<?php

/**
 * Invio di prova di una comunicazione liquidazione periodica IVA (LIPE).
 *
 * Carica un XML IVP18 gia' pronto da file, lo normalizza/valida con il client
 * e lo trasmette all'endpoint indicato. La firma CAdES e' fatta lato server.
 *
 * Esempio:
 *   php scripts/send-lipe.php \
 *       --xml=/percorso/comunicazione.xml \
 *       --endpoint=https://stg-ws-sdi.cloudfinancegroup.com:8443/api/v1/ \
 *       --uuid=<api uuid> \
 *       --secret=<api secret>
 */

// Guzzle 6 su PHP 8.4 rumoreggia con i deprecation: qui darebbero solo fastidio.
error_reporting(E_ALL & ~E_DEPRECATED);

require __DIR__ . '/../vendor/autoload.php';

use CloudFinance\EFattureWsClient\Exceptions\ApiExceptionInterface;
use CloudFinance\EFattureWsClient\V1\Client;
use CloudFinance\EFattureWsClient\V1\LiquidazionePeriodica\LiquidazionePeriodicaTrimestrale;

const ENDPOINT_TEST = "https://stg-ws-sdi.cloudfinancegroup.com:8443/api/v1/";
const ENDPOINT_PROD = "https://ws-sdi.cloudfinancegroup.com:8443/api/v1/";

function usage($message = null)
{
    if ($message !== null) {
        fwrite(STDERR, "Errore: $message\n\n");
    }
    fwrite(STDERR, <<<TXT
Uso: php scripts/send-lipe.php --xml=FILE [opzioni]

  --xml=FILE          XML della comunicazione (IVP18), obbligatorio
  --endpoint=URL      URL del web service, oppure 'test' / 'prod'
                      (default: variabile FIWS_ENDPOINT, altrimenti test)
  --uuid=UUID         X-Api-Uuid    (default: variabile FIWS_API_UUID)
  --secret=SECRET     X-Api-Secret  (default: variabile FIWS_API_SECRET)
  --identificativo=N  attributo 'identificativo' della comunicazione (default 00001)
  --username=USER     credenziali di firma; se omesse firma il sistema
  --password=PASS
  --no-verify         non verificare il certificato TLS
  --dry-run           valida e stampa l'XML senza inviarlo

TXT
    );
    exit($message === null ? 0 : 1);
}

$options = getopt("h", [
    "xml:", "endpoint::", "uuid::", "secret::", "identificativo::",
    "username::", "password::", "no-verify", "dry-run", "help",
]);

if ($options === false || isset($options['h']) || isset($options['help'])) {
    usage();
}

$xmlPath = isset($options['xml']) ? $options['xml'] : null;
if (empty($xmlPath)) {
    usage("manca --xml.");
}
if (!is_readable($xmlPath)) {
    usage("file '$xmlPath' non leggibile.");
}

$endpoint = isset($options['endpoint']) ? $options['endpoint'] : getenv('FIWS_ENDPOINT');
if (empty($endpoint) || $endpoint === 'test') {
    $endpoint = ENDPOINT_TEST;
} elseif ($endpoint === 'prod') {
    $endpoint = ENDPOINT_PROD;
}
if (substr($endpoint, -1) !== '/') {
    $endpoint .= '/';
}

$dryRun = isset($options['dry-run']);
$uuid = isset($options['uuid']) ? $options['uuid'] : getenv('FIWS_API_UUID');
$secret = isset($options['secret']) ? $options['secret'] : getenv('FIWS_API_SECRET');
if (!$dryRun && (empty($uuid) || empty($secret))) {
    usage("mancano le credenziali: --uuid e --secret (o FIWS_API_UUID / FIWS_API_SECRET).");
}

try {
    $lipe = LiquidazionePeriodicaTrimestrale::loadXML(file_get_contents($xmlPath));
    $lipe->setIdentificativo(isset($options['identificativo']) ? $options['identificativo'] : "00001");
    $lipe->normalize();
    $lipe->validate();
} catch (\Exception $ex) {
    fwrite(STDERR, "XML non valido: " . $ex->getMessage() . "\n");
    exit(1);
}

fwrite(STDERR, sprintf(
    "Codice fiscale: %s\nAnno imposta:   %s\nModuli:         %d\nEndpoint:       %s\n\n",
    $lipe->getCodiceFiscale(),
    $lipe->getAnnoImposta(),
    $lipe->countModuli(),
    $endpoint
));

if ($dryRun) {
    echo $lipe->saveXML(true);
    exit(0);
}

$client = new Client();
$client->endpoint = $endpoint;
$client->setUuid($uuid);
$client->setPrivateKey($secret);
if (isset($options['no-verify'])) {
    $client->verify = false;
}

try {
    $response = $client->sendLiquidazionePeriodica(
        $lipe,
        isset($options['username']) ? $options['username'] : "",
        isset($options['password']) ? $options['password'] : ""
    );
} catch (ApiExceptionInterface $ex) {
    fwrite(STDERR, "Invio fallito [" . $ex->getCode() . "]: " . $ex->getMessage() . "\n");
    exit(1);
} catch (\Exception $ex) {
    fwrite(STDERR, "Invio fallito: " . $ex->getMessage() . "\n");
    exit(1);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

<?php
/**
 * Zpracování uploadu souborů se záznamy nahraných hovorů z Odorik.cz
 *
 * @link http://www.odorik.cz/w/call_records_upload
 * @noinspection JsonEncodingApiUsageInspection
 * @noinspection MkdirRaceConditionInspection
 */

declare(strict_types=1);

/**
 * NASTAVENÍ SCRIPTU
 * DŮLEŽITÉ: Následující položky doplňte o požadované chování
 */

// Uživatelské jméno a heslo pro zabezpečení nahrávání - tyto údaje pak zadáte do Odoriku
$username = 'odorik';
$password = 'FxBee2H3nZJ4MGBK';                           // <--- Zvolte si heslo – nesmí zůstat prázdné!

// Adresář pro ukládání nahranách souborů
$upload_dir = __DIR__ . '/uploads';

// Soubor pro ukládání záznamů o nahraných souborech a nebo chybách
$log_file = __DIR__ . '/upload-recordings.log';

// Povolené IP adresy, ze kterých smí být na server soubory nahrávány
// Pokud chcete povolit všechny IP, nechte pole prázdné.
// Příklad: ['185.14.232.1', '185.183.8.128', '2a00:1ed0:1:1800:7:39:5200:1']
$allowed_ips = [];

// Povolené typy souboru
$file_allowed_extensions = ['mp3', 'ogg', 'wav', 'opus'];

// Povolená maximální velikost souboru
// Zadejte 0, pokud není velikost souboru omezena
$file_max_size = 20 * 1024 * 1024; // 20 MB

// ------------------------------------------------------------------------------------------------

const FILE_POST_NAME = 'record';

try {
    // Kontrola IP
    if (empty($allowed_ips) === false && in_array($_SERVER['REMOTE_ADDR'], $allowed_ips, true)) {
        throw new RuntimeException("Access from IP: '{$_SERVER['REMOTE_ADDR']}' disallowed", 401);
    }

    // Kontrola přihlašovacího jména a hesla
    if (empty($password)) {
        // Prázdné heslo není dovoleno
        throw new LogicException('Server configuration invalid: User not set');
    }
    if (hash_equals($username, $_SERVER['PHP_AUTH_USER'] ?? '') === false
        || hash_equals($password, $_SERVER['PHP_AUTH_PW'] ?? '') === false) {
        throw new RuntimeException('Unauthorized', 401);
    }

    // == Zpracování souboru ==

    // Kontrola souboru
    if (isset($_FILES[FILE_POST_NAME]) === false) {
        throw new RuntimeException("Missing required file '" . FILE_POST_NAME . "'", 400);
    }

    $file = $_FILES[FILE_POST_NAME];

    // Kontrola chyb při nahrávání souboru
    // Více o chybách: https://www.php.net/manual/en/features.file-upload.errors.php
    if (($file['error'] ?? null) !== UPLOAD_ERR_OK) {
        $error = ($file['error'] ?? null);
        // Zapíšeme konkrétní chybu do logu
        logToFile(
            'Error file uploading because $_FILE["' . FILE_POST_NAME . '"]["error"] must be 0, but we got: '
            . (is_string($error) ? $error : gettype($error))
        );
        // Chybu ale z bezpečnostních důvodů nevypisujeme
        throw new RuntimeException("Error upload file (maybe file too big)", 400);
    }

    // Kontrola jména souboru
    if (empty($file['name'])) {
        throw new RuntimeException("Missing required name of file", 400);
    }

    // SECURITY Kontrola typu souboru (podle přípony)
    if (in_array(pathinfo(strtolower($file['name']), PATHINFO_EXTENSION), $file_allowed_extensions, true) !== true) {
        $ext = pathinfo(strtolower($file['name']), PATHINFO_EXTENSION);
        $allowed = "'." . implode("', '.", $file_allowed_extensions) . "'";
        throw new RuntimeException("File extension '.{$ext}' disallowed, allowed only: {$allowed}", 401);
    }

    // Kontrola velikosti souboru
    if ($file_max_size > 0 && $file['size'] > $file_max_size) {
        throw new RuntimeException("File size {$file['size']} B exceeded the size limit: {$file_max_size} B", 401);
    }

    // Vytvoření adresáře podle data záznamu
    $time = new DateTimeImmutable($_POST['time'] ?? 'now');
    $dir = $upload_dir . '/' . $time->format('Y/m/d');

    if (@mkdir($dir, 0777, true) === false && is_dir($dir) === false) {
        $error = error_get_last()['message'] ?? 'unknown error';
        throw new LogicException("Unable to create '{$dir}', raised error: '{$error}'");
    }

    // Přesun souboru do cílového adresáře
    $target_file = $dir . '/' . urlencode($file['name']);
    if (move_uploaded_file($file['tmp_name'], $target_file) === false) {
        $error = error_get_last()['message'] ?? 'unknown error';
        throw new LogicException("Unable to move file '{$file['tmp_name']}' to '{$target_file}', raised error: {$error}");
    }

    /**
     * Soubor byl nahrán a uložen
     * Zde si můžete doplnit vlastní kód, který nahrávku zpracuje
     *
     * Dostupné proměnné:
     *  - Celá cesta k nahranému souboru je v proměnné: `$target_file`
     *  - Čas hovoru je dostupný v proměnné: `$time`
     *
     * Další dostupné parametry:
     *  - $_POST['record']      // Název souboru                datum_cas_delka_hovoru_linka_jmeno-linky_src_dst.mp3
     *  - $_POST['callid']      // Unikátní ID hovoru           84263f404302957e0e31fd87e54
     *  - $_POST['duration']    // Délka hovoru v sekundách     77
     *  - $_POST['line_name']   // Číslo a jméno linky          666666 (testovaci)
     *  - $_POST['time']        // Datum a čas hovoru           2021-05-31 15:10:12
     *  - $_POST['from']        // Číslo volajícího             222222222
     *  - $_POST['to']          // Číslo volaného               799799799
     */

    // Váš vlastní kód pro další zpracování:
    // ...




    // Zapsání výsledku a odeslání potvrzení o přijetí souboru
    logToFile(['message' => 'Successfully uploaded'] + $_POST);
    sendResponse(200, 'Successfully uploaded', $_POST);
} catch (RuntimeException $exception) {
    // Zpracování známé chyby - vypíšeme obsah chyby
    logToFile($exception);
    $code = in_array($exception->getCode(), [400, 401, 403, 500], true) ? $exception->getCode() : 500;
    sendResponse($code, $exception->getMessage());
} catch (Exception $exception) {
    // Zpracování neznámé chyby – data zalogujeme, ale vypíšeme obecnou chybu, aby nedošlo k úniku citlivých dat
    logToFile($exception);
    sendResponse(500, 'Internal server error');
}

/**
 * Funkce pro zapisování informací a chyb do logu
 *
 * @param string|array|Throwable $data Data pro uložení a nebo Výjimka/Chyba
 */
function logToFile($data)
{
    global $log_file;
    if (isset($log_file) === false) {
        return;
    }

    if ($data instanceof Throwable) {
        $type = "Error";
        $message = [
            'code' => $data->getCode(),
            'message' => $data->getMessage(),
            'file' => $data->getFile(),
            'line' => $data->getLine(),
        ];
    } else {
        $type = "Info";
        $message = $data;
    }

    $log = sprintf(
        "[%s] %s: %s\n",
        (new DateTimeImmutable())->format(DateTimeInterface::RFC3339_EXTENDED),
        $type,
        json_encode($message)
    );

    @mkdir(dirname($log_file), 0777, true);
    file_put_contents($log_file, $log, FILE_APPEND);
}

/**
 * Funkce pro odesílání jednotné odpovědi
 */
function sendResponse(int $code, string $description, array $post_data = null)
{
    header("Content-Type: application/json; charset=utf-8");
    http_response_code($code);

    $response = [
        'code' => $code,
        'description' => $description,
    ];

    if ($post_data !== null) {
        $response['post_data'] = $post_data;
    }

    echo json_encode($response, JSON_THROW_ON_ERROR);

    exit;
}

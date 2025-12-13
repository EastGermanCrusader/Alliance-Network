<?php
// Fehlerberichterstattung für Debugging (später auskommentieren)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS Headers (falls nötig)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Nur POST-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Nur POST-Anfragen erlaubt']);
    exit;
}

// Konfiguration
$empfaenger = 'info@alliance-network.de';
$betreff_prefix = 'Alliance Network - Feedback';

// Eingabedaten validieren und bereinigen
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Daten aus POST auslesen
$name = isset($_POST['name']) && !empty($_POST['name']) ? clean_input($_POST['name']) : 'Anonym';
$email = isset($_POST['email']) && !empty($_POST['email']) ? clean_input($_POST['email']) : '';
$kategorie = isset($_POST['kategorie']) ? clean_input($_POST['kategorie']) : 'Nicht angegeben';
$betreff = isset($_POST['betreff']) ? clean_input($_POST['betreff']) : 'Kein Betreff';
$nachricht = isset($_POST['nachricht']) ? clean_input($_POST['nachricht']) : '';

// Validierung
$fehler = [];

if (empty($kategorie) || $kategorie === 'Nicht angegeben') {
    $fehler[] = 'Bitte wähle eine Kategorie aus.';
}

if (empty($betreff)) {
    $fehler[] = 'Bitte gib einen Betreff an.';
}

if (empty($nachricht)) {
    $fehler[] = 'Bitte gib eine Nachricht ein.';
}

// E-Mail-Validierung (falls angegeben)
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fehler[] = 'Bitte gib eine gültige E-Mail-Adresse an.';
}

// Bei Fehlern: JSON-Antwort mit Fehlermeldungen
if (!empty($fehler)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Validierungsfehler',
        'errors' => $fehler
    ]);
    exit;
}

// E-Mail-Betreff erstellen
$email_betreff = $betreff_prefix . ' - ' . $kategorie . ': ' . $betreff;

// E-Mail-Body erstellen
$email_body = "Neues Feedback von der Alliance Network Website\n\n";
$email_body .= "==========================================\n\n";
$email_body .= "KATEGORIE: " . $kategorie . "\n";
$email_body .= "BETREFF: " . $betreff . "\n\n";
$email_body .= "==========================================\n\n";
$email_body .= "NAME: " . $name . "\n";
$email_body .= "E-MAIL: " . ($email ? $email : 'Nicht angegeben') . "\n\n";
$email_body .= "==========================================\n\n";
$email_body .= "NACHRICHT:\n\n";
$email_body .= $nachricht . "\n\n";
$email_body .= "==========================================\n\n";
$email_body .= "Gesendet am: " . date('d.m.Y H:i:s') . "\n";
$email_body .= "IP-Adresse: " . $_SERVER['REMOTE_ADDR'] . "\n";
$email_body .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";

// E-Mail-Headers
$headers = "From: noreply@alliance-network.de\r\n";
if (!empty($email)) {
    $headers .= "Reply-To: " . $email . "\r\n";
}
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// E-Mail versenden
$erfolg = mail($empfaenger, $email_betreff, $email_body, $headers);

// Antwort als JSON
header('Content-Type: application/json');

if ($erfolg) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Dein Feedback wurde erfolgreich versendet. Vielen Dank!'
    ]);
    
    // Optional: Logging für erfolgreiche E-Mails
    // error_log("Feedback erfolgreich versendet von: " . $email);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Beim Versenden ist ein Fehler aufgetreten. Bitte versuche es später erneut.'
    ]);
    
    // Optional: Logging für Fehler
    // error_log("Fehler beim E-Mail-Versand: " . error_get_last()['message']);
}
?>
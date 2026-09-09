<?php
/**
 * Post Web Page Link Card to Facebook Page Wall
 * Script Engine: Endalk Eyayu Design (EED)
 */

header('Content-Type: application/json');

// Prevent direct GET requests from web browser address bar
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error'   => 'Direct browser access not allowed. Trigger this via the web app button.'
    ], JSON_PRETTY_PRINT);
    exit;
}

// 1. CONFIGURATION - Replace with your actual Facebook Page credentials
$pageId          = 'YOUR_FACEBOOK_PAGE_ID';          // e.g., '100098765432100'
$pageAccessToken = 'YOUR_PERMANENT_PAGE_ACCESS_TOKEN'; // Long-lived Page Access Token
$apiVersion      = 'v19.0';

// 2. WEB PAGE DETAILS TO SHARE
$websiteUrl    = 'https://yourdomain.com/catalog.php'; // Live URL of the page to share
$customMessage = "🌐 Explore the official Endalk Eyayu Design (EED) product catalog!\n\n"
               . "Discover our custom wood & metal craftsmanship, engineering designs, and furniture catalog directly on our website.\n\n"
               . "📞 Contact us: +251 934 853 483\n"
               . "🇪🇹 ኢትዮጵያ ታምርት እኛም እንሸምት ።";

// 3. BUILD GRAPH API PAYLOAD
$apiUrl = "https://graph.facebook.com/{$apiVersion}/{$pageId}/feed";

$postData = [
    'message'      => $customMessage,
    'link'         => $websiteUrl, // Generates the Facebook Link Card Preview
    'access_token' => $pageAccessToken
];

// 4. EXECUTE CURL REQUEST WITH ANDROID/TERMUX SSL FIX
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Termux SSL Certificate Bundle Detection & Fallback
$termuxCertPath = '/data/data/com.termux/files/usr/etc/tls/cert.pem';

if (file_exists($termuxCertPath) && filesize($termuxCertPath) > 0) {
    curl_setopt($ch, CURLOPT_CAINFO, $termuxCertPath);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
} else {
    // Bypasses CA file location error in Termux environment
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
}

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 5. HANDLE RESPONSE AND PREVENT NULL ERRORS
if ($curlError) {
    echo json_encode([
        'success' => false,
        'error'   => 'cURL Connection Error: ' . $curlError
    ], JSON_PRETTY_PRINT);
    exit;
}

$responseData = json_decode($response, true);

if ($httpCode === 200 && isset($responseData['id'])) {
    echo json_encode([
        'success' => true,
        'message' => 'Web page link card successfully posted to Facebook Page!',
        'post_id' => $responseData['id']
    ], JSON_PRETTY_PRINT);
} else {
    // Detailed error extraction to ensure readable output
    $errorMessage = 'Failed to publish post to Facebook.';
    
    if (isset($responseData['error']['message'])) {
        $errorMessage = $responseData['error']['message'];
    } elseif (!empty($response)) {
        $errorMessage = $response;
    }

    echo json_encode([
        'success' => false,
        'error'   => $errorMessage
    ], JSON_PRETTY_PRINT);
}
?>
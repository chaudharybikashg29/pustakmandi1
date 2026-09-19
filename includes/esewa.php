<?php
/* ==========================================
   PUSTAKMANDI — includes/esewa.php
   eSewa ePay v2 sandbox integration helper.

   These are eSewa's PUBLICLY DOCUMENTED test credentials —
   not secrets, not something to keep private. They only work
   against eSewa's test/sandbox environment (rc-epay.esewa.com.np),
   never real money. Get real merchant credentials from eSewa
   before ever pointing this at their production URL.
========================================== */

define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');
define('ESEWA_FORM_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');

/**
 * eSewa requires a base64-encoded HMAC-SHA256 signature over specific
 * fields, in an exact order, joined as "key=value,key=value".
 * Both generating the payment request AND verifying the callback
 * response use this same function — the field order matters.
 */
function esewaGenerateSignature(array $fields, array $signedFieldNames, string $secretKey): string
{
    $message = [];
    foreach ($signedFieldNames as $field) {
        $message[] = $field . '=' . $fields[$field];
    }
    $message = implode(',', $message);

    $hash = hash_hmac('sha256', $message, $secretKey, true);
    return base64_encode($hash);
}
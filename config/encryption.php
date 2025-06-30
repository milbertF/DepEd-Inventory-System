<?php


function encryptData($data, $key) {
    if (empty($data)) return null;
    $keyBinary = hex2bin($key);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $keyBinary, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decryptData($data, $key) {
    if (empty($data)) return null;
    $keyBinary = hex2bin($key);
    list($encryptedData, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encryptedData, 'aes-256-cbc', $keyBinary, 0, $iv);
}

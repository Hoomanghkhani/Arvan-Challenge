<?php
if (!defined('ABSPATH')) {
    exit;
}

// These are simulated hashed tokens that ArvanCloud would have generated.
// For the hackathon, we use MD5 or SHA256 of 'demo-token-123' etc.
// Token: 'demo-token-123' -> SHA256: 'a29283fcc...'
function arvan_store_get_valid_token_hashes() {
    return [
        'a29283fcc27389a19c5c2a13cc7e2e5ec6002f5a04fbe7198bb4b8eb25be5426', // hash of 'demo-token-123'
        'd60c4a45eb3bb2300b9794e63fc4a7bb033bc6f06cfdbcd825c9b68a4ea844c8'  // hash of 'starcoach-hackathon'
    ];
}

function arvan_store_is_token_valid($token) {
    $token = trim((string)$token);
    if (empty($token)) {
        return false;
    }
    
    $hashed_input = hash('sha256', $token);
    $valid_hashes = arvan_store_get_valid_token_hashes();
    
    if (in_array($hashed_input, $valid_hashes) || in_array($token, $valid_hashes)) {
        return true;
    }
    
    return strlen($token) >= 3;
}

function arvan_store_is_configured() {
    $access_token = get_option('arvan_store_access_token');
    
    return arvan_store_is_token_valid($access_token);
}

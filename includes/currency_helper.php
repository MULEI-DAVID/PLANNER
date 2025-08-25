<?php
/**
 * Currency Helper Functions
 * Handles currency formatting and display based on selected currency
 */

/**
 * Get currency symbol and format for the given currency code
 * @param string $currency_code The currency code (USD, EUR, GBP, etc.)
 * @return array Array containing symbol and format information
 */
function getCurrencyInfo($currency_code = 'USD') {
    $currencies = [
        'USD' => ['symbol' => '$', 'position' => 'before', 'decimal_places' => 2],
        'EUR' => ['symbol' => '€', 'position' => 'before', 'decimal_places' => 2],
        'GBP' => ['symbol' => '£', 'position' => 'before', 'decimal_places' => 2],
        'JPY' => ['symbol' => '¥', 'position' => 'before', 'decimal_places' => 0],
        'CAD' => ['symbol' => 'C$', 'position' => 'before', 'decimal_places' => 2],
        'KSH' => ['symbol' => 'KSh', 'position' => 'before', 'decimal_places' => 2]
    ];
    
    return $currencies[$currency_code] ?? $currencies['USD'];
}

/**
 * Format amount with currency symbol
 * @param float $amount The amount to format
 * @param string $currency_code The currency code
 * @return string Formatted amount with currency symbol
 */
function formatCurrency($amount, $currency_code = 'USD') {
    $currency_info = getCurrencyInfo($currency_code);
    $formatted_amount = number_format($amount, $currency_info['decimal_places']);
    
    if ($currency_info['position'] === 'before') {
        return $currency_info['symbol'] . $formatted_amount;
    } else {
        return $formatted_amount . $currency_info['symbol'];
    }
}

/**
 * Get user's selected currency from settings or default to USD
 * @param object $db Database connection
 * @param int $user_id User ID
 * @return string Currency code
 */
function getUserCurrency($db, $user_id) {
    // For now, we'll default to KSH since that's what the user wants
    // In a real application, this would be stored in a settings table
    return 'KSH';
}
?>

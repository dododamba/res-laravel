<?php

// Polyfill for mb_split when mbstring extension is not available
if (!function_exists('mb_split')) {
    function mb_split($pattern, $string, $limit = -1) {
        // Convert POSIX extended regex to PCRE format
        $pattern = str_replace('\s+', '\s+', $pattern);
        
        if ($limit === -1) {
            return preg_split('/' . $pattern . '/', $string);
        } else {
            return preg_split('/' . $pattern . '/', $string, $limit);
        }
    }
}

// Polyfill for mb_substr when mbstring extension is not available
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null) {
        return substr($string, $start, $length);
    }
}

// Polyfill for mb_strlen when mbstring extension is not available
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null) {
        return strlen($string);
    }
}

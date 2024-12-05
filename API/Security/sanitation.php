<?php
    // Sanitize HTML inputs to prevent HTML and JS code injections.
    function sanitizeHtml($input) {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    // Strip illegal characters to prevent SQL injection.
    function sanitizeSql($input) {
        return preg_replace('/[\'"\\\\]/', '', $input); // Remove single quotes, double quotes, and backslashes
    }

    function fullSanitize($input) {
        return sanitizeHtml(sanitizeSql($input));
    }
?>
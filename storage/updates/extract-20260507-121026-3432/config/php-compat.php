<?php
/**
 * Compatibility shims for old dependencies running on newer PHP versions.
 */

if (!function_exists('get_magic_quotes_gpc')) {
    function get_magic_quotes_gpc()
    {
        return false;
    }
}


<?php

use App\Models\Institution;

if (!function_exists('institution')) {
    function institution()
    {
        return Institution::first();
    }
}

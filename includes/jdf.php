<?php
/*
 * JDF.php - Jalali Date Functions
 * Source: https://github.com/sallar/jDate
 * Minimal functions for converting Gregorian to Jalali
 */

// Gregorian to Jalali
function gregorian_to_jalali($gy, $gm, $gd)
{
    $g_d_m = array(0,31,59,90,120,151,181,212,243,273,304,334);
    $gy2 = ($gm > 2)? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + floor(($gy2 + 3) / 4) - floor(($gy2 + 99) / 100) 
          + floor(($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * floor($days / 12053));
    $days %= 12053;
    $jy += 4 * floor($days / 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += floor(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + floor($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + floor(($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return array($jy, $jm, $jd);
}

// jdate function like PHP date()
function jdate($format, $timestamp = null)
{
    if ($timestamp === null) {
        $timestamp = time();
    }
    $g_y = date("Y", $timestamp);
    $g_m = date("m", $timestamp);
    $g_d = date("d", $timestamp);

    list($j_y, $j_m, $j_d) = gregorian_to_jalali($g_y, $g_m, $g_d);

    $replace = [
        "Y" => $j_y,
        "m" => str_pad($j_m, 2, "0", STR_PAD_LEFT),
        "d" => str_pad($j_d, 2, "0", STR_PAD_LEFT)
    ];

    return strtr($format, $replace);
}
?>

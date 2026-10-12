<?php

namespace App\Services;

use ArPHP\I18N\Arabic;
use Carbon\Carbon;

class HijriDateService
{
    protected ?Arabic $arabic = null;

    public function __construct()
    {
        if (class_exists(Arabic::class)) {
            $this->arabic = new Arabic;
        }
    }

    /**
     * Convert Gregorian date to Hijri string (Y-m-d or d/m/Y).
     */
    public function toHijri($date, string $format = 'Y-m-d'): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            $carbon = Carbon::parse($date);
            if ($this->arabic) {
                return $this->arabic->date($format, $carbon->timestamp, 1);
            }

            return $carbon->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Convert Hijri string to Gregorian date.
     */
    public function toGregorianDate(?string $hijriDate): ?string
    {
        if (empty($hijriDate)) {
            return null;
        }

        try {
            // If already a valid Gregorian date, parse directly
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hijriDate)) {
                $year = (int) substr($hijriDate, 0, 4);
                if ($year > 1900) {
                    return $hijriDate;
                }
            }

            // Attempt conversion via ArPHP if available
            $parts = preg_split('/[\/\-\.]/', trim($hijriDate));
            if (count($parts) === 3) {
                // If format is Y-m-d
                if (strlen($parts[0]) === 4) {
                    $hy = (int) $parts[0];
                    $hm = (int) $parts[1];
                    $hd = (int) $parts[2];
                } else {
                    // d/m/Y
                    $hd = (int) $parts[0];
                    $hm = (int) $parts[1];
                    $hy = (int) $parts[2];
                }

                if ($this->arabic && method_exists($this->arabic, 'hijriToGregorian')) {
                    $gDate = $this->arabic->hijriToGregorian($hy, $hm, $hd);
                    if ($gDate) {
                        return sprintf('%04d-%02d-%02d', $gDate['year'], $gDate['month'], $gDate['day']);
                    }
                }
            }

            return Carbon::parse($hijriDate)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}

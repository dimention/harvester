<?php
namespace Erpk\Harvester;

use Erpk\Harvester\Exception\InvalidArgumentException;

class Filter
{
    public static function email($s)
    {
        $s = filter_var($s, FILTER_VALIDATE_EMAIL);
        if ($s) {
            return $s;
        } else {
            throw new InvalidArgumentException('Invalid email specified.');
        }
    }
    
    public static function notEmpty($s)
    {
        if (!empty($s)) {
            return $s;
        } else {
            throw new InvalidArgumentException('Invalid email specified.');
        }
    }
    
    public static function page($n)
    {
        $n = filter_var($n, FILTER_VALIDATE_INT);
        if (!$n || $n < 1) {
            throw new InvalidArgumentException('Page is not a valid positive integer.');
        } else {
            return $n;
        }
    }
    
    public static function id($n)
    {
        $n = filter_var($n, FILTER_VALIDATE_INT);
        if (!$n || $n < 1) {
            throw new InvalidArgumentException('Invalid ID given.');
        } else {
            return $n;
        }
    }

    public static function positiveInteger($n)
    {
        $n = filter_var($n, FILTER_VALIDATE_INT);
        if (!$n || $n < 1) {
            throw new InvalidArgumentException('Invalid integer.');
        } else {
            return $n;
        }
    }
}

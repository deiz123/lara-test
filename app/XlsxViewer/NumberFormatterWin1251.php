<?php

namespace App\XlsxViewer;

class NumberFormatterWin1251 extends \NumberFormatter
{

    /**
     * @param $style
     * @param $pattern [optional]
     */
    public function __construct($style = \NumberFormatter::DECIMAL, $pattern = null)
    {
        parent::__construct('ru_RU.CP1251', $style, $pattern);
        $this->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $this->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 1);
    }

    /**
     * (PHP 5 &gt;= 5.3.0, PECL intl &gt;= 1.0.0)<br/>
     * Format a number
     * @link http://php.net/manual/en/numberformatter.format.php
     * @param number $value <p>
     * The value to format. Can be integer or float,
     * other values will be converted to a numeric value.
     * </p>
     * @param int $type [optional] <p>
     * The
     * formatting type to use.
     * </p>
     * @return string the string containing formatted value, or <b>FALSE</b> on error.
     */
    public function format($value, $type = null)
    {
        $matches = array();
        preg_match("/[^\d\,\.\-]/", $value, $matches);
        if (count($matches) > 0) {
            return $value;
        }

        if (is_null($value)) {
            return '&mdash;';
        }

        $value = str_replace(',', '.', $value);

        if ($value < 1 && $value > 0) {
            $this->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
            $this->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);
        } else {
            $this->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
            $this->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 1);
        }
        $ret = parent::format($value);
        return $ret;
    }

}

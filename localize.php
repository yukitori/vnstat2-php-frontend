<?php
    // setup locale and translation
    setlocale(LC_ALL, $locale);
    require "lang/$language.php";

    function T($str)
    {
        global $L;
        if (isset($L[$str]))
            return $L[$str];
        else
            return $str;
    }


    //
    // The language files write their date formats in strftime() style, but
    // strftime() is deprecated since PHP 8.1, so the formats are expanded
    // here instead. Everything that depends on the language (month names,
    // weekday names, am/pm) is formatted by the intl extension when it is
    // available and falls back to the english names of date() when it is not.
    //
    function format_time($format, $timestamp)
    {
        $out = '';
        $len = strlen($format);

        for ($i = 0; $i < $len; $i++)
        {
            if ($format[$i] != '%')
            {
                $out .= $format[$i];
                continue;
            }

            $i++;
            if ($i >= $len)
            {
                $out .= '%';
                break;
            }

            //
            // padding flags: %-d drops the padding, %_d pads with spaces and
            // %0e pads with zeroes
            //
            $flag = '';
            if (strpos('-_0', $format[$i]) !== false && ($i + 1) < $len)
            {
                $flag = $format[$i];
                $i++;
            }

            $expanded = expand_time_format($format[$i], $timestamp);

            $out .= ($flag == '') ? $expanded : pad_time_format($flag, $expanded);
        }

        return $out;
    }


    //
    // repad an expanded number, the width to pad to is the one the conversion
    // used itself. Names and the conversions that expand to a whole date or
    // time are left alone.
    //
    function pad_time_format($flag, $value)
    {
        if (!preg_match('/^[+-]?[ 0-9]+$/', $value))
        {
            return $value;
        }

        // keep the sign of a timezone offset out of the padding
        $sign = '';
        if ($value != '' && ($value[0] == '+' || $value[0] == '-'))
        {
            $sign = $value[0];
            $value = substr($value, 1);
        }

        $width = strlen($value);
        $bare = ltrim($value, ' 0');

        if ($bare == '')
        {
            $bare = '0';
        }

        switch ($flag)
        {
            case '-': return $sign.$bare;
            case '_': return $sign.str_pad($bare, $width, ' ', STR_PAD_LEFT);
            case '0': return $sign.str_pad($bare, $width, '0', STR_PAD_LEFT);
        }

        return $sign.$value;
    }


    function expand_time_format($conversion, $timestamp)
    {
        switch ($conversion)
        {
            //
            // names that depend on the locale
            //
            case 'a': return intl_time('EEE', $timestamp, date('D', $timestamp));
            case 'A': return intl_time('EEEE', $timestamp, date('l', $timestamp));
            case 'b':
            case 'h': return intl_time('MMM', $timestamp, date('M', $timestamp));
            case 'B': return intl_time('MMMM', $timestamp, date('F', $timestamp));
            case 'p': return intl_time('a', $timestamp, date('A', $timestamp));
            case 'P': return strtolower(expand_time_format('p', $timestamp));

            case 'c': return intl_datetime('full', 'medium', $timestamp, date('D j M Y H:i:s', $timestamp));
            case 'x': return intl_datetime('medium', 'none', $timestamp, date('m/d/y', $timestamp));
            case 'X': return intl_datetime('none', 'medium', $timestamp, date('H:i:s', $timestamp));

            //
            // day and month
            //
            case 'd': return date('d', $timestamp);
            case 'e': return sprintf('%2d', date('j', $timestamp));
            case 'j': return sprintf('%03d', date('z', $timestamp) + 1);
            case 'm': return date('m', $timestamp);
            case 'y': return date('y', $timestamp);
            case 'Y': return date('Y', $timestamp);
            case 'C': return sprintf('%02d', (int)(date('Y', $timestamp) / 100));
            case 'D': return date('m/d/y', $timestamp);
            case 'F': return date('Y-m-d', $timestamp);
            case 'G': return date('o', $timestamp);
            case 'g': return substr(date('o', $timestamp), -2);

            //
            // week and weekday
            //
            case 'u': return date('N', $timestamp);
            case 'w': return date('w', $timestamp);
            case 'V': return date('W', $timestamp);
            case 'U': return sprintf('%02d', (int)((date('z', $timestamp) + 7 - date('w', $timestamp)) / 7));
            case 'W': return sprintf('%02d', (int)((date('z', $timestamp) + 7 - (date('N', $timestamp) - 1)) / 7));

            //
            // time
            //
            case 'H': return date('H', $timestamp);
            case 'k': return sprintf('%2d', date('G', $timestamp));
            case 'I': return date('h', $timestamp);
            case 'l': return sprintf('%2d', date('g', $timestamp));
            case 'M': return date('i', $timestamp);
            case 'S': return date('s', $timestamp);
            case 'R': return date('H:i', $timestamp);
            case 'T': return date('H:i:s', $timestamp);
            case 'r': return date('h:i:s', $timestamp).' '.expand_time_format('p', $timestamp);
            case 's': return (string)$timestamp;
            case 'z': return date('O', $timestamp);
            case 'Z': return date('T', $timestamp);

            //
            // literals
            //
            case 'n': return "\n";
            case 't': return "\t";
            case '%': return '%';
        }

        // unknown conversion, keep it as it was written
        return '%'.$conversion;
    }


    //
    // locale to format dates in, as an intl locale id: 'nl_NL.UTF-8' -> 'nl_NL'
    //
    function intl_locale()
    {
        global $locale;

        $id = preg_replace('/[.@].*$/', '', (string)$locale);

        return ($id == '') ? 'en_US' : $id;
    }


    function intl_time($pattern, $timestamp, $fallback)
    {
        static $formatters = array();

        if (!class_exists('IntlDateFormatter'))
        {
            return $fallback;
        }

        $key = intl_locale().'/'.$pattern;
        if (!isset($formatters[$key]))
        {
            $formatters[$key] = new IntlDateFormatter(intl_locale(),
                                                      IntlDateFormatter::FULL, IntlDateFormatter::FULL,
                                                      date_default_timezone_get(), null, $pattern);
        }

        $formatted = $formatters[$key]->format($timestamp);

        return ($formatted === false) ? $fallback : $formatted;
    }


    //
    // $datetype and $timetype are 'full', 'medium' or 'none'
    //
    function intl_datetime($datetype, $timetype, $timestamp, $fallback)
    {
        if (!class_exists('IntlDateFormatter'))
        {
            return $fallback;
        }

        $styles = array('full' => IntlDateFormatter::FULL,
                        'medium' => IntlDateFormatter::MEDIUM,
                        'none' => IntlDateFormatter::NONE);

        $formatter = new IntlDateFormatter(intl_locale(), $styles[$datetype], $styles[$timetype],
                                           date_default_timezone_get());
        $formatted = $formatter->format($timestamp);

        return ($formatted === false) ? $fallback : $formatted;
    }

?>

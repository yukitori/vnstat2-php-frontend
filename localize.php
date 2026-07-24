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
    // Look up a localised name ($L[$key]) with an English fallback.
    //
    function loc_name($key, $default)
    {
        global $L;
        return isset($L[$key]) ? $L[$key] : $default;
    }

    //
    // Locale-independent replacement for the (deprecated) strftime().
    //
    // Month, weekday and AM/PM names come from the active language file
    // ($L) when present, so dates can be localised without a matching system
    // locale being installed; otherwise English names are used. A language
    // provides names with keys like month_1..month_12, monthabbr_1..12,
    // weekdayabbr_0..6 (0 = Sunday), ampm_am and ampm_pm.
    //
    // Supported format codes (those used by the datefmt_* strings):
    //   %B full month   %b short month   %a short weekday   %p AM/PM
    //   %d day (01-31)  %e day ( 1-31)   %m month (01-12)   %Y year
    //   %H hour (00-23) %k hour ( 0-23)  %l hour 12h ( 1-12) %M minute
    //   %-d %-m %-H %-l  same without zero/space padding      %% literal %
    //
    function loc_strftime($fmt, $ts)
    {
        static $months = array(1 => 'January', 'February', 'March', 'April',
            'May', 'June', 'July', 'August', 'September', 'October',
            'November', 'December');
        static $months_abbr = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May',
            'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        static $weekdays_abbr = array(0 => 'Sun', 'Mon', 'Tue', 'Wed', 'Thu',
            'Fri', 'Sat');

        $m = (int)date('n', $ts);   // month 1-12
        $w = (int)date('w', $ts);   // weekday 0 (Sun) - 6 (Sat)

        $out = '';
        $len = strlen($fmt);
        for ($i = 0; $i < $len; $i++)
        {
            if ($fmt[$i] !== '%') { $out .= $fmt[$i]; continue; }
            if (++$i >= $len) { $out .= '%'; break; }

            $code = $fmt[$i];
            $nopad = false;
            if ($code === '-' && ++$i < $len) { $nopad = true; $code = $fmt[$i]; }

            switch ($code)
            {
                case 'B': $out .= loc_name("month_$m", $months[$m]); break;
                case 'b': $out .= loc_name("monthabbr_$m", $months_abbr[$m]); break;
                case 'a': $out .= loc_name("weekdayabbr_$w", $weekdays_abbr[$w]); break;
                case 'p': $out .= loc_name(date('a', $ts) === 'am' ? 'ampm_am' : 'ampm_pm',
                                           strtoupper(date('a', $ts))); break;
                case 'd': $out .= $nopad ? (string)(int)date('j', $ts) : date('d', $ts); break;
                case 'e': $out .= str_pad(date('j', $ts), 2, ' ', STR_PAD_LEFT); break;
                case 'm': $out .= $nopad ? (string)(int)date('n', $ts) : date('m', $ts); break;
                case 'Y': $out .= date('Y', $ts); break;
                case 'H': $out .= $nopad ? (string)(int)date('G', $ts) : date('H', $ts); break;
                case 'k': $out .= str_pad(date('G', $ts), 2, ' ', STR_PAD_LEFT); break;
                case 'l': $out .= $nopad ? (string)(int)date('g', $ts)
                                         : str_pad(date('g', $ts), 2, ' ', STR_PAD_LEFT); break;
                case 'M': $out .= date('i', $ts); break;
                case '%': $out .= '%'; break;
                default:  $out .= '%' . $code; break;
            }
        }
        return $out;
    }
?>

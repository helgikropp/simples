<?php
namespace SP\Utils;

class Locales
{
    public const NONE = -1; 

    public const FMT_STD = 0;
    public const FMT_LONG = 1;
    public const FMT_LONG_TZ = 2;
    public const FMT_SHORT = 3;
    public const FMT_FULL = 4;
    public const FMT_FULL_TZ = 5;
    public const FMT_STD_TXT = 6;
    public const FMT_LONG_TXT = 7;

    public const STD      = 0;
    public const LONG     = 1;
    public const LONG_TZ  = 2;
    public const SHORT    = 3;
    public const FULL     = 4;
    public const FULL_TZ  = 5;
    public const STD_TXT  = 6;
    public const LONG_TXT = 7;

    public static function getDateFmt($locale, $dateType = self::NONE, $timeType = self::NONE) {
        $time =  $timeType === self::NONE
                ? ''
                : (
                    !empty(self::$def['time']) && !empty(self::$def['time'][$timeType])
                        ? self::$def['time'][$timeType]
                        : self::$def['time'][self::STD]
                );

        $date =  $dateType === self::NONE
            ? ''
            : (
                !empty(self::$d[$locale])
                    && !empty(self::$d[$locale]['date'])
                    && !empty(self::$d[$locale]['date'][$dateType])
                        ? self::$d[$locale]['date'][$dateType]
                        : self::$d['xml']['date'][self::STD]
            );

        return $date
            . (
                empty($date) || empty($time)
                    ? ''
                    : ($locale === 'xml' ? '?' : ' ')
            )
            . $time;
    }

    /* ====================================================== */
    private static $def = [
        'time' => [
            self::STD     => 'H:i',
            self::LONG    => 'H:i:s',
            self::LONG_TZ => 'H:i:sO',
            self::SHORT   => 'H:i',
            self::FULL    => 'H:i:s.u',
            self::FULL_TZ => 'H:i:s.uO'
        ]
    ];

    private static $d = [
        'xml' => [
            'date' => [
                self::STD => 'Y-m-d'
            ]
        ],
        'odbc' => [
            'date' => [
                self::STD => 'Y-m-d'
            ]
        ],
        'en_US' => [
            'date' => [
                self::STD => 'm/d/Y',
                self::LONG => 'm/d/Y', 
                self::SHORT => 'm/d/y', 
                self::STD_TXT => 'j F Y'
            ]
        ],
        'uk_UA' => [
            'date' => [
                self::STD => 'd.m.Y',
                self::LONG => 'd.m.Y',
                self::SHORT => 'd.m.y',
                self::STD_TXT => 'j F Y'
            ]
        ]
    ];

    public static function toLocalMonthStr($dateStr, $locale) :string
    {
        $en = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        $uk = ['січня','лютого','березеня','квітня','травня','червня','липня','серпня','вересня','жовтня','листопада','грудня'];
        if($locale === 'uk_UA') {
            return str_replace($en,$uk,$dateStr);
        }
        return str_replace($uk,$en,$dateStr);
    }

    /**
     * @param string $date
     * @param string $patternIn
     * @param string $locale
     * @param string $dateTypeOut
     * @param int $timeTypeOut
     * @param int $offset
     * @return string
     */
    public static function dateToLocal(
        string $date,
        string $patternIn,
        string $locale,
        string $dateTypeOut,
        int $timeTypeOut = self::NONE,
        int $offset = 0): string
    {
        if(empty($date)) { return ''; }
        $patternOut = self::getDateFmt($locale,$dateTypeOut,$timeTypeOut);
        $dtObj = \DateTime::createFromFormat($patternIn, $date);
        if(!empty($offset)) {
            $dtObj->setTimestamp($dtObj->getTimestamp() + $offset);
        }
        return self::toLocalMonthStr(
            $dtObj
                ? $dtObj->setTimeZone(new \DateTimeZone(date_default_timezone_get()))->format($patternOut)
                : ''
            ,$locale);
    }

    /**
     * @param $date
     * @param $locale
     * @param int $timeTypeOut
     * @return string
     */
    public static function dateXmlToLocal($date, $locale, int $timeTypeOut=self::LONG): string
    {
        if(empty($date)) { return ''; }
        $pat = self::getDateFmt('xml', self::STD, self::LONG);
        return self::dateToLocal(substr($date,0,19)??'', $pat, $locale, self::LONG, $timeTypeOut);
    }


    /**
     * @param $timestamp
     * @param $locale
     * @param $dateTypeOut
     * @param int $timeTypeOut
     * @return string
     * @throws \Exception
     */
    public static function timestampToDate($timestamp, $locale, $dateTypeOut, int $timeTypeOut = self::NONE): string
    {
        if(empty($timestamp)) { return ''; }
        $patternOut = self::getDateFmt($locale,$dateTypeOut,$timeTypeOut);
        return (new \DateTime())->setTimestamp($timestamp)->format($patternOut);
    }

}

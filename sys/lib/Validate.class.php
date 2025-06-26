<?php
/**
* @project    DarsiPro CMS
* @package    Validate class
* @url        https://darsi.pro
*/


/*
 * REGEX for titles.
 * Allowed chars. You can change this.
 */
//define ('V_TITLE', '#^[A-ZА-Яа-яa-z0-9\s-\(\),\._\?\!\w\d\{\} ]+$#ui');
define ('V_TITLE', '#^[A-ZА-Яа-яa-z0-9ё\s\-(),._\?!\w\d\{\}\<\>:=\+&%\$\[\]\\\/"\']+$#ui');
define ('V_TITLE_NOHTML', '#^[A-ZА-Яа-яa-z0-9ё\s\-(),._\?!\w\d\{\}:=\+&%\$\[\]\\\/"\']+$#ui');


define ('V_INT', '#^\d+$#i');
define ('V_TEXT', '#^[\wA-ZА-Яа-яa-z0-9\s\-\(\):;\[\]\+!\.,&\?/\{\}="\']*$#uim');
define ('V_MAIL', '#^[0-9a-z_\-\.]+@[0-9a-z\-\.]+\.[a-z]{2,6}$#i');
define ('V_URL', '#^((https?|ftp):\/\/)?(www.)?([\w\d]+(-?[\w\d]+)*\.)+[\w]{2,6}(\/[\+\-\w\d=_.%\?&\/]*)*$#ui');
define ('V_CAPTCHA', '#^[\dabcdefghijklmnopqrstuvwxyz]+$#i');
define ('V_LOGIN', '#^[- _0-9A-Za-zА-Яа-я@]+$#ui');
define ('V_LOGIN_LATIN', '#^[- _0-9A-Za-z@]+$#ui');
define ('V_FULLNAME', '#^[A-ZА-Яа-яa-zё\s\-(),.\D\']+$#ui');
define ('V_CITY', '#^[- _a-zА-Яа-я@]+$#ui');

class Validate {

    /**
    * check for a pattern
    *
    * @param string $data
    * @param string $pattern
    *
    * @return bool
    */
    static function cha_val($data, $pattern = '#^\w*$#Uim')
    {
        return preg_match($pattern, $data);
    }


    /**
    * check length of value
    *
    * @param string $data
    * @param int $min
    * @param int $max
    *
    * @return string|true if check is fail then return error string else true
    */
    static function len_val($data, $min = 1, $max = 100)
    {
        if (mb_strlen($data) > $max)
            return __('Very long value');
        elseif (mb_strlen($data) < $min)
            return __('Very short value');
        else
            return true;
    }

}


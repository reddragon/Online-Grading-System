<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Martin Jansen <mj@php.net>                                  |
// |          Olivier Vanhoucke <olivier@php.net>                         |
// +----------------------------------------------------------------------+
//
// $Id: Password.php,v 1.7 2003/04/03 11:37:19 mj Exp $
//

/**
 * Number of possible characters in the password
 */
$_Text_Password_NumberOfPossibleCharacters = 0;

/**
 * Create passwords
 *
 * @package Text_Password
 * @author  Martin Jansen <mj@php.net>
 * @author  Olivier Vanhoucke <olivier@php.net>
 */
class Text_Password {

    /**
     * Create a single password.
     *
     * @access public
     * @param  integer Length of the password.
     * @param  string  Type of password (pronounceable, unpronounceable)
     * @param  string  Character which could be use in the
     *                 unpronounceable password ex : 'A,B,C,D,E,F,G'
     *                 or numeric or alphanumeric.
     * @return string  Returns the generated password.
     */
    function create($length = 10, $type = 'pronounceable', $chars = '')
    {
        mt_srand((double) microtime() * 1000000);

        switch ($type) {
        case 'unpronounceable' :
            return Text_Password::_createUnpronounceable($length, $chars);

        case 'pronounceable' :
        default :
            return Text_Password::_createPronounceable($length);
        }
    }

    /**
     * Create multiple, different passwords
     *
     * Method to create a list of different passwords which are
     * all different.
     *
     * @access public
     * @param  integer Number of different password
     * @param  integer Length of the password
     * @param  string  Type of password (pronounceable, unpronounceable)
     * @param  string  Character which could be use in the
     *                 unpronounceable password ex : 'A,B,C,D,E,F,G'
     *                 or numeric or alphanumeric.
     * @return array   Array containing the passwords
     */
    function createMultiple($number, $length = 10, $type = 'pronounceable', $chars = '')
    {
        $passwords = array();

        while ($number > 0) {
            while (true) {
                $password = Text_Password::create($length, $type, $chars);
                if (!in_array($password, $passwords)) {
                    $passwords[] = $password;
                    break;
                }
            }
            $number--;
        }
        return $passwords;
    }

    /**
     * Create password from login
     *
     * Method to create password from login
     *
     * @access public
     * @param  string  Login
     * @param  string  Type
     * @param  integer Key
     * @return string
     */
    function createFromLogin($login, $type, $key = 0)
    {
        switch ($type) {
        case 'reverse':
            return strrev($login);

        case 'shuffle':
            return Text_Password::_shuffle($login);

        case 'xor':
            return Text_Password::_xor($login, $key);

        case 'rot13':
            return str_rot13($login);

        case 'rotx':
            return Text_Password::_rotx($login, $key);

        case 'rotx++':
            return Text_Password::_rotxpp($login, $key);

        case 'rotx--':
            return Text_Password::_rotxmm($login, $key);

        case 'ascii_rotx':
            return Text_Password::_asciiRotx($login, $key);

        case 'ascii_rotx++':
            return Text_Password::_asciiRotxpp($login, $key);

        case 'ascii_rotx--':
            return Text_Password::_asciiRotxmm($login, $key);
        }
    }

    /**
     * Create multiple, different passwords from an array of login
     *
     * Method to create a list of different password from login
     *
     * @access public
     * @param  array   Login
     * @param  string  Type
     * @param  integer Key
     * @return array   Array containing the passwords
     */
    function createMultipleFromLogin($login, $type, $key = 0)
    {
        $passwords = array();
        $number    = count($login);
        $save      = $number;

        while ($number > 0) {
            while (true) {
                $password = Text_Password::createFromLogin($login[$save - $number], $type, $key);
                if (!in_array($password, $passwords)) {
                    $passwords[] = $password;
                    break;
                }
            }
            $number--;
        }
        return $passwords;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     *
     * @access private
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    function _xor($login, $key)
    {
        $tmp = '';

        for ($i = 0; $i < strlen($login); $i++) {
            $next = ord($login{$i}) ^ $key;
            if ($next > 255) {
                $next -= 255;
            } elseif ($next < 0) {
                $next += 255;
            }
            $tmp .= chr($next);
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     * lowercase only
     *
     * @access private
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    function _rotx($login, $key)
    {
        $tmp = '';
        $login = strtolower($login);

        for ($i = 0; $i < strlen($login); $i++) {
            if ((ord($login{$i}) >= 97) && (ord($login{$i}) <= 122)) { // 65, 90 for uppercase
                $next = ord($login{$i}) + $key;
                if ($next > 122) {
                    $next -= 26;
                } elseif ($next < 97) {
                    $next += 26;
                }
                $tmp .= chr($next);
            } else {
                $tmp .= $login{$i};
            }
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     * lowercase only
     *
     * @access private
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    function _rotxpp($login, $key)
    {
        $tmp = '';
        $login = strtolower($login);

        for ($i = 0; $i < strlen($login); $i++, $key++) {
            if ((ord($login{$i}) >= 97) && (ord($login{$i}) <= 122)) { // 65, 90 for uppercase
                $next = ord($login{$i}) + $key;
                if ($next > 122) {
                    $next -= 26;
                } elseif ($next < 97) {
                    $next += 26;
                }
                $tmp .= chr($next);
            } else {
                $tmp .= $login{$i};
            }
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     * lowercase only
     *
     * @access private
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    function _rotxmm($login, $key)
    {
        $tmp = '';
        $login = strtolower($login);

        for ($i = 0; $i < strlen($login); $i++, $key--) {
            if ((ord($login{$i}) >= 97) && (ord($login{$i}) <= 122)) { // 65, 90 for uppercase
                $next = ord($login{$i}) + $key;
                if ($next > 122) {
                    $next -= 26;
                } elseif ($next < 97) {
                    $next += 26;
                }
                $tmp .= chr($next);
            } else {
                $tmp .= $login{$i};
            }
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     *
     * @access private
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    function _asciiRotx($login, $key)
    {
        $tmp = '';

        for ($i = 0; $i < strlen($login); $i++) {
            $next = ord($login{$i}) + $key;
            if ($next > 255) {
                $next -= 255;
            } elseif ($next < 0) {
                $next += 255;
            }
            $tmp .= chr($next);
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     *
     * @access private
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    function _asciiRotxpp($login, $key)
    {
        $tmp = '';

        for ($i = 0; $i < strlen($login); $i++, $key++) {
            $next = ord($login{$i}) + $key;
            if ($next > 255) {
                $next -= 255;
            } elseif ($next < 0) {
                $next += 255;
            }
            $tmp .= chr($next);
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     *
     * @access private
     * @param  string  Login
     * @param  integer Key
     * @return string
     */
    function _asciiRotxmm($login, $key)
    {
        $tmp = '';

        for ($i = 0; $i < strlen($login); $i++, $key--) {
            $next = ord($login{$i}) + $key;
            if ($next > 255) {
                $next -= 255;
            } elseif ($next < 0) {
                $next += 255;
            }
            $tmp .= chr($next);
        }

        return $tmp;
    }

    /**
     * Helper method to create password
     *
     * Method to create a password from a login
     *
     * @access private
     * @param  string  Login
     * @return string
     */
    function _shuffle($login)
    {
        $tmp = array();

        for ($i = 0; $i < strlen($login); $i++) {
            $tmp[] = $login{$i};
        }

        shuffle($tmp);

        return implode($tmp, '');
    }

    /**
     * Create pronounceable password
     *
     * This method creates a string that consists of
     * vowels and consonats.
     *
     * @access private
     * @param  integer Length of the password
     * @return string  Returns the password
     */
    function _createPronounceable($length)
    {

        global $_Text_Password_NumberOfPossibleCharacters;
        $retVal = '';

        /**
         * List of vowels and vowel sounds
         */
        $v = array('a', 'e', 'i', 'o', 'u', 'ae', 'ou', 'io',
                   'ea', 'ou', 'ia', 'ai'
                   );

        /**
         * List of consonants and consonant sounds
         */
        $c = array('b', 'c', 'd', 'g', 'h', 'j', 'k', 'l', 'm',
                   'n', 'p', 'r', 's', 't', 'u', 'v', 'w',
                   'tr', 'cr', 'fr', 'dr', 'wr', 'pr', 'th',
                   'ch', 'ph', 'st', 'sl', 'cl'
                   );

        $v_count = 12;
        $c_count = 29;

        $_Text_Password_NumberOfPossibleCharacters = $v_count + $c_count;

        for ($i = 0; $i < $length; $i++) {
            $retVal .= $c[mt_rand(0, $c_count-1)] . $v[mt_rand(0, $v_count-1)];
        }

        return substr($retVal, 0, $length);
    }

    /**
     * Create unpronounceable password
     *
     * This method creates a random unpronounceable password
     *
     * @access private
     * @param  integer Length of the password
     * @param  string  Character which could be use in the
     *                 unpronounceable password ex : 'A,B,C,D,E,F,G'
     *                 or numeric or alphanumeric.
     * @return string  Returns the password
     */
    function _createUnpronounceable($length, $chars)
    {
        global $_Text_Password_NumberOfPossibleCharacters;

        $password = '';

        /**
         * List of character which could be use in the password
         */
         switch($chars) {

         case 'alphanumeric':
             $regex = 'A|B|C|D|E|F|G|H|I|J|K|L|M|N|O|P|Q|R|S|T|U|V|W|X|Y|Z|a|b|c|d|e|f|g|h|i|j|k|l|m|n|o|p|q|r|s|t|u|v|w|x|y|z|0|1|2|3|4|5|6|7|8|9';
             $_Text_Password_NumberOfPossibleCharacters = 62;
             break;

         case 'numeric':
             $regex = '0|1|2|3|4|5|6|7|8|9';
             $_Text_Password_NumberOfPossibleCharacters = 10;
             break;

         case '':
             $regex = '_|#|@|%|£|&|ç|A|B|C|D|E|F|G|H|I|J|K|L|M|N|O|P|Q|R|S|T|U|V|W|X|Y|Z|a|b|c|d|e|f|g|h|i|j|k|l|m|n|o|p|q|r|s|t|u|v|w|x|y|z|0|1|2|3|4|5|6|7|8|9';
             $_Text_Password_NumberOfPossibleCharacters = 69;
             break;

         default:
             /**
              * Some characters shouldn't be used
              */
             $chars = trim($chars);
             $chars = str_replace('+' , '' , $chars);
             $chars = str_replace('|' , '' , $chars);
             $chars = str_replace('$' , '' , $chars);
             $chars = str_replace('^' , '' , $chars);
             $chars = str_replace('/' , '' , $chars);
             $chars = str_replace('\\', '' , $chars);
             $chars = str_replace(',,', ',', $chars);

             if ($chars{strlen($chars)-1} == ',') {
                 $chars = substr($chars, 0, -1);
             }

             $regex = str_replace(',', '|', $chars);
             $_Text_Password_NumberOfPossibleCharacters = strlen(str_replace(',', '', $chars));
         }

         /**
          * Generate password
          */
         do {
             $chr = chr(mt_rand(0, 255));
             if (preg_match('/'.$regex.'/US', $chr)) {
                 $password .= $chr;
             }
         } while (strlen($password) < $length);

         /**
          * Return password
          */
         return $password;
    }
}
?>

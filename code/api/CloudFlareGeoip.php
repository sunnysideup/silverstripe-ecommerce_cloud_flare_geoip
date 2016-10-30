<?php

class CloudFlareGeoip extends Geoip
{

    private static $debug_email = '';

    /**
     * Find the country for an IP address.
     *
     * By default, it will return an array, keyed by
     * the country code with a value of the country
     * name.
     *
     * To return the code only, pass in true for the
     * $codeOnly parameter.
     *
     * @param string $address The IP address to get the country of
     * @param boolean $codeOnly Returns just the country code
     */
    public static function ip2country($address, $codeOnly = false)
    {

        $code1 = parent::ip2country($address, $codeOnly);
        $code2 = null;
        if (isset($_SERVER["HTTP_CF_IPCOUNTRY"])) {
            $code2 = $_SERVER["HTTP_CF_IPCOUNTRY"];
        }
        $from = Config::inst()->get('CloudFlareGeoip', 'debug_email');
        if($from && $code2 != $code1) {
            if( ! $code1) {
                $subject = 'CloudFlareGeoip: NO GEOLOOKUP PRESENT ';
            } else if( ! $code2) {
                $subject = 'CloudFlareGeoip: NO CF COUNTRY PRESENT ';
            } else {
                $subject = 'CloudFlareGeoip: GEOIP CONFLICT on ';
            }
            $subject .=
                Director::absoluteURL().
                ' for IP: '.self::get_remote_address().
                 ' geoiplookup code: --' . $code1 . '--'.
                 ' CF code: --' . $code2 . '--';
            $to = $from;
            $body = $subject;
            $email = Email::create($from, $to, $subject, $body);
            $email->sendPlain();
        }
        return $code2 ? $code2 : $code1 ;
    }

    /**
     * Returns the country code, for the current visitor
     *
     * @return string|bool
     */
    public static function visitor_country()
    {
        $code = null;
        if (Director::isDev()) {
            if (isset($_GET['countryfortestingonly'])) {
                $code = $_GET['countryfortestingonly'];
                Session::set("countryfortestingonly", $code);
            }
            if ($code = Session::get("countryfortestingonly")) {
                Session::set("MyCloudFlareCountry", $code);
            }
        }

        if (!$code) {
            $code = Session::get("MyCloudFlareCountry");
            if (!$code) {
                if ($address = self::get_remote_address()) {
                    $code = CloudFlareGeoip::ip2country($address, true);
                }
                if (!$code) {
                    $code = self::get_default_country_code();
                }
                if (!$code) {
                    $code = Config::inst()->get("EcommerceCountry", "default_country_code");
                }
                Session::set("MyCloudFlareCountry", $code);
            }
        }

        return $code;
    }

    public static function get_remote_address()
    {
        $ip = null;
        if (! Session::get("MyCloudFlareIPAddress") || (isset($_GET["ipfortestingonly"]))) {
            if (isset($_GET["ipfortestingonly"]) && Director::isDev()) {
                $ip = $_GET["ipfortestingonly"];
            }
            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
            if (
                !$ip ||
                !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            ) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            if ($ip) {
                Session::set("MyCloudFlareIPAddress", $ip);
            }
        }
        if ($ip) {
            return $ip;
        } else {
            return Session::get("MyCloudFlareIPAddress");
        }
    }
}

<?php

class CloudFlareGeoip extends Geoip
{
    private static $debug_email = '';

    /**
     * Find the country for an IP address.
     *
     * Always returns a string (parent method may return array)
     *
     * To return the code only, pass in true for the
     * $codeOnly parameter.
     *
     * @param string $address The IP address to get the country of
     * @param boolean $codeOnly Returns just the country code
     *
     * @return string
     */
    public static function ip2country($address, $codeOnly = false)
    {
        $results1 = null;
        $results2 = null;
        if (isset($_SERVER["HTTP_CF_IPCOUNTRY"])) {
            $results2 = $_SERVER["HTTP_CF_IPCOUNTRY"];
        } else {
            $results1 = parent::ip2country($address);
        }
        $returnValue = $results2 ? $results2 : $results1 ;
        if($codeOnly) {
            if(is_array($returnValue)) {
                return $returnValue['code'];
            }
            return $returnValue;
        }
        else {
            $name = $this->countryCode2name($returnValue);
            return array('code' => $returnValue, 'name' => $name);
        }
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

    /**
     * @see: http://stackoverflow.com/questions/14985518/cloudflare-and-logging-visitor-ip-addresses-via-in-php
     * @return string
     */
    public static function get_remote_address()
    {
        $ip = null;
        if (isset($_GET["ipfortestingonly"]) && Director::isDev()) {
            $ip = $_GET["ipfortestingonly"];

        } elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (
            !$ip ||
            !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        ) {
            $ip = Controller::curr()->getRequest()->getIP();
        }
        return $ip;
    }
}

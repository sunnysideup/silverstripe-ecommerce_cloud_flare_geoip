<?php

/**
 * this is a very basic class with as its sole purpose providing
 * the country of the customer.
 *
 *
 */
class EcommerceCountry_VisitorCountryProviderCloudFlare extends Object implements EcommerceGEOipProvider
{

    /**
     *
     * @var string
     */
    private static $forced_country_code = "";

    /**
     *
     * @var string
     */
    private static $forced_ip_address = "";

    /**
     *
     * @return String (Country Code - e.g. NZ, AU, or AF)
     */
    public function getCountry()
    {
        if ($code = Config::inst()->get("EcommerceCountry_VisitorCountryProviderCloudFlare", "forced_country_code")) {
            return $code;
        }
        return CloudFlareGeoip::visitor_country();
    }

    /**
     *
     *
     * @return string
     */
    public function getIP()
    {
        if ($ip = Config::inst()->get("EcommerceCountry_VisitorCountryProviderCloudFlare", "forced_ip_address")) {
            return $ip;
        }
        return CloudFlareGeoip::get_remote_address();
    }
}

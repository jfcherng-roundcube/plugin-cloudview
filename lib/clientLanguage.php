<?php
/**
 * @version $Id$
 * get client language
 *
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */
class clientLanguage
{
    public $aAvailableLanguages;
    public $sDefault;

    /**
     * find out if users's browser support the given languages.
     *
     * @param array  $aAvailableLanguages available languages
     * @param string $sDefault            the default language
     *
     * @return string the client language
     */
    public function getClientLanguage($aAvailableLanguages, $sDefault = 'en')
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $aLangs = \explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

            // start going through each one
            foreach ($aLangs as $value) {
                $sChoice = \substr($value, 0, 2);
                if (\in_array($sChoice, $aAvailableLanguages)) {
                    return $sChoice;
                }
            }
        }

        return $sDefault;
    }
}

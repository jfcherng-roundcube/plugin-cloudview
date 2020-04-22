<?php
/**
 * @version $Id$
 * simple logger for cloudview
 *
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */
class appendLogEntry
{
    public $slogString;
    public $sLabel;

    /**
     * add a new entry to the log file.
     *
     * @param string $slogString the message to log
     * @param string $sLabel     the log label
     */
    public function isLoggingEnabled()
    {
        $sUseLogging = false; //or disable
        return $sUseLogging;
    }

    public function addLogEntry($slogString, $sLabel = false)
    {
        if (!self::isLoggingEnabled()) {
            return false;
        }

        $sLogFileName =
            INSTALL_PATH . 'plugins/cloudview/log/cloudviewDebug.log';
        $sLogFile = \fopen($sLogFileName, 'a');
        $sNowTime = \date('Y-m-d H:i:s : ');
        $sLogText = $sNowTime . $slogString;

        if ($sLabel) {
            $sLogText .= ' [' . $sLabel . ']';
            \fwrite($sLogFile, $sLogText . "\n");
            \fclose($sLogFile);
        }
    }
}

<?php

namespace Meveto\Logging;

use Analog\Analog;
use Analog\Handler\File;

class Log
{
    /**
     * Construct the logger interface and log
     * the content of the argument.
     * 
     * @param string $content Content to log
     * @return void
     */
    public function __construct($content)
    {
        /**
         * Check and make sure the plugin is not running in production.
         * If so, don't log.
         */
        if ($GLOBALS['MEVETO_PLUGIN_ENV'] && $GLOBALS['MEVETO_PLUGIN_ENV'] === 'production') return;

        $logFile = plugin_dir_path(__FILE__) . 'logs.txt';

        Analog::handler(File::init($logFile));

        Analog::log($content);
    }
}
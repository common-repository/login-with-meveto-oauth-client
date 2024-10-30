<?php

namespace Meveto\Includes;

class MevetoOAuthDeactivator
{

    public static function deactivate()
    {
        flush_rewrite_rules();
    }
}

<?php
class Installation
{
    static public function finish()
    {
        //echo __FUNCTION__;
        echo Service :: strongRandomString(40);
    }

    static public function postAutoloadDump()
    {
        echo __FUNCTION__;
    }
}
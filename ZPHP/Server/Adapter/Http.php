<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core,
    ZPHP\Server\IServer,
    ZPHP\Protocol;

class Http implements IServer
{

    public function run()
    {
        if ($_SERVER['HTTP_CONTENT_ENCODING'] == "gzip") {
            Protocol\Request::setServer(Protocol\Factory::getInstance('GzHttp'));
        } else {
            Protocol\Request::setServer(Protocol\Factory::getInstance('Http'));
        }
        Protocol\Request::parse($_REQUEST);
        return Core\Route::route();
    }

}
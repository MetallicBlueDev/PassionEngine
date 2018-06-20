<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Représente un type de méthode HTTP à utiliser dans la requête.
 * http://php.net/manual/en/language.variables.superglobals.php
 *
 * @author Sébastien Villemain
 */
class CoreRequestType {

    /**
     * $_GET
     * http://php.net/manual/en/reserved.variables.get.php
     *
     * @var string
     */
    const GET = "GET";

    /**
     * $_POST
     * http://php.net/manual/en/reserved.variables.post.php
     *
     * @var string
     */
    const POST = "POST";

    /**
     * $_FILES
     * http://php.net/manual/en/reserved.variables.files.php
     *
     * @var string
     */
    const FILES = "FILES";

    /**
     * $_COOKIE
     * http://php.net/manual/en/reserved.variables.cookies.php
     *
     * @var string
     */
    const COOKIE = "COOKIE";

    /**
     * $_ENV
     * http://php.net/manual/en/reserved.variables.environment.php
     *
     * @var string
     */
    const ENV = "ENV";

    /**
     * $_SERVER
     * http://php.net/manual/en/reserved.variables.server.php
     *
     * @var string
     */
    const SERVER = "SERVER";

}
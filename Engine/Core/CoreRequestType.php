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
    public const GET = "GET";

    /**
     * $_POST
     * http://php.net/manual/en/reserved.variables.post.php
     *
     * @var string
     */
    public const POST = "POST";

    /**
     * $_FILES
     * http://php.net/manual/en/reserved.variables.files.php
     *
     * @var string
     */
    public const FILES = "FILES";

    /**
     * $_COOKIE
     * http://php.net/manual/en/reserved.variables.cookies.php
     *
     * @var string
     */
    public const COOKIE = "COOKIE";

    /**
     * $_ENV
     * http://php.net/manual/en/reserved.variables.environment.php
     *
     * @var string
     */
    public const ENV = "ENV";

    /**
     * $_SERVER
     * http://php.net/manual/en/reserved.variables.server.php
     *
     * @var string
     */
    public const SERVER = "SERVER";

}
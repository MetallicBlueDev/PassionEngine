<?php

namespace PassionEngine\Engine\Core;

/**
 * Représente un type de méthode HTTP à utiliser dans la requête.
 *
 * @link http://php.net/manual/language.variables.superglobals.php
 * @author Sébastien Villemain
 */
class CoreRequestType
{

    /**
     * $_GET
     *
     * @link http://php.net/manual/reserved.variables.get.php
     * @var string
     */
    public const GET = '_GET';

    /**
     * $_POST
     *
     * @link http://php.net/manual/reserved.variables.post.php
     * @var string
     */
    public const POST = '_POST';

    /**
     * $_FILES
     *
     * @link http://php.net/manual/reserved.variables.files.php
     * @var string
     */
    public const FILES = '_FILES';

    /**
     * $_COOKIE
     *
     * @link http://php.net/manual/reserved.variables.cookies.php
     * @var string
     */
    public const COOKIE = '_COOKIE';

    /**
     * $_ENV
     *
     * @link http://php.net/manual/reserved.variables.environment.php
     * @var string
     */
    public const ENV = '_ENV';

    /**
     * $_SERVER
     *
     * @link http://php.net/manual/reserved.variables.server.php
     * @var string
     */
    public const SERVER = '_SERVER';

}
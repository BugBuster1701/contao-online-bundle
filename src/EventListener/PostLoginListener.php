<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle
 *
 * @copyright  Glen Langer 2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Online-Bundle
 * @license    LGPL-3.0-or-later
 * @see        https://github.com/BugBuster1701/contao-online-bundle
 */

namespace BugBuster\OnlineBundle\EventListener;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Contao\System;
use Contao\User;

class PostLoginListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Contructor.
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * onPostLogin.
     */
    public function onPostLogin(User $user): void
    {
        $time = time();
        $strHash = '';
        $namespace = '';

        $intUserId = $user->getData()['id']; // for user id, ugly, but I don't know what's better.

        $this->framework->initialize();

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);
        $timeout = (int) $config->get('sessionTimeout');

        // Generate the cookie hash

        // Generate the cookie hash
        $container = System::getContainer();
        $token_name = $container->getParameter('contao.csrf_token_name');
        $CookiePrefix = $container->getParameter('contao.csrf_cookie_prefix');
        $KernelSecret = $container->getParameter('kernel.secret');

        if ($user instanceof FrontendUser) {
            $strCookie = 'FE_USER_AUTH';
            $namespace = !empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']) ? 'https-' : '';
        }
        if ($user instanceof BackendUser) {
            $strCookie = 'BE_USER_AUTH';
        }
        $token = $_COOKIE[$CookiePrefix.$namespace.$token_name];
        // $token = $container->get('contao.csrf.token_manager')
        //                    ->getToken($container->getParameter('contao.csrf_token_name'))
        //                    ->getValue()
        // ;

        $strHash = hash_hmac('sha256', $token.$intUserId.$strCookie, $KernelSecret, false);

        // Clean up old sessions
        \Database::getInstance()->prepare('DELETE FROM tl_online_session WHERE tstamp<? OR hash=?')
                                ->execute(($time - $timeout), $strHash)
        ;

        // Save the session in the database
        \Database::getInstance()->prepare('INSERT INTO tl_online_session (pid, tstamp, name, hash) VALUES (?, ?, ?, ?)')
                                ->execute($intUserId, $time, $strCookie, $strHash)
        ;
    }
}

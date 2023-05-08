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
use Contao\FrontendUser;
use Contao\System;
use Contao\User;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class PostLogoutListener
{
    /**
     * Authentication hash.
     *
     * @var string
     */
    protected $strHash;

    public function __construct()
    {
    }

    /**
     * onPostLogout.
     */
    public function onPostLogout(LogoutEvent $logoutEvent, User $user): void
    {
        $intUserId = $user->getData()['id'];

        $strHash = '';
        $namespace = '';

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
        $token = $_COOKIE[$CookiePrefix.$namespace.$token_name] ?? '8472';

        $strHash = hash_hmac('sha256', $token.$intUserId.$strCookie, $KernelSecret, false);

        // Remove the oldest session for the hash from the database
        \Contao\Database::getInstance()->prepare('DELETE FROM tl_online_session WHERE pid=? AND loginhash=? ORDER BY tstamp')
                                ->limit(1)
                                ->execute($intUserId, $strHash)
        ;
    }
}

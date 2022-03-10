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
     *
     * @return array 
     */
    public function onPostLogout(User $user)
    {
        $intUserId = $user->getData()['id']; // for user id, ugly, but I don't know what's better.

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
        $token = $CookiePrefix.$namespace.$token_name;
        // $token = $container->get('contao.csrf.token_manager')
        //                    ->getToken($container->getParameter('contao.csrf_token_name'))
        //                    ->getValue()
        // ;
        // $token = json_encode($token);

        $strHash = hash_hmac('sha256', $token.$strCookie, $KernelSecret, false);

        // Remove the oldest session for the hash from the database
        \Database::getInstance()->prepare('DELETE FROM tl_online_session WHERE pid=? AND hash=? ORDER BY tstamp')
                                ->limit(1)
                                ->execute($intUserId, $strHash)
        ;
    }
}

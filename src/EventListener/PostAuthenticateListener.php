<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle
 *
 * @copyright  Glen Langer 2020 <http://contao.ninja>
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

class PostAuthenticateListener
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
     * onPostAuthenticate
     * Hook postAuthenticate.
     */
    public function onPostAuthenticate(User $user): void
    {
        $intUserId = $user->getData()['id']; // for user id, ugly, but I don't know what's better.

        $strHash = '';
        $time = time();

        if ($user instanceof FrontendUser) {
            $strCookie = 'FE_USER_AUTH';
        }

        if ($user instanceof BackendUser) {
            $strCookie = 'BE_USER_AUTH';
        }

        // Generate the cookie hash
        $container = System::getContainer();
        $token = $container->get('contao.csrf.token_manager')
                           ->getToken($container->getParameter('contao.csrf_token_name'))
                           ->getValue()
        ;
        $token = json_encode($token);

        $strHash = hash_hmac('sha256', $token.$strCookie, $container->getParameter('kernel.secret'), false);

        // Update session
        \Database::getInstance()->prepare("UPDATE tl_online_session SET tstamp=$time WHERE pid=? AND hash=?")
                                ->execute($intUserId, $strHash)
        ;
    }
}

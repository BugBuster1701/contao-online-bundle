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
use Symfony\Component\Security\Http\Event\LogoutEvent;

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
    public function onPostLogin(LogoutEvent $logoutEvent, User $user): void
    {
        $time = time();
        $strHashLogin = '';

        $intUserId = $user->getData()['id'];

        $this->framework->initialize();

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);
        $timeout = (int) $config->get('sessionTimeout');

        // Generate the cookie hash

        // Generate the cookie hash
        $container = System::getContainer();
        $KernelSecret = $container->getParameter('kernel.secret');

        if ($user instanceof FrontendUser) {
            $strCookie = 'FE_USER_AUTH';
        }
        if ($user instanceof BackendUser) {
            $strCookie = 'BE_USER_AUTH';
        }

        $strHashLogin = hash_hmac('sha256', $intUserId.$strCookie, $KernelSecret, false);

        // Clean up old sessions
        \Contao\Database::getInstance()->prepare('DELETE FROM tl_online_session WHERE tstamp<? OR loginhash=?')
                                ->execute(($time - $timeout), $strHashLogin)
        ;

        // Save the session in the database
        \Contao\Database::getInstance()->prepare('INSERT INTO tl_online_session (pid, tstamp, instanceof, loginhash) VALUES (?, ?, ?, ?)')
                                ->execute($intUserId, $time, $strCookie, $strHashLogin)
        ;
    }
}

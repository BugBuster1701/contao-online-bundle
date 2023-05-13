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

//use Contao\Config;
//use Contao\System;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Monolog\ContaoContext;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;

class PostLoginListener
{

    /**
     * Contructor.
     */
    public function __construct(
        private ContaoFramework $framework, //kann wech
        private HttpUtils $httpUtils,
        private ScopeMatcher $scopeMatcher,
        private Security $security,
        private SessionInterface $session,
        private array|null $sessionStorageOptions = null,
        private LoggerInterface|null $logger,
        private string $secret
    )    {
    }

    /**
     * onPostLogin.
     */
    public function __invoke(LoginSuccessEvent $event): void
    {
        $time = time();
        $strHashLogin = '';

        $request = $event->getRequest();

        $token = $this->security->getToken();
        if ($token instanceof TokenInterface) {
            $user = $token->getUser();
            $intUserId = $user->id;
        }

        //$this->framework->initialize();

        /** @ var Config $config */
        //$config = $this->framework->getAdapter(Config::class);
        //$timeout = (int) $config->get('sessionTimeout');
        //- '%session.storage.options%'
        //private array|null $sessionStorageOptions = null,
        $timeout = (int) ($this->sessionStorageOptions['gc_maxlifetime'] ?? \ini_get('session.gc_maxlifetime'));

        // Generate the cookie hash

        if ($this->scopeMatcher->isFrontendRequest($request)) {
            $strCookie = 'FE_USER_AUTH';
        }
        if ($this->scopeMatcher->isBackendRequest($request)) {
            $strCookie = 'BE_USER_AUTH';
        }

        $strHashLogin = hash_hmac('sha256', $intUserId.$strCookie, $this->secret, false);

        // Clean up old sessions
        //TODO time() - $this->session->getMetadataBag()->getLastUsed() > $timeout = dann ausgelaufen
        if ($timeout > 0){
            \Contao\Database::getInstance()->prepare('DELETE FROM tl_online_session WHERE tstamp<? OR loginhash=?')
                                    ->execute(($time - $timeout), $strHashLogin)
            ;
        }
        // Save the session in the database
        \Contao\Database::getInstance()->prepare('INSERT INTO tl_online_session (pid, tstamp, instanceof, loginhash) VALUES (?, ?, ?, ?)')
                                ->execute($intUserId, $time, $strCookie, $strHashLogin)
        ;

        $this->logger?->info(
            sprintf('User "%s" has time and lastUsed "%s"', $user->username, $time, $this->session->getMetadataBag()->getLastUsed()),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $user->username)]
        );
    }
}

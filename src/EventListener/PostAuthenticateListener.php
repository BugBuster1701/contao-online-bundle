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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Monolog\ContaoContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

class PostAuthenticateListener
{

    public function __construct(
        private RequestStack $requestStack,
        private ScopeMatcher $scopeMatcher,
        private Security $security,
        private LoggerInterface|null $logger,
        private string $secret
    )    {
    }

    /**
     * onPostAuthenticate
     * Hook postAuthenticate.
     */
    public function onPostAuthenticate(AuthenticationSuccessEvent $event): void
    {
        // $strHash = '';
        $strHashLogin = '';
        $time = time();
        // $namespace = '';

        // Generate the cookie hash
        // $container = System::getContainer();
        // $token_name = $container->getParameter('contao.csrf_token_name');
        // $CookiePrefix = $container->getParameter('contao.csrf_cookie_prefix');
        // $KernelSecret = $container->getParameter('kernel.secret');

        $token = $this->security->getToken();
        if ($token instanceof TokenInterface) {
            $user = $token->getUser();
            $intUserId = $user->id;
        }

        $request = $this->requestStack->getCurrentRequest();
        //$session = $this->requestStack->getSession();

        if ($this->scopeMatcher->isFrontendRequest($request)) {
            $strCookie = 'FE_USER_AUTH';
            //$namespace = !empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']) ? 'https-' : '';
        }
        if ($this->scopeMatcher->isBackendRequest($request)) {
            $strCookie = 'BE_USER_AUTH';
        }
        // $token = $_COOKIE[$CookiePrefix.$namespace.$token_name] ?? '8472';

        // $strHash = hash_hmac('sha256', $token.$intUserId.$strCookie, $KernelSecret, false);
        $strHashLogin = hash_hmac('sha256', $intUserId.$strCookie, $this->secret, false);

        // Update session
        \Contao\Database::getInstance()->prepare("UPDATE tl_online_session SET tstamp=$time
                                            WHERE pid=? AND instanceof=? AND loginhash=?")
                                ->execute($intUserId, $strCookie, $strHashLogin)
        ;

        $this->logger?->info(
            sprintf('User "%s" has time "%s" update', $user->username, $time),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $user->username)]
        );
    }
}

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
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;
class PostLogoutListener
{
    /**
     * Authentication hash.
     *
     * @var string
     */
    protected $strHash;

    /**
     * @internal
     */
    public function __construct(
        private HttpUtils $httpUtils,
        private ScopeMatcher $scopeMatcher,
        private Security $security,
        private LoggerInterface|null $logger,
    ) {
    }

    /**
     * onPostLogout.
     */
    public function __invoke(LogoutEvent $event): void
    {
        $request = $event->getRequest();

        $token = $this->security->getToken();
        if ($token instanceof TokenInterface) {
            $user = $token->getUser();
            $intUserId = $user->id;
        }
        
        $strHash = '';
        $namespace = '';

        // Generate the cookie hash
        $container = \Contao\System::getContainer();
        $token_name = $container->getParameter('contao.csrf_token_name');
        $CookiePrefix = $container->getParameter('contao.csrf_cookie_prefix');
        $KernelSecret = $container->getParameter('kernel.secret');

        if ($this->scopeMatcher->isFrontendRequest($request)) {
            $strCookie = 'FE_USER_AUTH';
            $namespace = !empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']) ? 'https-' : '';
        }
        if ($this->scopeMatcher->isBackendRequest($request)) {
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

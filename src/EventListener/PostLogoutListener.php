<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle
 *
 * @copyright  Glen Langer 2023 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Online-Bundle
 * @license    LGPL-3.0-or-later
 * @see        https://github.com/BugBuster1701/contao-online-bundle
 */

namespace BugBuster\OnlineBundle\EventListener;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\LogoutEvent;

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
        private ScopeMatcher $scopeMatcher,
        private Security $security,
        private LoggerInterface|null $logger,
        private string $secret
    ) {
    }

    /**
     * onPostLogout.
     */
    public function __invoke(LogoutEvent $event): void
    {
        $token = $this->security->getToken();

        if ($token instanceof TokenInterface) {
            $user = $token->getUser();
            $intUserId = $user->id;

            $request = $event->getRequest();

            if ($this->scopeMatcher->isFrontendRequest($request)) {
                $strCookie = 'FE_USER_AUTH';
            }
            if ($this->scopeMatcher->isBackendRequest($request)) {
                $strCookie = 'BE_USER_AUTH';
            }

            $strHashLogin = hash_hmac('sha256', $intUserId.$strCookie, $this->secret, false);

            // Remove the oldest session for the hash from the database
            \Contao\Database::getInstance()->prepare('DELETE FROM tl_online_session WHERE pid=? AND loginhash=? ORDER BY tstamp')
                                    ->limit(1)
                                    ->execute($intUserId, $strHashLogin)
            ;

            // $this->logger?->info(
            //     sprintf('User "%s" ("%s") has time "%s" PostLogoutListener', $user->username, $strCookie, time()),
            //     ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $user->username)]
            // );
        }
    }
}

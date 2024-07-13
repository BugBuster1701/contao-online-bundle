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

# use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class PostLoginListener
{
    /**
     * Contructor.
     */
    public function __construct(
        private ScopeMatcher $scopeMatcher,
        private Security $security,
        private array|null $sessionStorageOptions = null,
        private LoggerInterface|null $logger,
        private string $secret,
        private Connection $connection
    ) {
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

        $timeout = (int) ($this->sessionStorageOptions['gc_maxlifetime'] ?? \ini_get('session.gc_maxlifetime'));

        // sollte nicht vorkommen aber sicher ist sicher
        if (0 === $timeout) {
            $timeout = 3600;
        }

        // Generate the cookie hash
        if ($this->scopeMatcher->isFrontendRequest($request)) {
            $strCookie = 'FE_USER_AUTH';
        }
        if ($this->scopeMatcher->isBackendRequest($request)) {
            $strCookie = 'BE_USER_AUTH';
        }

        $strHashLogin = hash_hmac('sha256', $intUserId.$strCookie, $this->secret, false);

        // Clean up old sessions
        $stmt = $this->connection->prepare('DELETE FROM tl_online_session WHERE tstamp<:tstamp OR loginhash=:loginhash');
        $stmt->executeStatement(['tstamp' => $time - $timeout, 'loginhash' => $strHashLogin]);


        // Save the session in the database
        $this->connection->insert('tl_online_session', ['pid' => $intUserId, 'tstamp' => $time, 'instanceof' => $strCookie, 'loginhash' => $strHashLogin]);

        // $this->logger?->info(
        //     sprintf('User "%s" ("%s") has time "%s" PostLoginListener', $user->username, $strCookie, $time),
        //     ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $user->username)]
        // );
    }
}

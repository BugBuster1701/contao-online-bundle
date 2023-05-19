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
use Contao\CoreBundle\Monolog\ContaoContext;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;

class PostAuthenticateListener
{

    public function __construct(
        private Security $security,
        private LoggerInterface|null $logger,
        private Connection $connection,
        private string $secret
    )    {
    }

    /**
     * onPostAuthenticate
     * https://symfony.com/doc/current/components/http_kernel.html#component-http-kernel-kernel-terminate
     * vendor/bin/contao-console debug:event-dispatcher kernel.terminate
     */
    public function __invoke(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Without the DB table, the online bundle cannot work
        if (!$this->canRunDbQuery()) {
            return;
        }

        $strCookie = '8472';
        $strHashLogin = '';
        $time = time();

        $token = $this->security->getToken();
        if ($token instanceof TokenInterface) {
            $user = $token->getUser();
            $intUserId = $user->id;
        } else {
            return;
        }

        if (($user = $this->security->getUser()) instanceof FrontendUser) {
            $strCookie = 'FE_USER_AUTH';
        }
        if (($user = $this->security->getUser()) instanceof BackendUser) {
            $strCookie = 'BE_USER_AUTH';
        }
        $this->logger?->info(
            sprintf('postAuthenticate "%s" scope: "%s"', $time, $strCookie),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, 'DebugGlen')]
        );

        $strHashLogin = hash_hmac('sha256', $intUserId.$strCookie, $this->secret, false);

        // Update session
        \Contao\Database::getInstance()->prepare("UPDATE tl_online_session SET tstamp=$time
                                            WHERE pid=? AND instanceof=? AND loginhash=?")
                                ->execute($intUserId, $strCookie, $strHashLogin)
        ;

        // $this->logger?->info(
        //     sprintf('User "%s" ("%s") has time "%s" update hash: "%s" PostAuthenticateListener', $user->username, $strCookie, $time, $strHashLogin),
        //     ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $user->username)]
        // );
    }

    /**
     * Checks if a database connection can be established and the table exist.
     */
    private function canRunDbQuery(): bool
    {
        try {
            return $this->connection->isConnected()
                && $this->connection->createSchemaManager()->tablesExist(['tl_online_session']);
        } catch (Exception) {
            return false;
        }
    }
}
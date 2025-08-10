<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2024 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Online Bundle
 * @link       https://github.com/BugBuster1701/contao-online-bundle
 *
 * @license    LGPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace BugBuster\OnlineBundle\Session;

use Doctrine\DBAL\Connection;

class OnlineSession
{
    /**
     * Session Timeout.
     *
     * @var [integer]
     */
    private $timeout;

    public function __construct(
        private Connection $connection,
        private array|null $sessionStorageOptions = null,
    ) {
        $this->timeout = (int) ($this->sessionStorageOptions['gc_maxlifetime'] ?? \ini_get('session.gc_maxlifetime'));
    }

    /**
     * is somebody (FE|BE) online?
     *
     * @return bool
     */
    public function isSomebodyOnline()
    {
        $somebody = $this->connection->prepare('SELECT count(id) AS NUM
                                                FROM tl_online_session
                                                WHERE tstamp > :tstamp');
        $timeout = time() - $this->timeout;
        $somebody->bindValue('tstamp', $timeout);
        $resultSet = $somebody->executeQuery()->fetchAllAssociative();

        return (bool) $resultSet['NUM'];
    }
}

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

namespace BugBuster\OnlineBundle\Session;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;

class OnlineSession
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var Config
     */
    private $config;

    /**
     * Session Timeout.
     *
     * @var [integer]
     */
    private $timeout;

    public function __construct(Connection $connection, ContaoFramework $framework)
    {
        $this->connection = $connection;
        $this->framework = $framework;

        $this->framework->initialize();

        /* @var Config $config */
        $this->config = $this->framework->getAdapter(Config::class);
        $this->timeout = (int) $this->config->get('sessionTimeout');
    }

    public function isSomebodyOnline()
    {
        $somebody = $this->connection->query('SELECT count(id) AS NUM 
                                                FROM tl_online_session
                                                WHERE tstamp > :tstamp');
        $timeout = time() - $this->timeout;
        $somebody->execute(['tstamp' => $timeout]);

        $somebody = $somebody->fetch(\PDO::FETCH_OBJ);

        return (bool) $somebody->NUM;
    }
}

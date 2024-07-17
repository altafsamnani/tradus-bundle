<?php

namespace TradusBundle\Utils\MysqlHelper;

use Doctrine\DBAL\Connection;

/**
 * Class MysqlHelper.
 *
 * Helper class for MySQL usage when using large datasets as doctrine 2 can neither handle the amount of queries nor
 * large data sets.
 * This is a small helper class which uses the php PDO class.
 */
class MysqlHelper
{
    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @return \PDO
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    /**
     * MysqlHelper constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $sql_params = $connection->getParams();
        $this->connection = new \PDO(
            "mysql:host={$sql_params['host']};dbname={$sql_params['dbname']};charset={$sql_params['charset']}",
            $sql_params['user'],
            $sql_params['password'],
            $sql_params['driverOptions']
        );
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->connection = null;
    }
}

<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc\Session;

use DateTimeImmutable;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Jtl\Connector\Core\Session\SessionHandlerInterface;
use Jtl\Connector\Dbc\AbstractTable;
use Jtl\Connector\Dbc\DbcRuntimeException;
use Jtl\Connector\Dbc\DbManager;
use Jtl\Connector\Dbc\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use ReturnTypeWillChange;

class SessionHandler extends AbstractTable implements SessionHandlerInterface
{
    public const
        SESSION_ID   = 'session_id',
        SESSION_DATA = 'session_data',
        EXPIRES_AT   = 'expires_at';
    protected string          $tableName;
    protected int             $maxLifetime;
    protected LoggerInterface $logger;

    /**
     * SessionHandler constructor.
     *
     * @param DbManager $dbManager
     * @param string    $tableName
     *
     * @throws \Exception
     */
    public function __construct(DbManager $dbManager, string $tableName = 'session_store')
    {
        $this->tableName   = $tableName;
        $this->maxLifetime = (int)\ini_get('session.gc_maxlifetime');
        parent::__construct($dbManager);
    }

    /**
     * @return boolean
     */
    public function close(): bool
    {
        $this->getConnection()->close();

        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     * @throws DBALException
     * @throws InvalidArgumentException
     * @throws DbcRuntimeException
     * @throws \RuntimeException
     */
    public function destroy($sessionId): bool
    {
        $this->delete([self::SESSION_ID => $sessionId]);
        return true;
    }

    /**
     * @param int $maxLifetime
     *
     * @return bool
     * @throws DbcRuntimeException
     * @throws \Doctrine\DBAL\Exception
     * @throws \RuntimeException
     */
    #[ReturnTypeWillChange]
    public function gc($maxLifetime): bool
    {
        $this->createQueryBuilder()
             ->delete()
             ->andWhere($this->getConnection()->getExpressionBuilder()->lte(self::EXPIRES_AT, ':now'))
             ->setParameter('now', new DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
             ->execute();

        return true;
    }

    /**
     * @param string $savePath
     * @param string $name
     *
     * @return boolean
     */
    public function open($savePath, $name): bool
    {
        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return string
     * @throws Exception|\Doctrine\DBAL\Exception
     * @throws DbcRuntimeException
     * @throws \RuntimeException
     */
    #[ReturnTypeWillChange]
    public function read($sessionId): string
    {
        $stmt = $this->createReadQuery($sessionId, [self::SESSION_DATA])->execute();
        if (\is_object($stmt) && \is_scalar(($fetchOne = $stmt->fetchOne()))) {
            return (string)$fetchOne;
        }
        return '';
    }

    /**
     * @param string         $sessionId
     * @param array|string[] $columns
     *
     * @return QueryBuilder
     * @throws DbcRuntimeException
     * @throws \RuntimeException
     */
    protected function createReadQuery(string $sessionId, array $columns = [self::SESSION_DATA]): QueryBuilder
    {
        return $this->createQueryBuilder()
                    ->select($columns)
                    ->where($this->getConnection()->getExpressionBuilder()->eq(self::SESSION_ID, ':sessionId'))
                    ->setParameter('sessionId', $sessionId)
                    ->andWhere($this->getConnection()->getExpressionBuilder()->gt(self::EXPIRES_AT, ':now'))
                    ->setParameter('now', new DateTimeImmutable(), Types::DATETIME_IMMUTABLE);
    }

    /**
     * @param string $sessionId
     * @param string $sessionData
     *
     * @return bool
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws \RuntimeException
     */
    public function write($sessionId, $sessionData): bool
    {
        $data = [
            self::SESSION_DATA => $sessionData,
            self::EXPIRES_AT   => (new DateTimeImmutable())->setTimestamp($this->calculateExpiryTime())
        ];

        $rowCount = $this->update($data, [self::SESSION_ID => $sessionId]);
        if ($rowCount === 0) {
            try {
                $this->insert(\array_merge($data, [self::SESSION_ID => $sessionId]));
            } catch (UniqueConstraintViolationException $ex) {
                $this->update($data, [self::SESSION_ID => $sessionId]);
            }
        }

        return true;
    }

    /**
     * @return integer
     */
    protected function calculateExpiryTime(): int
    {
        return \time() + $this->maxLifetime;
    }

    /**
     * @param string $sessionId
     *
     * @return boolean
     * @throws \Doctrine\DBAL\Exception|Exception
     * @throws DbcRuntimeException
     * @throws \RuntimeException
     */
    public function validateId($sessionId): bool
    {
        $stmt = $this->createReadQuery($sessionId, [self::SESSION_ID])->execute();
        if (\is_object($stmt)) {
            return $stmt->fetchOne() === $sessionId;
        }

        return false;
    }

    /**
     * @param string $sessionId
     * @param string $sessionData
     *
     * @return boolean
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws \RuntimeException
     */
    public function updateTimestamp($sessionId, $sessionData): bool
    {
        $this->update(
            [self::EXPIRES_AT => (new DateTimeImmutable())->setTimestamp($this->calculateExpiryTime())],
            [self::SESSION_ID => $sessionId]
        );

        return true;
    }

    /**
     * @return string
     */
    protected function getName(): string
    {
        return $this->tableName;
    }

    /**
     * @param Table $tableSchema
     *
     * @throws SchemaException
     * @throws \Doctrine\DBAL\Exception
     */
    protected function createTableSchema(Table $tableSchema): void
    {
        $tableSchema->addColumn(self::SESSION_ID, Types::STRING, ['length' => 128]);
        $tableSchema->addColumn(self::SESSION_DATA, Types::BLOB);
        $tableSchema->addColumn(self::EXPIRES_AT, Types::DATETIME_IMMUTABLE);
        $tableSchema->setPrimaryKey([self::SESSION_ID]);
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}

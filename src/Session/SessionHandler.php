<?php

namespace Jtl\Connector\MappingTables\Session;

use Doctrine\DBAL\Types\Types;
use Jtl\Connector\Core\Session\SessionHandlerInterface;

class SessionHandler extends \Jtl\Connector\Dbc\Session\SessionHandler implements SessionHandlerInterface
{
    public function isValid(string $sessionId): bool
    {
        $stmt = $this->createQueryBuilder()
            ->select(self::SESSION_ID)
            ->from($this->getTableName())
            ->where($this->getConnection()->getExpressionBuilder()->eq(self::SESSION_ID, ':sessionId'))
            ->setParameter('sessionId', $sessionId)
            ->andWhere($this->getConnection()->getExpressionBuilder()->gt(self::EXPIRES_AT, ':now'))
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
            ->execute();

        if ($stmt instanceof \PDOStatement) {
            return $stmt->fetchColumn() === $sessionId;
        }
        return false;
    }
}
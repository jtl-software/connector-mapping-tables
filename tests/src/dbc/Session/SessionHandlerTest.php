<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc\Session;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Type;
use Exception;
use Jtl\Connector\Dbc\Connection;
use Jtl\Connector\Dbc\DbManager;
use Jtl\Connector\Dbc\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use Throwable;

class SessionHandlerTest extends TestCase
{
    protected SessionHandler $handler;

    /**
     * @runInSeparateProcess
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function testMaxLifetime(): void
    {
        $expected = 254;
        \ini_set('session.gc_maxlifetime', $expected);
        $handler             = new SessionHandler($this->createMock(DbManager::class));
        $reflection          = new \ReflectionClass($handler);
        $reflMaxLifetimeProp = $reflection->getProperty('maxLifetime');
        $reflMaxLifetimeProp->setAccessible(true);
        $this->assertEquals($expected, $reflMaxLifetimeProp->getValue($handler));
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DBALException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testReadSessionSuccess(): void
    {
        $sessionId   = \uniqid('sess', true);
        $sessionData = 'serializedSessionData';

        $data = [
            SessionHandler::SESSION_ID   => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT   => (new \DateTimeImmutable())->setTimestamp(\time() + 1)
        ];

        $this->handler->insert($data);
        $actual = $this->handler->read($sessionId);
        $this->assertEquals($sessionData, $actual);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws DBALException
     */
    public function testReadSessionExpired(): void
    {
        $sessionId   = \uniqid('sess', true);
        $sessionData = 'something';

        $data = [
            SessionHandler::SESSION_ID   => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT   => (new \DateTimeImmutable())->setTimestamp(\time() - 1)
        ];

        $this->handler->insert($data);

        $actual = $this->handler->read($sessionId);
        $this->assertEquals('', $actual);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function testReadSessionDoesNotExist(): void
    {
        $this->assertEquals('', $this->handler->read(\uniqid('presess', true)));
    }

    /**
     * @throws DBALException
     */
    public function testWriteInsert(): void
    {
        $sessionId   = \uniqid('sess', true);
        $sessionData = 'serializedSessionData';

        $this->assertEquals(0, $this->countRows($this->handler->getTableName()));
        $this->handler->write($sessionId, $sessionData);
        $this->assertEquals(1, $this->countRows($this->handler->getTableName()));
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DBALException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testWriteUpdate(): void
    {
        $sessionId   = \uniqid('sess', true);
        $sessionData = 'yeasdasdasf';

        $data = [
            SessionHandler::SESSION_ID   => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT   => (new \DateTimeImmutable())->setTimestamp(\time() + 1)
        ];

        $this->handler->insert($data);
        $this->assertEquals($sessionData, $this->handler->read($sessionId));
        $newData = 'yalla';
        $this->handler->write($sessionId, $newData);
        $this->assertEquals($newData, $this->handler->read($sessionId));
    }

    /**
     * @throws ReflectionException
     * @throws DBALException
     */
    public function testWriteInsertSimultaneouslySameSessionId(): void
    {
        $sessionId   = \uniqid('sess', true);
        $sessionData = 'sdoliufndvnsdzf089wezu089eu';

        /** @var SessionHandler|MockObject $handler */
        $handler = $this->getMockBuilder(SessionHandler::class)
                        ->setConstructorArgs([$this->getDBManager()])
                        ->setMethods(['insert', 'update'])
                        ->getMock();

        $handler
            ->expects($this->once())
            ->method('insert')
            ->willThrowException(
                new UniqueConstraintViolationException(
                    'Duplicate Key entry',
                    $this->createMock(DriverException::class)
                )
            );

        $expiryTime = $this->invokeMethodFromObject($this->handler, 'calculateExpiryTime');

        $updateData = [
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT   => (new \DateTimeImmutable())->setTimestamp($expiryTime)
        ];

        $updateIdentifier = [SessionHandler::SESSION_ID => $sessionId];

        $handler
            ->expects($this->exactly(2))
            ->method('update')
            ->with($updateData, $updateIdentifier)
            ->willReturnOnConsecutiveCalls(0, 1);

        $handler->write($sessionId, $sessionData);
    }

    /**
     * @throws DBALException
     */
    public function testClose(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->once())
            ->method('close');

        /** @var SessionHandler|MockObject $handler */
        $handler = $this->getMockBuilder(SessionHandler::class)
                        ->setConstructorArgs([$this->getDBManager()])
                        ->setMethods(['getConnection'])
                        ->getMock();

        $handler
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->assertTrue($handler->close());
    }

    public function testOpen(): void
    {
        $this->assertTrue($this->handler->open('yalla', 'yolo'));
    }

    /**
     * @throws DBALException
     * @throws InvalidArgumentException
     */
    public function testDestroy(): void
    {
        $sessionId   = \uniqid('sess', true);
        $sessionData = 'something';

        $data = [
            SessionHandler::SESSION_ID   => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT   => (new \DateTimeImmutable())->setTimestamp(\time() - 1)
        ];

        $this->assertEquals(0, $this->countRows($this->handler->getTableName()));
        $this->handler->insert($data);
        $this->assertEquals(1, $this->countRows($this->handler->getTableName()));
        $this->handler->destroy($sessionId);
        $this->assertEquals(0, $this->countRows($this->handler->getTableName()));
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws DBALException
     */
    public function testGc(): void
    {
        $expiredCount = 0;
        $insertedRows = \random_int(3, 10);
        for ($i = 0; $i < $insertedRows; $i++) {
            $expiresAt = (new \DateTimeImmutable())->setTimestamp(\time() + 2);
            if (\random_int(0, 1) === 1) {
                $expiresAt = (new \DateTimeImmutable())->setTimestamp(\time());
                $expiredCount++;
            }

            $this->handler->insert([
                                       SessionHandler::SESSION_ID   => \uniqid('sess', true),
                                       SessionHandler::SESSION_DATA => \sprintf('round %s', $i),
                                       SessionHandler::EXPIRES_AT   => $expiresAt
                                   ]);
        }

        $this->assertEquals($insertedRows, $this->countRows($this->handler->getTableName()));
        $this->handler->gc(1234);
        $this->assertEquals($insertedRows - $expiredCount, $this->countRows($this->handler->getTableName()));
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DBALException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testValidateIdSuccess(): void
    {
        $sessionId   = \uniqid('sess', true);
        $sessionData = 'whateverData';

        $data = [
            SessionHandler::SESSION_ID   => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT   => (new \DateTimeImmutable())->setTimestamp(\time() + 1)
        ];

        $this->handler->insert($data);

        $this->assertTrue($this->handler->validateId($sessionId));
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DBALException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testValidateIdSessionExpired(): void
    {
        $sessionId   = \uniqid('sess', true);
        $sessionData = 'whateverData';

        $data = [
            SessionHandler::SESSION_ID   => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT   => (new \DateTimeImmutable())->setTimestamp(\time())
        ];

        $this->handler->insert($data);

        $this->assertFalse($this->handler->validateId($sessionId));
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function testValidateIdSessionDoesNotExist(): void
    {
        $this->assertFalse($this->handler->validateId(\uniqid('foobar', true)));
    }

    /**
     * @throws DBALException
     * @throws ReflectionException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUpdateTimestamp(): void
    {
        $sessionId   = \uniqid('sess', true);
        $sessionData = 'whateverData';
        $expiresAt   = \time() + 1;


        $data = [
            SessionHandler::SESSION_ID   => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT   => (new \DateTimeImmutable())->setTimestamp($expiresAt)
        ];

        $this->handler->insert($data);

        $this->handler->updateTimestamp($sessionId, $sessionData);

        $qb = $this->getDbManager()->getConnection()->createQueryBuilder();

        $expectedExpiresAtTimestamp = $this->invokeMethodFromObject($this->handler, 'calculateExpiryTime');

        $stmt = $qb
            ->select(SessionHandler::EXPIRES_AT)
            ->from($this->handler->getTableName())
            ->where(\sprintf('%s = :sessionId', SessionHandler::SESSION_ID))
            ->setParameter('sessionId', $sessionId)
            ->execute();

        /** @var \DateTimeImmutable $expiresAt */
        $expiresAt = Type::getType(Type::DATETIME_IMMUTABLE)
                         ->convertToPHPValue(
                             $stmt->fetchColumn(),
                             $this->getDBManager()->getConnection()->getDatabasePlatform()
                         );

        $this->assertEquals($expectedExpiresAtTimestamp, $expiresAt->getTimestamp());
    }

    /**
     * @throws DBALException
     * @throws Throwable
     */
    protected function setUp(): void
    {
        $this->handler = new SessionHandler($this->getDBManager());
        parent::setUp();
    }
}

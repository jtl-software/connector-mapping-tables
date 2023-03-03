<?php

namespace Jtl\Connector\Dbc\Session;

use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Type;
use Jtl\Connector\Dbc\Connection;
use Jtl\Connector\Dbc\TestCase;
use Jtl\Connector\Dbc\DbManager;
use PHPUnit\Framework\MockObject\MockObject;

class SessionHandlerTest extends TestCase
{
    protected $handler;

    protected function setUp(): void
    {
        $this->handler = new SessionHandler($this->getDBManager());
        parent::setUp();
    }

    /**
     * @runInSeparateProcess
     *
     * @throws \ReflectionException
     */
    public function testMaxLifetime()
    {
        $expected = 254;
        ini_set('session.gc_maxlifetime', $expected);
        $handler = new SessionHandler($this->createMock(DbManager::class));
        $reflection = new \ReflectionClass($handler);
        $reflMaxLifetimeProp = $reflection->getProperty('maxLifetime');
        $reflMaxLifetimeProp->setAccessible(true);
        $this->assertEquals($expected, $reflMaxLifetimeProp->getValue($handler));
    }

    public function testReadSessionSuccess()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'serializedSessionData';

        $data = [
            SessionHandler::SESSION_ID => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT => (new \DateTimeImmutable())->setTimestamp(time() + 1)
        ];

        $this->handler->insert($data);
        $actual = $this->handler->read($sessionId);
        $this->assertEquals($sessionData, $actual);
    }

    public function testReadSessionExpired()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'something';

        $data = [
            SessionHandler::SESSION_ID => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT => (new \DateTimeImmutable())->setTimestamp(time() - 1)
        ];

        $this->handler->insert($data);

        $actual = $this->handler->read($sessionId);
        $this->assertEquals('', $actual);
    }

    public function testReadSessionDoesNotExist()
    {
        $this->assertEquals('', $this->handler->read(uniqid('presess', true)));
    }

    public function testWriteInsert()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'serializedSessionData';

        $this->assertEquals(0, $this->countRows($this->handler->getTableName()));
        $this->handler->write($sessionId, $sessionData);
        $this->assertEquals(1, $this->countRows($this->handler->getTableName()));
    }

    public function testWriteUpdate()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'yeasdasdasf';

        $data = [
            SessionHandler::SESSION_ID => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT => (new \DateTimeImmutable())->setTimestamp(time() + 1)
        ];

        $this->handler->insert($data);
        $this->assertEquals($sessionData, $this->handler->read($sessionId));
        $newData = 'yalla';
        $this->handler->write($sessionId, $newData);
        $this->assertEquals($newData, $this->handler->read($sessionId));
    }

    public function testWriteInsertSimultaneouslySameSessionId()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'sdoliufndvnsdzf089wezu089eu';

        /** @var SessionHandler|MockObject $handler */
        $handler = $this->getMockBuilder(SessionHandler::class)
            ->setConstructorArgs([$this->getDBManager()])
            ->setMethods(['insert', 'update'])
            ->getMock();

        $handler
            ->expects($this->once())
            ->method('insert')
            ->willThrowException(new UniqueConstraintViolationException('Duplicate Key entry', $this->createMock(DriverException::class)));

        $expiryTime = $this->invokeMethodFromObject($this->handler, 'calculateExpiryTime');

        $updateData = [
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT => (new \DateTimeImmutable())->setTimestamp($expiryTime)
        ];

        $updateIdentifier = [SessionHandler::SESSION_ID => $sessionId];

        $handler
            ->expects($this->exactly(2))
            ->method('update')
            ->with($updateData, $updateIdentifier)
            ->willReturnOnConsecutiveCalls(0, 1);

        $handler->write($sessionId, $sessionData);
    }

    public function testClose()
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

    public function testOpen()
    {
        $this->assertTrue($this->handler->open('yalla', 'yolo'));
    }

    public function testDestroy()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'something';

        $data = [
            SessionHandler::SESSION_ID => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT => (new \DateTimeImmutable())->setTimestamp(time() - 1)
        ];

        $this->assertEquals(0, $this->countRows($this->handler->getTableName()));
        $this->handler->insert($data);
        $this->assertEquals(1, $this->countRows($this->handler->getTableName()));
        $this->handler->destroy($sessionId);
        $this->assertEquals(0, $this->countRows($this->handler->getTableName()));
    }

    public function testGc()
    {
        $expiredCount = 0;
        $insertedRows = mt_rand(3, 10);
        for ($i = 0; $i < $insertedRows; $i++) {
            $expiresAt = (new \DateTimeImmutable())->setTimestamp(time() + 2);
            if (mt_rand(0, 1) === 1) {
                $expiresAt = (new \DateTimeImmutable())->setTimestamp(time());
                $expiredCount++;
            }

            $this->handler->insert([
                SessionHandler::SESSION_ID => uniqid('sess', true),
                SessionHandler::SESSION_DATA => sprintf('round %s', $i),
                SessionHandler::EXPIRES_AT => $expiresAt
            ]);
        }

        $this->assertEquals($insertedRows, $this->countRows($this->handler->getTableName()));
        $this->handler->gc(1234);
        $this->assertEquals($insertedRows - $expiredCount, $this->countRows($this->handler->getTableName()));
    }

    public function testValidateIdSuccess()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'whateverData';

        $data = [
            SessionHandler::SESSION_ID => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT => (new \DateTimeImmutable())->setTimestamp(time() + 1)
        ];

        $this->handler->insert($data);

        $this->assertTrue($this->handler->validateId($sessionId));
    }

    public function testValidateIdSessionExpired()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'whateverData';

        $data = [
            SessionHandler::SESSION_ID => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT => (new \DateTimeImmutable())->setTimestamp(time())
        ];

        $this->handler->insert($data);

        $this->assertFalse($this->handler->validateId($sessionId));
    }

    public function testValidateIdSessionDoesNotExist()
    {
        $this->assertFalse($this->handler->validateId(uniqid('foobar', true)));
    }

    public function testUpdateTimestamp()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'whateverData';
        $expiresAt = time() + 1;


        $data = [
            SessionHandler::SESSION_ID => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT => (new \DateTimeImmutable())->setTimestamp($expiresAt)
        ];

        $this->handler->insert($data);

        $this->handler->updateTimestamp($sessionId, $sessionData);

        $qb = $this->getDbManager()->getConnection()->createQueryBuilder();

        $expectedExpiresAtTimestamp = $this->invokeMethodFromObject($this->handler, 'calculateExpiryTime');

        $stmt = $qb
            ->select(SessionHandler::EXPIRES_AT)
            ->from($this->handler->getTableName())
            ->where(sprintf('%s = :sessionId', SessionHandler::SESSION_ID))
            ->setParameter('sessionId', $sessionId)
            ->execute();

        /** @var \DateTimeImmutable $expiresAt */
        $expiresAt = Type::getType(Type::DATETIME_IMMUTABLE)
            ->convertToPHPValue($stmt->fetchColumn(), $this->getDBManager()->getConnection()->getDatabasePlatform());

        $this->assertEquals($expectedExpiresAtTimestamp, $expiresAt->getTimestamp());
    }
}

<?php

namespace Jtl\Connector\MappingTables\Session;

use Jtl\Connector\MappingTables\DbTestCase;

class SessionHandlerTest extends DbTestCase
{
    protected $handler;

    protected function setUp(): void
    {
        $this->handler = new SessionHandler($this->getDBManager());
        parent::setUp();
    }

    public function testIsValidSuccess()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'serializedSessionData';

        $data = [
            SessionHandler::SESSION_ID => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT => (new \DateTimeImmutable())->setTimestamp(time() + 1)
        ];

        $this->handler->insert($data);
        $this->assertTrue($this->handler->isValid($sessionId));
    }


    public function testIsValidFailsExpired()
    {
        $sessionId = uniqid('sess', true);
        $sessionData = 'serializedSessionData';

        $data = [
            SessionHandler::SESSION_ID => $sessionId,
            SessionHandler::SESSION_DATA => $sessionData,
            SessionHandler::EXPIRES_AT => (new \DateTimeImmutable())->setTimestamp(time() - 1)
        ];

        $this->handler->insert($data);
        $this->assertFalse($this->handler->isValid($sessionId));
    }

    public function testIsValidFailsNotExists()
    {
        $sessionId = uniqid('sess', true);
        $this->assertFalse($this->handler->isValid($sessionId));
    }
}

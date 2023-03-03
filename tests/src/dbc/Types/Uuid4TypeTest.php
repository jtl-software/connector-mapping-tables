<?php

namespace Jtl\Connector\Dbc\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDb1027Platform;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\ConversionException;
use PHPUnit\Framework\TestCase;

class Uuid4TypeTest extends TestCase
{
    public function testRequiresSQLCommentHint()
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $type = new Uuid4Type();
        $this->assertTrue($type->requiresSQLCommentHint($platform));
    }

    /**
     * @dataProvider convertToDatabaseValueProvider
     *
     * @param string $givenValue
     * @param string $convertedValue
     * @throws ConversionException
     */
    public function testConvertToDatabaseValue(string $givenValue, string $convertedValue)
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $type = new Uuid4Type();
        $this->assertEquals($convertedValue, $type->convertToDatabaseValue($givenValue, $platform));
    }

    /**
     * @dataProvider convertToPhpValueProvider
     *
     * @param string $givenValue
     * @param string $convertedValue
     */
    public function testConvertToPHPValue(string $givenValue, string $convertedValue)
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $type = new Uuid4Type();
        $this->assertEquals($convertedValue, $type->convertToPHPValue($givenValue, $platform));
    }

    /**
     * @dataProvider convertToPHPValueSQLProvider
     *
     * @param AbstractPlatform $platform
     * @param string $columnExpresion
     * @param string $expectedExpression
     */
    public function testConvertToPHPValueSQL(AbstractPlatform $platform, string $columnExpresion, string $expectedExpression)
    {
        $this->assertEquals($expectedExpression, (new Uuid4Type())->convertToPHPValueSQL($columnExpresion, $platform));
    }

    /**
     * @return array[]
     */
    public function convertToDatabaseValueProvider(): array
    {
        return [
            ['336dc2d2-5047-4995-9378-6be53f3b51be', base64_decode('M23C0lBHSZWTeGvlPztRvg==', true)],
            ['65105f26b55c4f0497d04ac36ed625b7', base64_decode('ZRBfJrVcTwSX0ErDbtYltw==', true)],
        ];
    }

    /**
     * @return array[]
     */
    public function convertToPhpValueProvider(): array
    {
        return [
            [base64_decode('M23C0lBHSZWTeGvlPztRvg==', true), '336dc2d25047499593786be53f3b51be'],
            ['0e68bdd4f95b4fa09dee433b4f9f40e1', '0e68bdd4f95b4fa09dee433b4f9f40e1'],
            ['0E68BDD4F95B4FA09DEE433B4F9F40E1', '0E68BDD4F95B4FA09DEE433B4F9F40E1'],
            ['336dc2d2-5047-4995-9378-6be53f3b51be', '336dc2d2-5047-4995-9378-6be53f3b51be'],
        ];
    }

    public function convertToPHPValueSQLProvider(): array
    {
        return [
            [new MySqlPlatform(), 'foo', 'LOWER(HEX(foo))'],
            [new MariaDb1027Platform(), 'bar', 'LOWER(HEX(bar))'],
            [new MySQL57Platform(), 'foobar', 'LOWER(HEX(foobar))'],
            [new MySQL80Platform(), 'yeeha', 'LOWER(HEX(yeeha))'],
            [new SqlitePlatform(), 'rofl', 'LOWER(HEX(rofl))'],
            [$this->getMockForAbstractClass(AbstractPlatform::class), 'abcde', 'abcde'],
        ];
    }
}

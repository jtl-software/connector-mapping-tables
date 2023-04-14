<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDb1027Platform;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\ConversionException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownClassException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

class Uuid4TypeTest extends TestCase
{
    /**
     * @throws InvalidMethodNameException
     * @throws ClassIsFinalException
     * @throws ExpectationFailedException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws RuntimeException
     * @throws ClassIsReadonlyException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws InvalidArgumentException
     * @throws ClassAlreadyExistsException
     */
    public function testRequiresSQLCommentHint(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $type     = new Uuid4Type();
        $this->assertTrue($type->requiresSQLCommentHint($platform));
    }

    /**
     * @dataProvider convertToDatabaseValueProvider
     *
     * @param string $givenValue
     * @param string $convertedValue
     *
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws ClassIsReadonlyException
     * @throws ConversionException
     * @throws DuplicateMethodException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws UnknownTypeException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     */
    public function testConvertToDatabaseValue(string $givenValue, string $convertedValue): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $type     = new Uuid4Type();
        $this->assertEquals($convertedValue, $type->convertToDatabaseValue($givenValue, $platform));
    }

    /**
     * @dataProvider convertToPhpValueProvider
     *
     * @param string $givenValue
     * @param string $convertedValue
     *
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws ClassIsReadonlyException
     * @throws DuplicateMethodException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws UnknownTypeException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     */
    public function testConvertToPHPValue(string $givenValue, string $convertedValue): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $type     = new Uuid4Type();
        $this->assertEquals($convertedValue, $type->convertToPHPValue($givenValue, $platform));
    }

    /**
     * @dataProvider convertToPHPValueSQLProvider
     *
     * @param AbstractPlatform $platform
     * @param string           $columnExpresion
     * @param string           $expectedExpression
     *
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    public function testConvertToPHPValueSQL(
        AbstractPlatform $platform,
        string           $columnExpresion,
        string           $expectedExpression
    ): void {
        $this->assertEquals($expectedExpression, (new Uuid4Type())->convertToPHPValueSQL($columnExpresion, $platform));
    }

    /**
     * @return array<int, array<int, string>>
     * @throws \RuntimeException
     */
    public function convertToDatabaseValueProvider(): array
    {
        $firstDecode  = \base64_decode('M23C0lBHSZWTeGvlPztRvg==', true);
        $secondDecode = \base64_decode('ZRBfJrVcTwSX0ErDbtYltw==', true);
        if ($firstDecode === false || $secondDecode === false) {
            throw new \RuntimeException('decode must not be false.');
        }

        return [
            ['336dc2d2-5047-4995-9378-6be53f3b51be', $firstDecode],
            ['65105f26b55c4f0497d04ac36ed625b7', $secondDecode],
        ];
    }

    /**
     * @return array<int, array<int, string>>
     * @throws \RuntimeException
     */
    public function convertToPhpValueProvider(): array
    {
        $decode = \base64_decode('M23C0lBHSZWTeGvlPztRvg==', true);
        if ($decode === false) {
            throw new \RuntimeException('decode must not be false.');
        }

        return [
            [$decode, '336dc2d25047499593786be53f3b51be'],
            ['0e68bdd4f95b4fa09dee433b4f9f40e1', '0e68bdd4f95b4fa09dee433b4f9f40e1'],
            ['0E68BDD4F95B4FA09DEE433B4F9F40E1', '0E68BDD4F95B4FA09DEE433B4F9F40E1'],
            ['336dc2d2-5047-4995-9378-6be53f3b51be', '336dc2d2-5047-4995-9378-6be53f3b51be'],
        ];
    }

    /**
     * @return array<int, array{0: AbstractPlatform, 1: string, 2: string}>
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws ClassIsReadonlyException
     * @throws DuplicateMethodException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws UnknownTypeException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws UnknownClassException
     */
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

<?php

declare(strict_types=1);

namespace Scoutapm\ScoutApmBundle\Tests\Unit;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scoutapm\ScoutApmAgent;
use Scoutapm\ScoutApmBundle\EventListener\DoctrineSqlLogger;
use Scoutapm\ScoutApmBundle\ScoutApmBundle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/** @covers \Scoutapm\ScoutApmBundle\ScoutApmBundle */
final class ScoutApmBundleTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private $container;

    /** @var ScoutApmBundle */
    private $bundle;

    public function setUp() : void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);

        $this->bundle = new ScoutApmBundle();
        $this->bundle->setContainer($this->container);
    }

    public function testBootRegistersWhenContainerHasService() : void
    {
        $sqlLogger = new DoctrineSqlLogger($this->createMock(ScoutApmAgent::class));

        $connection = $this->createMock(Connection::class);

        $this->container->expects(self::once())
            ->method('has')
            ->with('doctrine.dbal.default_connection')
            ->willReturn(true);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                [DoctrineSqlLogger::class],
                ['doctrine.dbal.default_connection']
            )
            ->willReturnOnConsecutiveCalls(
                $sqlLogger,
                $connection
            );

        $configuration = new Configuration();

        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->bundle->boot();

        self::assertSame($sqlLogger, $configuration->getSQLLogger());
    }

    public function testBootDoesNothingWhenDoctrineDoesNotExist() : void
    {
        $this->container->expects(self::once())
            ->method('has')
            ->with('doctrine.dbal.default_connection')
            ->willReturn(false);

        $this->container->expects(self::never())
            ->method('get');

        $this->bundle->boot();
    }
}

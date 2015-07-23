<?php

namespace Incenteev\ParameterHandler\Tests;

use Incenteev\ParameterHandler\FileHandlerFactory;

class FileHandlerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unsupported file type: foobar
     */
    public function testIsDetectingBadHandler()
    {
        FileHandlerFactory::createFileHandler('foobar');
    }

    public function testIsCreatingFileHandler()
    {
        $handler = FileHandlerFactory::createFileHandler('yml');
        $this->assertInstanceOf('Incenteev\\ParameterHandler\\FileHandler\\YamlHandler', $handler);
    }
}

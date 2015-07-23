<?php

namespace Incenteev\ParameterHandler;

class FileHandlerFactory
{
    /**
     * @var array
     */
    public static $handlers = [
        'yml' => 'YamlHandler',
        'php' => 'PhpHandler',
    ];

    /**
     * @param string $fileType
     *
     * @return FileHandlerInterface
     */
    public static function createFileHandler($fileType)
    {
        if (!isset(self::$handlers[$fileType])) {
            throw new \RuntimeException('Unsupported file type: ' . $fileType);
        }

        $className = '\\Incenteev\\ParameterHandler\\FileHandler\\' . self::$handlers[$fileType];

        return new $className();
    }
}

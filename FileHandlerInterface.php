<?php

namespace Incenteev\ParameterHandler;

interface FileHandlerInterface
{
    /**
     * @param string $filePath
     *
     * @return array
     */
    public function load($filePath);

    /**
     * @param array $data
     *
     * @return array
     */
    public function save($filePath, $data);

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function parseInline($value);

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function dumpInline($value);
}

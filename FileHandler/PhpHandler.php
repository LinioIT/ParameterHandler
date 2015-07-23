<?php

namespace Incenteev\ParameterHandler\FileHandler;

class PhpHandler
{
    /**
     * {@inheritdoc}
     */
    public function load($filePath)
    {
        $data = include $filePath;

        if (is_int($data)) {
            return [];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function save($filePath, $data)
    {
        $contents = sprintf("<?php\n// This file is auto-generated during the composer install\nreturn %s;\n", var_export($data, true));

        return file_put_contents($filePath, $contents);
    }

    /**
     * {@inheritdoc}
     */
    public function parseInline($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function dumpInline($value)
    {
        return $value;
    }
}

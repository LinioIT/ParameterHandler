<?php

namespace Incenteev\ParameterHandler\FileHandler;

use Symfony\Component\Yaml\Inline;
use Symfony\Component\Yaml\Yaml;

class YamlHandler
{
    /**
     * {@inheritdoc}
     */
    public function load($filePath)
    {
        return Yaml::parse(file_get_contents($filePath));
    }

    /**
     * {@inheritdoc}
     */
    public function save($filePath, $data)
    {
        return file_put_contents($filePath, "# This file is auto-generated during the composer install\n" . Yaml::dump($data, 99));
    }

    /**
     * {@inheritdoc}
     */
    public function parseInline($value)
    {
        return Inline::parse($value);
    }

    /**
     * {@inheritdoc}
     */
    public function dumpInline($value)
    {
        return Inline::dump($value);
    }
}

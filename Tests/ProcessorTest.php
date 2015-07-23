<?php

namespace Incenteev\ParameterHandler\Tests;

use Incenteev\ParameterHandler\Processor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    private $io;
    private $environmentBackup = array();

    /**
     * @var Processor
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->io = $this->prophesize('Composer\IO\IOInterface');
        $this->processor = new Processor($this->io->reveal());
    }

    protected function tearDown()
    {
        parent::tearDown();

        foreach ($this->environmentBackup as $var => $value) {
            if (false === $value) {
                putenv($var);
            } else {
                putenv($var.'='.$value);
            }
        }
    }

    /**
     * @dataProvider provideInvalidConfiguration
     */
    public function testInvalidConfiguration(array $config, $exceptionMessage)
    {
        chdir(__DIR__);

        $this->setExpectedException('InvalidArgumentException', $exceptionMessage);

        $this->processor->processFile($config);
    }

    public function provideInvalidConfiguration()
    {
        return array(
            'yml: no file' => array(
                array(),
                'The extra.incenteev-parameters.file setting is required to use this script handler.',
            ),
            'yml: no file type' => array(
                array(
                    'file' => 'fixtures/existent/yml/dist.yml',
                ),
                'The extra.incenteev-parameters.file-type setting is required to use this script handler.',
            ),
            'yml: missing default dist file' => array(
                array(
                    'file' => 'fixtures/invalid/yml/missing.yml',
                    'file-type' => 'yml',
                ),
                'The dist file "fixtures/invalid/yml/missing.yml.dist" does not exist. Check your dist-file config or create it.',
            ),
            'yml: missing custom dist file' => array(
                array(
                    'file' => 'fixtures/invalid/yml/missing.yml',
                    'dist-file' => 'fixtures/invalid/yml/non-existent.dist.yml',
                    'file-type' => 'yml',
                ),
                'The dist file "fixtures/invalid/yml/non-existent.dist.yml" does not exist. Check your dist-file config or create it.',
            ),
            'yml: missing top level key in dist file' => array(
                array(
                    'file' => 'fixtures/invalid/yml/missing_top_level.yml',
                    'file-type' => 'yml',
                ),
                'The top-level key parameters is missing.',
            ),
            'yml: invalid values in the existing file' => array(
                array(
                    'file' => 'fixtures/invalid/yml/invalid_existing_values.yml',
                    'file-type' => 'yml',
                ),
                'The existing "fixtures/invalid/yml/invalid_existing_values.yml" file does not contain an array',
            ),
            'php: no file' => array(
                array(),
                'The extra.incenteev-parameters.file setting is required to use this script handler.',
            ),
            'php: no file type' => array(
                array(
                    'file' => 'fixtures/existent/php/dist.php',
                ),
                'The extra.incenteev-parameters.file-type setting is required to use this script handler.',
            ),
            'php: missing default dist file' => array(
                array(
                    'file' => 'fixtures/invalid/php/missing.php',
                    'file-type' => 'php',
                ),
                'The dist file "fixtures/invalid/php/missing.php.dist" does not exist. Check your dist-file config or create it.',
            ),
            'php: missing custom dist file' => array(
                array(
                    'file' => 'fixtures/invalid/php/missing.php',
                    'dist-file' => 'fixtures/invalid/php/non-existent.dist.php',
                    'file-type' => 'php',
                ),
                'The dist file "fixtures/invalid/php/non-existent.dist.php" does not exist. Check your dist-file config or create it.',
            ),
            'php: missing top level key in dist file' => array(
                array(
                    'file' => 'fixtures/invalid/php/missing_top_level.php',
                    'file-type' => 'php',
                ),
                'The top-level key parameters is missing.',
            ),
            'php: invalid values in the existing file' => array(
                array(
                    'file' => 'fixtures/invalid/php/invalid_existing_values.php',
                    'file-type' => 'php',
                ),
                'The existing "fixtures/invalid/php/invalid_existing_values.php" file does not contain an array',
            ),
        );
    }

    /**
     * @dataProvider provideParameterHandlingTestCases
     */
    public function testParameterHandling($testCaseName, $fileType)
    {
        $dataDir = __DIR__.'/fixtures/testcases/'. $fileType . '/' .$testCaseName;

        $testCase = array_replace_recursive(
            array(
                'title' => 'unknown test',
                'config' => array(
                    'file' => 'parameters.' . $fileType,
                    'file-type' => $fileType,
                ),
                'dist-file' => 'parameters.'. $fileType .'.dist',
                'environment' => array(),
                'interactive' => false,
            ),
            (array) Yaml::parse(file_get_contents($dataDir.'/setup.yml'))
        );

        $workingDir = sys_get_temp_dir() . '/incenteev_parameter_handler';
        $exists = $this->initializeTestCase($testCase, $dataDir, $workingDir, $fileType);

        $message = sprintf('<info>%s the "%s" file</info>', $exists ? 'Updating' : 'Creating', $testCase['config']['file']);
        $this->io->write($message)->shouldBeCalled();

        $this->setInteractionExpectations($testCase);

        $this->processor->processFile($testCase['config']);

        $this->assertFileEquals($dataDir.'/expected.' . $fileType, $workingDir.'/'.$testCase['config']['file'], $testCase['title']);
    }

    private function initializeTestCase(array $testCase, $dataDir, $workingDir, $fileType)
    {
        $fs = new Filesystem();

        if (is_dir($workingDir)) {
            $fs->remove($workingDir);
        }

        $fs->copy($dataDir.'/dist.' . $fileType, $workingDir.'/'. $testCase['dist-file']);

        if ($exists = file_exists($dataDir.'/existing.' . $fileType)) {
            $fs->copy($dataDir.'/existing.' . $fileType, $workingDir.'/'.$testCase['config']['file']);
        }

        foreach ($testCase['environment'] as $var => $value) {
            $this->environmentBackup[$var] = getenv($var);
            putenv($var.'='.$value);
        };

        chdir($workingDir);

        return $exists;
    }

    private function setInteractionExpectations(array $testCase)
    {
        $this->io->isInteractive()->willReturn($testCase['interactive']);

        if (!$testCase['interactive']) {
            return;
        }

        if (!empty($testCase['requested_params'])) {
            $this->io->write('<comment>Some parameters are missing. Please provide them.</comment>')->shouldBeCalledTimes(1);
        }

        foreach ($testCase['requested_params'] as $param => $settings) {
            $this->io->ask(sprintf('<question>%s</question> (<comment>%s</comment>): ', $param, $settings['default']), $settings['default'])
                ->willReturn($settings['input'])
                ->shouldBeCalled();
        }
    }

    public function provideParameterHandlingTestCases()
    {
        $tests = array();

        foreach (glob(__DIR__.'/fixtures/testcases/yml/*/') as $folder) {
            $tests[] = array(basename($folder), 'yml');
        }

        foreach (glob(__DIR__.'/fixtures/testcases/php/*/') as $folder) {
            $tests[] = array(basename($folder), 'php');
        }

        return $tests;
    }
}

<?php
/**
 * This file is part of the <Migration> project.
 *
 * @category   Migration
 * @package    Command
 * @subpackage Migration
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @copyright  2015 PI-GROUPE
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version    2.3
 * @link       http://opensource.org/licenses/gpl-license.php
 * @since      2015-2-16
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sfynx\MigrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Command to execute migration since a specific version
 *
 * <code>
 * php app/console sfynx:migration --currentVersion 24
 * </code>
 *
 * @category   Migration
 * @package    Command
 * @subpackage Migration
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @copyright  2015 PI-GROUPE
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version    2.3
 * @link       http://opensource.org/licenses/gpl-license.php
 * @since      2015-2-16
 */
class PiMigrationCommand extends ContainerAwareCommand
{
    protected $versionFilepath;

    const PARAM_MIGRATION_DIR = 'migrationDir';
    const PARAM_VERSION_DIR = 'versionDir';
    const PARAM_VERSION_FILENAME = 'versionFilename';
    const PARAM_CURRENT_VERSION = 'currentVersion';
    const PARAM_DEBUG = 'debug';

    /**
     * List of concrete $responseException that can be built using this factory.
     * @var string[]
     */
    protected static $parametersList = [
        self::PARAM_MIGRATION_DIR => 'sfynx.tool.migration.migration_dir',
        self::PARAM_VERSION_DIR => 'sfynx.tool.migration.version_dir',
        self::PARAM_VERSION_FILENAME => 'sfynx.tool.migration.version_filename',
        self::PARAM_DEBUG => 'sfynx.tool.migration.debug'
    ];

    protected function configure()
    {
        $this
            ->setName('sfynx:migration')
            ->setDescription('Migration Handler')
            ->addOption(self::PARAM_CURRENT_VERSION, null, InputOption::VALUE_REQUIRED, 'Force the last current version of migration')
            ->addOption(self::PARAM_MIGRATION_DIR, null, InputOption::VALUE_REQUIRED, 'Use another directory with all migration scripts')
            ->addOption(self::PARAM_VERSION_DIR, null, InputOption::VALUE_REQUIRED, 'Use another directory to store the last current version of migration')
            ->addOption(self::PARAM_VERSION_FILENAME, null, InputOption::VALUE_REQUIRED, 'Use another filename to store the last current version of migration')
            ->addOption(self::PARAM_DEBUG, null, InputOption::VALUE_REQUIRED, 'Force all migrations to run from the current version despite erroneous migrations')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        /** we set parameters */
        $this->setParams();

        $this->setOutput();

        /** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
        $dialog   = $this->getHelperSet()->get('question');

        $finder = new Finder();
        $finder->files()->name('Migration_*.php')->in($this->{self::PARAM_MIGRATION_DIR})->sortByName();

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file) {
            $migrationName = $file->getBaseName('.php');
            $migrationVersion = (int) \str_replace('Migration_', '', $migrationName);

            if ($this->{self::PARAM_CURRENT_VERSION} < $migrationVersion) {
                $this->output->writeln('Start ' . $migrationName);

                try {
                    // We execute the migration file
                    require_once($file->getRealpath());

                    $migrationStart = \microtime(true);

                    $var = new $migrationName($this->getContainer(), $input, $output, $dialog);

                    $migrationEnd = \microtime(true);
                    $this->time   = \round($migrationEnd - $migrationStart, 2);
                    $output->writeln(sprintf('    <info>++</info> migrated (%ss)', $this->time));
                } catch (\Exception $e) {
                    if (!$this->{self::PARAM_DEBUG}) {
                        throw new \Exception($e->getMessage(), $e->getCode());
                    }
                }

                //We save the actual migration version
                $this->saveVersion($this->versionFilepath, $migrationVersion);

                $this->output->writeln('End ' . $migrationName);
            }
        }
        $this->output->writeln('saving version '.$migrationVersion.' in ' . $this->versionFilepath);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function setParams()
    {
        $name_key = \array_map([$this, 'setAttributsFromParameters'],
            \array_keys(self::$parametersList),
            \array_values(self::$parametersList)
        );

        $this->versionFilepath = $this->{self::PARAM_VERSION_DIR} . $this->{self::PARAM_VERSION_FILENAME};

        $this->{self::PARAM_CURRENT_VERSION} = $this->input->getOption(self::PARAM_CURRENT_VERSION);
        if (null === $this->{self::PARAM_CURRENT_VERSION}) {
            $this->{self::PARAM_CURRENT_VERSION} = $this->loadVersion($this->versionFilepath);
        }
    }

    /**
     * @param string $optionName
     * @param string $parameterName
     */
    protected function setAttributsFromParameters(string $optionName, string $parameterName)
    {
        $value = $this->input->getOption($optionName);
        $value = \in_array(\strtolower(\trim($value)), ['false', '0']) ? false : $value;
        $value = \in_array(\strtolower(\trim($value)), ['true', '1']) ? true : $value;

        $this->{$optionName} = $value;
        if (null === $this->{$optionName}) {
            $this->{$optionName}  = $this->getContainer()->getParameter($parameterName);
        }
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    protected function setOutput()
    {
        $this->output->writeln('loading from cache ' . $this->versionFilepath);
        $this->output->writeln('loading from current version ' . $this->{self::PARAM_CURRENT_VERSION});
    }

    /**
     * @param $filePath
     * @param $version
     * @return void
     */
    protected function saveVersion($filePath, $version)
    {
        //create directory if not exists
        if (!\file_exists($dir = \dirname($filePath))) {
            \mkdir($dir, 0755, true);
        }
        \file_put_contents($filePath, $version);
    }

    /**
     * @param $filePath
     * @return int
     */
    protected function loadVersion($filePath)
    {
        if (\file_exists($filePath)) {
            return (int) \file_get_contents($filePath);
        }
        return 0;
    }
}

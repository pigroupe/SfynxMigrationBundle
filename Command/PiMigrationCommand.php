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
    protected $versionFilename = 'version.txt';

    protected function configure()
    {
        $this
            ->setName('sfynx:migration')
            ->setDescription('Migration Handler')
            ->addOption('currentVersion', null, InputOption::VALUE_REQUIRED, 'Force the version of migration')
            ->addOption('migrationDir', null, InputOption::VALUE_REQUIRED, 'Use another directory with all migration scripts')
            ->addOption('versionDir', null, InputOption::VALUE_REQUIRED, 'Use another directory to store version file')
            ->addOption('test', null, InputOption::VALUE_NONE, 'For test');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
        $dialog   = $this->getHelperSet()->get('question');

        // migration number
        $currentVersion = $input->getOption('currentVersion');

        $output->writeln('Current version : ' . $currentVersion);

        // migration directory
        $migrationDir = $input->getOption('migrationDir');
        if (null === $migrationDir) {
            $migrationDir  = $this->getContainer()->getParameter('sfynx.tool.migration.migration_dir');
        }

        // version directory
        $versionDir= $input->getOption('versionDir');
        if (null === $versionDir) {
            $versionDir  = $this->getContainer()->getParameter('sfynx.tool.migration.version_dir');
        }

        $versionFilepath = $versionDir.$this->versionFilename;

        //if no version in options, load file
        if (null === $currentVersion) {
            $output->writeln('loading from cache ' . $versionFilepath);
            $currentVersion = $this->loadVersion($versionFilepath);
            $output->writeln('current version is ' . $currentVersion);
        }

        $finder = new Finder();
        $finder->files()->name('Migration_*.php')->in($migrationDir)->sortByName();

        /** @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($finder as $file) {
            $migrationName = $file->getBaseName('.php');
            $migrationVersion = (int) str_replace('Migration_', '', $migrationName);

            if ($currentVersion < $migrationVersion) {
                //if ($migrationVersion == "24") {  // pour lancer la migration 25
                $output->writeln('Start ' . $migrationName);

                // We execute the migration file
                require_once($file->getRealpath());
                $var = new $migrationName($this->getContainer(), $output, $dialog);

                //We save the actual migration version
                $this->saveVersion($versionFilepath, $migrationVersion);

                $output->writeln('End ' . $migrationName);
            }
        }
        $output->writeln('saving version '.$migrationVersion.' in ' . $versionFilepath);
    }

    /**
     * @param $filePath
     * @param $version
     */
    protected function saveVersion($filePath, $version)
    {
        //create directory if not exists
        if (!file_exists($dir = dirname($filePath))) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, $version);
    }
    
    /**
     * @param $filePath
     * @return int
     */
    protected function loadVersion($filePath)
    {
        if (file_exists($filePath)) {
            return (int) file_get_contents($filePath);
        }
        return 0;
    }
}

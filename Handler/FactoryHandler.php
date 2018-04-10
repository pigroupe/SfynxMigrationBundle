<?php
namespace Sfynx\MigrationBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Sfynx\MigrationBundle\Handler\Generalisation\Interfaces\FactoryHandlerInterface;
use Sfynx\MigrationBundle\Handler\MigrationHandler;

/**
 * Generate migration classes by comparing your current database schema
 * to your mapping information..
 *
 * @category   Migration
 * @package    Model
 * @abstract
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @copyright  2015 PI6GROUPE
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version    2.3
 * @link       http://opensource.org/licenses/gpl-license.php
 * @since      2015-02-16
 *
 * <code>
 *         $Migration = FactoryHandler::executeQuery(
 *         $this->input,
 *         $this->output,
 *         $em,
 *         str_replace('Migration_', '', __CLASS__),
 *         'up',
 *         [
 *              "ALTER TABLE {$tableName} DROP COLUMN surface",
 *              "ALTER TABLE {$tableName} ADD COLUMN surface integer",
 *         ],
 *         [],
 *         []
 *         );
 *         if($Migration) {
 *              $SQLexclude = [
 *                  'DROP INDEX "primary"'
 *              ];
 *              FactoryHandler::schemaUpdate($this->input, $this->output, $em, str_replace('Migration_', '', __CLASS__), $SQLexclude);
 *         }
 * </code>
 */
class FactoryHandler implements FactoryHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public static function databaseCreate(EntityManagerInterface $em, string $DbName)
    {
        $databases = $em->getConnection()->getSchemaManager()->listDatabases();
        if (!in_array($DbName, $databases)) {
            $em->getConnection()->getSchemaManager()->createDatabase($DbName);
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function schemaCreate(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $em
    ) {
        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        if ( ! empty($metadatas)) {
            // Create SchemaTool
            $schemaTool = new SchemaTool($em);

            $output->writeln('Creating database schema...');
            $schemaTool->createSchema($metadatas);
            $output->writeln('Database schema created successfully!');

            return true;
        } else {
            $output->writeln('No Metadata Classes to process.');
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function schemaUpdate(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $em,
        bool $saveMode = false
    ) {
        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        if ( ! empty($metadatas)) {
            // Create SchemaTool
            $schemaTool = new SchemaTool($em);

            $sqls = $schemaTool->getUpdateSchemaSql($metadatas, $saveMode);
            if (0 === count($sqls)) {
                $output->writeln('Nothing to update - your database is already in sync with the current entity metadata.');

                return 0;
            }

            $output->writeln('Updating database schema...');
            $schemaTool->updateSchema($metadatas, $saveMode);
            $pluralization = (1 === count($sqls)) ? 'query was' : 'queries were';
            $output->writeln(sprintf('Database schema updated successfully! "<info>%s</info>" %s executed', count($sqls), $pluralization));

            return true;
        } else {
            $output->writeln('No Metadata Classes to process.');
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function schemaUpdateDiff(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $em,
        string $version,
        array $SQLexclude = [],
        string $tableName = null
    ) {
        $diffMigration = new MigrationHandler($input, $output, $em, $version, $tableName);
        if ($diffMigration->isDiff()) {
            return $diffMigration->up($SQLexclude);
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public static function executeQuery(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $em,
        string $version,
        $direction,
        array $queries,
        array $params = [],
        array $types = []
    ) {
        $MigrationHandler = new MigrationHandler($input, $output, $em, $version, null);

        rsort($queries);

        array_map(function($query) use (&$MigrationHandler, $direction, $params, $types) {
            $MigrationHandler->addFirstSql($direction, $query, $params, $types);
        },  $queries);

        return $MigrationHandler->up();
    }
 }

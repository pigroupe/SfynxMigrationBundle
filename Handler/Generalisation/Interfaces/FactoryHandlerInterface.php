<?php
namespace Sfynx\MigrationBundle\Handler\Generalisation\Interfaces;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Interface FactoryHandlerInterface
 *
 * @category   Sfynx\MigrationBundle
 * @package    Handler
 * @subpackage Generalisation\Interfaces
 */
interface FactoryHandlerInterface
{
    /**
     * @param EntityManagerInterface $em
     * @param string $DbName
     * @static
     */
    public static function databaseCreate(EntityManagerInterface $em, string $DbName);

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param EntityManagerInterface $em
     * @return bool
     * @static
     */
    public static function schemaCreate(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $em
    );

    /**
     * Execute the schema update process from Diff of all tables in relation with the entityManager.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param EntityManagerInterface $em
     * @param boolean $saveMode If TRUE, only generates SQL for a partial update
     *                          that does not include SQL for dropping assets which are scheduled for deletion.
     * @return bool TRUE on schema update success or FALSE on failure
     * @static
     */
    public static function schemaUpdate(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $em,
        bool $saveMode = false
    );

    /**
     * Execute the schema update process from Diff of all tables in relation with the entityManager.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param EntityManagerInterface $em
     * @param boolean $saveMode If TRUE, only generates SQL for a partial update
     *                          that does not include SQL for dropping assets which are scheduled for deletion.
     * @param string $version
     * @param string|null $tableName
     * @return bool TRUE on schema update success or FALSE on failure
     * @static
     */
    public static function schemaUpdateDiff(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $em,
        string $version,
        array $SQLexclude = [],
        string $tableName = null
    );

    /**
     * Execute the transaction process with up or down direction.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param EntityManagerInterface $em
     * @param string $version
     * @param $direction The direction to execute the migration. ['up', 'down']
     * @param array $queries
     * @param array $params
     * @param array $types
     * @return bool TRUE on commit success transaction or FALSE on failure
     * @static
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
    );
}
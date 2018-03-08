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
     * Execute the schema update process of all tables in relation with the entityManager.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param EntityManagerInterface $em
     * @param string $version
     * @param string|null $tableName
     * @return bool TRUE on schema update success or FALSE on failure
     * @static
     */
    public static function schemaUpdate(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $em,
        string $version,
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
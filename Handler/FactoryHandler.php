<?php
namespace Sfynx\MigrationBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
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
 *              FactoryHandler::schemaUpdate($this->input, $this->output, $em, str_replace('Migration_', '', __CLASS__));
 *         }
 * </code>
 */
class FactoryHandler implements FactoryHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public static function schemaUpdate(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $em,
        string $version,
        string $tableName = null
    ) {
        $diffMigration = new MigrationHandler($input, $output, $em, $version, $tableName);
        if ($diffMigration->isDiff()) {
            return $diffMigration->up();
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

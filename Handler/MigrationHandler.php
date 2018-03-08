<?php
namespace Sfynx\MigrationBundle\Handler;

//use Doctrine\DBAL\Migrations\AbstractMigration;
//use Doctrine\DBAL\Migrations\Configuration\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Version as DbalVersion;
use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;
use Doctrine\DBAL\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\DBAL\Migrations\Provider\SchemaDiffProvider;
use Doctrine\DBAL\Migrations\Provider\OrmSchemaProvider;
use Doctrine\DBAL\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\DBAL\Migrations\SkipMigrationException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Sfynx\MigrationBundle\Handler\Generalisation\Interfaces\MigrationHandlerInterface;


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
 */
class MigrationHandler implements MigrationHandlerInterface
{
    const STATE_NONE = 0;
    const STATE_PRE  = 1;
    const STATE_EXEC = 2;
    const STATE_POST = 3;
    const DIRECTION_UP   = 'up';
    const DIRECTION_DOWN = 'down';

    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;
    /** @var EntityManagerInterface */
    protected $em;
    /** @var Connection */
    protected $connection;
    /** @var SchemaProviderInterface */
    protected $schemaProvider;
    /** @var SchemaDiffProviderInterface */
    protected $schemaDiffProvider;
    /** @var string */
    protected $tableName;

    /** The array of collected SQL statements for this version */
    protected $sql = [];
    /** The array of collected parameters for SQL statements for this version */
    protected $params = [];
    /** The array of collected types for SQL statements for this version */
    protected $types = [];
    /** The time in seconds that this migration version took to execute */
    protected $time;

    /**
     * MigrationHandler constructor.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $em,
        string $version,
        string $tableName = null
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->em = $em;
        $this->connection = $em->getConnection();
        $this->tableName = $tableName;
        $this->version = $version;

        $this->sql['up'] = null;
        $this->sql['down'] = null;
    }

    /**
     * {@inheritDoc}
     */
    public function isDiff($filterExpr = false, $formatted = InputOption::VALUE_NONE, $lineLength = InputOption::VALUE_OPTIONAL)
    {
        $isDbalOld     = (DbalVersion::compare('2.2.0') > 0);
        $platform = $this->connection->getDatabasePlatform();
        if ($filterExpr) {
            if ($isDbalOld) {
                throw new \InvalidArgumentException('The "--filter-expression" option can only be used as of Doctrine DBAL 2.2');
            }
            $this->connection->getConfiguration()
                ->setFilterSchemaAssetsExpression($filterExpr);
        }
        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $toSchema   = $this->getSchemaProvider()->createSchema();
        //Not using value from options, because filters can be set from config.yml
        if ( ! $isDbalOld && $filterExpr = $this->connection->getConfiguration()->getFilterSchemaAssetsExpression()) {
            foreach ($toSchema->getTables() as $table) {
                $tableName = $table->getName();
                if ( ! preg_match($filterExpr, $this->resolveTableName($tableName))) {
                    $toSchema->dropTable($tableName);
                }
            }
        }
        $up = $this->buildCodeFromSql(
            self::DIRECTION_UP,
            $fromSchema->getMigrateToSql($toSchema, $platform),
            $formatted,
            $lineLength
        );
        $down = $this->buildCodeFromSql(
            self::DIRECTION_DOWN,
            $fromSchema->getMigrateFromSql($toSchema, $platform),
            $formatted,
            $lineLength
        );
//        dump($this->sql['up']);
//        dump($this->sql['down']);
        if ( ! $up && ! $down) {
            $this->output->writeln('No changes detected in your mapping information.');
            return false;
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function up($transaction = true, $timeAllQueries = false, $dryRun = false)
    {
        return $this->execute(self::DIRECTION_UP, $transaction, $timeAllQueries, $dryRun);
    }

    /**
     * {@inheritDoc}
     */
    public function down($transaction = true, $timeAllQueries = false, $dryRun = false)
    {
        return $this->execute(self::DIRECTION_DOWN, $transaction, $timeAllQueries, $dryRun);
    }

    /**
     * Instancies and return the OrmSchemaProvider object
     * @return OrmSchemaProvider
     */
    private function getSchemaProvider()
    {
        if ( ! $this->schemaProvider) {
            $this->schemaProvider = new OrmSchemaProvider($this->em);
        }
        return $this->schemaProvider;
    }

    /**
     * Instancies and return the SchemaDiffProvider object
     * @return SchemaDiffProviderInterface
     */
    private function getSchemaDiffProvider()
    {
        if ( ! $this->schemaDiffProvider) {
            $schemaDiffProvider       = new SchemaDiffProvider(
                $this->connection->getSchemaManager(),
                $this->connection->getDatabasePlatform()
            );
            $this->schemaDiffProvider = LazySchemaDiffProvider::fromDefaultProxyFactoryConfiguration($schemaDiffProvider);
        }
        return $this->schemaDiffProvider;
    }

    /**
     * Resolve a table name from its fully qualified name. The `$name` argument
     * comes from Doctrine\DBAL\Schema\Table#getName which can sometimes return
     * a namespaced name with the form `{namespace}.{tableName}`. This extracts
     * the table name from that.
     *
     * @param   string $name
     * @return  string
     */
    private function resolveTableName($name)
    {
        $pos = strpos($name, '.');
        return false === $pos ? $name : substr($name, $pos + 1);
    }

    /**
     * @param string  $direction      The direction to execute the migration. ['up', 'down']
     * @param array $queries
     * @param bool $formatted
     * @param int $lineLength
     * @return string
     */
    private function buildCodeFromSql($direction, array $queries, $formatted = false, $lineLength = 120)
    {
        $currentPlatform = $this->connection->getDatabasePlatform()->getName();
        $code            = [];
        foreach ($queries as $sql) {
            if (is_null($this->tableName) ||
                ( ! is_null($this->tableName) && stripos($sql, $this->tableName) !== false)
            ) {
                // we register sql no formatted in container to register it forward
                $this->addSql($direction, $sql);

                if ($formatted) {
                    if ( ! class_exists('\SqlFormatter')) {
                        throw new \InvalidArgumentException(
                            'The "--formatted" option can only be used if the sql formatter is installed.' .
                            'Please run "composer require jdorn/sql-formatter".'
                        );
                    }
                    $maxLength = $lineLength - 18 - 8; // max - php code length - indentation
                    if (strlen($sql) > $maxLength) {
                        $sql = \SqlFormatter::format($sql, false);
                    }
                }
                $code[] = $sql;
            }
        }
        return implode("\n", $code);
    }


    /**
     * Execute this migration version up or down and and return the SQL.
     * We are only allowing the addSql call and the schema modification to take effect in the up and down call.
     * This is necessary to ensure that the migration is revertable.
     * The schema is passed to the pre and post method only to be able to test the presence of some table, And the
     * connection that can get used trough it allow for the test of the presence of records.
     *
     * @param string  $direction      The direction to execute the migration. ['up', 'down']
     * @param boolean $transaction
     * @param boolean $timeAllQueries Measuring or not the execution time of each SQL query.
     * @param boolean $dryRun         Whether to not actually execute the migration SQL and just do a dry run.
     *
     * @return boolean TRUE on commit success transaction or FALSE on failure
     *
     * @throws \Exception when migration fails
     */
    private function execute($direction, $transaction = true, $timeAllQueries = false, $dryRun = false)
    {
        if ($transaction) {
            //only start transaction if in transactional mode
            $this->connection->beginTransaction();
        }

        try {
            $this->state = self::STATE_PRE;

            $migrationStart = microtime(true);

            if ($direction === self::DIRECTION_UP) {
                $this->output->writeln(sprintf('  <info>++</info> migrating <comment>%s</comment>', $this->version) . "\n");
            } else {
                $this->output->writeln(sprintf('  <info>--</info> reverting <comment>%s</comment>', $this->version) . "\n");
            }

            $this->state = self::STATE_EXEC;
            $this->executeRegisteredSql($direction, $dryRun, $timeAllQueries);
            $this->state = self::STATE_POST;

            $migrationEnd = microtime(true);
            $this->time   = round($migrationEnd - $migrationStart, 2);
            if ($direction === self::DIRECTION_UP) {
                $this->output->writeln(sprintf('    <info>++</info> migrated (%ss)', $this->time));
            } else {
                $this->output->writeln(sprintf('    <info>--</info> reverted (%ss)', $this->time));
            }

            if ($transaction) {
                //commit only if running in transactional mode
                $this->connection->commit();
            }
            $this->state = self::STATE_NONE;

            return true;
        } catch (SkipMigrationException $e) {
            if ($transaction) {
                //only rollback transaction if in transactional mode
                $this->connection->rollBack();
            }
            $this->output->writeln(sprintf("<info>SS</info> skipped (Reason: %s)", $e->getMessage()));

            return false;
        } catch (\Exception $e) {
            $this->output->writeln(sprintf(
                '<error>Migration %s failed during %s. Error %s</error>',
                $this->version,
                $this->getExecutionState(),
                $e->getMessage()
            ));

            if ($transaction) {
                //only rollback transaction if in transactional mode
                $this->connection->rollBack();
            }

            return false;
        }
    }

    /**
     * Add some SQL queries to this versions migration
     *
     * @param string  $direction      The direction to execute the migration. ['up', 'down']
     * @param array|string $sql
     * @param array        $params
     * @param array        $types
     */
    public function addSql($direction, $sql, array $params = [], array $types = [])
    {
        if (is_array($sql)) {
            foreach ($sql as $key => $query) {
                $this->sql[$direction][] = $query;
                if ( ! empty($params[$key])) {
                    $queryTypes = isset($types[$key]) ? $types[$key] : [];
                    $this->addQueryParams($direction, $params[$key], $queryTypes);
                }
            }
        } else {
            $this->sql[$direction][] = $sql;
            if ( ! empty($params)) {
                $this->addQueryParams($direction, $params, $types);
            }
        }
    }

    /**
     * Add some SQL queries to this versions migration
     *
     * @param string  $direction      The direction to execute the migration. ['up', 'down']
     * @param array|string $sql
     * @param array        $params
     * @param array        $types
     */
    public function addFirstSql($direction, $sql, array $params = [], array $types = [])
    {
        if (null !== $this->sql[$direction]) {
            rsort($this->sql[$direction]);
        }
        $this->addSql($direction, $sql, $params, $types);
        rsort($this->sql[$direction]);

    }

    /**
     * @param string  $direction      The direction to execute the migration. ['up', 'down']
     * @param mixed[] $params Array of prepared statement parameters
     * @param string[] $types Array of the types of each statement parameters
     */
    private function addQueryParams($direction, $params, $types)
    {
        $index = count($this->sql[$direction]) - 1;
        $this->params[$direction][$index] = $params;
        $this->types[$direction][$index]  = $types;
    }

    /**
     * @param string  $direction      The direction to execute the migration. ['up', 'down']
     * @param boolean $dryRun         Whether to not actually execute the migration SQL and just do a dry run.
     * @param boolean $timeAllQueries
     */
    private function executeRegisteredSql($direction, $dryRun = false, $timeAllQueries = false)
    {
        if ( ! $dryRun) {
            if ( ! empty($this->sql[$direction])) {
                foreach ($this->sql[$direction] as $key => $sql) {
                    $queryStart = microtime(true);

                    if ( ! isset($this->params[$direction][$key])) {
                        $this->output->writeln('     <comment>-></comment> ' . $sql);
                        $this->connection->executeQuery($sql);
                    } else {
                        $this->output->writeln(sprintf('    <comment>-</comment> %s (with parameters)', $sql));
                        $this->connection->executeQuery($sql, $this->params[$direction][$key], $this->types[$direction][$key]);
                    }

                    $this->outputQueryTime($queryStart, $timeAllQueries);
                }
            } else {
                $this->output->write(sprintf(
                    '<error>Migration %s was executed but did not result in any SQL statements.</error>',
                    $this->version
                ));
            }
        } else {
            foreach ($this->sql[$direction] as $idx => $sql) {
                $this->outputSqlQuery($direction, $idx, $sql);
            }
        }
    }

    /**
     * @param $queryStart
     * @param bool $timeAllQueries
     */
    private function outputQueryTime($queryStart, $timeAllQueries = false)
    {
        if ($timeAllQueries !== false) {
            $queryEnd  = microtime(true);
            $queryTime = round($queryEnd - $queryStart, 4);

            $this->output->writeln(sprintf("  <info>%ss</info>", $queryTime));
        }
    }

    /**
     * Outputs a SQL query via the `Output`.
     *
     * @param string  $direction      The direction to execute the migration. ['up', 'down']
     * @param int $idx The SQL query index. Used to look up params.
     * @param string $query the query to output
     * @return void
     */
    private function outputSqlQuery($direction, $idx, $query)
    {
        $params = $this->formatParamsForOutput(
            isset($this->params[$direction][$idx]) ? $this->params[$direction][$idx] : [],
            isset($this->types[$direction][$idx]) ? $this->types[$direction][$idx] : []
        );

        $this->output->writeln(rtrim(sprintf(
            '     <comment>-></comment> %s %s',
            $query,
            $params
        )));
    }

    /**
     * @return string
     */
    private function getExecutionState()
    {
        switch ($this->state) {
            case self::STATE_PRE:
                return 'Pre-Checks';
            case self::STATE_POST:
                return 'Post-Checks';
            case self::STATE_EXEC:
                return 'Execution';
            default:
                return 'No State';
        }
    }
}

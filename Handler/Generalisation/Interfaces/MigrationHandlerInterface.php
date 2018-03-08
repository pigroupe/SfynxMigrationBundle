<?php
namespace Sfynx\MigrationBundle\Handler\Generalisation\Interfaces;

use Symfony\Component\Console\Input\InputOption;

/**
 * Interface MigrationHandlerInterface
 *
 * @category   Sfynx\MigrationBundle
 * @package    Handler
 * @subpackage Generalisation\Interfaces
 */
interface MigrationHandlerInterface
{
    /**
     * @param bool $filterExpr
     * @param bool $formatted
     * @param int $lineLength
     * @return boolean FALSE to no changes detected in your mapping information.
     */
    public function isDiff($filterExpr = false, $formatted = InputOption::VALUE_NONE, $lineLength = InputOption::VALUE_OPTIONAL);

    /**
     * @param boolean $transaction
     * @param boolean $timeAllQueries Measuring or not the execution time of each SQL query.
     * @param boolean $dryRun         Whether to not actually execute the migration SQL and just do a dry run.
     * @return boolean TRUE on commit success transaction or FALSE on failure
     * @throws \Exception
     */
    public function up($transaction = true, $timeAllQueries = false, $dryRun = false);

    /**
     * @param boolean $transaction
     * @param boolean $timeAllQueries Measuring or not the execution time of each SQL query.
     * @param boolean $dryRun         Whether to not actually execute the migration SQL and just do a dry run.
     * @return boolean TRUE on commit success transaction or FALSE on failure
     * @throws \Exception
     */
    public function down($transaction = true, $timeAllQueries = false, $dryRun = false);
}

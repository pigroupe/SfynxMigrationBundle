<?php
namespace Sfynx\MigrationBundle\Model;

use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\HelperInterface;

/**
 * Abstract model of a migration file.
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
abstract class AbstractMigration
{
    /** @var ContainerInterface */
    protected $container;
    /** @var OutputInterface */
    protected $output;
    /** @var DialogHelper */
    protected $dialog;
    /** @var array */
    protected $em = [];

    /**
     * AbstractMigration constructor.
     * @param ContainerInterface $container
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     */
    public function __construct(
        ContainerInterface $container,
        OutputInterface $output,
        HelperInterface $dialog
    ) {
        $this->container = $container;
        $this->output    = $output;
        $this->dialog    = $dialog;

        if ($this->test()) {
            $this->PreUp();
            $this->Up();
            $this->PostUp();
        }
    }

    /**
     * @return bool
     */
    protected function test()
    {
        return true;
    }

    /**
     *
     */
    protected function PreUp()
    {
        $this->beginTransaction();
    }

    /**
     * @return mixed
     */
    abstract protected function Up();

    /**
     *
     */
    protected function PostUp()
    {
        $this->commitTransaction();
    }

    /**
     * @param $msg
     * @param null $test
     */
    protected function log($msg, $test = null)
    {
        if (null === $test) {
            $this->output->writeln("  $msg");
        } elseif ($test) {
            $this->output->writeln("  $msg <info>[OK]</info>");
        } else {
            $this->output->writeln("  $msg <error>[KO]</error>");
        }
    }

    /**
     * @return array
     */
    protected function getTransactionManagers(): ?array
    {
        return null;
    }

    /**
     * begin transactions of all command repository manager
     * @return bool
     */
    protected function beginTransaction()
    {
        if (null === $this->getTransactionManagers()) {
            return false;
        }

        foreach ($this->getTransactionManagers() as $k => $em) {
            $this->em[$k] = $em;
            $this->em[$k]->getConnection()->beginTransaction();
        }

        return true;
    }

    /**
     * Commit transactions of all command repository manager
     * @return bool
     * @throws Exception
     */
    protected function commitTransaction()
    {
        if (null === $this->getTransactionManagers()) {
            return false;
        }

        try {
            // Try and commit the transaction
            foreach ($this->getTransactionManagers() as $k => $em) {
                $this->em[$k]->flush();
                $this->em[$k]->commit();
            }

            return true;
        } catch (Exception $e) {
            // Rollback the failed transaction attempt
            foreach ($this->getTransactionManagers() as $k => $em) {
                $this->em[$k]->rollback();
                $this->em[$k]->close();
            }
            throw new Exception($e->getMessage());
        }
    }
}

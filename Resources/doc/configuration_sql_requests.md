# Summary

- [How to delete all rows of a table](#how-to-delete-all-rows-of-a-table)
- [How to execute multiple sql requests with transaction](#how-to-execute-multiple-sql-requests-with-transaction)
- [How to execute schema update from mmigration](#how-to-execute-schema-update-from-mmigration)

## How to delete all rows of a table

```php
<?php
use Sfynx\MigrationBundle\Model\AbstractMigration;
use GeographieContext\Domain\Entity\Pays;
use GeographieContext\Domain\Service\Entity\Pays\PaysManager;

/**
 * Class Migration_1518010502
 *
 * @category SfynxMigration
 * @package Sfynx
 * @subpackage orm
 *
 */
class Migration_1518010502 extends AbstractMigration
{
    /** @var PaysManager */
    protected $paysManager;

    /**
     * @return array
     */
    protected function getTransactionManagers(): array
    {
        $this->paysManager = $this->container->get('geographie_pays_manager');

        return [
            $this->paysManager->getRepositoryCommand()->getEm()
        ];
    }

    /**
     * Does the migration
     */
    public function Up()
    {
        //delete all with a raw query
        $con = $this->paysManager->getRepositoryCommand()->getEm()->getConnection();
        $sql = 'DELETE FROM ' . $this->paysManager->getTableName();
        $con->prepare($sql)->execute();
    }
}
```

## How to execute multiple sql requests with transaction

```php
<?php
use Sfynx\MigrationBundle\Model\AbstractMigration;
use Sfynx\MigrationBundle\Handler\FactoryHandler;
use UserContext\Domain\Service\Entity\Origin\OriginManager;

/**
 * Class Migration_1522944300
 *
 * @category SfynxMigration
 * @package Sfynx
 * @subpackage orm
 */
class Migration_1522944300 extends AbstractMigration
{
    /** @var OriginManager */
    protected $originManager;

    /**
     * @return null|array
     */
    protected function getTransactionManagers(): ?array
    {
        $this->originManager = $this->container->get('user_origin_manager');

        return null;
    }

    /**
     * Does the migration
     */
    public function Up()
    {
        $em = $this->originManager->getRepositoryCommand()->getEm();
        $tableName = $this->originManager->getCommandRepository()->getTableName();

        FactoryHandler::executeQuery(
            $this->input,
            $this->output,
            $em,
            str_replace('Migration_', '', __CLASS__),
            'up',
            [
                "ALTER TABLE {$tableName} DROP COLUMN nbrlot",
                "ALTER TABLE {$tableName} ADD COLUMN nbrlot integer",
                "UPDATE {$tableName} SET nbrlot = 0 WHERE nbrlot ISNULL",
            ],
            [],
            []
        );
    }
}
```

## How to execute schema update from mmigration

```php
<?php
use Sfynx\MigrationBundle\Model\AbstractMigration;
use Sfynx\MigrationBundle\Handler\MigrationHandler;
use Sfynx\MigrationBundle\Handler\FactoryHandler;
use UserContext\Domain\Service\Entity\Parcel\ParcelManager;

/**
 * Class Migration_1519914937
 *
 * @category SfynxMigration
 * @package Sfynx
 * @subpackage orm
 */
class Migration_1519914937 extends AbstractMigration
{
    /** @var ParcelManager */
    protected $parcelManager;

    /**
     * @return array
     */
    protected function getTransactionManagers(): ?array
    {
        $this->parcelManager = $this->container->get('user_parcel_manager');

        return null;
    }

    /**
     * Does the migration
     */
    public function Up()
    {
        $em = $this->parcelManager->getCommandRepository()->getEm();
        $tableName = $this->parcelManager->getCommandRepository()->getTableName();

        $Migration = FactoryHandler::executeQuery(
            $this->input,
            $this->output,
            $em,
            str_replace('Migration_', '', __CLASS__),
            'up',
            [
                "ALTER TABLE {$tableName} DROP COLUMN surface",
                "ALTER TABLE {$tableName} ADD COLUMN surface integer",
                "ALTER TABLE ground_owner DROP COLUMN estimated_price",
                "ALTER TABLE ground_owner ADD COLUMN estimated_price DOUBLE PRECISION",
                "ALTER TABLE ground_owner DROP COLUMN possible_dation",
                "ALTER TABLE ground_owner ADD COLUMN possible_dation DOUBLE PRECISION",
                "ALTER TABLE ground_owner DROP COLUMN eviction_indemnity",
                "ALTER TABLE ground_owner ADD COLUMN eviction_indemnity DOUBLE PRECISION",
                "ALTER TABLE ground_owner DROP COLUMN lease_indemnity",
                "ALTER TABLE ground_owner ADD COLUMN lease_indemnity DOUBLE PRECISION"
            ],
            [],
            []
        );

        if ($Migration) {
            FactoryHandler::schemaUpdateDiff($this->input, $this->output, $em, str_replace('Migration_', '', __CLASS__));
            // FactoryHandler::schemaUpdate($this->input, $this->output, $em, false);
        }
    }
}
```

# Summary

- [How to update auto increment value](#how-to-update-auto-increment-table)

## How to update auto increment value

First, define in class entity the SequenceName value associated to the identifier.

```php
<?php
namespace GeographieContext\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pays
 *
 * @ORM\Table(name="pays")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class Pays
{
    const PAYS_FRANCE = 1;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\SequenceGenerator(sequenceName="pays_id_seq")
     */
    protected $id;

    ...

}
```

Secondly, execute this simple request to update the max id value of the entity.

```php
<?php
use Sfynx\MigrationBundle\Model\AbstractMigration;
use GeographieContext\Domain\Entity\Pays;
use GeographieContext\Domain\Service\Entity\Pays\PaysManager;
use Sfynx\MigrationBundle\Handler\Generalisation\Traits\TraitGetMaxId;

/**
 * Class Migration_1518010503
 *
 * @category SfynxMigration
 * @package Sfynx
 * @subpackage orm
 *
 */
class Migration_1518010503 extends AbstractMigration
{
    use TraitGetMaxId;

    /** @var PaysManager */
    protected $paysManager;

    /**
     * @return null|array
     */
    protected function getTransactionManagers(): ?array
    {
        $this->paysManager = $this->container->get('geographie_pays_manager');

        return null;
    }

    /**
     * Does the migration
     */
    public function Up()
    {
        $fixtures = [
            [
                'id' => 36,
                'name'  => 'FRANCE'
            ],
            [
                'id' => 37,
                'name'  => 'SENEGAL'
            ],
        ];

        $this->paysManager->setIdGenerator();
        foreach ($fixtures as $fixture) {
            $entity = new DateList();
            $entity->setId($fixture['id']);
            $entity->setName($fixture['name']);
            $entity->setEnabled(true);

            $this->paysManager->getCommandRepository()->persist($entity, false);
        }
    }

    /**
     *
     */
    protected function PostUp()
    {
        $this->commitTransaction();

        $this->setMaxId($em, Pays::class, 'pays_id_seq');
    }
}
```

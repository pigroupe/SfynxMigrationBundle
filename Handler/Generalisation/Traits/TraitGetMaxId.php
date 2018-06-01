<?php
namespace Sfynx\MigrationBundle\Handler\Generalisation\Traits;

use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Récuperation du dernier id inseré dans la table
 *
 * Trait TraitGetMaxId
 * @category Sfynx\MigrationBundle
 * @package Handler
 * @subpackage Generalisation\Traits
 */
trait TraitGetMaxId
{
    /**
     * Return the max id (last id in database)
     *
     * @param EntityManagerInterface $em
     * @param object $entity (Format: MyClass::class)
     * @return mixed
     */
    protected function getMaxId(EntityManagerInterface $em, $entity)
    {
      return $em->createQueryBuilder()
        ->select('MAX(e.id)')
        ->from($entity , 'e')
        ->getQuery()
        ->getSingleScalarResult();
    }

    /**
     *Execute setIdGenerator before insert new lines with id
     *
     * @param EntityRepository $repository
     * @return EntityRepository
     */
    protected function setIdGenerator(EntityRepository $repository): EntityRepository
    {
        $repository->getClassMetadata()->setIdGenerator(new AssignedGenerator());
        return $repository;
    }

    /**
     * Execute  setMaxId after insert new lines with id
     *
     * @param EntityManagerInterface $em
     * @param object $class
     */
    protected function setMaxId(EntityManagerInterface $em, $class)
    {
        $tlMaxId = $this->getMaxId($em, $class);
        //The number to update the auto increment in database
        $tlNb = ($tlMaxId > 0)?($tlMaxId + 1):1;
        //Update auto increment query
        $sql1 = "ALTER SEQUENCE typelist_id_seq RESTART WITH ".$tlNb ;
        //Connection statement and prepare query
        $stmt1 = $tlEm->getConnection()->prepare($sql1);

        $stmt1->execute();
    }
}

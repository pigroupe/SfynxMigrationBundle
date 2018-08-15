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
     * @param string $entity (Format: MyClass::class)
     * @return mixed
     */
    protected function getMaxId(EntityManagerInterface $em, string $entity)
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
     * @param string $entity (Format: MyClass::class)
     * @param string $sequence_id
     */
    protected function setMaxId(EntityManagerInterface $em, string $entity, string $sequence_id = '')
    {
        if (empty($sequence_id)) {
            $sequence_id = $this->getSequenceIdFromClassName($entity);
        }

        $MaxId = $this->getMaxId($em, $entity);
        //The number to update the auto increment in database
        $Nb = ($MaxId > 0)?($MaxId + 1):1;
        //Update auto increment query
        $sql1 = "ALTER SEQUENCE $sequence_id RESTART WITH " . $Nb ;
        //Connection statement and prepare query
        $em->getConnection()->prepare($sql1)->execute();
    }

    /**
     * @param string $entity (Format: MyClass::class)
     * @return string
     */
    protected function getSequenceIdFromClassName(string $entity): string
    {
        if (\strrpos($entity, '\\')) {
            return \substr($entity, \strrpos($entity, '\\') + 1);
        }
        return \strtolower($entity) . '_id_seq';
    }
}

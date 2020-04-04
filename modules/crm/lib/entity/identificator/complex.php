<?php
namespace Bitrix\Crm\Entity\Identificator;

use Bitrix\Main\ArgumentNullException;

/**
 * Class `Entity complex identificator`.
 *
 * @package Bitrix\Crm\Entity\Identificator
 */
class Complex
{
	/** @var int $typeId Entity type ID.  */
	protected $typeId;

	/** @var int $id Entity ID.  */
	protected $id;

	/**
	 * Entity complex identificator constructor.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @throws ArgumentNullException
	 */
	public function __construct($entityTypeId, $entityId)
	{
		if (!self::validateId($entityTypeId))
		{
			throw new ArgumentNullException('$entityTypeId');
		}
		if (!self::validateId($entityId))
		{
			throw new ArgumentNullException('$entityId');
		}

		$this->typeId = (int) $entityTypeId;
		$this->id = (int) $entityId;
	}

	/**
	 * Get entity type ID.
	 *
	 * @return int
	 */
	public function getTypeId()
	{
		return $this->typeId;
	}

	/**
	 * Get entity ID.
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Convert to array.
	 *
	 * @param array $keys Keys of array.
	 * @return array
	 */
	public function toArray(array $keys = ['ENTITY_TYPE_ID', 'ENTITY_ID'])
	{
		return [
			$keys[0] => $this->getTypeId(),
			$keys[1] => $this->getId(),
		];
	}

	/**
	 * Return true if this entities is equal.
	 *
	 * @param Complex $complexA Complex identificator A.
	 * @param Complex $complexB Complex identificator B.
	 * @return bool
	 */
	public static function isEqual(Complex $complexA, Complex $complexB)
	{
		return (
			$complexA->getTypeId() === $complexB->getTypeId()
			&&
			$complexA->getId() === $complexB->getId()
		);
	}

	/**
	 * Return true if ID is valid.
	 *
	 * @param mixed $id ID.
	 * @return bool
	 */
	public static function validateId($id)
	{
		return !empty($id) && is_numeric($id);
	}
}
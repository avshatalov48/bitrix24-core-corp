<?php
namespace Bitrix\Crm\Automation\Converter;

use Bitrix\Crm\Entity\Identificator;

/**
 * Class Result
 *
 * @package Bitrix\Crm\Automation\Converter
 */
class Result extends \Bitrix\Main\Result
{
	/** @var Identificator\ComplexCollection $createdEntities Created entities. */
	protected $createdEntities;

	/** @var Identificator\ComplexCollection $boundEntities Bound entities. */
	protected $boundEntities;

	/**
	 * Set converter result data.
	 *
	 * @param array $data Data.
	 * @return void
	 */
	public function setConverterResultData(array $data)
	{
		foreach ($data as $entityTypeName => $entityId)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
			$this->getBoundEntities()->addIdentificator($entityTypeId, $entityId);

			if (isset($data["IS_RECENT_{$entityTypeName}"]))
			{
				$this->getCreatedEntities()->addIdentificator($entityTypeId, $entityId);
			}
		}
	}

	/**
	 * Get created entities.
	 *
	 * @return Identificator\ComplexCollection
	 */
	public function getCreatedEntities()
	{
		if (!$this->createdEntities)
		{
			$this->createdEntities = new Identificator\ComplexCollection();
		}

		return $this->createdEntities;
	}

	/**
	 * Get bound entities.
	 *
	 * @return Identificator\ComplexCollection
	 */
	public function getBoundEntities()
	{
		if (!$this->boundEntities)
		{
			$this->boundEntities = new Identificator\ComplexCollection();
		}

		return $this->boundEntities;
	}
}
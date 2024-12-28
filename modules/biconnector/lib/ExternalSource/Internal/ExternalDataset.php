<?php

namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\BIConnector;
use Bitrix\Main\Entity\ReferenceField;

class ExternalDataset extends EO_ExternalDataset
{
	/**
	 * Get source id by dataset id from relation table
	 *
	 * @return int|null
	 */
	public function getSourceId(): ?int
	{
		$source = ExternalSourceDatasetRelationTable::getByDatasetId($this->getId());
		if ($source)
		{
			return (int)(current($source)['SOURCE_ID']);
		}

		return null;
	}

	/**
	 * Get source of dataset.
	 *
	 * @return ExternalSource|null
	 */
	public function getSource(): ?ExternalSource
	{
		$sourceQuery = ExternalSourceTable::query()
			->setSelect(['*'])
			->setFilter(['SOURCE_RELATION.DATASET_ID' => $this->getId()])
			->registerRuntimeField('SOURCE_RELATION', new ReferenceField(
				'SOURCE_RELATION',
				ExternalSourceDatasetRelationTable::getEntity(),
				['=this.ID' => 'ref.SOURCE_ID']
			))
		;

		return $sourceQuery->exec()->fetchObject();
	}

	/**
	 * Gets enum type
	 *
	 * @return BIConnector\ExternalSource\Type
	 */
	public function getEnumType(): BIConnector\ExternalSource\Type
	{
		return BIConnector\ExternalSource\Type::from($this->getType());
	}

	public function toArray(): array
	{
		return $this->collectValues();
	}
}

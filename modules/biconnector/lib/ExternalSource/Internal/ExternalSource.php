<?php

namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\BIConnector;

class ExternalSource extends EO_ExternalSource
{
	public function getSettings(): ExternalSourceSettingsCollection
	{
		return ExternalSourceSettingsTable::getList([
			'filter' => [
				'=SOURCE_ID' => $this->getId(),
			],
		])
			->fetchCollection()
		;
	}

	public function removeAllSettings(): void
	{
		ExternalSourceSettingsTable::deleteByFilter([
			'=SOURCE_ID' => $this->getId(),
		]);
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
}

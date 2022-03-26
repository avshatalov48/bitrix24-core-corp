<?php


namespace Bitrix\Crm\Service\Broker;


use Bitrix\Crm\Service\Broker;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Application;
use Bitrix\Main\UserTable;

class Enumeration extends Broker
{
	protected function loadEntry(int $id): ?array
	{
		$enums = \CUserFieldEnum::GetList(
			[],
			[
				'ID' => $id,
			]
		);
		$enum = $enums->Fetch();

		return (is_array($enum) ? $enum : null);
	}

	/**
	 * @inheritDoc
	 */
	protected function loadEntries(array $ids): array
	{
		$enums = \CUserFieldEnum::GetList(
			[],
			[
				'ID' => $ids,
			]
		);
		$entries = [];
		while($enum = $enums->Fetch())
		{
			$entries[$enum['ID']] = $enum;
		}

		return $entries;
	}
}

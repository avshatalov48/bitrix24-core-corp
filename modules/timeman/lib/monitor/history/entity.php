<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Timeman\Model\Monitor\MonitorEntityTable;

class Entity
{
	public static function record($entities): array
	{
		$publicCodes = array_column($entities, 'PUBLIC_CODE');

		$existingEntities = MonitorEntityTable::getList([
			'select' => [
				'ENTITY_ID' => 'ID',
				'TYPE',
				'TITLE',
				'PUBLIC_CODE',
			],
			'filter' => [
				'@PUBLIC_CODE' => $publicCodes,
			]
		])->fetchAll();

		$existingPublicCodes = array_column($existingEntities, 'PUBLIC_CODE');

		$newCodes = self::findNewCodes($publicCodes, $existingPublicCodes);

		$newEntities = [];
		foreach ($entities as $entity)
		{
			if (in_array($entity['PUBLIC_CODE'], $newCodes, true))
			{
				$newEntities[] = $entity;
			}
		}

		foreach ($newEntities as $index => $newEntity)
		{
			$entityAddResult = MonitorEntityTable::add([
				'TYPE' => $newEntity['TYPE'],
				'TITLE' => $newEntity['TITLE'],
				'PUBLIC_CODE' => $newEntity['PUBLIC_CODE']
			]);

			if ($entityAddResult->isSuccess())
			{
				$newEntities[$index]['ENTITY_ID'] = $entityAddResult->getId();
			}
		}

		return array_merge($existingEntities, $newEntities);
	}

	private static function findNewCodes($neededCodes, $existingCodes): array
	{
		$result = [];
		foreach ($neededCodes as $code)
		{
			if (!in_array($code, $existingCodes, true))
			{
				$result[] = $code;
			}
		}

		return $result;
	}
}
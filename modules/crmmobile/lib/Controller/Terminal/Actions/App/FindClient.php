<?php

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\App;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Integrity\DuplicateCommunicationMatchCodeTable;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Query\Join;

class FindClient extends Action
{
	final public function run(string $phoneNumber): array
	{
		$result = [];

		$entityTypeIds = self::getEntityTypeIds();
		foreach ($entityTypeIds as $entityTypeId => $entityTypeData)
		{
			/** @var DataManager $table */
			$table = $entityTypeData['table'];

			$categoryId = 0;

			$duplicatesList = DuplicateCommunicationMatchCodeTable::getList([
				'select' => ['ENTITY_TYPE_ID', 'ENTITY_ID'],
				'filter' => [
					'=TYPE' => 'PHONE',
					'=VALUE' => DuplicateCommunicationCriterion::normalizePhone($phoneNumber),
					'=ENTITY_TYPE_ID' => $entityTypeId,
				],
				'order' => [
					'ENTITY_TYPE_ID' => 'ASC',
					'ENTITY_ID' => 'ASC'
				],
				'runtime' => [
					new ReferenceField('UA',
						$table::getEntity(),
						[
							'=ref.ID' => 'this.ENTITY_ID',
							'=ref.CATEGORY_ID' => new SqlExpression('?i', $categoryId),
						],
						['join_type' => Join::TYPE_INNER]
					)
				],
				'limit' => 50,
			]);

			$entitiesData = [];
			while ($duplicate = $duplicatesList->fetch())
			{
				$entitiesData[$duplicate['ENTITY_ID']] = [];
			}

			self::prepareEntitiesData($entityTypeId, $entitiesData);
			$entityMultiFieldValues = self::getEntityMultiFieldValues($entityTypeId, array_keys($entitiesData));

			foreach ($entitiesData as $entityId => $entityInfo)
			{
				$result[] = [
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $entityId,
					'TITLE' => $entityInfo['TITLE'] ?? '',
					'POST' => $entityInfo['POST'] ?? '',
					'URL' => $entityInfo['SHOW_URL'] ?? '',
					'PHONE' => $entityMultiFieldValues[$entityId]['PHONE'] ?? null,
					'EMAIL' => $entityMultiFieldValues[$entityId]['EMAIL'] ?? null,
				];
			}
		}

		return $result;
	}

	private static function getEntityTypeIds(): array
	{
		$result = [];

		if (\CCrmContact::CheckReadPermission())
		{
			$result[\CCrmOwnerType::Contact] = [
				'table' => ContactTable::class,
			];
		}

		if (\CCrmCompany::CheckReadPermission())
		{
			$result[\CCrmOwnerType::Company] = [
				'table' => CompanyTable::class,
			];
		}

		return $result;
	}

	private static function prepareEntitiesData(int $entityTypeId, array &$entitiesData): void
	{
		\CCrmOwnerType::PrepareEntityInfoBatch(
			$entityTypeId,
			$entitiesData,
			false
		);
	}

	private static function getEntityMultiFieldValues(int $entityTypeId, array $ids): array
	{
		$multiFieldResult = \CCrmFieldMulti::GetListEx(
			[],
			[
				'=ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeId),
				'@ELEMENT_ID' => $ids,
				'@TYPE_ID' => ['PHONE', 'EMAIL'],
			],
			false,
			false,
			['ELEMENT_ID', 'TYPE_ID', 'VALUE']
		);

		if (!is_object($multiFieldResult))
		{
			return [];
		}

		$result = [];
		while ($multiFields = $multiFieldResult->fetch())
		{
			$entityId = isset($multiFields['ELEMENT_ID']) ? intval($multiFields['ELEMENT_ID']) : 0;
			if ($entityId <= 0)
			{
				continue;
			}

			if (!isset($result[$entityId]))
			{
				$result[$entityId] = [];
			}

			$typeId = $multiFields['TYPE_ID'] ?? '';
			$value = $multiFields['VALUE'] ?? '';
			if ($typeId === '' || $value === '')
			{
				continue;
			}

			if (!isset($result[$entityId][$typeId]))
			{
				$result[$entityId][$typeId] = [$value];
			}
			elseif(count($result[$entityId][$typeId]) < 10)
			{
				$result[$entityId][$typeId][] = $value;
			}
		}

		return $result;
	}
}

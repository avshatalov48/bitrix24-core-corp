<?php

namespace Bitrix\Crm\Update;

use Bitrix\Crm\FieldMultiTable;
use Bitrix\Crm\Model\FieldMultiPhoneCountryTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;

final class RemoveDuplicatingMultifieldsStepper extends Stepper
{
	protected static $moduleId = 'crm';

	function execute(array &$option)
	{
		$lastId = isset($option['lastId']) ? (int)$option['lastId'] : 0;

		$query = FieldMultiTable::query()
			->setSelect(['ID', 'ENTITY_ID', 'ELEMENT_ID', 'TYPE_ID', 'VALUE_TYPE', 'VALUE'])
			->addOrder('ID')
			->setLimit(self::getSingleStepLimit())
		;
		if ($lastId > 0)
		{
			$query->where('ID', '>', $lastId);
		}

		$originals = $query->fetchCollection();

		$allPossibleDuplicateCandidates = FieldMultiTable::query()
			->setSelect(['ID', 'ENTITY_ID', 'ELEMENT_ID', 'TYPE_ID', 'VALUE_TYPE', 'VALUE', 'PHONE_COUNTRY.ID'])
			->whereIn('ENTITY_ID', array_unique($originals->getEntityIdList()))
			->whereIn('ELEMENT_ID', array_unique($originals->getElementIdList()))
			->whereIn('TYPE_ID', array_unique($originals->getTypeIdList()))
			->whereIn('VALUE_TYPE', array_unique($originals->getValueTypeList()))
			->whereIn('VALUE', array_unique($originals->getValueList()))
			->fetchCollection()
		;

		$fmIdsToDelete = [];
		$phoneCountryIdsToDelete = [];
		foreach ($allPossibleDuplicateCandidates as $duplicateCandidate)
		{
			if (isset($fmIdsToDelete[$duplicateCandidate->getId()]))
			{
				// already in delete list - no sense comparing
				continue;
			}

			foreach ($originals as $original)
			{
				if (isset($fmIdsToDelete[$original->getId()]))
				{
					// there can be a duplicate amongst originals too - if we compare against it, we will delete the original
					continue;
				}

				if (
					$duplicateCandidate->getId() !== $original->getId()
					&& $duplicateCandidate->requireEntityId() === $original->requireEntityId()
					&& $duplicateCandidate->requireElementId() === $original->requireElementId()
					&& $duplicateCandidate->requireTypeId() === $original->requireTypeId()
					&& $duplicateCandidate->requireValueType() === $original->requireValueType()
					&& $duplicateCandidate->requireValue() === $original->requireValue()
				)
				{
					$fmIdsToDelete[$duplicateCandidate->getId()] = $duplicateCandidate->getId();

					$phoneCountryId = $duplicateCandidate->requirePhoneCountry()?->getId();
					if ($phoneCountryId > 0)
					{
						$phoneCountryIdsToDelete[$phoneCountryId] = $phoneCountryId;
					}

					break;
				}
			}
		}

		if (!empty($fmIdsToDelete))
		{
			$connection = Application::getConnection();
			$helper = $connection->getSqlHelper();

			foreach (array_chunk($fmIdsToDelete, self::getDeleteChunkSize()) as $fmIdsToDeleteChunk)
			{
				$connection->query(
					'DELETE FROM '
					. $helper->quote(FieldMultiTable::getTableName())
					. ' WHERE ID IN ('
					. implode(',', $fmIdsToDeleteChunk)
					. ');'
				);
			}

			FieldMultiTable::cleanCache();

			if (!empty($phoneCountryIdsToDelete))
			{
				foreach (array_chunk($phoneCountryIdsToDelete, self::getDeleteChunkSize()) as $phoneCountryIdsToDeleteChunk)
				{
					$connection->query(
						'DELETE FROM '
						. $helper->quote(FieldMultiPhoneCountryTable::getTableName())
						. ' WHERE ID IN ('
						. implode(',', $phoneCountryIdsToDeleteChunk)
						. ');'
					);
				}

				FieldMultiPhoneCountryTable::cleanCache();
			}
		}

		$originalsArray = $originals->getAll();
		$lastOriginal = end($originalsArray);
		if ($lastOriginal)
		{
			$option['lastId'] = $lastOriginal->getId();
		}

		if (count($originals) < self::getSingleStepLimit())
		{
			return self::FINISH_EXECUTION;
		}

		return self::CONTINUE_EXECUTION;
	}

	private static function getSingleStepLimit(): int
	{
		return (int)Option::get('crm', 'remove_dup_fm_values_stepper_step_limit', 50);
	}

	private static function getDeleteChunkSize(): int
	{
		return (int)Option::get('crm', 'remove_dup_fm_values_stepper_delete_chunk_size', 500);
	}

	public static function bindOnCrmModuleInstall(): void
	{
		\CAgent::AddAgent(
			/** @see self::execAgent() */
			self::class . '::execAgent();',
			'crm',
			"Y",
			// run once every minute
			60,
			"",
			"Y",
			// 5 min delay
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 300, 'FULL'),
			100,
			false,
			false
		);
	}
}

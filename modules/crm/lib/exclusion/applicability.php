<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Exclusion;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\QuoteTable;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\Result;

/**
 * Class Applicability for exclusions
 *
 * @package Bitrix\Crm\Exclusion
 */
class Applicability
{
	/**
	 * Return true if entity is applicable for exclusion.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @return bool
	 * @throws NotSupportedException
	 */
	public static function isEntityApplicable($entityTypeId, $entityId)
	{
		$list = [$entityId];
		self::filterEntities($entityTypeId, $list);

		return !empty($list);
	}

	public static function checkApplicability(int $entityTypeId, int $entityId): Result
	{
		if (self::isEntityApplicable($entityTypeId, $entityId))
		{
			return new Result();
		}

		$result = new Result();

		if (\CCrmOwnerType::IsClient($entityTypeId))
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_EXCLUSION_APPLICABILITY_ERROR_EMPTY_FM'))
			);
		}
		else
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_EXCLUSION_APPLICABILITY_ERROR_CLIENT_WITH_EMPTY_FM'))
			);
		}

		return $result;
	}

	/**
	 * Filter entity list. Applicable entities stay on.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int[] $list List of entity IDs.
	 * @return void
	 * @throws NotSupportedException
	 */
	public static function filterEntities($entityTypeId, array &$list)
	{
		$entityTypeId = (int) $entityTypeId;
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Contact:
				self::removeFromContacts($list);
				return;

			case \CCrmOwnerType::Company:
				self::removeFromCompanies($list);
				return;

			case \CCrmOwnerType::Lead:
				$entities = [];
				$leads = LeadTable::getList([
					'select' => [
						'ID', 'IS_RETURN_CUSTOMER', 'COMPANY_ID', 'CONTACT_ID',
						'HAS_EMAIL', 'HAS_PHONE'
					],
					'filter' => ['=ID' => $list]
				]);
				foreach ($leads as $entity)
				{
					if ($entity['IS_RETURN_CUSTOMER'] === 'Y')
					{
						$entities[] = [
							'ID' => $entity['ID'],
							'CONTACT_ID' => $entity['CONTACT_ID'],
							'COMPANY_ID' => $entity['COMPANY_ID'],
						];
					}
					elseif ($entity['HAS_EMAIL'] <> 'Y' && $entity['HAS_PHONE'] <> 'Y')
					{
						self::removeFromList($entity['ID'], $list);
					}
				}
				break;

			case \CCrmOwnerType::Deal:
				$entities = DealTable::getList([
					'select' => ['ID', 'COMPANY_ID', 'CONTACT_ID'],
					'filter' => ['=ID' => $list]
				])->fetchAll();
				break;

			case \CCrmOwnerType::Quote:
				$entities = QuoteTable::getList([
					'select' => ['ID', 'COMPANY_ID', 'CONTACT_ID'],
					'filter' => ['=ID' => $list]
				])->fetchAll();
				break;

			default:
				throw new NotSupportedException("Entity type ID `$entityTypeId` not supported.");
		}

		if (empty($entities))
		{
			return;
		}

		$companies = [];
		$contacts = [];
		foreach ($entities as $entity)
		{
			if ($entity['COMPANY_ID'])
			{
				$companies[] = $entity['COMPANY_ID'];
			}
			if ($entity['CONTACT_ID'])
			{
				$contacts[] = $entity['CONTACT_ID'];
			}
		}

		// find & remove contacts without comm data
		if (!empty($contacts))
		{
			self::removeFromContacts($contacts);
		}

		// find & remove companies without comm data
		if (!empty($companies))
		{
			self::removeFromCompanies($companies);
		}

		// remove entities without comm data
		foreach ($entities as $entity)
		{
			if ($entity['CONTACT_ID'] && in_array($entity['CONTACT_ID'], $contacts))
			{
				continue;
			}

			if ($entity['COMPANY_ID'] && in_array($entity['COMPANY_ID'], $companies))
			{
				continue;
			}

			self::removeFromList($entity['ID'], $list);
		}
	}

	protected static function removeFromContacts(&$list)
	{
		$entities = ContactTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=ID' => $list,
				'=HAS_EMAIL' => 'N',
				'=HAS_PHONE' => 'N',
			],
		]);
		foreach ($entities as $entity)
		{
			self::removeFromList($entity['ID'], $list);
		}
	}

	protected static function removeFromCompanies(&$list)
	{
		$entities = CompanyTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=ID' => $list,
				'=HAS_EMAIL' => 'N',
				'=HAS_PHONE' => 'N',
			],
		]);
		foreach ($entities as $entity)
		{
			self::removeFromList($entity['ID'], $list);
		}
	}

	protected static function removeFromList($element, &$list)
	{
		$key = array_search($element, $list);
		if ($key !== false)
		{
			unset($list[$key]);
		}
	}
}

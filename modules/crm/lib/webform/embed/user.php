<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Embed;

use Bitrix\Crm;
use Bitrix\Crm\Entity\Identificator;

/**
 * Class User
 * @package Bitrix\Crm\WebForm\Embed
 */
class User
{
	/**
	 * Get user data from entities.
	 *
	 * @param Identificator\ComplexCollection $entities Entities.
	 * @return array
	 */
	public static function getData(Identificator\ComplexCollection $entities): array
	{
		$data = [
			'fields' => [],
			'entities' => [],
		];
		foreach ($entities as $entity)
		{
			/** @var Identificator\Complex $entity */
			$data['entities'][] = [
				'typeId' => $entity->getTypeId(),
				'id' => $entity->getId(),
			];
			$data['fields'] = $data['fields'] + self::getEntityFields($entity);
		}

		return $data;
	}

	protected static function getEntityFields(Identificator\Complex $entity): array
	{
		$data = [
			/*
			'name' => '',
			'last-name' => '',
			'email' => '',
			'phone' => '',
			'second-name' => '',
			'company-name' => '',
			*/
		];

		switch ($entity->getTypeId())
		{
			case \CCrmOwnerType::Deal:
				return self::getClientDataByFields(
					Crm\DealTable::getRow([
						'select' => ['CONTACT_ID', 'COMPANY_ID'],
						'filter' => ['=ID' => $entity->getId()]
					])
				);

			case \CCrmOwnerType::Quote:
				return self::getClientDataByFields(
					Crm\QuoteTable::getRow([
						'select' => ['CONTACT_ID', 'COMPANY_ID'],
						'filter' => ['=ID' => $entity->getId()]
					])
				);

			case \CCrmOwnerType::Company:
				$map = [
					'TITLE' => 'company-name',
				];
				$fields = Crm\CompanyTable::getRow([
					'select' => array_keys($map),
					'filter' => ['=ID' => $entity->getId()]
				]) ?: [];
				return self::getDataByFieldsMap($fields, $map);

			case \CCrmOwnerType::Lead:
				$map = [
					'NAME' => 'name',
					'LAST_NAME' => 'last-name',
					'SECOND_NAME' => 'second-name',
					'EMAIL_WORK' => 'email',
					'EMAIL_MAILING' => 'email',
					'EMAIL_HOME' => 'email',
					'PHONE_WORK' => 'phone',
					'PHONE_MAILING' => 'phone',
					'PHONE_MOBILE' => 'phone',
					'COMPANY_TITLE' => 'company-name',
					'COMPANY_ID' => '',
					'CONTACT_ID' => '',
				];
				$fields = Crm\LeadTable::getRow([
					'select' => array_keys($map),
					'filter' => ['=ID' => $entity->getId()]
				]);
				return self::getClientDataByFields($fields) + self::getDataByFieldsMap($fields, $map);

			case \CCrmOwnerType::Contact:
				$map = [
					'NAME' => 'name',
					'LAST_NAME' => 'last-name',
					'SECOND_NAME' => 'second-name',
					'EMAIL_WORK' => 'email',
					'EMAIL_MAILING' => 'email',
					'EMAIL_HOME' => 'email',
					'PHONE_WORK' => 'phone',
					'PHONE_MAILING' => 'phone',
					'PHONE_MOBILE' => 'phone',
					'COMPANY_ID' => '',
				];
				$fields = Crm\ContactTable::getRow([
					'select' => array_keys($map),
					'filter' => ['=ID' => $entity->getId()]
				]);
				return self::getDataByFieldsMap($fields, $map) + self::getClientDataByFields($fields);

			default:
				if (!\CCrmOwnerType::isPossibleDynamicTypeId($entity->getTypeId()))
				{
					break;
				}

				$dynamicFactory = Crm\Service\Container::getInstance()->getFactory($entity->getTypeId());
				$dynamicItem = $dynamicFactory->getItem($entity->getId());
				if (!$dynamicItem)
				{
					break;
				}

				if (!$dynamicItem->getContactId() && !$dynamicItem->getCompanyId())
				{
					break;
				}

				return self::getClientDataByFields([
					'CONTACT_ID' => $dynamicItem->getContactId(),
					'COMPANY_ID' => $dynamicItem->getCompanyId(),
				]);
		}

		return $data;
	}

	protected static function getDataByFieldsMap($fields, array $map): array
	{
		$data = [];
		if (!is_array($fields))
		{
			return $data;
		}

		static $templatedData = null;
		if ($templatedData === null)
		{
			$templatedData = Crm\WebForm\Entity::getMap();
			$templatedData = [
				'name' => $templatedData['CONTACT']['FIELD_AUTO_FILL_TEMPLATE']['NAME']['TEMPLATE'],
				'company-name' => $templatedData['COMPANY']['FIELD_AUTO_FILL_TEMPLATE']['TITLE']['TEMPLATE']
			];
		}

		foreach ($map as $fieldKey => $dataKey)
		{
			if (empty($fields[$fieldKey]) || !$dataKey)
			{
				continue;
			}

			if (!empty($templatedData[$dataKey]))
			{
				if ($fields[$fieldKey] === $templatedData[$dataKey])
				{
					continue;
				}
			}

			if ($dataKey === 'name' && $fields[$fieldKey] === $templatedData[$dataKey])
			{
				continue;
			}

			$data[$dataKey] = $fields[$fieldKey];
		}

		return $data;
	}

	protected static function getClientDataByFields($fields): array
	{
		if (!is_array($fields))
		{
			return [];
		}

		$contactFields = $fields['CONTACT_ID']
			? self::getEntityFields(new Identificator\Complex(
				\CCrmOwnerType::Contact,
				$fields['CONTACT_ID']
			))
			: [];
		$companyFields = $fields['COMPANY_ID']
			? self::getEntityFields(new Identificator\Complex(
				\CCrmOwnerType::Company,
				$fields['COMPANY_ID']
			))
			: [];
		return $contactFields + $companyFields;
	}
}

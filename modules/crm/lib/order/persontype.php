<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class PersonType
 * @package Bitrix\Crm\Order
 */
class PersonType extends Sale\PersonType
{
	public const CRM_COMPANY = 'CRM_COMPANY';
	public const CRM_CONTACT = 'CRM_CONTACT';

	public static function getCompanyPersonTypeId()
	{
		return self::getPersonTypeId(static::CRM_COMPANY);
	}

	public static function getContactPersonTypeId()
	{
		return self::getPersonTypeId(static::CRM_CONTACT);
	}

	private static function getPersonTypeId($code)
	{
		if ($code === self::CRM_COMPANY)
		{
			$businessValueDomain = Sale\BusinessValue::ENTITY_DOMAIN;
		}
		elseif ($code === self::CRM_CONTACT)
		{
			$businessValueDomain = Sale\BusinessValue::INDIVIDUAL_DOMAIN;
		}
		else
		{
			throw new Main\SystemException('Supported only company or contact entities');
		}

		$personTypeRaw = static::getList([
			'filter' => [
				'=CODE' => $code,
			],
			'select' => ['ID'],
			'limit' => 1
		]);
		if ($personType = $personTypeRaw->fetch())
		{
			return $personType['ID'];
		}

		$personTypeRaw = static::getList([
			'filter' => [
				'BIZVAL.DOMAIN' => $businessValueDomain,
			],
			'select' => ['ID'],
			'runtime' => [
				new \Bitrix\Main\Entity\ReferenceField(
					'BIZVAL',
					'Bitrix\Sale\Internals\BusinessValuePersonDomainTable',
					[
						'=this.ID' => 'ref.PERSON_TYPE_ID'
					],
					['join_type' => 'INNER']
				),
			],
			'limit' => 1
		]);

		if ($personType = $personTypeRaw->fetch())
		{
			return $personType['ID'];
		}

		return null;
	}
}
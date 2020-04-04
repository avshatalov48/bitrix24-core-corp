<?php

namespace Bitrix\Crm\Ads;

use Bitrix\Seo\Retargeting\Audience;
use Bitrix\Seo\Retargeting\Service;

/**
 * Class AdsAudience.
 * @package Bitrix\Crm\Ads
 */
class AdsAudience extends \Bitrix\Seo\Retargeting\AdsAudience
{
	/** @var array $logs Log messages. */
	protected static $logs = array();

	/** @var bool $isQueueUsed Is queue used. */
	protected static $isQueueUsed = false;

	/**
	 * Use queue.
	 *
	 * @return void
	 */
	public static function useQueue()
	{
		static::$isQueueUsed = true;
	}

	/**
	 * Add to audience from entity.
	 *
	 * @param integer $entityTypeId Entity type ID.
	 * @param integer $entityId Entity ID.
	 * @param \Bitrix\Seo\Retargeting\AdsAudienceConfig $config Configuration.
	 * @return bool
	 */
	public static function addFromEntity($entityTypeId, $entityId, \Bitrix\Seo\Retargeting\AdsAudienceConfig $config)
	{
		$authAdapter = Service::getAuthAdapter($config->type);
		if (!$authAdapter->hasAuth())
		{
			return false;
		}

		$addresses = self::getAddresses($entityTypeId, $entityId);
		if ($config->contactType && empty($addresses[$config->contactType]))
		{
			return false;
		}

		return self::addToAudience($config, $addresses);
	}

	/**
	 * Extract phones and emails from entity
	 * @param string $entityTypeId Entity type.
	 * @param int $entityId Entity id.
	 * @return array
	 */
	protected static function getAddresses($entityTypeId, $entityId)
	{
		$result = array();

		$multiFieldTypeToAudienceContactTypeMap = array(
			\CCrmFieldMulti::EMAIL => Audience::ENUM_CONTACT_TYPE_EMAIL,
			\CCrmFieldMulti::PHONE => Audience::ENUM_CONTACT_TYPE_PHONE,
		);

		$entityFilterList = array();
		if (in_array($entityTypeId, array(\CCrmOwnerType::Deal, \CCrmOwnerType::Quote, \CCrmOwnerType::Invoice)))
		{
			$companyFieldCode = 'COMPANY_ID';
			$contactFieldCode = 'CONTACT_ID';
			$subFilter = array('=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N');
			switch ($entityTypeId)
			{
				case \CCrmOwnerType::Deal:
					$entityDb = \CCrmDeal::getListEx(array(), $subFilter);
					break;

				case \CCrmOwnerType::Quote:
					$entityDb = \CCrmQuote::getList(array(), $subFilter);
					break;

				case \CCrmOwnerType::Invoice:
					$companyFieldCode = 'UF_COMPANY_ID';
					$contactFieldCode = 'UF_CONTACT_ID';
					$entityDb = \CCrmInvoice::getList(array(), $subFilter);
					break;

				default:
					return $result;
			}

			$entityData = $entityDb->fetch();
			if (isset($entityData[$contactFieldCode]) && $entityData[$contactFieldCode])
			{
				$entityFilterList[\CCrmOwnerType::Contact] = $entityData[$contactFieldCode];
			}
			if (isset($entityData[$companyFieldCode]) && $entityData[$companyFieldCode])
			{
				$entityFilterList[\CCrmOwnerType::Company] = $entityData[$companyFieldCode];
			}
		}
		else
		{
			$entityFilterList[$entityTypeId] = $entityId;
		}

		foreach ($entityFilterList as $entityTypeId => $entityId)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
			if (!$entityTypeName)
			{
				continue;
			}
			if (!$entityId)
			{
				continue;
			}

			$multiFieldDb = \CCrmFieldMulti::GetListEx(
				null,
				array(
					'ENTITY_ID' => $entityTypeName,
					'ELEMENT_ID' => $entityId,
					'TYPE_ID' => array(
						\CCrmFieldMulti::EMAIL,
						\CCrmFieldMulti::PHONE
					)
				)
			);
			while($multiField = $multiFieldDb->Fetch())
			{
				if (!isset($multiFieldTypeToAudienceContactTypeMap[$multiField['TYPE_ID']]))
				{
					continue;
				}

				$contactType = $multiFieldTypeToAudienceContactTypeMap[$multiField['TYPE_ID']];
				if (!is_array($result[$contactType]))
				{
					$result[$contactType] = array();
				}

				$result[$contactType][] = $multiField['VALUE'];
			}
		}
		return $result;
	}
}
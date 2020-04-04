<?php

namespace Bitrix\Crm\Ads;

use Bitrix\Main\Localization\Loc;

use Bitrix\Seo\Retargeting\Audience;
use Bitrix\Seo\Retargeting\Service;

/**
 * Class AdsAudience.
 * @package Bitrix\Crm\Ads
 */
class AdsAudience extends AdsService
{
	/** @var bool $isQueueUsed Is queue used. */
	protected static $isQueueUsed = false;

	/**
	 * Use queue.
	 *
	 * @return void
	 */
	public static function useQueue()
	{
		self::$isQueueUsed = true;
	}

	/**
	 * Get service.
	 *
	 * @return Service
	 */
	public static function getService()
	{
		return Service::getInstance();
	}

	/**
	 * Add audience.
	 *
	 * @param string $type Type.
	 * @param integer|null $accountId Account ID.
	 * @param string|null $name Name.
	 * @return integer|null
	 */
	public static function addAudience($type, $accountId = null, $name = null)
	{
		$audience = Service::getAudience($type);
		if (!$audience)
		{
			return null;
		}

		$audience->setAccountId($accountId);
		$parameters = array(
			'NAME' => $name ?: Loc::getMessage('')
		);
		$addResult = $audience->add($parameters);
		if ($addResult->isSuccess() && $addResult->getId())
		{
			return $addResult->getId();
		}
		else
		{
			self::$errors = $addResult->getErrorMessages();
			return null;
		}
	}

	/**
	 * Get audiences.
	 *
	 * @param string $type Type.
	 * @param integer|null $accountId Account ID.
	 * @return array
	 */
	public static function getAudiences($type, $accountId = null)
	{
		$result = array();

		$audience = Service::getAudience($type);

		$audience->setAccountId($accountId);
		$audiencesResult = $audience->getList();
		if ($audiencesResult->isSuccess())
		{
			while ($audienceData = $audiencesResult->fetch())
			{
				$audienceData = $audience->normalizeListRow($audienceData);
				if ($audienceData['ID'])
				{
					$result[] = array(
						'id' => $audienceData['ID'],
						'isSupportMultiTypeContacts' => $audience->isSupportMultiTypeContacts(),
						//'isAddingRequireContacts' => $audience->isAddingRequireContacts(),
						'supportedContactTypes' => $audienceData['SUPPORTED_CONTACT_TYPES'],
						'name' =>
							$audienceData['NAME']
								?
								$audienceData['NAME'] . (
								$audienceData['COUNT_VALID'] ?
									' (' . $audienceData['COUNT_VALID'] . ')'
									:
									''
								)
								:
								$audienceData['ID']
					);
				}
			}
		}
		else
		{
			self::$errors = $audiencesResult->getErrorMessages();
		}

		return $result;
	}

	/**
	 * Get providers.
	 *
	 * @param array|null $types Types.
	 * @return array
	 */
	public static function getProviders(array $types = null)
	{
		$providers = static::getServiceProviders($types);
		foreach ($providers as $type => $provider)
		{
			$audience = Service::getAudience($type);
			$providers[$type]['URL_AUDIENCE_LIST'] =  $audience->getUrlAudienceList();
			$providers[$type]['IS_SUPPORT_ACCOUNT'] =  $audience->isSupportAccount();
			$providers[$type]['IS_SUPPORT_REMOVE_CONTACTS'] =  $audience->isSupportRemoveContacts();
			//$providers[$type]['IS_ADDING_REQUIRE_CONTACTS'] =  $audience->isAddingRequireContacts();
			$providers[$type]['IS_SUPPORT_MULTI_TYPE_CONTACTS'] =  $audience->isSupportMultiTypeContacts();
		}

		return $providers;
	}

	/**
	 * Add to audience from entity.
	 *
	 * @param integer $entityTypeId Entity type ID.
	 * @param integer $entityId Entity ID.
	 * @param AdsAudienceConfig $config Configuration.
	 * @return bool
	 */
	public static function addFromEntity($entityTypeId, $entityId, AdsAudienceConfig $config)
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

	public static function addToAudience(AdsAudienceConfig $config, $contacts)
	{
		static $audiences = array();
		if (!isset($audiences[$config->type]))
		{
			$audience = Service::getAudience($config->type);
			$audiences[$config->type] = $audience;
		}
		else
		{
			$audience = $audiences[$config->type];
		}

		$audience->setAccountId($config->accountId);
		static::$isQueueUsed ? $audience->enableQueueMode() : $audience->disableQueueMode();
		if ($config->autoRemoveDayNumber)
		{
			$audience->enableQueueAutoRemove($config->autoRemoveDayNumber);
		}
		else
		{
			$audience->disableQueueAutoRemove();
		}

		$audienceImportResult = $audience->addContacts(
			$config->audienceId,
			$contacts,
			array(
				'type' => $config->contactType
			)
		);

		self::$errors = $audienceImportResult->getErrorMessages();
		return $audienceImportResult->isSuccess();
	}

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
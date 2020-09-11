<?php

namespace Bitrix\Voximplant\Integration\Crm;

use Bitrix\Crm\EntityManageFacility;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Voximplant\Call;
use Bitrix\Voximplant\Model\CallTable;

use Bitrix\Main\PhoneNumber\Parser;

class EntityManagerRegistry
{
	/** @var EntityManageFacility[] */
	protected static $instances = array();

	/**
	 * Returns EntityManageFacility for the specified call.
	 * @param Call $call
	 * @return EntityManageFacility|false
	 * @throws SystemException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getWithCall(Call $call)
	{
		if(static::$instances[$call->getCallId()])
		{
			return static::$instances[$call->getCallId()];
		}

		$facilityInstance = new \Bitrix\Crm\EntityManageFacility();
		$facilityInstance->setUpdateClientMode(EntityManageFacility::UPDATE_MODE_NONE);
		$facilityInstance->disableAutomationRun();

		if ($call->getPrimaryEntityType() != '' && $call->getPrimaryEntityId() > 0)
		{
			$facilityInstance->getSelector()->setEntity(
				\CCrmOwnerType::ResolveID($call->getPrimaryEntityType()),
				$call->getPrimaryEntityId()
			);

			foreach ($call->getCrmBindings() as $binding)
			{
				if(isset($binding['OWNER_TYPE_NAME']) && isset($binding['OWNER_ID']))
				{
					$facilityInstance->getSelector()->setEntity(
						\CCrmOwnerType::ResolveID($binding['OWNER_TYPE_NAME']),
						(int)$binding['OWNER_ID']
					);
				}
				else if (isset($binding['OWNER_TYPE_ID']) && isset($binding['OWNER_ID']))
				{
					$facilityInstance->getSelector()->setEntity(
						(int)$binding['OWNER_TYPE_ID'],
						(int)$binding['OWNER_ID']
					);
				}
			}
		}
		else if($call->getCallerId() != '')
		{
			$facilityInstance->getSelector()->appendPhoneCriterion($call->getCallerId());

			$config = $call->getConfig();

			if($config['PORTAL_MODE'] !== \CVoxImplantConfig::MODE_SIP)
			{
				$portalNumber = $call->getPortalNumber();
				if(mb_substr($portalNumber, 0, 1) != '+')
				{
					$portalNumber = '+' . $portalNumber;
				}
				$parsedPortalNumber = Parser::getInstance()->parse($portalNumber);
				if($parsedPortalNumber->isValid())
				{
					$country = Parser::getInstance()->parse($portalNumber)->getCountry();

					$parsedNumber = Parser::getInstance()->parse($call->getCallerId(), $country);
					if($parsedNumber->isValid())
					{
						$facilityInstance->getSelector()->appendPhoneCriterion($parsedNumber->getNationalNumber());
					}
				}
			}
		}
		else
		{
			return false;
		}

		$facilityInstance->getSelector()->search();

		static::$instances[$call->getCallId()] = $facilityInstance;
		return $facilityInstance;
	}
}
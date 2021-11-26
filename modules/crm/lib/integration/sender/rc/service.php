<?php

namespace Bitrix\Crm\Integration\Sender\Rc;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Sender;

/**
 * Class Service
 *
 * @package Bitrix\Crm\Integration\Sender\Rc
 */
class Service
{
	/**
	 * Return true if can use.
	 * @return bool
	 */
	public static function canUse()
	{
		if (!Loader::includeModule('sender'))
		{
			return false;
		}

		return class_exists('\Bitrix\Sender\Integration\Crm\ReturnCustomer\Service');
	}

	/**
	 * Return true if can use.
	 * @return bool
	 */
	public static function isAvailable()
	{
		if (!self::canUse())
		{
			return false;
		}

		return Sender\Integration\Bitrix24\Service::isRcAvailable();
	}

	/**
	 * Return true if current user can use.
	 * @return bool
	 */
	public static function canCurrentUserUse()
	{
		if (!self::canUse())
		{
			return false;
		}

		return Sender\Integration\Crm\ReturnCustomer\Service::canCurrentUserUse();
	}

	/**
	 * Get path to lead add.
	 * @return bool
	 */
	public static function getPathToAddLead()
	{
		if (!self::canUse())
		{
			return null;
		}

		return '/marketing/rc/edit/0/?code=rc_lead&isOutside=Y';
	}

	/**
	 * Get path to lead add.
	 * @return bool
	 */
	public static function getPathToAddDeal()
	{
		if (!self::canUse())
		{
			return null;
		}

		return '/marketing/rc/edit/0/?code=rc_deal&isOutside=Y';
	}

	/**
	 * Get name.
	 * @return bool
	 */
	public static function getName()
	{
		return Loc::getMessage('CRM_INTEGRATION_SENDER_RC_SERVICE_NAME');
	}

	/**
	 * Init js-extensions.
	 * @return void
	 */
	public static function initJsExtensions()
	{
		\CJSCore::init('sidepanel');
		Sender\Integration\Bitrix24\Service::initLicensePopup();
	}

	/**
	 * Get js available popup shower.
	 * @return bool
	 */
	public static function getJsAvailablePopupShower()
	{
		return "BX.Sender.B24License.showPopup('Rc');";
	}

	/**
	 * Get deal worker count.
	 * @param int|null $categoryId Deal category ID.
	 * @return bool
	 */
	public static function getDealWorkerCount($categoryId = null)
	{
		if (!self::canUse())
		{
			return null;
		}

		$items = Sender\Entity\Rc::getList([
			'select' => ['MESSAGE_ID'],
			'filter' => [
				'=REITERATE' => 'Y',
				'=STATUS' => Sender\Dispatch\Semantics::getWorkStates(),
				'=MESSAGE_CODE' => 'rc_deal'
			],
			'cache' => ['ttl' => 36000]
		])->fetchAll();

		$fields = Sender\Internals\Model\MessageFieldTable::getList([
			'select' => ['VALUE'],
			'filter'=> [
				'=MESSAGE_ID'=> array_column($items, 'MESSAGE_ID'),
				'=CODE' => 'CATEGORY_ID'
			],
			'cache' => ['ttl' => 36000]
		])->fetchAll();

		return count(
			$categoryId !== null
				? array_filter(
						$fields,
						function ($field) use ($categoryId)
						{
							return mb_strlen($field['VALUE']) && $field['VALUE'] == $categoryId;
						}
					)
				: $fields
		);
	}

	public static function getDealWorkerUrl($categoryId = null)
	{
		if (!self::canUse())
		{
			return null;
		}

		$uri = new Uri('/marketing/rc/');
		$uri->addParams([
			'apply_filter' => 'Y',
			'REITERATE' => 'Y',
			'STATE' => Sender\Dispatch\Semantics::getWorkStates(),
			'MESSAGE_CODE' => 'rc_deal',
		]);
		if ($categoryId !== null)
		{
			$uri->addParams([
				'CATEGORY_ID' => $categoryId == '0' ? 'common' : $categoryId
			]);
		}
		return $uri->getLocator();
	}

	public static function getLeadWorkerUrl()
	{
		if (!self::canUse())
		{
			return null;
		}

		$uri = new Uri('/marketing/rc/');
		$uri->addParams([
			'apply_filter' => 'Y',
			'REITERATE' => 'Y',
			'STATE' => Sender\Dispatch\Semantics::getWorkStates(),
			'MESSAGE_CODE' => 'rc_lead',
		]);

		return $uri->getLocator();
	}
}

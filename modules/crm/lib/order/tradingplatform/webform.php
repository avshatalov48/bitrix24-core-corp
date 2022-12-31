<?php

namespace Bitrix\Crm\Order\TradingPlatform;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class WebForm
 * @package Bitrix\Crm\TradingPlatform
 */
class WebForm
	extends Platform
	implements Sale\TradingPlatform\IRestriction
{
	public const CODE_DELIMITER = '_';
	public const TRADING_PLATFORM_CODE = 'webform';

	protected $webForm = [];

	/**
	 * @return string
	 */
	protected function getName(): string
	{
		$data = $this->getInfo();

		return Main\Localization\Loc::getMessage(
			'CRM_ORDER_TRADING_PLATFORM_WEB_FORM',
			[
				'#WEB_FORM#' => $data['name']
			]
		);
	}

	public static function onWebFormAdd(Main\Event $event)
	{
		$fields = $event->getParameter('fields');
		if (!self::isOrderSupported($fields))
		{
			return;
		}

		$id = (int)$event->getParameter('id');
		$webForm = static::getInstanceByCode(static::getCodeByFormId($id));
		if (!$webForm->isInstalled())
		{
			$webForm->install();
		}
	}

	public static function onWebFormDelete(Main\Event $event)
	{
		$id = (int)$event->getParameter('id')['ID'];
		if ($id > 0)
		{
			$webForm = static::getInstanceByCode(static::getCodeByFormId($id));
			if ($webForm->isInstalled())
			{
				$webForm->uninstall();
			}
		}
	}

	public static function onWebFormUpdate(Main\Event $event)
	{
		$id = (int)$event->getParameter('id')['ID'];
		$fields = $event->getParameter('fields');

		$webForm = static::getInstanceByCode(static::getCodeByFormId($id));
		if (!$webForm->isInstalled())
		{
			if (!self::isOrderSupported($fields))
			{
				return;
			}

			if (!$webForm->install())
			{
				return;
			}
		}
		elseif (!self::isOrderSupported($fields))
		{
			$webForm->unsetActive();

			return;
		}

		if (isset($fields['ACTIVE']) && $fields['ACTIVE'] === 'Y')
		{
			$webForm->setActive();
		}
		else
		{
			$webForm->unsetActive();
		}
	}

	protected static function isOrderSupported(array $fields)
	{
		if (isset($fields['ENTITY_SCHEME']))
		{
			return Crm\WebForm\Entity::isSchemeSupportEntity($fields['ENTITY_SCHEME'], \CCrmOwnerType::Invoice);
		}

		return false;
	}

	/**
	 * @return int
	 */
	protected function getWebFormId()
	{
		return (int)mb_substr($this->getCode(), mb_strrpos($this->getCode(), '_') + 1);
	}

	/**
	 * @param $id
	 * @return string
	 */
	public static function getCodeByFormId($id)
	{
		return static::TRADING_PLATFORM_CODE.static::CODE_DELIMITER.$id;
	}

	/**
	 * @return array
	 */
	public function getInfo()
	{
		if (!$this->webForm)
		{
			$id = $this->getWebFormId();
			$this->webForm = Crm\WebForm\Options::create($id)->getArray();
		}

		return $this->webForm;
	}
}

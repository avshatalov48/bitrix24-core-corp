<?php

namespace Bitrix\Crm\Order\TradingPlatform;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;

Main\Localization\Loc::loadMessages(__FILE__);

class DynamicEntity extends Platform
{
	public const CODE_DELIMITER = '_';
	public const TRADING_PLATFORM_CODE = 'dynamic';

	protected $entity = [];

	/**
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function getName(): string
	{
		$data = $this->getInfo();

		return Main\Localization\Loc::getMessage(
			'CRM_ORDER_TRADING_PLATFORM_DYNAMIC_ENTITY',
			[
				'#DYNAMIC_ENTITY#' => $data['TITLE']
			]
		);
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getInfo() : array
	{
		if (!$this->entity)
		{
			$id = $this->getEntityTypeId();

			$type = Crm\Service\Container::getInstance()->getTypeByEntityTypeId($id);
			$this->entity = $type ? $type->collectValues() : [];
		}

		return $this->entity;
	}

	/**
	 * @return int
	 */
	protected function getEntityTypeId() : int
	{
		return (int)mb_substr($this->getCode(), mb_strrpos($this->getCode(), '_') + 1);
	}

	/**
	 * @param $id
	 * @return string
	 */
	public static function getCodeByEntityTypeId($id) : string
	{
		return static::TRADING_PLATFORM_CODE.static::CODE_DELIMITER.$id;
	}

	public static function onEntityAdd(Main\Event $event)
	{
		/** @var Crm\Model\Dynamic\Type $object */
		$object = $event->getParameter('object');

		if (!$object->getIsPaymentsEnabled())
		{
			return;
		}

		$platform = static::getInstanceByCode(static::getCodeByEntityTypeId($object->getEntityTypeId()));
		if (!$platform->isInstalled())
		{
			$platform->install();
		}
	}

	public static function onEntityDelete(Main\Event $event)
	{
		/** @var Crm\Model\Dynamic\Type $object */
		$object = $event->getParameter('object');

		$platform = static::getInstanceByCode(static::getCodeByEntityTypeId($object->getEntityTypeId()));
		if ($platform->isInstalled())
		{
			$platform->uninstall();
		}
	}

	public static function onEntityUpdate(Main\Event $event)
	{
		/** @var Crm\Model\Dynamic\Type $object */
		$object = $event->getParameter('object');

		$platform = static::getInstanceByCode(static::getCodeByEntityTypeId($object->getEntityTypeId()));
		if (!$platform->isInstalled())
		{
			if ($object->getIsPaymentsEnabled())
			{
				$platform->install();
			}

			return;
		}

		if (!$object->getIsPaymentsEnabled())
		{
			$platform->unsetActive();
		}
		else
		{
			$platform->setActive();
		}

		$fields = $event->getParameter('fields');
		if (isset($fields['TITLE']))
		{
			Sale\TradingPlatformTable::update(
				$platform->getId(),
				[
					'NAME' => $platform->getName(),
				]
			);
		}
	}
}

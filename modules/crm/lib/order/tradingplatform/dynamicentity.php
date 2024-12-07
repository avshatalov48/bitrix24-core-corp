<?php

namespace Bitrix\Crm\Order\TradingPlatform;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Sale;

class DynamicEntity
	extends Platform
	implements Sale\TradingPlatform\IRestriction
{
	protected $entity;

	/**
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function getName(): string
	{
		return \CCrmOwnerType::GetDescription($this->getEntityTypeId());
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
		return \CCrmOwnerType::ResolveID($this->getCode());
	}

	public function getAnalyticCode()
	{
		return $this->getCode();
	}

	/**
	 * @param $id
	 * @return string
	 */
	public static function getCodeByEntityTypeId($id) : string
	{
		return mb_strtolower(\CCrmOwnerType::ResolveName($id));
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

		$object->fill(Main\ORM\Fields\FieldTypeMask::FLAT);

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

<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Item;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;

class DynamicController extends FactoryBasedController
{
	public const ADD_EVENT_NAME = 'timeline_dynamic_add';
	public const REMOVE_EVENT_NAME = 'timeline_dynamic_remove';
	public const RESTORE_EVENT_NAME = 'timeline_dynamic_restore';

	protected $entityTypeId;

	protected function __construct(int $entityTypeId)
	{
		parent::__construct();
		$this->entityTypeId = $entityTypeId;
	}

	public static function getInstance(int $entityTypeId = null): FactoryBasedController
	{
		if ($entityTypeId <= 0)
		{
			throw new ArgumentException('Invalid value for $entityTypeId', 'entityTypeId');
		}

		$identifier = static::getServiceLocatorIdentifier($entityTypeId);

		if (!ServiceLocator::getInstance()->has($identifier))
		{
			$instance = new static($entityTypeId);
			ServiceLocator::getInstance()->addInstance($identifier, $instance);
		}

		return ServiceLocator::getInstance()->get($identifier);
	}

	protected static function getServiceLocatorIdentifier(int $entityTypeId = null): string
	{
		return parent::getServiceLocatorIdentifier() . ".$entityTypeId";
	}

	protected function getTrackedFieldNames(): array
	{
		return [
			Item::FIELD_NAME_TITLE,
			Item::FIELD_NAME_ASSIGNED,
			Item::FIELD_NAME_CATEGORY_ID,
			Item::FIELD_NAME_STAGE_ID,
			Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY,
		];
	}

	public function getEntityTypeID(): int
	{
		return $this->entityTypeId;
	}
}
<?php

namespace Bitrix\Crm\Entity\Traits;

use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use CCrmOwnerType;

/**
 * Trait VisibilityConfig
 * @package Bitrix\Crm\Entity\Traits
 */
trait VisibilityConfig
{
	private $entityFieldVisibilityConfigs = [];
	private static $instance;

	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @param $entityTypeId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function prepareEntityFieldVisibilityConfigs($entityTypeId): array
	{
		if (!isset($this->entityFieldVisibilityConfigs[$entityTypeId]))
		{
			$this->entityFieldVisibilityConfigs[$entityTypeId]
				= VisibilityManager::getUserFieldsAccessCodesAndData($entityTypeId);
		}
		return $this->entityFieldVisibilityConfigs[$entityTypeId];
	}
}
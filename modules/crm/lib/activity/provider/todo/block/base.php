<?php

namespace Bitrix\Crm\Activity\Provider\ToDo\Block;

use Bitrix\Crm\Activity\Provider\ToDo\OptionallyConfigurable;
use Bitrix\Crm\Activity\Provider\ToDo\SaveConfig;
use Bitrix\Crm\Service\Container;

abstract class Base implements BlockInterface
{
	protected array $blockData;
	protected array $activityData;

	public function __construct(array $blockData = [], array $activityData = [])
	{
		$this->blockData = $blockData;
		$this->activityData = $activityData;
	}

	final protected function getResponsibleId(): int
	{
		return ($this->activityData['RESPONSIBLE_ID'] ?? Container::getInstance()->getContext()->getUserId());
	}

	public function fetchSettings(): array
	{
		return [];
	}

	public function getOptions(OptionallyConfigurable $entity): array
	{
		return [];
	}

	public function prepareEntityBefore(OptionallyConfigurable $entity): SaveConfig
	{
		return new SaveConfig(false);
	}
}

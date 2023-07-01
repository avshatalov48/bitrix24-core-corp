<?php

namespace Bitrix\Crm\Activity\Settings\Section;

use Bitrix\Crm\Activity\Settings\SettingsInterface;
use Bitrix\Crm\Service\Container;

abstract class Base implements SettingsInterface
{
	protected array $data;
	protected array $activityData;

	public function __construct(array $data = [], array $activityData = [])
	{
		$this->data = $data;
		$this->activityData = $activityData;
	}

	final protected function getResponsibleId(): int
	{
		return ($this->activityData['RESPONSIBLE_ID'] ?? Container::getInstance()->getContext()->getUserId());
	}
}

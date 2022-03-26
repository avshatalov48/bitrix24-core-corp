<?php

namespace Bitrix\Disk\Internals\Engine\ActionFilter;

use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class B24Feature extends ActionFilter\Base
{
	public const ERROR_BLOCKED_BY_FEATURE = 'blocked_by_feature';

	/** @var string */
	private $feature;

	public function __construct(string $feature)
	{
		parent::__construct();

		$this->feature = $feature;
	}

	public function onBeforeAction(Event $event)
	{
		if (!Bitrix24Manager::isFeatureEnabled($this->feature))
		{
			$this->addError(new Error('Feature is not available', self::ERROR_BLOCKED_BY_FEATURE));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
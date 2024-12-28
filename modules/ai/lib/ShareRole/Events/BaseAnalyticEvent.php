<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Events;

use Bitrix\AI\ShareRole\Events\Enums\ShareType;
use Bitrix\AI\ShareRole\Events\Enums\Status;
use Bitrix\Main\Analytics\AnalyticsEvent;

abstract class BaseAnalyticEvent
{
	protected const DEFAULT_TOOL = 'ai';
	protected const DEFAULT_CATEGORY = 'roles_saving';

	public function __construct(
		protected ?ShareType $shareType = null,
	)
	{
	}

	abstract public function getEventName(): string;

	public function send(Status $status): void
	{
		$event = $this->getAnalyticEvent()
			->setStatus($status->value);

		if (!empty($this->shareType))
		{
			$event->setP1($this->shareType->value);
		}

		$event->send();
	}

	protected function getAnalyticEvent(): AnalyticsEvent
	{
		return new AnalyticsEvent(
			$this->getEventName(),
			static::DEFAULT_TOOL,
			static::DEFAULT_CATEGORY
		);
	}
}

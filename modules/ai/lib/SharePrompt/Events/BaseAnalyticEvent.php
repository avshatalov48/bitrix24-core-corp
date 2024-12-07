<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Events;

use Bitrix\AI\SharePrompt\Events\Enums\ShareType;
use Bitrix\AI\SharePrompt\Events\Enums\Status;
use Bitrix\AI\SharePrompt\Enums\Category;
use Bitrix\Main\Analytics\AnalyticsEvent;

abstract class BaseAnalyticEvent
{
	protected const DEFAULT_TOOL = 'ai';
	protected const DEFAULT_CATEGORY = 'prompt_saving';

	public function __construct(
		protected ?Category $category = null,
		protected ?ShareType $shareType = null,
	)
	{
	}

	abstract public function getEventName(): string;

	public function send(Status $status)
	{
		$event = $this->getAnalyticEvent()
			->setSection($this->category->value)
			->setStatus($status->value);

		if (!empty($this->shareType))
		{
			$event->setP1($this->shareType->value);
		}


		$event->send();
	}

	protected function getAnalyticEvent()
	{
		return new AnalyticsEvent(
			$this->getEventName(),
			static::DEFAULT_TOOL,
			static::DEFAULT_CATEGORY
		);
	}
}

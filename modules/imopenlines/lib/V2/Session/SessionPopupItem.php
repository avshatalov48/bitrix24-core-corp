<?php

namespace Bitrix\ImOpenLines\V2\Session;

use Bitrix\Im\V2\Rest\PopupData;
use Bitrix\Im\V2\Rest\PopupDataAggregatable;
use Bitrix\Im\V2\Rest\PopupDataItem;
use Bitrix\Main\Loader;

Loader::requireModule('im');

class SessionPopupItem implements PopupDataItem, PopupDataAggregatable
{
	private array $sessionIds;
	private ?SessionCollection $sessions = null;

	public function __construct(array $sessionIds)
	{
		$this->sessionIds = array_unique($sessionIds);
	}

	public function merge(PopupDataItem $item): PopupDataItem
	{
		if ($item instanceof self)
		{
			$this->sessionIds = array_unique(array_merge($this->sessionIds, $item->sessionIds));
		}

		return $this;
	}

	public static function getRestEntityName(): string
	{
		return 'sessions';
	}

	public function toRestFormat(array $option = []): array
	{
		return $this->getSessions()->toRestFormat($option);
	}

	private function getSessions(): SessionCollection
	{
		if (is_null($this->sessions))
		{
			$this->sessions = new SessionCollection($this->sessionIds);
		}

		return $this->sessions;
	}

	public function getPopupData(array $excludedList = []): PopupData
	{
		return $this->getSessions()->getPopupData();
	}
}
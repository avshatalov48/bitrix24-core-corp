<?php

namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\StaffTrack\Helper\DateHelper;

class Counter extends EO_Counter implements Arrayable
{
	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'userId' => $this->getUserId(),
			'muteStatus' => $this->getMuteStatus(),
			'muteUntil' => DateHelper::getInstance()->getDateUtc($this->getMuteUntil())->format(DateHelper::CLIENT_DATETIME_FORMAT),
		];
	}
}
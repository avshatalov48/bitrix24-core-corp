<?php

namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\Text\Emoji;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\StaffTrack\Helper\DateHelper;

class Shift extends EO_Shift implements Arrayable
{
	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'userId' => $this->getUserId(),
			'shiftDate' => $this->getShiftDate()->format(DateHelper::CLIENT_DATE_FORMAT),
			'dateCreate' => DateHelper::getInstance()->getDateUtc($this->getDateCreate())->format(DateHelper::CLIENT_DATETIME_FORMAT),
			'status' => $this->getStatus(),
			'location' => $this->getLocation() ? Emoji::decode($this->getLocation()) : null,
			'geoImageUrl' => $this->getGeo()?->getImageUrl(),
			'address' => $this->getGeo()?->getAddress(),
			'cancelReason' => $this->getCancellation()?->getReason()
				? Emoji::decode($this->getCancellation()->getReason())
				: null
			,
			'dateCancel' => $this->getCancellation()
				? DateHelper::getInstance()->getDateUtc($this->getCancellation()->getDateCancel())->format(DateHelper::CLIENT_DATETIME_FORMAT)
				: null
			,
		];
	}
}
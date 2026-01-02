<?php

namespace Bitrix\Crm\RepeatSale\Service\Handler;

use Bitrix\Crm\RepeatSale\Segment\Data\SegmentDataInterface;

final class Result extends \Bitrix\Main\Result
{
	private ?SegmentDataInterface $segmentData = null;

	public function getSegmentData(): ?SegmentDataInterface
	{
		return $this->segmentData;
	}

	public function setSegmentData(?SegmentDataInterface $segmentData): self
	{
		$this->segmentData = $segmentData;

		return $this;
	}
}

<?php

namespace Bitrix\Crm\RepeatSale\Segment;

use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Web\Uri;

final class DataFormatter
{
	use Singleton;

	private ?array $segments = null;

	public function getTitle(int $segmentId): ?string
	{
		if ($segmentId <= 0)
		{
			return null;
		}

		$segments = $this->getSegments();

		$segment = $segments[$segmentId] ?? null;

		if ($segment === null)
		{
			return null;
		}

		return $segment['TITLE'];
	}

	private function getSegments(): array
	{
		if ($this->segments === null)
		{
			$this->segments = RepeatSaleSegmentController::getInstance()->getList()->collectValues();
		}

		return $this->segments;
	}

	public function getUri(int $segmentId): Uri
	{
		return new Uri('/crm/repeat-sale-segment/details/' . $segmentId . '/');
	}
}

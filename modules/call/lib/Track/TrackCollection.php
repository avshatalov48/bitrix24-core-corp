<?php
namespace Bitrix\Call\Track;

use Bitrix\Call\Model\CallTrackTable;
use Bitrix\Call\Model\EO_CallTrack_Collection;
use Bitrix\Call\Track;

class TrackCollection extends EO_CallTrack_Collection
{
	public static function getTrackById(int $callId, int $trackId): ?\Bitrix\Call\Track
	{
		return CallTrackTable::query()
			->setSelect(['*'])
			->where('ID', $trackId)
			->where('CALL_ID', $callId)
			->setLimit(1)
			->exec()
			?->fetchObject()
		;
	}

	public static function getRecordings(int $callId): ?static
	{
		return CallTrackTable::query()
			->setSelect(['*'])
			->where('CALL_ID', $callId)
			->where('TYPE', Track::TYPE_RECORD)
			->setLimit(1)
			->exec()
			?->fetchCollection()
		;
	}

	public function toRestFormat(): array
	{
		$list = [];
		foreach ($this as $track)
		{
			$list[] = $track->toRestFormat();
		}

		return $list;
	}
}
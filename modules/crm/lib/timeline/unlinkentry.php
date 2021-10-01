<?php

namespace Bitrix\Crm\Timeline;

class UnlinkEntry extends RelationEntry
{
	protected static function getTimelineEntryType(): int
	{
		return TimelineType::UNLINK;
	}
}

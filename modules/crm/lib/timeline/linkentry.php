<?php
namespace Bitrix\Crm\Timeline;

class LinkEntry extends RelationEntry
{
	protected static function getTimelineEntryType(): int
	{
		return TimelineType::LINK;
	}
}

<?php

namespace Bitrix\Crm\Timeline\SignB2eDocument;

use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main\Type\DateTime;

final class LogEntry extends Entry
{
	public static function getTypeId(): string
	{
		return TimelineType::SIGN_B2E_DOCUMENT_LOG;
	}
}

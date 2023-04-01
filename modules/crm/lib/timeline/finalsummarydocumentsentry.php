<?php

namespace Bitrix\Crm\Timeline;

class FinalSummaryDocumentsEntry extends FinalSummaryEntry
{
	protected const TIMELINE_ENTRY_TYPE = TimelineType::FINAL_SUMMARY_DOCUMENTS;

	public static function create(array $params)
	{
		return parent::create($params);
	}
}

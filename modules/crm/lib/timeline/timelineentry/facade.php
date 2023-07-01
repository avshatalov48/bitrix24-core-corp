<?php

namespace Bitrix\Crm\Timeline\TimelineEntry;

use Bitrix\Crm\Timeline\ConversionEntry;
use Bitrix\Crm\Timeline\CreationEntry;
use Bitrix\Crm\Timeline\LinkEntry;
use Bitrix\Crm\Timeline\LogMessageEntry;
use Bitrix\Crm\Timeline\MarkEntry;
use Bitrix\Crm\Timeline\ModificationEntry;
use Bitrix\Crm\Timeline\RestorationEntry;
use Bitrix\Crm\Timeline\SignDocument;
use Bitrix\Crm\Timeline\CalendarSharing;
use Bitrix\Crm\Timeline\Tasks;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\UnlinkEntry;
use Bitrix\Crm\Timeline\FinalSummaryEntry;
use Bitrix\Crm\Timeline\FinalSummaryDocumentsEntry;
use Bitrix\Main\ArgumentException;

class Facade
{
	public const CREATION = CreationEntry::class;
	public const MODIFICATION = ModificationEntry::class;
	public const RESTORATION = RestorationEntry::class;
	public const CONVERSION = ConversionEntry::class;
	public const LINK = LinkEntry::class;
	public const UNLINK = UnlinkEntry::class;
	public const MARK = MarkEntry::class;
	public const FINAL_SUMMARY = FinalSummaryEntry::class;
	public const FINAL_SUMMARY_DOCUMENTS = FinalSummaryDocumentsEntry::class;
	public const SIGN_DOCUMENT = SignDocument\Entry::class;
	public const SIGN_DOCUMENT_LOG = SignDocument\LogEntry::class;
	public const LOG_MESSAGE = LogMessageEntry::class;
	public const CALENDAR_SHARING = CalendarSharing\Entry::class;
	public const TASK = Tasks\Entry::class;

	/** @var TimelineEntry */
	protected $timelineEntryClass = TimelineEntry::class;

	/**
	 * Create a timeline entry of the specified type
	 * Returns id of the created entry
	 *
	 * @param string $entryType should be valid TimelineEntry type
	 * @param array $params
	 *
	 * @return int if something went wrong, returns 0
	 * @throws ArgumentException
	 */
	public function create(string $entryType, array $params): int
	{
		if (!is_a($entryType, TimelineEntry::class, true))
		{
			throw new ArgumentException(
				'Invalid timeline entry type. Please, use class constants of ' . static::class,
				'entryType'
			);
		}

		/** @var string|TimelineEntry $entryType */
		return $entryType::create($params);
	}

	/**
	 * Returns timeline entry by its id
	 *
	 * @param int $id
	 *
	 * @return array|null
	 */
	public function getById(int $id): ?array
	{
		return $this->timelineEntryClass::getByID($id);
	}
}
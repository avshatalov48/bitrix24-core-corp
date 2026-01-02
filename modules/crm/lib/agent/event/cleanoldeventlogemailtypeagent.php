<?php

namespace Bitrix\Crm\Agent\Event;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\EventTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
use CFile;

class CleanOldEventLogEmailTypeAgent extends AgentBase
{
	private const MODULE = 'crm';
	private const DELIMITER_N_R = "\n\r";
	private const DELIMITER_N = "\n";
	private const DELIMITER_R = "\r";
	private const DEFAULT_LIMIT_VALUE = '100';
	private const ONE_HOUR_IN_SECONDS = 3600;
	private const ONE_MINUTE_IN_SECONDS = 60;
	private const FIVE_MINUTE_IN_SECONDS = 300;
	private const ONE_EVENT_WITH_FILES = 1;
	private const OPTION_LIMIT_VALUE = 'clean_email_type_event_log_table_option_limit_value';
	private const OPTION_LAST_ID = 'clean_email_type_event_log_table_option_last_id';
	private const OPTION_CURRENT_MAX_ID = 'clean_email_type_event_log_table_option_correct_id';
	private const OPTION_IS_AGENT_ENABLED = 'clean_email_type_event_log_table_option_is_agent_enabled';
	private const OPTION_EXECUTION_PERIOD = 'clean_email_type_event_log_table_option_execution_period';
	private const OPTION_NEXT_EXECUTION_PERIOD = 'clean_email_type_event_log_table_option_next_execution_period';
	private const OPTION_NEXT_EXECUTION_PERIOD_FILE = 'clean_email_type_event_log_table_option_next_execution_period_file';
	private const OPTION_EVENTS_WITH_FILES_COUNT = 'clean_email_type_event_log_table_option_events_with_files_count';
	private array $events = [];

	public static function doRun(): bool
	{
		return (new self())->execute();
	}

	private function isAgentEnabled(): bool
	{
		return Option::get(self::MODULE, self::OPTION_IS_AGENT_ENABLED, 'N') === 'Y' || !Loader::includeModule('bitrix24');
	}

	private function execute(): bool
	{
		if (!$this->isAgentEnabled())
		{
			$this->setExecutionPeriod(Option::get(self::MODULE, self::OPTION_EXECUTION_PERIOD, self::ONE_HOUR_IN_SECONDS));

			return true;
		}

		$eventIds = $this->getEventIds();

		if (empty($eventIds))
		{
			$this->cleanAfterWork();

			return false;
		}

		$this->fillEventDataByIds($eventIds);

		$lastId = 0;

		$deletedCounter = 0;

		$nextExecutionPeriod = Option::get(self::MODULE, self::OPTION_NEXT_EXECUTION_PERIOD, self::ONE_MINUTE_IN_SECONDS);

		$eventsWithFilesCount = (int)Option::get(self::MODULE, self::OPTION_EVENTS_WITH_FILES_COUNT, self::ONE_EVENT_WITH_FILES);

		foreach ($this->events as $event)
		{
			$hasFilesDeleted = false;
			if (!empty($event['FILES']))
			{
				$this->cleanRelatedFiles($event['FILES']);

				$hasFilesDeleted = true;
				$deletedCounter++;
			}

			$this->cleanData($event['ID'], $event['EVENT_TEXT_1']);

			$lastId = $event['ID'];

			if ($hasFilesDeleted && $deletedCounter >= $eventsWithFilesCount)
			{
				$nextExecutionPeriod = Option::get(self::MODULE, self::OPTION_NEXT_EXECUTION_PERIOD_FILE, self::FIVE_MINUTE_IN_SECONDS);

				break;
			}
		}

		if ($lastId === Option::get(self::MODULE, self::OPTION_CURRENT_MAX_ID))
		{
			$this->cleanAfterWork();

			return false;
		}

		Option::set(self::MODULE, self::OPTION_LAST_ID, $lastId);

		$this->setExecutionPeriod($nextExecutionPeriod);

		return true;
	}

	private function getEventIds(): array
	{
		$limit = Option::get(self::MODULE, self::OPTION_LIMIT_VALUE, self::DEFAULT_LIMIT_VALUE);
		$currentMaxId = Option::get(self::MODULE, self::OPTION_CURRENT_MAX_ID, $this->getCurrentMaxId());
		$lastId = Option::get(self::MODULE, self::OPTION_LAST_ID, 0);

		return EventTable::query()
			->setSelect(['ID'])
			->where('ID', '>', $lastId)
			->where('ID', '<=', $currentMaxId)
			->where('EVENT_TYPE', \CCrmEvent::TYPE_EMAIL)
			->setLimit($limit)
			->setOrder(['ID' => 'ASC'])
			->fetchCollection()
			->getIdList()
		;
	}

	private function getCurrentMaxId(): int
	{
		$row = EventTable::query()
			->setSelect(['MAX_ID'])
			->registerRuntimeField(new ExpressionField('MAX_ID', 'MAX(%s)', 'ID'))
			->fetch()
		;
		$maxId = $row['MAX_ID'] ?? 0;

		Option::set(self::MODULE, self::OPTION_CURRENT_MAX_ID, $maxId);

		return $maxId;
	}

	private function fillEventDataByIds(array $eventIds): void
	{
		$this->events = EventTable::query()
			->setSelect(['ID', 'FILES', 'EVENT_TEXT_1'])
			->whereIn('ID', $eventIds)
			->fetchAll()
		;
	}

	private function cleanRelatedFiles(string $serializedData): void
	{
		$fileIds = unserialize($serializedData, ['allowed_classes' => false]);

		if (!$fileIds)
		{
			return;
		}

		foreach ($fileIds as $fileId)
		{
			CFile::Delete((int)$fileId);
		}
	}

	private function cleanData(int $eventId, string $eventText): void
	{
		EventTable::update($eventId, ['EVENT_TEXT_1' => $this->cleanText($eventText), 'FILES' => null]);
	}

	public function cleanText(string $text): string
	{
		$parts = preg_split('/(\n\r|\n|\r)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

		$lines = [];
		$result = '';
		$cutFound = false;

		$currentLine = '';
		foreach ($parts as $part)
		{
			$currentLine .= $part;

			if (in_array($part, [self::DELIMITER_N_R, self::DELIMITER_N, self::DELIMITER_R], true))
			{
				$lines[] = $currentLine;
				$currentLine = '';
			}
		}

		if ($currentLine !== '')
		{
			$lines[] = $currentLine;
		}

		foreach ($lines as $i => $line)
		{
			if ($i === 2 && str_starts_with($line, '<b>') && str_ends_with($line, self::DELIMITER_N))
			{
				$cutFound = true;
				$result .= $line;

				break;
			}

			if ($i >= 2 && (
					str_starts_with($line, self::DELIMITER_N_R)
					|| str_starts_with($line, self::DELIMITER_N)
					|| str_starts_with($line, self::DELIMITER_R)
				))
			{
				$cutFound = true;

				break;
			}

			$result .= $line;
		}

		if ($cutFound)
		{
			return $this->cutLengthAndRemoveLastDelimeter($result);
		}

		return $this->cutLengthAndRemoveLastDelimeter($text);
	}

	private function cleanAfterWork(): void
	{
		\COption::RemoveOption(self::MODULE, self::OPTION_LIMIT_VALUE);
		\COption::RemoveOption(self::MODULE, self::OPTION_LAST_ID);
		\COption::RemoveOption(self::MODULE, self::OPTION_CURRENT_MAX_ID);
		\COption::RemoveOption(self::MODULE, self::OPTION_IS_AGENT_ENABLED);
		\COption::RemoveOption(self::MODULE, self::OPTION_EXECUTION_PERIOD);
		\COption::RemoveOption(self::MODULE, self::OPTION_NEXT_EXECUTION_PERIOD);
		\COption::RemoveOption(self::MODULE, self::OPTION_NEXT_EXECUTION_PERIOD_FILE);
		\COption::RemoveOption(self::MODULE, self::OPTION_EVENTS_WITH_FILES_COUNT);
	}

	private function cutLengthAndRemoveLastDelimeter(string $string): string
	{
		foreach ([self::DELIMITER_N_R, self::DELIMITER_N, self::DELIMITER_R] as $delimiter)
		{
			if (str_ends_with($string, $delimiter))
			{
				$string = mb_substr($string, 0, -strlen($delimiter));

				break;
			}
		}

		return mb_strlen($string) > 250 ? mb_substr($string, 0, 249) : $string;
	}
}

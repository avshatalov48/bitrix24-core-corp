<?php

namespace Bitrix\Crm\Activity\LastCommunication;

use Bitrix\Crm\Model\LastCommunicationTable;
use Bitrix\Main\Type\DateTime;
use CCrmDateTimeHelper;

class LastCommunicationTimeFormatter
{
	private const LAST_COMMUNICATION_TIME_FORMATTED = 'LAST_COMMUNICATION_TIME_FORMATTED';

	public function formatDetailDate(array &$field): void
	{
		if (!isset($field[LastCommunicationTable::getLastStateFieldName()]) || !$field[LastCommunicationTable::getLastStateFieldName()])
		{
			return;
		}

		if (isset($field[self::LAST_COMMUNICATION_TIME_FORMATTED]) && $field[self::LAST_COMMUNICATION_TIME_FORMATTED])
		{
			return;
		}

		$field[LastCommunicationTable::getLastStateFieldName()] = $this->prepareFormatedDate(
			$field[LastCommunicationTable::getLastStateFieldName()],
		);
		
		$field[self::LAST_COMMUNICATION_TIME_FORMATTED] = true;
	}

	public function formatListDate(array $data, array &$columnData): void
	{
		if (!isset($data[LastCommunicationTable::getLastStateFieldName()]) || !$data[LastCommunicationTable::getLastStateFieldName()])
		{
			return;
		}

		if (isset($field[self::LAST_COMMUNICATION_TIME_FORMATTED]) && $field[self::LAST_COMMUNICATION_TIME_FORMATTED])
		{
			return;
		}

		$columnData[LastCommunicationTable::getLastStateFieldName()] = $this->prepareFormatedDate(
			$data[LastCommunicationTable::getLastStateFieldName()],
		);

		$columnData[self::LAST_COMMUNICATION_TIME_FORMATTED] = true;
	}

	public function formatKanbanDate(array &$data): void
	{
		if ($data['code'] !== LastCommunicationTable::getLastStateFieldName())
		{
			return;
		}

		$data['value'] = $this->prepareFormatedDate($data['value']);
	}

	private function prepareFormatedDate(string $value): string
	{
		return FormatDate(
			CCrmDateTimeHelper::getDefaultDateTimeFormat(),
			DateTime::createFromUserTime($value),
		);
	}
}

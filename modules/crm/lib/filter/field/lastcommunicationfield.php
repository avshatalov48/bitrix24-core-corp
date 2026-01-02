<?php

namespace Bitrix\Crm\Filter\Field;

use Bitrix\Crm\Activity\LastCommunication\LastCommunicationAvailabilityChecker;
use Bitrix\Crm\Model\LastCommunicationTable;
use Bitrix\Main\Filter\DataProvider;
use Bitrix\Main\Filter\Field;

final class LastCommunicationField {

	private string $code;
	private string $name;
	private bool $isOptionRemoved;

	public function __construct()
	{
		[$this->code, $this->name] = LastCommunicationTable::getLastStateCodeName();
		$this->isOptionRemoved = LastCommunicationAvailabilityChecker::getInstance()->isEnabled();
	}
	public function addLastCommunicationField(DataProvider $dataProvider, array &$fieldData): void
	{
		if (!$this->isOptionRemoved)
		{
			return;
		}

		$params = [
			'type' => 'date',
			'name' => $this->name,
			'data' => [
				'additionalFilter' => [
					'isEmpty',
					'hasAnyValue',
				],
			],
		];

		$fieldData[$this->code] = new Field($dataProvider, $this->code, $params);
	}

	public function addLastCommunicationGridHeader(array &$headersData): void
	{
		if (!$this->isOptionRemoved)
		{
			return;
		}

		$headersData[] = [
			'id' => $this->code,
			'name' => $this->name,
			'sort' => mb_strtolower($this->code),
			'class' => 'datetime',
			'default' => false,
		];
	}

	public function addLastCommunicationFieldInfo(array &$entityFieldsInfo): void
	{
		if (!$this->isOptionRemoved)
		{
			return;
		}

		$entityFieldsInfo[] = [
			'name' => $this->code,
			'title' => $this->name,
			'type' => 'text',
			'editable' => false,
			'enableAttributes' => false,
			'mergeable' => false,
		];
	}
}
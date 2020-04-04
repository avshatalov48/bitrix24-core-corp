<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics;

interface AggregateStrategy
{
	public function getExistingRecordByPrimary();
	public function createRecord();

	/**
	 * @param array $existingRecord
	 * @return void
	 */
	public function updateRecord(array $existingRecord);
	public function getErrors();
}
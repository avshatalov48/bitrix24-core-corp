<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics;

use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\TreatmentByHourStatTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;

/**
 * Class Treatment
 * @package Bitrix\ImOpenLines\Integrations\Report\Statistics
 */
class TreatmentByHour extends AggregatorBase
{

	/**
	 * Treatment constructor.
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		parent::__construct($params);
		$this->setDate(new DateTime($params['DATE']->format('Y-m-d H'), 'Y-m-d H'));
	}

	/**
	 * @return array|null
	 */
	public function getExistingRecordByPrimary()
	{
		$existStatistic = TreatmentByHourStatTable::getRow(array(
			'select' => array('QTY'),
			'filter' => Query::filter()
				->where('DATE', $this->getDate())
				->where('OPEN_LINE_ID', $this->getOpenLineId())
				->where('SOURCE_ID', $this->getSourceId())
				->where('OPERATOR_ID', $this->getOperatorId())
		));

		return $existStatistic;
	}

	public function createRecord()
	{
		TreatmentByHourStatTable::add(array(
			'fields' => array(
				'DATE' => $this->getDate(),
				'OPEN_LINE_ID' => $this->getOpenLineId(),
				'SOURCE_ID' => $this->getSourceId(),
				'OPERATOR_ID' => $this->getOperatorId(),
				'QTY' => 1,
			)
		));
	}

	/**
	 * @param $existingRecord
	 * @return void
	 */
	public function updateRecord(array $existingRecord)
	{
		$primary = array(
			'DATE' => $this->getDate(),
			'OPEN_LINE_ID' => $this->getOpenLineId(),
			'SOURCE_ID' => $this->getSourceId(),
			'OPERATOR_ID' => $this->getOperatorId(),
		);
		TreatmentByHourStatTable::update($primary, array(
			'fields' => array(
				'QTY' => $existingRecord['QTY'] + 1,
			)
		));
	}
}
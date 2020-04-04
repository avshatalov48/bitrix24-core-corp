<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics;

use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable;

/**
 * Class Dialog
 * @package Bitrix\ImOpenLines\Integrations\Report\Statistics
 */
class Dialog extends AggregatorBase
{
	const STATUS_ANSWERED = 1;
	const STATUS_NO_PRECESSED = 0;
	const STATUS_SKIPPED = 2;

	private $status;
	private $secsToAnswer;

	/**
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		parent::__construct($params);
		$this->setStatus($params['STATUS']);
		if ($this->getStatus() === self::STATUS_ANSWERED)
		{
			$this->setSecsToAnswer($params['SECS_TO_ANSWER']);
		}

	}


	public function createRecord()
	{
		DialogStatTable::add(array(
			'fields' => array(
				'DATE' => $this->getDate(),
				'OPEN_LINE_ID' => $this->getOpenLineId(),
				'SOURCE_ID' => $this->getSourceId(),
				'OPERATOR_ID' => $this->getOperatorId(),
				'ANSWERED_QTY' => $this->getStatus() === self::STATUS_ANSWERED ?  1 : 0,
				'APPOINTED_QTY' => $this->getStatus() === self::STATUS_NO_PRECESSED ? 1 : 0,
				'SKIP_QTY' => $this->getStatus() === self::STATUS_SKIPPED ? 1 : 0,
				'AVERAGE_SECS_TO_ANSWER' => $this->getStatus() === self::STATUS_ANSWERED ? $this->getSecsToAnswer() : 0,
			)
		));
	}

	/**
	 * @param array $existingRecord
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

		$fields = array();
		if ($this->getStatus() === self::STATUS_ANSWERED)
		{
			$fields['ANSWERED_QTY'] = $existingRecord['ANSWERED_QTY'] + 1;
			$fields['AVERAGE_SECS_TO_ANSWER'] =  floor(($existingRecord['AVERAGE_SECS_TO_ANSWER'] + $this->getSecsToAnswer()) / ($existingRecord['ANSWERED_QTY'] + 1));
		}

		if ($this->getStatus() === self::STATUS_NO_PRECESSED)
		{
			$fields['APPOINTED_QTY'] = $existingRecord['APPOINTED_QTY'] + 1;
		}

		if ($this->getStatus() === self::STATUS_SKIPPED)
		{
			$fields['SKIP_QTY'] = $existingRecord['SKIP_QTY'] + 1;
		}

		DialogStatTable::update($primary, array(
			'fields' => $fields
		));
	}

	/**
	 * @return mixed
	 */
	public function getSecsToAnswer()
	{
		return $this->secsToAnswer;
	}

	/**
	 * @param mixed $secsToAnswer
	 */
	public function setSecsToAnswer($secsToAnswer)
	{
		$this->secsToAnswer = $secsToAnswer;
	}

	/**
	 * @return mixed
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param mixed $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}


}
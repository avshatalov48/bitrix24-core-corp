<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics;

use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable;
use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\MarkStatTable;

/**
 * Class Mark
 * @package Bitrix\ImOpenLines\Integrations\Report\Statistics
 */
class Mark extends AggregatorBase
{
	const POSITIVE = '5';
	const NEGATIVE = '1';
	const WITHOUT_MARK = '0';

	private $mark;
	private $oldMark = null;


	/**
	 * Mark constructor.
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		parent::__construct($params);
		if (isset($params['OLD_MARK']))
		{
			$this->setOldMark($params['OLD_MARK']);
		}
		$this->setMark($params['MARK']);

	}

	public function createRecord()
	{
		$fields = array(
			'DATE' => $this->getDate(),
			'OPEN_LINE_ID' => $this->getOpenLineId(),
			'SOURCE_ID' => $this->getSourceId(),
			'OPERATOR_ID' => $this->getOperatorId(),
		);
		switch ($this->getMark())
		{
			case self::WITHOUT_MARK:
				$fields['WITHOUT_MARK_QTY'] = 1;
				break;
			case self::POSITIVE:
				$fields['POSITIVE_QTY'] = 1;
				break;
			case self::NEGATIVE:
				$fields['NEGATIVE_QTY'] = 1;
				break;
		}
		DialogStatTable::add(array(
			'fields' => $fields
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

		iF ($this->getOldMark() !== null)
		{
			switch ($this->getOldMark())
			{
				case self::WITHOUT_MARK:
					$fields['WITHOUT_MARK_QTY'] = $existingRecord['WITHOUT_MARK_QTY'] - 1;
					break;
				case self::POSITIVE:
					$fields['POSITIVE_QTY'] = $existingRecord['POSITIVE_QTY'] - 1;
					break;
				case self::NEGATIVE:
					$fields['NEGATIVE_QTY'] = $existingRecord['NEGATIVE_QTY'] - 1;
					break;
			}
		}

		switch ($this->getMark())
		{
			case self::WITHOUT_MARK:
				$fields['WITHOUT_MARK_QTY'] = $existingRecord['WITHOUT_MARK_QTY'] + 1;
				break;
			case self::POSITIVE:
				$fields['POSITIVE_QTY'] = $existingRecord['POSITIVE_QTY'] + 1;
				break;
			case self::NEGATIVE:
				$fields['NEGATIVE_QTY'] = $existingRecord['NEGATIVE_QTY'] + 1;
				break;
		}
		DialogStatTable::update($primary, array(
			'fields' => $fields
		));
	}

	/**
	 * @return mixed
	 */
	public function getMark()
	{
		return $this->mark;
	}

	/**
	 * @param mixed $mark
	 */
	public function setMark($mark)
	{
		$this->mark = $mark;
	}

	/**
	 * @return mixed
	 */
	public function getOldMark()
	{
		return $this->oldMark;
	}

	/**
	 * @param mixed $oldMark
	 */
	public function setOldMark($oldMark)
	{
		$this->oldMark = $oldMark;
	}
}
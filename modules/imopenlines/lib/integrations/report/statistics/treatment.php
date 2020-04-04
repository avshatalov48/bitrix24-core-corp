<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics;

use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable;
use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\TreatmentStatTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;

/**
 * Class Treatment
 * @package Bitrix\ImOpenLines\Integrations\Report\Statistics
 */
class Treatment extends AggregatorBase
{

	private $isSessionFirst;

	/**
	 * Treatment constructor.
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		parent::__construct($params);
		$this->setIsSessionFirst($params['IS_CHAT_CREATED_NEW']);
	}

	public function createRecord()
	{
		DialogStatTable::add(array(
			'fields' => array(
				'DATE' => $this->getDate(),
				'OPEN_LINE_ID' => $this->getOpenLineId(),
				'SOURCE_ID' => $this->getSourceId(),
				'OPERATOR_ID' => $this->getOperatorId(),
				'FIRST_TREATMENT_QTY' => $this->getIsSessionFirst() ?  1 : 0,
				'REPEATED_TREATMENT_QTY' => !$this->getIsSessionFirst() ? 1 : 0,
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
		DialogStatTable::update($primary, array(
			'fields' => array(
				'FIRST_TREATMENT_QTY' => $this->getIsSessionFirst() ? $existingRecord['FIRST_TREATMENT_QTY'] + 1 : $existingRecord['FIRST_TREATMENT_QTY'],
				'REPEATED_TREATMENT_QTY' => !$this->getIsSessionFirst() ? $existingRecord['REPEATED_TREATMENT_QTY'] + 1 : $existingRecord['REPEATED_TREATMENT_QTY'],
			)
		));
	}


	/**
	 * @return mixed
	 */
	public function getIsSessionFirst()
	{
		if ($this->isSessionFirst === true || $this->isSessionFirst === 'Y')
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param mixed $isSessionFirst
	 */
	public function setIsSessionFirst($isSessionFirst)
	{
		$this->isSessionFirst = $isSessionFirst;
	}


}
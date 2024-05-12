<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics;

use Bitrix\Main\Error;
use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;

abstract class AggregatorBase implements AggregateStrategy
{
	private $date;
	private $openLineId;
	private $sourceId;
	private $operatorId;
	private $errors;

	/**
	 * AggregatorBase constructor.
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		$this->setDate(new DateTime($params['DATE']->format('Y-m-d 00:00:00'), 'Y-m-d 00:00:00'));
		$this->setOpenLineId($params['OPEN_LINE_ID']);
		$this->setSourceId($params['SOURCE_ID']);
		$this->setOperatorId($params['OPERATOR_ID']);
	}

	/**
	 * @return array|null
	 */
	public function getExistingRecordByPrimary()
	{
		$existStatistic = DialogStatTable::getRow(array(
			'filter' => Query::filter()
				->whereBetween(
					'DATE',
					new DateTime($this->getDate()->format('Y-m-d 00:00:00'), 'Y-m-d H:i:s'),
					new DateTime($this->getDate()->format('Y-m-d 23:59:59'), 'Y-m-d H:i:s')
				)
				->where('OPEN_LINE_ID', $this->getOpenLineId())
				->where('SOURCE_ID', $this->getSourceId())
				->where('OPERATOR_ID', $this->getOperatorId())
		));

		return $existStatistic;
	}

	/**
	 * @return string
	 */
	public static function getClassName()
	{
		return get_called_class();
	}

	/**
	 * @return mixed
	 */
	public function getOperatorId()
	{
		return $this->operatorId;
	}

	/**
	 * @param mixed $operatorId
	 */
	public function setOperatorId($operatorId)
	{
		if ($operatorId === null)
		{
			$this->errors[] = new Error('Operator id can\'t be empty');
		}

		$this->operatorId = $operatorId;
	}

	/**
	 * @return mixed
	 */
	public function getSourceId()
	{
		return $this->sourceId;
	}

	/**
	 * @param mixed $sourceId
	 */
	public function setSourceId($sourceId)
	{
		if ($sourceId === null)
		{
			$this->errors[] = new Error('Source id can\'t be empty');
		}
		$this->sourceId = $sourceId;
	}

	/**
	 * @return mixed
	 */
	public function getDate()
	{
		return $this->date;
	}

	/**
	 * @param mixed $date
	 */
	public function setDate($date)
	{
		if ($date === null)
		{
			$this->errors[] = new Error('Date can\'t be empty');
		}

		$this->date = $date;
	}

	/**
	 * @return mixed
	 */
	public function getOpenLineId()
	{

		return $this->openLineId;
	}

	/**
	 * @param mixed $openLineId
	 */
	public function setOpenLineId($openLineId)
	{
		if ($openLineId === null)
		{
			$this->errors[] = new Error('Open line id can\'t be empty');
		}

		$this->openLineId = $openLineId;
	}

	/**
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}
}
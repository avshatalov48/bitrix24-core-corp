<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Handlers;

use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\TreatmentByHourStatTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Report\VisualConstructor\Fields\Valuable\DropDown;
use Bitrix\Report\VisualConstructor\IReportMultipleBiGroupedData;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Report\VisualConstructor\IReportMultipleGroupedData;
use Bitrix\Report\VisualConstructor\IReportSingleData;

/**
 * Report provider class for Treatment
 * @package Bitrix\ImOpenLines\Integrations\Report\Handlers
 */
class Treatment extends Base implements IReportMultipleBiGroupedData, IReportSingleData, IReportMultipleData, IReportMultipleGroupedData
{
	const WHAT_WILL_CALCULATE_ALL = 'ALL';
	const WHAT_WILL_CALCULATE_ALL_TREATMENT_BY_HOUR = 'ALL_BY_HOUR';
	const WHAT_WILL_CALCULATE_FIRST = 'FIRST';
	const WHAT_WILL_CALCULATE_DUPLICATE = 'DUPLICATE';
	const WHAT_WILL_CALCULATE_POSITIVE_MARK = 'POSITIVE_MARK';
	const WHAT_WILL_CALCULATE_NEGATIVE_MARK = 'NEGATIVE_MARK';
	const WHAT_WILL_CALCULATE_WITHOUT_MARK = 'WITHOUT_MARK';
	const WHAT_WILL_CALCULATE_ALL_MARK = 'ALL_MARK';
	const WHAT_WILL_CALCULATE_ALL_APPOINTED = 'ALL_APPOINTED';
	const WHAT_WILL_CALCULATE_ANSWERED = 'ACCEPTED';
	const WHAT_WILL_CALCULATE_SKIPPED = 'SKIPPED';
	const WHAT_WILL_CALCULATE_CONTENTMENT = 'CONTENTMENT';

	/**
	 * Treatment constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setTitle(Loc::getMessage('OPEN_LINES_TREATMENT'));
		$this->setCategoryKey('open_lines');
	}

	/**
	 * @param null $groupBy
	 * @return array
	 */
	public function getWhatWillCalculateOptions($groupBy = null)
	{
		if ($this->getView()->getKey() === 'activity')
		{
			return array(
				self::WHAT_WILL_CALCULATE_ALL_TREATMENT_BY_HOUR => Loc::getMessage('WHAT_WILL_CALCULATE_ALL_TREATMENT_BY_HOUR'),
			);
		}
		return array(
			self::WHAT_WILL_CALCULATE_ALL => Loc::getMessage('WHAT_WILL_CALCULATE_ALL_TREATMENT'),
			self::WHAT_WILL_CALCULATE_ALL_TREATMENT_BY_HOUR => Loc::getMessage('WHAT_WILL_CALCULATE_ALL_TREATMENT_BY_HOUR'),
			self::WHAT_WILL_CALCULATE_FIRST => Loc::getMessage('WHAT_WILL_CALCULATE_FIRST_TREATMENT_NEW'),
			self::WHAT_WILL_CALCULATE_DUPLICATE => Loc::getMessage('WHAT_WILL_CALCULATE_DUPLICATE_TREATMENT_NEW'),
			self::WHAT_WILL_CALCULATE_POSITIVE_MARK => Loc::getMessage('WHAT_WILL_CALCULATE_POSITIVE_MARK'),
			self::WHAT_WILL_CALCULATE_NEGATIVE_MARK => Loc::getMessage('WHAT_WILL_CALCULATE_NEGATIVE_MARK'),
			self::WHAT_WILL_CALCULATE_WITHOUT_MARK => Loc::getMessage('WHAT_WILL_CALCULATE_WITHOUT_MARK'),
			self::WHAT_WILL_CALCULATE_ALL_MARK => Loc::getMessage('WHAT_WILL_CALCULATE_ALL_MARK'),
			self::WHAT_WILL_CALCULATE_ALL_APPOINTED => Loc::getMessage("WHAT_WILL_CALCULATE_ALL_APPOINTED"),
			self::WHAT_WILL_CALCULATE_ANSWERED => Loc::getMessage("WHAT_WILL_CALCULATE_ANSWERED"),
			self::WHAT_WILL_CALCULATE_SKIPPED => Loc::getMessage("WHAT_WILL_CALCULATE_SKIPPED"),
			self::WHAT_WILL_CALCULATE_CONTENTMENT => Loc::getMessage('WHAT_WILL_CALCULATE_CONTENTMENT'),
		);
	}

	/**
	 * @return Query
	 */
	public function getQueryInstance()
	{
		$whatWillCalculate = $this->getFormElement('calculate');
		$whatWillCalculateValue = $whatWillCalculate->getValue();
		switch ($whatWillCalculateValue)
		{
			case self::WHAT_WILL_CALCULATE_ALL_TREATMENT_BY_HOUR:
				$query = new Query(TreatmentByHourStatTable::getEntity());
				break;
			default:
				$query = parent::getQueryInstance();
		}
		return $query;
	}

	/**
	 * Prepare data before pass to specific interface  method.
	 * @return mixed
	 */
	public function prepare()
	{
		$query = $this->getQueryForPrepareData();
		/** @var DropDown $whatWillCalculate */
		$whatWillCalculate = $this->getFormElement('calculate');
		$whatWillCalculateValue = $whatWillCalculate->getValue();
		switch ($whatWillCalculateValue)
		{
			case self::WHAT_WILL_CALCULATE_ALL:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(FIRST_TREATMENT_QTY + REPEATED_TREATMENT_QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_ALL_TREATMENT_BY_HOUR:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_FIRST:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(FIRST_TREATMENT_QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_DUPLICATE:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(REPEATED_TREATMENT_QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_POSITIVE_MARK:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(POSITIVE_QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_NEGATIVE_MARK:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(NEGATIVE_QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_WITHOUT_MARK:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(WITHOUT_MARK_QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_ALL_MARK:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(POSITIVE_QTY + NEGATIVE_QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_ALL_APPOINTED:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(APPOINTED_QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_ANSWERED:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(ANSWERED_QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_SKIPPED:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(SKIP_QTY)'));
				break;
			case self::WHAT_WILL_CALCULATE_CONTENTMENT:
				$query->addSelect(new ExpressionField('VALUE', '(SUM(POSITIVE_QTY)/SUM(POSITIVE_QTY + NEGATIVE_QTY)) * 100'));
				break;
			default:
				$query->addSelect(new ExpressionField('VALUE', 'SUM(FIRST_TREATMENT_QTY + REPEATED_TREATMENT_QTY)'));

		}

		$query->addOrder('VALUE', 'DESC');
		$result = $query->exec();

		return $this->getCalculatedDataFromDbResult($result);
	}

	/**
	 * array with format
	 * array(
	 *     'title' => 'Some Title',
	 *     'value' => 0,
	 *     'targetUrl' => 'http://url.domain?params=param'
	 * )
	 * @return array
	 */
	public function getSingleData()
	{

		$data = parent::getSingleData();

		/** @var DropDown $whatWillCalculate */
		$whatWillCalculate = $this->getFormElement('calculate');
		$whatWillCalculateValue = $whatWillCalculate->getValue();

		switch ($whatWillCalculateValue)
		{
			case self::WHAT_WILL_CALCULATE_CONTENTMENT:
				$data['config']['unitOfMeasurement'] = '%';
				break;
		}
		return $data;
	}


	/**
	 * @return array
	 */
	public function getMultipleBiGroupedData()
	{
		$calculatedData = $this->getCalculatedData();
		$result = array();
		foreach ($calculatedData as $key => $data)
		{
			$date = new DateTime($key, 'Y-m-d H:i');
			$dayInWeek = $date->format('w');
			$hourInDay = $date->format('H');
			$item = array(
				'firstGroupId' => $hourInDay,
				'secondGroupId' => $dayInWeek,
				'value' => $data['value']
			);
			$result['items'][] = $item;
		}
		return $result;
	}






	////////////////////////
	///////DEMO DATA////////
	////////////////////////
	/**
	 * @return array
	 */
	protected function getGeneratedDemoData()
	{
		$result = array();
		if (\Bitrix\Main\Application::getInstance()->getLicense()->getRegion() === 'ru')
		{
			$providers = [
				'viber', 'telegrambot', 'vkgroup', 'livechat'
			];
		}
		else
		{
			$providers = [
				'viber', 'facebook', 'telegrambot', 'livechat'
			];
		}
		for ($i = 0; $i < 30; $i++)
		{
			$result[] =	array(
					'date' => date('Y-m-d H:i', strtotime("-" . 30 - $i . " day", time() - (rand(0, 24) * 3600))),
					'open_line_id' => '1',
					'source_id' => $providers[rand(0, 3)],
					'operator_id' => rand(1, 6),
					'first_treatment_qty' => rand(10, 90),
					'repeated_treatment_qty' => rand(40, 160),
					'positive_qty' => rand(10, 170),
					'negative_qty' => rand(5, 10),
					'without_mark_qty' => rand(12, 20),
					'answered_qty' => rand(10, 50),
					'skipped_qty' => rand(0, 10),
					'refused_qty' => 6,
					'average_secs_to_answer' => rand(250, 480),
			);
		}
		return $result;
	}




	protected function prepareResultByWhatWillCalculate($data, $whatWillCalculateValue)
	{
		$result = array();
		foreach ($data as $groupingKey => $value)
		{
			switch ($whatWillCalculateValue)
			{
				case self::WHAT_WILL_CALCULATE_CONTENTMENT:
					$result[$groupingKey]['value'] = floor(array_sum($value['value']) / count($value['value']));
					$result[$groupingKey]['label'] = $value['label'];
					break;
				default:
					$result[$groupingKey]['value'] = array_sum($value['value']);
					$result[$groupingKey]['label'] = $value['label'];


			}
		}

		return $result;
	}
	/**
	 * @param $row
	 * @return array
	 */
	protected function getPreparedDemoRow($row)
	{

		/** @var DropDown $whatWillCalculate */
		$whatWillCalculate = $this->getFormElement('calculate');
		$whatWillCalculateValue = $whatWillCalculate->getValue();
		$result = array();
		switch ($whatWillCalculateValue)
		{
			case self::WHAT_WILL_CALCULATE_ALL_TREATMENT_BY_HOUR:
				$result['value'] = $row['first_treatment_qty'] + $row['repeated_treatment_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_ALL:
				$result['value'] = $row['first_treatment_qty'] + $row['repeated_treatment_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_FIRST:
				$result['value'] = $row['first_treatment_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_DUPLICATE:
				$result['value'] = $row['repeated_treatment_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_POSITIVE_MARK:
				$result['value'] = $row['positive_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_ANSWERED:
				$result['value'] = $row['answered_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_ALL_APPOINTED:
				$result['value'] = $row['answered_qty'] + $row['skipped_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_SKIPPED:
				$result['value'] = $row['skipped_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_NEGATIVE_MARK:
				$result['value'] = $row['negative_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_WITHOUT_MARK:
				$result['value'] = $row['without_mark_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_ALL_MARK:
				$result['value'] = $row['positive_qty'] + $row['negative_qty'];
				break;
			case self::WHAT_WILL_CALCULATE_CONTENTMENT:
				$result['value'] = floor( ($row['positive_qty'] / ($row['positive_qty'] + $row['negative_qty'])) * 100);
				break;
		}

		return $result;
	}

	/**
	 * @return mixed
	 */
	public function getMultipleBiGroupedDemoData()
	{
		$this->setCalculatedData($this->getPreparedDemoData());
		return $this->getMultipleBiGroupedData();
	}

	/**
	 * @return array
	 */
	public function getMultipleDemoData()
	{
		$this->setCalculatedData($this->getPreparedDemoData());
		return $this->getMultipleData();
	}

	/**
	 * @return array
	 */
	public function getMultipleGroupedDemoData()
	{
		$this->setCalculatedData($this->getPreparedDemoData());
		return $this->getMultipleGroupedData();
	}

	/**
	 * @return array
	 */
	public function getSingleDemoData()
	{
		$this->setCalculatedData($this->getPreparedDemoData());
		return $this->getSingleData();
	}
}
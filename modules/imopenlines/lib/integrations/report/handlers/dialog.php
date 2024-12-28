<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Handlers;

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Fields\Valuable\DropDown;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Report\VisualConstructor\IReportMultipleGroupedData;
use Bitrix\Report\VisualConstructor\IReportSingleData;

/**
 * Class Dialog
 * @package Bitrix\ImOpenLines\Integrations\Report\Handlers
 */
class Dialog extends Base implements IReportSingleData, IReportMultipleData, IReportMultipleGroupedData
{
	const WHAT_WILL_CALCULATE_AVERAGE_TIME_TO_ANSWER = 'AVERAGE_TIME';

	/**
	 * Dialog constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setTitle(Loc::getMessage('OPEN_LINES_DIALOGS'));
		$this->setCategoryKey('open_lines');
	}

	/**
	 * @param null $groupBy
	 * @return array
	 */
	public function getWhatWillCalculateOptions($groupBy = null)
	{
		return array(
			self::WHAT_WILL_CALCULATE_AVERAGE_TIME_TO_ANSWER => Loc::getMessage('WHAT_WILL_CALCULATE_AVERAGE_TIME_TO_ANSWER'),
		);
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
			case self::WHAT_WILL_CALCULATE_AVERAGE_TIME_TO_ANSWER:
				$query->addSelect(new ExpressionField('VALUE', '(SUM(AVERAGE_SECS_TO_ANSWER) / COUNT(*)) / 60'));
				break;
		}
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
			case self::WHAT_WILL_CALCULATE_AVERAGE_TIME_TO_ANSWER:
				$data['config']['unitOfMeasurement'] = Loc::getMessage('DIALOG_MINUTE');
				break;
		}

		return $data;
	}


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
				'average_first_answer_time' => rand(1, 6),
			);
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
			case self::WHAT_WILL_CALCULATE_AVERAGE_TIME_TO_ANSWER:
				$result['value'] = $row['average_first_answer_time'];
				break;
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
				case self::WHAT_WILL_CALCULATE_AVERAGE_TIME_TO_ANSWER:
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
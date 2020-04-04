<?php
namespace Bitrix\Crm\Integration\Report\Handler;

use Bitrix\Crm\CompanyTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Report\VisualConstructor\IReportMultipleGroupedData;
use Bitrix\Report\VisualConstructor\IReportSingleData;

class Company extends Base implements IReportSingleData, IReportMultipleData, IReportMultipleGroupedData
{
	const WHAT_WILL_CALCULATE_COUNT = 'COUNT';

	const GROUPING_BY_RESPONSIBLE = 'RESPONSIBLE';
	const GROUPING_BY_DATE = 'DATE';

	const FILTER_FIELDS_PREFIX = 'FROM_COMPANY_';

	public function __construct()
	{
		parent::__construct();
		$this->setTitle('Company');
		$this->setCategoryKey('crm');
	}

	protected function getGroupByOptions()
	{
		return [
			'1' => 11
		];
	}

	public function getWhatWillCalculateOptions($groupingValue = null)
	{
		return [
			'calc' => 'Calc'
		];
	}

	/**
	 * Called every time when calculate some report result before passing some concrete handler, such us getMultipleData or getSingleData.
	 * Here you can get result of configuration fields of report, if report in widget you can get configurations of widget.
	 *
	 * @return mixed
	 */
	public function prepare()
	{
		/** @var DropDown $grouping */
		$groupingField = $this->getFormElement('groupingBy');
		$groupingValue = $groupingField ? $groupingField->getValue() : null;

		$query = new Query(CompanyTable::getEntity());
		switch ($groupingValue)
		{
			case self::GROUPING_BY_DATE:
				$query->registerRuntimeField(new ExpressionField('DATE_CREATE_DAY', "DATE_FORMAT(%s, '%%Y-%%m-%%d 00:00')", 'DATE_CREATE'));
				$query->addSelect('DATE_CREATE_DAY');
				break;
		}

		$query->addSelect(new ExpressionField('COUNT', 'COUNT(*)'));
		$results = $query->exec()->fetchAll();

		$leadCountListByGroup = [];
		foreach ($results as $result)
		{
			switch ($groupingValue)
			{
				case self::GROUPING_BY_DATE:
					$leadCountListByGroup[$result['DATE_CREATE_DAY']]['value'] = $result['COUNT'];
					$leadCountListByGroup[$result['DATE_CREATE_DAY']]['label'] = $result['DATE_CREATE_DAY'];
					break;
			}
		}

		return $leadCountListByGroup;
	}

	/**
	 * array with format
	 * array(
	 *     'items' => array(
	 *            array(
	 *                'label' => 'Some Title',
	 *                'value' => 5,
	 *                'targetUrl' => 'http://url.domain?params=param'
	 *          )
	 *     )
	 * )
	 * @return array
	 */
	public function getMultipleData()
	{
		// TODO: Implement getMultipleData() method.
	}

	/**
	 * @return array
	 */
	public function getMultipleDemoData()
	{
		// TODO: Implement getMultipleDemoData() method.
	}

	/**
	 * Array format for return this method:<br>
	 * array(
	 *      'items' => array(
	 *           array(
	 *              'groupBy' => 01.01.1970 or 15 etc.
	 *              'title' => 'Some Title',
	 *              'value' => 1,
	 *              'targetUrl' => 'http://url.domain?params=param'
	 *          ),
	 *          array(
	 *              'groupBy' => 01.01.1970 or 15 etc.
	 *              'title' => 'Some Title',
	 *              'value' => 2,
	 *              'targetUrl' => 'http://url.domain?params=param'
	 *          )
	 *      ),
	 *      'config' => array(
	 *          'groupsLabelMap' => array(
	 *              '01.01.1970' => 'Start of our internet evolution'
	 *              '15' =>  'Just a simple integer'
	 *          ),
	 *          'reportTitle' => 'Some title for this report'
	 *      )
	 * )
	 * @return array
	 */
	public function getMultipleGroupedData()
	{
		$calculatedData = $this->getCalculatedData();

		$grouping = $this->getFormElement('groupingBy');
		$groupingValue = $grouping ? $grouping->getValue() : null;
		$items = [];
		$config = [];
		if ($groupingValue == self::GROUPING_BY_DATE)
		{
			$config['mode'] = 'date';
		}

		foreach ($calculatedData as $groupingKey => $item)
		{
			$items[] = array(
				'groupBy' => $groupingKey,
				'label' => $item['label'],
				'value' => $item['value'],
			);

			$config['groupsLabelMap'][$groupingKey] = $item['label'];
		}

		$config['reportTitle'] = $this->getFormElement('label')->getValue();
		$result =  [
			'items' => $items,
			'config' => $config,
		];
		return $result;
	}

	/**
	 * @return array
	 */
	public function getMultipleGroupedDemoData()
	{
		// TODO: Implement getMultipleGroupedDemoData() method.
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
		// TODO: Implement getSingleData() method.
	}

	/**
	 * @return array
	 */
	public function getSingleDemoData()
	{
		// TODO: Implement getSingleDemoData() method.
	}
}
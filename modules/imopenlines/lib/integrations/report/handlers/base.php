<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Handlers;

use Bitrix\ImOpenLines\Config;
use Bitrix\ImConnector\Connector;
use Bitrix\ImOpenLines\Model\QueueTable;
use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable;
use Bitrix\ImOpenLines\Integrations\Report\VisualConstructor\Fields\Valuable\DropDownResponsible;
use Bitrix\Main\Loader;
use Bitrix\Main\DB\Result;
use Bitrix\Main\UserTable;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Fields\Div;
use Bitrix\Report\VisualConstructor\Helper\Util;
use Bitrix\Report\VisualConstructor\Handler\BaseReport;
use Bitrix\Report\VisualConstructor\Fields\Valuable\DropDown;
use Bitrix\Report\VisualConstructor\Fields\Valuable\TimePeriod;

/**
 * Class Base
 * @package Bitrix\ImOpenLines\Integrations\Report\Handlers
 */
abstract class Base extends BaseReport
{
	const GROUP_BY_DATE = 'DATE';
	const GROUP_BY_RESPONSIBLE = 'RESPONSIBLE';
	const GROUP_BY_LINE = 'LINE';
	const GROUP_BY_CHANEL = 'CHANEL';

	/**
	 *  Collect form elements for specific widget configuration form
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function collectFormElements()
	{
		$listOpenLinesOptions = $this->getOpenLinesOptions();
		$listChanelOptions = $this->getChanelOptions();
		$listResponsibleOptions = $this->getResponsibleOptions();
		$listLinesResponsibleOptions = $this->getLinesResponsibleOptions();

		parent::collectFormElements();
		$rowContainer = new Div();
		$rowContainer->addClass('report-configuration-row');
		$whatWillCalculateField = $this->getFormElement('calculate');

		$openLineSelect = new DropDown('filterOpenLine');
		$openLineSelect->setLabel(Loc::getMessage('FILTER_BY_OPEN_LINE'));
		$openLineSelect->addOptions($listOpenLinesOptions);
		$this->addFormElementBefore($openLineSelect, $whatWillCalculateField);

		$channelSelect = new DropDown('filterByChanel');
		$channelSelect->setLabel(Loc::getMessage('FILTER_BY_CHANNEL'));
		$channelSelect->addOptions($listChanelOptions);
		$this->addFormElementBefore($channelSelect, $whatWillCalculateField);

		$responsibleSelect = new DropDownResponsible('filterByResponsible');
		$responsibleSelect->setLabel(Loc::getMessage('FILTER_BY_RESPONSIBLE'));
		$responsibleSelect->addOptions($listResponsibleOptions);
		$responsibleSelect->setLinesOperators($listLinesResponsibleOptions);
		$responsibleSelect->setOpenLines($openLineSelect);
		$this->addFormElementBefore($responsibleSelect, $whatWillCalculateField);

		if ($previewBlock = $this->getWidgetHandler()->getFormElement('view_type'))
		{
			$previewBlock->addJsEventListener($openLineSelect, $openLineSelect::JS_EVENT_ON_CHANGE, [
				'class' => 'BX.Report.VisualConstructor.FieldEventHandlers.PreviewBlock',
				'action' => 'reloadWidgetPreview'
			]);
			$previewBlock->addJsEventListener($channelSelect, $channelSelect::JS_EVENT_ON_CHANGE, [
				'class' => 'BX.Report.VisualConstructor.FieldEventHandlers.PreviewBlock',
				'action' => 'reloadWidgetPreview'
			]);
			$previewBlock->addJsEventListener($responsibleSelect, $responsibleSelect::JS_EVENT_ON_CHANGE, [
				'class' => 'BX.Report.VisualConstructor.FieldEventHandlers.PreviewBlock',
				'action' => 'reloadWidgetPreview'
			]);
		}

	}

	/**
	 * @return array
	 */
	protected function getOpenLinesOptions()
	{
		static $result = array();
		if (!$result)
		{
			$openLines = Config::getOptionList();
			foreach ($openLines as $openLine)
			{
				$result[$openLine['ID']] = $openLine['NAME'];
			}
		}


		return $result;
	}

	/**
	 * @param int $openLineId
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function getChanelOptions($openLineId = 1)
	{
		$result = array();
		if (Loader::includeModule('imconnector'))
		{
			$channels = Connector::getListActiveConnector(false, true);
			foreach ($channels as $channelKey => $label)
			{
				$result[$channelKey] = $label;
				//TODO show channel which exist in selected open line
				//			$isChannelActive = Status::getInstance($channelKey)->isStatus();
				//			if ($isChannelActive)
				//			{
				//
				//			}
			}
		}


		return $result;
	}

	/**
	 * @return array[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getResponsible()
	{
		static $result = null;
		if ($result !== null)
		{
			return $result;
		}

		$operatorIds = [];
		$operatorsLines = [];
		$result = [
			'operatorsName' => [],
			'operatorsLines' => []
		];

		$queueResults = QueueTable::getList([
			'select' => [
				'ID',
				'USER_ID',
				'CONFIG_ID'
			],
			'order' => [
				'USER_ID' => 'ASC'
			]
		])->fetchAll();

		foreach ($queueResults as $resultRow)
		{
			$operatorIds[$resultRow['ID']] = (int)$resultRow['USER_ID'];
			$operatorsLines[$resultRow['CONFIG_ID']][(int)$resultRow['USER_ID']] = '';
		}

		if (!$operatorIds)
		{
			return $result;
		}

		$userQuery = new Query(UserTable::getEntity());
		$userQuery->addSelect('ID');
		$userQuery->addSelect('NAME');
		$userQuery->addSelect('LAST_NAME');
		$userQuery->whereIn('ID', $operatorIds);
		$users = $userQuery->exec()->fetchAll();

		foreach ($users as $user)
		{
			$name = \CUser::FormatName(\CSite::GetDefaultNameFormat(), [
				'NAME' => $user['NAME'],
				'LAST_NAME' => $user['LAST_NAME']
			]);

			$result['operatorsName'][$user['ID']] = $name;
		}
		foreach ($operatorsLines as $idLines => $operators)
		{
			foreach ($operators as $id => $name)
			{
				if(isset($result['operatorsName'][$id]))
				{
					$result['operatorsLines'][$idLines][$id] = $result['operatorsName'][$id];
				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getResponsibleOptions()
	{
		return $this->getResponsible()['operatorsName'];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getLinesResponsibleOptions()
	{
		return $this->getResponsible()['operatorsLines'];
	}

	/**
	 * @return array
	 */
	protected function getGroupByOptions()
	{
		if ($this->getView()->getKey() === 'activity')
		{
			return array(
				self::GROUP_BY_DATE => Loc::getMessage('GROUPING_DATE'),
			);
		}
		return array(
			self::GROUP_BY_DATE => Loc::getMessage('GROUPING_DATE'),
			self::GROUP_BY_RESPONSIBLE => Loc::getMessage('GROUPING_RESPONSIBLE'),
			self::GROUP_BY_LINE => Loc::getMessage('GROUPING_LINE'),
			self::GROUP_BY_CHANEL => Loc::getMessage('GROUPING_CHANEL')
		);
	}

	/**
	 * @return Query
	 */
	protected function getQueryForPrepareData()
	{
		$query = $this->getQueryInstance();
		$query = $this->prepareQueryForFiltering($query);


		/** @var DropDown $grouping */
		$grouping = $this->getFormElement('groupingBy');
		$groupingValue = $grouping ? $grouping->getValue() : null;

		switch ($groupingValue)
		{
			case self::GROUP_BY_CHANEL:
				$query = $this->prepareQueryForGroupingByChannel($query);
				break;
			case self::GROUP_BY_DATE:
				$query = $this->prepareQueryForGroupingByDate($query);
				break;
			case self::GROUP_BY_LINE:
				$query = $this->prepareQueryForGroupingByLine($query);
				break;
			case self::GROUP_BY_RESPONSIBLE:
				$query = $this->prepareQueryForGroupingByResponsible($query);
				break;
		}


		return $query;
	}

	/**
	 * @return Query
	 */
	protected function getQueryInstance()
	{
		return new Query(DialogStatTable::getEntity());
	}
	/**
	 * @param Query $query
	 * @return Query
	 */
	private function  prepareQueryForFiltering(Query $query)
	{
		/** @var TimePeriod $period */
		$period = $this->getWidgetHandler()->getFormElement('time_period');
		$periodStartEnd = $period->getValueAsPeriod();
		$query->where('DATE', '>=', $periodStartEnd['start']);
		$query->where('DATE', '<=', $periodStartEnd['end']);

		$openLineField = $this->getFormElement('filterOpenLine');
		$openLineFieldValue = $openLineField->getValue();
		if ($openLineFieldValue !== '__')
		{
			$query->where('OPEN_LINE_ID', $openLineFieldValue);
		}

		$channelField = $this->getFormElement('filterByChanel');
		$channelFieldValue = $channelField->getValue();
		if ($channelFieldValue !== '__')
		{
			$query->where('SOURCE_ID', $channelFieldValue);
		}


		$responsibleField = $this->getFormElement('filterByResponsible');
		$responsibleFieldValue = $responsibleField->getValue();

		if ($responsibleFieldValue !== '__')
		{
			$query->where('OPERATOR_ID', $responsibleFieldValue);
		}

		$query->where('OPERATOR_ID', '!=', 0);
		return $query;
	}

	/**
	 * @param Query $query
	 * @return Query
	 */
	private function prepareQueryForGroupingByChannel(Query $query)
	{
		$query->addSelect('SOURCE_ID');
		$query->addGroup('SOURCE_ID');
		return $query;
	}

	/**
	 * @param Query $query
	 * @return Query
	 */
	private function prepareQueryForGroupingByLine(Query $query)
	{
		$query->addSelect('OPEN_LINE_ID');
		$query->addGroup('OPEN_LINE_ID');
		return $query;
	}
	/**
	 * @param Query $query
	 * @return Query
	 */
	private function prepareQueryForGroupingByDate(Query $query)
	{
		$query->addSelect('DATE');
		$query->addGroup('DATE');
		return $query;
	}

	/**
	 * @param Query $query
	 * @return Query
	 */
	private function prepareQueryForGroupingByResponsible(Query $query)
	{
		$query->addSelect('OPERATOR_ID');
		$query->addGroup('OPERATOR_ID');
		return $query;
	}


	/**
	 * @param Result $result
	 * @return array
	 */
	protected function getCalculatedDataFromDbResult(Result $result)
	{
		$calculatedResult = array();
		/** @var DropDown $grouping */
		$grouping = $this->getFormElement('groupingBy');
		$groupingValue = $grouping ? $grouping->getValue() : null;
		while ($ary = $result->fetch())
		{
			switch ($groupingValue)
			{
				case self::GROUP_BY_DATE:
					$calculatedResult += $this->prepareItemForGroupingByDate($ary);
					break;
				case self::GROUP_BY_RESPONSIBLE:
					$calculatedResult += $this->prepareItemForGroupingByResponsible($ary);
					break;
				case self::GROUP_BY_CHANEL:
					$calculatedResult += $this->prepareItemForGroupingByChannel($ary);
					break;
				case self::GROUP_BY_LINE:
					$calculatedResult += $this->prepareItemForGroupingByLine($ary);
					break;
				default:
					$calculatedResult['withoutGrouping'] = $this->prepareItemForNoneGrouping($ary);
			}
		}

		return $calculatedResult;
	}

	/**
	 * @param $array
	 * @return array
	 */
	private function prepareItemForGroupingByDate($array)
	{
		/** @var DateTime|Date $date */
		$date = $array['DATE'];
		if ($date instanceof DateTime)
		{
			if(\CTimeZone::Enabled())
			{
				$formatDate = $date->toUserTime()->format('Y-m-d H:i');
			}
			else
			{
				$formatDate = $date->format('Y-m-d H:i');
			}
		}
		else
		{
			$formatDate = $date->format('Y-m-d H:i');
		}
		return array(
			$formatDate => array(
			'label' => $formatDate,
			'value' => (int)$array['VALUE']
		));
	}

	/**
	 * @param $array
	 * @return array
	 */
	private function prepareItemForGroupingByLine($array)
	{
		$openLinesLabelMap = $this->getOpenLineLabelMap();
		return array(
			$array['OPEN_LINE_ID'] => array(
				'label' => isset($openLinesLabelMap[$array['OPEN_LINE_ID']]) ? $openLinesLabelMap[$array['OPEN_LINE_ID']] : 'Undefined',
				'value' => (int)$array['VALUE']
			));
	}

	/**
	 * @return array
	 */
	private function getOpenLineLabelMap()
	{
		static $map;
		if (!$map)
		{
			$openLines = Config::getOptionList();
			foreach ($openLines as $line)
			{
				$map[$line['ID']] = $line['NAME'];
			}
		}

		return $map;
	}

	/**
	 * @param $array
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function prepareItemForGroupingByResponsible($array)
	{
		$userQuery = new Query(UserTable::getEntity());
		$userQuery->addSelect('NAME');
		$userQuery->addSelect('LAST_NAME');
		$userQuery->addSelect('SECOND_NAME');
		$userQuery->addSelect('LOGIN');
		$userQuery->addSelect('PERSONAL_PHOTO');
		$userQuery->where('ID', $array['OPERATOR_ID']);
		$user = $userQuery->exec()->fetchRaw();
		$name = \CUser::FormatName(\CSite::GetNameFormat(false), array(
			"NAME" => $user["NAME"],
			"LAST_NAME" => $user["LAST_NAME"],
			"SECOND_NAME" => $user["SECOND_NAME"],
			"LOGIN" => $user["LOGIN"]
		), false, false);

		return array(
			(string)$array['OPERATOR_ID'] => array(
			'label' => $name,
			'value' => (int)$array['VALUE'],
			'logo' => Util::getAvatarSrc($user['PERSONAL_PHOTO'], 100 ,100)
		));
	}

	/**
	 * @param $array
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function prepareItemForGroupingByChannel($array)
	{
		Loader::includeModule('imconnector');
		$sourcesLabelMap = Connector::getListConnector();
		return array(
			$array['SOURCE_ID'] => array(
			'label' => isset($sourcesLabelMap[$array['SOURCE_ID']]) ? $sourcesLabelMap[$array['SOURCE_ID']] : 'Undefined',
			'value' => (int)$array['VALUE']
		));
	}

	/**
	 * @param $array
	 * @return array
	 */
	private function prepareItemForNoneGrouping($array)
	{
		return
			array(
				'value' => (int)$array['VALUE']
			);
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
		$calculationResult = $this->getCalculatedData();
		$items = array();
		foreach ($calculationResult as $result)
		{
			$items[] = array(
				'label' => $result['label'],
				'value' => $result['value'],
			);
		}
		return array(
			'items' => $items
		);
	}


	/**
	 * @return array with format
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
	 */
	public function getMultipleGroupedData()
	{
		$calculationResult = $this->getCalculatedData();
		$items = array();
		$config = array(
			'reportTitle' => $this->getFormElement('label')->getValue(),
			'reportColor' => $this->getFormElement('color')->getValue()
		);

		$grouping = $this->getFormElement('groupingBy');
		$groupingValue = $grouping ? $grouping->getValue() : null;
		if ($groupingValue == self::GROUP_BY_DATE)
		{
			$config['mode'] = 'date';
		}

		foreach ($calculationResult as $groupingKey => $result)
		{
			$items[] = array(
				'groupBy' => $groupingKey,
				'label' => $result['label'],
				'value' => $result['value'],
			);
			$config['groupsLabelMap'][$groupingKey] = $result['label'];
			$config['groupsLogoMap'][$groupingKey] = $result['logo'];
		}
		return array(
			'items' => $items,
			'config' => $config,
		);
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
		$calculationResult = $this->getCalculatedData();
		$colorFieldValue = $this->getFormElement('color');



		$data['title'] = $this->getFormElement('label')->getValue();

		$data['value'] = $calculationResult['withoutGrouping']['value'];
		$data['config']['color'] = $colorFieldValue ? $colorFieldValue->getValue() : '#ffffff';

		return $data;
	}



	abstract protected function getGeneratedDemoData();
	abstract protected function getPreparedDemoRow($row);
	abstract protected function prepareResultByWhatWillCalculate($data, $whatWillCalculateValue);

	/**
	 * @return array
	 */
	protected function getPreparedDemoData()
	{
		$demoData = $this->getGeneratedDemoData();
		$grouping = $this->getFormElement('groupingBy');
		$groupingValue = $grouping ? $grouping->getValue() : null;

		$result = array();
		foreach ($demoData as $data)
		{
			switch ($groupingValue)
			{
				case self::GROUP_BY_CHANEL:
					$result[$data['source_id']]['value'][] = $this->getPreparedDemoRow($data)['value'];
					$result[$data['source_id']]['label'] = $data['source_id'];
					break;
				case self::GROUP_BY_DATE:
					$result[$data['date']]['value'][] = $this->getPreparedDemoRow($data)['value'];
					$result[$data['date']]['label'] = $data['date'];
					break;
				case self::GROUP_BY_RESPONSIBLE:
					$result[$data['operator_id']]['value'][] = $this->getPreparedDemoRow($data)['value'];
					$result[$data['operator_id']]['label'] = Loc::getMessage('REPORT_OPERATOR_DEMO_NAME_PREFIX_NEW') . '-' . $data['operator_id'];
					break;
				default:
					$result['withoutGrouping']['value'][] =  $this->getPreparedDemoRow($data)['value'];
					break;
			}
		}

		$calculateField = $this->getFormElement('calculate');
		$whatWillCalculateValue = $calculateField ? $calculateField->getValue() : null;
		return $this->prepareResultByWhatWillCalculate($result, $whatWillCalculateValue);
	}


}

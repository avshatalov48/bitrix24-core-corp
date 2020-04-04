<?php

namespace Bitrix\Crm\Integration\Report\Handler;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\Web\Uri;
use Bitrix\Report\VisualConstructor\AnalyticBoard;
use Bitrix\Report\VisualConstructor\Handler\BaseReport;
use Bitrix\Report\VisualConstructor\Helper\Filter;
use Bitrix\Report\VisualConstructor\RuntimeProvider\AnalyticBoardProvider;

/**
 * Class Base
 * @package Bitrix\Crm\Integration\Report\Handler
 */
abstract class Base extends BaseReport
{
	const FILTER_FIELDS_PREFIX = '';

	protected function getFilterParameters()
	{
		static $filterParameters = [];

		$filter = $this->getFilter();
		$filterId = $filter->getFilterParameters()['FILTER_ID'];

		if (!$filterParameters[$filterId])
		{
			//@TODO it is HACK here cant be filter class by no means
			//maybe add some construction to collect all filters for reports in one container
			$options = new Options($filterId, $filter::getPresetsList());
			$filterParameters[$filterId] = $options->getFilter($filter::getFieldsList());
		}



		return $this->mutateFilterParameter($filterParameters[$filterId]);
	}

	private function getFilter()
	{
		static $filter;
		if ($filter)
		{
			return $filter;
		}

		$boardKey = $this->getWidgetHandler()->getWidget()->getBoardId();
		$board = $this->getAnalyticBoardByKey($boardKey);
		if ($board)
		{
			$filter = $board->getFilter();
		}
		else
		{
			$filter = new Filter($boardKey);
		}

		return $filter;
	}

	protected function mutateFilterParameter($filterParameters)
	{
		$mutatedFilterParameters = [];

		$filter = $this->getFilter();
		$fieldList = $filter::getFieldsList();

		$preparedFieldList = [];
		foreach ($fieldList as $field)
		{
			if ($field['id'] === 'TIME_PERIOD')
			{
				$preparedFieldList[$field['id']] = [
					'type' => isset($field['type']) ? $field['type'] : 'none',
					'field' => $field
				];
				continue;
			}

			if (strpos($field['id'], static::FILTER_FIELDS_PREFIX) === 0)
			{
				$newFieldKeyList = explode(static::FILTER_FIELDS_PREFIX, $field['id']);
				$newFieldKey = $newFieldKeyList[1];
				$preparedFieldList[$newFieldKey] = [
					'type' => isset($field['type']) ? $field['type'] : 'none',
					'field' => $field
				];
			}
		}

		foreach ($filterParameters as $key => $value)
		{
			if (strpos($key, 'TIME_PERIOD') === 0)
			{
				$mutatedFilterParameters[$key] = $value;
				continue;
			}

			if (strpos($key, static::FILTER_FIELDS_PREFIX) === 0)
			{
				$newKeyList = explode(static::FILTER_FIELDS_PREFIX, $key);
				$newKey = $newKeyList[1];
				$mutatedFilterParameters[$newKey] = $value;
			}
		}

		if (empty($mutatedFilterParameters))
		{
			return $mutatedFilterParameters;
		}


		foreach ($preparedFieldList as $fieldId => $preparedField)
		{
			switch ($preparedField['type'])
			{
				case 'none':
					if (isset($mutatedFilterParameters[$fieldId]))
					{
						$mutatedFilterParameters[$fieldId] = [
							'type' => 'none',
							'value' => $mutatedFilterParameters[$fieldId]
						];
					}
					break;
				case 'list':
					if (isset($mutatedFilterParameters[$fieldId]))
					{
						$mutatedFilterParameters[$fieldId] = [
							'type' => 'list',
							'value' => $mutatedFilterParameters[$fieldId]
						];
					}
					break;
				case 'date':
					if (isset($mutatedFilterParameters[$fieldId . '_from']) && isset($mutatedFilterParameters[$fieldId . '_to']))
					{
						$mutatedFilterParameters[$fieldId] = [
							'type' => 'date',
							'from' => new DateTime($mutatedFilterParameters[$fieldId . '_from']),
							'to' =>  new DateTime($mutatedFilterParameters[$fieldId . '_to']),
							'datesel' => $mutatedFilterParameters[$fieldId . '_datesel'],
							'month' => $mutatedFilterParameters[$fieldId . '_month'],
							'quarter' => $mutatedFilterParameters[$fieldId . '_quarter'],
							'year' => $mutatedFilterParameters[$fieldId . '_year'],
							'days' => $mutatedFilterParameters[$fieldId . '_days'],
						];
					}

					unset($mutatedFilterParameters[$fieldId . '_datesel']);
					unset($mutatedFilterParameters[$fieldId . '_month']);
					unset($mutatedFilterParameters[$fieldId . '_quarter']);
					unset($mutatedFilterParameters[$fieldId . '_year']);
					unset($mutatedFilterParameters[$fieldId . '_days']);
					unset($mutatedFilterParameters[$fieldId . '_from']);
					unset($mutatedFilterParameters[$fieldId . '_to']);
					break;
				case 'checkbox':
					if (isset($mutatedFilterParameters[$fieldId]))
					{
						$mutatedFilterParameters[$fieldId] = [
							'type' => 'checkbox',
							'value' => $mutatedFilterParameters[$fieldId]
						];
					}
					break;
				case 'number':
					if (isset($mutatedFilterParameters[$fieldId . '_from']) && isset($mutatedFilterParameters[$fieldId . '_to']))
					{
						$mutatedFilterParameters[$fieldId] = [
							'type' => 'diapason',
							'numsel' => $mutatedFilterParameters[$fieldId . '_numsel'],
							'from' => $mutatedFilterParameters[$fieldId . '_from'],
							'to' => $mutatedFilterParameters[$fieldId . '_to']
						];
					}

					unset($mutatedFilterParameters[$fieldId . '_numsel']);
					unset($mutatedFilterParameters[$fieldId . '_from']);
					unset($mutatedFilterParameters[$fieldId . '_to']);
					break;
				case 'text':
					if (isset($mutatedFilterParameters[$fieldId]))
					{
						$mutatedFilterParameters[$fieldId] = [
							'type' => 'text',
							'value' => $mutatedFilterParameters[$fieldId]
						];
					}
					break;
				case 'custom_entity':
					if (isset($mutatedFilterParameters[$fieldId]))
					{
						if ($preparedField['field']['selector']['TYPE'] === 'crm_entity')
						{

							$encodedValue = $mutatedFilterParameters[$fieldId];
							$decodedValue  = json_decode($mutatedFilterParameters[$fieldId], true);
							//TODO change to other structure, ere can potential bug
							foreach ($preparedField['field']['selector']['DATA']['ENTITY_TYPE_NAMES'] as $entityName)
							{
								$mutatedFilterParameters[$fieldId] = $decodedValue[$entityName];
							}
						}

						$mutatedFilterParameters[$fieldId] = [
							'type' => 'custom_entity',
							'selectorEntityType' => 'none',
							'label' => $mutatedFilterParameters[$fieldId . '_label'],
							'value' => $mutatedFilterParameters[$fieldId],

						];

						if ($preparedField['field']['selector']['TYPE'] === 'crm_entity')
						{
							$mutatedFilterParameters[$fieldId]['selectorEntityType'] = 'crm_entity';
							$mutatedFilterParameters[$fieldId]['encodedValue'] = $encodedValue;
						}

						unset($mutatedFilterParameters[$fieldId . '_label']);
					}

					break;
			}
		}



		return $mutatedFilterParameters;
	}

	/**
	 * @param $key
	 * @return AnalyticBoard
	 */
	private function getAnalyticBoardByKey($key)
	{
		$boardProvider = new AnalyticBoardProvider();
		$boardProvider->addFilter('boardKey', $key);
		$board = $boardProvider->execute()->getFirstResult();
		return $board;
	}

	protected function getTargetUrl($baseUri, $params = [])
	{
		$uri = new Uri($baseUri);
		$uri->addParams([
			'apply_filter' => 'Y',
			'from_analytics' => 'Y',
			'board_id' => $this->getWidgetHandler()->getWidget()->getBoardId()
		]);
		if (!empty($params))
		{
			$uri->addParams($params);
		}

		$filterParameters = $this->getFilterParameters();

		foreach ($filterParameters as $key => $filterParameter)
		{
			if ($key === 'TIME_PERIOD')
			{
				continue;
			}

			switch ($filterParameter['type'])
			{
				case 'list':
				case 'text':
				case 'checkbox':
					$uri->addParams([$key => $filterParameter['value']]);
					break;
				case 'date':
					/** @var DateTime $from */
					$from = $filterParameter['from'];
					/** @var DateTime $to */
					$to = $filterParameter['to'];

					$uri->addParams([$key . '_datesel' => $filterParameter['datesel']]);
					$uri->addParams([$key . '_month' => $filterParameter['month']]);
					$uri->addParams([$key . '_year' => $filterParameter['year']]);
					$uri->addParams([$key . '_quarter' => $filterParameter['quarter']]);
					$uri->addParams([$key . '_days' => $filterParameter['days']]);
					$uri->addParams([$key . '_from' => $from->format('d.m.Y H:i:s')]);
					$uri->addParams([$key . '_to' => $to->format('d.m.Y H:i:s')]);
					break;
				case 'diapason':
					$uri->addParams([$key . '_numsel' => $filterParameter['numsel']]);
					$uri->addParams([$key . '_from' => $filterParameter['from']]);
					$uri->addParams([$key . '_to' => $filterParameter['to']]);
					break;
				case 'custom_entity':
					$uri->addParams([$key . '_label' => $filterParameter['label']]);
					$uri->addParams([$key . '_value' => $filterParameter['value']]);
					if($filterParameter['selectorEntityType'] === 'crm_entity')
					{
						$uri->addParams([$key => $filterParameter['encodedValue']]);
					}
					else
					{
						$uri->addParams([$key => $filterParameter['value']]);
					}
					break;
			}
		}
		return $uri->getUri();
	}
}
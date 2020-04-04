<?php

namespace Bitrix\Crm\Integration\Report\Handler;

use Bitrix\Crm\UI\Filter\EntityHandler;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Uri;
use Bitrix\Report\VisualConstructor\AnalyticBoard;
use Bitrix\Report\VisualConstructor\Handler\BaseReport;
use Bitrix\Report\VisualConstructor\Helper\Filter;
use Bitrix\Report\VisualConstructor\RuntimeProvider\AnalyticBoardProvider;

if (!Loader::includeModule("report"))
{
	return false;
}

/**
 * Class Base
 * @package Bitrix\Crm\Integration\Report\Handler
 */
abstract class Base extends BaseReport
{
	const FILTER_FIELDS_PREFIX = '';

	/**
	 * @param array|Request $requestParameters
	 *
	 * @return |null
	 */
	public function prepareEntityListFilter($requestParameters)
	{
		return null;
	}

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
			$rawParameters = $options->getFilter($filter::getFieldsList());
			$filterParameters[$filterId] = $this->mutateFilterParameter($rawParameters);
		}

		return $filterParameters[$filterId];
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


			if (strpos($key, 'FIND') === 0)
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

		$preparedFieldListForEntityHandler = [];
		foreach ($preparedFieldList as $key => $value)
		{
			$preparedFieldListForEntityHandler[$key] = $value['field'];
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
							'from' => $mutatedFilterParameters[$fieldId.'_from'],
							'to' =>  $mutatedFilterParameters[$fieldId.'_to'],
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

						$oldMutateFilterValue = $mutatedFilterParameters[$fieldId];
						$mutatedFilterParameters[$fieldId] = [
							'type' => 'custom_entity',
							'selectorEntityType' => 'none',
							'label' => $mutatedFilterParameters[$fieldId . '_label'],
							'value' => $mutatedFilterParameters[$fieldId],
						];

						if ($preparedField['field']['selector']['TYPE'] === 'crm_entity')
						{
							$encodedValue = $oldMutateFilterValue;
							$decodedValue  = json_decode($oldMutateFilterValue, true);

							$mutatedFilterParameters[$fieldId]['selectorEntityType'] = 'crm_entity';
							$mutatedFilterParameters[$fieldId]['encodedValue'] = $encodedValue;


							$data = $preparedField['field']['selector']['DATA'];
							$entityTypeNames = isset($data['ENTITY_TYPE_NAMES']) && is_array($data['ENTITY_TYPE_NAMES'])
								? $data['ENTITY_TYPE_NAMES'] : array();

							$isMultiple = isset($data['IS_MULTIPLE']) ? $data['IS_MULTIPLE'] : false;

							//TODO change to other structure, ere can potential bug
							foreach ($entityTypeNames as $entityName)
							{
								$entityTypeQty = count($entityTypeNames);
								if($entityTypeQty > 1)
								{
									$entityTypeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeID(\CCrmOwnerType::ResolveID($entityName));
									$prefix = "{$entityTypeAbbr}_";
								}
								else
								{
									$prefix = '';
								}

								if(!(isset($decodedValue[$entityName])
									&& is_array($decodedValue[$entityName])
									&& !empty($decodedValue[$entityName]))
								)
								{
									continue;
								}

								if(!$isMultiple)
								{
									$mutatedFilterParameters[$fieldId]['value'] = "{$prefix}{$decodedValue[$entityName][0]}";
								}
								else
								{
									$effectiveValues = array();
									for($i = 0, $qty = count($decodedValue[$entityName]); $i < $qty; $i++)
									{
										$effectiveValues[] = "{$prefix}{$decodedValue[$entityName][$i]}";
									}
									$mutatedFilterParameters[$fieldId]['value'] = $effectiveValues;
								}

							}
						}


						unset($mutatedFilterParameters[$fieldId . '_label']);
					}

					break;
				case 'dest_selector':
					if (isset($mutatedFilterParameters[$fieldId]))
					{
						$mutatedFilterParameters[$fieldId] = [
							'type' => 'dest_selector',
							'label' => $mutatedFilterParameters[$fieldId . '_label'],
							'value' => $mutatedFilterParameters[$fieldId],
						];
						unset($mutatedFilterParameters[$fieldId . '_label']);
					}
					break;
			}
		}



		return $mutatedFilterParameters;
	}

	protected function getConvertedToServerTime($dateTimeStr)
	{
		$dateTime = new DateTime($dateTimeStr);
		//$from = $from->toUserTime();
		/*if (\CTimeZone::Enabled())
		{
			$diff = \CTimeZone::GetOffset();
			if ($diff > 0)
			{
				$dateTime->add('-T'. abs($diff).'S');
			}
			else
			{
				$dateTime->add('T'.abs($diff).'S');
			}
		}*/


		return $dateTime;
	}

	/**
	 * @param $key
	 * @return AnalyticBoard
	 */
	public static function getAnalyticBoardByKey($key)
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
			if ($key === 'FIND')
			{
				$uri->addParams([$key => $filterParameter]);
				continue;
			}

			switch ($filterParameter['type'])
			{
				case 'none':
				case 'list':
				case 'text':
				case 'checkbox':
					$uri->addParams([$key => $filterParameter['value']]);
					break;
				case 'date':
					if ($key === 'TIME_PERIOD')
					{
						$key = 'ACTIVE_TIME_PERIOD';
					}
					/** @var DateTime $from */
					$from = new DateTime($filterParameter['from']);
					$fromString = $from->format($from::getFormat());

					/** @var DateTime $to */
					$to = new DateTime($filterParameter['to']);
					$toString = $to->format($to::getFormat());

					$uri->addParams([$key . '_datesel' => $filterParameter['datesel']]);
					$uri->addParams([$key . '_month' => $filterParameter['month']]);
					$uri->addParams([$key . '_year' => $filterParameter['year']]);
					$uri->addParams([$key . '_quarter' => $filterParameter['quarter']]);
					$uri->addParams([$key . '_days' => $filterParameter['days']]);
					$uri->addParams([$key . '_from' => $fromString]);
					$uri->addParams([$key . '_to' => $toString]);
					break;
				case 'diapason':
					$uri->addParams([$key . '_numsel' => $filterParameter['numsel']]);
					$uri->addParams([$key . '_from' => $filterParameter['from']]);
					$uri->addParams([$key . '_to' => $filterParameter['to']]);
					break;
				case 'custom_entity':
					$uri->addParams([$key . '_label' => $filterParameter['label']]);
					if($filterParameter['selectorEntityType'] === 'crm_entity')
					{
						$uri->addParams([$key => $filterParameter['encodedValue']]);
					}
					else
					{
						$uri->addParams([$key => $filterParameter['value']]);
					}
					break;
				case 'dest_selector':
					$uri->addParams([$key . '_label' => $filterParameter['label']]);
					$uri->addParams([$key => $filterParameter['value']]);
					break;
			}
		}
		return $uri->getUri();
	}


	protected function getFormattedPassTime($spentTime)
	{
		$spentTimeUnit = 'SECS';
		$spentTimeValue = 0;

		if ($spentTime > 60 * 60 *24)
		{
			$spentTimeUnit = 'DAYS';
			$spentTimeValue = $spentTime / (60 * 60 * 24);
		}
		elseif($spentTime >= 60 * 60)
		{
			$spentTimeUnit = 'HOURS';
			$spentTimeValue = $spentTime / (60 * 60);
		}
		elseif ($spentTime > 60)
		{
			$spentTimeUnit = 'MINUTES';
			$spentTimeValue = $spentTime / 60;
		}
		elseif ($spentTime > 0)
		{
			$spentTimeUnit = 'SECS';
			$spentTimeValue = $spentTime;
		}

		return round($spentTimeValue, 2) . ' ' . Loc::getMessage("CRM_REPORT_BASE_HANDLER_DEAL_SPENT_TIME_{$spentTimeUnit}");
	}

	/**
	 * Returns [id, name, link, icon] for the specified use id.
	 *
	 * @param int $userId Id of the user.
	 * @return array|null
	 */
	public function getUserInfo($userId)
	{
		static $users = [];

		$userId = (int)$userId;

		if (!$userId)
		{
			return ['name' => Loc::getMessage('CRM_REPORT_BASE_USER_DEFAULT_NAME')];
		}

		if(isset($users[$userId]))
		{
			return $users[$userId];
		}

		// prepare link to profile
		$replaceList = ['user_id' => $userId];
		$template = '/company/personal/user/#user_id#/';
		$link = \CComponentEngine::makePathFromTemplate($template, $replaceList);

		$userFields = UserTable::getRowById($userId);
		if (!$userFields)
		{
			return ['name' => Loc::getMessage('CRM_REPORT_BASE_USER_DEFAULT_NAME')];
		}

		// format name
		$userName = \CUser::FormatName(
			\CSite::GetNameFormat(),
			[
				'LOGIN' => $userFields['LOGIN'],
				'NAME' => $userFields['NAME'],
				'LAST_NAME' => $userFields['LAST_NAME'],
				'SECOND_NAME' => $userFields['SECOND_NAME']
			],
			true,
			false
		);

		$userName =  !empty($userName) ? $userName : Loc::getMessage('CRM_REPORT_BASE_USER_DEFAULT_NAME');

		// prepare icon
		$fileTmp = \CFile::ResizeImageGet(
			$userFields['PERSONAL_PHOTO'],
			['width' => 42, 'height' => 42],
			BX_RESIZE_IMAGE_EXACT,
			false
		);
		$userIcon = $fileTmp['src'];

		$users[$userId] = [
			'id' => $userId,
			'name' => $userName,
			'link' => $link,
			'icon' => $userIcon
		];

		return $users[$userId];
	}
}
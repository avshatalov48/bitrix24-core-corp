<?php

namespace Bitrix\Crm\Integration\Report\Handler;

use Bitrix\Crm\UI\Filter\EntityHandler;
use Bitrix\Main\ArgumentException;
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

	const DEFAULT_AVATAR_WIDTH = 42;
	const DEFAULT_AVATAR_HEIGHT = 42;

	protected static $userFields = [];
	protected static $requiredUserFieldsList = ['ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME','PERSONAL_PHOTO'];

	private ?Filter $filter = null;
	private array $filterParameters = [];
	private array $users = [];

	/**
	 * Fix base method - return array only
	 *
	 * @override
	 */
	public function getCalculatedData(): array
	{
		$result = parent::getCalculatedData(); // return mixed value in base method

		return is_array($result) ? $result : [];
	}

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
		$filter = $this->getFilter();
		$filterId = $filter->getFilterParameters()['FILTER_ID'];

		if (!$this->filterParameters[$filterId])
		{
			//@TODO it is HACK here cant be filter class by no means
			//maybe add some construction to collect all filters for reports in one container
			$options = new Options($filterId, $filter->getPresetsList());
			$fieldList = $filter->getFieldsList();
			$rawParameters = $options->getFilter($fieldList);

			$this->filterParameters[$filterId] = $this->mutateFilterParameter($rawParameters, $fieldList);
		}

		return $this->filterParameters[$filterId];
	}

	protected function getFilter()
	{
		if (isset($this->filter))
		{
			return $this->filter;
		}

		$boardKey = $this->getWidgetHandler()->getWidget()->getBoardId();
		$board = $this->getAnalyticBoardByKey($boardKey);
		if ($board)
		{
			$this->filter = $board->getFilter();
		}
		else
		{
			$this->filter = new Filter($boardKey);
		}

		return $this->filter;
	}

	protected function mutateFilterParameter($filterParameters, array $fieldList)
	{
		$mutatedFilterParameters = [];
		$preparedFieldList = [];

		foreach ($fieldList as $field)
		{
			if ($field['id'] === 'TIME_PERIOD' || $field['id'] === 'PREVIOUS_PERIOD')
			{
				$preparedFieldList[$field['id']] = [
					'type' => isset($field['type']) ? $field['type'] : 'none',
					'field' => $field
				];
				continue;
			}

			if (mb_strpos($field['id'], static::FILTER_FIELDS_PREFIX) === 0)
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
			if (mb_strpos($key, 'TIME_PERIOD') === 0)
			{
				$mutatedFilterParameters[$key] = $value;
				continue;
			}
			if (mb_strpos($key, 'PREVIOUS_PERIOD') === 0)
			{
				$mutatedFilterParameters[$key] = $value;
				continue;
			}


			if (mb_strpos($key, 'FIND') === 0)
			{
				$mutatedFilterParameters[$key] = $value;
				continue;
			}

			if (mb_strpos($key, static::FILTER_FIELDS_PREFIX) === 0)
			{
				$newKeyList = explode(static::FILTER_FIELDS_PREFIX, $key);
				$newKey = $newKeyList[1];
				$normalizedKey = $this->extractFieldId($newKey);
				if (isset($preparedFieldList[$normalizedKey]))
				{
					$mutatedFilterParameters[$newKey] = $value;
				}
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
				case 'entity_selector':
					if (isset($mutatedFilterParameters[$fieldId]))
					{
						$mutatedFilterParameters[$fieldId] = [
							'type' => 'entity_selector',
							'value' => $mutatedFilterParameters[$fieldId],
						];
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
			'from_analytics' => 'Y',
			'report_id' => $this->getReport()->getGId()
		]);

		if (!empty($params))
		{
			$uri->addParams($params);
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

	public function preloadUserInfo(array $userIds)
	{
		$missingUserIds = array_diff($userIds, array_keys(static::$userFields));
		if (count($missingUserIds) === 0)
		{
			return;
		}

		$cursor = UserTable::getList([
			'select' => static::$requiredUserFieldsList,
			'filter' => [
				'=ID' => $missingUserIds
			]
		]);

		foreach ($cursor->getIterator() as $row)
		{
			static::$userFields[$row['ID']] = $row;
		}
	}

	/**
	 * Returns [id, name, link, icon] for the specified use id.
	 *
	 * @param int $userId Id of the user.
	 * @param array $params Additional optional parameters
	 *   <li> avatarWidth int
	 *   <li> avatarHeight int
	 * @return array|null
	 */
	public function getUserInfo($userId, array $params = [])
	{
		$userId = (int)$userId;
		if (!$userId)
		{
			return ['name' => Loc::getMessage('CRM_REPORT_BASE_USER_DEFAULT_NAME')];
		}

		if (isset($this->users[$userId]))
		{
			return $this->users[$userId];
		}

		// prepare link to profile
		$replaceList = ['user_id' => $userId];
		$template = '/company/personal/user/#user_id#/';
		$link = \CComponentEngine::makePathFromTemplate($template, $replaceList);

		$this->preloadUserInfo([$userId]);
		$userFields = static::$userFields[$userId];

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

		$userName = !empty($userName) ? $userName : Loc::getMessage('CRM_REPORT_BASE_USER_DEFAULT_NAME');

		// prepare icon
		$fileTmp = \CFile::ResizeImageGet(
			$userFields['PERSONAL_PHOTO'],
			[
				'width' => $params['avatarWidth'] ?? static::DEFAULT_AVATAR_WIDTH,
				'height' => $params['avatarHeight'] ?? static::DEFAULT_AVATAR_HEIGHT
			],
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true
		);
		$userIcon = $fileTmp['src'];

		$this->users[$userId] = [
			'id' => $userId,
			'name' => $userName,
			'link' => $link,
			'icon' => $userIcon
		];

		return $this->users[$userId];
	}

	public static function getPreviousPeriod(DateTime $from, DateTime $to)
	{
		$difference = $to->getTimestamp() - $from->getTimestamp();

		if($difference < 0)
		{
			throw new ArgumentException("Date from should be earlier than date to");
		}

		$to = clone $from;
		$fromTimestamp = $from->getTimestamp() - $difference;
		$from = DateTime::createFromTimestamp($fromTimestamp);

		return [$from, $to];
	}

	private function extractFieldId(string $fieldId)
	{
		$postfixes = [
			'_datesel', '_month', '_quarter', '_year', '_days', // date
			'_numsel', // number
			'_from', '_to', // date and number ranges
		];
		foreach ($postfixes as $postfix)
		{
			if (mb_substr($fieldId, -($postfixLen = mb_strlen($postfix))) === $postfix)
			{
				return mb_substr($fieldId, 0, -$postfixLen);
			}
		}
		return $fieldId;
	}
}

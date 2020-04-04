<?php
namespace Bitrix\Crm\Widget\Data\Activity;

use Bitrix\Crm\Activity\CommunicationWidgetPanel;
use Bitrix\Main;
use Bitrix\Crm\Activity;
use Bitrix\Crm\Activity\StatisticsMark;
use Bitrix\Crm\Activity\StatisticsStatus;
use Bitrix\Crm\Activity\StatisticsStream;
use Bitrix\Crm\Widget\Filter;

abstract class DataSource extends \Bitrix\Crm\Widget\Data\DataSource
{
	/** @var string */
	protected $permissionSql;
	/** @var string */
	protected static $entityListPath = null;
	/**
	 * Prepare permission SQL.
	 * @return string|boolean
	 */
	protected function preparePermissionSql()
	{
		if($this->permissionSql !== null)
		{
			return $this->permissionSql;
		}

		if(\CCrmPerms::IsAdmin($this->userID))
		{
			$this->permissionSql = '';
		}
		else
		{
			$this->permissionSql = \CCrmActivity::BuildPermSql(
				'',
				'READ',
				array(
					'RAW_QUERY' => true,
					'PERMS'=> \CCrmPerms::GetUserPermissions($this->userID)
				)
			);
		}
		return $this->permissionSql;
	}
	/**
	 * Externalize filter (prepare array for external usage).
	 * @static
	 * @param Filter $filter Source filter.
	 * @return array
	 */
	protected static function externalizeFilter(Filter $filter)
	{
		$filterParams = $filter->getParams();
		$params = Filter::externalizeParams($filterParams);
		return array_merge($params, self::externalizeFilterChannel($filter));
	}
	/**
	 * Internalize filter (prepare Filter object for internal usage).
	 * @static
	 * @param array $params Source filter params.
	 * @return Filter
	 */
	protected static function internalizeFilter(array $params)
	{
		$filterParams = Filter::internalizeParams($params);
		self::internalizeFilterChannel($params, $filterParams);
		return new Filter($filterParams);
	}

	/**
	 * Get details page URL.
	 * @param array $params Parameters.
	 * @return string
	 * @throws Main\ObjectNotFoundException
	 */
	public function getDetailsPageUrl(array $params)
	{
		$urlParams = array('WG' => 'Y', 'DS' => $this->getTypeName(), 'page' => '1', 'PN' => $this->getPresetName());

		/** @var string $field */
		$field = isset($params['field']) ? $params['field'] : '';
		if($field !== '')
		{
			$urlParams['FIELD'] = $field;
		}

		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
		{
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");
		}

		$filterParams = self::externalizeFilter($filter);
		foreach($filterParams as $k => $v)
		{
			if(!is_array($v))
			{
				$urlParams[$k] = $v;
			}
			else
			{
				$qty = count($v);
				for($i = 0; $i < $qty; $i++)
				{
					$urlParams["{$k}[{$i}]"] = $v[$i];
				}
			}
		}

		return \CHTTP::urlAddParams(self::getEntityListPath(), $urlParams);
	}
	/**
	 * Extract details page URL params from request.
	 * @static
	 * @param array $request Source request params.
	 * @return array
	 */
	public static function extractDetailsPageUrlParams(array $request)
	{
		if(!(isset($request['WG']) && strtoupper($request['WG']) === 'Y'))
		{
			return array();
		}

		$dataSourceName = isset($request['DS']) ? $request['DS'] : '';
		if($dataSourceName === '')
		{
			return array();
		}

		$result = array('WG' => 'Y', 'DS' => $dataSourceName);
		try
		{
			$filter = self::internalizeFilter($request);
			if(!$filter->isEmpty())
			{
				$result = array_merge($result, self::externalizeFilter($filter));
				if(isset($request['FIELD']))
				{
					$result['FIELD'] = $request['FIELD'];
				}
			}

		}
		catch(Main\ArgumentException $e)
		{
		}

		return $result;
	}

	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$period = $filter->getPeriod();
		$dataSourceName = isset($filterParams['DS']) ? (string)$filterParams['DS'] : '';

		//fix date -> datetime
		$periodEnd = Main\Type\DateTime::createFromTimestamp($period['END']->getTimestamp());
		$periodEnd->setTime(23, 59, 59);

		$result = array(
			'>=DEADLINE' => $period['START'],
			'<=DEADLINE' => $periodEnd,
			'=COMPLETED' => 'Y'
		);

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$result['@RESPONSIBLE_ID'] = $responsibleIDs;
		}

		if (isset($filterParams['PN']))
		{
			// filter Calls, Meetings and Emails by TYPE_ID (not by PROVIDER_TYPE_ID) for compatibility.
			list($providerId, $providerTypeId) = static::parsePresetName($filterParams['PN']);
			if (
				$providerId === Activity\Provider\Call::getId()
				&& $providerTypeId === Activity\Provider\Call::ACTIVITY_PROVIDER_TYPE_CALL
			)
			{
				$result['=TYPE_ID'] = \CCrmActivityType::Call;
			}
			elseif ($providerId === Activity\Provider\Meeting::getId())
			{
				$result['=TYPE_ID'] = \CCrmActivityType::Meeting;
			}
			elseif ($providerId === Activity\Provider\Email::getId())
			{
				$result['=TYPE_ID'] = \CCrmActivityType::Email;
			}
			else
			{
				if ($providerId)
					$result['=PROVIDER_ID'] = $providerId;
				if ($providerTypeId)
					$result['=PROVIDER_TYPE_ID'] = $providerTypeId;
			}
		}

		$field = isset($filterParams['FIELD']) ? $filterParams['FIELD'] : '';
		switch ($field)
		{
			case 'NONE_QTY':
				$result['=RESULT_MARK'] = StatisticsMark::None;
				break;
			case 'POSITIVE_QTY':
				$result['=RESULT_MARK'] = StatisticsMark::Positive;
				break;
			case 'NEGATIVE_QTY':
				$result['=RESULT_MARK'] = StatisticsMark::Negative;
				break;
			case 'ANSWERED_QTY':
				$result['=RESULT_STATUS'] = StatisticsStatus::Answered;
				unset($result['=COMPLETED']);
				break;
			case 'UNANSWERED_QTY':
				$result['=RESULT_STATUS'] = StatisticsStatus::Unanswered;
				unset($result['=COMPLETED']);
				break;
			case 'INCOMING_QTY':
				$result['=RESULT_STREAM'] = StatisticsStream::Incoming;
				unset($result['=COMPLETED']);
				break;
			case 'OUTGOING_QTY':
				$result['=RESULT_STREAM'] = StatisticsStream::Outgoing;
				unset($result['=COMPLETED']);
				break;
			case 'REVERSING_QTY':
				$result['=RESULT_STREAM'] = StatisticsStream::Reversing;
				unset($result['=COMPLETED']);
				break;
			case 'MISSING_QTY':
				$result['=RESULT_STREAM'] = StatisticsStream::Missing;
				unset($result['=COMPLETED']);
				break;
			case 'SUM_TOTAL':
				$result['>RESULT_SUM'] = 0;
				break;
			case 'TOTAL':
				if ($dataSourceName === StatusStatistics::TYPE_NAME)
				{
					$result['>STATUS_ID'] = StatisticsStatus::Undefined;
				}
				elseif ($dataSourceName === StreamStatistics::TYPE_NAME)
				{
					$result['>RESULT_STREAM'] = StatisticsStream::Undefined;
				}
				break;
		}

		return $result;
	}

	/**
	 * Get entity list path.
	 * @static
	 * @return string
	 */
	protected static function getEntityListPath()
	{
		if(self::$entityListPath === null)
		{
			self::$entityListPath = \CComponentEngine::MakePathFromTemplate(
				Main\Config\Option::get('crm', 'path_to_activity_list', '/crm/activity/', false),
				array()
			);
		}
		return self::$entityListPath;
	}


	protected static function getProviderCategories($statisticType)
	{
		$categories = array();

		$providers = CommunicationWidgetPanel::getProvidersTypesRelation();
		foreach ($providers as $provider => $types)
		{
			$categoryId = 'ACTIVITY_'.$provider::getId();

			if ($types && $provider::canUseCommunicationStatistics($statisticType))
			{
				$categories[$categoryId] = $provider::getId().':*';

				foreach ($types as $type)
				{
					$categoryId .= '_'.$type['PROVIDER_TYPE_ID'];
					$categories[$categoryId] = $provider::getId().':'.$type['PROVIDER_TYPE_ID'];
				}
			}
		}

		return $categories;
	}

	protected function getActivityProviderInfo()
	{
		return static::parsePresetName($this->getPresetName());
	}

	protected static function parsePresetName($presetName)
	{
		$providerId = $providerTypeId = null;
		$exploded = explode(':', $presetName);
		if (count($exploded) === 3)
		{
			$providerId = (string)$exploded[0];
			$providerTypeId = $exploded[1] !== '*' ? (string)$exploded[1] : null;
		}

		return array($providerId, $providerTypeId);
	}
}
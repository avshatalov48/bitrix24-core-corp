<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;
use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\PhaseSemantics;

abstract class LeadDataSource extends DataSource
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
			$this->permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::LeadName,
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS'=> \CCrmPerms::GetUserPermissions($this->userID))
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

		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$params['SEMANTIC_ID'] = $semanticID;
		}

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
		if(!isset($filterParams['extras']))
		{
			$filterParams['extras'] = array();
		}

		if(isset($params['SEMANTIC_ID']))
		{
			$filterParams['extras']['semanticID'] = $params['SEMANTIC_ID'];
		}
		self::internalizeFilterChannel($params, $filterParams);
		return new Filter($filterParams);
	}
	/**
		 * Get details page URL.
		 * @param array $params Parameters.
		 * @return string
		 */
	public function getDetailsPageUrl(array $params)
	{
		$urlParams = array('WG' => 'Y', 'DS' => $this->getTypeName(), 'page' => '1');

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
				Main\Config\Option::get('crm', 'path_to_lead_list', '/crm/lead/list/', false),
				array()
			);
		}
		return self::$entityListPath;
	}
}
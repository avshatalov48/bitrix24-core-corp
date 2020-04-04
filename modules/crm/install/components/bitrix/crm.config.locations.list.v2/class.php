<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2014 Bitrix
 */

//use Bitrix\Main\Config;
use Bitrix\Main;
use Bitrix\Sale\Location;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Sale\Location\Admin\LocationHelper as Helper;

Loc::loadMessages(__FILE__);

class CBitrixCrmConfigLocationList2Component extends CBitrixComponent
{
	protected $componentData = 	array();
	protected $dbResult = 		array();
	protected $errors = 		array('FATAL' => array(), 'NONFATAL' => array());

	protected $currentCache = 	false;

	/**
	 * Function checks and prepares all the parameters passed. Everything about $arParam modification is here.
	 * @param mixed[] $arParams List of unchecked parameters
	 * @return mixed[] Checked and valid parameters
	 */
	public function onPrepareComponentParams($arParams)
	{
		self::tryParseInt($arParams['CACHE_TIME'], 36000000, true);

		// parse function examples:

		//self::tryParseInt($arParams['ID']);
		//self::tryParseString($arParams['CODE']);
		//self::tryParseStringStrict($arParams['JS_CONTROL_GLOBAL_ID']);
		//self::tryParseWhiteList($arParams['PROVIDE_LINK_BY'], array('id', 'code'));
		//self::tryParseBoolean($arParams['SEARCH_BY_PRIMARY']);

		// some more custom parsing here

		return $arParams;
	}

	/**
	 * Function checks if required modules installed. If not, throws an exception
	 * @throws Exception
	 * @return void
	 */
	protected function checkRequiredModules()
	{
		$result = true;

		if(!Loader::includeModule('crm'))
		{
			$this->errors['FATAL'][] = Loc::getMessage("CRM_CLL2_CRM_MODULE_NOT_INSTALL");
			$result = false;
		}

		if(!Loader::includeModule('sale'))
		{
			$this->errors['FATAL'][] = Loc::getMessage("CRM_CLL2_SALE_MODULE_NOT_INSTALL");
			$result = false;
		}

		return $result;
	}

	/**
	 * Function checks if user have basic permissions to launch the component
	 * @throws Exception
	 * @return void
	 */
	protected function checkPermissions()
	{
		$result = true;

		$CrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());
		if(!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$this->errors['FATAL'][] = Loc::getMessage('CRM_CLL2_PERMISSION_DENIED');
			$result = false;
		}
		else
		{
			$this->dbResult['CAN_DELETE'] = $this->dbResult['CAN_EDIT'] = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		}

		return $result;
	}

	/**
	 * Additional parameters check, if we have parameters depend on modules included.
	 * @return void
	 */
	protected function checkParameters()
	{
		$this->arParams['PATH_TO_LOCATIONS_LIST'] = CrmCheckPath('PATH_TO_LOCATIONS_LIST', $this->arParams['PATH_TO_LOCATIONS_LIST'], '');
		$this->arParams['PATH_TO_LOCATIONS_ADD'] = CrmCheckPath('PATH_TO_LOCATIONS_ADD', $this->arParams['PATH_TO_LOCATIONS_ADD'], '?add');
		$this->arParams['PATH_TO_LOCATIONS_EDIT'] = CrmCheckPath('PATH_TO_LOCATIONS_EDIT', $this->arParams['PATH_TO_LOCATIONS_EDIT'], '?loc_id=#loc_id#&edit');

		return true;
	}

	/**
	 * Function makes some actions based on what is in $this->request
	 * @return void
	 */
	protected function performAction()
	{
		$this->dbResult['REQUEST'] = array(
			'GET' => $this->request->getQueryList(),
			'POST' => $this->request->getPostList(),
		);

		$this->componentData['LIST_HEADERS'] = Helper::getListGridColumns(); // columns figure in list
		$this->componentData['FILTER_HEADERS'] = Helper::getFilterColumns(); // columns figure in filter

		//_print_r($this->dbResult['REQUEST']);
		$requestMethod = \Bitrix\Main\Context::getCurrent()->getServer()->getRequestMethod();

		$this->getGridOpts();
		$gridId = $this->dbResult['GRID_ID'];

		$parameters = array();
		$actionSupposeToBeDone = false;

		if ($requestMethod === 'POST' && check_bitrix_sessid() && isset($this->dbResult['REQUEST']['POST']['action_button_'.$gridId]))
		{
			// group delete

			$action = $this->dbResult['REQUEST']['POST']['action_button_'.$gridId];
			if($this->dbResult['CAN_DELETE'] && $action === 'delete')
			{
				if($this->dbResult['REQUEST']['POST']['action_all_rows_'.$gridId] == 'Y') // delete all by filter
				{
					$parameters = $this->getListParameters();
				}
				else // delete all by list
					$parameters['ID'] = $this->dbResult['REQUEST']['POST']['ID'];

				$actionSupposeToBeDone = true;
			}
		}
		elseif ($requestMethod == 'GET' && check_bitrix_sessid() && isset($this->dbResult['REQUEST']['GET']['action_'.$gridId]))
		{
			// single delete

			if ($this->dbResult['CAN_DELETE'] && $this->dbResult['REQUEST']['GET']['action_'.$gridId] === 'delete')
			{
				if(isset($this->dbResult['REQUEST']['GET']['ID']))
					$parameters['ID'] = array($this->dbResult['REQUEST']['GET']['ID']);
			}

			$actionSupposeToBeDone = true;
		}

		$parameters['OPERATION'] = 'DELETE';

		$result = Helper::performGridOperations($parameters);
		if(is_array($result['errors']))
			$this->errors['NONFATAL'] = array_merge($this->errors['NONFATAL'], $result['errors']);

		if($actionSupposeToBeDone && !$this->checkHasErrors() && !isset($this->dbResult['REQUEST']['POST']['AJAX_CALL']))
		{
			LocalRedirect($GLOBALS['APPLICATION']->GetCurPage());
		}
	}

	protected function getGridOpts()
	{
		if(isset($this->componentData['GRID_OPTS']))
			return $this->componentData['GRID_OPTS'];

		$this->dbResult['GRID_ID'] = 'CRM_LOC_LIST';
		$this->dbResult['FORM_ID'] = isset($this->arParams['FORM_ID']) ? $this->arParams['FORM_ID'] : '';
		$this->dbResult['TAB_ID'] = isset($this->arParams['TAB_ID']) ? $this->arParams['TAB_ID'] : '';

		$this->componentData['GRID_OPTS'] = new CCrmGridOptions($this->dbResult['GRID_ID']);
		return $this->componentData['GRID_OPTS'];
	}

	protected function getFilter()
	{
		$this->dbResult['FILTER_PRESETS'] = array();
		$this->dbResult['FILTER'] = array();

		foreach($this->componentData['FILTER_HEADERS'] as $code => $fld)
		{
			$this->dbResult['FILTER'][] = array(
				'id' => $code, 'name' => str_replace('&nbsp;', ' ', $fld['title']), 'default' => $fld['DEFAULT']
			);
		}

		$this->dbResult['FILTER_VALUES'] = $this->getGridOpts()->GetFilter($this->dbResult['FILTER']);

		return $this->dbResult['FILTER_VALUES'];
	}

	protected function getOrder()
	{
		$arSort = array();

		$request = $this->request->getQueryList();

		$by = isset($request['by']) ? trim($request['by']) : 'ID';
		$sort = isset($request['order']) ? trim($request['order']) : 'asc';

		if(isset($request['by']) && isset($request['order']))
			$arSort = array($by => $sort);

		$gridSorting = $this->getGridOpts()->GetSorting(
			array(
				'sort' => array('ID' => 'asc'),
				'vars' => array('by' => 'by', 'order' => 'order')
			)
		);

		$this->dbResult['SORT'] = !empty($arSort) ? $arSort : $gridSorting['sort'];
		$this->dbResult['SORT_VARS'] = $gridSorting['vars'];

		return $arSort;
	}

	// get parameters from filter (filter) and grid (sort) (generalized)
	protected function getListParameters()
	{
		$filter = $this->getFilter();
		$parsedFilter = array();

		foreach($this->componentData['FILTER_HEADERS'] as $code => $field)
		{
			if(isset($filter[$code]))
				$parsedFilter[$code] = $filter[$code];
			if(isset($filter[$code.'_from']))
				$parsedFilter[$code]['FROM'] = $filter[$code.'_from'];
			if(isset($filter[$code.'_to']))
				$parsedFilter[$code]['TO'] = $filter[$code.'_to'];
		}

		// convert values to the general format Helper can understand
		$proxed = array(
			'FILTER' => $parsedFilter,
			'ORDER' => $this->getOrder()
		);

		return $proxed;
	}

	/**
	 * Here we get some data that cannot be cached for a long time
	 * @return void
	 */
	protected function obtainNonCachedData()
	{
	}

	/**
	 * Here we get some data that can be cached for a long time.
	 * @param mixed[] $cachedData Buffer for keeping data that will be put to a cache later
	 * @return void
	 */
	protected function obtainCachedData(&$cachedData)
	{
		$cachedData['TYPES'] = Helper::getTypeList();
	}

	/**
	 * Here we obtain data that is dependent to data in the cache 
	 * @return void
	 */
	protected function obtainCacheDependentData()
	{
		$listParams = Helper::getParametersForList($this->getListParameters());

		$dbRecordsList = Helper::getList($listParams, false, 20); // there is no pagenav api in d7, so use wrapper of the old api
		$dbRecordsList->bShowAll = false;

		$this->dbResult['LOCS'] = array();
		while($arLoc = $dbRecordsList->GetNext())
		{
			$arLoc['PATH_TO_LOCATIONS_EDIT'] =
				CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_LOCATIONS_EDIT'],
					array('loc_id' => $arLoc['ID'])
				);

			$arLoc['PATH_TO_LOCATIONS_DELETE'] =
				CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						$this->arParams['PATH_TO_LOCATIONS_LIST'],
						array('loc_id' => $arLoc['ID'])
					),
					array('action_'.$this->dbResult['GRID_ID'] => 'delete', 'ID' => $arLoc['ID'], 'sessid' => bitrix_sessid())
				);

			$arLoc['TYPE_ID'] = htmlspecialcharsbx($this->dbResult['TYPES'][$arLoc['TYPE_ID']]);

			$this->dbResult['LOCS'][$arLoc['ID']] = $arLoc;
		}

		$navComponentObject = 1; // formal
		$this->dbResult['ROWS_COUNT'] = $dbRecordsList->NavRecordCount;
		$this->dbResult['NAV_STRING'] = $dbRecordsList->GetPageNavStringEx($navComponentObject, Loc::getMessage("CRM_CLL2_INTS_TASKS_NAV"), "", false);
		$this->dbResult['NAV_RESULT'] = $dbRecordsList;
	}

	/**
	 * Move data read from database to a specially formatted $arResult
	 * @return void
	 */
	protected function formatResult()
	{
		$this->arResult =& $this->dbResult;
		$this->arResult['ERRORS'] =& $this->errors;

		// grid
		$this->arResult['HEADERS'] = array();
		foreach($this->componentData['LIST_HEADERS'] as $code => $fld)
		{
			$this->arResult['HEADERS'][] = array(
				'id' => $code,
				'name' => $fld['title'],
				'sort' => $code,
				'default' => $fld['DEFAULT'],
				'editable' => false
			);
		}

		unset($this->componentData);
	}

	/**
	 * Function implements all the life cycle of our component
	 * @return void
	 */
	public function executeComponent()
	{
		if($this->checkRequiredModules() && $this->checkPermissions() && $this->checkParameters())
		{
			$this->performAction();
			$this->obtainData();
		}

		$this->formatResult();

		$this->includeComponentTemplate();
	}

	protected function checkHasErrors($fatalOnly = false)
	{
		return count($this->errors['FATAL']) || (!$fatalOnly && count($this->errors['NONFATAL']));
	}

	protected function getCacheDependences()
	{
		return	array(
					static::getClassName(),
					LANGUAGE_ID
				);
	}

	protected static function getStrForVariable($val)
	{
		return ':'.($val ? strval($val) : '0').':';
	}

	/**
	 * Fetches all required data from database. Everyting that connected with data fetch lies here.
	 * @return void
	 */
	protected function obtainData()
	{
		$this->obtainNonCachedData();

		// obtain cached data
		if ($this->startCache(static::getCacheDependences()))
		{
			try
			{
				$cachedData = array();
				$this->obtainCachedData($cachedData);
			}
			catch (Exception $e)
			{
				$this->abortCache();
				throw $e;
			}

			$this->endCache($cachedData);
		}
		else
			$cachedData = $this->getCacheData();

		$this->dbResult = array_merge($this->dbResult, $cachedData);

		$this->obtainCacheDependentData();
	}

	//////////////////////////////
	// Parameter parse functions
	//////////////////////////////

	/**
	 * Function forces 'Y'/'N' value to boolean
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseBoolean(&$fld)
	{
		$fld = $fld == 'Y';
		return $fld;
	}

	/**
	 * Function processes parameter value by white list, if gets null, passes the first value in white list
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseWhiteList(&$fld, $list = array())
	{
		if(!in_array($fld, $list))
			$fld = current($list);

		return $fld;
	}

	/**
	 * Function reduces input value to integer type, and, if gets null, passes the default value
	 * @param mixed $fld Field value
	 * @param int $default Default value
	 * @param int $allowZero Allows zero-value of the parameter
	 * @return int Parsed value
	 */
	public static function tryParseInt(&$fld, $default = false, $allowZero = false)
	{
		$fld = intval($fld);
		if(!$allowZero && !$fld && $default !== false)
			$fld = $default;
			
		return $fld;
	}

	/**
	 * Function processes string value and, if gets null, passes the default value to it
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseString(&$fld, $default = false)
	{
		$fld = trim((string)$fld);
		if(!strlen($fld) && $default !== false)
			$fld = $default;

		$fld = htmlspecialcharsbx($fld);

		return $fld;
	}

	/**
	 * Function processes string value and, if gets null, passes the default value to it
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseStringStrict(&$fld, $default = false)
	{
		$fld = trim((string)$fld);
		if(!strlen($fld) && $default !== false)
			$fld = $default;

		$fld = preg_replace('#[^a-z0-9_-]#i', '', $fld);

		return $fld;
	}

	/**
	 * Function checks if it`s argument is a legal array for foreach() construction
	 * @param mixed $arr data to check
	 * @return boolean
	 */
	protected static function checkIsNonemptyArray($arr)
	{
		return is_array($arr) && !empty($arr);
	}

	////////////////////////
	// Cache functions
	////////////////////////

	/**
	 * Function checks if cacheing is enabled in component parameters
	 * @return boolean
	 */
	final protected function getCacheNeed()
	{
		return	intval($this->arParams['CACHE_TIME']) > 0 &&
				$this->arParams['CACHE_TYPE'] != 'N' &&
				Config\Option::get("main", "component_cache_on", "Y") == "Y";
	}

	/**
	 * Function perform start of cache process, if needed
	 * @param mixed[]|string $cacheId An optional addition for cache key
	 * @return boolean True, if cache content needs to be generated, false if cache is valid and can be read
	 */
	final protected function startCache($cacheId = array())
	{
		if(!$this->getCacheNeed())
			return true;

		$this->currentCache = Data\Cache::createInstance();

		return $this->currentCache->startDataCache(intval($this->arParams['CACHE_TIME']), $this->getCacheKey($cacheId));
	}

	/**
	 * Function perform start of cache process, if needed
	 * @throws Main\SystemException
	 * @param mixed[] $data Data to be stored in the cache
	 * @return void
	 */
	final protected function endCache($data = false)
	{
		if(!$this->getCacheNeed())
			return;

		if($this->currentCache == 'null')
			throw new Main\SystemException('Cache were not started');

		$this->currentCache->endDataCache($data);
		$this->currentCache = null;
	}

	/**
	 * Function discard cache generation
	 * @throws Main\SystemException
	 * @return void
	 */
	final protected function abortCache()
	{
		if(!$this->getCacheNeed())
			return;

		if($this->currentCache == 'null')
			throw new Main\SystemException('Cache were not started');

		$this->currentCache->abortDataCache();
		$this->currentCache = null;
	}

	/**
	 * Function return data stored in cache
	 * @throws Main\SystemException
	 * @return void|mixed[] Data from cache
	 */
	final protected function getCacheData()
	{
		if(!$this->getCacheNeed())
			return;

		if($this->currentCache == 'null')
			throw new Main\SystemException('Cache were not started');

		return $this->currentCache->getVars();
	}

	/**
	 * Function leaves the ability to modify cache key in future.
	 * @return string Cache key to be used in CPHPCache()
	 */
	final protected function getCacheKey($cacheId = array())
	{
		if(!is_array($cacheId))
			$cacheId = array((string) $cacheId);

		$cacheId['SITE_ID'] = SITE_ID;
		$cacheId['LANGUAGE_ID'] = LANGUAGE_ID;
		// if there are two or more caches with the same id, but with different cache_time, make them separate
		$cacheId['CACHE_TIME'] = intval($this->arResult['CACHE_TIME']);

		if(defined("SITE_TEMPLATE_ID"))
			$cacheId['SITE_TEMPLATE_ID'] = SITE_TEMPLATE_ID;

		return implode('|', $cacheId);
	}

	protected static function getClassName()
	{
		return __CLASS__;
	}
}
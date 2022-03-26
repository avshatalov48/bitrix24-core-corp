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

class CBitrixCrmConfigLocationEdit2Component extends CBitrixComponent
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
	 * @return bool
	 */
	protected function checkRequiredModules()
	{
		$result = true;

		if(!Loader::includeModule('crm'))
		{
			$this->errors['FATAL'][] = Loc::getMessage("CRM_CLE2_CRM_MODULE_NOT_INSTALL");
			$result = false;
		}

		if(!Loader::includeModule('sale'))
		{
			$this->errors['FATAL'][] = Loc::getMessage("CRM_CLE2_SALE_MODULE_NOT_INSTALL");
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
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$this->errors['FATAL'][] = Loc::getMessage('CRM_CLE2_PERMISSION_DENIED');
			$result = false;
		}

		return $result;
	}

	/**
	 * Additional parameters check, if we have parameters depend on modules included.
	 * @return void
	 */
	protected function checkParameters()
	{
		$this->dbResult['REQUEST'] = array(
			'GET' => $this->request->getQueryList()->toArray(),
			'POST' => $this->request->getPostList()->toArray(),
		);

		$this->arParams['PATH_TO_LOCATIONS_LIST'] = CrmCheckPath('PATH_TO_LOCATIONS_LIST', $this->arParams['PATH_TO_LOCATIONS_LIST'], '');
		$this->arParams['PATH_TO_LOCATIONS_EDIT'] = CrmCheckPath('PATH_TO_LOCATIONS_EDIT', $this->arParams['PATH_TO_LOCATIONS_EDIT'], '?loc_id=#loc_id#&edit');
		//$this->arParams['PATH_TO_LOCATIONS_ADD'] = CrmCheckPath('PATH_TO_LOCATIONS_ADD', $this->arParams['PATH_TO_LOCATIONS_ADD'], '?add');
		
		$this->componentData['LOCATION_ID'] = isset($this->arParams['LOC_ID']) ? intval($this->arParams['LOC_ID']) : 0;

		if($this->componentData['LOCATION_ID'] <= 0)
		{
			$locIDParName = isset($this->arParams['LOC_ID_PAR_NAME']) ? intval($this->arParams['LOC_ID_PAR_NAME']) : 0;

			if($locIDParName <= 0)
				$locIDParName = 'loc_id';

			$this->componentData['LOCATION_ID'] = isset($this->dbResult['REQUEST']['GET'][$locIDParName]) ? intval($this->dbResult['REQUEST']['GET'][$locIDParName]) : 0;
		}

		return true;
	}

	/**
	 * Function makes some actions based on what is in $this->request
	 * @return void
	 */
	protected function performAction()
	{
		$requestMethod = \Bitrix\Main\Context::getCurrent()->getServer()->getRequestMethod();

		$this->componentData['FORM_ROWS'] = Helper::getDetailPageRows();

		$this->dbResult['CALCULATED_BACK_URL'] = false;
		if($this->dbResult['REQUEST']['GET']['return_url'] <> '')
		{
			$this->dbResult['SPECIFIED_BACK_URL'] = $this->dbResult['REQUEST']['GET']['return_url'];
			$this->dbResult['CALCULATED_BACK_URL'] = $this->dbResult['REQUEST']['GET']['return_url'];
		}

		if(check_bitrix_sessid())
		{
			$actionSave = 	$requestMethod == 'POST' && isset($this->dbResult['REQUEST']['POST']['save']);
			$actionApply = 	$requestMethod == 'POST' && isset($this->dbResult['REQUEST']['POST']['apply']);
			$actionDelete = $requestMethod == 'GET' && isset($this->dbResult['REQUEST']['GET']['delete']);

			$id = isset($this->dbResult['REQUEST']['POST']['loc_id']) ? intval($this->dbResult['REQUEST']['POST']['loc_id']) : 0;

			if($id <= 0 && isset($this->dbResult['REQUEST']['POST']['ID']))
				$id = intval($this->dbResult['REQUEST']['POST']['ID']) > 0 ? intval($this->dbResult['REQUEST']['POST']['ID']) : 0;

			$res = false;
			if($actionSave || $actionApply)
			{
				if($id)
				{
					$res = Helper::update($id, $this->dbResult['REQUEST']['POST']);
				}
				else
				{
					$res = Helper::add($this->dbResult['REQUEST']['POST']);
					$id = $res['id'];
				}
			}
			elseif($actionDelete)
			{
				$locID = isset($this->arParams['LOC_ID']) ? intval($this->arParams['LOC_ID']) : 0;

				if($locID)
				{
					$res = Location\LocationTable::getById($locID)->fetch();
					$parentOfDeleted = intval($res['PARENT_ID']);

					$res = Helper::delete($locID);
				}
			}

			if($res !== false) // action was performed. or at least tried to
			{
				if(!$res['success'])
				{
					$this->errors['NONFATAL'] = array_merge($this->errors['NONFATAL'], $res['errors']);
					$this->componentData['ACTION_FAILURE'] = true;
				}
				else
				{
					$url = $this->dbResult['CALCULATED_BACK_URL'];

					if($actionApply)
					{
						if((int)$this->componentData['LOCATION_ID'] > 0)
						{
							$url = false; //on apply we do not do any redirects
						}
						else
						{
							$res = Location\LocationTable::getById($id)->fetch();

							if($res)
							{
								$url = CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_LOCATIONS_EDIT'],['loc_id' => intval($res['ID'])]);
							}
						}
					}
					else
					{
						if($actionSave)
						{
							// get parent (only for locations)
							$res = Location\LocationTable::getById($id)->fetch();

							if(!$url)
								$url = CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_LOCATIONS_LIST']).'?PARENT_ID='.intval($res['PARENT_ID']);
						}
						elseif($actionDelete)
						{
							if(!$url)
								$url = CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_LOCATIONS_LIST']).'?PARENT_ID='.intval($parentOfDeleted);
						}
					}

					if($url)
						LocalRedirect($url);
				}
			}
		}
	}

	/**
	 * Here we get some data that cannot be cached for a long time
	 * @return void
	 */
	protected function obtainNonCachedData()
	{
		$this->dbResult['FORM_DATA'] = array();

		if($this->componentData['ACTION_FAILURE']) // we tried to add\update, but failed due to some reason, so get data from request
		{
			$this->dbResult['FORM_DATA'] = $this->dbResult['REQUEST']['POST'];
		}
		else
		{
			if($this->componentData['LOCATION_ID'] > 0)
			{
				$arLoc = Helper::getFormData($this->componentData['LOCATION_ID']);
				if(!($arLoc))
				{
					$this->errors['FATAL'][] = Loc::getMessage('CRM_CLE2_LOC_NOT_FOUND');
					@define('ERROR_404', 'Y');
					if($this->arParams['SET_STATUS_404'] === 'Y')
					{
						CHTTP::SetStatus("404 Not Found");
					}
					return false;
				}

				$this->dbResult['FORM_DATA'] = $arLoc;
			}
		}

		// special case for PARENT_ID
		if(!isset($this->dbResult['FORM_DATA']['PARENT_ID']) && intval($this->dbResult['REQUEST']['GET']['PARENT_ID']))
			$this->dbResult['FORM_DATA']['PARENT_ID'] = intval($this->dbResult['REQUEST']['GET']['PARENT_ID']);

		$this->dbResult['LOCATION_ID'] = $this->componentData['LOCATION_ID'];

		if(!$this->dbResult['CALCULATED_BACK_URL'])
		{
			// by default back url is root item list
			$this->dbResult['CALCULATED_BACK_URL'] = CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_LOCATIONS_LIST']);

			// if element exists, back url will be to it`s parent list
			if($this->componentData['LOCATION_ID'])
			{
				$this->dbResult['CALCULATED_BACK_URL'].'?PARENT_ID='.intval($this->dbResult['FORM_DATA']['PARENT_ID']);
			}
		}

		return true;
	}

	/**
	 * Here we get some data that can be cached for a long time.
	 * @param mixed[] $cachedData Buffer for keeping data that will be put to a cache later
	 * @return void
	 */
	protected function obtainCachedData(&$cachedData)
	{
		$cachedData['TYPES'] = Helper::getTypeList();
		$cachedData['EXTERNAL_SERVICES'] = Helper::getExternalServicesList();
		$cachedData['YANDEX_MARKET_ES_ID'] = Helper::getYandexMarketExternalServiceId();
		$cachedData['PORTAL_ZONE'] = Bitrix\Sale\Delivery\Helper::getPortalZone();
	}

	/**
	 * Here we obtain data that is dependent to data in the cache 
	 * @return void
	 */
	protected function obtainCacheDependentData()
	{
	}

	/**
	 * Move data read from database to a specially formatted $arResult
	 * @return void
	 */
	protected function formatResult()
	{
		$this->arResult =& $this->dbResult;
		$this->arResult['ERRORS'] =& $this->errors;

		$this->arResult['FORM_ID'] = 'CRM_LOC_EDIT_FORM';
		$this->arResult['GRID_ID'] = 'CRM_LOC_EDIT_GRID';

		foreach($this->componentData['FORM_ROWS'] as $code => $row)
		{
			$required = $row['required'];

			if(!$required && mb_substr($code, 0, 5) == 'NAME_')
				$required = true;

			$this->arResult['FIELDS'][] = array(
				'id' => $code,
				'name' => $row['title'],
				'value' => Helper::makeSafeDisplay($this->arResult['FORM_DATA'][$code], $code),
				'required' => $required,
				'type' => 'text' // can and will be redefined at the template
			);
		}

		if($this->checkIsNonemptyArray($this->arResult['EXTERNAL_SERVICES']))
		{
			$this->arResult['FIELDS'][] = array(
				'id' => 'EXTERNAL',
				'required' => false
			);
		}

		$this->arResult['EXTERNAL_TABLE_COLUMNS'] = Helper::getExternalMap();

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

	/**
	 * Do smth when called over ajax
	 * @return mixed[]
	 */
	protected static function doAjaxStuff()
	{
		return array(
			'errors' => array(),
			'data' => array()
		);
	}

	protected function checkHasErrors($fatalOnly = false)
	{
		return count($this->errors['FATAL']) || (!$fatalOnly && count($this->errors['NONFATAL']));
	}

	protected function getCacheDependences()
	{
		return	array(
					static::getClassName(),
					//self::getStrForVariable($this->arParams['FILTER_BY_SITE']),
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
		if($this->obtainNonCachedData())
		{
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
		if(!mb_strlen($fld) && $default !== false)
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
		if(!mb_strlen($fld) && $default !== false)
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
<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class CIntranetStructureListComponent extends CBitrixComponent
{
	const ADMIN_GROUP_ID = 1;
	const MAX_EXEC_RESIZE_TIME = 3;
	const LAST_ACTIVITY = 120;

	/**
	 * @var null|array
	 */
	protected $externalValues = null;
	protected $arWhiteList = array();

	protected $arFilter = array();
	/**
	 * @var null|CPHPCache
	 */
	protected $obCache = null;
	public $bExcel = null;

	public function __construct($component = null)
	{
		$this->bExcel = isset($_GET['excel']) && $_GET['excel'] === 'yes';

		parent::__construct($component);
	}

	protected static function getOnlineInterval()
	{
		static $interval;

		if (is_null($interval))
		{
			$interval = \Bitrix\Main\UserTable::getSecondsForLimitOnline() ?: static::LAST_ACTIVITY;
		}

		return $interval;
	}

	protected function initWhiteList()
	{
		$arFieldWhiteList = array(
			'WORK_POSITION' => 'POST',
			'WORK_PHONE' => 'PHONE',
			'WORK_COMPANY' => 'COMPANY',
			'EMAIL' => 'EMAIL',
			'NAME' => 'FIO',
			'KEYWORDS' => 'KEYWORDS',
			'LAST_NAME' => 'LAST_NAME',
			'LAST_NAME_RANGE' => 'LAST_NAME_RANGE',
		);

		$arPropertyWhiteList = array(
			'UF_PHONE_INNER' => 'UF_PHONE_INNER'
		);

		$tmpVal = COption::GetOptionString("socialnetwork", "user_property_searchable", false, SITE_ID);
		if ($tmpVal)
		{
			$arPropertySearchable = unserialize($tmpVal,  ["allowed_classes" => false]);
			if (!empty($arPropertySearchable))
			{
				foreach ($arPropertySearchable as $ufCode)
				{
					if (
						$ufCode != 'UF_DEPARTMENT'
						&& !array_key_exists($ufCode, $arPropertyWhiteList)
					)
					{
						$arPropertyWhiteList[$ufCode] = $ufCode;
					}
				}
			}
		}

		$this->arWhiteList = array_merge($arFieldWhiteList, $arPropertyWhiteList);
	}

	protected function initExternalValues($filterName)
	{
		if (!empty($_REQUEST['del_filter_'.$filterName]))
		{
			return;
		}

		$this->initWhiteList();

		$this->externalValues = array(
			'UF_DEPARTMENT' => $_REQUEST[$filterName . '_UF_DEPARTMENT'],
			'IS_ONLINE' => isset($_REQUEST[$filterName . '_IS_ONLINE']) ? $_REQUEST[$filterName . '_IS_ONLINE'] : null,
		);

		foreach($this->arWhiteList as $key => $value)
		{
			if (!array_key_exists($value, $this->externalValues))
			{
				if ($value == "FIO")
				{
					$this->externalValues[$value] = (
						isset($_REQUEST[$filterName . '_'.$value])
						&& GetFilterQuery("TEST", $_REQUEST[$filterName . '_'.$value])
							? $_REQUEST[$filterName . '_'.$value]
							: null
					);
				}
				else
				{
					$this->externalValues[$value] = (
						isset($_REQUEST[$filterName . '_'.$value])
							? $_REQUEST[$filterName . '_'.$value]
							: (
								isset($_REQUEST['flt_'.mb_strtolower($value)]) // from user_profile
									? $_REQUEST['flt_'.mb_strtolower($value)]
									: null)
					);
				}
			}
		}

		if($this->externalValues['UF_DEPARTMENT'] !== null)
		{
			if(!is_array($this->externalValues['UF_DEPARTMENT']))
			{
				$this->externalValues['UF_DEPARTMENT'] = array($this->externalValues['UF_DEPARTMENT']);
			}
			$this->externalValues['UF_DEPARTMENT'] = array_filter(array_map('intval', $this->externalValues['UF_DEPARTMENT']));
		}
		else
		{
			$this->externalValues['UF_DEPARTMENT'] = array();
		}
	}

	public function onPrepareComponentParams($arParams)
	{
		$arParams['FILTER_NAME'] = $this->initFilterName($arParams['FILTER_NAME']);
		$this->initExternalValues($arParams['FILTER_NAME']);

		$arParams['USERS_PER_PAGE']      = intval($arParams['USERS_PER_PAGE']);
		$arParams['NAV_TITLE']           = !empty($arParams['NAV_TITLE']) ? $arParams['NAV_TITLE'] : GetMessage('INTR_ISL_PARAM_NAV_TITLE_DEFAULT');
		$arParams['DATE_FORMAT']         = !empty($arParams['DATE_FORMAT']) ? $arParams['DATE_FORMAT'] : CComponentUtil::GetDateFormatDefault(false);
		$arParams['DATE_FORMAT_NO_YEAR'] = !empty($arParams['DATE_FORMAT_NO_YEAR']) ? $arParams['DATE_FORMAT_NO_YEAR'] : CComponentUtil::GetDateFormatDefault(true);

		InitBVar($arParams['FILTER_1C_USERS']);
		InitBVar($arParams['FILTER_SECTION_CURONLY']);
		InitBVar($arParams['SHOW_NAV_TOP']);
		InitBVar($arParams['SHOW_NAV_BOTTOM']);
		InitBVar($arParams['SHOW_UNFILTERED_LIST']);
		InitBVar($arParams['SHOW_DEP_HEAD_ADDITIONAL']);

		!isset($arParams["CACHE_TIME"]) && $arParams["CACHE_TIME"] = 3600;

		if ($arParams['CACHE_TYPE'] == 'A')
		{
			$arParams['CACHE_TYPE'] = COption::GetOptionString("main", "component_cache_on", "Y");
		}
		$arParams['DETAIL_URL'] = COption::GetOptionString('intranet', 'search_user_url', '/user/#ID#/');

		if (!array_key_exists("PM_URL", $arParams))
		{
			$arParams["PM_URL"] = "/company/personal/messages/chat/#USER_ID#/";
		}
		if (!array_key_exists("PATH_TO_USER_EDIT", $arParams))
		{
			$arParams["PATH_TO_USER_EDIT"] = '/company/personal/user/#user_id#/edit/';
		}
		if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
		{
			$arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
		}
		if (!$this->getUser()->CanDoOperation("edit_all_users") && isset($arParams["SHOW_USER"]) && $arParams["SHOW_USER"] != "fired")
		{
			$arParams["SHOW_USER"] = "active";
		}

		return parent::onPrepareComponentParams($arParams);
	}

	protected function fillFilterByExtranet()
	{
		$this->arFilter["ACTIVE"] = "Y";
		if ($this->arParams["EXTRANET_TYPE"] == "employees")
		{
			$this->arFilter["!UF_DEPARTMENT"] = false;
		}
		else
		{
			$this->arFilter["UF_DEPARTMENT"] = false;
			$this->arFilter["!EXTERNAL_AUTH_ID"] = \Bitrix\Main\UserTable::getExternalUserTypes();
		}
	}

	protected function fillFilterByIntranet()
	{
		if (!isset($this->arParams["SHOW_USER"]))
		{
			$this->arFilter = array('ACTIVE' => 'Y');
		}
		else
		{
			switch ($this->arParams["SHOW_USER"])
			{
				case "fired":
					$this->arFilter = array('ACTIVE' => 'N');
					break;
				case "inactive":
					$this->arFilter = array('ACTIVE'     => 'Y',
											'!CONFIRM_CODE' => false);
					break;
				case "extranet":
					if (CModule::IncludeModule('extranet'))
					{
						$this->arFilter = array('ACTIVE'      => 'Y',
												'GROUPS_ID'   => CExtranet::GetExtranetUserGroupID(),
												'CONFIRM_CODE' => false);
					}
					break;
				case "active":
						$this->arFilter = array('ACTIVE'      => 'Y',
												'CONFIRM_CODE' => false);
					break;
			}
			$this->arResult["SHOW_USER"] = $this->arParams["SHOW_USER"];
		}
	}

	protected function fillFilter()
	{
		if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
		{
			$this->fillFilterByExtranet();
		}
		else
		{
			$this->fillFilterByIntranet();
		}

		if ($this->arParams['FILTER_1C_USERS'] == 'Y')
		{
			$this->arFilter['UF_1C'] = 1;
		}

		if ($this->externalValues['UF_DEPARTMENT'])
		{
			$this->arFilter['UF_DEPARTMENT'] = $this->arParams['FILTER_SECTION_CURONLY'] == 'N'?
				CIntranetUtils::GetIBlockSectionChildren($this->externalValues['UF_DEPARTMENT']) :
				$this->externalValues['UF_DEPARTMENT'];
		}
		else
		{
			if (\CModule::includeModule('extranet'))
			{
				if (!\CExtranet::isExtranetSite())
				{
					if ($this->arParams['SHOW_USER'] == 'extranet')
						$this->arFilter['UF_DEPARTMENT'] = false;
					else if (!in_array($this->arParams['SHOW_USER'], array('all', 'inactive', 'fired')))
						$this->arFilter['!UF_DEPARTMENT'] = false;
				}
			}
			else
			{
				$this->arFilter['!UF_DEPARTMENT'] = false;
			}
		}

		$this->arFilter["!EXTERNAL_AUTH_ID"] = \Bitrix\Main\UserTable::getExternalUserTypes();

		//items equal to FALSE (see converting to boolean in PHP) will be removed (see array_filter()). After merge with $this->arFilter

		$arTmp = array();
		foreach($this->arWhiteList as $key => $value)
		{
			$arTmp[$key] = $this->externalValues[$value];
		}

		$this->arFilter = array_merge(
			$this->arFilter,
			array_filter($arTmp)
		);

		if ($this->externalValues['IS_ONLINE'] == 'Y')
		{
			$this->arFilter['LAST_ACTIVITY'] = static::getOnlineInterval();
		}
		if ($this->externalValues['LAST_NAME'])
		{
			$this->arFilter['LAST_NAME_EXACT_MATCH'] = 'Y';
		}

		$isEnoughFiltered = (boolean) array_intersect(array_keys($this->arFilter), array(
			'WORK_POSITION',
			'WORK_PHONE',
			'UF_PHONE_INNER',
			'WORK_COMPANY',
			'EMAIL',
			'NAME',
			'KEYWORDS',
			'LAST_NAME',
			'LAST_NAME_RANGE',
			'LAST_ACTIVITY',
			'UF_DEPARTMENT',
		));

		if(!empty($this->arFilter['LAST_NAME_RANGE']))
		{
			//input format: a-z (letter - letter)
			$letterRange      = explode('-', $this->arFilter['LAST_NAME_RANGE'], 2);
			$startLetterRange = array_shift($letterRange);
			$endLetterRange   = array_shift($letterRange);

			$this->arFilter[] = array(
				'LOGIC' => 'OR',
				array(
					'><F_LAST_NAME' => array(mb_strtoupper($startLetterRange), mb_strtoupper($endLetterRange)),
				),
				array(
					'><F_LAST_NAME' => array(mb_strtolower($startLetterRange), mb_strtolower($endLetterRange)),
				),
			);
			unset($this->arFilter['LAST_NAME_RANGE']);
		}

		return $isEnoughFiltered;
	}

	/**
	 * @param $filterName
	 * @return string
	 */
	protected function initFilterName($filterName)
	{
		if ($filterName == '' || !preg_match("/^[A-Za-z_][A-Za-z0-9_]*$/", $filterName))
		{
			return 'find_';
		}

		return $filterName;
	}

	/**
	 * @param bool $reload
	 * @return array
	 */
	protected function getCacheIdWithDepartment($reload = false)
	{
		// we'll cache all variants of selection by UF_DEPARTMENT (and GROUPS_ID with extranet)
		static $cntStartCacheId = '';
		static $cacheCount = null;

		if($cntStartCacheId && !$reload)
		{
			return array($cntStartCacheId, $cacheCount);
		}

		$cacheCount = count($this->arFilter);
		foreach ($this->arFilter as $key => $value)
		{
			$cntStartCacheId .= '|'.$key.':'.preg_replace("/[\s]*/", "", var_export($value, true));
		}

		return array($cntStartCacheId, $cacheCount);
	}

	/**
	 * Init CPHPCache and return status of initialization
	 * @param $cntStartCacheId
	 * @return bool
	 */
	protected function initCache($cntStartCacheId)
	{
		$this->cacheDir  = '/'.SITE_ID.$this->getRelativePath()
			.'/'.mb_substr(md5($cntStartCacheId), 0, 5)
					. '/' . trim(CDBResult::NavStringForCache($this->arParams['USERS_PER_PAGE'], false), '|');

		$this->cacheId = $this->getName() . '|' . SITE_ID;

		if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
		{
			$this->cacheId .= '|' . $this->getUser()->GetID() . '|' . $this->arParams['EXTRANET_TYPE'];
		}
		$this->cacheId .= CDBResult::NavStringForCache($this->arParams['USERS_PER_PAGE'], false)
				. $cntStartCacheId . "|" . $this->arParams['USERS_PER_PAGE'];

		if (
			isset($this->arParams['LIST_URL'])
			&& $this->arParams['LIST_URL'] <> ''
		)
		{
			$this->cacheId .= "|" . $this->arParams['LIST_URL'];
		}

		$this->obCache = new CPHPCache;

		return $this->obCache->initCache($this->arParams['CACHE_TIME'], $this->cacheId, $this->cacheDir);
	}

	public function executeComponent()
	{
		if (!CModule::IncludeModule('intranet'))
		{
			ShowError(GetMessage('INTR_ISL_INTRANET_MODULE_NOT_INSTALLED'));
			return;
		}
		if (!CModule::IncludeModule('socialnetwork'))
			return;

		$showDepHeadAdditional = $this->arParams['SHOW_DEP_HEAD_ADDITIONAL'] == 'Y';
		$bNav                  = $this->arParams['SHOW_NAV_TOP'] == 'Y' || $this->arParams['SHOW_NAV_BOTTOM'] == 'Y';

		$isEnoughFiltered = $this->fillFilter();

		list($cntStartCacheId, $cntStart) = $this->getCacheIdWithDepartment();

		if ($this->arParams['SHOW_UNFILTERED_LIST'] == 'N' && !$this->bExcel && !$isEnoughFiltered)
		{
			$this->arResult['EMPTY_UNFILTERED_LIST'] = 'Y';
			$this->includeComponentTemplate();

			return;
		}

		$this->arParams['bCache'] =
			$cntStart == count($this->arFilter) // we cache only unfiltered list
			&& !$this->bExcel
			&& $this->arParams['CACHE_TYPE'] == 'Y' && $this->arParams['CACHE_TIME'] > 0;

		$this->arResult['FILTER_VALUES'] = $this->arFilter;

		if (!$this->bExcel && $bNav)
		{
			CPageOption::SetOptionString("main", "nav_page_in_session", "N");
		}

		$bFromCache = false;
		if ($this->arParams['bCache'])
		{
			if($bFromCache = $this->initCache($cntStartCacheId))
			{
				$vars                              = $this->obCache->getVars();
				$this->arResult['USERS']           = $vars['USERS'];
				$this->arResult['DEPARTMENTS']     = $vars['DEPARTMENTS'];
				$this->arResult['DEPARTMENT_HEAD'] = $vars['DEPARTMENT_HEAD'];
				$this->arResult['USERS_NAV']       = $vars['USERS_NAV'];
				$strUserIDs                        = $vars['STR_USER_ID'];
			}
			else
			{
				$this->obCache->startDataCache();
				$this->getCacheManager()->startTagCache($this->cacheDir);
				$this->getCacheManager()->registerTag('intranet_users');
			}
		}

		if(!$bFromCache)
		{
			// get users list
			$obUser = new CUser();
			$arSelect = array('ID', 'ACTIVE', 'CONFIRM_CODE', 'DEP_HEAD', 'GROUP_ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL',
				'LID', 'DATE_REGISTER',  'PERSONAL_PROFESSION', 'PERSONAL_WWW', 'PERSONAL_ICQ', 'PERSONAL_GENDER', 'PERSONAL_BIRTHDATE',
				'PERSONAL_PHOTO', 'PERSONAL_PHONE', 'PERSONAL_FAX', 'PERSONAL_MOBILE', 'PERSONAL_PAGER', 'PERSONAL_STREET', 'PERSONAL_MAILBOX',
				'PERSONAL_CITY', 'PERSONAL_STATE', 'PERSONAL_ZIP', 'PERSONAL_COUNTRY', 'PERSONAL_NOTES', 'ADMIN_NOTES', 'WORK_COMPANY', 'WORK_DEPARTMENT',
				'WORK_POSITION', 'WORK_WWW', 'WORK_PHONE', 'WORK_FAX', 'WORK_PAGER', 'WORK_STREET', 'WORK_MAILBOX', 'WORK_CITY', 'WORK_STATE',
				'WORK_ZIP', 'WORK_COUNTRY', 'WORK_PROFILE', 'WORK_LOGO', 'WORK_NOTES', 'PERSONAL_BIRTHDAY', 'LAST_ACTIVITY_DATE', 'LAST_LOGIN', 'IS_ONLINE',
				'EXTERNAL_AUTH_ID');

			$this->arResult['USERS']           = array();
			$this->arResult['DEPARTMENTS']     = array();
			$this->arResult['DEPARTMENT_HEAD'] = 0;
			// disable/enable appearing of department head on page
			if ($showDepHeadAdditional && !empty($this->arFilter['UF_DEPARTMENT']) && is_array($this->arFilter['UF_DEPARTMENT']))
			{
				if ($this->arParams['bCache'])
				{
					$this->getCacheManager()->registerTag('intranet_department_' . $this->arFilter['UF_DEPARTMENT'][0]);
				}

				$managerId = CIntranetUtils::GetDepartmentManagerID($this->arFilter['UF_DEPARTMENT'][0]);
				$appendManager = \CUser::getById($managerId)->fetch();

				if ($appendManager && $appendManager['ACTIVE'] == 'Y')
				{
					$this->arResult['DEPARTMENT_HEAD']     = $appendManager['ID'];
					$this->arFilter['!ID']                 = $appendManager['ID'];
					$this->arResult['USERS'][$appendManager['ID']] = $appendManager;
				}
			}

			$bDisable = false;
			if (CModule::IncludeModule('extranet'))
			{
				if (CExtranet::IsExtranetSite() && !CExtranet::IsExtranetAdmin())
				{
					$arIDs = array_merge(CExtranet::GetMyGroupsUsers(SITE_ID), CExtranet::GetPublicUsers());

					if ($this->arParams['bCache'])
					{
						$this->getCacheManager()->registerTag('extranet_public');
						$this->getCacheManager()->registerTag('extranet_user_'.$this->getUser()->getID());
					}

					if (false !== ($key = array_search($this->getUser()->getID(), $arIDs)))
						unset($arIDs[$key]);

					if (count($arIDs) > 0)
					{
						$this->arFilter['ID'] = implode('|', array_unique($arIDs));
					}
					else
					{
						$bDisable = true;
					}
				}
			}

			if ($bDisable)
			{
				$dbUsers = new CDBResult();
				$dbUsers->initFromArray(array());
			}
			else
			{
				$arListParams = array('SELECT' => array('UF_*'), 'ONLINE_INTERVAL' => static::getOnlineInterval());
				if (!$this->bExcel && $this->arParams['USERS_PER_PAGE'] > 0)
				{
					$arListParams['NAV_PARAMS'] = array('nPageSize' => $this->arParams['USERS_PER_PAGE'], 'bShowAll' => false);
				}

				$dbUsers = $obUser->getList(
					'FULL_NAME',
					'ASC',
					$this->arFilter,
					$arListParams
				);
			}

			$strUserIDs = '';
			while ($arUser = $dbUsers->Fetch())
			{
				$this->arResult['USERS'][$arUser['ID']] = $arUser;
				$strUserIDs .= ($strUserIDs === '' ? '' : '|').$arUser['ID'];
			}

			$structure = CIntranetUtils::getStructure();
			$this->arResult['DEPARTMENTS'] = $structure['DATA']
			;
			$this->setDepWhereUserIsHead();

			$arAdmins = array();
			$rsUsers  = CUser::GetList('', '', array("GROUPS_ID" => array(static::ADMIN_GROUP_ID)), array("SELECT"=>array("ID")));
			while ($ar = $rsUsers->Fetch())
			{
				$arAdmins[$ar["ID"]] = $ar["ID"];
			}

			$integratorsId = array();
			if (IsModuleInstalled("bitrix24"))
			{
				$integratorsId = \Bitrix\Bitrix24\Integrator::getIntegratorsId();
			}

			$displayPhoto = $this->displayPersonalPhoto();
			foreach ($this->arResult['USERS'] as $key => &$arUser)
			{
				// cache optimization
				foreach ($arUser as $k => $value)
				{
					if (
						is_array($value) && count($value) <= 0
						|| !is_array($value) && $value == ''
						|| !in_array($k, $arSelect) && mb_substr($k, 0, 3) != 'UF_'
					)
					{
						unset($arUser[$k]);
					}
					elseif ($k == "PERSONAL_COUNTRY" || $k == "WORK_COUNTRY")
					{
						$arUser[$k] = GetCountryByID($value);
					}
				}

				$arUser['IS_ONLINE'] = $arUser['IS_ONLINE'] == 'Y'? true : false;
				if ($this->arParams['bCache'])
				{
					$this->getCacheManager()->registerTag('intranet_user_'.$arUser['ID']);
				}

				$arUser['DETAIL_URL']      = str_replace(array('#ID#', '#USER_ID#'), $arUser['ID'], $this->arParams['DETAIL_URL']);
				$arUser['ADMIN']           = isset($arAdmins[$arUser['ID']]); //is user admin/extranet
				$arUser['ACTIVITY_STATUS'] = 'active';
				$arUser['EXTRANET']        = false;
				if (isModuleInstalled('extranet') && empty($arUser['UF_DEPARTMENT'][0]))
				{
					$arUser["ACTIVITY_STATUS"] = 'extranet';
					$arUser['EXTRANET']        = true;
				}

				if (!empty($integratorsId) && in_array($arUser['ID'], $integratorsId))
				{
					$arUser["ACTIVITY_STATUS"] = 'integrator';
				}
				if ($arUser["ACTIVE"] == "N")
				{
					$arUser["ACTIVITY_STATUS"] = 'fired';
				}
				if (!empty($arUser["CONFIRM_CODE"]))
				{
					$arUser["ACTIVITY_STATUS"] = 'inactive';
				}
				$arUser['SHOW_USER']   = $this->arParams["SHOW_USER"] ?? null;
				$arUser['IS_FEATURED'] = CIntranetUtils::IsUserHonoured($arUser['ID']);

				$arDep = array();
				foreach ((array)$arUser['UF_DEPARTMENT'] as $sect)
				{
					$arDep[$sect] = $this->arResult['DEPARTMENTS'][$sect]['NAME'];
				}
				$arUser['UF_DEPARTMENT'] = $arDep;
				if(!$this->bExcel && $displayPhoto)
				{
					$this->resizePersonalPhoto($arUser);
				}

				if (count($arUser['UF_DEPARTMENT']) <= 0 && !$arUser['EXTRANET'])
					unset($this->arResult['USERS'][$key]);

				if (CModule::includeModule('sale'))
				{
					if ($arUser['ID'] == COption::getOptionInt('sale', 'anonymous_user_id', null))
						unset($this->arResult['USERS'][$key]);
				}
			}
			unset($arUser, $key);

			$navComponentObject = null;
			$this->arResult["USERS_NAV"] = ($bNav ? $dbUsers->GetPageNavStringEx($navComponentObject, $this->arParams["NAV_TITLE"]) : '');

			if ($this->arParams['bCache'])
			{
				$this->getCacheManager()->endTagCache();
				$this->obCache->endDataCache(array( 'USERS'           => $this->arResult['USERS'],
													'STR_USER_ID'     => $strUserIDs,
													'DEPARTMENTS'     => $this->arResult['DEPARTMENTS'],
													'DEPARTMENT_HEAD' => $this->arResult['DEPARTMENT_HEAD'],
													'USERS_NAV'       => $this->arResult['USERS_NAV']));
			}
		}

		$this->initSonetUserPerms(array_keys($this->arResult['USERS']));
		$this->workWithNonCacheAttr($bFromCache, $strUserIDs);

		if (!$this->bExcel)
		{
			$this->arResult['bAdmin'] = $this->getUser()->canDoOperation('edit_all_users') || $this->getUser()->canDoOperation('edit_subordinate_users');
			$this->IncludeComponentTemplate();
		}
		else
		{
			$this->getApplication()->restartBuffer();
			// hack. any '.default' customized template should contain 'excel' page
			$this->setTemplateName('.default');

			Header("Content-Type: application/force-download");
			Header("Content-Type: application/octet-stream");
			Header("Content-Type: application/download");
			Header("Content-Disposition: attachment;filename=users.xls");
			Header("Content-Transfer-Encoding: binary");

			$this->IncludeComponentTemplate('excel');

			die;
		}

		return;
	}

	protected function setDepWhereUserIsHead()
	{
		foreach ($this->arResult['DEPARTMENTS'] as &$dep)
		{
			if(!isset($dep['USERS']))
			{
				//structure for compatibility
				$dep['USERS'] = array();
			}

			if(!isset($this->arResult['USERS'][$dep['UF_HEAD']]))
				continue;

			$this->arResult['USERS'][$dep['UF_HEAD']]["DEP_HEAD"][$dep['ID']] = $dep['NAME'];

		}
		unset($dep);
	}

	/**
	 * Show column PHOTO
	 * @return bool
	 */
	protected function displayPersonalPhoto()
	{
		return in_array('PERSONAL_PHOTO', $this->arParams['USER_PROPERTY']);
	}

	/**
	 * Get default picture for gender (socialnetwork)
	 * @param $gender
	 * @return string
	 */
	protected function getDefaultPictureSonet($gender)
	{
		static $defaultPicture = array();
		if(empty($defaultPicture))
		{
			$defaultPicture = array(
				'M'       => COption::GetOptionInt('socialnetwork', 'default_user_picture_male', false, SITE_ID),
				'F'       => COption::GetOptionInt('socialnetwork', 'default_user_picture_female', false, SITE_ID),
				'unknown' => COption::GetOptionInt('socialnetwork', 'default_user_picture_unknown', false, SITE_ID),
			);
		}

		if(!isset($defaultPicture[$gender]))
		{
			$gender = 'unknown';
		}
		return $defaultPicture[$gender];
	}

	/**
	 * Resize users photo. Time is limited.
	 * @param array $arUser
	 * @return bool If modify photo
	 */
	protected function resizePersonalPhoto(array &$arUser)
	{
		static $startTime = null;

		if($startTime === null)
		{
			$startTime = microtime(true);
		}

		//photo for current user not resized. Do it!
		if(empty($arUser['PERSONAL_PHOTO_RESIZED']))
		{
			if (empty($arUser['PERSONAL_PHOTO']))
			{
				$arUser['PERSONAL_PHOTO'] = $this->getDefaultPictureSonet($arUser['PERSONAL_GENDER'] ?? null);
			}

			if(empty($arUser['PERSONAL_PHOTO_SOURCE']))
			{
				$arUser['PERSONAL_PHOTO_SOURCE'] = $arUser['PERSONAL_PHOTO'];
			}

			//if not run resize photo or we resize photo long time and we want stop it
			if (round(microtime(true)-$startTime, 3) > static::MAX_EXEC_RESIZE_TIME)
			{
				$arUser['PERSONAL_PHOTO']         = CFile::ShowImage($arUser['PERSONAL_PHOTO_SOURCE'], 9999, 100);
				$arUser['PERSONAL_PHOTO_RESIZED'] = false;

				return false;
			}

			$arImage = CIntranetUtils::InitImage($arUser['PERSONAL_PHOTO_SOURCE'], 100);
			$arUser['PERSONAL_PHOTO'] = $arImage['IMG'];
			$arUser['PERSONAL_PHOTO_RESIZED'] = true;

			return true;
		}

		return false;
	}

	/**
	 * Set mutable attributes
	 * @param bool   $bFromCache
	 * @param string $strUserIds
	 */
	protected function workWithNonCacheAttr($bFromCache = false, $strUserIds = '')
	{
		//if list of users in cache - get last activity
		if ($bFromCache && $strUserIds)
		{
			foreach (array_keys($this->arResult['USERS']) as $id)
			{
				$this->arResult['USERS'][$id]['IS_ONLINE'] = false;
			}

			$dbRes = \CUser::getList('id', 'asc', array('ID' => $strUserIds, 'LAST_ACTIVITY' => static::getOnlineInterval()), array('FIELDS' => array('ID')));
			while ($arRes = $dbRes->fetch())
			{
				if ($this->arResult['USERS'][$arRes['ID']])
				{
					$this->arResult['USERS'][$arRes['ID']]['IS_ONLINE'] = true;
				}
			}
			unset($dbRes, $arRes);
		}

		$buildResizedPhoto = false;
		$displayPhoto      = $this->displayPersonalPhoto();
		foreach ($this->arResult['USERS'] as &$arUser)
		{
			if($this->bExcel && $displayPhoto)
			{
				//if export in excel, then method $this->resizePersonalPhoto() not run. And not modify PERSONAL_PHOTO
				if(!$arUser['PERSONAL_PHOTO'])
				{
					$arUser['PERSONAL_PHOTO'] = $this->getDefaultPictureSonet($arUser['PERSONAL_GENDER']);
				}
				$arUser['PERSONAL_PHOTO_SOURCE'] = $arUser['PERSONAL_PHOTO'];
				$arUser['PERSONAL_PHOTO']        = CFile::GetPath($arUser['PERSONAL_PHOTO']);
			}
			elseif($bFromCache && $displayPhoto)
			{
				$buildResizedPhoto = $this->resizePersonalPhoto($arUser) || $buildResizedPhoto;
			}
			$arUser['IS_BIRTHDAY'] = CIntranetUtils::IsToday($arUser['PERSONAL_BIRTHDAY'] ?? null);
			$arUser['IS_ABSENT']   = CIntranetUtils::IsUserAbsent($arUser['ID']);
		}

		//rewrite cache if we build new resized photo
		if($buildResizedPhoto)
		{
			$this->obCache->clean($this->cacheId, $this->cacheDir);

			$this->obCache->startDataCache();
			$this->obCache->endDataCache(array(
											'USERS'          => $this->arResult['USERS'],
											'STR_USER_ID'     => $strUserIds,
											'DEPARTMENTS'     => $this->arResult['DEPARTMENTS'],
											'DEPARTMENT_HEAD' => $this->arResult['DEPARTMENT_HEAD'],
											'USERS_NAV'       => $this->arResult['USERS_NAV']));
		}
	}

	protected function initSonetUserPerms($arUserID)
	{
		if (!is_array($arUserID))
			$arUserID = array(intval($arUserID));

		CSocNetUserPerms::GetOperationPerms($arUserID, "viewprofile");
	}

	/**
	 * @return CUser
	 */
	public function getUser()
	{
		global $USER;

		return $USER;
	}

	/**
	 * @return CMain
	 */
	public function getApplication()
	{
		global $APPLICATION;

		return $APPLICATION;
	}

	/**
	 * @return CCacheManager
	 */
	public function getCacheManager()
	{
		global $CACHE_MANAGER;

		return $CACHE_MANAGER;
	}
}

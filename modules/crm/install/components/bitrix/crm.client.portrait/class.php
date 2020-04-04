<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Statistics\Entity\CompanyActivityStatisticsTable;
use Bitrix\Crm\Statistics\Entity\ContactActivityStatisticsTable;
use Bitrix\Crm\Statistics\Entity\DealSumStatisticsTable;
use Bitrix\Main;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CrmClientPortraitComponent extends \CBitrixComponent
{
	const LOAD_STAT_CACHE_TIME = 86400;

	protected $manualTargetOptions;
	protected $loadMax;
	protected $loadCurrent;
	protected $loadAverage;

	public function onPrepareComponentParams($arParams)
	{
		global $APPLICATION;
		
		$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
		$arResult['PATH_TO_CONTACT_SHOW'] = $arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
		$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
		$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
		$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
		$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
		$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
		$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
		$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
		$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
		$arParams['PATH_TO_REQUISITE_EDIT'] = CrmCheckPath('PATH_TO_REQUISITE_EDIT', $arParams['PATH_TO_REQUISITE_EDIT'], $APPLICATION->GetCurPage().'?id=#id#&edit');
		$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
		$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

		return $arParams;
	}

	protected function getElementId()
	{
		return isset($this->arParams['ELEMENT_ID']) ? (int)$this->arParams['ELEMENT_ID'] : 0;
	}

	protected function getElementType()
	{
		return isset($this->arParams['ELEMENT_TYPE']) ? (int)$this->arParams['ELEMENT_TYPE'] : 0;
	}
	
	protected function getEntityType()
	{
		$type = '';
		switch ($this->getElementType())
		{
			case CCrmOwnerType::Company:
				$type = 'COMPANY';
				break;

			case CCrmOwnerType::Contact:
				$type = 'CONTACT';
				break;
		}
		return $type;
	}

	protected function getElement()
	{
		$elementId = $this->getElementId();
		
		$iterator = null;

		switch ($this->getElementType())
		{
			case CCrmOwnerType::Company:
				$iterator = CCrmCompany::GetListEx(
					array(),
					array('ID' => $elementId)
				);
				break;
				
			case CCrmOwnerType::Contact:
				$iterator = CCrmContact::GetListEx(
					array(),
					array('ID' => $elementId)
				);
				break;
		}

		$element = $iterator ? $iterator->fetch() : null;

		if ($element)
		{
			$element['FM'] = array();
			$multiFieldsIterator = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $this->getEntityType(), 'ELEMENT_ID' => $elementId)
			);
			while($arMultiFields = $multiFieldsIterator->fetch())
			{
				$element['FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
			}

			$element['ASSIGNED_BY_FORMATTED_NAME'] = (int)$element['ASSIGNED_BY_ID'] > 0
				? CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $element['ASSIGNED_BY_LOGIN'],
						'NAME' => $element['ASSIGNED_BY_NAME'],
						'LAST_NAME' => $element['ASSIGNED_BY_LAST_NAME'],
						'SECOND_NAME' => $element['ASSIGNED_BY_SECOND_NAME']
					),
					true, false
				) : GetMessage('RESPONSIBLE_NOT_ASSIGNED');
			
			$element['ASSIGNED_BY_URL'] = (int)$element['ASSIGNED_BY_ID'] > 0 ? CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_USER_PROFILE'],
				array('user_id' => $element['ASSIGNED_BY_ID'])
			) : '';
		}
		
		if ($this->getElementType() == CCrmOwnerType::Company)
		{
			$companyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
			$companyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');
			
			if (isset($companyTypeList[$element['COMPANY_TYPE']]))
				$element['COMPANY_TYPE_TITLE'] = $companyTypeList[$element['COMPANY_TYPE']];

			if (isset($companyIndustryList[$element['INDUSTRY']]))
				$element['INDUSTRY_TITLE'] = $companyIndustryList[$element['INDUSTRY']];
		}

		return $element;
	}
	
	protected function getPageTitle()
	{
		return Loc::getMessage('CRM_CLIENT_PORTRAIT_'.$this->getEntityType().'_TITLE');
	}

	protected function getLoadTitle()
	{
		return Loc::getMessage('CRM_CLIENT_PORTRAIT_'.$this->getEntityType().'_LOAD_TITLE');
	}

	protected function checkReadPermissions()
	{
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$elementId = $this->getElementId();
		if (!$elementId)
			return false;

		$elementType = $this->getElementType();
		
		switch ($elementType)
		{
			case CCrmOwnerType::Company:
				return CCrmCompany::CheckReadPermission($elementId, $userPermissions);
				break;

			case CCrmOwnerType::Contact:
				return CCrmContact::CheckReadPermission($elementId, $userPermissions);
				break;
		}

		return false;
	}

	protected function getWonDealsStat()
	{
		$query = new Query(DealSumStatisticsTable::getEntity());

		$query->registerRuntimeField('', new ExpressionField('SUM_TOTAL_R', "SUM(%s)", 'SUM_TOTAL'));
		$query->registerRuntimeField('', new ExpressionField('CNT', 'COUNT(*)'));

		$query->addSelect('SUM_TOTAL_R');
		$query->addSelect('CNT');

		$query->addFilter('=STAGE_SEMANTIC_ID', PhaseSemantics::SUCCESS);

		switch ($this->getElementType())
		{
			case CCrmOwnerType::Company:
				$query->registerRuntimeField('',
					new ReferenceField('M',
						DealTable::getEntity(),
						array('=this.OWNER_ID' => 'ref.ID'),
						array('join_type' => 'INNER')
					)
				);

				$query->addFilter('=M.COMPANY_ID', $this->getElementId());
				break;

			case CCrmOwnerType::Contact:
				$query->registerRuntimeField('',
					new ReferenceField('M',
						DealContactTable::getEntity(),
						array('=this.OWNER_ID' => 'ref.DEAL_ID'),
						array('join_type' => 'INNER')
					)
				);

				$query->addFilter('=M.CONTACT_ID', $this->getElementId());
				break;
		}

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		
		$result = array(
			'SUM' => $result && $result['SUM_TOTAL_R'] ? (float)$result['SUM_TOTAL_R'] : 0,
			'CNT' => $result && $result['CNT'] ? (int)$result['CNT'] : 0,
		);

		$currencyID = CCrmCurrency::GetAccountCurrencyID();
		$result['SUM_FORMATTED'] = CCrmCurrency::MoneyToString($result['SUM'], $currencyID);
		
		return $result;
	}

	protected function getActivitiesStat()
	{
		switch ($this->getElementType())
		{
			case CCrmOwnerType::Company:
				$query = new Query(CompanyActivityStatisticsTable::getEntity());
				break;

			case CCrmOwnerType::Contact:
				$query = new Query(ContactActivityStatisticsTable::getEntity());
				break;

			default:
				return array();
		}

		$query->registerRuntimeField('', new ExpressionField('CNT', 'SUM(TOTAL_QTY)'));

		$query->addSelect('PROVIDER_ID');
		$query->addSelect('CNT');

		$query->addFilter('=OWNER_ID', $this->getElementId());
		$query->addGroup('PROVIDER_ID');

		$result = array('ITEMS' => array(), 'CNT' => 0);
		$dbResult = $query->exec();

		while($row = $dbResult->fetch())
		{
			$label = '';
			if ($row['PROVIDER_ID'] && ($provider = \CCrmActivity::GetProviderById($row['PROVIDER_ID'])) !== null)
			{
				$label = $provider::getName();
			}
			
			$result['ITEMS'][] = array(
				'LABEL' => $label,
				'CNT' => $row['CNT']
			);

			$result['CNT'] += $row['CNT'];
		}

		return $result;
	}

	protected function calculateConversionPercent($actCnt, $dealCnt)
	{
		return $actCnt > 0 ? $dealCnt / $actCnt * 100 : ($dealCnt > 0 ? 100 : 0);
	}

	public function executeComponent()
	{
		if (!Main\Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
			return;
		}

		if (!$this->checkReadPermissions())
		{
			ShowError(GetMessage('CRM_PERMISSION_DENIED'));
			return;
		}
		
		$this->arResult['ELEMENT'] = $this->getElement();
		$this->arResult['IS_COMPANY'] = $this->getElementType() === \CCrmOwnerType::Company;
		$this->arResult['ENTITY_TYPE_ID'] = $this->getElementType();

		if (!$this->arResult['ELEMENT'])
		{
			ShowError(GetMessage('CRM_PERMISSION_DENIED'));
			return;
		}
		
		if ($this->arResult['IS_COMPANY'])
			$this->arResult['ELEMENT_PHOTO_SRC'] = CFile::GetPath($this->arResult['ELEMENT']['LOGO']);
		else
			$this->arResult['ELEMENT_PHOTO_SRC'] = CFile::GetPath($this->arResult['ELEMENT']['PHOTO']);

		$this->arResult['PAGE_TITLE'] = $this->getPageTitle();
		$this->arResult['LOAD_TITLE'] = $this->getLoadTitle();
		$this->arResult['ENTITY_TYPE'] = $this->getEntityType();
		$this->arResult['WON_DEALS_STAT'] = $this->getWonDealsStat();
		$this->arResult['ACTIVITIES_STAT'] = $this->getActivitiesStat();
		$this->arResult['CONVERSION_PERCENT'] = $this->calculateConversionPercent(
			$this->arResult['ACTIVITIES_STAT']['CNT'],
			$this->arResult['WON_DEALS_STAT']['CNT']
		);

		$this->arResult['LOADBARS'] = array(
			'primary' => array(
				'name' => 'primary',
				'data' => $this->getLoadData(),
				'context' => array(
					'entityType' => $this->getEntityType()
				)
			)
		);

		$dealCategories = \Bitrix\Crm\Category\DealCategory::getSelectListItems();
		if (count($dealCategories) > 1)
		{
			foreach ($dealCategories as $categoryId => $categoryName)
			{
				$this->arResult['LOADBARS'][] = array(
					'name' => $categoryName,
					'data' => $this->getLoadData($categoryId),
					'context' => array(
						'entityType' => $this->getEntityType(),
						'dealCategoryId' => $categoryId
					)
				);
			}
		}

		$curUser = CCrmSecurityHelper::GetCurrentUser();
		$CrmPerms = new CCrmPerms($curUser->GetID());
		$this->arResult['CAN_WRITE_CONFIG'] = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

		$this->includeComponentTemplate();
	}

	protected function getLoadData($dealCategoryId = null)
	{
		$data = array(
			'max' => $this->getLoadMax($dealCategoryId),
			'current' => $this->getLoadCurrent($dealCategoryId),
			'manualTarget' => false
		);

		$target = $this->getLoadManualTarget($dealCategoryId);
		if ($target === null)
		{
			$target = $this->getLoadAverage($dealCategoryId);
		}
		else
		{
			$data['manualTarget'] = true;
		}

		$data['target'] = $target;

		return $data;
	}

	protected function getLoadMax($dealCategoryId = null)
	{
		if ($this->loadMax === null)
		{
			$cache = Main\Data\Cache::createInstance();
			if($cache->initCache(static::LOAD_STAT_CACHE_TIME, 'crm_client_portrait_loadmax', 'crm'))
			{
				$this->loadMax = $cache->getVars();
			}
			else
			{
				$this->loadMax = CommunicationStatistics::getLoadMaxValues($this->getElementType());
				$cache->startDataCache();
				$cache->endDataCache($this->loadMax);
			}
		}

		if ($dealCategoryId === null)
			return $this->loadMax['*'];

		return isset($this->loadMax[$dealCategoryId]) ? $this->loadMax[$dealCategoryId] : 0;
	}

	protected function getLoadCurrent($dealCategoryId = null)
	{
		if ($this->loadCurrent === null)
		{
			$this->loadCurrent = CommunicationStatistics::getLoadCurrents(
				$this->getElementType(),
				$this->getElementId()
			);
		}

		if ($dealCategoryId === null)
			return ceil($this->loadCurrent['*']);

		return isset($this->loadCurrent[$dealCategoryId]) ? ceil($this->loadCurrent[$dealCategoryId]) : 0;
	}

	protected function getLoadAverage($dealCategoryId = null)
	{
		if ($this->loadAverage === null)
		{
			$cache = Main\Data\Cache::createInstance();
			if($cache->initCache(static::LOAD_STAT_CACHE_TIME, 'crm_client_portrait_loadavg', 'crm'))
			{
				$this->loadAverage = $cache->getVars();
			}
			else
			{
				$this->loadAverage = CommunicationStatistics::getLoadAverages($this->getElementType());
				$cache->startDataCache();
				$cache->endDataCache($this->loadAverage);
			}
		}

		if ($dealCategoryId === null)
			return ceil($this->loadAverage['*']);

		return isset($this->loadAverage[$dealCategoryId]) ? ceil($this->loadAverage[$dealCategoryId]) : 0;
	}

	protected function getLoadManualTarget($dealCategoryId = null)
	{
		if ($this->manualTargetOptions === null)
		{
			$this->manualTargetOptions = \Bitrix\Main\Config\Option::get('crm', 'portrait_'.strtolower($this->getEntityType()));
			if ($this->manualTargetOptions !== '')
				$this->manualTargetOptions = unserialize($this->manualTargetOptions);

			if (!is_array($this->manualTargetOptions))
				$this->manualTargetOptions = array();
		}

		$optionId = $dealCategoryId === null ? 'primary' : (int)$dealCategoryId;

		return isset($this->manualTargetOptions[$optionId]) ? (int)$this->manualTargetOptions[$optionId] : null;
	}

	public static function prepareComments($comments)
	{
		return trim(htmlspecialcharsback(strip_tags(
			preg_replace('/(<br[^>]*>)+/is'.BX_UTF_PCRE_MODIFIER, "\n", $comments)
		)));
	}
}
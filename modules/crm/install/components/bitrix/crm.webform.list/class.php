<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Bitrix24\PhoneVerify;
use Bitrix\Crm\WebForm\Options;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Crm;
use Bitrix\Crm\WebForm;
use Bitrix\Crm\WebForm\Internals;
use Bitrix\Crm\WebForm\Script;
use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\WebForm\Preset;
use Bitrix\Crm\WebForm\Entity;
use Bitrix\Crm\UI\Webpack;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\WebForm\Internals\PageNavigation;
use Bitrix\Main\UserTable;
use Bitrix\Crm\Ads\AdsForm;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options as FilterOptions;

Loc::loadMessages(__FILE__);

class CCrmWebFormListComponent extends \CBitrixComponent
{
	const DEFAULT_NAV_KEY = "CRM_WEBFORM_PAGE";
	const DEFAULT_FILTER_ID = "CRM_WEBFORM_FILTER";
	const DEFAULT_PAGE_SIZE = 10;
	const DEFAULT_GRID_ID = "CRM_WEBFORM_GRID";

	protected $errors = array();

	private function rebuildWebpack()
	{
		if ($this->request->get('rebuildResources') === 'y' || $this->request->get('rebuildAll') === 'y')
		{
			if ($this->request->get('rebuildFiles') === 'y')
			{
				$files = Main\FileTable::query()
					->addSelect('ID')
					->addFilter('MODULE_ID', 'crm')
					->addFilter('SUBDIR', 'crm/form')
					->addFilter('FILE_NAME', 'app.js')
					->setOrder(['ID' => 'DESC'])
					->setLimit(3)
					->fetchAll()
				;
				if (count($files) > 1)
				{
					foreach ($files as $file)
					{
						\CFile::Delete($file['ID']);
					}
				}
			}

			Webpack\Form::rebuildResources();
			if (
				Main\Loader::includeModule('landing')
				&& is_callable(['\Bitrix\Landing\Subtype\Form', 'clearCache'])
			)
			{
				\Bitrix\Landing\Subtype\Form::clearCache();
			}
		}

		if ($this->request->get('rebuildForms') === 'y' || $this->request->get('rebuildAll') === 'y')
		{
			WebForm\Manager::updateScriptCache(null, 0);
		}
	}

	public function prepareResult()
	{
		/* Fix unhandled errors */
		$this->rebuildWebpack();

		/* COLUMNS */
		$this->arResult['COLUMNS'] = $this->getGridColumns();

		/**@var \CUser $USER*/
		global $USER;

		/* ADS */
		$this->arResult['ADS_FORM'] = array();
		$this->arResult['ADS_FORM']['CAN_EDIT'] = AdsForm::canUserEdit($USER->GetID());
		$this->arResult['ADS_FORM'] = array();
		$adsTypes = AdsForm::getServiceTypes();
		foreach ($adsTypes as $adsType)
		{
			$this->arResult['ADS_FORM'][$adsType] = AdsForm::getLinkedForms($adsType);
		}

		$replaceListNew = array('id' => 0, 'form_id' => 0);
		$this->arResult['PATH_TO_WEB_FORM_NEW'] = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_WEB_FORM_EDIT'], $replaceListNew);
		$preset = $this->request->get('PRESET');

		$this->arResult['SHOW_PERMISSION_ERROR'] = $this->request->get('show_permission_error') === 'Y';

		if ($preset && preg_match('#^[A-Za-z0-9-_]+$#D', $preset))
		{
			$uri = new \Bitrix\Main\Web\Uri($this->arResult['PATH_TO_WEB_FORM_NEW']);
			$uri->addParams(["PRESET" => $preset]);
			$this->arResult['PATH_TO_WEB_FORM_NEW'] = $uri->getLocator();
		}

		$this->arResult['ITEMS'] = array();
		$filter = array();
		if (isset($this->arResult['FILTER_ACTIVE_CURRENT']) && in_array($this->arResult['FILTER_ACTIVE_CURRENT'], array('N', 'Y')))
		{
			$filter['ACTIVE'] = $this->arResult['FILTER_ACTIVE_CURRENT'];
		}
		if ($this->arResult['PERM_CAN_EDIT']
			&& $this->request->isPost() && check_bitrix_sessid()
		)
		{
			$this->preparePost();
		}

		$dbForms = Internals\FormTable::getDefaultTypeList(array(
			"select"=> $this->getDataFields(),
			"filter"=> $this->getDataFilters(),
			'order' => $this->getOrder(),
			'offset' => $this->prepareNavigation()->getOffset(),
			'limit' => $this->prepareNavigation()->getLimit(),
			'count_total' => true,
			// 'cache' => array('ttl' => 36000),
		));
		$this->arResult['TOTAL_ROWS_COUNT'] = $dbForms->getCount();
		$this->prepareNavigation()->setRecordCount($this->arResult['TOTAL_ROWS_COUNT']);
		$this->setUiFilterPresets();

		while($form = $dbForms->fetch())
		{
			$this->arResult['ADS_FORM']['ALL_ID'][] = $form['ID'];

			$counters = Form::getCounters($form['ID'], $form['ENTITY_SCHEME']);
			$this->addEntityCounters($form, $counters['ENTITY']);

			$form['COUNT_START_FILL'] = (int)$counters['COMMON']['START_FILL'];
			$form['COUNT_END_FILL'] = (int)$counters['COMMON']['END_FILL'];
			$form['COUNT_VIEWS'] = (int)$counters['COMMON']['VIEWS'];
			$form['COUNT_RESET_DATE'] = $counters['COMMON']['DATE_CREATE'];
			$form['SUMMARY_CONVERSION'] = 0;
			$form['SUMMARY_SUBMITS'] = 0;

			$form['ACTIVE_CHANGE_BY'] = $this->getUserInfo($form['ACTIVE_CHANGE_BY']);

			$replaceList = array('id' => $form['ID'], 'form_id' => $form['ID']);
			$form['PATH_TO_WEB_FORM_LIST'] = CComponentEngine::makePathFromTemplate(
				$this->arParams['PATH_TO_WEB_FORM_LIST'],
				$replaceList
			);

			if ($this->arResult['PERM_CAN_EDIT'])
			{
				$form['PATH_TO_WEB_FORM_EDIT'] = Bitrix\Crm\Integration\Landing\FormLanding::getInstance()->canUse()
					? Bitrix\Crm\WebForm\Manager::getEditUrl($form['ID'])
					: CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_WEB_FORM_EDIT'], $replaceList)
				;
			}
			else
			{
				$form['PATH_TO_WEB_FORM_EDIT'] = '/crm/webform/edit/' . $form['ID'] . '/';
			}

			$form['PATH_TO_WEB_FORM_FILL'] = Script::getUrlContext($form, $this->arParams['PATH_TO_WEB_FORM_FILL']);

			$form['PHONE_VERIFIED'] = !$this->isPhoneVerificationRequired($form['ID']);

			$form['HAS_ADS_FORM_LINKS'] = false;
			$form['ADS_FORM'] = array();
			if (AdsForm::canUse())
			{
				$adsTypes = AdsForm::getServiceTypes();
				foreach ($adsTypes as $adsType)
				{
					$replaceList['ads_type'] = $adsType;
					$hasLinks = in_array($form['ID'], $this->arResult['ADS_FORM'][$adsType], true);
					$form['ADS_FORM'][$adsType] = array(
						'TYPE' => $adsType,
						'ICON' => $adsType === 'facebook' ? 'fb' : 'vk',
						'NAME' => AdsForm::getServiceTypeName($adsType),
						'HAS_LINKS' => $hasLinks,
						'PATH_TO_ADS' => CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_WEB_FORM_ADS'], $replaceList),
					);
					if ($hasLinks)
					{
						$form['HAS_ADS_FORM_LINKS'] = true;
					}
				}
			}

			$this->arResult['ITEMS'][] = $form;
		}

		$this->calcConversionAndSubmits();

		$this->arResult['STUB'] = [
			'title' => Loc::getMessage('TASKS_GRID_STUB_NO_DATA_TITLE'),
			'description' => Loc::getMessage('TASKS_GRID_STUB_NO_DATA_DESCRIPTION'),
		];
		$this->arResult['SHOW_PLUGINS'] = false;
		$this->arResult['USER_CONSENT_EMAIL'] = '';

		$userOptionViewType = 'webform_list_view';
		$userViewTypes = \CUserOptions::GetOption('crm', $userOptionViewType, array());
		$this->arResult['HIDE_DESC'] = ($userViewTypes['hide-desc'] ?? 'N') == 'Y';

		$this->arResult['HIDE_DESC_FZ152'] = true;
		if (in_array(Main\Application::getInstance()->getLicense()->getRegion(), ['ru', 'by']))
		{
			$notifyOptions = \CUserOptions::GetOption('crm', 'notify_webform', array());
			$this->arResult['HIDE_DESC_FZ152'] = (is_array($notifyOptions) && $notifyOptions['ru_fz_152'] == 'Y');

			$user = UserTable::getList(array(
				'select' => array('EMAIL'),
				'filter' => array(
					'=ID' => array_slice(\CGroup::getGroupUser(1), 0, 200),
					'=ACTIVE' => 'Y'
				),
				'limit' => 1
			))->fetch();
			if ($user && $user['EMAIL'])
			{
				$email = $user['EMAIL'];
			}
			else
			{
				$email = Option::get('main', 'email_from', '');
			}
			$this->arResult['USER_CONSENT_EMAIL'] = $email;
		}
		$this->arResult['RESTRICTION_POPUP'] = \Bitrix\Crm\Restriction\RestrictionManager::getWebformLimitRestriction()->preparePopupScript();
	}

	protected function preparePost()
	{
		$ids = $this->request->get('ID');
		$action = $this->request->get('action_button_' . $this->arResult['GRID_ID']);
		switch ($action)
		{
			case 'delete':
				if (!is_array($ids))
				{
					$ids = array($ids);
				}

				foreach ($ids as $id)
				{
					if(!Form::delete($id))
					{
						$this->errors[] = '';
					}
				}
				break;

			case 'activate':
				if (!is_array($ids))
				{
					$ids = array($ids);
				}

				foreach ($ids as $id)
				{
					if(!Form::activate($id))
					{
						$this->errors[] = '';
					}
				}
				break;
		}
	}

	protected function calcConversionAndSubmits(): void
	{
		$ids = array_column($this->arResult['ITEMS'], 'ID');

		$periods = [
			'current' => [
				'from' => (new Main\Type\Date())->add("-14 days"),
				'to' => (new Main\Type\Date())->add("1 days"),
			],
			'recent' => [
				'from' => (new Main\Type\Date())->add("-28 days"),
				'to' => (new Main\Type\Date())->add("-14 days"),
			],
		];
		$views = [];
		foreach ($periods as $periodKey => $period)
		{
			$rows = WebForm\Internals\FormCounterDailyTable::query()
				->addSelect('FORM_ID')
				->addSelect('CNT')
				->addFilter('FORM_ID', $ids)
				->addFilter('>DATE_STAT', $period['from'])
				->addFilter('<=DATE_STAT', $period['to'])
				->registerRuntimeField(new Main\ORM\Fields\ExpressionField('CNT', 'SUM(%s)', 'VIEWS'))
				->addGroup('FORM_ID')
			;
			$rows = $rows->fetchAll();
			$views[$periodKey] = array_combine(
				array_column($rows, 'FORM_ID'),
				array_column($rows, 'CNT')
			);
		}

		$results = [];
		foreach ($periods as $periodKey => $period)
		{
			$rows = WebForm\Internals\FormCounterDailyTable::query()
				->addSelect('FORM_ID')
				->addSelect('CNT')
				->addFilter('FORM_ID', $ids)
				->addFilter('>DATE_STAT', $period['from'])
				->addFilter('<=DATE_STAT', $period['to'])
				->registerRuntimeField(new Main\ORM\Fields\ExpressionField('CNT', 'SUM(%s)', 'END_FILL'))
				->addGroup('FORM_ID')
			;
			$rows = $rows->fetchAll();
			$results[$periodKey] = array_combine(
				array_column($rows, 'FORM_ID'),
				array_column($rows, 'CNT')
			);
		}

		foreach ($this->arResult['ITEMS'] as $formIndex => $form)
		{
			$id = $form['ID'];
			$formCreate = max($form['COUNT_RESET_DATE'], $form['DATE_CREATE']);
			$allTimeEndFill = $form['COUNT_END_FILL'];
			$allTimeViews = $form['COUNT_VIEWS'];

			$conv = $submits = [];
			foreach ($periods as $periodKey => $period)
			{
				$viewsForPeriod = $views[$periodKey][$id] ?? 0;
				$resultsForPeriod = $results[$periodKey][$id] ?? 0;
				$conv[$periodKey] = $viewsForPeriod > 0 ? $resultsForPeriod / $viewsForPeriod : 0;
				$submits[$periodKey] = $resultsForPeriod;
			}

			$form['SUMMARY_CONVERSION'] = self::formatConversion($conv['current'], $allTimeViews);

			$form['SUMMARY_SUBMITS'] = self::formatSubmits((int)$submits['current'], (int)$submits['recent'], $formCreate, $allTimeEndFill);

			$this->arResult['ITEMS'][$formIndex] = $form;
		}
	}

	protected function addEntityCounters(array &$form, array $entityCounters): void
	{
		foreach($entityCounters as $index => $counter)
		{
			if (intval($counter['VALUE'] ?? 0) <= 0)
			{
				$counter['LINK'] = null;
			}

			$entityCounters[$index] = $counter;
		}

		static $categories = [];
		$scheme = Bitrix\Crm\WebForm\Entity::getSchemes((int)$form['ENTITY_SCHEME']);
		$caption = $scheme['MAIN_ENTITY'] ? \CCrmOwnerType::GetDescription($scheme['MAIN_ENTITY']) : '';
		$captionAdd = '';
		$categoryId = $form['FORM_SETTINGS']['DEAL_CATEGORY'] ?? null;
		if ($categoryId	&& $scheme['MAIN_ENTITY'] ===\CCrmOwnerType::Deal)
		{
			if (!array_key_exists($scheme['MAIN_ENTITY'], $categories))
			{
				$categories[$scheme['MAIN_ENTITY']] = Crm\Category\DealCategory::getAll(false);
				$categories[$scheme['MAIN_ENTITY']] = array_combine(
					array_column($categories[$scheme['MAIN_ENTITY']], 'ID'),
					array_column($categories[$scheme['MAIN_ENTITY']], 'NAME')
				);
			}

			$captionAdd = $categories[$scheme['MAIN_ENTITY']][$categoryId] ?? '';
		}

		$categoryId = $form['FORM_SETTINGS']['DYNAMIC_CATEGORY'] ?? null;
		if ($scheme['DYNAMIC'] && $categoryId)
		{
			if (!array_key_exists($scheme['MAIN_ENTITY'], $categories))
			{
				$typesMap = Crm\Service\Container::getInstance()->getDynamicTypesMap();
				$typesMap->load([
					'isLoadCategories' => true,
				]);
				$categories[$scheme['MAIN_ENTITY']] = array_map(
					function ($itemCategory)
					{
						return [
							'ID' => $itemCategory->getId(),
							'NAME' => $itemCategory->getName(),
						];
					},
					$typesMap->getCategories($scheme['MAIN_ENTITY'])
				);
				$categories[$scheme['MAIN_ENTITY']] = array_combine(
					array_column($categories[$scheme['MAIN_ENTITY']], 'ID'),
					array_column($categories[$scheme['MAIN_ENTITY']], 'NAME')
				);
			}

			if (count($categories[$scheme['MAIN_ENTITY']]) > 1)
			{
				$captionAdd = $categories[$scheme['MAIN_ENTITY']][$categoryId] ?? '';
			}
		}

		$caption = $caption ?: $scheme['NAME'];
		$caption .= $captionAdd ? " ({$captionAdd})" : '';
		$form['ENTITY_COUNTERS'] = [
			'caption' => $caption,
			'counters' => $entityCounters
		];
	}

	protected static function formatConversion($value, $allTimeViews)
	{
		$value = $value > 1 ? 1 : $value;
		$value = round($value * 100);

		switch (true)
		{
			case $value <= 35:
				$code = 'bad'; break;
			case $value >= 36 && $value <= 60:
				$code = 'normal'; break;
			case $value >= 61 && $value <= 80:
				$code = 'good'; break;
			case $value >= 81:
				$code = 'perfect'; break;
			default:
				$code = 'none'; break;
		}

		$map = [
			'none' => [
				'text' => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_CONVERSION_NONE'),
				'color' => '',
			],
			'bad' => [
				'text' =>  Loc::getMessage('CRM_WEBFORM_LIST_ITEM_CONVERSION_BAD'),
				'color' => 'down2',
			],
			'normal' => [
				'text' =>  Loc::getMessage('CRM_WEBFORM_LIST_ITEM_CONVERSION_NORMAL'),
				'color' => 'normal-green',
			],
			'good' => [
				'text' =>  Loc::getMessage('CRM_WEBFORM_LIST_ITEM_CONVERSION_GOOD'),
				'color' => 'up2',
			],
			'perfect' => [
				'text' =>  Loc::getMessage('CRM_WEBFORM_LIST_ITEM_CONVERSION_PERFECT'),
				'color' => 'up3',
			],
		];

		return [
			'color' => $map[$code]['color'],
			'value' => $allTimeViews > 0 ? $value . '%' : null,
			'text' => $map[$code]['text'],
		];
	}

	protected static function formatSubmits(int $valueCurrent, int $valueRecent, DateTime $creationDate, int $allTimeEndFill): array
	{
		$result = [
			'value' => $valueCurrent,
			'color' => '',
			'text' => '',
			'hint' => '',
		];

		// if there are no submits
		if ($allTimeEndFill <= 0)
		{
			return $result;
		}

		// form created in the last 4 weeks, data collecting
		if ($creationDate > DateTime::createFromPhp(new \DateTime('-4 weeks')))
		{
			$result['text'] = Loc::getMessage('CRM_WEBFORM_LIST_ITEM_SUBMITS_NONE');
			$result['hint'] = Loc::getMessage('CRM_WEBFORM_LIST_ITEM_SUBMITS_NONE');
			$result['value'] = null;
			return $result;
		}

		// low values, simplified mode
		if ($valueCurrent <=10 && $valueRecent <=10)
		{
			switch (true)
			{
				case $valueCurrent === $valueRecent:
					$result['color'] = 'normal-green';
					$result['hint'] = Loc::getMessage('CRM_WEBFORM_LIST_ITEM_SUBMITS_NORMAL');
					break;
				case $valueCurrent > $valueRecent:
					$result['color'] = 'up2';
					$result['hint'] = Loc::getMessage('CRM_WEBFORM_LIST_ITEM_SUBMITS_GOOD');
					break;
				case $valueCurrent < $valueRecent:
					$result['color'] = 'down2';
					$result['hint'] = Loc::getMessage('CRM_WEBFORM_LIST_ITEM_SUBMITS_BAD');
					break;
			}
			return $result;
		}

		$rate = $valueCurrent === 0
			? ($valueRecent === 0 ? 0 : -100)
			: (($valueCurrent - $valueRecent) / $valueCurrent) * 100
		;

		switch (true)
		{
			// stable, if equals or differs by no more than 7.5%
			case abs($rate) <= 7.5:
				$result['color'] = 'normal-green';
				$result['hint'] = Loc::getMessage('CRM_WEBFORM_LIST_ITEM_SUBMITS_NORMAL');
				break;
			case $rate > 7.5 && $rate <= 20:
				$result['color'] = 'up2';
				$result['hint'] = Loc::getMessage('CRM_WEBFORM_LIST_ITEM_SUBMITS_GOOD');
				break;
			case $rate < -7.5 && $rate >= -20:
				$result['color'] = 'down2';
				$result['hint'] = Loc::getMessage('CRM_WEBFORM_LIST_ITEM_SUBMITS_BAD');
				break;
			case $rate > 20:
				$result['color'] = 'up3';
				$result['hint'] = Loc::getMessage('CRM_WEBFORM_LIST_ITEM_SUBMITS_PERFECT');
				break;
			case $rate < -20:
				$result['color'] = 'down3';
				$result['hint'] = Loc::getMessage('CRM_WEBFORM_LIST_ITEM_SUBMITS_AWFUL');
				break;
		}

		return $result;
	}

	protected function getGridColumns()
	{
		return array(
			array(
				"id" => "ID",
				"name" => "ID",
				"sort" => "ID",
				"default" => false,
			),
			array(
				"id" => "NAME",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_NAME'),
				"sort" => "NAME",
				"default" => true,
			),
			array(
				"id" => "DATE_CREATE",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_DATE_CREATE'),
				"sort" => "DATE_CREATE",
				"default" => false,
			),
			array(
				"id" => "ACTIVE_CHANGE_DATE",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_CHANGE_DATE'),
				"sort" => "ACTIVE_CHANGE_DATE",
				"default" => false,
			),
			array(
				"id" => "ACTIVE_CHANGE_BY",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_CHANGE_BY'),
				"sort" => "ACTIVE_CHANGE_BY",
				"default" => false,
			),
			array(
				"id" => "PATH_TO_WEB_FORM_FILL",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_PUBLIC_LINK'),
				"default" => true,
			),
			array(
				"id" => "EMBEDDING",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_EMBEDDING'),
				"default" => true,
			),
			array(
				"id" => "ACTIVE",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE'),
				"sort" => "ACTIVE",
				"default" => true,
			),
			array(
				"id" => "ENTITY_COUNTERS",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ENTITY_COUNTERS'),
				"default" => true,
			),
			array(
				"id" => "SUMMARY_CONVERSION",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_CONVERSION_MENU'),
				"default" => true,
				"title" => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_CONVERSION_MENU_HINT'),
				"hint" => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_CONVERSION_MENU_HINT'),
			),
			array(
				"id" => "SUMMARY_SUBMITS",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_SUBMITS_MENU'),
				"default" => true,
				"title" => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_SUBMITS_MENU_HINT'),
				"hint" => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_SUBMITS_MENU_HINT'),
			),
			[
				"id" => "GET_PAYMENT",
				"name" => Loc::getMessage("CRM_WEBFORM_LIST_ITEM_GET_PAYMENT"),
				"default" => false
			]
		);
	}
	protected function getOrder()
	{
		$gridOptions = new GridOptions($this->arResult["GRID_ID"]);
		$sorting = $gridOptions->getSorting();

		$order = [];
		$sortByList = $this->getGridSortList();
		foreach (($sorting["sort"] ?? []) as $sortBy => $sortOrder)
		{
			if (!in_array($sortBy, $sortByList, true))
			{
				continue;
			}
			$sortOrder = mb_strtoupper($sortOrder) === "ASC" ? "ASC" : "DESC";
			$order[$sortBy] = $sortOrder;
		}

		return $order ?: $this->getGridDefaultSort();
	}

	protected function getGridDefaultSort()
	{
		return array("ID" => "DESC");
	}

	protected function getGridSortList()
	{
		$result = [];
		foreach ($this->getGridColumns() as $column)
		{
			if (!isset($column["sort"]) || !$column["sort"])
			{
				continue;
			}
			$result[] = $column["sort"];
		}
		return $result;
	}

	protected function getIntegrationTypes()
	{
		if (!AdsForm::canUse())
		{
			return [];
		}

		static $items = null;
		if ($items !== null)
		{
			return $items;
		}

		$items = [];
		foreach (AdsForm::getServiceTypes() as $type)
		{
			$items[$type] = AdsForm::getServiceTypeName($type);
		}

		return $items;
	}

	public function prepareParams()
	{
		//$this->errors = new ErrorCollection();
		$this->arResult['SET_TITLE'] = isset($this->arResult['SET_TITLE']) && $this->arResult['SET_TITLE'] !== 'N';
		//$this->prepareAccessParams();
		$this->prepareGridParams();
		$this->prepareFilterParams();
		$this->prepareNavigationParams();
//		$this->setTitle();
		$this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams["NAME_TEMPLATE"]);

		//return $this->checkAccess();
	}

	protected function prepareNavigationParams()
	{
		if (!isset($this->arResult["PAGE_SIZE"]) || (int)$this->arResult["PAGE_SIZE"] <= 0)
		{
			$this->arResult["PAGE_SIZE"] = self::DEFAULT_PAGE_SIZE;
		}
		$this->arResult["NAVIGATION_KEY"] = $this->arResult["NAVIGATION_KEY"] ?? self::DEFAULT_NAV_KEY;
	}

	protected function prepareFilterParams()
	{
		$this->arResult["FILTER_ID"] = $this->arResult["FILTER_ID"] ?? self::DEFAULT_FILTER_ID;
		$this->arResult["FILTER"] = $this->arResult["FILTER"] ?? $this->getFilters();
	}

	protected function prepareGridParams()
	{
		$this->arResult["GRID_ID"] = $this->arResult["GRID_ID"] ?? self::DEFAULT_GRID_ID;
		$this->arResult["COLUMNS"] = $this->arResult["COLUMNS"] ?? $this->getGridColumns();
	}
	protected function getDataFields()
	{
		return [
			"ID",
			"NAME",
			"DATE_CREATE",
			"ACTIVE_CHANGE_DATE",
			"ACTIVE",
			"ACTIVE_CHANGE_BY",
			"ENTITY_SCHEME",
			"IS_SYSTEM",
			"FORM_SETTINGS",
			"IS_PAY"
		];
	}

	protected function prepareNavigation()
	{
		if(!isset($this->arResult["NAVIGATION_OBJECT"]))
		{
			$this->prepareNavigationParams();
			$this->arResult["NAVIGATION_OBJECT"] = new PageNavigation($this->arResult["NAVIGATION_KEY"]);
			$this->arResult["NAVIGATION_OBJECT"]->allowAllRecords(false)->setPageSize(10)->initFromUri();
		}
		return $this->arResult["NAVIGATION_OBJECT"];
	}

	protected function getDataFilters()
	{
		$filterOptions = new FilterOptions(self::DEFAULT_FILTER_ID);
		$requestFilter = $filterOptions->getFilter($this->arResult["FILTER"]);
		$searchString = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent(trim($filterOptions->getSearchString()));

		$filter = [];
		if ($searchString)
		{
			$filter["NAME"] = "%" . $searchString . "%";
		}
		if (!empty($requestFilter['ID']))
		{
			$filter['=ID'] = $requestFilter['ID'];
		}
		if (isset($requestFilter['NAME']) && $requestFilter['NAME'])
		{
			$filter['NAME'] = '%' . $requestFilter['NAME'] . '%';
		}
		if (isset($requestFilter['ACTIVE_CHANGE_BY']) && $requestFilter['ACTIVE_CHANGE_BY'])
		{
			$filter['=ACTIVE_CHANGE_BY'] = $requestFilter['ACTIVE_CHANGE_BY'];
		}
		if (isset($requestFilter['DATE_CREATE_from']) && $requestFilter['DATE_CREATE_from'])
		{
			$filter['>=DATE_CREATE'] = $requestFilter['DATE_CREATE_from'];
		}
		if (isset($requestFilter['DATE_CREATE_to']) && $requestFilter['DATE_CREATE_to'])
		{
			$filter['<=DATE_CREATE'] = $requestFilter['DATE_CREATE_to'];
		}

		if (isset($requestFilter['ACTIVE_CHANGE_DATE_from']) && $requestFilter['ACTIVE_CHANGE_DATE_from'])
		{
			$filter['>=ACTIVE_CHANGE_DATE'] = $requestFilter['ACTIVE_CHANGE_DATE_from'];
		}
		if (isset($requestFilter['ACTIVE_CHANGE_DATE_to']) && $requestFilter['ACTIVE_CHANGE_DATE_to'])
		{
			$filter['<=ACTIVE_CHANGE_DATE'] = $requestFilter['ACTIVE_CHANGE_DATE_to'];
		}
		if (isset($requestFilter['IS_CALLBACK_FORM']) && in_array($requestFilter['IS_CALLBACK_FORM'], ['Y', 'N'], true))
		{
			$filter['=IS_CALLBACK_FORM'] = $requestFilter['IS_CALLBACK_FORM'] === 'Y';
		}
		if (isset($requestFilter['IS_WHATSAPP_FORM']) && in_array($requestFilter['IS_WHATSAPP_FORM'], ['Y', 'N'], true))
		{
			$filter['=IS_WHATSAPP_FORM'] = $requestFilter['IS_WHATSAPP_FORM'] === 'Y';
		}
		if (isset($requestFilter['IS_SYSTEM']) && in_array($requestFilter['IS_SYSTEM'], ['Y', 'N'], true))
		{
			$filter['=IS_SYSTEM'] = $requestFilter['IS_SYSTEM'] === 'Y';
		}
		if (isset($requestFilter['ACTIVE']) && in_array($requestFilter['ACTIVE'], ['Y', 'N'], true))
		{
			$filter['=ACTIVE'] = $requestFilter['ACTIVE'] === 'Y';
		}
		if (isset($requestFilter['GET_PAYMENT']) && in_array($requestFilter['GET_PAYMENT'], ['Y', 'N'], true))
		{
			$filter['=IS_PAY'] = $requestFilter['GET_PAYMENT'] === 'Y';
		}

		if (!empty($requestFilter['PUBLINK']))
		{
			$formIdByPublink = null;

			// check for cloud link
			$codePrefix = Crm\Integration\Landing\FormLanding::LANDING_CODE_PRERIX;
			if (preg_match('#('.$codePrefix.'[a-zA-Z\d]+)#', $requestFilter['PUBLINK'], $matches))
			{
				$landingCode = $matches[1];
				$formIdByPublink = $this->getFormIdByLandingCode($landingCode);
			}

			// check for public link
			if (preg_match('#/pub/site/(\d+)/([a-zA-Z_\d]+)#', $requestFilter['PUBLINK'], $matches))
			{
				// $siteId = $matches[1];
				$landingCode = $matches[2];
				$formIdByPublink = $this->getFormIdByLandingCode($landingCode);
			}

			// check for form editor link
			if (!$formIdByPublink && preg_match('#/sites/site/(\d+)/view/(\d+)#', $requestFilter['PUBLINK'], $matches))
			{
				// $siteId = $matches[1];
				$landingId = $matches[2];
				$formIdByPublink = $this->getFormIdByLandingId((int)$landingId);
			}

			$filter['=ID'] = $formIdByPublink;
		}

		$integrations = $requestFilter['INTEGRATIONS'] ?? null;
		if ($integrations)
		{
			$filter['=ADS_OPTIONS.ADS_TYPE'] =  $integrations === 'N'
				? null
				: strtolower($integrations);
		}

		return $filter;
	}

	protected function getFilters()
	{
		$list = array(
			array(
				"id" => "ID",
				"name" => "ID",
				"default" => false,
			),
			array(
				"id" => "NAME",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_NAME'),
				"default" => false,
			),
			array(
				"id" => "ACTIVE_CHANGE_BY",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_ACTIVE_CHANGE_BY'),
				"default" => true,
				'type' => 'entity_selector',
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 240,
						'context' => 'filter',
						'entities' => [
							[
								'id' => 'user',
								'options' => [
									'inviteEmployeeLink' => false
								],
							],
							[
								'id' => 'department',
							]
						]
					],
				],
			),
			array(
				"id" => "DATE_CREATE",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_DATE_CREATE'),
				"type" => "date",
				"default" => true,
			),
			array(
				"id" => "ACTIVE_CHANGE_DATE",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_ACTIVE_CHANGE_DATE'),
				"type" => "date",
				"default" => true,
			),
			array(
				"id" => "ACTIVE",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_ACTIVE'),
				"type" => "checkbox",
				"default" => false,
			),
			array(
				"id" => "IS_SYSTEM",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_PRESET_SYSTEM'),
				"type" => "checkbox",
				"default" => false,
			),
			array(
				"id" => "IS_CALLBACK_FORM",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_PRESET_CALLABLE'),
				"type" => "checkbox",
				"default" => false,
			),
			[
				"id" => "GET_PAYMENT",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_PRESET_GET_PAYMENT'),
				'type' => 'checkbox',
				'default' => false,
			]
		);

		if (\Bitrix\Main\ModuleManager::isModuleInstalled('landing'))
		{
			$list[] = array(
				"id" => "PUBLINK",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_PRESET_PUBLINK'),
				"default" => false,
			);
		}

		if (WebForm\WhatsApp::canUse())
		{
			$list[] = array(
				"id" => "IS_WHATSAPP_FORM",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_PRESET_WHATSAPP'),
				"type" => "checkbox",
				"default" => false,
			);
		}

		$adTypes = $this->getIntegrationTypes();
		if ($adTypes)
		{
			$items = [
				"N" => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_CONVERSION_NONE'),
			];

			foreach ($adTypes as $adType => $adTypeName)
			{
				$adType = strtoupper($adType);
				$items[$adType] = Loc::getMessage('CRM_WEBFORM_LIST_FILTER_INTEGRATIONS_' . $adType);
			}

			$list[] = [
				"id" => "INTEGRATIONS",
				"name" => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_INTEGRATIONS'),
				"type" => "list",
				"items" => $items,
			];
		}

		return $list;
	}

	protected function getUiFilterPresets()
	{
		global $USER;

		$list = array(
			'active' => array(
				'name' => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_ACTIVE'),
				'fields' => array(
					'ACTIVE' => 'Y',
				),
			),
			'user_forms' => array(
				'name' => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_PRESET_MY_FORMS'),
				'fields' => array(
					'ACTIVE_CHANGE_BY' => $USER->GetID(),
				)
			),
			'system_fields' => array(
				'name' => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_PRESET_SYSTEM'),
				'fields' => array(
					'IS_SYSTEM' => 'Y',
				)
			),
			'callable_fields' => array(
				'name' => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_PRESET_CALLABLE'),
				'fields' => array(
					'IS_CALLBACK_FORM' => 'Y',
				)
			),
			'get_payment' => [
				'name' => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_PRESET_GET_PAYMENT'),
				'fields' => [
					'GET_PAYMENT' => 'Y',
					'ACTIVE' => 'Y',
				]
			]
		);

		if (WebForm\WhatsApp::canUse())
		{
			$list['whatsapp_fields'] = [
				'name' => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_PRESET_WHATSAPP'),
				'fields' => [
					'IS_WHATSAPP_FORM' => 'Y',
				]
			];
		}

		if (AdsForm::canUse())
		{
			$adTypes = $this->getIntegrationTypes();
			foreach ($adTypes as $adType => $adTypeName)
			{
				$adType = strtoupper($adType);
				$list[$adType] = [
					'name' => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_INTEGRATIONS_' . $adType),
					'fields' => [
						'INTEGRATIONS' => $adType,
					]
				];
			}
		}

		return $list;
	}

	protected function setUiFilterPresets()
	{
		$this->arResult['FILTER_PRESETS'] = $this->getUiFilterPresets();
	}

	protected static function getEntityCaption($entityName)
	{
		static $entities;
		if(!$entities)
		{
			$entities = Entity::getList();
		}

		return $entities[$entityName];
	}

	protected function checkInstalledPresets()
	{
		if(Preset::checkVersion())
		{
			$preset = new Preset();
			$preset->install();
		}
	}

	public function executeComponent()
	{
		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('CRM_WEBFORM_LIST_TITLE'));

		if (!$this->checkModules())
		{
			$this->showErrors();
			return;
		}

		/**@var $USER \CUser*/
		global $USER;
		$CrmPerms = new CCrmPerms($USER->GetID());
		if($CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE))
		{
			ShowError(Loc::getMessage('CRM_PERMISSION_DENIED'));
			return;
		}
		$this->arResult['PERM_CAN_EDIT'] = !$CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE, 'WRITE');

		$this->prepareParams();

		$this->checkInstalledPresets();
		$this->prepareResult();

		$this->includeComponentTemplate();

	}

	protected function checkModules()
	{
		if(!Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		if(!CAllCrmInvoice::installExternalEntities())
		{
			return false;
		}

		if(!CCrmQuote::LocalComponentCausedUpdater())
		{
			return false;
		}

		if(!Loader::includeModule('currency'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
			return false;
		}

		if(!Loader::includeModule('catalog'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
			return false;
		}

		if(!Loader::includeModule('sale'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
			return false;
		}

		return true;
	}

	protected function hasErrors()
	{
		return (count($this->errors) > 0);
	}

	protected function showErrors()
	{
		if(count($this->errors) <= 0)
		{
			return;
		}

		foreach($this->errors as $error)
		{
			ShowError($error);
		}
	}

	public function getUserInfo($userId)
	{
		static $users = array();

		if(!$userId)
		{
			return null;
		}

		if(!isset($users[$userId]) || !$users[$userId])
		{
			// prepare link to profile
			$replaceList = array('user_id' => $userId);
			$link = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER_PROFILE'], $replaceList);

			$userFields = \Bitrix\Main\UserTable::getRowById($userId);
			if(!$userFields)
			{
				return null;
			}

			// format name
			$userName = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'],
				array(
					'LOGIN' => $userFields['LOGIN'],
					'NAME' => $userFields['NAME'],
					'LAST_NAME' => $userFields['LAST_NAME'],
					'SECOND_NAME' => $userFields['SECOND_NAME']
				),
				true, false
			);

			// prepare icon
			$fileTmp = CFile::ResizeImageGet(
				$userFields['PERSONAL_PHOTO'],
				array('width' => 42, 'height' => 42),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);
			$userIcon = $fileTmp && isset($fileTmp['src']) ? $fileTmp['src'] : '';

			$users[$userId] = array(
				'ID' => $userId,
				'NAME' => $userName,
				'LINK' => $link,
				'ICON' => $userIcon
			);
		}

		return $users[$userId];
	}

	protected function getFormIdByLandingCode(string $landingCode): ?int
	{
		if (!Loader::includeModule('landing'))
		{
			return null;
		}

		$result = \Bitrix\Landing\Internals\LandingTable::query()
			->setSelect(['FORM_ID' => 'BCWL.FORM_ID'])
			->setFilter([
				'=CODE' => $landingCode,
				// '=SITE_ID' => (int)$siteId,
				// '=DELETED' => 'N',
			])
			->registerRuntimeField(
				'BCWL',
				(new Main\ORM\Fields\Relations\Reference(
					'BCWL',
					Internals\LandingTable::class,
					Main\ORM\Query\Join::on('this.ID', 'ref.LANDING_ID')
				))->configureJoinType(Main\ORM\Query\Join::TYPE_INNER),
			)
			->setLimit(1)
			->exec();

		if ($row = $result->fetch())
		{
			return (int)$row['FORM_ID'];
		}
		return null;
	}

	protected function getFormIdByLandingId(int $landingId): ?int
	{
		$row = Internals\LandingTable::getRow([
			'select' => ['FORM_ID'],
			'filter' => [
				'=LANDING_ID' => $landingId,
			]
		]);

		if ($row) {
			return (int)$row['FORM_ID'];
		}
		return null;
	}

	private function isPhoneVerificationRequired(int $formId): bool
	{
		if (!Main\Loader::includeModule('bitrix24'))
		{
			return false;
		}

		$validatedLicenseType = [
			'project',
			'demo'
		];

		return in_array(\CBitrix24::getLicenseType(), $validatedLicenseType, true)
			&& !(new PhoneVerify(WebForm\Form::PHONE_VERIFY_ENTITY, $formId))->isVerified();
	}
}

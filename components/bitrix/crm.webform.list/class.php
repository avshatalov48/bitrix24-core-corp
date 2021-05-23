<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\WebForm;
use Bitrix\Crm\WebForm\Internals;
use Bitrix\Crm\WebForm\Script;
use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\WebForm\Preset;
use Bitrix\Crm\WebForm\Entity;
use Bitrix\Crm\UI\Webpack;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;
use Bitrix\Crm\Ads\AdsForm;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);


class CCrmWebFormListComponent extends \CBitrixComponent
{
	protected $errors = array();

	public function prepareResult()
	{
		if ($this->request->get('rebuildResources') === 'y' || $this->request->get('rebuildAll') === 'y')
		{
			Webpack\Form::rebuildResources();
			if (
				\Bitrix\Main\Loader::includeModule('landing') &&
				is_callable(['\Bitrix\Landing\Subtype\Form', 'clearCache'])
			)
			{
				\Bitrix\Landing\Subtype\Form::clearCache();
			}
		}
		if ($this->request->get('rebuildForms') === 'y' || $this->request->get('rebuildAll') === 'y')
		{
			WebForm\Manager::updateScriptCache(null, 0);
		}

		/**@var \CUser $USER*/
		global $USER;

		/* ADS */
		$this->arResult['ADS_FORM'] = array();
		$this->arResult['ADS_FORM']['CAN_EDIT'] = AdsForm::canUserEdit($USER->GetID());
		$linkedAdsCrmForms = array();
		$adsTypes = AdsForm::getServiceTypes();
		foreach ($adsTypes as $adsType)
		{
			$linkedAdsCrmForms[$adsType] = AdsForm::getLinkedForms($adsType);
		}

		/* FILTER */
		$this->prepareFilter();


		$baseCurrencyId = \CCrmCurrency::GetBaseCurrencyID();
		$this->arResult['ITEMS'] = array();
		$filter = array();
		if (in_array($this->arResult['FILTER_ACTIVE_CURRENT'], array('N', 'Y')))
		{
			$filter['ACTIVE'] = $this->arResult['FILTER_ACTIVE_CURRENT'];
		}

		$dbForms = Internals\FormTable::getList(array(
			'filter' => $filter,
			'order' => array('ID' => 'DESC'),
			'cache' => array('ttl' => 36000)
		));
		while($form = $dbForms->fetch())
		{
			$counters = Form::getCounters($form['ID'], $form['ENTITY_SCHEME']);
			$form['ENTITY_COUNTERS'] = $counters['ENTITY'];
			$form['COUNT_START_FILL'] = intval($counters['COMMON']['START_FILL']);
			$form['COUNT_END_FILL'] = intval($counters['COMMON']['END_FILL']);
			$form['COUNT_VIEW'] = intval($counters['COMMON']['VIEWS']);
			$form['COUNT_QUIT_FILL'] = intval($form['COUNT_START_FILL'] - $form['COUNT_END_FILL']);
			if($form['COUNT_QUIT_FILL'] < 0)
			{
				$form['COUNT_QUIT_FILL'] = 0;
			}

			if($form['COUNT_START_FILL'] > 0)
			{
				$form['SUMMARY_CONVERSION'] = round($form['COUNT_END_FILL'] * 100 / $form['COUNT_START_FILL'], 2);
				$form['SUMMARY_CONVERSION'] = $form['SUMMARY_CONVERSION'] > 100 ? 100 : $form['SUMMARY_CONVERSION'];
			}
			else
			{
				$form['SUMMARY_CONVERSION'] = 0;
			}
			$form['SUMMARY_CONVERSION_DISPLAY'] = $form['SUMMARY_CONVERSION'] . '%';
			$form['SUMMARY_NUMBER_DISPLAY'] = $form['COUNT_END_FILL'];
			$form['SUMMARY_PRICE_DISPLAY'] = $counters['COMMON']['MONEY'];
			$form['CURRENCY_ID'] = $form['CURRENCY_ID'] ? $form['CURRENCY_ID'] : $baseCurrencyId;
			$form['SUMMARY_PRICE_DISPLAY'] = \CCrmCurrency::MoneyToString($form['SUMMARY_PRICE_DISPLAY'], $form['CURRENCY_ID']);

			$dateCreate = $form['DATE_CREATE'];
			/** @var DateTime $dateCreate */
			$form['DATE_CREATE_DISPLAY'] = $dateCreate ? $dateCreate->format(Date::getFormat()) : '';

			$activeChangeDate = $form['ACTIVE_CHANGE_DATE'];
			/** @var DateTime $activeChangeDate */
			if($activeChangeDate)
			{
				$form['DATE_CREATE_DISPLAY_TIME'] = $activeChangeDate->toUserTime()->format(IsAmPmMode() ? 'g:i a': 'H:i');
				$form['DATE_CREATE_DISPLAY_DATE'] = $activeChangeDate->format(Date::getFormat());
				$form['ACTIVE_CHANGE_DATE_DISPLAY'] = $form['DATE_CREATE_DISPLAY_TIME'] . ', '. $form['DATE_CREATE_DISPLAY_DATE'];
			}
			else
			{
				$form['DATE_CREATE_DISPLAY_TIME'] = '';
				$form['DATE_CREATE_DISPLAY_DATE'] = '';
				$form['DATE_CREATE_DISPLAY'] = '';
			}

			$form['ACTIVE_CHANGE_BY_DISPLAY'] = $this->getUserInfo($form['ACTIVE_CHANGE_BY']);
			$form['ACTIVE_CHANGE_BY_NOW_DISPLAY'] = $this->getUserInfo($USER->GetID());

			$replaceList = array('id' => $form['ID'], 'form_id' => $form['ID']);
			$form['PATH_TO_WEB_FORM_LIST'] = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_WEB_FORM_LIST'], $replaceList);
			$form['PATH_TO_WEB_FORM_EDIT'] = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_WEB_FORM_EDIT'], $replaceList);
			$form['PATH_TO_WEB_FORM_FILL'] = Script::getUrlContext($form, $this->arParams['PATH_TO_WEB_FORM_FILL']);

			$form['HAS_ADS_FORM_LINKS'] = false;
			$form['ADS_FORM'] = array();
			if (AdsForm::canUse())
			{
				$adsTypes = AdsForm::getServiceTypes();
				foreach ($adsTypes as $adsType)
				{
					$replaceList['ads_type'] = $adsType;
					$hasLinks = in_array($form['ID'], $linkedAdsCrmForms[$adsType]);
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

		$replaceListNew = array('id' => 0, 'form_id' => 0);
		$this->arResult['PATH_TO_WEB_FORM_NEW'] = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_WEB_FORM_EDIT'], $replaceListNew);
		$this->arResult['SHOW_PLUGINS'] = false;

		$this->arResult['USER_CONSENT_EMAIL'] = '';
		$this->arResult['HIDE_DESC_FZ152'] = true;

		if (Context::getCurrent()->getLanguage() == 'ru' && !(ModuleManager::isModuleInstalled('bitrix24') && \CBitrix24::getPortalZone() == 'ua'))
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

	protected function getFormCountByActivity($isActive = true)
	{
		$query = Internals\FormTable::query();

		$query->addSelect(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)'));
		$query->setFilter(array('ACTIVE' => $isActive ? 'Y' : 'N'));
		$query->setCacheTtl(36000);
		$result = $query->exec()->fetch();

		return $result['CNT'];
	}

	protected function prepareFilter()
	{
		$flt = $this->request->get('filter');
		$flt = is_array($flt) ? $flt : array();
		if (isset($flt['ACTIVE']))
		{
			\CUserOptions::SetOption('crm', 'webform_list_filter', array(
				'ACTIVE' => $flt['ACTIVE']
			));
		}

		$currentFilter = $this->arResult['CURRENT_FILTER'] = \CUserOptions::GetOption('crm', 'webform_list_filter', array());
		$currentFilterActive = $this->arResult['FILTER_ACTIVE_CURRENT'] = (isset($currentFilter['ACTIVE']) && in_array($currentFilter['ACTIVE'], array('N', 'Y'))) ? $currentFilter['ACTIVE'] : 'ALL';
		$this->arResult['FILTER'] = array(
			'ACTIVE' => array(
				'ALL' => array(
					'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_ACTIVE_ALL'),
					'SELECTED' => $currentFilterActive == 'ALL'
				),
				'Y' => array(
					'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_ACTIVE_Y') . ': ' . $this->getFormCountByActivity(true),
					'SELECTED' => $currentFilterActive == 'Y'
				),
				'N' => array(
					'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_FILTER_ACTIVE_N') . ': ' . $this->getFormCountByActivity(false),
					'SELECTED' => $currentFilterActive == 'N'
				),
			)
		);
		$this->arResult['FILTER_ACTIVE_CURRENT_NAME'] = Loc::getMessage('CRM_WEBFORM_LIST_FILTER_ACTIVE_' . $currentFilterActive);
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

	public function checkParams()
	{
		$this->arParams['IFRAME'] = isset($this->arParams['IFRAME']) ? $this->arParams['IFRAME'] : false;
		$this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams["NAME_TEMPLATE"]);
		Script::setPublicFormPath($this->arParams['PATH_TO_WEB_FORM_FILL']);

		return true;
	}

	public function getUserInfo($userId)
	{
		static $users = array();

		if(!$userId)
		{
			return null;
		}

		if(!$users[$userId])
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
				false
			);
			$userIcon = $fileTmp['src'];

			$users[$userId] = array(
				'ID' => $userId,
				'NAME' => $userName,
				'LINK' => $link,
				'ICON' => $userIcon
			);
		}

		return $users[$userId];
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
		if (!$this->checkModules())
		{
			$this->showErrors();
			return;
		}

		if (!$this->checkParams())
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

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('CRM_WEBFORM_LIST_TITLE'));

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
}
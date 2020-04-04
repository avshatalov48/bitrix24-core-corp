<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\Main\Engine\Contract\Controllerable,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\HttpApplication,
	\Bitrix\ImOpenlines\QuickAnswers;


class ImOpenLinesComponentLinesEdit extends CBitrixComponent implements Controllerable
{
	/** @var \Bitrix\ImOpenlines\Security\Permissions */
	protected $userPermissions;

	protected function checkModules()
	{
		if (!Loader::includeModule('imopenlines'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED'));
			return false;
		}
		if (!Loader::includeModule('im'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_IM_NOT_INSTALLED'));
			return false;
		}
		return true;
	}

	private function updateLine()
	{
		if (!\check_bitrix_sessid())
			return false;

		$request = HttpApplication::getInstance()->getContext()->getRequest();
		$post = $request->getPostList()->toArray();

		$configManager = new \Bitrix\ImOpenLines\Config();
		if (!$configManager->canEditLine($post['CONFIG_ID']))
			return false;

		$boolParams = Array(
			'ACTIVE',
			'CRM',
			'CRM_FORWARD',
			'CRM_TRANSFER_CHANGE',
			'CHECK_AVAILABLE',
			'RECORDING',
			'WORKTIME_ENABLE',
			'CATEGORY_ENABLE',
			'VOTE_MESSAGE',
			'VOTE_BEFORE_FINISH',
			'VOTE_CLOSING_DELAY',
			'WELCOME_MESSAGE',
			'WELCOME_BOT_ENABLE',
			'AGREEMENT_MESSAGE',
			'KPI_FIRST_ANSWER_ALERT',
			'KPI_FURTHER_ANSWER_ALERT',
			'KPI_CHECK_OPERATOR_ACTIVITY'
		);
		foreach ($boolParams as $field)
		{
			$post['CONFIG'][$field] = isset($post['CONFIG'][$field])? $post['CONFIG'][$field]: 'N';
		}

		$post['CONFIG']['WORKTIME_DAYOFF'] = isset($post['CONFIG']['WORKTIME_DAYOFF'])? $post['CONFIG']['WORKTIME_DAYOFF']: Array();

		if(empty($post['CONFIG']['LIMITATION_MAX_CHAT']) || !is_numeric($post['CONFIG']['MAX_CHAT']) || $post['CONFIG']['MAX_CHAT'] < 1)
		{
			$post['CONFIG']['MAX_CHAT'] = 0;
		}
		elseif($post['CONFIG']['MAX_CHAT'] > 150)
		{
			$post['CONFIG']['MAX_CHAT'] = 150;
		}
		else
		{
			$post['CONFIG']['MAX_CHAT'] = round($post['CONFIG']['MAX_CHAT']);
		}

		$queueList = Array();
		$queueUsersFields = Array();

		if (!empty($post['CONFIG']['QUEUE']['U']))
		{
			$arAccessCodes = Array();
			foreach ($post['CONFIG']['QUEUE']['U'] as $userCode)
			{
				$userId = substr($userCode, 1);
				if (\Bitrix\Im\User::getInstance($userId)->isExtranet())
					continue;

				$queueList[] = $userId;
				$arAccessCodes[] = $userCode;

				if (!empty($post['CONFIG']['QUEUE_USERS_FIELDS']['U'][$userCode]) && is_array($post['CONFIG']['QUEUE_USERS_FIELDS']['U'][$userCode]))
				{
					$queueUsersFields[$userId] = $post['CONFIG']['QUEUE_USERS_FIELDS']['U'][$userCode];
					if (substr($queueUsersFields[$userId]['USER_AVATAR'], 0, 1) == '/')
					{
						$queueUsersFields[$userId]['USER_AVATAR'] = \Bitrix\ImOpenLines\Common::getServerAddress() . $queueUsersFields[$userId]['USER_AVATAR'];
					}

					$avatarFromProfile = \Bitrix\ImOpenLines\Common::getServerAddress() . \Bitrix\Im\User::getInstance($userId)->getAvatar();
					if ($queueUsersFields[$userId]['USER_AVATAR'] === $avatarFromProfile)
					{
						$queueUsersFields[$userId]['USER_AVATAR'] = '';
					}
				}
			}
			\Bitrix\Main\FinderDestTable::merge(array(
				'CONTEXT' => 'IMOPENLINES',
				'CODE' => \Bitrix\Main\FinderDestTable::convertRights($arAccessCodes, array('U'.$GLOBALS['USER']->GetId()))
			));
		}

		$post['CONFIG']['QUEUE'] = $queueList;
		$post['CONFIG']['QUEUE_USERS_FIELDS'] = $queueUsersFields;
		$post['CONFIG']['TEMPORARY'] = 'N';
		$post['CONFIG']['WORKTIME_HOLIDAYS'] = explode(',', $post['CONFIG']['WORKTIME_HOLIDAYS']);

		$configManager = new \Bitrix\ImOpenLines\Config();
		$config = $configManager->get($post['CONFIG_ID']);
		if($config['TEMPORARY'] == 'Y' && !$configManager->canActivateLine())
		{
			$post['CONFIG']['ACTIVE'] = 'N';
		}

		if (!$configManager->update($post['CONFIG_ID'], $post['CONFIG']))
		{
			$this->arResult['ERROR'] = $configManager->getError()->msg;
		}
		else if ($request->getPost('action') == 'save')
		{
			if(empty($request['back_url']))
				LocalRedirect($this->arResult['PATH_TO_LIST']);
			else
				LocalRedirect(urldecode($request['back_url']));

			return false;
		}

		return true;
	}

	private function getWorkTimeConfig()
	{
		$params['TIME_ZONE_ENABLED'] = CTimeZone::Enabled();
		$params['TIME_ZONE_LIST'] = CTimeZone::GetZones();

		$params['WEEK_DAYS'] = Array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');

		$params['WORKTIME_LIST_FROM'] = array();
		$params['WORKTIME_LIST_TO'] = array();
		if (\Bitrix\Main\Loader::includeModule('calendar'))
		{
			$params['WORKTIME_LIST_FROM'][strval(0)] = CCalendar::FormatTime(0, 0);
			for ($i = 0; $i < 24; $i++)
			{
				if ($i !== 0)
				{
					$params['WORKTIME_LIST_FROM'][strval($i)] = CCalendar::FormatTime($i, 0);
					$params['WORKTIME_LIST_TO'][strval($i)] = CCalendar::FormatTime($i, 0);
				}
				$params['WORKTIME_LIST_FROM'][strval($i).'.30'] = CCalendar::FormatTime($i, 30);
				$params['WORKTIME_LIST_TO'][strval($i).'.30'] = CCalendar::FormatTime($i, 30);
			}
			$params['WORKTIME_LIST_TO'][strval('23.59')] = CCalendar::FormatTime(23, 59);
		}

		return $params;
	}

	private function getQueueDestination()
	{
		if (!\Bitrix\Main\Loader::includeModule('socialnetwork'))
			return Array();

		$structure = CSocNetLogDestination::GetStucture(array('LAZY_LOAD' => true));
		// TODO filter non-business users

		$destination = array(
			'LINE_ID' => $this->arResult['CONFIG']['ID'],
			'DEST_SORT' => CSocNetLogDestination::GetDestinationSort(array(
				'DEST_CONTEXT' => 'IMOPENLINES',
				'CODE_TYPE' => 'U'
			)),
			'LAST' => array(),
			'DEPARTMENT' => $structure['department'],
			'SELECTED' => array(
				'USERS' => array_values($this->arResult['CONFIG']['QUEUE'])
			),
		);
		CSocNetLogDestination::fillLastDestination($destination['DEST_SORT'], $destination['LAST']);

		$destinationUsers = array_values($this->arResult['CONFIG']['QUEUE']);
		if (isset($destination['LAST']['USERS']))
		{
			foreach ($destination['LAST']['USERS'] as $value)
				$destinationUsers[] = str_replace('U', '', $value);
		}
		$destination['EXTRANET_USER'] = 'N';
		$destination['USERS'] = CSocNetLogDestination::GetUsers(Array('id' => $destinationUsers));
		$queueUsersFields = array();
		foreach ($this->arResult['CONFIG']['QUEUE_USERS_FIELDS'] as $key => $userFields)
		{
			if (empty($userFields['USER_AVATAR']))
			{
				$userFields['USER_AVATAR'] = \Bitrix\Im\User::getInstance($key)->getAvatar();
			}
			$queueUsersFields['U'.$key] = $userFields;
		}
		$destination['QUEUE_USERS_FIELDS'] = $queueUsersFields;

		return $destination;
	}

	private function getPagesMenu($pathToList, $configId)
	{
		$menuList = array(
			'queue-crm' => array(
				'PAGE' => 'queue-crm.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_QUEUE')
			),
			'work-time' => array(
				'PAGE' => 'work-time.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_WORKTIME')
			),
			'agreements' => array(
				'PAGE' => 'agreements.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_AGREEMENTS')
			),
			'automatic-actions' => array(
				'PAGE' => 'automatic-actions.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_AUTOMATIC_ACTIONS')
			),
			'quality-mark' => array(
				'PAGE' => 'quality-mark.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_QUALITY_MARK')
			),
			'bots' => array(
				'PAGE' => 'bots.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_BOTS')
			),
			'kpi' => array(
				'PAGE' => 'kpi.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_KPI')
			),
			'others' => array(
				'PAGE' => 'others.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_OTHERS')
			)
		);
		$menuItemBase = $pathToList . 'edit.php?ID=' . $configId;
		if ($this->request['IFRAME'] === 'Y')
		{
			$menuItemBase .= '&IFRAME=Y';
		}

		foreach ($menuList as $code => &$menuItem)
		{
			$menuItem['ATTRIBUTES']['HREF'] =  $menuItemBase . '&PAGE=' . $code;
		}

		return $menuList;
	}

	private function showConfig()
	{
		$request = HttpApplication::getInstance()->getContext()->getRequest();

		$configManager = new \Bitrix\ImOpenLines\Config();
		$configId = intval($request->get('ID'));
		if ($configId == 0)
		{
			if(!$configManager->canActivateLine())
			{
				\ShowError(Loc::getMessage('OL_COMPONENT_LE_ERROR_PERMISSION'));
				return false;
			}

			if(!$this->userPermissions->canPerform(\Bitrix\ImOpenlines\Security\Permissions::ENTITY_LINES, \Bitrix\ImOpenlines\Security\Permissions::ACTION_MODIFY))
			{
				\ShowError(Loc::getMessage('OL_COMPONENT_LE_ERROR_PERMISSION'));
				return false;
			}

			$configId = $configManager->create();
			if ($configId)
			{
				//LocalRedirect($this->arResult['PATH_TO_LIST'] . 'edit.php?ID='.$configId);
			}
			else
			{
				//LocalRedirect($this->arResult['PATH_TO_LIST']);
				\ShowError(Loc::getMessage('OL_COMPONENT_LE_ERROR_PERMISSION'));
				return false;
			}
		}

		if (!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_SOCIALNETWORK_NOT_INSTALLED'));
			return false;
		}

		if (!$configManager->canViewLine($configId))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_LE_ERROR_PERMISSION'));
			return false;
		}

		$config = $configManager->get($configId);
		if (!$config)
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_LE_ERROR_PERMISSION'));
			return false;
		}

		$this->arResult['CAN_EDIT'] = $configManager->canEditLine($configId);
		$this->arResult['CAN_EDIT_CONNECTOR'] = $configManager->canEditConnector($configId);
		$this->arResult['IS_CRM_INSTALLED'] = IsModuleInstalled('crm')? 'Y': 'N';

		$this->arResult['LANGUAGE_LIST'] = \Bitrix\Intranet\Util::getLanguageList();
		if (!$config['LANGUAGE_ID'])
		{
			$context = \Bitrix\Main\Context::getCurrent();
			$config['LANGUAGE_ID'] = $context !== null? $context->getLanguage(): 'en';
		}

		$config['WORKTIME_HOLIDAYS'] = implode(',', $config['WORKTIME_HOLIDAYS']);
		$this->arResult['CONFIG'] = $config;

		$this->arResult['QUEUE_DESTINATION'] = $this->getQueueDestination();

		$this->arResult['CRM_SOURCES'] = \Bitrix\Main\Loader::includeModule('crm')? CCrmStatus::GetStatusList('SOURCE'): Array();
		$this->arResult['CRM_SOURCES'] = ['create' => Loc::getMessage('OL_COMPONENT_LE_CRM_SOURCE_CREATE')]+$this->arResult['CRM_SOURCES'];

		$this->arResult['BOT_LIST'] = [];
		if (\Bitrix\Main\Loader::includeModule('im'))
		{
			$list = \Bitrix\Im\Bot::getListCache(\Bitrix\Im\Bot::LIST_OPENLINE);
			foreach ($list as $botId => $botData)
			{
				$this->arResult['BOT_LIST'][$botId] = \Bitrix\Im\User::getInstance($botId)->getFullName();
			}

			if (\Bitrix\Main\Loader::includeModule('rest'))
			{
				$this->arResult['CAN_INSTALL_APPLICATIONS'] = \CRestUtil::canInstallApplication();
			}
		}

		$this->arResult['NO_ANSWER_RULES'] = [];
		if ($this->arResult['IS_CRM_INSTALLED'] == 'Y')
		{
			//$this->arResult['NO_ANSWER_RULES']['disabled'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_FORM');
		}
		$this->arResult['NO_ANSWER_RULES']['text'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_TEXT');
		$this->arResult['NO_ANSWER_RULES']['none'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_NONE');

		$this->arResult['SELECT_RULES'] = [];
		if ($this->arResult['IS_CRM_INSTALLED'] == 'Y')
		{
			//$this->arResult['SELECT_RULES']['disabled'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_FORM');
		}
		$this->arResult['SELECT_RULES']['text'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_TEXT');
		$this->arResult['SELECT_RULES']['none'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_NONE');

		$this->arResult['CLOSE_RULES'] = [];
		if ($this->arResult['IS_CRM_INSTALLED'] == 'Y')
		{
			//$this->arResult['CLOSE_RULES']['disabled'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_FORM');
		}
		//$this->arResult['CLOSE_RULES']['quality'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_QUALITY');
		$this->arResult['CLOSE_RULES']['text'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_TEXT');
		$this->arResult['CLOSE_RULES']['none'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_NONE');

		$workTimeConfig = $this->getWorkTimeConfig();
		$this->arResult['TIME_ZONE_ENABLED'] = $workTimeConfig['TIME_ZONE_ENABLED'];
		$this->arResult['TIME_ZONE_LIST'] = $workTimeConfig['TIME_ZONE_LIST'];
		$this->arResult['WEEK_DAYS'] = $workTimeConfig['WEEK_DAYS'];
		$this->arResult['WORKTIME_LIST_FROM'] = $workTimeConfig['WORKTIME_LIST_FROM'];
		$this->arResult['WORKTIME_LIST_TO'] = $workTimeConfig['WORKTIME_LIST_TO'];

		if (empty($this->arResult['CONFIG']['WORKTIME_TIMEZONE']))
		{
			if (LANGUAGE_ID == 'ru')
				$tzByLang = 'Europe/Moscow';
			elseif (LANGUAGE_ID == 'de')
				$tzByLang = 'Europe/Berlin';
			elseif (LANGUAGE_ID == 'ua')
				$tzByLang = 'Europe/Kiev';
			else
				$tzByLang = 'America/New_York';

			$this->arResult['CONFIG']['WORKTIME_TIMEZONE'] = $tzByLang;
		}

		$usersLimit = \Bitrix\Imopenlines\Limit::getLicenseUsersLimit();
		if ($usersLimit)
		{
			$this->arResult['BUSINESS_USERS'] = 'U'.implode(',U', $usersLimit);
			$this->arResult['BUSINESS_USERS_LIMIT'] = 'Y';
		}
		else
		{
			$this->arResult['BUSINESS_USERS'] = Array();
			$this->arResult['BUSINESS_USERS_LIMIT'] = 'N';
		}

		$quickAnswersStorageList = QuickAnswers\ListsDataManager::getStorageList();
		if($this->isCreateNewQuickAnswersStorageAllowed($this->arResult['CONFIG'], $quickAnswersStorageList))
		{
			$quickAnswersStorageList[0] = array('NAME' => Loc::getMessage('OL_COMPONENT_LE_QUICK_ANSWERS_STORAGE_CREATE'));
			ksort($quickAnswersStorageList);
			$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
			$this->arResult['QUICK_ANSWERS_MANAGE_URL'] = $uri->addParams(array('action' => 'imopenlines_create_qa_list'))->getLocator();
		}

		$this->arResult['QUICK_ANSWERS_STORAGE_LIST'] = $quickAnswersStorageList;
		if($this->arResult['CONFIG']['QUICK_ANSWERS_IBLOCK_ID'] > 0 && isset($quickAnswersStorageList[$this->arResult['CONFIG']['QUICK_ANSWERS_IBLOCK_ID']]))
		{
			$dataManager = new QuickAnswers\ListsDataManager($this->arResult['CONFIG']['ID']);
			$this->arResult['QUICK_ANSWERS_MANAGE_URL'] = $dataManager->getUrlToList();
		}
		else
		{
			$this->arResult['CONFIG']['QUICK_ANSWERS_IBLOCK_ID'] = 0;
		}

		$this->arResult['CONFIG_MENU'] = $this->getPagesMenu($this->arResult['PATH_TO_LIST'], $configId);

		$this->arResult['IFRAME'] = $request->get('IFRAME') == 'Y';
		$this->arResult['IS_ACTION'] = $request->get('action') != '';
		$this->arResult['IS_OPENED'] = $request->get('opened') === 'Y';
		$this->arResult['SHOW_QUEUE_SETTINGS'] = htmlspecialcharsbx($request->get('SHOW_QUEUE_SETTINGS'));
		$this->arResult['SHOW_AUTO_ACTION_SETTINGS'] = htmlspecialcharsbx($request->get('SHOW_AUTO_ACTION_SETTINGS'));
		$this->arResult['SHOW_WORKERS_TIME'] = htmlspecialcharsbx($request->get('SHOW_WORKERS_TIME'));
		$this->arResult['PAGE'] = (htmlspecialcharsbx($request->get('PAGE')) ? : 'queue-crm');

		$uri = new \Bitrix\Main\Web\Uri(htmlspecialchars_decode(POST_FORM_ACTION_URI));
		$uriParams['action-line'] = 'edit';
		$uriParams['rating-request'] = $this->arResult['CONFIG']['VOTE_MESSAGE'];
		$uri->addParams($uriParams);
		$this->arResult['ACTION_URI'] = htmlspecialcharsbx($uri->getUri());

		$kpiFirstAnswerElements = [
			0 => ['NAME' => '0 ' . Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_SECONDS'), 'VALUE' => 0],
			15 => ['NAME' => '15 ' . Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_SECONDS'), 'VALUE' => 15],
			30 => ['NAME' => '30 ' . Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_SECONDS'), 'VALUE' => 30],
			60 => ['NAME' => '60 ' . Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_SECONDS'), 'VALUE' => 60],
			120 => ['NAME' => '120 ' . Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_SECONDS'), 'VALUE' => 120],
			'CUSTOM' => [
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_SECONDS'),
				'TITLE' => Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_OWN'),
				'VALUE' => 0,
				'CUSTOM' => 'Y',
				'TYPE' => 'first'
			],
		];
		$kpiFurtherAnswerElements = [
			0 => ['NAME' => '0 ' . Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_MINUTES'), 'VALUE' => 0],
			1 => ['NAME' => '1 ' . Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_MINUTES_1'), 'VALUE' => 60],
			5 => ['NAME' => '5 ' . Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_MINUTES'), 'VALUE' => 300],
			10 => ['NAME' => '10 ' . Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_MINUTES'), 'VALUE' => 600],
			15 => ['NAME' => '15 ' . Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_MINUTES'), 'VALUE' => 900],
			'CUSTOM' => [
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_MINUTES'),
				'TITLE' => Loc::getMessage('OL_COMPONENT_LE_KPI_ANSWER_TIME_OWN'),
				'VALUE' => 0,
				'CUSTOM' => 'Y',
				'TYPE' => 'further'
			],
		];

		$this->arResult['KPI_MENU']['kpiFirstAnswer']['items'] = $kpiFirstAnswerElements;
		$customItem = $this->arResult['KPI_MENU']['kpiFirstAnswer']['items']['CUSTOM'];
		$customItem['VALUE'] = $this->arResult['CONFIG']['KPI_FIRST_ANSWER_TIME'];
		$customItem['NAME'] = $customItem['VALUE'] . ' ' . $customItem['NAME'];

		if (!in_array($customItem['VALUE'], array_keys($this->arResult['KPI_MENU']['kpiFirstAnswer']['items'])))
		{
			$this->arResult['KPI_MENU']['kpiFirstAnswer']['currentTitle'] = $customItem['NAME'];
		}
		else
		{
			$this->arResult['KPI_MENU']['kpiFirstAnswer']['currentTitle'] = $kpiFirstAnswerElements[$customItem['VALUE']]['NAME'];
		}
		$this->arResult['KPI_MENU']['kpiFirstAnswer']['items']['CUSTOM'] = $customItem;


		$this->arResult['KPI_MENU']['kpiFurtherAnswer']['items'] = $kpiFurtherAnswerElements;
		$customItem = $this->arResult['KPI_MENU']['kpiFurtherAnswer']['items']['CUSTOM'];
		$customItem['VALUE'] = $this->arResult['CONFIG']['KPI_FURTHER_ANSWER_TIME'] / 60;
		$customItem['NAME'] = $customItem['VALUE'] . ' ' . $customItem['NAME'];

		if (!in_array($customItem['VALUE'], array_keys($this->arResult['KPI_MENU']['kpiFurtherAnswer']['items'])))
		{
			$this->arResult['KPI_MENU']['kpiFurtherAnswer']['currentTitle'] = $customItem['NAME'];
		}
		else
		{
			$this->arResult['KPI_MENU']['kpiFurtherAnswer']['currentTitle'] = $kpiFurtherAnswerElements[$customItem['VALUE']]['NAME'];
		}
		$this->arResult['KPI_MENU']['kpiFurtherAnswer']['items']['CUSTOM'] = $customItem;

		if (is_null($this->arResult['CONFIG']['KPI_FIRST_ANSWER_TEXT']))
		{
			$this->arResult['CONFIG']['KPI_FIRST_ANSWER_TEXT'] = Loc::getMessage('OL_COMPONENT_KPI_FIRST_ANSWER_TEXT');
		}

		if (is_null($this->arResult['CONFIG']['KPI_FURTHER_ANSWER_TEXT']))
		{
			$this->arResult['CONFIG']['KPI_FURTHER_ANSWER_TEXT'] = Loc::getMessage('OL_COMPONENT_KPI_FURTHER_ANSWER_TEXT');
		}

		if (empty($this->arResult['CONFIG']['KPI_FIRST_ANSWER_LIST']) ||
			!is_array($this->arResult['CONFIG']['KPI_FIRST_ANSWER_LIST'])
		)
		{
			$this->arResult['CONFIG']['KPI_FIRST_ANSWER_LIST'] = [];
		}

		if (empty($this->arResult['CONFIG']['KPI_FURTHER_ANSWER_LIST']) ||
			!is_array($this->arResult['CONFIG']['KPI_FURTHER_ANSWER_LIST'])
		)
		{
			$this->arResult['CONFIG']['KPI_FURTHER_ANSWER_LIST'] = [];
		}

		//Visible block
		if($this->arResult['CONFIG']['QUEUE_TYPE'] == Config::QUEUE_TYPE_ALL)
		{
			$this->arResult['VISIBLE']['QUEUE_TIME'] = false;
			$this->arResult['VISIBLE']['LIMITATION_MAX_CHAT'] = false;
			$this->arResult['VISIBLE']['MAX_CHAT'] = false;
		}
		else
		{
			$this->arResult['VISIBLE']['QUEUE_TIME'] = true;
			$this->arResult['VISIBLE']['LIMITATION_MAX_CHAT'] = true;
			if(empty($this->arResult['CONFIG']['MAX_CHAT']) || $this->arResult['CONFIG']['MAX_CHAT'] < 1)
			{
				$this->arResult['VISIBLE']['MAX_CHAT'] = false;
			}
			else
			{
				$this->arResult['VISIBLE']['MAX_CHAT'] = true;
			}
		}
		//END Visible block

		$this->includeComponentTemplate();

		return true;
	}

	public function executeComponent()
	{
		global $APPLICATION;

		$this->includeComponentLang('class.php');

		if (!$this->checkModules())
		{
			return false;
		}

		$this->userPermissions = \Bitrix\ImOpenlines\Security\Permissions::createWithCurrentUser();

		$this->arResult['PATH_TO_LIST'] = \Bitrix\ImOpenLines\Common::getPublicFolder() . 'list/';

//		CModule::includeModule('crm');
//		$contactCenterHandler = new \Bitrix\Intranet\ContactCenter();
//		$forms = $contactCenterHandler->crmGetItems()->getData()['form']['LIST'];
//		$this->arResult['CRM_FORMS_LIST'] = $forms;

		$request = HttpApplication::getInstance()->getContext()->getRequest();
		if($request->getQuery('action') == 'imopenlines_create_qa_list' && $request->getQuery('ID') > 0)
		{
			if(!$this->createQuickAnswersStorage())
			{
				$this->showConfig();
			}
		}
		if ($request->isPost() && $request->getPost('form') == 'imopenlines_edit_form')
		{
			if (!$this->updateLine())
			{
				\ShowError(Loc::getMessage('OL_COMPONENT_LE_ERROR_PERMISSION'));
				return false;
			}
		}

		return $this->showConfig();
	}

	protected function isCreateNewQuickAnswersStorageAllowed($config, $storageList = null)
	{
		if(intval($config['QUICK_ANSWERS_IBLOCK_ID']) <= 0)
		{
			return true;
		}
		$configsWithTheSameQuickAnswersStorage = \Bitrix\ImOpenLines\Model\ConfigTable::getCount(array('=QUICK_ANSWERS_IBLOCK_ID' => $config['QUICK_ANSWERS_IBLOCK_ID']));
		if($configsWithTheSameQuickAnswersStorage > 1)
		{
			return true;
		}
		if($storageList === null)
		{
			$storageList = QuickAnswers\ListsDataManager::getStorageList();
		}
		if(!isset($storageList[$config['QUICK_ANSWERS_IBLOCK_ID']]))
		{
			return true;
		}

		return false;
	}

	protected function createQuickAnswersStorage()
	{
		$request = HttpApplication::getInstance()->getContext()->getRequest();
		$configManager = new \Bitrix\ImOpenLines\Config();
		if(!$configManager->canEditLine($request->getQuery('ID')))
		{
			$this->arResult['ERROR'] = Loc::getMessage('OL_COMPONENT_LE_QUICK_ANSWERS_NO_ACCESS_CREATE');
			return false;
		}
		$lineId = intval($request->getQuery('ID'));
		$config = $configManager->get($lineId);
		if($config && $this->isCreateNewQuickAnswersStorageAllowed($config))
		{
			global $USER;
			$iblockId = QuickAnswers\ListsDataManager::createStorage($lineId, $USER->GetID());
			if($iblockId > 0)
			{
				$configManager->update($lineId, array('QUICK_ANSWERS_IBLOCK_ID' => $iblockId));
				$listsDataManager = new QuickAnswers\ListsDataManager($lineId);
				$newUrl = $listsDataManager->getUrlToList();
				LocalRedirect($newUrl);
				return true;
			}
			else
			{
				$this->arResult['ERROR'] = Loc::getMessage('OL_COMPONENT_LE_QUICK_ANSWERS_STORAGE_CREATE_ERROR');
			}
		}
		else
		{
			$this->arResult['ERROR'] = Loc::getMessage('OL_COMPONENT_LE_QUICK_ANSWERS_STORAGE_CREATE_ERROR_UNIQUE');
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return array();
	}

	/**
	 * Reload blocks using ajax-request
	 *
	 * @return array
	 */
	public function loadPageAction()
	{
		ob_start();
		$this->executeComponent();
		$html = ob_get_clean();
		return array(
			'html' => $html
		);
	}
};
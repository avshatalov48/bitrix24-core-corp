<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\HttpApplication,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Config\Configuration,
	\Bitrix\Main\Engine\Contract\Controllerable;

use \Bitrix\UI\EntitySelector,
	\Bitrix\UI\EntitySelector\Item;

use \Bitrix\Im;

use	\Bitrix\ImOpenLines\Queue,
	\Bitrix\ImOpenLines\Tools,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Common,
	\Bitrix\ImOpenLines\QueueManager,
	\Bitrix\ImOpenlines\QuickAnswers;

class ImOpenLinesComponentLinesEdit extends CBitrixComponent implements Controllerable
{
	/** @var \Bitrix\ImOpenlines\Security\Permissions */
	protected $userPermissions;

	protected const AVAILABLE_OPTION_TIME_TASK = [
		'10800',
		'25200',
		'43200',
		'172800',
		'345600',
		'518400',
		'1209600'
	];

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function checkModules()
	{
		$result = true;

		if (!Loader::includeModule('imopenlines'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED'));
			$result = false;
		}
		if (!Loader::includeModule('im'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_IM_NOT_INSTALLED'));
			$result = false;
		}

		return $result;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function updateLine(): bool
	{
		$result = false;

		$post = $this->request->getPostList()->toArray();

		if(
			!empty($post) &&
			\check_bitrix_sessid() &&
			Config::canEditLine($post['CONFIG_ID'])
		)
		{
			$boolParams = [
				'ACTIVE',
				'CRM',
				'CRM_FORWARD',
				'CRM_CHAT_TRACKER',
				'CRM_TRANSFER_CHANGE',
				'CRM_CREATE_THIRD',
				'CHECK_AVAILABLE',
				'RECORDING',
				'WORKTIME_ENABLE',
				'CATEGORY_ENABLE',
				'VOTE_MESSAGE',
				'VOTE_BEFORE_FINISH',
				'VOTE_CLOSING_DELAY',
				'WATCH_TYPING',
				'WELCOME_MESSAGE',
				'WELCOME_BOT_ENABLE',
				'AGREEMENT_MESSAGE',
				'KPI_FIRST_ANSWER_ALERT',
				'KPI_FURTHER_ANSWER_ALERT',
				'KPI_CHECK_OPERATOR_ACTIVITY'
			];
			foreach ($boolParams as $field)
			{
				$post['CONFIG'][$field] = isset($post['CONFIG'][$field])? $post['CONFIG'][$field]: 'N';
			}

			$post['CONFIG']['WORKTIME_DAYOFF'] = isset($post['CONFIG']['WORKTIME_DAYOFF'])? $post['CONFIG']['WORKTIME_DAYOFF']: [];

			if(
				empty($post['CONFIG']['LIMITATION_MAX_CHAT']) ||
				!is_numeric($post['CONFIG']['MAX_CHAT']) || $post['CONFIG']['MAX_CHAT'] < 1
			)
			{
				$post['CONFIG']['MAX_CHAT'] = 0;
			}
			elseif($post['CONFIG']['MAX_CHAT'] > Queue::MAX_CHAT)
			{
				$post['CONFIG']['MAX_CHAT'] = Queue::MAX_CHAT;
			}
			else
			{
				$post['CONFIG']['MAX_CHAT'] = round($post['CONFIG']['MAX_CHAT']);
			}

			$queueList = [];
			$queueUsersFields = [];

			if((string)$post['CONFIG']['OPERATOR_DATA'] !== 'queue')
			{
				unset($post['CONFIG']['QUEUE_USERS_FIELDS']);
			}

			if((string)$post['CONFIG']['OPERATOR_DATA'] !== 'hide')
			{
				$post['CONFIG']['DEFAULT_OPERATOR_DATA'] = [];
			}

			if(
				!empty($post['CONFIG']['QUEUE']) &&
				is_array($post['CONFIG']['QUEUE'])
			)
			{
				$userList = QueueManager::getUserListFromQueue($post['CONFIG']['QUEUE']);

				foreach ($post['CONFIG']['QUEUE'] as $entity)
				{
					if(QueueManager::validateQueueTypeField($entity['type']))
					{
						if(
							(string)$entity['type'] === 'user' &&
							QueueManager::isValidUser($entity['id'])
						)
						{
							$queueList[] = [
								'ENTITY_ID' => $entity['id'],
								'ENTITY_TYPE' => $entity['type'],
							];
						}
						elseif((string)$entity['type'] === 'department')
						{
							$queueList[] = [
								'ENTITY_ID' => $entity['id'],
								'ENTITY_TYPE' => $entity['type'],
							];
						}
					}
				}

				if(!empty($userList))
				{
					foreach ($userList as $userId)
					{
						if (
							!empty($post['CONFIG']['QUEUE_USERS_FIELDS'][$userId]) &&
							is_array($post['CONFIG']['QUEUE_USERS_FIELDS'][$userId])
						)
						{
							$queueUsersFields[$userId] = $post['CONFIG']['QUEUE_USERS_FIELDS'][$userId];
							if (mb_strpos($queueUsersFields[$userId]['USER_AVATAR'], '/') === 0)
							{
								$queueUsersFields[$userId]['USER_AVATAR'] = Common::getServerAddress() . $queueUsersFields[$userId]['USER_AVATAR'];
							}

							$avatarFromProfile = Common::getServerAddress() . Im\User::getInstance($userId)->getAvatar();
							if ($queueUsersFields[$userId]['USER_AVATAR'] === $avatarFromProfile)
							{
								$queueUsersFields[$userId]['USER_AVATAR'] = '';
							}
							if($queueUsersFields[$userId]['USER_NAME'] === Im\User::getInstance($userId)->getFullName(false))
							{
								$queueUsersFields[$userId]['USER_NAME'] = '';
							}
						}
					}
				}
			}

			if(
				!isset($post['CONFIG']['VOTE_ENABLE_TIME_LIMIT']) ||
				$post['CONFIG']['VOTE_ENABLE_TIME_LIMIT'] !== 'Y'
			)
			{
				$post['CONFIG']['VOTE_TIME_LIMIT'] = 0;
			}
			unset($post['CONFIG']['VOTE_ENABLE_TIME_LIMIT']);

			$post['CONFIG']['QUEUE'] = $queueList;
			$post['CONFIG']['QUEUE_USERS_FIELDS'] = $queueUsersFields;
			$post['CONFIG']['TEMPORARY'] = 'N';
			$post['CONFIG']['WORKTIME_HOLIDAYS'] = explode(',', $post['CONFIG']['WORKTIME_HOLIDAYS']);

			$configManager = new Config();
			$config = $configManager->get($post['CONFIG_ID']);

			if(
				(string)$config['TEMPORARY'] === 'Y' &&
				!Config::canActivateLine()
			)
			{
				$post['CONFIG']['ACTIVE'] = 'N';
			}

			if ($configManager->update($post['CONFIG_ID'], $post['CONFIG']))
			{
				if(
					empty($post['AUTOMATIC_MESSAGE']['ENABLE']) ||
					$post['AUTOMATIC_MESSAGE']['ENABLE'] !== 'Y'
				)
				{
					$configManager->deleteAllAutomaticMessage($post['CONFIG_ID']);
				}
				else
				{
					foreach ($post['AUTOMATIC_MESSAGE']['TASK'] as $cell=>$task)
					{
						if(!in_array($task['TIME_TASK'], self::AVAILABLE_OPTION_TIME_TASK, false))
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['TIME_TASK'] = current(self::AVAILABLE_OPTION_TIME_TASK);
						}

						if(Tools::isEmpty($task['MESSAGE']))
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['MESSAGE'] = Loc::getMessage('OL_COMPONENT_AUTOMATIC_MESSAGE_TITLE');
						}
						if(Tools::isEmpty($task['TEXT_BUTTON_CLOSE']))
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['TEXT_BUTTON_CLOSE'] = Loc::getMessage('OL_COMPONENT_AUTOMATIC_MESSAGE_CLOSE_TITLE');
						}
						if(Tools::isEmpty($task['LONG_TEXT_BUTTON_CLOSE']))
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['LONG_TEXT_BUTTON_CLOSE'] = Loc::getMessage('OL_COMPONENT_AUTOMATIC_MESSAGE_CLOSE_TITLE');
						}
						if(Tools::isEmpty($task['TEXT_BUTTON_CONTINUE']))
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['TEXT_BUTTON_CONTINUE'] = Loc::getMessage('OL_COMPONENT_AUTOMATIC_MESSAGE_CONTINUE_TITLE');
						}
						if(Tools::isEmpty($task['LONG_TEXT_BUTTON_CONTINUE']))
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['LONG_TEXT_BUTTON_CONTINUE'] = Loc::getMessage('OL_COMPONENT_AUTOMATIC_MESSAGE_CONTINUE_TITLE');
						}
						if(Tools::isEmpty($task['TEXT_BUTTON_NEW']))
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['TEXT_BUTTON_NEW'] = Loc::getMessage('OL_COMPONENT_AUTOMATIC_MESSAGE_NEW_TITLE');
						}
						if(Tools::isEmpty($task['LONG_TEXT_BUTTON_NEW']))
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['LONG_TEXT_BUTTON_NEW'] = Loc::getMessage('OL_COMPONENT_AUTOMATIC_MESSAGE_NEW_TITLE');
						}
					}

					$configManager->updateAllAutomaticMessage($post['CONFIG_ID'], $post['AUTOMATIC_MESSAGE']['TASK']);
				}

				$result = true;

				if ((string)$this->request->getPost('action') === 'save')
				{
					if(empty($this->request['back_url']))
					{
						LocalRedirect($this->arResult['PATH_TO_LIST']);
					}
					else
					{
						LocalRedirect(urldecode($this->request['back_url']));
					}
				}
			}
			else
			{
				$this->arResult['ERROR'] = $configManager->getError()->msg;
			}
		}

		return $result;
	}

	/**
	 * @return mixed
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function getWorkTimeConfig()
	{
		$params['TIME_ZONE_ENABLED'] = CTimeZone::Enabled();
		$params['TIME_ZONE_LIST'] = CTimeZone::GetZones();

		$params['WEEK_DAYS'] = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];

		$params['WORKTIME_LIST_FROM'] = [];
		$params['WORKTIME_LIST_TO'] = [];
		if (Loader::includeModule('calendar'))
		{
			$params['WORKTIME_LIST_FROM'][(string)0] = CCalendar::FormatTime(0, 0);
			for ($i = 0; $i < 24; $i++)
			{
				if ($i !== 0)
				{
					$params['WORKTIME_LIST_FROM'][(string)$i] = CCalendar::FormatTime($i, 0);
					$params['WORKTIME_LIST_TO'][(string)$i] = CCalendar::FormatTime($i, 0);
				}
				$params['WORKTIME_LIST_FROM'][$i.'.30'] = CCalendar::FormatTime($i, 30);
				$params['WORKTIME_LIST_TO'][$i.'.30'] = CCalendar::FormatTime($i, 30);
			}
			$params['WORKTIME_LIST_TO']['23.59'] = CCalendar::FormatTime(23, 59);
		}

		return $params;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function getQueue(): array
	{
		$result = [
			'lineId' => $this->arResult['CONFIG']['ID'],
			'readOnly' => !$this->arResult['CAN_EDIT'],
			'queueInputName' => 'CONFIG[QUEUE]',
			'queueUserInputName' => 'CONFIG[QUEUE_USERS_FIELDS]',
			'blockIdQueueInput' => 'input-config-queue',
			'popupDepartment' => [
				'nameOption' => [
					'category' => 'imopenlines',
					'name' => 'config',
					'nameValue' => 'disablesPopupDepartment'
				],
				'valueDisables' => false,
				'titleOption' => GetMessageJS('OL_COMPONENT_DISABLES_POPUP_HEAD_DEPARTMENT_EXCLUDED_QUEUE_TITLE')
			],
		];

		$configUserOptions = \CUserOptions::GetOption($result['popupDepartment']['nameOption']['category'], $result['popupDepartment']['nameOption']['name']);
		if(!empty($configUserOptions[$result['popupDepartment']['nameOption']['nameValue']]))
		{
			$result['popupDepartment']['valueDisables'] = $configUserOptions[$result['popupDepartment']['nameOption']['nameValue']] === 'N';
		}

		if (Loader::includeModule('ui'))
		{
			if(
				!empty($this->arResult['CONFIG']['QUEUE']) &&
				is_array($this->arResult['CONFIG']['QUEUE'])
			)
			{
				$preselectedItems = [];
				if(
					!empty($this->arResult['CONFIG']['configQueue']) &&
					is_array($this->arResult['CONFIG']['configQueue'])
				)
				{
					foreach ($this->arResult['CONFIG']['configQueue'] as $configQueue)
					{
						$preselectedItems[] = [
							$configQueue['ENTITY_TYPE'],
							$configQueue['ENTITY_ID']
						];
					}
				}

				$itemCollections = EntitySelector\Dialog::getSelectedItems($preselectedItems);

				$preselectedUsers = [];
				foreach ($this->arResult['CONFIG']['QUEUE'] as $userId)
				{
					$preselectedUsers[] = [
						'user',
						$userId
					];
				}

				//TODO: 279941 (426ad54dd7a8) socialnetwork
				$userCollections = EntitySelector\Dialog::getSelectedItems($preselectedUsers);
				$items = $userCollections->getAll();
				$users = [];

				foreach ($items as $item)
				{
					//$item = new \Bitrix\UI\EntitySelector\Item;
					$users[] = [
						'entityId' => $item->getId(),
						'entityType' => $item->getEntityId(),
						'name' => $item->getTitle(),
						'avatar' => $item->getAvatar(),
						'department' => $this->arResult['CONFIG']['QUEUE_FULL'][$item->getId()]['DEPARTMENT_ID']
					];
				}

				$result['queueUsers'] = $users;

				foreach ($this->arResult['CONFIG']['QUEUE_USERS_FIELDS'] as $key => $userFields)
				{
					if (empty($userFields['USER_AVATAR']))
					{
						$userFields['USER_AVATAR'] = \Bitrix\Im\User::getInstance($key)->getAvatar();
					}
					$result['queueUsersFields'][$key] = $userFields;
				}

				//TODO ui 20.400.0
				//$result['queueItems'] = $itemCollections->toArray();
				$result['queueItems'] = array_map(function(EntitySelector\Item $item) {
					return $item->jsonSerialize();
				}, $itemCollections->getAll());
			}
		}

		return $result;
	}

	/**
	 * @param $pathToList
	 * @param $configId
	 * @return array
	 */
	protected function getPagesMenu($pathToList, $configId)
	{
		$menuList = [
			'queue-crm' => [
				'PAGE' => 'queue-crm.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_QUEUE')
			],
			'work-time' => [
				'PAGE' => 'work-time.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_WORKTIME')
			],
			'agreements' => [
				'PAGE' => 'agreements.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_AGREEMENTS')
			],
			'automatic-actions' => [
				'PAGE' => 'automatic-actions.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_AUTOMATIC_ACTIONS')
			],
			'quality-mark' => [
				'PAGE' => 'quality-mark.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_QUALITY_MARK')
			],
			'bots' => [
				'PAGE' => 'bots.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_BOTS')
			],
			'kpi' => [
				'PAGE' => 'kpi.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_KPI')
			],
			'others' => [
				'PAGE' => 'others.php',
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_OTHERS')
			]
		];
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

	/**
	 * @param $config
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function getCrmFields($config): array
	{
		$result = [];
		$selected = Config::CRM_CREATE_NONE;
		$dealCategories = \Bitrix\ImOpenLines\Crm\Common::getDealCategories();
		$secondItemsDeal = [];
		if(
			$dealCategories->isSuccess() &&
			count($dealCategories->getData()) > 0)
		{
			foreach ($dealCategories->getData() as $category)
			{
				$secondItemsDeal[] = [
					'ID' => $category['ID'],
					'NAME' => $category['NAME'],
					'SELECT' => $config['CRM_CREATE_SECOND'] == $category['ID']
				];
			}
		}

		if($config['CRM_CREATE'] === Config::CRM_CREATE_LEAD)
		{
			$selected = Config::CRM_CREATE_LEAD;
		}
		elseif($config['CRM_CREATE'] === Config::CRM_CREATE_DEAL)
		{
			$selected = Config::CRM_CREATE_DEAL;
		}

		if($this->arResult['IS_CRM_INSTALLED'] === 'Y')
		{
			$result = [
				'CRM_CREATE_ITEMS' => [
					[
						'ID' => Config::CRM_CREATE_NONE,
						'NAME' => Loc::getMessage('OL_COMPONENT_CONFIG_EDIT_CRM_CREATE_IN_CHAT'),
						'SELECT' => $selected === Config::CRM_CREATE_NONE
					],
					[
						'ID' => Config::CRM_CREATE_LEAD,
						'NAME' => Loc::getMessage('OL_COMPONENT_CONFIG_EDIT_CRM_CREATE_LEAD'),
						'SELECT' => $selected === Config::CRM_CREATE_LEAD
					],
					[
						'ID' => Config::CRM_CREATE_DEAL,
						'NAME' => Loc::getMessage('OL_COMPONENT_CONFIG_EDIT_CRM_CREATE_DEAL'),
						'SELECT' => $selected === Config::CRM_CREATE_DEAL,
						'SECOND_ITEMS' => $secondItemsDeal,
						'SECOND_ITEMS_NAME' => Loc::getMessage('OL_COMPONENT_CONFIG_EDIT_CRM_CREATE_DEAL_SECOND'),
						'THIRD_SELECT' => $config['CRM_CREATE_THIRD'] !== 'N',
						'THIRD_NAME' => Loc::getMessage('OL_COMPONENT_CONFIG_EDIT_CRM_CREATE_DEAL_THIRD'),
					],
				],
				'VISIBLE' => [
					'SOURCE_DEAL_TITLE' => $selected === Config::CRM_CREATE_DEAL,
					'CRM_TRANSFER_CHANGE' => $selected !== Config::CRM_CREATE_NONE ? $selected : false,
				]
			];
		}

		return $result;
	}

	/**
	 * @param $configId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getConfigAutomaticMessage($configId): array
	{
		$result = [];

		$configManager = new Config();
		$configAutomaticMessage = $configManager->getAutomaticMessage($configId);

		if(count($configAutomaticMessage) > 0)
		{
			$result['ENABLE'] = 'Y';
			foreach ($configAutomaticMessage as $config)
			{
				$result['TASK'][$config['ID']] = [
					'TIME_TASK' => $config['TIME_TASK'],
					'MESSAGE' => $config['MESSAGE'],
					'TEXT_BUTTON_CLOSE' => $config['TEXT_BUTTON_CLOSE'],
					'LONG_TEXT_BUTTON_CLOSE' => $config['LONG_TEXT_BUTTON_CLOSE'],
					'AUTOMATIC_TEXT_CLOSE' => $config['AUTOMATIC_TEXT_CLOSE'],
					'TEXT_BUTTON_CONTINUE' => $config['TEXT_BUTTON_CONTINUE'],
					'LONG_TEXT_BUTTON_CONTINUE' => $config['LONG_TEXT_BUTTON_CONTINUE'],
					'AUTOMATIC_TEXT_CONTINUE' => $config['AUTOMATIC_TEXT_CONTINUE'],
					'TEXT_BUTTON_NEW' => $config['TEXT_BUTTON_NEW'],
					'LONG_TEXT_BUTTON_NEW' => $config['LONG_TEXT_BUTTON_NEW'],
					'AUTOMATIC_TEXT_NEW' => $config['AUTOMATIC_TEXT_NEW'],
				];
			}
		}
		else
		{
			$result['ENABLE'] = 'N';
			$result['TASK']['n1'] = [];
		}

		return $result;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function showConfig()
	{
		$configManager = new Config();
		$configId = (int)$this->request->get('ID');
		if ($configId === 0)
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

		if (!Loader::includeModule('socialnetwork'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_SOCIALNETWORK_NOT_INSTALLED'));
			return false;
		}

		if (!$configManager->canViewLine($configId))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_LE_ERROR_PERMISSION'));
			return false;
		}

		$config = $configManager->get($configId, true, true, true);

		if (!$config)
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_LE_ERROR_PERMISSION'));
			return false;
		}

		if(Configuration::getValue('ol_automatic_messages'))
		{
			$this->arResult['AUTOMATIC_MESSAGE'] = $this->getConfigAutomaticMessage($configId);
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

		$this->arResult['QUEUE'] = $this->getQueue();

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

			if (Loader::includeModule('rest'))
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
			{
				$tzByLang = 'Europe/Moscow';
			}
			elseif (LANGUAGE_ID == 'de')
			{
				$tzByLang = 'Europe/Berlin';
			}
			elseif (LANGUAGE_ID == 'ua')
			{
				$tzByLang = 'Europe/Kiev';
			}
			else
			{
				$tzByLang = 'America/New_York';
			}

			$this->arResult['CONFIG']['WORKTIME_TIMEZONE'] = $tzByLang;
		}

		$quickAnswersStorageList = QuickAnswers\ListsDataManager::getStorageList();
		if($this->isCreateNewQuickAnswersStorageAllowed($this->arResult['CONFIG'], $quickAnswersStorageList))
		{
			$quickAnswersStorageList[0] = array('NAME' => Loc::getMessage('OL_COMPONENT_LE_QUICK_ANSWERS_STORAGE_CREATE'));
			ksort($quickAnswersStorageList);
			$uri = new \Bitrix\Main\Web\Uri($this->request->getRequestUri());
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

		$this->arResult['IFRAME'] = $this->request->get('IFRAME') == 'Y';
		$this->arResult['IS_ACTION'] = $this->request->get('action') != '';
		$this->arResult['IS_OPENED'] = $this->request->get('opened') === 'Y';
		$this->arResult['SHOW_QUEUE_SETTINGS'] = htmlspecialcharsbx($this->request->get('SHOW_QUEUE_SETTINGS'));
		$this->arResult['SHOW_AUTO_ACTION_SETTINGS'] = htmlspecialcharsbx($this->request->get('SHOW_AUTO_ACTION_SETTINGS'));
		$this->arResult['SHOW_WORKERS_TIME'] = htmlspecialcharsbx($this->request->get('SHOW_WORKERS_TIME'));
		$this->arResult['PAGE'] = (htmlspecialcharsbx($this->request->get('PAGE')) ? : 'queue-crm');

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

		if((int)$this->arResult['CONFIG']['VOTE_TIME_LIMIT'] > 0)
		{
			$this->arResult['CONFIG']['VOTE_ENABLE_TIME_LIMIT'] = 'Y';
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

		$this->arResult['CRM'] = $this->getCrmFields($config);

		$this->includeComponentTemplate();

		return true;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function executeComponent()
	{
		$result = false;

		$this->includeComponentLang('class.php');

		if ($this->checkModules())
		{
			$this->userPermissions = \Bitrix\ImOpenlines\Security\Permissions::createWithCurrentUser();

			$this->arResult['PATH_TO_LIST'] = \Bitrix\ImOpenLines\Common::getPublicFolder() . 'list/';

			/*CModule::includeModule('crm');
			$contactCenterHandler = new \Bitrix\Intranet\ContactCenter();
			$forms = $contactCenterHandler->crmGetItems()->getData()['form']['LIST'];
			$this->arResult['CRM_FORMS_LIST'] = $forms;*/

			if(
				$this->request->getQuery('action') == 'imopenlines_create_qa_list' &&
				$this->request->getQuery('ID') > 0 &&
				!$this->createQuickAnswersStorage()
			)
			{
				$this->showConfig();
			}
			if (
				$this->request->isPost() &&
				$this->request->getPost('form') == 'imopenlines_edit_form' &&
				!$this->updateLine()
			)
			{
				\ShowError(Loc::getMessage('OL_COMPONENT_ERROR_SAVE_CONFIG'));
			}
			else
			{
				$result = $this->showConfig();
			}
		}

		return $result;
	}

	/**
	 * @param $config
	 * @param null $storageList
	 * @return bool
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
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

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function createQuickAnswersStorage()
	{
		$configManager = new \Bitrix\ImOpenLines\Config();
		if(!$configManager->canEditLine($this->request->getQuery('ID')))
		{
			$this->arResult['ERROR'] = Loc::getMessage('OL_COMPONENT_LE_QUICK_ANSWERS_NO_ACCESS_CREATE');
			return false;
		}
		$lineId = (int)$this->request->getQuery('ID');
		$config = $configManager->get($lineId);
		if($config && $this->isCreateNewQuickAnswersStorageAllowed($config))
		{
			global $USER;
			$iblockId = QuickAnswers\ListsDataManager::createStorage($lineId, $USER->GetID());
			if($iblockId > 0)
			{
				$configManager->update($lineId, ['QUICK_ANSWERS_IBLOCK_ID' => $iblockId]);
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
		return [];
	}

	/**
	 * Reload blocks using ajax-request
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function loadPageAction()
	{
		ob_start();
		$this->executeComponent();
		$html = ob_get_clean();
		return [
			'html' => $html
		];
	}
};
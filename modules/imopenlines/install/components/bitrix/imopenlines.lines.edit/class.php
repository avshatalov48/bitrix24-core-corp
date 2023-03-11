<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Engine\Contract\Controllerable;

use Bitrix\UI\EntitySelector;

use Bitrix\Im;

use Bitrix\Intranet;

use Bitrix\ImOpenLines\Crm;
use Bitrix\Imopenlines\Limit;
use Bitrix\ImOpenLines\Queue;
use Bitrix\ImOpenLines\Tools;
use Bitrix\ImOpenLines\Config;
use Bitrix\ImOpenLines\Common;
use Bitrix\ImOpenLines\QueueManager;
use Bitrix\ImOpenlines\QuickAnswers;
use Bitrix\ImOpenLines\Model\ConfigTable;
use Bitrix\ImOpenlines\Security\Permissions;

class ImOpenLinesComponentLinesEdit extends CBitrixComponent implements Controllerable
{
	/** @var Permissions */
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

	protected const BOOL_PARAMS_FORM = [
		'CHECK_AVAILABLE',
		'CHECK_ONLINE',
		'CRM',
		'CRM_CHAT_TRACKER',
		'CRM_CREATE_THIRD',
		'CRM_FORWARD',
		'CRM_TRANSFER_CHANGE',
		'WORKTIME_ENABLE',
		'AGREEMENT_MESSAGE',
		'WELCOME_MESSAGE',
		'SEND_WELCOME_EACH_SESSION',
		'WATCH_TYPING',
		'WELCOME_BOT_ENABLE',
		'KPI_FIRST_ANSWER_ALERT',
		'KPI_FURTHER_ANSWER_ALERT',
		'KPI_CHECK_OPERATOR_ACTIVITY',
		'ACTIVE',
		'RECORDING',
		'VOTE_MESSAGE',
		'VOTE_BEFORE_FINISH',
		'VOTE_CLOSING_DELAY',
		'VOTE_ENABLE_TIME_LIMIT',
		'USE_WELCOME_FORM',
		'WELCOME_FORM_DELAY',
		'CONFIRM_CLOSE',
		'IGNORE_WELCOME_FORM_RESPONSIBLE',

		'CATEGORY_ENABLE'
	];

	protected const REQUIRED_PARAMS_FORM = [
		'CRM_CREATE',
		'QUEUE_TYPE',
		'QUEUE_TIME',
		'OPERATOR_DATA',
		'NO_ANSWER_TIME',
		'FULL_CLOSE_TIME',
		'AUTO_CLOSE_TIME'
	];

	protected const ARRAY_PARAMS_FORM = [
		'KPI_FIRST_ANSWER_LIST',
		'KPI_FURTHER_ANSWER_LIST'
	];

	protected const OTHER_PARAMS_FORM = [
		'SESSION_PRIORITY',
		'CRM_CREATE_ITEMS',
		'CRM_CREATE_SECOND',
		'CRM_SOURCE',
		'WORKTIME_TIMEZONE',
		'WORKTIME_FROM',
		'WORKTIME_TO',
		'WORKTIME_DAYOFF',
		'WORKTIME_HOLIDAYS',
		'WORKTIME_DAYOFF_RULE',
		'WORKTIME_DAYOFF_TEXT',
		'AGREEMENT_ID',
		'WELCOME_MESSAGE_TEXT',
		'NO_ANSWER_RULE',
		'NO_ANSWER_FORM_ID',
		'NO_ANSWER_TEXT',
		'CLOSE_RULE',
		'CLOSE_TEXT',
		'AUTO_CLOSE_RULE',
		'AUTO_CLOSE_FORM_ID',
		'AUTO_CLOSE_TEXT',
		'QUICK_ANSWERS_IBLOCK_ID',
		'WELCOME_BOT_ID',
		'WELCOME_BOT_JOIN',
		'WELCOME_BOT_TIME',
		'WELCOME_BOT_LEFT',
		'KPI_FIRST_ANSWER_TIME',
		'KPI_FIRST_ANSWER_TEXT',
		'KPI_FURTHER_ANSWER_TIME',
		'KPI_FURTHER_ANSWER_TEXT',
		'LINE_NAME',
		'LANGUAGE_ID',
		'VOTE_TIME_LIMIT',
		'VOTE_MESSAGE_1_TEXT',
		'VOTE_MESSAGE_1_DISLIKE',
		'VOTE_MESSAGE_1_LIKE',
		'VOTE_MESSAGE_2_TEXT',
		'VOTE_MESSAGE_2_DISLIKE',
		'VOTE_MESSAGE_2_LIKE',
		'DEFAULT_OPERATOR_DATA',
		'QUEUE_USERS_FIELDS',
		'LIMITATION_MAX_CHAT'
	];

	/**
	 * @return bool
	 */
	protected function checkModules(): bool
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
	 */
	protected function updateLine(): bool
	{
		$result = false;

		$post = $this->request->getPostList()->toArray();

		if(empty($post))
		{
			$this->arResult['ERROR'][] = Loc::getMessage('OL_COMPONENT_ERROR_EMPTY_DATA_UPDATE');
		}
		elseif(!\check_bitrix_sessid())
		{
			$this->arResult['ERROR'][] = Loc::getMessage('OL_COMPONENT_ERROR_UPDATE_SESSION_EXPIRED');
		}
		elseif(!Config::canEditLine($post['CONFIG_ID']))
		{
			$this->arResult['ERROR'][] = Loc::getMessage('OL_COMPONENT_ERROR_NO_PERMISSION_FOR_UPDATE');
		}
		else
		{
			foreach (self::BOOL_PARAMS_FORM as $fieldId)
			{
				$post['CONFIG'][$fieldId] = $post['CONFIG'][$fieldId] ?? 'N';
			}

			$post['CONFIG']['WORKTIME_DAYOFF'] = $post['CONFIG']['WORKTIME_DAYOFF'] ?? [];

			if(
				empty($post['CONFIG']['LIMITATION_MAX_CHAT'])
				|| !is_numeric($post['CONFIG']['MAX_CHAT'])
				|| $post['CONFIG']['MAX_CHAT'] < 1
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
				!empty($post['CONFIG']['QUEUE'])
				&& is_array($post['CONFIG']['QUEUE'])
			)
			{
				$userList = QueueManager::getUserListFromQueue($post['CONFIG']['QUEUE']);

				foreach ($post['CONFIG']['QUEUE'] as $entity)
				{
					if(QueueManager::validateQueueTypeField($entity['type']))
					{
						if(
							(string)$entity['type'] === 'user'
							&& QueueManager::isValidUser($entity['id'])
						)
						{
							$queueList[] = [
								'ENTITY_ID' => $entity['id'],
								'ENTITY_TYPE' => $entity['type'],
							];
						}
						elseif ((string)$entity['type'] === 'department')
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
							!empty($post['CONFIG']['QUEUE_USERS_FIELDS'][$userId])
							&& is_array($post['CONFIG']['QUEUE_USERS_FIELDS'][$userId])
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
				!isset($post['CONFIG']['VOTE_ENABLE_TIME_LIMIT'])
				|| $post['CONFIG']['VOTE_ENABLE_TIME_LIMIT'] !== 'Y'
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

			$resultConfigUpdate = $configManager->update($post['CONFIG_ID'], $post['CONFIG']);

			if ($resultConfigUpdate->isSuccess())
			{
				if((int)$post['CONFIG']['MAX_CHAT'] === 0)
				{
					unset($this->arResult['CONFIG']['LIMITATION_MAX_CHAT']);
				}

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
						if(!isset($task['ACTIVE']))
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['ACTIVE'] = 'N';
						}
						else
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['ACTIVE'] = 'Y';
						}

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

						if(is_numeric($cell))
						{
							$post['AUTOMATIC_MESSAGE']['TASK'][$cell]['ID'] = $cell;
						}
					}

					$configManager->updateAllAutomaticMessage($post['CONFIG_ID'], $post['AUTOMATIC_MESSAGE']['TASK']);
				}

				$result = true;

				if ((string)$this->request->getPost('action') === 'apply')
				{
					if(empty($this->request['back_url']))
					{
						$uri = new Uri(Context::getCurrent()->getServer()->getRequestUri());
						$uri->addParams(['isSuccessSendForm' => 'Y']);
						LocalRedirect($uri->getUri());
					}
					else
					{
						LocalRedirect(urldecode($this->request['back_url']));
					}
				}
			}
			else
			{
				$errorCollection = $resultConfigUpdate->getErrorCollection();

				foreach ($errorCollection as $error)
				{
					$this->arResult['ERROR'][] = $error->getMessage();
				}
			}
		}

		return $result;
	}

	/**
	 * @return mixed
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

			$result['queueUsers'] = $this->getQueueUsers($this->arResult['CONFIG']['QUEUE']);

			if (!empty($this->arResult['CONFIG']['QUEUE_USERS_FIELDS']))
			{
				foreach ($this->arResult['CONFIG']['QUEUE_USERS_FIELDS'] as $key => $userFields)
				{
					if (empty($userFields['USER_AVATAR']))
					{
						$userFields['USER_AVATAR'] = Im\User::getInstance($key)->getAvatar();
					}
					$result['queueUsersFields'][$key] = $userFields;
				}
			}

			//TODO ui 20.400.0
			//$result['queueItems'] = $itemCollections->toArray();
			$result['queueItems'] = array_map(static function(EntitySelector\Item $item) {
				return $item->jsonSerialize();
			}, $itemCollections->getAll());
		}

		return $result;
	}

	/**
	 * @param array $ids
	 * @return array
	 */
	protected function getQueueUsers(array $ids): array
	{
		$users = [];

		$preselectedUsers = [];
		if(
			!empty($ids) &&
			is_array($ids)
		)
		{
			foreach ($ids as $userId)
			{
				$preselectedUsers[] = [
					'user',
					$userId
				];
			}
		}

		$userCollections = EntitySelector\Dialog::getSelectedItems($preselectedUsers);
		$items = $userCollections->getAll();

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

		return $users;
	}

	/**
	 * @param $pathToList
	 * @param $configId
	 * @return array
	 */
	protected function getPagesMenu($pathToList, $configId): array
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
				'NAME' => Loc::getMessage('OL_COMPONENT_LE_MENU_AGREEMENTS_1')
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
		$menuItemBase = $pathToList . 'lines_edit/?ID=' . $configId;
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
	 */
	protected function getCrmFields($config): array
	{
		$result = [];
		$selected = Config::CRM_CREATE_NONE;
		$dealCategories = Crm\Common::getDealCategories();
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
					'ACTIVE' => $config['ACTIVE'],
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
	 * @return array
	 */
	protected function getVisibleConfig($isErrorSaveForm = false): array
	{
		$result = [];

		if($this->arResult['CONFIG']['QUEUE_TYPE'] === Config::QUEUE_TYPE_ALL)
		{
			$result['QUEUE_TIME'] = false;
			$result['LIMITATION_MAX_CHAT'] = false;
			$result['MAX_CHAT'] = false;
		}
		else
		{
			$result['QUEUE_TIME'] = true;
			$result['LIMITATION_MAX_CHAT'] = true;
			if(
				(
					$isErrorSaveForm === false
					|| empty($this->arResult['CONFIG']['LIMITATION_MAX_CHAT'])
				)
				&& (
					empty($this->arResult['CONFIG']['MAX_CHAT'])
					|| $this->arResult['CONFIG']['MAX_CHAT'] < 1
				)
			)
			{
				$result['MAX_CHAT'] = false;
			}
			else
			{
				$result['MAX_CHAT'] = true;
			}
		}

		return $result;
	}

	protected function showKpi(): void
	{
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
	}

	/**
	 * @return bool
	 */
	protected function showConfig(): bool
	{
		if(!empty($this->request->get('isSuccessSendForm')))
		{
			$this->arResult['IS_SUCCESS_SEND_FORM'] = true;
		}
		else
		{
			$this->arResult['IS_SUCCESS_SEND_FORM'] = false;
		}

		$configManager = new Config();
		$configId = (int)$this->request->get('ID');

		if ($configId === 0)
		{
			if(!$configManager->canActivateLine())
			{
				\ShowError(Loc::getMessage('OL_COMPONENT_LE_ERROR_PERMISSION'));
				return false;
			}

			if(!$this->userPermissions->canPerform(Permissions::ENTITY_LINES, Permissions::ACTION_MODIFY))
			{
				\ShowError(Loc::getMessage('OL_COMPONENT_LE_ERROR_PERMISSION'));
				return false;
			}

			$configId = $configManager->create();
			if (!$configId)
			{
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
		$this->arResult['IS_CRM_INSTALLED'] = \Bitrix\Main\ModuleManager::isModuleInstalled('crm')? 'Y': 'N';

		$this->arResult['LANGUAGE_LIST'] = Intranet\Util::getLanguageList();
		if (!$config['LANGUAGE_ID'])
		{
			$context = Context::getCurrent();
			$config['LANGUAGE_ID'] = $context !== null? $context->getLanguage(): 'en';
		}

		$config['WORKTIME_HOLIDAYS'] = implode(',', $config['WORKTIME_HOLIDAYS']);
		$this->arResult['CONFIG'] = $config;

		$this->arResult['CRM_SOURCES'] = Loader::includeModule('crm')? CCrmStatus::GetStatusList('SOURCE'): Array();
		$this->arResult['CRM_SOURCES'] = ['create' => Loc::getMessage('OL_COMPONENT_LE_CRM_SOURCE_CREATE')]+$this->arResult['CRM_SOURCES'];

		$this->arResult['BOT_LIST'] = [];
		if (Loader::includeModule('im'))
		{
			$list = Im\Bot::getListCache(Im\Bot::LIST_OPENLINE);
			foreach ($list as $botId => $botData)
			{
				$this->arResult['BOT_LIST'][$botId] = Im\User::getInstance($botId)->getFullName();
			}

			if (Loader::includeModule('rest'))
			{
				$this->arResult['CAN_INSTALL_APPLICATIONS'] = \CRestUtil::canInstallApplication();
			}
		}

		$this->arResult['NO_ANSWER_RULES'] = [];
		if($this->arResult['IS_CRM_INSTALLED'] === 'Y')
		{
			//$this->arResult['NO_ANSWER_RULES']['disabled'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_FORM');
		}
		$this->arResult['NO_ANSWER_RULES']['text'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_TEXT');
		$this->arResult['NO_ANSWER_RULES']['none'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_NONE');

		$this->arResult['SELECT_RULES'] = [];
		if($this->arResult['IS_CRM_INSTALLED'] === 'Y')
		{
			//$this->arResult['SELECT_RULES']['disabled'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_FORM');
		}
		$this->arResult['SELECT_RULES']['text'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_TEXT');
		$this->arResult['SELECT_RULES']['none'] = Loc::getMessage('OL_COMPONENT_LE_OPTION_NONE');

		$this->arResult['CLOSE_RULES'] = [];
		if($this->arResult['IS_CRM_INSTALLED'] === 'Y')
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
			if (LANGUAGE_ID === 'ru')
			{
				$tzByLang = 'Europe/Moscow';
			}
			elseif (LANGUAGE_ID === 'de')
			{
				$tzByLang = 'Europe/Berlin';
			}
			elseif (LANGUAGE_ID === 'ua')
			{
				$tzByLang = 'Europe/Kiev';
			}
			else
			{
				$tzByLang = 'America/New_York';
			}

			$this->arResult['CONFIG']['WORKTIME_TIMEZONE'] = $tzByLang;
		}

		$this->arResult['CAN_USE_QUICK_ANSWERS'] = Limit::canUseQuickAnswers();
		if($this->arResult['CAN_USE_QUICK_ANSWERS'] === true)
		{
			$quickAnswersStorageList = QuickAnswers\ListsDataManager::getStorageList();
			if($this->isCreateNewQuickAnswersStorageAllowed($this->arResult['CONFIG'], $quickAnswersStorageList))
			{
				$quickAnswersStorageList[0] = ['NAME' => Loc::getMessage('OL_COMPONENT_LE_QUICK_ANSWERS_STORAGE_CREATE')];
				ksort($quickAnswersStorageList);
				$uri = new \Bitrix\Main\Web\Uri($this->request->getRequestUri());
				$this->arResult['QUICK_ANSWERS_MANAGE_URL'] = $uri->addParams(['action' => 'imopenlines_create_qa_list'])->getLocator();
			}

			$this->arResult['QUICK_ANSWERS_STORAGE_LIST'] = $quickAnswersStorageList;
			if(
				$this->arResult['CONFIG']['QUICK_ANSWERS_IBLOCK_ID'] > 0
				&& isset($quickAnswersStorageList[$this->arResult['CONFIG']['QUICK_ANSWERS_IBLOCK_ID']])
			)
			{
				$dataManager = new QuickAnswers\ListsDataManager($this->arResult['CONFIG']['ID']);
				$this->arResult['QUICK_ANSWERS_MANAGE_URL'] = $dataManager->getUrlToList();
			}
			else
			{
				$this->arResult['CONFIG']['QUICK_ANSWERS_IBLOCK_ID'] = 0;
			}
		}
		else
		{
			$this->arResult['QUICK_ANSWERS_STORAGE_LIST'] = [
				0 => ['NAME' => Loc::getMessage('OL_COMPONENT_LE_QUICK_ANSWERS_STORAGE_NOT_SELECTED')]
			];

			$this->arResult['CONFIG']['QUICK_ANSWERS_IBLOCK_ID'] = 0;
		}

		$this->arResult['CONFIG_MENU'] = $this->getPagesMenu($this->arResult['PATH_TO_LIST'], $configId);

		$this->arResult['IFRAME'] = $this->request->get('IFRAME') === 'Y';
		$this->arResult['IS_ACTION'] = $this->request->get('action') != '';
		$this->arResult['IS_OPENED'] = $this->request->get('opened') === 'Y';
		$this->arResult['SHOW_QUEUE_SETTINGS'] = htmlspecialcharsbx($this->request->get('SHOW_QUEUE_SETTINGS'));
		$this->arResult['SHOW_AUTO_ACTION_SETTINGS'] = htmlspecialcharsbx($this->request->get('SHOW_AUTO_ACTION_SETTINGS'));
		$this->arResult['SHOW_WORKERS_TIME'] = htmlspecialcharsbx($this->request->get('SHOW_WORKERS_TIME'));
		$this->arResult['PAGE'] = (htmlspecialcharsbx($this->request->get('PAGE')) ? : 'queue-crm');

		$uri = new Uri(htmlspecialchars_decode(POST_FORM_ACTION_URI));
		$uriParams['action-line'] = 'edit';
		$uriParams['rating-request'] = $this->arResult['CONFIG']['VOTE_MESSAGE'];
		$uri->addParams($uriParams);
		$uri->deleteParams(['isSuccessSendForm']);
		$this->arResult['ACTION_URI'] = htmlspecialcharsbx($uri->getUri());

		if((int)$this->arResult['CONFIG']['VOTE_TIME_LIMIT'] > 0)
		{
			$this->arResult['CONFIG']['VOTE_ENABLE_TIME_LIMIT'] = 'Y';
		}
		else
		{
			$this->arResult['CONFIG']['VOTE_ENABLE_TIME_LIMIT'] = 'N';
		}

		$this->arResult['CAN_WATCH_TYPING'] = \CPullOptions::GetQueueServerStatus() && \CPullOptions::GetPublishWebEnabled();

		return true;
	}

	protected function processNoSaveFormData(): void
	{
		$post = $this->request->getPostList()->toArray();

		//Required params
		foreach (self::REQUIRED_PARAMS_FORM as $id)
		{
			if(isset($post['CONFIG'][$id]))
			{
				$this->arResult['CONFIG'][$id] = $post['CONFIG'][$id];
			}
		}

		//Bool params
		foreach (self::BOOL_PARAMS_FORM as $id)
		{
			if(isset($post['CONFIG'][$id]))
			{
				$this->arResult['CONFIG'][$id] = $post['CONFIG'][$id];
			}
			else
			{
				$this->arResult['CONFIG'][$id] = 'N';
			}
		}

		//Bool params
		foreach (self::ARRAY_PARAMS_FORM as $id)
		{
			if(
				isset($post['CONFIG'][$id])
				&& is_array($post['CONFIG'][$id])
			)
			{
				$this->arResult['CONFIG'][$id] = $post['CONFIG'][$id];
			}
			else
			{
				$this->arResult['CONFIG'][$id] = [];
			}
		}

		//Other params
		foreach (self::OTHER_PARAMS_FORM as $id)
		{
			if(isset($post['CONFIG'][$id]))
			{
				$this->arResult['CONFIG'][$id] = $post['CONFIG'][$id];
			}
			else
			{
				unset($this->arResult['CONFIG'][$id]);
			}
		}

		//Special data processing
		if(isset($post['CONFIG']['LIMITATION_MAX_CHAT']))
		{
			if(isset($post['CONFIG']['MAX_CHAT']))
			{
				$this->arResult['CONFIG']['MAX_CHAT'] = (int)$post['CONFIG']['MAX_CHAT'];
			}
			else
			{
				$this->arResult['CONFIG']['MAX_CHAT'] = 0;
			}
			if(isset($post['CONFIG']['TYPE_MAX_CHAT']))
			{
				$this->arResult['CONFIG']['TYPE_MAX_CHAT'] = $post['CONFIG']['TYPE_MAX_CHAT'];
			}
			else
			{
				unset($this->arResult['CONFIG']['TYPE_MAX_CHAT']);
			}
		}
		else
		{
			unset($this->arResult['CONFIG']['TYPE_MAX_CHAT']);
			$this->arResult['CONFIG']['MAX_CHAT'] = 0;
		}

		//Automatic message
		if(
			isset($post['AUTOMATIC_MESSAGE']['ENABLE'])
			&& $post['AUTOMATIC_MESSAGE']['ENABLE'] === 'Y'
		)
		{
			$this->arResult['AUTOMATIC_MESSAGE'] = $post['AUTOMATIC_MESSAGE'];
		}
		else
		{
			$this->arResult['AUTOMATIC_MESSAGE']['ENABLE'] = 'N';
			unset($this->arResult['AUTOMATIC_MESSAGE']['TASK']);
		}

		//Queue
		unset($this->arResult['CONFIG']['configQueue'], $this->arResult['CONFIG']['queueUsers']);

		$usersId = [];

		if (!empty($post['CONFIG']['QUEUE']))
		{
			foreach ($post['CONFIG']['QUEUE'] as $queue)
			{
				$this->arResult['CONFIG']['configQueue'][] = [
					'ENTITY_TYPE' => $queue['type'],
					'ENTITY_ID' => $queue['id']
				];

				if($queue['type'] === 'user')
				{
					if(in_array($queue['id'], $usersId, false) === false)
					{
						$usersId[] = $queue['id'];
					}

				}
				elseif($queue['type'] === 'department')
				{
					$usersDepartment = QueueManager::getUsersDepartment($queue['id']);
					while ($userId = $usersDepartment->fetch()['ID'])
					{
						if(in_array($userId, $usersId, false) === false)
						{
							$usersId[] = $userId;
						}
					}
				}
			}
		}

		$this->arResult['CONFIG']['QUEUE'] = $usersId;
		//END Queue
	}

	/**
	 * @param $config
	 * @param null $storageList
	 * @return bool
	 */
	protected function isCreateNewQuickAnswersStorageAllowed($config, $storageList = null)
	{
		if((int)$config['QUICK_ANSWERS_IBLOCK_ID'] <= 0)
		{
			return true;
		}
		$configsWithTheSameQuickAnswersStorage = ConfigTable::getCount(['=QUICK_ANSWERS_IBLOCK_ID' => $config['QUICK_ANSWERS_IBLOCK_ID']]);
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
	 */
	protected function createQuickAnswersStorage(): bool
	{
		$configManager = new Config();
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
	public function configureActions(): array
	{
		return [];
	}

	/**
	 * Reload blocks using ajax-request
	 *
	 * @return array
	 */
	public function loadPageAction(): array
	{
		ob_start();
		$this->executeComponent();
		$html = ob_get_clean();

		return [
			'html' => $html
		];
	}

	/**
	 * @return mixed|void|null
	 */
	public function executeComponent()
	{
		$isSendForm = false;
		$this->includeComponentLang('class.php');

		if ($this->checkModules())
		{
			$this->userPermissions = Permissions::createWithCurrentUser();

			$this->arResult['PATH_TO_LIST'] = Common::getContactCenterPublicFolder();
			if (Loader::includeModule('crm'))
			{
				$this->arResult['CRM_INSTALLED'] = true;
				$this->arResult['CRM_FORMS_LIST'] = \Bitrix\Crm\WebForm\Manager::getActiveForms();
				$this->arResult['CRM_FORMS_CREATE_LINK'] = \Bitrix\Crm\WebForm\Manager::getEditUrl();
			}

			if(
				$this->request->getQuery('action') === 'imopenlines_create_qa_list'
				&& $this->request->getQuery('ID') > 0
			)
			{
				$this->createQuickAnswersStorage();
			}
			if (
				$this->request->isPost()
				&& $this->request->getPost('form') === 'imopenlines_edit_form'
			)
			{
				$resultUpdate = $this->updateLine();
				$isSendForm = true;

				if(
					$resultUpdate === false
					&& empty($this->arResult['ERROR'])
				)
				{
					$this->arResult['ERROR'][] = Loc::getMessage('OL_COMPONENT_ERROR_SAVE_CONFIG');
				}
			}

			$resultShowConfig = $this->showConfig();

			if($resultShowConfig === true)
			{
				if(
					$isSendForm === true
					&& !empty($this->arResult['ERROR'])
				)
				{
					$this->processNoSaveFormData();
					$this->arResult['VISIBLE'] = $this->getVisibleConfig(true);
				}
				else
				{
					$this->arResult['VISIBLE'] = $this->getVisibleConfig(false);
				}

				$this->showKpi();
				$this->arResult['QUEUE'] = $this->getQueue();
				$this->arResult['CRM'] = $this->getCrmFields($this->arResult['CONFIG']);

				$this->includeComponentTemplate();
			}
		}
	}
}

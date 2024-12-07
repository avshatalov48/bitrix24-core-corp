<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Loader,
	\Bitrix\Main\Web\Uri,
	\Bitrix\Main\Context,
	\Bitrix\Main\Web\Json,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\UI\EntitySelector;
use \Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Connector,
	\Bitrix\ImConnector\Component,
	\Bitrix\ImConnector\InfoConnectors;
use \Bitrix\ImOpenLines\Common,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Helper,
	\Bitrix\Imopenlines\Limit,
	\Bitrix\ImOpenlines\Security,
	\Bitrix\ImOpenlines\Model\QueueTable,
	\Bitrix\ImOpenlines\Security\Permissions;

class ImConnectorConnectorSettings extends \CBitrixComponent
{
	/** @var \Bitrix\ImOpenlines\Security\Permissions */
	protected $userPermissions;

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 */
	protected function checkModules(): bool
	{
		$result = false;

		if (
			Loader::includeModule('imopenlines') &&
			Loader::includeModule('imconnector')
		)
		{
			$result = true;
		}
		else
		{
			if(!Loader::includeModule('imopenlines'))
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_MODULE_IMOPENLINES_NOT_INSTALLED'));
			}
			if(!Loader::includeModule('imconnector'))
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_MODULE_IMCONNECTOR_NOT_INSTALLED_MSGVER_1'));
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function showList()
	{
		$allowedUserIds = Security\Helper::getAllowedUserIds(
			Security\Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Permissions::ENTITY_CONNECTORS, Permissions::ACTION_MODIFY)
		);
		$infoConnectors = InfoConnectors::getInfoConnectorsList();
		$statusList = Status::getInstanceAll();

		$limit = null;
		if (is_array($allowedUserIds))
		{
			$limit = [];
			$orm = QueueTable::getList([
				'filter' => [
					'=USER_ID' => $allowedUserIds
				]
			]);
			while ($row = $orm->fetch())
			{
				$limit[$row['CONFIG_ID']] = $row['CONFIG_ID'];
			}
		}

		$configManager = new Config();
		$result = $configManager->getList([
			'select' => [
				'ID',
				'NAME' => 'LINE_NAME',
				'IS_LINE_ACTIVE' => 'ACTIVE'
			],
			'filter' => ['=TEMPORARY' => 'N'],
			'order' => ['LINE_NAME']
		]);
		foreach ($result as $id => $config)
		{
			if (
				$limit !== null
				&& !isset($limit[$config['ID']])
				&& !in_array($config['MODIFY_USER_ID'], $allowedUserIds, false)
			)
			{
				unset($result[$id]);
				continue;
			}

			// getting status if connector is connected for the open line
			if (
				isset($statusList[$this->arResult['ID']], $statusList[$this->arResult['ID']][$config['ID']])
				&& ($status = $statusList[$this->arResult['ID']][$config['ID']])
				&& ($status instanceof Status)
				&& $status->isStatus()
			)
			{
				$config['STATUS'] = 1;
			}
			else
			{
				$config['STATUS'] = 0;
			}

			// getting connected channel name
			$channelInfo = $infoConnectors[$config['ID']];
			$channelName = '';
			try
			{
				if (
					($channelData = JSON::decode($channelInfo['DATA']))
					&& !empty($channelData[$this->arResult['ID']]['name'])
				)
				{
					$channelName = trim($channelData[$this->arResult['ID']]['name']);
				}
			}
			catch (\Bitrix\Main\SystemException $exception)
			{
			}

			if (!empty($channelName))
			{
				$config['NAME'] .= " ({$channelName})";
			}
			elseif ($config['STATUS'] === 1)
			{
				$connectedMessage = Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CONNECTED_CONNECTOR');
				$config['NAME'] .= " ({$connectedMessage})";
			}

			if (empty($this->arResult['LINE']) && $id === 0)
			{
				$config['ACTIVE'] = true;
			}
			elseif (!empty($this->arResult['LINE']) && $config['ID'] == $this->arResult['LINE'])
			{
				$config['ACTIVE'] = true;
			}

			$config['URL'] = str_replace(array('#ID#', '#LINE#'), [$this->arResult['ID'], $config['ID']], $this->arResult['PATH_TO_CONNECTOR_LINE']);

			if (
				!empty($this->request['group_orders']) ||
				//TODO: For iMessage
					(
						!empty($this->request['page_imess']) &&
						$this->request['page_imess'] === 'connection' &&
						!empty($this->request['business_id'])
					)
				//END iMessage
			)
			{
				$uri = new Uri($config['URL']);
				if(!empty($this->request['group_orders']))
				{
					$uri->addParams(['group_orders' => htmlspecialcharsbx($this->request['group_orders'])]);
				}

				//TODO: For iMessage
				if(
					!empty($this->request['page_imess']) &&
					$this->request['page_imess'] === 'connection' &&
					!empty($this->request['business_id'])
				)
				{
					$uri->addParams(['business_id' => htmlspecialcharsbx($this->request['business_id'])]);
					$uri->addParams(['page_imess' => htmlspecialcharsbx($this->request['page_imess'])]);

					if(!empty($this->request['business_name']))
					{
						$uri->addParams(['business_name' => htmlspecialcharsbx($this->request['business_name'])]);
					}
				}
				//END iMessage
				$config['URL'] = $uri->getUri();
			}

			if (!empty($config['ACTIVE']))
			{
				$this->arResult['ACTIVE_LINE'] = $config;

				$this->arResult['ACTIVE_LINE']['URL_EDIT'] = str_replace('#ID#', $config['ID'], $this->arResult['PATH_TO_EDIT']);

				$this->arResult['QUEUE'] = $this->getQueue();
			}

			$result[$id] = $config;
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected function getQueue(): array
	{
		$result = [
			'lineId' => $this->arResult['ACTIVE_LINE']['ID'],
			'readOnly' => !$this->arResult['CAN_CHANGE_USERS'],
			'popupDepartment' => [
				'nameOption' => [
					'category' => 'imopenlines',
					'name' => 'config',
					'nameValue' => 'disablesPopupDepartment'
				],
				'valueDisables' => false,
				'titleOption' => GetMessage('IMCONNECTOR_COMPONENT_CONNECTOR_DISABLES_POPUP_HEAD_DEPARTMENT_EXCLUDED_QUEUE_TITLE')
			],
		];

		$configUserOptions = \CUserOptions::GetOption($result['popupDepartment']['nameOption']['category'], $result['popupDepartment']['nameOption']['name']);
		if(!empty($configUserOptions[$result['popupDepartment']['nameOption']['nameValue']]))
		{
			$result['popupDepartment']['valueDisables'] = $configUserOptions[$result['popupDepartment']['nameOption']['nameValue']] === 'N';
		}

		$configManager = new Config();
		$config = $configManager->get($this->arResult['ACTIVE_LINE']['ID'], false, true, true);

		if (Loader::includeModule('ui'))
		{
			$preselectedItems = [];
			if(
				!empty($config['configQueue']) &&
				is_array($config['configQueue'])
			)
			{
				foreach ($config['configQueue'] as $configQueue)
				{
					$preselectedItems[] = [
						$configQueue['ENTITY_TYPE'],
						$configQueue['ENTITY_ID']
					];
				}
			}

			$itemCollections = EntitySelector\Dialog::getSelectedItems($preselectedItems);

			//TODO ui 20.400.0
			//$result['queueItems'] = $itemCollections->toArray();
			$result['queueItems'] = array_map(function(EntitySelector\Item $item) {
				return $item->jsonSerialize();
			}, $itemCollections->getAll());
		}

		return $result;
	}

	/**
	 * @return mixed|void|null
	 */
	public function executeComponent()
	{
		global $APPLICATION;

		$this->includeComponentLang('class.php');
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix/imconnector.settings.status/class.php');
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix/imconnector.settings/class.php');

		if($this->checkModules())
		{
			Connector::initIconCss();

			$this->arResult['PUBLIC_PATH'] = Common::getContactCenterPublicFolder();

			if(empty($this->arParams['connector']))
			{
				$this->arResult['ID'] = $this->request['ID'];
			}
			else
			{
				$this->arResult['ID'] = $this->arParams['connector'];
			}

			$this->arResult['ID'] = Connector::getConnectorRealId($this->arResult['ID']);
			$this->arResult['SHOW_LIST_LINES'] = $this->request['LINE_SETTING'] !== 'Y';

			if(!empty($this->arResult['ID']) && Connector::isConnector($this->arResult['ID']))
			{
				if(
					$this->request['reload'] === 'y' ||
					$this->request['reload'] === 'Y'
				)
				{
					CUtil::InitJSCore(['ajax' , 'popup' ]);

					$uri = new Uri(Context::getCurrent()->getServer()->getRequestUri());

					$this->arResult['RELOAD'] = $this->request['ajaxid'];
					$uri->deleteParams(['reload', 'ajaxid']);
					$uri->addParams(['bxajaxid' => $this->arResult['RELOAD']]);
					$this->arResult['URL_RELOAD'] = $uri->getUri();
				}
				else
				{
					$this->arResult['CAN_CHANGE_USERS'] = Config::canEditLine($this->request['LINE']);
					$this->userPermissions = Permissions::createWithCurrentUser();

					if (!empty($this->request['LINE']))
					{
						$configManager = new Config();
						if ($configManager->get($this->request['LINE']))
						{
							$this->arResult['LINE'] = $this->request['LINE'];
						}
						else
						{
							foreach ($this->showList() as $line)
							{
								if (!empty($line['ID']))
								{
									$this->arResult['LINE'] = $line['ID'];
									break;
								}
							}
						}
					}
					$listComponentConnector = Connector::getListComponentConnector();
					$this->arResult['COMPONENT'] = $listComponentConnector[$this->arResult['ID']];
					$this->arResult['NAME'] = Connector::getNameConnectorReal($this->arResult['ID'], false);
					$this->arResult['NAME_SMALL'] = Connector::getNameConnectorReal($this->arResult['ID'], true);
					$this->arResult['LANG_JS_SETTING'] = Component::getJsLangMessageSetting();

					$this->arResult['PATH_TO_EDIT'] = $this->arResult['PUBLIC_PATH'] . 'lines_edit/?ID=#ID#';
					$ratingRequest = Limit::canUseVoteClient() ? 'Y' : 'N';

					if(empty($this->arParams['connector']))
					{
						$this->arResult['PATH_TO_CONNECTOR'] = $this->arResult['PUBLIC_PATH'] . 'connector/?ID=#ID#';
						$this->arResult['PATH_TO_CONNECTOR_LINE'] = $this->arResult['PUBLIC_PATH'] . 'connector/?ID=#ID#&LINE=#LINE#&action-line=create&rating-request=' . $ratingRequest;
					}
					else
					{
						$this->arResult['PATH_TO_CONNECTOR'] = $this->arResult['PUBLIC_PATH'] . 'connector/#ID#/';
						$this->arResult['PATH_TO_CONNECTOR_LINE'] = $this->arResult['PUBLIC_PATH'] . 'connector/#ID#/?LINE=#LINE#&action-line=create&rating-request=' . $ratingRequest;
					}

					$this->arResult['PATH_TO_CONNECTOR_LINE_ADAPTED'] = str_replace('#ID#', $this->arResult['ID'], $this->arResult['PATH_TO_CONNECTOR_LINE']);
					$this->arResult['LIST_LINE'] = $this->showList();

					/*if(empty($this->arResult['ACTIVE_LINE']) && !empty($this->arResult['LINE']))
					{
						LocalRedirect($this->arResult['PUBLIC_PATH']);
					}*/

					if(
						(
							empty($this->arParams['connector']) ||
							Config::canActivateLine()
						)
					   && $this->userPermissions->canPerform(Permissions::ENTITY_LINES, Permissions::ACTION_MODIFY))
					{
						$this->arResult['PATH_TO_ADD_LINE'] = Helper::getAddUrl();
					}
				}

				$APPLICATION->SetTitle(Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CONNECT') . ' ' . $this->arResult['NAME']);

				if(
					!empty($this->arResult['RELOAD'])
					|| !empty($this->arResult['URL_RELOAD'])
				)
				{
					$APPLICATION->RestartBuffer();
				}

				$this->includeComponentTemplate();

				if(
					!empty($this->arResult['RELOAD'])
					|| !empty($this->arResult['URL_RELOAD'])
				)
				{
					\CMain::FinalActions();
				}
			}
			else
			{
				LocalRedirect($this->arResult['PUBLIC_PATH']);
			}
		}
	}
};
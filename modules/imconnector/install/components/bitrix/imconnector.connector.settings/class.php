<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Web\Uri,
	\Bitrix\Main\Context,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Connector,
	\Bitrix\ImConnector\Component;
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
	 * @throws LoaderException
	 */
	protected function checkModules()
	{
		if (Loader::includeModule('imopenlines') && Loader::includeModule('imconnector'))
		{
			return true;
		}
		else
		{
			if(!Loader::includeModule('imopenlines') && !Loader::includeModule('imconnector'))
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_MODULE_IMOPENLINES_NOT_INSTALLED'));
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_MODULE_IMCONNECTOR_NOT_INSTALLED'));
			}
			elseif(!Loader::includeModule('imopenlines'))
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_MODULE_IMOPENLINES_NOT_INSTALLED'));
			}
			else
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_MODULE_IMCONNECTOR_NOT_INSTALLED'));
			}

			return false;
		}
	}

	private function showList()
	{
		$allowedUserIds = Security\Helper::getAllowedUserIds(
			Security\Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Permissions::ENTITY_CONNECTORS, Permissions::ACTION_MODIFY)
		);

		$limit = null;
		if (is_array($allowedUserIds))
		{
			$limit = array();
			$orm = QueueTable::getList(Array(
				'filter' => Array(
					'=USER_ID' => $allowedUserIds
				)
			));
			while ($row = $orm->fetch())
			{
				$limit[$row['CONFIG_ID']] = $row['CONFIG_ID'];
			}
		}

		$configManager = new \Bitrix\ImOpenLines\Config();
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
			if (!is_null($limit))
			{
				if (!isset($limit[$config['ID']]) && !in_array($config['MODIFY_USER_ID'], $allowedUserIds))
				{
					unset($result[$id]);
					continue;
				}
			}

			if(empty($this->arResult['LINE']) && $id === 0)
				$config['ACTIVE'] = true;
			elseif(!empty($this->arResult['LINE']) && $config['ID'] == $this->arResult['LINE'])
				$config['ACTIVE'] = true;

			$config['URL'] = str_replace(array('#ID#', '#LINE#'), array($this->arResult['ID'], $config['ID']), $this->arResult['PATH_TO_CONNECTOR_LINE']);

			if (!empty($this->request['group_orders']))
			{
				$uri = new Uri($config['URL']);
				$uri->addParams(array('group_orders' => htmlspecialcharsbx($this->request['group_orders'])));
				$config['URL'] = $uri->getUri();
			}

			if(!empty($config['ACTIVE']))
			{
				$this->arResult['ACTIVE_LINE'] = $config;
				if(!empty($this->arResult['ACTIVE_LINE']['NAME']))
				{
					$this->arResult['ACTIVE_LINE']['~NAME'] = $this->arResult['ACTIVE_LINE']['NAME'];
					$this->arResult['ACTIVE_LINE']['NAME'] = htmlspecialcharsbx($this->arResult['ACTIVE_LINE']['NAME']);
				}
				$uri = new Uri(str_replace('#ID#', $config['ID'], $this->arResult['PATH_TO_EDIT']));
				$uri->addParams(array('back_url' => urlencode(
					str_replace(array('#ID#', '#LINE#'), array($this->arResult['ID'], $config['ID']), $this->arResult['PATH_TO_CONNECTOR_LINE']))
				));
				$this->arResult['ACTIVE_LINE']['URL_EDIT'] = $uri->getUri();

				$this->arResult['QUEUE_DESTINATION'] = $this->getQueueDestination($this->arResult['ACTIVE_LINE']['ID']);
			}

			$result[$id] = $config;
		}

		return $result;
	}

	private function setUserLimits()
	{
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
	}

	private function getQueueDestination($lineId)
	{
		$destination = array();

		if (Loader::includeModule('socialnetwork'))
		{
			$configManager = new Config();
			$config = $configManager->get($lineId);
			$structure = CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true));
			// TODO filter non-business users

			$destinationUsers = array_values($config['QUEUE']);
			$destination = array(
				'DEST_SORT' => CSocNetLogDestination::GetDestinationSort(array(
																			 "DEST_CONTEXT" => "IMOPENLINES",
																			 "CODE_TYPE" => 'U'
																		 )),
				'LAST' => array(),
				"DEPARTMENT" => $structure['department'],
				"SELECTED" => array(
					"USERS" => $destinationUsers
				)
			);
			CSocNetLogDestination::fillLastDestination($destination['DEST_SORT'], $destination['LAST']);

			if (isset($destination['LAST']['USERS']))
			{
				foreach ($destination['LAST']['USERS'] as $value)
					$destinationUsers[] = str_replace('U', '', $value);
			}

			$destination['EXTRANET_USER'] = 'N';
			$destination['USERS'] = CSocNetLogDestination::GetUsers(Array('id' => $destinationUsers));
			if (Loader::includeModule('im'))
			{
				foreach ($destination['USERS'] as &$user)
				{
					$user['link'] = CIMContactList::GetUserPath($user['entityId']);
				}
			}
		}

		return $destination;
	}

	public function executeComponent()
	{
		global $APPLICATION;

		$this->includeComponentLang('class.php');
		Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . '/bitrix/components/bitrix/imconnector.settings.status/class.php');
		Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . '/bitrix/components/bitrix/imconnector.settings/class.php');

		if($this->checkModules())
		{
			Connector::initIconCss();
			$this->setUserLimits();

			$this->arResult['PUBLIC_PATH'] = Common::getPublicFolder();

			if(empty($this->arParams['connector']))
				$this->arResult['ID'] = $this->request['ID'];
			else
				$this->arResult['ID'] = $this->arParams['connector'];

			$this->arResult['ID'] = Connector::getConnectorRealId($this->arResult['ID']);
			$this->arResult['SHOW_LIST_LINES'] = $this->request['LINE_SETTING'] !== 'Y';

			if(!empty($this->arResult['ID']) && Connector::isConnector($this->arResult['ID']))
			{
				if($this->request['reload'] == 'y' || $this->request['reload'] == 'Y')
				{
					CUtil::InitJSCore( array('ajax' , 'popup' ));

					$uri = new Uri(Context::getCurrent()->getServer()->getRequestUri());

					$this->arResult['RELOAD'] = $this->request['ajaxid'];
					$uri->deleteParams(array('reload', 'ajaxid'));
					$uri->addParams(array('bxajaxid' => $this->arResult['RELOAD']));
					$this->arResult['URL_RELOAD'] = $uri->getUri();
				}
				else
				{
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

					$this->arResult['PATH_TO_EDIT'] = $this->arResult['PUBLIC_PATH'] . "list/edit.php?ID=#ID#";
					$ratingRequest = \Bitrix\Imopenlines\Limit::canUseVoteClient() ? 'Y' : 'N';

					if(empty($this->arParams['connector']))
					{
						$this->arResult['PATH_TO_CONNECTOR'] = $this->arResult['PUBLIC_PATH'] . "connector/?ID=#ID#";
						$this->arResult['PATH_TO_CONNECTOR_LINE'] = $this->arResult['PUBLIC_PATH'] . "connector/?ID=#ID#&LINE=#LINE#&action-line=create&rating-request=" . $ratingRequest;
					}
					else
					{
						$this->arResult['PATH_TO_CONNECTOR'] = $this->arResult['PUBLIC_PATH'] . "connector/#ID#/";
						$this->arResult['PATH_TO_CONNECTOR_LINE'] = $this->arResult['PUBLIC_PATH'] . "connector/#ID#/?LINE=#LINE#&action-line=create&rating-request=" . $ratingRequest;
					}

					$this->arResult['PATH_TO_CONNECTOR_LINE_ADAPTED'] = str_replace('#ID#', $this->arResult['ID'], $this->arResult["PATH_TO_CONNECTOR_LINE"]);
					$this->arResult['LIST_LINE'] = $this->showList();

					/*if(empty($this->arResult['ACTIVE_LINE']) && !empty($this->arResult['LINE']))
					{
						LocalRedirect($this->arResult['PUBLIC_PATH']);
					}*/

					$configManager = new Config();
					if(($configManager->canActivateLine() || empty($this->arParams['connector']))
					   && $this->userPermissions->canPerform(Permissions::ENTITY_LINES, Permissions::ACTION_MODIFY))
					{
						$this->arResult['PATH_TO_ADD_LINE'] = Helper::getAddUrl();
					}

					$this->arResult['CAN_CHANGE_USERS'] = $configManager->canEditLine($this->arResult['ACTIVE_LINE']['ID']);
				}

				$APPLICATION->SetTitle(Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CONNECT') . " " . $this->arResult['NAME']);

				if(!empty($this->arResult['RELOAD']))
					$APPLICATION->RestartBuffer();

				$this->includeComponentTemplate();

				if(!empty($this->arResult['RELOAD']))
				{
					CMain::FinalActions();
					die();
				}
			}
			else
			{
				LocalRedirect($this->arResult['PUBLIC_PATH']);
			}
		}
	}
};
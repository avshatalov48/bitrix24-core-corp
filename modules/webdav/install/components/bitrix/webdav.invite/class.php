<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavInviteComponent extends CBitrixComponent
{
	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR   = 'error';

	/** @var \Bitrix\Webdav\InviteDispatcher */
	protected $dispatcher;

	/**
	 * @return CAllMain
	 */
	private static function getApplication()
	{
		global $APPLICATION;

		return $APPLICATION;
	}

	public function onPrepareComponentParams($arParams)
	{
		$arParams['action'] = strtolower($arParams['action']);
		$arParams['inviteFromUserId'] = (int)$arParams['inviteFromUserId'];
		$arParams['attachToUserId'] = (int)$arParams['attachToUserId'];
		$arParams['attachToUserIds'] = empty($arParams['attachToUserIds'])? array() : $arParams['attachToUserIds'];
		$arParams['unshareUserIds'] = empty($arParams['unshareUserIds'])? array() : $arParams['unshareUserIds'];
		$arParams['inviteDescription'] = empty($arParams['inviteDescription'])? '' : $arParams['inviteDescription'];
		$arParams['canEdit'] = !empty($arParams['canEdit']);
		$arParams['pathToUser'] = !empty($arParams['pathToUser'])? $arParams['pathToUser'] : '/company/personal/user/#user_id#/';
		$arParams['pathToGroup'] = !empty($arParams['pathToGroup'])? $arParams['pathToGroup'] : '/workgroups/group/#group_id#/';
		$arParams['userListType'] = !empty($arParams['userListType'])? strtolower($arParams['userListType']) : 'cannot_edit';
		$arParams['currentUserCanUnshare'] = !empty($arParams['currentUserCanUnshare']);
		if(!is_array($arParams['attachToUserIds']))
		{
			$arParams['attachToUserIds'] = array();
		}
		if(!is_array($arParams['unshareUserIds']))
		{
			$arParams['unshareUserIds'] = array();
		}


		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if(!CModule::IncludeModule('webdav'))
		{
			return false;
		}
		try
		{
			$this->dispatcher = new \Bitrix\Webdav\InviteDispatcher;
			if($this->isAjax())
			{
				$this
					->checkSession()
					->checkUser()
					->runAction()
				;
			}
		}
		catch(Exception $e)
		{
			$this->sendJsonResponse(array(
				'status' => self::STATUS_ERROR,
				'message'=> $e->getMessage(),
			));
		}

		return;
	}

	private function isAjax()
	{
		return !empty($this->arParams['ajax']);
	}

	protected function runAction()
	{
		switch($this->arParams['action'])
		{
			case 'connect':
				$this->processActionConnect();
				break;
			case 'disconnect':
				$this->processActionDisconnect();
				break;
			case 'detail_group_connect':
				$this->processActionDetailGroupConnect();
				break;
			case 'detail_user_share':
				$this->processActionDetailUserShare();
				break;
			case 'info_user_share':
				$this->processActionInfoUserShare();
				break;
			case 'load_users_for_detail_user_share':
				$this->processActionLoadUsersForDetailUserShare();
				break;
			case 'share':
				$this->processActionShare();
				break;
			case 'unshare':
				if($this->arParams['currentUserCanUnshare'])
				{
					$this->processActionUnshare();
				}
				break;
			default:
				throw new Exception('Wrong action');
		}

		return $this;
	}

	protected function processActionConnect()
	{
		$this->sendJsonResponse($this->dispatcher->processActionConnect($this->arParams));
	}

	protected function processActionDisconnect()
	{
		$this->sendJsonResponse($this->dispatcher->processActionDisconnect($this->arParams));
	}

	protected function processActionDetailGroupConnect()
	{
		$this->arResult = $this->dispatcher->processActionDetailGroupConnect($this->arParams);
		$this->includeComponentTemplate('ajax_group');
	}

	protected function processActionLoadUsersForDetailUserShare()
	{
		$this->arResult = $this->dispatcher->processActionLoadUsersForDetailUserShare($this->arParams);
		$this->arResult['USER_LIST_TYPE'] = $this->arParams['userListType'];
		if(!empty($this->arResult['IS_LINK_SECTION']))
		{
			$this->arParams['currentUserCanUnshare'] = false;
		}

		$this->includeComponentTemplate('ajax_users_list');
	}

	protected function processActionDetailUserShare()
	{
		$this->arResult = $this->dispatcher->processActionDetailUserShare($this->arParams);

		if(\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			// socialnetwork
			$this->arResult["FEED_DESTINATION"] = array(
				'LAST' => array(),
			);
			$this->arResult["FEED_DESTINATION"]['LAST']['SONETGROUPS'] = array();

			$this->arResult["FEED_DESTINATION"]['SONETGROUPS'] = array();

			$this->arResult["FEED_DESTINATION"]['SELECTED'] = array();

			// intranet structure
			$arStructure = CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true));
			$this->arResult["FEED_DESTINATION"]['DEPARTMENT'] = $arStructure['department'];
			$this->arResult["FEED_DESTINATION"]['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
			$this->arResult["FEED_DESTINATION"]['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

			$this->arResult["FEED_DESTINATION"]['LAST']['DEPARTMENT'] = CSocNetLogDestination::GetLastDepartment();

			// users
			$this->arResult["FEED_DESTINATION"]['LAST']['USERS'] = CSocNetLogDestination::GetLastUser();

			foreach ($this->arResult["FEED_DESTINATION"]['LAST']['USERS'] as $value)
				$this->arResult["dest_users"][] = str_replace('U', '', $value);

			$this->arResult["FEED_DESTINATION"]['EXTRANET_USER'] = 'N';
			$this->arResult["FEED_DESTINATION"]['USERS'] = CSocNetLogDestination::GetUsers(array('id' => $this->arResult["dest_users"]));

			$this->arResult["FEED_DESTINATION"]["DENY_TOALL"] = true;
		}

		$this->includeComponentTemplate('ajax_user');
	}

	protected function processActionInfoUserShare()
	{
		$this->arResult = $this->dispatcher->processActionInfoUserShare($this->arParams);
		$this->includeComponentTemplate('ajax_info_user');
	}

	protected function processActionShare()
	{
		$this->sendJsonResponse($this->dispatcher->processActionShare($this->arParams));
	}

	protected function processActionUnshare()
	{
		$this->sendJsonResponse($this->dispatcher->processActionUnshare($this->arParams));
	}

	/**
	 * @return $this
	 * @throws Exception
	 */
	private function checkUser()
	{
		if(!($this->getUser() instanceof CAllUser) || intval($this->getUser()->getId()) <= 0)
		{
			throw new Exception('Wrong auth');
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws Exception
	 */
	private function checkSession()
	{
		if(!check_bitrix_sessid())
		{
			throw new Exception('Wrong sessid');
		}
		return $this;
	}

	/**
	 * @return CAllUser
	 */
	private function getUser()
	{
		global $USER;

		return $USER;
	}

	public function sendJsonResponse($response, $httpStatusCode = null)
	{
		CWebDavTools::sendJsonResponse($response, $httpStatusCode);
	}
}
<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CVoximplantQueueEditComponent extends \CBitrixComponent
{
	private $action;

	public function executeComponent()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		$action = $_REQUEST['action'];
		switch ($action)
		{
			case 'save':
				$this->executeSaveAction();
				break;
			default:
				$this->executeEditAction();
				break;
		}
	}

	protected function executeEditAction()
	{
		$this->arResult = $this->prepareEditData();
		$this->includeComponentTemplate();
		return $this->arResult;
	}

	protected function executeSaveAction()
	{
		$saveResult = $this->save($_POST);
		if($saveResult->isSuccess())
		{
			LocalRedirect(CVoxImplantMain::GetPublicFolder()."groups.php");
		}
		else
		{
			$this->arResult = array(
				'ERROR' => implode('<br>', $saveResult->getErrorMessages()),
				'ITEM' => $_POST,
				'DESTINATION' => $this->getDestinationParams((is_array($_POST['USERS']) ? $_POST['USERS'] : array()))
			);
			$this->includeComponentTemplate();
		}
	}

	protected function prepareEditData()
	{
		$id = (int)$this->arParams['ID'];
		$result = array();
		$result['INLINE_MODE'] = (bool)$this->arParams['INLINE_MODE'];;
		$result['EXTERNAL_REQUEST_ID'] = (string)$this->arParams['EXTERNAL_REQUEST_ID'];
		$result['MAXIMUM_GROUP_MEMBERS'] = \Bitrix\Voximplant\Limits::getMaximumGroupMembers();

		$userIds = array();
		if($id > 0)
		{
			$result['ITEM'] = \Bitrix\Voximplant\Model\QueueTable::getById($id)->fetch();

			$cursor = \Bitrix\Voximplant\Model\QueueUserTable::getList(array(
				'select' => array('USER_ID'),
				'filter' => array(
					'=QUEUE_ID' => $id
				),
				'order' => array('ID' => 'ASC'),
			));
			while ($row = $cursor->fetch())
			{
				$userIds[] = $row['USER_ID'];
			}
		}
		else
		{

		}
		$result['DESTINATION'] = $this->getDestinationParams($userIds);

		$result['QUEUE_LIST'] = \Bitrix\Voximplant\Model\QueueTable::getList(array(
			'select' => array('ID', 'NAME'),
		))->fetchAll();

		return $result;
	}
	
	protected function getDestinationParams(array $userIds)
	{
		if (!CModule::IncludeModule("socialnetwork"))
			return array();

		$arStructure = CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true));
		$result = array(
			'DEST_SORT' => CSocNetLogDestination::GetDestinationSort(array(
				"DEST_CONTEXT" => "VOXIMPLANT",
				"CODE_TYPE" => 'U'
			)),
			'LAST' => array(),
			"DEPARTMENT" => $arStructure['department'],
			"SELECTED" => array(
				"USERS" => $userIds
			)
		);
		CSocNetLogDestination::fillLastDestination($result['DEST_SORT'], $result['LAST']);

		$userList = $userIds;
		if (is_array($result['LAST']['USERS']))
		{
			foreach ($result['LAST']['USERS'] as $value)
			{
				$userList[] = str_replace('U', '', $value);
			}
		}
		$result['EXTRANET_USER'] = 'N';
		$result['USERS'] = CSocNetLogDestination::GetUsers(Array('id' => $userList));

		return $result;
	}

	/**
	 * @return \Bitrix\Main\Result
	 * @throws Exception
	 * @throws \Bitrix\Voximplant\Model\ArgumentException
	 */
	public static function save($request)
	{
		$result = new \Bitrix\Main\Result();
		$id = (int)$request['ID'];
		
		$queueFields = array(
			'NAME' => (string) $request['NAME'],
			'WAIT_TIME' => (int)$request['WAIT_TIME'],
			'NO_ANSWER_RULE' => (string)$request['NO_ANSWER_RULE'],
			'ALLOW_INTERCEPT' => ($request['ALLOW_INTERCEPT'] === 'Y' && \Bitrix\Voximplant\Limits::canInterceptCall() ? 'Y' : 'N'),
		);
		
		if($queueFields['NAME'] == '')
		{
			$result->addError(new \Bitrix\Main\Error(Loc::getMessage('VI_CONFIG_ERROR_EMPTY_NAME')));
			return $result;
		}

		if($request['TYPE'] === CVoxImplantConfig::QUEUE_TYPE_ALL)
		{
			if(CVoxImplantAccount::IsPro())
				$queueFields['TYPE'] = CVoxImplantConfig::QUEUE_TYPE_ALL;
			else
				$queueFields['TYPE'] = CVoxImplantConfig::QUEUE_TYPE_EVENLY;
		}
		else
		{
			$queueFields['TYPE'] = (string)$request['TYPE'];
		}

		if($queueFields['NO_ANSWER_RULE'] == CVoxImplantIncoming::RULE_PSTN_SPECIFIC)
		{
			$queueFields['FORWARD_NUMBER'] = (string)$request['FORWARD_NUMBER'];
		}
		else
		{
			$queueFields['FORWARD_NUMBER'] = null;
		}

		if($queueFields['NO_ANSWER_RULE'] == CVoxImplantIncoming::RULE_NEXT_QUEUE && $queueFields['TYPE'])
			$queueFields['NEXT_QUEUE_ID'] = (int)$request['NEXT_QUEUE_ID'];
		else
			$queueFields['NEXT_QUEUE_ID'] = null;

		if($id > 0)
			$dbResult = \Bitrix\Voximplant\Model\QueueTable::update($id, $queueFields);
		else
			$dbResult = \Bitrix\Voximplant\Model\QueueTable::add($queueFields);

		if(!$dbResult->isSuccess())
		{
			$result->addError(new \Bitrix\Main\Error('DB error'));
			return $result;
		}

		if($id == 0)
		{
			$id = $dbResult->getId();
		}
		$queueFields['ID'] = $id;
		\Bitrix\Voximplant\Model\QueueUserTable::deleteByQueueId($id);
		$users = (array)$request['USERS'];
		$maximumUsers = \Bitrix\Voximplant\Limits::getMaximumGroupMembers();
		$currentUserCount = 0;
		foreach ($users as $userId)
		{
			$userId = (int)$userId;
			$dbResult = \Bitrix\Voximplant\Model\QueueUserTable::add(array(
				'QUEUE_ID' =>  $id,
				'USER_ID' => $userId
			));
			if(!$dbResult->isSuccess())
			{
				$result->addError(new \Bitrix\Main\Error('DB error'));
				return $result;
			}
			$currentUserCount++;
			if($maximumUsers > 0 && $currentUserCount >= $maximumUsers)
				break;
		}
		$result->setData(array(
			'GROUP' => $queueFields
		));

		return $result;
	}
}
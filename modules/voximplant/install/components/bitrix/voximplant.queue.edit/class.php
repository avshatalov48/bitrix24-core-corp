<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Security\Permissions;

Loc::loadMessages(__FILE__);

class CVoximplantQueueEditComponent extends \CBitrixComponent
{
	private $action;

	public function executeComponent()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		if(!$this::checkAccess())
			return false;

		$action = $_REQUEST['action'] ?? null;
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

	protected static function checkAccess()
	{
		$userPermissions = Permissions::createWithCurrentUser();
		return $userPermissions->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY);
	}

	protected function executeEditAction()
	{
		$this->arResult = $this->prepareEditData();
		$this->includeComponentTemplate();
		return $this->arResult;
	}

	protected function executeSaveAction()
	{
		$saveResult = static::save($_POST);
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
		$result['EXTERNAL_REQUEST_ID'] = (string)($this->arParams['EXTERNAL_REQUEST_ID'] ?? null);
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
	 */
	public static function save($request)
	{
		$result = new \Bitrix\Main\Result();
		if (!static::checkAccess())
		{
			$result->addError(new \Bitrix\Main\Error("VI_CONFIG_ERROR_ACCESS_DENIED", "access_denied"));
			return $result;
		}
		$id = (int)$request['ID'];

		$queueFields = array(
			'NAME' => (string) $request['NAME'],
			'PHONE_NUMBER' => preg_replace('/\D/', '', $request['PHONE_NUMBER']),
			'WAIT_TIME' => (int)$request['WAIT_TIME'],
			'NO_ANSWER_RULE' => (string)$request['NO_ANSWER_RULE'],
			'ALLOW_INTERCEPT' => (
				($request['ALLOW_INTERCEPT'] ?? null) === 'Y'
				&& \Bitrix\Voximplant\Limits::canInterceptCall() ? 'Y' : 'N'
			),
		);

		if($queueFields['NAME'] == '')
		{
			return $result->addError(new \Bitrix\Main\Error(Loc::getMessage('VI_CONFIG_ERROR_EMPTY_NAME')));
		}

		if($queueFields['PHONE_NUMBER'] != '')
		{
			if (mb_strlen($queueFields['PHONE_NUMBER']) > 4)
			{
				return $result->addError(new \Bitrix\Main\Error(Loc::getMessage("VI_CONFIG_ERROR_PHONE_NUMBER_TOO_LONG")));
			}

			$entity = CVoxImplantIncoming::getByInternalPhoneNumber($queueFields['PHONE_NUMBER']);
			if ($entity && !($entity['ENTITY_TYPE'] === 'queue' && $entity['ENTITY_ID'] === $id))
			{
				if ($entity['ENTITY_TYPE'] === 'queue')
				{
					$result->addError(new \Bitrix\Main\Error(Loc::getMessage("VI_CONFIG_ERROR_NUMBER_IN_USE_BY_GROUP", [
						'#NAME#' => static::getQueueName($entity['ENTITY_ID'])
					])));
				}
				else
				{
					$result->addError(new \Bitrix\Main\Error(Loc::getMessage("VI_CONFIG_ERROR_NUMBER_IN_USE_BY_USER", [
						'#NAME#' => static::getUserName($entity['ENTITY_ID'])
					])));
				}

				return $result;
			}
		}

		if ($request['TYPE'] === CVoxImplantConfig::QUEUE_TYPE_ALL && !\Bitrix\Voximplant\Limits::isQueueAllAllowed())
		{
			$queueFields['TYPE'] = CVoxImplantConfig::QUEUE_TYPE_EVENLY;
		}
		else
		{
			$queueFields['TYPE'] = (string)$request['TYPE'];
		}

		if ($queueFields['NO_ANSWER_RULE'] === CVoxImplantIncoming::RULE_NEXT_QUEUE && !\Bitrix\Voximplant\Limits::isRedirectToQueueAllowed())
		{
			$queueFields['NO_ANSWER_RULE'] = CVoxImplantIncoming::RULE_VOICEMAIL;
		}

		if($queueFields['NO_ANSWER_RULE'] === CVoxImplantIncoming::RULE_NEXT_QUEUE)
		{
			$queueFields['NEXT_QUEUE_ID'] = (int)$request['NEXT_QUEUE_ID'];
		}
		else
		{
			$queueFields['NEXT_QUEUE_ID'] = null;
		}

		if($queueFields['NO_ANSWER_RULE'] === CVoxImplantIncoming::RULE_PSTN_SPECIFIC)
		{
			$queueFields['FORWARD_NUMBER'] = (string)$request['FORWARD_NUMBER'];
		}
		else
		{
			$queueFields['FORWARD_NUMBER'] = null;
		}

		if (!$id && !\Bitrix\Voximplant\Limits::canCreateGroup())
		{
			$result->addError(new \Bitrix\Main\Error(Loc::getMessage("VI_CONFIG_ERROR_MAX_GROUP_COUNT_REACHED")));
			return $result;
		}

		if($id > 0)
		{
			$dbResult = \Bitrix\Voximplant\Model\QueueTable::update($id, $queueFields);
		}
		else
		{
			$dbResult = \Bitrix\Voximplant\Model\QueueTable::add($queueFields);
		}

		if(!$dbResult->isSuccess())
		{
			$result->addError(new \Bitrix\Main\Error('DB error'));
			return $result;
		}

		if($id === 0)
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
			if ($maximumUsers > -1 && $currentUserCount >= $maximumUsers)
			{
				break;
			}
		}
		$result->setData(array(
			'GROUP' => $queueFields
		));

		return $result;
	}

	protected static function getUserName(int $id): ?string
	{
		$userFields = \Bitrix\Main\UserTable::getList([
			'select' => ['LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
			'filter' => ['=ID' => $id]
		])->fetch();
		if ($userFields)
		{
			return \CUser::formatName(CSite::getNameFormat(), $userFields);
		}
		return null;
	}

	protected static function getQueueName(int $id): ?string
	{
		$queueFields = \Bitrix\Voximplant\Model\QueueTable::getList([
			'select' => ['NAME'],
			'filter' => ['=ID' => $id]
		])->fetch();
		
		if ($queueFields)
		{
			return $queueFields['NAME'];
		}
		return null;
	}
}
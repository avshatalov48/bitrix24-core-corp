<?php

/**
* Bitrix Framework
* @package bitrix
* @subpackage crm
* @copyright 2001-2019 Bitrix
*/

namespace Bitrix\Crm\Integration\Im;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Im;
use Bitrix\Main\Localization\Loc;

class Chat
{
	const CHAT_ENTITY_TYPE = "CRM";

	public static function getEntityUserIDs($entityTypeID, $entityID)
	{
		$results = [];

		$responsibleID = 0;
		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			$responsibleID = Crm\Entity\Lead::getResponsibleID($entityID);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			$responsibleID = Crm\Entity\Deal::getResponsibleID($entityID);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$responsibleID = Crm\Entity\Contact::getResponsibleID($entityID);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			$responsibleID = Crm\Entity\Contact::getResponsibleID($entityID);
		}

		if($responsibleID > 0)
		{
			$results[] = $responsibleID;
		}

		$results = array_merge($results, Crm\Observer\ObserverManager::getEntityObserverIDs($entityTypeID, $entityID));
		return array_unique($results);
	}

	public static function prepareUserInfos(array $userIDs)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return [];
		}

		$userInfos = [];
		foreach ($userIDs as $userID)
		{
			$userInfos[$userID] = Im\User::getInstance($userID)->getArray(['JSON' => 'Y']);
		}
		return $userInfos;
	}

	public static function onAddChatUser(array $eventArgs)
	{
		$chatID = isset($eventArgs['CHAT_ID']) ? (int)$eventArgs['CHAT_ID'] : 0;
		if($chatID <= 0)
		{
			return;
		}

		$userIDs = isset($eventArgs['NEW_USERS']) && is_array($eventArgs['NEW_USERS']) ? $eventArgs['NEW_USERS'] : null;
		if(empty($userIDs))
		{
			return;
		}

		$chatData = Im\Model\ChatTable::getList(
			[ 'select' => [ 'ID', 'ENTITY_TYPE', 'ENTITY_ID' ], 'filter' => [ '=ID' => $chatID ] ]
		)->fetch();
		if(!is_array($chatData))
		{
			return;
		}

		if(!(isset($chatData['ENTITY_TYPE']) && $chatData['ENTITY_TYPE'] === self::CHAT_ENTITY_TYPE))
		{
			return;
		}

		$entityInfo = Crm\Integration\Im\Chat::resolveEntityInfo(
			isset($chatData['ENTITY_ID']) ? $chatData['ENTITY_ID'] : ''
		);

		if(!is_array($entityInfo))
		{
			return;
		}

		if($entityInfo['ENTITY_TYPE_ID'] === \CCrmOwnerType::Deal)
		{
			\CCrmDeal::AddObserverIDs($entityInfo['ENTITY_ID'], $userIDs);
		}
		elseif($entityInfo['ENTITY_TYPE_ID'] === \CCrmOwnerType::Lead)
		{
			\CCrmLead::AddObserverIDs($entityInfo['ENTITY_ID'], $userIDs);
		}
	}

	public static function onEntityModification($entityTypeID, $entityID, array $params)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return;
		}

		$chatID = self::getChatId($entityTypeID, $entityID);
		if($chatID <= 0)
		{
			return;
		}

		$currentFields = isset($params['CURRENT_FIELDS']) && is_array($params['CURRENT_FIELDS'])
			? $params['CURRENT_FIELDS'] : array();
		$previousFields = isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
			? $params['PREVIOUS_FIELDS'] : array();
		$removedObserverIDs = isset($params['REMOVED_OBSERVER_IDS']) && is_array($params['REMOVED_OBSERVER_IDS'])
			? $params['REMOVED_OBSERVER_IDS'] : array();

		$currentOwnerID = 0;
		$currentTitle = '';

		if($entityTypeID === \CCrmOwnerType::Lead || $entityTypeID === \CCrmOwnerType::Deal)
		{
			$previousResponsibleID = isset($previousFields['ASSIGNED_BY_ID']) ? $previousFields['ASSIGNED_BY_ID'] : 0;
			if(isset($currentFields['ASSIGNED_BY_ID']) && $currentFields['ASSIGNED_BY_ID'] != $previousResponsibleID)
			{
				$currentOwnerID = (int)$currentFields['ASSIGNED_BY_ID'];
			}

			$previousTitle = isset($previousFields['TITLE']) ? $previousFields['TITLE'] : '';
			if(isset($currentFields['TITLE']) && $currentFields['TITLE'] != $previousTitle)
			{
				$currentTitle = $currentFields['TITLE'];
			}
		}

		if($currentOwnerID > 0 || $currentTitle !== '' || !empty($removedObserverIDs))
		{
			$chat = new \CIMChat(0);
			if($currentOwnerID > 0)
			{
				$chat->AddUser($chatID, [ $currentOwnerID ], false, false);
				$chat->SetOwner($chatID, $currentOwnerID, false);
			}

			if($currentTitle !== '')
			{
				$currentTitle = self::buildChatName(
					[
						'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($entityTypeID),
						'ENTITY_TITLE' => $currentTitle,
					]
				);
				$chat->Rename($chatID, $currentTitle, false, false);
			}

			foreach($removedObserverIDs as $removedObserverID)
			{
				$chat->DeleteUser($chatID, $removedObserverID, false, false);
			}
		}
	}

	//protected

	protected static function resolveEntityInfo($entitySlug)
	{
		$parts = explode('|', $entitySlug);
		if(!(is_array($parts) && count($parts) >= 2))
		{
			return null;
		}

		return
			[
				'ENTITY_TYPE_NAME' => $parts[0],
				'ENTITY_TYPE_ID' => \CCrmOwnerType::ResolveID($parts[0]),
				'ENTITY_ID' => (int)$parts[1]
			];
	}

	public static function getChatId($entityTypeID, $entityID)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return 0;
		}

		if (!\CCrmOwnerType::IsDefined($entityTypeID) || $entityID <= 0)
		{
			return 0;
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$chatData = \Bitrix\Im\Model\ChatTable::getList(
			[
				'select' => ['ID'],
				'filter' => [ '=ENTITY_TYPE' => self::CHAT_ENTITY_TYPE,  '=ENTITY_ID' => $entityTypeName.'|'.$entityID ],
			]
		)->fetch();
		return is_array($chatData) && isset($chatData['ID']) ? (int)$chatData['ID'] : 0;
	}

	public static function transferOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return;
		}

		if(!is_int($oldEntityTypeID))
		{
			$oldEntityTypeID = (int)$oldEntityTypeID;
		}

		if($oldEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityTypeID');
		}

		if(!is_int($oldEntityID))
		{
			$oldEntityID = (int)$oldEntityID;
		}

		if($oldEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityID');
		}

		if(!is_int($newEntityTypeID))
		{
			$newEntityTypeID = (int)$newEntityTypeID;
		}

		if($newEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityTypeID');
		}

		if(!is_int($newEntityID))
		{
			$newEntityID = (int)$newEntityID;
		}

		if($newEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityID');
		}

		$chatData = Im\Model\ChatTable::getList(
			[
				'select' => ['ID'],
				'filter' =>
					[
						'=ENTITY_TYPE' => self::CHAT_ENTITY_TYPE,
						'=ENTITY_ID' => \CCrmOwnerType::ResolveName($oldEntityTypeID).'|'.$oldEntityID
					],
			]
		)->fetch();

		if(is_array($chatData) && isset($chatData['ID']))
		{
			Im\Model\ChatTable::update(
				$chatData['ID'],
				[ 'ENTITY_ID' => \CCrmOwnerType::ResolveName($newEntityTypeID).'|'.$newEntityID ]
			);
		}
	}


	protected static function checkPermission($entityTypeID, $entityID, $userId = 0)
	{
		if($userId <= 0)
		{
			$userId = Crm\Security\EntityAuthorization::getCurrentUserID();
		}

		if(Crm\Security\EntityAuthorization::IsAdmin($userId))
		{
			return true;
		}

		return Crm\Security\EntityAuthorization::checkReadPermission(
			$entityTypeID,
			$entityID,
			Crm\Security\EntityAuthorization::getUserPermissions($userId)
		);
	}

	public static function joinChat($params = [])
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}

		$entityType = $params['ENTITY_TYPE'];
		$entityTypeId = \CCrmOwnerType::ResolveID($entityType);
		$entityId = (int)$params['ENTITY_ID'];
		$userId = (int)$params['USER_ID'];

		if (empty($entityType) || empty($entityId) || empty($userId))
		{
			return false;
		}

		if (!self::checkPermission($entityTypeId, $entityId, $userId))
		{
			return false;
		}

		$chatData = \Bitrix\Im\Model\ChatTable::getList(Array(
			'select' => [
				'ID',
				'RELATION_USER_ID' => 'RELATION.USER_ID',
			],
			'filter' => [
				'=ENTITY_TYPE' => self::CHAT_ENTITY_TYPE,
				'=ENTITY_ID' => $entityType.'|'.$entityId,
			],
			'runtime' => Array(
				new \Bitrix\Main\Entity\ReferenceField(
					'RELATION',
					'\Bitrix\Im\Model\RelationTable',
					array(
						"=ref.CHAT_ID" => "this.ID",
						"=ref.USER_ID" => new \Bitrix\Main\DB\SqlExpression('?', $userId)
					),
					array("join_type"=>"LEFT")
				)
			)
		))->fetch();
		if ($chatData)
		{
			if (!$chatData['RELATION_USER_ID'])
			{
				$chat = new \CIMChat(0);
				$chat->AddUser($chatData['ID'], [$userId], false);
			}

			return $chatData['ID'];
		}
		else
		{
			return self::createChat([
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => $entityId,
				'USER_ID' => $userId,
				'ENABLE_PERMISSION_CHECK' => false,
			]);
		}
	}

	public static function createChat($params = [])
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}

		$entityType = $params['ENTITY_TYPE'];
		$entityTypeId = \CCrmOwnerType::ResolveID($entityType);
		$entityId = (int)$params['ENTITY_ID'];
		$userId = (int)$params['USER_ID'];

		if (empty($entityType) || empty($entityId) || empty($userId))
		{
			return false;
		}

		$enablePermissionCheck = isset($params['ENABLE_PERMISSION_CHECK'])
			? (bool)$params['ENABLE_PERMISSION_CHECK'] : true;
		if ($enablePermissionCheck && !self::checkPermission($entityTypeId, $entityId, $userId))
		{
			return false;
		}

		$crmEntityTitle = '';
		$crmEntityAvatarId = 0;

		$entityData = self::getEntityData($entityType, $entityId, true);
		if ($entityType == \CCrmOwnerType::CompanyName)
		{
			if (isset($entityData['TITLE']))
			{
				$crmEntityTitle = $entityData['TITLE'];
			}
			if (isset($entityData['LOGO']))
			{
				$crmEntityAvatarId = intval($entityData['LOGO']);
			}
		}
		else if (
			$entityType == \CCrmOwnerType::LeadName || $entityType == \CCrmOwnerType::DealName
		)
		{
			if (isset($entityData['TITLE']))
			{
				$crmEntityTitle = $entityData['TITLE'];
			}
		}
		else if ($entityType == \CCrmOwnerType::ContactName)
		{
			if (isset($entityData['FULL_NAME']))
			{
				$crmEntityTitle = $entityData['FULL_NAME'];
			}
			if (isset($entityData['PHOTO']))
			{
				$crmEntityAvatarId = intval($entityData['PHOTO']);
			}
		}

		if (!$crmEntityTitle)
		{
			$crmEntityTitle = '#'.$entityId;;
		}

		$authorId = (int)$entityData['ASSIGNED_BY_ID'];
		if($authorId <= 0)
		{
			$authorId = $userId;
		}
		$joinUserList = array_unique(array_merge([ $userId ], self::getEntityUserIDs($entityTypeId, $entityId)));

		$chatFields = array(
			'TITLE' => self::buildChatName([
				'ENTITY_TYPE' => $entityType,
				'ENTITY_TITLE' => $crmEntityTitle,
			]),
			'TYPE' => IM_MESSAGE_CHAT,
			'ENTITY_TYPE' => self::CHAT_ENTITY_TYPE,
			'ENTITY_ID' => $entityType.'|'.$entityId,
			'SKIP_ADD_MESSAGE' => 'Y',
			'AUTHOR_ID' => $authorId,
			'USERS' => $joinUserList
		);
		if ($crmEntityAvatarId)
		{
			$chatFields['AVATAR_ID'] = $crmEntityAvatarId;
		}

		$chat = new \CIMChat(0);
		$chatId = $chat->add($chatFields);

		$users = [];
		foreach ($joinUserList as $uid)
		{
			$users[$uid] = \Bitrix\Im\User::getInstance($uid)->getArray(['JSON' => 'Y']);
		}

		if (Main\Loader::includeModule('pull'))
		{
			$tag = Crm\Timeline\TimelineEntry::prepareEntityPushTag($entityTypeId, $entityId);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_chat_create',
					'params' => array(
						'CHAT_DATA' => array('CHAT_ID' => $chatId, 'USER_INFOS' => $users),
						'TAG' => $tag
					),
				)
			);
		}

		// first message in chat, if you delete this message, need set SKIP_ADD_MESSAGE = N in creating chat props
		\CIMChat::AddMessage([
			"TO_CHAT_ID" => $chatId,
			"USER_ID" => $userId,
			"MESSAGE" => '[b]'.Loc::getMessage('CRM_INTEGRATION_IM_CHAT_CARD_TITLE_'.$entityType).'[/b]',
			"SYSTEM" => 'Y',
			"ATTACH" => self::getEntityCard($entityType, $entityId, $entityData)
		]);

		return $chatId;
	}

	public static function deleteChat(array $params = [])
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return;
		}

		$entityTypeName = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
		if($entityTypeName === '')
		{
			$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? (int)$params['ENTITY_TYPE_ID'] : 0;
			if($entityTypeID > 0)
			{
				$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			}
		}

		$entityId = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
		if($entityTypeName !== '' && $entityId > 0)
		{
			\CIMChat::DeleteEntityChat(self::CHAT_ENTITY_TYPE, $entityTypeName.'|'.$entityId);
		}
	}

	public static function buildChatName($params = [])
	{
		$entityType = $params['ENTITY_TYPE'];
		$entityTitle = $params['ENTITY_TITLE'];

		if (empty($entityType) || empty($entityTitle))
		{
			return false;
		}

		$currentSite = \CSite::getById(SITE_ID);
		$siteLanguageId = (
			($siteFields = $currentSite->fetch())
				? $siteFields['LANGUAGE_ID']
				: LANGUAGE_ID
		);

		return Loc::getMessage(
			'CRM_INTEGRATION_IM_CHAT_TITLE_'.$entityType,
			array("#TITLE#" => $entityTitle),
			$siteLanguageId
		);
	}

	public static function getEntityCard($entityType, $entityId, $entityData = null)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return null;
		}

		if (!in_array($entityType, [
			\CCrmOwnerType::LeadName,
			\CCrmOwnerType::ContactName,
			\CCrmOwnerType::CompanyName,
			\CCrmOwnerType::DealName
		]))
		{
			return null;
		}

		if (!$entityData)
		{
			$entityData = self::getEntityData($entityType, $entityId, true);
		}

		if (!$entityData)
		{
			return null;
		}

		$attach = new \CIMMessageParamAttach();

		$entityGrid = Array();
		if ($entityType == \CCrmOwnerType::LeadName)
		{
			if (isset($entityData['TITLE']))
			{
				$attach->AddLink(Array(
					'NAME' => $entityData['TITLE'],
					'LINK' => \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::ResolveID($entityType), $entityId, false)
				));
			}

			if (!empty($entityData['FULL_NAME']) && strpos($entityData['TITLE'], $entityData['FULL_NAME']) === false)
			{
				$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('CRM_INTEGRATION_IM_CHAT_CARD_FULL_NAME'), 'VALUE' => $entityData['FULL_NAME']);
			}
			if (!empty($entityData['COMPANY_TITLE']))
			{
				$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('CRM_INTEGRATION_IM_CHAT_CARD_COMPANY_TITLE'), 'VALUE' => $entityData['COMPANY_TITLE']);
			}
			if (!empty($entityData['POST']))
			{
				$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('CRM_INTEGRATION_IM_CHAT_CARD_POST'), 'VALUE' => $entityData['POST']);
			}

		}
		else if ($entityType == \CCrmOwnerType::ContactName)
		{
			if (isset($entityData['FULL_NAME']))
			{
				$attach->AddLink(Array(
					'NAME' => $entityData['FULL_NAME'],
					'LINK' => \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::ResolveID($entityType), $entityId, false)
				));
			}

			if (!empty($entityData['POST']))
			{
				$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('CRM_INTEGRATION_IM_CHAT_CARD_POST'), 'VALUE' => $entityData['POST']);
			}
		}
		else if ($entityType == \CCrmOwnerType::CompanyName || $entityType == \CCrmOwnerType::DealName)
		{
			if (isset($entityData['TITLE']))
			{
				$attach->AddLink(Array(
					'NAME' => $entityData['TITLE'],
					'LINK' => \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::ResolveID($entityType), $entityId, false)
				));
			}
		}

		if ($entityData['HAS_PHONE'] == 'Y' && isset($entityData['FM']['PHONE']))
		{
			$fields = Array();
			foreach ($entityData['FM']['PHONE'] as $phones)
			{
				foreach ($phones as $phone)
				{
					$fields[] = $phone;
				}
			}
			$entityGrid[] = Array('DISPLAY' => 'LINE', 'NAME' => Loc::getMessage('CRM_INTEGRATION_IM_CHAT_CARD_PHONE'), 'VALUE' => implode('[br]', $fields), 'HEIGHT' => '20');
		}
		if ($entityData['HAS_EMAIL'] == 'Y' && $entityData['FM']['EMAIL'])
		{
			$fields = Array();
			foreach ($entityData['FM']['EMAIL'] as $emails)
			{
				foreach ($emails as $email)
				{
					$fields[] = $email;
				}
			}
			$entityGrid[] = Array('DISPLAY' => 'LINE', 'NAME' => Loc::getMessage('CRM_INTEGRATION_IM_CHAT_CARD_EMAIL'), 'VALUE' => implode('[br]', $fields), 'HEIGHT' => '20');
		}
		$attach->AddGrid($entityGrid);

		return $attach;
	}

	public static function getEntityData($entityType, $entityId, $withMultiFields = false)
	{
		if ($entityType == \CCrmOwnerType::LeadName)
		{
			$entity = new \CCrmLead(false);
		}
		else if ($entityType == \CCrmOwnerType::CompanyName)
		{
			$entity = new \CCrmCompany(false);
		}
		else if ($entityType == \CCrmOwnerType::ContactName)
		{
			$entity = new \CCrmContact(false);
		}
		else if ($entityType == \CCrmOwnerType::DealName)
		{
			$entity = new \CCrmDeal(false);
		}
		else
		{
			return false;
		}
		$data = $entity->GetByID($entityId, false);

		if ($withMultiFields)
		{
			$multiFields = new \CCrmFieldMulti();
			$res = $multiFields->GetList(Array(), Array(
				'ENTITY_ID' => $entityType,
				'ELEMENT_ID' => $entityId
			));
			while ($row = $res->Fetch())
			{
				$data['FM'][$row['TYPE_ID']][$row['VALUE_TYPE']][] = $row['VALUE'];
			}
		}

		return $data;
	}
}
?>
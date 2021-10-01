<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CrmCardShowComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		global $USER;
		$this->arResult = array();
		$this->arResult['SIMPLE'] = true;
		$entity = $this->getEntityData(
			$this->arParams['ENTITY_TYPE'],
			$this->arParams['ENTITY_ID'],
			$USER->GetID()
		);

		if($entity)
		{
			$this->arResult['FOUND'] = true;
			$this->arResult['SLIDER_ENABLED'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();
			$this->arResult['ENTITY'] = $entity;

			$photoId = null;
			if(isset($entity['PHOTO']) && $entity['PHOTO'] > 0)
				$photoId = $entity['PHOTO'];
			else if(isset($entity['LOGO']) && $entity['LOGO'] > 0)
				$photoId = $entity['LOGO'];

			if($photoId)
			{
				$photoInfo = CFile::ResizeImageGet($photoId, array('width' => 300, 'height' => 300), BX_RESIZE_IMAGE_EXACT);
				if(is_array($photoInfo) && isset($photoInfo['src']))
				{
					$this->arResult['ENTITY']['PHOTO_URL'] = $photoInfo['src'];
				}
			}

			if(is_array($entity['ACTIVITIES']) && count($entity['ACTIVITIES']) > 0
				|| is_array($entity['DEALS']) && count($entity['DEALS']) > 0
				|| is_array($entity['INVOICES']) && count($entity['INVOICES']) > 0
			)
			{
				$this->arResult['SIMPLE'] = false;
			}

			if($this->arResult['ENTITY']['FORMATTED_NAME'] == '' && $this->arResult['ENTITY']['TITLE'] != '')
				$this->arResult['ENTITY']['FORMATTED_NAME'] = $this->arResult['ENTITY']['TITLE'];

			$this->arResult['ENTITY']['VK_PROFILE'] = $this->getVkProfile(
				$this->arParams['ENTITY_TYPE'],
				$this->arParams['ENTITY_ID']
			);

			$this->arResult['ENTITY']['RESPONSIBLE'] = $this->getUserInfo($this->arResult['ENTITY']['ASSIGNED_BY_ID']);
		}
		else
		{
			$this->arResult['FOUND'] = false;
			$this->arResult['ENTITY'] = array(
				'FORMATTED_NAME' => Loc::getMessage('CRM_CARD_NAME_UNKNOWN'),
			);
		}

		$this->includeComponentTemplate();
		return $this->arResult;
	}

	protected function getEntityData($entityType, $entityId, $userId)
	{
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if (!CCrmAuthorizationHelper::CheckReadPermission($entityType, $entityId, $userPermissions))
		{
			return false;
		}

		$findParams = array('USER_ID'=> $userId);

		$entityTypeId = CCrmOwnerType::ResolveID($entityType);
		$entityId = (int)$entityId;
		return CCrmSipHelper::getEntityFields($entityTypeId, $entityId, $findParams);
	}

	protected function getVkProfile($entityType, $entityId)
	{
		$cursor = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityId)
		);
		while ($row = $cursor->Fetch())
		{
			if($row['TYPE_ID'] === 'WEB' && $row['VALUE_TYPE'] === 'VK')
			{
				return $row['VALUE'];
			}
		}
		return '';
	}

	protected function getUserInfo($userId)
	{
		$user = Bitrix\Main\UserTable::getById($userId)->fetch();
		if (!$user)
			return false;

		$userPhoto = \CFile::ResizeImageGet(
			$user['PERSONAL_PHOTO'],
			array('width' => 37, 'height' => 37),
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true
		);

		return array(
			'ID' => $user['ID'],
			'NAME' => \CUser::FormatName(CSite::GetNameFormat(false), $user, true, false),
			'PHOTO' => $userPhoto ? $userPhoto['src']: '',
			'POST' => $user['WORK_POSITION'],
			'PROFILE_PATH' => CComponentEngine::makePathFromTemplate($this->getPathToUserProfile(), array(
				'user_id' => $user['ID']
			))
		);
	}

	protected function getPathToUserProfile()
	{
		$pathToUser = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", false, SITE_ID);
		return ($pathToUser ? $pathToUser : SITE_DIR."company/personal/user/#user_id#/");
	}
}
<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CrmCardShowComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		global $USER;

		$this->arResult = [];
		$this->arResult['SIMPLE'] = true;
		$entity = $this->getEntityData(
			$this->arParams['ENTITY_TYPE'],
			$this->arParams['ENTITY_ID'],
			$USER->GetID()
		);

		if ($entity)
		{
			$this->arResult['FOUND'] = true;
			$this->arResult['SLIDER_ENABLED'] = LayoutSettings::getCurrent()?->isSliderEnabled();
			$this->arResult['ENTITY'] = $entity;

			$photoId = null;
			if (isset($entity['PHOTO']) && $entity['PHOTO'] > 0)
			{
				$photoId = $entity['PHOTO'];
			}
			else if (isset($entity['LOGO']) && $entity['LOGO'] > 0)
			{
				$photoId = $entity['LOGO'];
			}

			if ($photoId)
			{
				$photoInfo = CFile::ResizeImageGet(
					$photoId,
					[
						'width' => 300,
						'height' => 300
					],
					BX_RESIZE_IMAGE_EXACT
				);

				if (is_array($photoInfo) && isset($photoInfo['src']))
				{
					$this->arResult['ENTITY']['PHOTO_URL'] = $photoInfo['src'];
				}
			}

			if (
				$this->isValidData($entity['ACTIVITIES'] ?? [])
				|| $this->isValidData($entity['DEALS'] ?? [])
				||$this->isValidData($entity['INVOICES'] ?? [])
			)
			{
				$this->arResult['SIMPLE'] = false;
			}

			if (
				empty($this->arResult['ENTITY']['FORMATTED_NAME'])
				&& !empty($this->arResult['ENTITY']['TITLE'])
			)
			{
				$this->arResult['ENTITY']['FORMATTED_NAME'] = $this->arResult['ENTITY']['TITLE'];
			}

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

		$this->arResult['isEnableCopilotReplacement'] = $this->isEnableCopilotReplacement();

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

		return CCrmSipHelper::getEntityFields(
			CCrmOwnerType::ResolveID($entityType),
			(int)$entityId,
			['USER_ID'=> $userId]
		);
	}

	protected function getVkProfile($entityType, $entityId)
	{
		$cursor = CCrmFieldMulti::GetList(
			[
				'ID' => 'asc',
			],
			[
				'ENTITY_ID' => $entityType,
				'ELEMENT_ID' => $entityId,
			]
		);

		while ($row = $cursor->Fetch())
		{
			if ($row['TYPE_ID'] === 'WEB' && $row['VALUE_TYPE'] === 'VK')
			{
				return $row['VALUE'];
			}
		}

		return '';
	}

	protected function getUserInfo($userId)
	{
		$user = Bitrix\Main\UserTable::getById($userId)->fetch();
		if (!$user) {
			return false;
		}

		$userPhoto = \CFile::ResizeImageGet(
			$user['PERSONAL_PHOTO'],
			[
				'width' => 37,
				'height' => 37,
			],
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true
		);

		return [
			'ID' => $user['ID'],
			'NAME' => \CUser::FormatName(CSite::GetNameFormat(false), $user, true, false),
			'PHOTO' => $userPhoto ? $userPhoto['src']: '',
			'POST' => $user['WORK_POSITION'],
			'PROFILE_PATH' => CComponentEngine::makePathFromTemplate(
				$this->getPathToUserProfile(),
				['user_id' => $user['ID']]
			)
		];
	}

	protected function getPathToUserProfile()
	{
		$pathToUser = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", false, SITE_ID);

		return ($pathToUser ?: SITE_DIR . "company/personal/user/#user_id#/");
	}

	protected function isEnableCopilotReplacement(): bool
	{
		return
			($this->arParams['isEnableCopilotReplacement'] ?? true)
			&& AIManager::isAiCallProcessingEnabled()
			&& AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment)
			&& Container::getInstance()->getUserPermissions()->canReadCopilotCallAssessmentSettings()
		;
	}

	private function isValidData(mixed $input): bool
	{
		return isset($input) && is_array($input) && count($input) > 0;
	}
}

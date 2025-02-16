<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die;
}

CModule::IncludeModule("crm");

use Bitrix\Crm\Component\EntityDetails\Traits\EditorInitialMode;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Component\EntityDetails\Error;

Loc::loadMessages(__FILE__);

class CCrmEntityErrorComponent extends CBitrixComponent
{
	use EditorInitialMode;

	private int $entityTypeID = CCrmOwnerType::Undefined;

	private int $entityID = 0;

	public function getEntityId(): int
	{
		return $this->entityID;
	}

	public function executeComponent()
	{
		$error = $this->arParams['ERROR'];
		$entityTypeId = $this->arParams['ENTITY_TYPE_ID'];

		$this->arResult['ERROR'] = $error;
		$this->arResult['ERROR_MESSAGE'] = Error::getErrorMessage($error, $entityTypeId);
		$this->arResult['IMAGE'] = $this->getImage($error);

		$this->includeComponentTemplate();

		return $this->arResult;
	}

	private function getImage(mixed $error): string
	{
		switch ($error)
		{
			case Error::EntityNotExist:
				return '<div class="crm-entity-details-error-image"></div>';
			case Error::NoAddPermission:
			case Error::NoAccessToEntityType:
				return '<div class="crm-entity-details-error-image-no-access-error"></div>';
			case Error::NoReadPermission:
				return '<div class="crm-entity-details-error-image-no-read-permission-error"></div>';
		}
		return '';
	}
}

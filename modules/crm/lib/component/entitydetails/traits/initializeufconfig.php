<?php

namespace Bitrix\Crm\Component\EntityDetails\Traits;

use Bitrix\Crm\UserField\Router;

trait InitializeUFConfig
{
	abstract protected function getCategoryId();

	private function initializeUFConfig(): void
	{
		$enableUfCreation = \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission();

		$this->arResult['ENABLE_USER_FIELD_CREATION'] = $enableUfCreation;
		$this->arResult['USER_FIELD_ENTITY_ID'] = $this->userFieldEntityID;
		$this->arResult['USER_FIELD_CREATE_PAGE_URL'] = \CCrmOwnerType::GetUserFieldEditUrl($this->userFieldEntityID,
			0);
		$this->arResult['USER_FIELD_CREATE_SIGNATURE'] =
			$enableUfCreation
				? $this->userFieldDispatcher->getCreateSignature(['ENTITY_ID' => $this->userFieldEntityID])
				: '';

		if ($this->factory->isCategoriesSupported())
		{
			$ufCreatePageUrl = (new Router($this->userFieldEntityID))->getEditUrlByCategory($this->getCategoryId());
		}
		else
		{
			$ufCreatePageUrl = (new Router($this->userFieldEntityID))->getEditUrl();
		}

		$this->arResult['USER_FIELD_CREATE_PAGE_URL'] = $ufCreatePageUrl;
		$this->arResult['USER_FIELD_FILE_URL_TEMPLATE'] = $this->getFileUrlTemplate();
	}

	protected function getFileUrlTemplate(): string
	{
		$entityTypeName = mb_strtolower($this->factory->getEntityName());

		return "/bitrix/components/bitrix/crm.{$entityTypeName}.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#";
	}
}

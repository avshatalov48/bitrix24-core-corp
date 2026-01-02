<?php

use Bitrix\Crm\Component\Utils\OldEntityViewDisableHelper;
use Bitrix\Crm\Service\Container;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmEntityDisableAlert extends CBitrixComponent
{
	public function executeComponent(): void
	{
		if (!$this->checkPermissions())
		{
			return;
		}

		$this->prepareParams();
		$this->includeComponentTemplate();
	}

	private function getPreviewHref(): string
	{
		if (!isset($this->arParams['ENTITY_TYPE_ID'], $this->arParams['ENTITY_ID']))
		{
			return '';
		}

		$entityTypeId = (int)$this->arParams['ENTITY_TYPE_ID'];
		$entityId = (int)$this->arParams['ENTITY_ID'];

		if ($entityTypeId <= 0 || $entityId <= 0)
		{
			return '';
		}

		$urlString = Container::getInstance()->getRouter()->getItemDetailUrl($entityTypeId, $entityId);
		$urlString->setPath(str_replace('/show/', '/details/', $urlString));

		$params = [
			'FORCE_READONLY' => 'Y',
		];
		$urlString->addParams($params);

		return $urlString;
	}

	private function checkPermissions(): bool
	{
		return Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions()
			->item()
			->canRead($this->arParams['ENTITY_TYPE_ID'], $this->arParams['ENTITY_ID'])
		;
	}

	private function prepareParams(): void
	{
		$this->arResult['jsParams'] = [
			'daysUntilDisable' => OldEntityViewDisableHelper::getDaysLeftUntilDisable(),
			'isAdmin' => Container::getInstance()->getUserPermissions()->isCrmAdmin(),
			'lastTimeShownField' => OldEntityViewDisableHelper::LAST_TIME_SHOWN_FIELD,
			'lastTimeShownOptionName' => OldEntityViewDisableHelper::LAST_TIME_SHOWN_OPTION_NAME,
			'previewHref' => $this->getPreviewHref(),
		];
	}
}

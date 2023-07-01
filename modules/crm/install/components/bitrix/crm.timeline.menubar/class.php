<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('crm');

class CrmTimelineMenuBarComponent extends \CBitrixComponent
{
	private int $entityTypeId = 0;
	private int $entityId = 0;
	private ?int $entityCategoryId = null;
	private string $guid = 'timeline';
	private bool $isReadonly = false;

	private function init(): void
	{
		$this->entityTypeId = (int)($this->arParams['ENTITY_TYPE_ID'] ?? 0);

		$this->entityId = (int)($this->arParams['ENTITY_ID'] ?? 0);
		if ($this->entityId <= 0)
		{
			$this->entityId = 0;
		}

		$this->entityCategoryId = (int)($this->arParams['ENTITY_CATEGORY_ID'] ?? -1);
		$this->entityCategoryId = ($this->entityCategoryId >= 0) ? $this->entityCategoryId : null;

		$this->guid = (string)($this->arParams['GUID'] ?? $this->guid);
		$this->isReadonly = (bool)($this->arParams['READ_ONLY'] ?? false);

		$this->arResult['guid'] = $this->guid;
		$this->arResult['entityTypeId'] = $this->entityTypeId;
		$this->arResult['entityId'] = $this->entityId;
		$this->arResult['isReadonly'] = $this->isReadonly;
	}

	public function executeComponent()
	{
		$this->init();

		if (!CCrmOwnerType::IsDefined($this->entityTypeId))
		{
			return;
		}

		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkReadPermissions(
			$this->entityTypeId,
			$this->entityId,
			$this->entityCategoryId
		))
		{
			return;
		}

		$repoContext = new \Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Context($this->entityTypeId, $this->entityId);
		$repoContext->setGuid($this->guid);
		$repoContext->setIsReadonly($this->isReadonly);
		$repoContext->setEntityCategoryId($this->entityCategoryId);

		$repo = new \Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Repository($repoContext);
		$this->arResult['items'] = $repo->getAvailableItems();

		$this->includeComponentTemplate();
	}
}

<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Container;

Main\Loader::includeModule('crm');

class CrmItemAutomation extends \Bitrix\Crm\Component\Base
{
	/**
	 * @var \Bitrix\Crm\Category\Entity\Category
	 */
	protected $category;
	protected $categories;
	protected $description;

	public function onPrepareComponentParams($arParams): array
	{
		$this->fillParameterFromRequest('categoryId', $arParams);
		$this->fillParameterFromRequest('entityTypeId', $arParams);
		$this->fillParameterFromRequest('id', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();
		if ($this->getErrors())
		{
			return;
		}

		if (!$this->isIframe())
		{
			return;
		}

		if (!Main\Loader::includeModule('bizproc'))
		{
			$this->errorCollection[] = new Main\Error(Loc::getMessage('CRM_ITEM_AUTOMATION_BP_MODULE_NOT_INSTALLED'));
			return;
		}

		$this->entityTypeId = (int)$this->arParams['entityTypeId'];

		$factory = Container::getInstance()->getFactory($this->entityTypeId);

		if (is_null($factory))
		{
			$this->errorCollection[] = new Main\Error(Loc::getMessage('CRM_ITEM_AUTOMATION_INVALID_ENTITY_TYPE'));
			return;
		}
		$this->description = $factory->getEntityDescription();
		if ($factory->isCategoriesEnabled())
		{
			$categoryId = (int)$this->arParams['categoryId'];
			$this->category = $factory->getCategory($categoryId);

			if (!$this->category)
			{
				$this->errorCollection[] = new Main\Error(Loc::getMessage('CRM_ITEM_AUTOMATION_WRONG_CATEGORY'));
				return;
			}
			$categories = Container::getInstance()->getUserPermissions()->filterAvailableForReadingCategories(
				$factory->getCategories()
			);
			foreach ($categories as $category)
			{
				$this->categories[] = [
					'text' => htmlspecialcharsbx($category->getName()),
					'link' => Container::getInstance()->getRouter()->getAutomationUrl(
						$this->entityTypeId,
						$category->getId()
					),
				];
			}
		}
	}

	public function executeComponent()
	{
		$this->init();

		if ($this->getErrors())
		{
			$this->includeComponentTemplate('error');
			return false;
		}

		if (!$this->isIframe())
		{
			$this->includeComponentTemplate('list');
			return false;
		}

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeId;
		$this->arResult['PAGE_TITLE'] =
			$this->entityTypeId === CCrmOwnerType::SmartDocument
				? Loc::getMessage('CRM_ITEM_AUTOMATION_PAGETITLE_AUTOMATION')
				: Loc::getMessage('CRM_ITEM_AUTOMATION_PAGETITLE')
		;
		$this->arResult['PAGE_SUBTITLE'] = \CCrmOwnerType::GetCategoryCaption($this->entityTypeId);

		$this->arResult['BACK_URL'] = Container::getInstance()->getRouter()->getAutomationUrl(
			$this->entityTypeId,
			isset($this->category) ? $this->category->getId() : null
		);
		if ($this->category)
		{
			$this->arResult['CATEGORY_NAME'] = $this->category->getName();
			$this->arResult['ENTITY_CATEGORY_ID'] = $this->category->getId();
			$this->arResult['CATEGORIES'] = $this->categories;
		}

		return $this->includeComponentTemplate();
	}
}

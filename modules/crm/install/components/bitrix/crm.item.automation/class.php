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

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();
		if ($this->getErrors())
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

		$this->arResult['DOCUMENT_NAME'] = CCrmBizProcHelper::ResolveDocumentName($this->entityTypeId);
		$this->arResult['DOCUMENT_TYPE'] = CCrmOwnerType::ResolveName($this->entityTypeId);
		$this->arResult['DOCUMENT_ID'] = $this->arResult['DOCUMENT_TYPE'] . '_' . 0;
		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeId;
		$this->arResult['PAGE_TITLE'] = Loc::getMessage('CRM_ITEM_AUTOMATION_TITLE', [
			'#ENTITY#' => htmlspecialcharsbx($this->description),
		]);
		$this->arResult['BACK_URL'] = Container::getInstance()->getRouter()->getAutomationUrl(
			$this->entityTypeId,
			isset($this->category) ? $this->category->getId() : null
		);
		if ($this->category)
		{
			$this->arResult['CATEGORY_NAME'] = htmlspecialcharsbx($this->category->getName());
			$this->arResult['ENTITY_CATEGORY_ID'] = $this->category->getId();
			$this->arResult['PAGE_TITLE'] = Loc::getMessage('CRM_ITEM_AUTOMATION_TITLE_CATEGORY', [
				'#ENTITY#' => htmlspecialcharsbx($this->description),
				'#CATEGORY#' => htmlspecialcharsbx($this->category->getName()),
			]);
			$this->arResult['CATEGORIES'] = $this->categories;
		}

		return $this->includeComponentTemplate();
	}
}

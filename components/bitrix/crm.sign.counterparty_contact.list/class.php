<?php

use Bitrix\Crm\Service\Container;
use Bitrix\Main;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Class CrmStoreContractorContactListComponent
 */
class CrmSignCounterpartyListComponent extends \CBitrixComponent
{
	/** @var bool bool */
	private $isIframe;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->isIframe = (
			$this->request->get('IFRAME') === 'Y'
			&& $this->request->get('IFRAME_TYPE') === 'SIDE_SLIDER'
		);
	}

	public function executeComponent()
	{
		if (!Main\Loader::includeModule('sign'))
		{
			ShowError('Catalog sign is not installed');

			return;
		}

		$category = Container::getInstance()
			->getFactory(\CCrmOwnerType::Contact)
			->getCategoryByCode(\Bitrix\Crm\Service\Factory\SmartDocument::CONTACT_CATEGORY_CODE)
		;

		if (!$category)
		{
			ShowError('Category has not been found');

			return;
		}
		$categoryId = $category->getId();

		$this->arResult['PATH_TO_LIST'] = $this->arParams['PATH_TO']['COUNTERPARTY_CONTACTS'] ?? '';
		$this->arResult['CATEGORY_ID'] = $categoryId;
		$this->arResult['MENU_ITEMS'] = $this->arParams['MENU_ITEMS'] ?? [];

		$this->includeComponentTemplate();
	}

	/**
	 * @return bool
	 */
	public function isIframeMode(): bool
	{
		return $this->isIframe;
	}
}

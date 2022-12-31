<?php

use Bitrix\Main;
use Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Class CrmStoreContractorListComponent
 */
class CrmStoreContractorListComponent extends \CBitrixComponent
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
		if (!Main\Loader::includeModule('catalog'))
		{
			ShowError('Catalog module is not installed');

			return;
		}

		$categoryId = CategoryRepository::getIdByEntityTypeId(\CCrmOwnerType::Company);
		if (!$categoryId)
		{
			ShowError('Category has not been found');

			return;
		}

		$this->arResult['PATH_TO_LIST'] = $this->arParams['PATH_TO']['CONTRACTORS'] ?? '';
		$this->arResult['CATEGORY_ID'] = $categoryId;

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

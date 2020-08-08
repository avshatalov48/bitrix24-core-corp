<?php
namespace Bitrix\Crm\Product\Url;

use Bitrix\Main\Loader;

if (Loader::includeModule('catalog'))
{
	class ProductBuilder extends ShopBuilder
	{
		public const TYPE_ID = 'CRM';

		protected const TYPE_WEIGHT = 400;

		protected const PATH_PREFIX = '/crm/catalog/';

		public function use(): bool
		{
			return (defined('CATALOG_PRODUCT') && defined('CRM_MODE'));
		}

		protected function initUrlTemplates(): void
		{
			$this->urlTemplates[self::PAGE_SECTION_LIST] = '#PATH_PREFIX#'
				.($this->iblockListMixed ? 'list/' : 'section_list/')
				.'#PARENT_ID#/'
				.'?#BASE_PARAMS#'
				.'#PARENT_FILTER#'
				.'#ADDITIONAL_PARAMETERS#';
			$this->urlTemplates[self::PAGE_SECTION_DETAIL] = '#PATH_PREFIX#'
				.'section/'
				.'#ENTITY_ID#/'
				.'?#BASE_PARAMS#'
				.'&ID=#ENTITY_ID#'
				.'#ADDITIONAL_PARAMETERS#';
			$this->urlTemplates[self::PAGE_SECTION_COPY] = $this->urlTemplates[self::PAGE_SECTION_DETAIL]
				.$this->getCopyAction();
			$this->urlTemplates[self::PAGE_SECTION_SAVE] = '/bitrix/tools/crm/section_save.php'
				.'?#BASE_PARAMS#'
				.'#ADDITIONAL_PARAMETERS#';
			$this->urlTemplates[self::PAGE_SECTION_SEARCH] = '/bitrix/tools/iblock/section_search.php'
				.'?#LANGUAGE#'
				.'#ADDITIONAL_PARAMETERS#';

			$this->urlTemplates[self::PAGE_ELEMENT_LIST] = '#PATH_PREFIX#'
				.'list/'
				.'#PARENT_ID#/'
				.'?#BASE_PARAMS#'
				.'#PARENT_FILTER#'
				.'#ADDITIONAL_PARAMETERS#';
			if ($this->isUiCatalog())
			{
				$this->urlTemplates[self::PAGE_ELEMENT_DETAIL] = '/shop/catalog/'
					.'#IBLOCK_ID#/product/#ENTITY_ID#/';
				$this->urlTemplates[self::PAGE_ELEMENT_COPY] = '/shop/catalog/'
					.'#IBLOCK_ID#/product/0/copy/#ENTITY_ID#/';
				$this->urlTemplates[self::PAGE_ELEMENT_SAVE] = $this->urlTemplates[self::PAGE_ELEMENT_DETAIL];
			}
			else
			{
				$this->urlTemplates[self::PAGE_ELEMENT_DETAIL] = '#PATH_PREFIX#'
					.'product/'
					.'#ENTITY_ID#/'
					.'?#BASE_PARAMS#'
					.'&ID=#ENTITY_ID#'
					.'#ADDITIONAL_PARAMETERS#';
				$this->urlTemplates[self::PAGE_ELEMENT_COPY] = $this->urlTemplates[self::PAGE_ELEMENT_DETAIL]
					.$this->getCopyAction();
				$this->urlTemplates[self::PAGE_ELEMENT_SAVE] = $this->urlTemplates[self::PAGE_ELEMENT_DETAIL];
			}
			$this->urlTemplates[self::PAGE_ELEMENT_SEARCH] = '/bitrix/tools/iblock/element_search.php'
				.'?#LANGUAGE#'
				.'#ADDITIONAL_PARAMETERS#';
		}
	}
}
<?php
namespace Bitrix\Crm\Product\Url;

use Bitrix\Main\Loader,
	Bitrix\Catalog;

if (Loader::includeModule('catalog'))
{
	class ShopBuilder extends Catalog\Url\AdminPage\CatalogBuilder
	{
		public const TYPE_ID = 'SHOP';

		protected const TYPE_WEIGHT = 300;

		protected const PATH_PREFIX = '/shop/settings/';

		public function use(): bool
		{
			return (defined('CATALOG_PRODUCT') && defined('SELF_FOLDER_URL'));
		}

		protected function addSliderOptions(array &$options): void
		{
			$options['publicSidePanel'] = 'Y';
			$options['IFRAME'] = 'Y';
			$options['IFRAME_TYPE'] = 'SIDE_SLIDER';
		}

		protected function initConfig(): void
		{
			parent::initConfig();
			$this->config['UI_CATALOG'] = Catalog\Config\State::isProductCardSliderEnabled();
		}

		protected function isUiCatalog(): bool
		{
			return (isset($this->config['UI_CATALOG']) && $this->config['UI_CATALOG']);
		}

		protected function initUrlTemplates(): void
		{
			$this->urlTemplates[self::PAGE_SECTION_LIST] = '#PATH_PREFIX#'
				.($this->iblockListMixed ? 'menu_catalog_goods_#IBLOCK_ID#/' : 'menu_catalog_category_#IBLOCK_ID#/')
				.'?#BASE_PARAMS#'
				.'#PARENT_FILTER#'
				.'#ADDITIONAL_PARAMETERS#';
			$this->urlTemplates[self::PAGE_SECTION_DETAIL] = '#PATH_PREFIX#'
				.'cat_section_edit/'
				.'?#BASE_PARAMS#'
				.'&ID=#ENTITY_ID#'
				.'#ADDITIONAL_PARAMETERS#';
			$this->urlTemplates[self::PAGE_SECTION_COPY] = $this->urlTemplates[self::PAGE_SECTION_DETAIL]
				.$this->getCopyAction();
			$this->urlTemplates[self::PAGE_SECTION_SAVE] = '#PATH_PREFIX#'
				.'cat_section_edit.php'
				.'?#BASE_PARAMS#'
				.'#ADDITIONAL_PARAMETERS#';
			$this->urlTemplates[self::PAGE_SECTION_SEARCH] = '/bitrix/tools/iblock/section_search.php'
				.'?#LANGUAGE#'
				.'#ADDITIONAL_PARAMETERS#';

			$this->urlTemplates[self::PAGE_ELEMENT_LIST] = '#PATH_PREFIX#'
				.($this->iblockListMixed ? 'menu_catalog_goods_#IBLOCK_ID#/' : 'menu_catalog_#IBLOCK_ID#/')
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
					.'cat_product_edit/'
					.'?#BASE_PARAMS#'
					.'&ID=#ENTITY_ID#'
					.'#ADDITIONAL_PARAMETERS#';
				$this->urlTemplates[self::PAGE_ELEMENT_COPY] = $this->urlTemplates[self::PAGE_ELEMENT_DETAIL]
					.$this->getCopyAction();
				$this->urlTemplates[self::PAGE_ELEMENT_SAVE] = '#PATH_PREFIX#'
					.'cat_product_edit.php'
					.'?#BASE_PARAMS#'
					.'#ADDITIONAL_PARAMETERS#';
			}
			$this->urlTemplates[self::PAGE_ELEMENT_SEARCH] = '/bitrix/tools/iblock/element_search.php'
				.'?#LANGUAGE#'
				.'#ADDITIONAL_PARAMETERS#';
		}
	}
}
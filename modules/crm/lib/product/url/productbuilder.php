<?php
namespace Bitrix\Crm\Product\Url;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (Loader::includeModule('catalog'))
{
	class ProductBuilder extends ShopBuilder
	{
		public const TYPE_ID = 'CRM';

		protected const TYPE_WEIGHT = 400;

		protected const PATH_PREFIX = '/crm/catalog/';

		private const OLD_PRODUCT_PATH_PREFIX = '/crm/product/';

		public const PAGE_CSV_IMPORT = 'csvImport';

		public function use(): bool
		{
			if (defined('CATALOG_PRODUCT') && defined('CRM_MODE'))
			{
				return true;
			}
			if (!$this->request->isAdminSection())
			{
				if ($this->checkCurrentPage([
					self::PATH_PREFIX,
					self::OLD_PRODUCT_PATH_PREFIX
				]))
				{
					return true;
				}
			}

			return false;
		}

		public function getContextMenuItems(string $pageType, array $items = [], array $options = []): ?array
		{
			if ($pageType !== self::PAGE_ELEMENT_LIST && $pageType !== self::PAGE_SECTION_LIST)
			{
				return null;
			}

			$result = [];

			$importUrl = $this->fillUrlTemplate(
				$this->getUrlTemplate(self::PAGE_CSV_IMPORT),
				$this->templateVariables
			);
			if ($importUrl !== null)
			{
				$result[] = [
					'TEXT' => Loc::getMessage('CRM_PRODUCT_BUILDER_CONTEXT_MENU_ITEM_CSV_IMPORT_NAME'),
					'TITLE' => Loc::getMessage('CRM_PRODUCT_BUILDER_CONTEXT_MENU_ITEM_CSV_IMPORT_TITLE'),
					'ONCLICK' => "location.href='".htmlspecialcharsbx($importUrl)."'"
				];
			}
			unset($importUrl);
			if (!empty($items))
			{
				$result = array_merge($result, $items);
			}

			return (!empty($result) ? $result: null);
		}

		protected function initUrlTemplates(): void
		{
			if ($this->isUiCatalog())
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
				$this->urlTemplates[self::PAGE_ELEMENT_DETAIL] = '#PATH_PREFIX#'
					.'#IBLOCK_ID#/product/#ENTITY_ID#/'
					.'?#ADDITIONAL_PARAMETERS#';
				$this->urlTemplates[self::PAGE_ELEMENT_COPY] = '#PATH_PREFIX#'
					.'#IBLOCK_ID#/product/0/copy/#ENTITY_ID#/';
				$this->urlTemplates[self::PAGE_ELEMENT_SAVE] = $this->urlTemplates[self::PAGE_ELEMENT_DETAIL];
				$this->urlTemplates[self::PAGE_OFFER_DETAIL] = '#PATH_PREFIX#'
					.'#PRODUCT_IBLOCK_ID#/product/#PRODUCT_ID#/'
					.'variation/#ENTITY_ID#/';
				$this->urlTemplates[self::PAGE_ELEMENT_SEARCH] = '/bitrix/tools/iblock/element_search.php'
					.'?#LANGUAGE#'
					.'#ADDITIONAL_PARAMETERS#';
			}
			else
			{
				$this->setPrefix(self::OLD_PRODUCT_PATH_PREFIX);

				$this->urlTemplates[self::PAGE_SECTION_LIST] = '#PATH_PREFIX#'
					.'section_list/'
					.'#PARENT_ID#/'
					.'?tree=Y';

				$this->urlTemplates[self::PAGE_ELEMENT_LIST] = '#PATH_PREFIX#'
					.'list/'
					.'#PARENT_ID#/'
					.'?tree=Y';

				$this->urlTemplates[self::PAGE_ELEMENT_DETAIL] = '#PATH_PREFIX#'
					.'show/'
					.'#ENTITY_ID#/';
				$this->urlTemplates[self::PAGE_OFFER_DETAIL] = '#PATH_PREFIX#'
					.'show/'
					.'#PRODUCT_ID#/';
			}

			$this->urlTemplates[self::PAGE_CSV_IMPORT] = '#PATH_PREFIX#'
				.'import/';
		}
	}
}

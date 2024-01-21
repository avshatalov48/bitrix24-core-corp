<?php

use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\CatalogMobile\Controller\StoreDocumentList;
use Bitrix\CatalogMobile\Controller\RealizationDocumentList;
use Bitrix\CatalogMobile\PermissionsProvider;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$component = new class {
	private const NOT_CONDUCTED = 'N';
	private const CONDUCTED = 'Y';
	private const CANCELLED = 'C';

	private const TYPE_LOGO_PATTERN = '/bitrix/mobileapp/catalogmobile/extensions/catalog/store/document-card/component/images/type_#DOCUMENT_TYPE#.png';

	use ErrorableImplementation;

	private $result = [];

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
	}

	private function checkModules(): void
	{
		try
		{
			Loader::requireModule('mobile');
			Loader::requireModule('catalog');
			Loader::requireModule('crm');
			Loader::requireModule('currency');
			Loader::requireModule('sale');
		}
		catch (LoaderException $exception)
		{
			$this->errorCollection[] = new Error($exception->getMessage(), $exception->getCode());
		}
	}

	private function showErrors(): array
	{
		return ['errors' => $this->getErrors()];
	}

	private function getDetailNavigation(): array
	{
		return [
			'types' => $this->getDocumentTypes(),
		];
	}

	private function getDocumentTypes(): array
	{
		$types = [];

		$typeList = array_merge(
			StoreDocumentTable::getTypeList(true),
			['W' => Loc::getMessage('M_CSDL_TAB_REALIZATION_DESC_MSGVER_1')]
		);

		foreach ($typeList as $type => $name)
		{
			$types[] = [
				'id' => $type,
				'name' => $name,
				'logo' => $this->getDocumentTypeLogo($type),
			];
		}

		return $types;
	}

	private function getDocumentTypeLogo(string $type): ?string
	{
		$docRoot = Context::getCurrent()->getServer()->getDocumentRoot();
		$path = str_replace('#DOCUMENT_TYPE#', mb_strtolower($type), self::TYPE_LOGO_PATTERN);

		$file = new Bitrix\Main\IO\File($docRoot . $path);
		if (!$file->isExists() || !$file->isReadable())
		{
			return null;
		}

		return $path;
	}

	private function getDocumentTabs(): array
	{
		$accessController = \Bitrix\Catalog\Access\AccessController::getCurrent();
		$documentTabs = [];
		if (
			$accessController->checkByValue(ActionDictionary::ACTION_STORE_DOCUMENT_VIEW, StoreDocumentTable::TYPE_ARRIVAL)
			|| $accessController->checkByValue(ActionDictionary::ACTION_STORE_DOCUMENT_VIEW, StoreDocumentTable::TYPE_STORE_ADJUSTMENT)
		)
		{
			$documentTabs[] = [
				'id' => 'receipt_adjustment',
				'title' => Loc::getMessage('M_CSDL_TAB_RECEIPT_ADJUSTMENT'),
				'documentTypes' => [
					[
						'id' => StoreDocumentTable::TYPE_ARRIVAL,
						'title' => Loc::getMessage('M_CSDL_TAB_MENU_ARRIVAL'),
					],
					[
						'id' => StoreDocumentTable::TYPE_STORE_ADJUSTMENT,
						'title' => Loc::getMessage('M_CSDL_TAB_MENU_STORE_ADJUSTMENT'),
					],
				],
			];
		}

		if ($accessController->checkByValue(ActionDictionary::ACTION_STORE_DOCUMENT_VIEW, StoreDocumentTable::TYPE_SALES_ORDERS))
		{
			$documentTabs[] = [
				'id' => 'shipment',
				'title' => Loc::getMessage('M_CSDL_TAB_REALIZATION'),
				'documentTypes' => [
					[
						'id' => StoreDocumentTable::TYPE_SALES_ORDERS,
						'title' => Loc::getMessage('M_CSDL_TAB_REALIZATION'),
					],
				],
			];
		}

		if ($accessController->checkByValue(ActionDictionary::ACTION_STORE_DOCUMENT_VIEW, StoreDocumentTable::TYPE_MOVING))
		{
			$documentTabs[] = [
				'id' => 'moving',
				'title' => Loc::getMessage('M_CSDL_TAB_MOVING'),
				'documentTypes' => [
					[
						'id' => StoreDocumentTable::TYPE_MOVING,
						'title' => Loc::getMessage('M_CSDL_TAB_MENU_MOVING'),
					],
				],
			];
		}

		if ($accessController->checkByValue(ActionDictionary::ACTION_STORE_DOCUMENT_VIEW, StoreDocumentTable::TYPE_DEDUCT))
		{
			$documentTabs[] = [
				'id' => 'deduct',
				'title' => Loc::getMessage('M_CSDL_TAB_DEDUCT'),
				'documentTypes' => [
					[
						'id' => StoreDocumentTable::TYPE_DEDUCT,
						'title' => Loc::getMessage('M_CSDL_TAB_MENU_DEDUCT'),
					],
				],
			];
		}

		return $documentTabs;
	}

	public function execute(): array
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		if (!CAllCrmInvoice::installExternalEntities())
		{
			$this->errorCollection[] = new Error('Could not install external entities', 2494608);
		}
		elseif (!CCrmQuote::LocalComponentCausedUpdater())
		{
			$this->errorCollection[] = new Error('Could not install external entities', 2623264);
		}

		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		return [
			'detailNavigation' => $this->getDetailNavigation(),
			'documentTabs' => $this->getDocumentTabs(),
			'actions' => [
				'storeDocumentActions' => StoreDocumentList::getActionsList(),
				'realizationDocumentActions' => RealizationDocumentList::getActionsList(),
			],
			'permissions' => PermissionsProvider::getInstance()->getPermissions(),
			'floatingMenuTypes' => $this->getFloatingMenuTypes(),
		];
	}

	private function getFloatingMenuTypes(): array
	{
		return [
			[
				'id' => StoreDocumentTable::TYPE_ARRIVAL,
				'title' => Loc::getMessage('M_CSDL_TAB_MENU_ARRIVAL'),
			],
			[
				'id' => StoreDocumentTable::TYPE_STORE_ADJUSTMENT,
				'title' => Loc::getMessage('M_CSDL_TAB_MENU_STORE_ADJUSTMENT'),
			],
			[
				'id' => StoreDocumentTable::TYPE_SALES_ORDERS,
				'title' => Loc::getMessage('M_CSDL_TAB_REALIZATION'),
			],
			[
				'id' => StoreDocumentTable::TYPE_MOVING,
				'title' => Loc::getMessage('M_CSDL_TAB_MENU_MOVING'),
			],
			[
				'id' => StoreDocumentTable::TYPE_DEDUCT,
				'title' => Loc::getMessage('M_CSDL_TAB_MENU_DEDUCT'),
			],
		];
	}
};

return $component->execute();

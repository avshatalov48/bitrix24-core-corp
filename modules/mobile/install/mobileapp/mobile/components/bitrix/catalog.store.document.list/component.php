<?php

use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Controller\Catalog\StoreDocumentList;
use Bitrix\Mobile\Integration\Catalog\PermissionsProvider;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$component = new class {
	private const NOT_CONDUCTED = 'N';
	private const CONDUCTED = 'Y';
	private const CANCELLED = 'C';

	private const NOT_CONDUCTED_STATUS_PARAMS = [
		'value' => self::NOT_CONDUCTED,
		'textColor' => '#79818b',
		'backgroundColor' => '#e0e2e4',
	];
	private const CONDUCTED_STATUS_PARAMS = [
		'value' => self::CONDUCTED,
		'textColor' => '#589309',
		'backgroundColor' => '#e0f5c2',
	];
	private const CANCELLED_STATUS_PARAMS = [
		'value' => self::CANCELLED,
		'textColor' => '#9d7e2b',
		'backgroundColor' => '#faf4a0',
	];

	private const TYPE_LOGO_PATTERN = '/bitrix/mobileapp/mobile/components/bitrix/catalog.store.document.details/images/type_#DOCUMENT_TYPE#.png';

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
			Loader::requireModule('catalog');
			Loader::requireModule('crm');
			Loader::requireModule('currency');
			Loader::requireModule('mobile');
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

		foreach (StoreDocumentTable::getTypeList(true) as $type => $name)
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
		return [
			[
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
			],
			[
				'id' => 'shipment',
				'title' => Loc::getMessage('M_CSDL_TAB_REALIZATION'),
				'documentTypes' => [],
				'selectable' => false,
				'link' => '/shop/documents/sales_order/',
			],
			[
				'id' => 'moving',
				'title' => Loc::getMessage('M_CSDL_TAB_MOVING'),
				'documentTypes' => [
					[
						'id' => StoreDocumentTable::TYPE_MOVING,
						'title' => Loc::getMessage('M_CSDL_TAB_MENU_MOVING'),
					],
				],
				'selectable' => false,
				'link' => '/shop/documents/moving/',
			],
			[
				'id' => 'deduct',
				'title' => Loc::getMessage('M_CSDL_TAB_DEDUCT'),
				'documentTypes' => [
					[
						'id' => StoreDocumentTable::TYPE_DEDUCT,
						'title' => Loc::getMessage('M_CSDL_TAB_MENU_DEDUCT'),
					],
				],
				'selectable' => false,
				'link' => '/shop/documents/deduct/',
			],
		];
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
			'actions' => StoreDocumentList::getActionsList(),
			'statuses' => $this->prepareStatuses(),
			'permissions' => PermissionsProvider::getInstance()->getPermissions(),
		];
	}

	private function prepareStatuses(): array
	{
		$conducted = self::CONDUCTED_STATUS_PARAMS + ['title' => Loc::getMessage('M_CSDL_STATUS_CONDUCTED')];
		$notConducted = self::NOT_CONDUCTED_STATUS_PARAMS + ['title' => Loc::getMessage('M_CSDL_STATUS_NOT_CONDUCTED')];
		$cancelled = self::CANCELLED_STATUS_PARAMS + ['title' => Loc::getMessage('M_CSDL_STATUS_CANCELLED')];

		return [
			self::CONDUCTED => $conducted,
			self::NOT_CONDUCTED => $notConducted,
			self::CANCELLED => $cancelled,
		];
	}
};

return $component->execute();

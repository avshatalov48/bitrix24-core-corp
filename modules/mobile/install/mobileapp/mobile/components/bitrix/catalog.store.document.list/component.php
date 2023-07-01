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
		'textColor' => '#828b95',
		'backgroundColor' => '#dfe0e3',
	];
	private const CONDUCTED_STATUS_PARAMS = [
		'value' => self::CONDUCTED,
		'textColor' => '#688800',
		'backgroundColor' => '#eaf6c3',
	];
	private const CANCELLED_STATUS_PARAMS = [
		'value' => self::CANCELLED,
		'textColor' => '#ae914b',
		'backgroundColor' => '#fef3b8',
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
				'id' => 'moving',
				'title' => Loc::getMessage('M_CSDL_TAB_MOVING'),
				'documentTypes' => [
					[
						'id' => StoreDocumentTable::TYPE_MOVING,
						'title' => Loc::getMessage('M_CSDL_TAB_MENU_MOVING'),
					],
				],
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
			],
			[
				'id' => 'shipment',
				'title' => Loc::getMessage('M_CSDL_TAB_REALIZATION'),
				'documentTypes' => [],
				'selectable' => false,
				'link' => '/shop/documents/sales_order/',
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
				'id' => StoreDocumentTable::TYPE_MOVING,
				'title' => Loc::getMessage('M_CSDL_TAB_MENU_MOVING'),
			],
			[
				'id' => StoreDocumentTable::TYPE_DEDUCT,
				'title' => Loc::getMessage('M_CSDL_TAB_MENU_DEDUCT'),
			],
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

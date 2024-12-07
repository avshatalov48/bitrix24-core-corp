<?php

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Config\State;
use Bitrix\Catalog\Url\InventoryManagementSourceBuilder;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Crm\Order\Internals\ShipmentRealizationTable;
use Bitrix\Sale\Internals\ShipmentItemTable;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\Crm;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\Internals\ShipmentTable;
use Bitrix\Sale\Tax\VatCalculator;
use Bitrix\UI;
use Bitrix\Catalog;
use Bitrix\Catalog\Access\Model\StoreDocument;
use Bitrix\Sale;
use Bitrix\UI\Buttons\LockedButton;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Main\Loader::includeModule('crm');
Main\Loader::includeModule('catalog');
Main\Loader::includeModule('currency');
Main\Loader::includeModule('sale');

class CrmStoreDocumentListComponent extends CBitrixComponent implements Controllerable
{
	private const GRID_ID = 'crm_store_documents';
	private const FILTER_ID = 'crm_store_documents_filter';

	private const SHIPMENT_MODE = 'shipment';
	private const TYPE_SHIPMENT = 'W';

	private const RUNTIME_REALIZATION_FIELD_NAME = 'SHIPMENT_REALIZATION';

	private $defaultGridSort = [
		'DATE_INSERT' => 'desc',
	];

	private $navParamName = 'page';

	/** @var Crm\Filter\StoreDocumentDataProvider $itemProvider */
	private $itemProvider;

	/** @var Main\Filter\Filter $filter */
	private $filter;

	/** @var array $stores */
	private $stores;

	/** @var array $documentStores */
	private $documentStores;

	/** @var array $documentTotals */
	private $documentTotals = [];

	public function onPrepareComponentParams($arParams)
	{
		if (!isset($arParams['PATH_TO']))
		{
			$arParams['PATH_TO'] = [];
		}

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$this->init();
		if (!$this->checkDocumentReadRights())
		{
			$this->arResult['ERROR_MESSAGES'][] = [
				'TITLE' => Loc::getMessage('CRM_DOCUMENT_LIST_ERR_ACCESS_DENIED'),
				'HELPER_CODE' => 15955386,
				'LESSON_ID' => 25010,
				'COURSE_ID' => 48,
			];
			$this->includeComponentTemplate();

			return;
		}
		$this->arResult['GRID'] = $this->prepareGrid();
		$this->arResult['FILTER_ID'] = $this->getFilterId();
		$this->prepareToolbar();

		$this->arResult['PATH_TO'] = $this->arParams['PATH_TO'];

		$this->initInventoryManagementSlider();

		$this->checkIfInventoryManagementIsDisabled();

		$this->arResult['INVENTORY_MANAGEMENT_SOURCE'] =
			InventoryManagementSourceBuilder::getInstance()->getInventoryManagementSource()
		;

		$this->includeComponentTemplate();
	}

	private function checkDocumentReadRights(): bool
	{
		return
			AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
			&& AccessController::getCurrent()->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
			&& AccessController::getCurrent()->checkByValue(
				ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
				StoreDocumentTable::TYPE_SALES_ORDERS
			)
		;
	}

	private function checkIfInventoryManagementIsDisabled(): void
	{
		$this->arResult['IS_INVENTORY_MANAGEMENT_DISABLED'] = !\Bitrix\Catalog\Config\Feature::isInventoryManagementEnabled();
		if ($this->arResult['IS_INVENTORY_MANAGEMENT_DISABLED'])
		{
			$this->arResult['INVENTORY_MANAGEMENT_FEATURE_SLIDER_CODE'] = \Bitrix\Catalog\Config\Feature::getInventoryManagementHelpLink()['FEATURE_CODE'] ?? null;
		}
		else
		{
			$this->arResult['INVENTORY_MANAGEMENT_FEATURE_SLIDER_CODE'] = null;
		}
	}

	private function checkDocumentModifyRights(): bool
	{
		return AccessController::getCurrent()->check(
			ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
			StoreDocument::createForSaleRealization(0)
		);
	}

	public function configureActions()
	{
	}

	private function getFilterId()
	{
		return self::FILTER_ID . '_' . self::SHIPMENT_MODE;
	}

	public static function getGridId(): string
	{
		return self::GRID_ID . '_' . self::SHIPMENT_MODE;
	}

	private function init()
	{
		$this->initMode();

		$this->itemProvider = new Crm\Filter\StoreDocumentDataProvider(self::SHIPMENT_MODE);
		$this->filter = new Main\Filter\Filter($this->getFilterId(), $this->itemProvider);
	}

	private function initMode()
	{
		$this->arResult['MODE'] = self::SHIPMENT_MODE;
	}

	private function prepareGrid()
	{
		$result = [];

		$gridId = self::getGridId();
		$result['GRID_ID'] = $gridId;
		$gridColumns = $this->itemProvider->getGridColumns();

		$gridOptions = new Bitrix\Main\Grid\Options($gridId);
		$navParams = $gridOptions->getNavParams();
		$pageSize = (int)$navParams['nPageSize'];
		$gridSort = $gridOptions->GetSorting(['sort' => $this->defaultGridSort]);

		$sortField = key($gridSort['sort']);
		foreach ($gridColumns as $key => $column)
		{
			if (isset($column['sort']) && $column['sort'] === $sortField)
			{
				$gridColumns[$key]['color'] = Bitrix\Main\Grid\Column\Color::BLUE;
				break;
			}
		}

		if ($sortField === 'DEDUCTED')
		{
			$gridSort['sort']['EMP_DEDUCTED_ID'] = $gridSort['sort']['DEDUCTED'];
		}

		$result['COLUMNS'] = $gridColumns;

		$pageNavigation = new Main\UI\PageNavigation($this->navParamName);
		$pageNavigation->allowAllRecords(false)->setPageSize($pageSize)->initFromUri();

		$this->arResult['GRID']['ROWS'] = $buffer = [];
		$listFilter = $this->getListFilter();
		$select = array_merge(
			['*'],
			[
				'ORDER_CURRENCY' => 'ORDER.CURRENCY',
			],
			$this->getUserSelectColumns($this->getUserReferenceColumns())
		);

		if (!empty($listFilter['PRODUCTS']))
		{
			unset($listFilter['PRODUCTS']);
		}

		$query = Sale\Internals\ShipmentTable::query()
			->setOrder($gridSort['sort'])
			->setOffset($pageNavigation->getOffset())
			->setLimit($pageNavigation->getLimit())
			->setFilter($listFilter)
			->setSelect($select)
			->setDistinct()
		;

		foreach ($this->getListRuntime() as $field)
		{
			$query->registerRuntimeField($field);
		}

		$list = $query->fetchAll();

		$totalCount = $this->getTotalCount();

		if($totalCount > 0)
		{
			$this->fillClientEntities($list);

			$this->getDocumentStores(array_column($list, 'ID'));
			$this->calculateDocumentTotals($list);

			foreach($list as $item)
			{
				$result['ROWS'][] = [
					'id' => $item['ID'],
					'data' => $item,
					'columns' => $this->getItemColumn($item),
					'actions' => $this->getItemActions($item),
				];
			}
		}
		else
		{
			$result['STUB'] = $this->getStub();
		}

		$pageNavigation->setRecordCount($totalCount);
		$result['NAV_PARAM_NAME'] = $this->navParamName;
		$result['CURRENT_PAGE'] = $pageNavigation->getCurrentPage();
		$result['NAV_OBJECT'] = $pageNavigation;
		$result['TOTAL_ROWS_COUNT'] = $totalCount;
		$result['AJAX_MODE'] = 'Y';
		$result['ALLOW_ROWS_SORT'] = false;
		$result['AJAX_OPTION_JUMP'] = "N";
		$result['AJAX_OPTION_STYLE'] = "N";
		$result['AJAX_OPTION_HISTORY'] = "N";
		$result['AJAX_ID'] = \CAjax::GetComponentID("bitrix:main.ui.grid", '', '');
		$result['SHOW_PAGINATION'] = $totalCount > 0;
		$result['SHOW_NAVIGATION_PANEL'] = true;
		$result['NAV_PARAM_NAME'] = 'page';
		$result['SHOW_PAGESIZE'] = true;
		$result['PAGE_SIZES'] = [['NAME' => 10, 'VALUE' => '10'], ['NAME' => 20, 'VALUE' => '20'], ['NAME' => 50, 'VALUE' => '50']];
		$result['SHOW_ROW_CHECKBOXES'] = true;
		$result['SHOW_CHECK_ALL_CHECKBOXES'] = true;
		$result['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] = (bool)(
			$this->arParams['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? \Bitrix\Main\ModuleManager::isModuleInstalled('ui')
		);
		$result['ENABLE_FIELDS_SEARCH'] = 'Y';


		$result['ACTION_PANEL'] = $this->getGroupActionPanel();
		$result['SHOW_ACTION_PANEL'] = !empty($result['ACTION_PANEL']);

		return $result;
	}

	private function getGroupActionPanel(): ?array
	{
		$resultItems = [];

		$storeDocument = StoreDocument::createForSaleRealization(0);
		$canDelete = AccessController::getCurrent()->check(ActionDictionary::ACTION_STORE_DOCUMENT_DELETE, $storeDocument);
		$canCancel = AccessController::getCurrent()->check(ActionDictionary::ACTION_STORE_DOCUMENT_CANCEL, $storeDocument);
		$canConduct = AccessController::getCurrent()->check(ActionDictionary::ACTION_STORE_DOCUMENT_CONDUCT, $storeDocument);

		$snippet = new Main\Grid\Panel\Snippet();

		if ($canDelete)
		{
			$removeButton = $snippet->getRemoveButton();
			$snippet->setButtonActions($removeButton, [
				[
					'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
					'CONFIRM' => true,
					'CONFIRM_APPLY_BUTTON' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_DELETE_TEXT'),
					'DATA' => [
						[
							'JS' => 'BX.Crm.StoreDocumentGridManager.Instance.deleteSelectedDocuments()'
						],
					],
				]
			]);

			$resultItems[] = $removeButton;
		}

		$dropdownActions = [
			[
				'NAME' => Loc::getMessage('CRM_DOCUMENT_LIST_SELECT_GROUP_ACTION'),
				'VALUE' => 'none',
			],
		];
		if ($canConduct)
		{
			$dropdownActions[] = [
				'NAME' => Loc::getMessage('CRM_DOCUMENT_LIST_CONDUCT_GROUP_ACTION'),
				'VALUE' => 'conduct',
			];
		}
		if ($canCancel)
		{
			$dropdownActions[] = [
				'NAME' => Loc::getMessage('CRM_DOCUMENT_LIST_CANCEL_GROUP_ACTION'),
				'VALUE' => 'cancel',
			];
		}
		if (count($dropdownActions) > 1)
		{
			$dropdownActionsButton = [
				'TYPE' => Main\Grid\Panel\Types::DROPDOWN,
				'ID' => 'action_button_'. self::getGridId(),
				'NAME' => 'action_button_'. self::getGridId(),
				'ITEMS' => $dropdownActions,
			];

			$applyButton = $snippet->getApplyButton([
				'ONCHANGE' => [
					[
						'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							[
								'JS' => 'BX.Crm.StoreDocumentGridManager.Instance.processApplyButtonClick()',
							]
						]
					]
				]
			]);

			$resultItems[] = $dropdownActionsButton;
			$resultItems[] = $applyButton;
		}

		if (empty($resultItems))
		{
			return null;
		}
		return [
			'GROUPS' => [
				[
					'ITEMS' => $resultItems,
				],
			]
		];
	}

	private function formClientFilterLogic(array $clientFilter): array
	{
		$formedFilterData = [
			'CONTACT' => [],
			'COMPANY' => [],
		];

		foreach ($clientFilter as $jsonClientItem)
		{
			$clientItem = Bitrix\Main\Web\Json::decode($jsonClientItem);

			if (isset($clientItem['CONTACT']))
			{
				$formedFilterData['CONTACT'][] = $clientItem['CONTACT'][0];
			}

			if (isset($clientItem['COMPANY']))
			{
				$formedFilterData['COMPANY'][] = $clientItem['COMPANY'][0];
			}
		}

		return $formedFilterData;
	}

	private function getUserReferenceColumns()
	{
		return ['RESPONSIBLE_BY'];
	}

	private function getUserSelectColumns($userReferenceNames)
	{
		$result = [];
		$fieldsToSelect = ['LOGIN', 'PERSONAL_PHOTO', 'NAME', 'SECOND_NAME', 'LAST_NAME'];

		foreach ($userReferenceNames as $userReferenceName)
		{
			foreach ($fieldsToSelect as $field)
			{
				$result[$userReferenceName . '_' . $field] = $userReferenceName . '.' . $field;
			}
		}

		return $result;
	}

	private function fillClientEntities(array &$documentDataList): void
	{
		$orderIds = array_column($documentDataList, 'ORDER_ID');

		$clients = $this->getClients($orderIds);

		foreach ($documentDataList as &$documentData)
		{
			$documentData['CLIENT'] = $clients[$documentData['ORDER_ID']];
		}
	}

	/**
	 * Return array of primary clients of orders
	 * @param array $orderIds
	 */
	private function getClients(array $orderIds): array
	{
		$clientsData = Crm\Binding\OrderContactCompanyTable::getList([
			'select' => [
				'ORDER_ID',
				'ENTITY_ID',
				'ENTITY_TYPE_ID',
			],
			'filter' => [
				'=ORDER_ID' => $orderIds,
				'=IS_PRIMARY' => 'Y',
			],
		])->fetchAll();

		$companyIds = [];
		$contactIds = [];
		foreach ($clientsData as $clientData)
		{
			switch ($clientData['ENTITY_TYPE_ID'])
			{
				case CCrmOwnerType::Contact:
					$contactIds[] = $clientData['ENTITY_ID'];
					break;

				case CCrmOwnerType::Company:
					$companyIds[] = $clientData['ENTITY_ID'];
					break;
			}
		}

		$companies = $this->getCompanies($companyIds);
		$contacts = $this->getContacts($contactIds);

		$clients = [];
		foreach ($clientsData as $clientData)
		{
			$orderId = $clientData['ORDER_ID'];
			$clients[$orderId] = $clients[$orderId] ?? [];

			switch ($clientData['ENTITY_TYPE_ID'])
			{
				case CCrmOwnerType::Contact:
					$clients[$orderId]['CONTACT'] = $contacts[$clientData['ENTITY_ID']];
					break;

				case CCrmOwnerType::Company:
					$clients[$orderId]['COMPANY'] = $companies[$clientData['ENTITY_ID']];
					break;
			}
		}

		return $clients;
	}

	private function getItemActions($item)
	{
		$urlToDocumentDetail = $this->getUrlToDocumentDetail($item['ID']);

		$storeDocument = StoreDocument::createForSaleRealization((int)$item['ID']);
		$canDelete = AccessController::getCurrent()->check(ActionDictionary::ACTION_STORE_DOCUMENT_DELETE, $storeDocument);
		$canCancel = AccessController::getCurrent()->check(ActionDictionary::ACTION_STORE_DOCUMENT_CANCEL, $storeDocument);
		$canConduct = AccessController::getCurrent()->check(ActionDictionary::ACTION_STORE_DOCUMENT_CONDUCT, $storeDocument);

		$actions = [
			[
				'TEXT' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_OPEN_TEXT'),
				'ONCLICK' => "BX.SidePanel.Instance.open('" . $urlToDocumentDetail . "', {cacheable: false, customLeftBoundary: 0, loader: 'crm-entity-details-loader'})",
				'DEFAULT' => true,
			],
		];

		if ($item['DEDUCTED'] === 'N')
		{
			if ($canConduct)
			{
				$actions[] = [
					'TEXT' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_CONDUCT_TEXT_2'),
					'ONCLICK' => "BX.Crm.StoreDocumentGridManager.Instance.conductDocument(" . $item['ID'] . ")",
				];
			}

			if ($canDelete)
			{
				$actions[] = [
					'TEXT' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_DELETE_TEXT'),
					'ONCLICK' => "BX.Crm.StoreDocumentGridManager.Instance.deleteDocument(" . $item['ID'] . ")",
				];
			}
		}
		else
		{
			if ($canCancel)
			{
				$actions[] = [
					'TEXT' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_CANCEL_TEXT_2'),
					'ONCLICK' => "BX.Crm.StoreDocumentGridManager.Instance.cancelDocument(" . $item['ID'] . ")",
				];
			}
		}

		return $actions;
	}

	private function getStub()
	{
		if ($this->isUserFilterApplied() && $this->getTotalCountWithoutUserFilter() > 0)
		{
			return [
				'title' => Loc::getMessage('CRM_DOCUMENT_LIST_STUB_NO_DATA_TITLE'),
				'description' => Loc::getMessage('CRM_DOCUMENT_LIST_STUB_NO_DATA_DESCRIPTION'),
			];
		}

		return '
			<div class="main-grid-empty-block-title">' . Loc::getMessage('CRM_DOCUMENT_LIST_STUB_TITLE_SHIPMENT') . '</div>
			<div class="main-grid-empty-block-description document-list-stub-description">' . Loc::getMessage('CRM_DOCUMENT_LIST_STUB_DESCRIPTION_SHIPMENT') . '</div>
			<a href="#" class="ui-link ui-link-dashed documents-grid-link" onclick="BX.Crm.StoreDocumentGridManager.Instance.openHowToShipProducts()">' . Loc::getMessage('CRM_DOCUMENT_LIST_STUB_LINK_SHIPMENT') . '</a>
		';
	}

	private function getContacts(array $contactIds): array
	{
		$fetchResult = Crm\ContactTable::getList([
			'select' => [
				'ID',
				'FULL_NAME',
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
			],
			'filter' => [
				'=ID' => $contactIds,
			],
		])->fetchAll();

		$contacts = [];
		foreach ($fetchResult as $item)
		{
			$contacts[$item['ID']] = $item;
			$contacts[$item['ID']]['HAS_ACCESS'] = CCrmContact::CheckReadPermission($item['ID']);
		}

		return $contacts;
	}

	private function getCompanies(array $companyIds): array
	{
		$fetchResult = Crm\CompanyTable::getList([
			'select' => [
				'ID',
				'TITLE',
			],
			'filter' => [
				'=ID' => $companyIds,
			],
		])->fetchAll();

		$companies = [];
		foreach ($fetchResult as $item)
		{
			$companies[$item['ID']] = $item;
			$companies[$item['ID']]['HAS_ACCESS'] = CCrmCompany::CheckReadPermission($item['ID']);
		}

		return $companies;
	}

	private function getItemColumn($item)
	{
		$column = $item;

		$column['TITLE'] = $this->prepareTitleView($column);

		if (isset($column['PRICE_DELIVERY']))
		{
			$column['PRICE_DELIVERY'] = CCurrencyLang::CurrencyFormat($column['TOTAL'], $column['CURRENCY']);
		}
		else
		{
			$column['PRICE_DELIVERY'] = CCurrencyLang::CurrencyFormat(0, (string)\Bitrix\Currency\CurrencyManager::getBaseCurrency());
		}

		if ($column['RESPONSIBLE_ID'])
		{
			$column['RESPONSIBLE_ID'] = $this->getUserDisplay($column, $column['RESPONSIBLE_ID'], 'RESPONSIBLE_BY');
		}

		if ($column['CLIENT'])
		{
			$column['CLIENT'] = $this->prepareClient($column['CLIENT']);
		}

		$column['DOC_TYPE'] = [
			'DOC_TYPE_LABEL' => [
				'text' => Loc::getMessage('CRM_DOCUMENT_LIST_DOC_TYPE_W'),
				'color' => 'ui-label-light',
			],
		];

		if ($column['DEDUCTED'])
		{
			if ($column['DEDUCTED'] === 'N')
			{
				if (!empty($column['EMP_DEDUCTED_ID']))
				{
					$labelColor = 'ui-label-lightorange';
					$labelText = Loc::getMessage('CRM_DOCUMENT_LIST_STATUS_CANCELLED');
					$filterLetter = 'C';
				}
				else
				{
					$labelColor = 'ui-label-light';
					$labelText = Loc::getMessage('CRM_DOCUMENT_LIST_STATUS_N');
					$filterLetter = 'N';
				}
			}
			else
			{
				$labelColor = 'ui-label-lightgreen';
				$labelText = Loc::getMessage('CRM_DOCUMENT_LIST_STATUS_Y');
				$filterLetter = 'Y';
			}

			$encodedFilter = Json::encode(
				[
					'DEDUCTED' => [$filterLetter],
				],
				// JSON_FORCE_OBJECT flag has been added so that the output complies with the filter's API
				JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_FORCE_OBJECT
			);

			$labelColor .= ' label-uppercase';
			$column['DEDUCTED'] = [
				'DEDUCTED_LABEL' => [
					'text' => $labelText,
					'color' => $labelColor,
					'events' => [
						'click' => 'BX.delegate(function() {BX.Crm.StoreDocumentGridManager.Instance.applyFilter(' . $encodedFilter . ')})',
					],
				],
			];
		}

		if ($column['ACCOUNT_NUMBER'])
		{
			$column['ACCOUNT_NUMBER'] = htmlspecialcharsbx($column['ACCOUNT_NUMBER']);
		}

		if ($column['DELIVERY_NAME'])
		{
			$column['DELIVERY_NAME'] = htmlspecialcharsbx($column['DELIVERY_NAME']);
		}

		if (isset($this->documentTotals[$column['ID']]))
		{
			$column['TOTAL'] = \CCurrencyLang::CurrencyFormat($this->documentTotals[$column['ID']], $column['ORDER_CURRENCY']);
		}
		else
		{
			$column['TOTAL'] = \CCurrencyLang::CurrencyFormat(0, $column['ORDER_CURRENCY']);
		}

		$stores = $this->documentStores[$column['ID']];
		if (!empty($stores))
		{
			$existingStores = $this->getStores();
			foreach ($stores as $store)
			{
				$encodedFilter = Json::encode([
					'STORES' => [$store],
					'STORES_label' => [$existingStores[$store]['TITLE']],
				]);
				$column['STORES']['STORE_LABEL_' . $store] = [
					'text' => $existingStores[$store]['TITLE'] ?: Loc::getMessage('CRM_DOCUMENT_LIST_EMPTY_STORE_TITLE'),
					'color' => 'ui-label-light',
					'events' => [
						'click' => 'BX.delegate(function() {BX.Crm.StoreDocumentGridManager.Instance.applyFilter(' . $encodedFilter . ')})',
					],
				];
			}
		}

		return $column;
	}

	private function prepareTitleView($column)
	{
		$urlToDocumentDetail = $this->getUrlToDocumentDetail($column['ID']);

		$title = Loc::getMessage('CRM_DOCUMENT_LIST_TITLE', [
			'#DOCUMENT_ID#' => $column['ACCOUNT_NUMBER'],
		]);

		$result = '<a target="_top"  href="' . $urlToDocumentDetail . '">' . htmlspecialcharsbx($title) . '</a>';

		$dateTimestamp = (new DateTime($column['DATE_INSERT']))->getTimestamp();
		$date = FormatDate(Context::getCurrent()->getCulture()->getLongDateFormat(), $dateTimestamp);
		$result .= '<div>' . Loc::getMessage('CRM_DOCUMENT_LIST_TITLE_DOCUMENT_DATE', ['#DATE#' => $date]) . '</div>';

		return $result;
	}

	private function prepareClient($clientData): string
	{
		if (
			isset($clientData['CONTACT'], $clientData['COMPANY'])
			&& $clientData['CONTACT']['HAS_ACCESS']
			&& $clientData['COMPANY']['HAS_ACCESS']
		)
		{
			$client = $this->getContactCompanyLink($clientData);
		}
		else if (isset($clientData['CONTACT']) && $clientData['CONTACT']['HAS_ACCESS'])
		{
			$client = $this->getContactLink($clientData['CONTACT']);
		}
		else if (isset($clientData['COMPANY']) && $clientData['COMPANY']['HAS_ACCESS'])
		{
			$client = $this->getCompanyLink($clientData['COMPANY']);
		}
		else if (isset($clientData['CONTACT']))
		{
			$client = Loc::getMessage('CRM_DOCUMENT_LIST_HIDDEN_CONTACT');
		}
		else if (isset($clientData['COMPANY']))
		{
			$client = Loc::getMessage('CRM_DOCUMENT_LIST_HIDDEN_COMPANY');
		}
		else
		{
			return '';
		}

		return "<div class='client-info-wrapper'>{$client}</div>";
	}

	private function getContactLink($contact): string
	{
		$contactId = (int)$contact['ID'];

		$name = \CUser::FormatName(
			\CSite::GetNameFormat(false),
			$contact
		);
		$name = htmlspecialcharsbx($name);

		$contactUrl = "/crm/contact/details/{$contactId}/";
		$userId = "CONTACT_{$contactId}";

		return "<a href='{$contactUrl}'
		 		   bx-tooltip-user-id='{$userId}'
		 		   bx-tooltip-loader='/bitrix/components/bitrix/crm.contact.show/card.ajax.php'
		 		   bx-tooltip-classname='crm_balloon_contact'>
		 		   {$name}
		 		</a>";
	}

	private function getContactCompanyLink($client): string
	{
		$contactLink = $this->getContactLink($client['CONTACT']);
		$companyTitle = htmlspecialcharsbx($client['COMPANY']['TITLE']);

		return "<div class='client-info-title-wrapper'>{$contactLink}</div>"
			."<div class='client-info-description-wrapper'>{$companyTitle}</div>";
	}

	private function getCompanyLink($company): string
	{
		$companyId = (int)$company['ID'];
		$title = htmlspecialcharsbx($company['TITLE']);

		$companyUrl = "/crm/company/details/{$companyId}/";
		$userId = "COMPANY_{$companyId}";

		return "<a href='{$companyUrl}'
		 		   bx-tooltip-user-id='{$userId}'
		 		   bx-tooltip-loader='/bitrix/components/bitrix/crm.company.show/card.ajax.php'
		 		   bx-tooltip-classname='crm_balloon_company'>{$title}</a>";
	}

	private function getUserDisplay($column, $userId, $userReferenceName)
	{
		$userEmptyAvatar = ' documents-grid-avatar-empty';
		$userAvatar = '';

		$userName = \CUser::FormatName(
			\CSite::GetNameFormat(false),
			[
				'LOGIN' => $column[$userReferenceName . '_LOGIN'],
				'NAME' => $column[$userReferenceName . '_NAME'],
				'LAST_NAME' => $column[$userReferenceName . '_LAST_NAME'],
				'SECOND_NAME' => $column[$userReferenceName . '_SECOND_NAME'],
			],
			true
		);

		$fileInfo = \CFile::ResizeImageGet(
			(int)$column[$userReferenceName . '_PERSONAL_PHOTO'],
			['width' => 60, 'height' => 60],
			BX_RESIZE_IMAGE_EXACT
		);
		if (is_array($fileInfo) && isset($fileInfo['src']))
		{
			$userEmptyAvatar = '';
			$photoUrl = $fileInfo['src'];
			$userAvatar = ' style="background-image: url(\'' . Uri::urnEncode($photoUrl) . '\')"';
		}

		$userNameElement = "<span class='documents-grid-avatar ui-icon ui-icon-common-user{$userEmptyAvatar}'><i{$userAvatar}></i></span>"
			."<span class='documents-grid-username-inner'>{$userName}</span>";

		return "<div class='documents-grid-username-wrapper'>"
			."<a class='documents-grid-username' href='/company/personal/user/{$userId}/'>{$userNameElement}</a>"
			."</div>";
	}

	private function getTotalCount()
	{
		$runtime = [
			new Main\Entity\ExpressionField('CNT', 'COUNT(*)'),
		];
		$runtime = array_merge($runtime, $this->getListRuntime());

		$listFilter = $this->getListFilter();
		if (!empty($listFilter['PRODUCTS']))
		{
			unset($listFilter['PRODUCTS']);
		}

		$count = Crm\Order\Shipment::getList([
			'select' => ['CNT'],
			'filter' => $listFilter,
			'runtime' => $runtime,
		])->fetch();

		if ($count)
		{
			return $count['CNT'];
		}

		return 0;
	}

	private function getTotalCountWithoutUserFilter()
	{
		$runtime = [
			new Main\Entity\ExpressionField('CNT', 'COUNT(*)'),
		];
		$runtime = array_merge($runtime, $this->getListRuntime());

		$count = Crm\Order\Shipment::getList([
			'select' => ['CNT'],
			'filter' => $this->getIsRealizeFilter(),
			'runtime' => $runtime,
		])->fetch();

		if ($count)
		{
			return $count['CNT'];
		}

		return 0;
	}

	private function prepareToolbar()
	{
		$filterOptions = [
			'GRID_ID' => self::getGridId(),
			'FILTER_ID' => $this->filter->getID(),
			'FILTER' => $this->filter->getFieldArrays(),
			'FILTER_PRESETS' => [],
			'ENABLE_LABEL' => true,
			'THEME' => Bitrix\Main\UI\Filter\Theme::LIGHT,
			'CONFIG' => [
				'AUTOFOCUS' => false,
				'popupWidth' => 800,
			],
			'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => \Bitrix\Main\ModuleManager::isModuleInstalled('ui'),
			'ENABLE_FIELDS_SEARCH' => 'Y',
		];
		UI\Toolbar\Facade\Toolbar::addFilter($filterOptions);

		if (!\Bitrix\Catalog\Config\Feature::isInventoryManagementEnabled())
		{
			$addDocumentButton = UI\Buttons\CreateButton::create([
				'text' => Loc::getMessage('CRM_DOCUMENT_LIST_ADD_DOCUMENT_BUTTON_2'),
				'color' => \Bitrix\UI\Buttons\Color::SUCCESS,
				'classList' => [
					'add-document-button',
					'ui-btn-icon-lock',
				],
			]);

			$inventoryManagementHelpLink = \Bitrix\Catalog\Config\Feature::getInventoryManagementHelpLink();
			if (isset($inventoryManagementHelpLink['LINK']))
			{
				$addDocumentButton->bindEvent('click', new \Bitrix\UI\Buttons\JsCode(
					"{$inventoryManagementHelpLink['LINK']}",
				));
			}
		}
		elseif (!$this->checkDocumentModifyRights())
		{
			$addDocumentButton = LockedButton::create([
				'text' => Loc::getMessage('CRM_DOCUMENT_LIST_ADD_DOCUMENT_BUTTON_2'),
				'color' => \Bitrix\UI\Buttons\Color::SUCCESS,
				'hint' => Loc::getMessage('CRM_DOCUMENT_LIST_ADD_DOCUMENT_BUTTON_DISABLE_HINT_MSGVER_1'),
				'classList' => [
					'add-document-button',
					'add-document-button-disabled',
				],
			]);
		}
		else
		{
			$addDocumentButton = UI\Buttons\CreateButton::create([
				'text' => Loc::getMessage('CRM_DOCUMENT_LIST_ADD_DOCUMENT_BUTTON_2'),
				'color' => UI\Buttons\Color::SUCCESS,
				'dataset' => [
					'toolbar-collapsed-icon' => \Bitrix\UI\Buttons\Icon::ADD,
				],
				'classList' => ['add-document-button'],
			]);

			$addDocumentButton->setLink($this->getUrlToNewDocumentDetail(self::TYPE_SHIPMENT));
		}

		UI\Toolbar\Facade\Toolbar::addButton($addDocumentButton, UI\Toolbar\ButtonLocation::AFTER_TITLE);
	}

	private function getUrlToNewDocumentDetail(string $documentType): string
	{
		$uriEntity = new Uri($this->getUrlToDocumentDetail(0, $documentType));
		$uriEntity->addParams(['focusedTab' => 'tab_products']);

		return $uriEntity->getUri();
	}

	private function getUserFilter()
	{
		$filterOptions = new Main\UI\Filter\Options($this->filter->getID());
		$filterFields = $this->filter->getFieldArrays();

		return $filterOptions->getFilterLogic($filterFields);
	}

	private function getListFilter(): array
	{
		static $filter = null;
		if ($filter === null)
		{
			$filter = array_merge($this->getUserFilter(), $this->getIsRealizeFilter(), $this->getAccessProductStoreFilter());

			$filter = $this->prepareListFilter($filter);

			if (isset($filter['CLIENT']))
			{
				$formedClientFilterData = $this->formClientFilterLogic($filter['CLIENT']);
				$clientFilter = [
					'LOGIC' => 'OR',
					[
						'=ENTITY_TYPE_ID' => CCrmOwnerType::Company,
						'=ENTITY_ID' => $formedClientFilterData['COMPANY']
					],
					[
						'=ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
						'=ENTITY_ID' => $formedClientFilterData['CONTACT']
					],
				];

				$fetchResult = Bitrix\Crm\Binding\OrderContactCompanyTable::getList([
					'select' => [
						new Bitrix\Main\Entity\ExpressionField('DISTINCT_ORDER_ID', 'DISTINCT %s', 'ORDER_ID'),
					],
					'filter' => $clientFilter
				])->fetchAll();

				$orderIds = array_column($fetchResult, 'DISTINCT_ORDER_ID');
				unset($filter['CLIENT']);
				$filter['=ORDER_ID'] = $orderIds;
			}

			if (isset($filter['PRODUCTS']))
			{
				$productFilter = [
					'=BASKET_PRODUCTS.PRODUCT_ID' => $filter['PRODUCTS'],
					'=SYSTEM' => 'N',
				];

				$fetchResult = ShipmentTable::getList([
					'select' => [
						new Bitrix\Main\Entity\ExpressionField('DISTINCT_ID', 'DISTINCT %s', 'ID'),
					],
					'filter' => $productFilter,
					'runtime' => [
						new \Bitrix\Main\Entity\ReferenceField(
							'DLV_BASKET',
							ShipmentItemTable::getEntity(),
							['=this.ID' => 'ref.ORDER_DELIVERY_ID'],
							['join_type' => 'left'],
						),
						new \Bitrix\Main\Entity\ReferenceField(
							'BASKET_PRODUCTS',
							Sale\Internals\BasketTable::getEntity(),
							['=this.DLV_BASKET.BASKET_ID' => 'ref.ID'],
							['join_type' => 'left'],
						),
					]
				]);
				$fetchResult = $fetchResult->fetchAll();
				$shipmentIds = array_column($fetchResult, 'DISTINCT_ID');
				$filter['=ID'] = $shipmentIds;
			}
		}

		return $filter;
	}

	private function getListRuntime(): array
	{
		return [
			new Main\Entity\ReferenceField(
				self::RUNTIME_REALIZATION_FIELD_NAME,
				ShipmentRealizationTable::class,
				[
					'=this.ID' => 'ref.SHIPMENT_ID',
				],
				'left_join'
			),
		];
	}

	private function getIsRealizeFilter(): array
	{
		return [
			self::RUNTIME_REALIZATION_FIELD_NAME . '.IS_REALIZATION' => 'Y',
			'=SYSTEM' => 'N',
		];
	}

	private function getAccessProductStoreFilter(): array
	{
		return AccessController::getCurrent()->getEntityFilter(
			ActionDictionary::ACTION_STORE_VIEW,
			ShipmentTable::class
		);
	}

	private function getUrlToDocumentDetail($documentId, $documentType = null): string
	{
		$pathToDocumentDetailTemplate = $this->arParams['PATH_TO']['SALES_ORDER'] ?? '';
		if ($pathToDocumentDetailTemplate === '')
		{
			return $pathToDocumentDetailTemplate;
		}

		$pathToDocumentDetail = str_replace('#DOCUMENT_ID#', $documentId, $pathToDocumentDetailTemplate);

		if ($documentType)
		{
			$pathToDocumentDetail .= '?DOCUMENT_TYPE=' . $documentType;
		}

		return InventoryManagementSourceBuilder::getInstance()->addInventoryManagementSourceParam($pathToDocumentDetail);
	}

	private function isUserFilterApplied()
	{
		return !empty($this->getUserFilter());
	}

	private function initInventoryManagementSlider()
	{
		$context = Main\Application::getInstance()->getContext();
		/** @var \Bitrix\Main\HttpRequest $request */
		$request = $context->getRequest();

		$this->arResult['OPEN_INVENTORY_MANAGEMENT_SLIDER'] =
			State::isUsedInventoryManagement() === false
			&& $request->get('STORE_MASTER_HIDE') !== 'Y'
		;
		$this->arResult['OPEN_INVENTORY_MANAGEMENT_SLIDER_ON_ACTION'] = !State::isUsedInventoryManagement();

		$sliderPath = \CComponentEngine::makeComponentPath('bitrix:catalog.store.enablewizard');
		$sliderPath = getLocalPath('components' . $sliderPath . '/slider.php');
		$this->arResult['MASTER_SLIDER_URL'] = $sliderPath;
	}

	private function getStores()
	{
		if (!is_null($this->stores))
		{
			return $this->stores;
		}

		$dbResult = Catalog\StoreTable::getList(['select' => ['ID', 'TITLE']]);
		while ($store = $dbResult->fetch())
		{
			$this->stores[$store['ID']] = [
				'TITLE' => $store['TITLE'],
				'ID' => $store['ID'],
			];
		}

		return $this->stores;
	}

	private function getDocumentStores($documentIds)
	{
		if (!is_null($this->documentStores))
		{
			return $this->documentStores;
		}

		$shipmentStoresResult = Sale\Internals\ShipmentItemStoreTable::getList(['select' => ['DOCUMENT_ID' => 'ORDER_DELIVERY_BASKET.ORDER_DELIVERY_ID', 'STORE_ID'], 'filter' => ['=ORDER_DELIVERY_BASKET.ORDER_DELIVERY_ID' => $documentIds]]);
		while ($store = $shipmentStoresResult->fetch())
		{
			if (!is_array($this->documentStores[$store['DOCUMENT_ID']]) || !in_array($store['STORE_ID'], $this->documentStores[$store['DOCUMENT_ID']]))
			{
				$this->documentStores[$store['DOCUMENT_ID']][] = $store['STORE_ID'];
			}
		}

		return $this->documentStores;
	}

	private function calculateDocumentTotals(array $documentList): void
	{
		$shipmentIds = array_column($documentList, 'ID');
		$shipmentBasketResult = Sale\ShipmentItem::getList([
			'select' => [
				'PRICE' => 'BASKET.PRICE',
				'VAT_RATE' => 'BASKET.VAT_RATE',
				'VAT_INCLUDED' => 'BASKET.VAT_INCLUDED',
				'ORDER_DELIVERY_ID',
				'QUANTITY',
			],
			'filter' => ['=ORDER_DELIVERY_ID' => $shipmentIds]
		]);
		while ($shipmentItem = $shipmentBasketResult->fetch())
		{
			if (!isset($this->documentTotals[$shipmentItem['ORDER_DELIVERY_ID']]))
			{
				$this->documentTotals[$shipmentItem['ORDER_DELIVERY_ID']] = 0;
			}

			$priceWithVat = (float)$shipmentItem['PRICE'];
			if ($shipmentItem['VAT_RATE'] !== null)
			{
				$vatCalculator = new VatCalculator((float)$shipmentItem['VAT_RATE']);

				$priceWithVat = ($shipmentItem['VAT_INCLUDED'] === 'Y')
					? $priceWithVat
					: $vatCalculator->accrue($priceWithVat);
			}

			$this->documentTotals[$shipmentItem['ORDER_DELIVERY_ID']] += $priceWithVat * $shipmentItem['QUANTITY'];
		}
	}

	private function prepareListFilter(array $filter): array
	{
		$preparedFilter = $filter;

		if (isset($preparedFilter['DEDUCTED']) && $preparedFilter['DEDUCTED'])
		{
			$statuses = $preparedFilter['DEDUCTED'];
			unset($preparedFilter['DEDUCTED']);

			$statusFilters = [
				'LOGIC' => 'OR',
			];
			foreach ($statuses as $status)
			{
				$statusFilter = [];
				if ($status === 'Y')
				{
					$statusFilter['DEDUCTED'] = 'Y';
				}
				elseif ($status === 'N')
				{
					$statusFilter['==EMP_DEDUCTED_ID'] = null;
					$statusFilter['DEDUCTED'] = 'N';
				}
				elseif ($status === 'C')
				{
					$statusFilter['DEDUCTED'] = 'N';
					$statusFilter['!==EMP_DEDUCTED_ID'] = null;
				}

				$statusFilters[] = $statusFilter;
			}

			$preparedFilter[] = $statusFilters;
		}

		if (isset($preparedFilter['STORES']) && $preparedFilter['STORES'])
		{
			$storeIds = $preparedFilter['STORES'];
			unset($preparedFilter['STORES']);
			$documentsWithStores = Sale\Internals\ShipmentItemStoreTable::getList([
				'select' => ['DOCUMENT_ID' => 'ORDER_DELIVERY_BASKET.ORDER_DELIVERY_ID', 'STORE_ID'],
				'filter' => ['=STORE_ID' => $storeIds]
			])->fetchAll();
			$documentsWithStores = array_unique(array_column($documentsWithStores, 'DOCUMENT_ID'));
			$preparedFilter['ID'] = $documentsWithStores;
		}

		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->filter->getID());
		$searchString = $filterOptions->getSearchString();
		if ($searchString)
		{
			$preparedFilter['ACCOUNT_NUMBER'] = '%' . $searchString . '%';
		}

		return $preparedFilter;
	}
}

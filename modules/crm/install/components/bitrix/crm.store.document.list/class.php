<?php

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm;
use Bitrix\Main\Web\Json;
use Bitrix\UI;
use Bitrix\Catalog;
use Bitrix\Sale;

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

	private $analyticsSource = '';

	/** @var Crm\Filter\StoreDocumentDataProvider $itemProvider */
	private $itemProvider;

	/** @var Main\Filter\Filter $filter */
	private $filter;

	/** @var string $mode */
	private $mode;

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
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('CRM_DOCUMENT_LIST_NO_VIEW_RIGHTS_ERROR');
			$this->includeComponentTemplate();
			return;
		}
		$this->arResult['GRID'] = $this->prepareGrid();
		$this->arResult['FILTER_ID'] = $this->getFilterId();
		$this->prepareToolbar();

		$this->arResult['PATH_TO'] = $this->arParams['PATH_TO'];

		$this->initInventoryManagementSlider();

		$this->includeComponentTemplate();
	}

	private function checkDocumentReadRights(): bool
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		return Crm\Order\Permissions\Order::checkReadPermission(0, $userPermissions);
	}

	public function configureActions()
	{
	}

	private function getFilterId()
	{
		return self::FILTER_ID . '_' . $this->mode;
	}

	private function getGridId()
	{
		return self::GRID_ID . '_' . $this->mode;
	}

	private function init()
	{
		$this->initMode();

		$this->itemProvider = new Crm\Filter\StoreDocumentDataProvider($this->mode);
		$this->filter = new Main\Filter\Filter($this->getFilterId(), $this->itemProvider);

		$this->analyticsSource = $this->request->get('inventoryManagementSource') ?? '';
	}

	private function initMode()
	{
		$this->mode = self::SHIPMENT_MODE;
		$this->arResult['MODE'] = $this->mode;
	}

	private function prepareGrid()
	{
		$result = [];

		$gridId = $this->getGridId();
		$result['GRID_ID'] = $gridId;
		$gridColumns = $this->itemProvider->getGridColumns();

		$gridOptions = new Bitrix\Main\Grid\Options($gridId);
		$navParams = $gridOptions->getNavParams();
		$pageSize = (int)$navParams['nPageSize'];
		$gridSort = $gridOptions->GetSorting(['sort' => $this->defaultGridSort]);

		$sortField = key($gridSort['sort']);
		foreach ($gridColumns as $key => $column)
		{
			if ($column['sort'] === $sortField)
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
		$list = Crm\Order\Shipment::getList([
			'order' => $gridSort['sort'],
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
			'filter' => $listFilter,
			'select' => $select,
			'runtime' => $this->getListRuntime(),
		])->fetchAll();
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
		$result['SHOW_ACTION_PANEL'] = true;
		$snippet = new Main\Grid\Panel\Snippet();
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

		$dropdownActions = [
			[
				'NAME' => Loc::getMessage('CRM_DOCUMENT_LIST_SELECT_GROUP_ACTION'),
				'VALUE' => 'none',
			],
			[
				'NAME' => Loc::getMessage('CRM_DOCUMENT_LIST_CONDUCT_GROUP_ACTION'),
				'VALUE' => 'conduct',
			],
			[
				'NAME' => Loc::getMessage('CRM_DOCUMENT_LIST_CANCEL_GROUP_ACTION'),
				'VALUE' => 'cancel',
			]
		];

		$dropdownActionsButton = [
			'TYPE' => Main\Grid\Panel\Types::DROPDOWN,
			'ID' => 'action_button_'. $this->getGridId(),
			'NAME' => 'action_button_'. $this->getGridId(),
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

		$result['ACTION_PANEL'] = [
			'GROUPS' => [
				[
					'ITEMS' => [
						$removeButton,
						$dropdownActionsButton,
						$applyButton,
					],
				],
			]
		];

		return $result;
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
			if (!$clients[$orderId])
			{
				$clients[$orderId] = [];
			}

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

		$actions = [
			[
				'TITLE' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_OPEN_TITLE'),
				'TEXT' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_OPEN_TEXT'),
				'ONCLICK' => "BX.SidePanel.Instance.open('" . $urlToDocumentDetail . "', {cacheable: false, customLeftBoundary: 0, loader: 'crm-entity-details-loader'})",
				'DEFAULT' => true,
			],
		];
		if ($item['DEDUCTED'] === 'N')
		{
			$actions[] = [
				'TITLE' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_CONDUCT_TITLE'),
				'TEXT' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_CONDUCT_TEXT'),
				'ONCLICK' => "BX.Crm.StoreDocumentGridManager.Instance.conductDocument(" . $item['ID'] . ")",
			];
			$actions[] = [
				'TITLE' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_DELETE_TITLE'),
				'TEXT' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_DELETE_TEXT'),
				'ONCLICK' => "BX.Crm.StoreDocumentGridManager.Instance.deleteDocument(" . $item['ID'] . ")",
			];
		}
		else
		{
			$actions[] = [
				'TITLE' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_CANCEL_TITLE'),
				'TEXT' => Loc::getMessage('CRM_DOCUMENT_LIST_ACTION_CANCEL_TEXT'),
				'ONCLICK' => "BX.Crm.StoreDocumentGridManager.Instance.cancelDocument(" . $item['ID'] . ")",
			];
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
			$column['PRICE_DELIVERY'] = CCurrencyLang::CurrencyFormat(0, \Bitrix\Currency\CurrencyManager::getBaseCurrency());
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
		if ($clientData['CONTACT'] && $clientData['COMPANY'])
		{
			$client = $this->getContactCompanyLink($clientData);
		}
		else if ($clientData['CONTACT'])
		{
			$client = $this->getContactLink($clientData['CONTACT']);
		}
		else if ($clientData['COMPANY'])
		{
			$client = $this->getCompanyLink($clientData['COMPANY']);
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
			$userAvatar = " style='background-image: url(\"{$photoUrl}\")'";
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

		$count = Crm\Order\Shipment::getList([
			'select' => ['CNT'],
			'filter' => $this->getListFilter(),
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
			'GRID_ID' => $this->getGridId(),
			'FILTER_ID' => $this->filter->getID(),
			'FILTER' => $this->filter->getFieldArrays(),
			'FILTER_PRESETS' => [],
			'ENABLE_LABEL' => true,
			'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
		];
		UI\Toolbar\Facade\Toolbar::addFilter($filterOptions);

		$addDocumentButton = UI\Buttons\CreateButton::create([
			'text' => Loc::getMessage('CRM_DOCUMENT_LIST_ADD_DOCUMENT_BUTTON'),
			'color' => UI\Buttons\Color::SUCCESS,
			'dataset' => [
				'toolbar-collapsed-icon' => \Bitrix\UI\Buttons\Icon::ADD,
			],
		]);
		$addDocumentUrl = $this->getUrlToDocumentDetail(0);
		$analyticsSourcePart = $this->analyticsSource ? '&inventoryManagementSource=' . $this->analyticsSource : '';
		if ($this->mode === self::SHIPMENT_MODE)
		{
			$addDocumentButton->setLink($addDocumentUrl . '?DOCUMENT_TYPE=' . self::TYPE_SHIPMENT  . $analyticsSourcePart);
		}

		UI\Toolbar\Facade\Toolbar::addButton($addDocumentButton, UI\Toolbar\ButtonLocation::AFTER_TITLE);
	}

	private function getUserFilter()
	{
		$filterOptions = new Main\UI\Filter\Options($this->filter->getID());
		$filterFields = $this->filter->getFieldArrays();

		return $filterOptions->getFilterLogic($filterFields);
	}

	private function getListFilter(): array
	{
		$filter = array_merge($this->getUserFilter(), $this->getIsRealizeFilter());

		$filter = $this->prepareListFilter($filter);

		return $filter;
	}

	private function getListRuntime(): array
	{
		return [
			new Main\Entity\ReferenceField(
				self::RUNTIME_REALIZATION_FIELD_NAME,
				Crm\Order\Internals\ShipmentRealizationTable::class,
				[
					'=this.ID' => 'ref.SHIPMENT_ID',
				],
				'left_join'
			)
		];
	}

	private function getIsRealizeFilter(): array
	{
		return [
			self::RUNTIME_REALIZATION_FIELD_NAME . '.IS_REALIZATION' => 'Y',
			'=SYSTEM' => 'N',
		];
	}

	private function getUrlToDocumentDetail($documentId)
	{
		$pathToDocumentDetail = $this->arParams['PATH_TO']['SALES_ORDER'] ?? '';
		if ($pathToDocumentDetail === '')
		{
			return $pathToDocumentDetail;
		}

		return str_replace('#DOCUMENT_ID#', $documentId, $pathToDocumentDetail);
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
			Catalog\Component\UseStore::needShowSlider()
			&& $request->get(Catalog\Component\UseStore::URL_PARAM_STORE_MASTER_HIDE) !== 'Y'
		;
		$this->arResult['OPEN_INVENTORY_MANAGEMENT_SLIDER_ON_ACTION'] = !Catalog\Component\UseStore::isUsed();

		$sliderPath = \CComponentEngine::makeComponentPath('bitrix:catalog.warehouse.master.clear');
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
			'select' => ['PRICE' => 'BASKET.PRICE', 'ORDER_DELIVERY_ID', 'QUANTITY'],
			'filter' => ['=ORDER_DELIVERY_ID' => $shipmentIds]
		]);
		while ($shipmentItem = $shipmentBasketResult->fetch())
		{
			if (!isset($this->documentTotals[$shipmentItem['ORDER_DELIVERY_ID']]))
			{
				$this->documentTotals[$shipmentItem['ORDER_DELIVERY_ID']] = 0;
			}
			$this->documentTotals[$shipmentItem['ORDER_DELIVERY_ID']] += (float)$shipmentItem['PRICE'] * $shipmentItem['QUANTITY'];
		}
	}

	private function prepareListFilter($filter)
	{
		$preparedFilter = $filter;

		if ($preparedFilter['DEDUCTED'])
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

		if ($preparedFilter['STORES'])
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

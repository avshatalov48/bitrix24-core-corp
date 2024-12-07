<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;
use Bitrix\UI;

Main\Loader::includeModule('crm');

class CrmTerminalPaymentList extends \CBitrixComponent implements Main\Engine\Contract\Controllerable, Main\Errorable
{
	private const GRID_ID = 'crm_terminal_payment_list';
	private const FILTER_ID = 'crm_terminal_payment_list_filter';

	private Crm\Filter\TerminalPaymentProvider $itemProvider;
	private Main\Filter\Filter $filter;

	/** @var Main\ErrorCollection */
	protected $errorCollection;

	public function configureActions()
	{
		return [];
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->errorCollection = new Main\ErrorCollection();

		return parent::onPrepareComponentParams($arParams);
	}

	private function initResult(): void
	{
		$this->arResult = [
			'GRID_ID' => '',
			'COLUMNS' => [],
			'ROWS' => [],
			'NAV_OBJECT' => null,
			'ERROR_MESSAGES' => [],
			'SETTINGS_PATH' => $this->getTerminalSettingsComponentPath(),
			'TOTAL_ROWS_COUNT' => 0,
			'IS_ROWS_EXIST' => $this->isRowsExists(),
		];
	}

	private function initFilter(): void
	{
		$this->itemProvider = new Crm\Filter\TerminalPaymentProvider();
		$this->filter = new Main\Filter\Filter(self::FILTER_ID, $this->itemProvider);
	}

	private function prepareGrid(): void
	{
		$this->prepareNavigation();
		$this->arResult['GRID_ID'] = self::GRID_ID;
		$this->arResult['COLUMNS'] = $this->itemProvider->getGridColumns();
		$this->arResult['ROWS'] = $this->getRows();
		$this->arResult['ACTION_PANEL'] = $this->getGroupActionPanel();
		$this->arResult['SHOW_ACTION_PANEL'] = !empty($this->arResult['ACTION_PANEL']);
		$this->arResult['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] = \Bitrix\Main\ModuleManager::isModuleInstalled('ui');
		$this->arResult['ENABLE_FIELDS_SEARCH'] = 'Y';
	}

	private function getGroupActionPanel(): ?array
	{
		$resultItems = [];

		$snippet = new Main\Grid\Panel\Snippet();

		$removeButton = $snippet->getRemoveButton();
		$snippet->setButtonActions($removeButton, [
			[
				'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
				'CONFIRM' => true,
				'DATA' => [
					[
						'JS' => 'BX.Crm.Component.TerminalPaymentList.Instance.deletePayments()'
					],
				],
			]
		]);

		$resultItems[] = $removeButton;

		return [
			'GROUPS' => [
				[
					'ITEMS' => $resultItems,
				],
			]
		];
	}

	private function prepareNavigation(): void
	{
		$gridOptions = new Main\Grid\Options(self::GRID_ID);
		$navigationParams = $gridOptions->GetNavParams();

		$navigation = new Main\UI\PageNavigation(self::GRID_ID);
		$navigation->allowAllRecords(true)
			->setPageSize($navigationParams['nPageSize'])
			->initFromUri()
		;

		$this->arResult['NAV_OBJECT'] = $navigation;
	}

	private function getSort(): array
	{
		$gridOptions = new Main\Grid\Options(self::GRID_ID);
		$sort = $gridOptions->GetSorting([
			'sort' => [
				'ID' => 'DESC',
			],
			'vars' => [
				'by' => 'by',
				'order' => 'order',
			],
		]);

		return $sort['sort'];
	}

	private function getRows(): array
	{
		$listFilter = $this->getListFilter();

		$select = array_merge(
			[
				'ID',
				'CURRENCY',
				'ORDER_ID',
				'PAY_SYSTEM_ID',
			],
			array_column($this->itemProvider->getGridColumns(), 'id')
		);
		$select = array_intersect($select, Crm\Order\Payment::getAllFields());
		$select = array_merge($select, $this->getUserSelectColumns($this->getUserReferenceColumns()));
		$select['PAY_SYSTEM_ACTION'] = 'PAY_SYSTEM.ACTION_FILE';

		$paymentIterator = Sale\Payment::getList([
			'select' => $select,
			'filter' => $listFilter,
			'offset' => $this->arResult['NAV_OBJECT']->getOffset(),
			'limit' => $this->arResult['NAV_OBJECT']->getLimit(),
			'order' => $this->getSort(),
			'count_total' => true,
			'runtime' => [
				Crm\Service\Container::getInstance()->getTerminalPaymentService()->getRuntimeReferenceField()
			],
		]);

		$this->arResult['NAV_OBJECT']->setRecordCount($paymentIterator->getCount());

		$payments = $paymentIterator->fetchAll();

		if ($paymentIterator->getCount() > 0)
		{
			$this->fillClientEntities($payments);
		}

		$result = [];
		foreach ($payments as $payment)
		{
			$result[] = [
				'id' => $payment['ID'],
				'data' => [
					'ACCOUNT_NUMBER' => $payment['ACCOUNT_NUMBER'],
					'SUM' => $payment['SUM'],
					'DATE_PAID' => $payment['DATE_PAID'],
					'PAY_SYSTEM_NAME' => $payment['PAY_SYSTEM_NAME'],
					'PAID' => $payment['PAID'],
					'CLIENT' => $payment['CLIENT'],
					'RESPONSIBLE_ID' => $payment['RESPONSIBLE_ID'],
					'MARKED' => $payment['MARKED'] === 'Y' ? 'Y' : 'N',
				],
				'actions' => $this->getItemActions($payment),
				'columns' => $this->getItemColumn($payment),
			];
		}

		$this->arResult['TOTAL_ROWS_COUNT'] = $paymentIterator->getCount();

		return $result;
	}

	private function isRowsExists(): bool
	{
		return (bool)Sale\Payment::getList([
			'select' => ['ID'],
			'runtime' => [
				Crm\Service\Container::getInstance()->getTerminalPaymentService()->getRuntimeReferenceField()
			],
			'limit' => 1,
		])->fetch();
	}

	private function getUserReferenceColumns(): array
	{
		return ['RESPONSIBLE_BY'];
	}

	private function getUserSelectColumns($userReferenceNames): array
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

	private function prepareToolbar(): void
	{
		if ($this->arResult['IS_ROWS_EXIST'])
		{
			$filterOptions = [
				'GRID_ID' => self::GRID_ID,
				'FILTER_ID' => $this->filter->getID(),
				'FILTER' => $this->filter->getFieldArrays(),
				'FILTER_PRESETS' => [],
				'ENABLE_LABEL' => true,
				'THEME' => Bitrix\Main\UI\Filter\Theme::LIGHT,
				'CONFIG' => [
					'popupWidth' => 800,
				],
				'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => \Bitrix\Main\ModuleManager::isModuleInstalled('ui'),
				'ENABLE_FIELDS_SEARCH' => 'Y',
			];
			UI\Toolbar\Facade\Toolbar::addFilter($filterOptions);

			if (Main\Loader::includeModule('mobile'))
			{
				UI\Toolbar\Facade\Toolbar::addButton($this->getQrButtonOptions());
			}
		}

		UI\Toolbar\Facade\Toolbar::addButton($this->getSettingsButtonOptions());
	}

	private function getSettingsButtonOptions(): array
	{
		return [
			'color' => UI\Buttons\Color::LIGHT_BORDER,
			'icon' => UI\Buttons\Icon::SETTING,
			'dropdown' => false,
			'menu' => [
				'items' => [
					[
						'text' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TERMINAL_SETTINGS_MSGVER_1'),
						'onclick' => new UI\Buttons\JsHandler(
							'BX.Crm.Component.TerminalPaymentList.Instance.openTerminalSettingsSlider',
							'BX.Crm.Component.TerminalPaymentList.Instance',
						)
					],
					[
						'text' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_HELPDESK_HOW_IT_WORKS'),
						'onclick' => new UI\Buttons\JsHandler(
							'BX.Crm.Component.TerminalPaymentList.Instance.openHelpdesk',
							'BX.Crm.Component.TerminalPaymentList.Instance',
						)
					],
				],
				'closeByEsc' => true,
				'angle' => [
					'offset' => 15,
					'position' => 'top',
				],
				'offsetLeft' => 20,
			],
		];
	}

	private function getQrButtonOptions(): array
	{
		return [
			'color' => UI\Buttons\Color::LIGHT_BORDER,
			'text' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TERMINAL_RUN'),
			'click' => new UI\Buttons\JsHandler(
				'BX.Crm.Component.TerminalPaymentList.Instance.openQrAuthPopup',
				'BX.Crm.Component.TerminalPaymentList.Instance',
			),
		];
	}

	private function getListFilter(): array
	{
		$filterOptions = new Main\UI\Filter\Options($this->filter->getID());
		$filterFields = $this->filter->getFieldArrays();

		$filter = $filterOptions->getFilterLogic($filterFields);

		$filter = $this->prepareListFilter($filter);

		return $filter;
	}

	private function prepareListFilter($filter)
	{
		$preparedFilter = $filter;

		$filterOptions = new Main\UI\Filter\Options($this->filter->getID());
		$searchString = $filterOptions->getSearchString();
		if ($searchString)
		{
			$preparedFilter[] = [
				'LOGIC' => 'OR',
				['ACCOUNT_NUMBER' => '%' . $searchString . '%'],
				['*ORDER.SEARCH_CONTENT' => $searchString]
			];
		}

		if (isset($preparedFilter['CLIENT']))
		{
			$formedClientFilterData = $this->formClientFilterLogic($preparedFilter['CLIENT']);
			$clientFilter = [
				'LOGIC' => 'OR',
				[
					'=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
					'=ENTITY_ID' => $formedClientFilterData['COMPANY']
				],
				[
					'=ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
					'=ENTITY_ID' => $formedClientFilterData['CONTACT']
				],
			];

			$fetchResult = Crm\Binding\OrderContactCompanyTable::getList([
				'select' => [
					new Main\Entity\ExpressionField('DISTINCT_ORDER_ID', 'DISTINCT %s', 'ORDER_ID'),
				],
				'filter' => $clientFilter
			])->fetchAll();

			$orderIds = array_column($fetchResult, 'DISTINCT_ORDER_ID');
			unset($preparedFilter['CLIENT']);
			$preparedFilter['=ORDER_ID'] = $orderIds;
		}

		if (isset($preparedFilter['PAY_SYSTEM_NAME']))
		{
			$preparedFilter['=PAY_SYSTEM_ID'] = $preparedFilter['PAY_SYSTEM_NAME'];
			unset($preparedFilter['PAY_SYSTEM_NAME']);
		}

		return $preparedFilter;
	}

	private function formClientFilterLogic(array $clientFilter): array
	{
		$formedFilterData = [
			'CONTACT' => [],
			'COMPANY' => [],
		];

		foreach ($clientFilter as $jsonClientItem)
		{
			$clientItem = Main\Web\Json::decode($jsonClientItem);

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

	private function fillClientEntities(array &$documentDataList): void
	{
		$orderIds = array_column($documentDataList, 'ORDER_ID');

		$clients = $this->getClients($orderIds);

		foreach ($documentDataList as &$documentData)
		{
			$documentData['CLIENT'] = $clients[$documentData['ORDER_ID']];
		}
	}

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
				case \CCrmOwnerType::Contact:
					$contactIds[] = $clientData['ENTITY_ID'];
					break;

				case \CCrmOwnerType::Company:
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
				case \CCrmOwnerType::Contact:
					$clients[$orderId]['CONTACT'] = $contacts[$clientData['ENTITY_ID']];
					break;

				case \CCrmOwnerType::Company:
					$clients[$orderId]['COMPANY'] = $companies[$clientData['ENTITY_ID']];
					break;
			}
		}

		return $clients;
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
			$contacts[$item['ID']]['HAS_ACCESS'] = \CCrmContact::CheckReadPermission($item['ID']);
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
			$companies[$item['ID']]['HAS_ACCESS'] = \CCrmCompany::CheckReadPermission($item['ID']);
		}

		return $companies;
	}

	private function getItemActions(array $item): array
	{
		$actions[] = [
			'TITLE' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_ACTION_OPEN_TITLE'),
			'TEXT' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_ACTION_OPEN_TEXT'),
			'ONCLICK' => $this->getOpenPaymentJsCallback($item),
			'DEFAULT' => true,
		];

		$actions[] = [
			'TITLE' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_ACTION_STATUS_TITLE'),
			'TEXT' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_ACTION_STATUS_TEXT'),
			'MENU' => [
				[
					'TITLE' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_STATUS_Y'),
					'TEXT' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_STATUS_Y'),
					'ICONCLASS' => $item['PAID'] === 'Y' ? 'menu-popup-item-accept-sm' : '',
					'ONCLICK' => $this->getSetStatusJsCallback($item, 'Y'),
				],
				[
					'TITLE' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_STATUS_N'),
					'TEXT' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_STATUS_N'),
					'ICONCLASS' => $item['PAID'] === 'N' ? 'menu-popup-item-accept-sm' : '',
					'ONCLICK' => $this->getSetStatusJsCallback($item, 'N'),
				],
			],
		];

		if ($item['PAID'] === 'N')
		{
			$actions[] = [
				'TITLE' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_ACTION_DELETE_TITLE'),
				'TEXT' => Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_ACTION_DELETE_TEXT'),
				'ONCLICK' => "BX.Crm.Component.TerminalPaymentList.Instance.deletePayment(" . (int)$item['ID'] . ")",
			];
		}

		return $actions;
	}

	private function getItemColumn(array $item): array
	{
		$column = $item;

		$salescenterOptions = \CUtil::PhpToJSObject([
			'paymentId' => $item['ID'],
			'orderId' => $item['ORDER_ID'],
		]);

		$column['ACCOUNT_NUMBER'] = '<a href="#" onclick="' . $this->getOpenPaymentJsCallback($item) . '">' . htmlspecialcharsbx($column['ACCOUNT_NUMBER']) . '</a>';

		if ($column['PAID'] === 'N')
		{
			$labelColor = 'ui-label-lightorange';
			$labelText = Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_STATUS_N');

			if ($column['MARKED'] === 'Y')
			{
				$labelColor = 'ui-label-danger';
				$labelText = Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_STATUS_N_MARKER');
			}
		}
		else
		{
			$labelColor = 'ui-label-lightgreen';
			$labelText = Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_STATUS_Y');
		}

		$labelColor .= ' label-uppercase';
		$column['PAID'] = [
			'PAID_LABEL' => [
				'text' => $labelText,
				'color' => $labelColor,
			],
		];

		if ($column['MARKED'] === 'Y')
		{
			$labelColor = 'ui-label-danger';
			$labelText = Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_MARKED_Y');
		}
		else
		{
			$labelColor = 'ui-label-lightgreen';
			$labelText = Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_MARKED_N');
		}

		$labelColor .= ' label-uppercase';
		$column['MARKED'] = [
			'MARKED_LABEL' => [
				'text' => $labelText,
				'color' => $labelColor,
			],
		];

		if ($column['DATE_PAID'] instanceof Main\Type\DateTime)
		{
			$column['DATE_PAID'] = $column['DATE_PAID']->toString();
		}

		if (isset($column['CLIENT']))
		{
			$column['CLIENT'] = $this->prepareClient($column['CLIENT']);
		}

		if (isset($column['RESPONSIBLE_ID']))
		{
			$column['RESPONSIBLE_ID'] = $this->getUserDisplay($column, $column['RESPONSIBLE_ID'], 'RESPONSIBLE_BY');
		}

		$column['SUM'] = \CCurrencyLang::CurrencyFormat($column['SUM'], $column['CURRENCY']);

		if (
			(int)$item['PAY_SYSTEM_ID'] === (int)Sale\PaySystem\Manager::getInnerPaySystemId()
			|| $item['PAY_SYSTEM_ACTION'] === 'cash'
		)
		{
			$column['PAY_SYSTEM_NAME'] = '';
		}
		else
		{
			$column['PAY_SYSTEM_NAME'] = htmlspecialcharsbx($column['PAY_SYSTEM_NAME']);
		}

		return $column;
	}

	private function getOpenPaymentJsCallback(array $payment): string
	{
		$salescenterOptions = \CUtil::PhpToJSObject([
			'paymentId' => $payment['ID'],
			'orderId' => $payment['ORDER_ID'],
		]);

		return 'BX.Crm.Component.TerminalPaymentList.Instance.openPaymentInSalescenter(' . $salescenterOptions . ')';
	}

	private function getSetStatusJsCallback(array $payment, string $status): string
	{
		return 'BX.Crm.Component.TerminalPaymentList.Instance.setPaidStatus(' . implode(', ', [(int)$payment['ID'], \CUtil::PhpToJSObject($status)]) . ')';
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
			$client = Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_HIDDEN_CONTACT');
		}
		else if (isset($clientData['COMPANY']))
		{
			$client = Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_HIDDEN_COMPANY');
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

		$name = \CUser::FormatName(\CSite::GetNameFormat(false), $contact);
		$name = htmlspecialcharsbx($name);

		$contactUrl = Main\Config\Option::get('crm', 'path_to_contact_details', '/crm/contact/details/#contact_id#/');
		$contactUrl = CComponentEngine::MakePathFromTemplate($contactUrl, ['contact_id' => $contactId]);
		$userId = "CONTACT_{$contactId}";

		return "<a href='{$contactUrl}'
				 bx-tooltip-user-id='{$userId}'
				 bx-tooltip-loader='/bitrix/components/bitrix/crm.contact.show/card.ajax.php'
				 bx-tooltip-classname='crm_balloon_contact'>
				 {$name}
				</a>"
		;
	}

	private function getContactCompanyLink($client): string
	{
		$contactLink = $this->getContactLink($client['CONTACT']);
		$companyTitle = htmlspecialcharsbx($client['COMPANY']['TITLE']);

		return "<div class='client-info-title-wrapper'>{$contactLink}</div>"
			."<div class='client-info-description-wrapper'>{$companyTitle}</div>"
		;
	}

	private function getCompanyLink($company): string
	{
		$companyId = (int)$company['ID'];
		$title = htmlspecialcharsbx($company['TITLE']);

		$companyUrl = Main\Config\Option::get('crm', 'path_to_company_details', '/crm/company/details/#company_id#/');
		$companyUrl = CComponentEngine::MakePathFromTemplate($companyUrl, ['company_id' => $companyId]);
		$userId = "COMPANY_{$companyId}";

		return "<a href='{$companyUrl}'
				 bx-tooltip-user-id='{$userId}'
				 bx-tooltip-loader='/bitrix/components/bitrix/crm.company.show/card.ajax.php'
				 bx-tooltip-classname='crm_balloon_company'>{$title}</a>"
			;
	}

	private function getDetailComponentPath(int $id): string
	{
		$pathToPaymentDetailTemplate = $this->arParams['PATH_TO']['DETAIL'] ?? '';
		if ($pathToPaymentDetailTemplate === '')
		{
			return $pathToPaymentDetailTemplate;
		}

		return str_replace('#PAYMENT_ID#', $id, $pathToPaymentDetailTemplate);
	}

	private function getUserDisplay($column, $userId, $userReferenceName): string
	{
		$userEmptyAvatar = ' terminal-payment-grid-avatar-empty';
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
			$userAvatar = ' style="background-image: url(\'' . Main\Web\Uri::urnEncode($photoUrl) . '\')"';
		}

		$userNameElement = "<span class='terminal-payment-grid-avatar ui-icon ui-icon-common-user{$userEmptyAvatar}'><i{$userAvatar}></i></span>"
			. "<span class='terminal-payment-grid-username-inner'>{$userName}</span>"
		;

		$personalUrl = $this->getUserPersonalUrl($userId);

		return "<div class='terminal-payment-grid-username-wrapper'>"
			. "<a class='terminal-payment-grid-username' href='{$personalUrl}'>{$userNameElement}</a>"
			. "</div>"
		;
	}

	private function getUserPersonalUrl(int $userId): Main\Web\Uri
	{
		$template = $this->getUserPersonalUrlTemplate();

		return new Main\Web\Uri(str_replace('#USER_ID#', $userId, $template));
	}

	private function getUserPersonalUrlTemplate(): string
	{
		return Main\Config\Option::get('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $this->getSiteId());
	}

	private function checkPermission(): bool
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		if (!Crm\Order\Permissions\Payment::checkReadPermission(0, $userPermissions))
		{
			$this->arResult['ERROR_MESSAGES'][] = Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_PERMISSION_DENIED');
			return false;
		}

		return true;
	}

	public function isRowsExistsAction(): array
	{
		return [
			'IS_ROWS_EXIST' => $this->isRowsExists()
		];
	}

	/**
	 * Getting array of errors.
	 * @return Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Main\Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function executeComponent()
	{
		$this->initResult();

		if ($this->checkPermission())
		{
			$this->initFilter();

			$this->prepareGrid();
			$this->prepareToolbar();
		}

		$this->includeComponentTemplate();
	}

	private function getTerminalSettingsComponentPath()
	{
		$sliderUrl = \CComponentEngine::makeComponentPath('bitrix:crm.config.terminal.settings');
		$sliderUrl = getLocalPath('components' . $sliderUrl . '/slider.php');

		return $sliderUrl;
	}
}
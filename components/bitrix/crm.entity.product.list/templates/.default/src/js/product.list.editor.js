import {ajax, Cache, Dom, Event, Loc, Reflection, Runtime, Tag, Text, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Row} from './product.list.row';
import {PageEventsManager} from './page.events.manager';
import {DiscountType} from 'catalog.product-calculator';
import SettingsPopup from './settings.button';
import {CurrencyCore} from 'currency.currency-core';
import {ProductSelector} from 'catalog.product-selector';
import HintPopup from './hint.popup';
import {ProductModel} from "catalog.product-model";
import {PULL} from 'pull.client';
import {FieldHintManager} from "./field.hint.manager";
import {Guide} from 'ui.tour';
import 'ui.hint';

const GRID_TEMPLATE_ROW = 'template_0';
const DEFAULT_PRECISION: number = 2;

export class Editor
{
	id: ?string;
	settings: Object;
	ajaxPool: Map = new Map();
	controller: ?BX.Crm.EntityProductListController;
	container: ?HTMLElement;
	form: ?HTMLElement
	products: Row[] = [];
	productsWasInitiated = false;
	isChangedGrid = false;
	pageEventsManager: PageEventsManager;
	cache = new Cache.MemoryCache();

	#fieldHintManager: FieldHintManager;

	actions = {
		disableSaveButton: 'disableSaveButton',
		productChange: 'productChange',
		productListChanged: 'productListChanged',
		updateListField: 'listField',
		stateChanged: 'stateChange',
		updateTotal: 'total'
	};

	stateChange = {
		changed: false,
		sended: false
	};

	updateFieldForList = null;

	totalData = {
		inProgress: false
	};

	productSelectionPopupHandler = this.handleProductSelectionPopup.bind(this);
	productRowAddHandler = this.handleProductRowAdd.bind(this);
	showSettingsPopupHandler = this.handleShowSettingsPopup.bind(this);

	onDialogSelectProductHandler = this.handleOnDialogSelectProduct.bind(this);
	onSaveHandler = this.handleOnSave.bind(this);
	onFocusToProductList = this.handleProductListFocus.bind(this);
	onEntityUpdateHandler = this.handleOnEntityUpdate.bind(this);
	onEditorSubmit = this.handleEditorSubmit.bind(this);
	onInnerCancelHandler = this.handleOnInnerCancel.bind(this);
	onBeforeGridRequestHandler = this.handleOnBeforeGridRequest.bind(this);
	onGridUpdatedHandler = this.handleOnGridUpdated.bind(this);
	onGridRowMovedHandler = this.handleOnGridRowMoved.bind(this);
	onBeforeProductChangeHandler = this.handleOnBeforeProductChange.bind(this);
	onProductChangeHandler = this.handleOnProductChange.bind(this);
	onBeforeProductClearHandler = this.handleOnBeforeProductClear.bind(this);
	onProductClearHandler = this.handleOnProductClear.bind(this);
	dropdownChangeHandler = this.handleDropdownChange.bind(this);
	pullReloadGrid = null;

	changeProductFieldHandler = this.handleFieldChange.bind(this);
	updateTotalDataDelayedHandler = Runtime.debounce(this.updateTotalDataDelayed, 1000, this);

	constructor(id)
	{
		this.setId(id);
	}

	init(config = {})
	{
		this.setSettings(config);

		if (this.canEdit())
		{
			this.addFirstRowIfEmpty();
			this.enableEdit();
		}

		this.initForm();
		this.initProducts();
		this.initGridData();

		this.#fieldHintManager = new FieldHintManager(this.getContainer(), this.getGrid.bind(this));

		EventEmitter.emit(window, 'EntityProductListController', [this]);

		this.#initSupportCustomRowActions();

		this.subscribeDomEvents();
		this.subscribeCustomEvents();

		if (this.getSettingValue('isReserveBlocked', false))
		{
			const headersToLock = ['STORE_INFO', 'RESERVE_INFO'];
			const container = this.getContainer();
			headersToLock.forEach((headerId) => {
				const header = container?.querySelector(`.main-grid-cell-head[data-name="${headerId}"] .main-grid-cell-head-container`);
				if (header)
				{
					Dom.addClass(header, 'main-grid-cell-head-locked');
					header.onclick = (event) => {
						if (Dom.hasClass(event.target, 'ui-hint-icon'))
						{
							return;
						}
						this.openIntegrationLimitSlider();
					};
					const lock = Tag.render`<span class="crm-entity-product-list-locked-header"></span>`;
					header.insertBefore(lock, header.firstChild);
				}
			});
		}

		this
			.getContainer()
			.querySelectorAll('.crm-entity-product-list-add-block')
			.forEach((buttonBlock) => {
				BX.UI.Hint.init(buttonBlock);
			})
		;
	}

	subscribeDomEvents()
	{
		this.unsubscribeDomEvents();
		const container = this.getContainer();

		if (Type.isElementNode(container))
		{
			if (!this.getSettingValue('disabledSelectProductButton', false))
			{
				container.querySelectorAll('[data-role="product-list-select-button"]').forEach((selectButton) => {
					Event.bind(
						selectButton,
						'click',
						this.productSelectionPopupHandler
					);
				});
			}

			if (!this.getSettingValue('disabledAddRowButton', false))
			{
				container.querySelectorAll('[data-role="product-list-add-button"]').forEach((addButton) => {
					Event.bind(
						addButton,
						'click',
						this.productRowAddHandler
					);
				});
			}

			container.querySelectorAll('[data-role="product-list-settings-button"]').forEach((configButton) => {
				Event.bind(
					configButton,
					'click',
					this.showSettingsPopupHandler
				);
			});
		}
	}

	unsubscribeDomEvents()
	{
		const container = this.getContainer();

		if (Type.isElementNode(container))
		{
			container.querySelectorAll('[data-role="product-list-select-button"]').forEach((selectButton) => {
				Event.unbind(
					selectButton,
					'click',
					this.productSelectionPopupHandler
				);
			});

			container.querySelectorAll('[data-role="product-list-add-button"]').forEach((addButton) => {
				Event.unbind(
					addButton,
					'click',
					this.productRowAddHandler
				);
			});

			container.querySelectorAll('[data-role="product-list-settings-button"]').forEach((configButton) => {
				Event.unbind(
					configButton,
					'click',
					this.showSettingsPopupHandler
				);
			});
		}
	}

	subscribeCustomEvents()
	{
		this.unsubscribeCustomEvents();
		EventEmitter.subscribe('CrmProductSearchDialog_SelectProduct', this.onDialogSelectProductHandler);
		EventEmitter.subscribe('BX.Crm.EntityEditor:onSave', this.onSaveHandler);
		EventEmitter.subscribe('onFocusToProductList', this.onFocusToProductList);
		EventEmitter.subscribe('onCrmEntityUpdate', this.onEntityUpdateHandler);
		EventEmitter.subscribe('BX.Crm.EntityEditorAjax:onSubmit', this.onEditorSubmit);
		EventEmitter.subscribe('EntityProductListController:onInnerCancel', this.onInnerCancelHandler);
		EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeGridRequestHandler);
		EventEmitter.subscribe('Grid::updated', this.onGridUpdatedHandler);
		EventEmitter.subscribe('Grid::rowMoved', this.onGridRowMovedHandler);
		EventEmitter.subscribe('BX.Catalog.ProductSelector:onBeforeChange', this.onBeforeProductChangeHandler);
		EventEmitter.subscribe('BX.Catalog.ProductSelector:onChange', this.onProductChangeHandler);
		EventEmitter.subscribe('BX.Catalog.ProductSelector:onBeforeClear', this.onBeforeProductClearHandler);
		EventEmitter.subscribe('BX.Catalog.ProductSelector:onClear', this.onProductClearHandler);
		EventEmitter.subscribe('Dropdown::change', this.dropdownChangeHandler);
		if (PULL)
		{
			this.pullReloadGrid = PULL.subscribe({
				moduleId: 'crm',
				callback: (data) => {
					if (
						data.command === 'onCatalogInventoryManagementEnabled'
						|| data.command === 'onCatalogInventoryManagementDisabled'
					)
					{
						this.reloadGrid(false);
					}
				}
			});
		}
	}

	unsubscribeCustomEvents()
	{
		EventEmitter.unsubscribe('CrmProductSearchDialog_SelectProduct', this.onDialogSelectProductHandler);
		EventEmitter.unsubscribe('BX.Crm.EntityEditor:onSave', this.onSaveHandler);
		EventEmitter.unsubscribe('onFocusToProductList', this.onFocusToProductList);
		EventEmitter.unsubscribe('onCrmEntityUpdate', this.onEntityUpdateHandler);
		EventEmitter.unsubscribe('BX.Crm.EntityEditorAjax:onSubmit', this.onEditorSubmit);
		EventEmitter.unsubscribe('EntityProductListController:onInnerCancel', this.onInnerCancelHandler);
		EventEmitter.unsubscribe('Grid::beforeRequest', this.onBeforeGridRequestHandler);
		EventEmitter.unsubscribe('Grid::updated', this.onGridUpdatedHandler);
		EventEmitter.unsubscribe('Grid::rowMoved', this.onGridRowMovedHandler);
		EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onBeforeChange', this.onBeforeProductChangeHandler);
		EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onChange', this.onProductChangeHandler);
		EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onBeforeClear', this.onBeforeProductClearHandler);
		EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onClear', this.onProductClearHandler);
		EventEmitter.unsubscribe('Dropdown::change', this.dropdownChangeHandler);
		if (!Type.isNil(this.pullReloadGrid))
		{
			this.pullReloadGrid();
		}
	}

	#initSupportCustomRowActions()
	{
		this.getGrid()._clickOnRowActionsButton = () => {};
	}

	handleOnDialogSelectProduct(event: BaseEvent)
	{
		const [productId] = event.getCompatData();
		let id;
		if (this.getProductCount() > 0 || this.products[0]?.getField('ID') <= 0)
		{
			id = this.addProductRow();
		}
		else
		{
			id = this.products[0]?.getField('ID');
		}
		this.selectProductInRow(id, productId)
	}

	selectProductInRow(id: string, productId: number): void
	{
		if (!Type.isStringFilled(id) || Text.toNumber(productId) <= 0)
		{
			return;
		}

		requestAnimationFrame(() => {
			this
				.getProductSelector(id)
				?.onProductSelect(productId)
			;
		});
	}

	handleOnSave(event: BaseEvent)
	{
		const items = [];

		this.products.forEach((product) => {
			const item = {
				fields: {...product.fields},
				rowId: product.fields.ROW_ID
			};
			items.push(item);
		});

		this.setSettingValue('items', items);
	}

	handleProductListFocus(event: BaseEvent)
	{
		if (this.isReadOnly())
		{
			return;
		}

		let listHaveEmptyRows = false;

		for (const product of this.products)
		{
			if (product.isEmptyRow())
			{
				listHaveEmptyRows = true;
				this.focusProductSelector(product.fields['ID']);
				break;
			}
		}

		if (!listHaveEmptyRows)
		{
			this.handleProductRowAdd();
		}
	}

	handleOnEntityUpdate(event: BaseEvent)
	{
		const [data] = event.getData();
		if (
			this.isChanged()
			&& data.entityId === this.getSettingValue('entityId')
			&& data.entityTypeId === this.getSettingValue('entityTypeId')
		)
		{
			this.setGridChanged(false);
			this.reloadGrid(false);
		}
	}

	handleEditorSubmit(event: BaseEvent)
	{
		if (!this.isLocationDependantTaxesEnabled())
		{
			return;
		}
		const entityData = event.getData()[0];
		if (!entityData || !entityData.hasOwnProperty('LOCATION_ID'))
		{
			return;
		}
		if (entityData['LOCATION_ID'] !== this.getLocationId())
		{
			this.setLocationId(entityData['LOCATION_ID']);
			this.reloadGrid(false);
		}
	}

	handleOnInnerCancel(event: BaseEvent)
	{
		if (this.controller)
		{
			this.controller.rollback();
		}

		this.setGridChanged(false);

		EventEmitter.subscribeOnce(
			this,
			'onGridReloaded',
			() => this.actionUpdateTotalData({isInternalChanging: true})
		)
		this.reloadGrid(false);
	}

	changeActivePanelButtons(panelCode: 'top' | 'bottom'): HTMLElement
	{
		const container = this.getContainer();
		const activePanel = container.querySelector('.crm-entity-product-list-add-block-' + panelCode);
		if (Type.isDomNode(activePanel))
		{
			Dom.removeClass(activePanel, 'crm-entity-product-list-add-block-hidden');
			Dom.addClass(activePanel, 'crm-entity-product-list-add-block-active');
		}

		const hiddenPanelCode = (panelCode === 'top') ? 'bottom' : 'top';
		const removePanel = container.querySelector('.crm-entity-product-list-add-block-' + hiddenPanelCode);
		if (Type.isDomNode(removePanel))
		{
			Dom.addClass(removePanel, 'crm-entity-product-list-add-block-hidden');
			Dom.removeClass(removePanel, 'crm-entity-product-list-add-block-active');
		}

		return activePanel;
	}

	reloadGrid(useProductsFromRequest: boolean = true, isInternalChanging: ?boolean = null): void
	{
		if (isInternalChanging === null)
		{
			isInternalChanging = !useProductsFromRequest;
		}

		this.getGrid().reloadTable(
			'POST',
			{useProductsFromRequest},
			() => EventEmitter.emit(this, 'onGridReloaded')
		);
	}

	/*
		keep in mind different actions for this handler:
		- native reload by grid actions (columns settings, etc)		- products from request
		- reload by tax/discount settings button					- products from request		this.reloadGrid(true)
		- rollback													- products from db			this.reloadGrid(false)
		- reload after SalesCenter order save						- products from db			this.reloadGrid(false)
		- reload after save if location had been changed
	 */
	handleOnBeforeGridRequest(event: BaseEvent)
	{
		const [grid, eventArgs] = event.getCompatData();

		if (!grid || !grid.parent || grid.parent.getId() !== this.getGridId())
		{
			return;
		}

		// reload by native grid actions (columns settings, etc), otherwise by this.reloadGrid()
		const isNativeAction = !('useProductsFromRequest' in eventArgs.data);
		const useProductsFromRequest = isNativeAction ? true : eventArgs.data.useProductsFromRequest;

		eventArgs.url = this.getReloadUrl();
		eventArgs.method = 'POST';
		eventArgs.sessid = BX.bitrix_sessid();
		eventArgs.data = {
			...eventArgs.data,
			signedParameters: this.getSignedParameters(),
			products: useProductsFromRequest ? this.getProductsFields(Editor.#getAjaxFields()) : null,
			locationId: this.getLocationId(),
			currencyId: this.getCurrencyId(),
		};

		this.clearEditor();

		if (isNativeAction && this.isChanged())
		{
			EventEmitter.subscribeOnce('Grid::updated', () => this.actionUpdateTotalData({isInternalChanging: false}));
		}
	}

	handleOnGridUpdated(event: BaseEvent)
	{
		const [grid] = event.getCompatData();

		if (!grid || grid.getId() !== this.getGridId())
		{
			return;
		}

		this.getSettingsPopup().updateCheckboxState();
	}

	handleOnGridRowMoved(event: BaseEvent)
	{
		const [ids, , grid] = event.getCompatData();

		if (!grid || grid.getId() !== this.getGridId())
		{
			return;
		}

		const changed = this.resortProductsByIds(ids);
		if (changed)
		{
			this.refreshSortFields();
			this.numerateRows();
			this.executeActions([{type: this.actions.productListChanged}]);
		}
	}

	initPageEventsManager(): void
	{
		const componentId = this.getSettingValue('componentId');
		this.pageEventsManager = new PageEventsManager({id: componentId});
	}

	getPageEventsManager(): PageEventsManager
	{
		if (!this.pageEventsManager)
		{
			this.initPageEventsManager();
		}

		return this.pageEventsManager;
	}

	canEdit(): boolean
	{
		return this.getSettingValue('allowEdit', false) === true;
	}

	canEditCatalogPrice(): boolean
	{
		return this.getSettingValue('allowCatalogPriceEdit', false) === true;
	}

	canSaveCatalogPrice(): boolean
	{
		return this.getSettingValue('allowCatalogPriceSave', false) === true;
	}

	enableEdit()
	{
		// Cannot use editSelected because checkboxes have been removed
		const rows = this.getGrid().getRows().getRows();
		rows.forEach((current) => {
			if (!current.isHeadChild() && !current.isTemplate())
			{
				current.edit();
			}
		});
	}

	addFirstRowIfEmpty(): void
	{
		if (this.getGrid().getRows().getCountDisplayed() === 0)
		{
			requestAnimationFrame(() => this.addProductRow());
		}
	}

	clearEditor()
	{
		this.unsubscribeProductsEvents();

		this.products = [];
		this.productsWasInitiated = false;

		this.destroySettingsPopup();
		this.unsubscribeDomEvents();
		this.unsubscribeCustomEvents();

		Event.unbindAll(this.container);
	}

	wasProductsInitiated()
	{
		return this.productsWasInitiated;
	}

	unsubscribeProductsEvents()
	{
		this.products.forEach((current) => {
			current.unsubscribeCustomEvents();
		});
	}

	destroy()
	{
		this.setForm(null);
		this.clearController();
		this.clearEditor();
	}

	setController(controller)
	{
		if (this.controller === controller)
		{
			return;
		}
		if (this.controller)
		{
			this.controller.clearProductList();
		}
		this.controller = controller;
	}

	clearController()
	{
		this.controller = null;
	}

	getId()
	{
		return this.id;
	}

	setId(id)
	{
		this.id = id;
	}

	/* settings tools */
	getSettings()
	{
		return this.settings;
	}

	setSettings(settings)
	{
		this.settings = settings ? settings : {};
	}

	getSettingValue(name: string, defaultValue)
	{
		return this.settings.hasOwnProperty(name) ? this.settings[name] : defaultValue;
	}

	setSettingValue(name, value)
	{
		this.settings[name] = value;
	}

	getComponentName()
	{
		return this.getSettingValue('componentName', '');
	}

	getReloadUrl()
	{
		return this.getSettingValue('reloadUrl', '');
	}

	getSignedParameters()
	{
		return this.getSettingValue('signedParameters', '');
	}

	getContainerId()
	{
		return this.getSettingValue('containerId', '');
	}

	getGridId(): string
	{
		return this.getSettingValue('gridId', '');
	}

	getLanguageId()
	{
		return this.getSettingValue('languageId', '');
	}

	getSiteId()
	{
		return this.getSettingValue('siteId', '');
	}

	getCatalogId()
	{
		return this.getSettingValue('catalogId', 0);
	}

	isReadOnly()
	{
		return this.getSettingValue('readOnly', true);
	}

	setReadOnly(readOnly)
	{
		this.setSettingValue('readOnly', readOnly);
	}

	getCurrencyId(): string
	{
		return this.getSettingValue('currencyId', '');
	}

	setCurrencyId(currencyId): void
	{
		this.setSettingValue('currencyId', currencyId);
		this.products.forEach(product => product.getModel()?.setOption('currency', currencyId));
	}

	isLocationDependantTaxesEnabled(): boolean
	{
		return this.getSettingValue('isLocationDependantTaxesEnabled', false);
	}

	getLocationId(): ?string
	{
		return this.getSettingValue('locationId');
	}

	setLocationId(locationId: string): void
	{
		this.setSettingValue('locationId', locationId);
	}

	changeCurrencyId(currencyId): void
	{
		this.setCurrencyId(currencyId);
		const products = [];
		this.products.forEach((product) => {
			const priceFields = {};
			this.#getCalculatePriceFieldNames().forEach((name) => {
				priceFields[name] = product.getField(name);
			});

			products.push({
				fields: priceFields,
				id: product.getId()
			});
		});

		if (products.length > 0)
		{
			this.ajaxRequest('calculateProductPrices', {
				products,
				currencyId
			});
		}

		const editData = this.getGridEditData();
		const templateRow = editData[GRID_TEMPLATE_ROW];
		templateRow['CURRENCY'] = this.getCurrencyId();
		const templateFieldNames = ['DISCOUNT_ROW', 'SUM', 'PRICE'];

		templateFieldNames.forEach((field) => {
			templateRow[field]['CURRENCY']['VALUE'] = this.getCurrencyId();
		});
		this.setGridEditData(editData);
	}

	#getCalculatePriceFieldNames(): []
	{
		return [
			'BASE_PRICE',
			'TAX_INCLUDED',
			'PRICE_NETTO',
			'PRICE_BRUTTO',
			'DISCOUNT_ROW',
			'DISCOUNT_SUM',
			'CURRENCY'
		];
	}

	onCalculatePricesResponse(products: [])
	{
		this.products.forEach((product) => {
			if (Type.isObject(products[product.getId()]))
			{
				product.updateUiCurrencyFields();
				['BASE_PRICE', 'DISCOUNT_ROW', 'DISCOUNT_SUM', 'CURRENCY_ID'].forEach((name) => {
					product.updateField(name, Text.toNumber(products[product.getId()][name]));
				});
				product.setField('CURRENCY', products[product.getId()]['CURRENCY_ID']);
			}
		});

		this.updateTotalUiCurrency();
	}

	updateTotalUiCurrency()
	{
		const totalBlock = BX(this.getSettingValue('totalBlockContainerId', null));
		if (Type.isElementNode(totalBlock))
		{
			totalBlock.querySelectorAll('.crm-product-list-payment-side-table-column').forEach((column) => {
				const valueElement = column.querySelector('.crm-product-list-result-grid-total');
				if (valueElement)
				{
					column.innerHTML = CurrencyCore.getPriceControl(valueElement, this.getCurrencyId());
				}
			});
		}
	}

	getCurrencyText(): string
	{
		const currencyId = this.getCurrencyId();
		if (!Type.isStringFilled(currencyId))
		{
			return '';
		}

		const format = CurrencyCore.getCurrencyFormat(currencyId);

		return format && format.FORMAT_STRING.replace(/(^|[^&])#/, '$1').trim() || '';
	}

	getDataFieldName()
	{
		return this.getSettingValue('dataFieldName', '');
	}

	getDataSettingsFieldName()
	{
		const field = this.getDataFieldName();

		return Type.isStringFilled(field) ? field + '_SETTINGS' : '';
	}

	getDiscountEnabled(): 'Y' | 'N'
	{
		return this.getSettingValue('enableDiscount', 'N');
	}

	getPricePrecision(): number
	{
		return this.getSettingValue('pricePrecision', DEFAULT_PRECISION);
	}

	getQuantityPrecision(): number
	{
		return this.getSettingValue('quantityPrecision', DEFAULT_PRECISION);
	}

	getCommonPrecision(): number
	{
		return this.getSettingValue('commonPrecision', DEFAULT_PRECISION);
	}

	getTaxList(): Array
	{
		return this.getSettingValue('taxList', []);
	}

	getTaxAllowed(): 'Y' | 'N'
	{
		return this.getSettingValue('allowTax', 'N');
	}

	isTaxAllowed(): boolean
	{
		return this.getTaxAllowed() === 'Y';
	}

	getTaxEnabled(): 'Y' | 'N'
	{
		return this.getSettingValue('enableTax', 'N');
	}

	isTaxEnabled(): boolean
	{
		return this.getTaxEnabled() === 'Y';
	}

	isTaxUniform(): boolean
	{
		return this.getSettingValue('taxUniform', true);
	}

	getMeasures(): []
	{
		return this.getSettingValue('measures', []);
	}

	getDefaultMeasure()
	{
		return this.getSettingValue('defaultMeasure', {});
	}

	getRowIdPrefix()
	{
		return this.getSettingValue('rowIdPrefix', 'crm_entity_product_list_');
	}

	/* settings tools finish */

	/* calculate tools */
	parseInt(value: number | string, defaultValue: number = 0)
	{
		let result;

		const isNumberValue = Type.isNumber(value);
		const isStringValue = Type.isStringFilled(value);

		if (!isNumberValue && !isStringValue)
		{
			return defaultValue;
		}

		if (isStringValue)
		{
			value = value.replace(/^\s+|\s+$/g, '');
			const isNegative = value.indexOf('-') === 0;
			result = parseInt(value.replace(/[^\d]/g, ''), 10);
			if (isNaN(result))
			{
				result = defaultValue;
			}
			else
			{
				if (isNegative)
				{
					result = -result;
				}
			}
		}
		else
		{
			result = parseInt(value, 10);
			if (isNaN(result))
			{
				result = defaultValue;
			}
		}

		return result;
	}

	parseFloat(value: number | string, precision: number = DEFAULT_PRECISION, defaultValue: number = 0.0)
	{
		let result;

		const isNumberValue = Type.isNumber(value);
		const isStringValue = Type.isStringFilled(value);

		if (!isNumberValue && !isStringValue)
		{
			return defaultValue;
		}

		if (isStringValue)
		{
			value = value.replace(/^\s+|\s+$/g, '');

			const dot = value.indexOf('.');
			const comma = value.indexOf(',');
			const isNegative = value.indexOf('-') === 0;

			if (dot < 0 && comma >= 0)
			{
				let s1 = value.substr(0, comma);
				const decimalLength = value.length - comma - 1;

				if (decimalLength > 0)
				{
					s1 += '.' + value.substr(comma + 1, decimalLength);
				}

				value = s1;
			}

			value = value.replace(/[^\d.]+/g, '');
			result = parseFloat(value);

			if (isNaN(result))
			{
				result = defaultValue;
			}
			if (isNegative)
			{
				result = -result;
			}
		}
		else
		{
			result = parseFloat(value);
		}

		if (precision >= 0)
		{
			result = this.round(result, precision);
		}

		return result;
	}

	round(value: number, precision: number = DEFAULT_PRECISION)
	{
		const factor = Math.pow(10, precision);

		return Math.round(value * factor) / factor;
	}

	calculatePriceWithoutDiscount(price, discount, discountType)
	{
		let result = 0.0;

		switch (discountType)
		{
			case DiscountType.PERCENTAGE:
				result = (price - ((price * discount) / 100));
				break;

			case DiscountType.MONETARY:
				result = (price - discount);
				break;
		}

		return result;
	}

	calculateDiscountRate(originalPrice, price)
	{
		if (originalPrice === 0.0)
		{
			return 0.0;
		}

		if (price === 0.0)
		{
			return originalPrice > 0 ? 100.0 : -100.0;
		}

		return ((100 * (originalPrice - price)) / originalPrice);
	}

	calculateDiscount(originalPrice, discountRate)
	{
		return originalPrice * discountRate / 100;
	}

	calculatePriceWithoutTax(price, taxRate)
	{
		// Tax is not included in price
		return price / (1 + (taxRate / 100));
	}

	calculatePriceWithTax(price, taxRate)
	{
		// Tax is included in price
		return price * (1 + (taxRate / 100));
	}

	/* calculate tools finish */

	getContainer()
	{
		return this.cache.remember('container', () => {
			return document.getElementById(this.getContainerId());
		});
	}

	initForm()
	{
		const formId = this.getSettingValue('formId', '');
		const form = Type.isStringFilled(formId) ? BX('form_' + formId) : null;

		if (Type.isElementNode(form))
		{
			this.setForm(form);
		}
	}

	isExistForm()
	{
		return Type.isElementNode(this.getForm());
	}

	getForm()
	{
		return this.form;
	}

	setForm(form)
	{
		this.form = form;
	}

	initFormFields()
	{
		const container = this.getForm();
		if (Type.isElementNode(container))
		{
			const field = this.getDataField();
			if (!Type.isElementNode(field))
			{
				this.initDataField();
			}

			const settingsField = this.getDataSettingsField();
			if (!Type.isElementNode(settingsField))
			{
				this.initDataSettingsField();
			}
		}
	}

	initFormField(fieldName)
	{
		const container = this.getForm();

		if (Type.isElementNode(container) && Type.isStringFilled(fieldName))
		{
			Dom.append(
				Dom.create(
					'input',
					{attrs: {type: "hidden", name: fieldName}}
				),
				container
			)
		}
	}

	removeFormFields()
	{
		const field = this.getDataField();
		if (Type.isElementNode(field))
		{
			Dom.remove(field);
		}

		const settingsField = this.getDataSettingsField();
		if (Type.isElementNode(settingsField))
		{
			Dom.remove(settingsField);
		}
	}

	initDataField()
	{
		this.initFormField(this.getDataFieldName());
	}

	initDataSettingsField()
	{
		this.initFormField(this.getDataSettingsFieldName());
	}

	getFormField(fieldName)
	{
		const container = this.getForm();

		if (Type.isElementNode(container) && Type.isStringFilled(fieldName))
		{
			return container.querySelector('input[name="' + fieldName + '"]');
		}

		return null;
	}

	getDataField()
	{
		return this.getFormField(this.getDataFieldName());
	}

	getDataSettingsField()
	{
		return this.getFormField(this.getDataSettingsFieldName());
	}

	getProductCount()
	{
		return this.products
			.filter(item => !item.isEmpty())
			.length
			;
	}

	initProducts()
	{
		const list = this.getSettingValue('items', []);

		const isReserveBlocked = this.getSettingValue('isReserveBlocked', false);

		for (const item of list)
		{
			const fields = {...item.fields};
			const settings = {
				selectorId: item.selectorId,
				isReserveBlocked,
			};
			this.products.push(new Row(item.rowId, fields, settings, this));
		}

		this.numerateRows();

		this.productsWasInitiated = true;
	}

	numerateRows()
	{
		this.products.forEach((product, index) => {
			product.setRowNumber(index + 1);
		})
	}

	getGrid(): ?BX.Main.Grid
	{
		return this.cache.remember('grid', () => {
			const gridId = this.getGridId();

			if (!Reflection.getClass('BX.Main.gridManager.getInstanceById'))
			{
				throw Error(`Cannot find grid with '${gridId}' id.`)
			}

			return BX.Main.gridManager.getInstanceById(gridId);
		});
	}

	initGridData()
	{
		const gridEditData = this.getSettingValue('templateGridEditData', null);
		if (gridEditData)
		{
			this.setGridEditData(gridEditData);
		}
	}

	getGridEditData()
	{
		return this.getGrid().arParams.EDITABLE_DATA;
	}

	setGridEditData(data: {})
	{
		this.getGrid().arParams.EDITABLE_DATA = data;
	}

	setOriginalTemplateEditData(data)
	{
		this.getGrid().arParams.EDITABLE_DATA[GRID_TEMPLATE_ROW] = data;
	}

	handleProductErrorsChange()
	{
		if (this.#childrenHasErrors())
		{
			this.controller.disableSaveButton();
		}
	}

	#childrenHasErrors()
	{
		return this.products
			.filter((product) => product.getModel().getErrorCollection().hasErrors())
			.length > 0
		;
	}

	handleFieldChange(event)
	{
		const row = event.target.closest('tr');
		if (row && row.hasAttribute('data-id'))
		{
			const product = this.getProductById(row.getAttribute('data-id'));
			if (product)
			{
				const cell = event.target.closest('td');
				const fieldCode = this.getFieldCodeByGridCell(row, cell);
				if (fieldCode)
				{
					product.updateFieldByEvent(fieldCode, event);
				}
			}
		}
	}

	handleDropdownChange(event: BaseEvent)
	{
		const [dropdownId, , , , value] = event.getData();
		const regExp = new RegExp(this.getRowIdPrefix() + '([A-Za-z0-9]+)_(\\w+)_control', 'i');
		const matches = dropdownId.match(regExp);
		if (matches)
		{
			const [, rowId, fieldCode] = matches;
			const product = this.getProductById(rowId);
			if (product)
			{
				product.updateField(fieldCode, value, product.modeChanges.EDIT);
			}
		}
	}

	getProductById(id: string): ?Row
	{
		const rowId = this.getRowIdPrefix() + id;

		return this.getProductByRowId(rowId);
	}

	getProductByRowId(rowId: string): ?Row
	{
		return this.products.find((row: Row) => {
			return row.getId() === rowId;
		});
	}

	getFieldCodeByGridCell(row: HTMLTableRowElement, cell: HTMLTableCellElement): ?string
	{
		if (!Type.isDomNode(row) || !Type.isDomNode(cell))
		{
			return null;
		}

		const grid = this.getGrid();
		if (grid)
		{
			const headRow = grid.getRows().getHeadFirstChild();
			const index = [...row.cells].indexOf(cell);

			return headRow.getCellNameByCellIndex(index);
		}

		return null;
	}

	handleProductSelectionPopup(event)
	{
		const caller = 'crm_entity_product_list';
		const jsEventsManagerId = this.getSettingValue('jsEventsManagerId', '');

		const popup = new BX.CDialog({
			content_url: '/bitrix/components/bitrix/crm.product_row.list/product_choice_dialog.php?'
				+ 'caller=' + caller
				+ '&JS_EVENTS_MANAGER_ID=' + BX.util.urlencode(jsEventsManagerId)
				+ '&sessid=' + BX.bitrix_sessid(),
			height: Math.max(500, window.innerHeight - 400),
			width: Math.max(800, window.innerWidth - 400),
			draggable: true,
			resizable: true,
			min_height: 500,
			min_width: 800,
			zIndex: 800
		});

		EventEmitter.subscribeOnce(popup, 'onWindowRegister', BX.defer(() => {
			popup.Get().style.position = 'fixed';
			popup.Get().style.top = (parseInt(popup.Get().style.top) - BX.GetWindowScrollPos().scrollTop) + 'px';
		}));

		EventEmitter.subscribeOnce(window, 'EntityProductListController:onInnerCancel', BX.defer(() => {
			popup.Close();
		}));

		if (!Type.isUndefined(BX.Crm.EntityEvent))
		{
			EventEmitter.subscribeOnce(window, BX.Crm.EntityEvent.names.update, BX.defer(() => {
				requestAnimationFrame(() => {
					popup.Close()
				}, 0);
			}));
		}

		popup.Show();
	}

	addProductRow(anchorProduct: Row = null): string
	{
		const row = this.createGridProductRow();
		const newId = row.getId();

		if (anchorProduct)
		{
			const anchorRowNode = this.getGrid().getRows().getById(anchorProduct.getField('ID'))?.getNode();
			if (anchorRowNode)
			{
				anchorRowNode.parentNode.insertBefore(row.getNode(), anchorRowNode.nextSibling);
			}
		}

		this.initializeNewProductRow(newId, anchorProduct);
		this.getGrid().bindOnRowEvents();
		return newId;
	}

	handleProductRowAdd(): void
	{
		const id = this.addProductRow();
		this.focusProductSelector(id);
	}

	handleShowSettingsPopup()
	{
		this.getSettingsPopup().show();
	}

	destroySettingsPopup()
	{
		if (this.cache.has('settings-popup'))
		{
			this.cache.get('settings-popup').getPopup().destroy();
			this.cache.delete('settings-popup');
		}
	}

	getSettingsPopup()
	{
		return this.cache.remember('settings-popup', () => {
			return new SettingsPopup(
				this.getContainer().querySelector('.crm-entity-product-list-add-block-active [data-role="product-list-settings-button"]'),
				this.getSettingValue('popupSettings', []),
				this
			);
		});
	}

	getHintPopup(): HintPopup
	{
		return this.cache.remember('hint-popup', () => {
			return new HintPopup(this);
		});
	}

	createGridProductRow(): BX.Grid.Row
	{
		const newId = Text.getRandom();
		const originalTemplate = this.redefineTemplateEditData(newId);

		const grid = this.getGrid();
		let newRow;
		if (this.getSettingValue('newRowPosition') === 'bottom')
		{
			newRow = grid.appendRowEditor();
		}
		else
		{
			newRow = grid.prependRowEditor();
		}

		const newNode = newRow.getNode();

		if (Type.isDomNode(newNode))
		{
			newNode.setAttribute('data-id', newId);
			newRow.makeCountable();
		}

		if (originalTemplate)
		{
			this.setOriginalTemplateEditData(originalTemplate);
		}

		EventEmitter.emit('Grid::thereEditedRows', []);

		grid.adjustRows();
		grid.updateCounterDisplayed();
		grid.updateCounterSelected();

		return newRow;
	}

	handleDeleteRow(rowId, event: BaseEvent)
	{
		event.preventDefault();
		this.deleteRow(rowId);
	}

	redefineTemplateEditData(newId: string)
	{
		const data = this.getGridEditData();
		const originalTemplateData = data[GRID_TEMPLATE_ROW];
		const customEditData = this.prepareCustomEditData(originalTemplateData, newId);

		this.setOriginalTemplateEditData({...originalTemplateData, ...customEditData})

		return originalTemplateData;
	}

	prepareCustomEditData(originalEditData, newId)
	{
		const customEditData = {};
		const templateIdMask = this.getSettingValue('templateIdMask', '');

		for (let i in originalEditData)
		{
			if (originalEditData.hasOwnProperty(i))
			{
				if (Type.isStringFilled(originalEditData[i]) && originalEditData[i].indexOf(templateIdMask) >= 0)
				{
					customEditData[i] = originalEditData[i].replace(
						new RegExp(templateIdMask, 'g'),
						newId
					);
				}
				else if (Type.isPlainObject(originalEditData[i]))
				{
					customEditData[i] = this.prepareCustomEditData(originalEditData[i], newId);
				}
				else
				{
					customEditData[i] = originalEditData[i];
				}
			}
		}

		return customEditData;
	}

	initializeNewProductRow(newId, anchorProduct: Row = null): Row
	{
		let fields = anchorProduct?.getFields();
		if (Type.isNil(fields))
		{
			fields = {
				...this.getSettingValue('templateItemFields', {}),
				...{
					CURRENCY: this.getCurrencyId()
				}
			};

			const lastItem = this.products[this.products.length - 1];
			if (lastItem)
			{
				fields.TAX_INCLUDED = lastItem.getField('TAX_INCLUDED');
			}
		}

		const rowId = this.getRowIdPrefix() + newId;
		fields.ID = newId;
		if (Type.isObject(fields.IMAGE_INFO))
		{
			delete(fields.IMAGE_INFO.input);
		}
		delete(fields.RESERVE_ID);
		const isReserveBlocked = this.getSettingValue('isReserveBlocked', false);
		const settings = {
			isReserveBlocked,
			selectorId: 'crm_grid_' + rowId,
		};
		const product = new Row(rowId, fields, settings, this);
		product.refreshFieldsLayout();

		if (anchorProduct instanceof Row)
		{
			this.products.splice(1 + this.products.indexOf(anchorProduct), 0, product);

			product.getSelector()?.reloadFileInput();
			product.getSelector()?.layout();
			product.updateUiMeasure(
				product.getField('MEASURE_CODE'),
				Text.encode(product.getField('MEASURE_NAME'))
			);
		}
		else if (this.getSettingValue('newRowPosition') === 'bottom')
		{
			this.products.push(product);
		}
		else
		{
			this.products.unshift(product);
		}

		this.refreshSortFields();
		this.numerateRows();

		product.updateUiCurrencyFields();
		this.updateTotalUiCurrency();

		product
			.getSelector()
			?.setConfig(
				'ENABLE_EMPTY_PRODUCT_ERROR',
				this.getSettingValue('enableEmptyProductError', false)
			)
		;
		return product;
	}

	isTaxIncludedActive(): boolean
	{
		return this.products
			.filter((product) => product.isTaxIncluded())
			.length > 0
		;
	}

	getProductSelector(newId: string): ?ProductSelector
	{
		return ProductSelector.getById('crm_grid_' + this.getRowIdPrefix() + newId);
	}

	focusProductSelector(newId: string): void
	{
		requestAnimationFrame(() => {
			this
				.getProductSelector(newId)
				?.searchInDialog()
				.focusName()
			;
		});
	}

	handleOnBeforeProductChange(event: BaseEvent)
	{
		const data = event.getData();
		const product = this.getProductByRowId(data.rowId);
		if (product)
		{
			this.getGrid().tableFade();
			product.resetExternalActions();
		}
	}

	handleOnProductChange(event: BaseEvent)
	{
		const data = event.getData();

		const productRow = this.getProductByRowId(data.rowId);
		if (productRow && data.fields)
		{
			const promise = new Promise((resolve, reject) => {
				const fields = data.fields;

				if (!Type.isNil(fields['IMAGE_INFO']))
				{
					fields['IMAGE_INFO'] = JSON.stringify(fields['IMAGE_INFO']);
				}

				if (this.getCurrencyId() !== fields['CURRENCY_ID'])
				{
					fields['CURRENCY'] = fields['CURRENCY_ID'];

					const priceFields = {};
					this.#getCalculatePriceFieldNames().forEach((name) => {
						priceFields[name] = data.fields[name]
					});

					const products = [{
						fields: priceFields,
						id: productRow.getId()
					}];

					ajax.runComponentAction(
						this.getComponentName(),
						'calculateProductPrices',
						{
							mode: 'class',
							signedParameters: this.getSignedParameters(),
							data: {
								products,
								currencyId: this.getCurrencyId(),
								options: {
									ACTION: 'calculateProductPrices'
								}
							}
						}
					).then(
						(response) => {
							const changedFields = response.data.result[productRow.getId()];
							if (changedFields)
							{
								changedFields['CUSTOMIZED'] = 'Y';
								resolve(Object.assign(fields, changedFields));
							}
							else
							{
								resolve(fields);
							}
						}
					);
				}
				else
				{
					resolve(fields);
				}
			});

			promise.then((fields) => {
				if (this.products.length > 1)
				{
					const taxId = fields['VAT_ID'] || fields['TAX_ID'];
					const taxIncluded = fields['VAT_INCLUDED'] || fields['TAX_INCLUDED'];

					if (taxId > 0 && taxIncluded !== productRow.getTaxIncluded())
					{
						const taxRate = this.getTaxList()?.find((item) => parseInt(item.ID) === taxId);
						if (taxRate?.VALUE > 0 && taxIncluded === 'Y')
						{
							fields['BASE_PRICE'] = fields['BASE_PRICE'] / (1 + taxRate.VALUE / 100);
						}
					}

					['TAX_INCLUDED', 'VAT_INCLUDED'].forEach(name => delete(fields[name]));
				}

				if (productRow.getField('OFFER_ID') !== fields.ID)
				{
					fields['ROW_RESERVED'] = 0;
					fields['DEDUCTED_QUANTITY'] = 0;
					if (!this.getSettingValue('allowDiscountChange', true))
					{
						fields['DISCOUNT_ROW'] = 0;
						fields['DISCOUNT_SUM'] = 0;
						fields['DISCOUNT_RATE'] = 0;
						fields['DISCOUNT'] = 0;
						productRow.updateUiHtmlField(
							'DISCOUNT_PRICE',
							CurrencyCore.currencyFormat(0, this.getCurrencyId(), true)
						);
						productRow.updateUiHtmlField(
							'DISCOUNT_ROW',
							CurrencyCore.currencyFormat(0, this.getCurrencyId(), true)
						);
					}
				}

				Object.keys(fields).forEach((key) => {
					productRow.updateFieldValue(key, fields[key]);
				});

				if (!Type.isStringFilled(fields['CUSTOMIZED']))
				{
					productRow.setField('CUSTOMIZED', 'N');
				}

				productRow.setField('IS_NEW', data.isNew ? 'Y' : 'N');

				productRow.layoutReserveControl();
				productRow.layoutStoreSelector();
				productRow.initHandlersForSelectors();
				productRow.updateUiStoreAmountData();
				productRow.updatePropertyFields();
				productRow.modifyBasePriceInput();
				productRow.executeExternalActions();
				this.getGrid().tableUnfade();
			});
		}
		else
		{
			this.getGrid().tableUnfade();
		}
	}

	handleOnBeforeProductClear(event: BaseEvent)
	{
		const {rowId} = event.getData();
		const product = this.getProductByRowId(rowId);
		product.clearPropertyFields();
	}

	handleOnProductClear(event: BaseEvent)
	{
		const {rowId} = event.getData();

		const product = this.getProductByRowId(rowId);
		if (product)
		{
			product.layoutReserveControl();
			product.initHandlersForSelectors();
			product.changeBasePrice(0);
			if (!this.getSettingValue('allowDiscountChange', true))
			{
				product.setDiscount(0);
				product.updateUiHtmlField(
					'DISCOUNT_PRICE',
					CurrencyCore.currencyFormat(0, this.getCurrencyId(), true)
				);
				product.updateUiHtmlField(
					'DISCOUNT_ROW',
					CurrencyCore.currencyFormat(0, this.getCurrencyId(), true)
				);
			}
			product.modifyBasePriceInput();
			product.executeExternalActions();
		}
	}

	compileProductData(): void
	{
		if (!this.isExistForm())
		{
			return;
		}
		this.initFormFields();

		const field = this.getDataField();
		const settingsField = this.getDataSettingsField();

		this.cleanProductRows();

		if (Type.isElementNode(field) && Type.isElementNode(settingsField))
		{
			field.value = this.prepareProductDataValue();

			settingsField.value = JSON.stringify({
				ENABLE_DISCOUNT: this.getDiscountEnabled(),
				ENABLE_TAX: this.getTaxEnabled()
			});
		}

		this.addFirstRowIfEmpty();
	}

	prepareProductDataValue(): string
	{
		let productDataValue = '';

		if (this.getProductCount())
		{
			const productData = [];

			this.products.forEach((item) => {
				const saveFields = item.getFields(Editor.#getAjaxFields());

				if (!/^[0-9]+$/.test(saveFields['ID']))
				{
					saveFields['ID'] = 0;
				}

				saveFields['CUSTOMIZED'] = 'Y';

				productData.push(saveFields);
			});

			productDataValue = JSON.stringify(productData);
		}

		return productDataValue;
	}

	static #getAjaxFields(): []
	{
		return [
			'ID',
			'PRODUCT_ID',
			'PRODUCT_NAME',
			'QUANTITY',
			'TAX_RATE',
			'TAX_INCLUDED',
			'PRICE_EXCLUSIVE',
			'PRICE_NETTO',
			'PRICE_BRUTTO',
			'PRICE',
			'CUSTOMIZED',
			'BASE_PRICE',
			'DISCOUNT_ROW',
			'DISCOUNT_SUM',
			'DISCOUNT_TYPE_ID',
			'DISCOUNT_RATE',
			'CURRENCY',
			'STORE_ID',
			'INPUT_RESERVE_QUANTITY',
			'RESERVE_QUANTITY',
			'DATE_RESERVE_END',
			'SORT',
			'MEASURE_CODE',
			'MEASURE_NAME',
			'TYPE',
		];
	}

	/* actions */
	executeActions(actions)
	{
		if (!Type.isArrayFilled(actions))
		{
			return;
		}

		const disableSaveButton = actions
			.filter(
				(action) => action.type === this.actions.updateTotal || action.type === this.actions.disableSaveButton
			)
			.length > 0
		;

		for (const item of actions)
		{
			if (
				!Type.isPlainObject(item)
				|| !Type.isStringFilled(item.type)
			)
			{
				continue;
			}

			switch (item.type)
			{
				case this.actions.productChange:
					this.actionSendProductChange(item, disableSaveButton);
					break;

				case this.actions.productListChanged:
					this.actionSendProductListChanged(disableSaveButton);
					break;

				case this.actions.updateListField:
					this.actionUpdateListField(item);
					break;

				case this.actions.updateTotal:
					this.actionUpdateTotalData();
					break;

				case this.actions.stateChanged:
					this.actionSendStatusChange(item);
					break;
			}
		}
	}

	actionSendProductChange(item, disableSaveButton)
	{
		if (!Type.isStringFilled(item.id))
		{
			return;
		}

		const product = this.getProductByRowId(item.id);
		if (!product)
		{
			return;
		}

		EventEmitter.emit(this, 'ProductList::onChangeFields', {
			rowId: item.id,
			productId: product.getField('PRODUCT_ID'),
			fields: this.getProductByRowId(item.id).getCatalogFields()
		});

		if (this.controller)
		{
			this.controller.productChange(disableSaveButton);
			this.setGridChanged(true);
		}
	}

	actionSendProductListChanged(disableSaveButton: boolean = false): void
	{
		if (this.controller)
		{
			this.controller.productChange(disableSaveButton);
			this.setGridChanged(true);
		}
	}

	actionUpdateListField(item)
	{
		if (!Type.isStringFilled(item.field) || !('value' in item))
		{
			return;
		}

		if (!this.allowUpdateListField(item.field))
		{
			return;
		}

		this.updateFieldForList = item.field;

		for (const row of this.products)
		{
			row.updateFieldByName(item.field, item.value);
		}

		this.updateFieldForList = null;
	}

	actionUpdateTotalData(options = {})
	{
		if (this.totalData.inProgress)
		{
			return;
		}

		this.updateTotalDataDelayedHandler(options);
	}

	actionSendStatusChange(item)
	{
		if (!('value' in item))
		{
			return;
		}
		if (this.stateChange.changed === item.value)
		{
			return;
		}
		this.stateChange.changed = item.value;
		if (this.stateChange.sended)
		{
			return;
		}
		this.stateChange.sended = true;
	}

	/* actions finish */

	/* action tools */
	allowUpdateListField(field)
	{
		if (this.updateFieldForList !== null)
		{
			return false;
		}

		let result = true;

		switch (field)
		{
			case 'TAX_INCLUDED':
				result = this.isTaxUniform() && this.isTaxAllowed();
				break;
		}

		return result;
	}

	setGridChanged(changed: boolean)
	{
		this.isChangedGrid = changed;
	}

	isChanged(): boolean
	{
		return this.isChangedGrid;
	}

	updateTotalDataDelayed(options = {})
	{
		if (this.totalData.inProgress)
		{
			return;
		}

		this.totalData.inProgress = true;

		const products = this.getProductsFields(this.getProductFieldListForTotalData());
		products.forEach(item => item['CUSTOMIZED'] = 'Y');

		this.ajaxRequest('calculateTotalData', {
			options,
			products,
			currencyId: this.getCurrencyId()
		});
	}

	getProductsFields(fields: Array = [])
	{
		const productFields = [];

		for (const item of this.products)
		{
			productFields.push(item.getFields(fields));
		}

		return productFields;
	}

	getProductFieldListForTotalData()
	{
		return [
			'PRODUCT_ID',
			'PRODUCT_NAME',
			'QUANTITY',
			'DISCOUNT_TYPE_ID',
			'DISCOUNT_RATE',
			'DISCOUNT_SUM',
			'TAX_RATE',
			'TAX_INCLUDED',
			'PRICE_EXCLUSIVE',
			'PRICE',
			'CUSTOMIZED'
		];
	}

	setTotalData(data, options = {})
	{
		const item = BX(this.getSettingValue('totalBlockContainerId', null));
		if (Type.isElementNode(item))
		{
			const currencyId = this.getCurrencyId();
			const list = ['totalCost', 'totalDelivery', 'totalTax', 'totalWithoutTax', 'totalDiscount', 'totalWithoutDiscount'];

			for (const id of list)
			{
				const row = item.querySelector('[data-total="' + id + '"]');

				if (Type.isElementNode(row) && (id in data))
				{
					row.innerHTML = CurrencyCore.currencyFormat(data[id], currencyId, false);
				}
			}
		}

		this.sendTotalData(data, options);
		this.totalData.inProgress = false;
	}

	sendTotalData(data, options)
	{
		if (this.controller)
		{
			let needMarkAsChanged = true;
			if (
				Type.isObject(options)
				&& (options.isInternalChanging === true || options.isInternalChanging === 'true')
			)
			{
				needMarkAsChanged = false;
			}

			setTimeout(
				() => {
					this.controller.changeSumTotal(data, needMarkAsChanged, !this.#childrenHasErrors());
				},
				500
			);
		}
	}

	/* action tools finish */

	/* ajax tools */
	ajaxRequest(action, data)
	{
		const requestKey = Text.getRandom();
		this.ajaxPool.set(action, requestKey);

		if (!Type.isPlainObject(data.options))
		{
			data.options = {};
		}
		data.options.ACTION = action;
		data.options.REQUEST_KEY = requestKey;

		ajax.runComponentAction(
			this.getComponentName(),
			action,
			{
				mode: 'class',
				signedParameters: this.getSignedParameters(),
				data: data
			}
		).then(
			(response) => this.ajaxResultSuccess(response, data.options),
			(response) => this.ajaxResultFailure(response, data.options)
		);
	}

	ajaxResultSuccess(response, requestOptions)
	{
		if (!this.ajaxResultCommonCheck(response) || this.ajaxPool.get(response.data.action) !== requestOptions.REQUEST_KEY)
		{
			return;
		}

		this.ajaxPool.delete(response.data.action);

		EventEmitter.emit(this, 'onAjaxSuccess', response.data.action);

		switch (response.data.action)
		{
			case 'calculateTotalData':
				if (Type.isPlainObject(response.data.result))
				{
					this.setTotalData(response.data.result, requestOptions);
				}

				break;
			case 'calculateProductPrices':
				if (Type.isPlainObject(response.data.result))
				{
					this.onCalculatePricesResponse(response.data.result);
				}

				break;
		}
	}

	validateSubmit()
	{
		return new Promise((resolve, reject) => {
			const currentBalloon = BX.UI.Notification.Center.getBalloonByCategory(ProductModel.SAVE_NOTIFICATION_CATEGORY);
			if (currentBalloon)
			{
				EventEmitter.subscribeOnce(
					currentBalloon,
					BX.UI.Notification.Event.getFullName('onClose'),
					() => {
						setTimeout(resolve, 500);
					}
				);
				currentBalloon.close();
			}
			else
			{
				setTimeout(resolve(), 50);
			}
		});
	}

	ajaxResultFailure(response, requestOptions)
	{
		this.ajaxPool.delete(requestOptions.ACTION);
	}

	ajaxResultCommonCheck(responce)
	{
		if (!Type.isPlainObject(responce))
		{
			return false;
		}

		if (!Type.isStringFilled(responce.status))
		{
			return false;
		}

		if (responce.status !== 'success')
		{
			return false;
		}

		if (!Type.isPlainObject(responce.data))
		{
			return false;
		}

		if (!Type.isStringFilled(responce.data.action))
		{
			return false;
		}

		// noinspection RedundantIfStatementJS
		if (!('result' in responce.data))
		{
			return false;
		}

		return true;
	}

	deleteRow(rowId: string, skipActions: boolean = false): void
	{
		if (!Type.isStringFilled(rowId))
		{
			return;
		}

		const gridRow = this.getGrid().getRows().getById(rowId);
		if (gridRow)
		{
			Dom.remove(gridRow.getNode());
			this.getGrid().getRows().reset();
		}

		const productRow = this.getProductById(rowId);
		if (productRow)
		{
			const index = this.products.indexOf(productRow);
			if (index > -1)
			{
				this.products.splice(index, 1);
				this.refreshSortFields();
				this.numerateRows();
			}
		}

		EventEmitter.emit('Grid::thereEditedRows', []);

		if (!skipActions)
		{
			this.addFirstRowIfEmpty();
			this.executeActions([
				{type: this.actions.productListChanged},
				{type: this.actions.updateTotal}
			]);
		}
	}

	copyRow(row: Row): void
	{
		this.addProductRow(row)
		this.refreshSortFields();
		this.numerateRows();

		EventEmitter.emit('Grid::thereEditedRows', []);

		this.executeActions([
			{type: this.actions.productListChanged},
			{type: this.actions.updateTotal}
		]);
	}

	cleanProductRows(): void
	{
		this.products
			.filter(item => item.isEmpty())
			.forEach((row) => this.deleteRow(row.getField('ID'), true))
		;
	}

	resortProductsByIds(ids: Array): boolean
	{
		let changed = false;

		if (Type.isArrayFilled(ids))
		{
			this.products.sort((a, b) => {
				if (ids.indexOf(a.getField('ID')) > ids.indexOf(b.getField('ID')))
				{
					return 1;
				}

				changed = true;

				return -1;
			});
		}

		return changed;
	}

	refreshSortFields(): void
	{
		this.products.forEach((item, index) => item.setField('SORT', (index + 1) * 10));
	}

	handleOnTabShow(): void
	{
		EventEmitter.emit('onDemandRecalculateWrapper', [this]);
	}

	showFieldTourHint(fieldName: string, tourData: Object, endTourHandler: Function, addictedFields: Array<string> = [], rowId: string = ''): void
	{
		if (this.products.length > 0)
		{
			let productNode = this.products[0].getNode();
			if (this.getProductByRowId(rowId))
			{
				productNode = this.getProductByRowId(rowId).getNode();
			}

			const addictedNodes = [];
			for (const fieldName of addictedFields)
			{
				const fieldNode = productNode.querySelector(`[data-name="${fieldName}"]`);
				if (fieldNode !== null)
				{
					addictedNodes.push(fieldNode);
				}
			}

			const fieldNode = productNode.querySelector(`[data-name="${fieldName}"]`);

			if (fieldNode !== null)
			{
				this.#fieldHintManager.processFieldTour(fieldNode, tourData, endTourHandler, addictedNodes);
			}
		}
	}

	getActiveHint(): Guide|null
	{
		return this.#fieldHintManager.getActiveHint();
	}

	openIntegrationLimitSlider()
	{
		top.BX.UI.InfoHelper.show('limit_store_crm_integration');
		const helperSlider = top.BX.UI.InfoHelper.getSlider();
		top.BX.Event.EventEmitter.subscribeOnce('SidePanel.Slider:onCloseComplete', (event) => {
			const slider = event.getData()[0]?.getSlider();
			if (slider !== helperSlider)
			{
				return;
			}

			window.location.search += '&active_tab=tab_products';
		});
	}

	getRestrictedProductTypes(): Array
	{
		return this.getSettingValue('restrictedProductTypes', []);
	}
}

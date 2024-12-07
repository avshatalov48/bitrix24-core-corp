import {Cache, Dom, Event, Loc, Reflection, Runtime, Tag, Text, Type} from 'main.core';
import {Editor} from './product.list.editor';
import {DiscountType, DiscountTypes, FieldScheme, ProductCalculator} from 'catalog.product-calculator';
import {CurrencyCore} from 'currency.currency-core';
import 'ui.hint';
import 'ui.notification';
import HintPopup from './hint.popup';
import {ProductModel} from "catalog.product-model";
import {EventEmitter} from "main.core.events";
import {StoreSelector} from "catalog.store-selector";
import ReserveControl from "./reserve.control";
import {PopupMenu} from "main.popup";
import {ProductSelector} from "catalog.product-selector";
import StoreAvailablePopup from './store.available.popup';
import MoneyControl from './money.control';

type Action = {
	type: string,
	field?: string,
	value?: string,
};

type Settings = {}

const MODE_EDIT = 'EDIT';
const MODE_SET = 'SET';

const enableImageInputCache = new Map();

export class Row
{
	static CATALOG_PRICE_CHANGING_DISABLED = 'CATALOG_PRICE_CHANGING_DISABLED';

	id: ?string;
	settings: Object;
	editor: ?Editor;
	model: ?ProductModel;
	mainSelector: ?ProductSelector;
	reserveControl: ?ReserveControl;
	storeSelector: ?StoreSelector;
	storeAvailablePopup: ?StoreAvailablePopup;
	fields: Object = {};
	externalActions: Array<Action> = [];

	handleChangeStoreData = this.#onChangeStoreData.bind(this);
	handleProductErrorsChange = Runtime.debounce(this.#onProductErrorsChange, 500, this);
	handleMainSelectorClear = Runtime.debounce(this.#onMainSelectorClear.bind(this), 500, this);
	handleStoreFieldChange = Runtime.debounce(this.#onStoreFieldChange.bind(this), 500, this);
	handleStoreFieldClear = Runtime.debounce(this.#onStoreFieldClear.bind(this), 500, this);

	cache = new Cache.MemoryCache();
	modeChanges = {
		EDIT: MODE_EDIT,
		SET: MODE_SET,
	};
	onAfterExecuteExternalActions: ?CallableFunction;

	constructor(id: string, fields: Object, settings: Settings, editor: Editor): void
	{
		this.setId(id);
		this.setSettings(settings);
		this.setEditor(editor);
		this.setModel(fields, settings);
		this.setFields(fields);
		this.#initActions();
		this.#initSelector();
		this.#initStoreSelector();
		this.#initStoreAvailablePopup();
		this.#initReservedControl();
		this.modifyBasePriceInput();
		this.modifyQuantityInput();
		this.refreshFieldsLayout();
		this.updateUiStoreAmountData();
		this.initHandlersForSelectors();
		requestAnimationFrame(this.initHandlers.bind(this));
	}

	getNode(): ?HTMLElement
	{
		return this.cache.remember('node', () => {
			const rowId = this.getField('ID', 0);

			return this.getEditorContainer().querySelector('[data-id="' + rowId + '"]');
		});
	}

	getSelector(): ?ProductSelector
	{
		return this.mainSelector;
	}

	isNewRow(): Boolean
	{
		return isNaN(+this.getField('ID'));
	}

	getId(): string
	{
		return this.id;
	}

	setId(id: string): void
	{
		this.id = id;
	}

	getSettings(): {}
	{
		return this.settings;
	}

	setSettings(settings: Settings): void
	{
		this.settings = Type.isPlainObject(settings) ? settings : {};
	}

	getSettingValue(name, defaultValue)
	{
		return this.settings.hasOwnProperty(name) ? this.settings[name] : defaultValue;
	}

	setSettingValue(name, value): void
	{
		this.settings[name] = value;
	}

	setEditor(editor: Editor): void
	{
		this.editor = editor;
	}

	getEditor(): Editor
	{
		return this.editor;
	}

	getEditorContainer(): HTMLElement
	{
		return this.getEditor().getContainer();
	}

	getHintPopup(): HintPopup
	{
		return this.getEditor().getHintPopup();
	}

	initHandlers()
	{
		const editor = this.getEditor();

		this.getNode().querySelectorAll('input').forEach((node) => {
			Event.bind(node, 'input', editor.changeProductFieldHandler);
			Event.bind(node, 'change', editor.changeProductFieldHandler);
			// disable drag-n-drop events for text fields
			Event.bind(node, 'mousedown', (event) => event.stopPropagation());
		});
		this.getNode().querySelectorAll('select').forEach((node) => {
			Event.bind(node, 'change', editor.changeProductFieldHandler);
			// disable drag-n-drop events for select fields
			Event.bind(node, 'mousedown', (event) => event.stopPropagation());
		});
	}

	initHandlersForSelectors()
	{
		const editor = this.getEditor();

		const selectorNames = ['MAIN_INFO', 'STORE_INFO', 'RESERVE_INFO'];

		selectorNames.forEach((name) => {
			this.getNode().querySelectorAll('[data-name="'+ name +'"] input[type="text"]').forEach(node => {
				Event.bind(node, 'input', editor.changeProductFieldHandler);
				Event.bind(node, 'change', editor.changeProductFieldHandler);
				// disable drag-n-drop events for select fields
				Event.bind(node, 'mousedown', (event) => event.stopPropagation());
			});
		});
	}

	unsubscribeCustomEvents()
	{
		if (this.mainSelector)
		{
			this.mainSelector.unsubscribeEvents();
			EventEmitter.unsubscribe(
				this.mainSelector,
				'onClear',
				this.handleMainSelectorClear
			);
		}

		if (this.storeSelector)
		{
			this.storeSelector.unsubscribeEvents();
			EventEmitter.unsubscribe(
				this.storeSelector,
				'onChange',
				this.handleStoreFieldChange
			);

			EventEmitter.unsubscribe(
				this.storeSelector,
				'onClear',
				this.handleStoreFieldClear
			);
		}

		EventEmitter.unsubscribe(
			this.model,
			'onChangeStoreData',
			this.handleChangeStoreData,
		);

		EventEmitter.unsubscribe(
			this.model,
			'onErrorsChange',
			this.handleProductErrorsChange,
		);
	}

	#initActions()
	{
		if (this.getEditor().isReadOnly() || this.isRestrictedStoreInfo())
		{
			return;
		}

		const actionCellContentContainer = this.getNode().querySelector('.main-grid-cell-action .main-grid-cell-content');
		if (Type.isDomNode(actionCellContentContainer))
		{
			const actionsButton = Tag.render`
				<a
					href="#"
					class="main-grid-row-action-button"
				></a>
			`;

			Event.bind(actionsButton, 'click', (event) => {
				const menuItems = [
					{
						text: Loc.getMessage('CRM_ENTITY_PL_COPY'),
						onclick: this.handleCopyAction.bind(this),
						disabled: this.editor.getSettingValue('disabledSelectProductInput'),
					},
					{
						text: Loc.getMessage('CRM_ENTITY_PL_DELETE'),
						onclick: this.handleDeleteAction.bind(this),
						disabled: this.getModel().isEmpty() && this.getEditor().products.length <= 1,
					}
				];

				PopupMenu.show({
					id: this.getId() + '_actions_popup',
					bindElement: actionsButton,
					items: menuItems,
					cacheable: false,
				});

				event.preventDefault();
				event.stopPropagation();
			});

			Dom.append(actionsButton, actionCellContentContainer);
		}
	}

	modifyBasePriceInput(): void
	{
		const priceNode = this.#getNodeChildByDataName('PRICE');
		if (!priceNode)
		{
			return;
		}

		const control = new MoneyControl({
			node: priceNode,
			hint: Loc.getMessage('CRM_ENTITY_PL_PRICE_CHANGING_RESTRICTED'),
		});
		if (!this.#isEditableCatalogPrice())
		{
			control.disable();
		}
		else
		{
			control.enable();
		}
	}

	modifyQuantityInput(): void
	{
		if (!this.isRestrictedStoreInfo())
		{
			return;
		}

		const countField = this.#getNodeChildByDataName('QUANTITY');
		if (countField)
		{
			const control = new MoneyControl({
				node: countField,
				hint: Loc.getMessage('CRM_ENTITY_PL_ROW_UPDATE_RESTRICTED_BY_STORE'),
			});
			control.disable();
		}
	}

	#isEditableCatalogPrice(): boolean
	{
		return this.editor.canEditCatalogPrice()
			|| !this.getModel().isCatalogExisted()
			|| this.getModel().isNew()
		;
	}

	#isSaveableCatalogPrice(): boolean
	{
		return this.getModel().isCatalogExisted() && this.getModel().isNew();
	}

	#initSelector()
	{
		const id = 'crm_grid_' + this.getId();
		const enableImageInput = this.editor.getSettingValue('enableSelectProductImageInput', true);

		this.mainSelector = ProductSelector.getById(id);
		if (!this.mainSelector)
		{
			const selectorOptions = {
				iblockId: this.model.getIblockId(),
				basePriceId: this.model.getBasePriceId(),
				currency: this.model.getCurrency(),
				model: this.model,
				config: {
					ENABLE_SEARCH: true,
					IS_ALLOWED_CREATION_PRODUCT: true,
					ENABLE_IMAGE_INPUT: enableImageInput,
					ROLLBACK_INPUT_AFTER_CANCEL: true,
					ENABLE_INPUT_DETAIL_LINK: true,
					ROW_ID: this.getId(),
					ENABLE_SKU_SELECTION: true,
					ENABLE_EMPTY_PRODUCT_ERROR: false,
					SELECTOR_INPUT_DISABLED: this.editor.getSettingValue('disabledSelectProductInput'),
					URL_BUILDER_CONTEXT: this.editor.getSettingValue('productUrlBuilderContext'),
					RESTRICTED_PRODUCT_TYPES: this.getEditor().getRestrictedProductTypes(),
				},
				mode: ProductSelector.MODE_EDIT,
			};

			this.mainSelector = new ProductSelector('crm_grid_' + this.getId(), selectorOptions);
		}
		else
		{
			this.mainSelector.subscribeEvents();

			if (enableImageInput !== enableImageInputCache[id])
			{
				this.mainSelector.setConfig('ENABLE_IMAGE_INPUT', enableImageInput);
				if (enableImageInput)
				{
					this.mainSelector.layoutImage();
				}
			}
		}

		enableImageInputCache[id] = enableImageInput;

		if (this.isRestrictedStoreInfo())
		{
			this.mainSelector.setMode(ProductSelector.MODE_VIEW);
		}

		const mainInfoNode = this.#getNodeChildByDataName('MAIN_INFO');
		if (mainInfoNode)
		{
			const numberSelector = mainInfoNode.querySelector('.main-grid-row-number');
			if (!Type.isDomNode(numberSelector))
			{
				Dom.append(Tag.render`<div class="main-grid-row-number"></div>`, mainInfoNode);
			}

			let selectorWrapper =  mainInfoNode.querySelector('.main-grid-row-product-selector');
			if (!Type.isDomNode(selectorWrapper))
			{
				selectorWrapper = Tag.render`<div class="main-grid-row-product-selector"></div>`;
				Dom.append(selectorWrapper, mainInfoNode);
			}

			this.mainSelector.skuTreeInstance = null;
			if (this.editor.isVisible())
			{
				this.mainSelector.renderTo(selectorWrapper);
			}
			else
			{
				this.mainSelector.wrapper = selectorWrapper;
			}
		}

		EventEmitter.subscribe(
			this.mainSelector,
			'onClear',
			this.handleMainSelectorClear
		);
	}

	#onMainSelectorClear()
	{
		this.updateField('OFFER_ID', 0);
		this.updateField('PRODUCT_NAME', '');
		this.updateUiStoreAmountData();
		this.updateField('DEDUCTED_QUANTITY', 0);
		this.updateField('ROW_RESERVED', 0);
	}

	#initStoreSelector()
	{
		this.storeSelector = new StoreSelector(
			this.getId(),
			{
				inputFieldId: 'STORE_ID',
				inputFieldTitle: 'STORE_TITLE',
				config: {
					ENABLE_SEARCH: true,
					ENABLE_INPUT_DETAIL_LINK: false,
					ROW_ID: this.getId(),
				},
				mode: StoreSelector.MODE_EDIT,
				model: this.model,
			}
		);

		EventEmitter.subscribe(
			this.storeSelector,
			'onChange',
			this.handleStoreFieldChange
		);

		EventEmitter.subscribe(
			this.storeSelector,
			'onClear',
			this.handleStoreFieldClear
		);

		if (this.isRestrictedStoreInfo() && this.storeSelector.searchInput)
		{
			this.storeSelector.searchInput.disable(
				Loc.getMessage('CRM_ENTITY_PL_ROW_UPDATE_STORE_RESTRICTED_BY_STORE')
			);
		}

		this.layoutStoreSelector();
	}

	layoutStoreSelector()
	{
		const storeWrapper = this.#getNodeChildByDataName('STORE_INFO');
		if (this.storeSelector && storeWrapper)
		{
			storeWrapper.innerHTML = '';

			if (this.#needStoreSelectorInput())
			{
				this.storeSelector.renderTo(storeWrapper);

				if (this.isReserveBlocked())
				{
					this.#applyStoreSelectorRestrictionTweaks();
				}
				else if (!this.isInventoryManagementToolEnabled())
				{
					this.#applyStoreSelectorToolAvailabilityTweaks();
				}
			}
		}
	}

	#initStoreAvailablePopup()
	{
		const storeAvaiableNode = this.#getNodeChildByDataName('STORE_AVAILABLE');
		if (!storeAvaiableNode)
		{
			return;
		}

		this.storeAvailablePopup = new StoreAvailablePopup({
			rowId: this.id,
			model: this.getModel(),
			node: storeAvaiableNode,
			inventoryManagementMode: this.getInventoryManagementMode(),
		});
	}

	#applyStoreSelectorRestrictionTweaks()
	{
		const storeSearchInput = this.storeSelector.searchInput;
		if (!storeSearchInput || !storeSearchInput.getNameInput())
		{
			return;
		}

		storeSearchInput.toggleIcon(this.storeSelector.searchInput.getSearchIcon(), 'none');
		storeSearchInput.getNameInput().disabled = true;
		Dom.addClass(storeSearchInput.getNameInput(), 'crm-entity-product-list-locked-field');
		if (this.storeSelector.getWrapper())
		{
			Dom.addClass(this.storeSelector.getWrapper(), 'crm-entity-product-list-locked-field-wrapper');
			Event.bind(this.storeSelector.getWrapper(), 'click', () => {
				this.editor.openIntegrationLimitSlider();
			});
		}
	}

	#applyStoreSelectorToolAvailabilityTweaks()
	{
		const storeSearchInput = this.storeSelector.searchInput;
		if (!storeSearchInput || !storeSearchInput.getNameInput())
		{
			return;
		}

		storeSearchInput.toggleIcon(this.storeSelector.searchInput.getSearchIcon(), 'none');
		storeSearchInput.getNameInput().disabled = true;
		Dom.addClass(storeSearchInput.getNameInput(), 'crm-entity-product-list-locked-field');
		if (this.storeSelector.getWrapper())
		{
			Dom.addClass(this.storeSelector.getWrapper(), 'crm-entity-product-list-locked-field-wrapper');
			Event.bind(this.storeSelector.getWrapper(), 'click', () => {
				this.editor.openInventoryManagementToolDisabledSlider();
			});
		}
	}

	#initReservedControl()
	{
		const storeWrapper = this.#getNodeChildByDataName('RESERVE_INFO');
		if (storeWrapper && this.#getAllowedStores().length)
		{
			this.reserveControl = new ReserveControl({
				row: this,
				isReserveEqualProductQuantity: this.#isReserveEqualProductQuantity(),
				defaultDateReservation: this.editor.getSettingValue('defaultDateReservation'),
				isInventoryManagementToolEnabled: this.isInventoryManagementToolEnabled(),
				inventoryManagementMode: this.getInventoryManagementMode(),
				isBlocked: this.isReserveBlocked(),
				measureName: this.getMeasureName(),
			});

			EventEmitter.subscribe(
				this.reserveControl,
				'onNodeClick',
				() => {
					if (this.isReserveBlocked())
					{
						this.editor.openIntegrationLimitSlider();
					}
					else if (!this.isInventoryManagementToolEnabled())
					{
						this.editor.openInventoryManagementToolDisabledSlider();
					}
				}
			);

			if (this.isRestrictedStoreInfo())
			{
				this.reserveControl.disable();
			}

			this.layoutReserveControl();
		}

		const quantityInput = this.getNode().querySelector('div[data-name="QUANTITY"] input');
		if (quantityInput)
		{
			Event.bind(
				quantityInput,
				'change',
				(event) => {
					const isReserveEqualProductQuantity =
						this.#isReserveEqualProductQuantity()
						&& this.reserveControl?.isReserveEqualProductQuantity
					;
					if (isReserveEqualProductQuantity)
					{
						this.setReserveQuantity(this.getField('QUANTITY'));
						return;
					}

					const value = Text.toNumber(event.target.value);
					const errorNotifyId = 'quantityReservedCountError';
					let notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);
					if (value < this.getField('INPUT_RESERVE_QUANTITY'))
					{
						if (!notify)
						{
							const notificationOptions = {
								id: errorNotifyId,
								closeButton: true,
								autoHideDelay: 3000,
								content: Tag.render`<div>${Loc.getMessage('CRM_ENTITY_PL_IS_LESS_QUANTITY_THEN_RESERVED')}</div>`,
							};

							notify = BX.UI.Notification.Center.notify(notificationOptions);
						}

						this.setReserveQuantity(this.getField('QUANTITY'));
						notify.show();
					}
				}
			);
		}
	}

	#onStoreFieldChange(event)
	{
		const data = event.getData();
		data.fields.forEach((item) => {
			this.updateField(item.NAME, item.VALUE);
		});

		this.initHandlersForSelectors();
	}

	#onStoreFieldClear()
	{
		this.initHandlersForSelectors();
	}

	layoutReserveControl()
	{
		const storeWrapper = this.#getNodeChildByDataName('RESERVE_INFO');
		if (storeWrapper && this.reserveControl)
		{
			storeWrapper.innerHTML = '';
			this.reserveControl.clearCache();

			if (this.#needReserveControlInput())
			{
				if (this.isRestrictedStoreInfo())
				{
					storeWrapper.innerHTML = this.reserveControl.getReservedQuantity() + ' ' + Text.encode(this.getMeasureName());
					return;
				}

				this.reserveControl.renderTo(storeWrapper);
			}
		}
	}

	clearReserveControl()
	{
		const storeWrapper = this.#getNodeChildByDataName('RESERVE_INFO');
		if (storeWrapper && this.reserveControl)
		{
			storeWrapper.innerHTML = '';
			this.reserveControl.clearCache();
		}
	}

	setRowNumber(number)
	{
		this.getNode().querySelectorAll('.main-grid-row-number').forEach(node => {
			node.textContent = number + '.';
		});
	}

	getFields(fields: Array = [])
	{
		let result;

		if (!Type.isArrayFilled(fields))
		{
			result = Runtime.clone(this.fields);
		}
		else
		{
			result = {};

			for (const fieldName of fields)
			{
				result[fieldName] = this.getField(fieldName);
			}
		}

		if ('PRODUCT_NAME' in result)
		{
			const fixedProductName = this.getField('FIXED_PRODUCT_NAME', '');

			if (Type.isStringFilled(fixedProductName))
			{
				result['PRODUCT_NAME'] = fixedProductName;
			}
		}

		return result;
	}

	getCatalogFields(): Object
	{
		const fields = this.getFields(['CURRENCY', 'QUANTITY', 'MEASURE_CODE']);

		fields['PRICE'] = this.getBasePrice();
		fields['VAT_INCLUDED'] = this.getTaxIncluded();
		fields['VAT_ID'] = this.getTaxId();

		return fields;
	}

	getCalculateFields(): FieldScheme
	{
		return {
			'PRICE': this.getPrice(),
			'BASE_PRICE': this.getBasePrice(),
			'PRICE_EXCLUSIVE': this.getPriceExclusive(),
			'PRICE_NETTO': this.getPriceNetto(),
			'PRICE_BRUTTO': this.getPriceBrutto(),
			'QUANTITY': this.getQuantity(),
			'DISCOUNT_TYPE_ID': this.getDiscountType(),
			'DISCOUNT_RATE': this.getDiscountRate(),
			'DISCOUNT_SUM': this.getDiscountSum(),
			'DISCOUNT_ROW': this.getDiscountRow(),
			'TAX_INCLUDED': this.getTaxIncluded(),
			'TAX_RATE': this.getTaxRate()
		};
	}

	setFields(fields: Object): void
	{
		for (const name in fields)
		{
			if (fields.hasOwnProperty(name))
			{
				this.setField(name, fields[name]);
				this.getModel().setField(name, fields[name]);
			}
		}
	}

	getField(name: string, defaultValue)
	{
		return this.fields.hasOwnProperty(name) ? this.fields[name] : defaultValue;
	}

	setField(name: string, value, changeModel: boolean = true): void
	{
		this.fields[name] = value;

		if (changeModel)
		{
			this.getModel().setField(name, value);
		}
	}

	getUiFieldId(field): string
	{
		return this.getId() + '_' + field;
	}

	getBasePrice(): number
	{
		return this.getField('BASE_PRICE', 0);
	}

	isPriceNetto(): boolean
	{
		return this.getEditor().isTaxAllowed() && !this.isTaxIncluded();
	}

	getPrice(): number
	{
		return this.getField('PRICE', 0);
	}

	getPriceExclusive(): number
	{
		return this.getField('PRICE_EXCLUSIVE', 0);
	}

	getPriceNetto(): number
	{
		return this.getField('PRICE_NETTO', 0);
	}

	getPriceBrutto(): number
	{
		return this.getField('PRICE_BRUTTO', 0);
	}

	getQuantity(): number
	{
		return this.getField('QUANTITY', 1);
	}

	getDiscountType(): DiscountTypes
	{
		return this.getField('DISCOUNT_TYPE_ID', DiscountType.UNDEFINED);
	}

	isDiscountUndefined(): boolean
	{
		return this.getDiscountType() === DiscountType.UNDEFINED;
	}

	isDiscountPercentage(): boolean
	{
		return this.getDiscountType() === DiscountType.PERCENTAGE;
	}

	isDiscountMonetary(): boolean
	{
		return this.getDiscountType() === DiscountType.MONETARY;
	}

	isDiscountHandmade(): boolean
	{
		return this.isDiscountPercentage() || this.isDiscountMonetary();
	}

	getDiscountRate(): number
	{
		return this.getField('DISCOUNT_RATE', 0);
	}

	getDiscountSum(): number
	{
		return this.getField('DISCOUNT_SUM', 0);
	}

	getDiscountRow(): number
	{
		return this.getField('DISCOUNT_ROW', 0);
	}

	isEmptyDiscount(): boolean
	{
		if (this.isDiscountPercentage())
		{
			return this.getDiscountRate() === 0;
		}
		else if (this.isDiscountMonetary())
		{
			return this.getDiscountSum() === 0;
		}
		else if (this.isDiscountUndefined())
		{
			return true;
		}

		return false;
	}

	isEmptyRow(): boolean
	{
		return (
			!Type.isStringFilled(this.getField('NAME', '').trim())
			&& this.model.isEmpty()
			&& this.getBasePrice() <= 0
		)
	}

	getTaxIncluded(): 'Y' | 'N'
	{
		return this.getField('TAX_INCLUDED', 'N');
	}

	isTaxIncluded(): boolean
	{
		return this.getTaxIncluded() === 'Y';
	}

	getTaxRate(): number
	{
		return this.getField('TAX_RATE', 0);
	}

	getTaxSum(): number
	{
		return this.isTaxIncluded()
			? this.getPrice() * this.getQuantity() * (1 - 1 / (1 + this.getTaxRate() / 100))
			: this.getPriceExclusive() * this.getQuantity() * this.getTaxRate() / 100;
	}

	getTaxNode(): ?HTMLSelectElement
	{
		return this.getNode().querySelector('select[data-field-code="TAX_RATE"]');
	}

	getTaxId(): number
	{
		const taxNode = this.getTaxNode();

		if (Type.isDomNode(taxNode) && taxNode.options[taxNode.selectedIndex])
		{
			return Text.toNumber(taxNode.options[taxNode.selectedIndex].getAttribute('data-tax-id'));
		}

		return 0;
	}

	updateFieldByEvent(fieldCode: string, event: UIEvent): void
	{
		const target = event.target;
		const value = target.type === 'checkbox' ? target.checked : target.value;
		const mode = (event.type === 'input' || event.type === 'change') ? MODE_EDIT : MODE_SET;

		this.updateField(fieldCode, value, mode);
	}

	updateField(fieldCode: string, value, mode = MODE_SET): void
	{
		this.resetExternalActions();
		this.updateFieldValue(fieldCode, value, mode);
		this.executeExternalActions();
	}

	updateFieldValue(code: string, value, mode = MODE_SET): void
	{
		switch (code)
		{
			case 'ID':
			case 'OFFER_ID':
				this.changeProductId(value);
				break;

			case 'QUANTITY':
				this.changeQuantity(value, mode);
				break;

			case 'MEASURE_CODE':
				this.changeMeasureCode(value, mode);
				break;

			case 'DISCOUNT':
			case 'DISCOUNT_PRICE':
				this.changeDiscount(value, mode);
				break;

			case 'DISCOUNT_TYPE_ID':
				this.changeDiscountType(value);
				break;

			case 'DISCOUNT_ROW':
				this.changeRowDiscount(value, mode);
				break;

			case 'VAT_ID':
			case 'TAX_ID':
				this.changeTaxId(value);
				break;

			case 'TAX_RATE':
				this.changeTaxRate(value);
				break;

			case 'VAT_INCLUDED':
			case 'TAX_INCLUDED':
				this.changeTaxIncluded(value);
				break;

			case 'SUM':
				this.changeRowSum(value, mode);
				break;

			case 'NAME':
			case 'PRODUCT_NAME':
			case 'MAIN_INFO':
				this.changeProductName(value);
				break;

			case 'SORT':
				this.changeSort(value, mode);
				break;

			case 'STORE_ID':
				this.changeStore(value);
				break;
			case 'STORE_TITLE':
				this.changeStoreName(value);
				break;
			case 'INPUT_RESERVE_QUANTITY':
				this.changeReserveQuantity(value);
				break;
			case 'DATE_RESERVE_END':
				this.changeDateReserveEnd(value);
				break;
			case 'PRICE':
			case 'BASE_PRICE':
				this.changeBasePrice(value, mode);
				break;
			case 'DEDUCTED_QUANTITY':
				this.setDeductedQuantity(value);
				break;
			case 'ROW_RESERVED':
				this.setRowReserved(value);
				break;
			case 'TYPE':
				this.setType(value);
				break;
			case 'SKU_TREE':
			case 'DETAIL_URL':
			case 'IMAGE_INFO':
			case 'COMMON_STORE_AMOUNT':
				this.setField(code, value);
				break;
		}
	}

	updateFieldByName(field, value)
	{
		switch (field)
		{
			case 'TAX_INCLUDED':
				this.setTaxIncluded(value);
				break;
		}
	}

	handleCopyAction(event, menuItem)
	{
		this.getEditor()?.copyRow(this);
		const menu = menuItem.getMenuWindow();
		if (menu)
		{
			menu.destroy();
		}
	}

	handleDeleteAction(event, menuItem)
	{
		this.getEditor()?.deleteRow(this.getField('ID'));
		const menu = menuItem.getMenuWindow();
		if (menu)
		{
			menu.destroy();
		}
	}

	changeProductId(value)
	{
		const preparedValue = this.parseInt(value);

		this.setProductId(preparedValue);
	}

	changeQuantity(value, mode = MODE_SET)
	{
		const preparedValue = this.parseFloat(value, this.getQuantityPrecision());
		this.setQuantity(preparedValue, mode);
	}

	changeMeasureCode(value: string, mode = MODE_SET): void
	{
		this
			.getEditor()
			.getMeasures()
			.filter((item) => item.CODE === value)
			.forEach((item) => this.setMeasure(item, mode))
		;
	}

	changeDiscount(value, mode = MODE_SET)
	{
		let preparedValue;

		if (this.isDiscountPercentage())
		{
			preparedValue = this.parseFloat(value, this.getCommonPrecision());

		}
		else
		{
			preparedValue = this
				.parseFloat(value, this.getPricePrecision())
				.toFixed(this.getPricePrecision())
			;
		}

		this.setDiscount(preparedValue, mode);
	}

	changeDiscountType(value)
	{
		const preparedValue = this.parseInt(value, DiscountType.UNDEFINED);

		this.setDiscountType(preparedValue);
	}

	changeRowDiscount(value, mode = MODE_SET)
	{
		const preparedValue = this.parseFloat(value, this.getPricePrecision());

		this.setRowDiscount(preparedValue, mode);
	}

	changeTaxId(value: number): void
	{
		const taxList = this.getEditor().getTaxList();
		if (Type.isArrayFilled(taxList))
		{
			let taxRate = taxList.find((item) => parseInt(item.ID) === parseInt(value));
			if (!taxRate)
			{
				taxRate = taxList.find((item) => Type.isNil(item.VALUE));
			}

			if (taxRate)
			{
				this.changeTaxRate(taxRate.VALUE);
			}
		}
	}

	changeTaxRate(value: number | null): void
	{
		const preparedValue =
			Type.isNil(value) || value === ''
				? null
				: this.parseFloat(value, this.getCommonPrecision())
		;

		this.setTaxRate(preparedValue);
	}

	changeTaxIncluded(value)
	{
		if (Type.isBoolean(value))
		{
			value = value ? 'Y' : 'N';
		}

		this.setTaxIncluded(value);
	}

	changeRowSum(value, mode = MODE_SET)
	{
		const preparedValue = this.parseFloat(value, this.getPricePrecision());

		this.setRowSum(preparedValue, mode);
	}

	changeProductName(value)
	{
		const preparedValue = value.toString();
		const isChangedValue = this.getField('PRODUCT_NAME') !== preparedValue;

		if (isChangedValue)
		{
			this.setField('PRODUCT_NAME', preparedValue);
			this.setField('NAME', preparedValue);
			this.addActionProductChange();
		}
	}

	changeSort(value, mode = MODE_SET)
	{
		const preparedValue = this.parseInt(value);

		if (mode === MODE_SET)
		{
			this.setField('SORT', preparedValue);
		}

		const isChangedValue = this.getField('SORT') !== preparedValue;

		if (isChangedValue)
		{
			this.addActionProductChange();
		}
	}

	changeStore(value: number): void
	{
		if (this.isReserveBlocked())
		{
			return;
		}

		const preparedValue = Text.toNumber(value);
		if (this.getField('STORE_ID') === preparedValue)
		{
			return;
		}

		this.setField('STORE_ID', preparedValue);
		this.setField('STORE_AVAILABLE', this.model.getStoreCollection().getStoreAvailableAmount(value));

		this.updateUiStoreAmountData();
		this.layoutReserveControl();
		this.addActionProductChange();
		this.initHandlersForSelectors();
	}

	#onChangeStoreData()
	{
		let storeId = this.getField('STORE_ID');

		if (!this.isReserveBlocked() && this.isNewRow() && this.storeSelector)
		{
			const currentAmount = this.getModel().getStoreCollection().getStoreAmount(storeId);
			if (currentAmount <= 0 && this.getModel().isChanged())
			{
				const maxStore = this.getModel().getStoreCollection().getMaxFilledStore();
				if (maxStore.AMOUNT > currentAmount)
				{
					this.storeSelector.onStoreSelect(maxStore.STORE_ID, Text.decode(maxStore.STORE_TITLE));
				}
				else if (Type.isNil(storeId))
				{
					storeId = +this.storeSelector.getStoreId();
					if (storeId > 0)
					{
						this.changeStore(storeId);
					}
				}
			}
		}

		this.setField('STORE_AVAILABLE', this.model.getStoreCollection().getStoreAvailableAmount(storeId));

		this.updateUiStoreAmountData();
	}

	updateUiStoreAmountData()
	{
		const availableWrapper = this.#getNodeChildByDataName('STORE_AVAILABLE');
		if (!Type.isDomNode(availableWrapper))
		{
			return;
		}

		const storeId = this.getField('STORE_ID');
		if (!storeId)
		{
			return;
		}

		const available = this.model.getStoreCollection().getStoreAvailableAmount(storeId);
		const amount = Text.toNumber(available)

		let amountWithMeasure = '';

		if (!this.getModel().isCatalogExisted() || this.isRestrictedStoreInfo() || this.getModel().isService())
		{
			return;
		}

		amountWithMeasure = amount + ' ' + this.getMeasureName();
		availableWrapper.innerHTML =
			amount > 0
				? amountWithMeasure
				: `<span class="store-available-popup-link--danger">${amountWithMeasure}</span>`
		;
	}

	updatePropertyFields()
	{
		const productProps = this.model.getField('PRODUCT_PROPERTIES');

		for (const property in productProps)
		{
			const availableWrapper = this.#getNodeChildByDataName(property);
			if (availableWrapper)
			{
				const value = this.model.getField('PRODUCT_PROPERTIES')[property] ?? '';
				availableWrapper.innerHTML = value;
			}
		}
	}

	clearPropertyFields()
	{
		const propNodes = this.#getNodesChild();
		propNodes.forEach((property) => {
			property.innerHTML = '';
		})
	}

	setRowReserved(value)
	{
		this.setField('ROW_RESERVED', value)
		const reserveWrapper = this.#getNodeChildByDataName('ROW_RESERVED');
		if (!Type.isDomNode(reserveWrapper))
		{
			return;
		}

		if (!this.getModel().isCatalogExisted() || this.getModel().isService())
		{
			reserveWrapper.innerHTML = '';
			return;
		}

		reserveWrapper.innerHTML = Text.toNumber(this.getField('ROW_RESERVED')) + ' ' + this.getMeasureName();
	}

	setDeductedQuantity(value)
	{
		this.setField('DEDUCTED_QUANTITY', value);
		const deductedWrapper = this.#getNodeChildByDataName('DEDUCTED_QUANTITY');
		if (!Type.isDomNode(deductedWrapper))
		{
			return;
		}

		if (!this.getModel().isCatalogExisted() || this.getModel().isService())
		{
			deductedWrapper.innerHTML = '';
			return;
		}

		deductedWrapper.innerHTML = Text.toNumber(this.getField('DEDUCTED_QUANTITY')) + ' ' + this.getMeasureName();
	}

	changeStoreName(value: number)
	{
		const preparedValue = value.toString();
		this.setField('STORE_TITLE', preparedValue);
		this.addActionProductChange();
	}

	changeDateReserveEnd(value: string)
	{
		const preparedValue = Type.isNil(value) ? '' : value.toString();
		this.setField('DATE_RESERVE_END', preparedValue);
		this.addActionProductChange();
	}

	changeReserveQuantity(value: number)
	{
		const preparedValue = Text.toNumber(value);
		const reserveDifference = preparedValue - this.getField('INPUT_RESERVE_QUANTITY');

		if (reserveDifference === 0 || isNaN(reserveDifference))
		{
			return;
		}
		const newReserve = this.getField('ROW_RESERVED') + reserveDifference;

		this.setField('ROW_RESERVED', newReserve);
		this.setField('RESERVE_QUANTITY', Math.max(newReserve, 0));
		this.setField('INPUT_RESERVE_QUANTITY', preparedValue);

		this.addActionProductChange();
	}

	resetReserveFields()
	{
		this.setField('ROW_RESERVED', null);
		this.setField('RESERVE_QUANTITY', null);
		this.setField('INPUT_RESERVE_QUANTITY', null);
	}

	refreshFieldsLayout(exceptFields: Array<string> = []): void
	{
		for (const field in this.fields)
		{
			if (this.fields.hasOwnProperty(field) && !exceptFields.includes(field))
			{
				this.updateUiField(field, this.fields[field]);
			}
		}
	}

	getCalculator(): ProductCalculator
	{
		return this.getModel()
			.getCalculator()
			.setFields(this.getCalculateFields())
			.setSettings(this.getEditor().getSettings())
		;
	}

	setModel(fields: {} = {}, settings: Settings = {}): void
	{
		const selectorId = settings.selectorId;
		if (selectorId)
		{
			const model = ProductModel.getById(selectorId);
			if (model)
			{
				this.model = model;
			}
		}

		if (!this.model)
		{
			this.model = new ProductModel({
				id: selectorId,
				currency: this.getEditor().getCurrencyId(),
				iblockId: fields['IBLOCK_ID'],
				basePriceId: fields['BASE_PRICE_ID'],
				isSimpleModel: Text.toInteger(fields['PRODUCT_ID']) <= 0 && Type.isStringFilled(fields['NAME']),
				skuTree: Type.isStringFilled(fields['SKU_TREE']) ? JSON.parse(fields['SKU_TREE']) : null,
				storeMap: fields['STORE_MAP'] ?? {},
				fields,
			});

			if (!Type.isNil(fields['DETAIL_URL']))
			{
				this.model.setDetailPath(fields['DETAIL_URL']);
			}
		}

		// fill after change setting show pictures.
		const imageInfo = Type.isStringFilled(fields['IMAGE_INFO']) ? JSON.parse(fields['IMAGE_INFO']) : null
		if (Type.isObject(imageInfo))
		{
			this.model.getImageCollection().setPreview(imageInfo['preview']);
			this.model.getImageCollection().setEditInput(imageInfo['input']);
			this.model.getImageCollection().setMorePhotoValues(imageInfo['values']);
		}

		if (this.#isReserveEqualProductQuantity())
		{
			if (!this.getModel().getField('DATE_RESERVE_END'))
			{
				this.setField('DATE_RESERVE_END', this.editor.getSettingValue('defaultDateReservation'));
			}
		}

		EventEmitter.subscribe(
			this.model,
			'onErrorsChange',
			this.handleProductErrorsChange,
		);

		EventEmitter.subscribe(
			this.model,
			'onChangeStoreData',
			this.handleChangeStoreData,
		);
	}

	getModel(): ?ProductModel
	{
		return this.model;
	}

	#onProductErrorsChange()
	{
		this.getEditor().handleProductErrorsChange();
	}

	setProductId(value)
	{
		const isChangedValue = this.getField('PRODUCT_ID') !== value;

		if (isChangedValue)
		{
			this.getModel().setOption('isSimpleModel', value <= 0 && Type.isStringFilled(this.getField('NAME')));
			this.setField('PRODUCT_ID', value, false);
			this.setField('OFFER_ID', value, false);
			this.storeSelector?.setProductId(value);

			this.addActionProductChange();
			this.addActionUpdateTotal();

			if (
				this.reserveControl
				&& this.#isReserveEqualProductQuantity()
				&& this.#needReserveControlInput()
			)
			{
				if (!this.getModel().getField('DATE_RESERVE_END'))
				{
					this.setField('DATE_RESERVE_END', this.editor.getSettingValue('defaultDateReservation'));
				}

				this.resetReserveFields();

				this.onAfterExecuteExternalActions = () => {

					this.reserveControl.changeInputValue(this.getField('QUANTITY'));
				};
			}
		}
	}

	changeBasePrice(value, mode = MODE_SET)
	{
		if (mode === MODE_EDIT && !this.#isEditableCatalogPrice())
		{
			value = this.getField('BASE_PRICE');
			this.updateUiInputField('PRICE', value.toFixed(this.getPricePrecision()));

			return;
		}

		const originalPrice = value;
		// price can't be less than zero
		value = Math.max(value, 0);

		if (mode === MODE_SET)
		{
			this.updateUiInputField('PRICE', value.toFixed(this.getPricePrecision()));
		}

		const isChangedValue = this.getBasePrice() !== value;
		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculateBasePrice(value);
			this.setFields(calculatedFields);

			const exceptFieldNames = (mode === MODE_EDIT) ? ['BASE_PRICE', 'PRICE'] : [];
			this.refreshFieldsLayout(exceptFieldNames);

			this.addActionProductChange();
			this.addActionUpdateTotal();
		}

		this.#togglePriceHintPopup(originalPrice < 0 && originalPrice !== value);
	}

	#shouldShowSmallPriceHint(): boolean
	{
		return (
			Text.toNumber(this.getField('PRICE')) > 0
			&& Text.toNumber(this.getField('PRICE')) < 1
			&& this.isDiscountPercentage()
			&& (
				Text.toNumber(this.getField('DISCOUNT_SUM')) > 0
				|| Text.toNumber(this.getField('DISCOUNT_RATE')) > 0
				|| Text.toNumber(this.getField('DISCOUNT_ROW')) > 0
			)
		);
	}

	#togglePriceHintPopup(showNegative: boolean = false): void
	{
		if (this.#shouldShowSmallPriceHint())
		{
			this.getHintPopup()
				.load(
					this.getInputByFieldName('PRICE'),
					Loc.getMessage('CRM_ENTITY_PL_SMALL_PRICE_NOTICE')
				)
				.show()
			;
		}
		else if (showNegative)
		{
			this.getHintPopup()
				.load(
					this.getInputByFieldName('PRICE'),
					Loc.getMessage('CRM_ENTITY_PL_NEGATIVE_PRICE_NOTICE')
				)
				.show()
			;
		}
		else
		{
			this.getHintPopup().close();
		}
	}

	setQuantity(value, mode = MODE_SET)
	{
		if (mode === MODE_SET)
		{
			this.updateUiInputField('QUANTITY', value);
		}

		const isChangedValue = this.getField('QUANTITY') !== value;
		if (isChangedValue)
		{
			const errorNotifyId = 'quantityReservedCountError';
			const notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);
			if (notify)
			{
				notify.close();
			}

			const calculatedFields = this.getCalculator().calculateQuantity(value);
			this.setFields(calculatedFields);
			this.refreshFieldsLayout(['QUANTITY']);

			this.addActionProductChange();
			this.addActionUpdateTotal();
		}
	}

	setReserveQuantity(value)
	{
		const node = this.#getNodeChildByDataName('RESERVE_INFO');
		const input = node?.querySelector('input[name="INPUT_RESERVE_QUANTITY"]');
		if (Type.isElementNode(input))
		{
			input.value = value;

			const view = node?.querySelector('span[data-name="VIEW_RESERVE_QUANTITY"]');
			if (view)
			{
				view.textContent = value;
			}

			this.reserveControl?.changeInputValue(value);
		}
		else
		{
			this.changeReserveQuantity(value)
		}
	}

	setMeasure(measure, mode = MODE_SET)
	{
		this.setField('MEASURE_CODE', measure.CODE);
		this.setField('MEASURE_NAME', measure.SYMBOL);

		this.updateUiMoneyField('MEASURE_CODE', measure.CODE, Text.encode(measure.SYMBOL));

		if (this.getModel().isNew())
		{
			this.getModel().save(['MEASURE_CODE']);
		}
		else if (mode === MODE_EDIT)
		{
			this.getModel().showSaveNotifier(
				'measureChanger_' + this.getId(),
				{
					title: Loc.getMessage('CATALOG_PRODUCT_MODEL_SAVING_NOTIFICATION_MEASURE_CHANGED_QUERY'),
					events: {
						onSave: () => {
							this.getModel().save(['MEASURE_CODE', 'MEASURE_NAME']);
						}
					},
				}
			);
		}

		this.addActionProductChange();
	}

	setDiscount(value, mode = MODE_SET)
	{
		if (!this.isDiscountHandmade())
		{
			return;
		}

		const fieldName = this.isDiscountPercentage() ? 'DISCOUNT_RATE' : 'DISCOUNT_SUM';
		const isChangedValue = this.getField(fieldName) !== value;
		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculateDiscount(value);
			this.setFields(calculatedFields);
			const exceptFieldNames = (mode === MODE_EDIT) ? ['DISCOUNT_RATE', 'DISCOUNT_SUM', 'DISCOUNT'] : [];
			this.refreshFieldsLayout(exceptFieldNames);

			this.addActionProductChange();
			this.addActionUpdateTotal();
		}

		this.#togglePriceHintPopup();
	}

	setDiscountType(value)
	{
		const isChangedValue = value !== DiscountType.UNDEFINED
			&& this.getField('DISCOUNT_TYPE_ID') !== value;

		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculateDiscountType(value);
			this.setFields(calculatedFields);
			this.refreshFieldsLayout();

			this.addActionProductChange();
			this.addActionUpdateTotal();
		}
	}

	setRowDiscount(value, mode = MODE_SET)
	{
		const isChangedValue = this.getField('DISCOUNT_ROW') !== value;
		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculateRowDiscount(value);
			this.setFields(calculatedFields);

			const exceptFieldNames = (mode === MODE_EDIT) ? ['DISCOUNT_ROW'] : [];
			this.refreshFieldsLayout(exceptFieldNames);

			this.addActionProductChange();
			this.addActionUpdateTotal();
		}
	}

	setTaxRate(value)
	{
		if (!this.getEditor().isTaxAllowed())
		{
			return;
		}

		const isChangedValue = this.getTaxRate() !== value;
		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculateTax(value);
			this.setFields(calculatedFields);
			this.refreshFieldsLayout();

			this.addActionProductChange();
			this.addActionUpdateTotal();
		}
	}

	setTaxIncluded(value: 'Y' | 'N', mode = MODE_SET)
	{
		if (!this.getEditor().isTaxAllowed())
		{
			return;
		}

		if (mode === MODE_SET)
		{
			this.updateUiCheckboxField('TAX_INCLUDED', value);
		}

		const isChangedValue = this.getTaxIncluded() !== value;
		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculateTaxIncluded(value);
			this.setFields(calculatedFields);
			this.refreshFieldsLayout();

			this.addActionUpdateFieldList('TAX_INCLUDED', value);
			this.addActionProductChange();
			this.addActionUpdateTotal();
		}
	}

	setRowSum(value, mode = MODE_SET)
	{
		const isChangedValue = this.getField('SUM') !== value;
		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculateRowSum(value);
			this.setFields(calculatedFields);
			const exceptFieldNames = (mode === MODE_EDIT) ? ['SUM'] : [];
			this.refreshFieldsLayout(exceptFieldNames);

			this.addActionProductChange();
			this.addActionUpdateTotal();
		}
	}

	// controls
	getInputByFieldName(fieldName: string): ?HTMLElement
	{
		const fieldId = this.getUiFieldId(fieldName);
		let item = document.getElementById(fieldId);

		if (!Type.isElementNode(item))
		{
			item = this.getNode().querySelector('[name="' + fieldId + '"]');
		}

		return item;
	}

	updateUiInputField(name, value)
	{
		const item = this.getInputByFieldName(name);

		if (Type.isElementNode(item))
		{
			item.value = value;
		}
	}

	updateUiCheckboxField(name, value)
	{
		const item = this.getInputByFieldName(name);

		if (Type.isElementNode(item))
		{
			item.checked = value === 'Y';
		}
	}

	updateUiDiscountTypeField(name: string, value: number): void
	{
		const text =
			value === DiscountType.MONETARY
				? this.getEditor().getCurrencyText()
				: '%'
		;

		this.updateUiMoneyField(name, value, text);
	}

	getMoneyFieldDropdownApi(name): ?BX.Main.dropdown
	{
		if (!Reflection.getClass('BX.Main.dropdownManager'))
		{
			return null;
		}

		return BX.Main.dropdownManager.getById(this.getId() + '_' + name + '_control');
	}

	updateMoneyFieldUiWithDropdownApi(dropdown: BX.Main.dropdown, value: number | string)
	{
		if (dropdown.getValue() === value)
		{
			return;
		}

		const item = dropdown.menu.itemsContainer.querySelector('[data-value="' + value + '"]');
		const menuItem = item && dropdown.getMenuItem(item);
		if (menuItem)
		{
			dropdown.refresh(menuItem);
			dropdown.selectItem(menuItem);
		}
	}

	updateMoneyFieldUiManually(name: string, value: number | string, text: string): void
	{
		const item = this.getInputByFieldName(name);
		if (!Type.isElementNode(item))
		{
			return;
		}

		item.dataset.value = value;

		const span = item.querySelector('span.main-dropdown-inner');
		if (!Type.isElementNode(span))
		{
			return;
		}

		span.innerHTML = text;
	}

	updateUiMoneyField(name: string, value: number | string, text: string): void
	{
		const dropdownApi = this.getMoneyFieldDropdownApi(name);
		if (dropdownApi)
		{
			this.updateMoneyFieldUiWithDropdownApi(dropdownApi, value);
		}
		else
		{
			this.updateMoneyFieldUiManually(name, value, text);
		}
	}

	updateUiMeasure(code, name)
	{
		this.updateUiMoneyField(
			'MEASURE_CODE',
			code,
			name
		);

		this.updateUiStoreAmountData();
	}

	updateUiHtmlField(name, html)
	{
		const item = this.getNode().querySelector('[data-name="' + name + '"]');

		if (Type.isElementNode(item))
		{
			item.innerHTML = html;
		}
	}

	updateUiCurrencyFields()
	{
		const currencyText = this.getEditor().getCurrencyText();
		const currencyId = '' + this.getEditor().getCurrencyId();

		const currencyFieldNames = ['PRICE_CURRENCY', 'SUM_CURRENCY', 'DISCOUNT_TYPE_ID', 'DISCOUNT_ROW_CURRENCY'];
		currencyFieldNames.forEach((name) => {
			const dropdownValues = [];
			if (name === 'DISCOUNT_TYPE_ID')
			{
				dropdownValues.push({
					NAME: '%',
					VALUE: '' + DiscountType.PERCENTAGE
				});
				dropdownValues.push({
					NAME: currencyText,
					VALUE: '' + DiscountType.MONETARY
				});
				if (this.getDiscountType() === DiscountType.MONETARY)
				{
					this.updateMoneyFieldUiManually(name, DiscountType.MONETARY, currencyText);
				}
			}
			else
			{
				dropdownValues.push({
					NAME: currencyText,
					VALUE: currencyId
				});
				this.updateUiMoneyField(name, currencyId, currencyText);
			}

			Dom.attr(this.getInputByFieldName(name), 'data-items', dropdownValues);
		});

		this.updateUiField('TAX_SUM', this.getField('TAX_SUM'));
	}

	updateUiField(field, value): void
	{
		const uiName = this.getUiFieldName(field);
		if (!uiName)
		{
			return;
		}

		const uiType = this.getUiFieldType(uiName);
		if (!uiType)
		{
			return;
		}

		if (!this.allowUpdateUiField(field))
		{
			return;
		}

		switch (uiType)
		{
			case 'input':
				if (field === 'QUANTITY')
				{
					value = this.parseFloat(value, this.getQuantityPrecision());
				}
				else if (field === 'DISCOUNT_RATE')
				{
					value = this.parseFloat(value, this.getCommonPrecision());
				}
				else if (field === 'TAX_RATE')
				{
					value =
						Type.isNil(value) || value === ''
							? ''
							: this.parseFloat(value, this.getCommonPrecision())
					;
				}
				else if (value === 0)
				{
					value = '';
				}
				else if (Type.isNumber(value))
				{
					value = this
						.parseFloat(value, this.getPricePrecision())
						.toFixed(this.getPricePrecision())
					;
				}

				this.updateUiInputField(uiName, value);
				break;

			case 'checkbox':
				this.updateUiCheckboxField(uiName, value);
				break;

			case 'discount_type_field':
				this.updateUiDiscountTypeField(uiName, value);
				break;

			case 'html':
				this.updateUiHtmlField(uiName, value);
				break;

			case 'money_html':
				value = CurrencyCore.currencyFormat(value, this.getEditor().getCurrencyId(), true);
				this.updateUiHtmlField(uiName, value);
				break;
		}
	}

	getUiFieldName(field): string
	{
		let result = null;

		switch (field)
		{
			case 'QUANTITY':
			case 'MEASURE_CODE':
			case 'DISCOUNT_ROW':
			case 'DISCOUNT_TYPE_ID':
			case 'TAX_RATE':
			case 'TAX_INCLUDED':
			case 'TAX_SUM':
			case 'SUM':
			case 'PRODUCT_NAME':
			case 'SORT':
				result = field;
				break;

			case 'BASE_PRICE':
				result = 'PRICE';
				break;

			case 'DISCOUNT_RATE':
			case 'DISCOUNT_SUM':
				result = 'DISCOUNT_PRICE';
				break;
		}

		return result;
	}

	getUiFieldType(field): string
	{
		let result = null;

		switch (field)
		{
			case 'PRICE':
			case 'QUANTITY':
			case 'TAX_RATE':
			case 'DISCOUNT_PRICE':
			case 'DISCOUNT_RATE':
			case 'DISCOUNT_SUM':
			case 'DISCOUNT_ROW':
			case 'SUM':
			case 'PRODUCT_NAME':
			case 'SORT':
				result = 'input';
				break;

			case 'DISCOUNT_TYPE_ID':
				result = 'discount_type_field';
				break;

			case 'TAX_INCLUDED':
				result = 'checkbox';
				break;

			case 'TAX_SUM':
				result = 'money_html';
				break;
		}

		return result;
	}

	allowUpdateUiField(field): boolean
	{
		let result = true;

		switch (field)
		{
			case 'PRICE_NETTO':
				result = this.isPriceNetto();
				break;

			case 'PRICE_BRUTTO':
				result = !this.isPriceNetto();
				break;

			case 'DISCOUNT_RATE':
				result = this.isDiscountPercentage();
				break;

			case 'DISCOUNT_SUM':
				result = this.isDiscountMonetary();
				break;
		}

		return result;
	}

	// proxy
	parseInt(value: number | string, defaultValue: number = 0): number
	{
		return this.getEditor().parseInt(value, defaultValue);
	}

	parseFloat(value: number | string, precision: number, defaultValue = 0): number
	{
		return this.getEditor().parseFloat(value, precision, defaultValue);
	}

	getPricePrecision(): number
	{
		return this.getEditor().getPricePrecision();
	}

	getQuantityPrecision(): number
	{
		return this.getEditor().getQuantityPrecision();
	}

	getCommonPrecision(): number
	{
		return this.getEditor().getCommonPrecision();
	}

	resetExternalActions()
	{
		this.externalActions.length = 0;
	}

	addExternalAction(action: Action)
	{
		this.externalActions.push(action);
	}

	addActionProductChange()
	{
		this.addExternalAction({
			type: this.getEditor().actions.productChange,
			id: this.getId()
		});
	}

	addActionDisableSaveButton()
	{
		this.addExternalAction({
			type: this.getEditor().actions.disableSaveButton,
			id: this.getId()
		});
	}

	addActionUpdateFieldList(field, value)
	{
		this.addExternalAction({
			type: this.getEditor().actions.updateListField,
			field,
			value
		});
	}

	addActionStateChanged()
	{
		this.addExternalAction({
			type: this.getEditor().actions.stateChanged,
			value: true
		});
	}

	addActionStateReset()
	{
		this.addExternalAction({
			type: this.getEditor().actions.stateChanged,
			value: false
		});
	}

	addActionUpdateTotal()
	{
		this.addExternalAction({
			type: this.getEditor().actions.updateTotal
		});
	}

	executeExternalActions()
	{
		if (this.externalActions.length === 0)
		{
			return;
		}

		this.getEditor().executeActions(this.externalActions);
		this.resetExternalActions();

		if (this.onAfterExecuteExternalActions)
		{
			const callback = this.onAfterExecuteExternalActions;
			this.onAfterExecuteExternalActions = null;
			callback.call();
		}
	}

	isEmpty(): boolean
	{
		return (
			!Type.isStringFilled(this.getField('PRODUCT_NAME', '').trim())
			&& this.getField('PRODUCT_ID', 0) <= 0
			&& this.getPrice() <= 0
		)
	}

	isReserveBlocked(): boolean
	{
		return this.getSettingValue('isReserveBlocked', false);
	}

	isInventoryManagementToolEnabled(): boolean
	{
		return this.getSettingValue('isInventoryManagementToolEnabled', true);
	}

	getInventoryManagementMode(): ?string
	{
		return this.getSettingValue('inventoryManagementMode', '');
	}

	isRestrictedStoreInfo(): boolean
	{
		if (!this.editor.getSettingValue('allowReservation', true))
		{
			return false;
		}

		const storeId = this.getField('STORE_ID')?.toString();
		if (Type.isNil(storeId) || storeId === '0')
		{
			return false;
		}
		else if (this.getModel().isSimple() || this.getModel().isService())
		{
			return false;
		}

		return !this.#getAllowedStores().includes(storeId);
	}

	#getAllowedStores(): Array
	{
		return this.editor.getSettingValue('allowedStores', []);
	}

	#isReserveEqualProductQuantity(): Boolean
	{
		return this.editor.getSettingValue('isReserveEqualProductQuantity', false);
	}

	getMeasureName()
	{
		const measureName =
			Type.isStringFilled(this.model.getField('MEASURE_NAME'))
				? this.model.getField('MEASURE_NAME')
				: this.editor.getDefaultMeasure()?.SYMBOL || ''
		;

		return Text.encode(measureName);
	}

	#getNodeChildByDataName(name: String): HTMLElement
	{
		return this.getNode().querySelector(`[data-name="${name}"]`);
	}

	#getNodesChild(): NodeList
	{
		return this.getNode().querySelectorAll(`span[data-name]`);
	}

	setType(value)
	{
		this.setField('TYPE', value);

		if (this.getModel().isService())
		{
			this.clearReserveControl();
		}
	}

	#needReserveControlInput(): boolean
	{
		return !this.getModel().isSimple() && !this.getModel().isService();
	}

	#needStoreSelectorInput(): boolean
	{
		return !this.getModel().isSimple() && !this.getModel().isService();
	}
}

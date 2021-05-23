import {Cache, Dom, Event, Reflection, Runtime, Text, Type} from 'main.core';
import {Editor} from './product.list.editor';
import {DiscountType, DiscountTypes, FieldScheme, ProductCalculator} from 'catalog.product-calculator';
import {CurrencyCore} from 'currency.currency-core';

type Action = {
	type: string,
	field?: string,
	value?: string,
};

type Settings = {}

const MODE_EDIT = 'EDIT';
const MODE_SET = 'SET';

export class Row
{
	id: ?string;
	settings: Object;
	editor: ?Editor
	fields: Object = {};
	externalActions: Array<Action> = [];
	cache = new Cache.MemoryCache();

	constructor(id: string, fields: Object, settings: Settings, editor: Editor): void
	{
		this.setId(id);
		this.setFields(fields);
		this.setSettings(settings);
		this.setEditor(editor);

		requestAnimationFrame(this.initHandlers.bind(this));
	}

	getNode(): ?HTMLElement
	{
		return this.cache.remember('node', () => {
			const rowId = this.getField('ID', 0);

			return this.getEditorContainer().querySelector('[data-id="' + rowId + '"]');
		});
	}

	getId(): string
	{
		return this.id;
	}

	setId(id: string): void
	{
		this.id = id;
	}

	getSettings()
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

	initHandlersForProductSelector()
	{
		const editor = this.getEditor();

		this.getNode().querySelectorAll('[data-name="MAIN_INFO"] input[type="text"]').forEach(node => {
			Event.bind(node, 'input', editor.changeProductFieldHandler);
			Event.bind(node, 'change', editor.changeProductFieldHandler);
			// disable drag-n-drop events for select fields
			Event.bind(node, 'mousedown', (event) => event.stopPropagation());
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

			for (let fieldName of fields)
			{
				result[fieldName] = this.getField(fieldName);
			}
		}

		if ('PRODUCT_NAME' in result)
		{
			let fixedProductName = this.getField('FIXED_PRODUCT_NAME', '');

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
		for (let name in fields)
		{
			if (fields.hasOwnProperty(name))
			{
				this.setField(name, fields[name]);
			}
		}
	}

	getField(name: string, defaultValue)
	{
		return this.fields.hasOwnProperty(name) ? this.fields[name] : defaultValue;
	}

	setField(name: string, value): void
	{
		this.fields[name] = value;
	}

	getUiFieldId(field): string
	{
		return this.getId() + '_' + field;
	}

	getBasePrice(): number
	{
		return this.isPriceNetto() ? this.getPriceNetto() : this.getPriceBrutto();
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
		const mode = event.type === 'input' ? MODE_EDIT : MODE_SET;

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
				this.changeProductId(value);
				break;

			case 'PRICE':
				this.changePrice(value, mode);
				break;

			case 'QUANTITY':
				this.changeQuantity(value, mode);
				break;

			case 'MEASURE_CODE':
				this.changeMeasureCode(value);
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

	changeProductId(value)
	{
		const preparedValue = this.parseInt(value);

		this.setProductId(preparedValue);
	}

	changePrice(value, mode = MODE_SET)
	{
		const preparedValue = this.parseFloat(value, this.getPricePrecision());
		this.setPrice(preparedValue, mode);
	}

	changeQuantity(value, mode = MODE_SET)
	{
		const preparedValue = this.parseFloat(value, this.getQuantityPrecision());
		this.setQuantity(preparedValue, mode);
	}

	changeMeasureCode(value: string): void
	{
		this
			.getEditor()
			.getMeasures()
			.filter((item) => item.CODE === value)
			.forEach((item) => this.setMeasure(item))
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
		const taxNode = this.getTaxNode();
		const taxOptionNode = this.getNode().querySelector(`option[data-tax-id="${value}"]`);

		if (Type.isDomNode(taxNode) && Type.isDomNode(taxOptionNode))
		{
			taxNode.value = taxOptionNode.value;
			this.changeTaxRate(this.parseFloat(taxOptionNode.value));
		}
	}

	changeTaxRate(value: number): void
	{
		const preparedValue = this.parseFloat(value, this.getCommonPrecision());

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

	refreshFieldsLayout(exceptFields: Array<string> = []): void
	{
		for (let field in this.fields)
		{
			if (this.fields.hasOwnProperty(field) && !exceptFields.includes(field))
			{
				this.updateUiField(field, this.fields[field]);
			}
		}
	}

	getCalculator(): ProductCalculator
	{
		/** @var {ProductCalculator} */
		const calculator = this.cache.remember('calculator', () => new ProductCalculator());

		return calculator
			.setFields(this.getCalculateFields())
			.setSettings(this.getEditor().getSettings())
			;
	}

	setProductId(value)
	{
		const isChangedValue = this.getField('PRODUCT_ID') !== value;

		if (isChangedValue)
		{
			this.setField('PRODUCT_ID', value);
			this.setField('OFFER_ID', value);

			this.addActionProductChange();
			this.addActionUpdateTotal();
		}
	}

	setPrice(value, mode = MODE_SET)
	{
		if (mode === MODE_SET)
		{
			this.updateUiInputField('PRICE', value.toFixed(this.getPricePrecision()));
		}

		const isChangedValue = this.getBasePrice() !== value;
		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculatePrice(value);
			this.setFields(calculatedFields);
			this.refreshFieldsLayout(['PRICE_NETTO', 'PRICE_BRUTTO']);

			this.addActionProductChange();
			this.addActionUpdateTotal();
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
			const calculatedFields = this.getCalculator().calculateQuantity(value);
			this.setFields(calculatedFields);
			this.refreshFieldsLayout(['QUANTITY']);

			this.addActionProductChange();
			this.addActionUpdateTotal();
		}
	}

	setMeasure(measure)
	{
		this.setField('MEASURE_CODE', measure.CODE);
		this.setField('MEASURE_NAME', measure.SYMBOL);

		this.updateUiMoneyField('MEASURE_CODE', measure.CODE, measure.SYMBOL);
		this.addActionProductChange();
	}

	setDiscount(value, mode = MODE_SET)
	{
		if (!this.isDiscountHandmade())
		{
			return;
		}

		if (mode === MODE_SET)
		{
			this.updateUiInputField('DISCOUNT_PRICE', value);
		}

		const fieldName = this.isDiscountPercentage() ? 'DISCOUNT_RATE' : 'DISCOUNT_SUM';
		const isChangedValue = this.getField(fieldName) !== value;
		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculateDiscount(value);
			this.setFields(calculatedFields);
			this.refreshFieldsLayout(['DISCOUNT_RATE', 'DISCOUNT_SUM', 'DISCOUNT']);

			this.addActionProductChange();
			this.addActionUpdateTotal();
		}
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
		if (mode === MODE_SET)
		{
			this.updateUiInputField('DISCOUNT_ROW', value.toFixed(this.getPricePrecision()));
		}

		const isChangedValue = this.getField('DISCOUNT_ROW') !== value;
		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculateRowDiscount(value);
			this.setFields(calculatedFields);
			this.refreshFieldsLayout(['DISCOUNT_ROW']);

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
		if (mode === MODE_SET)
		{
			this.updateUiInputField('SUM', value.toFixed(this.getPricePrecision()));
		}

		const isChangedValue = this.getField('SUM') !== value;
		if (isChangedValue)
		{
			const calculatedFields = this.getCalculator().calculateRowSum(value);
			this.setFields(calculatedFields);
			this.refreshFieldsLayout(['SUM']);

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
		let text = value === DiscountType.MONETARY ? this.getEditor().getCurrencyText() : '%';
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
		if (dropdown.getValue() == value)
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

	updateUiHtmlField(name, html)
	{
		const item = this.getInputByFieldName(name);

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

		const uiType = this.getUiFieldType(field);
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

	getUiFieldName(field)
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

			case 'PRICE_NETTO':
			case 'PRICE_BRUTTO':
				result = 'PRICE';
				break;

			case 'DISCOUNT_RATE':
			case 'DISCOUNT_SUM':
				result = 'DISCOUNT_PRICE';
				break;
		}

		return result;
	}

	getUiFieldType(field)
	{
		let result = null;

		switch (field)
		{
			case 'PRICE_NETTO':
			case 'PRICE_BRUTTO':
			case 'QUANTITY':
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

			case 'TAX_RATE':
				result = 'list';
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

	allowUpdateUiField(field)
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
	parseInt(value: number | string, defaultValue: number = 0)
	{
		return this.getEditor().parseInt(value, defaultValue);
	}

	parseFloat(value: number | string, precision: number, defaultValue = 0)
	{
		return this.getEditor().parseFloat(value, precision, defaultValue);
	}

	getPricePrecision()
	{
		return this.getEditor().getPricePrecision();
	}

	getQuantityPrecision()
	{
		return this.getEditor().getQuantityPrecision();
	}

	getCommonPrecision()
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
	}
}
import * as Item from './item';
import * as Component from './components/field';
import * as Messages from "../../form/messages";
import * as Design from "../../form/design";
import { getStoredFieldValue, getStoredFieldValueByFieldName } from "../storage"
import Event from "../../util/event";

type Entity = {
	id: number;
	name: string;
	fieldName: string;
	type: string;
};

type Size = {
	min: ?number;
	max: ?number;
};

type Options = {
	entity: ?Entity;
	type: ?string;
	id: ?string;
	name: ?string;
	label: ?string;
	multiple: ?Boolean;
	autocomplete: ?Boolean;
	hint: ?String;
	hintOnFocus: ?Boolean;
	visible: ?Boolean;
	required: ?Boolean;
	placeholder: ?string;
	items: ?Array;
	value: ?string;
	checked: ?Boolean;
	values: ?Array;
	messages: ?Messages.Storage;
	design: ?Design.Model;
	size: ?Size;
	contentTypes: ?Array<string>;
	maxSizeMb: ?Number;
	readonly: ?boolean;
};

let DefaultOptions: Options = {
	type: 'string',
	label: 'Default field name',
	multiple: false,
	autocomplete: null,
	visible: true,
	required: false,
};

class Controller extends Event
{
	events: Object = {
		blur: 'blur',
		focus: 'focus',
		changeSelected: 'change:selected',
	};
	options: Options = DefaultOptions;
	id: String;
	name: String;
	type: String;
	multiple: Boolean;
	autocomplete: Boolean;
	hint: String;
	hintOnFocus: Boolean;
	visible: Boolean;
	required: Boolean;
	placeholder: String;
	label: String;
	items: Array<Item.Item> = [];

	validated: Boolean = false;
	focused: Boolean = false;

	//#baseType: string;
	validators: Array<Function> = [];
	normalizers: Array<Function> = [];
	formatters: Array<Function> = [];
	filters: Array<Function> = [];

	messages: Messages.Storage;
	design: Design.Model;

	static type(): string
	{
		return '';
	}

	static component()
	{
		return Component.Field;
	}

	static createItem(options): Item.Item
	{
		return new Item.Item(options);
	}

	getComponentName(): string
	{
		return 'field-' + this.getType();
	}

	get isComponentDuplicable()
	{
		return false;
	}

	getType(): string
	{
		return this.constructor.type();
	}

	constructor(options: Options = DefaultOptions)
	{
		super(options);
		this.visible = !!options.visible;

		this.adjust(options);
	}

	reset()
	{
		this.items = [];
		this.adjust(this.options, false);
	}

	selectedItems()
	{
		return this.items.filter(item => item.selected);
	}

	selectedItem(): Item.Item|null
	{
		return this.selectedItems()[0];
	}

	unselectedItems()
	{
		return this.items.filter(item => !item.selected);
	}

	unselectedItem(): Item.Item|null
	{
		return this.unselectedItems()[0];
	}

	item(): Item.Item|null
	{
		return this.items[0];
	}

	value()
	{
		return this.values()[0];
	}

	values(): Array
	{
		return this.selectedItems().map(item => item.value);
	}

	getComparableValues(): Array
	{
		return this.selectedItems().map(item => item.getComparableValue());
	}

	normalize(value)
	{
		return this.normalizers.reduce((v, f) => f(v), value);
	}

	filter(value)
	{
		return this.filters.reduce((v, f) => f(v), value);
	}

	format(value)
	{
		return this.formatters.reduce((v, f) => f(v), value);
	}

	validate(value): boolean
	{
		if (value === '')
		{
			return true;
		}

		return !this.validators.some((validator) => !validator.call(this, value));
	}

	hasValidValue()
	{
		return this.values().some(value => value !== '' && this.validate(value));
	}

	isEmptyRequired(): boolean
	{
		let items = this.selectedItems();

		if (this.required)
		{
			if (items.length === 0 || !items[0] || !items[0].selected || (items[0].value+'').trim() === '')
			{
				return true;
			}
		}

		return false;
	}

	valid(): boolean
	{
		if (!this.visible)
		{
			return true;
		}

		this.validated = true;
		let items = this.selectedItems();

		if (this.isEmptyRequired())
		{
			return false;
		}

		return !items.some((item) => !this.validate(item.value));
	}

	getOriginalType(): string
	{
		return this.type;
	}

	addItem(options: Item.Options): Item.Item
	{
		if (options.selected && !this.multiple && this.values().length > 0)
		{
			options.selected = false;
		}

		let item = this.constructor.createItem(options);
		if (item)
		{
			item.subscribe(item.events.changeSelected, (data, obj, type) => {
				this.emit(this.events.changeSelected, {data, type, item: obj});
			});

			this.items.push(item);
		}

		return item;
	}

	addSingleEmptyItem()
	{
		if (this.items.length > this.values().length)
		{
			return;
		}

		if (this.items.length > 0 && !this.multiple)
		{
			return;
		}

		this.addItem({});
	}

	removeItem(itemIndex)
	{
		this.items.splice(itemIndex, 1);
		this.addSingleEmptyItem();
	}

	removeFirstEmptyItems()
	{

	}

	#prepareValues(values: Array<string>): Array<string>
	{
		return values
			.filter(value => typeof value !== 'undefined')
			.map(value => this.normalize(value + ''))
			.map(value => this.format(value))
			.filter(value => this.validate(value))
		;
	}

	setValues(values: Array<string>): this
	{
		values = this.#prepareValues(values);
		if (values.length === 0)
		{
			return this;
		}

		if (!this.multiple)
		{
			values = [values[0]];
		}

		if (this.isComponentDuplicable)
		{
			if (this.items.length > values.length)
			{
				this.items = this.items.slice(0, values.length - 1);
			}
			values.forEach((value, index) => {
				let item = this.items[index];
				if (!item)
				{
					item = this.addItem({value});
				}

				item.value = value;
			});
		}

		this.items.forEach(item => {
			item.selected = values.indexOf(item.getComparableValue()) >= 0;
		});

		return this;
	}

	adjust(options: Options = DefaultOptions, autocomplete: boolean = true)
	{
		this.options = Object.assign({}, this.options, options);
		this.id = this.options.id || '';
		this.name = this.options.name || '';
		this.type = this.options.type;
		this.label = this.options.label;
		this.multiple = !!this.options.multiple;
		this.autocomplete = !!this.options.autocomplete;
		this.required = !!this.options.required;
		this.hint = this.options.hint || '';
		this.hintOnFocus = !!this.options.hintOnFocus;
		this.placeholder = this.options.placeholder || '';

		if (options.messages || !this.messages)
		{
			if (options.messages instanceof Messages.Storage)
			{
				this.messages = options.messages;
			}
			else
			{
				this.messages = new Messages.Storage();
				this.messages.setMessages(options.messages || {});
			}
		}

		if (options.design || !this.design)
		{
			if (options.design instanceof Design.Model)
			{
				this.design = options.design;
			}
			else
			{
				this.design = new Design.Model();
				this.design.adjust(options.design || {});
			}
		}

		let values = this.options.values || [];
		let value = this.options.value || values[0];

		if (autocomplete)
		{
			value = value
				|| (this.autocomplete ? getStoredFieldValueByFieldName(this.name) : null)
				|| (this.autocomplete ? getStoredFieldValue(this.getType()) : null)
				|| ''
			;
		}

		let items = this.options.items || [];
		let selected = !this.multiple || values.length > 0;
		if (values.length === 0)
		{
			values.push(value);
			selected = typeof value !== 'undefined' && value !== '';
		}

		// empty single
		if (items.length === 0 && !this.multiple)
		{
			if (typeof this.options.checked !== "undefined")
			{
				selected = !!this.options.checked;
			}
			items.push({value: value, selected: selected});
		}

		// empty multi
		if (items.length === 0 && this.multiple)
		{
			values.forEach(value => items.push({value: value, selected: selected}));
		}

		items.forEach(item => this.addItem(item));
	}
}

export {Controller, Options, DefaultOptions}
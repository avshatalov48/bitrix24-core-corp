import * as BaseField from '../base/controller';
import * as Item from './item';
import { Factory } from '../factory';
import { FieldsContainer } from './component';

type Options = {
	name: number;
	nestedFields: Array<BaseField.Options>;
};

class Controller extends BaseField.Controller
{
	nestedFields: Array<BaseField.Controller> = [];

	static type(): string
	{
		return 'container';
	}

	static component()
	{
		return FieldsContainer;
	}

	static createItem(options: Item.Options): Item.Item
	{
		return new Item.Item(options);
	}

	constructor(options: BaseField.Options)
	{
		super(options);
		//this.nestedFields = [];
	}

	adjust(options: BaseField.Options, autocomplete = true)
	{
		super.adjust(options);
		setTimeout(() => {
			this.actualizeFields();
			this.actualizeValues();
		}, 0);
	}

	setValues(values: Array<string>): this
	{
		values = values[0] || {};
		this.nestedFields.forEach(field => {
			const value = values[field.name];
			if (typeof value === 'undefined')
			{
				return;
			}

			field.setValues(Array.isArray(value) ? value : [value]);
		});
	}

	actualizeFields()
	{
		this.nestedFields = this.makeFields(this.options.fields || []);
	}

	makeFields(list: Array<BaseField.Options> = [])
	{
		return list.map((options: Options) => {
			options.messages = this.options.messages;
			options.design = this.options.design;
			options.format = this.options.format;
			options.sundayFirstly = this.options.sundayFirstly;

			const field = new Factory.create(Object.assign({
				visible: true,
				id: this.id + '-' + options.name,
			}, options));
			field.subscribe(field.events.changeSelected, () => this.actualizeValues());

			return field;
		});
	}

	valid()
	{
		if (!this.visible)
		{
			return true;
		}

		this.validated = true;
		let valid = true;
		this.nestedFields.forEach(field => {
			if (!field.valid())
			{
				valid = false;
			}
		});

		return valid;
	}

	actualizeValues()
	{
		const value = (this.nestedFields || []).reduce(
			(acc, field: BaseField) => {
				const key = field.name || '';
				const val = field.value();
				if (key.length > 0)
				{
					acc[key] = val;
				}

				return acc;
			},
			{}
		);

		const item: Item = this.item();
		item.value = value;
		item.selected = true;

		console.log('value', value)
	}
}

export {Controller, Options}
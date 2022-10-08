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

		const item = this.item();
		item.value = value;
		item.selected = true;
	}
}

export {Controller, Options}
import { Controller as ContainerController } from '../container/controller';
import { Factory } from '../factory';
import * as BaseField from "../base/controller";

type PresetOptions = {
	id: number;
	label: string;
	countryId: number;
	nestedFields: Array<Object>;
};

type Options = {
	name: ?string;
	label: ?string;
	disabled: ?boolean;
	requisite: {
		presets: Array<PresetOptions>;
	};
};

class Controller extends ContainerController
{
	presetField: BaseField.Controller;

	static type(): string
	{
		return 'rq';
	}

	actualizeFields()
	{
		if (!this.presetField)
		{
			this.options.requisite = this.options.requisite || {};
			this.options.requisite.presets = (this.options.requisite.presets || [])
				.filter(preset => !preset.disabled)
			;

			this.presetField = new Factory.create({
				type: 'radio',
				name: 'presetId',
				label: this.label,
				items: this.options.requisite.presets.map(preset => {
					return {value: preset.id, label: preset.label}
				}),
				visible: true,
			});
			this.presetField.subscribe(this.presetField.events.changeSelected, () => {
				this.actualizeFields();
				this.actualizeValues();
			});
		}

		const v = this.presetField.value();
		const presets = this.options.requisite.presets;
		const preset = presets.filter(preset => preset.id === v)[0] || {};

		const fields = [];
		(preset.fields || [])
			.filter((options: Options) => !options.disabled)
			.forEach((options: Options) => {
				options = JSON.parse(JSON.stringify(options));
				if (options.fields && options.fields.length > 0)
				{
					if (['address', 'account'].includes(options.type))
					{
						fields.push({
							type: 'layout',
							label: options.label,
							content: {
								type: 'section',
							},
						});
					}
					options.type = 'container';
				}

				fields.push(options);
			})
		;
		this.nestedFields = [].concat([this.presetField], this.makeFields(fields));
	}
}

export {Controller, Options}
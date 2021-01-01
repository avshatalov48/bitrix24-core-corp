import {B24Options} from './type';
import {EventTypes, Options, Controller} from "./form/registry";
import {BaseField} from './field/registry';

export function performEventOfWidgetFormInit(b24options: B24Options, options: Options)
{
	const compatibleData = createEventData(b24options, options);
	BX.SiteButton.onWidgetFormInit(compatibleData);
	applyOldenLoaderData(options, compatibleData);
}

export function applyOldenLoaderData(options: Options, oldenLoaderData: Object)
{
	if (options.fields && typeof oldenLoaderData.fields === 'object' && typeof oldenLoaderData.fields.values === 'object')
	{
		Object.keys(oldenLoaderData.fields.values).forEach(key => {
			options.fields.filter(field => field.name === key)
				.forEach(field => field.value = oldenLoaderData.fields.values[key]);
		});
	}

	if (typeof oldenLoaderData.presets === 'object')
	{
		options.properties = options.properties || {};
		Object.keys(oldenLoaderData.presets).forEach(key => {
			options.properties[key] = oldenLoaderData.presets[key];
		});
	}

	if (oldenLoaderData.type === 'auto' && oldenLoaderData.delay)
	{
		if (typeof options.view === 'object' && parseInt(oldenLoaderData.delay) > 0)
		{
			options.view.delay = parseInt(oldenLoaderData.delay);
		}
	}

	if (typeof oldenLoaderData.handlers === 'object')
	{
		options.handlers = options.handlers || {};
		Object.keys(oldenLoaderData.handlers).forEach(key => {
			const value = oldenLoaderData.handlers[key];
			if (typeof value !== "function")
			{
				return;
			}

			let type;
			let handler;
			switch (key)
			{
				case 'load':
					type = EventTypes.init;
					handler = (data: Object, form: Controller) => {
						value(oldenLoaderData, form);
					};
					break;
				case 'fill':
					type = EventTypes.fieldBlur;
					handler = (data: Object) => {
						const field = data.field;
						value(field.name, field.values());
					};
					break;
				case 'send':
					type = EventTypes.sendSuccess;
					if (typeof value === "function")
					{
						handler = (data, form: Controller) => {
							value(
								Object.assign(
									form.getFields().reduce((acc, field: BaseField) => {
										acc[field.name] = field.multiple ? field.values() : field.value();
										return acc;
									}, {}),
									data || {}
								),
								form
							);
						};
					}
					break;
				case 'unload':
					type = EventTypes.destroy;
					handler = (data: Object, form: Controller) => {
						value(oldenLoaderData, form);
					};
					break;
			}

			if (type)
			{
				options.handlers[type] = handler ? handler : value;
			}
		});
	}
}


function createEventData(b24options: B24Options)
{
	return {
		id: b24options.id,
		sec: b24options.sec,
		lang: b24options.lang,
		address: b24options.address,
		handlers: {},
		presets: {},
		fields: {values: {}},
	};
}
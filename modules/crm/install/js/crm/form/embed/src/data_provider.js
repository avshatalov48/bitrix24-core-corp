import {Type} from 'main.core';
import {EmbedDataValues, EmbedDataOptions, EmbedDict} from "./types";

/**
 * @package
 */
export class DataProvider
{
	#data: Object = {
		loaded: false,
		data: {},
		errors: {},
	};

	updateValues(type: string, values: Object): undefined
	{
		if (type === 'click')
		{
			if (values.button.plain === '1' && Type.isStringFilled(values?.link?.align))
			{
				values.button.align = values.link.align;
			}
			delete values.link;
		}

		this.data.embed.viewValues[type] = values;
	}

	getValues(type: string): EmbedDataValues
	{
		const values = this.data.embed.viewValues[type];

		if (type === 'click' && Type.isStringFilled(values?.button?.align))
		{
			values.link = {align: values?.button?.align};
		}
		
		return BX.mergeEx(this.#getDefaultValues(type), values);
	}

	getOptions(type: string): EmbedDataOptions
	{
		return this.data.embed.viewOptions[type];
	}

	getDict(): EmbedDict
	{
		const dict = this.data.dict;
		dict.viewOptions.link = {aligns: dict?.viewOptions?.button?.aligns};
		return dict;
	}

	/**
	 * @package
	 */
	get data(): Object
	{
		return this.#data.data;
	}

	/**
	 * @package
	 */
	set data(data: Object)
	{
		this.#data.data = data;
	}

	/**
	 * @package
	 */
	get errors(): Object
	{
		return this.#data.errors;
	}

	/**
	 * @package
	 */
	set errors(errors: Object)
	{
		this.#data.errors = errors;
	}

	/**
	 * @package
	 */
	get loaded(): boolean
	{
		return this.#data.loaded || false;
	}

	/**
	 * @package
	 */
	set loaded(value: boolean)
	{
		this.#data.loaded = value;
	}

	#getDefaultValues(type: string): EmbedDataValues
	{
		const defaults = {
			inline: {},
			auto: {}, // 'type', 'position', 'vertical', 'delay'
			click: {
				// 'type', 'position', 'vertical',
				button: {
					use: "0", // 1|0
					text: BX.Loc.getMessage('EMBED_SLIDER_OPTION_BUTTONSTYLE_LABEL'),
					font: "modern", // modern|classic|elegant
					align: "center", // left|right|center|inline
					plain: "0",
					rounded: "0",
					outlined: "0",
					decoration: "", // ""|dotted|solid
					color: {
						text: "#ffffffff",
						textHover: "#ffffffff",
						background: "#3eddffff",
						backgroundHover: "#3eddffa6",
					},
				},
			},
		};

		return !Type.isUndefined(defaults[type])
			? defaults[type]
			: {}
		;
	}
}
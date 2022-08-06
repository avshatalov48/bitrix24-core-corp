import {Type, Text, Tag, Dom, Event} from 'main.core';
import type {EmbedDataOptions, EmbedDataValues, EmbedDict} from "../types";
import {Controls} from "./controls";
import "popup";
import {isHexDark} from "../util";
import {handlerToggleClickMode} from "./handler";
import { EventEmitter, BaseEvent } from 'main.core.events'

type RenderOptions = {
	options: Array,
	dict: EmbedDict,
	callback: function,
	formId: number,
	data: Object,
	values: Object,
};

export class Wizard extends EventEmitter
{
	#type: string;
	#options: Object;
	#values: Object;
	#dict: EmbedDict;
	#formId: number;

	constructor(type: string, formId: number, values: EmbedDataValues, options: EmbedDataOptions, dict: EmbedDict)
	{
		super();
		this.setEventNamespace("BX:Crm:Form:Embed")
		this.#type = type;
		this.#options = options;
		this.#values = values;
		this.#dict = dict;
		this.#formId = formId;
	}

	getValues(): Object
	{
		return this.#values;
	}

	#updateValues(data: Array, instantSave: boolean): Promise
	{
		const promises = [];
		data.forEach((field) => {
			promises.push(this.#updateValue(field.name, field.value));
		});

		return Promise.all(promises).then(() => {
			if (instantSave) {
				const event = new BaseEvent({data: {type: this.#type}});
				return this.emitAsync('BX:Crm:Form:Embed:needToSave', event);
			}
			return Promise.resolve;
		});
	}

	#updateValue(name: string, value): Promise
	{
		const aNames = name.split('.');
		const updatedValues = this.#values;
		const buildOption = function(values, names) {
			const nextName = names.shift();
			if (names.length > 0)
			{
				values[nextName] = Type.isUndefined(values[nextName]) ? {} : values[nextName];
				buildOption(values[nextName], names);
			}
			else
			{
				values[nextName] = value;
			}
		};
		buildOption(updatedValues, aNames);

		this.#values = updatedValues;

		const event = new BaseEvent({data: {type: this.#type, name: name, value: value}});

		this.emit('BX:Crm:Form:Embed:valueChanged', event);

		return Promise.resolve();
	}

	renderControlContainer(elem: Element | Array = null): HTMLElement
	{
		if (Type.isDomNode(elem))
		{
			elem = [elem];
		}
		const container = Tag.render`
			<div class="crm-form-embed__settings"></div>
		`;
		if (Type.isArray(elem))
		{
			elem.forEach((item) => Dom.append(item, container))
		}
		return container;
	}

	renderRow(elem: Element | Array = null, last: boolean = false): HTMLElement
	{
		if (Type.isDomNode(elem))
		{
			elem = [elem];
		}
		const row = Tag.render`
			<div class="crm-form-embed__settings-row ${last ? '--last' : ''}"></div>
		`;
		if (Type.isArray(elem))
		{
			elem.forEach((item) => Dom.append(item, row))
		}
		return row;
	}

	renderBlock(elem: Element | Array = null): HTMLElement
	{
		if (Type.isDomNode(elem))
		{
			elem = [elem];
		}
		const block = Tag.render`
			<div class="crm-form-embed__settings--block"></div>
		`;
		if (Type.isArray(elem))
		{
			elem.forEach((item) => Dom.append(item, block))
		}
		return block;
	}

	renderCol(elem: Element | Array = null, first: boolean = false): HTMLElement
	{
		if (Type.isDomNode(elem))
		{
			elem = [elem];
		}
		const col = Tag.render`
			<div class="crm-form-embed__settings--block-col ${first ? '--first' : ''}"></div>
		`;
		if (Type.isArray(elem))
		{
			elem.forEach((item) => Dom.append(item, col))
		}
		return col;
	}

	renderTitle(text: string, line: boolean = false): HTMLElement
	{
		return Tag.render`
			<div class="crm-form-embed__subtitle ${line ? '--line' : ''}">${text ? text : ''}</div>
		`;
	}

	renderLabel(text: string, buttonSetting: boolean = false): HTMLElement
	{
		return Renderer.renderLabel(text, buttonSetting);
	}

	renderOptionTo(container: HTMLElement, name: string, instantSave: boolean = true)
	{
		const funcName = this.#getFuncName(name),
			fieldOptions = this.#getOptions(name),
			fieldValue = this.#getValue(name);

		if (Type.isFunction(Renderer["renderField" + funcName]))
		{
			Renderer["renderField" + funcName](
				container,
				fieldValue,
				{
					options: fieldOptions,
					dict: this.#dict,
					callback: (data) => this.#updateValues(data, instantSave),
					formId: this.#formId,
					data: this.#options,
					values: this.#values,
				}
			);
		}
		else
		{
			console.error('embed: ' + name + ' is not valid type');
		}
	}

	renderOption(name: string, instantSave: boolean = true): HTMLElement
	{
		const container = this.renderControlContainer();
		this.renderOptionTo(container, name, instantSave);
		return container;
	}

	renderColorInline(container: HTMLElement, name: string, instantSave: boolean = true)
	{
		this.renderOptionTo(container, name, instantSave);
	}

	renderColorPopup(container: HTMLElement, name: string, instantSave: boolean = true)
	{
		const value = this.#getValue(name, '#FFFFFF');
		const picker = this.renderOption(name, instantSave);
		const popup = new BX.PopupWindow({
			content: picker,
			bindElement: container,
			autoHide: true,
			closeByEsc: true,
		});

		// hack: hide color picker elements, embed.css
		Dom.addClass(picker, 'crm-form-embed__color_popup');

		const getStyle = function(valueHex: string): string {
			const fgColor = isHexDark(valueHex) ? 'white' : 'black';
			return `background-color: ${Text.encode(valueHex)}; color: ${fgColor}`;
		}

		const getThemeClass = function(valueHex: string): string {
			return isHexDark(valueHex) ? 'bitrix24-light-theme' : 'bitrix24-dark-theme';
		}

		const button = Tag.render`
			<button class="ui-btn ui-btn-xs ui-btn-link ui-btn-hover ui-btn-icon-edit ui-btn-themes"></button>
		`;
		Event.bind(button, "click", (event) => popup.show());

		const text = Tag.render`
			<span class="crm-form-embed__settings--color-text">${Text.encode(value)}</span>
		`;

		Dom.append(Controls.renderLabel(Renderer.getOptionKeyName(name), true), container);
		const colorBox = Tag.render`
			<div class="crm-form-embed__settings--color ${getThemeClass(value)}" style="${getStyle(value)}">
				${text}
				${button}
			</div>
		`;
		Dom.append(colorBox, container);

		this.subscribe('BX:Crm:Form:Embed:valueChanged', (event) => {
			if (event.data.name === name)
			{
				text.innerText = Text.encode(event.data.value);
				colorBox.style = getStyle(event.data.value);
				Dom.removeClass(
					colorBox,
					getThemeClass(event.data.value) === 'bitrix24-dark-theme'
						? 'bitrix24-light-theme'
						: 'bitrix24-dark-theme'
				);
				Dom.addClass(colorBox, getThemeClass(event.data.value));
			}
		});
	}

	#getValue(name: string, defaultValue = ''): string | number
	{
		const aName = name.split('.');
		let fieldValue = this.#values;
		aName.forEach((part) => {
			fieldValue = !Type.isUndefined(fieldValue[part]) ? fieldValue[part] : defaultValue;
		});
		return fieldValue;
	}

	#getOptions(name: string): Array
	{
		const aName = name.split('.');

		let fieldOptions = this.#options;
		aName.forEach((part) => {
			fieldOptions = !Type.isUndefined(fieldOptions[part]) ? fieldOptions[part] : [];
		});
		return fieldOptions;
	}

	#getFuncName(name: string): string
	{
		const aName = name.split('.');
		let funcName = '';
		aName.forEach((part) => {
			funcName = funcName.concat(part.charAt(0).toUpperCase() + part.slice(1));
		});
		return funcName;
	}
}

const Renderer = {
	renderFieldDelay: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const handler = (val) => {
			options.callback([{name: 'delay', value: val}]).then(() => {
				Renderer.renderFieldDelay(container, val, options);
			});
		};

		const block = Tag.render`<div class="crm-form-embed__settings--block-input"></div>`;

		Controls.renderDropdown({
			callback: handler,
			node: block,
			options: this.getOptions(options.options, 'delay', options.dict),
			value: value,
			key: 'delay',
			keyName: Renderer.getOptionKeyName('delay'),
			formId: options.formId,
		});

		Dom.append(block, container);
	},

	renderFieldType: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const handler = (val) => {
			options.callback([{name: 'type', value: val}]).then(() => {
				Renderer.renderFieldType(container, val, options);
			});
		};

		const block = Tag.render`<div class="crm-form-embed__settings--block-input"></div>`;

		Controls.renderDropdown({
			callback: handler,
			node: block,
			options: this.getOptions(options.options, 'type', options.dict),
			value: value,
			key: 'type',
			keyName: Renderer.getOptionKeyName('type'),
			formId: options.formId,
		});

		Dom.append(block, container);
	},

	renderFieldPosition: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const controlOptions = this.getOptions(options.options, 'position', options.dict);

		const handler = (val) => {
			options.callback([{name: 'position', value: val}]).then(() => {
				Renderer.renderFieldPosition(container, val, options);
			});
		};

		const handlerLeft = () => handler('left');
		const handlerCenter = () => handler('center');
		const handlerRight = () => handler('right');

		Dom.append(Controls.renderOptionTitle(Renderer.getOptionKeyName('position')), container);

		const row = Tag.render`<div class="crm-form-embed__settings"></div>`;

		Controls.renderSquareButton('left', {
			callback: handlerLeft,
			node: row,
			options: controlOptions,
			value: value,
			key: 'position',
			keyName: Renderer.getOptionValueName('position', 'left', options.dict),
			formId: options.formId,
		});

		Controls.renderSquareButton('center', {
			callback: handlerCenter,
			node: row,
			options: controlOptions,
			value: value,
			key: 'position',
			keyName: Renderer.getOptionValueName('position', 'center', options.dict),
			formId: options.formId,
		});

		Controls.renderSquareButton('right', {
			callback: handlerRight,
			node: row,
			options: controlOptions,
			value: value,
			key: 'position',
			keyName: Renderer.getOptionValueName('position', 'right', options.dict),
			formId: options.formId,
		});

		Dom.append(row, container);
	},

	renderFieldVertical: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const controlOptions = this.getOptions(options.options, 'vertical', options.dict);

		const handler = (val) => {
			options.callback([{name: 'vertical', value: val}]).then(() => {
				Renderer.renderFieldVertical(container, val, options);
			});
		};

		const handlerBottom = () => handler('bottom');
		const handlerTop = () => handler('top');

		Dom.append(Controls.renderOptionTitle(Renderer.getOptionKeyName('vertical')), container);

		const row = Tag.render`<div class="crm-form-embed__settings"></div>`;

		Controls.renderSquareButton('bottom', {
			callback: handlerBottom,
			node: row,
			options: controlOptions,
			value: value,
			key: 'vertical',
			keyName: Renderer.getOptionValueName('vertical', 'bottom', options.dict),
			formId: options.formId,
		});

		Controls.renderSquareButton('top', {
			callback: handlerTop,
			node: row,
			options: controlOptions,
			value: value,
			key: 'vertical',
			keyName: Renderer.getOptionValueName('vertical', 'top', options.dict),
			formId: options.formId,
		});

		Dom.append(row, container);
	},

	renderFieldButtonStyle: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		if (Type.isStringFilled(options?.values?.button?.rounded) && Type.isStringFilled(options?.values?.button?.outlined))
		{
			value = options.values.button.rounded + ':' + options.values.button.outlined;
		}

		container.innerHTML = '';

		const controlOptions = this.getOptions(options.options, 'buttonStyle', options.dict);

		const handler = (val) => {
			const values = val.split(':');
			options.callback([
				{name: 'button.rounded', value: values[0]},
				{name: 'button.outlined', value: values[1]},
			]).then(() => {
				Renderer.renderFieldButtonStyle(container, val, options);
			});
		};

		const handlerPm = () => handler('0:0');
		const handlerPmr = () => handler('1:0');
		const handlerLb = () => handler('0:1');
		const handlerLbr = () => handler('1:1');

		const advanced = container.dataset.controlMode === 'advanced';

		Controls.renderButton('0:0', 'ui-btn-primary', advanced, {
			callback: handlerPm,
			node: container,
			options: controlOptions,
			value: value,
			key: 'buttonStyle',
			keyName: Renderer.getOptionValueName('buttonStyle', '0:0', options.dict),
			formId: options.formId,
		});

		Controls.renderButton('1:0', 'ui-btn-primary ui-btn-round', advanced, {
			callback: handlerPmr,
			node: container,
			options: controlOptions,
			value: value,
			key: 'buttonStyle',
			keyName: Renderer.getOptionValueName('buttonStyle', '1:0', options.dict),
			formId: options.formId,
		});

		Controls.renderButton('0:1', 'ui-btn-light-border', advanced, {
			callback: handlerLb,
			node: container,
			options: controlOptions,
			value: value,
			key: 'buttonStyle',
			keyName: Renderer.getOptionValueName('buttonStyle', '0:1', options.dict),
			formId: options.formId,
		});

		Controls.renderButton('1:1', 'ui-btn-light-border ui-btn-round', advanced, {
			callback: handlerLbr,
			node: container,
			options: controlOptions,
			value: value,
			key: 'buttonStyle',
			keyName: Renderer.getOptionValueName('buttonStyle', '1:1', options.dict),
			formId: options.formId,
		});
	},

	renderFieldButtonDecoration: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const controlOptions = this.getOptions(options.options, 'button.decoration', options.dict);

		const handler = (val) => {
			options.callback([{name: 'button.decoration', value: val}]).then(() => {
				Renderer.renderFieldButtonDecoration(container, val, options);
			});
		};

		const handlerNoBorder = () => handler('');
		const handlerDotted = () => handler('dotted');
		const handlerLine = () => handler('solid');

		const advanced = container.dataset.controlMode === 'advanced';

		Controls.renderLink('', '--border-no', advanced, {
			callback: handlerNoBorder,
			node: container,
			options: controlOptions,
			value: value,
			key: 'button.decoration',
			keyName: Renderer.getOptionValueName('button.decoration', '', options.dict),
			formId: options.formId,
		});

		Controls.renderLink('dotted', '--border-dotted', advanced, {
			callback: handlerDotted,
			node: container,
			options: controlOptions,
			value: value,
			key: 'button.decoration',
			keyName: Renderer.getOptionValueName('button.decoration', 'dotted', options.dict),
			formId: options.formId,
		});

		Controls.renderLink('solid', '--border-line', advanced, {
			callback: handlerLine,
			node: container,
			options: controlOptions,
			value: value,
			key: 'button.decoration',
			keyName: Renderer.getOptionValueName('button.decoration', 'solid', options.dict),
			formId: options.formId,
		});
	},

	renderFieldButtonFont: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const controlOptions = this.getOptions(options.options, 'button.font', options.dict);

		const handler = (val) => {
			options.callback([{name: 'button.font', value: val}]).then(() => {
				Renderer.renderFieldButtonFont(container, val, options);
			});
		};

		const handlerModern = () => handler('modern');
		const handlerClassic = () => handler('classic');
		const handlerElegant = () => handler('elegant');

		const advanced = container.dataset.controlMode === 'advanced';

		Controls.renderTypeface('modern', advanced, {
			callback: handlerModern,
			node: container,
			options: controlOptions,
			value: value,
			key: 'button.font',
			keyName: Renderer.getOptionValueName('button.font', 'modern', options.dict),
			formId: options.formId,
		});

		Controls.renderTypeface('classic', advanced, {
			callback: handlerClassic,
			node: container,
			options: controlOptions,
			value: value,
			key: 'button.font',
			keyName: Renderer.getOptionValueName('button.font', 'classic', options.dict),
			formId: options.formId,
		});

		Controls.renderTypeface('elegant', advanced, {
			callback: handlerElegant,
			node: container,
			options: controlOptions,
			value: value,
			key: 'button.font',
			keyName: Renderer.getOptionValueName('button.font', 'elegant', options.dict),
			formId: options.formId,
		});
	},

	renderFieldButtonAlign: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const controlOptions = this.getOptions(options.options, 'button.align', options.dict);

		const handler = (val) => {
			options.callback([{name: 'button.align', value: val}]).then(() => {
				Renderer.renderFieldButtonAlign(container, val, options);
			});
		};

		const handlerLeft = () => handler('left');
		const handlerCenter = () => handler('center');
		const handlerRight = () => handler('right');

		// container.appendChild(Controls.renderOptionTitle(Renderer.getOptionKeyName('button.align')));

		const row = Tag.render`<div class="crm-form-embed__settings"></div>`;

		Controls.renderSquareButton('left', {
			callback: handlerLeft,
			node: row,
			options: controlOptions,
			value: value,
			key: 'button.align',
			keyName: Renderer.getOptionValueName('button.align', 'left', options.dict),
			formId: options.formId,
		});

		Controls.renderSquareButton('center', {
			callback: handlerCenter,
			node: row,
			options: controlOptions,
			value: value,
			key: 'button.align',
			keyName: Renderer.getOptionValueName('button.align', 'center', options.dict),
			formId: options.formId,
		});

		Controls.renderSquareButton('right', {
			callback: handlerRight,
			node: row,
			options: controlOptions,
			value: value,
			key: 'button.align',
			keyName: Renderer.getOptionValueName('button.align', 'right', options.dict),
			formId: options.formId,
		});

		Dom.append(row, container);
	},

	renderFieldLinkAlign: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const controlOptions = this.getOptions(options.options, 'link.align', options.dict);

		const handler = (val) => {
			options.callback([{name: 'link.align', value: val}]).then(() => {
				Renderer.renderFieldLinkAlign(container, val, options);
			});
		};

		const handlerInline = () => handler('inline');
		const handlerLeft = () => handler('left');
		const handlerCenter = () => handler('center');
		const handlerRight = () => handler('right');

		// container.appendChild(Controls.renderOptionTitle(Renderer.getOptionKeyName('button.align')));

		const row = Tag.render`<div class="crm-form-embed__settings"></div>`;

		Controls.renderSquareButton('inline', {
			callback: handlerInline,
			node: row,
			options: controlOptions,
			value: value,
			key: 'link.align',
			keyName: Renderer.getOptionValueName('link.align', 'inline', options.dict),
			formId: options.formId,
		});

		Controls.renderSquareButton('left', {
			callback: handlerLeft,
			node: row,
			options: controlOptions,
			value: value,
			key: 'link.align',
			keyName: Renderer.getOptionValueName('link.align', 'left', options.dict),
			formId: options.formId,
		});

		Controls.renderSquareButton('center', {
			callback: handlerCenter,
			node: row,
			options: controlOptions,
			value: value,
			key: 'link.align',
			keyName: Renderer.getOptionValueName('link.align', 'center', options.dict),
			formId: options.formId,
		});

		Controls.renderSquareButton('right', {
			callback: handlerRight,
			node: row,
			options: controlOptions,
			value: value,
			key: 'link.align',
			keyName: Renderer.getOptionValueName('link.align', 'right', options.dict),
			formId: options.formId,
		});

		Dom.append(row, container);
	},

	renderFieldButtonPlain: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const handler = (val) => {
			options.callback([{name: 'button.plain', value: val}]).then(() => {
				Renderer.renderFieldButtonPlain(container, val, options);
			});
		};

		const block = Tag.render`<div class="crm-form-embed__settings--block-input"></div>`;

		Controls.renderDropdown({
			callback: handler,
			node: block,
			options: this.getOptions(options.options, 'button.plain', options.dict),
			value: value,
			key: 'button.plain',
			keyName: Renderer.getOptionKeyName('button.plain'),
			formId: options.formId,
		});

		Dom.append(block, container);
	},

	renderFieldButtonText: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const handler = (val) => {
			if (!Type.isStringFilled(val))
			{
				val = BX.Loc.getMessage('EMBED_SLIDER_BUTTONTEXT_PLACEHOLDER');
			}
			options.callback([{name: 'button.text', value: val}]).then(() => {
				Renderer.renderFieldButtonText(container, val, options);
			});
		};

		const block = Tag.render`<div class="crm-form-embed__settings--block-input"></div>`;

		const advanced = container.dataset.controlMode === 'advanced';

		Controls.renderText({
			callback: handler,
			node: block,
			options: null,
			value: value ? value : BX.Loc.getMessage('EMBED_SLIDER_BUTTONTEXT_PLACEHOLDER'),
			key: 'button.text',
			keyName: Renderer.getOptionKeyName('button.text'),
			formId: options.formId,
		}, advanced);

		Dom.append(block, container);
	},

	renderFieldButtonColorBackground: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const advanced = container.dataset.controlMode === 'advanced';

		const handler = (val) => {
			const fields = [
				{name: 'button.color.background', value: val}
			];
			if (!advanced)
			{
				fields.push({name: 'button.color.backgroundHover', value: val});
			}

			options.callback(fields).then(() => {
				// Renderer.renderFieldButtonColorBackground(container, val, options);
			});
		};

		Controls.renderColorPicker('color', {
			callback: handler,
			node: container,
			options: null,
			value: value,
			key: 'button.color.background',
			keyName: Renderer.getOptionKeyName('button.color.background'),
			formId: options.formId,
		});
	},

	renderFieldButtonColorBackgroundHover: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const advanced = container.dataset.controlMode === 'advanced';

		const handler = (val) => {
			options.callback([{name: 'button.color.backgroundHover', value: val}]).then(() => {
				// Renderer.renderFieldButtonColorBackgroundHover(container, val, options);
			});
		};

		Controls.renderColorPicker('color', {
			callback: handler,
			node: container,
			options: null,
			value: value,
			key: 'button.color.backgroundHover',
			keyName: Renderer.getOptionKeyName('button.color.backgroundHover'),
			formId: options.formId,
		});
	},

	renderFieldButtonColorText: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const advanced = container.dataset.controlMode === 'advanced';

		const handler = (val) => {
			const fields = [
				{name: 'button.color.text', value: val}
			];
			if (!advanced)
			{
				fields.push({name: 'button.color.textHover', value: val});
			}

			options.callback(fields).then(() => {
				// Renderer.renderFieldButtonColorText(container, val, options);
			});
		};

		Controls.renderColorPicker('color', {
			callback: handler,
			node: container,
			options: null,
			value: value,
			key: 'button.color.text',
			keyName: Renderer.getOptionKeyName('button.color.text'),
			formId: options.formId,
		});
	},

	renderFieldButtonColorTextHover: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const advanced = container.dataset.controlMode === 'advanced';

		const handler = (val) => {
			options.callback([{name: 'button.color.textHover', value: val}]).then(() => {
				// Renderer.renderFieldButtonColorTextHover(container, val, options);
			});
		};

		Controls.renderColorPicker('color', {
			callback: handler,
			node: container,
			options: null,
			value: value,
			key: 'button.color.textHover',
			keyName: Renderer.getOptionKeyName('button.color.textHover'),
			formId: options.formId,
		});
	},

	renderFieldButtonUse: function(container: HTMLElement, value: string, options: RenderOptions)
	{
		container.innerHTML = '';

		const handler = (val) => {
			return options.callback([{name: 'button.use', value: val}]).then(() => {
				Renderer.renderFieldButtonUse(container, val, options);
				handlerToggleClickMode(container, val === '1');
			});
		};

		Controls.renderSwitcher({
			callback: handler,
			node: container,
			options: null,
			value: value,
			key: 'button.use',
			keyName: Renderer.getOptionKeyName('button.use'),
			formId: options.formId,
		});
	},

	renderLabel: function(text: string, buttonSetting: boolean = false): HTMLElement
	{
		return Controls.renderLabel(text, buttonSetting);
	},

	getOptions: function(options, optionName: string, dict: EmbedDict): Object
	{
		const result = {};
		options.forEach((value) => {
			result[value] = Renderer.getOptionValueName(optionName, value, dict);
		});
		return result;
	},

	getOptionKeyName: function(key: string): string
	{
		const msgKey = 'EMBED_SLIDER_OPTION_' + key.toUpperCase();
		return BX.Loc.hasMessage(msgKey) ? BX.Loc.getMessage(msgKey) : Text.encode(key);
	},

	getOptionValueName: function(key: string, value: string, dict: EmbedDict): Object
	{
		const keySplit = key.split('.');
		const getDictValues = (parts: Array, dict: Object) => {
			const nextKey = parts.shift();
			if (parts.length > 0)
			{
				return getDictValues(parts, !Type.isUndefined(dict[nextKey]) ? dict[nextKey] : {});
			}
			return !Type.isUndefined(dict[nextKey + 's']) ? dict[nextKey + 's'] : [];
		}
		const dictValues = getDictValues(keySplit, dict['viewOptions']);

		let result = value;
		if (Type.isArray(dictValues)) {
			dictValues.forEach(elem => {
				if (elem.id.toString() === value.toString()) {
					result = elem.name;
				}
			});
		}
		return Text.encode(result);
	},
}
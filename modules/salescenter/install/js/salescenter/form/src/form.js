import {Tag, Text} from 'main.core';
import {Type} from 'main.core';
import "ui.forms";

export class Form
{
	constructor(id, options = {
		config: [],
		fields: [],
		data: [],
		classes: {},
		container: null
	})
	{
		this.id = id;
		this.config = options.config;
		this.fields = options.fields;
		this.data = options.data;
		if(options.container)
		{
			this.setContainer(options.container);
		}
		this.classes = new Map([
			['sectionContainer', 'salescenter-form-settings-section'],
			['sectionTitle', 'ui-title-6'],
			['controlContainer', 'salescenter-control-container'],
			['controlRequired', 'salescenter-control-required'],
			['subtextContainer', 'salescenter-subtext-container'],
			['subtextLink', 'ui-link ui-link-dashed'],
			['controlTitle', 'ui-ctl-label-text'],
			['controlInner', 'ui-ctl ui-ctl-w100'],
			['controlAfterIcon', 'ui-ctl-after-icon'],
			['controlSelect', 'ui-ctl-dropdown ui-ctl-after-icon'],
			['controlSelectIcon', 'ui-ctl-after ui-ctl-icon-angle'],
			['controlFile', 'ui-ctl-file-btn ui-ctl-w33'],
			['controlInput', 'ui-ctl-element'],
			['controlCheckbox', 'ui-ctl-checkbox'],
			['controlCheckboxLabel', 'ui-ctl-label-text'],
		]);

		if(Type.isPlainObject(options.classes))
		{
			this.classes.forEach((value, name) =>
			{
				if(Type.isString(options.classes[name]))
				{
					this.classes[name] = options.classes[name];
				}
			})
		}
	}

	static getByName(collection, name)
	{
		let items = [];
		if(Type.isArray(collection) && Type.isString(name))
		{
			items = collection.filter((item) =>
			{
				return item.name === name;
			});
			if(items.length > 0)
			{
				return items[0];
			}
		}

		return null;
	}

	/**
	 * @param {HTMLElement|null} nodeTo
	 * @returns {HTMLElement[]}
	 */
	render(nodeTo = null)
	{
		let result = '';
		this.config.forEach((section) =>
		{
			result += this.renderSection(section);
		});

		let nodes = Tag.render`${result}`;
		if(!Type.isArray(nodes))
		{
			nodes = [nodes];
		}

		if(Type.isDomNode(nodeTo))
		{
			nodes.forEach((node) =>
			{
				nodeTo.appendChild(node);
			})
		}

		return nodes;
	}

	/**
	 * @param field
	 * @returns {HTMLElement}
	 */
	renderField(field)
	{
		let result = '';
		if (!Type.isObject(field))
		{
			return result;
		}

		if (!field.html)
		{
			field.html = this.renderFieldInput(field);
		}

		if (Type.isDomNode(field.html))
		{
			field.input = field.html;
			field.html = field.html.innerHTML;
		}
		else
		{
			field.input = Tag.render`${field.html}`;
		}

		let label = '';
		let hint = '';
		if (field.hint)
		{
			hint = Tag.render`<span class="ui-ctl-after" data-hint="${Text.encode(field.hint)}"></span>`;
		}

		let title = '';
		if (field.title)
		{
			title = Tag.render`<div class="${this.classes.get('controlTitle')} ${field.required ? this.classes.get('controlRequired') : ''}">${Text.encode(field.title)}</div>`;
		}

		let subtextLink = '';
		if (field.subtextLinkOnClick && field.subtextLinkText)
		{
			subtextLink = Tag.render`<a onclick="${Text.encode(field.subtextLinkOnClick)}" class="${this.classes.get('subtextLink')}">${Text.encode(field.subtextLinkText)}</a>`;
		}

		let subtext = '';
		if (field.subtext)
		{
			subtext = Tag.render`<div class="${this.classes.get('subtextContainer')}">${Text.encode(field.subtext)} ${subtextLink}</div>`;
		}

		if (field.html.indexOf('type="checkbox"') > 0)
		{
			label = Tag.render`<label class="${this.classes.get('controlInner')} ${this.classes.get('controlCheckbox')}">${field.input}${field.title ? '<div class="' + this.classes.get('controlCheckboxLabel') + '">' + Text.encode(field.title) + '</div>' : ''}${hint}</label>`;
		}
		else if (field.type === 'file')
		{
			let hiddenFileInput = '';
			if (field.addHidden === true)
			{
				const hiddenFileField = {
					name: field.name,
					type: 'hidden',
					value: field.value,
				};
				hiddenFileInput = this.renderFieldInput(hiddenFileField);
			}
			label = Tag.render`
				${title}
				<label class="${this.classes.get('controlInner')} ${this.classes.get('controlFile')}">
				${field.input}
				${field.label ? '<div class="ui-ctl-label-text">' + Text.encode(field.label) + '</div>' : ''}
				</label>
				<span></span>
				${hiddenFileInput}
				${subtext}
			`;
		}
		else if (field.type === 'list' || field.html.indexOf('select') > 0)
		{
			label = Tag.render`
				${title}
				<div class="${this.classes.get('controlSelect')} ${this.classes.get('controlInner')}">
					<div class="${this.classes.get('controlSelectIcon')}"></div>
					${field.input}
				</div>
				${subtext}
			`;
		}
		else
		{
			label = Tag.render`
				${title}
				<div class="${this.classes.get('controlInner')}${hint ? ' ' + this.classes.get('controlAfterIcon') : ''}">
					${field.input}${hint}
				</div>
				${subtext}
			`;
		}

		result = Tag.render`<div class="${this.classes.get('controlContainer')}">${label}</div>`;

		return result;
	}

	/**
	 * @param field
	 * @returns {string}
	 */
	renderFieldInput(field)
	{
		let result = '';
		let type = field.type;
		if(!type)
		{
			type = 'text';
		}

		let value = '';
		if(field.hasOwnProperty('value'))
		{
			value = Text.encode(field.value);
		}
		else if(this.data[field.name])
		{
			value = Text.encode(this.data[field.name]);
		}

		let required = '';
		if(field.required === true)
		{
			required = ' required="required"';
		}

		let attribute = '';
		if (field.attribute && Type.isArray(field.attribute))
		{
			attribute = field.attribute.join(' ');
		}

		if(type === 'text')
		{
			result = `<input name="${field.name}"
				class="${this.classes.get('controlInput')}"
				value="${value}"${required}
				type="text"
				${attribute}>`
			;
		}
		else if(type === 'boolean')
		{
			value = 'Y';
			result =`<input type="checkbox" name="${Text.encode(field.name)}"${this.data[field.name] === value ? ' checked="checked"' : ''}${field.disabled ? ' disabled="disabled"' : ''}${required}
				value="${value}" class="${this.classes.get('controlInput')}">`;
		}
		else if(type === 'list')
		{
			result = `<select class="${this.classes.get('controlInput')}" name="${Text.encode(field.name)}"${required}>`;
			if(field.data && Type.isArray(field.data.items))
			{
				field.data.items.forEach((item) =>
				{
					result += `<option${Type.isString(item.VALUE) ? ' value="' + Text.encode(item.VALUE) + '"' : ''}${item.SELECTED ? ' selected="selected"' : ''}>${Text.encode(item.NAME)}</option>`;
				});
			}
			result += `</select>`;
		}
		else if(type === 'hidden')
		{
			result = `<input name="${Text.encode(field.name)}"
				value="${value}"
				type="hidden">`
			;
		}
		else if(type === 'file')
		{
			const onFileChange = ({target}) =>
			{
				const value = target.value.split(/(\\|\/)/g).pop();
				target.parentNode.nextSibling.innerText = Text.encode(value);
			};
			result = Tag.render`<input 
				onchange="${onFileChange}" 
				name="${Text.encode(field.name)}"
				value=""
				class="${this.classes.get('controlInput')}"
				type="file">`
			;
		}

		return result;
	}

	/**
	 * @param section
	 * @returns {HTMLElement}
	 */
	renderSection(section)
	{
		let result = null;
		if(!Type.isObject(section))
		{
			return result;
		}

		if(!Type.isArray(section.elements))
		{
			section.elements = [];
		}

		let sectionId = '';
		if(section.name)
		{
			sectionId = ' id="' + this.id + '-' + section.name + '"';
		}

		result = `<div${sectionId} class="${this.classes.get('sectionContainer')}">`;
		if(section.title)
		{
			result += `<div class="${this.classes.get('sectionTitle')}">${Text.encode(section.title)}</div><hr class="ui-hr ui-mb-15">`;
		}
		result += `</div>`;

		result = Tag.render`${result}`;

		section.elements.forEach((element) =>
		{
			if(Type.isObject(element) && element.name)
			{
				let field = Form.getByName(this.fields, element.name);
				if(field)
				{
					result.appendChild(this.renderField(field));
				}
			}
		});

		return result;
	}

	/**
	 * @param container
	 */
	setContainer(container)
	{
		if(Type.isDomNode(container))
		{
			this.container = container;
		}
	}

	/**
	 * @returns {HTMLElement}
	 */
	getContainer()
	{
		let container = this.container;
		if(!container)
		{
			container = document;
		}

		return container;
	}

	/**
	 * @param field
	 * @returns {Element | null}
	 */
	getFieldInput(field)
	{
		if(!field.input)
		{
			let container = this.getContainer();
			field.input = container.querySelector('[name="' + field.name + '"]');
		}

		return field.input;
	}

	/**
	 * @returns {Object}
	 */
	getData()
	{
		let result = {};
		var container = this.getContainer();
		if(container.nodeName === 'FORM')
		{
			return new FormData(container);
		}
		this.fields.forEach((field) =>
		{
			let input = this.getFieldInput(field);
			if(Type.isDomNode(input))
			{
				if(input.getAttribute('type') === 'checkbox')
				{
					if(input.checked)
					{
						result[field.name] = Text.decode(input.value);
					}
				}
				else
				{
					result[field.name] = Text.decode(input.value);
				}
			}
		});

		return result;
	}
}

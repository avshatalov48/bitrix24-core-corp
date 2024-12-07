"use strict";

/**
 * @requires module:iterators
 * @module form
 */

/**
 *
 * @type {{BUTTON: string, INPUT: string, CHECK: string, SWITCH: string, SELECTOR: string, MULTICHECK: string, DATE: string}}
 */
var FormItemType = {
	BUTTON: 'button',
	INPUT: 'input',
	CHECK: 'check',
	SWITCH: 'switch',
	SELECTOR: 'selector',
	MULTICHECK: 'multicheck',
	DATE: 'date',
};

/**
 *
 * @type {{DATE: string, TIME: string, DATETIME: string}}
 */
var FormItemDateType = {
	DATE: "date",
	TIME: "time",
	DATETIME: "datetime",
};

(function(){

	this.Form = class Form
	{
		/**
		 * @param {String} id
		 * @param {String} title
		 * @param {Iterable.<FormItem>|null} [items]
		 * @param {Iterable.<FormSection>|null} [sections]
		 */
		static create(id, title, items = null, sections = null)
		{
			return new this(id, title, items, sections);
		}

		/**
		 * @param {String} id
		 * @param {String} title
		 * @param {Iterable.<FormItem>|null} [items]
		 * @param {Iterable.<FormSection>|null} [sections]
		 */
		constructor(id, title, items = null, sections = null)
		{
			const variables = { id, title };
			for (const name in variables)
			{
				if (typeof variables[name] !== 'string')
				{
					console.error(`%cForm.constructor: field '%c${name}%c' is not a string (%c${typeof variables[name]}%c)`, "color: black;", "font-weight: bold; color: red", "color: black", "font-weight: bold; color: red", "color: black");
				}
			}

			this.id = id;
			this.title = title;

			this.items = {
				[Symbol.iterator]() { return new ObjectIterator(this); },
			};
			this.addItems(items);

			this.sections = {
				[Symbol.iterator]() { return new ObjectIterator(this); },
			};
			this.addSections(sections);
		}

		/**
		 *
		 * @param {string} text
		 * @returns {Form}
		 */
		setTitle(text = '')
		{
			if (typeof text !== 'string')
			{
				console.warn(`%cForm.setTitle: text is not a string, action skipped. (%c${typeof text}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black');

				return this;
			}

			this.title = text;

			return this;
		}

		/**
		 * @param {FormItem} item
		 * @returns {Form}
		 */
		addItem(item)
		{
			if (!(item instanceof FormItem))
			{
				console.warn('%cForm.addItem: item is not a %cFormItem%c instance, action skipped.', 'color: black;', 'font-weight: bold;', 'color: black;', item);

				return this;
			}

			this.items[item.id] = item;

			return this;
		}

		/**
		 * @param {Iterable.<FormItem>} items
		 * @returns {Form}
		 */
		addItems(items)
		{
			if (!items)
			{
				return this;
			}

			if (!(Symbol.iterator in Object(items)))
			{
				console.warn('%cForm.addItems: items is not iterable, action skipped.', 'color: black;', items);

				return this;
			}

			items.forEach((item) => {
				if (!(item instanceof FormItem))
				{
					console.warn('%cForm.addItems: item is not a %cFormItem%c instance, action skipped.', 'color: black;', 'font-weight: bold;', 'color: black;', item);

					return false;
				}

				this.items[item.id] = item;
			});

			return this;
		}

		/**
		 *
		 * @param {String} id
		 * @returns {boolean}
		 */
		removeItem(id)
		{
			delete this.items[id];

			return true;
		}

		/**
		 *
		 * @param {String} id
		 * @returns {FormItem}
		 */
		getItem(id)
		{
			if (!this.items[id])
			{
				console.info(`%cForm.getItem: item (%c${id}%c) not found.`, 'color: black;', 'font-weight: bold', 'color: black');

				return false;
			}

			return this.items[id];
		}

		/**
		 *
		 * @param {FormSection} section
		 * @returns {Form}
		 */
		addSection(section)
		{
			if (!section)
			{
				return this;
			}

			if (!(section instanceof FormSection))
			{
				console.warn('%cForm.addSection: section is not a %cFormSection%c instance, action skipped.', 'color: black;', 'font-weight: bold;', 'color: black;', section);

				return this;
			}

			for (const item of section.items)
			{
				this.items[item.id] = item;
			}

			delete section.items;

			this.sections[section.id] = section;

			return this;
		}

		/**
		 *
		 * @param {Iterable.<FormSection>} sections
		 * @returns {Form}
		 */
		addSections(sections)
		{
			if (!sections)
			{
				return this;
			}

			if (!(Symbol.iterator in Object(sections)))
			{
				console.warn('%cForm.addSections: sections is not iterable, action skipped.', 'color: black;', sections);

				return this;
			}

			sections.forEach((section) => {
				if (!(section instanceof FormSection))
				{
					console.warn('%cForm.addSections: section is not a %cFormSection%c instance, action skipped.', 'color: black;', 'font-weight: bold;', 'color: black;', section);

					return false;
				}

				for (const item of section.items)
				{
					this.items[item.id] = item;
				}
				delete section.items;

				this.sections[section.id] = section;

				return true;
			});

			return this;
		}

		/**
		 *
		 * @param {string} id
		 * @returns {boolean}
		 */
		removeSection(id)
		{
			delete this.sections[id];

			return true;
		}

		/**
		 *
		 * @param {string} id
		 * @returns {FormSection}
		 */
		getSection(id = 'main')
		{
			if (!this.sections[id])
			{
				console.info(`%cForm.getSection: item (%c${id}%c) not found.`, 'color: black;', 'font-weight: bold', 'color: black');

				return false;
			}

			return this.sections[id];
		}

		/**
		 *
		 * @returns {string}
		 */
		getId()
		{
			return this.id;
		}

		/**
		 *
		 * @returns {boolean}
		 */
		hasItems()
		{
			let result = false;
			for (const item of this.items)
			{
				result = true;
			}

			return result;
		}

		/**
		 *
		 * @returns {object|false}
		 */
		compile()
		{
			const items = [];
			const sections = [];

			for (const item of this.items)
			{
				if (!this.sections[item.sectionCode])
				{
					this.sections[item.sectionCode] = new FormSection(item.sectionCode);
				}
				items.push(item.compile());
			}

			if (items.length <= 0)
			{
				console.info(`%cForm.compile: form (%c${this.id + (this.title ? ` - ${this.title}` : '')}%c) compiled with empty items.`, 'color: black;', 'font-weight: bold', 'color: black');
			}

			for (const item of this.sections)
			{
				const section = item.compile();
				delete section.items;
				sections.push(section);
			}

			return {
				id: this.id,
				title: this.title,
				items,
				sections,
			};
		}
	};

	this.FormItem = class FormItem
	{
		/**
		 *
		 * @param {string} id
		 * @param {FormItemType} type
		 * @param {string} [title]
		 * @param {string} [subtitle]
		 * @param {string} [sectionCode]
		 */
		static create(id, type, title, subtitle = '', sectionCode = 'main')
		{
			return new this(id, type, title, subtitle, sectionCode);
		}

		/**
		 *
		 * @param {string} id
		 * @param {FormItemType} type
		 * @param {string} [title]
		 * @param {string} [subtitle]
		 * @param {string} [sectionCode]
		 */
		constructor(id, type, title, subtitle = '', sectionCode = 'main')
		{
			const variables = { id, type, title, subtitle, sectionCode };
			for (const name in variables)
			{
				if (typeof variables[name] !== 'string')
				{
					console.error(`%cFormItem.constructor: field '%c${name}%c' is not a string (%c${typeof variables[name]}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black', 'font-weight: bold; color: red', 'color: black');
				}
			}

			this.id = id;
			this.type = type;
			this.title = title;
			this.subtitle = subtitle;
			this.sectionCode = sectionCode;

			this.testId = null;
			this.value = null;
			this.enabled = null;
			this.imageUrl = null;

			this.params = {};
		}

		/**
		 *
		 * @returns {string}
		 */
		getId()
		{
			return this.id;
		}

		/**
		 *
		 * @param {string} testId
		 * @returns {FormItem}
		 */
		setTestId(testId = '')
		{
			if (typeof testId !== 'string')
			{
				console.warn(`%cForm.setTestId: testId is not a string, action skipped. (%c${typeof testId}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black');

				return this;
			}

			this.testId = testId;

			return this;
		}

		/**
		 *
		 * @param {string} text
		 * @returns {FormItem}
		 */
		setTitle(text = '')
		{
			if (typeof text !== 'string')
			{
				console.warn(`%cFormItem.setTitle: text is not a string, action skipped. (%c${typeof text}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black');

				return this;
			}

			this.title = text;

			return this;
		}

		/**
		 *
		 * @param {string} text
		 * @returns {FormItem}
		 */
		setSubTitle(text = '')
		{
			if (typeof text != 'string')
			{
				console.warn(`%cFormItem.setSubTitle: text is not a string, action skipped. (%c${typeof text}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black');
				return this;
			}

			this.subtitle = text;

			return this;
		}

		/**
		 *
		 * @param {string} code
		 * @returns {FormItem}
		 */
		setSectionCode(code = 'main')
		{
			if (typeof code != 'string')
			{
				console.warn(`%cFormItem.setSectionCode: code is not a string, action skipped. (%c${typeof code}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black');
				return this;
			}

			this.sectionCode = code;

			return this;
		}

		/**
		 *
		 * @param {boolean} value
		 * @returns {FormItem}
		 */
		setEnabled(value = true)
		{
			this.enabled = value === true;

			return this;
		}

		/**
		 *
		 * @param {string} url
		 * @returns {FormItem}
		 */
		setImageUrl(url = '')
		{
			if (typeof url !== 'string')
			{
				console.warn(`%cFormItem.setImageUrl: url is not a string, action skipped. (%c${typeof url}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black');

				return this;
			}

			this.imageUrl = url;

			return this;
		}

		/**
		 *
		 * @param {string} name
		 * @param {*} value
		 * @returns {FormItem}
		 */
		setCustomParam(name, value)
		{
			if (!name || typeof name !== 'string')
			{
				console.warn(`%cFormItem.setCustomParam: name is not a string, action skipped. (%c${typeof name}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black');

				return this;
			}

			this.params[name] = value;

			return this;
		}

		/**
		 *
		 * @param {boolean|string} value
		 * @returns {FormItem}
		 */
		setDefaultValue(value = '')
		{
			if (
				this.type == FormItemType.CHECK
				|| this.type == FormItemType.SWITCH
			)
			{
				this.value = value !== false;
			}
			else
			{
				this.value = value.toString();
			}

			return this;
		}

		/**
		 *
		 * @param {boolean|array|string} value
		 * @returns {FormItem}
		 */
		setValue(value = '')
		{
			if (
				this.type == FormItemType.CHECK
				|| this.type == FormItemType.SWITCH
			)
			{
				this.value = value !== false;
			}
			else if (
				this.type == FormItemType.MULTICHECK
				|| this.type == FormItemType.SWITCH
			)
			{
				this.value = value && Symbol.iterator in Object(value)? value: [];
			}
			else
			{
				this.value = value.toString();
			}

			return this;
		}

		/**
		 *
		 * @param {boolean} value
		 * @returns {FormItem}
		 */
		setButtonTransition(value = true)
		{
			if (this.type != FormItemType.BUTTON)
			{
				console.warn(`%cFormItem.setButtonTransition: action not permitted to current item type (%c${this.type}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black');

				return this;
			}

			this.params.useTransition = value === true;

			return this;
		}

		/**
		 *
		 * @param {string} value
		 * @returns {FormItem}
		 */
		setInputHint(value = '')
		{
			if (this.type != FormItemType.INPUT)
			{
				console.warn(`%cFormItem.setInputHint: action not permitted to current item type (%c${this.type}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black');

				return this;
			}

			if (typeof value !== 'string')
			{
				console.warn(`%cFormItem.setInputHint: value is not a string, action skipped. (%c${typeof value}%c)`, 'color: black;', 'font-weight: bold; color: red', 'color: black');

				return this;
			}

			this.params.hint = value;

			return this;
		}

		/**
		 *
		 * @param {Array} value
		 * @returns {FormItem}
		 */
		setSelectorItems(value)
		{
			if (!(
				this.type == FormItemType.MULTICHECK
				|| this.type == FormItemType.SELECTOR
			))
			{
				console.warn(`%cFormItem.setSelectorItems: action not permitted to current item type (%c${this.type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");

				return this;
			}

			this.params.items = value && Symbol.iterator in Object(value) ? value : [];

			return this;
		}

		/**
		 *
		 * @param {Array} value
		 * @returns {FormItem}
		 */
		setMulticheckItems(value)
		{
			return this.setSelectorItems(value);
		}

		/**
		 *
		 * @param {FormItemDateType} type
		 * @returns {FormItem}
		 */
		setDateType(type)
		{
			if (this.type != FormItemType.DATE)
			{
				console.warn(`%cFormItem.setDateType: action not permitted to current item type (%c${this.type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			if (typeof type != 'string')
			{
				console.warn(`%cFormItem.setDateType: type is not a string, action skipped. (%c${typeof type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.params.type = type;

			return this;
		}

		/**
		 *
		 * @param {string} format
		 * @returns {FormItem}
		 */
		setDateFormat(format)
		{
			if (this.type != FormItemType.DATE)
			{
				console.warn(`%cFormItem.setDateFormat: action not permitted to current item type (%c${this.type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			if (typeof format != 'string')
			{
				console.warn(`%cFormItem.setDateFormat: format is not a string, action skipped. (%c${typeof format}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.params.format = format;

			return this;
		}

		/**
		 *
		 * @param {string} value
		 * @returns {FormItem}
		 */
		setDateMinDate(value)
		{
			if (this.type != FormItemType.DATE)
			{
				console.warn(`%cFormItem.setDateMinDate: action not permitted to current item type (%c${this.type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			if (typeof value != 'string')
			{
				console.warn(`%cFormItem.setDateMinDate: value is not a string, action skipped. (%c${typeof value}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.params.min_date = value;

			return this;
		}

		/**
		 *
		 * @param {string} value
		 * @returns {FormItem}
		 */
		setDateMaxDate(value)
		{
			if (this.type != FormItemType.DATE)
			{
				console.warn(`%cFormItem.setDateMinDate: action not permitted to current item type (%c${this.type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			if (typeof value != 'string')
			{
				console.warn(`%cFormItem.setDateMinDate: value is not a string, action skipped. (%c${typeof value}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.params.max_date = value;

			return this;
		}

		/**
		 *
		 * @returns {Object|false}
		 */
		compile()
		{
			let result = {
				id: this.id,
				type: this.type,
				title: this.title,
				subtitle: this.subtitle,
				sectionCode: this.sectionCode,
				enabled: this.enabled,
				params: {}
			};

			['value', 'enabled', 'imageUrl', 'testId'].forEach((code) =>
			{
				if (this[code] !== null)
				{
					result[code] = this[code];
				}
			});

			for (let code in this.params)
			{
				if (
					this.params.hasOwnProperty(code)
					&& this.params[code] !== null
				)
				{
					result.params[code] = this.params[code];
				}
			}

			return result;
		}
	};

	this.FormSection = class FormSection
	{
		/**
		 *
		 * @param id
		 * @param [title]
		 * @param [footer]
		 * @param {Iterable.<FormItem>|null} [items]
		 */
		static create(id = 'main', title = '', footer = '', items = null)
		{
			return new this(id, title, footer, items);
		}

		/**
		 *
		 * @param id
		 * @param [title]
		 * @param [footer]
		 * @param {Iterable.<FormItem>|null} [items]
		 */
		constructor(id = 'main', title = '', footer = '', items = null)
		{
			let variables = {id, title, footer};
			for (let name in variables)
			{
				if (typeof variables[name] != 'string')
				{
					console.error(`%cFormSection.constructor: field '%c${name}%c' is not a string (%c${typeof variables[name]}%c)`, "color: black;", "font-weight: bold; color: red", "color: black", "font-weight: bold; color: red", "color: black");
				}
			}

			this.id = id;
			this.title = title;
			this.footer = footer;
			this.items = {
				[Symbol.iterator]() { return new ObjectIterator(this); }
			};

			this.addItems(items);
		}

		/**
		 *
		 * @returns {string}
		 */
		getId()
		{
			return this.id;
		}

		/**
		 * @param {FormItem} item
		 */
		addItem(item)
		{
			if (!(item instanceof FormItem))
			{
				console.warn(`%cFormSection.addItem: item is not a %cFormItem%c instance, action skipped.`, "color: black;", "font-weight: bold;", "color: black;", item);
				return this;
			}

			item.sectionCode = this.id;
			this.items[item.id] = item;

			return this;
		}

		/**
		 * @param {Iterable.<FormItem>} items
		 */
		addItems(items)
		{
			if (!items)
			{
				return this;
			}

			if (!(Symbol.iterator in Object(items)))
			{
				console.warn('%cFormSection.addItems: items is not iterable, action skipped.', "color: black;", items);
				return this;
			}

			items.forEach((item) => {
				if (!(item instanceof FormItem))
				{
					console.warn(`%cFormSection.addItems: item is not a %cFormItem%c instance, action skipped.`, "color: black;", "font-weight: bold;", "color: black;", item);
					return false;
				}

				item.sectionCode = this.id;
				this.items[item.id] = item;
			});

			return this;
		}

		/**
		 *
		 * @returns {FormSection}
		 */
		removeItems()
		{
			this.items = {
				[Symbol.iterator]() { return new ObjectIterator(this); }
			};
			return this;
		}

		/**
		 *
		 * @param {string} text
		 * @returns {FormSection}
		 */
		setTitle(text = '')
		{
			if (typeof text != 'string')
			{
				console.warn(`%cFormSection.setTitle: text is not a string, action skipped. (%c${typeof text}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.title = text;

			return this;
		}

		/**
		 *
		 * @param {string} text
		 * @returns {FormSection}
		 */
		setFooter(text = '')
		{
			if (typeof text != 'string')
			{
				console.warn(`%cFormSection.setFooter: text is not a string, action skipped. (%c${typeof text}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.footer = text;

			return this;
		}

		/**
		 *
		 * @returns {object|false}
		 */
		compile()
		{
			return {
				id: this.id,
				title: this.title,
				footer: this.footer,
				items: this.items
			}
		}
	};

})();
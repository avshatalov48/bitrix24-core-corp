/**
* @bxjs_lang_path component.php
*/

var WidgetListItemType = {
	default: null,
	button: 'button',
	info: 'info',
	loading: 'loading',
	destruct: 'destruct',
	carousel: 'carousel',
};

var WidgetListFontStyle = {
	normal: 'normal',
	bold: 'bold',
	semibold: 'semibold',
	italic: 'italic',
};

var WidgetListAnimationType = {
	bubbles: 'bubbles',
};

(function()
{
	this.WidgetListItem = class WidgetListItem
	{
		/**
		 *
		 * @param {string} id
		 * @param {string} [title]
		 * @param {string} [sectionCode]
		 * @param {WidgetListItemType} [type]
		 *
		 * @returns {WidgetListItem}
		 */
		static create(id, title = '', sectionCode = 'main', type = null)
		{
			return new this(id, title, sectionCode, type);
		}

		/**
		 *
		 * @param {string} id
		 * @param {string} [title]
		 * @param {string} [sectionCode]
		 * @param {WidgetListItemType} [type]
		 */
		constructor(id, title = '', sectionCode = 'main', type = null)
		{
			this.className = 'WidgetListItemType';

			let variables = {id, type, title, sectionCode};
			for (let name in variables)
			{
				if (name === 'type' && variables[name] === null)
				{
					continue;
				}

				if (typeof variables[name] !== 'string')
				{
					console.error(`%cconstructor: field '%c${name}%c' is not a string (%c${typeof variables[name]}%c)`, "color: black;", "font-weight: bold; color: red", "color: black", "font-weight: bold; color: red", "color: black");
				}
			}

			this.id = id;
			this.type = null;
			this.title = title;
			this.subtitle = null;
			this.sectionCode = sectionCode;

			this.imageUrl = null;
			this.useLetterImage = null;
			this.height = null;
			this.useEstimatedHeight = null;
			this.styles = null;
			this.backgroundColor = null;
			this.color = null;
			this.unselectable = null;
			this.actions = null;
			this.sortValues = null;
			this.params = null;
			this.childItems = null;

			this.skipCallback = null;

			this.setType(type);
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
		 * @param {Function|boolean} condition
		 * @returns {WidgetListItem}
		 */
		skip(condition)
		{
			if (typeof condition === 'function')
			{
				this.skipCallback = condition;
			}
			else
			{
				this.skipCallback = () => condition === true;
			}

			return this;
		}

		/**
		 *
		 * @param {string} text
		 * @returns {WidgetListItem}
		 */
		setTitle(text = '')
		{
			if (typeof text !== 'string')
			{
				console.warn(`%c${this.className}.setTitle: text is not a string, action skipped. (%c${typeof text}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.title = text;

			return this;
		}

		/**
		 *
		 * @param {string} text
		 * @returns {WidgetListItem}
		 */
		setSubTitle(text = '')
		{
			if (typeof text !== 'string')
			{
				console.warn(`%c${this.className}.setSubTitle: text is not a string, action skipped. (%c${typeof text}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.subtitle = text;

			return this;
		}

		/**
		 *
		 * @param {WidgetListItemType} type
		 * @returns {WidgetListItem}
		 */
		setType(type)
		{
			if (!type)
			{
				this.type = null;
				return this;
			}

			if (typeof WidgetListItemType[type] === 'undefined')
			{
				console.warn(`%c${this.className}.setType: type is not supported (%c${type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.type = type;

			return this;
		}

		/**
		 *
		 * @param {string} code
		 * @returns {WidgetListItem}
		 */
		setSectionCode(code = 'main')
		{
			if (typeof code !== 'string')
			{
				console.warn(`%c${this.className}.setSectionCode: code is not a string, action skipped. (%c${typeof code}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.sectionCode = code;

			return this;
		}

		/**
		 *
		 * @param {boolean} [flag]
		 * @returns {WidgetListItem}
		 */
		setDisabled(flag = true)
		{
			this.unselectable = flag === true;

			return this;
		}

		/**
		 *
		 * @param {string} url
		 * @param {string} color
		 * @returns {WidgetListItem}
		 */
		setImageUrl(url = '', color = null)
		{
			if (typeof url !== 'string')
			{
				console.warn(`%c${this.className}.setImageUrl: url is not a string, action skipped. (%c${typeof url}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			if (color)
			{
				this.setImageBackgroundColor(color);
			}

			this.imageUrl = url;

			return this;
		}

		/**
		 *
		 * @param {boolean} [flag]
		 * @param {string} [color]
		 * @returns {WidgetListItem}
		 */
		setImageLetter(flag = true, color = null)
		{
			this.useLetterImage = flag !== false;

			if (color)
			{
				this.setImageBackgroundColor(color);
			}

			return this;
		}

		/**
		 *
		 * @param {string} color
		 * @returns {WidgetListItem}
		 */
		setImageBackgroundColor(color)
		{
			if (!(/^#([A-Fa-f0-9]{8}|[A-Fa-f0-9]{6})$/.test(color)))
			{
				console.warn(`%c${this.className}.setImageBackgroundColor: color is not a hex (rgb or argb), action skipped. (%c${color}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.color = color;

			return this;
		}

		/**
		 *
		 * @param {number} height
		 * @returns {WidgetListItem}
		 */
		setHeight(height)
		{
			if (Application.getApiVersion() <= 27)
			{
				return this;
			}

			if (typeof height !== 'number')
			{
				console.warn(`%c${this.className}.setHeight: height is not a number, action skipped. (%c${typeof height}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.height = height;

			return this;
		}

		/**
		 *
		 * @param {boolean} flag
		 * @returns {WidgetListItem}
		 */
		setUseEstimatedHeight(flag = true)
		{
			this.useEstimatedHeight = flag !== false;

			return this;
		}

		/**
		 *
		 * @param {object} style
		 * @returns {WidgetListItem}
		 */
		setStyles(style)
		{
			if (style instanceof WidgetListStyle)
			{
				this.styles = {title: style.compile()};
				return this;
			}

			let styles = {};
			for (let id in style)
			{
				if (!style.hasOwnProperty(id))
				{
					continue;
				}

				if (style[id] instanceof WidgetListStyle)
				{
					styles[id] = style[id].compile();
				}
				else if (typeof style[id] === 'object' && style[id])
				{
					styles[id] = style[id];
				}
			}

			this.styles = styles;

			return this;
		}

		/**
		 *
		 * @param {object} params
		 * @returns {WidgetListItem}
		 */
		setActions(params)
		{
			let actions = {};
			for (let id in params)
			{
				if (!params.hasOwnProperty(id))
				{
					continue;
				}

				if (params[id] instanceof WidgetListItemAction)
				{
					actions[id] = params[id].compile();
				}
				else if (typeof params[id] === 'object' && params[id])
				{
					actions[id] = params[id];
				}
			}

			this.actions = actions;

			return this;
		}

		/**
		 *
		 * @param {object }params
		 * @returns {WidgetListItem}
		 */
		sortValues(params)
		{
			let sortValues = {};
			for (let field in params)
			{
				if (!params.hasOwnProperty(field))
				{
					continue;
				}

				sortValues[field] = params[field];
			}

			this.sortValues = sortValues;

			return this;
		}

		/**
		 *
		 * @param {string} color
		 * @returns {WidgetListItem}
		 */
		setBackgroundColor(color)
		{
			if (!(/^#([A-Fa-f0-9]{8}|[A-Fa-f0-9]{6})$/.test(color)))
			{
				console.warn(`%c${this.className}.setBackgroundColor: color is not a hex (rgb or argb), action skipped. (%c${color}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.backgroundColor = color;

			return this;
		}

		/**
		 *
		 * @param {string} name
		 * @param {*} value
		 * @returns {WidgetListItem}
		 */
		addCustomParam(name, value)
		{
			if (!name || typeof name !== 'string')
			{
				console.warn(`%c${this.className}.setCustomParam: name is not a string, action skipped. (%c${typeof name}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			if (value )

			if (!this.params)
			{
				this.params = {};
			}

			this.params[name] = value;

			return this;
		}

		/**
		 *
		 * @param name
		 * @returns {WidgetListItem}
		 */
		removeCustomParam(name)
		{
			if (!name || typeof name !== 'string')
			{
				console.warn(`%c${this.className}.removeCustomParam: name is not a string, action skipped. (%c${typeof name}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			delete this.params[name];

			return this;
		}

		/**
		 *
		 * @param {object} params
		 * @returns {WidgetListItem}
		 */
		setCustomParams(params)
		{
			if (!params || typeof params !== 'object')
			{
				console.warn(`%c${this.className}.setCustomParams: params is not a object, action skipped. (%c${typeof name}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.params = params;

			return this;
		}

		/**
		 * @param {WidgetListItem} item
		 * @returns {WidgetListItem}
		 */
		addChildItem(item)
		{
			if (!this.childItems)
			{
				this.childItems = {
					[Symbol.iterator]() { return new ObjectIterator(this); }
				};
			}

			if (item instanceof WidgetListItem)
			{
				this.childItems[item.id] = item.compile();
			}
			else
			{
				this.childItems[item.id] = item;
			}

			return this;
		}


		removeChildItem(id)
		{
			if (!id || typeof id !== 'string')
			{
				console.warn(`%c${this.className}.removeCustomParam: name is not a string, action skipped. (%c${typeof name}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			if (this.childItems)
			{
				delete this.childItems[id];
			}


			return this;
		}

		/**
		 * @param {Iterable.<WidgetListItem>} items
		 * @returns {WidgetListItem}
		 */
		setChildItems(items)
		{
			if (!items)
			{
				return this;
			}

			if (!(Symbol.iterator in Object(items)))
			{
				console.warn('%c${this.className}.setChildItems: items is not iterable, action skipped.', "color: black;", items);
				return this;
			}

			if (!this.childItems)
			{
				this.childItems = {
					[Symbol.iterator]() { return new ObjectIterator(this); }
				};
			}

			items.forEach((item) =>
			{
				if (item instanceof WidgetListItem)
				{
					this.items[item.id] = item.compile();
				}
				else
				{
					this.items[item.id] = item;
				}
			});

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
				sectionCode: this.sectionCode
			};

			[
				'imageUrl',
				'useLetterImage',
				'imageUrl',
				'height',
				'useEstimatedHeight',
				'styles',
				'backgroundColor',
				'color',
				'unselectable',
				'actions',
				'sortValues',
				'params',
				'childItems',

			].forEach((code) =>
			{
				if (this[code] !== null)
				{
					result[code] = this[code];
				}
			});

			return result;
		}
	};

	this.WidgetListItemAction = class WidgetListItemAction
	{
		/**
		 *
		 * @param {String} id
		 * @param {String} icon
		 * @param {String} [color]
		 * @param {String} [title]
		 *
		 * @returns {WidgetListItemAction}
		 */
		static create(id, icon, color = '', title = '')
		{
			return new this(id, icon, color, title);
		}

		/**
		 *
		 * @param {String} id
		 * @param {String} icon
		 * @param {String} [color]
		 * @param {String} [title]
		 */
		constructor(id, icon, color = '', title = '')
		{
			this.className = 'WidgetListItemAction';

			let variables = {id, icon, color};
			for (let name in variables)
			{
				if (typeof variables[name] !== 'string')
				{
					console.error(`%cconstructor: field '%c${name}%c' is not a string (%c${typeof variables[name]}%c)`, "color: black;", "font-weight: bold; color: red", "color: black", "font-weight: bold; color: red", "color: black");
				}
			}

			this.identifier = id;
			this.iconName = icon;
			this.color = null;
			this.title = null;

			if (color)
			{
				this.setColor(color);
			}
			if (title)
			{
				this.setTitle(title);
			}
		}

		/**
		 *
		 * @returns {string}
		 */
		getId()
		{
			return this.identifier;
		}

		/**
		 *
		 * @param {string} text
		 * @returns {WidgetListItemAction}
		 */
		setTitle(text = '')
		{
			if (typeof text !== 'string')
			{
				console.warn(`%c${this.className}.setTitle: text is not a string, action skipped. (%c${typeof text}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.title = text;

			return this;
		}

		/**
		 *
		 * @param {string} color
		 * @returns {WidgetListItemAction}
		 */
		setColor(color)
		{
			if (!(/^#([A-Fa-f0-9]{8}|[A-Fa-f0-9]{6})$/.test(color)))
			{
				console.warn(`%c${this.className}.setBackgroundColor: color is not a hex (rgb or argb), action skipped. (%c${color}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.backgroundColor = color;

			return this;
		}

		/**
		 *
		 * @returns {object}
		 */
		compile()
		{

			let result = {
				identifier: this.identifier,
				iconName: this.iconName,
			};

			let properties = [
				'color',
				'title',
			];

			for (let property in properties)
			{
				if (this[property] !== null)
				{
					result[property] = this[property];
				}
			}

			return result;
		}
	};

	this.WidgetListItemAnimation = class WidgetListItemAnimation
	{
		/**
		 *
		 * @returns {WidgetListItemAnimation}
		 */
		static create()
		{
			return new this();
		}

		constructor()
		{
			this.className = 'WidgetListItemAnimation';

			this.color = null;
			this.type = null;
		}

		/**
		 *
		 * @param {string} color
		 * @returns {WidgetListItemAnimation}
		 */
		setColor(color)
		{
			if (!(/^#([A-Fa-f0-9]{8}|[A-Fa-f0-9]{6})$/.test(color)))
			{
				console.warn(`%WidgetListItemFont.setColor: color is not a hex (rgb or argb), action skipped. (%c${color}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.color = color;

			return this;
		}

		/**
		 *
		 * @param {WidgetListAnimationType} type
		 * @returns {WidgetListItemAnimation}
		 */
		setType(type)
		{
			if (typeof WidgetListAnimationType[type] === 'undefined')
			{
				console.warn(`%WidgetListItemAnimation.setType: type is not supported (%c${type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.type = type;

			return this;
		}

		/**
		 *
		 * @returns {object}
		 */
		compile()
		{

			let result = {};

			let properties = [
				'color',
				'type',
			];

			for (let property in properties)
			{
				if (this[property] !== null)
				{
					result[property] = this[property];
				}
			}

			return result;
		}
	};

	this.WidgetListItemImage = class WidgetListItemImage
	{
		/**
		 *
		 * @returns {WidgetListItemImage}
		 */
		static create()
		{
			return new this();
		}

		constructor()
		{
			this.className = 'WidgetListItemImage';

			this.name = null;
			this.url = null;
			this.sizeMultiplier = null;
		}

		/**
		 *
		 * @param {string} name
		 * @param {number} sizeMultiplier
		 * @returns {WidgetListItemImage}
		 */
		setIcon(name, sizeMultiplier = null)
		{
			if (typeof name !== 'string')
			{
				console.warn(`%WidgetListItemFont.setName: type is not supported (%c${type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.name = name;

			this.setSizeMultiplier(this.sizeMultiplier);

			return this;
		}

		/**
		 *
		 * @param {string} url
		 * @param {number} [sizeMultiplier]
		 * @returns {WidgetListItemImage}
		 */
		setImage(url, sizeMultiplier = null)
		{
			if (typeof url !== 'string')
			{
				console.warn(`%WidgetListItemFont.setName: type is not supported (%c${type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.url = url;

			this.setSizeMultiplier(this.sizeMultiplier);

			return this;
		}

		/**
		 *
		 * @param {number} size
		 * @returns {WidgetListItemImage}
		 */
		setSizeMultiplier(size)
		{
			if (typeof size === 'number')
			{
				this.sizeMultiplier = size;
			}

			return this;
		}

		/**
		 *
		 * @returns {object}
		 */
		compile()
		{

			let result = {};

			let properties = [
				'name',
				'url',
				'sizeMultiplier',
			];

			for (let property in properties)
			{
				if (this[property] !== null)
				{
					result[property] = this[property];
				}
			}

			return result;
		}
	};

	this.WidgetListStyle = class WidgetListStyle
	{
		/**
		 *
		 * @returns {WidgetListStyle}
		 */
		static create()
		{
			return new this();
		}

		constructor()
		{
			this.className = 'WidgetListStyle';

			this.color = null;
			this.image = null;
			this.additionalImage = null;
			this.animation = null;
			this.font = null;
		}

		/**
		 *
		 * @param {string} color
		 * @returns {WidgetListStyle}
		 */
		setColor(color)
		{
			if (!(/^#([A-Fa-f0-9]{8}|[A-Fa-f0-9]{6})$/.test(color)))
			{
				console.warn(`%WidgetListStyle.setColor: color is not a hex (rgb or argb), action skipped. (%c${color}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.color = color;

			return this;
		}

		/**
		 *
		 * @param {WidgetListItemImage} image
		 * @returns {WidgetListStyle}
		 */
		setImage(image)
		{
			if (image instanceof WidgetListItemImage)
			{
				this.image = image.compile();
			}
			else
			{
				this.image = image;
			}

			return this;
		}

		/**
		 *
		 * @param {WidgetListItemImage} image
		 * @returns {WidgetListStyle}
		 */
		additionalImage(image)
		{
			if (image instanceof WidgetListItemImage)
			{
				this.additionalImage = image.compile();
			}
			else
			{
				this.additionalImage = image;
			}

			return this;
		}

		/**
		 *
		 * @param {WidgetListItemAnimation} animation
		 * @returns {WidgetListStyle}
		 */
		setAnimation(animation)
		{
			if (animation instanceof WidgetListItemAnimation)
			{
				this.animation = animation.compile();
			}
			else
			{
				this.animation = animation;
			}

			return this;
		}

		/**
		 *
		 * @param {WidgetListItemFont} font
		 * @returns {WidgetListStyle}
		 */
		setFont(font)
		{
			if (Application.getApiVersion() <= 27)
			{
				return this;
			}

			if (font instanceof WidgetListItemFont)
			{
				this.font = font.compile();
			}
			else
			{
				this.font = font;
			}

			return this;
		}

		/**
		 *
		 * @returns {object}
		 */
		compile()
		{

			let result = {};

			[
				'color',
				'image',
				'additionalImage',
				'animation',
				'font',
			].forEach(property =>
			{
				if (this[property] !== null)
				{
					result[property] = this[property];
				}
			});

			return result;
		}
	};

	this.WidgetListItemFont = class WidgetListItemFont
	{
		/**
		 *
		 * @returns {WidgetListItemFont}
		 */
		static create()
		{
			return new this();
		}

		constructor()
		{
			this.className = 'WidgetListItemFont';

			this.fontStyle = null;
			this.size = null;
			this.color = null;
		}

		/**
		 *
		 * @param {WidgetListFontStyle} type
		 * @returns {WidgetListItemFont}
		 */
		setFontStyle(type)
		{
			if (typeof WidgetListFontStyle[type] === 'undefined')
			{
				console.warn(`%WidgetListItemFont.setType: type is not supported (%c${type}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.fontStyle = type;

			return this;
		}

		/**
		 *
		 * @param {Number} size
		 * @returns {WidgetListItemFont}
		 */
		setSize(size)
		{
			if (typeof size !== 'number')
			{
				console.warn(`%WidgetListItemFont.setSize: size is not number (%c${typeof size}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.size = size;

			return this;
		}

		/**
		 *
		 * @param {string} color
		 * @returns {WidgetListItemFont}
		 */
		setColor(color)
		{
			if (!(/^#([A-Fa-f0-9]{8}|[A-Fa-f0-9]{6})$/.test(color)))
			{
				console.warn(`%WidgetListItemFont.setColor: color is not a hex (rgb or argb), action skipped. (%c${color}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.color = color;

			return this;
		}

		/**
		 *
		 * @returns {object}
		 */
		compile()
		{
			let result = {};

			[
				'fontStyle',
				'size',
				'color',
			].forEach(property =>
			{
				if (this[property] !== null)
				{
					result[property] = this[property];
				}
			});

			return result;
		}
	};

	this.WidgetListSection = class WidgetListSection
	{
		/**
		 *
		 * @returns {WidgetListSection}
		 */
		static create(id = 'main', title = '')
		{
			return new this(id, title);
		}
		/**
		 *
		 * @param id
		 * @param [title]
		 */
		constructor(id = 'main', title = '')
		{
			this.className = 'WidgetListSection';

			let variables = {id, title};
			for (let name in variables)
			{
				if (typeof variables[name] !== 'string')
				{
					console.error(`%cconstructor: field '%c${name}%c' is not a string (%c${typeof variables[name]}%c)`, "color: black;", "font-weight: bold; color: red", "color: black", "font-weight: bold; color: red", "color: black");
				}
			}

			this.id = id;
			this.title = title;

			this.height = null;
			this.sortItemParams = null;
			this.backgroundColor = null;
			this.styles = null;
			this.foldable = null;
			this.folded = null;
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
		 * @param {string} text
		 * @returns {WidgetListSection}
		 */
		setTitle(text = '')
		{
			if (typeof text !== 'string')
			{
				console.warn(`%c${this.className}.setTitle: text is not a string, action skipped. (%c${typeof text}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.title = text;

			return this;
		}

		/**
		 *
		 * @param {number} height
		 * @returns {WidgetListSection}
		 */
		setHeight(height)
		{
			if (Application.getApiVersion() <= 27)
			{
				return this;
			}

			if (typeof height !== 'number')
			{
				console.warn(`%c${this.className}.setHeight: height is not a number, action skipped. (%c${typeof height}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.height = height;

			return this;
		}

		/**
		 *
		 * @param {object} order
		 * @returns {WidgetListSection}
		 */
		setSortItemsParams(order)
		{
			let sortItemParams = {};
			for (let field in order)
			{
				if (!order.hasOwnProperty(field))
				{
					continue;
				}

				sortItemParams[field] = order[field].toString().toLowerCase() === 'asc'? 'asc': 'desc';
			}

			this.sortItemParams = sortItemParams;

			return this;
		}

		/**
		 *
		 * @param {string} color
		 * @returns {WidgetListSection}
		 */
		setBackgroundColor(color)
		{
			if (!(/^#([A-Fa-f0-9]{8}|[A-Fa-f0-9]{6})$/.test(color)))
			{
				console.warn(`%c${this.className}.setBackgroundColor: color is not a hex (rgb or argb), action skipped. (%c${color}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.backgroundColor = color;

			return this;
		}

		/**
		 *
		 * @param {object} style
		 * @returns {WidgetListSection}
		 */
		setStyles(style)
		{
			if (style instanceof WidgetListStyle)
			{
				this.styles = {title: style};
				return this;
			}

			let styles = {};
			for (let id in params)
			{
				if (!params.hasOwnProperty(id))
				{
					continue;
				}

				if (params[id] instanceof WidgetListStyle)
				{
					styles[id] = params[id].compile();
				}
				else if (typeof params[id] === 'object' && params[id])
				{
					styles[id] = params[id];
				}
			}

			this.styles = styles;

			return this;
		}

		/**
		 *
		 * @param {boolean} flag
		 * @returns {WidgetListSection}
		 */
		setFoldable(flag = true)
		{
			this.foldable = flag !== false;

			return this;
		}

		/**
		 *
		 * @param {boolean} flag
		 * @returns {WidgetListSection}
		 */
		setFolded(flag = true)
		{
			this.folded = flag !== false;

			return this;
		}

		/**
		 *
		 * @returns {object}
		 */
		compile()
		{
			let result = {
				id: this.id,
				title: this.title,
			};

			let properties = [
				'height',
				'sortItemParams',
				'backgroundColor',
				'styles',
				'foldable',
				'folded'
			];

			for (let property in properties)
			{
				if (this[property] !== null)
				{
					result[property] = this[property];
				}
			}

			return result;
		}
	};
})();
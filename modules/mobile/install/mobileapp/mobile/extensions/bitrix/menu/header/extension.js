/**
* @bxjs_lang_path component.php
*/

var HeaderMenuIcon = {
	chat: 'chat_v1.png',
	checked: 'checked_v1.png',
	copy: 'copy_v1.png',
	cross: 'cross_v1.png',
	edit: 'edit_v1.png',
	lifefeed: 'lifefeed_v1.png',
	notify_off: 'notify_off_v1.png',
	notify: 'notify_v1.png',
	phone: 'phone_v1.png',
	video: 'video_v1.png',
	quote: 'quote_v1.png',
	reload: 'reload_v1.png',
	reply: 'reply_v1.png',
	task: 'task_v1.png',
	trash: 'trash_v1.png',
	unread: 'unread_v1.png',
	user: 'user_v1.png',
	user_plus: 'user_plus_v1.png',
	users: 'users_v1.png',
};

var HeaderMenuIconPath = '/bitrix/mobileapp/mobile/extensions/bitrix/menu/header/images/';

(function()
{
	this.HeaderMenu = class HeaderMenu
	{
		/**
		 * @param {Iterable.<HeaderMenuItem>|null} [items]
		 *
		 * @returns {HeaderMenu}
		 */
		static create(items = null)
		{
			return new this(items);
		}

		/**
		 * @param {Iterable.<HeaderMenuItem>|null} [items]
		 */
		constructor(items = null)
		{
			this._menu = null;

			this.useNavigationBarColor = null;
			this.customParams = {};
			this.callback = () => {};

			this.items = {
				[Symbol.iterator]() { return new ObjectIterator(this); }
			};
			this.setItems(items);
		}

		/**
		 *
		 * @param params
		 * @returns {HeaderMenu}
		 */
		setCustomParams(params)
		{
			this.customParams = Object.assign({}, params);

			return this;
		}

		/**
		 * @param {boolean} flag
		 * @returns {HeaderMenu}
		 */
		setUseNavigationBarColor(flag = true)
		{
			this.useNavigationBarColor = flag === true;

			return this;
		}

		/**
		 * @param {HeaderMenuItem} item
		 * @returns {HeaderMenu}
		 */
		addItem(item)
		{
			if (item instanceof HeaderMenuItem)
			{
				this.items[item.id] = item.compile();
			}
			else
			{
				this.items[item.id] = item;
			}

			return this;
		}

		/**
		 * @param {Iterable.<HeaderMenuItem>} items
		 * @returns {HeaderMenu}
		 */
		setItems(items)
		{
			if (!items)
			{
				return this;
			}

			if (!(Symbol.iterator in Object(items)))
			{
				console.warn('%cHeaderMenu.setItems: items is not iterable, action skipped.', "color: black;", items);
				return this;
			}

			this.items = {
				[Symbol.iterator]() { return new ObjectIterator(this); }
			};
			items.forEach((item) =>
			{
				this.items[item.id] = item;
			});

			return this;
		}

		/**
		 *
		 * @param {String} id
		 * @returns {HeaderMenu}
		 */
		removeItem(id)
		{
			delete this.items[id];

			return this;
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
			for (let item of this.items)
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
			let items = [];

			for (let item of this.items)
			{
				if (
					typeof item.skipCallback === 'function'
					&& item.skipCallback(this.customParams) === true
				)
				{
					continue;
				}

				let element = item;
				if (element instanceof HeaderMenuItem)
				{
					element = element.compile();
				}

				if (!element.url && !element.action)
				{
					element.action = () => this.postEvent({name: 'selected', params: {id: element.id}});
				}

				items.push(element);
			}

			if (items.length <= 0)
			{
				console.info(`%cHeaderMenu.compile: HeaderMenu (%c${this.id}%c) compiled with empty items.`, "color: black;", "font-weight: bold", "color: black");
			}

			return items;
		}

		/**
		 *
		 * @param {Function} callback
		 * @returns {HeaderMenu}
		 */
		setEventListener(callback)
		{
			this.callback = callback;

			return this;
		}

		postEvent(event)
		{
			this.callback(event.name, event.params, this.customParams);
		}

		show(rebuild = false)
		{
			if (!this._menu || rebuild)
			{
				const items = this.compile();
				if (items.length <= 0)
				{
					return false;
				}

				let options = {};

				[
					'useNavigationBarColor',

				].forEach((code) =>
				{
					if (this[code] !== null)
					{
						options[code] = this[code];
					}
				});

				options['items'] = items;

				app.menuCreate(options);
				this._menu = true;
			}

			this.postEvent({name: 'showed', params: {}});
			app.menuShow();
		}

		hide()
		{
			this.postEvent({name: 'hided', params: {}});
			app.menuHide();
		}
	};

	this.HeaderMenuItem = class HeaderMenuItem
	{
		/**
		 *
		 * @param {string} id
		 * @param {string} [title]
		 *
		 * @returns {HeaderMenuItem}
		 */
		static create(id, title = '')
		{
			return new this(id, title);
		}

		/**
		 *
		 * @param {string} id
		 * @param {string} [title]
		 */
		constructor(id, title = '')
		{
			this.className = 'HeaderMenuItem';

			let variables = {id, title};
			for (let name in variables)
			{
				if (name === 'type' && variables[name] === null)
				{
					continue;
				}

				if (typeof variables[name] !== 'string')
				{
					console.error(`%c${this.className}.constructor: field '%c${name}%c' is not a string (%c${typeof variables[name]}%c)`, "color: black;", "font-weight: bold; color: red", "color: black", "font-weight: bold; color: red", "color: black");
				}
			}

			this.id = id;
			this.name = title;

			this.url = null;
			this.icon = null;
			this.image = null;
			this.action = null;

			this.skipCallback = null;
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
		 * @returns {HeaderMenuItem}
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
		 * @returns {HeaderMenuItem}
		 */
		setTitle(text = '')
		{
			if (typeof text !== 'string')
			{
				console.warn(`%c${this.className}.setTitle: text is not a string, action skipped. (%c${typeof text}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.name = text;

			return this;
		}

		/**
		 *
		 * @param {string} url
		 * @returns {HeaderMenuItem}
		 */
		setUrl(url)
		{
			if (typeof url !== 'string')
			{
				console.warn(`%c${this.className}.setUrl: url is not a string, action skipped. (%c${typeof url}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.url = url;

			return this;
		}

		/**
		 *
		 * @param {string} icon
		 * @param {boolean} nativeIcon
		 * @returns {HeaderMenuItem}
		 */
		setIcon(icon, nativeIcon = false)
		{
			if (typeof icon !== 'string')
			{
				console.warn(`%c${this.className}.setIcon: url is not a string, action skipped. (%c${typeof icon}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			if (nativeIcon)
			{
				this.icon = icon;
			}
			else
			{
				if (!icon.includes('.png'))
				{
					if (!HeaderMenuIcon[icon])
					{
						console.warn(`%c${this.className}.setIcon: icon is not defined, action skipped. (%cfile: ${icon}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
						return this;
					}

					icon = HeaderMenuIcon[icon];
				}

				this.setImageUrl(HeaderMenuIconPath + icon)
			}

			return this;
		}

		/**
		 *
		 * @param {string} url
		 * @returns {HeaderMenuItem}
		 */
		setImageUrl(url)
		{
			if (typeof url !== 'string')
			{
				console.warn(`%c${this.className}.setUrl: url is not a string, action skipped. (%c${typeof icon}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.image = url;

			return this;
		}

		/**
		 *
		 * @param {Function} callback
		 * @returns {HeaderMenuItem}
		 */
		setCallback(callback)
		{
			if (typeof callback !== 'function')
			{
				console.warn(`%c${this.className}.setCallback: url is not a function, action skipped. (%c${typeof callback}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			this.action = callback;

			return this;
		}


		/**
		 *
		 * @returns {Object|false}
		 */
		compile()
		{
			let result = {};

			[
				'id',
				'name',
				'url',
				'icon',
				'image',
				'action',

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

})();
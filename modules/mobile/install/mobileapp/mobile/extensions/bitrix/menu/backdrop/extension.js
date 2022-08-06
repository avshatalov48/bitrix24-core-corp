/**
* @bxjs_lang_path component.php
*/

var BackdropMenuItemHeightDefault = 62;
var BackdropMenuSectionHeightDefault = 24;

var BackdropMenuIcon = {
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
	circle_plus: 'circle_plus_v1.png',
	select_text: 'select_text_v1.png',
};

var BackdropMenuIconPath = '/bitrix/mobileapp/mobile/extensions/bitrix/menu/backdrop/images/';

var BackdropMenuItemType = WidgetListItemType;
BackdropMenuItemType.menu = BackdropMenuItemType.default;

var BackdropMenuFontStyle = WidgetListFontStyle;

var BackdropMenuAnimationType = WidgetListAnimationType;

(function()
{
	class BackdropMenu
	{
		/**
		 * @param {String} id
		 * @param {Iterable.<BackdropMenuItem>|null} [items]
		 * @param {Iterable.<BackdropMenuSection>|null} [sections]
		 *
		 * @returns {BackdropMenu}
		 */
		static create(id, items = null, sections = null)
		{
			return new this(id, items, sections);
		}

		/**
		 * @param {String} id
		 * @param {Iterable.<BackdropMenuItem>|null} [items]
		 * @param {Iterable.<BackdropMenuSection>|null} [sections]
		 */
		constructor(id, items = null, sections = null)
		{
			this.version = '1.0.0';

			this.id = id? id.toString(): 'backdrop.menu';
			this.testId = this.id;

			this.bounceEnable = false;
			this.hideNavigationBar = true;
			this.overlayOpacity = null;
			this.mediumPositionPercent = null;
			this.forceDismissOnSwipeDown = null;
			this.onlyMediumPosition = null;
			this.swipeAllowed = null;
			this.showOnTop = null;
			this.topPosition = null;
			this.mediumPositionHeight = null;

			this.customParams = {};

			this.callback = () => {};
			this.componentId = '';

			this.items = {
				[Symbol.iterator]() { return new ObjectIterator(this); }
			};
			this.setItems(items);

			this.sections = {
				[Symbol.iterator]() { return new ObjectIterator(this); }
			};
			this.setSections(sections);

			if (BackdropMenu.EventBinds['backdrop.menu:events|'+this.id])
			{
				BX.removeCustomEvent('backdrop.menu:events|'+this.id, BackdropMenu.EventBinds['backdrop.menu:events|'+this.id]);
			}

			BackdropMenu.EventBinds['backdrop.menu:events|'+this.id] = this.eventRouter.bind(this);
			BX.addCustomEvent('backdrop.menu:events|'+this.id, BackdropMenu.EventBinds['backdrop.menu:events|'+this.id]);
		}

		/**
		 *
		 * @param version
		 * @returns {BackdropMenu}
		 */
		setVersion(version)
		{
			this.version = version;
			return this;
		}

		/**
		 *
		 * @param testId
		 * @returns {BackdropMenu}
		 */
		setTestId(testId)
		{
			this.testId = testId.toString();
			return this;
		}

		/**
		 *
		 * @param params
		 * @returns {BackdropMenu}
		 */
		setCustomParams(params)
		{
			this.customParams = Object.assign({}, params);

			return this;
		}

		/**
		 * @param {boolean} flag
		 * @returns {BackdropMenu}
		 */
		setBounceEnable(flag = true)
		{
			this.bounceEnable = flag === true;

			return this;
		}

		/**
		 * @param {boolean} flag
		 * @returns {BackdropMenu}
		 */
		setNavigationBar(flag = true)
		{
			this.hideNavigationBar = !(flag === true);

			return this;
		}

		/**
		 * @param {number} number
		 * @returns {BackdropMenu}
		 */
		setOverlayOpacity(number)
		{
			if (number > 100 || number < 0)
			{
				return this;
			}

			this.hideNavigationBar = number;

			return this;
		}

		/**
		 * @param {number} number
		 * @returns {BackdropMenu}
		 */
		setMediumPositionPercent(number)
		{
			if (number > 100 || number < 0)
			{
				return this;
			}

			this.mediumPositionPercent = number;

			return this;
		}

		/**
		 * @param {boolean} flag
		 * @returns {BackdropMenu}
		 */
		setForceDismissOnSwipeDown(flag = true)
		{
			this.forceDismissOnSwipeDown = flag === true;

			return this;
		}

		/**
		 * @param {boolean} flag
		 * @returns {BackdropMenu}
		 */
		setOnlyMediumPosition(flag = true)
		{
			this.onlyMediumPosition = flag === true;

			return this;
		}
		/**
		 * @param {boolean} flag
		 * @returns {BackdropMenu}
		 */
		setShouldResizeContent(flag = true)
		{
			this.shouldResizeContent = flag === true;

			return this;
		}

		/**
		 * @param {boolean} flag
		 * @returns {BackdropMenu}
		 */
		setSwipeAllowed(flag = true)
		{
			this.swipeAllowed = flag === true;

			return this;
		}

		/**
		 * @param {boolean} flag
		 * @returns {BackdropMenu}
		 */
		setShowOnTop(flag = true)
		{
			this.showOnTop = flag === true;

			return this;
		}

		/**
		 * @param {number} number
		 * @returns {BackdropMenu}
		 */
		setTopPosition(number)
		{
			if (number < 0)
			{
				return this;
			}

			this.topPosition = number;

			return this;
		}

		/**
		 * @param {number} number
		 * @returns {BackdropMenu}
		 */
		setMediumPositionHeight(number)
		{
			if (number < 0)
			{
				return this;
			}

			this.mediumPositionHeight = number;

			return this;
		}

		/**
		 * @param {BackdropMenuItem} item
		 * @returns {BackdropMenu}
		 */
		addItem(item)
		{
			if (item instanceof BackdropMenuItem)
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
		 * @param {Iterable.<BackdropMenuItem>} items
		 * @returns {BackdropMenu}
		 */
		setItems(items)
		{
			if (!items)
			{
				return this;
			}

			if (!(Symbol.iterator in Object(items)))
			{
				console.warn('%cBackdropMenu.setItems: items is not iterable, action skipped.', "color: black;", items);
				return this;
			}

			items.forEach((item) =>
			{
				this.items[item.id] = item;
			});

			return this;
		}

		/**
		 *
		 * @param {String} id
		 * @returns {BackdropMenu}
		 */
		removeItem(id)
		{
			delete this.items[id];

			return this;
		}

		/**
		 *
		 * @param {BackdropMenuSection} section
		 * @returns {BackdropMenu}
		 */
		addSection(section)
		{
			if (!section)
			{
				return this;
			}

			if (section instanceof BackdropMenuSection)
			{
				this.sections[section.id] = section.compile();
			}
			else
			{
				this.sections[section.id] = section;
			}

			return this;
		}

		/**
		 *
		 * @param {Iterable.<BackdropMenuSection>} sections
		 * @returns {BackdropMenu}
		 */
		setSections(sections)
		{
			if (!sections)
			{
				return this;
			}

			if (!(Symbol.iterator in Object(sections)))
			{
				console.warn('%cBackdropMenu.setSections: sections is not iterable, action skipped.', "color: black;", sections);
				return this;
			}

			sections.forEach((section) =>
			{
				if (section instanceof BackdropMenuSection)
				{
					this.sections[section.id] = section.compile();
				}
				else
				{
					this.sections[section.id] = section;
				}

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
		 * @returns {string}
		 */
		getId()
		{
			return this.id;
		}

		/**
		 *
		 * @returns {string}
		 */
		getTestId()
		{
			return this.testId;
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
			let sections = [];

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
				if (element instanceof BackdropMenuItem)
				{
					element = element.compile();
				}

				if (!this.sections[element.sectionCode])
				{
					this.sections[element.sectionCode] = BackdropMenuSection.create(element.sectionCode).compile();
				}

				items.push(element);
			}

			if (items.length <= 0)
			{
				console.info(`%cBackdropMenu.compile: BackdropMenu (%c${this.id}%c) compiled with empty items.`, "color: black;", "font-weight: bold", "color: black");
			}

			for (let item of this.sections)
			{
				sections.push(item);
			}

			return {
				items: items,
				sections: sections,
			}
		}

		/**
		 *
		 * @param {Function} callback
		 * @param {string} componentId
		 * @returns {BackdropMenu}
		 */
		setEventListener(callback, componentId = '')
		{
			this.callback = callback;
			this.componentId = componentId? componentId.toString(): '';

			if (!BackdropMenu.EventInitialized)
			{
				if (typeof BXMobileApp !== 'undefined' && typeof BXMobileApp.addCustomEvent !== 'undefined')
				{
					BXMobileApp.addCustomEvent('backdrop.menu:events', BackdropMenu.eventRouter);
				}
				else
				{
					BX.addCustomEvent('backdrop.menu:events', BackdropMenu.eventRouter);
				}
				BackdropMenu.EventInitialized = true;
			}

			return this;
		}

		eventRouter(event)
		{
			this.callback(event.name, event.params, this.customParams, this);

			if (event.name === 'destroyed')
			{
				this.destroy();
			}
		}

		showSubMenu(menu)
		{
			if (!(menu instanceof BackdropMenu))
			{
				console.warn(`%c${this.className}.showSubMenu: menu is not BackdropMenu class, action skipped.`, "color: black;");
				return false;
			}

			let componentId = '';
			if (typeof BXMobileApp !== 'undefined')
			{
				componentId = 'web';
			}
			else
			{
				componentId = menu.componentId || this.componentId;
			}

			this.postEvent('backdrop.menu:showSubMenu', {
				id: menu.id,
				testId: menu.getTestId(),
				config: menu.compile(),
				componentId: componentId
			});
		}

		show()
		{
			const config = this.compile();
			if (config.items.length <= 0)
			{
				return false;
			}

			let options = {};

			[
				//'bounceEnable',
				'hideNavigationBar',
				'overlayOpacity',
				'mediumPositionPercent',
				'forceDismissOnSwipeDown',
				'onlyMediumPosition',
				'shouldResizeContent',
				'mediumPositionHeight',
				'swipeAllowed',
				'showOnTop',
				'topPosition',

			].forEach((code) =>
			{
				if (this[code] !== null)
				{
					options[code] = this[code];
				}
			});
			const isBrowser = typeof window.navigator.userAgent !== "undefined";
			if (this.topPosition === null && this.mediumPositionHeight === null)
			{
				let maxHeight = 0;

				config.items.forEach(item => {
					maxHeight += item.height? item.height: BackdropMenuItemHeightDefault;
				});

				config.sections.forEach(section => {
					maxHeight += section.height? section.height: BackdropMenuSectionHeightDefault;
				});

				if(isBrowser)
				{
					let halfScreen = screen.availHeight / 2;
					if (maxHeight <= halfScreen)
					{
						options.onlyMediumPosition = true;
						options.mediumPositionHeight = maxHeight;
					}
					else
					{
						options.mediumPositionHeight = halfScreen;
					}

				}
				else
				{
					options.mediumPositionHeight = maxHeight;
				}
			}

			config.items[config.items.length-1].hideBottomLine = true;

			let componentId = '';
			if (typeof BXMobileApp !== 'undefined')
			{
				componentId = 'web';
			}
			else
			{
				componentId = this.componentId;
			}

			let componentParams = {
					name: "JSStackComponent",
					componentCode: this.id,
					scriptPath: "/mobileapp/jn/backdrop.menu/"+(this.version? '?version='+this.version: ''),
					params: {
						"ID": this.id,
						"COMPONENT_ID": componentId,
						"CONFIG": config,
					},
					rootWidget: {
						name: 'list',
						settings: {
							objectName: "List",
							testId: this.getTestId(),
							backdrop: options
						}
					}
				};
			if(isBrowser)
			{
				app.exec("openComponent", componentParams, false);
				app.exec("callVibration");
			}
			else
			{
				PageManager.openComponent("JSStackComponent", componentParams);
			}
		}

		hide()
		{
			this.postEvent('backdrop.menu:destroy');
		}

		destroy()
		{
		}

		postEvent(name, params = {})
		{
			if (typeof BXMobileApp !== 'undefined')
			{
				BXMobileApp.Events.postToComponent(name, params, this.id);
			}
			else
			{
				BX.postComponentEvent(name, [params], this.id);
			}
		}

		static eventRouter(event)
		{
			BX.onCustomEvent('backdrop.menu:events|'+event.id, [event]);
		}
	}
	BackdropMenu.EventInitialized = false;
	BackdropMenu.EventBinds = {};

	this.BackdropMenu = BackdropMenu;

	this.BackdropMenuItem = class BackdropMenuItem extends WidgetListItem
	{
		/**
		 *
		 * @param {string} id
		 * @param {string} [title]
		 * @param {string} [sectionCode]
		 * @param {BackdropMenuItemType} [type]
		 */
		constructor(id, title, sectionCode = 'main', type = null)
		{
			if (!type)
			{
				type = BackdropMenuItemType.info;
			}
			super(id, title, sectionCode, type);

			this.setHeight(BackdropMenuItemHeightDefault);

			this.defaultStyles = {title: {font: {size: 18}}};

			this.setStyles({});

			this.className = 'BackdropMenuItem';
			this.unclosable = null;
		}

		/**
		 *
		 * @param {boolean} [flag]
		 * @param {string} [color]
		 * @returns {WidgetListItem}
		 */
		setImageLetter(flag = true, color = null)
		{
			if (!color)
			{
				color = '#8A8A8A';
			}

			return super.setImageLetter(flag, color);
		}

		/**
		 *
		 * @param {string} icon
		 * @param {string} [color]
		 * @returns {WidgetListItem}
		 */
		setIcon(icon, color = null)
		{
			if (!icon.includes('.png'))
			{
				icon = BackdropMenuIcon[icon]
			}

			if (!icon)
			{
				console.warn(`%c${this.className}.setIcon: icon is not defined, action skipped. (%cfile: ${icon}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
				return this;
			}

			return super.setImageUrl(BackdropMenuIconPath+icon, color);
		}

		/**
		 *
		 * @param {object} style
		 * @returns {WidgetListItem}
		 */
		setStyles(style)
		{
			super.setStyles(style);

			this.styles = this.objectMerge(this.defaultStyles, this.styles);

			return this;
		}

		/**
		 *
		 * @param {boolean} flag
		 * @returns {BackdropMenuItemType}
		 */
		disableClose(flag = true)
		{
			this.unclosable = flag !== false;

			return this;
		}

		/**
		 *
		 * @returns {Object|false}
		 */
		compile()
		{
			let result = Object.assign(
				{},
				super.compile(),
				{
					unclosable: this.unclosable,
				}
			);

			[
				'unclosable',

			].forEach((code) =>
			{
				if (this[code] !== null)
				{
					result[code] = this[code];
				}
			});

			return result;
		}

		objectMerge(...objects)
		{
			const isObject = object => object && typeof object === 'object';

			return objects.reduce((object1, object2) =>
			{
				Object.keys(object2).forEach(key =>
				{
					const element1 = object1[key];
					const element2 = object2[key];

					if (Array.isArray(element1) && Array.isArray(element2))
					{
						object1[key] = element1.concat(...element2);
						return true;
					}

					if (isObject(element1) && isObject(element2))
					{
						object1[key] = this.objectMerge(element1, element2);
						return true;
					}

					object1[key] = element2;
				});

				return object1;
			}, {});
		}
	};



	this.BackdropMenuItemAction = class BackdropMenuItemAction extends WidgetListItemAction
	{
		/**
		 *
		 * @param {String} id
		 * @param {String} icon
		 * @param {String} [color]
		 * @param {String} [title]
		 */
		constructor(id, icon, color = '', title = '')
		{
			super(id, title, sectionCode, type);
			this.className = 'BackdropMenuItemAction';
		}
	};

	this.BackdropMenuItemAnimation = class BackdropMenuItemAnimation extends WidgetListItemAnimation
	{
		constructor()
		{
			super();
			this.className = 'BackdropMenuItemAnimation';
		}
	};

	this.BackdropMenuItemImage = class BackdropMenuItemImage extends WidgetListItemImage
	{
		constructor()
		{
			super();
			this.className = 'BackdropMenuItemImage';
		}
	};

	this.BackdropMenuStyle = class BackdropMenuStyle extends WidgetListStyle
	{
		constructor()
		{
			super();
			this.className = 'BackdropMenuStyle';
		}
	};

	this.BackdropMenuItemFont = class BackdropMenuItemFont extends WidgetListItemFont
	{
		constructor()
		{
			super();
			this.className = 'BackdropMenuItemFont';
		}
	};

	this.BackdropMenuSection = class BackdropMenuSection extends WidgetListSection
	{
		constructor(id = 'main', title = '')
		{
			super(id, title);
			this.className = 'BackdropMenuSection';
			this.setHeight(BackdropMenuSectionHeightDefault);
		}
	};
})();
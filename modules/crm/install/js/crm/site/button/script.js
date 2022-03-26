;(function () {

	"use strict";

	/** @requires module:webpacker */
	/** @1var {module:webpacker} webPacker */
	/** @var {Object} module */
	if(typeof webPacker === "undefined")
	{
		return;
	}

	if (!window.BX)
	{
		window.BX = {};
	}
	else if (window.BX.SiteButton)
	{
		return;
	}

	var Classes = webPacker.classes;
	var Browser = webPacker.browser;
	var Type = webPacker.type;

	var Shadow = {
		clickHandler: null,
		shadowNode: null,
		displayed: false,
		init: function(params)
		{
			this.shadowNode = params.shadowNode;

			webPacker.addEventListener(this.shadowNode, 'click', this.onClick.bind(this));
			webPacker.addEventListener(document, 'keyup', function (e) {
				if ((e || window.e).keyCode === 27) this.onClick();
			}.bind(this));
		},
		onClick: function()
		{
			if (!this.displayed)
			{
				return;
			}

			WidgetManager.hide();
			ButtonManager.hide();

			if (!this.clickHandler)
			{
				return;
			}

			this.clickHandler.apply(this, []);
			this.clickHandler = null;
		},
		show: function(clickHandler)
		{
			this.clickHandler = clickHandler;
			Classes.add(this.shadowNode, 'b24-widget-button-show');
			Classes.remove(this.shadowNode, 'b24-widget-button-hide');

			Hacks.saveScrollPos();
			Classes.add(document.documentElement, 'crm-widget-button-mobile', true);
			this.displayed = true;
		},
		hide: function()
		{
			if (this.displayed)
			{
				Classes.add(this.shadowNode, 'b24-widget-button-hide');
			}
			Classes.remove(this.shadowNode, 'b24-widget-button-show');

			Classes.remove(document.documentElement, 'crm-widget-button-mobile');
			Hacks.restoreScrollPos();
			this.displayed = false;
		}
	};

	var ButtonManager = {
		isShown: false,
		isInit: false,
		wasOnceShown: false,
		wasOnceClick: false,
		blankButtonNode: null,
		list: [],
		frozen: false,
		init: function(params)
		{
			this.container = params.container;
			this.blankButtonNode = params.blankButtonNode;
			this.openerButtonNode = params.openerButtonNode;

			/* location magic */
			this.openerClassName = Manager.config.location > 3 ? 'b24-widget-button-bottom' : 'b24-widget-button-top';

			webPacker.addEventListener(this.openerButtonNode, 'click', function () {
				if (this.frozen)
				{
					this.unfreeze();
				}
				else
				{
					if (this.list.length === 1 && this.list[0].onclick && !this.list[0].href)
					{
						this.list[0].onclick.apply(this, []);
					}
					else
					{
						this.toggle();
					}
				}
			}.bind(this));

			this.isInit = true;

			this.list.forEach(function (button) {
				if (!button.node) this.insert(button);
			}, this);

			Animation.restart();
		},
		getByType: function(type)
		{
			var buttons = this.list.filter(function (button) {
				return type === button.type;
			}, this);

			return (buttons.length > 0 ? buttons[0] : null);
		},
		toggle: function()
		{
			this.isShown ? this.hide() : this.show();
		},
		show: function()
		{
			if(Browser.isIOS())
			{
				Classes.add(document.documentElement, 'bx-ios-fix-frame-focus');
			}

			//if (Browser.isMobile())
			{
				Shadow.show();
			}

			this.isShown = true;
			this.wasOnceShown = true;
			Classes.add(Manager.container, this.openerClassName);
			Classes.add(this.container, 'b24-widget-button-show');
			Classes.remove(this.container, 'b24-widget-button-hide');

			Hello.hide();
		},
		hide: function()
		{
			if(Browser.isIOS())
			{
				Classes.remove(document.documentElement, 'bx-ios-fix-frame-focus');
			}

			this.isShown = false;

			Classes.add(this.container, 'b24-widget-button-hide');
			Classes.remove(this.container, 'b24-widget-button-show');
			Classes.remove(Manager.container, this.openerClassName);

			Hello.hide();
			Shadow.hide();
		},
		freeze: function(type)
		{
			this.hide();
			if (type)
			{
				Animation.freeze(type);
			}

			this.frozen = true;
		},
		unfreeze: function()
		{
			Animation.start();
			WidgetManager.hide();
			this.hide();
			this.frozen = false;
		},
		displayButton: function (id, display)
		{
			this.list.forEach(function (button) {
				if (button.id !== id) return;
				if (!button.node) return;
				button.node.style.display = display ? '' : 'none';
			});
		},
		sortOut: function ()
		{
			this.list.sort(function(buttonA, buttonB){
				return buttonA.sort > buttonB.sort ? 1 : -1;
			});

			this.list.forEach(function(button){
				if (!button.node) return;
				button.node.parentNode.appendChild(button.node);
			});
		},
		add: function (params)
		{
			this.list.push(params);
			return this.insert(params);
		},
		insert: function (params)
		{
			if (!this.isInit)
			{
				params.node = null;
				return null;
			}

			var buttonNode = this.blankButtonNode.cloneNode(true);
			params.node = buttonNode;
			params.sort = params.sort || 100;

			buttonNode.setAttribute('data-b24-crm-button-widget', params.id);
			buttonNode.setAttribute('data-b24-widget-sort', params.sort);

			if (params.classList && params.classList.length > 0)
			{
				params.classList.forEach(function (className) {
					Classes.add(buttonNode, className);
				}, this);
			}

			if (params.title)
			{
				var tooltipNode = buttonNode.querySelector('[data-b24-crm-button-tooltip]');
				if (tooltipNode)
				{
					tooltipNode.innerText = params.title;
				}
				else
				{
					buttonNode.title = params.title;
				}
			}

			if (params.icon)
			{
				buttonNode.style['background-image'] = 'url(' + params.icon + ')';
			}
			else
			{
				if (params.iconColor)
				{
					setTimeout(function () {
						var styleName = 'background-image';
						if(!window.getComputedStyle)
						{
							return;
						}

						var styleValue = window.getComputedStyle(buttonNode, null).getPropertyValue(styleName);
						buttonNode.style[styleName] = (
							styleValue || ''
						).replace('FFF', params.iconColor.substring(1));
					}, 1000);
				}

				if (params.bgColor)
				{
					buttonNode.style['background-color'] = params.bgColor;
				}
			}

			if (params.href)
			{
				buttonNode.href = params.href;
				buttonNode.target = params.target ? params.target : '_blank';
			}

			if (params.onclick)
			{
				webPacker.addEventListener(buttonNode, 'click', function () {
					this.wasOnceClick = true;
					params.onclick.apply(this, []);
				}.bind(this));
			}

			this.container.appendChild(buttonNode);
			this.sortOut();
			Animation.restart();

			return buttonNode;
		}
	};

	var Animation = {
		isInit: false,
		timer: null,
		timerPeriod: 1500,
		icons: [],
		pulsar: null,
		stop: function()
		{
			this.rotate(false).pulse(false);
		},
		freeze: function(type)
		{
			this.rotate(type).pulse(false);
		},
		start: function()
		{
			this.rotate().pulse(true);
		},
		rotate: function(type)
		{
			this.init();
			if (this.timer) clearTimeout(this.timer);
			if (type === false)
			{
				return this;
			}

			var className = 'b24-widget-button-icon-animation';
			var current = 0;

			var icons = this.icons.filter(function (icon) { return !icon.hidden; });
			icons.forEach(function (icon, index) {
				if (Classes.has(icon.node, className)) current = index;
				Classes.remove(icon.node, className);
			}, this);

			var icon;
			if (type === 'whatsapp')
			{
				type = 'callback';
			}
			if (type && !(icon = icons.filter(function (icon) { return icon.type === type; })[0]))
			{
				throw new Error('Animation.rotate: Unknown type `' + type + '`');
			}

			if (!icon && !(icon = icons.concat(this.icons).slice(current+1)[0]))
			{
				return this;
			}

			Classes.add(icon.node, className);
			if (!type && icons.length > 1)
			{
				this.timer = setTimeout(this.rotate.bind(this), this.timerPeriod);
			}

			return this;
		},
		pulse: function(state)
		{
			Classes.change(this.pulsar, 'b24-widget-button-pulse-animate', state);
			return this;
		},
		restart: function()
		{
			this.isInit = false;
			this.start();
		},
		init: function()
		{
			if (this.isInit)
			{
				return this;
			}

			var attributeName = 'data-b24-crm-button-icon';
			this.icons = Type.toArray(
				Manager.context.querySelectorAll('[' + attributeName + ']')
			).map(function (node) {
				var type = node.getAttribute(attributeName);
				var hidden = !ButtonManager.getByType(type);
				if (hidden && type === 'callback')
				{
					hidden = !ButtonManager.getByType('whatsapp');
				}

				node.style.display = hidden ? 'none' : '';
				return {node: node, type: type, hidden: hidden};
			}, this).filter(function (icon) {
				return !icon.hidden;
			}, this);

			this.pulsar = Manager.context.querySelector('[data-b24-crm-button-pulse]');
			this.isInit = true;

			return this;
		}
	};

	var WidgetManager = { /* Widget Manager */
		showedWidget: null,
		loadedCount: 0,
		getList: function()
		{
			return Manager.config.widgets.filter(function (widget) {
				return widget.isLoaded;
			}, this);
		},
		getById: function(id)
		{
			var widgets = Manager.config.widgets.filter(function (widget) {
				return (id === widget.id && widget.isLoaded);
			}, this);

			return (widgets.length > 0 ? widgets[0] : null);
		},
		hide: function()
		{
			if (!this.showedWidget)
			{
				return;
			}

			var showedWidget = this.showedWidget;
			this.showedWidget = null;
			if(showedWidget.hide)
			{
				Utils.evalGlobal(showedWidget.hide);
			}

			Manager.show();
			Shadow.hide();
		},
		show: function(widget)
		{
			this.storeTrace(widget);

			var show = widget.show;
			if(show && typeof(show) === 'object' && show.js)
			{
				if (Browser.isMobile() && show.js.mobile)
				{
					show = show.js.mobile;
				}
				else if (!Browser.isMobile() && show.js.desktop)
				{
					show = show.js.desktop;
				}
				else if (Type.isString(show.js))
				{
					show = show.js;
				}
				else
				{
					show = null;
				}
			}
			else if(!Type.isString(show))
			{
				show = null;
			}

			if(!show)
			{
				return;
			}


			this.showedWidget = widget;
			if (!widget.freeze)
			{
				Shadow.show();
			}

			Utils.evalGlobal(show);
			if (widget.freeze)
			{
				Manager.freeze(widget.type);
			}
			else
			{
				Manager.hide();
			}
		},
		storeTrace: function(widget)
		{
			if (!widget || !widget.tracking || !widget.tracking.detecting)
			{
				return;
			}
			widget.tracking.detecting = false;

			var trace = Manager.getTrace({channels: [widget.tracking.channel]});
			Manager.b24Tracker.guest.storeTrace(trace);
		},
		showById: function(id)
		{
			var selectorId = this.getById(id);
			if (selectorId)
			{
				this.show(selectorId);
			}
		},
		checkAll: function()
		{
			return Manager.config.widgets.some(this.check, this);
		},
		check: function(widget)
		{
			return this.checkPages(widget) && this.checkWorkTime(widget);
		},
		checkPagesAll: function()
		{
			return Manager.config.widgets.some(this.checkPages, this);
		},
		checkPages: function(widget)
		{
			var isPageFound = Utils.isCurPageInList(widget.pages.list);
			if(widget.pages.mode === 'EXCLUDE')
			{
				return !isPageFound;
			}
			else
			{
				return isPageFound;
			}
		},
		checkWorkTimeAll: function()
		{
			return Manager.config.widgets.some(this.checkWorkTime, this);
		},
		checkWorkTime: function(widget)
		{
			if (!widget.workTime)
			{
				widget.isWorkTimeNow = true;
				widget.isWorkTimeChecked = true;
			}
			if (widget.isWorkTimeChecked)
			{
				return widget.isWorkTimeNow;
			}

			var workTime = widget.workTime;

			// get date with timezone
			var date = new Date();
			if (Manager.config.serverTimeStamp)
			{
				date = new Date(Manager.config.serverTimeStamp);
			}
			var timeZoneOffset = workTime.timeZoneOffset + date.getTimezoneOffset();
			date = new Date(date.valueOf() + timeZoneOffset * 60000);
			var minutes = date.getMinutes();
			minutes = minutes >= 10 ? minutes : '0' + minutes;
			var currentTime = parseFloat(date.getHours() + '.' + minutes);

			var isSuccess = true;
			if (workTime.dayOff) // check day off
			{
				var day = date.getDay();
				if (workTime.dayOff.some(function (item) { return item === day; }))
				{
					isSuccess = false;
				}
			}

			if (isSuccess && workTime.holidays) // check holidays
			{
				var currentDay = (date.getMonth() + 1).toString();
				currentDay = (currentDay.length === 1 ? '0' : '') + currentDay;
				currentDay = date.getDate() + '.' + currentDay;
				if (workTime.holidays.some(function (item) { return item === currentDay; }))
				{
					isSuccess = false;
				}
			}

			if (isSuccess) // check time
			{
				var isNightMode = workTime.timeTo < workTime.timeFrom;
				if (isNightMode)
				{
					// ex: 22:00 - 08:00
					if (currentTime > workTime.timeTo && currentTime < workTime.timeFrom)
					{
						isSuccess = false;
					}
				}
				else
				{
					// ex: 09:00 - 18:00
					if (currentTime < workTime.timeFrom || currentTime > workTime.timeTo)
					{
						isSuccess = false;
					}
				}
			}

			widget.isWorkTimeChecked = true;
			widget.isWorkTimeActionRule = false;
			if (!isSuccess && !!workTime.actionRule)
			{
				isSuccess = true;
				widget.isWorkTimeActionRule = true;
			}
			widget.isWorkTimeNow = isSuccess;
			return isSuccess;
		},
		loadAll: function()
		{
			Manager.config.widgets.forEach(this.load, this);
		},
		load: function(widget)
		{
			widget.isLoaded = false;

			Manager.execEventHandler('load-widget-' + widget.id, [widget]);

			if(!this.check(widget))
			{
				return;
			}

			if (widget.workTime && widget.isWorkTimeActionRule)
			{
				switch (widget.workTime.actionRule)
				{
					case 'text':
						if (widget.type === 'callback')
						{
							Manager.addEventHandler('form-init', function (form) {
								if (!form.isCallbackForm) return;
								window.Bitrix24FormLoader.addEventHandler(
									form, 'init-frame-params', function (form, frameParameters) {
										frameParameters.resultSuccessText = widget.workTime.actionText;
										frameParameters.stopCallBack = true;
									}
								);
							});
						}
						break;
				}
			}

			widget.buttonNode = ButtonManager.add({
				'id': widget.id,
				'type': widget.type,
				'href': this.getButtonUrl(widget),
				'sort': widget.sort,
				'classList': (typeof widget.classList !== "undefined" ? widget.classList : null),
				'title': (typeof widget.title !== "undefined" ? widget.title : null),
				'onclick': this.show.bind(this, widget),
				'bgColor': widget.useColors ? Manager.config.bgColor : null,
				'iconColor': widget.useColors ? Manager.config.iconColor : null
			});

			this.loadScript(widget);
			widget.isLoaded = true;
			this.loadedCount++;
		},
		getButtonUrl: function(widget)
		{
			if (!widget.show || (widget.script && !(widget.show.url && widget.show.url.force)))
			{
				return null;
			}

			if (Type.isString(widget.show) || !widget.show.url)
			{
				return null;
			}

			var url = null;
			if (Browser.isMobile() && widget.show.url.mobile)
			{
				url = widget.show.url.mobile;
			}
			else if (!Browser.isMobile() && widget.show.url.desktop)
			{
				url = widget.show.url.desktop;
			}
			else if (Type.isString(widget.show.url))
			{
				url = widget.show.url;
			}

			return url;
		},
		loadScript: function(widget)
		{
			if (!widget.script)
			{
				return;
			}

			var scriptText = '';
			var isAddInHead = false;
			var parsedScript = widget.script.match(/<script\b[^>]*>(.*?)<\/script>/i);
			if(parsedScript && parsedScript[1])
			{
				scriptText = parsedScript[1];
				isAddInHead = true;
			}
			else if(!widget.freeze)
			{
				widget.node = Utils.getNodeFromText(widget.script);
				if(!widget.node)
				{
					return;
				}
				isAddInHead = false;

				if (typeof widget.caption !== "undefined")
				{
					var widgetCaptionNode = widget.node.querySelector('[data-bx-crm-widget-caption]');
					if (widgetCaptionNode)
					{
						widgetCaptionNode.innerText = widget.caption;
					}
				}
			}
			else
			{
				scriptText = widget.script;
				isAddInHead = true;
			}

			if (isAddInHead)
			{
				widget.node = document.createElement("script");
				try {
					widget.node.appendChild(document.createTextNode(scriptText));
				} catch(e) {
					widget.node.text = scriptText;
				}
				document.head.appendChild(widget.node);
			}
			else
			{
				document.body.insertBefore(widget.node, document.body.firstChild);
			}
		}
	};

	var Hello = {
		isInit: false,
		wasOnceShown: false,
		condition: null,
		cookieName: 'b24_sitebutton_hello',
		init: function (params)
		{
			if (this.isInit)
			{
				return;
			}

			this.context = params.context;
			this.showClassName = 'b24-widget-button-popup-show';
			this.config = Manager.config.hello || {};
			this.delay = this.config.delay;

			this.buttonHideNode = this.context.querySelector('[data-b24-hello-btn-hide]');
			this.iconNode = this.context.querySelector('[data-b24-hello-icon]');
			this.nameNode = this.context.querySelector('[data-b24-hello-name]');
			this.textNode = this.context.querySelector('[data-b24-hello-text]');

			this.initHandlers();
			this.isInit = true;

			if (webPacker.cookie.get(this.cookieName) === 'y')
			{
				return;
			}

			if (!this.config || !this.config.conditions || this.config.conditions.length === 0)
			{
				return;
			}

			if (!this.condition)
			{
				this.setConditions(this.config.conditions, true);
			}
			Manager.addEventHandler('first-show', this.initCondition.bind(this));
		},
		setConditions: function (conditions, setOnly)
		{
			this.condition = this.findCondition(conditions);
			if (!setOnly)
			{
				this.initCondition();
			}
		},
		initCondition: function ()
		{
			if (!this.condition)
			{
				return;
			}

			if (!this.isInit)
			{
				return;
			}

			if (this.condition.icon)
			{
				this.iconNode.style['background-image'] = 'url(' + this.condition.icon + ')';
			}
			if (this.condition.name)
			{
				this.nameNode.innerText = this.condition.name;
			}
			if (this.condition.text)
			{
				this.textNode.innerText = this.condition.text;
			}
			if (this.condition.delay)
			{
				this.delay = this.condition.delay;
			}

			this.planShowing();
		},
		initHandlers: function ()
		{
			webPacker.addEventListener(this.buttonHideNode, 'click', function (e) {
				this.hide();

				if(!e) e = window.event;
				if(e.stopPropagation){e.preventDefault();e.stopPropagation();}
				else{e.cancelBubble = true;e.returnValue = false;}
			}.bind(this));
			webPacker.addEventListener(this.context, 'click', this.showWidget.bind(this));
		},
		planShowing: function ()
		{
			if (this.wasOnceShown || ButtonManager.wasOnceClick)
			{
				return;
			}

			setTimeout(this.show.bind(this), (this.delay || 10) * 1000);
		},
		findCondition: function (conditions)
		{
			if (!conditions)
			{
				return;
			}

			var filtered;
			// find first suitable condition with mode 'include'
			filtered = conditions.filter(function (condition) {
				if (!condition.pages || condition.pages.MODE === 'EXCLUDE' || condition.pages.LIST.length === 0)
				{
					return false;
				}

				return Utils.isCurPageInList(condition.pages.LIST);
			}, this);
			if (filtered.length > 0)
			{
				return filtered[0];
			}

			// find first suitable condition with mode 'exclude'
			filtered = conditions.filter(function (condition) {
				if (!condition.pages || condition.pages.MODE === 'INCLUDE')
				{
					return false;
				}

				return !Utils.isCurPageInList(condition.pages.LIST);
			}, this);
			if (filtered.length > 0)
			{
				return filtered[0];
			}

			// find first condition with empty pages
			filtered = conditions.filter(function (condition) {
				return !condition.pages;
			}, this);
			if (filtered.length > 0)
			{
				return filtered[0];
			}

			// nothing found
			return null;
		},
		showWidget: function ()
		{
			this.hide();

			var widget = null;
			if (this.condition && this.condition.showWidgetId)
			{
				widget = WidgetManager.getById(this.condition.showWidgetId);
			}

			if (!widget)
			{
				widget = WidgetManager.getById(this.config.showWidgetId);
			}

			if (!widget)
			{
				var list = WidgetManager.getList();
				if (list.length > 0)
				{
					widget = list[0];
				}
			}

			if (widget)
			{
				WidgetManager.show(widget);
			}
		},
		showImmediately: function (options)
		{
			options = options || null;
			if (options)
			{
				this.setConditions([{
					'icon': options.icon,
					'name': options.name,
					'text': options.text,
					'page': '',
					'delay': 0
				}])
			}

			this.show(true);
		},
		show: function (forceShowing)
		{
			if (!this.condition)
			{
				return;
			}

			forceShowing = forceShowing || false;
			if (!forceShowing && ButtonManager.isShown) //maybe use wasOnceShown
			{
				this.planShowing();
				return;
			}

			this.wasOnceShown = true;
			Classes.add(this.context, this.showClassName);
		},
		hide: function ()
		{
			Classes.remove(this.context, this.showClassName);
			webPacker.cookie.set(this.cookieName, 'y', 60*60*6);
		}
	};

	var Utils = {
		getNodeFromText: function(text)
		{
			var node = document.createElement('div');
			node.innerHTML = text;
			return node.children[0];
		},
		evalGlobal: function(text)
		{
			webPacker.resource.loadJs(text, false, true);
		},
		isCurPageInList: function(list)
		{
			var filtered = list.filter(function (page) {
				page = encodeURI(page);
				var pattern = this.prepareUrl(page).split('*').map(function(chunk){
					return chunk.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
				}).join('.*');
				pattern = '^' + pattern + '$';
				return (new RegExp(pattern)).test(this.prepareUrl(window.location.href));
			}, this);

			return filtered.length > 0;
		},
		prepareUrl: function(url)
		{
			var result;
			if (url.substring(0, 5) === 'http:')
			{
				result = url.substring(7);
			}
			else if (url.substring(0, 6) === 'https:')
			{
				result = url.substring(8);
			}
			else
			{
				result = url;
			}

			return result;
		}
	};

	var Hacks = {
		scrollPos: 0,
		saveScrollPos: function()
		{
			this.scrollPos = window.pageYOffset;
		},
		restoreScrollPos: function()
		{
			if (!Browser.isMobile())
			{
				return;
			}
			window.scrollTo(0,this.scrollPos);
		}
	};

	var Manager = window.BX.SiteButton = {
		buttons: ButtonManager,
		animation: Animation,
		shadow: Shadow,
		wm: WidgetManager,
		hello: Hello,
		util: Utils,
		classes: Classes,
		hacks: Hacks,

		isShown: false,
		init: function(config)
		{
			this.b24Tracker = window.b24Tracker || {};

			this.userParams = window.Bitrix24WidgetObject || {};
			this.config = config;
			this.handlers = this.userParams.handlers || {};
			this.eventHandlers = [];

			this.execEventHandler('init', [this]);

			if(!this.check())
			{
				return;
			}

			this.load();

			if(this.config.delay)
			{
				window.setTimeout(this.show.bind(this), 1000 * this.config.delay);
			}
			else
			{
				this.show();
			}
		},
		check: function()
		{
			if(!this.config.isActivated)
			{
				return false;
			}

			if(this.config.widgets.length === 0)
			{
				return false;
			}

			if(this.config.disableOnMobile && Browser.isMobile())
			{
				return false;
			}

			return WidgetManager.checkAll();
		},
		loadResources: function()
		{
			//throw Error('loadResources unavailable');
		},
		load: function()
		{
			this.execEventHandler('load', [this]);

			// set common classes
			Browser.isIOS() ? Classes.add(document.documentElement, 'bx-ios') : null;
			Browser.isMobile() ? Classes.add(document.documentElement, 'bx-touch') : null;

			// load resources
			this.loadResources();

			this.container = document.body.querySelector('[data-b24-crm-button-cont]');
			this.context = this.container.parentNode;


			// init components
			this.shadow.init({
				'shadowNode': this.context.querySelector('[data-b24-crm-button-shadow]')
			});
			this.buttons.init({
				'container': this.container.querySelector('[data-b24-crm-button-block]'),
				'blankButtonNode': this.context.querySelector('[data-b24-crm-button-widget-blank]'),
				'openerButtonNode': this.context.querySelector('[data-b24-crm-button-block-button]')
			});
			this.hello.init({
				context: this.container.querySelector('[data-b24-crm-hello-cont]')
			});

			// load widgets
			this.wm.loadAll();

			this.execEventHandler('loaded', [this]);
		},
		show: function()
		{
			Classes.remove(this.container, 'b24-widget-button-disable');
			Classes.add(this.container, 'b24-widget-button-visible');

			this.execEventHandler('show', [this]);
			if (!this.isShown)
			{
				this.execEventHandler('first-show', [this]);
			}
			this.isShown = true;
		},
		hide: function()
		{
			Classes.add(this.container, 'b24-widget-button-disable');
			this.execEventHandler('hide', [this]);
		},
		freeze: function(type)
		{
			setTimeout(function () {
				ButtonManager.freeze(type);
				this.show();
			}.bind(this));
		},
		addEventHandler: function(eventName, handler)
		{
			if (!eventName || !handler)
			{
				return;
			}

			this.eventHandlers.push({
				'eventName': eventName,
				'handler': handler
			});
		},
		execEventHandler: function(eventName, params)
		{
			params = params || [];
			if (!eventName)
			{
				return;
			}

			this.eventHandlers.forEach(function (eventHandler) {
				if (eventHandler.eventName === eventName)
				{
					eventHandler.handler.apply(this, params);
				}
			}, this);

			if(this.handlers[eventName])
			{
				this.handlers[eventName].apply(this, params);
			}

			var externalEventName = 'b24-sitebutton-' + eventName;
			if (window.BX.onCustomEvent)
			{
				window.BX.onCustomEvent(document, externalEventName, params);
			}
			if (window.jQuery && typeof(window.jQuery) === 'function')
			{
				var obj = window.jQuery( document );
				if (obj && obj.trigger) obj.trigger(externalEventName, params);
			}
		},
		onWidgetFormInit: function(form)
		{
			this.execEventHandler('form-init', [form]);
		},
		onWidgetClose: function()
		{
			ButtonManager.unfreeze();
			this.show();
		},
		getTrace: function(options)
		{
			if (!this.b24Tracker.guest)
			{
				return null;
			}

			options = options || {};
			options.channels = options.channels || [];
			options.channels = [this.config.tracking.channel].concat(options.channels);
			return this.b24Tracker.guest.getTrace(options);
		}
	};

})();
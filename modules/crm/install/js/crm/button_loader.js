;(function (window) {
	if (!window.BX)
	{
		window.BX = {};
	}
	else if (window.BX.SiteButton)
	{
		return;
	}

	window.BX.SiteButton = {

		isShown: false,
		init: function(config)
		{
			this.loadBxAnalytics();

			this.userParams = window.Bitrix24WidgetObject || {};
			this.config = config;
			this.handlers = this.userParams.handlers || {};
			this.eventHandlers = [];

			this.loadGuestTracker();
			this.execEventHandler('init', [this]);

			if(!this.check())
			{
				return;
			}

			this.load();

			if(this.config.delay)
			{
				var _this = this;
				window.setTimeout(
					function(){
						_this.show();
					},
					1000 * this.config.delay
				);
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

			if(this.config.widgets.length == 0)
			{
				return false;
			}

			if(this.config.disableOnMobile && this.util.isMobile())
			{
				return false;
			}

			if (!this.wm.checkPagesAll(this))
			{
				return false;
			}

			if (!this.wm.checkWorkTimeAll(this))
			{
				return false;
			}
			else
			{
				return true;
			}
		},
		loadGuestTracker: function()
		{
			var dataVarName = 'b24CrmGuestData';
			window[dataVarName] = window[dataVarName] || {
				name: 'b24CrmGuest',
				ref: this.config.serverAddress + '/'
			};

			this.loadResources('manual', 'guest_tracker.js');
		},
		loadBxAnalytics: function()
		{
			if(typeof window._ba != "undefined")
			{
				return;
			}

			var targetHost = document.location.hostname;

			window._ba = window._ba || [];
			window._ba.push(["aid", "ext:" + targetHost]);
			window._ba.push(["host", targetHost]);
			(function() {
				var ba = document.createElement("script"); ba.type = "text/javascript"; ba.async = true;
				ba.src = (document.location.protocol == "https:" ? "https://" : "http://") + "bitrix.info/ba.js";
				var s = document.getElementsByTagName("script")[0];
				s.parentNode.insertBefore(ba, s);
			})();
		},
		loadResources: function(mode, fileName)
		{
			this.config.resources.forEach(function(resource){

				resource.loadMode = resource.loadMode || 'auto';
				if (resource.loadMode != mode)
				{
					return;
				}
				if (fileName && fileName != resource.name)
				{
					return;
				}

				switch (resource.type)
				{
					case 'text/javascript':
						this.util.evalGlobal(resource.content);
						break;

					case 'text/css':
						this.util.addCss(resource.content);
						break;
				}

			}, this);
		},
		load: function()
		{
			this.execEventHandler('load', [this]);

			// set common classes
			if(this.util.isIOS()) this.addClass(document.documentElement, 'bx-ios');
			if(this.util.isMobile()) this.addClass(document.documentElement, 'bx-touch');

			// load resources
			this.loadResources('auto');

			// insert layout
			this.context = this.util.getNodeFromText(this.config.layout);
			if (!this.context)
			{
				return;
			}

			document.body.appendChild(this.context);
			this.container = this.context.querySelector('[data-b24-crm-button-cont]');


			// init components
			this.shadow.init({
				'caller': this,
				'shadowNode': this.context.querySelector('[data-b24-crm-button-shadow]')
			});
			this.buttons.init({
				'caller': this,
				'container': this.container.querySelector('[data-b24-crm-button-block]'),
				'blankButtonNode': this.context.querySelector('[data-b24-crm-button-widget-blank]'),
				'openerButtonNode': this.context.querySelector('[data-b24-crm-button-block-button]')
			});
			this.wm.init({'caller': this});
			this.hacks.init({'caller': this});
			this.hello.init({
				caller: this,
				context: this.container.querySelector('[data-b24-crm-hello-cont]')
			});

			// load widgets
			this.wm.loadAll();

			this.execEventHandler('loaded', [this]);
		},
		setPulse: function(isActive)
		{
			isActive = isActive || false;
			var pulseNode = this.context.querySelector('[data-b24-crm-button-pulse]');
			if (!pulseNode)
			{
				return;
			}
			pulseNode.style.display = isActive ? '' : 'none';
		},
		show: function()
		{
			this.removeClass(this.container, 'b24-widget-button-disable');
			this.addClass(this.container, 'b24-widget-button-visible');

			this.execEventHandler('show', [this]);
			if (!this.isShown)
			{
				this.execEventHandler('first-show', [this]);
			}
			this.isShown = true;
		},
		hide: function()
		{
			this.addClass(this.container, 'b24-widget-button-disable');

			this.execEventHandler('hide', [this]);
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
				if (eventHandler.eventName == eventName)
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
			this.buttons.hide();
			this.show();
		},
		addClass: function(element, className)
		{
			if (element && typeof element.className == "string" && element.className.indexOf(className) === -1)
			{
				element.className += " " + className;
				element.className = element.className.replace('  ', ' ');
			}
		},
		removeClass: function(element, className)
		{
			if (!element || !element.className)
			{
				return;
			}

			element.className = element.className.replace(className, '').replace('  ', ' ');
		},
		addEventListener: function(el, eventName, handler)
		{
			el = el || window;
			if (window.addEventListener)
			{
				el.addEventListener(eventName, handler, false);
			}
			else
			{
				el.attachEvent('on' + eventName, handler);
			}
		},
		buttons: {
			isShown: false,
			isInit: false,
			wasOnceShown: false,
			wasOnceClick: false,
			blankButtonNode: null,
			list: [],
			animatedNodes: [],
			attributeAnimateNode: 'data-b24-crm-button-icon',
			init: function(params)
			{
				this.c = params.caller;
				this.container = params.container;
				this.blankButtonNode = params.blankButtonNode;
				this.openerButtonNode = params.openerButtonNode;

				/* location magic */
				this.openerClassName = this.c.config.location > 3 ? 'b24-widget-button-bottom' : 'b24-widget-button-top';

				var _this = this;
				this.c.addEventListener(this.openerButtonNode, 'click', function (e) {
					if (_this.list.length == 1 && _this.list[0].onclick && !_this.list[0].href)
					{
						_this.list[0].onclick.apply(this, []);
					}
					else
					{
						_this.toggle();
					}
				});

				this.isInit = true;

				this.list.forEach(function (button) {
					if (!button.node) this.insert(button);
				}, this);

				// main button animation
				this.initAnimation();

				// pulse animation
				this.startPulseAnimation();
			},
			startPulseAnimation: function()
			{
				this.c.addClass(
					this.c.context.querySelector('[data-b24-crm-button-pulse]'),
					'b24-widget-button-pulse-animate'
				);
			},
			stopPulseAnimation: function()
			{
				this.c.removeClass(
					this.c.context.querySelector('[data-b24-crm-button-pulse]'),
					'b24-widget-button-pulse-animate'
				);
			},
			startIconAnimation: function()
			{
				this.animate()
			},
			stopIconAnimation: function()
			{
				clearTimeout(this.iconAnimationTimeout);
			},
			initAnimation: function()
			{
				var animatedNodes = this.c.util.nodeListToArray(
					this.c.context.querySelectorAll('[' + this.attributeAnimateNode + ']')
				);

				this.animatedNodes = animatedNodes.filter(function (node) {
					var type = node.getAttribute(this.attributeAnimateNode);
					var isHidden = !this.getByType(type);
					node.style.display = isHidden ? 'none' : '';
					return !isHidden;
				}, this);

				this.animate();
			},
			animate: function()
			{
				var className = 'b24-widget-button-icon-animation';
				var curIndex = 0;
				this.animatedNodes.forEach(function (node, index) {
					if (this.c.util.hasClass(node, className)) curIndex = index;
					this.c.removeClass(node, className);
				}, this);

				curIndex++;
				curIndex = curIndex < this.animatedNodes.length ? curIndex : 0;
				this.c.addClass(this.animatedNodes[curIndex], className);

				if (this.animatedNodes.length > 1)
				{
					var _this = this;
					this.iconAnimationTimeout = setTimeout(function () {_this.animate();}, 1500);
				}
			},
			getByType: function(type)
			{
				var buttons = this.list.filter(function (button) {
					return type == button.type;
				}, this);

				return (buttons.length > 0 ? buttons[0] : null);
			},
			toggle: function()
			{
				this.isShown ? this.hide() : this.show();
			},
			show: function()
			{
				if(this.c.util.isIOS()) this.c.addClass(document.documentElement, 'bx-ios-fix-frame-focus');

				//if (this.c.util.isMobile())
				{
					this.c.shadow.show();
				}

				this.isShown = true;
				this.wasOnceShown = true;
				this.c.addClass(this.c.container, this.openerClassName);
				this.c.addClass(this.container, 'b24-widget-button-show');
				this.c.removeClass(this.container, 'b24-widget-button-hide');

				this.c.hello.hide();
			},
			hide: function()
			{
				if(this.c.util.isIOS()) this.c.removeClass(document.documentElement, 'bx-ios-fix-frame-focus');

				this.isShown = false;

				this.c.addClass(this.container, 'b24-widget-button-hide');
				this.c.removeClass(this.container, 'b24-widget-button-show');
				this.c.removeClass(this.c.container, this.openerClassName);

				this.c.hello.hide();
				this.c.shadow.hide();
			},
			displayButton: function (id, display)
			{
				this.list.forEach(function (button) {
					if (button.id != id) return;
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
						this.c.addClass(buttonNode, className);
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
					var _this = this;
					this.c.addEventListener(buttonNode, 'click', function (e) {
						_this.wasOnceClick = true;
						params.onclick.apply(_this, []);
					});
				}

				this.container.appendChild(buttonNode);
				this.sortOut();
				this.initAnimation();

				return buttonNode;
			}
		},
		shadow: {
			clickHandler: null,
			shadowNode: null,
			autoClose: true,
			displayed: false,
			init: function(params)
			{
				this.c = params.caller;
				this.shadowNode = params.shadowNode;

				this.c.addEventListener(this.shadowNode, 'click', this.onClick.bind());
				this.c.addEventListener(document, 'keyup', function (e) {
					e = e || window.e;
					if (e.keyCode === 27)
					{
						this.onClick();
					}
				}.bind(this));
			},
			onClick: function()
			{
				if (!this.autoClose)
				{
					return;
				}

				this.c.wm.hide();
				this.c.buttons.hide();

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
				this.c.addClass(this.shadowNode, 'b24-widget-button-show');
				this.c.removeClass(this.shadowNode, 'b24-widget-button-hide');

				this.c.hacks.saveScrollPos();
				this.c.addClass(document.documentElement, 'crm-widget-button-mobile');
				this.displayed = true;
			},
			hide: function()
			{
				if (this.displayed)
				{
					this.c.addClass(this.shadowNode, 'b24-widget-button-hide');
				}
				this.c.removeClass(this.shadowNode, 'b24-widget-button-show');

				this.c.removeClass(document.documentElement, 'crm-widget-button-mobile');
				this.c.hacks.restoreScrollPos();
				this.displayed = false;
			},
			setAutoClose: function (enable)
			{
				this.autoClose = enable !== false;
			}
		},
		util: {
			getNodeFromText: function(text)
			{
				var node = document.createElement('div');
				node.innerHTML = text;
				return node.children[0];
			},
			hasClass: function(node, className)
			{
				var classList = this.nodeListToArray(node.classList);
				var filtered = classList.filter(function (name) { return name == className});
				return filtered.length > 0;
			},
			nodeListToArray: function(nodeList)
			{
				var list = [];
				if (!nodeList) return list;
				for (var i = 0; i < nodeList.length; i++)
				{
					list.push(nodeList.item(i));
				}
				return list;
			},
			isIOS: function()
			{
				return (/(iPad;)|(iPhone;)/i.test(navigator.userAgent));
			},
			isOpera: function()
			{
				return navigator.userAgent.toLowerCase().indexOf('opera') != -1;
			},
			isIE: function()
			{
				return document.attachEvent && !this.isOpera();
			},
			isMobile: function()
			{
				return (/(ipad|iphone|android|mobile|touch)/i.test(navigator.userAgent));
			},
			isArray: function(item) {
				return item && Object.prototype.toString.call(item) == "[object Array]";
			},
			isString: function(item) {
				return item === '' ? true : (item ? (typeof (item) == "string" || item instanceof String) : false);
			},
			evalGlobal: function(text)
			{
				if (!text)
				{
					return;
				}

				var head = document.getElementsByTagName("head")[0] || document.documentElement,
					script = document.createElement("script");

				script.type = "text/javascript";

				if (!this.isIE())
				{
					script.appendChild(document.createTextNode(text));
				}
				else
				{
					script.text = text;
				}

				head.insertBefore(script, head.firstChild);
				head.removeChild(script);
			},
			addCss: function(content)
			{
				var cssNode = document.createElement('STYLE');
				cssNode.setAttribute("type", "text/css");
				if(cssNode.styleSheet)
				{
					cssNode.styleSheet.cssText = resource.content;
				}
				else
				{
					cssNode.appendChild(document.createTextNode(content))
				}
				document.head.appendChild(cssNode);
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
			},
			getCookie: function (name)
			{
				var matches = document.cookie.match(new RegExp(
					"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
				));

				return matches ? decodeURIComponent(matches[1]) : undefined;
			},
			setCookie: function (name, value, options)
			{
				options = options || {};
				if (!options.path)
				{
					options.path = '/';
				}
				var expires = options.expires;
				if (typeof(expires) === "number" && expires)
				{
					var currentDate = new Date();
					currentDate.setTime(currentDate.getTime() + expires * 1000);
					expires = options.expires = currentDate;
				}

				if (expires && expires.toUTCString)
				{
					options.expires = expires.toUTCString();
				}
				value = encodeURIComponent(value);
				var updatedCookie = name + "=" + value;
				for (var propertyName in options)
				{
					if (!options.hasOwnProperty(propertyName))
					{
						continue;
					}
					updatedCookie += "; " + propertyName;
					var propertyValue = options[propertyName];
					if (propertyValue !== true)
					{
						updatedCookie += "=" + propertyValue;
					}
				}

				document.cookie = updatedCookie;
			}
		},
		hacks: { /* Hacks */
			scrollPos: 0,
			init: function(params)
			{
				this.c = params.caller;
			},
			saveScrollPos: function()
			{
				this.scrollPos = window.pageYOffset;
			},
			restoreScrollPos: function()
			{
				if (!this.c.util.isMobile())
				{
					return;
				}
				window.scrollTo(0,this.scrollPos);
			}
		},
		wm: { /* Widget Manager */
			showedWidget: null,
			loadedCount: 0,
			init: function(params)
			{
				this.c = params.caller;
			},
			getList: function()
			{
				return this.c.config.widgets.filter(function (widget) {
					return widget.isLoaded;
				}, this);
			},
			getById: function(id)
			{
				var widgets = this.c.config.widgets.filter(function (widget) {
					return (id == widget.id && widget.isLoaded);
				}, this);

				return (widgets.length > 0 ? widgets[0] : null);
			},
			hide: function()
			{
				if (!this.showedWidget)
				{
					return;
				}

				if(this.showedWidget.hide)
				{
					this.c.util.evalGlobal(this.showedWidget.hide);
				}

				this.c.onWidgetClose();
				this.c.shadow.hide();
				this.showedWidget = null;
			},
			show: function(widget)
			{
				if(!widget.show || !this.c.util.isString(widget.show))
				{
					return;
				}

				this.showedWidget = widget;
				this.c.shadow.show();

				this.c.util.evalGlobal(widget.show);
				this.c.hide();
			},
			checkPagesAll: function(manager)
			{
				this.c = manager;
				return this.c.config.widgets.some(this.checkPages, this);
			},
			checkPages: function(widget)
			{
				var isPageFound = this.c.util.isCurPageInList(widget.pages.list);
				if(widget.pages.mode == 'EXCLUDE')
				{
					return !isPageFound;
				}
				else
				{
					return isPageFound;
				}
			},
			checkWorkTimeAll: function(manager)
			{
				this.c = manager;
				return this.c.config.widgets.some(this.checkWorkTime, this);
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
				if (this.c.config.serverTimeStamp)
				{
					date = new Date(this.c.config.serverTimeStamp);
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
					currentDay = (currentDay.length == 1 ? '0' : '') + currentDay;
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
				this.c.config.widgets.forEach(this.load, this);
			},
			load: function(widget)
			{
				widget.isLoaded = false;

				this.c.execEventHandler('load-widget-' + widget.id, [widget]);

				if(!this.checkPages(widget))
				{
					return;
				}

				if(!this.checkWorkTime(widget))
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
								this.c.addEventHandler('form-init', function (form) {
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

				widget.buttonNode = this.c.buttons.add({
					'id': widget.id,
					'type': widget.type,
					'href': this.getButtonUrl(widget),
					'sort': widget.sort,
					'classList': (typeof widget.classList != "undefined" ? widget.classList : null),
					'title': (typeof widget.title != "undefined" ? widget.title : null),
					'onclick': this.getButtonHandler(widget),
					'bgColor': widget.useColors ? this.c.config.bgColor : null,
					'iconColor': widget.useColors ? this.c.config.iconColor : null
				});

				this.loadScript(widget);
				widget.isLoaded = true;
				this.loadedCount++;
			},
			getButtonHandler: function(widget)
			{
				var _this = this;
				return function () {
					_this.show(widget);
				};
			},
			getButtonUrl: function(widget)
			{
				if (widget.script || !widget.show)
				{
					return null;
				}

				if (this.c.util.isString(widget.show) || !widget.show.url)
				{
					return null;
				}

				var url = null;
				if (this.c.util.isMobile() && widget.show.url.mobile)
					url = widget.show.url.mobile;
				else if (!this.c.util.isMobile() && widget.show.url.desktop)
					url = widget.show.url.desktop;
				else if (this.c.util.isString(widget.show.url))
					url = widget.show.url;

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
				else
				{
					widget.node = this.c.util.getNodeFromText(widget.script);
					if(!widget.node)
					{
						return;
					}
					isAddInHead = false;

					if (typeof widget.caption != "undefined")
					{
						var widgetCaptionNode = widget.node.querySelector('[data-bx-crm-widget-caption]');
						if (widgetCaptionNode)
						{
							widgetCaptionNode.innerText = widget.caption;
						}
					}
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
		},
		hello: {
			isInit: false,
			wasOnceShown: false,
			condition: null,
			cookieName: 'b24_sitebutton_hello',
			init: function (params)
			{
				this.c = params.caller;

				if (this.isInit)
				{
					return;
				}

				this.context = params.context;
				this.showClassName = 'b24-widget-button-popup-show';
				this.config = this.c.config.hello;
				this.delay = this.config.delay;

				this.buttonHideNode = this.context.querySelector('[data-b24-hello-btn-hide]');
				this.iconNode = this.context.querySelector('[data-b24-hello-icon]');
				this.nameNode = this.context.querySelector('[data-b24-hello-name]');
				this.textNode = this.context.querySelector('[data-b24-hello-text]');

				this.initHandlers();
				this.isInit = true;

				if (this.c.util.getCookie(this.cookieName) == 'y')
				{
					return;
				}

				if (!this.config || !this.config.conditions || this.config.conditions.length == 0)
				{
					return;
				}

				if (!this.condition)
				{
					this.setConditions(this.config.conditions, true);
				}
				this.c.addEventHandler('first-show', this.initCondition.bind(this));
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
				var _this = this;
				this.c.addEventListener(this.buttonHideNode, 'click', function (e) {
					_this.hide();

					if(!e) e = window.event;
					if(e.stopPropagation){e.preventDefault();e.stopPropagation();}
					else{e.cancelBubble = true;e.returnValue = false;}
				});
				this.c.addEventListener(this.context, 'click', function () {
					_this.showWidget();
				});
			},
			planShowing: function ()
			{
				if (this.wasOnceShown || this.c.buttons.wasOnceClick)
				{
					return;
				}

				var showDelay = this.delay || 10;
				var _this = this;
				setTimeout(function () {
					_this.show();
				}, showDelay * 1000);
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
					if (!condition.pages || condition.pages.MODE == 'EXCLUDE' || condition.pages.LIST.length == 0)
					{
						return false;
					}

					return this.c.util.isCurPageInList(condition.pages.LIST);
				}, this);
				if (filtered.length > 0)
				{
					return filtered[0];
				}

				// find first suitable condition with mode 'exclude'
				filtered = conditions.filter(function (condition) {
					if (!condition.pages || condition.pages.MODE == 'INCLUDE')
					{
						return false;
					}

					return !this.c.util.isCurPageInList(condition.pages.LIST);
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
					widget = this.c.wm.getById(this.condition.showWidgetId);
				}

				if (!widget)
				{
					widget = this.c.wm.getById(this.config.showWidgetId);
				}

				if (!widget)
				{
					var list = this.c.wm.getList();
					if (list.length > 0)
					{
						widget = list[0];
					}
				}

				if (widget)
				{
					this.c.wm.show(widget);
				}
			},
			showImmediately: function (params)
			{
				params = params || null;
				if (params)
				{
					this.setConditions([{
						'icon': params.icon,
						'name': params.name,
						'text': params.text,
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
				if (!forceShowing && this.c.buttons.isShown) //maybe use wasOnceShown
				{
					this.planShowing();
					return;
				}

				this.wasOnceShown = true;
				this.c.addClass(this.context, this.showClassName);
			},
			hide: function ()
			{
				this.c.removeClass(this.context, this.showClassName);
				this.c.util.setCookie(this.cookieName, 'y', {expires: 60*60*6});
			}
		}
	};


})(window);
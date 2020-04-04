var Bitrix24FormLoader = {

	init: function()
	{
		this.yaId = null;
		this.forms = {};
		this.eventHandlers = [];
		this.frameHeight = '200';
		this.defaultNodeId = 'bx24_form_';

		if(!window.Bitrix24FormObject || !window[window.Bitrix24FormObject])
			return;

		var b24form = window[window.Bitrix24FormObject];
		b24form.forms = b24form.forms || [];
		var forms = b24form.forms;
		forms.ntpush = forms.push;
		forms.push = function (params)
		{
			forms.ntpush(params);
			this.preLoad(params);
		}.bind(this);
		forms.forEach(this.preLoad, this);
	},
	preLoad: function(params)
	{
		var _this = this;
		switch(params.type)
		{
			case 'click':
			case 'button':
			case 'link':
				var defaultNode = document.getElementById(this.defaultNodeId + params.type);
				var defaultClickClassNodeList = document.getElementsByClassName("b24-web-form-popup-btn-" + params.id);
				var click = params.click || null;
				if(!click && defaultClickClassNodeList && defaultClickClassNodeList.length > 0)
				{
					click = [];
					for(var i = 0; i < defaultClickClassNodeList.length; i++)
					{
						click.push(defaultClickClassNodeList.item(i));
					}
				}
				else if(!click && defaultNode)
				{
					click = defaultNode.nextElementSibling;
				}

				if(click && Object.prototype.toString.call(click) != "[object Array]")
				{
					click = [click];
				}

				var formInstance = params;
				if(this.isFormExisted(params))
				{
					formInstance = this.forms[this.getUniqueLoadId(params)];
				}
				click.forEach(function(buttonNode){
					var _this = this;
					this.addEventListener(buttonNode, 'click', function(){_this.showPopup(formInstance);});
				}, this);
				break;
			case 'delay':
				window.setTimeout(
					function(){_this.showPopup(params);},
					1000 * (params.delay ? params.delay : 5)
				);
				break;
			case 'inline':
			default:
				this.load(params);
				break;
		}
	},
	createPopup: function(params)
	{
		if(this.isFormExisted(params))
			return;

		var _this = this;
		var popup = document.createElement('div');

		popup.innerHTML = '' +
			'<div style="display: none; position: fixed; width: 100%; min-height: 100%; background-color: rgba(0,0,0,0.5); overflow: hidden;  z-index: 10000; top: 0; right: 0; bottom: 0; left: 0;">' +
				'<div style="position: absolute; top: 50%; left: 50%; margin: 0 auto; min-width: 300px; min-height: 110px; background: #fff; -webkit-transform: translate(-50%, -50%); -moz-transform: translate(-50%, -50%); transform: translate(-50%, -50%); -webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box; -webkit-box-shadow: 1px 1px 10px 1px rgba(0,0,0,0.5); -moz-box-shadow: 1px 1px 10px 1px rgba(0,0,0,0.5); box-shadow: 1px 1px 10px 1px rgba(0,0,0,0.5);">' +
					'<div style="position: absolute; top: -10px; right: -10px; cursor: pointer; z-index: 1;">' +
						'<div data-bx-form-popup-close="" style="width: 20px; height: 20px; -webkit-border-radius: 50%;  -moz-border-radius: 50%; border-radius: 50%; background: rgba(0,0,0, .5);">' +
							'<svg viewbox="-5 -5 50 50"><path style="stroke: #fff; fill: transparent; stroke-width: 5;" d="M 10,10 L 30,30 M 30,10 L 10,30" /></svg>' +
						'</div>' +
					'</div>' +
					'<div data-bx-form-popup-cont="" style="margin: 0 auto; min-width: 600px; -webkit-overflow-scrolling: touch;"></div>' +
				'</div>' +
			'</div>';
		popup = popup.children[0];
		var node = popup.querySelector('[data-bx-form-popup-cont]');
		var btn = popup.querySelector('[data-bx-form-popup-close]');
		this.addEventListener(popup, 'click', function(){_this.hidePopup(params)});
		this.addEventListener(btn, 'click', function(){_this.hidePopup(params)});
		if(document.body.children[0])
		{
			document.body.insertBefore(popup, document.body.children[0]);
		}
		else
		{
			document.body.appendChild(popup);
		}

		// fix ios form jumping after show keyboard
		var styleFixNode = document.createElement('STYLE');
		styleFixNode.setAttribute("type", "text/css");
		styleFixNode.appendChild(document.createTextNode(
			'html.bx-ios-fix-frame-focus, .bx-ios-fix-frame-focus body {'
			+ 'height: 100%;'
			+ 'overflow: auto;'
			+ '-webkit-overflow-scrolling: touch;'
			+ '}'
		));
		document.head.appendChild(styleFixNode);

		params.popup = popup;
		params.node = node;

		this.addEventListener(window, 'resize', function () {
			_this.resizePopup(params);
		});

		// add iframe keyboard event handler
		this.addEventHandler(params, 'keyboard', function (form, keyCode) {
			if (keyCode == 27) _this.hidePopup(form);
		});

		// add listener for escape button
		this.addEventListener(document, 'keyup', function (e) {
			e = e || window.e;
			var kc = (typeof e.which == "number") ? e.which : e.keyCode;
			if (kc == 27)
			{
				_this.hidePopup(params);
			}
		});
	},
	resizePopup: function(form)
	{
		if(!form || !form['popup'] || !form['node'])
		{
			return;
		}

		var interfaceMagic = 100;
		var heightValues = [
			document.body.scrollHeight, document.documentElement.scrollHeight,
			document.body.offsetHeight, document.documentElement.offsetHeight,
			document.body.clientHeight, document.documentElement.clientHeight
		];
		heightValues = heightValues.filter(function (heightValue) {
			return heightValue > 0;
		});
		var windowHeight = Math.min.apply(Math, heightValues);

		var popupHeight = windowHeight - interfaceMagic;
		var needScroll = popupHeight <= form.frameHeight;

		if(needScroll)
		{
			form.node.style['overflow-y'] = 'scroll';
			form.node.style['height'] = popupHeight + 'px';
		}
		else
		{
			form.node.style['overflow-y'] = 'hidden';
			form.node.style.height = null;
		}

		var width = Math.min(
			document.body.scrollWidth, document.documentElement.scrollWidth,
			document.body.offsetWidth, document.documentElement.offsetWidth,
			document.body.clientWidth, document.documentElement.clientWidth
		);
		width -= 20;

		if(width < 300) width = 300;
		else if(width > 600) width = 600;
		form.node.style['min-width'] = width + 'px';
	},
	showPopup: function(params)
	{
		if(!params.popup)
		{
			this.createPopup(params);
			this.load(params);
		}

		if(params.popup)
		{
			if(this.util.isIOS()) this.util.addClass(document.documentElement, 'bx-ios-fix-frame-focus');
			params.popup.style.display = 'block';
		}
	},
	hidePopup: function(params)
	{
		params.popup.style.display = 'none';
		if(this.util.isIOS()) this.util.removeClass(document.documentElement, 'bx-ios-fix-frame-focus');
	},
	scrollToPopupMiddle: function(uniqueLoadId)
	{
		var form = this.forms[uniqueLoadId];
		if(!form)
		{
			return;
		}

		var h;
		if (form.popup)
		{
			h = form.node.scrollHeight/2 - 200;
			form.node.scrollTop = h > 0 ? h : 0;
		}
		else if (window.BX && window.BX.pos)
		{
			h = form.iframe.scrollHeight/2 - 200;
			var pos = BX.pos(form.iframe);
			h += pos.top;

			var screenHeight = document.documentElement.clientHeight;
			var scrollOffset = window.pageYOffset;

			if (h && (h < scrollOffset || h > (scrollOffset + screenHeight)))
			{
				window.scrollTo(window.scrollWidth, h);
			}
		}
	},
	util: {
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
		hasClass: function(node, className)
		{
			var classList = this.nodeListToArray(node.classList);
			var filtered = classList.filter(function (name) { return name == className});
			return filtered.length > 0;
		},
		isIOS: function()
		{
			return (/(iPad;)|(iPhone;)/i.test(navigator.userAgent));
		},
		isMobile: function()
		{
			return (/(ipad|iphone|android|mobile|touch)/i.test(navigator.userAgent));
		}
	},
	createFrame: function(params)
	{
		var formUrl = params.page || (this.domain + '/pub/form.php');
		formUrl += formUrl.indexOf('?') > -1 ? '&' : '?';

		var frame = document.createElement('iframe');
		var frameName = 'bx_form_iframe_' + params.id;
		var locationHash = {
			domain: window.location.protocol + '//' + window.location.host,
			from: window.location.href
		};
		if(params.fields)
		{
			locationHash.fields = params.fields;
		}
		if(params.options)
		{
			locationHash.options = params.options;
		}
		if(params.presets)
		{
			locationHash.presets = params.presets;
		}

		var frameSrc = formUrl + 'view=frame&' +
			'form_id=' + params.id + '&widget_user_lang=' + params.lang + '&sec=' + params.sec + '&r=' + (1*new Date()) +
			'#' + encodeURIComponent(JSON.stringify(locationHash));

		frame.setAttribute('id', frameName);
		frame.setAttribute('name', frameName);
		frame.setAttribute('src', frameSrc);

		frame.setAttribute('scrolling', 'no');
		frame.setAttribute('frameborder', '0');
		frame.setAttribute('marginheight', '0');
		frame.setAttribute('marginwidth', '0');
		frame.setAttribute('style', 'width: 100%; height: ' + this.frameHeight + 'px; border: 0px; overflow: hidden; padding: 0; margin: 0;'); //max-width: 600px;

		return frame;
	},
	getUniqueLoadId: function(params)
	{
		var type = params.type;
		switch(type)
		{
			case 'click':
			case 'button':
			case 'link':
				type = 'button';
				break;
		}

		return type + '_' + params.id;
	},
	isFormExisted: function(params)
	{
		return !!this.forms[this.getUniqueLoadId(params)];
	},
	load: function(params)
	{
		if(this.isFormExisted(params))
			return;

		params.loaded = false;
		params.handlers = params.handlers || {};
		params.options = params.options || {};

		this.execEventHandler(params, 'init', [params]);

		var uniqueLoadId = this.getUniqueLoadId(params);
		this.forms[uniqueLoadId] = params;
		var node = params.node ? params.node : null;
		var defaultNode = document.getElementById(this.defaultNodeId + params.type);
		if(!node && !defaultNode)
			return;

		if (!params.ref)
		{
			var scriptNode = document.querySelector('script[src*="/bitrix/js/crm/form_loader.js"]')
			if (scriptNode)
			{
				params.ref = scriptNode.src;
			}
		}
		
		this.domain = params.ref.match(/((http|https):\/\/[^\/]+?)\//)[1];

		var iframe = this.createFrame(params);
		params.iframe = iframe;

		if(node)
			node.appendChild(iframe);
		else
			defaultNode.parentNode.insertBefore(iframe, defaultNode);

		var _this = this;
		this.addEventListener(iframe, 'load', function(){_this.onFrameLoad(uniqueLoadId);});


		if (!this.isMessageListenerAdded)
		{
			this.addEventListener(window, 'message', function(event){
				if(event && event.origin == _this.domain)
				{
					_this.doFrameAction(event.data);
				}
			});
			this.isMessageListenerAdded = true;
		}
	},
	unload: function(params)
	{
		if(!this.isFormExisted(params))
			return;

		this.execEventHandler(params, 'unload', [params]);

		var uniqueLoadId = this.getUniqueLoadId(params);
		var iframe = this.forms[uniqueLoadId].iframe;
		if (iframe && null != iframe.parentNode)
			iframe.parentNode.removeChild(iframe);

		this.forms[uniqueLoadId] = null;
	},
	doFrameAction: function(dataString, uniqueLoadId)
	{
		var data = {};
		try { data = JSON.parse(dataString); } catch (err){}
		if(!data.action || !data.value) return;

		switch (data.action)
		{
			case 'change_height':
				this.setFrameHeight(data.uniqueLoadId || uniqueLoadId, parseInt(data.value));
				break;
			case 'popup_showed':
				this.scrollToPopupMiddle(data.uniqueLoadId || uniqueLoadId);
				break;
			case 'guestLoader':
				if (!this.isGuestLoaded() && data.value)
				{
					eval(data.value);
					this.guestLoadedChecker();
				}
				break;
			case 'redirect':
				window.location = data.value;
				break;
			case 'keyboard':
				if (data.value == 27)
				{
					var form = this.forms[data.uniqueLoadId || uniqueLoadId];
					if(form) this.execEventHandler(form, 'keyboard', [form, data.value]);
				}
				break;
			case 'event':
				var form = this.forms[data.uniqueLoadId || uniqueLoadId];
				if(form) this.execEventHandler(form, data.eventName, data.value);
				break;
			case 'analytics':
				data.value.forEach(function(item) {
					if (item.type === 'ga' && window.gtag)
					{
						if (item.params[0] === 'pageview')
						{
							if (window.dataLayer)
							{
								var filtered = window.dataLayer.filter(function(item) {
									return item[0] === 'config';
								}).map(function (item) {
									return item[1]
								});
								if (filtered.length > 0)
								{
									window.gtag('config', filtered[0], {
										//'page_title' : item.params[2],
										'page_path': item.params[1]
									});
								}
							}
						}
						else if (item.params[0] === 'event')
						{
							window.gtag('event', item.params[2], {
								'event_category': item.params[1]
							});
						}
					}
					else if (item.type === 'ga' && window.dataLayer)
					{
						if (item.params[0] === 'pageview')
						{
							window.dataLayer.push({
								'event': 'VirtualPageview',
								//'virtualPageTitle': item.params[2],
								'virtualPageURL': item.params[1]
							});
						}
						else if (item.params[0] === 'event')
						{
							window.dataLayer.push({
								'event': 'crm-form',
								'eventCategory': item.params[1],
								'eventAction': item.params[2]
							});
						}
					}
					else if (item.type === 'ga' && window.ga)
					{
						var isGaExists = window.ga.getAll().filter(function(tracker){
							return tracker.get('trackingId') == item.gaId
						}).length > 0;
						if (!item.gaId || !isGaExists)
						{
							if (item.params[2])
								window.ga('send', item.params[0], item.params[1], item.params[2]);
							else
								window.ga('send', item.params[0], item.params[1]);
						}
					}
					else if (item.type === 'ya' && !window['yaCounter' + item.yaId])
					{
						if (!this.yaId && window['Ya'])
						{
							if (Ya.Metrika && Ya.Metrika.counters()[0])
							{
								this.yaId = Ya.Metrika.counters()[0].id;
							}
							else if (Ya.Metrika2 && Ya.Metrika2.counters()[0])
							{
								this.yaId = Ya.Metrika2.counters()[0].id;
							}

						}
						if (this.yaId && window['yaCounter' + this.yaId])
						{
							window['yaCounter' + this.yaId].reachGoal(item.params[0]);
						}
					}
				});
				break;
		}
	},
	checkHash: function(uniqueLoadId)
	{
		var dataString = window.location.hash.substring(1);
		this.doFrameAction(dataString, uniqueLoadId);

		var _this = this;
		setTimeout(function(){_this.checkHash(uniqueLoadId)}, 500);
	},
	sendDataToFrame: function(uniqueLoadId, data)
	{
		if(typeof window.postMessage !== 'function')
		{
			return;
		}

		var form = this.forms[uniqueLoadId];
		data = data || {};

		form.iframe.contentWindow.postMessage(
			JSON.stringify(data), this.domain
		);
	},
	onFrameLoad: function(uniqueLoadId)
	{
		var form = this.forms[uniqueLoadId];
		if (window.BX && window.BX.onCustomEvent)
		{
			BX.onCustomEvent('onFormFrameLoad', [form, uniqueLoadId]);
		}

		var ie = 0 /*@cc_on + @_jscript_version @*/;
		if(typeof window.postMessage === 'function' && !ie)
		{
			var frameParameters = {
				'domain': this.domain,
				'uniqueLoadId': uniqueLoadId
			};

			if (window.b24Tracker && window.b24Tracker.guest)
			{
				var pages = window.b24Tracker.guest.getPages();
				if (pages && pages.length > 0)
				{
					frameParameters.visitedPages = pages;
				}
			}

			this.execEventHandler(form, 'init-frame-params', [form, frameParameters]);
			//init postMessage
			this.sendDataToFrame(uniqueLoadId, frameParameters)
		}
		else
		{
			this.checkHash(uniqueLoadId);
		}

		this.addEventHandler(form, 'send', function (data) {
			if (window.b24Tracker && window.b24Tracker.guest)
			{
				window.b24Tracker.guest.link(data.gid);
			}
		});

		form.loaded = true;
		this.onGuestLoaded();
		this.execEventHandler(form, 'load', [form]);
	},

	isGuestLoaded: function()
	{
		return window.b24Tracker && window.b24Tracker.guest;
	},
	guestLoadedChecker: function()
	{
		if (this.onGuestLoaded())
		{
			return;
		}

		setTimeout(this.guestLoadedChecker.bind(this), 300);
	},
	onGuestLoaded: function()
	{
		if (!this.isGuestLoaded())
		{
			return false;
		}

		for (var uniqueLoadId in this.forms)
		{
			if (!this.forms.hasOwnProperty(uniqueLoadId))
			{
				continue;
			}

			var form = this.forms[uniqueLoadId];
			if (!form || form.guestLoaded || !form.loaded)
			{
				continue;
			}

			form.guestLoaded = true;

			var trace;
			if (form.options.siteButton && BX.SiteButton && BX.SiteButton.getTrace)
			{
				trace = BX.SiteButton.getTrace();
			}
			else
			{
				trace = window.b24Tracker.guest.getTrace();
			}
			this.sendDataToFrame(uniqueLoadId, {action: 'setTrace', trace: trace});
		}

		return true;
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
	addEventHandler: function(target, eventName, handler)
	{
		if (!eventName || !handler)
		{
			return;
		}

		this.eventHandlers.push({
			'target': target,
			'eventName': eventName,
			'handler': handler
		});
	},
	execEventHandler: function(target, eventName, params)
	{
		params = params || [];
		if (!eventName)
		{
			return;
		}

		this.eventHandlers.forEach(function (eventHandler) {
			if (eventHandler.eventName != eventName)
			{
				return;
			}
			if (eventHandler.target != target)
			{
				return;
			}

			eventHandler.handler.apply(this, params);
		}, this);

		if(target == this)
		{
			// global events
		}
		else
		{
			if(target.handlers && target.handlers[eventName])
			{
				target.handlers[eventName].apply(this, params);
			}
		}
	},
	
	setFrameHeight: function(uniqueLoadId, height)
	{
		var form = this.forms[uniqueLoadId];
		if(!form)
		{
			return;
		}

		if(form['frameHeight'] && form.frameHeight == height) return;

		form.frameHeight = height;
		form.iframe.style['height'] = height + 'px';

		if(form.popup)
		{
			this.resizePopup(form);
		}
	}
};

Bitrix24FormLoader.init();
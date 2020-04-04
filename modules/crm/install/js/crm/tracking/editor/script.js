;(function () {

	'use strict';

	/** @requires module:webpacker */
	/** @requires {Object} module */

	var Helper = {
		context: null,
		urlParameters: null,
		getNode: function (role)
		{
			return this.context.querySelector('[data-role="' + role + '"]');
		},
		changeClass: function (node, className, mode)
		{
			if (mode === true)
			{
				node.classList.add(className);
			}
			else if (mode === false)
			{
				node.classList.remove(className);
			}
			else
			{
				node.classList.toggle(className);
			}
		},
		getPosition: function(el)
		{
			var pos;
			try
			{
				var r = el.getBoundingClientRect();
				pos = {
					top: r.top,
					left: r.left,
					width: r.width,
					height: r.height,
					right: r.right,
					bottom: r.bottom
				}
			}
			catch(e)
			{
				pos = {
					top: el.offsetTop,
					left: el.offsetLeft,
					width: el.offsetWidth,
					height: el.offsetHeight,
					right: el.offsetLeft + el.offsetWidth,
					bottom: el.offsetTop + el.offsetHeight
				};
			}

			var root = document.documentElement;
			var body = document.body;

			pos.top += (root.scrollTop || body.scrollTop);
			pos.left += (root.scrollLeft || body.scrollLeft);
			pos.right += (root.scrollLeft || body.scrollLeft);
			pos.bottom += (root.scrollTop || body.scrollTop);
			pos.width = pos.right - pos.left;
			pos.height = pos.bottom - pos.top;

			for(var i in pos)
			{
				if(pos.hasOwnProperty(i))
				{
					pos[i] = Math.round(pos[i]);
				}
			}

			return pos;
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
		removeUrlParam: function(url, param)
		{
			if (param && Object.prototype.toString.call(param) === "[object Array]")
			{
				for (var i=0; i<param.length; i++)
				{
					url = BX.util.remove_url_param(url, param[i]);
				}
			}
			else
			{
				var pos, params;
				if((pos = url.indexOf('?')) >= 0 && pos !== url.length-1)
				{
					params = url.substr(pos + 1);
					url = url.substr(0, pos + 1);

					params = params.replace(new RegExp('(^|&)'+param+'=[^&#]*', 'i'), '');
					params = params.replace(/^&/, '');

					if(params && params !== '')
					{
						url = url + params;
					}
					else
					{
						//remove trailing question character
						url = url.substr(0, url.length - 1);
					}
				}
			}
			return url;
		}
	};


	var Template = {
		createNode: function (templateName)
		{
			return (Helper.getNode('tracker/tmpl/' + templateName)).cloneNode(true);
		},
		createItemSimpleNode: function (text)
		{
			var node = this.createNode('item/simple');
			node.children[0].textContent = text;
			return node;
		},
		createItemReplaceNode: function (textFrom, textTo, iconClass)
		{
			var node = this.createNode('item/replace');
			var childNode = node.children[0];
			childNode.children[0].textContent = textFrom;
			childNode.children[1].textContent = textTo;
			if (iconClass)
			{
				childNode.children[1].classList.add(iconClass);
			}

			return node;
		},
		createItemIconNode: function (text, iconClass)
		{
			iconClass = iconClass || '';
			var node = this.createNode('item/icon');
			node.children[0].textContent = text;
			node.children[0].classList.add(iconClass.trim() || 'b24-tracker-item-name-empty');

			return node;
		}
	};

	var Effect = {
		shadowNode: null,
		edgeNode: null,
		classShadowShow: 'b24-tracker-shadow-show',
		classShadowHide: 'b24-tracker-shadow-hide',
		classHighlight: 'b24-tracker-shadow-highlight',
		init: function ()
		{
			this.shadowNode = Helper.getNode('tracker/shadow');
			this.edgeNode = Helper.getNode('tracker/shadow/filter');
		},
		highlight: function (node, isHighlight, e)
		{
			if (e)
			{
				e.preventDefault();
				e.stopPropagation();
			}

			if (node.nodeName === '#text')
			{
				node = node.parentNode;
			}

			var self = this;
			isHighlight = isHighlight || false;

			if (isHighlight)
			{
				this.isShadowShowed = true;

				this.animateScrollTo(node, 800, function () {
					var edgePos = node.getBoundingClientRect();
					self.edgeNode.setAttribute('x', edgePos.left - 5);
					self.edgeNode.setAttribute('y', edgePos.top - 5);
					self.edgeNode.setAttribute('width', edgePos.width + 10);
					self.edgeNode.setAttribute('height', edgePos.height + 10);
				});

				this.shadowNode.classList.add(this.classShadowShow);
				this.shadowNode.classList.remove(this.classShadowHide);
				node.classList.add(this.classHighlight);
			}
			else
			{
				this.isShadowShowed = false;
				setTimeout(function () {
					if (!self.isShadowShowed)
					{
						self.shadowNode.classList.add(self.classShadowHide);
						self.shadowNode.classList.remove(self.classShadowShow);
					}
				}, 0);

				node.classList.remove(this.classHighlight);
			}
		},
		animateScrollTo: function(node, duration, callback)
		{
			var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
			var screenHeight = document.documentElement.clientHeight;
			var nodeTop = Helper.getPosition(node).top - screenHeight / 2 + node.offsetHeight / 2;

			this.easing({
				duration: duration || 800,
				start: scrollTop,
				finish: nodeTop,
				step: function(state) {
					window.scrollTo(0, state);
					if (callback)
					{
						callback();
					}
				},
				onFinish: function () {

				}
			});
		},
		easing: function(options)
		{
			if (!window.requestAnimationFrame)
			{
				options.step(options.finish);
				return;
			}

			var delta = function(progress)
			{
				return Math.pow(progress, 2);
			};
			var transition = function(progress) {
				return 1 - delta(1 - progress);
			};

			var startTimestamp = null;
			(function (options)
			{
				var timer = function (timestamp)
				{

					if (!startTimestamp)
					{
						startTimestamp = timestamp;
					}

					var progress = (timestamp - startTimestamp) / options.duration;
					if (progress > 1)
					{
						progress = 1;
					}

					options.step(Math.round(
						options.start
						+ (options.finish - options.start)
						* transition(progress)
					));

					if (progress < 1)
					{
						window.requestAnimationFrame(timer);
					}
					else if(options.onFinish)
					{
						options.onFinish();
					}
				};

				window.requestAnimationFrame(timer);
			})(options);
		}
	};

	var Selector = {
		classClosed: 'b24-tracker-list-closed',
		classDisabled: 'b24-tracker-selector-disabled',
		attributeDisabled: 'data-disabled',
		toggle: function (node)
		{
			Helper.changeClass(node, this.classClosed, this.isDisabled(node) ? true : null);
		},
		isDisabled: function (node)
		{
			return node.getAttribute(this.attributeDisabled) === 'y';
		},
		changeDisabled: function (node, isDisabled)
		{
			node.setAttribute(this.attributeDisabled, isDisabled ? 'y' : 'n');
			Helper.changeClass(node, this.classDisabled, isDisabled);
		},
		close: function (node)
		{
			Helper.changeClass(node, this.classClosed, true);
		},
		select: function (node, containerNode)
		{
			containerNode.innerHTML = '';
			containerNode.appendChild(node.children[0].cloneNode(true));
		},
		init: function (node)
		{
			Helper.addEventListener(node, 'click', this.toggle.bind(this, node));
			Helper.addEventListener(window, 'click', function (e)
			{
				if (!node.contains(e.target))
				{
					Selector.close(node);
				}
			});
		}
	};

	var PhoneSelector = {
		isInit: false,
		init: function (items)
		{
			this.selectorNode = Helper.getNode('tracker/phone');
			this.containerNode = Helper.getNode('tracker/phone/items');
			this.selectedNode = Helper.getNode('tracker/phone/selected');

			this.countPhone = 0;
			this.countEmail = 0;
			this.containerNode.innerHTML = '';

			if (items.length === 0)
			{
				Selector.select(Template.createItemIconNode(module.message('notFound')), this.selectedNode);
				this.disable();
				return;
			}

			items.forEach(this.addItem, this);
			Selector.select(
				Template.createItemSimpleNode(
					module.message('foundItems')
						.replace('%phones%', this.countPhone)
						.replace('%emails%', this.countEmail)
				),
				this.selectedNode
			);

			if (!this.isInit)
			{
				Selector.init(this.selectorNode);
			}
			this.isInit = true;
		},
		disable: function ()
		{
			Selector.changeDisabled(this.selectorNode, true);
		},
		enable: function ()
		{
			Selector.changeDisabled(this.selectorNode, false);
		},
		isDisabled: function ()
		{
			Selector.isDisabled(this.selectorNode);
		},
		addItem: function (item)
		{
			this.countPhone += item.values[0].type === 'phone' ? 1 : 0;
			this.countEmail += item.values[0].type === 'email' ? 1 : 0;

			var node;
			if (item.replaced)
			{
				node = Template.createItemReplaceNode(
					item.replaced.from,
					item.replaced.to
				);
			}
			else
			{
				node = Template.createItemSimpleNode(item.values[0].value);
			}

			this.containerNode.appendChild(node);
			Helper.addEventListener(node, 'click', this.onClick.bind(this));

			Helper.addEventListener(node, 'mouseenter', Effect.highlight.bind(Effect, item.node, true));
			Helper.addEventListener(node, 'mouseleave', Effect.highlight.bind(Effect, item.node, false));
		},
		onClick: function (e)
		{
			e.preventDefault();
			e.stopPropagation();
		}
	};

	var SourceSelector = {
		parameter: 'utm_source',
		current: '',
		changed: false,
		isInit: false,
		init: function (sources)
		{
			this.selectorNode = Helper.getNode('tracker/source');
			this.containerNode = Helper.getNode('tracker/source/items');
			this.selectedNode = Helper.getNode('tracker/source/selected');

			this.current = Manager.getCurrentSource() || '';
			var items = sources || [];
			if (items.length === 0)
			{
				this.disable();
				Selector.select(Template.createItemIconNode(module.message('sourcesNotConfigured')), this.selectedNode);
				return;
			}
			items = [{code: null, name: module.message('notSelected')}].concat(items);
			this.containerNode.innerHTML = '';
			items.forEach(this.addItem, this);

			if (!this.isInit)
			{
				Selector.init(this.selectorNode);
			}
			this.isInit = true;
		},
		disable: function ()
		{
			Selector.changeDisabled(this.selectorNode, true);
		},
		enable: function ()
		{
			Selector.changeDisabled(this.selectorNode, true);
		},
		isDisabled: function ()
		{
			Selector.isDisabled(this.selectorNode);
		},
		addItem: function (item)
		{
			item.node = Template.createItemIconNode(item.name, item.code);
			this.containerNode.appendChild(item.node);
			Helper.addEventListener(item.node, 'click', this.onClick.bind(this, item));

			if (item.code === this.current)
			{
				Selector.select(item.node, this.selectedNode);
			}
		},
		onClick: function (item)
		{
			Selector.select(item.node, this.selectedNode);

			var code = item.code ? item.code : '';
			this.changed = this.current !== code;
			this.current = code;
			Viewer.updateLocation(code);
		}
	};

	var Viewer = {
		parameter: 'utm_source',
		hideClass: 'b24-source-tracker-viewer-hide',
		bodyClass: 'b24-tracker-body-scroll-off',
		deviceActiveClass: 'b24-tracker-btn-active',
		mobile: false,
		init: function ()
		{
			this.container = Helper.getNode('tracker/viewer');
			this.frame = Helper.getNode('tracker/viewer/frame');

			this.desktopBtn = Helper.getNode('tracker/viewer/device/desktop');
			this.mobileBtn = Helper.getNode('tracker/viewer/device/mobile');

			Helper.addEventListener(this.desktopBtn, 'click', this.onChangeDevice.bind(this, false));
			Helper.addEventListener(this.mobileBtn, 'click', this.onChangeDevice.bind(this, true));
		},
		changeDisplay: function (show)
		{
			Helper.changeClass(this.container, this.hideClass, !show);
			Helper.changeClass(document.body, this.bodyClass, show);
		},
		onChangeDevice: function (isMobile)
		{
			Helper.changeClass(this.desktopBtn, this.deviceActiveClass, !isMobile);
			Helper.changeClass(this.mobileBtn, this.deviceActiveClass, isMobile);

			this.changeDisplay(isMobile);
			if (isMobile)
			{
				window.scrollTo(0, 0);
			}

			this.mobile = isMobile;
			this.updateLocation(null);
			this.mobile ? PhoneSelector.disable() : PhoneSelector.enable();
		},
		isMobile: function ()
		{
			return this.mobile;
		},
		updateLocation: function (sourceCode)
		{
			var url = this.frame.src;
			if (!this.mobile || !url)
			{
				url = window.location.href.split('#')[0];
			}

			if (sourceCode !== null)
			{
				url = Helper.removeUrlParam(url, this.parameter);
				url += (url.indexOf('?') > -1 ? '&' : '?') + (this.parameter + '=' + sourceCode);
			}

			if (this.mobile)
			{
				this.frame.src = url;
			}
			else if (SourceSelector.changed)
			{
				window.location.assign(url);
			}
		}
	};

	var Manager = {
		classSwitchDown: 'b24-tracker-wrapper-down',
		status: null,
		init: function (options)
		{
			this.connector = new b24Tracker.Connector({
				addressee: webPacker.getAddress(),
				responders: {
					'b24.portal.refresh': function ()
					{
						window.location.reload();
					}
				}
			});
			this.connector.request(window.opener, 'tracking.editor.getData', {}, this.load.bind(this));

			this.status = options.status;
			this.context = document.getElementById('b24-source-tracker-editor');
			Helper.context = this.context;
			Effect.init(this.context);
			Viewer.init(options);

			this.items = options.items;

			this.margin = Helper.getNode('tracker/margin');
			this.buttonClose = Helper.getNode('tracker/btn/close');
			this.buttonSwitch = Helper.getNode('tracker/btn/switch');
			if (this.buttonClose)
			{
				Helper.addEventListener(this.buttonClose, 'click', this.close.bind(this));
			}
			if (this.buttonSwitch)
			{
				Helper.addEventListener(this.buttonSwitch, 'click', this.switch.bind(this));
			}

			if (this.status.fields.get('bottom'))
			{
				this.switch();
			}
		},
		load: function (options)
		{
			window.b24Tracker.Manager.Instance.run(options);

			PhoneSelector.init(this.items || []);
			SourceSelector.init(options.sources);
		},
		close: function ()
		{
			if (this.status)
			{
				this.status.stop();
			}

			window.close();
		},
		switch: function ()
		{
			this.context.classList.toggle(this.classSwitchDown);
			var isOnBottom = this.context.classList.contains(this.classSwitchDown);
			if (isOnBottom)
			{
				document.body.appendChild(this.margin);
			}
			else
			{
				this.context.insertBefore(this.margin, this.context.firstChild);
			}

			if (this.status)
			{
				this.status.fields.set('bottom', isOnBottom);
			}
		},
		getCurrentSource: function ()
		{
			return (this.status ? this.status.fields.get('source') : null);
		}
	};

	if (!window.b24Tracker) window.b24Tracker = {};
	if (!b24Tracker.Editor) b24Tracker.Editor = {};
	if (b24Tracker.Editor.Manager) return;

	b24Tracker.Editor.Manager = Manager;

	if (b24Tracker.Editor.Status)
	{
		b24Tracker.Editor.Status.onEditorInit();
	}
})();
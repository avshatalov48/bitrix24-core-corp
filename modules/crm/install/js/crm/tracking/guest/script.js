;(function() {

	"use strict";

	/** @requires module:webpacker */
	/** @requires {Object} module */
	if(typeof webPacker === "undefined")
	{
		return;
	}

	/** @var {Object} b24Tracker */
	window.b24Tracker = window.b24Tracker || {};
	if (window.b24Tracker.guest)
	{
		return;
	}

	window.b24Tracker.guest = {
		cookieName: 'b24_crm_guest_id',
		returnCookieName: 'b24_crm_guest_id_returned',
		requestUrl: '',
		isInit: false,
		init: function()
		{
			if (this.isInit)
			{
				return;
			}
			this.isInit = true;
			this.requestUrl = (webPacker.getAddress() + '/').match(/((http|https):\/\/[^\/]+?)\//)[1] + '/pub/guest.php';

			if (module.properties['lifespan'])
			{
				var lifespan = parseInt(module.properties['lifespan']);
				if (!isNaN(lifespan) && lifespan)
				{
					TagTracker.lifespan = lifespan;
					RefTracker.lifespan = lifespan;
				}
			}

			TraceTracker.collect();
			TagTracker.collect();
			RefTracker.collect();
			PageTracker.collect();
			this.checkReturn();

			window.b24order = window.b24order || [];
			window.b24order.forEach(function (options) {
				this.registerOrder(options);
			}, this);
			window.b24order.push = function (options) {
				this.registerOrder(options);
			}.bind(this);
		},
		checkReturn: function()
		{
			if (!this.getGidCookie() || !window.sessionStorage || sessionStorage.getItem(this.returnCookieName))
			{
				return;
			}

			Request.query(this.requestUrl, {gid: this.getGidCookie(), a: 'event', e: 'Return'}, this.onAjaxResponse.bind(this));
			this.markReturned();
		},
		storeTrace: function(trace, action)
		{
			action = action || 'storeTrace';
			Request.query(this.requestUrl, {a: action, d: {trace: trace}});
		},
		link: function(gid)
		{
			if (!gid || this.getGidCookie())
			{
				return;
			}

			Request.query(this.requestUrl, {a: 'link', gid: gid}, this.onAjaxResponse.bind(this));
		},
		register: function()
		{
			if (this.getGidCookie())
			{
				return;
			}

			Request.query(this.requestUrl, {a: 'register'}, this.onAjaxResponse.bind(this));
		},
		onAjaxResponse: function (response)
		{
			response = response || {};
			response.data = response.data || {};
			if (!this.getGidCookie() && !!response.data.gid)
			{
				webPacker.ls.setItem(this.cookieName, response.data.gid);
				this.markReturned();
			}
		},
		getPages: function ()
		{
			return PageTracker.list();
		},
		getTags: function ()
		{
			return TagTracker.list();
		},
		registerOrder: function(options)
		{
			if (!module.properties['canRegisterOrder'])
			{
				return;
			}
			this.storeTrace(this.getTraceOrder(options), 'registerOrder');
		},
		getTraceOrder: function (options)
		{
			options = options || {};
			var id = options.id || '';

			if (!Number.isNaN(id) && typeof id === 'number')
			{
				id = id.toString();
			}
			if (!id || !webPacker.type.isString(id) || !id.match(/^[\d\w.\-\/\\_#]{1,30}$/i))
			{
				if (window.console && window.console.error)
				{
					window.console.error('Wrong order id: ' + options.id);
				}
			}

			var sum = parseFloat(options.sum);
			if (isNaN(sum) || sum < 0)
			{
				if (window.console && window.console.error)
				{
					window.console.error('Wrong order sum: ' + options.sum);
				}
			}

			this.sentOrders = this.sentOrders || [];
			if (this.sentOrders.indexOf(id) >= 0)
			{
				return;
			}
			this.sentOrders.push(id);

			return this.getTrace({
				channels: [
					{code: 'order', value: id}
				],
				order: {id: id, sum: sum}
			});
		},
		getTrace: function (options)
		{
			var trace = this.remindTrace(options);
			TraceTracker.clear();
			return trace;
		},
		remindTrace: function (options)
		{
			return JSON.stringify(TraceTracker.current(options));
		},
		getUtmSource: function ()
		{
			return this.getTags().utm_source || '';
		},
		isUtmSourceDetected: function ()
		{
			return TagTracker.isSourceDetected();
		},
		getGidCookie: function ()
		{
			return webPacker.ls.getItem(this.cookieName);
		},
		setGid: function(gid)
		{
			this.markReturned();
			return webPacker.ls.setItem(this.cookieName, gid);
		},
		markReturned: function()
		{
			if (window.sessionStorage)
			{
				sessionStorage.setItem(this.returnCookieName, 'y');
			}
		}
	};

	var TraceTracker = {
		maxCount: 5,
		lsKey: 'b24_crm_guest_traces',
		previous: function ()
		{
			return webPacker.ls.getItem(this.lsKey) || {list: []};
		},
		current: function (options)
		{
			options = options || {};
			var trace = {
				url: window.location.href,
				ref: RefTracker.getData().ref,
				device: {
					isMobile: webPacker.browser.isMobile()
				},
				tags: TagTracker.getData(),
				client: ClientTracker.getData(),
				pages: {
					list: PageTracker.list()
				},
				gid: b24Tracker.guest.getGidCookie(),
			};

			if (options.previous !== false)
			{
				trace.previous = this.previous();
			}
			if (options.channels)
			{
				trace.channels = options.channels;
			}
			if (options.order)
			{
				trace.order = options.order;
			}

			return trace;
		},
		clear: function ()
		{
			webPacker.ls.removeItem(this.lsKey);
		},
		collect: function ()
		{
			if (!TagTracker.isSourceDetected() && !RefTracker.detect().newest)
			{
				return;
			}

			var current = this.current({previous: false});
			if (!current.pages.list)
			{
				return;
			}

			var data = this.previous();
			data = data || {};
			data.list = data.list || [];

			data.list.push(this.current({previous: false}));
			if (data.list.length > this.maxCount)
			{
				data.list.shift();
			}

			TagTracker.clear();
			PageTracker.clear();

			webPacker.ls.setItem(this.lsKey, data);
		}
	};

	var ClientTracker = {
		getData: function ()
		{
			var data = {
				gaId: this.getGaId(),
				yaId: this.getYaId(),
			};
			if (!data.gaId) delete data['gaId'];
			if (!data.yaId) delete data['yaId'];
			return data;
		},
		getGaId: function ()
		{
			var id;
			if (typeof window.ga === 'function')
			{
				ga(function(tracker) {
					id = tracker.get('clientId');
				});
				if (id)
				{
					return id;
				}

				if (ga.getAll && ga.getAll()[0])
				{
					id = ga.getAll()[0].get('clientId');
				}
			}

			if (id)
			{
				return id;
			}

			id = (document.cookie || '').match(/_ga=(.+?);/);
			if (id)
			{
				id = (id[1] || '').split('.').slice(-2).join(".")
			}

			return id ? id : null;
		},
		getYaId: function ()
		{
			var id;
			if (window.Ya)
			{
				var yaId;
				if (Ya.Metrika && Ya.Metrika.counters()[0])
				{
					yaId = Ya.Metrika.counters()[0].id;
				}
				else if (Ya.Metrika2 && Ya.Metrika2.counters()[0])
				{
					yaId = Ya.Metrika2.counters()[0].id;
				}

				if (!yaId)
				{
					return null;
				}

				if (window.ym && typeof window.ym === 'object')
				{
					ym(yaId, 'getClientID', function(clientID) {
						id = clientID;
					});
				}

				if (!id && window['yaCounter' + yaId])
				{
					id = window['yaCounter' + yaId].getClientID();
				}
			}

			if (
				!id
				&& window.ym
				&& typeof window.ym === 'function'
				&& (window.ym.a && window.ym.a[0] !== undefined)
			)
			{
				id = window.ym.a[0][0];
			}

			if (!id)
			{
				id = webPacker.cookie.get('_ym_uid');
			}

			return id ? id : null;
		}
	};

	var isUtmSourceDetected = null;
	var TagTracker = {
		lifespan: 28,
		lsPageKey: 'b24_crm_guest_utm',
		tags: ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'],
		sameTagLifeSpan: 3600,
		list: function ()
		{
			return this.getData().list || {};
		},
		isSourceDetected: function ()
		{
			if (isUtmSourceDetected === null)
			{
				var key = this.tags[0];
				var tag = webPacker.url.parameter.get(key);
				if (tag === null || !tag)
				{
					isUtmSourceDetected = false;
				}
				else if (this.list()[key] !== tag)
				{
					isUtmSourceDetected = true;
				}
				else
				{
					isUtmSourceDetected = (this.getTimestamp(true) - this.getTimestamp()) > this.sameTagLifeSpan;
				}
			}

			return isUtmSourceDetected;
		},
		getGCLid: function ()
		{
			return this.getData().gclid || null;
		},
		getTimestamp: function (currentOnly)
		{
			return (currentOnly ? null : parseInt(this.getData().ts)) || parseInt(Date.now() / 1000);
		},
		getData: function ()
		{
			return (webPacker.ls.isSupported() ?
				webPacker.ls.getItem(this.lsPageKey)
				:
				webPacker.cookie.getItem(this.lsPageKey)) || {};
		},
		clear: function ()
		{
			webPacker.ls.removeItem(this.lsPageKey);
		},
		collect: function ()
		{
			var timestamp = this.getTimestamp();
			var tags = webPacker.url.parameter.getList().filter(function (item) {
				return this.tags.indexOf(item.name) > -1;
			}, this);

			if (tags.length > 0)
			{
				tags = tags.filter(function (item) {
					return item.value.trim().length > 0;
				}).reduce(function (acc, item) {
					acc[item.name] = decodeURIComponent(item.value);
					return acc;
				}, {});

				timestamp = this.getTimestamp(true);
			}
			else
			{
				tags = this.list();
			}

			var gclid = webPacker.url.parameter.getList().filter(function (item) {
				return item.name === 'gclid';
			}, this).map(function (item) {
				return item.value;
			});
			gclid = gclid[0] || this.getGCLid();

			if (this.getTimestamp(true) - timestamp > this.lifespan * 3600 * 24)
			{
				this.clear();
				return;
			}

			var data = {ts: timestamp, list: tags, gclid: gclid};
			webPacker.ls.isSupported() ?
				webPacker.ls.setItem(this.lsPageKey, data)
				:
				webPacker.cookie.setItem(this.lsPageKey, data);
		}
	};

	var RefTracker = {
		lifespan: 28,
		lsKey: 'b24_crm_guest_ref',
		sameRefLifeSpan: 3600,
		detect: function ()
		{
			var r = {
				detected: false,
				existed: false,
				expired: false,
				newest: false,
				value: null
			};
			var ref = document.referrer;
			if (!ref)
			{
				return r;
			}

			var a = document.createElement('a');
			a.href = ref;
			if (!a.hostname)
			{
				return r;
			}

			if (a.hostname === window.location.hostname)
			{
				return r;
			}

			r.value = ref;
			r.detected = true;
			if (ref !== this.getData().ref)
			{
				r.newest = true;
				return r;
			}

			r.existed = true;
			if (this.getTs(true) - this.getTs() > this.sameRefLifeSpan)
			{
				r.expired = true;
				return r;
			}

			return false;
		},
		getTs: function (currentOnly)
		{
			return (currentOnly ? null : parseInt(this.getData().ts)) || parseInt(Date.now() / 1000);
		},
		getData: function ()
		{
			return (webPacker.ls.isSupported() ?
				webPacker.ls.getItem(this.lsKey, this.getTtl())
				:
				null) || {};
		},
		clear: function ()
		{
			webPacker.ls.removeItem(this.lsKey);
		},
		getTtl: function ()
		{
			return this.lifespan * 3600 * 24;
		},
		collect: function ()
		{
			var result = this.detect();
			if (!result.detected)
			{
				return;
			}

			if (result.expired)
			{
				this.clear();
				return;
			}

			webPacker.ls.setItem(
				this.lsKey,
				{ts: this.getTs(), ref: result.value},
				this.getTtl()
			);
		}
	};

	var PageTracker = {
		maxCount: 5,
		lsPageKey: 'b24_crm_guest_pages',
		list: function ()
		{
			return webPacker.ls.getItem(this.lsPageKey);
		},
		clear: function ()
		{
			webPacker.ls.removeItem(this.lsPageKey);
		},
		collect: function ()
		{
			if (!document.body)
			{
				return;
			}
			var pageTitle = document.body.querySelector('h1');
			pageTitle = pageTitle ? pageTitle.textContent.trim() : '';
			if (pageTitle.length === 0)
			{
				pageTitle = document.head.querySelector('title');
				pageTitle = pageTitle ? pageTitle.textContent.trim() : '';
			}
			pageTitle = pageTitle.substring(0, 40);

			var page = window.location.href;
			var pages = webPacker.ls.getItem(this.lsPageKey);
			pages = (pages instanceof Array) ? pages : [];
			var ind = -1;
			pages.forEach(function (item, index) {
				if (item[0] === page) ind = index;
			});
			if (ind > -1)
			{
				pages = pages.slice(0, ind).concat(pages.slice(ind + 1));
			}
			while(pages.length >= this.maxCount)
			{
				pages.shift();
			}
			var date = new Date();
			pages.push([
				page,
				Math.round(date.getTime() / 1000),
				pageTitle
			]);
			webPacker.ls.setItem(this.lsPageKey, pages);
		}
	};

	var Request = {
		query: function(url, data, onSuccess)
		{
			this.ajax = null;
			if (window.XMLHttpRequest)
			{
				this.ajax = new XMLHttpRequest();
			}
			else if (window.ActiveXObject)
			{
				this.ajax = new window.ActiveXObject("Microsoft.XMLHTTP");
			}

			("withCredentials" in this.ajax) ? this.post(url, data, onSuccess) : this.get(url, data);
		},
		get: function (url, data)
		{
			var script = document.createElement("script");
			script.type = "text/javascript";
			script.src = url + "?" + this.stringify(data);
			script.async = true;
			var s = document.getElementsByTagName("script")[0];
			s.parentNode.insertBefore(script, s);
		},
		post: function (url, data, onSuccess)
		{
			var ajax = this.ajax;
			ajax.open("POST", url, true);
			ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			ajax.withCredentials = true;

			ajax.onreadystatechange = function() {
				if (onSuccess && ajax.readyState === 4 && ajax.status === 200)
				{
					onSuccess.apply(this, [JSON.parse(this.responseText)]);
				}
			};

			ajax.send(this.stringify(data));
		},
		stringify: function (data)
		{
			var result = [];
			if (Object.prototype.toString.call(data) === "[object Array]")
			{

			}
			else if (typeof(data) === "object")
			{
				for (var key in data)
				{
					if (!data.hasOwnProperty(key))
					{
						continue;
					}

					var value = data[key];
					if (typeof(value) === "object")
					{
						value = JSON.stringify(value);
					}
					result.push(key + '=' + encodeURIComponent(value));
				}
			}

			return result.join('&');
		},
		getAjax: function ()
		{
			if (this.ajax)
			{
				return this.ajax;
			}

			if (window.XMLHttpRequest)
			{
				this.ajax = new XMLHttpRequest();
			}
			else if (window.ActiveXObject)
			{
				this.ajax = new window.ActiveXObject("Microsoft.XMLHTTP");
			}

			return this.ajax;
		}
	};

	window.b24Tracker.guest.init();

})();

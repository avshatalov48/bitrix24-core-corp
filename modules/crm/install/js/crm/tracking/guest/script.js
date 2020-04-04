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
		domain: '',
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

			PageTracker.collect();
			TagTracker.collect();
			this.checkReturn();
		},
		checkReturn: function()
		{
			if (!this.getGidCookie() || webPacker.cookie.get(this.returnCookieName))
			{
				return;
			}

			Request.query(this.requestUrl, {a: 'event', e: 'Return'}, this.onAjaxResponse.bind(this));
			webPacker.cookie.set(this.returnCookieName, 'y', 3600 * 6);
		},
		storeTrace: function(trace)
		{
			Request.query(this.requestUrl, {a: 'storeTrace', d: {trace: trace}});
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
			if (this.getGidCookie() == null && !!response.data.gid)
			{
				webPacker.cookie.set(this.cookieName, response.data.gid);
				webPacker.cookie.set(this.returnCookieName, 'y', 3600 * 6);
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
		getTrace: function (options)
		{
			options = options || {};
			var trace = {
				url: window.location.href,
				ref: document.referrer,
				device: {
					isMobile: webPacker.browser.isMobile()
				},
				tags: TagTracker.getData(),
				pages: {
					list: PageTracker.list()
				},
				gid: this.getGidCookie()
			};

			if (options.channels)
			{
				trace.channels = options.channels;
			}

			return JSON.stringify(trace);
		},
		getUtmSource: function ()
		{
			return this.getTags().utm_source || '';
		},
		getGidCookie: function ()
		{
			return webPacker.cookie.get(this.cookieName)
		}
	};

	var TagTracker = {
		lifespan: 28,
		lsPageKey: 'b24_crm_guest_utm',
		tags: ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'],
		list: function ()
		{
			return this.getData().list || {};
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
				Math.round(date.getTime() / 1000)  + date.getTimezoneOffset() * 60,
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
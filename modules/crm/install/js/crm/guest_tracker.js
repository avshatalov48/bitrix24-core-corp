;(function (window) {
	window.BX = window.BX || {};
	if (window.BX.SiteGuest)
	{
		return;
	}
	if (!window.b24CrmGuestData)
	{
		return;
	}

	window.BX.SiteGuest = {

		cookieName: 'b24_crm_guest_id',
		returnCookieName: 'b24_crm_guest_id_returned',
		domain: '',
		requestUrl: '',
		isQueueProcessed: false,
		queue: [],
		init: function()
		{
			this.trackPages();

			this.ref = window.b24CrmGuestData.ref;
			this.requestUrl = this.ref.match(/((http|https):\/\/[^\/]+?)\//)[1] + '/pub/guest.php';

			this.varName = window.b24CrmGuestData.name || 'b24CrmGuest';
			window[this.varName] = window[this.varName] || [];
			this.queue = window[this.varName];

			// handle push
			var _this = this;
			window[this.varName].push = function () {
				Array.prototype.push.apply(window[_this.varName], arguments);
				_this.queue.push(arguments);
				_this.processQueue();
			};

			this.processQueue();
			this.checkReturn();
		},
		checkReturn: function()
		{
			if (!this.getGidCookie() || this.getCookie(this.returnCookieName))
			{
				return;
			}

			this.send('send', 'event', {'eventName': 'Return'});
			this.setCookie(this.returnCookieName, 'y', 3600 * 6);
		},
		processQueue: function()
		{
			if (this.queue.length == 0) return;
			if (this.isQueueProcessed)
			{
				var _this = this;
				setTimeout(function () {
					_this.processQueue();
				}, 500);
				return;
			}

			this.isQueueProcessed = true;
			this.queue.forEach(function (callArgs) {
				this.send.apply(this, callArgs)
			}, this);
			this.isQueueProcessed = false;
		},
		send: function(op, detail, ext)
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

			var params = '';
			switch (op)
			{
				case 'register':
					if (this.getGidCookie())
					{
						return;
					}
					params += 'a=' + op;
					break;
				case 'link':
					if (this.getGidCookie())
					{
						return;
					}
					params += 'a=' + op;
					params += '&gid=' + detail;
					break;
				case 'send':
					if (!this.getGidCookie())
					{
						return;
					}
					if (detail == 'event')
					{
						ext = ext || {};
						params += 'a=event';
						params += '&e=' + ext.eventName;
					}
					break;

				default:
					return;
			}
			("withCredentials" in this.ajax) ? this.sendPost(params) : this.sendGet(params);
		},

		sendGet: function (params)
		{
			var script = document.createElement("script");
			script.type = "text/javascript";
			script.src = this.requestUrl + "?" + params;
			script.async = true;
			var s = document.getElementsByTagName("script")[0];
			s.parentNode.insertBefore(script, s);
		},

		sendPost: function (params)
		{
			var ajax = this.ajax;
			ajax.open("POST", this.requestUrl, true);
			ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			ajax.withCredentials = true;

			ajax.onreadystatechange = function() {
				if (ajax.readyState == 4 && ajax.status == 200)
				{
					window.BX.SiteGuest.onAjaxResponse(JSON.parse(this.responseText));
				}
			};

			ajax.send(params);
		},

		onAjaxResponse: function (response)
		{
			response = response || {};
			response.data = response.data || {};
			if (this.getGidCookie() == null && !!response.data.gid)
			{
				this.setCookie(this.cookieName, response.data.gid);
				this.setCookie(this.returnCookieName, 'y', 3600 * 6);
				//var cookieDate = new Date(new Date().getTime() + 1000*3600*24*365*10);
				//document.cookie = this.cookieName + "=" + response.data.gid + "; path=/; expires="+cookieDate.toUTCString();
			}
		},

		pageMaxCount: 5,
		lsPageKey: 'b24_crm_guest_pages',
		trackPages: function ()
		{

			var pageTitle = document.body.querySelector('h1');
			pageTitle = pageTitle ? pageTitle.textContent.trim() : '';
			if (pageTitle.length == 0)
			{
				pageTitle = document.head.querySelector('title');
				pageTitle = pageTitle ? pageTitle.textContent.trim() : '';
			}
			pageTitle = pageTitle.substring(0, 40);

			var page = window.location.href;
			var pages = this.localStorage.getItem(this.lsPageKey);
			pages = (pages instanceof Array) ? pages : [];
			var ind = -1;
			pages.forEach(function (item, index) {
				if (item[0] == page) ind = index;
			});
			if (ind > -1) pages = pages.slice(0, ind).concat(pages.slice(ind + 1));
			while(pages.length >= this.pageMaxCount) { pages.shift(); }
			var date = new Date();
			pages.push([
				page,
				Math.round(date.getTime() / 1000)  + date.getTimezoneOffset() * 60,
				pageTitle
			]);
			this.localStorage.setItem(this.lsPageKey, pages);
		},
		getPages: function ()
		{
			return this.localStorage.getItem(this.lsPageKey);
		},
		clearPages: function ()
		{
			return this.localStorage.removeItem(this.lsPageKey);
		},

		localStorage: {
			sup: null,
			removeItem: function (key)
			{
				if (!this.isSupported()) return;
				window.localStorage.removeItem(key);
			},
			setItem: function (key, value)
			{
				if (!this.isSupported()) return;
				try{window.localStorage.setItem(key, JSON.stringify(value));}
				catch (e) {}
			},
			getItem: function (key)
			{
				if (!this.isSupported()) return null;
				try{return JSON.parse(window.localStorage.getItem(key));}
				catch (e) {return null;}
			},
			isSupported: function ()
			{
				if (this.sup === null )
				{
					this.sup = false;
					try
					{
						var mod = 'b24crm-x-test';
						window.localStorage.setItem(mod, 'x');
						window.localStorage.removeItem(mod);
						this.sup = true;
					}catch(e){}
				}
				return this.sup;
			}
		},

		setCookie: function (name, value, expires)
		{
			expires = expires || 3600*24*365*10;
			var cookieDate = new Date(new Date().getTime() + 1000 * expires);
			document.cookie = name + "=" + value + "; path=/; expires="+cookieDate.toUTCString();
		},

		getGidCookie: function ()
		{
			return this.getCookie(this.cookieName)
		},

		getCookie: function (name)
		{
			var matches = document.cookie.match(new RegExp(
				"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
			));
			return matches ? decodeURIComponent(matches[1]) : null;
		}
	};

	window.BX.SiteGuest.init();

})(window);
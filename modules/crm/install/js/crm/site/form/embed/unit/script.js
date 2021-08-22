;(function () {

	if (!window.b24form)
	{
		window.b24form = function (data) {
			b24form.forms = b24form.forms || [];
			b24form.forms.push(data);
			if (data.ref && b24form.Loader && !this.loaded)
			{
				this.loaded = true;
				b24form.Loader.loadJs(data.ref, true);
			}
		};
	}

	if (window.b24form.Loader)
	{
		return;
	}

	function Loader ()
	{
		this.requested = false;
		this.queue = [];
	}
	Loader.prototype = {
		run: function (options)
		{
			options = options || {};
			var res = options.resources || {};

			this.queue.push(options.form);

			if (!this.requested)
			{
				var loadApp = this.loadApp.bind(this, res.app);
				this.requested = true;
				if (res.polyfill && !this.checkPolyfills())
				{
					this.loadJs(res.polyfill, true, loadApp);
				}
				else
				{
					loadApp();
				}
			}

			this.loadForms();
		},
		loadApp: function (appRes)
		{
			if (!appRes)
			{
				return;
			}
			window.b24form.App
				? this.loadForms()
				: this.loadJs(appRes, true, this.loadForms.bind(this));
		},
		loadForms: function ()
		{
			if (!this.checkPolyfills())
			{
				return;
			}

			if (!window.b24form.App)
			{
				return;
			}

			var queue = this.queue;
			this.queue = [];
			queue.forEach(this.loadForm, this);
		},
		loadForm: function (form)
		{
			b24form.App.initFormScript24(form);
		},
		checkPolyfills: function ()
		{
			return window.fetch && window.Request && window.Response
				&& window.Promise
				&& Object.assign
				&& Array.prototype.find && Array.prototype.includes
		},
		loadJs: function (content, isUrl, callback)
		{
			var node = document.createElement('SCRIPT');
			node.setAttribute("type", "text/javascript");
			node.setAttribute("async", "");

			if (isUrl)
			{
				node.setAttribute("src", content + '?' + (Date.now()/86400000|0));
				if (callback)
				{
					node.onload = callback;
				}
				this.appendToHead(node);
			}
			else
			{
				node.appendChild(document.createTextNode(content));
				this.appendToHead(node);
			}
		},
		appendToHead: function (node)
		{
			(document.getElementsByTagName('head')[0] || document.documentElement).appendChild(node);
		}
	};

	window.b24form.Loader = new Loader();
})();
;(function ()
{
	"use strict";
	BX.namespace("BX.Report.Dashboard");

	/**
	 * @param options
	 * @constructor
	 */
	BX.Report.Dashboard.Content = function (options)
	{
		this.height = options.height || 'auto';
		this.color = options.color || 'inherit';
		this.previewImageUri = options.previewImageUri || '';
		this.errors = options.errors || [];
		this.data = options.data || {};
		this.rendered = false;
		this.widget = options.widget || null;
		this.layout = {
			container: null,
			previewContainer: null
		}
	};

	BX.Report.Dashboard.Content.prototype = {

		isRendered: function()
		{
			return this.rendered;
		},
		setRenderStatus: function(status)
		{
			this.rendered = status
		},
		getColor: function()
		{
			return this.color;
		},
		getHeight: function()
		{
			if (this.height !== 'auto')
			{
				return this.height - 55
			}
			else
			{
				return 'auto';
			}
		},
		/**
		 * @param {BX.Report.Dashboard.Widget} widget
		 */
		setWidget: function(widget)
		{
			this.widget = widget;
			return this;
		},
		getWidget: function ()
		{
			return this.widget;
		},
		render: function ()
		{
			return BX.create('div', {html: 'parent render'});
		},
		renderPreview: function ()
		{
			if (this.layout.previewContainer)
			{
				return this.layout.previewContainer;
			}

			if (this.previewImageUri)
			{
				this.layout.previewContainer = BX.create('img', {
					attrs: {
						'src': this.previewImageUri,
						'height': '390px',
						'width': '100%'
					}
				});
			}

			return this.layout.previewContainer;
		}
	};



	/**
	 * @param options
	 * @extends {BX.Report.Dashboard.Content}
	 * @constructor
	 */
	BX.Report.Dashboard.Content.Html = function (options)
	{
		this.html = options.html || [];
		this.js = options.js || [];
		this.css = options.css || '';
		this.config = options.config || '';
		this.htmlContentWrapper = BX.create('div');

		BX.Report.Dashboard.Content.apply(this, arguments);
	};

	BX.Report.Dashboard.Content.Html.counter = 0;
	BX.Report.Dashboard.Content.Html.callbacks = {};

	BX.Report.Dashboard.Content.Html.ready = function(callback)
	{
		this.callbacks[this.counter] = callback;
	};
	BX.Report.Dashboard.Content.Html.callCallbackInContext = function(id, context)
	{
		if (this.callbacks[id])
		{
			this.callbacks[id](context);
		}
	};

	BX.Report.Dashboard.Content.Html.prototype = {
		__proto__: BX.Report.Dashboard.Content.prototype,
		constructor: BX.Report.Dashboard.Content.Html,
		loadAssets: function()
		{
			var js = this.js.length ? this.js : [];
			var css = this.css.length ? this.css : [];
			var assets = js.concat(css);
			if(assets.length)
			{
				BX.load(assets, this.fillHtmlContentWrapper.bind(this));
			}
			else
			{
				this.fillHtmlContentWrapper();
			}
		},
		fillHtmlContentWrapper: function()
		{
			BX.Report.Dashboard.Content.Html.counter++;


			var html = BX.processHTML(this.html);
			this.htmlContentWrapper.innerHTML = html.HTML;

			BX.ajax.processScripts(html.SCRIPT, false, function ()
			{
				BX.Report.Dashboard.Content.Html.callCallbackInContext(BX.Report.Dashboard.Content.Html.counter, this.htmlContentWrapper);
			}.bind(this));

			this.htmlContentWrapper.style.minHeight = this.getHeight() + 'px';
			this.htmlContentWrapper.style.overflow = 'hidden';
		},
		render: function ()
		{
			if (this.isRendered())
			{
				return this.htmlContentWrapper;
			}
			else
			{
				BX.addCustomEvent(this.widget, 'Dashboard.Board.Widget:onAfterRender', function ()
				{
					if (this.htmlContentWrapper.parentNode)
					{
						this.loadAssets();
					}
				}.bind(this));

				this.setRenderStatus(true);
				return this.htmlContentWrapper;
			}

		},
		setHeight: function(height)
		{
			this.height = height;
			this.htmlContentWrapper.style.minHeight = this.height + 'px';
		}
	};



	/**
	 * @param options
	 * @extends {BX.Report.Dashboard.Content}
	 * @constructor
	 */
	BX.Report.Dashboard.Content.Empty = function (options)
	{
		BX.Report.Dashboard.Content.apply(this, arguments);
	};

	BX.Report.Dashboard.Content.Empty.prototype = {
		__proto__: BX.Report.Dashboard.Content.prototype,
		constructor: BX.Report.Dashboard.Content.Empty,

		/**
		 * @returns {Element}
		 * @override
		 */
		render: function ()
		{
			return BX.create('div', {
				styles: {
					height: this.getHeight() + 'px'
				},
				html: 'empty content'
			});
		}
	};


	/**
	 * @param options
	 * @extends {BX.Report.Dashboard.Content}
	 * @constructor
	 */
	BX.Report.Dashboard.Content.Error = function (options)
	{
		BX.Report.Dashboard.Content.apply(this, arguments);
	};

	BX.Report.Dashboard.Content.Error.prototype = {
		__proto__: BX.Report.Dashboard.Content.prototype,
		constructor: BX.Report.Dashboard.Content.Error,

		/**
		 * @returns {Element}
		 * @override
		 */
		render: function ()
		{
			var errors = [];
			for (var i = 0; i < this.errors.length; i++)
			{
				errors.push(BX.create('div', {
					html: this.errors[i],
					style: {
						color: "red"
					}
				}));
			}
			return BX.create('div', {
				styles: {
					height: this.getHeight() + 'px'
				},
				children: errors
			});
		}
	};

})();
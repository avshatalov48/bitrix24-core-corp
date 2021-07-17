;(function() {
	"use strict";

	BX.namespace("BX.Landing");

	var slice = BX.Landing.Utils.slice;
	var proxy = BX.Landing.Utils.proxy;
	var bind = BX.Landing.Utils.bind;
	var addClass = BX.Landing.Utils.addClass;
	var removeClass = BX.Landing.Utils.removeClass;
	var isNumber = BX.Landing.Utils.isNumber;
	var style = BX.Landing.Utils.style;
	var data = BX.Landing.Utils.data;
	var addQueryParams = BX.Landing.Utils.addQueryParams;

	/**
	 * Implements interface for works with template preview
	 * @constructor
	 */
	BX.Landing.TemplatePreview = function(params)
	{
		this.closeButton = document.querySelector(".landing-template-preview-close");
		this.createButton = document.querySelector(".landing-template-preview-create");
		this.createByImportButton = document.querySelector(".landing-template-preview-create-by-import");
		this.title = document.querySelector(".landing-template-preview-input-title");
		this.description = document.querySelector(".landing-template-preview-input-description");
		this.themesPalete = document.querySelector(".landing-template-preview-themes");
		this.themesSiteColorNode = document.querySelector(".landing-template-preview-site-color");
		this.themesSiteCustomColorNode = document.querySelector(".landing-demo-preview-custom-color");
		this.imageContainer = document.querySelector(".preview-desktop-body-image");
		this.loaderContainer = document.querySelector(".preview-desktop-body-loader-container");
		this.previewFrame = document.querySelector(".preview-desktop-body-preview-frame");
		this.baseUrlNode = document.querySelector(".landing-template-preview-base-url");
		this.siteGroupPalette = document.querySelector(".landing-template-preview-site-group");
		this.loader = new BX.Loader({});
		this.messages = params.messages || {};
		this.loaderText = null;
		this.progressBar = null;
		this.IsLoadedFrame = false;
		this.baseUrl = '';
		this.color = '';
		this.ajaxUrl = '';
		this.ajaxParams = {};

		this.createStore = BX.type.isBoolean(params.createStore)
						? params.createStore
						: false;
		this.disableStoreRedirect = BX.type.isBoolean(params.disableStoreRedirect)
						? params.disableStoreRedirect
						: false;
		this.disableClickHandler = BX.type.isBoolean(params.disableClickHandler)
						? params.disableClickHandler
						: false;
		this.zipInstallPath = params.zipInstallPath
						? params.zipInstallPath
						: null;

		this.onCreateButtonClick = proxy(this.onCreateButtonClick, this);
		this.onCancelButtonClick = proxy(this.onCancelButtonClick, this);
		this.onFrameLoad = proxy(this.onFrameLoad, this);

		this.init();

		return this;
	};

	/**
	 * Gets instance of BX.Landing.TemplatePreview
	 * @return {BX.Landing.TemplatePreview}
	 */
	BX.Landing.TemplatePreview.getInstance = function(params)
	{
		return (
			BX.Landing.TemplatePreview.instance ||
			(BX.Landing.TemplatePreview.instance = new BX.Landing.TemplatePreview(params))
		);
	};

	BX.Landing.TemplatePreview.prototype = {
		/**
		 * Initializes template preview elements
		 */
		init: function()
		{
			// themes
			var colorItems = slice(this.themesPalete.children);
			if(this.themesSiteColorNode)
			{
				colorItems = colorItems.concat(slice(this.themesSiteColorNode.children));
			}
			if(this.themesSiteCustomColorNode)
			{
				colorItems = colorItems.concat(slice(this.themesSiteCustomColorNode.children));
			}
			colorItems.forEach(this.initSelectableItem, this);

			// site group
			if(this.siteGroupPalette )
			{
				var siteGroupItems = slice(this.siteGroupPalette.children);
				siteGroupItems.forEach(this.initSelectableItem, this);
			}

			bind(this.previewFrame, "load", this.onFrameLoad);
			bind(this.closeButton, "click", this.onCancelButtonClick);

			if (!this.disableClickHandler)
			{
				bind(this.createButton, "click", this.onCreateButtonClick);
			}

			this.setBaseUrl();
			this.setColor();
			this.showPreview();
		},

		setBaseUrl: function(url) {
			if(url === undefined)
			{
				this.baseUrl = data(this.baseUrlNode, "data-base-url");
			}
			else
			{
				this.baseUrl = url;
			}
		},

		setColor: function(theme) {
			if(theme === undefined)
			{
				this.color = data(this.getActiveColorNode(), "data-value");
			}
			else
			{
				this.color = theme;
			}
		},

		createPreviewUrl: function() {
			if(!this.baseUrl)
			{
				this.setBaseUrl();
			}

			if(!this.color)
			{
				this.setColor();
			}

			return addQueryParams(this.baseUrl, {color: this.color,});
		},

		onFrameLoad: function() {
			if (this.createStore)
			{
				new BX.Landing.SaveBtn(document.querySelector(".landing-template-preview-create"));
			}
			this.IsLoadedFrame = true;
		},

		getActiveColorNode: function()
		{
			var active = this.themesPalete.querySelector(".active");
			if(!active && this.themesSiteColorNode)
			{
				active = this.themesSiteColorNode.querySelector(".active");
			}
			if(!active && this.themesSiteCustomColorNode)
			{
				active = this.themesSiteCustomColorNode.querySelector(".active");
			}
			// by default - first
			if(!active)
			{
				active = this.themesPalete.firstElementChild;
			}

			return active;
		},

		getActiveSiteGroupItem: function()
		{
			return this.siteGroupPalette.querySelector(".active");
		},

		/**
		 * Shows preview
		 * @param {?string} src
		 * @return {Promise<T>}
		 */
		showPreview: function(src)
		{
			if(src === undefined)
			{
				src = this.createPreviewUrl();
			}

			return this.showLoader()
				.then(this.createFrameIfNeeded())
				.then(this.loadPreview(src))
				.then(this.hideLoader());
		},

		/**
		 * Creates frame if needed
		 * @return {Function}
		 */
		createFrameIfNeeded: function()
		{
			return function()
			{
				return new Promise(function(resolve) {
					var createFrame = function() {
						if (!this.previewFrame)
						{
							this.previewFrame = BX.create('iframe', {
								props: {
									className: 'preview-desktop-body-preview-frame'
								}
							});

							this.imageContainer.appendChild(this.previewFrame);
							bind(this.previewFrame, "load", this.onFrameLoad);
						}

						if (!this.previewFrame.style.width)
						{
							var containerWidth = this.imageContainer.clientWidth;

							void style(this.previewFrame, {
								"width": "1000px",
								"height": "calc((100vh - 140px) * (100 / "+((containerWidth/1000)*100)+"))",
								"transform": "scale("+(containerWidth/1000)+") translateZ(0)",
								"transform-origin": "top left",
								"border": "none"
							});
						}

						resolve(this.previewFrame);
					}.bind(this);

					if (document.readyState !== "complete")
					{
						BX.bind(window, 'load', createFrame.bind(this));
					}
					else
					{
						createFrame();
					}
				}.bind(this));
			}.bind(this)
		},

		/**
		 * Loads template preview
		 * @param {string} src
		 * @return {Function}
		 */
		loadPreview: function(src)
		{
			return function()
			{
				return new Promise(function(resolve) {
					if (this.previewFrame.src !== src)
					{
						this.previewFrame.src = src;
						this.previewFrame.onload = function() {
							resolve(this.previewFrame);
						}.bind(this);
						return;
					}

					resolve(this.previewFrame);
				}.bind(this));
			}.bind(this)
		},

		/**
		 * Shows preview loader
		 * @return {Promise}
		 */
		showLoader: function()
		{
			return new Promise(function(resolve) {
				void this.loader.show(this.loaderContainer);
				addClass(this.imageContainer, "landing-template-preview-overlay");
				resolve();
			}.bind(this));
		},

		/**
		 * Hides loader
		 * @return {Function}
		 */
		hideLoader: function()
		{
			return function(iframe)
			{
				return new Promise(function(resolve) {
					void this.loader.hide();
					removeClass(this.imageContainer, "landing-template-preview-overlay");
					resolve(iframe);
				}.bind(this));
			}.bind(this);
		},

		/**
		 * Creates delay
		 * @param delay
		 * @return {Function}
		 */
		delay: function(delay)
		{
			delay = isNumber(delay) ? delay : 0;

			return function(image)
			{
				return new Promise(function(resolve) {
					setTimeout(resolve.bind(null, image), delay);
				});
			}
		},

		/**
		 * Gets value
		 * @return {Object}
		 */
		getValue: function()
		{
			var result = {};

			if(this.themesSiteColorNode && this.getActiveColorNode().parentElement === this.themesSiteColorNode)
			{
				result[this.themesSiteColorNode.dataset.name] = this.getActiveColorNode().dataset.value;
			}
			if(this.siteGroupPalette)
			{
				result[this.siteGroupPalette.dataset.name] = this.getActiveSiteGroupItem().dataset.value;
			}
			result[this.themesPalete.dataset.name] = this.getActiveColorNode().dataset.value;
			if (this.themesSiteCustomColorNode)
			{
				result[this.themesPalete.dataset.name] = this.getActiveColorNode().dataset.value;
			}
			result[this.title.dataset.name] = this.title.value;
			result[this.description.dataset.name] = this.description.value;

			return result;
		},

		/**
		 * Makes create url
		 * @return {string}
		 */
		getCreateUrl: function()
		{
			return addQueryParams(this.createButton.getAttribute("href"), this.getValue());
		},

		/**
		 * Handles click event on close button
		 * @param {MouseEvent} event
		 */
		onCancelButtonClick: function(event)
		{
			event.preventDefault();
			top.BX.SidePanel.Instance.close();
		},

		/**
		 * Handles click event on create button
		 * @param {MouseEvent} event
		 */
		onCreateButtonClick: function(event)
		{
			event.preventDefault();

			if(this.isStore() && this.IsLoadedFrame) {
				this.loaderText = BX.create("div", { props: { className: "landing-template-preview-loader-text"},
					text: this.messages.LANDING_LOADER_WAIT});

				this.progressBar = new BX.UI.ProgressBar({
					column: true
				});

				this.progressBar.getContainer().classList.add("ui-progressbar-landing-preview");

				this.loaderContainer.appendChild(this.loaderText);
				this.loaderContainer.appendChild(this.progressBar.getContainer());
			}

			if (this.isStore())
			{
				if (this.IsLoadedFrame)
				{
					this.showLoader();
					this.initCatalogParams();
					this.createCatalog();
				}
			}
			else
			{
				this.showLoader()
					.then(this.delay(200))
					.then(function() {
						this.finalRedirectAjax(
							this.getCreateUrl()
						);
					}.bind(this));
			}
		},

		/**
		 * Init params for create catalog.
		 */
		initCatalogParams: function()
		{
			if (this.createButton.hasAttribute('data-href'))
			{
				this.ajaxUrl = this.createButton.getAttribute('data-href');
			}
			this.ajaxParams = this.getValue();
			this.ajaxParams['start'] = 'Y';
			this.ajaxParams['showcaseId'] = 'clothes';
		},

		/**
		 * Base actions for create catalog.
		 */
		createCatalog: function()
		{
			if (this.ajaxUrl === '')
			{
				this.hideLoader();
				return;
			}
			BX.ajax({
				'method': 'POST',
				'dataType': 'json',
				'url': this.ajaxUrl,
				'data':  BX.ajax.prepareData(this.ajaxParams),
				'onsuccess': BX.proxy(this.createCatalogResult, this)
			})
		},

		/**
		 * Result step in create catalog.
		 * @param data
		 */
		createCatalogResult: function(data)
		{
			if (data.status === 'continue')
			{
				this.ajaxParams['start'] = 'N';
				this.progressBar.update(data.progress);
				this.progressBar.setTextAfter(data.message);
				this.createCatalog();
			}
			else
			{
				this.finalRedirectAjax(data.url);
			}
		},

		/**
		 * Redirect to final URL or submit it by ajax and close slider.
		 * @param url
		 */
		finalRedirectAjax: function(url)
		{
			if (this.zipInstallPath)
			{
				if (typeof top.BX.SidePanel !== 'undefined')
				{
					top.BX.SidePanel.Instance.open(this.zipInstallPath);
				}
			}
			else if (this.disableStoreRedirect)
			{
				BX.ajax({
					'method': 'POST',
					'dataType': 'html',
					'url': url,
					'onsuccess': function()
					{
						if (typeof top.BX.SidePanel !== 'undefined')
						{
							setTimeout(function() {
								top.BX.onCustomEvent('Landing:onDemoCreateStart');
								top.BX.SidePanel.Instance.close();
							}, 100);
						}
					}
				});
			}
			else
			{
				window.location = url;
			}
		},

		/**
		 * Initializes selectable items
		 * @param {HTMLElement} item
		 */
		initSelectableItem: function(item)
		{
			bind(item, "click", proxy(this.onSelectableItemClick, this));
		},

		/**
		 * Handles click on selectable item
		 * @param event
		 */
		onSelectableItemClick: function(event)
		{
			event.preventDefault();

			// themes
			if (
				event.currentTarget.parentElement === this.themesPalete ||
				(this.themesSiteColorNode && event.currentTarget.parentElement === this.themesSiteColorNode)
			)
			{
				//removeClass(this.getActiveColorNode(), "active");
				this.getActiveColorNode().classList.remove("active");
				addClass(event.currentTarget, "active");

				this.setColor(data(event.currentTarget, 'data-value'));
				this.showPreview();
			}

			// site group
			if (event.currentTarget.parentElement === this.siteGroupPalette)
			{
				removeClass(this.getActiveSiteGroupItem(), "active");
				addClass(event.currentTarget, "active");
				this.setBaseUrl(data(event.currentTarget, 'data-base-url'));
				this.showPreview();
			}
		},

		isStore: function()
		{
			return this.createStore;
		}
	};

	BX.addCustomEvent('BX.Landing.ColorPicker:onSelectColor', function(params)
	{
		var elementSettingCustomColor = BX("landing-template-preview-settings");
		var elementActive = elementSettingCustomColor.querySelector(".active");
		elementActive.classList.remove("active");
		params.node.classList.add("active");

		function replacePreview() {
			loader.hide();
			removeClass(imageContainer, "landing-template-preview-overlay");
		}
		var loader = new BX.Loader({});
		var loaderContainer = document.querySelector(".preview-desktop-body-loader-container");
		loader.show(loaderContainer);
		var imageContainer = document.querySelector(".preview-desktop-body-image");
		addClass(imageContainer, "landing-template-preview-overlay");

		var url = document.getElementsByClassName('preview-desktop-body-preview-frame');
		url = url[0];
		var attr = url.getAttribute('src');

		var hexFormat = attr.substr(-7,1);
		if (hexFormat === '#')
		{
			attr = attr.substr(0,attr.length - 7);
		}
		else
		{
			attr = attr.substr(0,attr.length - 6);
		}
		attr = attr + params.color.substr(1);
		url.setAttribute('src', attr);
		setTimeout(replacePreview, 1600);
	});
})();
;(function() {

"use strict";

BX.namespace("BX.Intranet.Bitrix24");

BX.Intranet.Bitrix24.ThemePicker = function(options)
{
	options = BX.type.isPlainObject(options) ? options : {};

	this.themeId = options.themeId;
	this.templateId = options.templateId;
	this.appliedThemeId = this.themeId;
	this.appliedTheme = BX.type.isPlainObject(options.theme) ? options.theme : null;
	this.siteId = options.siteId;
	this.entityType = options.entityType;
	this.entityId = options.entityId;
	this.maxUploadSize = BX.type.isNumber(options.maxUploadSize) ? options.maxUploadSize : 5 * 1024 * 1024;
	this.ajaxHandlerPath = BX.type.isNotEmptyString(options.ajaxHandlerPath) ? options.ajaxHandlerPath : null;
	this.isAdmin = options.isAdmin === true;
	this.allowSetDefaultTheme = options.allowSetDefaultTheme === true;
	this.isVideo = options.isVideo === true;

	if (BX.type.isDomNode(options.link))
	{
		BX.bind(options.link, "click", this.show.bind(this));
	}

	this.themes = [];
	this.baseThemes = {};

	this.popup = null;
	this.loaderTimeout = null;
	this.behaviour = BX.type.isNotEmptyString(options.behaviour) ? options.behaviour : 'apply';
	this.returnValue = (this.needReturnValue() ? this.themeId : null);

	this.newThemeDialog = new BX.Intranet.Bitrix24.NewThemeDialog(this);

	if (this.isVideo)
	{
		window.addEventListener("focus", this.handleWindowFocus.bind(this));
		window.addEventListener("blur", this.handleWindowBlur.bind(this));

		BX.addCustomEvent("OnIframeFocus", this.handleWindowFocus.bind(this));

		BX.addCustomEvent("SidePanel.Slider:onOpenComplete", this.handleSliderOpen.bind(this));
		BX.addCustomEvent("SidePanel.Slider:onCloseComplete", this.handleSliderClose.bind(this));

		var eventHandler = this.handleVisibilityChange.bind(this);
		window.addEventListener("load", eventHandler);
		document.addEventListener("visibilitychange", eventHandler);
	}

	this.isBodyClassRemoved = false;
	if ("onbeforeprint" in window)
	{
		window.addEventListener("beforeprint", this.handleBeforePrint.bind(this));
		window.addEventListener("afterprint", this.handleAfterPrint.bind(this));
	}
	else if (window.matchMedia)
	{
		window.matchMedia("print").addListener(this.handleMediaPrint.bind(this));
	}
};

BX.Intranet.Bitrix24.ThemePicker.prototype =
{
	showDialog: function(scrollToTop)
	{
		this.loadThemes();
		this.showLoader(this.getThemeListDialog().contentContainer);

		if (scrollToTop === false)
		{
			this.getThemeListDialog().show();
		}
		else
		{
			(new BX.easing({
				duration : 500,
				start : { scroll : window.pageYOffset || document.documentElement.scrollTop },
				finish : { scroll : 0 },
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step : function(state){
					window.scrollTo(0, state.scroll);
				},
				complete: function() {
					this.getThemeListDialog().show();
				}.bind(this)
			})).animate();
		}
	},

	closeDialog: function()
	{
		if (!this.needReturnValue())
		{
			this.applyTheme(this.getThemeId());
		}

		this.setThemes([]);
		this.popup.destroy();
		this.popup = null;
	},

	getNewThemeDialog: function()
	{
		return this.newThemeDialog;
	},

	showLoader: function(node, timeout, small)
	{
		if (!BX.type.isDomNode(node))
		{
			return;
		}

		timeout = BX.type.isNumber(timeout) ? timeout : 250;

		this.loaderTimeout = setTimeout(function() {
			node.appendChild(this.getLoader(small));
		}.bind(this), timeout);
	},

	getLoader: function(small)
	{
		if (!this.loader)
		{
			this.loader = BX.create("div", {
				props: {
					className: "intranet-loader-container intranet-loader-show"
				},
				html:
				'<svg class="intranet-loader-circular" viewBox="25 25 50 50">' +
					'<circle class="intranet-loader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>' +
				'</svg>'
			});
		}

		this.loader.classList[small ? "add" : "remove"]("intranet-loader-container-small");

		return this.loader;
	},

	hideLoader: function()
	{
		if (this.loaderTimeout)
		{
			clearTimeout(this.loaderTimeout);
		}

		BX.remove(this.loader);

		this.loaderTimeout = null;
	},

	ajax: function(data, onsuccess, onfailure)
	{
		data = BX.type.isPlainObject(data) ? data : {};

		data.sessid = BX.bitrix_sessid();
		data.templateId = this.getTemplateId();
		data.siteId = this.getSiteId();
		data.entityType = this.getEntityType();
		data.entityId = this.getEntityId();

		BX.ajax({
			method: "POST",
			dataType: "json",
			url: this.getAjaxHandlerPath(),
			data: data,
			onsuccess: onsuccess,
			onfailure: onfailure
		});
	},

	loadThemes: function()
	{
		this.ajax(
			{ action: "getlist" },

			function onSuccess(data) {

				if (!data || !data.success || !BX.type.isArray(data.themes) || data.themes.length < 1)
				{
					this.showFatalError();
					return;
				}

				this.hideLoader();
				this.setThemes(data.themes);
				this.setBaseThemes(data.baseThemes);
				this.renderLayout();

			}.bind(this),

			function onFailure() {
				this.showFatalError();
			}.bind(this)
		);
	},

	showFatalError: function()
	{
		this.hideLoader();
		this.getThemeListDialog().setContent(BX.message("BITRIX24_THEME_UNKNOWN_ERROR"));
		var cancelButton = this.getThemeListDialog().getButton("cancel-button");
		this.getThemeListDialog().setButtons([cancelButton]);
	},

	saveTheme: function(themeId)
	{
		this.ajax({
			action: "save",
			themeId: themeId,
			setDefaultTheme: this.isCheckboxChecked()
		});

		this.setThemeId(themeId);
	},

	applyTheme: function(themeId)
	{
		if (!BX.type.isNotEmptyString(themeId) || themeId === this.getAppliedThemeId())
		{
			return false;
		}

		var theme = this.getThemeAssets(themeId);
		if (!theme)
		{
			return false;
		}
		BX.Event.EventEmitter.emit('BX.Intranet.Bitrix24:ThemePicker:onThemeApply', {id: themeId, theme: theme});
		this.applyThemeAssets(theme);
		this.removeThemeAssets(this.getAppliedThemeId());
		this.setAppliedThemeId(themeId);

		this.appliedTheme = theme;

		return true;
	},

	removeThemeAssets: function(themeId)
	{
		var styles = document.head.querySelectorAll('[data-theme-id="'+ themeId + '"]');
		for (var i = 0; i < styles.length; i++)
		{
			BX.remove(styles[i]);
		}

		BX.remove(document.querySelector('body > [data-theme-id="' + themeId + '"]'))
	},

	applyThemeAssets: function(assets)
	{
		if (!assets || !BX.type.isArray(assets.css) || !BX.type.isNotEmptyString(assets.id))
		{
			return false;
		}

		var head = document.head;
		var themeId = assets.id;

		assets["css"].forEach(function(file) {
			var link = document.createElement("link");
			link.type = "text/css";
			link.rel = "stylesheet";
			link.href = file;
			link.dataset.themeId = themeId;
			head.appendChild(link);
		});

		if (BX.type.isNotEmptyString(assets["style"]))
		{
			var style = document.createElement("style");
			style.type = "text/css";
			style.dataset.themeId = themeId;
			if (style.styleSheet)
			{
				style.styleSheet.cssText = assets["style"];
			}
			else
			{
				style.appendChild(document.createTextNode(assets["style"]));
			}

			head.appendChild(style);
		}

		if (assets["video"] && BX.type.isPlainObject(assets["video"]["sources"]))
		{
			var sources = [];
			for (var type in assets["video"]["sources"])
			{
				sources.push(BX.create("source", {
					attrs: {
						type: "video/" + type,
						src: assets["video"]["sources"][type]
					}
				}))
			}

			var video = BX.create("div", {
				props: {
					className: "theme-video-container"
				},
				dataset: {
					themeId: themeId
				},
				children: [
					BX.create("video", {
						props: {
							className: "theme-video"
						},
						attrs: {
							poster: assets["video"]["poster"],
							autoplay: true,
							loop: true,
							muted: true,
							playsinline: true
						},
						dataset: {
							themeId: themeId
						},
						children: sources
					})
				]
			});

			document.body.insertBefore(video, document.body.firstElementChild);
		}

		var appliedBaseThemeId = this.getAppliedThemeId().split(":")[0];
		var baseThemeId = themeId.split(":")[0];

		if (appliedBaseThemeId !== baseThemeId)
		{
			BX.removeClass(document.body, "bitrix24-" + appliedBaseThemeId + "-theme");
			BX.addClass(document.body, "bitrix24-" + baseThemeId + "-theme");
		}
	},

	selectItem: function(item)
	{
		if (!BX.type.isDomNode(item) || !BX.hasClass(item, "theme-dialog-item"))
		{
			return;
		}

		var themeId = item.dataset.themeId;

		[].forEach.call(item.parentNode.children, function(item) {
			BX.removeClass(item, "theme-dialog-item-selected");
		});

		BX.addClass(item, "theme-dialog-item-selected");

		if (!this.needReturnValue())
		{
			this.showLoader(item, 100, true);

			this.preloadTheme(themeId, function() {
				if (BX.hasClass(item, "theme-dialog-item-selected")) //by this time user could select another theme
				{
					this.hideLoader();
					this.applyTheme(themeId);
				}
			}.bind(this));
		}
		else
		{
			this.setReturnValue(themeId);
		}
	},

	getThemeAssets: function(themeId)
	{
		var themes = this.getThemes();
		for (var i = 0; i < themes.length; i++)
		{
			if (themes[i]["id"] === themeId)
			{
				return themes[i];
			}
		}

		return null;
	},

	getAppliedTheme: function()
	{
		return this.appliedTheme;
	},

	getVideoContainer: function()
	{
		return document.querySelector(".theme-video-container");
	},

	preloadTheme: function(themeId, fn)
	{
		fn = BX.type.isFunction(fn) ? fn : BX.DoNothing;

		var theme = this.getThemeAssets(themeId);
		if (!theme)
		{
			return fn();
		}

		var asyncCount = 2; // preloadImages & preloadCss
		this.preloadImages(theme["prefetchImages"], onload);
		this.preloadCss(theme["css"], onload);

		function onload()
		{
			asyncCount--;
			if (asyncCount === 0)
			{
				fn();
			}
		}
	},

	preloadCss: function(css, fn)
	{
		if (!BX.type.isArray(css))
		{
			return BX.type.isFunction(fn) && fn();
		}

		var iframe = BX.create("iframe", {
			props: {
				src: "javascript:void(0)"
			},
			style: {
				display: "none"
			}
		});

		document.body.appendChild(iframe);

		var iframeDoc = iframe.contentWindow.document;

		if (!iframeDoc.body)
		{
			// null in IE
			iframeDoc.write("<body></body>");
		}

		//to avoid a conflict between a theme's preload image and a <body>'s image from preload css files
		iframeDoc.body.style.cssText = "background: #fff !important";

		BX.load(
			css,
			function() {
				BX.remove(iframe);
				BX.type.isFunction(fn) && fn();
			},
			iframeDoc
		);
	},

	preloadImages: function(images, fn)
	{
		fn = BX.type.isFunction(fn) ? fn : BX.DoNothing;

		if (!BX.type.isArray(images) || images.length === 0)
		{
			return fn();
		}

		var loaded = 0;

		images.forEach(function(imageSrc) {
			var image = new Image();
			image.src = imageSrc;
			image.onload = image.onerror = function() {
				loaded++;
				if (loaded === images.length)
				{
					fn();
				}
			}
		});
	},

	getTemplateId: function()
	{
		return this.templateId;
	},

	getThemeId: function()
	{
		return this.themeId;
	},

	setThemeId: function(themeId)
	{
		this.themeId = themeId;
	},

	getAppliedThemeId: function()
	{
		return this.appliedThemeId;
	},

	setAppliedThemeId: function(themeId)
	{
		this.appliedThemeId = themeId;
	},

	getSiteId: function()
	{
		return this.siteId;
	},

	getEntityType: function()
	{
		return this.entityType;
	},

	getEntityId: function()
	{
		return this.entityId;
	},

	getAjaxHandlerPath: function()
	{
		return this.ajaxHandlerPath;
	},

	getMaxUploadSize: function()
	{
		return this.maxUploadSize;
	},

	isCurrentUserAdmin: function()
	{
		return this.isAdmin;
	},

	canSetDefaultTheme: function()
	{
		return this.allowSetDefaultTheme;
	},

	setThemes: function(themes)
	{
		if (BX.type.isArray(themes))
		{
			this.themes = themes;
		}
	},

	/**
	 *
	 * @returns {Array}
	 */
	getThemes: function()
	{
		return this.themes;
	},

	setBaseThemes: function(themes)
	{
		if (BX.type.isPlainObject(themes))
		{
			this.baseThemes = themes;
		}
	},

	/**
	 *
	 * @returns {object}
	 */
	getBaseThemes: function()
	{
		return this.baseThemes;
	},

	getTheme: function(themeId)
	{
		var themes = this.getThemes();
		for (var i = 0; i < themes.length; i++)
		{
			if (themes[i]["id"] === themeId)
			{
				return themes[i];
			}
		}

		return null;
	},

	removeTheme: function(themeId)
	{
		this.themes = this.getThemes().filter(function(theme) {
			return theme.id !== themeId;
		});
	},

	setReturnValue: function(themeId)
	{
		this.returnValue = themeId;
	},

	getReturnValue: function()
	{
		return this.returnValue;
	},

	addItem: function(theme)
	{
		this.themes.unshift(theme);
		var newItem = this.createItem(theme);
		BX.prepend(newItem, this.getContentContainer());
		this.selectItem(newItem);
	},

	createItem: function(theme)
	{
		var className = "theme-dialog-item";
		if (theme["video"])
		{
			className += " theme-dialog-item-video";
		}

		if (this.getAppliedThemeId() === theme.id)
		{
			className += " theme-dialog-item-selected";
		}

		var div = BX.create("div", {
			attrs: {
				className: className,
				"data-theme-id": theme.id
			},
			children: [
				BX.create("div", {
					attrs: {
						className: "theme-dialog-item-title"
					},
					children: [
						BX.create("span", {
							attrs: {
								className: "theme-dialog-item-title-text"
							},
							text: theme.title
						}),
						theme.removable
							?
							BX.create("div", {
								attrs: {
									className: "theme-dialog-item-remove",
									"data-theme-id": theme.id,
									title: BX.message("BITRIX24_THEME_REMOVE_THEME")
								},
								events: {
									click: this.handleRemoveBtnClick.bind(this)
								}
							})
							:
							null
					]
				}),
				theme["default"] === true ? this.createDefaultLabel() : null
			],
			events: {
				click: this.handleItemClick.bind(this)
			}
		});

		if (BX.type.isNotEmptyString(theme.previewImage))
		{
			div.style.backgroundImage = 'url("' + theme.previewImage + '")';
			div.style.backgroundSize = 'cover';
		}

		if (BX.type.isNotEmptyString(theme.previewColor))
		{
			div.style.backgroundColor = theme.previewColor;
		}

		return div;
	},

	createDefaultLabel: function()
	{
		return BX.create("div", {
			props: {
				className: "theme-dialog-item-default"
			},
			text: BX.message("BITRIX24_THEME_DEFAULT_THEME")
		});
	},

	/**
	 *
	 * @returns {Element}
	 */
	getContentContainer: function()
	{
		return this.getThemeListDialog().contentContainer.querySelector(".theme-dialog-content");
	},

	/**
	 *
	 * @returns {BX.Intranet.Bitrix24.ThemePickerCheckboxButton}
	 */
	getCheckboxButton: function()
	{
		return this.getThemeListDialog().getButton("checkbox");
	},

	isCheckboxChecked: function()
	{
		return this.getCheckboxButton() ? this.getCheckboxButton().isChecked() : false;
	},

	renderLayout: function()
	{
		var container = BX.create("div", {
			attrs: {
				className: "theme-dialog-content"
			}
		});

		this.getThemes().forEach(function(theme) {
			container.appendChild(this.createItem(theme));
		}, this);

		this.getThemeListDialog().setContent(
			BX.create("div", {
				attrs: {
					className: "theme-dialog-container"
				},
				children: [container]
			})
		);

	},

	handleItemClick: function(event)
	{
		this.selectItem(this.getItemNode(event));
	},

	/**
	 *
	 * @param {Event} event
	 */
	handleRemoveBtnClick: function(event)
	{
		var item = this.getItemNode(event);
		if (!item)
		{
			return;
		}

		var themeId = item.dataset.themeId;
		var theme = this.getTheme(themeId);
		if (theme && theme.default)
		{
			var defaultThemeItem = this.getContentContainer().querySelector('[data-theme-id="default"]');
			if (defaultThemeItem)
			{
				defaultThemeItem.appendChild(this.createDefaultLabel());
			}
		}

		this.removeTheme(themeId);
		BX.remove(item);

		if (this.getAppliedThemeId() === themeId)
		{
			var firstItem = this.getContentContainer().children[0];
			this.selectItem(firstItem);

			if (this.getThemeId() === themeId && firstItem && firstItem.dataset.themeId)
			{
				this.saveTheme(firstItem.dataset.themeId);
			}
		}
		else if (this.getThemeId() === themeId)
		{
			this.saveTheme(this.getAppliedThemeId());
		}

		this.ajax({ action: "remove", themeId: themeId });
		event.stopPropagation();
	},

	getItemNode: function(event)
	{
		if (!event || !event.target)
		{
			return null;
		}

		var item =
			BX.hasClass(event.target, "theme-dialog-item")
				? event.target
				: BX.findParent(event.target, { className: "theme-dialog-item" })
		;

		return BX.type.isDomNode(item) ? item : null;
	},

	handleSaveButtonClick: function(event)
	{
		if (this.needReturnValue())
		{
			BX.onCustomEvent('Intranet.ThemePicker:onSave', [{
				theme: this.getThemeAssets(this.getReturnValue()),
			}]);
		}
		else if (this.getThemeId() !== this.getAppliedThemeId() || this.isCheckboxChecked())
		{
			this.saveTheme(this.getAppliedThemeId());
		}

		this.closeDialog();
	},

	handleNewThemeButtonClick: function(event)
	{
		this.getNewThemeDialog().show();
	},

	/**
	 *
	 * @returns {BX.PopupWindow}
	 */
	getThemeListDialog: function()
	{
		if (this.popup)
		{
			return this.popup;
		}

		var checkboxBtn = null;
		if (this.isCurrentUserAdmin() && this.getEntityType() === 'USER')
		{
			checkboxBtn = new BX.Intranet.Bitrix24.ThemePickerCheckboxButton(this);
		}

		this.popup = new BX.PopupWindow("bitrix24-theme-list-dialog", null, {
			width: 800,
			height: 500,
			titleBar: BX.message("BITRIX24_THEME_DIALOG_TITLE"),
			className: "theme-dialog-popup-window-container",
			closeByEsc: true,
			bindOnResize: false,
			closeIcon: true,
			draggable: true,
			events: {
				onPopupClose: function() {
					this.closeDialog();
				}.bind(this)
			},
			buttons: [
				new BX.PopupWindowButton({
					id: "save-button",
					text: BX.message("BITRIX24_THEME_DIALOG_SAVE_BUTTON"),
					className: "popup-window-button-accept",
					events: {
						click: this.handleSaveButtonClick.bind(this)
					}
				}),
				new BX.PopupWindowButtonLink({
					id: "cancel-button",
					text: BX.message("BITRIX24_THEME_DIALOG_CANCEL_BUTTON"),
					className: "popup-window-button-link theme-dialog-button-link",
					events: {
						click: function() {
							this.popupWindow.close();
						}
					}
				}),
				new BX.PopupWindowButtonLink({
					id: "create-button",
					text: BX.message("BITRIX24_THEME_DIALOG_NEW_THEME"),
					className: "popup-window-button-link theme-dialog-button-link theme-dialog-new-theme-btn",
					events: {
						click: this.handleNewThemeButtonClick.bind(this)
					}
				})
			].concat(checkboxBtn ? [checkboxBtn] : [])
		});

		return this.popup;
	},

	enableThemeListDialog: function()
	{
		BX.removeClass(
			this.getThemeListDialog().popupContainer,
			"theme-dialog-popup-window-container-disabled"
		);
	},

	disableThemeListDialog: function()
	{
		BX.addClass(
			this.getThemeListDialog().popupContainer,
			"theme-dialog-popup-window-container-disabled"
		);
	},

	getVideoElement: function()
	{
		return document.querySelector(".theme-video");
	},

	shouldPlayVideo: function()
	{
		const iframeMode = window !== window.top;
		if (iframeMode)
		{
			return BX.SidePanel.Instance.getSliderByWindow(window) === BX.SidePanel.Instance.getTopSlider();
		}
		else
		{
			return !BX.SidePanel.Instance.isOpen();
		}
	},

	playVideo: function()
	{
		var video = this.getVideoElement();
		if (video)
		{
			video.play().catch(function(error) {});
		}
	},

	pauseVideo: function()
	{
		var video = this.getVideoElement();
		if (video)
		{
			video.pause();
		}
	},

	handleVisibilityChange: function()
	{
		const video = this.getVideoElement();
		if (video)
		{
			if (document.visibilityState === "hidden")
			{
				this.pauseVideo();
			}
			else
			{
				if (this.shouldPlayVideo())
				{
					this.playVideo();
				}
			}
		}
	},

	handleWindowFocus: function()
	{
		if (this.shouldPlayVideo())
		{
			this.playVideo();
		}
	},

	handleWindowBlur: function()
	{
		this.pauseVideo();
	},

	handleBeforePrint: function(event)
	{
		window.scroll(0, 0);
		if (BX.hasClass(document.body, "bitrix24-light-theme"))
		{
			BX.removeClass(document.body, "bitrix24-light-theme");
			this.isBodyClassRemoved = true;
		}
	},

	handleAfterPrint: function()
	{
		if (this.isBodyClassRemoved)
		{
			BX.addClass(document.body, "bitrix24-light-theme");
			this.isBodyClassRemoved = false;
		}
	},

	handleMediaPrint: function(mql)
	{
		if (mql.matches)
		{
			this.handleBeforePrint();
		}
		else
		{
			this.handleAfterPrint();
		}
	},

	handleSliderOpen: function()
	{
		this.pauseVideo();
	},

	handleSliderClose: function()
	{
		if (this.shouldPlayVideo())
		{
			this.playVideo();
		}
	},

	needReturnValue: function()
	{
		return (this.behaviour === 'return');
	},

};

/**
 *
 * @extends {BX.Intranet.Bitrix24.ThemePicker}
 * @constructor
 * @param themePicker
 */
BX.Intranet.Bitrix24.ThemePickerCheckboxButton = function(themePicker)
{
	BX.PopupWindowButton.call(this, { id: "checkbox" });

	/** @var {BX.Intranet.Bitrix24.ThemePicker} */
	this.themePicker = themePicker;

	this.buttonNode = BX.create("div", {
		props: {
			className: "theme-dialog-checkbox-button"
		},
		children: [
			(this.checkbox = BX.create("input", {
				attrs: {
					type: "checkbox",
					name: "defaultTheme",
					value: "Y",
					id: "theme-dialog-checkbox-input",
					className: "theme-dialog-checkbox-input"
				},
				events: {
					click: this.handleCheckboxClick.bind(this)
				}
			})),
			BX.create("label", {
				props: {
					htmlFor: "theme-dialog-checkbox-input",
					className: "theme-dialog-checkbox-label"
				},
				text: BX.message("BITRIX24_THEME_DEFAULT_THEME_FOR_ALL")
			}),
			(!this.themePicker.canSetDefaultTheme() ? BX.create("span", { props: { className: "tariff-lock" }}) : null)

		]
	});

};

BX.Intranet.Bitrix24.ThemePickerCheckboxButton.prototype = {
	__proto__: BX.PopupWindowButton.prototype,
	constructor: BX.Intranet.Bitrix24.ThemePickerCheckboxButton,

	isChecked: function()
	{
		return this.checkbox.checked;
	},

	check: function()
	{
		this.checkbox.checked = true;
	},

	uncheck: function()
	{
		this.checkbox.checked = false;
	},

	handleCheckboxClick: function()
	{
		if (this.themePicker.canSetDefaultTheme())
		{
			return;
		}

		if (BX.getClass("BX.UI.InfoHelper"))
		{
			BX.UI.InfoHelper.show("limit_office_background_to_all");
		}

		this.uncheck();
	}
};

/**
 *
 * @param {BX.Intranet.Bitrix24.ThemePicker} themePicker
 * @constructor
 */
BX.Intranet.Bitrix24.NewThemeDialog = function(themePicker)
{
	this.themePicker = themePicker;
	this.bgImage = null;
	this.bgImageObjectUrl = null;
	this.colorPicker = null;

	this.previewApplied = false;
	this.origAppliedThemeId = null;
};

BX.Intranet.Bitrix24.NewThemeDialog.prototype =
{
	show: function()
	{
		this.getPopup().setContent(this.getContent());
		this.getPopup().show();
		this.getThemePicker().disableThemeListDialog();
	},

	close: function()
	{
		this.getPopup().close();
		this.resetResources();
	},

	resetResources: function()
	{
		this.setBgImage(null);

		if (this.previewApplied)
		{
			this.getThemePicker().applyTheme(this.origAppliedThemeId);
		}

		this.removeThemePreview();

		this.getThemePicker().enableThemeListDialog();
	},

	getBgImage: function()
	{
		return this.bgImage;
	},

	setBgImage: function(file)
	{
		this.bgImage = file;

		this.revokeBgImageObjectUrl();

		if (file)
		{
			this.bgImageObjectUrl = window.URL.createObjectURL(file);
		}
	},

	getBgImageObjectUrl: function()
	{
		return this.bgImageObjectUrl;
	},

	revokeBgImageObjectUrl: function()
	{
		if (this.bgImageObjectUrl)
		{
			window.URL.revokeObjectURL(this.bgImageObjectUrl);
		}

		this.bgImageObjectUrl = null;
	},

	getBgColor: function()
	{
		var color = this.getControl("field-bg-color").value;
		return this.validateBgColor(color) ? color : null;
	},

	getTextColor: function()
	{
		return this.getControl("field-text-color").value;
	},

	/**
	 *
	 * @returns {BX.Intranet.Bitrix24.ThemePicker|*}
	 */
	getThemePicker: function()
	{
		return this.themePicker;
	},

	/**
	 *
	 * @param name
	 * @returns {Element}
	 */
	getControl: function(name)
	{
		return this.getPopup().contentContainer.querySelector(".theme-dialog-" + name);
	},

	getControls: function(name)
	{
		return this.getPopup().contentContainer.querySelectorAll(".theme-dialog-" + name);
	},

	/**
	 *
	 * @returns {BX.PopupWindow}
	 */
	getPopup: function()
	{
		if (this.popup)
		{
			return this.popup;
		}

		this.popup = new BX.PopupWindow("bitrix24-new-theme-dialog", null, {
			width: 500,
			height: 500,
			className: "theme-dialog-popup-window-container",
			titleBar: BX.message("BITRIX24_THEME_CREATE_YOUR_OWN_THEME"),
			closeByEsc: true,
			bindOnResize: false,
			closeIcon: true,
			draggable: true,
			zIndex: 10,
			events: {
				onAfterPopupShow: function() {
					var windowSize =  BX.GetWindowInnerSize();
					var popupWidth = this.popupContainer.offsetWidth;
					var popupHeight = this.popupContainer.offsetHeight;

					var left  = windowSize.innerWidth / 2 - popupWidth / 2;
					var top  = windowSize.innerHeight / 2 - popupHeight / 2;

					this.setBindElement({ left: left, top: top });
					this.adjustPosition();
				},
				onPopupClose: this.resetResources.bind(this)
			},
			buttons: [
				new BX.PopupWindowButton({
					id: "theme-dialog-create-button",
					text: BX.message("BITRIX24_THEME_DIALOG_CREATE_BUTTON"),
					className: "popup-window-button-accept",
					events: {
						click: this.handleCreateButtonClick.bind(this)
					}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message("BITRIX24_THEME_DIALOG_CANCEL_BUTTON"),
					className: "popup-window-button-link theme-dialog-button-link",
					events: {
						click: function() {
							this.popupWindow.close();
						}
					}
				})
			]
		});

		return this.popup;
	},

	getColorPicker: function()
	{
		if (this.colorPicker)
		{
			return this.colorPicker;
		}

		this.colorPicker = new BX.ColorPicker({
			onColorSelected: this.handleBgColorSelect.bind(this)
		});

		return this.colorPicker;
	},

	handleCreateButtonClick: function(event)
	{
		var error = this.validateForm();
		if (error !== null)
		{
			this.showError(error);
			return;
		}

		var createButton = this.getPopup().getButton("theme-dialog-create-button");
		if (BX.hasClass(createButton.getContainer(), "popup-window-button-wait"))
		{
			//double click protection
			return;
		}

		var form = document.forms["theme-new-theme-form"];

		createButton.addClassName("popup-window-button-wait");
		BX.addClass(form, "theme-dialog-form-disabled");

		BX.ajax.submitAjax(form, {
			url: this.getThemePicker().getAjaxHandlerPath(),
			method: "POST",
			dataType: "json",
			data: {
				action: "create",
				sessid: BX.bitrix_sessid(),
				siteId: this.getThemePicker().getSiteId(),
				templateId: this.getThemePicker().getTemplateId(),
				bgImage: this.getBgImage()
			},
			onsuccess: function(response) {

				if (response && response.success && response.theme)
				{
					this.getThemePicker().preloadImages(response.theme["prefetchImages"], function() {

						createButton.removeClassName("popup-window-button-wait");
						BX.removeClass(form, "theme-dialog-form-disabled");

						this.removeThemePreview();
						this.getThemePicker().addItem(response.theme);
						this.getPopup().close();

					}.bind(this));
				}
				else
				{
					createButton.removeClassName("popup-window-button-wait");
					BX.removeClass(form, "theme-dialog-form-disabled");
					this.showError(response.error || BX.message("BITRIX24_THEME_UNKNOWN_ERROR"));
				}
			}.bind(this),

			onfailure: function() {
				createButton.removeClassName("popup-window-button-wait");
				BX.removeClass(form, "theme-dialog-form-disabled");
				this.showError(BX.message("BITRIX24_THEME_UNKNOWN_ERROR"));
			}.bind(this)
		});
	},

	getContent: function()
	{
		return BX.create("form", {
			attrs: {
				name: "theme-new-theme-form",
				method: "post",
				enctype: "multipart/form-data",
				action: this.getThemePicker().getAjaxHandlerPath()
			},
			events: {
				submit: function(event) {
					event.preventDefault();
				}
			},
			children: [
				BX.create("div", {
					props: {
						className: "theme-dialog-form-alert"
					},
					children: [
						BX.create("div", {
							props: {
								className: "theme-dialog-form-alert-content"
							}
						}),
						BX.create("div", {
							props: {
								className: "theme-dialog-form-alert-remove"
							},
							events: {
								click: this.hideError.bind(this)
							}
						})
					]
				}),

				BX.create("div", {
					props: {
						className: "theme-dialog-form"
					},
					children: [
						this.createField(BX.message("BITRIX24_THEME_THEME_BG_IMAGE"), this.getBgImageField()),
						this.createField(BX.message("BITRIX24_THEME_THEME_BG_COLOR"), this.getBgColorField()),
						this.createField(BX.message("BITRIX24_THEME_THEME_TEXT_COLOR"), this.getTextColorField())
					]
				})
			]
		});
	},

	showError: function(error)
	{
		BX.addClass(this.getControl("form-alert"), "theme-dialog-form-alert-show");
		this.getControl("form-alert-content").textContent = error;
	},

	hideError: function()
	{
		BX.removeClass(this.getControl("form-alert"), "theme-dialog-form-alert-show");
	},

	createField: function(title, field)
	{
		return BX.create("div", {
			props: {
				className: "theme-dialog-field"
			},
			children: [
				BX.create("div", {
					props: {
						className: "theme-dialog-field-label"
					},
					text: title
				}),
				BX.create("div", {
					props: {
						className: "theme-dialog-field-value"
					},
					children: [field]
				})
			]
		});
	},

	getBgColorField: function()
	{
		return BX.create("div", {
			attrs: {
				className: "theme-dialog-field-textbox-wrapper"
			},
			events: {
				click: this.handleBgColorClick.bind(this)
			},
			children: [
				BX.create("div", {
					attrs: {
						className: "theme-dialog-field-textbox-color"
					}
				}),
				BX.create("input", {
					attrs: {
						type: "text",
						placeholder: "",
						name: "bgColor",
						maxlength: 7,
						className: "theme-dialog-field-textbox theme-dialog-field-bg-color"
					},
					events: {
						bxchange: this.handleBgColorChange.bind(this)
					}
				}),
				BX.create("div", {
					attrs: {
						className: "theme-dialog-field-textbox-remove"
					},
					events: {
						click: this.handleBgColorClear.bind(this)
					}
				})
			]
		});
	},

	getBgImageField: function()
	{
		return BX.create("div", {
			attrs: {
				className: "theme-dialog-field-file"
			},
			children: [
				BX.create("label", {
					attrs: {
						for: "theme-dialog-field-file-input",
						className: "theme-dialog-field-button"
					},
					events: {
						dragenter: this.handleBgImageEnter.bind(this),
						dragleave: this.handleBgImageLeave.bind(this),
						dragover: this.handleBgImageOver.bind(this),
						drop: this.handleBgImageDrop.bind(this)
					},
					children: [
						BX.create("div", {
							attrs: {
								className: "theme-dialog-field-file-preview"
							}
						}),
						BX.create("div", {
							attrs: {
								className: "theme-dialog-field-file-text"
							},
							children: [
								BX.create("span", {
									attrs: {
										className: "theme-dialog-field-file-add"
									},
									text: BX.message("BITRIX24_THEME_UPLOAD_BG_IMAGE")
								}),
								BX.create("span", {
									attrs: {
										className: "theme-dialog-field-file-add-info"
									},
									text: BX.message("BITRIX24_THEME_DRAG_BG_IMAGE")
								})
							]
						})
					]
				}),
				this.getBgImageControl()
			]
		})
	},

	validateBgImage: function(image)
	{
		if (!image || !/^image\/(jpeg|gif|png)/.test(image.type))
		{
			return BX.message("BITRIX24_THEME_WRONG_FILE_TYPE");
		}

		if (image.size > this.getThemePicker().getMaxUploadSize())
		{
			var limit = this.getThemePicker().getMaxUploadSize() / 1024 / 1024;
			return BX.message("BITRIX24_THEME_FILE_SIZE_EXCEEDED").replace("#LIMIT#", limit.toFixed(0) + "Mb");
		}

		return null;
	},

	validateForm: function()
	{
		var bgImage = this.getBgImage();
		var bgColor = this.getControl("field-bg-color").value;

		if (BX.type.isNotEmptyString(bgColor) && !this.validateBgColor(bgColor))
		{
			return BX.message("BITRIX24_THEME_WRONG_BG_COLOR");
		}

		if (!bgImage && !BX.type.isNotEmptyString(bgColor))
		{
			return BX.message("BITRIX24_THEME_EMPTY_FORM_DATA");
		}

		return null;
	},

	validateBgColor: function(color)
	{
		return BX.type.isNotEmptyString(color) && color.match(/^#([A-Fa-f0-9]{6})$/);
	},

	handleBgImage: function(file)
	{
		if (!file)
		{
			return;
		}

		var error = this.validateBgImage(file);
		if (error !== null)
		{
			this.showError(error);
			this.clearBgImageControl();
			return;
		}

		this.hideError();
		this.setBgImage(file);
		this.showBgImagePreview();
		this.clearBgImageControl();
		this.applyThemePreview();
	},

	clearBgImageControl: function()
	{
		var control = this.getControl("field-file-input");
		BX.remove(control);

		var container = this.getControl("field-button");
		container.appendChild(this.getBgImageControl());
	},

	getBgImageControl: function()
	{
		return BX.create("input", {
			attrs: {
				id: "theme-dialog-field-file-input",
				className: "theme-dialog-field-file-input",
				type: "file",
				accept: "image/jpeg,image/gif,image/png"
			},
			events: {
				change: this.handleBgImageChange.bind(this)
			}
		})
	},

	showBgImagePreview: function()
	{
		var img = document.createElement("img");
		img.src = this.getBgImageObjectUrl();
		img.width = 48;
		img.height = 48;

		var preview = this.getControl("field-file-preview");
		BX.cleanNode(preview);
		preview.appendChild(img);
	},

	handleBgImageChange: function(event)
	{
		var file = event.target.files[0];
		this.handleBgImage(file);
	},

	handleBgImageEnter: function(event)
	{
		BX.addClass(this.getControl("field-button"), "theme-dialog-field-button-hover");
		event.stopPropagation();
		event.preventDefault();
	},

	handleBgImageLeave: function(event)
	{
		BX.removeClass(this.getControl("field-button"), "theme-dialog-field-button-hover");

		event.stopPropagation();
		event.preventDefault();
	},

	handleBgImageOver: function(event)
	{
		event.stopPropagation();
		event.preventDefault();
	},

	handleBgImageDrop: function(event)
	{
		event.stopPropagation();
		event.preventDefault();

		var dt = event.dataTransfer;
		this.handleBgImage(dt.files[0]);
	},

	handleBgColorClick: function(event)
	{
		this.getColorPicker().open({
			bindElement: this.getControl("field-bg-color")
		});
	},

	handleBgColorChange: function()
	{
		if (this.getBgColor())
		{
			this.hideError();
		}

		this.applyThemePreview();
	},

	/**
	 *
	 * @param {Event} event
	 */
	handleBgColorClear: function(event)
	{
		this.getColorPicker().close();

		BX.removeClass(this.getControl("field-bg-color"), "theme-dialog-field-textbox-not-empty");
		this.getControl("field-bg-color").value = "";
		this.getControl("field-textbox-color").style.backgroundColor = "";

		this.applyThemePreview();

		event.stopPropagation();
	},

	handleBgColorSelect: function(color)
	{
		this.getControl("field-bg-color").value = color;
		BX.addClass(this.getControl("field-bg-color"), "theme-dialog-field-textbox-not-empty");
		this.getControl("field-textbox-color").style.backgroundColor = color;

		this.hideError();
		this.applyThemePreview();
	},

	getTextColorField: function()
	{
		return BX.create("div", {
			props: {
				className: "theme-dialog-field-button-switcher"
			},
			children: [
				BX.create("div", {
					props: {
						className:
							"theme-dialog-field-button-switcher-item " +
							"theme-dialog-field-button-switcher-item-left " +
							"theme-dialog-field-button-switcher-item-pressed"
					},
					dataset: {
						textColor: "light"
					},
					text: BX.message("BITRIX24_THEME_THEME_LIGHT_COLOR"),
					events: {
						click: this.handleSwitcherClick.bind(this)
					}
				}),
				BX.create("div", {
					props: {
						className:
							"theme-dialog-field-button-switcher-item " +
							"theme-dialog-field-button-switcher-item-right "
					},
					dataset: {
						textColor: "dark"
					},
					text: BX.message("BITRIX24_THEME_THEME_DARK_COLOR"),
					events: {
						click: this.handleSwitcherClick.bind(this)
					}
				}),
				BX.create("input", {
					attrs: {
						type: "hidden",
						name: "textColor",
						value: "light",
						className: "theme-dialog-field-text-color"
					}
				})
			]
		});
	},

	handleSwitcherClick: function(event)
	{
		var color = event.target.dataset.textColor;
		var switchers = this.getControls("field-button-switcher-item");

		[].forEach.call(switchers, function(switcher) {
			if (switcher.dataset.textColor === color)
			{
				BX.addClass(switcher, "theme-dialog-field-button-switcher-item-pressed");
			}
			else
			{
				BX.removeClass(switcher, "theme-dialog-field-button-switcher-item-pressed");
			}
		});

		this.getControl("field-text-color").value = color;

		this.applyThemePreview();
	},

	applyThemePreview: function()
	{
		if (this.getBgImage() === null && this.getBgColor() === null)
		{
			if (this.previewApplied)
			{
				this.getThemePicker().applyTheme(this.origAppliedThemeId);
			}

			return;
		}

		var baseThemes = this.getThemePicker().getBaseThemes();
		var baseThemeId = this.getTextColor();
		if (!baseThemes[baseThemeId] || !BX.type.isArray(baseThemes[baseThemeId]["css"]))
		{
			return;
		}

		var body = "body { ";

		if (this.getBgImageObjectUrl())
		{
			body += 'background: url("' + this.getBgImageObjectUrl() + '") fixed 0 0 no-repeat; ';
			body += 'background-size: cover; ';
		}

		if (this.getBgColor())
		{
			body += "background-color: " + this.getBgColor() + "; ";
		}

		body += " }";

		if (!this.previewApplied)
		{
			this.origAppliedThemeId = this.getThemePicker().getAppliedThemeId();
		}

		this.getThemePicker().removeThemeAssets(this.getThemePicker().getAppliedThemeId());

		this.getThemePicker().applyThemeAssets({
			id: this.getPreviewThemeId(),
			css: baseThemes[baseThemeId]["css"],
			style: body
		});

		this.getThemePicker().setAppliedThemeId(this.getPreviewThemeId());

		this.previewApplied = true;
	},

	removeThemePreview: function()
	{
		this.getThemePicker().removeThemeAssets(this.getPreviewThemeId());
		this.previewApplied = false;
	},

	getPreviewThemeId: function()
	{
		return this.getTextColor() + ":" + "custom_live_preview"
	},

};

})();

(function() {

"use strict";

BX.namespace("BX.Intranet.Bitrix24");

BX.Intranet.Bitrix24.Slider = function(url, options)
{
	BX.SidePanel.Slider.apply(this, arguments);

	this.handleWindowResize = this.handleWindowResize.bind(this);
	this.handleMessengerOpen = this.handleMessengerOpen.bind(this);
	this.handleHelperShow = this.handleHelperShow.bind(this);
	this.handlePanelClick = this.handlePanelClick.bind(this);
	this.handleMessengerClose = this.handleMessengerClose.bind(this);
};

BX.Intranet.Bitrix24.Slider.prototype =
{
	__proto__: BX.SidePanel.Slider.prototype,
	constructor: BX.Intranet.Bitrix24.Slider,

	applyHacks: function()
	{
		this.adjustBackgroundSize();

		var verticalScrollWidth = window.innerWidth - document.documentElement.clientWidth;
		var horizontalScrollWidth = window.innerHeight - document.documentElement.clientHeight;

		if (this.getImBar() && !this.isMessengerOpen())
		{
			// this.getImBar().style.zIndex = this.options.imBarStylezIndex;
			// this.getImBar().style.right = scrollWidth  + "px";

			var pos = BX.pos(this.getImBar());
			this.getImBar().style.position = "absolute";
			this.getImBar().originalTop = this.getImBar().style.top;
			this.getImBar().style.top = pos.top + "px";
			this.getImBar().style.height = pos.height + horizontalScrollWidth + "px";
			this.getImBar().style.right = -window.pageXOffset + "px";
			this.getImBar().dataset.lockRedraw = true;

			//Safari has a strange glitch due to a transition property
			this.getImBar().style.transition = "none";

			BX.addClass(this.getImBar(), "bx-im-bar-default");
			this.getImBar().style.borderRight = verticalScrollWidth  + "px solid #eef2f4";
		}

		if (this.getPanel())
		{
			// this.getPanel().style.zIndex = this.options.panelStylezIndex;
			this.getPanel().style.cssText += "margin-right: -" + verticalScrollWidth + "px !important";

			BX("bx-panel-userinfo").style.cssText += "padding-right:" + verticalScrollWidth + "px !important";
			BX("bx-panel-site-toolbar").style.cssText += "padding-right:" + verticalScrollWidth + "px !important";

			this.getPanel().addEventListener("click", this.handlePanelClick, true);
		}

		//These hacks can influence the IM Bar height. Leave this code below IM Bar hacks.
		this.getHeader().style.paddingRight = verticalScrollWidth + "px";
		this.getHeader().style.marginRight = "-" + verticalScrollWidth + "px";

		//BX.addCustomEvent("OnMessengerWindowShowPopup", this.handleMessengerOpen);
		BX.addCustomEvent("BX.Helper:onShow", this.handleHelperShow);

		BX.bind(window, "resize", this.handleWindowResize);

		return true;
	},

	resetHacks: function()
	{
		this.getHeader().style.cssText = "";

		this.resetBackgroundSize();

		if (this.getImBar() && !this.isMessengerOpen())
		{
			this.getImBar().style.removeProperty("z-index");
			this.getImBar().style.removeProperty("width");
			this.getImBar().style.removeProperty("right");
			this.getImBar().style.removeProperty("height");
			this.getImBar().style.removeProperty("position");
			this.getImBar().style.top = this.getImBar().originalTop;
			this.getImBar().dataset.lockRedraw = false;

			BX.removeClass(this.getImBar(), "bx-im-bar-default");
			this.getImBar().style.removeProperty("border-right");

			//Safari has a strange glitch due to a transition property
			setTimeout(function() {
				this.getImBar().style.removeProperty("transition");
			}.bind(this), 100);
		}

		if (this.getPanel() && !this.isMessengerOpen())
		{
			this.getPanel().style.removeProperty("z-index");
			this.getPanel().style.removeProperty("margin-right");

			BX("bx-panel-userinfo").style.removeProperty("padding-right");
			BX("bx-panel-site-toolbar").style.removeProperty("padding-right");

			this.getPanel().removeEventListener("click", this.handlePanelClick, true);
		}

		var imBar = BX.getClass("BX.Intranet.Bitrix24.ImBar");
		if (imBar)
		{
			BX.Intranet.Bitrix24.ImBar.redraw();
		}

		if (!this.isMessengerOpen())
		{
			BX.removeCustomEvent("OnMessengerWindowShowPopup", this.handleMessengerOpen);
		}
		BX.removeCustomEvent("BX.Helper:onShow", this.handleHelperShow);

		BX.unbind(window, "resize", this.handleWindowResize);
	},

	adjustBackgroundSize: function()
	{
		var themePicker = BX.getClass("BX.Intranet.Bitrix24.ThemePicker.Singleton");
		if (!themePicker)
		{
			return;
		}

		var theme = themePicker.getAppliedTheme();
		if (theme && theme.resizable === true)
		{
			if (theme.video)
			{
				this.adjustVideoSize();
			}
			else if (theme.width > 0 && theme.height > 0)
			{
				this.adjustImageSize(theme.width, theme.height);
			}
		}
	},

	adjustImageSize: function(imgWidth, imgHeight)
	{
		var containerWidth = document.documentElement.clientWidth;
		var containerHeight = document.documentElement.clientHeight;

		var imgRatio = imgHeight / imgWidth;
		var containerRatio = containerHeight / containerWidth;
		var width = containerRatio > imgRatio ? containerHeight / imgRatio : containerWidth;
		var height = containerRatio > imgRatio ? containerHeight : containerWidth * imgRatio;

		document.body.style.backgroundSize = width + "px " + height + "px";
	},

	adjustVideoSize: function()
	{
		var themePicker = BX.getClass("BX.Intranet.Bitrix24.ThemePicker.Singleton");
		if (!themePicker)
		{
			return;
		}

		var videoContainer = themePicker.getVideoContainer();
		if (videoContainer)
		{
			videoContainer.style.right = window.innerWidth - document.documentElement.clientWidth + "px";
		}
	},

	resetBackgroundSize: function()
	{
		document.body.style.removeProperty("background-size");

		var themePicker = BX.getClass("BX.Intranet.Bitrix24.ThemePicker.Singleton");
		if (themePicker)
		{
			var videoContainer = themePicker.getVideoContainer();
			if (videoContainer)
			{
				videoContainer.style.removeProperty("right");
			}
		}
	},

	getTopBoundary: function()
	{
		var viewer = BX.getClass("BX.UI.Viewer.Instance");
		if (viewer && viewer.isOpen())
		{
			return 0;
		}
		else if (BX.type.isNumber(this.getData().get("topBoundary")))
		{
			return this.getData().get("topBoundary");
		}
		else
		{
			return this.getPanel()? BX.pos(this.getPanel()).bottom : 0;
		}
	},

	getLeftBoundary: function()
	{
		var windowWidth = BX.browser.IsMobile() ? window.innerWidth : document.documentElement.clientWidth;
		return windowWidth < 1160 ? 0 : 240; //Left Menu Width
	},

	getRightBoundary: function()
	{
		var viewer = BX.getClass("BX.UI.Viewer.Instance");
		if (viewer && viewer.isOpen())
		{
			return 0;
		}
		else if (BX.type.isNumber(this.getData().get("rightBoundary")))
		{
			return this.getData().get("rightBoundary");
		}
		else
		{
			var windowWidth = BX.browser.IsMobile() ? window.innerWidth : document.documentElement.clientWidth;
			if (this.getImBar())
			{
				if (this.isMessengerOpen())
				{
					return 0;
				}

				return windowWidth - BX.pos(this.getImBar()).left;
			}

			return 0;
		}
	},

	handleWindowResize: function()
	{
		this.adjustBackgroundSize();

		if (this.getImBar() && !this.isMessengerOpen())
		{
			var pos = this.getImBar().getBoundingClientRect();
			this.getImBar().style.top = window.pageYOffset + 'px';
			this.getImBar().style.height = document.documentElement.clientHeight - pos.top + "px";
		}
	},

	handleHelperShow: function()
	{
		BX.SidePanel.Instance.closeAll(true);
	},

	handleMessengerOpen: function()
	{
		if (this.isOpen())
		{
			BX.SidePanel.Instance.hide();
			BX.addCustomEvent("OnMessengerWindowClosePopup", this.handleMessengerClose);
		}
	},

	handleMessengerClose: function()
	{
		BX.SidePanel.Instance.unhide();

		if (BX.SidePanel.Instance.getTopSlider())
		{
			BX.SidePanel.Instance.getTopSlider().focus();
		}

		BX.removeCustomEvent("OnMessengerWindowClosePopup", this.handleMessengerClose);
	},

	handlePanelClick: function()
	{
		BX.SidePanel.Instance.closeAll();
	},

	/**
	 *
	 * @returns {boolean}
	 */
	isMessengerOpen: function()
	{
		return BX.MessengerWindow && BX.MessengerWindow.isPopupShow();
	},

	/**
	 *
	 * @returns {?Element}
	 */
	getPanel: function()
	{
		return BX("bx-panel", true);
	},

	/**
	 *
	 * @returns {?Element}
	 */
	getHeader: function()
	{
		return BX("header", true);
	},

	/**
	 *
	 * @returns {?Element}
	 */
	getImBar: function()
	{
		return BX("bx-im-bar", true);
	},

	/**
	 *
	 * @returns {?Element}
	 */
	getHelpBlock: function()
	{
		return BX("bx-help-block", true);
	}

};

BX.SidePanel.Manager.registerSliderClass("BX.Intranet.Bitrix24.Slider");

//Compatibility
BX.namespace("BX.Bitrix24");

/**
 * @deprecated use BX.SidePanel.Instance instead
 * @memberOf BX.Bitrix24
 * @memberOf top.BX.Bitrix24
 * @name BX.Bitrix24#Slider
 * @type BX.SidePanel.Manager
 * @static
 * @readonly
 */
Object.defineProperty(BX.Bitrix24, "Slider", {
	get: function() {
		return BX.SidePanel.Instance;
	}
});

/**
 * @deprecated use BX.SidePanel.Instance instead
 * @memberOf BX.Bitrix24
 * @memberOf top.BX.Bitrix24
 * @name BX.Bitrix24#PageSlider
 * @type BX.SidePanel.Manager
 * @static
 * @readonly
 */
Object.defineProperty(BX.Bitrix24, "PageSlider", {
	get: function() {
		return BX.SidePanel.Instance;
	}
});

})();
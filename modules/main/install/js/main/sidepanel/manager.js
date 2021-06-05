(function() {

"use strict";

/**
 * @typedef {object} BX.SidePanel.Options
 * @property {function} [contentCallback]
 * @property {number} [width]
 * @property {string} [title]
 * @property {boolean} [cacheable=true]
 * @property {boolean} [autoFocus=true]
 * @property {boolean} [printable=true]
 * @property {boolean} [allowChangeHistory=true]
 * @property {boolean} [allowChangeTitle]
 * @property {string} [requestMethod]
 * @property {object} [requestParams]
 * @property {string} [loader]
 * @property {string} [contentClassName]
 * @property {object} [data]
 * @property {string} [typeLoader] - option for compatibility
 * @property {number} [animationDuration]
 * @property {number} [customLeftBoundary]
 * @property {number} [customRightBoundary]
 * @property {number} [customTopBoundary]
 * @property {object} [label]
 * @property {boolean} [newWindowLabel]
 * @property {boolean} [copyLinkLabel]
 * @property {?object.<string, function>} [events]
 */

/**
 * @typedef {object} BX.SidePanel.Link
 * @property {string} url - URL
 * @property {string} target - Target Attribute
 * @property {Element} anchor - Dom Node
 * @property {array} [matches] - RegExp Matches
 */

/**
 * @typedef {object} BX.SidePanel.Rule
 * @property {string[]|RegExp[]} condition
 * @property {string[]} [stopParameters]
 * @property {function} [handler]
 * @property {function} [validate]
 * @property {boolean} [allowCrossDomain=false]
 * @property {boolean} [mobileFriendly=false]
 * @property {BX.SidePanel.Options} [options]
 */

/**
 * @namespace BX.SidePanel
 */
BX.namespace("BX.SidePanel");

var instance = null;

/**
 * @memberOf BX.SidePanel
 * @name BX.SidePanel#Instance
 * @type BX.SidePanel.Manager
 * @static
 * @readonly
 */
Object.defineProperty(BX.SidePanel, "Instance", {
	enumerable: false,
	get: function() {

		var topWindow = BX.PageObject.getRootWindow();
		if (topWindow !== window)
		{
			return topWindow.BX.SidePanel.Instance;
		}

		if (instance === null)
		{
			instance = new BX.SidePanel.Manager({});
		}

		return instance;
	}
});

/**
 * @class BX.SidePanel.Manager
 * @param {object} options
 * @constructor
 */
BX.SidePanel.Manager = function(options)
{
	this.anchorRules = [];
	this.anchorBinding = true;

	this.openSliders = [];
	this.lastOpenSlider = null;

	this.opened = false;
	this.hidden = false;
	this.hacksApplied = false;

	this.pageUrl = this.getCurrentUrl();
	this.pageTitle = this.getCurrentTitle();
	this.titleChanged = false;

	this.fullScreenSlider = null;

	this.handleAnchorClick = this.handleAnchorClick.bind(this);
	this.handleDocumentKeyDown = this.handleDocumentKeyDown.bind(this);
	this.handleWindowResize = BX.throttle(this.handleWindowResize, 300, this);
	this.handleWindowScroll = this.handleWindowScroll.bind(this);
	this.handleTouchMove = this.handleTouchMove.bind(this);

	this.handleSliderOpenStart = this.handleSliderOpenStart.bind(this);
	this.handleSliderOpenComplete = this.handleSliderOpenComplete.bind(this);
	this.handleSliderCloseStart = this.handleSliderCloseStart.bind(this);
	this.handleSliderCloseComplete = this.handleSliderCloseComplete.bind(this);
	this.handleSliderLoad = this.handleSliderLoad.bind(this);
	this.handleSliderDestroy = this.handleSliderDestroy.bind(this);
	this.handleEscapePress = this.handleEscapePress.bind(this);
	this.handleFullScreenChange = this.handleFullScreenChange.bind(this);

	BX.addCustomEvent("SidePanel:open", this.open.bind(this));
	BX.addCustomEvent("SidePanel:close", this.close.bind(this));
	BX.addCustomEvent("SidePanel:closeAll", this.closeAll.bind(this));
	BX.addCustomEvent("SidePanel:destroy", this.destroy.bind(this));
	BX.addCustomEvent("SidePanel:hide", this.hide.bind(this));
	BX.addCustomEvent("SidePanel:unhide", this.unhide.bind(this));

	BX.addCustomEvent("SidePanel:postMessage", this.postMessage.bind(this));
	BX.addCustomEvent("SidePanel:postMessageAll", this.postMessageAll.bind(this));
	BX.addCustomEvent("SidePanel:postMessageTop", this.postMessageTop.bind(this));

	BX.addCustomEvent("BX.Bitrix24.PageSlider:close", this.close.bind(this)); //Compatibility
	BX.addCustomEvent("Bitrix24.Slider:postMessage", this.handlePostMessageCompatible.bind(this)); // Compatibility
};

var sliderClassName = null;

/**
 * @static
 * @param {string} className
 */
BX.SidePanel.Manager.registerSliderClass = function(className)
{
	if (BX.type.isNotEmptyString(className))
	{
		sliderClassName = className;
	}
};

/**
 * @static
 * @returns {BX.SidePanel.Slider}
 */
BX.SidePanel.Manager.getSliderClass = function()
{
	var sliderClass = sliderClassName !== null ? BX.getClass(sliderClassName) : null;
	return sliderClass !== null ? sliderClass : BX.SidePanel.Slider;
};

BX.SidePanel.Manager.prototype =
{
	/**
	 * @public
	 * @param {string} url
	 * @param {BX.SidePanel.Options} [options]
	 */
	open: function(url, options)
	{
		if (!BX.type.isNotEmptyString(url))
		{
			return false;
		}

		url = this.refineUrl(url);

		if (this.isHidden())
		{
			this.unhide();
		}

		var topSlider = this.getTopSlider();
		if (topSlider)
		{
			if (topSlider.isOpen() && topSlider.getUrl() === url)
			{
				return false;
			}
		}

		var slider = null;
		if (this.getLastOpenSlider() && this.getLastOpenSlider().getUrl() === url)
		{
			slider = this.getLastOpenSlider();
		}
		else
		{
			if (typeof(options) === "undefined")
			{
				var rule = this.getUrlRule(url);
				options = rule && rule.options ? rule.options : options;
			}

			var sliderClass = BX.SidePanel.Manager.getSliderClass();
			slider = new sliderClass(url, options);

			var offset = null;
			if (slider.getWidth() === null && slider.getCustomLeftBoundary() === null)
			{
				offset = 0;
				var lastOffset = this.getLastOffset();
				if (topSlider && lastOffset !== null)
				{
					offset = Math.min(lastOffset + this.getMinOffset(), this.getMaxOffset());
				}
			}

			slider.setOffset(offset);

			BX.addCustomEvent(slider, "SidePanel.Slider:onOpenStart", this.handleSliderOpenStart);
			BX.addCustomEvent(slider, "SidePanel.Slider:onBeforeOpenComplete", this.handleSliderOpenComplete);
			BX.addCustomEvent(slider, "SidePanel.Slider:onCloseStart", this.handleSliderCloseStart);
			BX.addCustomEvent(slider, "SidePanel.Slider:onBeforeCloseComplete", this.handleSliderCloseComplete);
			BX.addCustomEvent(slider, "SidePanel.Slider:onLoad", this.handleSliderLoad);
			BX.addCustomEvent(slider, "SidePanel.Slider:onDestroy", this.handleSliderDestroy);
			BX.addCustomEvent(slider, "SidePanel.Slider:onEscapePress", this.handleEscapePress);
		}

		if (!this.isOpen())
		{
			this.applyHacks(slider);
		}

		var success = slider.open();
		if (!success)
		{
			this.resetHacks(slider);
		}

		return success;
	},

	/**
	 * @public
	 * @returns {boolean}
	 */
	isOpen: function()
	{
		return this.opened;
	},

	/**
	 * @public
	 * @param {boolean} [immediately]
	 * @param {function} [callback]
	 */
	close: function(immediately, callback)
	{
		var topSlider = this.getTopSlider();
		if (topSlider)
		{
			topSlider.close(immediately, callback);
		}
	},

	/**
	 * @public
	 * @param {boolean} [immediately]
	 */
	closeAll: function(immediately)
	{
		var openSliders = this.getOpenSliders();
		for (var i = openSliders.length - 1; i >= 0; i--)
		{
			var slider = openSliders[i];
			var success = slider.close(immediately);
			if (!success)
			{
				break;
			}
		}
	},

	/**
	 * @public
	 * @returns {boolean}
	 */
	hide: function()
	{
		if (this.hidden)
		{
			return false;
		}

		var topSlider = this.getTopSlider();

		this.getOpenSliders().forEach(function(slider) {
			slider.hide();
		});

		this.hidden = true;

		this.resetHacks(topSlider);

		return true;
	},

	/**
	 * @public
	 * @returns {boolean}
	 */
	unhide: function()
	{
		if (!this.hidden)
		{
			return false;
		}

		this.getOpenSliders().forEach(function(slider) {
			slider.unhide();
		});

		this.hidden = false;

		setTimeout(function() {
			this.applyHacks(this.getTopSlider());
		}.bind(this), 0);

		return true;
	},

	/**
	 * @public
	 * @returns {boolean}
	 */
	isHidden: function()
	{
		return this.hidden;
	},

	/**
	 * @public
	 * @param {string} url
	 */
	destroy: function(url)
	{
		if (!BX.type.isNotEmptyString(url))
		{
			return;
		}

		url = this.refineUrl(url);
		var sliderToDestroy = this.getSlider(url);

		if (this.getLastOpenSlider() && (sliderToDestroy || this.getLastOpenSlider().getUrl() === url))
		{
			this.getLastOpenSlider().destroy();
		}

		if (sliderToDestroy !== null)
		{
			var openSliders = this.getOpenSliders();
			for (var i = openSliders.length - 1; i >= 0; i--)
			{
				var slider = openSliders[i];
				slider.destroy();

				if (slider === sliderToDestroy)
				{
					break;
				}
			}
		}
	},

	/**
	 * @public
	 */
	reload: function()
	{
		var topSlider = this.getTopSlider();
		if (topSlider)
		{
			topSlider.reload();
		}
	},

	/**
	 * @public
	 * @returns {BX.SidePanel.Slider|null}
	 */
	getTopSlider: function()
	{
		var count = this.openSliders.length;
		return this.openSliders[count - 1] ? this.openSliders[count - 1] : null;
	},

	getPreviousSlider: function(currentSlider)
	{
		var previousSlider = null;
		var openSliders = this.getOpenSliders();
		currentSlider = currentSlider || this.getTopSlider();

		for (var i = openSliders.length - 1; i >= 0; i--)
		{
			var slider = openSliders[i];
			if (slider === currentSlider)
			{
				previousSlider = openSliders[i - 1] ? openSliders[i - 1] : null;
				break;
			}
		}

		return previousSlider;
	},

	/**
	 * @public
	 * @param {string} url
	 * @returns {BX.SidePanel.Slider}
	 */
	getSlider: function(url)
	{
		url = this.refineUrl(url);

		var openSliders = this.getOpenSliders();
		for (var i = 0; i < openSliders.length; i++)
		{
			var slider = openSliders[i];
			if (slider.getUrl() === url)
			{
				return slider;
			}
		}

		return null;
	},

	/**
	 * @public
	 * @param {Window} window
	 * @return {BX.SidePanel.Slider|null}
	 */
	getSliderByWindow: function(window)
	{
		var openSliders = this.getOpenSliders();
		for (var i = 0; i < openSliders.length; i++)
		{
			var slider = openSliders[i];
			if (slider.getFrameWindow() === window)
			{
				return slider;
			}
		}

		return null;
	},

	/**
	 * @public
	 * @returns {BX.SidePanel.Slider[]}
	 */
	getOpenSliders: function()
	{
		return this.openSliders;
	},

	/**
	 * @public
	 * @returns {Number}
	 */
	getOpenSlidersCount: function()
	{
		return this.openSliders.length;
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Slider} slider
	 */
	addOpenSlider: function(slider)
	{
		if (!(slider instanceof BX.SidePanel.Slider))
		{
			throw new Error("Slider is not an instance of BX.SidePanel.Slider");
		}

		this.openSliders.push(slider);
	},

	/**
	 * @private
	 * @returns {boolean}
	 */
	removeOpenSlider: function(slider)
	{
		var openSliders = this.getOpenSliders();
		for (var i = 0; i < openSliders.length; i++)
		{
			var openSlider = openSliders[i];
			if (openSlider === slider)
			{
				this.openSliders.splice(i, 1);
				return true;
			}
		}

		return false;
	},

	/**
	 * @public
	 * @returns {null|BX.SidePanel.Slider}
	 */
	getLastOpenSlider: function()
	{
		return this.lastOpenSlider;
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Slider} slider
	 */
	setLastOpenSlider: function(slider)
	{
		if (this.lastOpenSlider !== slider)
		{
			if (this.lastOpenSlider)
			{
				this.lastOpenSlider.destroy();
			}

			this.lastOpenSlider = slider;
		}
	},

	/**
	 * @private
	 */
	resetLastOpenSlider: function()
	{
		if (this.lastOpenSlider && this.getTopSlider() !== this.lastOpenSlider)
		{
			this.lastOpenSlider.destroy();
		}

		this.lastOpenSlider = null;
	},

	/**
	 * @public
	 */
	adjustLayout: function()
	{
		this.getOpenSliders().forEach(function(/*BX.SidePanel.Slider*/slider) {
			slider.adjustLayout();
		});
	},

	/**
	 * @private
	 * @return {?number}
	 */
	getLastOffset: function()
	{
		var openSliders = this.getOpenSliders();
		for (var i = openSliders.length - 1; i >= 0; i--)
		{
			var slider = openSliders[i];
			if (slider.getOffset() !== null)
			{
				return slider.getOffset();
			}
		}

		return null;
	},

	/**
	 * @public
	 * @param {string} url
	 * @returns {string}
	 */
	refineUrl: function(url)
	{
		if (BX.type.isNotEmptyString(url) && url.match(/IFRAME/))
		{
			return BX.util.remove_url_param(url, ["IFRAME", "IFRAME_TYPE"]);
		}

		return url;
	},

	/**
	 * @public
	 * @returns {string}
	 */
	getPageUrl: function()
	{
		return this.pageUrl;
	},

	/**
	 * @public
	 * @returns {string}
	 */
	getCurrentUrl: function()
	{
		return window.location.pathname + window.location.search + window.location.hash;
	},

	/**
	 * @public
	 * @returns {string}
	 */
	getPageTitle: function()
	{
		return this.pageTitle;
	},

	/**
	 * @public
	 * @returns {string}
	 */
	getCurrentTitle: function()
	{
		var title = document.title;
		if (BX.IM)
		{
			title = title.replace(/^\([0-9]+\) /, ""); //replace a messenger counter.
		}

		return title;
	},

	enterFullScreen: function()
	{
		if (!this.getTopSlider() || this.getFullScreenSlider())
		{
			return;
		}

		var container = document.body;
		if (container.requestFullscreen)
		{
			BX.bind(document, "fullscreenchange", this.handleFullScreenChange);
			container.requestFullscreen();
		}
		else if (container.webkitRequestFullScreen)
		{
			BX.bind(document, "webkitfullscreenchange", this.handleFullScreenChange);
			container.webkitRequestFullScreen();
		}
		else if (container.msRequestFullscreen)
		{
			BX.bind(document, "MSFullscreenChange", this.handleFullScreenChange);
			container.msRequestFullscreen();
		}
		else if (container.mozRequestFullScreen)
		{
			BX.bind(document, "mozfullscreenchange", this.handleFullScreenChange);
			container.mozRequestFullScreen();
		}
		else
		{
			console.log("Slider: Full Screen mode is not supported.");
		}
	},

	exitFullScreen: function()
	{
		if (!this.getFullScreenSlider())
		{
			return;
		}

		if (document.exitFullscreen)
		{
			document.exitFullscreen();
		}
		else if (document.webkitExitFullscreen)
		{
			document.webkitExitFullscreen();
		}
		else if (document.msExitFullscreen)
		{
			document.msExitFullscreen();
		}
		else if (document.mozCancelFullScreen)
		{
			document.mozCancelFullScreen();
		}
	},

	getFullScreenElement: function()
	{
		return (
			document.fullscreenElement ||
			document.webkitFullscreenElement ||
			document.mozFullScreenElement ||
			document.msFullscreenElement ||
			null
		);
	},

	getFullScreenSlider: function()
	{
		return this.fullScreenSlider;
	},

	handleFullScreenChange: function(event)
	{
		if (this.getFullScreenElement())
		{
			this.fullScreenSlider = this.getTopSlider();
			BX.addClass(this.fullScreenSlider.getOverlay(), "side-panel-fullscreen");

			this.fullScreenSlider.fireEvent("onFullScreenEnter");
		}
		else
		{
			if (this.getFullScreenSlider())
			{
				BX.removeClass(this.getFullScreenSlider().getOverlay(), "side-panel-fullscreen");
				this.fullScreenSlider.fireEvent("onFullScreenExit");
				this.fullScreenSlider = null;
			}

			BX.unbind(document, event.type, this.handleFullScreenChange);
			window.scrollTo(0, this.pageScrollTop);

			setTimeout(function() {
				this.adjustLayout();
				var event = document.createEvent("Event");
				event.initEvent("resize", true, true);
				window.dispatchEvent(event);
			}.bind(this), 1000);
		}
	},

	/**
	 * @public
	 * @param {string|Window|BX.SidePanel.Slider} source
	 * @param {string} eventId
	 * @param {object} data
	 */
	postMessage: function(source, eventId, data)
	{
		var sender = this.getSliderFromSource(source);
		if (!sender)
		{
			return;
		}

		var previousSlider = null;
		var openSliders = this.getOpenSliders();
		for (var i = openSliders.length - 1; i >= 0; i--)
		{
			var slider = openSliders[i];
			if (slider === sender)
			{
				previousSlider = openSliders[i - 1] ? openSliders[i - 1] : null;
				break;
			}
		}

		var sliderWindow = previousSlider && previousSlider.getWindow() || window;
		sliderWindow.BX.onCustomEvent("Bitrix24.Slider:onMessage", [slider, data]); //Compatibility

		var event = new BX.SidePanel.MessageEvent({
			sender: sender,
			slider: previousSlider ? previousSlider : null,
			data: data,
			eventId: eventId
		});

		if (previousSlider)
		{
			previousSlider.firePageEvent(event);
			previousSlider.fireFrameEvent(event);
		}
		else
		{
			BX.onCustomEvent(window, event.getFullName(), [event]);
		}
	},

	/**
	 * @public
	 * @param {string|Window|BX.SidePanel.Slider} source
	 * @param {string} eventId
	 * @param {object} data
	 */
	postMessageAll: function(source, eventId, data)
	{
		var sender = this.getSliderFromSource(source);
		if (!sender)
		{
			return;
		}

		var event = null;
		var openSliders = this.getOpenSliders();
		for (var i = openSliders.length - 1; i >= 0; i--)
		{
			var slider = openSliders[i];
			if (slider === sender)
			{
				continue;
			}

			event = new BX.SidePanel.MessageEvent({
				sender: sender,
				slider: slider,
				data: data,
				eventId: eventId
			});

			slider.firePageEvent(event);
			slider.fireFrameEvent(event);
		}

		event = new BX.SidePanel.MessageEvent({
			sender: sender,
			slider: null,
			data: data,
			eventId: eventId
		});

		BX.onCustomEvent(window, event.getFullName(), [event]);
	},

	/**
	 * @public
	 * @param {string|Window|BX.SidePanel.Slider} source
	 * @param {string} eventId
	 * @param {object} data
	 */
	postMessageTop: function(source, eventId, data)
	{
		var sender = this.getSliderFromSource(source);
		if (!sender)
		{
			return;
		}

		var event = new BX.SidePanel.MessageEvent({
			sender: sender,
			slider: null,
			data: data,
			eventId: eventId
		});

		BX.onCustomEvent(window, event.getFullName(), [event]);
	},

	/**
	 * @private
	 * @returns {number}
	 */
	getMinOffset: function()
	{
		return 63;
	},

	/**
	 * @private
	 * @returns {number}
	 */
	getMaxOffset: function()
	{
		return this.getMinOffset() * 3;
	},

	/**
	 * @public
	 * @param {{rules: BX.SidePanel.Rule[]}} parameters
	 */
	bindAnchors: function(parameters)
	{
		parameters = parameters || {};

		if (BX.type.isArray(parameters.rules) && parameters.rules.length)
		{
			if (this.anchorRules.length === 0)
			{
				window.document.addEventListener("click", this.handleAnchorClick, true);
			}

			if (!(parameters.rules instanceof Object))
			{
				console.error(
					"BX.SitePanel: anchor rules were created in a different context. " +
					"This might be a reason for a memory leak."
				);

				console.trace();
			}

			parameters.rules.forEach((function(rule) {
				if (BX.type.isArray(rule.condition))
				{
					for (var m = 0; m < rule.condition.length; m++)
					{
						if (BX.type.isString(rule.condition[m]))
						{
							rule.condition[m] = new RegExp(rule.condition[m], "i");
						}
					}
				}

				rule.options = BX.type.isPlainObject(rule.options) ? rule.options : {};
				if (BX.type.isNotEmptyString(rule.loader) && !BX.type.isNotEmptyString(rule.options.loader))
				{
					rule.options.loader = rule.loader;
					delete rule.loader;
				}

				this.anchorRules.push(rule);
			}).bind(this));
		}
	},

	/**
	 * @public
	 */
	isAnchorBinding: function()
	{
		return this.anchorBinding;
	},

	/**
	 * @public
	 */
	enableAnchorBinding: function()
	{
		this.anchorBinding = true;
	},

	/**
	 * @public
	 */
	disableAnchorBinding: function()
	{
		this.anchorBinding = false;
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Event} event
	 */
	handleSliderOpenStart: function(event)
	{
		if (!event.isActionAllowed())
		{
			return;
		}

		var slider = event.getSlider();
		if (slider.isDestroyed())
		{
			return;
		}

		if (this.getTopSlider())
		{
			this.exitFullScreen();

			this.getTopSlider().hideOverlay();

			var sameWidth = (
				this.getTopSlider().getOffset() === slider.getOffset() &&
				this.getTopSlider().getWidth() === slider.getWidth() &&
				this.getTopSlider().getCustomLeftBoundary() === slider.getCustomLeftBoundary()
			);

			if (!sameWidth)
			{
				this.getTopSlider().showShadow();
			}

			this.getTopSlider().hideOrDarkenCloseBtn();
			this.getTopSlider().hidePrintBtn();
			this.getTopSlider().hideExtraLabels();
		}
		else
		{
			slider.setOverlayAnimation(true);
		}

		this.addOpenSlider(slider);

		this.getOpenSliders().forEach(function(slider, index, openSliders) {
			slider.getLabel().moveAt(openSliders.length - index - 1); //move down
		}, this);

		this.losePageFocus();

		if (!this.opened)
		{
			this.pageUrl = this.getCurrentUrl();
			this.pageTitle = this.getCurrentTitle();
		}

		this.opened = true;

		this.resetLastOpenSlider();
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Event} event
	 */
	handleSliderOpenComplete: function(event)
	{
		this.setBrowserHistory(event.getSlider());
		this.updateBrowserTitle();
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Event} event
	 */
	handleSliderCloseStart: function(event)
	{
		if (!event.isActionAllowed())
		{
			return;
		}

		if (event.getSlider() && event.getSlider().isDestroyed())
		{
			return;
		}

		var previousSlider = this.getPreviousSlider();
		var topSlider = this.getTopSlider();

		this.exitFullScreen();

		this.getOpenSliders().forEach(function(slider, index, openSliders) {
			slider.getLabel().moveAt(openSliders.length - index - 2); //move up
		}, this);

		if (previousSlider)
		{
			previousSlider.unhideOverlay();
			previousSlider.hideShadow();
			previousSlider.showOrLightenCloseBtn();

			if (topSlider)
			{
				topSlider.hideOverlay();
				topSlider.hideShadow();
			}
		}
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Event} event
	 */
	handleSliderCloseComplete: function(event)
	{
		var slider = event.getSlider();
		if (slider === this.getTopSlider())
		{
			this.setLastOpenSlider(slider);
		}

		this.cleanUpClosedSlider(slider);
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Event} event
	 */
	handleSliderDestroy: function(event)
	{
		var slider = event.getSlider();

		BX.removeCustomEvent(slider, "SidePanel.Slider:onOpenStart", this.handleSliderOpenStart);
		BX.removeCustomEvent(slider, "SidePanel.Slider:onBeforeOpenComplete", this.handleSliderOpenComplete);
		BX.removeCustomEvent(slider, "SidePanel.Slider:onCloseStart", this.handleSliderCloseStart);
		BX.removeCustomEvent(slider, "SidePanel.Slider:onBeforeCloseComplete", this.handleSliderCloseComplete);
		BX.removeCustomEvent(slider, "SidePanel.Slider:onLoad", this.handleSliderLoad);
		BX.removeCustomEvent(slider, "SidePanel.Slider:onDestroy", this.handleSliderDestroy);
		BX.removeCustomEvent(slider, "SidePanel.Slider:onEscapePress", this.handleEscapePress);

		var frameWindow = event.getSlider().getFrameWindow();
		if (frameWindow)
		{
			frameWindow.document.removeEventListener("click", this.handleAnchorClick, true);
		}

		if (slider === this.getLastOpenSlider())
		{
			this.lastOpenSlider = null;
		}

		this.cleanUpClosedSlider(slider);
	},

	handleEscapePress: function(event)
	{
		if (this.isOnTop() && this.getTopSlider())
		{
			if (this.getTopSlider().canCloseByEsc())
			{
				this.getTopSlider().close();
			}
		}
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Slider} slider
	 */
	cleanUpClosedSlider: function(slider)
	{
		this.removeOpenSlider(slider);

		slider.unhideOverlay();
		slider.hideShadow();

		this.getOpenSliders().forEach(function(slider, index, openSliders) {
			slider.getLabel().moveAt(openSliders.length - index - 1); //update position
		}, this);

		if (this.getTopSlider())
		{
			this.getTopSlider().showOrLightenCloseBtn();
			this.getTopSlider().unhideOverlay();
			this.getTopSlider().hideShadow();
			this.getTopSlider().showExtraLabels();

			if (this.getTopSlider().isPrintable())
			{
				this.getTopSlider().showPrintBtn();
			}
			this.getTopSlider().focus();
		}
		else
		{
			window.focus();
		}

		if (!this.getOpenSlidersCount())
		{
			this.resetHacks(slider);
			this.opened = false;
		}

		this.resetBrowserHistory();
		this.updateBrowserTitle();
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Event} event
	 */
	handleSliderLoad: function(event)
	{
		var frameWindow = event.getSlider().getFrameWindow();
		if (frameWindow)
		{
			frameWindow.document.addEventListener("click", this.handleAnchorClick, true);
		}

		this.setBrowserHistory(event.getSlider());
		this.updateBrowserTitle();
	},

	/**
	 * @private
	 * @param {string|Window|BX.SidePanel.Slider} source
	 * @param {object} data
	 */
	handlePostMessageCompatible: function(source, data)
	{
		this.postMessage(source, "", data);
	},

	/**
	 * @private
	 * @param {string|Window|BX.SidePanel.Slider} source
	 */
	getSliderFromSource: function(source)
	{
		if (source instanceof BX.SidePanel.Slider)
		{
			return source;
		}
		else if (BX.type.isNotEmptyString(source))
		{
			return this.getSlider(source);
		}
		else if (source !== null && source === source.window && window !== source)
		{
			return this.getSliderByWindow(source);
		}

		return null;
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Slider} [slider]
	 * @returns {boolean}
	 */
	applyHacks: function(slider)
	{
		if (this.hacksApplied)
		{
			return false;
		}

		slider && slider.applyHacks();

		this.disablePageScrollbar();
		this.bindEvents();

		slider && slider.applyPostHacks();

		this.hacksApplied = true;

		return true;
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Slider} [slider]
	 * @returns {boolean}
	 */
	resetHacks: function(slider)
	{
		if (!this.hacksApplied)
		{
			return false;
		}

		slider && slider.resetPostHacks();

		this.enablePageScrollbar();
		this.unbindEvents();

		slider && slider.resetHacks();

		this.hacksApplied = false;

		return true;
	},

	/**
	 * @private
	 */
	bindEvents: function()
	{
		BX.bind(document, "keydown", this.handleDocumentKeyDown);
		BX.bind(window, "resize", this.handleWindowResize);
		BX.bind(window, "scroll", this.handleWindowScroll); //Live Comments can change scrollTop

		if (BX.browser.IsMobile())
		{
			BX.bind(document.body, "touchmove", this.handleTouchMove);
		}
	},

	/**
	 * @private
	 */
	unbindEvents: function()
	{
		BX.unbind(document, "keydown", this.handleDocumentKeyDown);
		BX.unbind(window, "resize", this.handleWindowResize);
		BX.unbind(window, "scroll", this.handleWindowScroll);

		if (BX.browser.IsMobile())
		{
			BX.unbind(document.body, "touchmove", this.handleTouchMove);
		}
	},

	/**
	 * @private
	 */
	disablePageScrollbar: function()
	{
		var scrollWidth = window.innerWidth - document.documentElement.clientWidth;
		document.body.style.paddingRight = scrollWidth + "px";
		BX.addClass(document.body, "side-panel-disable-scrollbar");
		this.pageScrollTop = window.pageYOffset || document.documentElement.scrollTop;
	},

	/**
	 * @private
	 */
	enablePageScrollbar: function()
	{
		document.body.style.removeProperty("padding-right");
		BX.removeClass(document.body, "side-panel-disable-scrollbar");
	},

	/**
	 * @private
	 */
	losePageFocus: function()
	{
		if (BX.type.isDomNode(document.activeElement))
		{
			document.activeElement.blur();
		}
	},

	/**
	 * @private
	 * @param {Event} event
	 */
	handleDocumentKeyDown: function(event)
	{
		if (event.keyCode !== 27)
		{
			return;
		}

		event.preventDefault(); //otherwise an iframe loading can be cancelled by a browser

		if (this.isOnTop() && this.getTopSlider())
		{
			if (this.getTopSlider().canCloseByEsc())
			{
				this.getTopSlider().close();
			}
		}
	},

	/**
	 * @private
	 */
	handleWindowResize: function()
	{
		this.adjustLayout();
	},

	/**
	 * @private
	 */
	handleWindowScroll: function()
	{
		window.scrollTo(0, this.pageScrollTop);
		this.adjustLayout();
	},

	/**
	 * @private
	 * @param {Event} event
	 */
	handleTouchMove: function(event)
	{
		event.preventDefault();
	},

	/**
	 * @private
	 * @returns {boolean}
	 */
	isOnTop: function()
	{
		//Photo Slider or something else can cover Side Panel.
		var centerX = document.documentElement.clientWidth / 2;
		var centerY = document.documentElement.clientHeight / 2;
		var element = document.elementFromPoint(centerX, centerY);

		return BX.hasClass(element, "side-panel") || BX.findParent(element, { className: "side-panel" }) !== null;
	},

	/**
	 * @private
	 * @param {MouseEvent} event
	 * @returns {BX.SidePanel.Link|null} link
	 */
	extractLinkFromEvent: function(event)
	{
		event = event || window.event;
		var target = event.target;

		if (event.which !== 1 || !BX.type.isDomNode(target) || event.ctrlKey || event.metaKey)
		{
			return null;
		}

		var a = target;
		if (target.nodeName !== "A")
		{
			a = BX.findParent(target, { tag: "A" }, 1);
		}

		if (!BX.type.isDomNode(a))
		{
			return null;
		}

		// do not use a.href here, the code will fail on links like <a href="#SG13"></a>
		var href = a.getAttribute("href");
		if (href)
		{
			return {
				url: href,
				anchor: a,
				target: a.getAttribute("target")
			};
		}

		return null;
	},

	/**
	 * @private
	 * @param {MouseEvent} event
	 */
	handleAnchorClick: function(event)
	{
		if (!this.isAnchorBinding())
		{
			return;
		}

		var link = this.extractLinkFromEvent(event);

		if (!link || BX.data(link.anchor, "slider-ignore-autobinding"))
		{
			return;
		}

		var rule = this.getUrlRule(link.url, link);

		if (!this.isValidLink(rule, link))
		{
			return;
		}

		if (BX.type.isFunction(rule.handler))
		{
			rule.handler(event, link);
		}
		else
		{
			event.preventDefault();
			this.open(link.url, rule.options);
		}
	},
	/**
	 * @public
	 * @param {string} url
	 */
	emulateAnchorClick: function(url)
	{
		var link = {
			url : url,
			anchor : null,
			target : null
		};
		var rule = this.getUrlRule(url, link);

		if (!this.isValidLink(rule, link))
		{
			BX.reload(url);
		}
		else if (BX.type.isFunction(rule.handler))
		{
			rule.handler(
				new Event(
					"slider",
					{
						"bubbles" : false,
						"cancelable" : true
					}
				),
				link
			);
		}
		else
		{
			this.open(link.url, rule.options);
		}
	},
	/**
	 * @private
	 * @param {string} href
	 * @param {BX.SidePanel.Link} [link]
	 * @returns {BX.SidePanel.Rule|null}
	 */
	getUrlRule: function(href, link)
	{
		if (!BX.type.isNotEmptyString(href))
		{
			return null;
		}

		for (var k = 0; k < this.anchorRules.length; k++)
		{
			var rule = this.anchorRules[k];

			if (!BX.type.isArray(rule.condition))
			{
				continue;
			}

			for (var m = 0; m < rule.condition.length; m++)
			{
				var matches = href.match(rule.condition[m]);
				if (matches && !this.hasStopParams(href, rule.stopParameters))
				{
					if (link)
					{
						link.matches = matches;
					}

					return rule;
				}
			}
		}

		return null;
	},
	/**
	 * @private
	 * @param {BX.SidePanel.Rule} rule
	 * @param {BX.SidePanel.Link} link
	 * @returns {boolean}
	 */
	isValidLink: function(rule, link)
	{
		if (!rule)
		{
			return false;
		}

		if (rule.allowCrossDomain !== true && BX.ajax.isCrossDomain(link.url))
		{
			return false;
		}

		if (rule.mobileFriendly !== true && BX.browser.IsMobile())
		{
			return false;
		}

		if (BX.type.isFunction(rule.validate) && !rule.validate(link))
		{
			return false;
		}

		return true;
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Slider} slider
	 */
	setBrowserHistory: function(slider)
	{
		if (!(slider instanceof BX.SidePanel.Slider))
		{
			return;
		}

		if (slider.canChangeHistory() && slider.isOpen() && slider.isLoaded())
		{
			window.history.replaceState({}, "", slider.getUrl());
		}
	},

	/**
	 * @private
	 */
	resetBrowserHistory: function()
	{
		var topSlider = null;
		var openSliders = this.getOpenSliders();
		for (var i = openSliders.length - 1; i >= 0; i--)
		{
			var slider = openSliders[i];
			if (slider.canChangeHistory() && slider.isOpen() && slider.isLoaded())
			{
				topSlider = slider;
				break;
			}
		}

		var url = topSlider ? topSlider.getUrl() : this.getPageUrl();
		if (url)
		{
			window.history.replaceState({}, "", url);
		}
	},

	/**
	 * @public
	 */
	updateBrowserTitle: function()
	{
		var title = null;
		var openSliders = this.getOpenSliders();
		for (var i = openSliders.length - 1; i >= 0; i--)
		{
			title = this.getBrowserTitle(openSliders[i]);
			if (BX.type.isNotEmptyString(title))
			{
				break;
			}
		}

		if (BX.type.isNotEmptyString(title))
		{
			document.title = title;
			this.titleChanged = true;
		}
		else if (this.titleChanged)
		{
			document.title = this.getPageTitle();
			this.titleChanged = false;
		}
	},

	/**
	 * @private
	 * @param {BX.SidePanel.Slider} slider
	 */
	getBrowserTitle: function(slider)
	{
		if (!slider || !slider.canChangeTitle() || !slider.isOpen() || !slider.isLoaded())
		{
			return null;
		}

		var title = slider.getTitle();
		if (!title && !slider.isSelfContained())
		{
			title = slider.getFrameWindow() ? slider.getFrameWindow().document.title : null;
		}

		return BX.type.isNotEmptyString(title) ? title : null;
	},

	/**
	 * @private
	 * @param url
	 * @param params
	 * @returns {boolean}
	 */
	hasStopParams: function(url, params)
	{
		if (!params || !BX.type.isArray(params) || !BX.type.isNotEmptyString(url))
		{
			return false;
		}

		var questionPos = url.indexOf("?");
		if (questionPos === -1)
		{
			return false;
		}

		var query = url.substring(questionPos);
		for (var i = 0; i < params.length; i++)
		{
			var param = params[i];
			if (query.match(new RegExp("[?&]" + param + "=", "i")))
			{
				return true;
			}
		}

		return false;
	},

	/**
	 * @deprecated
	 * @public
	 * @returns {null|BX.SidePanel.Slider}
	 */
	getLastOpenPage: function()
	{
		return this.getLastOpenSlider();
	},

	/**
	 * @deprecated use getTopSlider method
	 * @public
	 * @returns {BX.SidePanel.Slider}
	 */
	getCurrentPage: function()
	{
		return this.getTopSlider();
	}
};

})();

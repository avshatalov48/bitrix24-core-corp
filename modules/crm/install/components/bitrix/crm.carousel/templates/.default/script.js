if(typeof(BX.CrmCarousel) === "undefined")
{
	BX.CrmCarousel = function()
	{
		this._id = "";
		this._settings = {};
		this._pageNum = 1;
		this._pageCount = 1;
		this._wrapper = null;
		this._container = null;
		this._forwardButton = null;
		this._backwardButton = null;
		this._closeButton = null;
		this._bulletContainer = null;
		this._bulletNodeId = "";
		this._bulletNodes = [];
		this._autorewind = false;
		this._autorewindInterval = 60000;
		this._autorewindIntervalId = null;
		this._autoRewindHandler = BX.delegate(this.onAutoRewindTimer, this);
		this._mouseOverHandler = BX.delegate(this.onMouseOver, this);
		this._mouseOutHandler = BX.delegate(this.onMouseOut, this);
		this._forwardButtonHandler = BX.delegate(this.onForwardButtonClick, this);
		this._backwardButtonHandler = BX.delegate(this.onBackwardButtonClick, this);
		this._closeButtonHandler = BX.delegate(this.onCloseButtonClick, this);
		this._bulletNodeHandler = BX.delegate(this.onBulletNodeClick, this);
		this._closeConfirmDialog = null;
	};

	BX.CrmCarousel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._pageNum = parseInt(this.getSetting("pageNum", 1));
			if(!BX.type.isNumber(this._pageNum))
			{
				this._pageNum = 1;
			}

			this._pageCount = parseInt(this.getSetting("pageCount", 1));
			if(!BX.type.isNumber(this._pageCount))
			{
				this._pageCount = 1;
			}

			this._wrapper = BX(this.getSetting("wrapperId"));
			if(!BX.type.isElementNode(this._wrapper))
			{
				throw "BX.CrmCarousel. Could not find wrapper.";
			}

			this._container = BX(this.getSetting("containerId"));
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmCarousel. Could not find container.";
			}

			if(this._pageCount > 1)
			{
				this._bulletContainer = BX(this.getSetting("bulletContainerId"));
				if(!BX.type.isElementNode(this._bulletContainer))
				{
					throw "BX.CrmCarousel. Could not find bullet container.";
				}

				this._forwardButton = BX(this.getSetting("forwardButtonId"));
				if(BX.type.isElementNode(this._forwardButton))
				{
					BX.bind(this._forwardButton, "click", this._forwardButtonHandler);
				}

				this._backwardButton = BX(this.getSetting("backwardButtonId"));
				if(BX.type.isElementNode(this._backwardButton))
				{
					BX.bind(this._backwardButton, "click", this._backwardButtonHandler);
				}
			}

			this._closeButton = BX(this.getSetting("closeButtonId"));
			if(BX.type.isElementNode(this._closeButton))
			{
				BX.bind(this._closeButton, "click", this._closeButtonHandler);
			}

			this._bulletNodeId = this.getSetting("bulletNodeId");
			if(!BX.type.isNotEmptyString(this._bulletNodeId))
			{
				this._bulletNodeId = "bullet_#pagenum#";
			}

			if(this._pageCount > 1)
			{
				this._bulletNodes = this._bulletContainer.querySelectorAll(".crm-carousel-bullet-item");
				for(var i = 0, l = this._bulletNodes.length; i < l; i++)
				{
					var pageNum = i + 1;
					var node = this._bulletNodes[i];
					node.id = this.getPageBulletNodeId(pageNum);
					node.setAttribute("data-page-num", pageNum);
					if(pageNum === this._pageNum)
					{
						BX.addClass(node, "crm-carousel-bullet-current");
					}
					BX.bind(node, "click", this._bulletNodeHandler);
				}

				if(this._pageNum !== 1)
				{
					this.rewind(this._pageNum, false);
				}

				if(this.getSetting("autorewind", false))
				{
					this.enableAutoRewind(true);
				}
			}
		},
		release: function()
		{
			this.enableAutoRewind(false);

			if(this._forwardButton)
			{
				BX.unbind(this._forwardButton, "click", this._forwardButtonHandler);
				this._forwardButton = null;
			}

			if(this._backwardButton)
			{
				BX.unbind(this._backwardButton, "click", this._backwardButtonHandler);
				this._backwardButton = null;
			}

			if(this._closeButton)
			{
				BX.unbind(this._closeButton, "click", this._closeButtonHandler);
				this._closeButton = null;
			}

			for(var i = 0, l = this._bulletNodes.length; i < l; i++)
			{
				BX.unbind(this._bulletNodes[i], "click", this._bulletNodeHandler);
			}
			this._bulletNodes = [];
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		onForwardButtonClick: function()
		{
			this.enableAutoRewind(false);
			this.goToNext();
		},
		onBackwardButtonClick: function()
		{
			this.enableAutoRewind(false);
			this.goToPrevious();
		},
		onCloseButtonClick: function()
		{
			this.openCloseConfirmDialog();
		},
		onBulletNodeClick: function(e)
		{
			this.enableAutoRewind(false);
			var pageNum = parseInt(BX.getEventTarget(e).getAttribute("data-page-num"));
			if(pageNum > 0)
			{
				this.rewind(pageNum, false);
			}
		},
		onAutoRewindTimer: function()
		{
			if(!this._autorewind)
			{
				return;
			}

			this.goToNext();
			this.startAutoRewind();
		},
		onMouseOver: function()
		{
			if(this._autorewind)
			{
				this.stopAutoRewind();
			}
		},
		onMouseOut: function()
		{
			if(this._autorewind)
			{
				this.startAutoRewind();
			}
		},
		onCloseConfirm: function(sender, eventArgs)
		{
			if(!this._closeConfirmDialog)
			{
				return;
			}

			this._closeConfirmDialog.close();
			if(BX.type.isBoolean(eventArgs["isAccepted"]) && eventArgs["isAccepted"])
			{
				this.close();
			}
		},
		enableAutoRewind: function(enable)
		{
			enable = !!enable;
			if(this._autorewind === enable)
			{
				return;
			}

			this._autorewind = enable;
			if(this._autorewind)
			{
				this.startAutoRewind();
				BX.bind(this._container, "mouseover", this._mouseOverHandler);
				BX.bind(this._container, "mouseout", this._mouseOutHandler);
			}
			else
			{
				this.stopAutoRewind();
				BX.unbind(this._container, "mouseover", this._mouseOverHandler);
				BX.unbind(this._container, "mouseout", this._mouseOutHandler);
			}
		},
		startAutoRewind: function()
		{
			this._autorewindIntervalId = window.setTimeout(this._autoRewindHandler, this._autorewindInterval);
		},
		stopAutoRewind: function()
		{
			if(this._autorewindIntervalId !== null)
			{
				window.clearTimeout(this._autorewindIntervalId);
				this._autorewindIntervalId = null;
			}
		},
		goToNext: function()
		{
			if(this._pageNum < this._pageCount)
			{
				this.rewind((this._pageNum + 1), true);
			}
			else
			{
				this.rewind( 1, false);
			}
		},
		goToPrevious: function()
		{
			if(this._pageNum > 1)
			{
				this.rewind((this._pageNum - 1), true);
			}
			else
			{
				this.rewind(this._pageCount, false);
			}
		},
		getPageBulletNodeId:function(pageNum)
		{
			return this._bulletNodeId.replace(/#pagenum#/ig, pageNum);
		},
		getPageBulletNode: function(pageNum)
		{
			return BX(this.getPageBulletNodeId(pageNum));
		},
		rewind: function(pageNum, enableTransition)
		{
			if(this._pageNum === pageNum)
			{
				return;
			}

			BX.removeClass(BX(this.getPageBulletNode(this._pageNum)), "crm-carousel-bullet-current");

			this._pageNum = pageNum;
			if(!!enableTransition)
			{
				BX.addClass(this._container, "crm-carousel-transition");
			}
			else
			{
				BX.removeClass(this._container, "crm-carousel-transition");
			}
			this._container.style.transform = "translateX(-" + (100 * (this._pageNum - 1)).toString() + "%)";

			BX.addClass(BX(this.getPageBulletNode(this._pageNum)), "crm-carousel-bullet-current");
		},
		openCloseConfirmDialog: function()
		{
			if(!this._closeConfirmDialog)
			{
				this._closeConfirmDialog = BX.CrmCarouselCloseDialog.create(
					this._id,
					{ callback: BX.delegate(this.onCloseConfirm, this) }
				);
			}
			this._closeConfirmDialog.open();
		},
		close: function()
		{
			var eventArgs = { cancel: false };
			BX.onCustomEvent(window, "ON_CAROUSEL_CLOSE", [this, eventArgs]);

			if(eventArgs["cancel"])
			{
				return;
			}

			this.release();
			BX.remove(this._wrapper);
			this._wrapper = this._container = null;
		}
	};

	BX.CrmCarousel.create = function(id, settings)
	{
		var self = new BX.CrmCarousel();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmCarouselCloseDialog) === "undefined")
{
	BX.CrmCarouselCloseDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._callback = null;
		this._popup = null;
	};

	BX.CrmCarouselCloseDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			var callback = this.getSetting("callback");
			if(BX.type.isFunction(callback))
			{
				this._callback = callback;
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			return BX.CrmCarouselCloseDialog.messages.hasOwnProperty(name) ? BX.CrmCarouselCloseDialog.messages[name] : name;
		},
		open: function()
		{
			if(!this._popup)
			{
				this._popup = new BX.PopupWindow(
					this._id,
					null,
					{
						autoHide: false,
						draggable: true,
						bindOptions: { forceBindPosition: false },
						closeByEsc: true,
						closeIcon :
							{
								marginRight: "-2px",
								marginTop: "3px"
							},
						events:
							{
								onPopupClose: BX.delegate(this.onPopupClose, this),
								onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
							},
						titleBar: this.getMessage("title"),
						content: this.prepareContent(),
						buttons:
							[
								new BX.PopupWindowButton(
									{
										text : this.getMessage("closeButton"),
										className : "popup-window-button-accept",
										events: { click: BX.delegate(this.onAcceptButtonClick, this) }
									}
								),
								new BX.PopupWindowButtonLink(
									{
										text : BX.message("JS_CORE_WINDOW_CANCEL"),
										className : "popup-window-button-link-cancel",
										events: { click: BX.delegate(this.onCancelButtonClick, this) }
									}
								)
							],
						className : "crm-carousel-popup-close",
						lightShadow : true
					}
				);
			}

			if(!this._popup.isShown())
			{
				this._popup.show();
			}
		},
		close: function()
		{
			if(this._popup.isShown())
			{
				this._popup.close();
			}
		},
		prepareContent: function()
		{
			return(
				BX.create(
					"DIV",
					{
						attrs: { className: "crm-carousel-popup-close-text" },
						children:
							[
								BX.create(
									"SPAN",
									{
										attrs: { className: "crm-carousel-popup-close-text-item" },
										text: this.getMessage("confirm")
									}
								)
							]
					}
				)
			);
		},
		onAcceptButtonClick: function()
		{
			if(this._callback)
			{
				this._callback(this, { isAccepted: true });
			}
		},
		onCancelButtonClick: function()
		{
			if(this._callback)
			{
				this._callback(this, { isAccepted: false });
			}
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}
		}
	};

	if(typeof(BX.CrmCarouselCloseDialog.messages) === "undefined")
	{
		BX.CrmCarouselCloseDialog.messages = {};
	}

	BX.CrmCarouselCloseDialog.create = function(id, settings)
	{
		var self = new BX.CrmCarouselCloseDialog();
		self.initialize(id, settings);
		return self;
	}
}
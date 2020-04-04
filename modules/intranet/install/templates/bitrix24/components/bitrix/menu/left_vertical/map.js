BX.namespace("BX.Bitrix24");

BX.Bitrix24.SlidingPanel = function(options)
{
	this.containerClassName = this.containerClassName || "sliding-panel-window";
	this.container = BX.create("div", {
		props: {
			className: this.containerClassName
		}
	});

	this.overlayClassName = this.overlayClassName || "sliding-panel-overlay";
	this.overlay = BX.create("div", {
		props: {
			className: this.overlayClassName
		}
	});

	this.isOpen = false;

	this.header = BX("header");
	this.imBar = BX("bx-im-bar");
	this.panel = BX("panel");
	this.creatorConfirmedPanel = BX("creatorconfirmed");

	this.animation = null;
	this.startParams = this.startParams || {};
	this.endParams = this.endParams || {};
	this.currentParams = null;

	BX.bind(this.container, "click", this.onContainerClick.bind(this));
	BX.addCustomEvent("onTopPanelCollapse", this.onTopPanelCollapse.bind(this));
};

BX.Bitrix24.SlidingPanel.prototype = {

	animateStep: function(state)
	{
		//implements in child classes
	},

	setContent: function()
	{
		//implements in child classes
	},

	open: function()
	{
		if (this.isOpen)
		{
			return;
		}

		this.isOpen = true;

		BX.bind(document, "keyup", BX.proxy(this.onDocumentKeyUp, this));
		BX.bind(document, "click", BX.proxy(this.onDocumentClick, this));
		this.header.addEventListener("click", BX.proxy(this.onHeaderClick, this), true);

		if (!this.overlay.parentNode)
		{
			document.body.appendChild(this.overlay);
		}

		if (!this.container.parentNode)
		{
			this.setContent();
			//this.container.appendChild(this.content);
			this.overlay.appendChild(this.container);
		}

		var scrollSize = window.innerWidth - document.documentElement.clientWidth;
		document.body.style.paddingRight = scrollSize + "px";

		if (this.imBar)
		{
			this.imBar.style.right = scrollSize + "px";
		}

		if (this.panel)
		{
			this.panel.style.zIndex = 3001;
		}

		if (this.creatorConfirmedPanel)
		{
			this.creatorConfirmedPanel.style.zIndex = 3000;
		}

		document.body.style.overflow = "hidden";
		this.header.style.zIndex = 3000;

		this.adjustPosition();

		if (this.animation)
		{
			this.animation.stop();
		}

		this.animation = new BX.easing({
			duration: 300,
			start: this.currentParams ? this.currentParams : this.startParams,
			finish: this.endParams,
			transition : BX.easing.transitions.linear,
			step: BX.proxy(function(state) {
				this.currentParams = state;
				this.animateStep(state);
			}, this),
			complete: BX.proxy(function() {
				this.onTrasitionEnd();
			}, this)
		});

		this.animation.animate();
	},

	close: function(immediately)
	{
		if (!this.isOpen)
		{
			if (this.animation)
			{
				this.animation.stop(true);
			}

			return;
		}

		this.isOpen = false;

		BX.unbind(document, "keyup", BX.proxy(this.onDocumentKeyUp, this));
		BX.unbind(document, "click", BX.proxy(this.onDocumentClick, this));
		this.header.removeEventListener("click", BX.proxy(this.onHeaderClick, this), true);

		this.container.classList.remove(this.containerClassName + "-open");

		if (this.animation)
		{
			this.animation.stop();
		}

		if (immediately === true)
		{
			this.currentParams = this.startParams;
			this.onTrasitionEnd();
		}
		else
		{
			this.animation = new BX.easing({
				duration: 300,
				start: this.currentParams,
				finish: this.startParams,
				transition : BX.easing.transitions.linear,
				step: BX.proxy(function(state) {
					this.currentParams = state;
					this.animateStep(state);
				}, this),
				complete: BX.proxy(function() {
					this.onTrasitionEnd();
				}, this)
			});

			this.animation.animate();
		}
	},

	adjustPosition: function()
	{
		var headerPosition = BX.pos(this.header);
		var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

		if (scrollTop > 0)
		{
			this.overlay.style.bottom = -scrollTop + "px";
			this.container.style.bottom = -scrollTop + "px";
		}

		var top = scrollTop > headerPosition.bottom ? scrollTop : headerPosition.bottom;
		this.overlay.style.top = top + "px";
	},

	onTrasitionEnd: function()
	{
		this.animation = null;
		if (this.isOpen)
		{
			this.currentParams = this.endParams;
			this.container.classList.add(this.containerClassName + "-open");
		}
		else
		{
			this.currentParams = this.startParams;

			if (this.overlay.parentNode)
			{
				this.overlay.parentNode.removeChild(this.overlay);
			}

			if (this.imBar)
			{
				this.imBar.style.right = "";
			}

			if (this.panel)
			{
				this.panel.style.cssText = "";
			}

			if (this.creatorConfirmedPanel)
			{
				this.creatorConfirmedPanel.style.cssText = "";
			}

			document.body.style.cssText = "";
			this.container.style.cssText = "";
			this.header.style.cssText = "";
			this.overlay.style.cssText = "";
		}
	},

	onContainerClick: function(event)
	{
		event.stopPropagation();
	},

	onDocumentKeyUp: function(event)
	{
		if (event.keyCode === 27)
		{
			this.close();
		}
	},

	onDocumentClick: function(event)
	{
		if (BX.isParentForNode(this.container, event.target))
		{
			//Firefox fires events from children nodes
			return;
		}

		this.close();
	},

	onHeaderClick: function(event)
	{
		//we are trying to resolve a conflict with the help popup.
		if (this.isOpen && event.target.className.match(/help-/))
		{
			this.close(true);
		}
	},

	onTopPanelCollapse: function()
	{
		if (this.isOpen)
		{
			this.adjustPosition();
		}
	}
};

BX.Bitrix24.GroupPanel = function(options)
{
	this.containerClassName = "group-panel-window";
	this.overlayClassName = "group-panel-overlay";
	this.startParams = { translateX: -100, opacity: 0 };
	this.endParams = { translateX: 0, opacity: 65 };

	BX.Bitrix24.SlidingPanel.apply(this, arguments);

	options = options || {};

	this.ajaxPath = BX.type.isNotEmptyString(options.ajaxPath) ? options.ajaxPath : null;
	this.siteId = BX.type.isNotEmptyString(options.siteId) ? options.siteId : BX.message("SITE_ID");

	this.menu = BX("menu-all-groups-link");
	this.menuOverlay = document.createElement("div");
	this.menuOverlay.className = "group-panel-menu-overlay";

	this.leftMenu = BX("bx-left-menu");
	this.content = BX("group-panel-content");
	this.items = BX("group-panel-items");
	this.counter = BX("group-panel-header-filter-counter");

	var intranetGroups = this.items.getElementsByClassName("group-panel-item-intranet");
	var extranetGroups = this.items.getElementsByClassName("group-panel-item-extranet");
	if (intranetGroups.length <= 20 && extranetGroups.length <= 20)
	{
		BX.addClass(this.container, "group-panel-window-one-column");
	}

	this.closeLink = BX("group-panel-close-link");

	this.filters = [].slice.call(this.content.getElementsByClassName("group-panel-header-filter"));
	for (var i = 0; i < this.filters.length; i++)
	{
		var filter = this.filters[i];
		BX.bind(filter, "click", BX.proxy(this.onFilterClick, this));
	}

	BX.bind(this.items, "click", this.onItemsClick.bind(this));
	BX.bind(this.closeLink, "click", this.close.bind(this));
	BX.bind(this.menu, "click", this.onMenuClick.bind(this));

	var closeImmediately = function() {
		this.close(true);
	}.bind(this);

	BX.addCustomEvent("BX.Bitrix24.Map:onBeforeOpen", closeImmediately);
	BX.addCustomEvent("BX.Bitrix24.LeftMenuClass:onDragStart", closeImmediately);
	BX.addCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuToggle", closeImmediately);
};

BX.Bitrix24.GroupPanel.prototype = Object.create(BX.Bitrix24.SlidingPanel.prototype);
BX.Bitrix24.GroupPanel.prototype.constructor = BX.Bitrix24.GroupPanel;
BX.Bitrix24.GroupPanel.prototype.super = BX.Bitrix24.SlidingPanel.prototype;

BX.Bitrix24.GroupPanel.prototype.setContent = function()
{
	this.container.appendChild(this.content);
};

BX.Bitrix24.GroupPanel.prototype.animateStep = function(state)
{
	this.container.style.transform = "translateX(" + state.translateX + "%)";
	//this.overlay.style.opacity = state.opacity / 100;
	this.overlay.style.backgroundColor = "rgba(0, 0, 0, " + state.opacity / 100 + ")";
};

BX.Bitrix24.GroupPanel.prototype.open = function()
{
	BX.onCustomEvent("BX.Bitrix24.GroupPanel:onBeforeOpen", [this]);

	//a hack for the company pulse
	if (window.pulse_loading && window.pulse_loading.open)
	{
		window.pulse_loading.close(true);
	}

	this.leftMenu.style.zIndex = 3000;
	this.container.style.display = "block";
	BX.addClass(this.menu.parentNode, "menu-item-block-hover");
	this.menu.innerHTML = BX.message("menu_hide");

	var pos = BX.pos(this.leftMenu);
	this.menuOverlay.style.left = pos.left + "px";
	this.menuOverlay.style.top = pos.bottom + "px";
	this.menuOverlay.style.width = pos.width + "px";
	this.menuOverlay.style.backgroundColor = BX.style(this.leftMenu, "backgroundColor");
	this.menuOverlay.style.height = document.documentElement.scrollHeight - pos.bottom + "px";

	document.body.appendChild(this.menuOverlay);

	this.super.open.apply(this, arguments);
};

BX.Bitrix24.GroupPanel.prototype.close = function()
{
	this.menu.innerHTML = BX.message("menu_show");
	this.super.close.apply(this, arguments);
};

BX.Bitrix24.GroupPanel.prototype.onTrasitionEnd = function()
{
	this.super.onTrasitionEnd.apply(this, arguments);
	if (!this.isOpen)
	{
		this.leftMenu.style.cssText = "";
		BX.removeClass(this.menu.parentNode, "menu-item-block-hover");
		this.menuOverlay.parentNode.removeChild(this.menuOverlay);
	}
};

BX.Bitrix24.GroupPanel.prototype.onMenuClick = function(event)
{
	if (this.isOpen)
	{
		this.close();
	}
	else
	{
		this.open();
	}

	event.stopPropagation();
};

BX.Bitrix24.GroupPanel.prototype.onFilterClick = function(event)
{
	var filterElement = BX.type.isDomNode(BX.proxy_context) ? BX.proxy_context : null;

	var currentFilter = this.content.dataset.filter ? this.content.dataset.filter : "all";
	var newFilter = filterElement.dataset.filter ? filterElement.dataset.filter : "all";

	if (currentFilter !== newFilter)
	{
		this.content.dataset.filter = newFilter;
		this.saveFilter(newFilter);

		new BX.easing({
			duration: 50,
			start: { opacity: 1 },
			finish: { opacity: 0 },
			transition : BX.easing.transitions.linear,

			step: BX.delegate(function(state) {
				this.items.style.opacity = state.opacity / 100;
			}, this),

			complete: BX.delegate(function() {

				BX.removeClass(this.content, "group-panel-content-" + currentFilter);
				BX.addClass(this.content, "group-panel-content-" + newFilter);

				new BX.easing({
					duration: 50,
					start: { opacity: 0 },
					finish: { opacity: 1 },
					transition : BX.easing.transitions.linear,
					step: BX.delegate(function(state) {
						this.items.style.opacity = state.opacity / 100;
					}, this),
					complete: BX.delegate(function() {
						this.items.style.cssText = "";
					}, this)
				}).animate();

			}, this)
		}).animate();
	}

	event.stopPropagation();
};

BX.Bitrix24.GroupPanel.prototype.onItemsClick = function(event)
{
	if (!BX.hasClass(event.target, "group-panel-item-star"))
	{
		return;
	}

	var star = event.target;
	var item = star.parentNode;
	var groupId = item.dataset.id;

	var action = BX.hasClass(item, "group-panel-item-favorite") ? "remove_from_favorites" : "add_to_favorites";
	BX.toggleClass(item, "group-panel-item-favorite");

	this.animateStart(star);
	this.animateCounter(action === "add_to_favorites");

	BX.ajax({
		method: "POST",
		dataType: "json",
		url: this.ajaxPath,
		data: {
			sessid : BX.bitrix_sessid(),
			site_id : this.siteId,
			action: action,
			groupId: groupId
		}
	});

	event.preventDefault();
};


BX.Bitrix24.GroupPanel.prototype.animateStart = function(star)
{
	var flyingStar = star.cloneNode();
	flyingStar.style.marginLeft = "-" + star.offsetWidth + "px";
	star.parentNode.appendChild(flyingStar);

	new BX.easing({
		duration: 200,
		start: { opacity: 100, scale: 100 },
		finish: { opacity: 0, scale: 300 },
		transition : BX.easing.transitions.linear,
		step: function(state) {
			flyingStar.style.transform = "scale(" + state.scale / 100 + ")";
			flyingStar.style.opacity = state.opacity / 100;
		},
		complete: function() {
			flyingStar.parentNode.removeChild(flyingStar);
		}
	}).animate();
};

BX.Bitrix24.GroupPanel.prototype.animateCounter = function(positive)
{
	this.counter.innerHTML = positive === false ? "-1" : "+1";

	new BX.easing({
		duration: 400,
		start: { opacity: 100, top: 0 },
		finish: { opacity: 0, top: -20 },
		transition : BX.easing.transitions.linear,
		step: function(state) {
			this.counter.style.top = state.top + "px";
			this.counter.style.opacity = state.opacity / 100;
		}.bind(this),
		complete: function() {
			this.counter.style.cssText = "";
		}.bind(this)
	}).animate();
};

BX.Bitrix24.GroupPanel.prototype.saveFilter = function(filter)
{
	if (!this.ajaxPath || !BX.type.isNotEmptyString(filter))
	{
		return;
	}

	BX.ajax({
		method: "POST",
		dataType: "json",
		url: this.ajaxPath,
		data: {
			sessid : BX.bitrix_sessid(),
			site_id : this.siteId,
			action: "set_group_filter",
			filter: filter
		}
	});
};

BX.Bitrix24.Map = function(options)
{
	this.containerClassName = "sitemap-window";
	this.overlayClassName = "sitemap-window-overlay";
	this.startParams = { translateY: -100, opacity: 0 };
	this.endParams = { translateY: 0, opacity: 65 };

	BX.Bitrix24.SlidingPanel.apply(this, arguments);

	this.menu = BX("sitemap-menu");
	this.content = BX("sitemap-content");
	this.closeLink = BX("sitemap-close-link");

	BX.bind(this.menu, "click", this.onMenuClick.bind(this));
	BX.bind(this.closeLink, "click", this.close.bind(this));
};

BX.Bitrix24.Map.prototype = Object.create(BX.Bitrix24.SlidingPanel.prototype);
BX.Bitrix24.Map.prototype.constructor = BX.Bitrix24.Map;
BX.Bitrix24.Map.prototype.super = BX.Bitrix24.SlidingPanel.prototype;

BX.Bitrix24.Map.prototype.setContent = function()
{
	this.container.appendChild(this.content);
};

BX.Bitrix24.Map.prototype.animateStep = function(state)
{
	this.container.style.transform = "translateY(" + state.translateY + "%)";
	//this.overlay.style.opacity = state.opacity / 100;
	this.overlay.style.backgroundColor = "rgba(0, 0, 0, " + state.opacity / 100 + ")";
};

BX.Bitrix24.Map.prototype.open = function()
{
	BX.onCustomEvent("BX.Bitrix24.Map:onBeforeOpen", [this]);

	this.menu.classList.add("sitemap-menu-open");
	this.super.open.apply(this, arguments);
};

BX.Bitrix24.Map.prototype.close = function()
{
	this.menu.classList.remove("sitemap-menu-open");
	this.super.close.apply(this, arguments);
};

BX.Bitrix24.Map.prototype.onMenuClick = function(event)
{
	if (this.isOpen)
	{
		this.close();
	}
	else
	{
		this.open();
	}

	event.stopPropagation();
};
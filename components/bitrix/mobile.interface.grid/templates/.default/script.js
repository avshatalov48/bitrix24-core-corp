BX.namespace("BX.Mobile.Grid");

BX.Mobile.Grid = {
	init: function(params)
	{
		this.curPage = 1;
		this.pagerName = "";
		this.pagesNum = 1;
		this.ajaxUrl = "";
		this.sortEventName = "";
		this.fieldsEventName = "";
		this.filterEventName = "";
		this.reloadGridAfterEvent = true;

		if (typeof params == 'object')
		{
			this.pagerName = params.pagerName || "";
			this.pagesNum = params.pagesNum || 1;
			this.ajaxUrl = params.ajaxUrl || "";
			this.sortEventName = params.sortEventName || "";
			this.fieldsEventName = params.fieldsEventName || "";
			this.filterEventName = params.filterEventName || "";
			this.reloadGridAfterEvent = params.reloadGridAfterEvent !== "N";
		}

		BX.bind(window, "scroll", function() {
			var clientHeight = document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;
			var documentHeight = document.documentElement.scrollHeight ? document.documentElement.scrollHeight : document.body.scrollHeight;
			var scrollTop = window.pageYOffset ? window.pageYOffset : (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);

			if ((documentHeight - clientHeight) <= scrollTop)
			{
				BX.Mobile.Grid.getNextPageItems();
			}
		});

		this.wrapper = document.querySelector("[data-role='mobile-grid']");
		this.sections = document.querySelector("[data-role='mobile-grid-sections']");

		if (this.reloadGridAfterEvent)
		{
			BXMobileApp.addCustomEvent(this.sortEventName, function() {
				window.BXMobileApp.UI.Page.reload();
			});
			BXMobileApp.addCustomEvent(this.fieldsEventName, function() {
				window.BXMobileApp.UI.Page.reload();
			});
			BXMobileApp.addCustomEvent(this.filterEventName, function() {
				window.BXMobileApp.UI.Page.reload();
			});
		}
	},
	showMoreActions: function(actions)
	{
		var buttons = [];
		for (var i=0; i<actions.length; i++)
		{
			buttons.push({
				title: actions[i].TEXT,
				callback:BX.proxy(function()
				{
					eval(this.action);
				}, {action: actions[i].ONCLICK})
			});
		}

		new window.BXMobileApp.UI.ActionSheet({
				buttons: buttons
			}, "actionSheet"
		).show();
	},
	getNextPageItems: function()
	{
		this.curPage++;

		if (this.curPage > this.pagesNum)
			return;

		BXMobileApp.UI.Page.PopupLoader.show();
		var ajaxUrl = this.ajaxUrl.indexOf("?") !== -1 ? this.ajaxUrl + "&" + this.pagerName + "=" + this.curPage : this.ajaxUrl + "?" + this.pagerName + "=" + this.curPage;

		BX.ajax({
			timeout:   30,
			method:   'POST',
			url: ajaxUrl,
			data: {
				ajax: "Y",
				search: this.searchInput.value.length > 2 ? this.searchInput.value : ""
			},
			onsuccess: BX.proxy(function(newHTML)
			{
				var ob = BX.processHTML(newHTML, false),
					tmpNode = BX.create("div", {html: ob.HTML});
				var items = tmpNode.querySelectorAll('[data-role="mobile-grid-item"]');
				if (items)
				{
					for(var i=0; i<items.length; i++)
					{
						if (this.wrapper)
							this.wrapper.appendChild(items[i]);
					}
				}
				BX.ajax.processScripts(ob.SCRIPT);

				BXMobileApp.UI.Page.PopupLoader.hide();
			}, this),
			onfailure: function(){
			}
		});
	},

	searchInit : function()
	{
		this.searchInput = document.querySelector("[data-role='search-input']");
		if (this.searchInput)
		{
			BX.bind(this.searchInput.form, "submit", BX.proxy(function (e) {
				this.onSearchKeyUp();
				return BX.PreventDefault(e);
			}, this));
			BX.bind(this.searchInput, "keyup", BX.proxy(function () {
				this.onSearchKeyUp();
			}, this));

			var searchCancel = document.querySelector("[data-role='search-cancel']");
			if (searchCancel)
			{
				BX.bind(searchCancel, "click", BX.proxy(function(){
					this.searchInput.value = "";
					this.onSearchKeyUp();
				}, this));
			}
		}
	},

	onSearchKeyUp : function()
	{
		if (this.timeoutId)
			clearTimeout(this.timeoutId);

		if (this.searchInput.value.length > 2 || this.searchInput.value.length == 0)
		{
			this.timeoutId = setTimeout(BX.proxy(function () {
				BX.ajax.post(
					this.ajaxUrl,
					{
						action: "search",
						sessid: BX.bitrix_sessid(),
						search: this.searchInput.value,
						doNotUseViewPort: true
					},

					BX.proxy(function (result) {

						if (this.wrapper)
						{
							this.curPage = 1;

							var ob = BX.processHTML(result, false);
							var	f = function(){
									if (this.wrapper.childNodes.length > 0)
										BX.ajax.processScripts(ob.SCRIPT);
									else
										BX.defer_proxy(f, this);
								};
							if (ob.HTML)
							{
								this.wrapper.innerHTML = ob.HTML;
							}
							else //empty search
							{
								this.wrapper.innerHTML = '' +
									'<div class="mobile-grid-empty-search"> ' +
										'<div class="mobile-grid-empty-search-box"> ' +
										'<div class="mobile-grid-empty-search-icon"></div><br /> ' +
										'<div class="mobile-grid-empty-search-text">' + BX.message("M_GRID_EMPTY_SEARCH") + '</div> ' +
										'</div>' +
									'</div>';
							}

							if (this.sections)
							{
								this.sections.style.display = this.searchInput.value.length == 0 ? 'block' : 'none';
							}

							BX.defer_proxy(f, this);
						}
					}, this)
				);
			}, this), 250);
		}
	}
};


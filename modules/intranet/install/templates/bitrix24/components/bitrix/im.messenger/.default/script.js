BX.namespace("BX.Intranet.Bitrix24.ImBar");

(function() {

	var fixedAdminPanelHeight = 0;
	var adminPanelHeight = null;

	var isTransparentMode = false;
	var isScrollMode = false;
	var scrollModeThreshold = 20;

	function init()
	{
		var adminPanel = getAdminPanel();
		if (adminPanel)
		{
			var adminPanelState = BX.getClass("BX.admin.panel.state");
			if (adminPanelState && adminPanelState.fixed)
			{
				fixedAdminPanelHeight = getAdminPanelHeight();
			}

			BX.addCustomEvent("onTopPanelCollapse", function() {
				adminPanelHeight = null;
				if (BX.admin.panel.isFixed())
				{
					fixedAdminPanelHeight = getAdminPanelHeight();
				}

				adjustAdminPanel();

			}.bind(this));

			BX.addCustomEvent("onTopPanelFix", function(isFixed) {

				if (isFixed)
				{
					fixedAdminPanelHeight = getAdminPanelHeight();
				}
				else
				{
					fixedAdminPanelHeight = 0;
				}

				adjustAdminPanel();

			}.bind(this));

			adjustAdminPanel();
		}

		BX.bind(window, "scroll", redraw);
		BX.bind(window, "resize", redraw);

		redraw();

		BX.bind(BX("bx-im-bar-notify"), "click", function(){
			if (typeof(BXIM) == 'undefined') return false;
			BXIM.openNotify();
		});

		BX.bind(BX("bx-im-bar-search"), "click", function(){
			if (typeof(BXIM) == 'undefined') return false;
			BXIM.openMessenger(0, 'im');
		});

		BX.bind(BX("bx-im-bar-ol"), "click", function(){
			if (typeof(BXIM) == 'undefined') return false;
			BXIM.openMessenger(0, 'im-ol');
		});

		BX.bind(BX("bx-im-btn-call"), "click", function(e){
			if (typeof(BXIM) == 'undefined') return false;
			BXIM.webrtc.openKeyPad(e);
		});

		BX.bind(window, "scroll", function(){
			if (typeof(BXIM) == 'undefined' || !BXIM.messenger.popupPopupMenu) return true;
			if (BX.util.in_array(BXIM.messenger.popupPopupMenu.uniquePopupId.replace('bx-messenger-popup-',''), ["createChat", "contactList"]))
			{
				BXIM.messenger.popupPopupMenu.close();
			}
		});


		BX.bindDelegate(BX("bx-im-external-recent-list"), "contextmenu", {className: 'bx-messenger-cl-item'}, function(e) {
			if (typeof(BXIM) == 'undefined') return false;

			BXIM.messenger.openPopupMenu(this, 'contactList', false);

			return BX.PreventDefault(e);
		});

		BX.bindDelegate(BX("bx-im-external-recent-list"), "click", {className: 'bx-messenger-cl-item'}, function(e){
			if (typeof(BXIM) == 'undefined') return false;

			BXIM.openMessenger(this.getAttribute('data-userId'));

			return BX.PreventDefault(e);
		});

		BX.addCustomEvent("onMessengerWindowBodyOverflow", function(messengerWindow, size)
		{
			var td = BX.findChildrenByClassName(BX('im-workarea-popup'), "bx-im-fullscreen-popup-td", true);
			for (var i = 0; i < td.length; i++)
			{
				var computedStyle = getComputedStyle(td[i], null);
				computedStyle = computedStyle? parseInt(computedStyle.getPropertyValue('padding-left').toString().replace('px', '')): 85;
				td[i].style.paddingRight = (computedStyle+size)+'px';
			}
			document.body.style.paddingRight = size + "px";
			BX('bx-im-bar').style.right = size + "px";
		});

		BX.addCustomEvent("onImUpdateCounterNotify", function(counter) {
			var notifyCounter = BX.findChildByClassName(BX("bx-im-bar-notify"), "bx-im-informer-num");
			if (!notifyCounter)
				return false;

			if (counter > 0)
			{
				notifyCounter.innerHTML = '<div class="bx-im-informer-num-digit">'+(counter > 99? "99+": counter)+'</div>';
			}
			else
			{
				notifyCounter.innerHTML = "";
			}
		});

		BX.addCustomEvent("onImUpdateCounterLines", function(counter, type) {
			var node = null;
			if (type === 'LINES')
			{
				node = BX("bx-im-bar-ol");
			}
			else
			{
				return false;
			}

			var notifyCounter = node && BX.findChildByClassName(node, "bx-im-informer-num");
			if (!notifyCounter)
				return false;

			if (counter > 0)
			{
				notifyCounter.innerHTML = '<div class="bx-im-informer-num-digit">'+(counter > 99? "99+": counter)+'</div>';
			}
			else
			{
				notifyCounter.innerHTML = "";
			}
		});

		BX.bind(BX("im-workarea-backgound-selector"), "change", function(){
			BX("im-workarea-backgound-selector-title").innerHTML = this.options[this.selectedIndex].text;
		});

		BX.addCustomEvent('onMessengerWindowInit', function(){
			BX("im-workarea-backgound-selector-title").innerHTML = BX("im-workarea-backgound-selector").options[BX("im-workarea-backgound-selector").selectedIndex].text;
		});

		BX.addCustomEvent("onImInit", function(initObj) {
			initObj.notify.panelButtonCall = BX("bx-im-btn-call");
			initObj.notify.panelButtonCallAnlgePosition = "bottom";
			initObj.notify.panelButtonCallAnlgeOffset = 131;
			BX.MessengerCommon.recentListRedraw();
		});
	}

	function redraw()
	{
		var imBar = getImBar();
		if (!imBar || imBar.dataset.lockRedraw === "true")
		{
			return;
		}

		adjustAdminPanel();

		var scrollWidth = document.documentElement.scrollWidth - document.documentElement.clientWidth;
		if (scrollWidth > 0)
		{
			if (!isTransparentMode)
			{
				BX.addClass(imBar, "bx-im-bar-transparent");
				isTransparentMode = true;
			}
		}
		else
		{
			if (isTransparentMode)
			{
				BX.removeClass(imBar, "bx-im-bar-transparent");
				isTransparentMode = false;
			}
		}


		var threshold = scrollModeThreshold;
		if (fixedAdminPanelHeight === 0 && getAdminPanel())
		{
			threshold += getAdminPanelHeight()
		}

		if (window.pageYOffset > threshold)
		{
			if (!isScrollMode)
			{
				BX.addClass(imBar, "bx-im-bar-scroll-mode");
				isScrollMode = true;
			}
		}
		else
		{
			if (isScrollMode)
			{
				BX.removeClass(imBar, "bx-im-bar-scroll-mode");
				isScrollMode = false;
			}
		}
	}

	function getImBar()
	{
		return BX("bx-im-bar");
	}

	function getAdminPanel()
	{
		return BX("bx-panel");
	}

	function adjustAdminPanel()
	{
		var adminPanel = getAdminPanel();
		var imBar = getImBar();

		if (!adminPanel || !imBar)
		{
			return;
		}

		var rect = adminPanel.getBoundingClientRect();

		if (rect.bottom > 0)
		{
			imBar.style.top = Math.max(rect.bottom, fixedAdminPanelHeight) + "px";
		}
		else
		{
			imBar.style.top = Math.max(0, fixedAdminPanelHeight) + "px";
		}
	}

	function getAdminPanelHeight()
	{
		if (adminPanelHeight !== null)
		{
			return adminPanelHeight;
		}

		var adminPanel = getAdminPanel();
		if (adminPanel)
		{
			adminPanelHeight = adminPanel.offsetHeight;
		}
		else
		{
			adminPanelHeight = 0;
		}

		return adminPanelHeight;
	}

	function closeMessenger()
	{
		BX.MessengerWindow.closePopup();
	}

	BX.Intranet.Bitrix24.ImBar.init = init;
	BX.Intranet.Bitrix24.ImBar.redraw = redraw;
	window.bxFullscreenClose = closeMessenger;

})();

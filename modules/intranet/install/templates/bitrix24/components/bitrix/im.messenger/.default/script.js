function bxImBarInit()
{
	BX.isImBarTransparent = false;
	BX.bind(window, 'scroll', bxImBarRedraw);
	BX.bind(window, 'resize', bxImBarRedraw);
	BX.addCustomEvent("onTopPanelCollapse", bxImBarRedraw);

	bxImBarRedraw();

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
	BX.addCustomEvent("onImUpdateCounterMessage", function(counter, type) {
		var node = null;
		if (type == 'LINES')
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

	BX.addCustomEvent("onPullOnlineEvent", BX.delegate(function(command,params)
	{
		if (command == 'user_online')
		{
			if (typeof(BXIM.messenger.online) == 'undefined')
				return false;

			if (BXIM.messenger.online[params.USER_ID] != 'Y')
			{
				BXIM.messenger.online[params.USER_ID] = 'Y';
				bxImBarRecount();
			}
		}
		else if (command == 'user_offline')
		{
			if (typeof(BXIM.messenger.online) == 'undefined')
				return false;

			if (BXIM.messenger.online[params.USER_ID] == 'Y')
			{
				BXIM.messenger.online[params.USER_ID] = 'N';
				bxImBarRecount();
			}
		}
		else if (command == 'online_list')
		{
			BXIM.messenger.online = {};
			for (var i in params.USERS)
			{
				BXIM.messenger.online[i] = 'Y';
			}
			//bxImBarRecount();
		}
	}, this));

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

function bxImBarRedraw()
{
	var bar = BX('bx-im-bar');
	if (!bar || bar.dataset.lockRedraw === "true")
	{
		return;
	}

	var scrolledY = window.pageYOffset || document.documentElement.scrollTop;
	var scrolledX = window.pageXOffset || document.documentElement.scrollLeft;
	var scrollWidth = document.documentElement.scrollWidth - document.documentElement.clientWidth;
	var barOffset = 63;

	var panel = BX('bx-panel');
	if (panel)
	{
		barOffset = barOffset+panel.offsetHeight;
	}

	var creatorNotify = BX('creatorconfirmed');
	if (creatorNotify)
	{
		barOffset = barOffset+ creatorNotify.offsetHeight;
	}

	if(scrolledY <= barOffset)
	{
		bar.style.top = (barOffset - scrolledY) + 'px';
	}
	else if(scrolledY > barOffset)
	{
		if (bar.style.top != "0px")
		{
			bar.style.top = 0;
		}
	}

	if(scrollWidth > 19 && (scrollWidth - scrolledX) > 19)
	{
		if (!BX.isImBarTransparent)
		{
			BX.addClass(bar, 'bx-im-bar-transparent');
			BX.isImBarTransparent = true;
		}
	}
	else
	{
		if (BX.isImBarTransparent)
		{
			BX.removeClass(bar, 'bx-im-bar-transparent');
			BX.isImBarTransparent = false;
		}
	}
}

function bxImBarRecount()
{
	if (typeof(BXIM.messenger.online) == 'undefined' || !BX('bx-im-online-count'))
		return false;

	var count = 0;
	for (var i in BXIM.messenger.online)
	{
		if (BXIM.messenger.online[i] == 'Y')
		{
			count++;
		}
	}
	count = count <= 0? 1: count;
	count = count > 9999? 9999: count;

	BX('bx-im-online-count').innerHTML = count;

	return true;
}

function bxFullscreenClose()
{
	BX.MessengerWindow.closePopup();
}

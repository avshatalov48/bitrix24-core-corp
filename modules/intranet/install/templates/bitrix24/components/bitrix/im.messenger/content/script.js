function bxFullscreenInit()
{
	BX.bind(BX("im-workarea-popup"), "click", function(){
		bxFullscreenClose();
	});
	BX.bind(BX("im-workarea-backgound-selector"), "change", function(){
		BX("im-workarea-backgound-selector-title").innerHTML = this.options[this.selectedIndex].text;
	});
	BX.addCustomEvent('onMessengerWindowInit', function(){
		BX("im-workarea-backgound-selector-title").innerHTML = BX("im-workarea-backgound-selector").options[BX("im-workarea-backgound-selector").selectedIndex].text;
	})
}

function bxFullscreenClose()
{
	var redirect = '/';
	var items = BX.findChildrenByClassName(BX('bx-left-menu'), "menu-item-link");
	if (items)
	{
		if (BX.hasClass(items[0].parentNode, 'menu-item-active'))
		{
			if (items[1].parentNode)
			{
				redirect = items[1].href;
			}
		}
		else
		{
			redirect = items[0].href;
		}
	}
	if(BXIM.revision < 121)
	{
		location.href = redirect;
	}
	else
	{
		BX.MessengerWindow.closePopup({redirect: redirect});
	}
}

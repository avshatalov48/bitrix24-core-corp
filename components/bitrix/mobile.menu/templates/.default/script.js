MenuSettings = {
	lang: {},
	userId: false,
	canInvite: false,
	calendarFirstVisit: false,
	setSettings: function (settings)
	{
		if (settings)
		{
			if (settings.lang)
				this.lang = settings.lang;
			if (settings.userId)
				this.userId = settings.userId;
			if (settings.canInvite)
				this.canInvite = settings.canInvite;
			if (settings.calendarFirstVisit)
				this.calendarFirstVisit = settings.calendarFirstVisit;
		}

		BX.onCustomEvent("onMobileMenuSettingsSet", [settings]);
	}
};


Menu = {
	currentItem: null,

	init: function (currentItem)
	{
		this.currentItem = currentItem;
		var items = document.getElementById("menu-items");
		var that = this;

		new FastButton(
			items,
			function (event)
			{
				that.onItemClick(event);
			}
		);

	},

	onItemClick: function (event)
	{
		var target = event.target;
		var isChild = (BX.hasClass(target.parentNode, "menu-item"));
		if (target && target.nodeType && target.nodeType == 1 && (BX.hasClass(target, "menu-item") || isChild))
		{
			if (isChild)
				target = target.parentNode;
			if (this.currentItem != null)
				this.unselectItem(this.currentItem);
			this.selectItem(target);
			var url = target.getAttribute("data-url");
			var pageId = target.getAttribute("data-pageid");

			if (BX.type.isNotEmptyString(url) && BX.type.isNotEmptyString(pageId))
				app.loadPage(url, pageId);
			else if (BX.type.isNotEmptyString(url))
				app.loadPage(url);
			else
				target.onclick();

			this.currentItem = target;
		}

	},

	selectItem: function (item)
	{
		if (!BX.hasClass(item, "menu-item-selected"))
			BX.addClass(item, "menu-item-selected");
	},

	unselectItem: function (item)
	{
		BX.removeClass(item, "menu-item-selected");
	}
}


document.addEventListener("DOMContentLoaded", function ()
{
	Menu.init(null);
}, false);

function userList()
{
	if (MenuSettings.canInvite)
	{
		app.openBXTable({
			url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/?mobile_action=get_user_list&tags=Y&detail_url=" + (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/users/?user_id=",
			isroot: true,
			table_settings: {
				alphabet_index: true,
				outsection: false,
				button: {
					type: "plus",
					callback: function ()
					{
						app.openNewPage((BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/users/invite.php");
					}
				}
			}
		});
	}
	else
	{
		app.openUserList({
			source_url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/?mobile_action=get_user_list&tags=Y&detail_url=" + (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/users/?user_id="
		});
	}
	app.closeMenu();
}

function webdavList(p)
{
	app.openBXTable({
		url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/webdav/' + p,
		isroot: true,
		table_settings: {
			type: "files",
			useTagsInSearch: false
		}
	});
	app.closeMenu();
}

function calendarList(userId)
{

	BX.addCustomEvent('mobile_calendar_first_page', function ()
	{
		window.bCalendarShowMobileHelp = false;
	});

	if (window.bCalendarShowMobileHelp == undefined)
	{
		window.bCalendarShowMobileHelp = MenuSettings.calendarFirstVisit;
	}

	if (window.bCalendarShowMobileHelp === false || window.platform == 'android')
	{
		app.openBXTable({
			url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/?mobile_action=calendar&user_id=' + userId,
			isroot: true,
			table_id: 'calendar_list',
			table_settings: {
				cache: true,
				useTagsInSearch: false,
				use_sections: true,
				button: {
					type: 'plus',
					callback: function ()
					{
						app.openNewPage((BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/calendar/edit_event.php');
					}
				}
			}
		});
	}
	else
	{
		app.loadPage((BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/calendar/first_page.php');
	}
	app.closeMenu();
}

BX.addCustomEvent('mobile_calendar_first_page_trigger', function ()
{
	window.bCalendarShowMobileHelp = false;
	calendarList(MenuSettings.userId);
});


BX.addCustomEvent("onMobileMenuSettingsSet", function ()
{
	var pullParams = {
		enable: true,
		pulltext: MenuSettings.lang.pulltext,
		downtext: MenuSettings.lang.downtext,
		loadtext: MenuSettings.lang.loadtext
	};
	if (app.enableInVersion(2))
		pullParams.action = "RELOAD";
	else
		pullParams.callback = function ()
		{
			document.location.reload();
		};
	app.pullDown(pullParams);
});


BX.ready(function ()
{
	Menu.init(null);
});


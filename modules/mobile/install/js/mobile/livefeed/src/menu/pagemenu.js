import {Loc, Type} from 'main.core';
import {Instance,PostMenuInstance,FollowManagerInstance} from '../feed';

class PageMenu
{
	constructor()
	{
		this.type = 'list';
		this.listPageMenuItems = [];
		this.detailPageMenuItems = [];
	}

	init(data)
	{
		this.type = (data.type === 'detail' ? 'detail' : 'list');

		const menuItems = this.getPageMenuItems();
		const title = (
			this.type === 'detail'
				? (Type.isStringFilled(Loc.getMessage('MSLLogEntryTitle')) ? Loc.getMessage('MSLLogEntryTitle') : '')
				: (Type.isStringFilled(Loc.getMessage('MSLLogTitle')) ? Loc.getMessage('MSLLogTitle') : '')
		);

		if (menuItems.length > 0)
		{
			if (BXMobileAppContext.getApiVersion() >= Instance.getApiVersion('pageMenu'))
			{
				this.initPagePopupMenu();
			}
			else
			{
				app.menuCreate({
					items: menuItems
				});
				BXMobileApp.UI.Page.TopBar.title.setCallback(() => { app.menuShow(); });
			}
		}
		else if (BXMobileAppContext.getApiVersion() < Instance.getApiVersion('pageMenu'))
		{
			BXMobileApp.UI.Page.TopBar.title.setCallback("");
		}

		BXMobileApp.UI.Page.TopBar.title.setText(title);
		BXMobileApp.UI.Page.TopBar.title.show();
	}

	initPagePopupMenu()
	{
		if (BXMobileAppContext.getApiVersion() < Instance.getApiVersion('pageMenu'))
		{
			return;
		}

		const buttons = [];

		if(!oMSL.logId)
		{
			buttons.push({
				type: 'search',
				callback: function ()
				{
					app.exec("showSearchBar");
				}
			});
		}

		var menuItems = this.getPageMenuItems(this.type);

		if (
			BX.type.isArray(menuItems)
			&& menuItems.length > 0
		)
		{
			buttons.push({
				type: 'more',
				callback: () => {
					this.showPageMenu(this.type);
				},
			});
		}

		app.exec('setRightButtons', {
			items: buttons,
		});
	}

	showPageMenu()
	{
		if (this.type === 'detail')
		{
			this.detailPageMenuItems = this.buildDetailPageMenu(oMSL.menuData);
		}

		const menuItems = this.getPageMenuItems();
		if (menuItems.length <= 0)
		{
			return;
		}

		const popupMenuItems = [];
		const popupMenuActions = {};

		menuItems.forEach(menuItem => {
			popupMenuItems.push({
				id: menuItem.id,
				title: menuItem.name,
				iconUrl: (Type.isStringFilled(menuItem.image) ? menuItem.image : ''),
				iconName: (Type.isStringFilled(menuItem.iconName) ? menuItem.iconName : ''),
				sectionCode: 'defaultSection',
			});

			popupMenuActions[menuItem.id] = menuItem.action;
		});

		app.exec('setPopupMenuData', {
			items: popupMenuItems,
			sections: [
				{
					id: 'defaultSection',
				}
			],
			callback: (event) => {
				if (event.eventName === 'onDataSet')
				{
					app.exec('showPopupMenu');
				}
				else if (
					event.eventName === 'onItemSelected'
					&& Type.isPlainObject(event.item)
					&& Type.isStringFilled(event.item.id)
					&& Type.isFunction(popupMenuActions[event.item.id])
				)
				{
					popupMenuActions[event.item.id]();
				}
			}
		});
	}

	getPageMenuItems()
	{
		return (this.type === 'detail' ? this.detailPageMenuItems : this.listPageMenuItems);
	}

	buildDetailPageMenu(data)
	{
		var menuNode = null;

		if (BXMobileAppContext.getApiVersion() >= Instance.getApiVersion('pageMenu'))
		{
			menuNode = document.getElementById(`log-entry-menu-${Instance.getLogId()}`);
		}

		PostMenuInstance.init({
			logId: Instance.getLogId(),
			postId: parseInt(data.post_id),
			postPerms: data.post_perm,
			useShare: (data.entry_type === 'blog'),
			useFavorites: (menuNode && menuNode.getAttribute('data-use-favorites') === 'Y'),
			useFollow: (data.read_only !== 'Y'),
			usePinned: (Instance.getLogId() > 0),
			useRefreshComments: true,
			favoritesValue: (menuNode && menuNode.getAttribute('data-favorites') === 'Y'),
			followValue: (FollowManagerInstance.getFollowValue()),
			pinnedValue: (menuNode && menuNode.getAttribute('data-pinned') === 'Y'),
			contentTypeId: data.post_content_type_id,
			contentId: parseInt(data.post_content_id),
			target: menuNode,
			context: 'detail',
		});

		return PostMenuInstance.getMenuItems().map((item) => {
			item.name = item.title;
			item.image = item.iconUrl;

			delete item.title;
			delete item.iconUrl;

			return item;
		});
	};
}

export {
	PageMenu
}
import {Type,Loc} from 'main.core';
import {Ajax} from 'mobile.ajax';
import {Instance,PageMenuInstance} from './feed';

class FollowManager
{
	constructor()
	{
		this.defaultValue = true;
		this.value = true;

		this.class = {
			postItemFollow: 'post-item-follow',
			postItemFollowActive: 'post-item-follow-active',
		};
	}

	init()
	{
	}

	setFollow(params)
	{
		const logId = (!Type.isUndefined(params.logId) ? parseInt(params.logId) : 0);
		const pageId = (!Type.isUndefined(params.pageId) ? params.pageId : false);
		const runEvent = (!Type.isUndefined(params.bRunEvent) ? params.bRunEvent : true);
		const useAjax = (!Type.isUndefined(params.bAjax) ? params.bAjax : false);

		let turnOnOnly = (typeof params.bOnlyOn != 'undefined' ? params.bOnlyOn : false);
		if (turnOnOnly == 'NO')
		{
			turnOnOnly = false;
		}

		let menuNode = null;
		if (Type.isDomNode(params.menuNode))
		{
			menuNode = params.menuNode;
		}
		else if (Type.isStringFilled(params.menuNode))
		{
			menuNode = document.getElementById(params.menuNode);
		}

		if (!menuNode)
		{
			menuNode = document.getElementById(`log-entry-menu-${logId}`);
		}

		let followBlock = document.getElementById(`log_entry_follow_${logId}`);
		if (!followBlock)
		{
			followBlock = document.getElementById(`log_entry_follow`);
		}

		let followWrap = document.getElementById(`post_item_top_wrap_${logId}`);
		if (!followWrap)
		{
			followWrap = document.getElementById(`post_item_top_wrap`);
		}

		let oldValue = null;
		if (menuNode)
		{
			oldValue = (menuNode.getAttribute('data-follow') === 'Y' ? 'Y' : 'N');
		}
		else if (followBlock)
		{
			oldValue = (followBlock.getAttribute('data-follow') == 'Y' ? 'Y' : 'N');
		}
		else
		{
			return false;
		}

		const newValue = (oldValue === 'Y' ? 'N' : 'Y');

		if (
			(
				!Type.isStringFilled(Instance.getOption('detailPageId'))
				|| Instance.getOption('detailPageId') !== pageId
			)
			&& (
				!turnOnOnly
				|| oldValue === 'N'
			)
		)
		{
			this.drawFollow({
				value: (oldValue !== 'Y'),
				followBlock: followBlock,
				followWrap: followWrap,
				menuNode: menuNode,
				runEvent: runEvent,
				turnOnOnly: turnOnOnly,
				logId: logId,
			});
		}

		if (useAjax)
		{
			Ajax.runAction('socialnetwork.api.livefeed.changeFollow', {
				data: {
					logId: logId,
					value: newValue,
				},
				analyticsLabel: {
					b24statAction: (newValue === 'Y' ? 'setFollow' : 'setUnfollow'),
				}
			}).then((response) => {
				if (response.data.success)
				{
					return;
				}

				this.drawFollow({
					value: (oldValue === 'Y'),
					followBlock: followBlock,
					followWrap: followWrap,
					menuNode: menuNode,
					runEvent: true,
					turnOnOnly: turnOnOnly,
					logId: logId,
				});
			}, (response) => {
				this.drawFollow({
					value: (oldValue === 'Y'),
					followBlock: followBlock,
					followWrap: followWrap,
					menuNode: menuNode,
					runEvent: false,
				});
			});
		}

		return false;
	}

	drawFollow(params)
	{
		const value = (Type.isBoolean(params.value) ? params.value : null);
		if (Type.isNull(value))
		{
			return;
		}

		const followBlock = (Type.isDomNode(params.followBlock) ? params.followBlock : null);
		if (followBlock)
		{
			followBlock.classList.remove(value ? this.class.postItemFollow : this.class.postItemFollowActive);
			followBlock.classList.add(value ? this.class.postItemFollowActive : this.class.postItemFollow);
			followBlock.setAttribute('data-follow', (value ? 'Y' : 'N'));
		}

		const followWrap = (Type.isDomNode(params.followWrap) ? params.followWrap : null);
		if (
			followWrap
			&& !this.getFollowDefaultValue()
		)
		{
			if (value)
			{
				followWrap.classList.add(this.class.postItemFollow);
			}
			else
			{
				followWrap.classList.remove(this.class.postItemFollow);
			}
		}

		const menuNode = (Type.isDomNode(params.menuNode) ? params.menuNode : null);
		if (menuNode)
		{
			menuNode.setAttribute('data-follow', (value ? 'Y' : 'N'));
		}

		const detailPageId = Instance.getOption('detailPageId');

		if (Type.isStringFilled(detailPageId))
		{
			this.setFollowValue(value);
			this.setFollowMenuItemName();
		}

		const runEvent = (Type.isBoolean(params.runEvent) ? params.runEvent : false);
		const logId = (!Type.isUndefined(params.logId) ? parseInt(params.logId) : 0);
		const turnOnOnly = (Type.isBoolean(params.turnOnOnly) ? params.turnOnOnly : false);

		if (
			runEvent
			&& logId > 0
		)
		{
			BXMobileApp.onCustomEvent('onLogEntryFollow', {
				logId: logId,
				pageId: (Type.isStringFilled(detailPageId) ? detailPageId : ''),
				bOnlyOn: (turnOnOnly ? 'Y' : 'N')
			}, true);
		}
	}

	setFollowDefault(params)
	{
		if (Type.isUndefined(params.value))
		{
			return;
		}

		const value = !!params.value;

		if (!Type.isStringFilled(Instance.getOption('detailPageId')))
		{
			this.setFollowDefaultValue(value);
			this.setDefaultFollowMenuItemName();
		}

		var postData = {
			sessid: BX.bitrix_sessid(),
			site: Loc.getMessage('SITE_ID'),
			lang: Loc.getMessage('LANGUAGE_ID'),
			value: (value ? 'Y' : 'N'),
			action: 'change_follow_default',
			mobile_action: 'change_follow_default',
		};

		oMSL.changeListMode(
			postData,
			() => {
				oMSL.pullDownAndRefresh();
			},
			(response) => {
				this.setFollowDefaultValue(response.value !== 'Y');
				this.setDefaultFollowMenuItemName();
			}
		);
	}

	setFollowValue(value)
	{
		this.value = !!value;
	}

	getFollowValue()
	{
		return this.value;
	}

	setFollowDefaultValue(value)
	{
		this.defaultValue = !!value;
	}

	getFollowDefaultValue()
	{
		return this.defaultValue;
	}

	setFollowMenuItemName()
	{
		const menuItemIndex = PageMenuInstance.detailPageMenuItems.findIndex(item => {
			return (
				Type.isStringFilled(item.feature)
				&& item.feature === 'follow'
			);
		});

		if (menuItemIndex < 0)
		{
			return;
		}

		const menuItem = PageMenuInstance.detailPageMenuItems[menuItemIndex];

		menuItem.name = (
			this.getFollowValue()
				? Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_FOLLOW_Y')
				: Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_FOLLOW_N')
		);
		PageMenuInstance.detailPageMenuItems[menuItemIndex] = menuItem;
		PageMenuInstance.init({
			type: 'detail',
		});
	}

	setDefaultFollowMenuItemName ()
	{
		const menuItemIndex = PageMenuInstance.listPageMenuItems.findIndex(item => {
			return (
				Type.isStringFilled(item.feature)
				&& item.feature === 'follow'
			);
		});

		if (menuItemIndex < 0)
		{
			return;
		}

		const menuItem = PageMenuInstance.listPageMenuItems[menuItemIndex];

		menuItem.name = (
			this.getFollowDefaultValue()
				? Loc.getMessage('MSLMenuItemFollowDefaultY')
				: Loc.getMessage('MSLMenuItemFollowDefaultN')
		);
		PageMenuInstance.listPageMenuItems[menuItemIndex] = menuItem;
		PageMenuInstance.init({
			type: 'list',
		});
	}
}

export {
	FollowManager
}
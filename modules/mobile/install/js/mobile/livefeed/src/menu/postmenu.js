import { Loc, Type } from 'main.core';
import { Post } from '../post';
import { BlogPost } from '../blogpost';
import { FollowManagerInstance, CommentsInstance } from '../feed';
import { sendData } from 'ui.analytics';

class PostMenu
{
	init(data)
	{
		this.iconUrlFolderPath = '/bitrix/templates/mobile_app/images/lenta/menu/';
		this.sectionCode = 'defaultSection';

		this.logId = parseInt(data.logId, 10);
		this.postId = parseInt(data.postId, 10);
		this.postPerms = (Type.isStringFilled(data.postPerms) ? data.postPerms : 'R');
		this.pageId = data.pageId;
		this.contentTypeId = (Type.isStringFilled(data.contentTypeId) ? data.contentTypeId : null);
		this.contentId = (Type.isInteger(data.contentId) ? data.contentId : 0);

		this.useShare = Boolean(data.useShare) && (this.postId > 0);
		this.useFavorites = Boolean(data.useFavorites) && (this.logId > 0);
		this.useFollow = Boolean(data.useFollow) && (this.logId > 0);
		this.usePinned = Boolean(data.usePinned) && (this.logId > 0);
		this.useTasks = (Loc.getMessage('MOBILE_EXT_LIVEFEED_USE_TASKS') === 'Y');
		this.useRefreshComments = Boolean(data.useRefreshComments);

		this.favoritesValue = Boolean(data.favoritesValue);
		this.followValue = Boolean(data.followValue);
		this.pinnedValue = Boolean(data.pinnedValue);

		this.target = (Type.isDomNode(data.target) ? data.target : null);
		this.context = (Type.isStringFilled(data.context) ? data.context : 'list');
	}

	getMenuItems()
	{
		const result = [];

		if (this.usePinned)
		{
			result.push({
				id: 'pinned',
				title: Loc.getMessage(`MOBILE_EXT_LIVEFEED_POST_MENU_PINNED_${this.pinnedValue ? 'Y' : 'N'}`),
				iconUrl: this.iconUrlFolderPath + (this.pinnedValue ? 'unpin.png' : 'pin.png'),
				sectionCode: this.sectionCode,
				action: () => {
					const postInstance = new Post({
						logId: this.logId,
					});

					return postInstance.setPinned({
						menuNode: this.target,
						context: this.context,
					});
				},
			});
		}

		if (this.useShare)
		{
			var selectedDestinations = {
				a_users: [],
				b_groups: [],
			};

			if (
				selectedDestinations.a_users.length > 0
				|| selectedDestinations.b_groups.length > 0
			)
			{
				result.push({
					id: 'sharePost',
					title: Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_SHARE'),
					iconName: 'add',
					iconUrl: `${this.iconUrlFolderPath}n_plus.png`,
					sectionCode: this.sectionCode,
					action: () => {
						app.openTable({
							callback: () => {
								oMSL.shareBlogPost();
							},
							url: `${Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR')}mobile/index.php?mobile_action=${Loc.getMessage('MOBILE_EXT_LIVEFEED_CURRENT_EXTRANET_SITE') === 'Y' ? 'get_group_list' : 'get_usergroup_list'}&feature=blog`,
							markmode: true,
							multiple: true,
							return_full_mode: true,
							user_all: true,
							showtitle: true,
							modal: true,
							selected: selectedDestinations,
							alphabet_index: true,
							okname: Loc.getMessage('MOBILE_EXT_LIVEFEED_SHARE_TABLE_BUTTON_OK'),
							cancelname: Loc.getMessage('MOBILE_EXT_LIVEFEED_SHARE_TABLE_BUTTON_CANCEL'),
							outsection: (Loc.getMessage('MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DENIED') !== 'Y'),
						});
					},
					arrowFlag: false,
				});
			}
		}

		if (
			this.postId > 0
			&& this.postPerms === 'W'
		)
		{
			result.push(
				{
					id: 'edit',
					title: Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_EDIT'),
					iconUrl: `${this.iconUrlFolderPath}pencil.png`,
					sectionCode: this.sectionCode,
					action: () => {
						BlogPost.edit({
							feedId: window.LiveFeedID,
							postId: this.postId,
							pinnedContext: Boolean(this.pinnedValue),
						});
					},
					arrowFlag: false,
					feature: 'edit',
				},
				{
					id: 'delete',
					title: Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_DELETE'),
					iconName: 'delete',
					sectionCode: this.sectionCode,
					action: () => {
						BlogPost.delete({
							postId: this.postId,
							context: this.context,
						});
					},
					arrowFlag: false,
				},
			);
		}

		if (this.useFavorites)
		{
			result.push({
				id: 'favorites',
				title: Loc.getMessage(`MOBILE_EXT_LIVEFEED_POST_MENU_FAVORITES_${this.favoritesValue ? 'Y' : 'N'}`),
				iconUrl: `${this.iconUrlFolderPath}favorite.png`,
				sectionCode: this.sectionCode,
				action: () => {
					const postInstance = new Post({
						logId: this.logId,
					});

					return postInstance.setFavorites({
						node: this.target,
					});
				},
				arrowFlag: false,
				feature: 'favorites',
			});
		}

		if (this.useFollow)
		{
			result.push({
				id: 'follow',
				title: Loc.getMessage(`MOBILE_EXT_LIVEFEED_POST_MENU_FOLLOW_${this.followValue ? 'Y' : 'N'}`),
				iconUrl: `${this.iconUrlFolderPath}eye.png`,
				sectionCode: this.sectionCode,
				action: () => {
					FollowManagerInstance.setFollow({
						logId: this.logId,
						menuNode: this.target,
						pageId: this.pageId,
						bOnlyOn: false,
						bAjax: true,
						bRunEvent: true,
					});
				},
				arrowFlag: false,
			});
		}

		if (this.useRefreshComments)
		{
			result.push({
				id: 'refreshPostComments',
				title: Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_REFRESH_COMMENTS'),
				iconUrl: `${this.iconUrlFolderPath}n_refresh.png`,
				action: () => {
					if (oMSL.bDetailEmptyPage)
					{
						// get comments on refresh from detail page menu
						CommentsInstance.getComments({
							ts: oMSL.iDetailTs,
							bPullDown: true,
							obFocus: {
								form: false,
							},
						});
					}
					else
					{
						document.location.reload(true);
					}
				},
				arrowFlag: false,
			});
		}

		if (
			Type.isStringFilled(this.contentTypeId)
			&& this.contentId > 0
		)
		{
			result.push({
				id: 'getPostLink',
				title: Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_GET_LINK'),
				iconUrl: `${this.iconUrlFolderPath}link.png`,
				sectionCode: this.sectionCode,
				action: () => {
					oMSL.copyPostLink({
						contentTypeId: this.contentTypeId,
						contentId: this.contentId,
					});
				},
				arrowFlag: false,
			});

			if (
				this.useTasks
				&& this.logId > 0
			)
			{
				result.push({
					id: 'createTask',
					title: Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_CREATE_TASK'),
					iconUrl: `${this.iconUrlFolderPath}n_check.png`,
					sectionCode: this.sectionCode,
					action: () => {
						oMSL.createTask({
							entityType: this.contentTypeId,
							entityId: this.contentId,
							logId: this.logId,
						});

						sendData({
							tool: 'tasks',
							category: 'task_operations',
							event: 'task_create',
							type: 'task',
							c_section: 'feed',
							c_element: 'create_button',
						});

						return false;
					},
					arrowFlag: false,
				});
			}
		}

		return result;
	}
}

export {
	PostMenu,
};

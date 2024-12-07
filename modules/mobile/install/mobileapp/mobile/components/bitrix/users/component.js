/**
 * * @bxjs_lang_path component.php
 * @let BaseList list
 */

(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	const { ProfileView } = require('user/profile');
	const { AnalyticsEvent } = require('analytics');
	const storageId = 'user.component.result';

	// eslint-disable-next-line init-declarations
	let openIntranetInviteWidget;
	try
	{
		openIntranetInviteWidget = require('intranet/invite-opener-new').openIntranetInviteWidget;
	}
	catch (e)
	{
		console.warn(e);
		openIntranetInviteWidget = require('intranet/invite-opener').openIntranetInviteWidget;
	}

	const componentResult = {
		/**
		 * @returns {{nameFormat:String}}
		 */
		get()
		{
			if (this.result)
			{
				return this.result;
			}

			return result;
		},
		update()
		{
			this.result = Application.storage.getObject(storageId);
			BX.ajax({ url: component.resultUrl, dataType: 'json' })
				.then((result) => {
					this.result = result;
					Application.storage.setObject(storageId, result);
				})
				.catch((e) => console.error(e));
		},
	};

	componentResult.update();

	if (BX.componentParameters.get('canInvite', false))
	{
		const action = () => {
			openIntranetInviteWidget({
				analytics: new AnalyticsEvent().setSection('userList'),
			});
		};

		const addUserButton = {
			type: 'plus',
			callback: action,
			icon: 'plus', // for floating button
			animation: 'hide_on_scroll', // for floating button
			color: AppTheme.colors.accentMainPrimaryalt,
		};

		BX.onViewLoaded(() => {
			if (Application.getPlatform() === 'ios')
			{
				list.setRightButtons([{ type: 'search', callback: () => list.showSearchBar() }]);
			}
			list.setFloatingButton(addUserButton);
		});
	}

	class ListDelegate
	{
		filterUserList(items)
		{
			return items;
		}

		onSearchResult(items, sections, list, state)
		{
			list.setSearchResultItems(items, sections);
		}

		onUserSelected(user)
		{
			ProfileView.open(
				{
					userId: user.params.id,
					imageUrl: encodeURI(user.imageUrl),
					title: BX.message('PROFILE_INFO_MSGVER_1'),
					workPosition: user.subtitle,
					name: user.title,
					url: user.params.profileUrl,
				},
			);
		}
	}

	this.userList = new UserList(list, new ListDelegate(), componentResult.get().nameFormat);
	this.userList.init();
})();

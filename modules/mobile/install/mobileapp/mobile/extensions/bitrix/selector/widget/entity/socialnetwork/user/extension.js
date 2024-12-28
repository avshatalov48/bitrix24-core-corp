/**
 * @module selector/widget/entity/socialnetwork/user
 */

jn.define('selector/widget/entity/socialnetwork/user', (require, exports, module) => {
	const { Loc } = require('loc');
	const { mergeImmutable } = require('utils/object');
	const { AvatarClass } = require('ui-system/blocks/avatar');
	const { SelectorDataProvider } = require('layout/ui/user/user-name');
	const { BaseSelectorEntity } = require('selector/widget/entity');

	/**
	 * @class SocialNetworkUserSelector
	 */
	class SocialNetworkUserSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'user';
		}

		static getContext()
		{
			return 'mobile-user';
		}

		static getStartTypingText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_USER');
		}

		static getTitle()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_PICK_USER_2');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static prepareItemForDrawing(item)
		{
			if (!item.id || !AvatarClass?.isNativeSupported())
			{
				return item;
			}

			const avatarParams = AvatarClass.resolveEntitySelectorParams({ ...item, withRedux: true });
			const avatar = AvatarClass.getAvatar(avatarParams).getAvatarNativeProps();
			const userNameStyle = SelectorDataProvider.getUserTitleStyle(item);

			return mergeImmutable(
				item,
				{
					id: `${item.entityId}/${item.id}`,
					avatar,
					styles: userNameStyle,
				},
			);
		}

		static getSearchPlaceholderWithCreation()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_INVITE_SEARCH_WITH_CREATION');
		}

		static getCreateText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_INVITE_USER_TAG');
		}

		static getCreatingText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_INVITING_USER_TAG');
		}

		static canCreateWithEmptySearch()
		{
			return true;
		}

		static getCreateEntityHandler(providerOptions, getParentLayoutFunction = null, analytics = {})
		{
			// to prevent cyclical dependency
			const { openIntranetInviteWidget } = require('intranet/invite-opener-new');

			return (text, allowMultipleSelection) => {
				return new Promise((resolve, reject) => {
					openIntranetInviteWidget({
						analytics,
						multipleInvite: allowMultipleSelection,
						parentLayout: getParentLayoutFunction ? getParentLayoutFunction() : null,
						onInviteSentHandler: (users) => {
							if (Array.isArray(users) && users.length > 0)
							{
								const preparedUsers = users.map((user) => {
									return {
										id: user.id,
										type: 'user',
										entityId: 'user',
										phone: user.personalMobile,
										firstName: user.name,
										lastName: user.lastName,
										title: user.fullName,
									};
								});
								resolve(preparedUsers);
							}
						},
						onInviteError: (errors) => {
							reject(errors);
						},
						onViewHiddenWithoutInvitingHandler: () => {
							reject();
						},
					});
				});
			};
		}
	}

	module.exports = { SocialNetworkUserSelector };
});

(() => {
	const require = (ext) => jn.require(ext);
	const { SocialNetworkUserSelector } = require('selector/widget/entity/socialnetwork/user');

	this.SocialNetworkUserSelector = SocialNetworkUserSelector;
})();

(() => {
	const require = (ext) => jn.require(ext);
	const { Loc } = require('loc');

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

	/**
	 * @module selector/widget/entity/socialnetwork/user
	 */
	jn.define('selector/widget/entity/socialnetwork/user', (require, exports, module) => {
		module.exports = { SocialNetworkUserSelector };
	});

	this.SocialNetworkUserSelector = SocialNetworkUserSelector;
})();

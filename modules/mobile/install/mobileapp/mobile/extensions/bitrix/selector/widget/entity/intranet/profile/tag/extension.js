/**
 * @module selector/widget/entity/intranet/profile/tag
 */
jn.define('selector/widget/entity/intranet/profile/tag', (require, exports, module) => {
	const { BaseSelectorEntity } = require('selector/widget/entity');
	const { ProfileTagSelectorProvider } = require('selector/widget/entity/intranet/profile/tag/provider');
	const { Loc } = require('loc');
	const { fetchUsersIfNotLoaded } = require('statemanager/redux/slices/users/thunk');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	class ProfileTagSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'profile-tag';
		}

		static getContext()
		{
			return null;
		}

		static getStartTypingText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_PROFILE_TAG');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static getCreateText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_CREATE_PROFILE_TAG');
		}

		static getCreatingText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_CREATING_PROFILE_TAG');
		}

		static getCreateEntityHandler(providerOptions, createOptions)
		{
			if (createOptions?.createImmediately)
			{
				return async (text) => {
					try
					{
						const response = await BX.ajax.runAction(
							'mobile.Profile.addTag',
							{
								json: {
									ownerId: env.userId,
									tag: text,
								},
							},
						);

						if (response.data && response.data.tags)
						{
							if (response.data.userIds)
							{
								await dispatch(fetchUsersIfNotLoaded({ userIds: response.data.userIds }));
							}

							const newTag = response.data.tags[0];

							return {
								id: newTag.name,
								entityId: this.getEntityId(),
								name: text,
							};
						}

						return null;
					}
					catch (error)
					{
						console.error(error);

						return null;
					}
				};
			}

			return async (text) => {
				return {
					id: text,
					entityId: this.getEntityId(),
					name: text,
				};
			};
		}

		static getTitle()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_PROFILE_TAG_TITLE');
		}

		static prepareProvider(options)
		{
			if (!options.ownerId)
			{
				throw new Error('ProfileTagSelector: ownerId is required');
			}

			return {
				class: ProfileTagSelectorProvider,
				options: {
					entities: {},
					...options,
				},
			};
		}
	}

	module.exports = { ProfileTagSelector };
});
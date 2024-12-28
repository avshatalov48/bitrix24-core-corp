/**
 * @module ui-system/blocks/avatar/src/providers/selector
 */
jn.define('ui-system/blocks/avatar/src/providers/selector', (require, exports, module) => {
	const { isNil } = require('utils/type');
	const { mergeImmutable } = require('utils/object');
	const store = require('statemanager/redux/store');
	const { UserSelectorEntityType } = require('layout/ui/user/enums');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { AvatarEntityType } = require('ui-system/blocks/avatar/src/enums/entity-type-enum');

	/**
	 * @typedef {Object} SelectorParams
	 * @property {string} id
	 * @property {string} [title]
	 * @property {string} [avatar]
	 * @property {boolean} [withRedux]
	 * @property {boolean} [isCollaber]
	 * @property {boolean} [isExtranet]
	 * @property {Object} [customData]
	 *
	 * @param {SelectorParams} selectorProps
	 */

	class SelectorDataProvider
	{
		constructor(data = {})
		{
			this.data = mergeImmutable(data, this.getReduxData(data));
		}

		getReduxData({ id, withRedux })
		{
			if (!withRedux)
			{
				return {};
			}

			return usersSelector.selectById(store.getState(), Number(id)) || {};
		}

		getId()
		{
			const { id } = this.data;

			return id;
		}

		getName()
		{
			const { fullName, title } = this.data;
			const { name, lastName } = this.getCustomData();

			return fullName ?? (title || [name, lastName].filter(Boolean).join(' '));
		}

		getEntityType()
		{
			const { entityType } = this.data;

			return entityType;
		}

		getSelectorEntityId()
		{
			const { entityId } = this.data;

			return entityId;
		}

		getCustomData()
		{
			const { customData } = this.data;

			return customData || {};
		}

		getTestId()
		{
			const testId = 'avatar-selector';

			return this.getId() ? `${testId}-${this.getId()}` : testId;
		}

		/**
		 * @returns {AvatarEntityType}
		 */
		getAvatarEntityType()
		{
			const avatarType = this.getAvatarType();

			if (this.isCollaber())
			{
				return AvatarEntityType.COLLAB;
			}

			if (this.isExtranet())
			{
				return AvatarEntityType.EXTRANET;
			}

			if (avatarType)
			{
				return AvatarEntityType.resolveType(avatarType);
			}

			return null;
		}

		getAvatarType()
		{
			return this.getEntityType();
		}

		getUri()
		{
			const { avatarSize100, avatar } = this.data;

			return avatarSize100 ?? avatar;
		}

		isExtranet()
		{
			const { isExtranet } = this.data;

			return Boolean(isExtranet) || this.getAvatarType() === this.getExtranetEntityName();
		}

		isCollaber()
		{
			const { isCollaber } = this.data;

			return Boolean(isCollaber) || this.getAvatarType() === this.getCollabEntityName();
		}

		getShape()
		{
			return null;
		}

		getCollabEntityName()
		{
			return UserSelectorEntityType.COLLABER.getValue();
		}

		getExtranetEntityName()
		{
			return UserSelectorEntityType.EXTRANET.getValue();
		}

		getParams()
		{
			const { id, entityId, size, onClick } = this.data;

			const params = {
				id,
				size,
				onClick,
				entityId,
				uri: this.getUri(),
				name: this.getName(),
				testId: this.getTestId(),
				shape: this.getShape(),
				entityType: this.getAvatarEntityType(),
			};

			const cleanNil = (values) => {
				return Object.fromEntries(
					Object.entries(values).filter(([_, value]) => !isNil(value)),
				);
			};

			return cleanNil(params);
		}
	}

	module.exports = {
		SelectorDataProviderClass: SelectorDataProvider,
		selectorDataProvider: (data) => (new SelectorDataProvider(data)).getParams(),
	};
});

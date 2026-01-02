/**
 * @module selector/providers/tree-providers/nested-department-provider/src/entities/user
 */
jn.define('selector/providers/tree-providers/nested-department-provider/src/entities/user', (require, exports, module) => {
	const { AvatarClass } = require('ui-system/blocks/avatar');
	const { SelectorDataProvider } = require('layout/ui/user/user-name');
	const { BaseEntity } = require('selector/providers/tree-providers/nested-department-provider/src/entities/base-entity');

	const ENTITY_TYPE = {
		employee: 'employee',
	};

	/**
	 * @class UserEntity
	 */
	class UserEntity extends BaseEntity
	{
		static getId()
		{
			return 'user';
		}

		getEntityOptions()
		{
			return {
				emailUsers: true,
				inviteEmployeeLink: false,
			};
		}

		prepareItemForDrawing(item, initialEntity)
		{
			const isEmployee = item.params?.entityType === ENTITY_TYPE.employee;

			if (!item.id)
			{
				return this.getDefaultItemsStyles(item);
			}

			const preparedParams = this.getPreparedParamsForAvatar(item);
			const avatarParams = AvatarClass.resolveEntitySelectorParams({ ...preparedParams, withRedux: true });
			const avatar = AvatarClass.getAvatar(avatarParams).getAvatarNativeProps();
			const userNameStyle = SelectorDataProvider.getUserTitleStyle(preparedParams);

			if (!isEmployee)
			{
				return this.getNonEmployeeStyles(item, avatar, userNameStyle);
			}

			return this.getDefaultItemsStyles(item);
		}

		getPreparedParamsForAvatar(item)
		{
			return {
				...item,
				customData: item.params?.customData,
				entityId: item.params?.entityId,
				entityType: item.params?.entityType,
				avatar: item.imageUrl,
			};
		}

		getDefaultItemsStyles(item)
		{
			return {
				...item,
				styles: {
					...item.styles,
					image: {
						border: {
							width: 0,
						},
					},
				},
			};
		}

		getNonEmployeeStyles(item, avatar, userNameStyle)
		{
			return {
				...item,
				avatar,
				styles: userNameStyle,
			};
		}
	}

	module.exports = { UserEntity };
});

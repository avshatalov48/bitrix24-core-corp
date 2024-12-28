/**
 * @module layout/ui/fields/user/theme/air/src/entity-list
 */
jn.define('layout/ui/fields/user/theme/air/src/entity-list', (require, exports, module) => {
	const { AvatarStack } = require('ui-system/blocks/avatar-stack');
	const { Entity, AVATAR_SIZE } = require('layout/ui/fields/user/theme/air/src/entity');

	const MAX_ELEMENTS = 5;

	/**
	 * @typedef {Object} AirUserEntityList
	 * @property {field} UserField
	 * @class AirUserEntityList
	 */
	class AirUserEntityList extends LayoutComponent
	{
		render()
		{
			const entityList = this.getEntityList();
			const field = this.getField();
			const entities = this.getEntityIds();

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
					},
				},
				entityList.length > 1
					? AvatarStack({
						testId: `${field.testId}_CONTENT`,
						withRedux: true,
						size: AVATAR_SIZE,
						entities,
						visibleEntityCount: MAX_ELEMENTS,
						onClick: this.handleOnAvatarClick,
					})
					: Entity({
						field,
						entity: entityList[0],
						showTitle: !field.isMultiple(),
					}),
			);
		}

		handleOnAvatarClick = ({ id }) => {
			const field = this.getField();

			if (field.canOpenUserList())
			{
				field.openUserList();
			}
			else
			{
				field.openEntity(id);
			}
		};

		/**
		 * @returns {UserField}
		 */
		getField()
		{
			const { field } = this.props;

			return field;
		}

		/**
		 * @returns {Array<number | string>}
		 */
		getEntityIds()
		{
			return this.getEntityList().map(({ id }) => id);
		}

		/**
		 * @returns {Array<UserEntity>}
		 */
		getEntityList()
		{
			return this.getField().getEntityList();
		}
	}

	module.exports = {
		/**
		 * @param {AirUserEntityList} props
		 */
		EntityList: (props) => new AirUserEntityList(props),
	};
});

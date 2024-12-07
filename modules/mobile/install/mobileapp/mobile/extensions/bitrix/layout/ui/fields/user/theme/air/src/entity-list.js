/**
 * @module layout/ui/fields/user/theme/air/src/entity-list
 */
jn.define('layout/ui/fields/user/theme/air/src/entity-list', (require, exports, module) => {
	const { ElementsStack } = require('elements-stack');
	const { UserAvatar } = require('layout/ui/fields/user/theme/elements/user-icon');
	const { Indent } = require('tokens');
	const { Entity, AVATAR_SIZE } = require('layout/ui/fields/user/theme/air/src/entity');
	const { Counter } = require('layout/ui/fields/user/theme/air/src/counter');

	const MAX_ELEMENTS = 5;

	/**
	 * @param {UserField} field
	 * @return {any}
	 * @constructor
	 */
	const EntityList = ({ field }) => {
		const entityList = field.getEntityList();
		const children = entityList.slice(0, MAX_ELEMENTS).map((entity) => UserAvatar({
			field,
			entity,
			size: AVATAR_SIZE + Indent.XS2.toNumber() * 2,
		}));

		const showCounter = entityList.length > MAX_ELEMENTS;
		if (showCounter)
		{
			children.push(
				Counter({
					count: entityList.length - MAX_ELEMENTS,
					size: AVATAR_SIZE + Indent.XS2.toNumber() * 2,
					onClick: () => field.openUserList(),
				}),
			);
		}

		return View(
			{
				style: {
					flex: 1,
					flexDirection: 'row',
				},
			},
			entityList.length > 1
				? ElementsStack(
					{
						indent: Indent.XS2,
						offset: Indent.S,
						showRest: false,
						maxElements: showCounter ? MAX_ELEMENTS + 1 : MAX_ELEMENTS,
						testId: `${field.testId}_CONTENT`,
					},
					...children,
				)
				: Entity({
					field,
					entity: entityList[0],
					showTitle: !field.isMultiple(),
				}),
		);
	};

	module.exports = {
		EntityList,
	};
});

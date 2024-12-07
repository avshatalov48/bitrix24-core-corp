/**
 * @module layout/ui/fields/user/theme/air-compact/src/entity
 */
jn.define('layout/ui/fields/user/theme/air-compact/src/entity', (require, exports, module) => {
	const { UserAvatar } = require('layout/ui/fields/user/theme/elements/user-icon');
	const { Text4 } = require('ui-system/typography/text');
	const { Color, Indent } = require('tokens');

	/**
	 * @param {object} field
	 * @param {object} entity
	 * @param {number} avatarSize
	 * @param {boolean} canOpenEntity
	 * @param {boolean} showTitle
	 * @param {ColorEnum} [color=Color.accentMainPrimary]
	 * @param {object} [avatarStyle]
	 * @param {object} [style]
	 */
	const Entity = ({
		field,
		entity,
		avatarSize,
		canOpenEntity,
		avatarStyle = {},
	}) => {
		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
				},
			},
			View(
				{
					testId: `${field.testId}_COMPACT_USER_${entity.id}_CONTENT`,
				},
				UserAvatar({
					field,
					entity,
					name: entity.title,
					size: avatarSize,
					canOpenEntity,
					additionalStyles: avatarStyle,
				}),
			),
		);
	};

	module.exports = {
		Entity,
	};
});

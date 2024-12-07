/**
 * @module layout/ui/fields/user/theme/air/src/entity
 */
jn.define('layout/ui/fields/user/theme/air/src/entity', (require, exports, module) => {
	const { UserAvatar } = require('layout/ui/fields/user/theme/elements/user-icon');
	const { Color, Indent } = require('tokens');
	const { Text4, Text6 } = require('ui-system/typography/text');

	const AVATAR_SIZE = 32;

	/**
	 * @param {UserField} field
	 * @param {object} entity
	 * @param {string} entity.title
	 * @param {number} entity.id
	 * @param {boolean} showTitle
	 */
	const Entity = ({
		field,
		entity,
		showTitle = true,
	}) => View(
		{
			style: {
				flexDirection: 'row',
				alignItems: 'center',
				flexShrink: 2,
			},
		},
		View(
			{
				testId: `${field.testId}_CONTENT`,
			},
			UserAvatar({
				field,
				entity,
				size: AVATAR_SIZE,
			}),
		),
		View(
			{
				style: {
					flexDirection: 'column',
					marginLeft: Number(Indent.M),
					flexShrink: 2,
				},
			},
			showTitle && Text6({
				testId: `${field.testId}_TITLE`,
				style: {
					color: Color.base4.toHex(),
					marginBottom: Number(Indent.XS2),
					flexShrink: 2,
				},
				text: field.getTitleText(),
				numberOfLines: 1,
				ellipsize: 'end',
			}),
			View(
				{
					testId: `${field.testId}_CONTENT`,
				},
				Text4({
					testId: `${field.testId}_USER_${entity.id}_VALUE`,
					style: {
						flexShrink: 2,
						color: Color.base2.toHex(),
					},
					text: entity.title,
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			),
		),
	);

	module.exports = {
		Entity,
		AVATAR_SIZE,
	};
});

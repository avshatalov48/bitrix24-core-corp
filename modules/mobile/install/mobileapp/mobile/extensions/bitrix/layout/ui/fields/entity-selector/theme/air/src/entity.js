/**
 * @module layout/ui/fields/entity-selector/theme/air/src/entity
 */
jn.define('layout/ui/fields/entity-selector/theme/air/src/entity', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { EmptyAvatar } = require('layout/ui/user/empty-avatar');
	const IMAGE_SIZE = 32;

	/**
	 * @param {EntitySelectorField} field
	 * @param {number} id
	 * @param {string} name
	 * @param {string} imageUrl
	 * @param {string} avatar
	 * @param {number} indent
	 */
	const Entity = ({
		field,
		id,
		title,
		imageUrl,
		avatar,
		indent = 0,
	}) => {
		const onEntityClick = field.openEntity.bind(field, id);
		const testId = `${field.testId}_ENTITY_${id}`;

		return View(
			{
				style: {
					paddingVertical: Indent.L,
					marginBottom: indent,
					flexDirection: 'row',
					alignItems: 'center',
				},
				testId: `${field.testId}_ENTITY_${id}`,
			},
			(imageUrl || field.isEmpty()) ? Image({
				style: {
					width: IMAGE_SIZE,
					height: IMAGE_SIZE,
					borderRadius: 16,
				},
				testId: `${testId}_ICON`,
				uri: field.getImageUrl(imageUrl || avatar || field.getDefaultAvatar()),
				onClick: !field.isEmpty() && onEntityClick,
			}) : EmptyAvatar({
				id,
				name: title,
				size: IMAGE_SIZE,
				testId: `${testId}_LETTERS_ICON`,
				onClick: !field.isEmpty() && onEntityClick,
			}),
			View(
				{
					onClick: !field.isEmpty() && onEntityClick,
				},
				Text({
					text: title,
					style: {
						color: Color.base2,
						fontSize: 14,
						marginLeft: Indent.M,
						flexShrink: 2,
					},
					numberOfLines: 1,
					ellipsize: 'end',
					testId: `${testId}_TITLE`,
				}),
			),
		);
	};

	module.exports = {
		Entity,
	};
});

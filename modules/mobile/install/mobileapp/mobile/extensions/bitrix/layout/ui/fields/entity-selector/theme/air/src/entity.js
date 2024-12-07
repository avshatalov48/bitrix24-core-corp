/**
 * @module layout/ui/fields/entity-selector/theme/air/src/entity
 */
jn.define('layout/ui/fields/entity-selector/theme/air/src/entity', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { ShimmedSafeImage } = require('layout/ui/safe-image');
	const { EmptyAvatar } = require('layout/ui/user/empty-avatar');
	const IMAGE_SIZE = 32;

	/**
	 * @param {EntitySelectorField} field
	 * @param {number} id
	 * @param {string} name
	 * @param {string} imageUrl
	 * @param {string} avatar
	 */
	const Entity = ({
		field,
		id,
		title,
		imageUrl,
		avatar,
	}) => {
		const onEntityClick = field.openEntity.bind(field, id);
		const testId = `${field.testId}_ENTITY_${id}`;

		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
				},
				testId: `${field.testId}_ENTITY_${id}`,
			},
			(imageUrl || field.isEmpty()) ? ShimmedSafeImage({
				style: {
					width: IMAGE_SIZE,
					height: IMAGE_SIZE,
					borderRadius: IMAGE_SIZE / 2,
				},
				resizeMode: 'cover',
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
					style: {
						flexShrink: 2,
					},
				},
				Text4({
					text: title,
					style: {
						color: Color.base2.toHex(),
						marginLeft: Number(Indent.M),
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

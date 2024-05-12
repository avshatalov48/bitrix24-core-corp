/**
 * @module layout/ui/fields/tag/theme/air
 */
jn.define('layout/ui/fields/tag/theme/air', (require, exports, module) => {
	const { TagFieldClass } = require('layout/ui/fields/tag');
	const { withTheme } = require('layout/ui/fields/theme');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { Color, Indent, Corner } = require('tokens');
	const { IconView } = require('ui-system/blocks/icon');
	const { AddButton } = require('layout/ui/fields/theme/air/elements/add-button');

	const IMAGE_SIZE = 32;
	const ICON_SIZE = 20;

	/**
	 * @param  {TagField} field - instance of the TagFieldClass.
	 * @return {function} - functional component
	 */
	const AirTheme = ({ field }) => FieldWrapper(
		{ field },
		View(
			{
				style: {
					flexDirection: 'column',
				},
				onLongClick: field.getContentLongClickHandler(),
				onClick: () => field.focus(),
			},
			View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'flex-start',
						marginTop: Indent.XS2,
						marginBottom: Indent.XS2,
					},
				},
				field.isEmpty() && field.getLeftIcon().icon && IconView({
					icon: field.getLeftIcon().icon,
					size: {
						width: IMAGE_SIZE,
						height: IMAGE_SIZE,
					},
					iconColor: Color.accentMainPrimaryalt,
				}),
				field.isEmpty() && View(
					{
						onClick: field.openSelector,
						style: {
							paddingVertical: Indent.S,
						},
					},
					Text({
						text: field.getEmptyText(),
						style: {
							color: Color.base3,
							fontSize: 14,
							marginLeft: Indent.M,
							flexShrink: 2,
						},
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
				!field.isEmpty() && View(
					{
						style: {
							flexDirection: 'row',
							flexWrap: 'wrap',
							flexShrink: 2,
						},
					},
					...field.getEntityList().map((entity) => View(
						{
							style: {
								marginLeft: Indent.M,
								marginVertical: Indent.XS,

								paddingHorizontal: Indent.XS,
								borderRadius: Corner.XL,
								backgroundColor: Color.bgContentTertiary,
								flexDirection: 'row',
								flexShrink: 2,
							},
						},
						field.getLeftIcon().icon && IconView({
							icon: field.getLeftIcon().icon,
							size: {
								width: ICON_SIZE,
								height: ICON_SIZE,
							},
							iconColor: Color.base4,
						}),
						Text({
							text: entity.title,
							style: {
								color: Color.base2,
								fontSize: 12,
								marginLeft: Indent.XS,
								flexShrink: 2,
							},
							numberOfLines: 1,
							ellipsize: 'end',
						}),
					)),
				),
			),
			field.getAddButtonText()
			&& !field.isReadOnly()
			&& field.isMultiple()
			&& !field.isEmpty()
			&& AddButton({
				onClick: field.openSelector,
				text: field.getAddButtonText(),
			}),
		),
	);

	/** @type {function(object): object} */
	const TagField = withTheme(TagFieldClass, AirTheme);

	module.exports = {
		TagField,
	};
});

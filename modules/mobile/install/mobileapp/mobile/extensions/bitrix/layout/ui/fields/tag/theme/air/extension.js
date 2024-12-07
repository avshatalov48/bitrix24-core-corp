/**
 * @module layout/ui/fields/tag/theme/air
 */
jn.define('layout/ui/fields/tag/theme/air', (require, exports, module) => {
	const { TagFieldClass } = require('layout/ui/fields/tag');
	const { withTheme } = require('layout/ui/fields/theme');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { Color, Indent, Corner } = require('tokens');
	const { Text4, Text5 } = require('ui-system/typography/text');
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
				testId: `${field.testId}_CONTENT`,
				style: {
					flexDirection: 'column',
				},
				onLongClick: field.getContentLongClickHandler(),
				onClick: field.getContentClickHandler(),
			},
			View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'flex-start',
					},
				},
				field.isEmpty() && field.getDefaultLeftIcon() && IconView({
					testId: `${field.testId}_ICON`,
					icon: field.getDefaultLeftIcon(),
					size: {
						width: IMAGE_SIZE,
						height: IMAGE_SIZE,
					},
					color: Color.accentMainPrimaryalt,
				}),
				field.isEmpty() && View(
					{
						testId: `${field.testId}_EMPTY_VIEW`,
						onClick: field.getContentClickHandler(),
						style: {
							paddingVertical: Number(Indent.S),
						},
					},
					Text4({
						testId: `${field.testId}_EMPTY_VIEW_TEXT`,
						text: field.getEmptyText(),
						style: {
							color: Color.base3.toHex(),
							marginLeft: Number(Indent.M),
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
							testId: `${field.testId}_TAG_${entity.id}`,
							style: {
								marginRight: Number(Indent.M),
								marginBottom: Number(Indent.M),
								paddingLeft: Number(Indent.XS),
								paddingRight: Number(Indent.M),
								borderRadius: Number(Corner.XL),
								backgroundColor: Color.bgContentTertiary.toHex(),
								flexDirection: 'row',
								flexShrink: 2,
								alignItems: 'center',
							},
						},
						field.getDefaultLeftIcon() && IconView({
							testId: `${field.testId}_${entity.id}_ICON`,
							icon: field.getDefaultLeftIcon(),
							size: {
								width: ICON_SIZE,
								height: ICON_SIZE,
							},
							iconColor: Color.base4,
						}),
						Text5({
							testId: `${field.testId}_${entity.id}_VALUE`,
							text: entity.title,
							style: {
								color: Color.base2.toHex(),
								flexShrink: 2,
							},
							numberOfLines: 1,
							ellipsize: 'end',
						}),
					)),
				),
			),
			field.shouldShowAddButton()
			&& field.getAddButtonText()
			&& !field.isReadOnly()
			&& !field.isRestricted()
			&& !field.isEmpty()
			&& field.isMultiple()
			&& AddButton({
				testId: field.testId,
				onClick: field.getContentClickHandler(),
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

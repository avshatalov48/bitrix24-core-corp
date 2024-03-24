/**
 * @module layout/ui/fields/datetime/theme/default
 */
jn.define('layout/ui/fields/datetime/theme/default', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { IconView } = require('ui-system/blocks/icon');
	// eslint-disable-next-line no-unused-vars
	const { DateTimeField } = require('layout/ui/fields/datetime');

	/**
	 * @function DateTimeRenderFunction
	 * @param  {DateTimeField} field
	 * @return {object}
	 */
	const DateTimeRenderFunction = (field) => {
		return View(
			{
				testId: field.testId,
				style: {
					paddingHorizontal: 18,
					paddingVertical: 10,
					flexDirection: 'row',
					alignItems: 'center',
				},
				onLongClick: field.getContentLongClickHandler(),
				onClick: () => field.focus(),
			},
			View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						width: 32,
						height: 32,
						marginRight: 8,
					},
				},
				IconView({
					icon: 'calendar2',
					iconSize: {
						width: 32,
						height: 32,
					},
					iconColor: AppTheme.colors.accentMainPrimaryalt,
				}),
			),
			Text({
				text: field.isEmpty() ? field.getReadOnlyEmptyValue() : field.getFormattedDate(),
				style: {
					color: AppTheme.colors.base2,
					fontSize: 14,
					marginRight: 4,
				},
				numberOfLines: 1,
				ellipsize: 'end',
			}),
			!field.isReadOnly() && IconView({
				icon: 'chevronDown',
				iconSize: {
					width: 12,
					height: 12,
				},
				iconColor: AppTheme.colors.base2,
			}),
		);
	};

	module.exports = {
		DateTimeRenderFunction,
	};
});

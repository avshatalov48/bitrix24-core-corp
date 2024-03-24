/**
 * @module ui-system/form/buttons/simple-button
 */
jn.define('ui-system/form/buttons/simple-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PlainView } = require('ui-system/blocks/plain-view');

	const SIZES = {
		XS: 22,
		S: 28,
		M: 36,
	};
	const PADDING_HORIZONTAL = {
		XS: 22,
		S: 28,
		M: 36,
	};
	const PADDING_VERTICAL = {
		XS: 1,
		S: 2,
		M: 4,
	};

	const ICON_SIZE = {
		XS: 20,
		S: 24,
		M: 28,
	};

	/**
	 * @function SimpleButton
	 */
	const SimpleButton = (props) => {
		const {
			text = '',
			size = '',
			icon = null,
			color = null,
			stretch = false,
			iconColor = null,
			border = false,
			borderColor = AppTheme.colors.base5,
			backgroundColor = null,
			onClick = null,
		} = props;

		const borderStyle = border ? {
			borderColor,
			borderWidth: 2,
			borderRadius: 6,
		} : {};

		const singeSize = size ? size.toUpperCase() : 'M';

		const height = SIZES[singeSize];
		const paddingHorizontal = PADDING_HORIZONTAL[singeSize];
		const paddingVertical = PADDING_VERTICAL[singeSize];
		const iconSize = ICON_SIZE[singeSize];

		return View(
			{
				style: {
					flexDirection: 'row',
				},
				onClick,
			},
			View(
				{
					style: {
						width: stretch ? '100%' : null,
						alignItems: stretch ? 'center' : 'auto',
						height,
						paddingHorizontal,
						paddingVertical,
						backgroundColor,
						...borderStyle,
					},
				},
				PlainView({
					text,
					color,
					icon,
					iconColor,
					size: {
						width: iconSize,
						height: iconSize,
					},
				}),
			),
		);
	};

	module.exports = { SimpleButton };
});

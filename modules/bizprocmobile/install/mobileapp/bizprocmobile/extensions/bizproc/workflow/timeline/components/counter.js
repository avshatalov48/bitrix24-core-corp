/**
 * @module bizproc/workflow/timeline/components/counter
 * */

jn.define('bizproc/workflow/timeline/components/counter', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { SafeImage } = require('layout/ui/safe-image');
	const { Type } = require('type');

	/**
	 *
	 * @param {?{width: indent, color: string, style: string}} border
	 * @param {?string} value
	 * @param {?string} iconContent
	 * @param {?string} backgroundColor
	 * @param {?string} color
	 * @param {?string} trunkColor
	 * @param {?boolean} hasTail
	 * @param {?string} tailColor
	 * @param {?number} size
	 * @return {View}
	 */
	function Counter({
		border = null,
		value = null,
		iconContent = null,
		backgroundColor = AppTheme.colors.accentMainSuccess,
		color = AppTheme.colors.baseWhiteFixed,
		trunkColor = null,
		hasTail = true,
		tailColor = AppTheme.colors.base4,
		size = 17,
	} = {})
	{
		const showText = Type.isInteger(value);
		const showIcon = !Type.isInteger(value) && Type.isString(iconContent);

		let counterBorderStyle = null;
		let counterBorderColor = null;
		let counterBorderWidth = null;
		if (Type.isObjectLike(border))
		{
			counterBorderStyle = border.style;
			counterBorderColor = border.color;
			counterBorderWidth = border.width;
		}

		return View(
			{
				style: {
					justifyContent: 'flex-start',
					alignItems: 'center',
					paddingRight: 12,
					paddingLeft: 12,
					marginBottom: -20, // 12 + 8 - height from content to bottom borderline + distance between elements
				},
			},
			// line over counter
			View(
				{
					style: {
						alignItems: 'center',
						maxHeight: 12 + (18 - size) / 2,
						flex: 1,
					},
				},
				View({
					style: {
						flex: 1,
						width: 1,
						backgroundColor: trunkColor,
					},
				}),
			),
			// counter
			View(
				{
					style: {
						justifyContent: 'center',
						height: size,
						width: 18,
					},
				},
				// circle
				View(
					{
						style: {
							alignSelf: 'center',
							alignItems: 'center',
							justifyContent: 'center',
							width: size,
							height: size,
							backgroundColor,
							borderRadius: 100,
							borderStyle: counterBorderStyle,
							borderColor: counterBorderColor,
							borderWidth: counterBorderWidth,
						},
					},
					// number
					showText && Text({
						text: String(value),
						style: {
							position: 'relative',
							color,
							textAlign: 'center',
							fontSize: 12,
						},
					}),
					// icon
					showIcon && SafeImage({
						style: {
							width: 10,
							height: 7.06,
							position: 'relative',
							selfAlign: 'center',
						},
						resizeMode: 'contain',
						placeholder: {
							content: iconContent,
						},
					}),
				),
			),
			// line under counter
			hasTail && View(
				{
					style: {
						flex: 1,
						alignItems: 'center',
					},
				},
				View({
					style: {
						width: 1,
						flex: 1,
						backgroundColor: tailColor,
					},
				}),
			),
		);
	}

	module.exports = {
		Counter,
	};
});

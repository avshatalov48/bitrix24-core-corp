/**
 * @module imconnector/lib/ui/buttons/complete
 */
jn.define('imconnector/lib/ui/buttons/complete', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { withPressed } = require('utils/color');

	/**
	 * @param {CompleteButtonProps} props
	 * @return {*}
	 * @constructor
	 */
	function CompleteButton(props)
	{
		const borderRadius = BX.prop.getNumber(props.style, 'borderRadius', 6);
		const color = BX.prop.getString(props.style, 'color', AppTheme.colors.baseWhiteFixed);
		const width = BX.prop.getNumber(props.style, 'width', null);
		const height = BX.prop.getNumber(props.style, 'height', null);

		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					justifyContent: 'center',
					backgroundColor: withPressed(AppTheme.colors.accentMainSuccess),
					borderRadius,
					width,
					height,
					paddingVertical: 4,
					paddingHorizontal: 25,
				},
				clickable: true,
				onClick: () => {
					if (Type.isFunction(props.onClick))
					{
						props.onClick();
					}
				},
			},
			props.withoutIcon === true
				? null
				: Image({
					style: {
						width: 22,
						height: 22,
						marginRight: 5,
					},
					svg: {
						content: icon,
					},
					resizeMode: 'cover',
				}),
			Text({
				style: {
					color,
					fontSize: 16,
					fontWeight: 400,
					numberOfLines: 1,
				},
				text: props.text,
			}),
		);
	}

	const icon = `<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M3.3259 12.4766L8.64758 17.663C8.76409 17.7766 8.94985 17.7766 9.06635 17.663L19.1687 7.81748C19.3298 7.66048 19.3298 7.40156 19.1687 7.24456L17.703 5.81618C17.5477 5.66479 17.3 5.66479 17.1447 5.81618L8.85697 13.8932L5.3499 10.4753C5.19456 10.3239 4.94687 10.3239 4.79154 10.4753L3.3259 11.9037C3.1648 12.0607 3.1648 12.3196 3.3259 12.4766Z" fill="white"/>
</svg>
`;

	module.exports = { CompleteButton };
});

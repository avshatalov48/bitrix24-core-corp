/**
 * @module imconnector/lib/ui/buttons/link
 */
jn.define('imconnector/lib/ui/buttons/link', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { withPressed } = require('utils/color');
	const { inAppUrl } = require('in-app-url');

	/**
	 * @param {LinkButtonProps} props
	 * @return {*}
	 * @constructor
	 */
	function LinkButton(props)
	{
		const borderRadius = BX.prop.getNumber(props.style, 'borderRadius', 6);
		const width = BX.prop.getNumber(props.style, 'width', null);
		const height = BX.prop.getNumber(props.style, 'height', null);

		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					justifyContent: 'center',
					backgroundColor: withPressed(AppTheme.colors.accentMainPrimary),
					borderRadius,
					paddingVertical: 4,
					paddingHorizontal: 25,
					width,
					height,
				},
				clickable: true,
				onClick: () => {
					inAppUrl.open(props.link);

					if (Type.isFunction(props.onClick))
					{
						props.onClick();
					}
				},
			},
			Text({
				style: {
					color: AppTheme.colors.baseWhiteFixed,
					fontSize: 16,
					fontWeight: 400,
					numberOfLines: 1,
				},
				text: props.text,
			}),
			Image({
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
		);
	}

	const icon = `<svg width="23" height="22" viewBox="0 0 23 22" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M9.85917 4.83057V7.09458L8.66423 7.09527C8.11578 7.09527 7.66376 7.50812 7.60198 8.04L7.59478 8.16472V14.3297C7.59478 14.8782 8.00763 15.3302 8.53951 15.392L8.66423 15.3992H14.8293C15.3777 15.3992 15.8297 14.9863 15.8915 14.4545L15.8987 14.3297L15.8983 13.1337H18.1634V15.525C18.1634 16.7063 17.2058 17.6639 16.0245 17.6639H7.46897C6.28769 17.6639 5.33008 16.7063 5.33008 15.525V6.96946C5.33008 5.78818 6.28769 4.83057 7.46897 4.83057H9.85917ZM18.1634 10.9608V5.19723C18.1634 4.99473 17.9993 4.83057 17.7968 4.83057H12.0334C11.7884 4.83057 11.6657 5.12683 11.839 5.30005L13.9925 7.45284L11.0299 10.4163C10.9226 10.5237 10.9226 10.6978 11.03 10.8052L12.2424 12.0177C12.3498 12.1251 12.524 12.125 12.6314 12.0176L15.5935 9.05487L17.694 11.1553C17.8672 11.3285 18.1634 11.2058 18.1634 10.9608Z" fill="white"/>
</svg>
`;

	module.exports = { LinkButton };
});

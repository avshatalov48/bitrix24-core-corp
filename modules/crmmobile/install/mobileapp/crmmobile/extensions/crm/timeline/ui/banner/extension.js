/**
 * @module crm/timeline/ui/banner
 */
jn.define('crm/timeline/ui/banner', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { mergeImmutable } = require('utils/object');

	function Banner({ title, description, onClick, style = {} })
	{
		style.backgroundColor = BX.prop.getString(style, 'backgroundColor', AppTheme.colors.bgContentPrimary);
		style.marginBottom = BX.prop.getNumber(style, 'marginBottom', 0);
		style.opacity = BX.prop.getNumber(style, 'opacity', 1);
		style.innerOpacity = BX.prop.getNumber(style, 'innerOpacity', 1);

		const none = () => {};

		return View(
			{
				style: {
					padding: 16,
					borderRadius: 12,
					flexDirection: 'row',
					justifyContent: 'space-between',
					backgroundColor: style.backgroundColor,
					marginBottom: style.marginBottom,
					opacity: style.opacity,
				},
				onClick: onClick || none,
			},
			View(
				{
					style: {
						opacity: style.innerOpacity,
					},
				},
				Text({
					text: String(title),
					style: {
						fontSize: 15,
						fontWeight: '400',
						color: AppTheme.colors.base0,
						opacity: 0.94,
						marginBottom: description ? 4 : 0,
					},
				}),
				description && Text({
					text: String(description),
					style: {
						fontSize: 13,
						fontWeight: '400',
						color: AppTheme.colors.base3,
					},
				}),
			),
		);
	}

	function BannerStack(props)
	{
		const style = props.style || {};
		const marginBottom = BX.prop.getNumber(style, 'marginBottom', 0);
		const backgroundColor = BX.prop.getString(style, 'backgroundColor', AppTheme.colors.bgContentPrimary);

		return View(
			{
				style: {
					paddingBottom: 2,
					marginBottom,
				},
			},
			View(
				{
					style: {
						paddingLeft: 10,
						paddingRight: 10,
						position: 'absolute',
						bottom: 0,
						width: '100%',
					},
				},
				View(
					{
						style: {
							backgroundColor,
							borderWidth: 1,
							borderColor: AppTheme.colors.base6,
							borderRadius: 12,
							opacity: 0.8,
							padding: 16,
						},
					},
				),
			),
			Banner(mergeImmutable(props, {
				style: {
					opacity: 1,
					marginBottom: 0,
				},
			})),
		);
	}

	module.exports = { Banner, BannerStack };
});

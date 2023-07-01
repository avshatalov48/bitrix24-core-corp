/**
 * @module crm/timeline/ui/banner
 */
jn.define('crm/timeline/ui/banner', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');

	function Banner({ title, description, onClick, style = {} })
	{
		style.backgroundColor = BX.prop.getString(style, 'backgroundColor', '#ffffff');
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
						color: '#000000',
						opacity: 0.94,
						marginBottom: description ? 4 : 0,
					},
				}),
				description && Text({
					text: String(description),
					style: {
						fontSize: 13,
						fontWeight: '400',
						color: '#828B95',
					},
				}),
			),
		);
	}

	function BannerStack(props)
	{
		const style = props.style || {};
		const marginBottom = BX.prop.getNumber(style, 'marginBottom', 0);
		const backgroundColor = BX.prop.getString(style, 'backgroundColor', '#ffffff');

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
							borderColor: '#dfe0e3',
							borderRadius: 12,
							opacity: 0.8,
							padding: 16,
						},
					},
				),
			),
			Shadow(
				{
					color: '#e6e7e9',
					radius: 3,
					offset: {
						y: 3,
					},
					inset: {
						left: 3,
						right: 3,
					},
					style: {
						borderRadius: 12,
					},
				},
				Banner(mergeImmutable(props, {
					style: {
						opacity: 1,
						marginBottom: 0,
					},
				})),
			),
		);
	}

	module.exports = { Banner, BannerStack };
});

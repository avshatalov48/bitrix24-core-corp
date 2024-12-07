/**
 * @module im/messenger/lib/ui/base/carousel/carousel-item
 */
jn.define('im/messenger/lib/ui/base/carousel/carousel-item', (require, exports, module) => {
	const { Avatar } = require('im/messenger/lib/ui/base/avatar');
	const { Theme } = require('im/lib/theme');

	const crossColor = Theme.colors.baseWhiteFixed;
	const crossFillColor = Theme.colors.base5;
	const crossStrokeColor = Theme.colors.base8;
	const cross = `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="7.5" fill="${crossFillColor}" stroke="${crossStrokeColor}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9 8L11 10L10 11L8 9L6 11L5 10L7 8L5 6L6 5L8 7L10 5L11 6L9 8Z" fill="${crossColor}"/></svg>`;
	class CarouselItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}

		getId()
		{
			return this.props.id;
		}

		render()
		{
			return View(
				{
					clickable: true,
					style: {
						height: 88,
						width: 78,
						justifyContent: 'center',
						alignContent: 'center',
						alignItems: 'center',
					},
					onClick: () => {
						this.props.onClick(this.props.data);
					},
					// style: this.props.size === 'L' ? itemStyle.large : itemStyle.medium,
				},

				new Avatar({
					uri: this.props.data.avatarUri,
					text: this.props.data.title,
					size: this.props.size,
					color: this.props.data.avatarColor,
					isSuperEllipse: this.props.isSuperEllipseAvatar,
				}),
				Image(
					{
						style: {
							position: 'absolute',
							height: 16,
							width: 16,
							right: 7,
							top: 7,
						},
						svg: {
							content: cross,
						},
					},
				),
				View(
					{
						style: {
							paddingLeft: 16,
							paddingRight: 16,
						},
					},
					Text({
						style: {
							fontSize: 13,
							color: Theme.colors.base1,
						},
						text: this.props.data.title.split(' ')[0],
						ellipsize: 'end',
						numberOfLines: 1,
					}),
				),
			);
		}
	}

	module.exports = { CarouselItem };
});

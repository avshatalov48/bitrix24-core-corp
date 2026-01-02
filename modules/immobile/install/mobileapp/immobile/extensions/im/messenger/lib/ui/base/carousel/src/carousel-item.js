/**
 * @module im/messenger/lib/ui/base/carousel/carousel-item
 */
jn.define('im/messenger/lib/ui/base/carousel/carousel-item', (require, exports, module) => {
	const { Icon } = require('assets/icons');
	const { Theme } = require('im/lib/theme');
	const { Avatar } = require('im/messenger/lib/ui/base/avatar');

	class CarouselItem extends LayoutComponent
	{
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
				View(
					{
						style: {
							position: 'absolute',
							right: 7,
							top: 7,
							height: 20,
							width: 20,
							alignItems: 'center',
							justifyContent: 'center',
							backgroundColor: Theme.colors.base5,
							borderRadius: 10,
							borderWidth: 1,
							borderColor: Theme.colors.base8,
						},
					},
					Image(
						{
							style: {
								height: 16,
								width: 16,
							},
							tintColor: Theme.colors.baseWhiteFixed,
							named: Icon.CROSS.getIconName(),
						},
					),
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

/**
 * @module im/messenger/lib/ui/base/carousel/carousel-item
 */
jn.define('im/messenger/lib/ui/base/carousel/carousel-item', (require, exports, module) => {
	const { Avatar } = require('im/messenger/lib/ui/base/avatar');

	const cross = `<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M32 59.4286C47.1484 59.4286 59.4286 47.1484 59.4286 32C59.4286 16.8516 47.1484 4.57143 32 4.57143C16.8516 4.57143 4.57141 16.8516 4.57141 32C4.57141 47.1484 16.8516 59.4286 32 59.4286Z" fill="#A9A9A9" stroke="white" stroke-width="6"/><path d="M25 25.7645C25 26.2378 26.3259 27.7523 27.8413 29.2669L30.6826 32.0121L27.6519 35.1359C23.7688 39.2064 25 40.437 29.0725 36.5558L32.1979 33.5267L35.2287 36.5558C36.8387 38.2597 38.5435 39.301 38.9223 38.9224C39.3012 38.5437 38.2594 36.8398 36.5546 35.2306L33.5239 32.2014L36.5546 29.0776C40.4377 25.0072 39.2065 23.7766 35.1339 27.6577L32.0085 30.6868L29.2619 27.847C26.6101 25.0072 25 24.2499 25 25.7645Z" fill="white" stroke="white" stroke-width="1"/></svg>`;

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
						alignItems: 'center'
					},
					onClick: () => {
						this.props.onClick(this.props.data);
					}
					// style: this.props.size === 'L' ? itemStyle.large : itemStyle.medium,
				},

				new Avatar({
					uri: this.props.data.avatarUri,
					text: this.props.data.title,
					size: this.props.size,
					color: this.props.data.avatarColor,
				}),
				Image(
					{
						style: {
							position: 'absolute',
							height: 24,
							width: 24,
							right: 3,
							top: 0,
						},
						svg: {
							content: cross
						}
					},
				),
				View(
					{
						style: {
							paddingLeft: 16,
							paddingRight: 16,
						}
					},
					Text({
						style: {
							fontSize: 14,
						},
						text: this.props.data.title.split(' ')[0],
						ellipsize: 'end',
						numberOfLines: 1
					})
				)
			);

		}
	}

	module.exports = { CarouselItem };
});
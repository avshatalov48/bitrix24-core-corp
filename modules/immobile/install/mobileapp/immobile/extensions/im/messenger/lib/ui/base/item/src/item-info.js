/**
 * @module im/messenger/lib/ui/base/item/item-info
 */
jn.define('im/messenger/lib/ui/base/item/item-info', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { getStatus } = require('im/messenger/assets/common');

	class ItemInfo extends LayoutComponent
	{
		/**
		 *
		 * @param {Object} props
		 * @param {string} props.title
		 * @param {string} [props.isYouTitle]
		 * @param {string} props.subtitle
		 * @param {string} props.size
		 * @param {ItemInfoStyle} props.style
		 */
		constructor(props)
		{
			super(props);
		}

		render()
		{
			let image = null;
			if (this.props.status && getStatus(this.props.status) !== '')
			{
				image = Image({
					style: {
						height: 16,
						width: 16,
						marginBottom: 1,
						marginRight: 4,
					},
					uri: getStatus(this.props.status),
				});
			}
			/** @type{ItemInfoStyle} */
			const style = this.props.style;
			const iconSubtitle = this.#prepareIconSubtitle();

			return View(
				{
					style: style.mainContainer,
				},
				View(
					{
						style: {
							flexDirection: 'column',
							justifyContent: 'center',
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
								justifyContent: 'flex-start',
							},
						},
						image,
						Text({
							style: style.title,
							text: this.props.title,
							ellipsize: 'end',
							numberOfLines: 1,
						}),
						this.props.isYouTitle
							? Text({
								style: style.isYouTitle,
								text: this.props.isYouTitle,
							})
							: null,
					),
					View(
						{
							style: {
								flexDirection: 'row',
							},
						},
						iconSubtitle,
						Text({
							style: style.subtitle,
							text: this.props.subtitle,
							ellipsize: 'end',
							numberOfLines: 1,
						}),
					),
				),
			);
		}

		#prepareIconSubtitle()
		{
			if (!this.props.iconSubtitle)
			{
				return null;
			}

			const defaultStyle = {
				width: 22,
				height: 22,
				alignSelf: 'center',
			};
			const style = this.props.style.iconSubtitleStyle || defaultStyle;
			const tintColor = this.props.style.iconSubtitleStyle.color || Theme.colors.base4;

			return Image({
				style,
				tintColor,
				named: this.props.iconSubtitle,
			});
		}
	}

	module.exports = { ItemInfo };
});

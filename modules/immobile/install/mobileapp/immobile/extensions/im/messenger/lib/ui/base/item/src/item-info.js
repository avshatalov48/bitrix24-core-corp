/**
 * @module im/messenger/lib/ui/base/item/item-info
 */
jn.define('im/messenger/lib/ui/base/item/item-info', (require, exports, module) => {

	const { getStatus } = require('im/messenger/assets/common');
	class ItemInfo extends LayoutComponent
	{

		/**
		 *
		 * @param {Object} props
		 * @param {string} props.title
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
						marginBottom: 2,
						marginRight: 4,
					},
					uri: getStatus(this.props.status),
				});
			}
			/** @type{ItemInfoStyle}*/
			const style = this.props.style;
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
					),
					Text({
						style: style.subtitle,
						text: this.props.subtitle,
						ellipsize: 'end',
						numberOfLines: 1,
					}),
				),
			)
		}
	}

	module.exports = { ItemInfo };
});

/**
 * @module im/messenger/lib/ui/base/item/item
 */
jn.define('im/messenger/lib/ui/base/item/item', (require, exports, module) => {

	const { Avatar } = require('im/messenger/lib/ui/base/avatar');
	const { ItemInfo } = require('im/messenger/lib/ui/base/item/item-info');
	const { styles: itemStyles } = require('im/messenger/lib/ui/base/item/style');
	const { arrowRight } = require('im/messenger/assets/common');

	/**
	 * @typedef {Object} ItemData
	 * @property {string | number} id
	 * @property {string} title
	 * @property {string} subTitle
	 * @property {string} avatarUrl
	 * @property {string} avatarColor
	 * @property {string} size
	 */

	class Item extends LayoutComponent
	{

		/**
		 * @param {ItemData}props
		 */
		constructor(props)
		{
			super(props);
		}

		getStyleBySize()
		{
			const size = this.props.size === 'L' ? 'L' : 'M';
			if (size === 'L')
			{
				return itemStyles.large;
			}

			return itemStyles.medium
		}

		render()
		{
			const style = this.getStyleBySize();

			return View(
				{
					style: {
						flexDirection: 'column',
					},
					clickable: true,
					onClick: () => {
						const openDialogData = {
							dialogId: this.props.data.id,
							dialogTitleParams: {
								name: this.props.data.title,
								description: this.props.data.description,
								avatar: this.props.data.avatarUri,
								color: this.props.data.avatarColor
							}
						};
						this.props.onClick(openDialogData);
					}
				},
				View(
					{
						style: style.itemContainer,
					},
					View(
						{
							style: {
								marginBottom: 6,
								marginTop: 6,
							}
						},
						new Avatar({
							text: this.props.data.title,
							uri: this.props.data.avatarUri,
							color: this.props.data.avatarColor,
							size: this.props.size,
						}),
					),
					View(
						{
							style: {
								flexDirection: 'row',
								borderBottomWidth: 1,
								borderBottomColor: '#e9e9e9',
								flexGrow: 2,
								alignItems: 'center',
								marginBottom: 6,
								marginTop: 6,
								height: '100%',
							}
						},
						new ItemInfo(
						{
							title: this.props.data.title,
							subtitle: this.props.data.subtitle,
							size: this.props.data.size,
							style: style.itemInfo,
							status: this.props.data.status,
						}
						),
						this.getArrowRightImage(),
					),
				),
			);
		}

		getArrowRightImage()
		{
			if (!this.props.nextTo)
			{
				return null;
			}

			return View(
				{
					style: {
						alignSelf: 'flex-end',
						alignItems: 'center',
						justifyContent: 'center',
						marginRight: 20,
						height: '100%',
					},
				},
				Image({
					style:{
						width: 9,
						height: 12,
					},
					svg: {
						content: arrowRight(),
					},
				})
			);
		}
	}
	module.exports = { Item };
});
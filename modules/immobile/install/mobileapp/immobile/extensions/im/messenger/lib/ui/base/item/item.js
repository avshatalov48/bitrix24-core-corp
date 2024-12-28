/**
 * @module im/messenger/lib/ui/base/item/item
 */
jn.define('im/messenger/lib/ui/base/item/item', (require, exports, module) => {
	const { Type } = require('type');
	const { Feature: MobileFeature } = require('feature');

	const { Avatar: MessengerAvatarLegacy } = require('im/messenger/lib/ui/base/avatar');
	const { ItemInfo } = require('im/messenger/lib/ui/base/item/item-info');
	const { styles: itemStyles } = require('im/messenger/lib/ui/base/item/style');
	const { withPressed } = require('utils/color');
	const { ChatTitle } = require('im/messenger/lib/element');
	const { Logger } = require('im/messenger/lib/logger');
	const { clone } = require('utils/object');

	/**
	 * @class Item
	 * @typedef {LayoutComponent<MessengerItemProps, {}>} Item
	 */
	class Item extends LayoutComponent
	{
		getStyleBySize()
		{
			if (this.props.isCustomStyle)
			{
				return this.props.data.style;
			}

			let style = {};
			const size = this.props.size === 'L' ? 'L' : 'M';
			if (size === 'L')
			{
				style = clone(itemStyles.large);
			}
			else
			{
				style = clone(itemStyles.medium);
			}

			style.itemInfo.title.color = ChatTitle.createFromDialogId(this.props.data.id).getTitleColor();

			return style;
		}

		render()
		{
			const style = this.getStyleBySize();
			let avatar = null;
			if (Type.isObject(this.props.data.avatar) && MobileFeature.isNativeAvatarSupported())
			{
				avatar = Avatar(this.props.data.avatar);
			}
			else
			{
				avatar = new MessengerAvatarLegacy({
					text: this.props.data.title,
					uri: this.props.data.avatarUri,
					color: this.props.data.avatarColor,
					size: this.props.size,
					isSuperEllipse: this.props.isSuperEllipseAvatar,
				});
			}

			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.viewRef = ref;
						}
					},
					style: {
						flexDirection: 'column',
						backgroundColor: this.props.isWithPressed
							? withPressed(style.parentView.backgroundColor)
							: style.parentView.backgroundColor,
					},
					clickable: true,
					onClick: () => {
						if (this.props.onClick)
						{
							const openDialogData = {
								dialogId: this.props.data.id,
								dialogTitleParams: {
									key: this.props.data.key || null,
									name: this.props.data.title,
									description: this.props.data.description,
									avatar: this.props.data.avatarUri,
									color: this.props.data.avatarColor,
								},
							};
							this.props.onClick(openDialogData);
						}
					},
					onLongClick: () => {
						if (this.props.onLongClick)
						{
							this.props.onLongClick(this.props.data, this.viewRef);
						}
					},
				},
				View(
					{
						style: style.itemContainer,
					},
					View(
						{
							style: style.avatarContainer || {
								marginBottom: 6,
								marginTop: 6,
							},
						},
						avatar,
						this.renderStatusInAvatar(),
					),
					View(
						{
							style: style.itemInfoContainer || {
								flexDirection: 'row',
								flexGrow: 2,
								alignItems: 'center',
								marginBottom: 6,
								marginTop: 6,
								height: '100%',
							},
						},
						new ItemInfo(
							{
								title: this.props.data.title,
								isYouTitle: this.props.data.isYouTitle,
								subtitle: this.props.data.subtitle,
								size: this.props.size,
								style: style.itemInfo,
								status: this.props.data.status,
								iconSubtitle: this.props.data.iconSubtitle,
							},
						),
						this.getAdditionalComponent(),
					),
				),
			);
		}

		renderStatusInAvatar()
		{
			if (!this.props.isCustomStyle)
			{
				return null;
			}

			return View(
				{
					style: {
						position: 'absolute',
						zIndex: 2,
						flexDirection: 'column',
						alignSelf: 'flex-end',
					},
				},
				this.props.data.crownStatus
					? Image({
						style: {
							width: 18,
							height: 18,
							marginBottom: 10,
						},
						svg: { content: this.props.data.crownStatus },
						onFailure: (e) => Logger.error(e),
					})
					: null,
				!!this.props.data.status && Image({
					style: {
						width: 18,
						height: 18,
					},
					svg: { content: this.props.data.status },
					onFailure: (e) => Logger.error(e),
				}),
			);
		}

		getAdditionalComponent()
		{
			if (!this.props.additionalComponent)
			{
				return null;
			}

			if (Object.prototype.hasOwnProperty.call(this.props.additionalComponent, 'create'))
			{
				return this.props.additionalComponent.create();
			}

			return this.props.additionalComponent;
		}
	}

	module.exports = { Item };
});

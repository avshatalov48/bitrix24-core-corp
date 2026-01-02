/**
 * @module im/messenger/lib/ui/base/item/selected-item
 */
jn.define('im/messenger/lib/ui/base/item/selected-item', (require, exports, module) => {
	const { Type } = require('type');
	const { Icon } = require('assets/icons');
	const { Avatar: MessengerAvatarLegacy } = require('im/messenger/lib/ui/base/avatar');
	const { CheckBox } = require('im/messenger/lib/ui/base/checkbox');
	const { ItemInfo } = require('im/messenger/lib/ui/base/item/item-info');
	const { selectedItemStyles } = require('im/messenger/lib/ui/base/item/style');
	const { Item } = require('im/messenger/lib/ui/base/item/item');
	const { Theme } = require('im/lib/theme');

	class SelectedItem extends Item
	{
		/**
		 *
		 * @param{Object} props
		 * @param{boolean} props.selected
		 * @param{JNEventEmitter} props.eventEmitter
		 * @param{boolean} [props.disabled]
		 */
		constructor(props)
		{
			super(props);
			this.state.selected = props.selected;
			this.state.disabled = (typeof props.disabled !== 'undefined') ? props.disabled : false;
			this.state.currentColor = this.getCurrentColor();
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.props.parentEmitter.on('onItemUnselected', this.unselectItem.bind(this));
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();
			this.props.parentEmitter.off('onItemUnselected', this.unselectItem);
		}

		unselectItem(itemData)
		{
			if (this.props.data.id !== itemData.id)
			{
				return;
			}

			this.setState({
				selected: false,
				currentColor: selectedItemStyles.unselectColor,
			});
		}

		getCurrentColor()
		{
			return this.state.selected
				? selectedItemStyles.selectColor
				: selectedItemStyles.unselectColor
			;
		}

		getNextColor()
		{
			return this.state.selected
				? selectedItemStyles.unselectColor
				: selectedItemStyles.selectColor
			;
		}

		toggleSelect()
		{
			this.setState({
				selected: !this.state.selected,
				currentColor: this.getNextColor()
			});
			this.props.onClick(this.props.data, this.state.selected);
		}

		render()
		{
			const style = this.getStyleBySize();

			let avatar = null;
			if (Type.isObject(this.props.data.avatar))
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
					clickable: true,
					onClick: () => this.toggleSelect(),
					style: {
						flexDirection: 'row',
						maxHeight: 70,
					},
				},
				View( // checkBox
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					View(
						{
							style: {
								height: 58,
								marginLeft: 6,
								marginTop: 6,
								marginBottom: 6,
								backgroundColor: this.state.currentColor,
								borderTopLeftRadius: 8,
								borderBottomLeftRadius: 8,
								paddingLeft: 12,
								paddingTop: 16.5,
								paddingBottom: 17.5,
								paddingRight: 12,
								flexDirection: 'row',
								justifyContent: 'center',
								alignItems: 'center',
								backgroundOpacity: this.state.selected ? 0.6 : 1,
							},
						},
						new CheckBox({
							checked: this.state.selected,
							disabled: this.props.disabled,
							onClick: () => this.toggleSelect(),
						}),
					),
				),
				View( //itemInfo
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'center',
							alignItems: 'center',
							flex: 1,
							borderBottomWidth: 1,
							borderBottomColor: Theme.colors.bgSeparatorPrimary,
						},
					},
					View(
						{
							style: {
								flex: 1,
								height: 58,
								marginTop: 6,
								marginRight: 6,
								marginBottom: 6,
								backgroundColor: this.state.currentColor,
								borderTopRightRadius: 8,
								borderBottomRightRadius: 8,
								paddingRight: 12,
								paddingTop: 8.5,
								paddingBottom: 9.5,
								flexDirection: 'row',
								justifyContent: 'center',
								alignItems: 'center',
								backgroundOpacity: this.state.selected ? 0.6 : 1,
							},
						},
						View(
							{
								style: {
									marginRight: -1, //pixel perfect
								},
							},
							avatar,
						),
						View(
							{
								style: {
									flexDirection: 'row',
									flexGrow: 2,
									alignItems: 'center',
								},
							},
							new ItemInfo(
								{
									title: this.props.data.title,
									subtitle: this.props.data.subtitle,
									size: this.props.data.size,
									style: style.itemInfo,
									status: this.props.data.status,
								},
							),
						),
						View(
							{
								style: {
									opacity: this.state.selected ? 1 : 0,
								},
							},
							Image({
								style: {
									width: 24,
									height: 24,
								},
								tintColor: Theme.colors.base4,
								named: Icon.CHECK.getIconName(),
							}),
						),
					),
				),
			);
		}
	}

	module.exports = { SelectedItem };
});

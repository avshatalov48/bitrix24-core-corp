/**
 * @module im/messenger/lib/ui/base/item/selected-item
 */
jn.define('im/messenger/lib/ui/base/item/selected-item', (require, exports, module) => {
	const { CheckBox } = require('im/messenger/lib/ui/base/checkbox');
	const { Avatar } = require('im/messenger/lib/ui/base/avatar');
	const { ItemInfo } = require('im/messenger/lib/ui/base/item/item-info');
	const { styles: itemStyles, selectedItemStyles } = require('im/messenger/lib/ui/base/item/style');
	const { Item } = require('im/messenger/lib/ui/base/item/item');

	class SelectedItem extends Item
	{
		/**
		 *
		 * @param{Object} props
		 * @param{boolean} props.selected
		 * @param{JNEventEmitter} props.eventEmitter
		 * @param{boolean} [props.disabled[
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

			return View(
				{
					clickable: true,
					onClick: () => this.toggleSelect(),
					style: {
						flexDirection: 'row',
						backgroundColor: this.state.currentColor,
					}
				},
				View(
					{
						style: {
							marginLeft: 15,
							marginRight: 5,
							alignItems: 'center',
							justifyContent: 'center',
							backgroundColor: this.state.currentColor,
						},
						clickable: false,
					},
					new CheckBox({
						checked: this.state.selected,
						disabled: this.props.disabled,
						onClick: () => this.toggleSelect()
					}),
				),
				View(
					{
						style: {
							...style.itemContainer,
							borderBottomWidth: 1,
							borderBottomColor: '#e9e9e9',
							flexGrow: 2,
						},
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
	}



	module.exports = { SelectedItem };
});
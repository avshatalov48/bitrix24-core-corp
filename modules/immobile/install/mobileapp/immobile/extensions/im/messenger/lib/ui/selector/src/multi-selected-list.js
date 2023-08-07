/**
 * @module im/messenger/lib/ui/selector/multi-selected-list
 */
jn.define('im/messenger/lib/ui/selector/multi-selected-list', (require, exports, module) => {
	const { SelectedItem, EmptySearchItem } = require('im/messenger/lib/ui/base/item');
	const { List } = require('im/messenger/lib/ui/base/list');

	class MultiSelectedList extends List
	{
		constructor(props)
		{
			super(props);

			if (props.ref)
			{
				props.ref(this);
			}
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			if (nextProps.isShadow !== this.props.isShadow)
			{
				return true;
			}

			return super.shouldComponentUpdate(nextProps, nextState);
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.renderRecentText(),
				ListView({
					style: {
						flex: 1,
						backgroundColor: '#FFFFFF',
					},
					data: [{ items: this.state.itemList }],
					renderItem: (props) => {
						if (props.type === 'empty')
						{
							return new EmptySearchItem();
						}

						return new SelectedItem(
							{
								...props,
								onClick: (itemData, isSelected) => {
									if (isSelected)
									{
										this.props.onSelectItem(itemData);

										return;
									}
									this.props.onUnselectItem(itemData);
								},
								parentEmitter: this.emitter,
							},
						);
					},
					onLoadMore: () => {},
					renderLoadMore: () => {
						return this.loader;
					},
					ref: (ref) => this.listRef = ref,
				}),
			);
		}

		renderRecentText()
		{
			if (!this.props.recentText)
			{
				return null;
			}

			return View(
				{
					style: {
						backgroundColor: '#FFFFFF',
						borderTopRightRadius: 12,
						borderTopLeftRadius: 12,
						paddingLeft: 20,
						paddingVertical: 10,
					},
				},
				Text({
					text: this.props.recentText,
					style: {
						color: '#525C69',
						fontSize: 14,
						fontWeight: 400,
						textStyle: 'normal',
						textAlign: 'start',
					},
				}),
			);
		}

		unselectItem(itemData)
		{
			this.emit('onItemUnselected', [itemData]);
		}
	}

	module.exports = { MultiSelectedList };
});

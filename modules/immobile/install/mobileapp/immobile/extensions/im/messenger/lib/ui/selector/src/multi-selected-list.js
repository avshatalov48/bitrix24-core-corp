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
			return ListView({
				style: {
					flex: 1,
				},
				data: [{items: this.state.itemList}],
				renderItem: (props) => {
					if (props.type === 'empty')
					{
						return new EmptySearchItem();
					}
					return  new SelectedItem(
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
					});
				},
				onLoadMore: () =>
				{
				},
				renderLoadMore: () => {
					return this.loader;
				},
				ref: ref => this.listRef = ref,
			});
		}

		unselectItem(itemData)
		{
			this.emit('onItemUnselected', [itemData]);
		}
	}

	module.exports = { MultiSelectedList };
});
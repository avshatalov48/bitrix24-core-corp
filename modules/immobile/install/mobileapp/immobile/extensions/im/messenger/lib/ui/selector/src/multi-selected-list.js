/**
 * @module im/messenger/lib/ui/selector/multi-selected-list
 */
jn.define('im/messenger/lib/ui/selector/multi-selected-list', (require, exports, module) => {
	const { SelectedItem, EmptySearchItem } = require('im/messenger/lib/ui/base/item');
	const { List } = require('im/messenger/lib/ui/base/list');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');
	const { Theme } = require('im/lib/theme');

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
						backgroundColor: Theme.colors.bgContentTertiary,
						borderTopRightRadius: 12,
						borderTopLeftRadius: 12,
					},
				},
				this.renderRecentText(),
				ListView({
					style: {
						flex: 1,
						backgroundColor: Theme.colors.bgContentPrimary,
					},
					data: [{ items: this.state.itemList }],
					renderItem: (props) => {
						if (props.type === 'empty')
						{
							return new EmptySearchItem();
						}

						if (props.type === 'loader')
						{
							return new LoaderItem({ enable: true });
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
								isSuperEllipseAvatar: this.props.isSuperEllipseAvatar,
							},
						);
					},
					ref: (ref) => this.listRef = ref,
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

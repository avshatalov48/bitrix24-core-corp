/**
 * @module im/messenger/lib/ui/base/carousel
 */
jn.define('im/messenger/lib/ui/base/carousel', (require, exports, module) => {
	const { CarouselItem } = require('im/messenger/lib/ui/base/carousel/carousel-item');
	const { Type } = require('type');
	const { Theme } = require('im/lib/theme');
	class Carousel extends LayoutComponent
	{
		/**
		 *
		 * @param {Object} props
		 * @param {array} props.itemList
		 * @param {string} props.size
		 * @param {Function} [props.ref]
		 * @param {boolean} [props.isSuperEllipseAvatar]
		 */
		constructor(props)
		{
			super(props);
			this.currentItemKey = 1;
			this.state.itemList = this.prepareItemListForRender(props.itemList);
			this.gridViewRef = null;
			this.state.isVisible = this.state.itemList.length > 0;
			if (props.ref && Type.isFunction(props.ref))
			{
				props.ref(this);
			}
		}

		render()
		{
			return GridView({
				style: {
					height: this.state.isVisible === true ? 100 : 0,
					backgroundColor: Theme.isDesignSystemSupported ? Theme.colors.bgContentPrimary : Theme.colors.bgContentTertiary,
					paddingLeft: 12,
				},
				params: {
					orientation: 'horizontal',
					rows: 1,
				},
				showsHorizontalScrollIndicator: true,
				isScrollable: true,
				data: [{ items: this.state.itemList }],
				renderItem: (props) => {
					return new CarouselItem({
						...props,
						isSuperEllipseAvatar: this.props.isSuperEllipseAvatar,
						onClick: (itemData) => {
							this.removeItem(itemData);
							this.props.onItemSelected(itemData);
						},
					});
				},
				ref: (ref) => {
					this.gridViewRef = ref;
				},
			});
		}

		addItem(itemData)
		{
			if (this.state.itemList.length === 0)
			{
				const animator = this.gridViewRef.animate(
					{
						duration: 200,
						height: 100,
						option: 'linear',
					},
					() => {
						const item = {
							data: itemData,
							parentEmitter: this.emitter,
						};

						this.setState({
							isVisible: true,
							itemList: [this.prepareItemForRender(item)],
						});
					},
				);
				animator.start();

				return;
			}
			this.addItemToGrid(itemData);
		}

		addItemToGrid(itemData)
		{
			let item = this.state.itemList.find((currentItem) => currentItem.data.id === itemData.id);
			if (!Type.isUndefined(item))
			{
				return;
			}

			item = {
				data: itemData,
				parentEmitter: this.emitter,
			};
			item = this.prepareItemForRender(item);
			this.state.itemList = [...this.state.itemList, item];

			if (Application.getPlatform() === 'ios' && Application.getApiVersion() < 49)
			{
				this.gridViewRef.appendRows([item], 'fade');
				this.gridViewRef.scrollTo(0, (this.state.itemList.length - 1), true);

				return;
			}

			this.gridViewRef
				.appendRows([item], 'fade')
				.then(() => this.gridViewRef.scrollTo(0, (this.state.itemList.length - 1), true))
			;
		}

		removeItem(itemData)
		{
			const item = this.state.itemList.find((currentItem) => currentItem.data.id === itemData.id);
			if (Type.isUndefined(item))
			{
				return;
			}

			const { section, index } = this.gridViewRef.getElementPosition(item.key);
			this.gridViewRef.deleteRow(section, index, 'fade', () => {
				this.state.itemList = this.state.itemList.filter((currentItem) => currentItem.data.id !== itemData.id);
				if (this.state.itemList.length === 0)
				{
					const animator = this.gridViewRef.animate(
						{
							duration: 100,
							height: 0,
							option: 'linear',
						},
						() => this.setState({ isVisible: false }),
					);
					animator.start();
				}
			});
		}

		prepareItemListForRender(itemList)
		{
			if (!Type.isArray(itemList))
			{
				return [];
			}

			return itemList.map((item) => this.prepareItemForRender(item));
		}

		prepareItemForRender(item)
		{
			const result = {
				...item,
				type: 'carousel',
				size: this.props.size === 'L' ? 'L' : 'M',
			};

			if (!item.key)
			{
				result.key = (this.currentItemKey++).toString();
			}

			return result;
		}
	}

	module.exports = { Carousel, CarouselItem };
});

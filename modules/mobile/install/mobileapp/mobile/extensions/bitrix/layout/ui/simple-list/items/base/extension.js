/**
 * @module layout/ui/simple-list/items/base
 */
jn.define('layout/ui/simple-list/items/base', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { chain, transition } = require('animation');
	const { Haptics } = require('haptics');
	const { ItemLayoutBlockManager } = require('layout/ui/simple-list/items/base/item-layout-block-manager');
	const { debounce } = require('utils/function');
	const { mergeImmutable } = require('utils/object');

	/**
	 * @class Base
	 */
	class Base extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.blockManager = new ItemLayoutBlockManager(this.props.itemLayoutOptions);
			this.showMenuHandler = props.showMenuHandler;
			this.styles = this.getStyles();

			this.onItemClick = debounce((itemId, itemData, params) => {
				this.props.itemDetailOpenHandler(itemId, itemData, params);
			}, 100);

			this.onItemLongClick = (itemId, itemData, params) => {
				if (this.props.onItemLongClick)
				{
					this.props.onItemLongClick(itemId, itemData, params);
				}

				if (this.isMenuEnabled())
				{
					Haptics.impactLight();
					this.showMenuHandler(itemData.id);
				}
			};
		}

		get testId()
		{
			return this.props.testId || '';
		}

		get params()
		{
			return this.props.params || {};
		}

		get layout()
		{
			return this.props.layout || {};
		}

		getCustomStyles()
		{
			return BX.prop.getObject(this.props, 'customStyles', {});
		}

		get colors()
		{
			return this.props.showAirStyle ? AppTheme.realColors : AppTheme.colors;
		}

		getStyles()
		{
			return {
				wrapper: {
					paddingBottom: 1,
					backgroundColor: this.colors.bgContentPrimary,
				},
				item: {
					position: 'relative',
					backgroundColor: this.colors.bgContentPrimary,
				},
				itemContent: {
					paddingTop: 17,
					paddingBottom: 17,
				},
			};
		}

		/**
		 * @private
		 * @returns View
		 */
		render()
		{
			const customStyles = this.getCustomStyles();
			let wrapperStyle = BX.prop.getObject(customStyles, 'wrapper', {});
			wrapperStyle = mergeImmutable(this.styles.wrapper, wrapperStyle);

			const item = this.prepareItem(this.props.item);

			return View(
				{
					style: wrapperStyle,
					ref: this.props.forwardRef,
					testId: `${this.testId}_ITEM_${item.id}`,
					onClick: () => this.onItemClick(item.id, item.data, this.params),
					onLongClick: () => this.onItemLongClick(item.id, item.data, this.params),
				},
				View(
					{
						style: this.styles.item,
						ref: (ref) => {
							this.elementRef = ref;
						},
					},
					this.renderItemContent(),
				),
			);
		}

		/**
		 * Implement this method in child class if you need to change item layout
		 *
		 * @private
		 * @returns View
		 */
		renderItemContent()
		{
			const { data } = this.props.item;

			return View(
				{
					style: {
						...this.styles.itemContent,
						marginLeft: 16,
					},
				},
				Text({
					text: data.name || data.id,
				}),
			);
		}

		/**
		 * Implement this method in child class if you need to process actions before showing them in action's menu
		 *
		 * @public
		 * @param actions
		 */
		prepareActions(actions)
		{}

		/**
		 * Implement this method in child class if you need to process item before showing
		 *
		 * @public
		 * @param item
		 */
		prepareItem(item)
		{
			return item;
		}

		/**
		 * @protected
		 * @returns {boolean}
		 */
		isMenuEnabled()
		{
			return (this.blockManager.can('useItemMenu') && this.props.hasActions);
		}

		/**
		 * @public
		 * @param {String|function} property
		 * @return {boolean}
		 */
		isVisible(property)
		{
			if (typeof property === 'function')
			{
				return property(this.props);
			}

			const has = Object.prototype.hasOwnProperty;

			return (has.call(this.props.item.data, property) && this.props.item.data[property].length > 0);
		}

		/**
		 * @public
		 * @param callback
		 * @param showUpdated
		 */
		blink(callback = null, showUpdated = true)
		{
			this.setLoading(this.dropLoading.bind(this, callback, showUpdated));
		}

		/**
		 * @public
		 * @param callback
		 */
		setLoading(callback = null)
		{
			if (!this.elementRef)
			{
				return;
			}

			const duration = 300;
			const opacity = 0.5;

			this.elementRef.animate({ duration, opacity }, callback);
		}

		/**
		 * @public
		 * @param callback
		 * @param blink
		 */
		dropLoading(callback = null, blink = true)
		{
			if (!this.elementRef)
			{
				return;
			}

			const transitionToBeige = transition(this.elementRef, {
				duration: 300,
				backgroundColor: this.colors.accentSoftOrange1,
				opacity: 1,
			});
			const transitionToWhite = transition(this.elementRef, {
				duration: 300,
				backgroundColor: this.colors.base8,
				opacity: 1,
			});
			const animate = (
				blink
					? chain(
						transitionToBeige,
						transitionToWhite,
					)
					: chain(
						transitionToWhite,
					)
			);

			animate().then(callback).catch(console.error);
		}
	}

	module.exports = { Base };
});

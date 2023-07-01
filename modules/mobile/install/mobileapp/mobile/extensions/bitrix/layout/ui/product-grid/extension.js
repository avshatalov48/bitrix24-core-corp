/**
 * @module layout/ui/product-grid
 */
jn.define('layout/ui/product-grid', (require, exports, module) => {

	const { Loc } = require('loc');
	const { EventEmitter } = require('event-emitter');
	const { ProductGridSummary } = require('layout/ui/product-grid/components/summary');
	const { FocusContext } = require('layout/ui/product-grid/services/focus-context');
	const { FadeView } = require('animation/components/fade-view');
	const { EmptyScreen } = require('layout/ui/empty-screen');

	/**
	 * Class provides basic implementation of product grid and must be inherited to concrete use case.
	 * @abstract
	 * @class ProductGrid
	 */
	class ProductGrid extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.uid = props.uid || Random.getString();
			/** @type {EventEmitter} */
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.mounted = false;
			this.state = this.buildState(props);
			this.listViewRef = null;

			/** @type {{function():LayoutComponent}} */
			this.additionalSummary = null;

			/** @type {ProductGridSummary|null} */
			this.summaryRef = null;

			/** @type {{function():Promise}|null} */
			this.delayedSummaryUpdater = null;

			this.initServices();
		}

		/**
		 * @abstract
		 * @param {Object} props
		 * @returns {Object}
		 */
		buildState(props)
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		/**
		 * Method invokes after component receiving new props and new state was built.
		 * You can re-init some internal services here.
		 */
		initServices() {}

		componentWillReceiveProps(props)
		{
			if (BX.prop.getBoolean(props, 'reloadFromProps', false))
			{
				this.state = this.buildState(props);
				this.initServices();
			}
		}

		componentDidMount()
		{
			this.mounted = true;
		}

		componentWillUnmount()
		{
			this.mounted = false;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						backgroundColor: '#eef2f4',
					},
					onClick: () => FocusContext.blur(),
				},
				new FadeView({
					visible: false,
					fadeInOnMount: true,
					style: {
						flexGrow: 1,
					},
					slot: () => this.getItems().length ? this.renderListScreen() : this.renderEmptyScreen(),
				})
			);
		}

		/**
		 * Renders all items and summary block using ListView.
		 * @returns {View}
		 */
		renderListScreen()
		{
			const items = this.getItems().map((item, index) => ({
				index,
				key: item.getId(),
				editable: this.isEditable(),
				type: 'product',
				data: item.getRawValues(),
			}));

			items.push({
				key: 'summary',
				type: 'summary',
				data: this.getSummary(),
			});

			return FullScreen(
				ListView({
					ref: (ref) => this.listViewRef = ref,
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						position: 'absolute',
						width: '100%',
						height: '100%',
						top: -1,
					},
					onScroll: (scrollParams) => {
						if (this.props.onScroll)
						{
							this.props.onScroll(scrollParams);
						}
					},
					scrollEventThrottle: 15,
					data: [{ items }],
					renderItem: (props) => {
						if (props.type === 'summary')
						{
							return this.renderSummary(this.getSummary());
						}

						const item = this.getItem(props.key);
						if (item)
						{
							return this.renderSingleItem(item, props.index);
						}

						return null;
					},
				}),
				this.renderAddItemButton()
			);
		}

		/**
		 * Renders single grid item.
		 * @abstract
		 * @protected
		 * @param {ProductRow} productRow
		 * @param {number} index
		 * @returns {LayoutComponent}
		 */
		renderSingleItem(productRow, index)
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		/**
		 * Renders bottom summary block.
		 * @param {object} props
		 * @returns {LayoutComponent}
		 */
		renderSummary(props)
		{
			return new ProductGridSummary({
				ref: (ref) => {
					if (ref)
					{
						this.summaryRef = ref;
						if (this.delayedSummaryUpdater)
						{
							this.forceUpdateSummary(this.delayedSummaryUpdater);
						}
					}
				},
				...props,
				additionalSummary: this.additionalSummary,
				showSummaryAmount: this.showSummaryAmount,
				showSummaryTax: this.showSummaryTax,
				discountCaption: this.discountCaption,
				totalSumCaption: this.totalSumCaption,
			});
		}

		/**
		 * Renders generic empty screen.
		 * @returns {View}
		 */
		renderEmptyScreen()
		{
			return FullScreen(
				new EmptyScreen({
					image: {
						uri: EmptyScreen.makeLibraryImagePath('products.png'),
						style: {
							width: 218,
							height: 178,
						}
					},
					title: () => this.getEmptyScreenTitle(),
					description: () => this.getEmptyScreenDescription(),
				}),
				this.renderAddItemButton()
			);
		}

		/**
		 * @protected
		 * @return {string}
		 */
		getEmptyScreenTitle()
		{
			return Loc.getMessage('PRODUCT_GRID_NO_PRODUCTS');
		}

		/**
		 * @protected
		 * @return {string|null}
		 */
		getEmptyScreenDescription()
		{
			return null;
		}

		/**
		 * @return {boolean}
		 */
		get showFloatingButton()
		{
			return BX.prop.getBoolean(this.props, 'showFloatingButton', true);
		}

		/**
		 * @return {boolean}
		 */
		get showSummaryAmount()
		{
			return BX.prop.getBoolean(this.props, 'showSummaryAmount', true);
		}

		/**
		 * @return {boolean}
		 */
		get showSummaryTax()
		{
			return BX.prop.getBoolean(this.props, 'showSummaryTax', true);
		}

		/**
		 * @return {string}
		 */
		get discountCaption()
		{
			return BX.prop.getString(this.props, 'discountCaption', '');
		}

		/**
		 * @return {string}
		 */
		get totalSumCaption()
		{
			return BX.prop.getString(this.props, 'totalSumCaption', '');
		}

		/**
		 * Renders floating button to add new items (only to editable grid).
		 * @returns {LayoutComponent|null}
		 */
		renderAddItemButton()
		{
			if (this.isEditable() && this.showFloatingButton)
			{
				return new UI.FloatingButtonComponent({
					testId: 'productGridAddItemButton',
					onClick: () => this.onAddItemButtonClick(),
					onLongClick: () => this.onAddItemButtonLongClick(),
				});
			}

			return null;
		}

		/**
		 * Method invokes when user taps to "create product" floating button.
		 * @abstract
		 */
		onAddItemButtonClick() {}

		/**
		 * Method invokes when user performs long press to "create product" floating button.
		 * @abstract
		 */
		onAddItemButtonLongClick() {}

		/**
		 * Adds new row to the grid.
		 * @abstract
		 * @param {ProductRow} productRow
		 */
		addItem(productRow)
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		/**
		 * Removes row from the grid.
		 * @abstract
		 * @param {ProductRow} productRow
		 */
		removeItem(productRow)
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		/**
		 * Accessor returns product rows from state.
		 * @abstract
		 * @returns {ProductRow[]}
		 */
		getItems()
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		/**
		 * @param {string|number} id
		 * @return {ProductRow|undefined}
		 */
		getItem(id)
		{
			return this.getItems().find(item => item.getId() === id);
		}

		/**
		 * Accessor returns grid summary data from state, to render bottom summary block.
		 * @abstract
		 * @returns {Object}
		 */
		getSummary()
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		/**
		 * Use this method to update summary block only, avoiding re-render of whole grid.
		 * @param {function():Promise} fetcher
		 */
		forceUpdateSummary(fetcher)
		{
			if (!this.summaryRef)
			{
				// We need this hack because ref of summary block is async
				// and may not exist in that moment
				this.delayedSummaryUpdater = fetcher;
				return;
			}

			this.delayedSummaryUpdater = null;

			this.customEventEmitter.emit(CatalogStoreEvents.ProductList.StartUpdateSummary);

			this.summaryRef
				.fadeOut()
				.then(() => fetcher())
				.then(data => {
					this.summaryRef.fadeIn(data);
					this.customEventEmitter.emit(CatalogStoreEvents.ProductList.FinishUpdateSummary);
				})
			;
		}

		/**
		 * Returns current component state.
		 * @returns {Object}
		 */
		getState()
		{
			return this.state;
		}

		/**
		 * Returns true if component is mounted right now.
		 * @returns {boolean}
		 */
		isMounted()
		{
			return this.mounted;
		}

		/**
		 * Returns true if it's possible to change items.
		 * @abstract
		 * @returns {Boolean}
		 */
		isEditable()
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		scrollListToTheTop(animate = true)
		{
			if (this.listViewRef)
			{
				this.listViewRef.scrollToBegin(animate);
			}
		}

		/**
		 * Tries to scroll list to very bottom position.
		 * Supports both ListView and ScrollView.
		 * @param {boolean} animate
		 */
		scrollListToTheEnd(animate = true)
		{
			if (this.listViewRef)
			{
				this.listViewRef.scrollTo(0, this.getItems().length, animate);
			}
		}

		/**
		 * Works the same as setState(), but notifies other components that grid state was changed.
		 * @param {object} nextState
		 * @param {function} callback
		 */
		setStateWithNotification(nextState, callback)
		{
			this.setState(nextState, () => {
				this.notifyGridChanged();
				if (callback)
				{
					callback();
				}
			});
		}

		/**
		 * You can trigger some events here, to notify other components that product grid state was changed.
		 */
		notifyGridChanged() {}
	}

	function FullScreen(...content)
	{
		return View(
			{
				style: {
					flexDirection: 'column',
					flexGrow: 1,
					backgroundColor: '#eef2f4',
				},
			},
			...content
		);
	}

	module.exports = { ProductGrid };

});
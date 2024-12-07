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
	const { Random } = require('utils/random');
	const { FloatingButtonComponent } = require('layout/ui/floating-button');

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
			this.additionalTopContent = null;

			/** @type {{function():LayoutComponent}} */
			this.additionalBottomContent = null;

			/** @type {{function():LayoutComponent}} */
			this.additionalSummary = null;

			/** @type {{function():LayoutComponent}} */
			this.additionalSummaryBottom = null;

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
		initServices()
		{}

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
					},
					onClick: () => FocusContext.blur(),
				},
				new FadeView({
					visible: false,
					fadeInOnMount: true,
					notVisibleOpacity: 0.5,
					style: {
						flexGrow: 1,
					},
					slot: () => {
						return (
							this.getItems().length > 0
							|| this.showEmptyScreen === false
						)
							? this.renderListScreen()
							: this.renderEmptyScreen();
					},
				}),
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

			if (this.additionalTopContent)
			{
				items.unshift({
					key: 'top',
					type: 'top',
				});
			}

			if (this.additionalBottomContent)
			{
				items.push({
					key: 'bottom',
					type: 'bottom',
				});
			}

			return FullScreen(
				ListView({
					ref: (ref) => {
						this.listViewRef = ref;
					},
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
						if (props.type === 'top')
						{
							return this.additionalTopContent;
						}

						if (props.type === 'bottom')
						{
							return this.additionalBottomContent;
						}

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
				this.renderAddItemButton(),
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
				additionalSummaryBottom: this.additionalSummaryBottom,
				componentsForDisplay: this.getSummaryComponents(),
				discountCaption: this.discountCaption,
				totalSumCaption: this.totalSumCaption,
			});
		}

		/**
		 * Returns the parts that should be rendered in the summary
		 * @returns {{summary: boolean, discount: boolean, taxes: boolean}}
		 */
		getSummaryComponents()
		{
			return {
				summary: true,
				amount: true,
				discount: true,
				taxes: true,
			};
		}

		/**
		 * Renders generic empty screen.
		 * @returns {View}
		 */
		renderEmptyScreen()
		{
			return FullScreen(
				new EmptyScreen({
					image: this.getEmptyScreenImage(),
					title: () => this.getEmptyScreenTitle(),
					description: () => this.getEmptyScreenDescription(),
					styles: this.getEmptyScreenStyles(),
					backgroundColor: this.getEmptyScreenBackgroundColor(),
				}),
				this.renderAddItemButton(),
			);
		}

		/**
		 * @protected
		 * @return {ImageProps}
		 */
		getEmptyScreenImage()
		{
			return {
				svg: {
					uri: EmptyScreen.makeLibraryImagePath('products.svg'),
				},
				style: {
					width: 218,
					height: 178,
				},
			};
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
		 * @protected
		 * @return {object}
		 */
		getEmptyScreenStyles()
		{
			return {};
		}

		/**
		 * @protected
		 * @return {string|null}
		 */
		getEmptyScreenBackgroundColor()
		{
			return null;
		}

		/**
		 * @return {boolean}
		 */
		get showEmptyScreen()
		{
			return BX.prop.getBoolean(this.props, 'showEmptyScreen', true);
		}

		/**
		 * @return {boolean}
		 */
		get showFloatingButton()
		{
			return BX.prop.getBoolean(this.props, 'showFloatingButton', true);
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
				return new FloatingButtonComponent({
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
		onAddItemButtonClick()
		{}

		/**
		 * Method invokes when user performs long press to "create product" floating button.
		 * @abstract
		 */
		onAddItemButtonLongClick()
		{}

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
			return this.getItems().find((item) => item.getId() === id);
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

			this.customEventEmitter.emit('StoreEvents.ProductList.StartUpdateSummary');

			this.summaryRef
				.fadeOut()
				.then(() => fetcher())
				.then((data) => {
					this.summaryRef.fadeIn(data);
					this.customEventEmitter.emit('StoreEvents.ProductList.FinishUpdateSummary');
				}).catch(console.error);
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
		notifyGridChanged()
		{}
	}

	function FullScreen(...content)
	{
		return View(
			{
				style: {
					flexDirection: 'column',
					flexGrow: 1,
				},
			},
			...content,
		);
	}

	module.exports = { ProductGrid };
});

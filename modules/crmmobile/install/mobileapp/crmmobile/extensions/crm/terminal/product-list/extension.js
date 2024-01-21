/**
 * @module crm/terminal/product-list
 */
jn.define('crm/terminal/product-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { handleErrors } = require('crm/error');
	const { EventEmitter } = require('event-emitter');
	const { PaymentProductGrid } = require('crm/terminal/product-list/product-grid');
	const { PureComponent } = require('layout/pure-component');
	const { CrmProductTabShimmer } = require('layout/ui/detail-card/tabs/shimmer/crm-product');

	/**
	 * @class ProductList
	 */
	class ProductList extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isLoading: true,
			};

			this.grid = {};

			this.uid = props.uid;
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
		}

		static open(props, layout = PageManager)
		{
			layout.openWidget(
				'layout',
				{
					backdrop: {
						onlyMediumPosition: true,
						mediumPositionHeight: 500,
						swipeAllowed: true,
						forceDismissOnSwipeDown: false,
						horizontalSwipeAllowed: false,
						showOnTop: true,
						navigationBarColor: AppTheme.colors.bgSecondary,
					},
				},
			).then((layoutWidget) => {
				layoutWidget.enableNavigationBarBorder(false);
				layoutWidget.setTitle({
					text: ' ',
				});

				layoutWidget.showComponent(new this({
					...props,
					layoutWidget,
				}));
			}).catch(console.error);
		}

		initialize()
		{
			return new Promise(() => {
				BX.ajax.runAction('crmmobile.Terminal.App.getPaymentProductList', {
					data: {
						id: this.id,
					},
				}).then((response) => {
					this.grid = response.data.grid || {};

					this.layoutWidget.setTitle({
						text: Loc.getMessage('M_CRM_TL_PRODUCT_LIST_TITLE'),
					});
				}).catch(handleErrors)
					.finally(() => this.setState({ isLoading: false }));
			});
		}

		componentDidMount()
		{
			this.initialize();
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgPrimary,
					},
				},
				this.state.isLoading ? this.renderLoader() : this.renderProducts(),
			);
		}

		renderLoader()
		{
			return new CrmProductTabShimmer({
				animating: true,
				productCount: this.productsCnt,
			});
		}

		renderProducts()
		{
			return View(
				{
					style: {
						flexGrow: 1,
					},
				},
				new PaymentProductGrid({
					...this.grid,
					summaryComponents: {
						summary: true,
						amount: false,
						discount: this.grid.products.length > 0,
						taxes: false,
					},
					showEmptyScreen: false,
					showFloatingButton: false,
					discountCaption: Loc.getMessage('M_CRM_TL_PRODUCT_LIST_SUMMARY_TOTAL_DISCOUNT'),
					totalSumCaption: Loc.getMessage('M_CRM_TL_PRODUCT_LIST_SUMMARY_TOTAL'),
					uid: this.uid,
				}),
			);
		}

		/**
		 * @returns {number}
		 */
		get id()
		{
			return BX.prop.getInteger(this.props, 'id', 0);
		}

		get productsCnt()
		{
			return BX.prop.getInteger(this.props, 'productsCnt', 4);
		}

		/**
		 * @returns {Object}
		 */
		get layoutWidget()
		{
			return this.props.layoutWidget || PageManager;
		}
	}

	module.exports = {
		ProductList,
	};
});

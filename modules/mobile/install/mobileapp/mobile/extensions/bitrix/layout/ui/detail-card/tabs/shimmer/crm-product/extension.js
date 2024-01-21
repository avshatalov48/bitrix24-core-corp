/**
 * @module layout/ui/detail-card/tabs/shimmer/crm-product
 */
jn.define('layout/ui/detail-card/tabs/shimmer/crm-product', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { ShimmerView } = require('layout/polyfill');
	const { BaseShimmer } = require('layout/ui/detail-card/tabs/shimmer');

	/**
	 * @class CrmProductTabShimmer
	 */
	class CrmProductTabShimmer extends BaseShimmer
	{
		render()
		{
			const { productCount } = this.props;
			if (!productCount)
			{
				return this.renderDefaultLoader();
			}

			return super.render();
		}

		renderDefaultLoader()
		{
			return View(
				{
					style: {
						position: 'absolute',
						top: 0,
						left: 0,
						right: 0,
						bottom: 0,
					},
				},
				new LoadingScreenComponent({ backgroundColor: AppTheme.colors.bgContentPrimary }),
			);
		}

		renderContent()
		{
			const { productCount } = this.props;
			const productsToShow = Math.min(productCount, 4);

			return View(
				{
					style: {
						marginTop: 12,
					},
				},
				...Array.from({ length: productsToShow }).fill(0).map((value, index) => {
					return this.renderProduct(index % 2 === 0);
				}),
				this.renderSummary(),
			);
		}

		renderProduct(even)
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderRadius: 12,
						padding: 16,
						marginTop: 0,
						marginBottom: 12,
						flexDirection: 'row',
					},
				},
				this.renderImage(),
				this.renderProductContent(even),
				this.renderDeleteButton(),
			);
		}

		renderImage()
		{
			const { animating } = this.props;

			return View(
				{
					style: {
						width: 52,
						height: 52,
						marginTop: 4,
						marginLeft: 5,
						marginRight: 18,
					},
				},
				ShimmerView(
					{ animating },
					View({
						style: {
							width: 52,
							height: 52,
							justifyContent: 'center',
							alignItems: 'center',
							borderRadius: 4,
							backgroundColor: AppTheme.colors.base6,
						},
					}),
				),
			);
		}

		renderProductContent(even)
		{
			return View(
				{
					style: {
						flexGrow: 1,
						flexShrink: 1,
						width: 0,
					},
				},
				this.renderName(even),
				this.renderContextMenu(),
				this.renderInnerContent(even),
			);
		}

		renderName(even)
		{
			return View(
				{
					style: {
						paddingRight: 40,
						marginBottom: 14,
					},
				},
				this.renderLine(even ? 182 : 129, 6, 8, 6),
			);
		}

		renderContextMenu()
		{
			return View(
				{
					style: {
						position: 'absolute',
						right: -8,
						top: 0,
						width: 40,
						height: 40,
						alignItems: 'flex-end',
						justifyContent: 'center',
					},
				},
				this.renderCircle(19),
			);
		}

		renderInnerContent(even)
		{
			return View(
				{},
				this.renderSkuTree(even),
				this.renderProductPricing(),
				this.renderProductTotals(),
			);
		}

		renderSkuTree(even)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						marginBottom: 13,
					},
				},
				this.renderLine(even ? 122 : 77, 4, 9, 18),
				this.renderLine(53, 4, 7, 18),
			);
		}

		renderProductPricing()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						marginBottom: 10,
					},
				},
				this.renderInput(0, 13, AppTheme.colors.base6),
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
						},
					},
					this.renderCircle(26, 4, 18, 0, 5),
					this.renderInput(),
					this.renderCircle(26, 4, 18, 5, 0),
				),
			);
		}

		renderProductTotals()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
					},
				},
				this.renderInput(0, 8, AppTheme.colors.base6),
				this.renderInput(8, 0, AppTheme.colors.base6),
			);
		}

		renderInput(marginLeft = 0, marginRight = 0, backgroundColor = AppTheme.colors.base6)
		{
			const { animating } = this.props;

			return View(
				{
					style: {
						flex: 1,
						marginLeft,
						marginRight,
					},
				},
				ShimmerView(
					{ animating },
					View({
						style: {
							flex: 1,
							height: 34,
							backgroundColor,
							borderRadius: 4,
						},
					}),
				),
			);
		}

		renderDeleteButton()
		{
			return View(
				{
					style: {
						position: 'absolute',
						left: 7,
						bottom: 0,
						paddingHorizontal: 10,
					},
				},
				this.renderCircle(15),
			);
		}

		renderSummary()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderRadius: 12,
						padding: 12,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								justifyContent: 'flex-start',
							},
						},
						this.renderLine(100, 6, 10, 6),
					),
					View(
						{
							style: {
								flexDirection: 'column',
								justifyContent: 'flex-end',
								alignItems: 'flex-end',
							},
						},
						this.renderLine(133, 6, 10, 6),
						this.renderLine(72, 3, 10, 6),
					),
				),
			);
		}
	}

	module.exports = { CrmProductTabShimmer };
});


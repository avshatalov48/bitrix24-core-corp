/**
 * @module layout/ui/detail-card/tabs/shimmer
 */
jn.define('layout/ui/detail-card/tabs/shimmer', (require, exports, module) => {
	const { ShimmerView } = require('layout/polyfill');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @abstract
	 * @class BaseShimmer
	 */
	class BaseShimmer extends PureComponent
	{
		render()
		{
			return View(
				{
					style: {
						position: 'absolute',
						top: 0,
						left: 0,
						right: 0,
						display: 'flex',
					},
				},
				this.renderContent(),
			);
		}

		/**
		 * @abstract
		 */
		renderContent()
		{
			return null;
		}

		renderCircle(size = 18, marginTop = 0, marginVertical = 18, marginLeft = 0, marginRight = 10)
		{
			const { animating } = this.props;

			return View(
				{
					style: {
						height: size,
						width: size,
						marginVertical,
						marginTop,
						marginLeft,
						marginRight,
					},
				},
				ShimmerView(
					{ animating },
					View({
						style: {
							height: size,
							width: size,
							borderRadius: size / 2,
							backgroundColor: '#dfe0e3',
							position: 'relative',
							left: 0,
							top: 0,
						},
					}),
				),
			);
		}

		renderLine(width, height, marginTop = 0, marginBottom = 0)
		{
			const { animating } = this.props;
			const innerViewStyle = {
				width,
				height,
				borderRadius: height / 2,
				backgroundColor: '#dfe0e3',
			};
			const outerViewStyle = {
				width,
				height,
			};

			if (marginTop)
			{
				outerViewStyle.marginTop = marginTop;
			}
			if (marginBottom)
			{
				outerViewStyle.marginBottom = marginBottom;
			}

			return View(
				{
					style: outerViewStyle,
				},
				ShimmerView(
					{ animating },
					View({ style: innerViewStyle }),
				),
			);
		}
	}

	module.exports = { BaseShimmer };
});

/**
 * @module ui-system/blocks/banners/safety-banner/src/progress-banner
 */
jn.define('ui-system/blocks/banners/safety-banner/src/progress-banner', (require, exports, module) => {
	const { Indent, Color, Component } = require('tokens');
	const { PropTypes } = require('utils/validation');
	const { SafeImage } = require('layout/ui/safe-image');
	const { Text6, H5 } = require('ui-system/typography');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @typedef {Object} CardBannerProps
	 * @property {string} [testId]
	 * @property {string} [title]
	 * @property {string} [description]
	 * @property {string} [imageUrl]
	 * @property {number} [progressPercent]
	 *
	 * @function ProgressSafetyBanner
	 */
	class ProgressSafetyBanner extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: 'progress-banner',
				context: this,
			});
		}

		render()
		{
			return View(
				{
					style: {
						paddingTop: Indent.XL4.toNumber(),
						paddingHorizontal: Indent.XL3.toNumber(),
						paddingBottom: Indent.XL3.toNumber(),
					},
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							flex: 1,
						},
					},
					this.renderImage(),
				),
				View(
					{
						style: {
							flex: 2,
							marginLeft: Indent.XL3.toNumber(),
						},
					},
					this.renderTitle(),
					this.renderProgressBar(),
					this.renderDescription(),
				),
			);
		}

		renderTitle()
		{
			const { title } = this.props;

			return H5({
				text: title,
				color: Color.base1,
			});
		}

		renderDescription()
		{
			const { description } = this.props;

			return Text6({
				style: {
					marginTop: Indent.L.toNumber(),
				},
				text: description,
				color: Color.base3,
			});
		}

		renderProgressBar()
		{
			const progressPercent = this.#getProgressPercent();
			const progressColor = progressPercent < 100
				? Color.accentSoftBorderBlue
				: Color.accentMainSuccess;

			return View(
				{
					style: {
						position: 'relative',
						height: 6,
						marginTop: Indent.S.toNumber(),
						backgroundColor: Color.baseWhiteFixed.toHex(),
						borderRadius: Component.elementAccentCorner.toNumber(),
					},
				},
				View({
					style: {
						position: 'absolute',
						left: 0,
						top: 0,
						height: 6,
						width: `${progressPercent}%`,
						backgroundColor: progressColor.toHex(),
						borderRadius: Component.elementAccentCorner.toNumber(),
					},
				}),
			);
		}

		renderImage()
		{
			const { imageUrl } = this.props;

			return SafeImage({
				uri: imageUrl,
				testId: this.getTestId('image'),
				resizeMode: 'contain',
				style: {
					height: 108,
					width: 108,
				},
			});
		}

		/**
		 * @returns {number}
		 */
		#getProgressPercent()
		{
			const { progressSize, progressCount } = this.props;

			return progressSize > 0
				? Math.round((progressCount / progressSize) * 100)
				: 0;
		}
	}

	ProgressSafetyBanner.defaultProps = {
		progressPercent: 0,
	};

	ProgressSafetyBanner.propTypes = {
		testId: PropTypes.string.isRequired,
		title: PropTypes.string,
		description: PropTypes.string,
		progressSize: PropTypes.number,
		progressCount: PropTypes.number,
	};

	module.exports = {
		/**
		 * @param {CardBannerProps} props
		 */
		ProgressSafetyBanner: (props) => new ProgressSafetyBanner(props),
	};
});

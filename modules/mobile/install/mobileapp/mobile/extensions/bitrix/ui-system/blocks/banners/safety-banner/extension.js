/**
 * @module ui-system/blocks/banners/safety-banner
 */
jn.define('ui-system/blocks/banners/safety-banner', (require, exports, module) => {
	const { SafetyType } = require('ui-system/blocks/banners/safety-banner/src/enums/safety-enum');
	const { ProgressSafetyBanner } = require('ui-system/blocks/banners/safety-banner/src/progress-banner');
	const { CenterSafetyBanner } = require('ui-system/blocks/banners/safety-banner/src/center-banner');
	const { PropTypes } = require('utils/validation');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @typedef {Object} SafetyBannerProps
	 * @property {string} [testId]
	 * @property {boolean} [progress=false]
	 * @property {string} [description]
	 * @property {number} [progressSize]
	 * @property {number} [progressCount]
	 * @property {SafetyType} [safetyType]
	 *
	 * @function SafetyBanner
	 */
	class SafetyBanner extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: 'safety-banner',
				context: this,
			});
		}

		render()
		{
			return this.#isProgress()
				? this.#renderProgressMode()
				: this.#renderCenterMode();
		}

		/**
		 * @return {boolean}
		 */
		#isProgress()
		{
			const { progress } = this.props;

			return Boolean(progress);
		}

		#renderCenterMode()
		{
			return CenterSafetyBanner({
				testId: this.getTestId('center-mode'),
				safetyType: this.#getSafetyType(),
				description: this.#getDescription(),
			});
		}

		#renderProgressMode()
		{
			return ProgressSafetyBanner({
				testId: this.getTestId('progress-mode'),
				title: this.#getTitle(),
				description: this.#getDescription(),
				imageUrl: this.#getSafetyType().getImageUri(),
				progressSize: this.#getProgressSize(),
				progressCount: this.#getProgressCount(),
			});
		}

		/**
		 * @returns {number}
		 */
		#getProgressCount()
		{
			const { progressCount } = this.props;

			return Number(progressCount);
		}

		/**
		 * @returns {number}
		 */
		#getProgressSize()
		{
			const { progressSize } = this.props;

			return Number(progressSize);
		}

		/**
		 * @returns {SafetyType}
		 */
		#getSafetyType()
		{
			const { safetyType } = this.props;

			return SafetyType.resolve(safetyType, SafetyType.SUCCESS);
		}

		/**
		 * @returns {string}
		 */
		#getTitle()
		{
			const { title } = this.props;

			return title;
		}

		/**
		 * @returns {string}
		 */
		#getDescription()
		{
			const { description } = this.props;

			return description;
		}
	}

	SafetyBanner.defaultProps = {
		progress: false,
	};

	SafetyBanner.propTypes = {
		testId: PropTypes.string.isRequired,
		progress: PropTypes.bool,
		title: PropTypes.string,
		description: PropTypes.string,
		safetyType: PropTypes.instanceOf(SafetyType),
		progressSize: PropTypes.number,
		progressCount: PropTypes.number,
	};

	module.exports = {
		/**
		 * @param {SafetyBannerProps} props
		 * @returns {SafetyBanner}
		 */
		SafetyBanner: (props) => new SafetyBanner(props),
		SafetyType,
	};
});

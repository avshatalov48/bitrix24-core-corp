/**
 * @module layout/ui/banners/security
 */
jn.define('layout/ui/banners/security', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { SafetyBanner, SafetyType } = require('ui-system/blocks/banners/safety-banner');

	/**
	 * @param {boolean} progress
	 * @param {SafetyType} safetyType
	 * @param {number} progressCount
	 * @param {number} progressSize
	 * @return {SafetyBanner}
	 */
	class SecurityBanner extends LayoutComponent
	{
		render()
		{
			const safetyType = this.#getSafetyType();
			const progress = this.#isProgress();

			const props = {
				safetyType,
				progress,
				testId: this.#getTestId(),
				progressSize: this.#getProgressSize(),
				progressCount: this.#getProgressCount(),
				description: this.#getDescription(safetyType),
			};

			if (progress)
			{
				props.title = this.#getTitle();
			}

			return SafetyBanner(props);
		}

		#getTestId()
		{
			const { testId } = this.props;

			return testId ?? 'security-banner';
		}

		/**
		 * @returns {number}
		 */
		#getProgressSize()
		{
			const { progressSize } = this.props;

			return Type.isNumber(progressSize) ? progressSize : 0;
		}

		/**
		 * @returns {number}
		 */
		#getProgressCount()
		{
			const { progressCount } = this.props;
			const progressSize = this.#getProgressSize();

			if (progressCount >= progressSize)
			{
				return progressSize;
			}

			return progressCount;
		}

		/**
		 * @returns {SafetyType}
		 */
		#getSafetyType()
		{
			const { progress, safetyType } = this.props;
			if (!progress)
			{
				return safetyType ?? SafetyType.SUCCESS;
			}

			const progressCount = this.#getProgressCount();
			const progressSize = this.#getProgressSize();

			if (progressCount === 0)
			{
				return SafetyType.WARNING;
			}

			if (progressCount < progressSize)
			{
				return SafetyType.ATTENTION;
			}

			return SafetyType.SUCCESS;
		}

		/**
		 * @param safetyType
		 * @returns {string}
		 */
		#getDescription(safetyType)
		{
			const descriptionTypeMap = {
				[SafetyType.SUCCESS]: Loc.getMessage('SAFETY_BANNER_CENTER_MODE_DESCRIPTION'),
				[SafetyType.ATTENTION]: Loc.getMessage('SAFETY_BANNER_PROGRESS_MODE_SUCCESS_DESCRIPTION'),
				[SafetyType.WARNING]: Loc.getMessage('SAFETY_BANNER_PROGRESS_MODE_WARNING_DESCRIPTION'),
			};

			return descriptionTypeMap[safetyType];
		}

		#isProgress()
		{
			const { progress } = this.props;

			return Boolean(progress);
		}

		#getTitle()
		{
			return Loc.getMessage('SAFETY_BANNER_PROGRESS_MODE_DEFENCE_LEVEL', {
				'#PROGRESS_COUNT#': String(this.#getProgressCount()),
				'#PROGRESS_SIZE#': String(this.#getProgressSize()),
			});
		}
	}

	module.exports = {
		/**
		 * @returns {SecurityBanner}
		 */
		SecurityBanner: (props) => new SecurityBanner(props),
		SafetyType,
	};
});

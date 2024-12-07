/**
 * @module tasks/layout/checklist/list/src/checkbox/checkbox-counter/progress
 */
jn.define('tasks/layout/checklist/list/src/checkbox/checkbox-counter/progress', (require, exports, module) => {
	const { Color } = require('tokens');
	const { PureComponent } = require('layout/pure-component');
	const { CHECKBOX_SIZE } = require('tasks/layout/checklist/list/src/constants');

	/**
	 * @class ChecklistCheckboxProgress
	 */
	class ChecklistCheckboxProgress extends PureComponent
	{
		/**
		 * @param {object} props
		 * @param {function} [props.onClick]
		 * @param {number} [props.totalCount]
		 * @param {number} [props.completedCount]
		 */
		constructor(props)
		{
			super(props);
			this.progressRef = null;
			this.currentProgress = 0;

			this.handleOnClick = this.handleOnClick.bind(this);
		}

		handleOnClick()
		{
			const { onClick } = this.props;

			if (onClick)
			{
				onClick();
			}
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			void this.setProgress(nextProps);

			return false;
		}

		/**
		 * @returns {Promise}
		 */
		setProgress(props)
		{
			const { completedCount, totalCount } = props;

			const calculateCurrentPercent = this.calculateCurrentPercent(completedCount, totalCount);

			if (!this.progressRef || calculateCurrentPercent === this.currentProgress)
			{
				return Promise.resolve();
			}

			this.currentProgress = calculateCurrentPercent;

			return new Promise((resolve) => {
				this.progressRef.setProgress(
					calculateCurrentPercent,
					{
						duration: 500,
						style: 'linear',
					},
					resolve,
				);
			});
		}

		/**
		 * @param {number} completedCount
		 * @param {number} totalCount
		 * @return {number} percent
		 */
		calculateCurrentPercent(completedCount, totalCount)
		{
			return parseInt(
				(completedCount > 0 ? (completedCount * 100 / totalCount) : 0).toFixed(0),
				10,
			);
		}

		/**
		 * @private
		 * @returns {ProgressView}
		 */
		render()
		{
			const { totalCount, completedCount } = this.props;
			const calculateCurrentPercent = this.calculateCurrentPercent(completedCount, totalCount);

			return ProgressView(
				{
					ref: (ref) => {
						this.progressRef = ref;
					},
					params: {
						type: 'circle',
						currentPercent: calculateCurrentPercent,
						color: Color.accentExtraDarkblue.toHex(),
					},
					style: {
						width: CHECKBOX_SIZE,
						height: CHECKBOX_SIZE,
						justifyContent: 'center',
						alignItems: 'center',
						backgroundColor: Color.base5.toHex(),
					},
					onClick: this.handleOnClick,
				},
				View(
					{
						style: {
							position: 'absolute',
							width: 20,
							height: 20,
							justifyContent: 'center',
							alignItems: 'center',
							borderRadius: 10,
							backgroundColor: Color.bgContentPrimary.toHex(),
						},
					},
				),
			);
		}
	}

	module.exports = { ChecklistCheckboxProgress };
});

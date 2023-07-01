/**
 * @module layout/ui/wizard/step
 */

jn.define('layout/ui/wizard/step', (require, exports, module) => {
	/**
	 * @class WizardStep
	 */
	class WizardStep
	{
		constructor(props)
		{
			this.props = props;
			this.titleChangeCallback = () => {
			};
			this.stepAvailabilityChangeCallback = () => {
			};
		}

		renderNumberBlock()
		{
			return null;
		}

		getProgressBarSettings()
		{
			return {
				isEnabled: false,
				number: 1,
				count: 1,
				title: {},
				previousLineColor: '#9dcf00',
				currentLineColor: '#55d0e0',
				nextLineColor: '#d5d7db',
			};
		}

		/**
		 * Create step layout object
		 *
		 * @returns {View}
		 */
		createLayout(props)
		{
			return null;
		}

		/**
		 * First line of step title
		 *
		 * @returns {string}
		 */
		getTitle()
		{
			return '';
		}

		/**
		 * Second line of step title
		 *
		 * @returns {string}
		 */
		getSubTitle()
		{
			return '';
		}

		/**
		 * Should wizard "previous step" button be enabled?
		 *
		 * @returns {boolean}
		 */
		isPrevStepEnabled()
		{
			return true;
		}

		/**
		 * Should wizard "next step" button be enabled?
		 *
		 * @returns {boolean}
		 */
		isNextStepEnabled()
		{
			return true;
		}

		isNeedToShowNextStep()
		{
			return true;
		}

		isNeedToSkip()
		{
			return false;
		}

		/**
		 * Wizard "next step" button text
		 *
		 * @returns {*}
		 */
		getNextStepButtonText()
		{
			return BX.message('WIZARD_STEP_BUTTON_CONTINUE_TEXT');
		}

		/**
		 * Set title change handler.
		 * If step title changed, this handler will be executed
		 *
		 * @param callback
		 * @returns {WizardStep}
		 */
		setTitleChangeHandler(callback)
		{
			this.titleChangeCallback = callback;

			return this;
		}

		/**
		 * Set step availability change handler.
		 * If prev/next buttons should change enabled/disabled state, this handler will be executed
		 *
		 * @param callback
		 * @returns {WizardStep}
		 */
		setStepAvailabilityChangeHandler(callback)
		{
			this.stepAvailabilityChangeCallback = callback;

			return this;
		}

		/**
		 * Executed when user tries to move wizard to next step.
		 * Can return
		 *  - true: allow move to next step
		 *  - false: deny move to next step
		 *  - promise: will the wizard be moved to next step or not, depends on promise resolve/reject
		 *
		 * @returns {boolean|Promise}
		 */
		onMoveToNextStep()
		{
			return true;
		}

		/**
		 * Executed when the user has passed the last step
		 */
		onFinishStep()
		{
		}

		/**
		 * Callback executed when wizard enter this step
		 * @param stepId
		 */
		onEnterStep()
		{
		}

		/**
		 * Callback executed when wizard leave this step
		 * @param stepId
		 */
		onLeaveStep(stepId)
		{
		}
	}

	module.exports = { WizardStep };
});

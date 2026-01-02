/**
 * @module intranet/qualification/analytics-handler
 */
jn.define('intranet/qualification/analytics-handler', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	class AnalyticsHandler
	{
		constructor()
		{
			/** @type {Map<string,number>} */
			this.enterStepTime = new Map();
		}

		/**
		 * @param {Wizard} wizard
		 */
		setWizard(wizard)
		{
			this.wizard = wizard;
		}

		/**
		 * @param {string} qualificationId
		 */
		setQualificationId(qualificationId)
		{
			this.qualificationId = qualificationId;
		}

		/**
		 * @param {string} stepId
		 * @return {Step}
		 */
		#getStep(stepId)
		{
			return this.wizard.getStepById(stepId);
		}

		#getStepNumber(stepId)
		{
			return this.wizard.getStepIndexById(stepId) + 1;
		}

		#getStepFieldsAnalyticsData(stepId)
		{
			return this.#getStep(stepId).getFieldsAnalyticsData();
		}

		#getTimeSpent(stepId)
		{
			return Number((Date.now() - this.enterStepTime.get(stepId)) / 1000);
		}

		#isRequiredStep(stepId)
		{
			return this.#getStep(stepId).canSkip() ? 'false' : 'true';
		}

		/**
		 * @param {string} stepId
		 * @return {AnalyticsEvent}
		 */
		#getBasicEvent(stepId)
		{
			return new AnalyticsEvent()
				.setTool('qualification')
				.setCategory(this.qualificationId)
				.setType(stepId)
				.setP1(`StepNumber_${this.#getStepNumber(stepId)}`)
				.setP3(`StepTime_${this.#getTimeSpent(stepId)}`)
				.setP4(`RequiredStep_${this.#isRequiredStep(stepId)}`)
			;
		}

		onEnterStep(stepId)
		{
			this.enterStepTime.set(stepId, Date.now());
		}

		handleShowStepEvent(stepId)
		{
			this.#getBasicEvent(stepId)
				.setEvent('render_step')
				.send()
			;
		}

		handleContinueEvent(stepId)
		{
			this.#getBasicEvent(stepId)
				.setEvent('save')
				.setP2(`StepData_${this.#getStepFieldsAnalyticsData(stepId)}`)
				.send()
			;
		}

		handleBackEvent(stepId)
		{
			this.#getBasicEvent(stepId)
				.setEvent('back')
				.send()
			;
		}

		handleSkipEvent(stepId)
		{
			this.#getBasicEvent(stepId)
				.setEvent('skip')
				.send()
			;
		}

		handleCloseWizardEvent(stepId)
		{
			this.#getBasicEvent(stepId)
				.setEvent('close')
				.send()
			;
		}

		handleSetPresetEvent(stepId, presetId)
		{
			this.#getBasicEvent(stepId)
				.setEvent('main_tool_selection')
				.setType(presetId)
				.setStatus('success')
				.send()
			;
		}
	}

	module.exports = {
		AnalyticsHandler: new AnalyticsHandler(),
	};
});

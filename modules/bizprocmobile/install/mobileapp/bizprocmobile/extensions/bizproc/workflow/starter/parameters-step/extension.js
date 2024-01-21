/**
 * @module bizproc/workflow/starter/parameters-step
 */
jn.define('bizproc/workflow/starter/parameters-step', (require, exports, module) => {
	const { Loc } = require('loc');
	const { EventEmitter } = require('event-emitter');
	const { NotifyManager } = require('notify-manager');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { ProgressBarNumber } = require('bizproc/wizard/progress-bar-number');
	const { ParametersStepComponent } = require('bizproc/workflow/starter/parameters-step/component');

	class ParametersStep extends WizardStep
	{
		constructor(props)
		{
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.templateId = props.templateId || null;
			this.prevTemplateId = null;

			this.editorConfig = null;
			this.prevEditorConfig = null;

			this.component = null;
			this.componentRefCallback = (ref) => {
				this.component = ref;
			};

			this.subscribeOnEvents();
		}

		subscribeOnEvents()
		{
			this.customEventEmitter
				.on('ParametersStepComponent:OnAfterLoadEditor', ({ editorConfig, hasErrors }) => {
					this.editorConfig = editorConfig;
					if (this.prevEditorConfig === null)
					{
						this.prevEditorConfig = this.editorConfig;
					}

					this.stepAvailabilityChangeCallback(!hasErrors);

					if (hasErrors)
					{
						this.editorConfig = null;
						this.prevEditorConfig = null;
					}
				})
				.on('ParametersStepComponent:OnFieldChangeState', () => {
					this.customEventEmitter.emit('ParametersStep:OnFieldChangeState');
				})
			;
		}

		get isLoaded()
		{
			return this.editorConfig !== null;
		}

		updateProps(props)
		{
			if (this.isLoaded)
			{
				this.prevTemplateId = this.templateId;
				this.prevEditorConfig = this.editorConfig;

				this.editorConfig = null;
			}

			this.templateId = props.templateId === undefined ? this.templateId : props.templateId;
		}

		get isPreviousSelectedTemplateId()
		{
			return this.prevTemplateId === this.templateId;
		}

		onEnterStep()
		{
			super.onEnterStep();

			if (this.isPreviousSelectedTemplateId)
			{
				this.editorConfig = this.prevEditorConfig;
				this.stepAvailabilityChangeCallback(true);
			}

			if (!this.isLoaded)
			{
				this.stepAvailabilityChangeCallback(false);
			}
		}

		async onMoveToBackStep(stepId)
		{
			await NotifyManager.showLoadingIndicator();

			return new Promise((resolve) => {
				this.prevTemplateId = this.templateId;
				this.prevEditorConfig = this.editorConfig;

				this.editorConfig = null;

				this.component.getData()
					.then((parameters) => {
						if (this.prevEditorConfig)
						{
							this.prevEditorConfig.ENTITY_DATA = Object.assign(this.prevEditorConfig.ENTITY_DATA, parameters);
						}
					})
					.catch((errors) => {
						console.error(errors);
					})
					.finally(() => {
						NotifyManager.hideLoadingIndicator(true);
						resolve();
					})
				;
			});
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				isCompleted: false,
				number: this.props.stepNumber,
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: { text: Loc.getMessage('M_BP_STARTER_PARAMETERS_STEP_TITLE') },
				number: this.props.stepNumber,
				count: this.props.totalSteps,
			};
		}

		getTitle()
		{
			return this.props.title || '';
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('M_BP_STARTER_PARAMETERS_STEP_FINISH_BUTTON_TEXT');
		}

		isNextStepEnabled()
		{
			return this.isLoaded;
		}

		isPrevStepEnabled()
		{
			return this.isLoaded;
		}

		async onMoveToNextStep(stepId)
		{
			await NotifyManager.showLoadingIndicator();

			return new Promise((resolve) => {
				FocusManager.blurFocusedFieldIfHas()
					.then(() => this.component.validate())
					.then(() => this.component.getData())
					.then((parameters) => this.startWorkflow(parameters))
					.then(() => {
						NotifyManager.hideLoadingIndicator(
							true,
							Loc.getMessage('M_BP_STARTER_PARAMETERS_STEP_SUCCESS_MESSAGE'),
							1500,
						);
						setTimeout(() => resolve({ finish: true, next: true }), 1500);
					})
					.catch((errors) => {
						NotifyManager.hideLoadingIndicator(false);
						console.error(errors);
						if (Array.isArray(errors))
						{
							NotifyManager.showErrors(errors);
						}

						resolve({ finish: false, next: false });
					})
				;
			});
		}

		startWorkflow(parameters)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'bizprocmobile.Workflow.start',
					{
						data: {
							signedDocument: this.props.signedDocument,
							templateId: this.templateId,
							parameters,
						},
					},
				)
					.then(() => resolve())
					.catch((response) => reject(response.errors))
				;
			});
		}

		createLayout(props)
		{
			return new ParametersStepComponent({
				layout: this.props.layout,
				uid: this.uid,
				templateId: this.templateId,
				signedDocument: this.props.signedDocument,
				editorConfig: this.isPreviousSelectedTemplateId ? this.prevEditorConfig : this.editorConfig,
				ref: this.componentRefCallback,
			});
		}
	}

	module.exports = { ParametersStep };
});

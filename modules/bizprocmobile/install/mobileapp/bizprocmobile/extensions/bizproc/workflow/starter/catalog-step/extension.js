/**
 * @module bizproc/workflow/starter/catalog-step
 */
jn.define('bizproc/workflow/starter/catalog-step', (require, exports, module) => {
	const { Loc } = require('loc');
	const { EventEmitter } = require('event-emitter');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ProgressBarNumber } = require('bizproc/wizard/progress-bar-number');
	const { CatalogStepComponent } = require('bizproc/workflow/starter/catalog-step/component');

	class CatalogStep extends WizardStep
	{
		constructor(props)
		{
			super(props);

			this.selectedTemplate = null;

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.customEventEmitter.on('CatalogStepComponent:OnSelectTemplate', (template) => {
				this.selectedTemplate = template;
				this.customEventEmitter.emit('CatalogStep:OnSelectTemplate', [this.selectedTemplate]);
				this.stepAvailabilityChangeCallback(true);
			});
		}

		get hasSelectedTemplate()
		{
			return this.selectedTemplate !== null;
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				isCompleted: this.hasSelectedTemplate,
				number: this.props.stepNumber,
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: { text: Loc.getMessage('M_BP_WORKFLOW_STARTER_CATALOG_STEP_TITLE') },
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
			return Loc.getMessage('M_BP_WORKFLOW_STARTER_CATALOG_STEP_NEXT_STEP_BUTTON_TEXT');
		}

		isNextStepEnabled()
		{
			return this.hasSelectedTemplate;
		}

		isPrevStepEnabled()
		{
			return false;
		}

		createLayout(props)
		{
			return new CatalogStepComponent({
				uid: this.uid,
				selectedTemplate: this.selectedTemplate,
				layout: this.props.layout,
				signedDocument: this.props.signedDocument,
				documentType: this.props.documentType,
			});
		}
	}

	module.exports = { CatalogStep };
});

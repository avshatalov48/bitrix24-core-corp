/**
 * @module crm/conversion/wizard/steps/entities
 */
jn.define('crm/conversion/wizard/steps/entities', (require, exports, module) => {
	const { Loc } = require('loc');
	const { getEntityMessage } = require('crm/loc');
	const { NotifyManager } = require('notify-manager');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ConversionWizardEntitiesLayout } = require('crm/conversion/wizard/layouts');
	const ENTITIES = 'entities-step';

	/**
	 * @class ConversionWizardEntitiesStep
	 */
	class ConversionWizardEntitiesStep extends WizardStep
	{
		static getId()
		{
			return ENTITIES;
		}

		getTitle()
		{
			const { entityTypeId } = this.props;

			return getEntityMessage('MCRM_CONVERSION_WIZARD_STEPS_ENTITIES_TITLE', entityTypeId);
		}

		getMediumPositionHeight()
		{
			const margins = 80;
			const lineMargins = 10;
			const buttonHeight = 56;
			const booleanFieldHeight = 52;
			const { permissions, isReturnCustomer, entityTypeId, entityTypeIds, ConversionSelector } = this.props;
			const hasConversionSelectorMenuButton = ConversionSelector.hasConversionSelectorMenuButton({
				entityTypeId,
				permissions,
				isReturnCustomer,
			});
			const buttonsCount = hasConversionSelectorMenuButton ? 1 : 0;
			const entityTypesHeight = (entityTypeIds.length * booleanFieldHeight) + (entityTypeIds.length * lineMargins);
			const buttonsHeight = (buttonsCount * buttonHeight) + (buttonsCount * lineMargins);

			return margins + entityTypesHeight + buttonsHeight;
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('MCRM_CONVERSION_WIZARD_STEPS_NEXT_BUTTON');
		}

		onMoveToNextStep(stepId)
		{
			return this.props.onMoveToNextStep(stepId);
		}

		onMoveToBackStep()
		{
			return this.props.onMoveToBackStep();
		}

		onLeaveStep(stepId)
		{
			if (ENTITIES === stepId)
			{
				NotifyManager.hideLoadingIndicatorWithoutFallback();
			}
		}

		createLayout()
		{
			return new ConversionWizardEntitiesLayout(this.props);
		}
	}

	module.exports = { ConversionWizardEntitiesStep, ENTITIES };
});

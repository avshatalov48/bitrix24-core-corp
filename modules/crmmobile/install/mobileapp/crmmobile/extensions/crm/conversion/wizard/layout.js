/**
 * @module crm/conversion/wizard/layout
 */
jn.define('crm/conversion/wizard/layout', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BackdropHeader } = require('layout/ui/banners');
	const { WizardFields } = require('crm/conversion/wizard/fields');

	const EXTENSION_PATH = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/conversion/wizard/images/`;
	const ARROW_ICON = (opacity = 0.5) => `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M5.44043 4.22895L8.45846 7.24699L9.24018 7.9999L8.45846 8.75326L5.44043 11.7713L6.50541 12.8363L11.3415 8.00017L6.50541 3.16406L5.44043 4.22895Z" fill="#525C69" fill-opacity="${opacity}"/>
						</svg>`;

	/**
	 * @class ConversionWizardLayout
	 */
	class ConversionWizardLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.fields = props.fields;
			this.layoutWidget = null;
		}

		renderStep(stepId, enable)
		{
			const enableStyle = {
				color: '#2066B0',
				fontWeight: '600',
			};

			const disableStyle = {
				color: '#D5D7DB',
				fontWeight: '500',
			};

			const style = enable ? enableStyle : disableStyle;
			const stepNumber = this.getStepNumber(stepId);

			return Text({
				style: {
					fontSize: 13,
					...style,
				},
				text: Loc.getMessage(
					'MCRM_CONVERSION_WIZARD_LAYOUT_STEP',
					{ '#NUMBER#': stepNumber },
				),
			});
		}

		getStepIndex(stepId)
		{
			const { stepsIds } = this.props;

			return stepsIds.indexOf(stepId);
		}

		getStepNumber(stepId)
		{
			const stepIndex = this.getStepIndex(stepId);

			return stepIndex + 1;
		}

		getNextStepId(stepId)
		{
			const { stepsIds } = this.props;
			const stepIndex = this.getStepIndex(stepId);
			const nextIndex = stepIndex + 1;
			const nextStepIndex = nextIndex >= stepsIds.length ? stepsIds.length : nextIndex;

			return stepsIds[nextStepIndex];
		}

		getPrevStepId(stepId)
		{
			const { stepsIds } = this.props;
			const stepIndex = this.getStepIndex(stepId);

			return stepsIds[stepIndex - 1];
		}

		isEnable(id)
		{
			const { stepId } = this.props;

			return id === stepId;
		}

		renderSteps(stepId)
		{
			const { finalStep } = this.props;
			let currentStepId = stepId;
			let nextStepId = this.getNextStepId(stepId);

			if (finalStep)
			{
				currentStepId = this.getPrevStepId(stepId);
				nextStepId = stepId;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: 3,
						marginBottom: 12,
					},
				},
				this.renderStep(currentStepId, this.isEnable(currentStepId)),
				Image({
					resizeMode: 'cover',
					style: {
						width: 16,
						height: 16,
						marginHorizontal: 7,
					},
					svg: {
						content: ARROW_ICON(),
					},
				}),
				this.renderStep(nextStepId, this.isEnable(nextStepId)),
			);
		}

		render()
		{
			const { type, stepId, onChange } = this.props;

			return View(
				{
					style: {
						backgroundColor: '#EEF2F4',
					},
				},
				View(
					{
						style: {
							marginBottom: 12,
							borderRadius: 12,
						},
					},
					BackdropHeader({
						title: Loc.getMessage(`MCRM_CONVERSION_WIZARD_LAYOUT_${stepId.toUpperCase()}_TITLE`),
						description: Loc.getMessage(`MCRM_CONVERSION_WIZARD_LAYOUT_${stepId.toUpperCase()}_DESCRIPTION`),
						image: `${EXTENSION_PATH}/step_${stepId.toLowerCase()}.png`,
						additionalInfo: this.renderSteps(stepId),
						position: 'flex-start',
					}),
				),
				View(
					{
						style: {
							backgroundColor: '#ffffff',
							paddingHorizontal: 16,
							paddingVertical: 10,
							borderRadius: 12,
						},
					},
					new WizardFields({
						type,
						fields: this.fields,
						onChange: (value) => {
							if (onChange)
							{
								onChange(stepId, value);
							}
						},
					}),
				),
			);
		}
	}

	module.exports = { ConversionWizardLayout };
});

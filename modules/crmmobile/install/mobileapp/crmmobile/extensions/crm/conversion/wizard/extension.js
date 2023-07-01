/**
 * @module crm/conversion/wizard
 */
jn.define('crm/conversion/wizard', (require, exports, module) => {
	const { ConversionWizardStep } = require('crm/conversion/wizard/step');
	const { BackdropWizard } = require('layout/ui/wizard/backdrop');
	const { showLandingWizard } = require('crm/conversion/wizard/landing');
	const { last, sortBy } = require('utils/array');

	/**
	 * @class ConversionWizard
	 */
	class ConversionWizard
	{
		constructor(props)
		{
			this.props = props;
			this.handleOnSelectedFields = this.handleOnSelectedFields.bind(this);
			this.handleOnFinish = this.handleOnFinish.bind(this);
			this.fieldsData = sortBy(props.data, 'sort');
			this.stepsIds = this.fieldsData.map(({ id }) => id);
			this.steps = this.createSteps();
			this.selectedFields = {};

			this.fieldsData.forEach(({ id, data }) => {
				this.selectedFields[id] = data.map(({ id: dataId }) => dataId);
			});
		}

		handleOnSelectedFields(stepId)
		{
			return (value) => {
				this.selectedFields[stepId] = value;
			};
		}

		handleOnFinish()
		{
			const { onFinish } = this.props;

			onFinish({ ...this.selectedFields, enableSynchronization: true });
		}

		getSteps()
		{
			return this.steps;
		}

		createSteps()
		{
			return this.fieldsData.map(({ id, type, data }) => ({
				id,
				step: new ConversionWizardStep({
					type,
					stepId: id,
					fields: data,
					stepsIds: this.stepsIds,
					finalStep: id === last(this.stepsIds),
					onChange: this.handleOnSelectedFields(id),
					onFinish: this.handleOnFinish,
				}),
			}));
		}

		getLandingProps()
		{
			const { entityTypeId } = this.props;

			return {
				data: this.fieldsData.map(({ id, type, data }) => ({
					id,
					type,
					fields: data,
					onChange: this.handleOnSelectedFields(id),
				})),
				entityTypeId,
				onFinish: this.handleOnFinish,
			};
		}

		static open(props)
		{
			const { isLanding, ...restProps } = props;
			const conversionWizard = new ConversionWizard(restProps);

			if (isLanding)
			{
				return showLandingWizard(conversionWizard.getLandingProps());
			}

			return BackdropWizard.open({
				steps: conversionWizard.getSteps(),
			});
		}
	}

	module.exports = { ConversionWizard };
});

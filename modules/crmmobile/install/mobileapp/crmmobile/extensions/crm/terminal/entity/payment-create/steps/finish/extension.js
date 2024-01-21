/**
 * @module crm/terminal/entity/payment-create/steps/finish
 */
jn.define('crm/terminal/entity/payment-create/steps/finish', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Random } = require('utils/random');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { Layout } = require('crm/terminal/entity/payment-create/steps/finish/layout');

	/**
	 * @class FinishStep
	 */
	class FinishStep extends WizardStep
	{
		constructor(props)
		{
			super(props);

			this.uid = props.uid || Random.getString();
		}

		getTitle()
		{
			return Loc.getMessage('M_CRM_TL_EPC_FINISH_STEP_TITLE');
		}

		isNeedToShowNextStep()
		{
			return false;
		}

		isPrevStepEnabled()
		{
			return false;
		}

		isNavigationBarBorderEnabled()
		{
			return false;
		}

		onEnterStep()
		{
			super.onEnterStep();

			const analytics = BX.prop.getObject(this.props, 'analytics', {});
			if (analytics.onEnterStep)
			{
				analytics.onEnterStep();
			}
		}

		createLayout(props)
		{
			return new Layout({
				uid: this.props.uid || null,
				createPayment: this.props.createPayment || null,
				entityTypeId: this.props.entityTypeId || null,
				getResponsible: this.props.getResponsible || null,
				currentUserId: BX.prop.getInteger(this.props, 'currentUserId', null),
			});
		}
	}

	module.exports = { FinishStep };
});

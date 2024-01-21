/**
 * @module crm/terminal/entity/payment-create/steps/responsible
 */
jn.define('crm/terminal/entity/payment-create/steps/responsible', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { Loc } = require('loc');
	const { ProgressBarNumber } = require('crm/salescenter/progress-bar-number');
	const {
		FieldManagerService,
		FieldNameResponsible,
	} = require('crm/terminal/services/field-manager');

	/**
	 * @class ResponsibleStep
	 */
	class ResponsibleStep extends WizardStep
	{
		constructor(props)
		{
			super(props);

			this.fieldManagerService = new FieldManagerService(this.props.fields || []);

			this.responsible = null;
			this.fieldResponsibleRef = null;
			this.onChangeResponsible = this.onChangeResponsibleHandler.bind(this);
		}

		getTitle()
		{
			return Loc.getMessage('M_CRM_TL_EPC_PRODUCT_WIZARD_TITLE');
		}

		onMoveToNextStep()
		{
			const onMoveToNextStep = BX.prop.getFunction(this.props, 'onMoveToNextStep', null);
			if (onMoveToNextStep)
			{
				onMoveToNextStep({ responsible: this.responsible });
			}

			return Promise.resolve();
		}

		createLayout(props)
		{
			return View(
				{
					style: {
						marginTop: 10,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						borderRadius: 12,
						borderWidth: 1,
						marginHorizontal: 0,
						paddingHorizontal: 16,
						paddingVertical: 9,
					},
				},
				this.fieldManagerService.renderField(
					FieldNameResponsible,
					{
						ref: (ref) => {
							this.fieldResponsibleRef = ref;
							this.setResponsible();
						},
						testId: 'TerminalEntityPaymentCreateFieldResponsible',
						renderIfEmpty: true,
						readOnly: false,
						showEditIcon: true,
						required: true,
						showRequired: false,
						onChange: this.onChangeResponsible,
					},
				),
				Text({
					style: {
						color: AppTheme.colors.base4,
						fontSize: 12,
						marginBottom: 8,
					},
					text: Loc.getMessage('M_CRM_TL_EPC_RESPONSIBLE_STEP_DESCRIPTION'),
				}),
			);
		}

		onChangeResponsibleHandler()
		{
			this.setResponsible();
		}

		setResponsible()
		{
			const userEntityList = this.fieldResponsibleRef ? this.fieldResponsibleRef.getEntityList() : [];

			this.responsible = null;
			if (Array.isArray(userEntityList) && userEntityList.length > 0)
			{
				const userEntity = this.fieldResponsibleRef.getEntityList()[0];

				this.responsible = {
					id: userEntity.id,
					name: userEntity.title,
				};
			}

			const isNextStepAvailable = this.responsible !== null;
			this.stepAvailabilityChangeCallback(isNextStepAvailable);
			this.progressBarNumberRef.setState({ isCompleted: isNextStepAvailable });
		}

		renderNumberBlock()
		{
			const progressBarSettings = this.getProgressBarSettings();

			return new ProgressBarNumber({
				number: progressBarSettings.number.toString(),
				isCompleted: true,
				ref: (ref) => {
					this.progressBarNumberRef = ref;
				},
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: {
					text: Loc.getMessage('M_CRM_TL_EPC_RESPONSIBLE_STEP_PROGRESS_BAR_TITLE'),
				},
				number: 1,
				count: 2,
			};
		}
	}

	module.exports = { ResponsibleStep };
});

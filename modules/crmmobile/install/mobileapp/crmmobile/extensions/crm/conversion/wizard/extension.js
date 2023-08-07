/**
 * @module crm/conversion/wizard
 */
jn.define('crm/conversion/wizard', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TypeId } = require('crm/type');
	const { merge } = require('utils/object');
	const { NotifyManager } = require('notify-manager');
	const { getActionToChangeStage } = require('crm/entity-actions');
	const { prepareConversionFields } = require('crm/conversion/utils');
	const { ConversionWizardStoreManager } = require('crm/state-storage');
	const { ConversionSelector } = require('crm/conversion/wizard/selector');
	const { wizardSteps, ENTITIES, FIELDS } = require('crm/conversion/wizard/steps');

	/**
	 * @class ConversionWizard
	 */
	class ConversionWizard
	{
		constructor(props)
		{
			const { conversion } = props;
			this.wizard = null;
			this.layoutWidget = null;
			this.conversion = conversion;
			this.result = {
				[ENTITIES]: {
					entityIds: {},
					entityTypeIds: this.getRecentActivity(),
				},
				[FIELDS]: {
					categoryId: 0,
					requiredConfig: {},
					fieldsConfig: [],
					entityTypeIds: [],
				},
			};
		}

		get props()
		{
			return this.conversion.props;
		}

		setLayoutWidget(layoutWidget)
		{
			this.layoutWidget = layoutWidget;
			this.conversion.setLayoutWidget(layoutWidget);
		}

		getLayoutWidget()
		{
			return this.layoutWidget;
		}

		setWizard(wizard)
		{
			this.wizard = wizard;
		}

		handleOnSelectedFields(stepId)
		{
			return (value) => {
				this.result[stepId] = merge(this.result[stepId], value);

				if (stepId === ENTITIES)
				{
					this.isMoveToStepFields(value);
				}
			};
		}

		isSingleEntity()
		{
			const { entityTypeId } = this.props;

			return TypeId.Lead !== entityTypeId;
		}

		getRecentActivity()
		{
			const lastRecent = ConversionWizardStoreManager.getEntityTypeIds(this.getRecentKey());
			const recentActivityIds = lastRecent.length > 0 ? lastRecent : this.conversion.getEntityItemIds();

			return this.isSingleEntity() ? [recentActivityIds[0]] : recentActivityIds;
		}

		setRecentActivity(stepId)
		{
			const { entityTypeIds } = this.getStepData(stepId);

			ConversionWizardStoreManager.setEntityTypeIds({
				[this.getRecentKey()]: { entityTypeIds },
			});
		}

		getRecentKey()
		{
			const entityTypeIds = this.conversion.getEntityItemIds();

			return entityTypeIds.join('_');
		}

		isMoveToStepFields({ entityTypeIds = [] })
		{
			this.wizard.onChangeStepAvailability(entityTypeIds.length > 0);
		}

		setBottomSheetHeight(stepId)
		{
			if (!this.wizard)
			{
				return;
			}

			const step = this.wizard.steps.get(stepId);
			this.layoutWidget.setBottomSheetHeight(step.getMediumPositionHeight());
		}

		getSteps()
		{
			return wizardSteps.map((WizardStep) => {
				const stepId = WizardStep.getId();
				const props = this.getStepsProps(stepId);

				return { id: stepId, step: new WizardStep(props) };
			});
		}

		getStepsProps(stepId)
		{
			const { entityTypeId, entityId } = this.props;
			const props = {
				entityId,
				entityTypeId,
				onChange: this.handleOnSelectedFields(stepId),
				onMoveToBackStep: this.onMoveToBackStep.bind(this),
			};
			const executeConversion = this.executeConversion(stepId).bind(this);
			const stepParams = { stepId, executeConversion };

			// eslint-disable-next-line default-case
			switch (stepId)
			{
				case ENTITIES:
					return { ...props, ...this.getEntityStepProps(stepParams) };

				case FIELDS:
					props.getFieldsConfig = () => {
						const stepData = this.getStepData(stepId);

						return stepData.fieldsConfig;
					};

					props.onFinish = () => {
						executeConversion({ enableSynchronization: true });
					};

					props.onLeaveStep = () => {
						NotifyManager.hideLoadingIndicatorWithoutFallback();
					};

					break;
			}

			return props;
		}

		onMoveToBackStep(stepId)
		{
			this.setBottomSheetHeight(stepId);

			return Promise.resolve();
		}

		getEntityStepProps({ stepId, executeConversion })
		{
			return {
				isSingleEntity: this.isSingleEntity(),
				recentActivityIds: this.getRecentActivity(),
				entityTypeIds: this.conversion.getEntityItemIds(),
				permissions: this.conversion.getPermissions(),
				getLayoutWidget: this.getLayoutWidget.bind(this),
				isReturnCustomer: this.conversion.isReturnCustomer(),
				ConversionSelector,
				moveToNextStep: () => {
					this.wizard.moveToNextStep();
				},
				onMoveToNextStep: async () => {
					let categoryId = null;

					if (this.hasDealEntityTypeId(this.getStepData(stepId)))
					{
						categoryId = await this.openCategoryList();

						if (categoryId === null)
						{
							return { next: false };
						}
					}

					this.setRecentActivity(stepId);

					const result = await executeConversion({ categoryId });

					if (!result)
					{
						return { finish: true };
					}

					const nextStepId = this.wizard.getStepIdByIndex(this.wizard.getNextStepIndex());
					this.setNextStepData({ result, categoryId, stepId, nextStepId });
					this.setBottomSheetHeight(nextStepId);

					return { next: true };
				},
				onLeaveStep: () => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
				},
			};
		}

		executeConversion(stepId)
		{
			return async (params) => {
				NotifyManager.showLoadingIndicator();

				const data = await this.conversion.execute({
					...this.getStepData(stepId),
					...params,
				});

				if (!data)
				{
					return null;
				}

				return data;
			};
		}

		openCategoryList()
		{
			const { onAction } = getActionToChangeStage();

			return onAction({
				entityTypeId: TypeId.Deal,
				title: Loc.getMessage('MCRM_CONVERSION_MENU_CATEGORY_TITLE'),
				layoutWidget: this.getLayoutWidget(),
			});
		}

		hasDealEntityTypeId(params)
		{
			const { entityTypeIds = [] } = params;

			return entityTypeIds.includes(TypeId.Deal);
		}

		getStepData(stepId)
		{
			return this.result[stepId];
		}

		setNextStepData({ result, categoryId, stepId, nextStepId })
		{
			const fieldsConfig = prepareConversionFields(result);
			const configId = stepId.split('-')[0];
			const stepData = fieldsConfig.find(({ id }) => id === configId);

			if (stepData && Array.isArray(stepData.data))
			{
				const onSelected = this.handleOnSelectedFields(nextStepId);
				onSelected({
					categoryId,
					fieldsConfig,
					requiredConfig: result.CONFIG,
					entityTypeIds: stepData.data.map(({ id }) => id),
				});
			}
		}
	}

	module.exports = { ConversionWizard };
});

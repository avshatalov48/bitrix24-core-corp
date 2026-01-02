/**
 * @module intranet/qualification/step
 */
jn.define('intranet/qualification/step', (require, exports, module) => {
	const { BBCodeText } = require('ui-system/typography');
	const { Color, Component, Indent } = require('tokens');
	const { StepSwitchButtonType } = require('layout/ui/wizard/const');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { AnalyticsHandler } = require('intranet/qualification/analytics-handler');
	const { FieldsHandler } = require('intranet/qualification/fields-handler');
	const { parseColorCode, getImageDimensions } = require('intranet/qualification/utils');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const AppTheme = require('apptheme');

	class Step extends WizardStep
	{
		constructor(props)
		{
			super(props);

			this.fieldsHandler = new FieldsHandler({
				stepId: this.getId(),
				wizard: this.getWizard(),
				fields: this.getFields(),
				layoutWidget: this.getLayoutWidget(),
				onFieldsValuesUpdate: () => this.stepAvailabilityChangeCallback(),
			});
			this.finalWizardActions = [];
		}

		/**
		 * @return {Wizard}
		 */
		getWizard()
		{
			return this.props.wizard;
		}

		/**
		 * @return {string}
		 */
		getId()
		{
			return this.props.id;
		}

		/**
		 * @return {boolean}
		 */
		shouldCountAsStep()
		{
			return this.props.shouldCountAsStep || false;
		}

		/**
		 * @return {boolean}
		 */
		isActive()
		{
			return this.props.isActive || false;
		}

		/**
		 * @return {string}
		 */
		getBackgroundColor()
		{
			return Color.bgContentPrimary.toHex();
		}

		/**
		 * @return {string}
		 */
		getTitleType()
		{
			return 'wizard';
		}

		#getStepIndex()
		{
			const stepIndex = (
				[...this.getWizard().steps.values()]
					.filter((step) => step.shouldCountAsStep())
					.findIndex((step) => step.getId() === this.getId())
			);

			if (stepIndex === -1)
			{
				return 0;
			}

			return stepIndex + 1;
		}

		/**
		 * @return {string}
		 */
		getTitle()
		{
			const stepIndex = this.#getStepIndex();
			if (stepIndex === 0)
			{
				return '';
			}

			return Loc.getMessage('QUALIFICATION_STEP_TITLE', { '#INDEX#': stepIndex }) || '';
		}

		/**
		 * @return {Object|null}
		 */
		getImage()
		{
			return this.props.image || null;
		}

		/**
		 * @return {Object}
		 */
		getContentTitle()
		{
			return this.props.contentTitle || {};
		}

		/**
		 * @return {Object}
		 */
		getContentDescription()
		{
			return this.props.contentDescription || {};
		}

		/**
		 * @return {Array}
		 */
		getFields()
		{
			return this.props.fields || [];
		}

		/**
		 * @return {Array}
		 */
		getButtons()
		{
			return this.props.buttons || [];
		}

		/**
		 * @return {boolean}
		 */
		isNextStepButtonEnabled()
		{
			return this.fieldsHandler.areAllFieldsValid();
		}

		/**
		 * @return {string}
		 */
		getNextStepButtonText()
		{
			return this.getButtons().find((button) => button.type === 'continue')?.title ?? '';
		}

		/**
		 * @return {boolean}
		 */
		canSkip()
		{
			return this.getButtons().some((button) => button.type === 'skip');
		}

		getSkipStepButtonText()
		{
			return this.getButtons().find((button) => button.type === 'skip')?.title ?? super.getSkipStepButtonText();
		}

		/**
		 * @param {StepSwitchButtonType} stepSwitchButtonType
		 */
		onBeforeMoveToNextStep(stepSwitchButtonType)
		{
			switch (stepSwitchButtonType)
			{
				case StepSwitchButtonType.SKIP:
					AnalyticsHandler.handleSkipEvent(this.getId());
					break;

				case StepSwitchButtonType.CONTINUE:
					AnalyticsHandler.handleContinueEvent(this.getId());
					break;

				default:
					break;
			}

			this.fieldsHandler.handleFieldsTriggers(stepSwitchButtonType);
		}

		/**
		 * @param {string} stepId
		 * @return {boolean}
		 */
		onMoveToNextStep(stepId)
		{
			this.getWizard().getStepById(stepId)?.resetFieldsValues();

			return true;
		}

		/**
		 * @param {string} stepId
		 */
		onMoveToBackStep(stepId)
		{
			AnalyticsHandler.handleBackEvent(this.getId());
		}

		onEnterStep()
		{
			AnalyticsHandler.onEnterStep(this.getId());
			AnalyticsHandler.handleShowStepEvent(this.getId());
		}

		onFinishStep()
		{
			AnalyticsHandler.handleCloseWizardEvent(this.getId());
			this.finalWizardActions.forEach((action) => action());
		}

		/**
		 * @param {Function} action
		 */
		addFinalWizardAction(action)
		{
			if (Type.isFunction(action))
			{
				this.finalWizardActions.push(action);
			}
		}

		resetFieldsValues()
		{
			this.fieldsHandler.resetFieldsValues();
		}

		getFieldsAnalyticsData()
		{
			return this.fieldsHandler.getAnalyticsData();
		}

		#getRealStepsCount()
		{
			return [...this.getWizard().steps.values()].filter((step) => step.shouldCountAsStep()).length;
		}

		getProgressBarSettings()
		{
			return {
				isEnabled: this.shouldCountAsStep(),
				number: this.#getStepIndex(),
				count: this.#getRealStepsCount(),
			};
		}

		/**
		 * @return {View}
		 */
		createLayout()
		{
			return View(
				{},
				this.#renderImage(),
				View(
					{
						style: {
							marginHorizontal: Component.paddingLr.toNumber(),
						},
					},
					this.#renderContentTitle(),
					this.#renderContentDescription(),
					this.#renderFields(),
				),
			);
		}

		#renderImage()
		{
			const image = this.getImage();
			if (!image?.uri?.[AppTheme.id])
			{
				return null;
			}

			const { width, height, uri } = image;

			return new Image({
				style: {
					...getImageDimensions(width, height),
					alignSelf: 'center',
				},
				resizeMode: 'contain',
				uri: encodeURI(uri[AppTheme.id]),
			});
		}

		#renderContentTitle()
		{
			const contentTitle = this.getContentTitle();
			if (!contentTitle?.text)
			{
				return null;
			}

			return BBCodeText({
				style: {
					marginTop: Indent.XL4.toNumber(),
					textAlign: contentTitle.textAlign || 'left',
				},
				color: Color.base1,
				header: true,
				accent: true,
				size: 2,
				value: parseColorCode(contentTitle.text),
			});
		}

		#renderContentDescription()
		{
			const contentDescription = this.getContentDescription();
			if (!contentDescription?.text)
			{
				return null;
			}

			return BBCodeText({
				style: {
					marginTop: Indent.L.toNumber(),
					textAlign: contentDescription.textAlign || 'left',
				},
				color: Color.base1,
				value: parseColorCode(contentDescription.text),
			});
		}

		#renderFields()
		{
			return this.fieldsHandler.renderFields();
		}
	}

	module.exports = { Step };
});

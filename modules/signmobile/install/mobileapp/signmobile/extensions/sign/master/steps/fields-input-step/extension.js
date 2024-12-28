/**
 * @module sign/master/steps/fields-input-step
 */

jn.define('sign/master/steps/fields-input-step', (require, exports, module) => {
	const { FieldsLayout } = require('sign/master/steps/fields-input-step/fields-layout');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { Banner } = require('sign/banner');
	const { date } = require('utils/date/formats');
	const { Moment } = require('utils/date');
	const { trim } = require('utils/string');
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { CreateDocument } = require('sign/document/create');

	/**
	 * @class FieldsInputStep
	 */
	class FieldsInputStep extends WizardStep
	{
		constructor(props)
		{
			super(props);
			this.selectedTemplate = props.selectedTemplate;
			this.data = props.data;
			const fields = {};
			this.nextStepOpen = this.data.fields.every((field) => {
				if (field.required)
				{
					fields[field.uid] = trim(field.value);

					return fields[field.uid] !== undefined && fields[field.uid] !== null && fields[field.uid] !== '';
				}

				return true;
			});
			this.props.data.fillFields = fields;
		}

		createLayout()
		{
			return View(
				{
					style: {
						flex: 1,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				this.isFieldsEmpty()
					? new Banner({
						ref: (ref) => {
							this.banner = ref;
						},
						imageName: 'creation.svg',
						title: Loc.getMessage('SIGN_MOBILE_MASTER_INPUT_STEP_EMPTY_STATE_TITLE',),
						description: Loc.getMessage(
							'SIGN_MOBILE_MASTER_INPUT_STEP_EMPTY_STATE_DESCRIPTION',
							{ '#SELECTED_TEMPLATE_TITLE#': `[B]${String(this.selectedTemplate.data.title)}[/B]` },
						),
					})
					: new FieldsLayout({
						templateTitle: this.selectedTemplate.data.title,
						company: this.selectedTemplate.data.company,
						fields: this.props.data.fields,
						changeButtonStatus: this.changeButtonStatus.bind(this),
					}),
			);
		}

		onMoveToNextStep(stepId)
		{
			const preparedFields = this.props.data.fields.map((item) => ({
				name: item.uid,
				value: this.prepareField(item.type, item.uid),
			}));

			this.props.layout.close(() => {
				new CreateDocument({
					providerCodeForAnalytics: this.props.data.providerCodeForAnalytics,
					templateUid: this.selectedTemplate.data.uid,
					analyticsEvent: this.props.analyticsEvent,
					title: this.selectedTemplate.data.title,
					fromAvaMenu: this.props.fromAvaMenu,
					baseLayout: this.props.baseLayout,
					preparedFields,
				}).launch();
			});

			return true;
		}

		prepareField = (type, uid) => {
			const value = this.props.data.fillFields[uid];
			if (type === 'date')
			{
				const moment = new Moment(value * 1000);

				return moment.format(date());
			}

			return value;
		};

		getTitle()
		{
			return Loc.getMessage('SIGN_MOBILE_MASTER_FIELDS_INPUT_STEP_TITLE');
		}

		isNeedToSkip()
		{
			return false;
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('SIGN_MOBILE_MASTER_FIELDS_INPUT_STEP_NEXT_STEP_BUTTON_TEXT');
		}

		isNextStepEnabled()
		{
			return this.nextStepOpen;
		}

		isPrevStepEnabled()
		{
			return true;
		}

		changeButtonStatus(isAllFieldsFilled, fields)
		{
			this.props.data.fillFields = fields;
			this.nextStepOpen = isAllFieldsFilled;
			this.stepAvailabilityChangeCallback(isAllFieldsFilled);
		}

		resizableByKeyboard()
		{
			return true;
		}

		isFieldsEmpty()
		{
			return this.props.data.fields && Object.keys(this.props.data.fields).length === 0;
		}

		getLeftButtons()
		{
			return false;
		}

		getRightButtons()
		{
			return [{ type: 'cross', isCloseButton: true, callback: () => this.props.layout.close() }];
		}
	}

	module.exports = { FieldsInputStep };
});

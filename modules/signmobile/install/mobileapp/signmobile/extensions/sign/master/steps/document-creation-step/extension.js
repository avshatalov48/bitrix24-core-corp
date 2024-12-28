/**
 * @module sign/master/steps/document-creation-step
 */
jn.define('sign/master/steps/document-creation-step', (require, exports, module) => {
	const { WizardStep } = require('layout/ui/wizard/step');
	const { SignOpener } = require('sign/opener');
	const { Banner } = require('sign/banner');
	const { date } = require('utils/date/formats');
	const { Moment } = require('utils/date');
	const { Color } = require('tokens');
	const { Loc } = require('loc');

	/**
	 * @class DocumentCreationStep
	 */
	class DocumentCreationStep extends WizardStep
	{
		constructor(props)
		{
			super(props);
			this.selectedTemplate = props.selectedTemplate;
			this.data = props.data;
			this.nextStepOpen = false;
			this.rightButtons = [];
		}

		createLayout(props)
		{
			return View(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				new Banner({
					ref: (ref) => {
						this.banner = ref;
					},
					imageName: 'creation.svg',
					title:
						Loc.getMessage(
							'SIGN_MOBILE_MASTER_EMPTY_STATE_TITLE_DOCUMENT_CREATION',
							{ '#SELECTED_TEMPLATE_TITLE#': this.selectedTemplate.data.title },
						),
					description: Loc.getMessage('SIGN_MOBILE_MASTER_EMPTY_STATE_DESCRIPTION_DOCUMENT_CREATION'),
				}),
				super.createLayout(props),
			);
		}

		sendTemplate(uid, fields)
		{
			return BX.ajax.runAction('sign.api_v1.b2e.document.template.send', {
				json: {
					uid,
					fields,
				},
			});
		}

		getMember(uid)
		{
			return BX.ajax.runAction('sign.api_v1.document.member.get', {
				json: {
					uid,
				},
			});
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

		async onEnterStep()
		{
			const prepareFields = this.props.data.fields.map((item) => ({
				name: item.uid,
				value: this.prepareField(item.type, item.uid),
			}));
			try
			{
				const sendTemplateResponse = await this.sendTemplate(this.selectedTemplate.data.uid, prepareFields);
				const memberResponse = await this.getMember(sendTemplateResponse.data.employeeMember.uid);

				return new Promise((resolve) => {
					this.props.layout.close(() => {
						SignOpener.openSigning({
							memberId: memberResponse.data.id,
						});
						resolve();
					});
				});
			}
			catch
			{
				this.banner.rerender(
					'error.svg',
					Loc.getMessage('SIGN_MOBILE_MASTER_EMPTY_STATE_TITLE_CREATION_ERROR'),
					Loc.getMessage('SIGN_MOBILE_MASTER_EMPTY_STATE_DESCRIPTION_CREATION_ERROR'),
				);
				this.rightButtons = [{ type: 'cross', isCloseButton: true, callback: () => this.props.layout.close() }];
				this.changeButtonStatus(true);
			}
		}

		oneMoreTry()
		{
			this.rightButtons = [];
			this.changeButtonStatus(false);
			this.onEnterStep();
		}

		isNeedToSkip()
		{
			return false;
		}

		isPrevStepEnabled()
		{
			return false;
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('SIGN_MOBILE_MASTER_DOCUMENT_CREATION_STEP_NEXT_STEP_BUTTON_TEXT');
		}

		isNextStepEnabled()
		{
			return this.nextStepOpen;
		}

		changeButtonStatus(buttonStatus)
		{
			this.nextStepOpen = buttonStatus;
			this.stepAvailabilityChangeCallback(buttonStatus);
		}

		getTitle()
		{
			return Loc.getMessage('SIGN_MOBILE_MASTER_DOCUMENT_CREATION_STEP_TITLE');
		}

		getLeftButtons()
		{
			return [];
		}

		getRightButtons()
		{
			return this.rightButtons;
		}
	}
	module.exports = { DocumentCreationStep };
});

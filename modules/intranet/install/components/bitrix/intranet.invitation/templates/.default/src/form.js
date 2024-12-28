import {Type, Dom, Event, Loc} from 'main.core';
import {Submit} from './submit';
import {SelfRegister} from './self-register';
import {Row} from "./row";
import {Selector} from "./selector";
import {EventEmitter} from "main.core.events";
import {ActiveDirectory} from "./active-directory";
import { Analytics } from './analytics';

export default class Form extends EventEmitter
{
	constructor(formParams)
	{
		super();
		const params = Type.isPlainObject(formParams) ? formParams : {};

		this.signedParameters = params.signedParameters;
		this.componentName = params.componentName;

		this.menuContainer = params.menuContainerNode;
		this.contentContainer = params.contentContainerNode;
		this.contentBlocks = {};
		this.userOptions = params.userOptions;

		this.isExtranetInstalled = params.isExtranetInstalled === "Y";
		this.isCloud = params.isCloud === "Y";
		this.isAdmin = params.isAdmin === "Y";
		this.isInvitationBySmsAvailable = params.isInvitationBySmsAvailable === "Y";
		this.isCreatorEmailConfirmed = params.isCreatorEmailConfirmed === "Y";
		this.regenerateUrlBase = params.regenerateUrlBase;
		this.firstInvitationBlock = params.firstInvitationBlock;
		this.isSelfRegisterEnabled = params.isSelfRegisterEnabled;
		this.analyticsLabel = params.analyticsLabel;
		this.projectLimitExceeded = Type.isBoolean(params.projectLimitExceeded) ? params.projectLimitExceeded : true;
		this.projectLimitFeatureId = Type.isString(params.projectLimitFeatureId) ? params.projectLimitFeatureId : '';
		this.isCollabEnabled = params.isCollabEnabled === 'Y';

		if (Type.isDomNode(this.contentContainer))
		{
			const blocks = Array.prototype.slice.call(
				this.contentContainer.querySelectorAll(".js-intranet-invitation-block")
			);
			(blocks || []).forEach((block) => {
				let blockType = block.getAttribute("data-role");
				blockType = blockType.replace("-block", "");
				this.contentBlocks[blockType] = block;
			});

			this.errorMessageBlock = this.contentContainer.querySelector("[data-role='error-message']");
			this.successMessageBlock = this.contentContainer.querySelector("[data-role='success-message']");

			BX.UI.Hint.init(this.contentContainer);
		}

		this.button = document.querySelector("#intranet-invitation-btn");

		if (Type.isDomNode(this.menuContainer))
		{
			this.menuItems = Array.prototype.slice.call(this.menuContainer.querySelectorAll("a"));

			(this.menuItems || []).forEach((item) => {
				Event.bind(item, 'click', () => {
					this.changeContent(item.getAttribute('data-action'));
				});

				if (item.getAttribute('data-action') === this.firstInvitationBlock)
				{
					BX.fireEvent(item, 'click');
				}
			});
		}

		this.submit = new Submit(this);
		this.submit.subscribe('onInputError', (event) => {
			this.showErrorMessage(event.data.error);
		});

		this.analytics = new Analytics(this.analyticsLabel, this.isAdmin);
		this.analytics.sendOpenSliderData(this.analyticsLabel.source);

		if (this.isCloud)
		{
			this.selfRegister = new SelfRegister(this);
		}

		this.arrowBox = document.querySelector('.invite-wrap-decal-arrow');
		if (Type.isDomNode(this.arrowBox))
		{
			this.arrowRect = this.arrowBox.getBoundingClientRect();
			this.arrowHeight = this.arrowRect.height;
			this.setSetupArrow();
		}
	}

	renderSelector(params)
	{
		this.selector = new Selector(this, params);
		this.selector.render();
	}

	getSubSection()
	{
		const regex = /analyticsLabel\[source]=(\w*)&/gm;
		let match = regex.exec(decodeURI(window.location));
		if (match?.length > 1)
		{
			return match[1];
		}
		return null;
	}

	changeContent(action)
	{
		this.hideErrorMessage();
		this.hideSuccessMessage();
		let section = this.getSubSection();
		let subSection = "";

		if (action.length > 0)
		{
			if (action === 'active-directory')
			{
				if (!this.activeDirectory)
				{
					this.activeDirectory = new ActiveDirectory(this);
				}

				this.activeDirectory.showForm();
				this.analytics.sendTabData(section, Analytics.TAB_AD);

				return;
			}

			const projectId = (
				this.userOptions.hasOwnProperty('groupId')
					? parseInt(this.userOptions.groupId, 10)
					: 0
			);

			const departmentsId = (
				(this.userOptions.hasOwnProperty('departmentsId') && Array.isArray(this.userOptions.departmentsId))
					? this.userOptions.departmentsId.map((id) => parseInt(id, 10))
					: []
			);
			for (let type in this.contentBlocks)
			{
				let block = this.contentBlocks[type];

				if (type === action)
				{
					Dom.removeClass(block, 'invite-block-hidden');
					Dom.addClass(block, 'invite-block-shown');

					const params = {
						contentBlock: this.contentBlocks[action]
					};
					const row = new Row(this, params);

					if (action === 'invite')
					{
						subSection = Analytics.TAB_EMAIL;
						row.renderInviteInputs(5);
					}
					else if (action === 'invite-with-group-dp')
					{
						subSection = Analytics.TAB_DEPARTMENT;
						row.renderInviteInputs(3);

						const selectorParams = {
							contentBlock: this.contentBlocks[action]
								.querySelector("[data-role='entity-selector-container']"),
							options: {
								department: true,
								project: true,
								projectId: projectId,
								departmentsId: departmentsId,
								isAdmin: this.isAdmin,
								projectLimitExceeded: this.projectLimitExceeded,
								projectLimitFeatureId: this.projectLimitFeatureId,
							}
						};
						this.renderSelector(selectorParams);
					}
					else if (action === 'extranet')
					{
						subSection = Analytics.TAB_EXTRANET;
						row.renderInviteInputs(3);

						const selectorParams = {
							contentBlock: this.contentBlocks[action]
								.querySelector("[data-role='entity-selector-container']"),
							options: {
								department: false,
								project: "extranet",
								projectId: projectId,
								isAdmin: this.isAdmin,
								projectLimitExceeded: this.projectLimitExceeded,
								projectLimitFeatureId: this.projectLimitFeatureId,
								showCreateButton: !this.isCollabEnabled,
							}
						};
						this.renderSelector(selectorParams);
					}
					else if (action === "add")
					{
						subSection = Analytics.TAB_REGISTRATION;
						row.renderRegisterInputs();

						const selectorParams = {
							contentBlock: this.contentBlocks[action]
								.querySelector("[data-role='entity-selector-container']"),
							options: {
								department: true,
								project: true,
								projectId: projectId,
								isAdmin: this.isAdmin,
								projectLimitExceeded: this.projectLimitExceeded,
								projectLimitFeatureId: this.projectLimitFeatureId,
							}
						};
						this.renderSelector(selectorParams);
					}
					else if (action === "integrator")
					{
						subSection = Analytics.TAB_INTEGRATOR;
						row.renderIntegratorInput();
					}
					else if (action === "self")
					{
						subSection = Analytics.TAB_LINK;
					}
					else if (action === "mass-invite")
					{
						subSection = Analytics.TAB_MASS;
					}
				}
				else
				{
					Dom.removeClass(block, 'invite-block-shown');
					Dom.addClass(block, 'invite-block-hidden');
				}
			}

			if (this.analytics && section && subSection)
			{
				this.analytics.sendTabData(section, subSection);
			}
			this.changeButton(action);
		}
	}

	changeButton(action)
	{
		Event.unbindAll(this.button, 'click');

		if (!this.isCreatorEmailConfirmed)
		{
			Event.bind(this.button, 'click', () => {
				this.showErrorMessage(Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_CREATOR_EMAIL_ERROR'));
			});
			return;
		}

		this.activateButton();

		if (action === "invite")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');

			Event.bind(this.button, 'click',() => {
				if (!this.submit.waitingResponse) {
					this.submit.submitInvite();
				}
			});
		}
		else if (action === "mass-invite")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');

			Event.bind(this.button, 'click',() => {
				if (!this.submit.waitingResponse) {
					this.submit.submitMassInvite();
				}
			});
		}
		else if (action === "invite-with-group-dp")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');

			Event.bind(this.button, 'click', () => {
				if (!this.submit.waitingResponse) {
					this.submit.submitInviteWithGroupDp();
				}
			});
		}
		else if (action === "add")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_ADD');

			Event.bind(this.button, 'click', () => {
				if (!this.submit.waitingResponse) {
					this.submit.submitAdd();
				}
			});
		}
		else if (action === "self")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_SAVE');
			this.disableButton();

			Event.bind(this.button, 'click', () => {
				if (this.isButtonActive())
				{
					this.submit.submitSelf();
				}
			});
		}
		else if (action === "integrator")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');

			Event.bind(this.button, 'click', () => {
				if (!this.submit.waitingResponse) {
					this.submit.submitIntegrator();
				}
			});
		}
		else if (action === "extranet")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');

			Event.bind(this.button, 'click', () => {
				if (!this.submit.waitingResponse) {
					this.submit.submitExtranet();
				}
			});
		}
		else if (action === "success")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE_MORE');

			Event.bind(this.button, 'click', () => {
				BX.fireEvent(this.menuItems[0], 'click');
			});
		}
	}

	disableButton()
	{
		Dom.addClass(this.button, "ui-btn-disabled");
	}

	activateButton()
	{
		Dom.removeClass(this.button, "ui-btn-disabled");
	}

	isButtonActive()
	{
		return !Dom.hasClass(this.button, "ui-btn-disabled");
	}

	showSuccessMessage(successText)
	{
		this.hideErrorMessage();

		if (Type.isDomNode(this.successMessageBlock))
		{
			this.successMessageBlock.style.display = "block";
			const alert = this.successMessageBlock.querySelector(".ui-alert-message");
			if (Type.isDomNode(alert))
			{
				alert.innerHTML = BX.util.htmlspecialchars(successText);
			}
		}
	}

	hideSuccessMessage()
	{
		if (Type.isDomNode(this.successMessageBlock))
		{
			this.successMessageBlock.style.display = "none";
		}
	}

	showErrorMessage(errorText)
	{
		this.hideSuccessMessage();

		if (Type.isDomNode(this.errorMessageBlock) && errorText)
		{
			this.errorMessageBlock.style.display = "block";
			const alert = this.errorMessageBlock.querySelector(".ui-alert-message");
			if (Type.isDomNode(alert))
			{
				alert.innerHTML = BX.util.htmlspecialchars(errorText);
			}
		}
	}

	hideErrorMessage()
	{
		if (Type.isDomNode(this.errorMessageBlock))
		{
			this.errorMessageBlock.style.display = "none";
		}
	}

	getSetupArrow()
	{
		this.body = document.querySelector('.invite-body');
		this.panelConfirmBtn = document.getElementById('intranet-invitation-btn');
		this.sliderContent = document.querySelector('.ui-page-slider-workarea');
		this.sliderHeader = document.querySelector('.ui-side-panel-wrap-title-wrap');
		this.buttonPanel = document.querySelector('.ui-button-panel');
		this.inviteButton = document.querySelector('.invite-form-buttons');

		this.sliderHeaderHeight = this.sliderHeader.getBoundingClientRect().height;
		this.buttonPanelRect = this.buttonPanel.getBoundingClientRect();
		this.panelRect = this.panelConfirmBtn.getBoundingClientRect();
		this.btnWidth = Math.ceil(this.panelRect.width);
		this.arrowWidth = Math.ceil(this.arrowRect.width);
		this.sliderContentRect = this.sliderContent.getBoundingClientRect();

		this.bodyHeight = this.body.getBoundingClientRect().height - this.buttonPanelRect.height + this.sliderHeaderHeight;
		this.contentHeight = this.arrowHeight + this.sliderContentRect.height + this.buttonPanelRect.height + this.sliderHeaderHeight - 65;
	}

	updateArrow()
	{
		this.bodyHeight = this.body.getBoundingClientRect().height - this.buttonPanelRect.height + this.sliderHeaderHeight;
		this.contentHeight = this.arrowHeight + this.sliderContentRect.height + this.buttonPanelRect.height + this.sliderHeaderHeight - 65;
		this.contentHeight > this.bodyHeight ? this.body.classList.add('js-intranet-invitation-arrow-hide') : this.body.classList.remove('js-intranet-invitation-arrow-hide');
	}

	setSetupArrow()
	{
		this.getSetupArrow();
		const btnPadding = 40;
		this.arrowBox.style.left = (this.panelRect.left + (this.btnWidth / 2) - (this.arrowWidth / 2) - btnPadding) + 'px';
		this.contentHeight > this.bodyHeight ? this.body.classList.add('js-intranet-invitation-arrow-hide') : this.body.classList.remove('js-intranet-invitation-arrow-hide');

		window.addEventListener('resize', function() {
			if (window.innerWidth <= 1100)
			{
				this.arrowBox.style.left = (this.panelRect.left + (this.btnWidth / 2) - (this.arrowWidth / 2) - btnPadding) + 'px';
			}
			this.getSetupArrow();
			this.updateArrow();
		}.bind(this))

		this.inviteButton.addEventListener('click', function() {
			this.getSetupArrow();
			this.updateArrow();
		}.bind(this))
	}
}

import { Type, Validation, Loc, Dom, Event, Tag } from 'main.core';
import {EventEmitter} from "main.core.events";
import {Popup} from 'main.popup';
import { CreateButton, CancelButton } from 'ui.buttons';

export class Submit extends EventEmitter
{
	constructor(parent)
	{
		super();
		this.waitingResponse = false;
		this.parent = parent;
		this.setEventNamespace("BX.Intranet.Invitation.Submit");
		this.parent.subscribe("onButtonClick", (event) => {});
	}

	parseEmailAndPhone(form)
	{
		if (!Type.isDomNode(form))
		{
			return;
		}

		let errorInputData = [];
		let items = [];
		const phoneExp = /^[\d+][\d\(\)\ -]{4,22}\d$/;
		const rows = Array.prototype.slice.call(form.querySelectorAll(".js-form-row"));

		(rows || []).forEach((row) => {
			const emailInput = row.querySelector("input[name='EMAIL[]']");
			const phoneInput = row.querySelector("input[name='PHONE[]']");
			const nameInput = row.querySelector("input[name='NAME[]']");
			const lastNameInput = row.querySelector("input[name='LAST_NAME[]']");
			const emailValue = emailInput.value.trim();

			if (this.parent.isInvitationBySmsAvailable && Type.isDomNode(phoneInput))
			{
				const phoneValue = phoneInput.value.trim();
				if (phoneValue)
				{
					if (!phoneExp.test(String(phoneValue).toLowerCase()))
					{
						errorInputData.push(phoneValue);
					}
					else
					{
						const phoneCountryInput = row.querySelector("input[name='PHONE_COUNTRY[]']");
						items.push({
							"PHONE": phoneValue,
							"PHONE_COUNTRY": phoneCountryInput.value.trim(),
							"NAME": nameInput.value,
							"LAST_NAME": lastNameInput.value
						});
					}
				}
			}
			else if (emailValue)
			{
				if (Validation.isEmail(emailValue))
				{
					items.push({
						"EMAIL": emailValue,
						"NAME": nameInput.value,
						"LAST_NAME": lastNameInput.value
					});
				}
				else
				{
					errorInputData.push(emailValue);
				}
			}
		});

		return [items, errorInputData];
	}

	getGroupAndDepartmentData(requestData)
	{
		const selector = this.parent.selector;
		const selectorItems = selector.getItems();

		if (selectorItems["departments"].length > 0)
		{
			requestData["UF_DEPARTMENT"] = selectorItems["departments"];
		}

		if (selectorItems["projects"].length > 0)
		{
			requestData["SONET_GROUPS_CODE"] = selectorItems["projects"];
		}
	}

	submitInvite()
	{
		const inviteForm = this.parent.contentBlocks["invite"].querySelector("form");
		let [items, errorInputData] = [...this.parseEmailAndPhone(inviteForm)];

		if (errorInputData.length > 0)
		{
			const event = new Event.BaseEvent({data: {error: Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR") + ": " + errorInputData.join(', ')}});
			this.emit("onInputError", event);
			return;
		}

		if (items.length <= 0)
		{
			const event = new Event.BaseEvent({data: {error: Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR")}});
			this.emit("onInputError", event);
			return;
		}

		const requestData = {
			"ITEMS": items
		};

		const analyticsLabel = {
			"INVITATION_TYPE": "invite",
			"INVITATION_COUNT": items.length
		};

		this.sendAction("invite", requestData, analyticsLabel);
	}

	submitInviteWithGroupDp()
	{
		const inviteWithGroupDpForm = this.parent.contentBlocks["invite-with-group-dp"].querySelector("form");
		let [items, errorInputData] = [...this.parseEmailAndPhone(inviteWithGroupDpForm)];

		if (errorInputData.length > 0)
		{
			this.parent.showErrorMessage(Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR") + ": " + errorInputData.join(', '));
			return;
		}

		if (items.length <= 0)
		{
			const event = new Event.BaseEvent({data: {
				error: Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR")}
			});
			this.emit("onInputError", event);
			return;
		}

		let requestData = {
			"ITEMS": items
		};
		this.getGroupAndDepartmentData(requestData);

		const analyticsLabel = {
			"INVITATION_TYPE": "withGroupOrDepartment",
			"INVITATION_COUNT": items.length
		};

		this.sendAction("inviteWithGroupDp", requestData, analyticsLabel);
	}

	submitSelf()
	{
		const selfForm = this.parent.contentBlocks["self"].querySelector("form");
		let obRequestData = {
			"allow_register": selfForm["allow_register"].value,
			"allow_register_confirm": selfForm["allow_register_confirm"].checked ? "Y" : "N",
			"allow_register_secret": selfForm["allow_register_secret"].value,
			"allow_register_whitelist": selfForm["allow_register_whitelist"].value,
		};

		this.sendAction("self", obRequestData);
	}

	submitExtranet()
	{
		const extranetForm = this.parent.contentBlocks["extranet"].querySelector("form");
		let [items, errorInputData] = [...this.parseEmailAndPhone(extranetForm)];

		if (errorInputData.length > 0)
		{
			this.parent.showErrorMessage(Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR") + ": " + errorInputData.join(', '));
			return;
		}

		if (items.length <= 0)
		{
			const event = new Event.BaseEvent({data: {
				error: Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR")}
			});
			this.emit("onInputError", event);
			return;
		}

		let requestData = {
			"ITEMS": items
		};
		this.getGroupAndDepartmentData(requestData);

		const analyticsLabel = {
			"INVITATION_TYPE": "extranet",
			"INVITATION_COUNT": items.length
		};

		this.sendAction("extranet", requestData, analyticsLabel);
	}

	submitIntegrator()
	{
		this.getIntegratorConfirmPopup().show();
	}

	getIntegratorConfirmPopup(): Popup
	{
		const integratorForm = this.parent.contentBlocks.integrator.querySelector('form');
		const obRequestData = {
			integrator_email: integratorForm.integrator_email.value,
		};
		const analyticsLabel = {
			INVITATION_TYPE: 'integrator',
		};

		const message = Tag.render`
			<div class="invite-integrator-confirm-message">
				${Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_DESCRIPTION')}
			</div>
		`;
		const moreLink = message.querySelector('span');
		Event.bind(moreLink, 'click', () => {
			top.BX.Helper.show('redirect=detail&code=20682986');
		});

		const popup = new Popup({
			id: 'integrator-confirm-invitation-popup',
			maxWidth: 500,
			closeIcon: false,
			overlay: true,
			contentPadding: 10,
			titleBar: Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_TITLE'),
			content: message,
			offsetLeft : 100,
			buttons: [
				new CreateButton({
					text: Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_BUTTON_YES'),
					onclick: () => {
						popup.close();
						this.sendAction('inviteIntegrator', obRequestData, analyticsLabel);
					},
				}),
				new CancelButton({
					text: Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_BUTTON_NO'),
					onclick: () => {
						popup.close();
					},
				}),
			],
			events: {
				onClose: () => {
					popup.destroy();
				},
			},
		});

		return popup;
	}

	submitMassInvite()
	{
		const massInviteForm = this.parent.contentBlocks["mass-invite"].querySelector("form");

		const obRequestData = {
			"ITEMS": massInviteForm["mass_invite_emails"].value
		};

		const analyticsLabel = {
			"INVITATION_TYPE": "mass"
		};
		this.sendAction("massInvite", obRequestData, analyticsLabel);
	}

	submitAdd()
	{
		const addForm = this.parent.contentBlocks["add"].querySelector("form");

		let requestData = {
			"ADD_EMAIL": addForm["ADD_EMAIL"].value,
			"ADD_NAME": addForm["ADD_NAME"].value,
			"ADD_LAST_NAME": addForm["ADD_LAST_NAME"].value,
			"ADD_POSITION": addForm["ADD_POSITION"].value,
			"ADD_SEND_PASSWORD": (
				addForm["ADD_SEND_PASSWORD"]
				&& !!addForm["ADD_SEND_PASSWORD"].checked
					? addForm["ADD_SEND_PASSWORD"].value
					: "N"
			),
		};
		this.getGroupAndDepartmentData(requestData);

		const analyticsLabel = {
			"INVITATION_TYPE": "add"
		};
		this.sendAction("add", requestData, analyticsLabel);
	}

	sendAction(action, requestData, analyticsLabel)
	{
		this.disableSubmitButton(true);
		requestData["userOptions"] = this.parent.userOptions;
		requestData["analyticsData"] = this.parent.analyticsLabel;

		BX.ajax.runComponentAction(this.parent.componentName, action, {
			signedParameters: this.parent.signedParameters,
			mode: "ajax",
			data: requestData,
			analyticsLabel: analyticsLabel,
		}).then((response) => {

			this.disableSubmitButton(false);

			if (response.data)
			{
				if (action === "self")
				{
					this.parent.showSuccessMessage(response.data);
				}
				else
				{
					this.parent.changeContent("success");
					this.sendSuccessEvent(response.data);
				}
			}

			EventEmitter.subscribe(
				EventEmitter.GLOBAL_TARGET,
				'SidePanel.Slider:onClose',
				() => {
					BX.SidePanel.Instance.postMessageTop(window, 'BX.Bitrix24.EmailConfirmation:showPopup');
				},
			);
		}, (response) => {

			this.disableSubmitButton(false);

			if (response.data == "user_limit")
			{
				B24.licenseInfoPopup.show(
					"featureID",
					BX.message("BX24_INVITE_DIALOG_USERS_LIMIT_TITLE"),
					BX.message("BX24_INVITE_DIALOG_USERS_LIMIT_TEXT")
				);
			}
			else
			{
				this.parent.showErrorMessage(response.errors[0].message);
			}
		});
	}

	disableSubmitButton(isDisable)
	{
		const button = this.parent.button;

		if (!Type.isDomNode(button) || !Type.isBoolean(isDisable))
		{
			return;
		}

		if (isDisable)
		{
			this.waitingResponse = true;
			Dom.addClass(button, ["ui-btn-wait", "invite-cursor-auto"]);
		}
		else
		{
			this.waitingResponse = false;
			Dom.removeClass(button, ["ui-btn-wait", "invite-cursor-auto"]);
		}
	}

	sendSuccessEvent(users)
	{
		BX.SidePanel.Instance.postMessageAll(window, "BX.Intranet.Invitation:onAdd", { users: users });
	}
}

import {Type, Validation, Loc, Dom, Event} from "main.core";
import {EventEmitter} from "main.core.events";

export class Submit extends EventEmitter
{
	constructor(parent)
	{
		super();
		this.parent = parent;
		this.setEventNamespace('BX.Intranet.Invitation.Submit');

		this.parent.subscribe('onButtonClick', (event) => {

		});
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

	prepareGroupAndDepartmentData(inputs, form)
	{
		let groups = [];
		let departments = [];

		function checkValue(element)
		{
			const value = element.value;

			if (value.match(/^SG(\d+)$/))
			{
				groups.push(value);
			}
			else if (value.match(/^DR(\d+)$/))
			{
				departments.push(parseInt(value.replace('DR', '')));
			}
			else if (value.match(/^D(\d+)$/))
			{
				departments.push(parseInt(value.replace('D', '')));
			}
		}

		for (let i = 0, len = inputs.length; i < len; i++)
		{
			if (Type.isArrayLike(inputs[i])) //check RadioNodeList
			{
				inputs[i].forEach(element => {
					checkValue(element);
				});
			}
			else
			{
				checkValue(inputs[i]);
			}
		}

		return {groups: groups, departments: departments};
	}

	submitInvite()
	{
		const inviteForm = this.parent.contentBlocks["invite"].querySelector("form");
		let [items, errorInputData] = [...this.parseEmailAndPhone(inviteForm)];

		if (errorInputData.length > 0)
		{
			const event = new Event.BaseEvent({data: {error: Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR") + ": " + errorInputData.join(', ')}});
			this.emit('onInputError', event);
			return;
		}

		if (items.length <= 0)
		{
			const event = new Event.BaseEvent({data: {error: Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR")}});
			this.emit('onInputError', event);
			return;
		}

		const requestData = {
			"ITEMS": items
		};

		this.sendAction("invite", requestData);
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
			const event = new Event.BaseEvent({data: {error: Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR")}});
			this.emit('onInputError', event);
			return;
		}

		let requestData = {
			"ITEMS": items
		};

		if (!Type.isUndefined(inviteWithGroupDpForm["GROUP_AND_DEPARTMENT[]"]))
		{
			let arGroupsAndDepartmentInput;

			if (typeof inviteWithGroupDpForm["GROUP_AND_DEPARTMENT[]"].value == 'undefined')
			{
				arGroupsAndDepartmentInput = inviteWithGroupDpForm["GROUP_AND_DEPARTMENT[]"];
			}
			else
			{
				arGroupsAndDepartmentInput = [
					inviteWithGroupDpForm["GROUP_AND_DEPARTMENT[]"]
				];
			}

			let groupsAndDepartmentId = this.prepareGroupAndDepartmentData(arGroupsAndDepartmentInput, inviteWithGroupDpForm);

			if (Type.isArray(groupsAndDepartmentId["groups"]))
			{
				requestData["SONET_GROUPS_CODE"] = groupsAndDepartmentId["groups"];
			}

			if (Type.isArray(groupsAndDepartmentId["departments"]))
			{
				requestData["UF_DEPARTMENT"] = groupsAndDepartmentId["departments"];
			}
		}

		this.sendAction("inviteWithGroupDp", requestData);
	}

	submitSelf()
	{
		const selfForm = this.parent.contentBlocks["self"].querySelector("form");
		let obRequestData = {
			"allow_register": selfForm["allow_register"].checked ? "Y" : "N",
			'allow_register_confirm': selfForm["allow_register_confirm"].checked ? "Y" : "N",
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
			const event = new Event.BaseEvent({data: {error: Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR")}});
			this.emit('onInputError', event);
			return;
		}

		let requestData = {
			"ITEMS": items
		};

		if (!Type.isUndefined(extranetForm["GROUP_AND_DEPARTMENT[]"]))
		{
			let arGroupsInput;

			if (typeof extranetForm["GROUP_AND_DEPARTMENT[]"].value == 'undefined')
			{
				arGroupsInput = extranetForm["GROUP_AND_DEPARTMENT[]"];
			}
			else
			{
				arGroupsInput = [
					extranetForm["GROUP_AND_DEPARTMENT[]"]
				];
			}

			let groupsAndDepartmentId = this.prepareGroupAndDepartmentData(arGroupsInput, extranetForm);

			if (Type.isArray(groupsAndDepartmentId["groups"]))
			{
				requestData["SONET_GROUPS_CODE"] = groupsAndDepartmentId["groups"];
			}
		}

		this.sendAction("extranet", requestData);
	}

	submitIntegrator()
	{
		const integratorForm = this.parent.contentBlocks["integrator"].querySelector("form");

		const obRequestData = {
			"integrator_email": integratorForm["integrator_email"].value,
		};

		this.sendAction("inviteIntegrator", obRequestData);
	}

	submitMassInvite()
	{
		const massInviteForm = this.parent.contentBlocks["mass-invite"].querySelector("form");

		const obRequestData = {
			"ITEMS": massInviteForm["mass_invite_emails"].value,
		};

		this.sendAction("massInvite", obRequestData);
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

		if (!Type.isUndefined(addForm["GROUP_AND_DEPARTMENT[]"]))
		{
			let arGroupsAndDepartmentInput;

			if (typeof addForm["GROUP_AND_DEPARTMENT[]"].value == 'undefined')
			{
				arGroupsAndDepartmentInput = addForm["GROUP_AND_DEPARTMENT[]"];
			}
			else
			{
				arGroupsAndDepartmentInput = [
					addForm["GROUP_AND_DEPARTMENT[]"]
				];
			}

			let groupsAndDepartmentId = this.prepareGroupAndDepartmentData(arGroupsAndDepartmentInput, addForm);

			if (Type.isArray(groupsAndDepartmentId["groups"]))
			{
				requestData["SONET_GROUPS_CODE"] = groupsAndDepartmentId["groups"];
			}

			if (Type.isArray(groupsAndDepartmentId["departments"]))
			{
				requestData["DEPARTMENT_ID"] = groupsAndDepartmentId["departments"];
			}
		}

		this.sendAction("add", requestData);
	}

	sendAction(action, requestData)
	{
		this.disableSubmitButton(true);
		requestData["userOptions"] = this.parent.userOptions;

		BX.ajax.runComponentAction(this.parent.componentName, action, {
			signedParameters: this.parent.signedParameters,
			mode: 'ajax',
			data: requestData
		}).then(function (response) {

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

		}.bind(this), function (response) {

			this.disableSubmitButton(false);

			if (response.data == "user_limit")
			{
				B24.licenseInfoPopup.show('featureID', BX.message("BX24_INVITE_DIALOG_USERS_LIMIT_TITLE"), BX.message("BX24_INVITE_DIALOG_USERS_LIMIT_TEXT"));
			}
			else
			{
				this.parent.showErrorMessage(response.errors[0].message);
			}
		}.bind(this));
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
			Dom.addClass(button, "ui-btn-wait");
			button.style.cursor = 'auto';
		}
		else
		{
			Dom.removeClass(button, "ui-btn-wait");
			button.style.cursor = 'pointer';
		}
	}

	sendSuccessEvent(users)
	{
		BX.SidePanel.Instance.postMessageAll(window, 'BX.Intranet.Invitation:onAdd', { users: users });
	}
}
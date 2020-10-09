import {Type, Dom, Event, Loc} from 'main.core';
import {Submit} from './submit';
import {SelfRegister} from './self-register';
import {Row} from "./row";
import {EventEmitter} from "main.core.events";

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
		this.isInvitationBySmsAvailable = params.isInvitationBySmsAvailable === "Y";
		this.regenerateUrlBase = params.regenerateUrlBase;

		if (Type.isDomNode(this.contentContainer))
		{
			const blocks = this.contentContainer.querySelectorAll(".js-intranet-invitation-block");
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
			this.menuItems = this.menuContainer.querySelectorAll("a");

			(this.menuItems || []).forEach((item) => {
				Event.bind(item, 'click', () => {
					this.changeContent(item.getAttribute('data-action'));
				});
			});

			this.changeContent(this.menuItems[0].getAttribute('data-action'));
		}

		this.submit = new Submit(this);
		this.submit.subscribe('onInputError', (event) => {
			this.showErrorMessage(event.data.error);
		});

		if (this.isCloud)
		{
			this.selfRegister = new SelfRegister(this);
		}
	}

	changeContent(action)
	{
		this.hideErrorMessage();
		this.hideSuccessMessage();

		if (action.length > 0)
		{
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
						row.renderInviteInputs(5);
					}
					else if (action === 'invite-with-group-dp')
					{
						row.renderInviteInputs(3);
					}
					else if (action === 'extranet')
					{
						row.renderInviteInputs(3);
					}
					else if (action === "add")
					{
						row.renderRegisterInputs();
					}
					else if (action === "integrator")
					{
						row.renderIntegratorInput();
					}
				}
				else
				{
					Dom.removeClass(block, 'invite-block-shown');
					Dom.addClass(block, 'invite-block-hidden');
				}
			}

			this.changeButton(action);
		}
	}

	changeButton(action)
	{
		Event.unbindAll(this.button, 'click');

		if (action === "invite")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');

			Event.bind(this.button, 'click',() => {
				this.submit.submitInvite();
			});
		}
		else if (action === "mass-invite")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');

			Event.bind(this.button, 'click',() => {
				this.submit.submitMassInvite();
			});
		}
		else if (action === "invite-with-group-dp")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');

			Event.bind(this.button, 'click',() => {
				this.submit.submitInviteWithGroupDp();
			});
		}
		else if (action === "add")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_ADD');

			Event.bind(this.button, 'click', () => {
				this.submit.submitAdd();
			});
		}
		else if (action === "self")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_SAVE');

			Event.bind(this.button, 'click', () => {
				this.submit.submitSelf();
			});
		}
		else if (action === "integrator")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');

			Event.bind(this.button, 'click', () => {
				this.submit.submitIntegrator();
			});
		}
		else if (action === "extranet")
		{
			this.button.innerText = Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');

			Event.bind(this.button, 'click', () => {
				this.submit.submitExtranet();
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
}
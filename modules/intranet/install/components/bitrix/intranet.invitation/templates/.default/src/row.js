import {Event, Type, Dom, Tag, Loc} from "main.core";
import {Phone} from "./phone";

export class Row
{
	constructor(parent, params)
	{
		this.parent = parent;
		this.contentBlock = params.contentBlock;
		this.inputNum = 0;

		if (Type.isDomNode(this.contentBlock))
		{
			this.rowsContainer = this.contentBlock.querySelector("[data-role='rows-container']");
			this.bindActions();
		}

		if (this.parent.isInvitationBySmsAvailable)
		{
			this.phoneObj = new Phone(this);
		}
	}

	bindActions()
	{
		const moreButton = this.contentBlock.querySelector("[data-role='invite-more']");
		if (Type.isDomNode(moreButton))
		{
			Event.unbindAll(moreButton);
			Event.bind(moreButton, 'click', () => {
				this.renderInputRow();
			});
		}

		const massInviteButton = this.contentBlock.querySelector("[data-role='invite-mass']");
		if (Type.isDomNode(massInviteButton))
		{
			Event.unbindAll(massInviteButton);
			Event.bind(massInviteButton, 'click', () => {
				const massMenuNode = document.querySelector("[data-role='menu-mass-invite']");
				if (Type.isDomNode(massMenuNode))
				{
					BX.fireEvent(massMenuNode, 'click');
				}
			});
		}
	}

	checkPhoneInput(element)
	{
		const phoneExp = /^[\d+][\d\(\)\ -]{2,14}\d$/;

		if (element.value && phoneExp.test(String(element.value).toLowerCase()))
		{
			this.phoneObj.renderPhoneRow(element);
		}
	}

	bindPhoneChecker(element)
	{
		if (this.parent.isInvitationBySmsAvailable && Type.isDomNode(element))
		{
			const inputNodes = Array.prototype.slice.call(element.querySelectorAll(".js-email-phone-input"));
			if (inputNodes)
			{
				inputNodes.forEach(element => {
					Event.bind(element, 'input', () => {
						this.checkPhoneInput(element);
					});
				});
			}
		}
	}

	bindCloseIcons(container)
	{
		const inputNodes = Array.prototype.slice.call(container.querySelectorAll("input"));

		(inputNodes || []).forEach((node) => {
			let closeIcon = node.nextElementSibling;

			Event.bind(node, 'input', () => {
				Dom.style(closeIcon, 'display', node.value !== "" ? "block" : "none");
			});

			Event.bind(closeIcon, 'click', (event) => {
				event.preventDefault();
				node.value = "";

				if (Type.isDomNode(node.parentNode))
				{
					const phoneBlock = node.parentNode.querySelector("[data-role='phone-block']");
					if (Type.isDomNode(phoneBlock))
					{
						const newInput = Tag.render`
							<input
								name="EMAIL[]"
								type="text"
								maxlength="50"
								data-num="${node.getAttribute('data-num')}"
								class="ui-ctl-element js-email-phone-input"
								placeholder="${Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT')}"
							/>`;

						Dom.replace(node, newInput);
						this.bindCloseIcons(newInput.parentNode);
						this.bindPhoneChecker(newInput.parentNode);
						Dom.remove(phoneBlock);
					}
				}

				Dom.style(closeIcon, 'display', "none");
			});
		});
	}

	renderInviteInputs(numRows = 3)
	{
		Dom.clean(this.rowsContainer);
		for (let i = 0; i < numRows; i++)
		{
			this.renderInputRow(i === 0);
		}
	}

	renderInputRow(showTitles = false)
	{
		let emailTitle, nameTitle, lastNameTitle;

		if (showTitles)
		{
			emailTitle = `
				<div class="ui-ctl-label-text">
					${Loc.getMessage("INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT")}
				</div>`;

			nameTitle = `
				<div class="ui-ctl-label-text">
					${Loc.getMessage("BX24_INVITE_DIALOG_ADD_NAME_TITLE")}
				</div>`;

			lastNameTitle = `
				<div class="ui-ctl-label-text">
					${Loc.getMessage("BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE")}
				</div>`;

		}

		const element = Tag.render`
			<div class="invite-form-row js-form-row">
				<div class="invite-form-col">
					${showTitles ? emailTitle: ''}
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
						<input 
							name="EMAIL[]" 
							type="text" 
							maxlength="50"
							data-num="${this.inputNum++}" 
							class="ui-ctl-element js-email-phone-input" 
							placeholder="${Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT')}"
						/>
						<button class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
					</div>
				</div>
				<div class="invite-form-col">
					${showTitles ? nameTitle: ''}
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
						<input name="NAME[]" type="text" class="ui-ctl-element" placeholder="${Loc.getMessage('BX24_INVITE_DIALOG_ADD_NAME_TITLE')}">
						<button class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
					</div>
				</div>
				<div class="invite-form-col">
					${showTitles ? lastNameTitle: ''}
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
						<input name="LAST_NAME[]" type="text" class="ui-ctl-element" placeholder="${Loc.getMessage('BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE')}">
						<button class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
					</div>
				</div>
			</div>
		`;

		Dom.append(element, this.rowsContainer);
		this.bindCloseIcons(element);
		this.bindPhoneChecker(element);
	}

	renderRegisterInputs()
	{
		Dom.clean(this.rowsContainer);
		const element = Tag.render`
			<div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="ui-ctl-label-text">${Loc.getMessage("BX24_INVITE_DIALOG_ADD_NAME_TITLE")}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_NAME" id="ADD_NAME" class="ui-ctl-element">
							<button class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>
					</div>
				</div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="ui-ctl-label-text">${Loc.getMessage("BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE")}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_LAST_NAME" id="ADD_LAST_NAME" class="ui-ctl-element">
							<button class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>
					</div>
				</div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="ui-ctl-label-text">${Loc.getMessage("BX24_INVITE_DIALOG_ADD_EMAIL_TITLE")}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_EMAIL" id="ADD_EMAIL" class="ui-ctl-element" maxlength="50">
							<button class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>
					</div>
				</div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="ui-ctl-label-text">${Loc.getMessage("BX24_INVITE_DIALOG_ADD_POSITION_TITLE")}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_POSITION" id="ADD_POSITION" class="ui-ctl-element">
							<button class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>			
					</div>
				</div>
			</div>
		`;

		Dom.append(element, this.rowsContainer);
		this.bindCloseIcons(element);
	}

	renderIntegratorInput()
	{
		Dom.clean(this.rowsContainer);
		const element = Tag.render`
			<div class="invite-form-row">
				<div class="invite-form-col">
					<div class="ui-ctl-label-text">${Loc.getMessage("INTRANET_INVITE_DIALOG_INTEGRATOR_EMAIL")}</div>
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
						<input 
							type="text" 
							class="ui-ctl-element" 
							value="" 
							maxlength="50"
							name="integrator_email" 
							id="integrator_email" 
							placeholder="${Loc.getMessage("INTRANET_INVITE_DIALOG_INTEGRATOR_EMAIL")}"
						/>
						<button class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
					</div>
				</div>
			</div>
		`;

		Dom.append(element, this.rowsContainer);
		this.bindCloseIcons(element);
	}
}
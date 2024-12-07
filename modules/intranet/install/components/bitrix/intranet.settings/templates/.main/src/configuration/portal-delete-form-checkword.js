import { PortalDeleteForm } from './portal-delete-form';
import {ajax, Event, Loc, Tag} from "main.core";
import PortalDeleteFormTypes from './portal-delete-form';

export class PortalDeleteFormCheckword extends PortalDeleteForm
{
	#checkWord: string;
	#inputNode: ?HTMLElement;
	#inputContainer: ?HTMLElement;

	constructor(checkWord: string) {
		super();

		this.#checkWord = checkWord;
		this.getConfirmButton().setState(BX.UI.Button.State.DISABLED);

		Event.bind(this.getInputNode(), 'input', (event) => {
			if (event.target.value === this.#checkWord)
			{
				this.getConfirmButton().setState(null);
			}
			else
			{
				this.getConfirmButton().setState(BX.UI.Button.State.DISABLED);
			}
		});
	}

	onConfirmEventHandler(): void
	{
		if (this.getInputNode().value === this.#checkWord)
		{
			this.getConfirmButton().setWaiting(true);

			ajax.runAction('bitrix24.portal.deletePortal', { data: { checkWord: this.getInputNode().value } })
				.then(() => {
					top.window.location.reload();
				})
				.catch((reject) => {
					reject.errors.forEach((error) => {
						this.getConfirmButton().setWaiting(false);
						top.BX.UI.Notification.Center.notify({
							content: error.message,
							position: 'bottom-right',
						});
					})
				});
		}
	}

	getDescription(): HTMLElement
	{
		return Tag.render`
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_CHECKWORD', {'#CHECKWORD#': this.#checkWord})}
		`;
	}

	getInputContainer(): HTMLElement
	{
		if (!this.#inputContainer)
		{
			this.#inputContainer = Tag.render`
				<div class="ui-ctl ui-ctl-textbox ui-ctl-w75 --delete">
					${this.getInputNode()}
				</div>
			`;
		}

		return this.#inputContainer;
	}
	getInputNode(): HTMLInputElement
	{
		if (!this.#inputNode)
		{
			this.#inputNode = Tag.render`
				<input 
					data-bx-role="delete-portal-checkword"
					onchange="event.stopPropagation()"
					name="deletePortalCheckWord" 
					type="text" 
					class="ui-ctl-element" 
					placeholder="${Loc.getMessage('INTRANET_SETTINGS_FIELD_DELETE_PORTAL_CHECKWORD_PLACEHOLDER', {'#CHECKWORD#': this.#checkWord})}"
				>
			`;
		}

		return this.#inputNode;
	}
}
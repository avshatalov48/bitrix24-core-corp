import { Tag, Dom, Loc, Text, Event } from 'main.core';
import { CancelButton, CreateButton } from 'ui.buttons';
import { Popup } from 'main.popup';
import './create-list.css';

export class CreateListPopup
{
	async show(inputText: string = null): Promise<any>
	{
		return new Promise((resolve) => {
			const inputId = `listNameInput_${Date.now()}`;
			const input = Tag.render`
				<input
					type="text"
					id="${inputId}"
					class="ui-ctl-element"
					placeholder="${Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_POPUP_INPUT_PLACEHOLDER')}"
					value="${inputText === null ? '' : Text.encode(inputText)}"
				>
			`;

			const popup = new Popup(`listNamePopup_${inputId}`, null, {
				draggable: false,
				overlay: true,
				width: 500,
				height: 280,
				padding: 0,
				closeByEsc: true,
				closeIcon: true,
				className: 'sign-signers-grid-create-list-popup',

				content: Tag.render`
					<div class="sign-create-list-popup-item-container-wrapper">
						<span class="sign-create-list-title-titlebar">${Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_POPUP_TITLE')}</span>
						<div class="sign-create-list-popup-item-container">
							<div class="sign-create-list-title-input-container">
								${input}
							</div>
							<span style="text-align: left; width: 100%">${Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_DESCRIPTION')}</span>
						</div>
					</div>
				`,

				buttons: [
					new CreateButton({
						text: inputText === null
							? Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_CREATE_BUTTON_TEXT')
							: Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_SAVE_BUTTON_TEXT'),
						round: true,
						events: {
							click: async () => {
								try
								{
									const listName = await this.#handleSave(input);
									resolve(listName);
									popup.close();
								}
								catch (error)
								{
									this.#showError(error);
								}
							},
						},
					}),
					new CancelButton({
						text: Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_CANCEL_BUTTON_TEXT'),
						events: {
							click()
							{
								popup.close();
							},
						},
					}),
				],

				events: {
					onPopupShow() {
						Dom.style(this.popupContainer, 'backgroundColor', 'rgba(255, 255, 255)');
					},
					onAfterShow: () => {
						input.focus();

						if (inputText !== null && inputText.length > 1)
						{
							input.setSelectionRange(input.value.length, input.value.length);
						}

						Event.bind(input, 'keydown', async (event) => {
							if (event.key === 'Enter')
							{
								try
								{
									const listName = await this.#handleSave(input);
									resolve(listName);
									popup.close();
								}
								catch (error)
								{
									this.#showError(error);
								}
							}
						});
					},
				},
			});

			popup.show();
		});
	}

	async #handleSave(input: HTMLElement): Promise<string>
	{
		const listName = input ? input.value : '';

		if (!listName)
		{
			throw new Error(Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_HINT_TITLE_NOT_EMPTY'));
		}

		return listName;
	}

	#showError(error: Error): void
	{
		BX.UI.Notification.Center.notify({
			content: error.message,
		});
	}
}

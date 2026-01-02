import { Dom, Loc, Event } from 'main.core';

export class CreateFolderPopup
{
	async show(inputText: string = null): Promise<any>
	{
		return new Promise((resolve) => {
			const uniqueId = `folderNameInput_${Date.now()}`;

			const handleSubmit = () => {
				const folderName = document.getElementById(uniqueId).value;
				if (folderName)
				{
					resolve(folderName);
					popup.close();
				}
				else
				{
					BX.UI.Notification.Center.notify({
						content: Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_HINT_TITLE_NOT_EMPTY'),
					});
				}
			};

			const popup = new BX.PopupWindow(`folderNamePopup_${uniqueId}`, null, {
				className: 'sign-b2e-grid-templates-popup',
				content: `
					<div class="sign-create-folder-popup-item-container">
						<span class="sign-create-folder-popup-icon"></span>
						<div class="sign-create-folder-title-input-container">
							<input
							type="text"
							id="${uniqueId}"
							class="ui-ctl-element"
							placeholder="${Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_POPUP_INPUT_PLACEHOLDER')}"
							value="${inputText === null ? '' : BX.util.htmlspecialchars(inputText)}"
							>
						</div>
					</div>
				`,
				buttons: [
					new BX.PopupWindowButton({
						text: inputText === null
							? Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_CREATE_BUTTON_TEXT')
							: Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_SAVE_BUTTON_TEXT'),
						className: 'popup-window-button-blue',
						events: {
							click: handleSubmit,
						},
					}),
					new BX.PopupWindowButton({
						text: Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_CANCEL_BUTTON_TEXT'),
						events: {
							click()
							{
								popup.close();
							},
						},
					}),
				],
				draggable: true,
				overlay: true,
				width: 500,
				height: 420,
				events: {
					onPopupShow() {
						Dom.style(this.popupContainer, 'backgroundColor', 'rgba(255, 255, 255)');
						const input = document.getElementById(uniqueId);
						input.focus();
						input.setSelectionRange(input.value.length, input.value.length);
						Event.bind(input, 'keydown', (event) => {
							if (event.key === 'Enter')
							{
								event.preventDefault();
								handleSubmit();
							}
						});
					},
				},
			});

			popup.show();
		});
	}
}
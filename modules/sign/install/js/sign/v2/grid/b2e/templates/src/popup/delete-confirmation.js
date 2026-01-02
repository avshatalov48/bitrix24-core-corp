import { Dom, Loc } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';
import { TemplateEntity, TemplateEntityType } from 'sign.type';
import { Templates } from '../index';
import { FeatureStorage } from 'sign.feature-storage';

export class DeleteConfirmationPopup
{
	async show(entityType: TemplateEntityType, onConfirm: () => Promise<void>)
	{
		const messageContent = document.createElement('div');
		messageContent.innerHTML = this.#getMessageContentForDeleteMessageBox(entityType);
		Dom.style(messageContent, 'margin-top', '5%');
		Dom.style(messageContent, 'color', '#535c69');

		MessageBox.show({
			title: this.#getTitleForDeleteMessageBox(entityType),
			message: messageContent.outerHTML,
			modal: true,
			buttons: [
				new BX.UI.Button({
					text: Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_POPUP_YES'),
					color: BX.UI.Button.Color.PRIMARY,
					onclick: async (button) => {
						try
						{
							await onConfirm();
							window.top.BX.UI.Notification.Center.notify({
								content: this.#getSuccessNotificationForDeleteMessageBox(entityType),
							});
						}
						catch
						{
							if (entityType !== TemplateEntity.folder)
							{
								window.top.BX.UI.Notification.Center.notify({
									content: this.#getFailNotificationForDeleteMessageBox(entityType),
								});
							}
						}

						const templateGrid = new Templates();
						await templateGrid.reload();
						button.getContext().close();
					},
				}),
				new BX.UI.Button({
					text: Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_POPUP_NO'),
					color: BX.UI.Button.Color.LINK,
					onclick: (button) => {
						button.getContext().close();
					},
				}),
			],
		});
	}

	#getMessageContentForDeleteMessageBox(entityType: TemplateEntityType): ?string
	{
		switch (entityType)
		{
			case TemplateEntity.template:
				return Loc.getMessage('SIGN_TEMPLATE_DELETE_CONFIRMATION_MESSAGE');
			case TemplateEntity.folder:
				return Loc.getMessage('SIGN_FOLDER_DELETE_CONFIRMATION_MESSAGE');
			case TemplateEntity.multiple:
				return FeatureStorage.isTemplateFolderGroupingAllowed()
					? Loc.getMessage('SIGN_MULTIPLE_DELETE_CONFIRMATION_MESSAGE')
					: Loc.getMessage('SIGN_MULTIPLE_DELETE_TEMPLATES_CONFIRMATION_MESSAGE');
			default:
				return null;
		}
	}

	#getTitleForDeleteMessageBox(entityType: TemplateEntityType): ?string
	{
		switch (entityType)
		{
			case TemplateEntity.template:
				return Loc.getMessage('SIGN_TEMPLATE_DELETE_CONFIRMATION_TITLE');
			case TemplateEntity.folder:
				return Loc.getMessage('SIGN_FOLDER_DELETE_CONFIRMATION_TITLE');
			case TemplateEntity.multiple:
				return Loc.getMessage('SIGN_MULTIPLE_DELETE_CONFIRMATION_TITLE');
			default:
				return null;
		}
	}

	#getSuccessNotificationForDeleteMessageBox(entityType: TemplateEntityType): ?string
	{
		switch (entityType)
		{
			case TemplateEntity.template:
				return Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_HINT_SUCCESS');
			case TemplateEntity.folder:
				return Loc.getMessage('SIGN_FOLDER_GRID_DELETE_HINT_SUCCESS');
			case TemplateEntity.multiple:
				return FeatureStorage.isTemplateFolderGroupingAllowed()
					? Loc.getMessage('SIGN_MULTIPLE_GRID_DELETE_HINT_SUCCESS')
					: Loc.getMessage('SIGN_MULTIPLE_GRID_DELETE_HINT_TEMPLATES_SUCCESS');
			default:
				return null;
		}
	}

	#getFailNotificationForDeleteMessageBox(entityType: TemplateEntityType): ?string
	{
		switch (entityType)
		{
			case TemplateEntity.template:
				return Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_HINT_FAIL');
			case TemplateEntity.folder:
				return Loc.getMessage('SIGN_FOLDER_GRID_DELETE_HINT_FAIL');
			case TemplateEntity.multiple:
				return FeatureStorage.isTemplateFolderGroupingAllowed()
					? Loc.getMessage('SIGN_MULTIPLE_GRID_DELETE_HINT_FAIL')
					: Loc.getMessage('SIGN_MULTIPLE_GRID_DELETE_HINT_TEMPLATES_FAIL');
			default:
				return null;
		}
	}
}

import { ClientRegistrationOptions } from './types';
import { ajax as Ajax, Loc, Extension } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';

const DEFAULT_LANGUAGE_ID = 'en';

export default class ClientUnRegistration
{
	messageBox: MessageBox;
	options: ClientRegistrationOptions;
	popupContainerId = 'content-register-modal';

	constructor(options: ClientRegistrationOptions)
	{
		this.options = options;

		this.bindEvents();
	}

	bindEvents()
	{}

	start()
	{
		this.messageBox = MessageBox.create({
			title: Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_UNREGISTRATION_TITLE', { '#NAME#': this.#getServiceName() }),
			message: Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_UNREGISTRATION_MSG', { '#NAME#': this.#getServiceName() }),
			buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_UNREGISTRATION_UNREGISTER_BTN'),
			onOk: () => {
				this.handleClickUnregister();
			},
		});
		this.messageBox.show();
	}

	#getLanguageId(): string
	{
		return Loc.hasMessage('LANGUAGE_ID') ? Loc.getMessage('LANGUAGE_ID') : DEFAULT_LANGUAGE_ID;
	}

	#getServiceName(): string
	{
		return Extension.getSettings('disk.b24-documents-client-registration').get('serviceName');
	}

	handleClickUnregister()
	{
		Ajax.runAction('ai.integration.b24cloudai.unregister', {
			data: {
				languageId: this.#getLanguageId(),
			},
		}).then(() => {
			document.location.reload();
		}).catch((response) => {
			// eslint-disable-next-line no-console
			console.warn('Unregistration error', response);

			this.messageBox.setMessage(this.#buildUsefulErrorText(response.errors || []));
		});
	}

	#buildUsefulErrorText(errors: Array): string
	{
		for (const error of errors)
		{
			if (error.code === 'should_show_in_ui')
			{
				return error.message;
			}
		}

		return Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_UNREGISTRATION_ERROR_COMMON');
	}
}

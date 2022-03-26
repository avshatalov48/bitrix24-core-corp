import {ClientRegistrationOptions} from "./types";
import {ajax as Ajax, Loc, Extension} from "main.core";
import {MessageBox} from "ui.dialogs.messagebox";

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
			title: Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_UNREGISTRATION_TITLE', {'#NAME#' : this.#getServiceName()}),
			message: Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_UNREGISTRATION_MSG', {'#NAME#' : this.#getServiceName()}),
			buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_UNREGISTRATION_UNREGISTER_BTN'),
			onOk: () => {
				console.log(this);
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
		Ajax.runAction('disk.api.integration.b24documents.unregisterCloudClient', {
			data: {
				languageId: this.#getLanguageId(),
			}
		}).then(() => {
			document.location.reload();
		}).catch((response) => {
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

		return Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_UNREGISTRATION_ERROR_COMMON');
	}
};
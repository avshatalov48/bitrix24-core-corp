import {Extension, Loc} from "main.core";
import {QrAuthorization} from "ui.qrauthorization";

export class QrAuth
{
	qr;
	title;
	content;
	popup;

	constructor(options = {})
	{
		this.settingsCollection = Extension.getSettings('crm.terminal');

		this.qr = options.qr || this.settingsCollection.get('qr');
		this.title = options.title || Loc.getMessage('TERMINAL_QR_AUTH_TITLE');
		this.content = options.content || Loc.getMessage('TERMINAL_QR_AUTH_CONTENT_MSGVER_1');

		this.popup = null;

		this.#createQrAuthorization();
	}

	#createQrAuthorization()
	{
		if (!this.popup)
		{
			this.popup = new QrAuthorization({
				qr: this.qr,
				title: this.title,
				content: this.content,
				popupParam: {
					overlay: true
				}
			});
		}
	}

	show()
	{
		this.popup.show();
	}
}
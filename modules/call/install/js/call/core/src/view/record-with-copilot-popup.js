import '../css/record-with-copilot-popup.css';

import { Dom } from 'main.core';

type RecordWithCopilotPopupOptions = {
	onClickYesButton?: 'function',
	onClickNoButton?: 'function',
	onClose?: 'function',
	title: string,
	message: string,
	yesButtonText: string,
	noButtonText: string,
};

export class RecordWithCopilotPopup {
	constructor(config: RecordWithCopilotPopupOptions)
	{
		this.popup = null;

		this.callbacks = {
			onClickYesButton: BX.type.isFunction(config.onClickYesButton) ? config.onClickYesButton : BX.DoNothing,
			onClickNoButton: BX.type.isFunction(config.onClickNoButton) ? config.onClickNoButton : BX.DoNothing,
			onClose: BX.type.isFunction(config.onClose) ? config.onClose : BX.DoNothing,
		};

		this.title = BX.type.isString(config.title) ? config.title : '';
		this.message = BX.type.isString(config.message) ? config.message : '';
		this.yesButtonText = BX.type.isString(config.yesButtonText) ? config.yesButtonText : '';
		this.noButtonText = BX.type.isString(config.noButtonText) ? config.noButtonText : '';
	}

	create()
	{
		this.popup = BX.UI.Dialogs.MessageBox.create({
			modal: true,
			popupOptions: {
				content: Dom.create("div", {
					props: {className: 'bx-messenger-videocall-record-alert-popup-content'},
					children: [
						Dom.create("div", {
							props: {className: 'bx-messenger-videocall-record-alert-title'},
							text: this.title,
						}),
						Dom.create("div", {
							props: {className: 'bx-messenger-videocall-record-alert-message'},
							text: this.message,
						}),
						Dom.create("div", {
							props: {className: 'bx-messenger-videocall-record-alert-actions'},
							children: [
								Dom.create("button", {
									props: {className: 'bx-messenger-videocall-record-alert-button bx-messenger-videocall-record-alert-button-yes'},
									text: this.yesButtonText,
									events: {
										click: () => {
											this.callbacks.onClickYesButton();
											this.popup.close();
										}
									}
								}),
								Dom.create("button", {
									props: {className: 'bx-messenger-videocall-record-alert-button bx-messenger-videocall-record-alert-button-no'},
									text: this.noButtonText,
									events: {
										click: () => {
											this.callbacks.onClickNoButton();
											this.popup.close();
										}
									}
								}),
							]
						}),
					]
				}),
				className : "bx-messenger-videocall-record-alert-popup",
				darkMode: true,
				contentBackground: '#22272B',
				contentBorderRadius: '10px',
				autoHide: false,
				closeByEsc: false,
				closeIcon: true,
				contentNoPaddings: true,
				width: 420,
				maxWidth: 420,
				animation: "fading",
				events: {
					onPopupClose: ()=>
					{
						this.callbacks.onClose();
						this.popup = null;
					},
				},
			},
		});
	}

	show()
	{
		this.close();
		this.create();

		this.popup.show();
	}

	close()
	{
		if (this.popup)
		{
			this.popup.close();
		}
	}
}

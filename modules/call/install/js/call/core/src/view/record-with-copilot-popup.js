import '../css/record-with-copilot-popup.css';

import { Dom } from 'main.core';

type RecordWithCopilotPopupOptions = {
	onClickYesButton?: 'function',
	onClickNoButton?: 'function',
	onClose?: 'function'
};

export class RecordWithCopilotPopup {
	constructor(config: RecordWithCopilotPopupOptions)
	{
		this.popup = null;

		this.callbacks = {
			onClickYesButton: BX.type.isFunction(config.onClickYesButton) ? config.onClickYesButton : BX.DoNothing,
			onClickNoButton: BX.type.isFunction(config.onClickNoButton) ? config.onClickNoButton : BX.DoNothing,
			onClose: BX.type.isFunction(config.onClose) ? config.onClose : BX.DoNothing,
		}
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
							text: BX.message('CALL_RECORD_AUDIO_WITH_COPILOT_TITLE'),
						}),
						Dom.create("div", {
							props: {className: 'bx-messenger-videocall-record-alert-message'},
							text: BX.message('CALL_RECORD_AUDIO_WITH_COPILOT_MESSAGE'),
						}),
						Dom.create("div", {
							props: {className: 'bx-messenger-videocall-record-alert-actions'},
							children: [
								Dom.create("button", {
									props: {className: 'bx-messenger-videocall-record-alert-button bx-messenger-videocall-record-alert-button-yes'},
									text: BX.message('CALL_RECORD_AUDIO_WITH_COPILOT_YES_BUTTON'),
									events: {
										click: () => {
											this.callbacks.onClickYesButton();
											this.popup.close();
										}
									}
								}),
								Dom.create("button", {
									props: {className: 'bx-messenger-videocall-record-alert-button bx-messenger-videocall-record-alert-button-no'},
									text: BX.message('CALL_RECORD_AUDIO_WITH_COPILOT_NO_BUTTON'),
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
				autoHide: false,
				closeByEsc: false,
				closeIcon: true,
				contentNoPaddings: true,
				width: 420,
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

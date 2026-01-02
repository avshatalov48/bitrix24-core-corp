import { Loc, Runtime } from 'main.core';
import { type Popup as PopupInstance, type PopupOptions } from 'main.popup';

export type ShowPartnerFormParams = {
	partnerId: string;
	partnerName: string;
	partnerUrl: string;
	forms: PartnerForm[] | null;
	messages: { [string]: string }
};

export async function showPartnerConnectForm(params: ShowPartnerFormParams)
{
	Loc.setMessage(params.messages);
	await showPartnerFormPopup({
		...params,
		titleBar: Loc.getMessage('PARTNER_POPUP_TITLE'),
		sendButtonText: Loc.getMessage('PARTNER_POPUP_SEND_BUTTON'),
	});
}

type ShowPartnerFormPopupOptions = {
	forms: PartnerForm[] | null;
	titleBar: string;
	sendButtonText: string;
	partnerId: string;
	partnerName: string;
	partnerUrl: string;
	arParams: Object;
}
async function showPartnerFormPopup(options: ShowPartnerFormPopupOptions): Popup
{
	const titleBar = options.titleBar;
	const sendButtonText = options.sendButtonText;
	const partnerName = options.partnerName;
	const partnerUrl = options.partnerUrl;

	const [{ Popup }, { Button, ButtonColor }] = await Promise.all([
		Runtime.loadExtension('main.popup'),
		Runtime.loadExtension('ui.buttons'),
	]);

	const popupOptions: PopupOptions = {
		className: 'bitrix24-partner__popup',
		autoHide: false,
		cacheable: false,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		width: 540,
		height: 350,
		overlay: true,
		draggable: { restrict: true },
		closeByEsc: true,
		titleBar,
		closeIcon: true,
		content: `
			<div class="bitrix24-partner__popup-content">
				<div class="bitrix24-partner__popup-content_title">${Loc.getMessage('PARTNER_TITLE_FOR_NAME')}</div>
				<div class="bitrix24-partner__popup-content_main">
					<div class="bitrix24-partner__popup-content_name">${partnerName}</div>
					<a class="bitrix24-partner__popup-content_link" href="${encodeURI(partnerUrl)}" target="_blank">${Loc.getMessage('PARTNER_LINK_NAME_MORE')}</a>
				</div>
				<div class="bitrix24-partner__popup-content_desc">${Loc.getMessage('PARTNER_POPUP_DESCRIPTION_BOTTOM')}</div>
			</div>
		`,
		buttons: [
			new Button({
				color: ButtonColor.SUCCESS,
				text: sendButtonText,
				onclick: async (button: Button) => {
					setTimeout(() => {
						button.setClocking(true);
					}, 200);
					await showIntegratorApplicationForm();
					button.setClocking(false);
				},
			}),
		],
	};

	const popup: PopupInstance = new Popup(popupOptions);

	popup.show();
}

async function showIntegratorApplicationForm(): void
{
	const { PartnerForm } = await Runtime.loadExtension('ui.feedback.partnerform');
	const formParams = {
		id: `intranet-license-partner-form-${parseInt(Math.random() * 1000, 10)}`,
		source: 'intranet.bitrix24.partner-connect-form',
	};

	PartnerForm.show(formParams);
}

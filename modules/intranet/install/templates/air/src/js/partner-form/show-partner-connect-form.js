import { Loc, Runtime } from 'main.core';
import { type Popup as PopupInstance, type PopupOptions } from 'main.popup';

export type ShowPartnerFormParams = {
	partnerId: string;
	partnerName: string;
	partnerUrl: string;
	messages: { [string]: string }
};

export async function showPartnerConnectForm(params: ShowPartnerFormParams)
{
	Loc.setMessage(params.messages);
	await showPartnerFormPopup({
		...params,
		titleBar: Loc.getMessage('BX24_PARTNER_TITLE'),
		sendButtonText: Loc.getMessage('BX24_BUTTON_SEND'),
	});
}

type ShowPartnerFormPopupOptions = {
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
	const { Form } = await Runtime.loadExtension('ui.feedback.form');

	Form.open({
		id: `intranet-license-partner-form-${parseInt(Math.random() * 1000, 10)}`,
		portalUri: 'https://bitrix24.team',
		forms: [
			{ zones: ['de'], id: 883, lang: 'de', sec: 'a12ty8' },
			{ zones: ['com', 'in', 'eu', 'uk'], id: 735, lang: 'en', sec: 'vlhmlv' },
			{ zones: ['es', 'co', 'mx'], id: 900, lang: 'es', sec: 'w3qmwq' },
			{ zones: ['com.br'], id: 901, lang: 'pt', sec: 'prnm4x' },
			{ zones: ['cn/tc'], id: 902, lang: 'cn-tc', sec: 'z8isyg' },
			{ zones: ['cn'], id: 903, lang: 'cn-sc', sec: 'hsxnam' },
			{ zones: ['pl'], id: 904, lang: 'pl', sec: '2ph9ph' },
			{ zones: ['it'], id: 905, lang: 'it', sec: '2r3owa' },
			{ zones: ['fr'], id: 906, lang: 'fr', sec: 'jt2fii' },
			{ zones: ['com.tr'], id: 907, lang: 'tr', sec: 'ovevp8' },
			{ zones: ['id'], id: 908, lang: 'id', sec: '7kq7v2' },
			{ zones: ['com/my'], id: 909, lang: 'ms', sec: 'kmncmj' },
			{ zones: ['com/th'], id: 910, lang: 'th', sec: 'sknbp9' },
			{ zones: ['vn'], id: 911, lang: 'vn', sec: 'a573fy' },
			{ zones: ['jp'], id: 912, lang: 'jp', sec: '3purdq' },
			{ zones: ['ru'], id: 2122, lang: 'ru', sec: '8vralr' },
			{ zones: ['kz'], id: 2128, lang: 'ru', sec: '054phh' },
			{ zones: ['by'], id: 2129, lang: 'ru', sec: 'srs85j' },
		],
		defaultForm: { id: 735, lang: 'en', sec: 'vlhmlv' },
	});
}

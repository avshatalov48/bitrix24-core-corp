/**
 * @module communication/email-menu
 */
jn.define('communication/email-menu', (require, exports, module) => {
	const { email: emailSvg } = require('assets/communication/menu');
	const { Loc } = require('loc');
	const { copyToClipboard } = require('utils/copy');
	const { stringify } = require('utils/string');

	const pathToExtension = currentDomain + '/bitrix/mobileapp/mobile/extensions/bitrix/communication/email-menu/';
	const imagePath = pathToExtension + 'images/banner.png';

	let menu = null;

	/**
	 * @public
	 * @function openEmailMenu
	 * @param {string} email
	 */
	function openEmailMenu({ email })
	{
		email = stringify(email).trim();

		if (email === '')
		{
			return;
		}

		menu = new ContextMenu({
			actions: getMenuActions(email),
			params: {
				showActionLoader: false,
				showCancelButton: true,
				title: Loc.getMessage('EMAIL_MENU_SEND_TO', { '#EMAIL#': email }),
			},
		});

		void menu.show();
	}

	function getMenuActions(email)
	{
		return [
			{
				title: Loc.getMessage('EMAIL_MENU_NATIVE'),
				code: 'sendNativeEmail',
				data: { svgIcon: emailSvg() },
				onClickCallback: () => {
					const closeCallback = () => Application.openUrl(`mailto:${email}`);

					return Promise.resolve({ closeCallback });
				},
			},
			{
				title: Loc.getMessage('EMAIL_MENU_B24'),
				code: 'sendB24Email',
				subtitle: Loc.getMessage('EMAIL_MENU_B24_DISABLED'),
				subtitleType: 'warning',
				data: { svgIcon: SvgIcons.bitrix24 },
				onClickCallback: (action, itemId, { parentWidget }) => {
					showEmailBanner(parentWidget);

					return Promise.resolve({ closeMenu: false });
				},
			},
			{
				title: Loc.getMessage('EMAIL_MENU_COPY'),
				code: 'copy',
				data: { svgIcon: SvgIcons.copy },
				onClickCallback: () => {
					const closeCallback = () => copyToClipboard(email, Loc.getMessage('EMAIL_MENU_COPY_DONE'));

					return Promise.resolve({ closeCallback });
				},
			},
		];
	}

	function showEmailBanner(parentWidget)
	{
		const banner = new ContextMenu({
			banner: {
				featureItems: [
					Loc.getMessage('EMAIL_MENU_B24_BANNER_FEATURE_1'),
					Loc.getMessage('EMAIL_MENU_B24_BANNER_FEATURE_2'),
					Loc.getMessage('EMAIL_MENU_B24_BANNER_FEATURE_3'),
				],
				imagePath,
				qrauth: {
					redirectUrl: currentDomain + '/mail/',
				},
				positioning: 'vertical',
				title: Loc.getMessage('EMAIL_MENU_B24_BANNER_TITLE'),
				showSubtitle: false,
				buttonText: Loc.getMessage('EMAIL_MENU_B24_BANNER_BUTTON'),
			},
			params: {
				title: Loc.getMessage('EMAIL_MENU_B24_DISABLED'),
			},
		});

		banner.show(parentWidget);
	}

	const SvgIcons = {
		bitrix24: '<svg width="23" height="18" viewBox="0 0 23 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.21444 17.04H8.90497V13.5172C8.17396 13.0792 7.57483 12.4226 7.20968 11.6106C6.49683 10.0254 6.81872 8.16609 8.02297 6.9128C9.22722 5.65951 11.0722 5.2637 12.6846 5.91274C13.4774 6.23187 14.1357 6.77215 14.6002 7.44556H19.2487C19.0182 5.02774 17.0612 3.13753 14.6819 3.13753C14.1621 3.13745 13.6463 3.22926 13.1584 3.40872C12.4724 1.5883 10.7399 0.375193 8.79461 0.353271C6.19045 0.353271 4.0814 2.5562 4.0814 5.27196C4.0814 5.30151 4.0814 5.33072 4.08349 5.36027C1.75234 6.26721 0.227253 8.52388 0.255137 11.0251C0.255137 14.3469 2.83704 17.04 6.02252 17.04C6.07073 17.04 6.11854 17.0383 6.16641 17.0365L6.21444 17.0348V17.04ZM10.905 7.44556C10.7198 7.44556 10.5405 7.47073 10.3703 7.51783C10.4115 7.45111 10.4685 7.39374 10.538 7.35157C10.699 7.25398 10.9009 7.25398 11.0618 7.35157C11.1047 7.37757 11.1428 7.40936 11.1754 7.44556H10.905ZM11.8998 9.69373L16.6562 13.0311L21.4125 9.69373H11.8998ZM22.2773 10.7001L22.2773 10.7001L22.2773 10.5284V10.7001ZM22.2773 10.7001V17.1725C22.2773 17.5717 21.9163 17.8952 21.4689 17.8952H11.8435C11.3972 17.8952 11.0351 17.5722 11.0351 17.1725V10.7001L16.6562 14.9352L22.2773 10.7001Z" fill="#6a737f"/></svg>',
		copy: '<svg width="24" height="26" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.70938 4.06836C7.59672 4.06836 6.69798 4.97645 6.70949 6.08904L6.71041 6.1788H16.7298C17.8344 6.1788 18.7298 7.07423 18.7298 8.1788V18.4835H19.5188C20.6315 18.4835 21.5302 17.5754 21.5187 16.4628L21.411 6.04768C21.3996 4.95123 20.5076 4.06836 19.4111 4.06836H8.70938ZM2.03503 10.4475C2.03503 9.34292 2.93046 8.44749 4.03503 8.44749H14.4645C15.5691 8.44749 16.4645 9.34292 16.4645 10.4475V20.8769C16.4645 21.9815 15.5691 22.8769 14.4645 22.8769H4.03503C2.93047 22.8769 2.03503 21.9815 2.03503 20.8769V10.4475ZM13.9671 10.9449H4.53244V20.3795H13.9671V10.9449Z" fill="#6a737f"/></svg>',
	};

	module.exports = { openEmailMenu, showEmailBanner };
});

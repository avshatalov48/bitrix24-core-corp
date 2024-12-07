/**
 * @module communication/email-menu
 */
jn.define('communication/email-menu', (require, exports, module) => {
	const { email: emailSvg } = require('assets/communication/menu');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { Loc } = require('loc');
	const { copyToClipboard } = require('utils/copy');
	const { stringify } = require('utils/string');
	const { Connector } = require('crm/mail/mailbox/connector');
	const AppTheme = require('apptheme');
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

	function showEmailBanner(parentWidget, successAction)
	{
		const banner = new ContextMenu({
			banner: {
				featureItems: [
					Loc.getMessage('EMAIL_MENU_B24_BANNER_FEATURE_1_2'),
					Loc.getMessage('EMAIL_MENU_B24_BANNER_FEATURE_2_2'),
					Loc.getMessage('EMAIL_MENU_B24_BANNER_FEATURE_3_2'),
				],
				imageSvg: `<svg width="116" height="116" viewBox="0 0 116 116" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M116 58C116 90.0325 90.0325 116 58 116C25.9675 116 0 90.0325 0 58C0 25.9675 25.9675 0 58 0C90.0325 0 116 25.9675 116 58Z" fill="${AppTheme.colors.accentSoftBlue2}"/><g filter="url(#filter0_d_1040_61200)"><path d="M10.7908 22.8372C10.7908 18.4189 14.3725 14.8372 18.7908 14.8372H70.2324C74.6507 14.8372 78.2324 18.4189 78.2324 22.8372V57.4186C78.2324 61.8368 74.6507 65.4186 70.2324 65.4186H18.7908C14.3725 65.4186 10.7908 61.8368 10.7908 57.4186V22.8372Z" fill="#7FDEFC"/></g><path d="M44.329 50.6252C45.2282 50.3069 45.7267 49.364 45.5409 48.4284L44.9947 45.6782C44.9947 44.463 43.3578 43.0749 40.1343 42.266C39.0422 41.9704 38.0041 41.512 37.0569 40.9072C36.8498 40.7924 36.8813 39.7312 36.8813 39.7312L35.8431 39.5778C35.8431 39.4917 35.7543 38.219 35.7543 38.219C36.9965 37.814 36.8687 35.4245 36.8687 35.4245C37.6575 35.8492 38.1713 33.9583 38.1713 33.9583C39.1043 31.3312 37.7067 31.49 37.7067 31.49C37.9512 29.8862 37.9512 28.2563 37.7067 26.6525C37.0853 21.3323 27.7297 22.7766 28.839 24.5142C26.1047 24.0254 26.7286 30.0628 26.7286 30.0628L27.3217 31.6263C26.4997 32.1437 26.6611 32.7375 26.8414 33.4008C26.9166 33.6774 26.9951 33.966 27.0069 34.2662C27.0642 35.7728 28.0142 35.4606 28.0142 35.4606C28.0728 37.9472 29.3364 38.271 29.3364 38.271C29.5737 39.8326 29.4258 39.5668 29.4258 39.5668L28.3013 39.6988C28.3166 40.0539 28.2867 40.4095 28.2126 40.7576C27.5593 41.0401 27.1593 41.265 26.7633 41.4876C26.3579 41.7155 25.9566 41.9411 25.2919 42.2238C22.7535 43.3034 19.9946 44.7074 19.5042 46.5977C19.3703 47.1137 19.239 47.7956 19.1188 48.5145C18.9683 49.4151 19.4718 50.2984 20.3321 50.6047C23.7084 51.8071 27.5091 52.5191 31.5376 52.6046H33.1811C37.1867 52.5196 40.9671 51.8152 44.329 50.6252Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M49.8838 24.9535C48.7792 24.9535 47.8838 25.8489 47.8838 26.9535V27C47.8838 28.1046 48.7792 29 49.8838 29H67.4652C68.5698 29 69.4652 28.1046 69.4652 27V26.9535C69.4652 25.8489 68.5698 24.9535 67.4652 24.9535H49.8838ZM49.8838 33.0465C48.7792 33.0465 47.8838 33.9419 47.8838 35.0465V35.093C47.8838 36.1976 48.7792 37.093 49.8838 37.093H61.3954C62.5 37.093 63.3954 36.1976 63.3954 35.093V35.0465C63.3954 33.9419 62.5 33.0465 61.3954 33.0465H49.8838Z" fill="white"/><g filter="url(#filter1_d_1040_61200)"><path d="M37.7678 56.5814C37.7678 53.2677 40.4541 50.5814 43.7678 50.5814H99.2094C102.523 50.5814 105.209 53.2677 105.209 56.5814V95.1628C105.209 98.4765 102.523 101.163 99.2094 101.163H43.7678C40.4541 101.163 37.7678 98.4765 37.7678 95.1628V56.5814Z" fill="white"/></g><rect x="88.3491" y="62.7209" width="4.72093" height="4.72093" rx="2" fill="#C3F0FF"/><rect x="88.3491" y="72.1628" width="4.72093" height="4.72093" rx="2" fill="#7FDEFC"/><rect x="88.3491" y="81.6047" width="4.72093" height="4.72093" rx="2" fill="#C3F0FF"/><path fill-rule="evenodd" clip-rule="evenodd" d="M64.8363 74.1959L52.9306 65.5033H76.742L64.8363 74.1959ZM78.9073 67.9746V67.5529L78.9073 67.9746L78.9073 67.9746ZM78.9073 67.9746L64.837 78.3792L50.7666 67.9746V83.8756C50.7666 84.8575 51.673 85.6512 52.7902 85.6512H76.8837C78.0036 85.6512 78.9073 84.8564 78.9073 83.8756L78.9073 67.9746Z" fill="#9DCF00"/><defs><filter id="filter0_d_1040_61200" x="6.79053" y="12.8372" width="75.4419" height="58.5814" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="2"/><feGaussianBlur stdDeviation="2"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.12 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1040_61200"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1040_61200" result="shape"/></filter><filter id="filter1_d_1040_61200" x="33.7676" y="48.5814" width="75.4419" height="58.5814" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="2"/><feGaussianBlur stdDeviation="2"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.12 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1040_61200"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1040_61200" result="shape"/></filter></defs></svg>`,
				onCloseBanner() {
					(new Connector({ parentWidget, successAction })).show();
				},
				positioning: 'vertical',
				title: Loc.getMessage('EMAIL_MENU_B24_BANNER_TITLE'),
				showSubtitle: false,
				buttonText: Loc.getMessage('EMAIL_MENU_B24_BANNER_BUTTON'),
			},
			params: {
				showCancelButton: false,
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

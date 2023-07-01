/**
 * @module communication/phone-menu
 */
jn.define('communication/phone-menu', (require, exports, module) => {
	const { phone: phoneSvg } = require('assets/communication/menu');
	const { Loc } = require('loc');
	const { copyToClipboard } = require('utils/copy');
	const { getFormattedNumber } = require('utils/phone');
	const { stringify } = require('utils/string');

	const pathToExtension = currentDomain + '/bitrix/mobileapp/mobile/extensions/bitrix/communication/phone-menu/';
	const imagePath = pathToExtension + 'images/banner.png';

	let menu = null;

	/**
	 * @public
	 * @function openPhoneMenu
	 * @param {object} params
	 * @param {string} params.number
	 * @param {boolean} params.canUseTelephony
	 * @param {?object} params.params
	 */
	function openPhoneMenu(params)
	{
		params = {
			...params,
			number: stringify(params.number).trim(),
		};

		if (params.number === '')
		{
			return;
		}

		menu = new ContextMenu({
			actions: getMenuActions(params),
			params: {
				showActionLoader: false,
				showCancelButton: true,
				title: Loc.getMessage('PHONE_CALL_TO', { '#PHONE#': getFormattedNumber(params.number) }),
			},
		});

		void menu.show(params.layoutWidget || PageManager);
	}

	function getMenuActions(params)
	{
		const { number, canUseTelephony } = params;

		return [
			{
				title: Loc.getMessage('PHONE_CALL_MSGVER_2'),
				code: 'callNativePhone',
				data: { svgIcon: phoneSvg() },
				onClickCallback: () => {
					const closeCallback = () => Application.openUrl(`tel:${number}`);

					return Promise.resolve({ closeCallback });
				},
			},
			{
				title: Loc.getMessage('PHONE_CALL_B24'),
				code: 'callUseTelephony',
				subtitle: !canUseTelephony && Loc.getMessage('PHONE_CALL_B24_DISABLED'),
				subtitleType: !canUseTelephony && 'warning',
				data: { svgIcon: SvgIcons.telephony },
				onClickCallback: (action, itemId, { parentWidget }) => {
					if (canUseTelephony)
					{
						const closeCallback = () => BX.postComponentEvent('onPhoneTo', [params], 'calls');

						return Promise.resolve({ closeCallback });
					}

					showTelephonyBanner(parentWidget);

					return Promise.resolve({ closeMenu: false });
				},
			},
			{
				title: Loc.getMessage('PHONE_COPY'),
				code: 'copy',
				data: { svgIcon: SvgIcons.copy },
				onClickCallback: () => {
					const closeCallback = () => copyToClipboard(number, Loc.getMessage('PHONE_COPY_DONE'));

					return Promise.resolve({ closeCallback });
				},
			},
		];
	}

	function showTelephonyBanner(parentWidget)
	{
		const banner = new ContextMenu({
			banner: {
				featureItems: [
					Loc.getMessage('PHONE_CALL_B24_BANNER_FEATURE_1'),
					Loc.getMessage('PHONE_CALL_B24_BANNER_FEATURE_2'),
					Loc.getMessage('PHONE_CALL_B24_BANNER_FEATURE_3'),
				],
				imagePath,
				qrauth: {
					redirectUrl: currentDomain + '/telephony/',
				},
				positioning: 'vertical',
				title: Loc.getMessage('PHONE_CALL_B24_BANNER_TITLE'),
				showSubtitle: false,
				buttonText: Loc.getMessage('PHONE_CALL_B24_BANNER_BUTTON'),
			},
			params: {
				title: Loc.getMessage('PHONE_CALL_B24_DISABLED'),
			},
		});

		banner.show(parentWidget);
	}

	const SvgIcons = {
		telephony: '<svg width="23" height="16" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.9341 6.86401H10.7461V10.049H13.7895V8.861H11.9341V6.86401ZM11.4075 13.1691C9.17416 13.1691 7.36372 11.3586 7.36372 9.12534C7.36372 6.89204 9.17416 5.0816 11.4075 5.0816C13.6408 5.0816 15.4512 6.89204 15.4512 9.12534C15.4512 11.3586 13.6408 13.1691 11.4075 13.1691V13.1691ZM19.2878 8.02653C19.2966 7.91248 19.3023 7.79777 19.3023 7.6816C19.3023 5.22434 17.3105 3.2324 14.8532 3.2324C14.3189 3.2324 13.8067 3.32678 13.3321 3.49931C12.624 1.98073 11.0846 0.927734 9.29846 0.927734C6.98588 0.927734 5.08594 2.69237 4.87025 4.94832C2.491 5.48822 0.714355 7.61428 0.714355 10.1568C0.714355 13.1074 3.10614 15.4992 6.0567 15.4992H18.7092C20.804 15.4992 22.5019 14.021 22.5019 11.7064C22.5019 8.70878 19.5824 7.95314 19.2878 8.02653Z" fill="#6a737f"/></svg>',
		copy: '<svg width="24" height="26" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.70938 4.06836C7.59672 4.06836 6.69798 4.97645 6.70949 6.08904L6.71041 6.1788H16.7298C17.8344 6.1788 18.7298 7.07423 18.7298 8.1788V18.4835H19.5188C20.6315 18.4835 21.5302 17.5754 21.5187 16.4628L21.411 6.04768C21.3996 4.95123 20.5076 4.06836 19.4111 4.06836H8.70938ZM2.03503 10.4475C2.03503 9.34292 2.93046 8.44749 4.03503 8.44749H14.4645C15.5691 8.44749 16.4645 9.34292 16.4645 10.4475V20.8769C16.4645 21.9815 15.5691 22.8769 14.4645 22.8769H4.03503C2.93047 22.8769 2.03503 21.9815 2.03503 20.8769V10.4475ZM13.9671 10.9449H4.53244V20.3795H13.9671V10.9449Z" fill="#6a737f"/></svg>',
	};

	module.exports = { openPhoneMenu };
});

/**
 * @module ava-menu/aha-moment
 */
jn.define('ava-menu/aha-moment', (require, exports, module) => {
	const { AhaMoment } = require('ui-system/popups/aha-moment');
	const { Loc } = require('loc');
	const { withCurrentDomain } = require('utils/url');
	const { menu } = require('native/avamenu');

	function showAhaMoment()
	{
		AhaMoment.show({
			testId: 'aha-moment-ava-menu',
			targetRef: 'user_avatar',
			image: renderImage(),
			title: Loc.getMessage('MOBILE_AVA_MENU_AHA_MOMENT_TITLE'),
			description: Loc.getMessage('MOBILE_AVA_MENU_AHA_MOMENT_DESCRIPTION_MSGVER_1'),
			buttonText: Loc.getMessage('MOBILE_AVA_MENU_AHA_MOMENT_OK_BUTTON_MSGVER_1'),
			closeButton: false,
			onClick: onOkClick,
			spotlightParams: {
				pointerMargin: Application.getPlatform() === 'android' ? 2 : -6,
			},
		});
	}

	function renderImage()
	{
		const PATH = '/bitrix/mobileapp/mobile/extensions/bitrix/ava-menu/aha-moment/img/aha-moment.svg';

		return Image({
			style: {
				width: 78,
				height: 78,
			},
			svg: {
				uri: withCurrentDomain(PATH),
			},
		});
	}

	function onOkClick()
	{
		menu.show();

		BX.ajax.runAction(
			'mobile.AvaMenu.setAhaMomentStatus',
			{ data: { shouldBeShown: 'N' } },
		);
	}

	module.exports = { showAhaMoment };
});

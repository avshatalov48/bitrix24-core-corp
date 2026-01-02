/**
 * @module utils/store
 */
jn.define('utils/store', (require, exports, module) => {
	const { Feature } = require('feature');
	const { store } = require('native/store');
	const { showErrorToast } = require('toast');

	const APP_STORE_URL = 'https://apps.apple.com/ru/app/bitrix24/id561683423';
	const GOOGLE_PLAY_URL = 'https://play.google.com/store/apps/details?id=com.bitrix24.android';
	const APP_GALLERY_URL = 'https://appgallery.huawei.com/app/C105947779';
	const RU_STORE_URL = 'https://www.rustore.ru/catalog/app/com.bitrix24.android';

	const isAndroid = Application.getPlatform() === 'android';

	const STORE_URLS = {
		appstore: APP_STORE_URL,
		googleplay: GOOGLE_PLAY_URL,
		appgallery: APP_GALLERY_URL,
		rustore: RU_STORE_URL,
	};

	const openStore = () => {
		if (!Feature.isNativeStoreSupported())
		{
			showErrorToast();

			return;
		}

		const storeId = store.getStoreId();

		const storeUrl = STORE_URLS[storeId];
		if (storeUrl)
		{
			Application.openUrl(storeUrl);
		}
		else
		{
			Application.openUrl(isAndroid ? GOOGLE_PLAY_URL : APP_STORE_URL);
		}
	};

	module.exports = {
		openStore,
	};
});

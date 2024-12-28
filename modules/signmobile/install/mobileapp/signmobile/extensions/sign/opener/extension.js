/**
 * @module sign/opener
 */
jn.define('sign/opener', (require, exports, module) => {
	const { SignDialog } = require('sign/dialog');
	const { LocalStorage } = require('storage/local-storage');
	const LOCAL_STORAGE_KEY = 'banner';
	const MODULE_ID = 'signmobile';
	const SOME_BANNER_IS_OPEN_KEY = 'some_banner_is_open';
	const LAST_OPEN_BANNER_KEY = 'last_open_banner';
	const REQUEST_TO_SIGNING_BANNER_TYPE = 1;
	const CONFIRMATION_BANNER_TYPE = 2;

	/**
	 * @class SignOpener
	 */
	class SignOpener
	{
		static clearCacheSomeBannerIsAlreadyOpen()
		{
			const storage = LocalStorage.get(LOCAL_STORAGE_KEY, MODULE_ID);
			storage.set(SOME_BANNER_IS_OPEN_KEY, false);
		}

		static checkSomeBannerIsAlreadyOpen()
		{
			const storage = LocalStorage.get(LOCAL_STORAGE_KEY, MODULE_ID);

			return storage.get(SOME_BANNER_IS_OPEN_KEY, false, true);
		}

		static saveBannerToLocalStorageIfDifferent(props)
		{
			const storage = LocalStorage.get(LOCAL_STORAGE_KEY, MODULE_ID);

			const {
				type: currentBannerType,
				id: currentBannerId = 0,
			} = props;

			const {
				type: lastBannerType,
				id: lastBannerId = 0,
			} = storage.get(LAST_OPEN_BANNER_KEY, {});

			if (lastBannerType !== currentBannerType || Number(lastBannerId) !== Number(currentBannerId))
			{
				storage.set(LAST_OPEN_BANNER_KEY, Number(currentBannerId));
				storage.set(SOME_BANNER_IS_OPEN_KEY, true);

				return true;
			}

			return false;
		}

		/**
		 * @param props
		 */
		static openSigning(
			props,
		)
		{
			const {
				goWithoutConfirmation = true,
				title = '',
				memberId = 0,
				url = false,
				role,
				isGoskey = false,
				isExternal = false,
				initiatedByType,
			} = props;

			if (goWithoutConfirmation)
			{
				ComponentHelper.openLayout(
					{
						name: 'sign:sign.document',
						canOpenInDefault: true,
						widgetParams: {
							title: '',
							modal: true,
							backdrop: {
								hideNavigationBar: false,
								shouldResizeContent: true,
								swipeAllowed: false,
								showOnTop: true,
							},
						},
						componentParams: {
							title,
							memberId,
							role,
							url,
							isGoskey,
							isExternal,
							initiatedByType,
						},
					},
				);
			}
			else if (
				!SignOpener.checkSomeBannerIsAlreadyOpen()
				&& SignOpener.saveBannerToLocalStorageIfDifferent({
					id: memberId,
					type: REQUEST_TO_SIGNING_BANNER_TYPE,
				})
			)
			{
				SignDialog.openRequestDialog({
					url,
					title,
					memberId,
					role,
					isGoskey,
					isExternal,
					initiatedByType,
				});
			}
		}

		static openConfirmation(props)
		{
			const {
				title = '',
				memberId = 0,
				role,
				forcedBannerOpening = false,
				initiatedByType,
			} = props;

			if (
				!SignOpener.checkSomeBannerIsAlreadyOpen()
				/*
					The order of checks is important, since you first need to save the banner
					to local storage, and then skip showing it if the opening is forced.
					This is necessary so as not to show the banner again when opening the application.
				 */
				&& (SignOpener.saveBannerToLocalStorageIfDifferent({
					id: memberId,
					type: CONFIRMATION_BANNER_TYPE,
				}) || forcedBannerOpening)
			)
			{
				SignDialog.openSignConfirmationDialog({
					title,
					memberId,
					role,
					initiatedByType,
				});
			}
		}
	}

	module.exports = { SignOpener };
});

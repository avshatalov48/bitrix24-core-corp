/**
 * @module layout/ui/app-rating/src/strategies/store-redirect-strategy
 */
jn.define('layout/ui/app-rating/src/strategies/store-redirect-strategy', (require, exports, module) => {
	const { BaseAppRatingStrategy } = require('layout/ui/app-rating/src/strategies/strategy');
	const { MinRateForStore } = require('layout/ui/app-rating/src/rating-constants');
	const { store } = require('native/store') || {};

	class StoreRedirectStrategy extends BaseAppRatingStrategy
	{
		isApplicable(props)
		{
			return props.userRate >= MinRateForStore;
		}

		execute(props)
		{
			props.parentWidget.close(() => {
				store?.requestReview();
				props.onGoToStoreButtonClick?.();
			});
		}

		shouldRender()
		{
			return false;
		}
	}

	module.exports = {
		StoreRedirectStrategy,
	};
});

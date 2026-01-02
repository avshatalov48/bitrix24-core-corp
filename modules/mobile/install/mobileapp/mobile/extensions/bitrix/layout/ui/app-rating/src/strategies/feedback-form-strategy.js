/**
 * @module layout/ui/app-rating/src/strategies/feedback-form-strategy
 */
jn.define('layout/ui/app-rating/src/strategies/feedback-form-strategy', (require, exports, module) => {
	const { BackdropHeight } = require('layout/ui/app-rating/src/rating-constants');
	const { BaseAppRatingStrategy } = require('layout/ui/app-rating/src/strategies/strategy');
	const { FeedbackForm } = require('layout/ui/feedback-form-opener');

	class FeedbackFormStrategy extends BaseAppRatingStrategy
	{
		isApplicable(props)
		{
			return true;
		}

		execute(props)
		{
			props.parentWidget.close(() => {
				BX.postComponentEvent('app-feedback:onShouldOpenFeedback', [], 'background');
			});
		}

		shouldRender()
		{
			return false;
		}

		/**
		 * @returns {void}
		 */
		static openAppFeedbackForm()
		{
			(new FeedbackForm({
				sender_page: 'mobile_rating_drawer',
			})).openInBackdrop({
				mediumPositionHeight: BackdropHeight,
				hideNavigationBar: true,
				swipeAllowed: true,
				forceDismissOnSwipeDown: false,
				showOnTop: false,
				adoptHeightByKeyboard: true,
				shouldResizeContent: true,
			});
		}
	}

	module.exports = {
		FeedbackFormStrategy,
	};
});

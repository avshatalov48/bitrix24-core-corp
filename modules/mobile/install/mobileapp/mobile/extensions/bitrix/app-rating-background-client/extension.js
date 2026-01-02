/**
 * @module app-rating-background-client
 */
jn.define('app-rating-background-client', (require, exports, module) => {
	const { AppRatingClientBase } = require('app-rating-client');
	const { AppRatingManager } = require('app-rating-manager');
	const { AppRatingAnalytics, FeedbackFormStrategy } = require('layout/ui/app-rating');

	const AppRatingUserEvent = {
		LIVE_FEED_POST_CREATED: 'feed_post_created',
		LIVE_FEED_COMMENTS_LEFT: 'feed_post_commented',
		DEALS_VIEWED: 'crm_deal_viewed',
	};

	class AppRatingBackgroundClient extends AppRatingClientBase
	{
		/**
		 * @public
		 * @returns {object}
		 */
		getLimits()
		{
			return {
				[AppRatingUserEvent.LIVE_FEED_POST_CREATED]: 2,
				[AppRatingUserEvent.LIVE_FEED_COMMENTS_LEFT]: 3,
				[AppRatingUserEvent.DEALS_VIEWED]: 15,
			};
		}

		/**
		 * @public
		 * @returns {void}
		 * Should use for subscribing to user events which appears in isolated js-context.
		 */
		subscribeToUserEvents()
		{
			BX.addCustomEvent('livefeed.postform::onClose', async () => {
				await this.increaseCounter(AppRatingUserEvent.LIVE_FEED_POST_CREATED);
				this.tryOpenAppRating({
					openInComponent: true,
					event: AppRatingUserEvent.LIVE_FEED_POST_CREATED,
				});
			});

			BX.addCustomEvent('DetailCard::onClose_Global', async (params) => {
				const isDealDetailPageClosed = params?.entityTypeId === 2;
				if (isDealDetailPageClosed)
				{
					await this.increaseCounter(AppRatingUserEvent.DEALS_VIEWED);
					this.tryOpenAppRating({
						openInComponent: true,
						event: AppRatingUserEvent.DEALS_VIEWED,
					});
				}
			});

			BX.addCustomEvent('Comments.UploadQueue::setItem', async (data) => {
				const isLiveFeedPostCommentAdded = data.formId === 'bitrix:socialnetwork.blog.post.comment';
				if (isLiveFeedPostCommentAdded)
				{
					await this.increaseCounter(AppRatingUserEvent.LIVE_FEED_COMMENTS_LEFT);
					setTimeout(() => {
						this.tryOpenAppRating({
							openInComponent: true,
							event: AppRatingUserEvent.LIVE_FEED_COMMENTS_LEFT,
						});
					}, 1000);
				}
			});

			BX.addCustomEvent('app-feedback:onShouldOpenFeedback', () => {
				void FeedbackFormStrategy.openAppFeedbackForm();
			});

			BX.addCustomEvent('app-feedback:onFeedbackSend', async () => {
				AppRatingAnalytics.sendSubmitFeedback({
					section: AppRatingManager.getLastTriggerEvent(),
				});
			});

			BX.addCustomEvent('app-rating-manager:increaseCounterAndTryOpen', async (params) => {
				const { event } = params;
				await this.increaseCounter(event);
				this.tryOpenAppRating({
					openInComponent: true,
					event,
				});
			});
		}

		/**
		 * @public
		 * @returns {void}
		 */
		subscribeToAppPausedEvent()
		{
			AppRatingManager.subscribe();
		}
	}

	module.exports = {
		AppRatingBackgroundClient: new AppRatingBackgroundClient(),
	};
});

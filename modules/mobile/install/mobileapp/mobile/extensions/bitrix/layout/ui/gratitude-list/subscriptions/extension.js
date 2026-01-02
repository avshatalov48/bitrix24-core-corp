/**
 * @module layout/ui/gratitude-list/subscriptions
 */
jn.define('layout/ui/gratitude-list/subscriptions', (require, exports, module) => {
	const { Type } = require('type');

	function subscribeToPostEvents()
	{
		function handleGratitudeEvent(eventName, data)
		{
			const gratitudeType = data?.GRATITUDE_MEDAL;

			if (Type.isStringFilled(gratitudeType))
			{
				BX.postComponentEvent(eventName, [data]);
			}
		}

		BX.addCustomEvent('Livefeed.PublicationQueue::afterPostAdd', (item) => {
			handleGratitudeEvent('GratitudeList::onItemAdd', item);
		});

		BX.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdate', (item) => {
			handleGratitudeEvent('GratitudeList::onItemUpdate', item);
		});

		BX.addCustomEvent('onBlogPostDelete', (postId) => {
			BX.postComponentEvent('GratitudeList::onItemDeleted', [postId]);
		});
	}

	module.exports = {
		subscribeToPostEvents,
	};
});

/**
 * @module crm/timeline/item/ui/user-avatar
 */
jn.define('crm/timeline/item/ui/user-avatar', (require, exports, module) => {
	const { InAppLink } = require('in-app-url/components/link');

	const DEFAULT_AVATAR = '/bitrix/mobileapp/crmmobile/extensions/crm/timeline/item/ui/user-avatar/default-avatar.png';

	function TimelineItemUserAvatar({ title, imageUrl, detailUrl, testId })
	{
		imageUrl = imageUrl || DEFAULT_AVATAR;
		imageUrl = imageUrl.startsWith('/') ? currentDomain + imageUrl : imageUrl;
		testId = testId || 'TimelineItemUserAvatar';

		return InAppLink({
			testId,
			url: detailUrl,
			context: {
				backdrop: true,
			},
			containerStyle: {
				paddingVertical: 10,
				paddingHorizontal: 11,
				justifyContent: 'center',
				alignItems: 'center',
			},
			renderContent: () => Image(
				{
					style: {
						width: 20,
						height: 20,
						borderRadius: 20,
					},
					uri: encodeURI(imageUrl),
				},
			),
		});
	}

	module.exports = { TimelineItemUserAvatar };
});

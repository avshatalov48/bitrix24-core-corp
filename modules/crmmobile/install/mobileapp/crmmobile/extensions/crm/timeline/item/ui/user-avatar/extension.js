/**
 * @module crm/timeline/item/ui/user-avatar
 */
jn.define('crm/timeline/item/ui/user-avatar', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { useCallback } = require('utils/function');
	const { inAppUrl } = require('in-app-url');
	const { Avatar } = require('ui-system/blocks/avatar');

	function TimelineItemUserAvatar({ id, userId, title, imageUrl, detailUrl, testId })
	{
		const userTestId = testId || 'TimelineItemUserAvatar';

		return Avatar({
			id: id || userId,
			size: 20,
			name: title,
			uri: imageUrl,
			testId: userTestId,
			style: {
				margin: Indent.L.toNumber(),
			},
			withRedux: true,
			onClick: useCallback(() => {
				inAppUrl.open(
					detailUrl,
					{
						backdrop: true,
					},
				);
			}, [id]),
		});
	}

	module.exports = { TimelineItemUserAvatar };
});

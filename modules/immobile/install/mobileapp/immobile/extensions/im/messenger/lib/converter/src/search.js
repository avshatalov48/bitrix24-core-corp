/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/converter/search
 */
jn.define('im/messenger/lib/converter/search', (require, exports, module) => {
	const { get } = require('utils/object');

	/**
	 * @class SearchConverter
	 */
	class SearchConverter
	{
		toUserCarouselItem(user)
		{
			const item = {
				params: {},
			};
			const preparedUser = this.prepareParams(user);

			item.type = 'info';
			item.id = 'user/' + preparedUser.id;
			item.params.id = preparedUser.id;
			item.params.externalAuthId = preparedUser.externalAuthId;

			item.title = preparedUser.firstName;

			item.imageUrl = ChatUtils.getAvatar(preparedUser.avatar);

			if (!item.imageUrl && !preparedUser.lastActivityDate)
			{
				item.imageUrl = component.path + 'images' + '/avatar_wait_x3.png';
			}

			item.color = preparedUser.color;
			item.shortTitle = preparedUser.firstName ? preparedUser.firstName : preparedUser.name;
			item.subtitle = preparedUser.workPosition ? preparedUser.workPosition : '';

			if (preparedUser.extranet)
			{
				item.styles = {
					title: {
						font:{
							color: '#ca8600'
						}
					}
				}
			}
			return item;
		}

		prepareParams(user)
		{
			const result = {};

			result.id = user.id;
			result.externalAuthId = user.external_auth_id || user.externalAuthId;
			result.firstName = user.first_name || user.firstName;
			result.avatar = user.avatar;
			result.lastActivityDate = user.last_activity_date || user.lastActivityDate;
			result.color = user.color;
			result.name = user.name;
			result.workPosition = user.work_position || user.workPosition;
			result.extranet = user.extranet;

			return result;
		}
	}

	module.exports = {
		SearchConverter: new SearchConverter(),
	};
});

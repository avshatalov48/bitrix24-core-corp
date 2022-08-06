/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/converter/search
 */
jn.define('im/messenger/lib/converter/search', (require, exports, module) => {

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

			item.type = 'info';
			item.id = 'user/' + user.id;
			item.params.id = user.id;
			item.params.externalAuthId = user.external_auth_id;

			item.title = user.first_name;

			item.imageUrl = ChatUtils.getAvatar(user.avatar);
			if (!item.imageUrl && !user.last_activity_date)
			{
				item.imageUrl = component.path + 'images' + '/avatar_wait_x3.png';
			}

			item.color = user.color;
			item.shortTitle = user.first_name ? user.first_name : user.name;
			item.subtitle = user.work_position ? user.work_position : '';

			if (user.extranet)
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
	}

	module.exports = {
		SearchConverter: new SearchConverter(),
	};
});

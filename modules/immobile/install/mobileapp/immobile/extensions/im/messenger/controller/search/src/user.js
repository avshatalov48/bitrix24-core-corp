/**
 * @module im/messenger/controller/search/user
 */
jn.define('im/messenger/controller/search/user', (require, exports, module) => {
	const { BaseSearchController } = require('im/messenger/controller/search/base');
	const { UserAdapter } = require('im/messenger/controller/search/adapter/user');
	class UserSearchController extends BaseSearchController
	{
		getAdapter()
		{
			return new UserAdapter(this.collectionView);
		}

		getSearchEntities()
		{
			return [
				'user',
				'im-bot',
			];
		}
	}

	module.exports = { UserSearchController };
});

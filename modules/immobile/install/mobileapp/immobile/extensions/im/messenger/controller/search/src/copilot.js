/**
 * @module im/messenger/controller/search/copilot
 */
jn.define('im/messenger/controller/search/copilot', (require, exports, module) => {
	const { BaseSearchController } = require('im/messenger/controller/search/base');
	const { UserAdapter } = require('im/messenger/controller/search/adapter/user');
	class CopilotSearchController extends BaseSearchController
	{
		getAdapter()
		{
			// TODO  change to copilot adapter
			return new UserAdapter(this.collectionView);
		}

		getSearchEntities()
		{
			return [
				'user',
			];
		}
	}

	module.exports = { CopilotSearchController };
});
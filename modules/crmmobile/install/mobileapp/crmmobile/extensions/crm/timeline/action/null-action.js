/**
 * @module crm/timeline/action/null-action
 */
jn.define('crm/timeline/action/null-action', (require, exports, module) => {
	const { BaseTimelineAction } = require('crm/timeline/action/base');

	class NullAction extends BaseTimelineAction
	{
		execute()
		{
			console.warn('Action not supported in mobile app');
		}
	}

	module.exports = { NullAction };
});

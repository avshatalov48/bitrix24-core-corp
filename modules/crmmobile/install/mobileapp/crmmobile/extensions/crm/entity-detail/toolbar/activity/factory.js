/**
 * @module crm/entity-detail/toolbar/activity/factory
 */
jn.define('crm/entity-detail/toolbar/activity/factory', (require, exports, module) => {

	const {
		ActivityPinnedBase,
		ActivityPinnedIm,
		ActivityPinnedCall,
	} = require('crm/entity-detail/toolbar/activity/templates');

	const Templates = {
		base: ActivityPinnedBase,
		'Activity:OpenLine': ActivityPinnedIm,
		'Activity:Call': ActivityPinnedCall,
	};

	/**
	 * @class ActivityFactory
	 */
	class ActivityFactory
	{
		static make(type, props)
		{
			return Object.keys(Templates).includes(type)
				? new Templates[type](props)
				: new Templates.base(props);
		}
	}

	module.exports = { ActivityFactory };
});
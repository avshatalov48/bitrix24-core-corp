/**
 * @module crm/timeline/item/factory
 */
jn.define('crm/timeline/item/factory', (require, exports, module) => {

	const {
		CallActivity,
		OpenLineActivity,
		CreationActivity,
		TodoActivity,
		Document,
	} = require('crm/timeline/item/activity');

	const {
		Creation,
		Modification,
		Link,
		Unlink,
		TodoCreated,
		CallIncoming,
		Ping,
		DocumentViewed,
	} = require('crm/timeline/item/log');

	const { TimelineItemCompatible } = require('crm/timeline/item/compatible');

	/**
	 * You MUST register record type here.
	 */
	const SupportedTypes = {
		Creation,
		Modification,
		Link,
		Unlink,
		TodoCreated,
		CallIncoming,
		Ping,
		DocumentViewed,
		Document,
		'Activity:Call': CallActivity,
		'Activity:OpenLine': OpenLineActivity,
		'Activity:Creation': CreationActivity,
		'Activity:ToDo': TodoActivity,
	};

    /**
     * @class TimelineItemFactory
     */
    class TimelineItemFactory
    {
		/**
		 * @param {string} type
		 * @param {object} props
		 * @returns {TimelineItemBase}
		 */
		static make(type, props)
		{
			if (SupportedTypes[type])
			{
				return new SupportedTypes[type](props);
			}

			return new TimelineItemCompatible(props);
		}
    }

    module.exports = { TimelineItemFactory, SupportedTypes };

});
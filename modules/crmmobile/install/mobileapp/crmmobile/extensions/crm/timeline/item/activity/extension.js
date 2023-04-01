/**
 * @module crm/timeline/item/activity
 */
jn.define('crm/timeline/item/activity', (require, exports, module) => {

    const { CallActivity } = require('crm/timeline/item/activity/call');
	const { OpenLineActivity } = require('crm/timeline/item/activity/open-line');
	const { CreationActivity } = require('crm/timeline/item/activity/creation');
	const { TodoActivity } = require('crm/timeline/item/activity/todo');
	const { Document } = require('crm/timeline/item/activity/document');
	const { ConfigurableRestAppActivity } = require('crm/timeline/item/activity/configurable-rest-app');

    module.exports = {
		CallActivity,
		OpenLineActivity,
		CreationActivity,
		TodoActivity,
		Document,
		ConfigurableRestAppActivity,
	};

});
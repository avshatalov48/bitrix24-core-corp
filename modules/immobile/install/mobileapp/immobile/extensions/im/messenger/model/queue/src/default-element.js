/**
 * @module im/messenger/model/queue/default-element
 */

jn.define('im/messenger/model/queue/default-element', (require, exports, module) => {
	const queueDefaultElement = Object.freeze({
		id: '',
		requestName: '',
		requestData: {},
		priority: 0,
		messageIdList: [],
	});

	module.exports = {
		queueDefaultElement,
	};
});

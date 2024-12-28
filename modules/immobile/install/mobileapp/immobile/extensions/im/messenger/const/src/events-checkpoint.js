/**
 * @module im/messenger/const/events-checkpoint
 */
jn.define('im/messenger/const/events-checkpoint', (require, exports, module) => {
	const EventsCheckpointType = Object.freeze({
		selectMessagesMode: 'selectMessagesMode',
	});

	module.exports = { EventsCheckpointType };
});

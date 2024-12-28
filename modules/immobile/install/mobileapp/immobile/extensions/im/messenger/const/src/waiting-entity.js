/**
 * @module im/messenger/const/waiting-entity
 */
jn.define('im/messenger/const/waiting-entity', (require, exports, module) => {

	const WaitingEntity = {
		sync: {
			filler: {
				database: 'sync-filler-database',
				chat: 'sync-filler-chat',
				copilot: 'sync-filler-copilot',
				collab: 'sync-filler-collab',
				channel: 'sync-filler-channel',
			},
		},
	};

	module.exports = { WaitingEntity };
});

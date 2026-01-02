/**
 * @module im/messenger/const/waiting-entity
 */
jn.define('im/messenger/const/waiting-entity', (require, exports, module) => {

	const WaitingEntity = {
		chat: 'chat',
		sync: {
			filler: {
				database: 'immobile::sync-filler-database',
				chat: 'immobile::sync-filler-chat',
				copilot: 'immobile::sync-filler-copilot',
				collab: 'immobile::sync-filler-collab',
				channel: 'immobile::sync-filler-channel',
				counter: 'immobile::sync-filler-counter',
			},
		},
		push: {
			messageHandler: {
				database: 'immobile::database-push-handler',
				chat: 'immobile::chat-push-handler',
				copilot: 'immobile::copilot-push-handler',
				counter: 'immobile::counter-push-handler',
			},
		},
		core: {
			navigation: 'immobile::navigation-core',
			chat: 'immobile::chat-core',
		},
		externalApi: 'immobile::external-api',
	};

	module.exports = { WaitingEntity };
});

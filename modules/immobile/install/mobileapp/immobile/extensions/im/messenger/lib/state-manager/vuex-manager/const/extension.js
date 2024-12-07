/**
 * @module im/messenger/lib/state-manager/vuex-manager/const
 */
jn.define('im/messenger/lib/state-manager/vuex-manager/const', (require, exports, module) => {

	const MessengerMutationManagerEvent = Object.freeze({
		handleComplete: 'ImMobile.MessengerMutationManager.handleComplete',
	});

	module.exports = {
		MessengerMutationManagerEvent,
	};
});

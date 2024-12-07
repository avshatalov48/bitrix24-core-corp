/**
 * @module im/messenger/lib/state-manager/vuex-manager/mutation-handlers-waiter
 */
jn.define('im/messenger/lib/state-manager/vuex-manager/mutation-handlers-waiter', (require, exports, module) => {
	const { Type } = require('type');
	const { Uuid } = require('utils/uuid');

	const { MessengerMutationManagerEvent } = require('im/messenger/lib/state-manager/vuex-manager/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('core--messenger-mutation-handlers-waiter');

	/**
	 * @class MessengerMutationHandlersWaiter
	 */
	class MessengerMutationHandlersWaiter
	{
		#moduleName;
		#actionName;
		#actionUid;
		#mutationList;

		constructor(moduleName, actionName)
		{
			this.#moduleName = moduleName;
			this.#actionName = actionName;
			this.#actionUid = `${this.moduleName}/${this.actionName}|${Uuid.getV4()}`;
			this.#mutationList = [];
		}

		get moduleName()
		{
			return this.#moduleName;
		}

		get actionName()
		{
			return this.#actionName;
		}

		get actionUid()
		{
			return this.#actionUid;
		}

		get mutationList()
		{
			return this.#mutationList;
		}

		get isActionComplete()
		{
			return Type.isArray(this.mutationList) && this.mutationList.length === 0;
		}

		addMutation(mutationName)
		{
			const mutationFullName = `${this.moduleName}/${mutationName}`;
			this.#mutationList.push(mutationFullName);
		}

		deleteMutation(mutationName)
		{
			this.#mutationList = this.mutationList.filter((name) => name !== mutationName);
		}

		async waitComplete()
		{
			if (this.isActionComplete)
			{
				logger.log('MessengerMutationHandlersWaiter.waitComplete: mutation list is empty');

				return Promise.resolve();
			}

			logger.warn('MessengerMutationHandlersWaiter: start waiting', this.actionUid, this.mutationList);

			let resolveMutationCompletePromise;
			const mutationCompletePromise = new Promise((resolve, reject) => {
				resolveMutationCompletePromise = resolve;
			});

			const handlersCompleteHandler = (mutation, state) => {
				const actionUuid = mutation?.payload?.actionUid;
				if (this.actionUid !== actionUuid)
				{
					return;
				}

				const mutationName = mutation.type;
				if (!Type.isStringFilled(mutationName))
				{
					return;
				}

				this.deleteMutation(mutationName);

				logger.log('MessengerMutationHandlersWaiter: mutation complete', this.actionUid, mutationName);
				if (this.isActionComplete)
				{
					BX.removeCustomEvent(MessengerMutationManagerEvent.handleComplete, handlersCompleteHandler);

					logger.warn('MessengerMutationHandlersWaiter: stop waiting, action complete', this.actionUid);
					resolveMutationCompletePromise();

					return;
				}

				logger.log('MessengerMutationHandlersWaiter: keep waiting', this.actionUid, this.mutationList);
			};

			BX.addCustomEvent(MessengerMutationManagerEvent.handleComplete, handlersCompleteHandler);

			return mutationCompletePromise;
		}
	}

	module.exports = {
		MessengerMutationHandlersWaiter,
	};
});

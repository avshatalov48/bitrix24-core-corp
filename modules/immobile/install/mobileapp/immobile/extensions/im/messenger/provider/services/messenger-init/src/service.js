/**
 * @module im/messenger/provider/services/messenger-init/service
 */
jn.define('im/messenger/provider/services/messenger-init/service', (require, exports, module) => {
	const { MemoryStorage } = require('native/memorystore');
	const { EntityReady } = require('entity-ready');
	const {
		EventType,
		MessengerInitRestMethod,
		ComponentCode,
	} = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { runAction } = require('im/messenger/lib/rest');
	const { MessengerParams } = require('im/messenger/lib/params');

	/**
	 * @class MessengerInitService
	 */
	class MessengerInitService
	{
		actionCommonMethodList = [
			MessengerInitRestMethod.mobileRevision,
			MessengerInitRestMethod.portalCounters,
			MessengerInitRestMethod.serverTime,
			MessengerInitRestMethod.userData,
		];

		commonActionResultStoreId = 'chatCommonActionResult';
		commonActionResultEntityId = 'chat-common-action-result';

		/**
		 * @constructor
		 * @param {string} options.actionName
		 */
		constructor(options)
		{
			this.actionName = options.actionName;
			this.eventEmitter = new JNEventEmitter();
			this.commonActionResultStore = new MemoryStorage(this.commonActionResultStoreId);
			EntityReady.addCondition(this.commonActionResultEntityId, () => this.isReadyCommonActionResult);
		}

		/**
		 * @param {string[]} methodList
		 * @description Sends a request to initialize messenger component.
		 * If some common data for different contexts has not been received before, it also requests it.
		 * Other contexts may be waiting for the request to be ready from the massager context
		 * if the common data is not saved in the storage.
		 */
		async runAction(methodList)
		{
			if (!Feature.isMessengerV2Enabled)
			{
				const isNeedWait = !this.#isMessengerComponent() && !this.#hasCommonActionResultStore();
				if (isNeedWait)
				{
					await this.#waitChatCommonActionResult();
				}
			}

			const data = this.#prepareActionData(methodList);
			let result = await runAction(this.actionName, { data });

			if (this.#hasCommonActionResultStore())
			{
				const commonActionResultFromStore = await this.#getCommonActionResultStore();
				result = { ...commonActionResultFromStore, ...result };
			}
			else
			{
				await this.#setCommonActionResultStore(result);
			}

			this.eventEmitter.emit(EventType.messenger.init, [result]);
		}

		/**
		 * @param {Function} eventHandler
		 */
		onceOnInit(eventHandler)
		{
			this.#once(EventType.messenger.init, eventHandler);
		}

		/**
		 * @param {Function} eventHandler
		 */
		onInit(eventHandler)
		{
			this.#on(EventType.messenger.init, eventHandler);
		}

		async clearCommonActionResultStore()
		{
			await this.#clearCommonActionResultStore();
		}

		/**
		 * @param {Function} eventHandler
		 * @param {string} eventName
		 */
		#on(eventName, eventHandler)
		{
			this.eventEmitter.on(eventName, eventHandler);
		}

		/**
		 * @param {Function} eventHandler
		 * @param {string} eventName
		 */
		#once(eventName, eventHandler)
		{
			this.eventEmitter.once(eventName, eventHandler);
		}

		#isMessengerComponent()
		{
			const componentCode = MessengerParams.getComponentCode();

			return componentCode === ComponentCode.imMessenger;
		}

		/**
		 * @param {string[]} methodList
		 * @return {MessengerInitActionData}
		 */
		#prepareActionData(methodList)
		{
			const hasCommonActionResultStore = this.#hasCommonActionResultStore();
			const data = { methodList: [] };

			if (hasCommonActionResultStore)
			{
				data.methodList = this.#filterOutCommonMethods(methodList);
			}
			else
			{
				data.methodList = this.#mergeUniqueMethods(methodList);
			}

			return this.#prepareOptions(data);
		}

		/**
		 * @param {object} data
		 * @param {Array<string>} data.methodList
		 * @return {MessengerInitActionData}
		 */
		#prepareOptions(data)
		{
			const preparedData = { ...data };
			if (preparedData.methodList.includes('portalCounters'))
			{
				preparedData.options = { siteId: env.siteId };
			}

			return preparedData;
		}

		async #waitChatCommonActionResult()
		{
			return EntityReady.wait(this.commonActionResultEntityId);
		}

		/**
		 * @param {immobileTabChatLoadResult | immobileTabChannelLoadResult | immobileTabCopilotLoadResult} actionResult
		 */
		async #setCommonActionResultStore(actionResult)
		{
			/** @type {immobileTabsLoadCommonResult} */
			const commonActionResult = this.actionCommonMethodList.reduce((result, key) => {
				if (key in actionResult)
				{
					// eslint-disable-next-line no-param-reassign
					result[key] = actionResult[key];
				}

				return result;
			}, {});
			await this.commonActionResultStore.set('commonActionResult', commonActionResult);

			this.isReadyCommonActionResult = true;
			EntityReady.ready(this.commonActionResultEntityId);
		}

		async #clearCommonActionResultStore()
		{
			await this.commonActionResultStore.set('commonActionResult', null);
			this.isReadyCommonActionResult = false;
			EntityReady.unready(this.commonActionResultEntityId);
		}

		/**
		 * @return {immobileTabsLoadCommonResult}
		 */
		#getCommonActionResultStore()
		{
			return this.commonActionResultStore.getSync('commonActionResult');
		}

		#hasCommonActionResultStore()
		{
			const data = this.#getCommonActionResultStore();

			return Boolean(data);
		}

		/**
		 * @param {string[]} methodList
		 */
		#mergeUniqueMethods(methodList)
		{
			const combinedSet = new Set([...methodList, ...this.actionCommonMethodList]);

			return [...combinedSet];
		}

		/**
		 * @param {string[]} methodList
		 */
		#filterOutCommonMethods(methodList)
		{
			return methodList.filter((method) => !this.actionCommonMethodList.includes(method));
		}
	}

	module.exports = {
		MessengerInitService,
	};
});

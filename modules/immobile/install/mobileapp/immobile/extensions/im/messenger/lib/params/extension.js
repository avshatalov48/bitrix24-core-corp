/**
 * @module im/messenger/lib/params
 */

jn.define('im/messenger/lib/params', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('im/messenger/loc');
	const { MemoryStorage } = require('native/memorystore');
	const { EntityReady } = require('entity-ready');
	const { ComponentCode } = require('im/messenger/const');

	const sharedParamsStorage = new MemoryStorage('immobileMessengerSharedParams');
	const entityReadySharedParamsId = 'immobile:sharedParams::ready';

	/**
	 * @class MessengerParams
	 */
	class MessengerParams
	{
		#entityReadySharedParamsKey = 'immobile:sharedParams::ready';
		#isReadySharedParams = false;
		#wasSharedParamsSaved = false;

		constructor()
		{
			this.sharedParamsStorage = sharedParamsStorage;

			EntityReady.addCondition(this.#entityReadySharedParamsKey, () => this.#isReadySharedParams);

			this.setAiAssistantStatusMessages();
		}

		setAiAssistantStatusMessages()
		{
			const configMessages = this.get('MESSAGES', {});
			if ('AI_ASSISTANT' in configMessages)
			{
				Object.entries(configMessages.AI_ASSISTANT).forEach(([code, phase]) => {
					Loc.setMessage(code, phase);
				});
			}
		}

		async initSharedParams()
		{
			const sharedParams = this.get('SHARED_PARAMS');

			if (!sharedParams)
			{
				return;
			}

			await this.sharedParamsStorage.set('sharedParams', sharedParams);
			this.setSharedParamsFromStorage();

			this.#isReadySharedParams = true;
			EntityReady.ready(this.#entityReadySharedParamsKey);
		}

		async waitSharedParamsInit()
		{
			if (this.#wasSharedParamsSaved)
			{
				return;
			}

			await EntityReady.wait(entityReadySharedParamsId);
			this.#isReadySharedParams = true;

			this.setSharedParamsFromStorage();
		}

		/**
		 * @param {string} key
		 * @param defaultValue
		 * @returns {*}
		 */
		get(key, defaultValue)
		{
			return BX.componentParameters.get(key, defaultValue);
		}

		/**
		 * @param {string} key
		 * @param {*} value
		 */
		set(key, value)
		{
			BX.componentParameters.set(key, value);
		}

		setSharedParamsFromStorage()
		{
			const sharedParamsFromStorage = this.sharedParamsStorage.getSync('sharedParams') ?? {};
			const sharedParamsFromStorageKeys = Object.keys(sharedParamsFromStorage);
			sharedParamsFromStorageKeys.forEach((key) => this.set(key, sharedParamsFromStorage[key]));
			this.#wasSharedParamsSaved = true;
		}

		getSiteDir()
		{
			return this.get('SITE_DIR', '/');
		}

		getUserId()
		{
			return Number(this.get('USER_ID', 0));
		}

		getGeneralChatId()
		{
			return Number(this.get('IM_GENERAL_CHAT_ID', 0));
		}

		/**
		 *
		 * @return {string || ''}
		 */
		getComponentCode()
		{
			return this.get('COMPONENT_CODE', '');
		}

		setGeneralChatId(id)
		{
			this.set('IM_GENERAL_CHAT_ID', id);
		}

		isOpenlinesOperator()
		{
			return this.get('OPENLINES_USER_IS_OPERATOR', false);
		}

		isBetaAvailable()
		{
			return this.get('IS_BETA_AVAILABLE', false);
		}

		isChatLocalStorageAvailable()
		{
			return this.get('IS_CHAT_LOCAL_STORAGE_AVAILABLE', false);
		}

		shouldShowChatV2UpdateHint()
		{
			return this.get('SHOULD_SHOW_CHAT_V2_UPDATE_HINT', false);
		}

		isCloud()
		{
			return this.get('IS_CLOUD', false);
		}

		hasActiveCloudStorageBucket()
		{
			return this.get('HAS_ACTIVE_CLOUD_STORAGE_BUCKET', false);
		}

		/**
		 * @return boolean
		 */
		canUseTelephony()
		{
			return this.get('CAN_USE_TELEPHONY', false);
		}

		/**
		 * @return {PlanLimits}
		 */
		getPlanLimits()
		{
			return this.get('PLAN_LIMITS', {});
		}

		/**
		 * @param {PlanLimits} limits
		 * @return void
		 */
		setPlanLimits(limits)
		{
			this.set('PLAN_LIMITS', limits);
		}

		/**
		 * @return {boolean}
		 */
		isFullChatHistoryAvailable()
		{
			const limits = this.getPlanLimits();
			const componentCode = this.getComponentCode();
			if (componentCode !== ComponentCode.imChannelMessenger && limits?.fullChatHistory)
			{
				return limits?.fullChatHistory?.isAvailable;
			}

			return true;
		}

		/**
		 * @return ImFeatures
		 */
		getImFeatures()
		{
			return this.get('IM_FEATURES', {
				chatDepartments: false,
				chatV2: false,
				collabAvailable: false,
				collabCreationAvailable: false,
				copilotActive: false,
				copilotAvailable: false,
				giphyAvailable: false,
				sidebarBriefs: false,
				sidebarFiles: false,
				sidebarLinks: false,
				zoomActive: false,
				zoomAvailable: false,
				intranetInviteAvailable: false,
				messagesAutoDeleteEnabled: false,
				voteCreationAvailable: false,
				aiFileTranscriptionAvailable: false,
			});
		}

		/**
		 * @param {Partial<ImFeatures>} features
		 */
		updateExistingImFeatures(features)
		{
			const actualFeatures = this.getImFeatures();

			Object.entries(features).forEach(([key, value]) => {
				if (!Type.isNil(actualFeatures[key]))
				{
					actualFeatures[key] = value;
				}
			});

			this.set('IM_FEATURES', actualFeatures);
		}

		/**
		 * @return UserInfo
		 */
		getUserInfo()
		{
			return this.get('USER_INFO', {
				id: 0,
				type: 'user',
			});
		}

		/**
		 * @return Permissions
		 */
		getPermissions()
		{
			return this.get('PERMISSIONS', {});
		}

		/**
		 * @return {number}
		 */
		getMultipleActionMessageLimit()
		{
			return this.get('MULTIPLE_ACTION_MESSAGE_LIMIT', 20);
		}

		getMessengerV2Enabled()
		{
			return this.get('IS_MESSENGER_V2_ENABLED', false);
		}

		/**
		 * @param {ComponentCode} componentCode
		 * @return {boolean}
		 */
		isComponentAvailable(componentCode)
		{
			const availableComponents = this.get('AVAILABLE_MESSENGER_COMPONENTS', {});

			return Boolean(availableComponents[componentCode]);
		}

		/**
		 * @param {ComponentCode} componentCode
		 * @return {boolean}
		 */
		isComponentPreloaded(componentCode)
		{
			const preloadedComponents = this.get('PRELOADED_MESSENGER_COMPONENTS', {});

			return Boolean(preloadedComponents[componentCode]);
		}

		/**
		 * @param {ComponentCode} componentCode
		 * @return {boolean}
		 */
		isComponentRequestBroadcasterAvailable(componentCode)
		{
			return this.isComponentAvailable(componentCode) && this.isComponentPreloaded(componentCode);
		}

		canUseAudioPanel()
		{
			return Boolean(this.get('CAN_USE_AUDIO_PANEL', false));
		}

		/**
		 * @returns {string}
		 */
		getServiceHealthUrl()
		{
			return this.get('SERVICE_HEALTH_URL', '');
		}
	}

	module.exports = {
		MessengerParams: new MessengerParams(),
	};
});

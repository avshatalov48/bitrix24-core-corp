'use strict';

(function()
{
	const { EntityReady } = jn.require('entity-ready');

	const blankAvatar = '/bitrix/js/im/images/blank.gif';
	const ajaxActions = {
		createCall: 'im.call.create',
		createChildCall: 'im.call.createChildCall',
		getPublicChannels: 'pull.channel.public.list',
		getCall: 'im.call.get',
		startTrack: 'call.Track.start',
		stopTrack: 'call.Track.stop',
	};

	const pingTTLWebsocket = 10;
	const pingTTLPush = 45;

	BX.Call = {};

	BX.Call.State = {
		Incoming: 'Incoming',
	};

	BX.Call.UserState = {
		Idle: 'Idle',
		Busy: 'Busy',
		Calling: 'Calling',
		Unavailable: 'Unavailable',
		Declined: 'Declined',
		Ready: 'Ready',
		Connecting: 'Connecting',
		Connected: 'Connected',
		Failed: 'Failed',
	};

	BX.Call.JoinStatus = {
		None: 'none',
		Local: 'local',
		Remote: 'remote',
	};

	BX.Call.Type = {
		Instant: 1,
		Permanent: 2,
	};

	BX.Call.Provider = {
		Plain: 'Plain',
		Voximplant: 'Voximplant',
		Bitrix: 'Bitrix',
		BitrixDev: 'BitrixDev',
	};

	BX.Call.StreamTag = {
		Main: 'main',
		Screen: 'screen',
	};

	BX.Call.Direction = {
		Incoming: 'Incoming',
		Outgoing: 'Outgoing',
	};

	BX.Call.Quality = {
		VeryHigh: 'very_high',
		High: 'high',
		Medium: 'medium',
		Low: 'low',
		VeryLow: 'very_low',
	};

	BX.Call.UserMnemonic = {
		all: 'all',
		none: 'none',
	};

	BX.Call.Event = {
		onUserInvited: 'onUserInvited',
		onUserStateChanged: 'onUserStateChanged',
		onUserMicrophoneState: 'onUserMicrophoneState',
		onUserCameraState: 'onUserCameraState',
		onUserScreenState: 'onUserScreenState',
		onUserVoiceStarted: 'onUserVoiceStarted',
		onUserVoiceStopped: 'onUserVoiceStopped',
		onUserFloorRequest: 'onUserFloorRequest', // request for a permission to speak
		onUserEmotion: 'onUserEmotion',
		onLocalMediaReceived: 'onLocalMediaReceived',
		onLocalMediaStopped: 'onLocalMediaStopped',
		onDeviceListUpdated: 'onDeviceListUpdated',
		onRTCStatsReceived: 'onRTCStatsReceived',
		onCallFailure: 'onCallFailure',
		onStreamReceived: 'onStreamReceived',
		onStreamRemoved: 'onStreamRemoved',
		onJoin: 'onJoin',
		onLeave: 'onLeave',
		onActive: 'onActive',
		onInactive: 'onInactive',
		onDestroy: 'onDestroy',
		onHangup: 'onHangup',
		onPullEventUserInviteTimeout: 'onPullEventUserInviteTimeout',
		onReconnected: 'onReconnected',
		onSwitchTrackRecordStatus: 'onSwitchTrackRecordStatus',
	};

	class CallEngine
	{
		constructor()
		{
			this.calls = {};
			this.unknownCalls = {};
			this.callsToProcessAfterMessengerReady = new Set();

			this.debugFlag = false;
			this.isMessengerReady = false;

			this.pullStatus = '';

			this._onPullEventHandler = this._onPullEvent.bind(this);
			this._onPullClientEventHandler = this._onPullClientEvent.bind(this);
			BX.addCustomEvent('onPullEvent-im', this._onPullEventHandler);
			BX.addCustomEvent('onPullClientEvent-im', this._onPullClientEventHandler);
			BX.addCustomEvent('onAppActive', this.onAppActive.bind(this));

			BX.addCustomEvent('onPullStatus', (e) => {
				this.pullStatus = e.status;
				console.log(`[${CallUtil.getTimeForLog()}]: pull status: ${this.pullStatus}`);
			});

			this._onCallJoinHandler = this._onCallJoin.bind(this);
			this._onCallLeaveHandler = this._onCallLeave.bind(this);
			this._onCallDestroyHandler = this._onCallDestroy.bind(this);
			this._onCallInactiveHandler = this._onCallInactive.bind(this);
			this._onCallActiveHandler = this._onCallActive.bind(this);

			this._onNativeIncomingCallHandler = this._onNativeIncomingCall.bind(this);
			if ('callservice' in window)
			{
				callservice.on('incoming', this._onNativeIncomingCallHandler);
				if (callservice.currentCall())
				{
					setTimeout(() => this._onNativeIncomingCall(callservice.currentCall()), 0);
				}
			}

			this.timeOfLastPushNotificationWithAutoAnswer;

			this.startWithPush();

			setTimeout(
				() => BX.postComponentEvent('onPullGetStatus', [], 'communication'),
				100,
			);

			EntityReady.wait('chat')
				.then(() => this._onMessengerReady())
				.catch((error) => console.error(error))
			;
		}

		onAppActive()
		{
			for (const callId in this.calls)
			{
				if (this.calls.hasOwnProperty(callId)
					&& (this.calls[callId] instanceof PlainCall)
					&& !this.calls[callId].ready
					&& !this.isNativeCall(callId)
					&& ((Date.now()) - this.calls[callId].created) > 30000
				)
				{
					console.warn(`Destroying stale call ${callId}`);
					this.calls[callId].destroy();
				}
			}
			this.startWithPush();
		}

		_onMessengerReady()
		{
			this.isMessengerReady = true;
			for (const callId of this.callsToProcessAfterMessengerReady.values())
			{
				this._onCallActive(callId);
			}
			this.callsToProcessAfterMessengerReady.clear();
		}

		startWithPush()
		{
			const push = Application.getLastNotification();

			if (!push.id || !push.id.startsWith('IM_CALL_'))
			{
				return;
			}

			let pushParams;
			try
			{
				pushParams = JSON.parse(push.params);
			}
			catch
			{
				navigator.notification.alert(BX.message('MOBILE_CALL_INTERNAL_ERROR').replace('#ERROR_CODE#', 'E005'));
			}

			if (!pushParams.ACTION || !pushParams.ACTION.startsWith('IMINV_') || !pushParams.PARAMS || !pushParams.PARAMS.call)
			{
				return;
			}

			console.log('Starting with PUSH:', push);
			const callFields = pushParams.PARAMS.call;
			const isVideo = pushParams.PARAMS.video;
			const callId = callFields.ID;
			const timestamp = pushParams.PARAMS.ts;
			const timeAgo = Date.now() / 1000 - timestamp;
			const provider = callFields.PROVIDER;

			console.log('timeAgo:', timeAgo);
			this._onUnknownCallPing(callId, timeAgo, pingTTLPush).then((result) => {
				if (result && this.calls[callId])
				{
					BX.postComponentEvent('CallEvents::incomingCall', [{
						callId,
						video: isVideo,
						autoAnswer: true,
						provider,
					}], 'calls');
				}
			}).catch((err) => console.error(err));
		}

		shouldCallBeAutoAnswered(callId)
		{
			if (Application.getPlatform() !== 'android')
			{
				return false;
			}
			const push = Application.getLastNotification();
			if (!push.id || !push.id.startsWith('IM_CALL_'))
			{
				return false;
			}

			if (!push.extra || !push.extra.server_time_unix || push.extra.server_time_unix == this.timeOfLastPushNotificationWithAutoAnswer)
			{
				return false;
			}

			try
			{
				const pushParams = JSON.parse(push.params);
				if (!pushParams.ACTION || !pushParams.ACTION.startsWith('IMINV_') || !pushParams.PARAMS || !pushParams.PARAMS.call)
				{
					return false;
				}

				const callFields = pushParams.PARAMS.call;
				const pushCallId = callFields.ID;

				const shouldAnswer = callId == pushCallId;
				if (shouldAnswer)
				{
					this.timeOfLastPushNotificationWithAutoAnswer = push.extra.server_time_unix;
				}

				return shouldAnswer;
			}
			catch
			{
				return false;
			}
		}

		_onNativeIncomingCall(nativeCall)
		{
			console.log('_onNativeIncomingCall', nativeCall);
			if (nativeCall.params.type !== 'internal')
			{
				return;
			}
			const isVideo = nativeCall.params.video;
			const callId = nativeCall.params.call.ID;
			const timestamp = nativeCall.params.ts;
			const timeAgo = Date.now() / 1000 - timestamp;
			const provider = nativeCall.params.call.PROVIDER

			if (timeAgo > 15)
			{
				console.error('Call originated too long time ago');
			}

			/*
			if (this.calls[callId])

			{
				console.error(`Call ${callId} is already known`);

				return;
			}
			 */

			this._instantiateCall(nativeCall.params.call, nativeCall.params.connectionData, nativeCall.params.users, nativeCall.params.logToken, nativeCall.params.userData);
			BX.postComponentEvent('CallEvents::incomingCall', [{
				callId,
				video: isVideo,
				isNative: true,
				provider,
			}], 'calls');
		}

		/**
		 * @param {Object} config
		 * @param {int} config.type
		 * @param {string} config.provider
		 * @param {string} config.entityType
		 * @param {string} config.entityId
		 * @param {string} config.provider
		 * @param {boolean} config.joinExisting
		 * @param {boolean} config.videoEnabled
		 * @param {boolean} config.enableMicAutoParameters
		 * @return Promise<BX.Call.AbstractCall>
		 */
		createCall(config)
		{
			return new Promise((resolve, reject) => {
				const callType = config.type || BX.Call.Type.Instant;
				const callProvider = config.provider === BX.Call.Provider.BitrixDev ? BX.Call.Provider.Bitrix : config.provider || 'Plain';

				if (config.joinExisting)
				{
					for (const callId in this.calls)
					{
						if (this.calls.hasOwnProperty(callId))
						{
							const call = this.calls[callId];
							if (call.provider == config.provider && call.associatedEntity.type == config.entityType && call.associatedEntity.id == config.entityId)
							{
								this.log(callId, 'Found existing call, attaching to it');

								return resolve({
									call,
									isNew: false,
								});
							}
						}
					}
				}

				const callParameters = {
					type: callType,
					provider: callProvider,
					entityType: config.entityType,
					entityId: config.entityId,
					joinExisting: !!config.joinExisting,
					userIds: BX.type.isArray(config.userIds) ? config.userIds : [],
				};

				console.log(`CallEngine.createCall.rest.callMethod - '${ajaxActions.createCall}', callParameters:`, callParameters);
				this.getRestClient().callMethod(ajaxActions.createCall, callParameters).then((response) => {
					console.log(`CallEngine.createCall.rest.callMethod - '${ajaxActions.createCall}', verbose response:`, response);
					if (response.error())
					{
						const error = response.error().getError();

						return reject({
							code: error.error,
							message: error.error_description,
						});
					}

					const createCallResponse = response.data();
					if (createCallResponse.userData)
					{
						// BX.Call.Util.setUserData(createCallResponse.userData)
					}

					if (createCallResponse.publicChannels)
					{
						BX.PULL.setPublicIds(Object.values(createCallResponse.publicChannels));
					}
					const callFields = createCallResponse.call;
					if (this.calls[callFields.ID])
					{
						if (this.calls[callFields.ID] instanceof CallStub)
						{
							this.calls[callFields.ID].destroy();
						}
						else
						{
							console.warn(`Call ${callFields.ID} already exists`);

							return resolve({
								call: this.calls[callFields.ID],
								isNew: false,
							});
						}
					}

					CallUtil.setUserData(createCallResponse.userData);
					const callFactory = this._getCallFactory(callFields.PROVIDER);
					const call = callFactory.createCall({
						id: parseInt(callFields.ID, 10),
						roomId: callFields.UUID,
						instanceId: this.getUuidv4(),
						direction: BX.Call.Direction.Outgoing,
						users: createCallResponse.users,
						userData: CallUtil.getCurrentUserName(),
						videoEnabled: (config.videoEnabled === true),
						enableMicAutoParameters: (config.enableMicAutoParameters !== false),
						associatedEntity: callFields.ASSOCIATED_ENTITY,
						events: {
							[BX.Call.Event.onDestroy]: this._onCallDestroyHandler,
							[BX.Call.Event.onJoin]: this._onCallJoinHandler,
							[BX.Call.Event.onLeave]: this._onCallLeaveHandler,
							[BX.Call.Event.onInactive]: this._onCallInactiveHandler,
							[BX.Call.Event.onActive]: this._onCallActiveHandler,
						},
						debug: config.debug === true,
						logToken: createCallResponse.logToken,
						connectionData: createCallResponse.connectionData,
						isCopilotActive: callFields.RECORD_AUDIO,
					});

					this.calls[callFields.ID] = call;

					if (createCallResponse.isNew)
					{
						this.log(call.id, 'Creating new call');
					}
					else
					{
						this.log(call.id, 'Server returned existing call, attaching to it');
					}

					this._onCallActive(call.id);

					resolve({
						call,
						isNew: createCallResponse.isNew,
					});
				}).catch((response) => {
					console.warn(`CallEngine.createCall.rest.callMethod.catch - '${ajaxActions.createCall}', verbose error:`, response);
					const error = response.answer || response;
					reject({
						code: error.error || 0,
						message: error.error_description || error,
					});
				});
			});
		}

		getCallWithId(id)
		{
			return new Promise((resolve, reject) => {
				if (this.calls[id])
				{
					return resolve({
						call: this.calls[id],
						isNew: false,
					});
				}

				this.getRestClient().callMethod(ajaxActions.getCall, { callId: id }).then((response) => {
					const data = response.data();
					if (data.call.END_DATE)
					{
						BX.postComponentEvent('CallEvents::inactive', [id], 'im.recent');
						BX.postComponentEvent('CallEvents::inactive', [id], 'im.messenger');

						return reject({
							code: 'ALREADY_FINISHED',
						});
					}
					resolve({
						call: this._instantiateCall(data.call, data.connectionData, data.users, data.logToken, data.userData),
						isNew: false,
					});
				}).catch((error) => {
					if (typeof (error.error) === 'function')
					{
						error = error.error().getError();
					}
					reject({
						code: error.error,
						message: error.error_description,
					});
				});
			});
		}

		getUuidv4()
		{
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
				const r = Math.random() * 16 | 0; const
					v = c == 'x' ? r : (r & 0x3 | 0x8);

				return v.toString(16);
			});
		}

		debug(debugFlag)
		{
			if (typeof (debugFlag) !== 'boolean')
			{
				debugFlag = !this.debugFlag;
			}
			this.debugFlag = debugFlag;
			console.warn(`Debug ${this.debugFlag ? 'enabled' : 'disabled'}`);
		}

		log(callId, ...params)
		{
			if (this.calls[callId])
			{
				this.calls[callId].log(...params);
			}
			else
			{
				console.log.apply(console, arguments);
			}
		}

		getRestClient()
		{
			return BX.rest;
		}

		getLogService()
		{
			// return "wss://192.168.3.197:9991/call-log";
			// return "wss://192.168.50.40:9991/call-log";
			return BX.componentParameters.get('callLogService', '');
		}

		isCallServerAllowed()
		{
			return BX.componentParameters.get('sfuServerEnabled');
		}

		isBitrixCallServerEnabled()
		{
			return BX.componentParameters.get('bitrixCallsEnabled');
		}

		isCallBetaIosEnabled()
		{
			return BX.componentParameters.get('callBetaIosEnabled', false);
		}

		isAIServiceEnabled()
		{
			return BX.componentParameters.get('isAIServiceEnabled', false);
		}

		// previous method to detect new call, kept in case of reverting
		// use isBitrixCallServerEnabled instead
		// isBitrixCallDevEnabled()
		// {
		// 	const chatSettings = Application.storage.getObject('settings.chat', {
		// 		bitrixCallDevEnable: false,
		// 	});
		//
		// 	return chatSettings.bitrixCallDevEnable;
		// }

		isNativeCall(callId)
		{
			if (!('callservice' in window))
			{
				return false;
			}

			const nativeCall = callservice.currentCall();

			return nativeCall && nativeCall.params.call.ID == callId;
		}

		_isCallSupported(call) {
			return call instanceof PlainCall
				|| call instanceof VoximplantCall
				|| (call instanceof BitrixCallDev && callEngine.isBitrixCallServerEnabled());
		}

		_onPullEvent(command, params, extra)
		{
			const handlers = {
				'Call::incoming': this._onPullIncomingCall.bind(this),
			};

			if (command.startsWith('Call::') && params.publicIds)
			{
				BX.PULL.setPublicIds(Object.values(params.publicIds));
				console.warn('CallEngine._onPullEvent', command, params, extra);
			}

			if (handlers[command])
			{
				handlers[command].call(this, params, extra);
			}
			else if (command.startsWith('Call::') && (params.call || params.callId))
			{
				const callId = params.call ? params.call.ID : params.callId;
				if (this.calls[callId])
				{
					this.calls[callId]._onPullEvent(command, params, extra);
				}
				else if (command === 'Call::ping')
				{
					this._onUnknownCallPing(params.callId, extra.server_time_ago, pingTTLWebsocket).then((result) => {
						if (result && this.calls[callId])
						{
							this.calls[callId]._onPullEvent(command, params, extra);
						}
					});
				}
			}
		}

		_onPullClientEvent(command, params, extra)
		{
			if (command.startsWith('Call::') && params.callId)
			{
				const callId = params.callId;
				if (this.calls[callId])
				{
					this.calls[callId]._onPullEvent(command, params, extra);
				}
				else if (command === 'Call::ping')
				{
					this._onUnknownCallPing(params.callId, extra.server_time_ago, pingTTLWebsocket).then((result) => {
						if (result && this.calls[callId])
						{
							this.calls[callId]._onPullEvent(command, params, extra);
						}
					});
				}
			}
		}

		_onPullIncomingCall(params, extra)
		{
			if (extra.server_time_ago > 30)
			{
				console.error('Call was started too long time ago');

				return;
			}

			const callFields = params.call;
			const callId = parseInt(callFields.ID, 10);
			let call;

			if (params.userData)
			{
				// BX.Call.Util.setUserData(params.userData);
			}

			if (this.calls[callId])
			{
				call = this.calls[callId];
			}
			else
			{
				CallUtil.setUserData(params.userData);
				const callFactory = this._getCallFactory(callFields.PROVIDER);
				call = callFactory.createCall({
					id: callId,
					roomId: callFields.UUID,
					instanceId: this.getUuidv4(),
					parentId: callFields.PARENT_ID || null,
					callFromMobile: params.isLegacyMobile === true,
					direction: BX.Call.Direction.Incoming,
					users: params.users,
					userData: CallUtil.getCurrentUserName(),
					initiatorId: params.senderId,
					associatedEntity: callFields.ASSOCIATED_ENTITY,
					type: callFields.TYPE,
					startDate: callFields.START_DATE,
					logToken: params.logToken,
					events: {
						[BX.Call.Event.onDestroy]: this._onCallDestroyHandler,
						[BX.Call.Event.onJoin]: this._onCallJoinHandler,
						[BX.Call.Event.onLeave]: this._onCallLeaveHandler,
						[BX.Call.Event.onInactive]: this._onCallInactiveHandler,
						[BX.Call.Event.onActive]: this._onCallActiveHandler,
					},
					connectionData: params.connectionData,
					isCopilotActive: callFields.RECORD_AUDIO,
				});

				this.calls[callId] = call;
				this._onCallActive(callId);
			}

			if (call && !(call instanceof CallStub))
			{
				call.addInvitedUsers(params.invitedUsers);
				BX.postComponentEvent('CallEvents::incomingCall', [{
					callId,
					video: params.video === true,
					isLegacyMobile: params.isLegacyMobile === true,
					userData: params.userData || null,
					autoAnswer: this.shouldCallBeAutoAnswered(callId),
					provider: callFields.PROVIDER,
				}], 'calls');
				call.log(`Incoming call ${call.id}`);
			}
		}

		_onUnknownCallPing(callId, serverTimeAgo, ttl)
		{
			return new Promise((resolve, reject) => {
				callId = parseInt(callId, 10);
				if (serverTimeAgo > ttl)
				{
					this.log(callId, 'Error: Ping was sent too long time ago');

					return resolve(false);
				}

				if (this.unknownCalls[callId])
				{
					return resolve(false);
				}
				this.unknownCalls[callId] = true;

				/* if (params.userData)
					{
						BX.Call.Util.setUserData(params.userData);
					} */

				this.getCallWithId(callId).then((result) => {
					this.unknownCalls[callId] = false;
					resolve(true);
				}).catch((error) => {
					this.unknownCalls[callId] = false;
					this.log(callId, 'Error: Could not instantiate call', error);
					resolve(false);
				});
			});
		}

		_instantiateCall(callFields, connectionData, users, logToken, userData)
		{
			if (this.calls[callFields.ID])
			{
				console.error(`Call ${callFields.ID} already exists`);

				return this.calls[callFields.ID];
			}

			CallUtil.setUserData(userData);
			const callFactory = this._getCallFactory(callFields.PROVIDER);
			const call = callFactory.createCall({
				id: parseInt(callFields.ID, 10),
				roomId: callFields.UUID,
				instanceId: this.getUuidv4(),
				initiatorId: parseInt(callFields.INITIATOR_ID, 10),
				parentId: callFields.PARENT_ID,
				direction: callFields.INITIATOR_ID == env.userId ? BX.Call.Direction.Outgoing : BX.Call.Direction.Incoming,
				users,
				userData: CallUtil.getCurrentUserName(),
				associatedEntity: callFields.ASSOCIATED_ENTITY,
				type: callFields.TYPE,
				startDate: callFields.START_DATE,
				logToken,

				events: {
					[BX.Call.Event.onDestroy]: this._onCallDestroyHandler,
					[BX.Call.Event.onJoin]: this._onCallJoinHandler,
					[BX.Call.Event.onLeave]: this._onCallLeaveHandler,
					[BX.Call.Event.onInactive]: this._onCallInactiveHandler,
					[BX.Call.Event.onActive]: this._onCallActiveHandler,
				},
				connectionData: connectionData,
				isCopilotActive: callFields.RECORD_AUDIO,
			});
			this.calls[callFields.ID] = call;

			this._onCallActive(call.id);

			return call;
		}

		_getCallFields(call)
		{
			return {
				id: call.id,
				provider: call.provider,
				associatedEntity: call.associatedEntity,
			};
		}

		_getCallFactory(providerType)
		{
			if (providerType == BX.Call.Provider.Plain)
			{
				return PlainCallFactory;
			}

			if (providerType == BX.Call.Provider.Voximplant)
			{
				return VoximplantCallFactory;
			}

			if (providerType === BX.Call.Provider.Bitrix)
			{
				// we need to process calls from web (with Bitrix provider) with new provider (BitrixDev) for dev builds
				return BitrixCallDevFactory;
			}

			if (providerType === BX.Call.Provider.BitrixDev)
			{
				return BitrixCallDevFactory;
			}

			throw new Error(`Unknown call provider type ${providerType}`);
		}

		_onCallJoin(e)
		{
			console.warn('CallEngine.CallEvents::join', e);
			this._onCallActive(e.callId);
		}

		_onCallLeave(e)
		{
			console.warn('CallEngine.CallEvents::leave', e.callId);
			this._onCallActive(e.callId);
		}

		_onCallInactive(callId)
		{
			console.warn('CallEngine.CallEvents::inactive', callId);
			if (!this.isMessengerReady)
			{
				this.callsToProcessAfterMessengerReady.delete(callId);
				return;
			}

			BX.postComponentEvent('CallEvents::inactive', [callId], 'im.recent');
			BX.postComponentEvent('CallEvents::inactive', [callId], 'im.messenger');
		}

		_onCallActive(callId)
		{
			console.warn('CallEngine.CallEvents::active', callId, this.calls[callId]);
			const call = this.calls[callId];
			if (call && !(call instanceof CallStub) && callEngine._isCallSupported(call))
			{
				if (!this.isMessengerReady)
				{
					this.callsToProcessAfterMessengerReady.add(callId);
					return;
				}

				BX.postComponentEvent('CallEvents::active', [this._getCallFields(call), call.joinStatus], 'im.recent');
				BX.postComponentEvent('CallEvents::active', [this._getCallFields(call), call.joinStatus], 'im.messenger');
			}
		}

		_onCallDestroy(e)
		{
			const callId = e.call.id;
			const call = this.calls[e.call];
			if (call)
			{
				call
					.off(BX.Call.Event.onJoin, this._onCallJoinHandler)
					.off(BX.Call.Event.onLeave, this._onCallLeaveHandler)
					.off(BX.Call.Event.onDestroy, this._onCallDestroyHandler)
					.off(BX.Call.Event.onInactive, this._onCallInactiveHandler)
					.off(BX.Call.Event.onActive, this._onCallActiveHandler);
			}

			this.calls[callId] = new CallStub({
				callId,
				onDelete: () => {
					if (this.calls[callId])
					{
						delete this.calls[callId];
					}
				},
			});

			if (!this.isMessengerReady)
			{
				this.callsToProcessAfterMessengerReady.delete(callId);
				return;
			}

			console.warn('CallEvents::inactive', [e.call.id]);
			BX.postComponentEvent('CallEvents::inactive', [e.call.id], 'im.recent');
			BX.postComponentEvent('CallEvents::inactive', [e.call.id], 'im.messenger');
		}

		destroy()
		{
			BX.removeCustomEvent('onPullEvent-im', this._onPullEventHandler);
			BX.removeCustomEvent('onPullClientEvent-im', this._onPullClientEventHandler);
		}
	}

	let PlainCallFactory =		{
		createCall(config)
		{
			return new PlainCall(config);
		},
	};

	let VoximplantCallFactory =		{
		createCall(config)
		{
			return new VoximplantCall(config);
		},
	};

	let BitrixCallFactory =		{
		createCall(config)
		{
			return new BitrixCall(config);
		},
	};

	let BitrixCallDevFactory =		{
		createCall(config)
		{
			return new BitrixCallDev(config);
		},
	};

	class CallStub
	{
		constructor(config)
		{
			this.callId = config.callId;
			this.lifetime = config.lifetime || 120;
			this.callbacks = {
				onDelete: BX.type.isFunction(config.onDelete) ? config.onDelete : function()
				{},
			};

			this.deleteTimeout = setTimeout(() => {
				this.callbacks.onDelete({
					callId: this.callId,
				});
			}, this.lifetime * 1000);
		}

		_onPullEvent(command, params, extra)
		{
			// do nothing
		}

		isAnyoneParticipating()
		{
			return false;
		}

		addEventListener()
		{
			return false;
		}

		removeEventListener()
		{
			return false;
		}

		destroy()
		{
			clearTimeout(this.deleteTimeout);
			this.callbacks.onDelete = function()
			{};
		}
	}

	class CCallUtil
	{
		constructor()
		{
			this.userData = {};
			this.usersInProcess = {};
		}

		updateUserData(callId, users)
		{
			const usersToUpdate = [];
			for (const user of users)
			{
				if (this.userData.hasOwnProperty(user))
				{
					continue;
				}

				usersToUpdate.push(user);
			}

			const result = new Promise((resolve, reject) => {
				if (usersToUpdate.length === 0)
				{
					return resolve();
				}

				BX.rest.callMethod('im.call.getUsers', { callId, userIds: usersToUpdate }).then((response) => {
					const result = BX.type.isPlainObject(response.answer.result) ? response.answer.result : {};
					users.forEach((userId) => {
						if (result[userId])
						{
							this.userData[userId] = result[userId];
						}
						delete this.usersInProcess[userId];
					});
					resolve();
				}).catch((error) => {
					reject(error.answer);
				});
			});

			for (const element of usersToUpdate)
			{
				this.usersInProcess[element] = result;
			}

			return result;
		}

		getUsers(callId, users)
		{
			return new Promise((resolve, reject) => {
				this.updateUserData(callId, users).then(() => {
					const result = {};
					users.forEach((userId) => result[userId] = this.userData[userId] || {});

					return resolve(result);
				}).catch((error) => reject(error));
			});
		}

		setUserData(userData)
		{
			for (const userId in userData)
			{
				this.userData[userId] = userData[userId];
			}
		}

		getCurrentUserName()
		{
			return this.userData[env.userId]?.name || env?.userId || '';
		}

		getDateForLog()
		{
			const d = new Date();

			return `${d.getFullYear()}-${this.lpad(d.getMonth() + 1, 2, '0')}-${this.lpad(d.getDate(), 2, '0')} ${this.lpad(d.getHours(), 2, '0')}:${this.lpad(d.getMinutes(), 2, '0')}:${this.lpad(d.getSeconds(), 2, '0')}.${d.getMilliseconds()}`;
		}

		getTimeForLog()
		{
			const d = new Date();

			return `${this.lpad(d.getHours(), 2, '0')}:${this.lpad(d.getMinutes(), 2, '0')}:${this.lpad(d.getSeconds(), 2, '0')}.${d.getMilliseconds()}`;
		}

		log()
		{
			console.log(this.getLogMessage.apply(this, arguments));
		}

		warn()
		{
			console.warn(this.getLogMessage.apply(this, arguments));
		}

		error()
		{
			console.error(this.getLogMessage.apply(this, arguments));
		}

		formatSeconds(timeInSeconds)
		{
			timeInSeconds = Math.floor(timeInSeconds);
			const seconds = timeInSeconds % 60;
			const minutes = (timeInSeconds - seconds) / 60;

			return `${this.lpad(minutes, 2, '0')}:${this.lpad(seconds, 2, '0')}`;
		}

		getTimeText(startTime)
		{
			if (!startTime)
			{
				return '';
			}

			const nowDate = new Date();
			let startDate = new Date(startTime);
			if (startDate.getTime() < nowDate.getDate())
			{
				startDate = nowDate;
			}

			let totalTime = nowDate - startDate;
			if (totalTime <= 0)
			{
				totalTime = 0;
			}

			let second = Math.floor(totalTime / 1000);

			let hour = Math.floor(second / 60 / 60);
			if (hour > 0)
			{
				second -= hour * 60 * 60;
			}

			const minute = Math.floor(second / 60);
			if (minute > 0)
			{
				second -= minute * 60;
			}

			return (hour > 0 ? hour + ':' : '')
				+ (hour > 0 ? minute.toString().padStart(2, "0") + ':' : minute + ':')
				+ second.toString().padStart(2, "0")
			;
		}

		getTimeInSeconds(startTime)
		{
			if (!startTime)
			{
				return '';
			}

			const nowDate = new Date();
			let startDate = new Date(startTime);
			if (startDate.getTime() < nowDate.getDate())
			{
				startDate = nowDate;
			}

			let totalTime = nowDate - startDate;
			if (totalTime <= 0)
			{
				totalTime = 0;
			}

			return Math.floor(totalTime / 1000);
		}

		lpad(str, length, chr)
		{
			str = str.toString();
			chr = chr || ' ';

			if (str.length > length)
			{
				return str;
			}

			let result = '';
			for (let i = 0; i < length - str.length; i++)
			{
				result += chr;
			}

			return result + str;
		}

		isAvatarBlank(url)
		{
			return typeof (url) !== 'string' || url == '' || url.endsWith(blankAvatar);
		}

		makeAbsolute(url)
		{
			let result;
			if (typeof (url) !== 'string')
			{
				return url;
			}

			if (url.startsWith('http'))
			{
				result = url;
			}
			else
			{
				result = url.startsWith('/') ? currentDomain + url : `${currentDomain}/${url}`;
			}

			return result;
		}

		getCustomMessage(message, userData)
		{
			let messageText;
			if (!BX.type.isPlainObject(userData))
			{
				userData = {};
			}

			if (userData.gender && BX.message.hasOwnProperty(`${message}_${userData.gender}`))
			{
				messageText = BX.message(`${message}_${userData.gender}`);
			}
			else
			{
				messageText = BX.message(message);
			}

			userData = this.convertKeysToUpper(userData);

			return messageText.replace(/#.+?#/gm, (match) => {
				const placeHolder = match.slice(1, 1 + match.length - 2);

				return userData.hasOwnProperty(placeHolder) ? userData[placeHolder] : match;
			});
		}

		isCallServerAllowed()
		{
			return BX.message('call_server_enabled') === 'Y';
		}

		getUserLimit()
		{
			if (this.isCallServerAllowed())
			{
				return parseInt(BX.message('call_server_max_users'));
			}

			return parseInt(BX.message('turn_server_max_users'));
		}

		getLogMessage()
		{
			let text = this.getDateForLog();

			for (const argument of arguments)
			{
				if (argument instanceof Error)
				{
					text = `${argument.message}\n${argument.stack}`;
				}
				else
				{
					try
					{
						text = `${text} | ${typeof (argument) === 'object' ? this.printObject(argument) : argument}`;
					}
					catch
					{
						text += ' | (circular structure)';
					}
				}
			}

			return text;
		}

		printObject(obj)
		{
			let result = '[';

			for (const key in obj)
			{
				if (obj.hasOwnProperty(key))
				{
					const val = obj[key];
					switch (typeof val)
					{
						case 'object':
							result += key + (val === null ? ': null; ' : ': (object); ');
							break;
						case 'string':
						case 'number':
						case 'boolean':
							result += `${key}: ${val.toString()}; `;
							break;
						default:
							result += `${key}: (${typeof (val)}); `;
					}
				}
			}

			return `${result}]`;
		}

		getUuidv4()
		{
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
				const r = Math.random() * 16 | 0; const
					v = c == 'x' ? r : (r & 0x3 | 0x8);

				return v.toString(16);
			});
		}

		debounce(fn, timeout, ctx)
		{
			let timer = 0;

			return function()
			{
				clearTimeout(timer);
				timer = setTimeout(() => fn.apply(ctx, arguments), timeout);
			};
		}

		array_flip(inputObject)
		{
			const result = {};
			for (const key in inputObject)
			{
				result[inputObject[key]] = key;
			}

			return result;
		}

		isDeviceSupported()
		{
			return Application.getApiVersion() >= 36;
		}

		forceBackgroundConnectPull(timeoutSeconds = 10)
		{
			return new Promise((resolve, reject) => {
				if (callEngine && (callEngine.pullStatus === 'online'))
				{
					resolve();

					return;
				}

				const onConnectTimeout = function()
				{
					console.error('Timeout while waiting for p&p to connect');
					BX.removeCustomEvent('onPullStatus', onPullStatus);
					reject('connect timeout');
				};
				const connectionTimeout = setTimeout(onConnectTimeout, timeoutSeconds * 1000);

				var onPullStatus = ({ status, additional }) => {
					if (!additional)
					{
						additional = {};
					}

					if (status === 'online')
					{
						BX.removeCustomEvent('onPullStatus', onPullStatus);
						clearTimeout(connectionTimeout);
						resolve();
					}

					if (status === 'offline' && additional.isError) // offline is fired on errors too
					{
						BX.removeCustomEvent('onPullStatus', onPullStatus);
						clearTimeout(connectionTimeout);
						reject('connect error');
					}
				};

				BX.addCustomEvent('onPullStatus', onPullStatus);
				BX.postComponentEvent('onPullForceBackgroundConnect', [], 'communication');
			});
		}

		showDeviceAccessConfirm(withVideo, acceptCallback = () => {}, declineCallback = () => {})
		{
			return new Promise((resolve) => {
				navigator.notification.confirm(
					withVideo ? BX.message('MOBILE_CALL_MICROPHONE_CAMERA_REQUIRED') : BX.message('MOBILE_CALL_MICROPHONE_REQUIRED'),
					(button) => (button == 1 ? acceptCallback() : declineCallback()),
					withVideo ? BX.message('MOBILE_CALL_NO_MICROPHONE_CAMERA_ACCESS') : BX.message('MOBILE_CALL_NO_MICROPHONE_ACCESS'),
					[
						BX.message('MOBILE_CALL_MICROPHONE_SETTINGS'),
						BX.message('MOBILE_CALL_MICROPHONE_CANCEL'),
					],
				);
			});
		}

		getSdkAudioManager()
		{
			if (BX.componentParameters.get('bitrixCallsEnabled'))
			{
				return JNBXAudioManager;
			}

			return JNVIAudioManager;
		}

		isAIServiceEnabled(isConference = false)
		{
			return BX.componentParameters.get('isAIServiceEnabled', false) && !isConference;
		}
	}

	class DeviceAccessError extends Error
	{
		constructor(justDenied)
		{
			super('Media access denied');
			this.name = 'DeviceAccessError';
			this.justDenied = justDenied;
		}
	}

	class CallJoinedElseWhereError extends Error
	{
		constructor()
		{
			super('Call joined elsewhere');
			this.name = 'CallJoinedElseWhereError';
		}
	}

	window.DeviceAccessError = DeviceAccessError;
	window.CallJoinedElseWhereError = CallJoinedElseWhereError;
	window.CallEngine = CallEngine;
	window.CCallUtil = CCallUtil;
	window.CallStub = CallStub;
})
();


'use strict';

(function()
{
	const { EntityReady } = jn.require('entity-ready');
	const { EventType } = jn.require('call/const');
	const { CallSettingsManager } = jn.require('call/settings-manager');

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
	};

	BX.Call.RoomType = {
		Small: 1,
		Conference: 2,
		Large: 3,
		Personal: 4,
	};

	BX.Call.RecorderStatus = {
		None: 0, // recording not available at the moment
		Ready: 1, // recording not started but available
		Enabled: 2, // recording started and not paused
		Disabled: 3, // recording stopped and can not be resumed
		Paused: 4, // recording started and paused
		Destroyed: 5, // recording will be aborted and no results will be provided
	};

	BX.Call.ErrorPreventingReconnection = {
		CanNotCreateRoom: 1,
		InputError: 2,
		AccessDenied: 3,
		RoomNotFound: 5,
		MalfunctioningSignaling: 7,
	};

	BX.Call.CallError = {
		SecurityKeyChanged: 'SECURITY_KEY_CHANGED',
		RoomClosed: 'ROOM_CLOSED',
		EmptySignalingUrl: 'EMPTY_SIGNALING_URL',
		EmptyCallToken: 'EMPTY_CALL_TOKEN',
		MediaServerMissingParams: 'MEDIA_SERVER_MISSING_PARAMS',
		MediaServerUnreachable: 'MEDIA_SERVER_UNREACHABLE',
		CallNotFound: 'CALL_NOT_FOUND',
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
		onUserJoined: 'onUserJoined',
		onUserStateChanged: 'onUserStateChanged',
		onUserMicrophoneState: 'onUserMicrophoneState',
		onUserCameraState: 'onUserCameraState',
		onUserScreenState: 'onUserScreenState',
		onUsersLimitExceeded: 'onUsersLimitExceeded',
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
		onRecorderStatusChanged: 'onRecorderStatusChanged',
		onCallTokenRequest: 'onCallTokenRequest',
		onAllParticipantsVideoMuted: 'onAllParticipantsVideoMuted',
		onAllParticipantsAudioMuted: 'onAllParticipantsAudioMuted',
		onAllParticipantsScreenshareMuted: 'onAllParticipantsScreenshareMuted',
		onRecordState: 'onRecordState',

		onParticipantAudioMuted: 'onParticipantAudioMuted',
		onParticipantVideoMuted: 'onParticipantVideoMuted',
		onParticipantScreenshareMuted: 'onParticipantScreenshareMuted',

		onRoomSettingsChanged: 'onRoomSettingsChanged',
		onUserPermissionsChanged: 'onUserPermissionsChanged',

		onCallConnected: 'onCallConnected',
	};

	BX.Call.Scheme = {
		classic: 1,
		jwt: 2,
	};

	class CallEngine
	{
		constructor()
		{
			this.legacyCalls = {};
			this.jwtCalls = {};
			this.unknownCalls = {};
			this.callsToProcessAfterMessengerReady = {
				legacy: new Map(),
				jwt: new Map(),
			};

			this.debugFlag = false;
			this.isMessengerReady = false;

			this.pullStatus = '';

			this._onPullEventHandler = this._onPullEvent.bind(this);
			this._onPullClientEventHandler = this._onPullClientEvent.bind(this);
			BX.addCustomEvent('onPullEvent-im', this._onPullEventHandler);
			BX.addCustomEvent('onPullClientEvent-im', this._onPullClientEventHandler);
			BX.addCustomEvent('onPullEvent-call', this._onPullEventHandler);
			BX.addCustomEvent('onAppActive', this.onAppActive.bind(this));
			BX.addCustomEvent(EventType.imMobile.updateCallToken, this._onCallTokenUpdate.bind(this, true));

			BX.addCustomEvent('onPullStatus', (e) => {
				this.pullStatus = e.status;
				console.log(`[${CallUtil.getTimeForLog()}]: pull status: ${this.pullStatus}`);
			});

			BX.addCustomEvent(EventType.imMobile.activeCallsReceived, this.onActiveCallsReceived.bind(this));

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
			for (const callId in this.jwtCalls)
			{
				if (this.jwtCalls.hasOwnProperty(callId)
					&& (this.jwtCalls[callId] instanceof PlainCall)
					&& !this.jwtCalls[callId].ready
					&& !this.isNativeCall(callId)
					&& ((Date.now()) - this.jwtCalls[callId].created) > 30000
				)
				{
					console.warn(`Destroying stale call ${callId}`);
					this.jwtCalls[callId].destroy();
				}
			}
			for (const callId in this.legacyCalls)
			{
				if (this.legacyCalls.hasOwnProperty(callId)
					&& (this.legacyCalls[callId] instanceof PlainCall)
					&& !this.legacyCalls[callId].ready
					&& !this.isNativeCall(callId)
					&& ((Date.now()) - this.legacyCalls[callId].created) > 30000
				)
				{
					console.warn(`Destroying stale call ${callId}`);
					this.legacyCalls[callId].destroy();
				}
			}
			this.startWithPush();
		}

		onActiveCallsReceived(activeCalls)
		{
			const callsToInstantiate = { ...activeCalls };

			const removeEventSubscription = (call) => {
				if (!BX.type.isFunction(call.off))
				{
					return;
				}

				call.off(BX.Call.Event.onDestroy, this._onCallDestroyHandler);
				call.off(BX.Call.Event.onJoin, this._onCallJoinHandler);
				call.off(BX.Call.Event.onLeave, this._onCallLeaveHandler);
				call.off(BX.Call.Event.onInactive, this._onCallInactiveHandler);
				call.off(BX.Call.Event.onActive, this._onCallActiveHandler);
			};

			const tryToRemoveCall = (call) => {
				if (!call)
				{
					return;
				}

				if (call.id in callsToInstantiate)
				{
					delete callsToInstantiate[call.id];

					return;
				}

				const isLegacyCall = CallUtil.isLegacyCall(call.provider, call.scheme);

				if (isLegacyCall)
				{
					this._onCallInactive({ callId: call.id });
					delete this.legacyCalls[call.id];
				}
				else
				{
					this._onCallInactive({ callUuid: call.uuid });
					delete this.jwtCalls[call.uuid];
				}

				removeEventSubscription(call);
			};

			Object.keys(this.legacyCalls)
				.forEach((key) => {
					const call = this.legacyCalls[key];
					tryToRemoveCall(call);
				});

			Object.keys(this.jwtCalls)
				.forEach((key) => {
					const call = this.jwtCalls[key];
					tryToRemoveCall(call);
				});

			Object.values(callsToInstantiate).forEach((call) => {
				const instantiatedCall = this._instantiateCall(
					call,
					call.CONNECTION_DATA,
					call.USERS,
					call.LOG_TOKEN,
					call.USER_DATA,
				);

				const isLegacyCall = CallUtil.isLegacyCall(call.PROVIDER, call.SCHEME);

				if (isLegacyCall)
				{
					this.legacyCalls[call.ID] = instantiatedCall;
				}
				else
				{
					this.jwtCalls[call.UUID] = instantiatedCall;
				}
			});
		}

		_onMessengerReady()
		{
			this.isMessengerReady = true;
			for (const call of this.callsToProcessAfterMessengerReady.legacy.values())
			{
				this._onCallActive(call);
			}
			this.callsToProcessAfterMessengerReady.legacy.clear();

			for (const call of this.callsToProcessAfterMessengerReady.jwt.values())
			{
				this._onCallActive(call);
			}
			this.callsToProcessAfterMessengerReady.jwt.clear();
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
			const callUuid = callFields.UUID;
			const timestamp = pushParams.PARAMS.ts;
			const timeAgo = Date.now() / 1000 - timestamp;
			const provider = callFields.PROVIDER;

			console.log('timeAgo:', timeAgo);
			if (CallUtil.isLegacyCall(provider, callFields.SCHEME))
			{
				this._onUnknownCallPing(callId, timeAgo, pingTTLPush).then((result) => {
					if (result && this.legacyCalls[callId])
					{
						BX.postComponentEvent('CallEvents::incomingCall', [{
							callId,
							callUuid,
							video: isVideo,
							autoAnswer: true,
							provider,
						}], 'calls');
					}
				}).catch((err) => console.error(err));
			}
			else
			{
				const call = this._instantiateCall(pushParams.PARAMS.call);
				this.jwtCalls[callUuid] = call;

				if (!tokenManager.getTokenCached(call.associatedEntity.chatId))
				{
					tokenManager.getToken(call.associatedEntity.chatId);
				}

				BX.postComponentEvent('CallEvents::incomingCall', [{
					callId,
					callUuid,
					video: isVideo,
					autoAnswer: true,
					provider,
				}], 'calls');
			}
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
		createJwtCall(config)
		{
			return new Promise(async (resolve, reject) => {
				const callType = config.type || BX.Call.Type.Instant;
				const callProvider = config.provider || 'Plain';

				if (config.joinExisting)
				{
					for (const callId in this.jwtCalls)
					{
						if (this.jwtCalls.hasOwnProperty(callId))
						{
							const call = this.jwtCalls[callId];
							if (call.provider == config.provider && call.associatedEntity.type == config.entityType && call.associatedEntity.id == config.entityId)
							{
								this.log(callId, 'Found existing call, attaching to it');

								if (!call.hasConnectionData)
								{
									try
									{
										await this.updateConnectionData(call);
									}
									catch (e)
									{
										return reject(e);
									}
								}

								return resolve({
									call,
									isNew: false,
								});
							}
						}
					}
				}

				let data;
				const chatId = config.chatInfo.chatId;
				const instanceId = this.getUuidv4();
				const callToken = await tokenManager.getToken(chatId);
				if (!callToken)
				{
					return reject({ code: BX.Call.CallError.EmptyCallToken });
				}

				try
				{
					data = await CallUtil.getCallConnectionData({
						callToken,
						callType,
						instanceId,
						provider: callProvider,
						isVideo: config.videoEnabled,
					}, chatId);
				}
				catch(error)
				{
					return reject(error);
				}

				const existingCall = this.jwtCalls[data.result.roomId];

				if (existingCall)
				{
					const call = this.jwtCalls[data.result.roomId];
					if (call instanceof CallStub)
					{
						existingCall.destroy();
					}
					else if (existingCall instanceof PlainCallJwt)
					{
						console.warn(`Call ${data.result.roomId} already exists`);
						existingCall.destroy();
					}
					delete this.jwtCalls[data.result.roomId];
				}

				const callFactory = this._getCallFactory(callProvider, BX.Call.Scheme.jwt);
				const call = callFactory.createCall({
					uuid: data.result.roomId,
					instanceId: instanceId,
					direction: BX.Call.Direction.Outgoing,
					userData: CallUtil.getCurrentUserName(),
					videoEnabled: (config.videoEnabled === true),
					enableMicAutoParameters: (config.enableMicAutoParameters !== false),
					associatedEntity: config.chatInfo,
					events: {
						[BX.Call.Event.onDestroy]: this._onCallDestroyHandler,
						[BX.Call.Event.onJoin]: this._onCallJoinHandler,
						[BX.Call.Event.onLeave]: this._onCallLeaveHandler,
						[BX.Call.Event.onInactive]: this._onCallInactiveHandler,
						[BX.Call.Event.onActive]: this._onCallActiveHandler,
					},
					connectionData: {
						mediaServerUrl: data.result.mediaServerUrl,
						roomData: data.result.roomData,
						roomType: data.result.roomType,
					},
					debug: config.debug === true,
					scheme: BX.Call.Scheme.jwt,
				});

				this.jwtCalls[call.uuid] = call;

				if (data.result.isNew)
				{
					this.log(call.uuid, 'Creating new call');
				}
				else
				{
					this.log(call.uuid, 'Server returned existing call, attaching to it');
				}

				this._onCallActive({ callUuid: call.uuid });

				resolve({
					call,
					isNew: data.result.isNew,
				});
			});
		}

		createLegacyCall(config)
		{
			return new Promise((resolve, reject) => {
				const callType = config.type || BX.Call.Type.Instant;
				const callProvider = config.provider || 'Plain';

				if (config.joinExisting)
				{
					for (const callId in this.legacyCalls)
					{
						if (this.legacyCalls.hasOwnProperty(callId))
						{
							const call = this.legacyCalls[callId];
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
					if (this.legacyCalls[callFields.ID])
					{
						if (this.legacyCalls[callFields.ID] instanceof CallStub)
						{
							this.legacyCalls[callFields.ID].destroy();
						}
						else
						{
							console.warn(`Call ${callFields.ID} already exists`);

							return resolve({
								call: this.legacyCalls[callFields.ID],
								isNew: false,
							});
						}
					}

					CallUtil.setUserData(createCallResponse.userData);
					const callFactory = this._getCallFactory(callFields.PROVIDER, BX.Call.Scheme.classic);
					const call = callFactory.createCall({
						id: parseInt(callFields.ID, 10),
						uuid: callFields.UUID,
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
						scheme: BX.Call.Scheme.classic,
					});

					this.legacyCalls[callFields.ID] = call;

					if (createCallResponse.isNew)
					{
						this.log(call.id, 'Creating new call');
					}
					else
					{
						this.log(call.id, 'Server returned existing call, attaching to it');
					}

					this._onCallActive({ callId: call.id, callUuid: call.uuid });

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

		getJwtCallWithId(uuid, config) {
			return new Promise((resolve, reject) => {
				const call = this.jwtCalls[uuid];

				if (config)
				{
					this.createJwtCall(config)
						.then((result) => {
							resolve(result);
						})
						.catch((error) => {
							reject(error);
						});
				}
				else if (call)
				{
					if (call.hasConnectionData)
					{
						resolve({ call, isNew: false });
					}
					else
					{
						this.updateConnectionData(call)
							.then(() => {
								resolve({ call, isNew: false });
							})
							.catch((error) => {
								reject(error);
							});
					}
				}
				else
				{
					const error = { code: BX.Call.CallError.CallNotFound };
					reject(error);
				}
			});
		}

		getLegacyCallWithId(id)
		{
			return new Promise((resolve, reject) => {
				if (this.legacyCalls[id])
				{
					return resolve({
						call: this.legacyCalls[id],
						isNew: false,
					});
				}

				this.getRestClient().callMethod(ajaxActions.getCall, { callId: id }).then((response) => {
					const data = response.data();
					if (data.call.END_DATE)
					{
						const callFields = {
							id: data.call.ID,
							uuid: data.call.UUID,
							provider: data.call.PROVIDER,
							associatedEntity: data.call.ASSOCIATED_ENTITY,
						};

						BX.postComponentEvent('CallEvents::inactive', [callFields], 'im.recent');
						BX.postComponentEvent('CallEvents::inactive', [callFields], 'im.messenger');

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

		getCallWithDialogId(dialogId)
		{
			const jwtCalls = Object.values(this.jwtCalls);
			const legacyCalls = Object.values(this.legacyCalls);
			const findCall = (calls) => calls.find((call) => call.associatedEntity?.id == dialogId);

			return findCall(jwtCalls) || findCall(legacyCalls);
		}

		updateConnectionData(call)
		{
			return new Promise((resolve, reject) =>
			{
				const chatId = call.associatedEntity.chatId;

				tokenManager.getToken(chatId).then((callToken) =>
				{
					if (!callToken)
					{
						return reject({ code: BX.Call.CallError.EmptyCallToken });
					}

					const callOptions = {
						callToken,
						callType: call.type,
						provider: call.provider,
						instanceId: call.instanceId,
					};

					return CallUtil.getCallConnectionData(callOptions, chatId, false);
				}).then((data) =>
				{
					call.setConnectionData({
						mediaServerUrl: data.result.mediaServerUrl,
						roomData: data.result.roomData,
						roomType: data.result.roomType,
					});

					return resolve();
				}).catch((error) =>
				{
					reject(error);
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
			const call = this.legacyCalls[callId] || this.jwtCalls[callId]
			if (call)
			{
				call.log(...params);
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
				|| call instanceof PlainCallJwt
				|| call instanceof VoximplantCall
				|| (call instanceof BitrixCallJwt && callEngine.isBitrixCallServerEnabled())
				|| (call instanceof BitrixCallDev && callEngine.isBitrixCallServerEnabled());
		}

		_onPullEvent(command, params, extra)
		{
			const handlers = {
				chatUserAdd: this.#onChatUserChange.bind(this),
				chatUserLeave: this.#onChatUserChange.bind(this),
				'Call::incoming': this._onPullIncomingCall.bind(this),
				'Call::callTokenUpdate': this._onCallTokenUpdate.bind(this, false),
				'Call::clearCallTokens': this._onCallTokenClear.bind(this),
				'Call::callV2AvailabilityChanged': this._onCallV2AvailabilityChanged.bind(this),
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
				const callScheme = params?.call?.SCHEME || params?.call?.scheme;
				const isLegacyCall = callScheme !== BX.Call.Scheme.jwt;
				const callId = params.call?.ID || params.call?.id || params.callId;
				const callUuid = params.call?.UUID || params.call?.uuid;
				const call = isLegacyCall ? this.legacyCalls[callId] : this.jwtCalls[callUuid];

				if (call)
				{
					call._onPullEvent(command, params, extra);
				}
				else if (command === 'Call::ping')
				{
					this._onUnknownCallPing(params.callId, extra.server_time_ago, pingTTLWebsocket).then((result) => {
						if (result && this.legacyCalls[callId])
						{
							this.legacyCalls[callId]._onPullEvent(command, params, extra);
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
				if (this.legacyCalls[callId])
				{
					this.legacyCalls[callId]._onPullEvent(command, params, extra);
				}
				else if (command === 'Call::ping')
				{
					this._onUnknownCallPing(params.callId, extra.server_time_ago, pingTTLWebsocket).then((result) => {
						if (result && this.legacyCalls[callId])
						{
							this.legacyCalls[callId]._onPullEvent(command, params, extra);
						}
					});
				}
			}
		}

		_onCallTokenUpdate(initial, params)
		{
			if (initial && tokenManager.getTokenCached(params.chatId))
			{
				return;
			}
			tokenManager.setToken(params.chatId, params.token);
		}

		_onCallTokenClear()
		{
			tokenManager.clearTokenList();
		}

		_onCallV2AvailabilityChanged(params)
		{
			CallSettingsManager.jwtCallsEnabled = params.isJwtEnabled;
			CallSettingsManager.plainCallsUseJwt = params.isPlainUseJwt;

			if (params?.callBalancerUrl)
			{
				CallSettingsManager.callBalancerUrl = params.callBalancerUrl;
			}
		}

		#onChatUserChange(params)
		{
			BX.postComponentEvent(EventType.callMobile.chatUserChanged, [{
				dialogId: params.dialogId,
				userCount: params.userCount,
			}], 'calls');
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
			const callUuid = callFields.uuid;
			let call = this.legacyCalls[callId] || this.jwtCalls[callUuid];

			if (params.userData)
			{
				// BX.Call.Util.setUserData(params.userData);
			}
			const provider = callFields.PROVIDER || callFields.provider;
			const callScheme = callFields.SCHEME || callFields.scheme;
			const isLegacyCall = CallUtil.isLegacyCall(provider, callScheme);

			if (!call)
			{
				CallUtil.setUserData(params.userData);
				const callFactory = this._getCallFactory(provider, callScheme);

				if (isLegacyCall)
				{
					call = callFactory.createCall({
						id: callId,
						uuid: callFields.UUID,
						instanceId: this.getUuidv4(),
						parentId: callFields.PARENT_ID || null,
						callFromMobile: params.isLegacyMobile === true,
						direction: BX.Call.Direction.Incoming,
						users: params.users,
						userData: CallUtil.getCurrentUserName(),
						initiatorId: params.senderId || parseInt(callFields.INITIATOR_ID, 10),
						associatedEntity: {
							userCounter: params.users?.length || 0,
							...callFields.ASSOCIATED_ENTITY,
						},
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
						scheme: callFields.SCHEME,
					});

					this.legacyCalls[callId] = call;
					this._onCallActive({ callId, callUuid });
				}
				else
				{
					call = callFactory.createCall({
						id: parseInt(params.callId, 10),
						uuid: callFields.uuid,
						instanceId: this.getUuidv4(),
						parentUuid: callFields.parentUuid || null,
						callFromMobile: params.isLegacyMobile === true,
						direction: BX.Call.Direction.Incoming,
						users: params.users,
						userData: CallUtil.getCurrentUserName(),
						initiatorId: params.senderId || parseInt(callFields.initiatorId, 10),
						associatedEntity: {
							userCounter: callFields.userCounter || 0,
							...callFields.associatedEntity,
						},
						type: callFields.type,
						startDate: callFields.startDate,
						logToken: callFields.logToken,
						events: {
							[BX.Call.Event.onDestroy]: this._onCallDestroyHandler,
							[BX.Call.Event.onJoin]: this._onCallJoinHandler,
							[BX.Call.Event.onLeave]: this._onCallLeaveHandler,
							[BX.Call.Event.onInactive]: this._onCallInactiveHandler,
							[BX.Call.Event.onActive]: this._onCallActiveHandler,
						},
						scheme: callFields.scheme,
					});

					this.jwtCalls[callUuid] = call;
					this._onCallActive({ callUuid });
				}
			}

			if (call && !(call instanceof CallStub))
			{
				if (isLegacyCall)
				{
					if (params.invitedUsers)
					{
						call.addInvitedUsers(params.invitedUsers);
					}
				}
				else if (!tokenManager.getTokenCached(call.associatedEntity.chatId))
				{
					tokenManager.getToken(call.associatedEntity.chatId);
				}
				BX.postComponentEvent('CallEvents::incomingCall', [{
					callId: call.id,
					callUuid: call.uuid,
					video: params.video === true,
					isLegacyMobile: params.isLegacyMobile === true,
					userData: params.userData || null,
					autoAnswer: this.shouldCallBeAutoAnswered(call.uuid), // need to check data from push
					provider: provider,
				}], 'calls');
				call.log(`Incoming call ${call.uuid}`);
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

				this.getLegacyCallWithId(callId).then((result) => {
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
			const callId = callFields.ID;
			const callUuid = callFields.UUID;
			let call = this.legacyCalls[callId] || this.jwtCalls[callUuid];
			if (call)
			{
				console.warn(`Call ${callId} already exists`);

				return call;
			}

			CallUtil.setUserData(userData);
			const callFactory = this._getCallFactory(callFields.PROVIDER, callFields.SCHEME);
			const isLegacy = CallUtil.isLegacyCall(callFields.PROVIDER, callFields.SCHEME);
			call = callFactory.createCall({
				id: parseInt(callFields.ID, 10),
				uuid: callFields.UUID,
				instanceId: this.getUuidv4(),
				initiatorId: parseInt(callFields.INITIATOR_ID, 10),
				parentId: callFields.PARENT_ID,
				parentUuid: callFields.PARENT_UUID,
				direction: callFields.INITIATOR_ID == env.userId ? BX.Call.Direction.Outgoing : BX.Call.Direction.Incoming,
				users,
				userData: CallUtil.getCurrentUserName(),
				associatedEntity: {
					userCounter: users?.length || 0,
					...callFields.ASSOCIATED_ENTITY
				},
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
				connectionData:this._getValidConnectionData(connectionData, isLegacy),
				isCopilotActive: callFields.RECORD_AUDIO,
				scheme: callFields.SCHEME,
			});

			if (isLegacy)
			{
				this.legacyCalls[callId] = call;
				this._onCallActive({ callId, callUuid });
			}
			else
			{
				this.jwtCalls[callUuid] = call;
				this._onCallActive({ callUuid });
			}

			return call;
		}

		_getCallFields(call)
		{
			return {
				id: call.id,
				uuid: call.uuid,
				provider: call.provider,
				associatedEntity: call.associatedEntity,
			};
		}

		_getValidConnectionData(connectionData, isLegacy)
		{
			const hasLegacyConnectionData = isLegacy && connectionData?.endpoint && connectionData.jwt;
			const hasJwtConnectionData = !isLegacy && connectionData.mediaServerUrl && connectionData.roomData;
			if (hasLegacyConnectionData || hasJwtConnectionData)
			{
				return connectionData;
			}

			return {};
		}

		_getCallFactory(providerType, scheme = null)
		{
			if (providerType == BX.Call.Provider.Plain)
			{
				const isJwt = scheme === BX.Call.Scheme.jwt || (!scheme && CallSettingsManager.isJwtInPlainCallsEnabled());

				return isJwt ? PlainCallJwtFactory : PlainCallLegacyFactory;
			}

			if (providerType == BX.Call.Provider.Voximplant)
			{
				return VoximplantCallFactory;
			}

			if (providerType === BX.Call.Provider.Bitrix)
			{
				const isJwt = scheme === BX.Call.Scheme.jwt || (!scheme && CallSettingsManager.isJwtCallsEnabled());

				return isJwt ? BitrixCallJwtFactory : BitrixCallLegacyFactory;
			}

			throw new Error(`Unknown call provider type ${providerType}`);
		}

		_onCallJoin(e)
		{
			console.warn('CallEngine.CallEvents::join', e);
			this._onCallActive(e);
		}

		_onCallLeave(e)
		{
			console.warn('CallEngine.CallEvents::leave', e);
			this._onCallActive(e);
		}

		_onCallInactive(e)
		{
			console.warn('CallEngine.CallEvents::inactive', e);
			const call = this.legacyCalls[e.callId] || this.jwtCalls[e.callUuid];
			if (!call)
			{
				return;
			}

			if (!this.isMessengerReady)
			{
				if (e.callId)
				{
					this.callsToProcessAfterMessengerReady.legacy.delete(e.callId);
				}
				else
				{
					this.callsToProcessAfterMessengerReady.jwt.delete(e.callUuid);
				}
				return;
			}

			BX.postComponentEvent('CallEvents::inactive', [this._getCallFields(call)], 'im.recent');
			BX.postComponentEvent('CallEvents::inactive', [this._getCallFields(call)], 'im.messenger');
		}

		_onCallActive(e)
		{
			console.warn('CallEngine.CallEvents::active', e);
			const call = this.legacyCalls[e.callId] || this.jwtCalls[e.callUuid];
			if (call && !(call instanceof CallStub) && callEngine._isCallSupported(call))
			{
				if (!this.isMessengerReady)
				{
					if (e.callId)
					{
						this.callsToProcessAfterMessengerReady.legacy.set(e.callId, e);
					}
					else
					{
						this.callsToProcessAfterMessengerReady.jwt.set(e.callUuid, e);
					}
					return;
				}

				BX.postComponentEvent('CallEvents::active', [this._getCallFields(call), call.joinStatus], 'im.recent');
				BX.postComponentEvent('CallEvents::active', [this._getCallFields(call), call.joinStatus], 'im.messenger');
			}
		}

		_onCallDestroy(e)
		{
			const isLegacy = !!e.callId;
			const call = isLegacy ? this.legacyCalls[e.callId] : this.jwtCalls[e.callUuid];
			const callId = isLegacy ? e.callId : e.callUuid;
			if (call)
			{
				call
					.off(BX.Call.Event.onJoin, this._onCallJoinHandler)
					.off(BX.Call.Event.onLeave, this._onCallLeaveHandler)
					.off(BX.Call.Event.onDestroy, this._onCallDestroyHandler)
					.off(BX.Call.Event.onInactive, this._onCallInactiveHandler)
					.off(BX.Call.Event.onActive, this._onCallActiveHandler);

				if (this.isMessengerReady)
				{
					console.warn('CallEvents::inactive', [e.callUuid]);
					BX.postComponentEvent('CallEvents::inactive', [this._getCallFields(call)], 'im.recent');
					BX.postComponentEvent('CallEvents::inactive', [this._getCallFields(call)], 'im.messenger');
				}
				else if (isLegacy)
				{
					this.callsToProcessAfterMessengerReady.legacy.delete(callId);
				}
				else
				{
					this.callsToProcessAfterMessengerReady.jwt.delete(callId);
				}
			}

			const callStub = new CallStub({
				callId,
				onDelete: () => {
					if (isLegacy && this.legacyCalls[callId])
					{
						delete this.legacyCalls[callId];
					}
					else if (this.jwtCalls[callId])
					{
						delete this.jwtCalls[callId];
					}
				},
			});

			if (isLegacy)
			{
				this.legacyCalls[callId] = callStub;
			}
			else
			{
				this.jwtCalls[callId] = callStub;
			}
		}

		destroy()
		{
			BX.removeCustomEvent('onPullEvent-im', this._onPullEventHandler);
			BX.removeCustomEvent('onPullClientEvent-im', this._onPullClientEventHandler);
			BX.removeCustomEvent('onPullEvent-call', this._onPullCallEventHandler);
		}
	}

	let PlainCallJwtFactory = {
		createCall(config)
		{
			return new PlainCallJwt(config);
		},
	};

	let PlainCallLegacyFactory = {
		createCall(config)
		{
			return new PlainCall(config);
		},
	};

	let VoximplantCallFactory = {
		createCall(config)
		{
			return new VoximplantCall(config);
		},
	};

	let BitrixCallFactory = {
		createCall(config)
		{
			return new BitrixCall(config);
		},
	};

	let BitrixCallJwtFactory = {
		createCall(config)
		{
			return new BitrixCallJwt(config);
		},
	};

	let BitrixCallLegacyFactory = {
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

			/* User role & room permission */
			this.roomPermissions =
			{
				AudioEnabled: false,
				VideoEnabled: false,
				ScreenShareEnabled: false,
			};

			this.userPermissions =
			{
				ask: false,
				audio: true,
				can_approve: false,
				change_role: false,
				change_settings: false,
				end_call: false,
				give_permissions: false,
				invite: false,
				join_call: false,
				kick_user: false,
				mute: true,
				mute_others: false,
				record_call: false,
				screen_share: true,
				update: false,
				video: true,
				view_users: false,
			};

			const UsersRoles =
			{
				ADMIN: 'ADMIN', // chat admin
				MANAGER: 'MANAGER', // aka moderator
				USER: 'USER', // regular user
			}

			this.regularUserRoles = [UsersRoles.USER]; // TODO got this from signaling in future

			this.currentUserRole = UsersRoles.USER;
		}

		setCurrentUserRole(role)
		{
			if (role)
			{
				this.currentUserRole = role.toUpperCase();
			}
		}

		setRoomPermissions(_roomPermissions)
		{
			if (!BX.type.isPlainObject(_roomPermissions))
			{
				return;
			}

			this.roomPermissions = _roomPermissions;
		}

		setUserPermissionsByRoomPermissions(_roomPermissions)
		{
			if (this.isRegularUser(this.getCurrentUserRole()))
			{
				let permissions = this.getUserPermissions();

				for (let permission in _roomPermissions)
				{
					if (_roomPermissions.hasOwnProperty(permission))
					{
						switch (permission)
						{
							case 'AudioEnabled':
								permissions.audio = _roomPermissions[permission];
								break;
							case 'VideoEnabled':
								permissions.video = _roomPermissions[permission];
								break;
							case 'ScreenShareEnabled':
								permissions.screen_share = _roomPermissions[permission];
								break;
						}
					}
				}

				this.setUserPermissions(permissions);
			}
		}

		updateUserPermissionByNewRoomPermission(_roomPermission, value)
		{
			if (this.isRegularUser(this.getCurrentUserRole()))
			{
				let permissions = this.getUserPermissions();

				switch (_roomPermission)
				{
					case 'audio':
						permissions.audio = value;
						break;
					case 'video':
						permissions.video = value;
						break;
					case 'screen_share':
						permissions.screen_share = value;
						break;
				}

				this.setUserPermissions(permissions);
			}
		}

		isRegularUser(_role)
		{
			return this.regularUserRoles.includes(_role);
		}

		getRoomPermissions()
		{
			return this.roomPermissions;
		}

		setUserPermissions(_userPermissions)
		{
			if (BX.type.isPlainObject(_userPermissions))
			{
				for (let permission in _userPermissions)
				{
					if (this.userPermissions.hasOwnProperty(permission))
					{
						this.userPermissions[permission] = _userPermissions[permission];
					}
				}
			}
		}

		getUserPermissions()
		{
			return this.userPermissions;
		}

		getCurrentUserRole()
		{
			return this.currentUserRole;
		}

		havePermissionToBroadcast(type)
		{
			let havePermission = false;

			switch (type)
			{
				case 'mic':

					havePermission = this.userPermissions.audio;
					break;
				case 'cam':

					havePermission = this.userPermissions.video;
					break;
				case 'screenshare':

					havePermission = this.userPermissions.screen_share;
					break;
			}

			return havePermission;
		}

		canControlChangeSettings()
		{
			return !!this.userPermissions.change_settings;
		}

		canControlGiveSpeakPermission()
		{
			return !!this.userPermissions.give_permissions;
		}

		getUserRoleByUserId(userId)
		{
			if (this.userData.hasOwnProperty(userId))
			{
				return this.userData[userId].role;
			}
		}

		/* ------ */

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

		getUser(callId, userId)
		{
			return new Promise((resolve, reject) =>
			{
				if (this.userData.hasOwnProperty(userId))
				{
					return resolve(this.userData[userId]);
				}
				else if (this.usersInProcess.hasOwnProperty(userId))
				{
					this.usersInProcess[userId].then(() =>
					{
						return resolve(this.userData[userId]);
					});
				}
				else
				{
					this.updateUserData(callId, [userId]).then(() =>
					{
						return resolve(this.userData[userId]);
					});
				}
			});
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
				if (!this.userData[userId].color)
				{
					this.userData[userId].color = this.getAvatarBackground();
				}
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

		getAvatarBackground()
		{
			const colorList = ['#006484', '#00A2E8', '#559BE6', '#688800', '#7FA800', '#11A9D9', '#0B66C3', '#004F69', '#00789E', '#506900', '#828B95'];

			return colorList[Math.floor(Math.random() * colorList.length)];
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

		convertKeysToUpper(obj)
		{
			var result = JSON.parse(JSON.stringify(obj)); // clone object

			for (let k in result)
			{
				const u = k.toUpperCase();

				if (u != k)
				{
					result[u] = result[k];
					delete result[k];
				}
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

		isLegacyCall(provider, scheme = null)
		{
			if (scheme)
			{
				return scheme === BX.Call.Scheme.classic;
			}

			const isLegacyPlainCall = provider === BX.Call.Provider.Plain && !CallSettingsManager.isJwtInPlainCallsEnabled();
			const isLegacyBitrixCall = provider === BX.Call.Provider.Bitrix && !CallSettingsManager.isJwtCallsEnabled();

			return isLegacyPlainCall || isLegacyBitrixCall;
		}

		isJwtCallsSupported() {
			return CallSettingsManager.isJwtCallsSupported();
		}

		getApiVersion()
		{
			return CallSettingsManager.isJwtCallsSupported() ? '2.0.0' : '1.0.0';
		}

		async getLegacyCallConnectionData(callOptions)
		{
			return new Promise((resolve, reject) => {
				let url = `${callOptions.endpoint}/join`;
				url += `?token=${callOptions.jwt}`;
				url += `&clientVersion=${this.getApiVersion()}`;
				url += `&clientPlatform=${Application.getPlatform()}`;

				BX.ajax({
						url: url,
						method: 'GET',
						dataType: 'json',
						prepareData: false,
						skipAuthCheck: true,
						timeout: 60,
					})
					.then((response) =>
					{
						if (response.result?.mediaServerUrl && response.result?.roomData)
						{
							resolve(`${response.result.mediaServerUrl}?roomData=${response.result.roomData}`);
						}

						reject({name: 'MEDIASERVER_MISSING_PARAMS', message: `Incorrect signaling response`});
					})
					.catch((error) =>
					{
						reject(error);
					});
			});
		}

		getCallConnectionData(callOptions, chatId, mustCreate = true)
		{
			if (!BX.type.isPlainObject(callOptions))
			{
				callOptions = {};
			}

			return new Promise(async (resolve, reject) => {
				const roomType = callOptions.provider === BX.Call.Provider.Plain && CallSettingsManager.isJwtInPlainCallsEnabled()
					? BX.Call.RoomType.Personal
					: BX.Call.RoomType.Small;
				const clientVersion = CallUtil.getApiVersion();
				const clientPlatform = Application.getPlatform();
				const url = `${CallSettingsManager.callBalancerUrl}/v2/join?mustCreate=${Boolean(mustCreate)}`;

				const userToken = await tokenManager.getUserToken(chatId);

				const data = JSON.stringify({
					userToken,
					roomType,
					clientVersion,
					clientPlatform,
					...callOptions,
				});

				BX.ajax({
					url,
					data,
					method: 'POST',
					prepareData: false,
					dataType: 'json',
					skipAuthCheck: true,
					timeout: 60,
				}).then((response) => {
					if (response.result?.mediaServerUrl && response.result?.roomData)
					{
						resolve(response);
					}

					const error = { code: BX.Call.CallError.MediaServerMissingParams };
					reject(error);
				}).catch((e) => {
					let error = e;

					if (e.xhr?.responseText)
					{
						try
						{
							const response = JSON.parse(e.xhr.responseText);
							if (response.error.message)
							{
								error = { code: response.error.message, errorCode: response.error.code };
							}
						}
						catch
						{
							error = { code: BX.Call.CallError.MediaServerUnreachable };
						}
					}

					reject(error);
				});
			});
		}

		async getCallConnectionDataById(callUuid)
		{
			const call = callEngine.jwtCalls[callUuid];

			if (!call)
			{
				const error = { code: BX.Call.CallError.CallNotFound };

				return Promise.reject(error);
			}

			return this.getCallConnectionData(
				{
					callType: call.type,
					instanceId: call.instanceId,
					provider: call.provider,
					callToken: tokenManager.getTokenCached(call.associatedEntity.chatId),
				},
				call.associatedEntity.chatId,
				false,
			);
		}

		abortGetCallConnectionData()
		{
			this.abortableRequest?.abort();
		}

		isNewMobileGridEnabled()
		{
			return BX.componentParameters.get('isNewMobileGridEnabled', false);
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

"use strict";

(function ()
{
	const blankAvatar = '/bitrix/js/im/images/blank.gif';
	const ajaxActions = {
		createCall: 'im.call.create',
		createChildCall: 'im.call.createChildCall',
		getPublicChannels: 'pull.channel.public.list',
		getCall: 'im.call.get'
	};

	const pingTTLWebsocket = 10;
	const pingTTLPush = 45;

	BX.Call = {};

	BX.Call.State = {
		Incoming: 'Incoming'
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
		Failed: 'Failed'
	};

	BX.Call.JoinStatus = {
		None: 'none',
		Local: 'local',
		Remote: 'remote'
	};

	BX.Call.Type = {
		Instant: 1,
		Permanent: 2
	};

	BX.Call.Provider = {
		Plain: 'Plain',
		Voximplant: 'Voximplant',
	};

	BX.Call.StreamTag = {
		Main: 'main',
		Screen: 'screen'
	};

	BX.Call.Direction = {
		Incoming: 'Incoming',
		Outgoing: 'Outgoing'
	};

	BX.Call.Quality = {
		VeryHigh: "very_high",
		High: "high",
		Medium: "medium",
		Low: "low",
		VeryLow: "very_low"
	};

	BX.Call.UserMnemonic = {
		all: 'all',
		none: 'none'
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
	};

	class CallEngine
	{
		constructor()
		{
			this.calls = {};
			this.unknownCalls = {};

			this.debugFlag = false;

			this.pullStatus = '';

			this._onPullEventHandler = this._onPullEvent.bind(this);
			this._onPullClientEventHandler = this._onPullClientEvent.bind(this);
			BX.addCustomEvent("onPullEvent-im", this._onPullEventHandler);
			BX.addCustomEvent("onPullClientEvent-im", this._onPullClientEventHandler);
			BX.addCustomEvent("onAppActive", this.onAppActive.bind(this));

			BX.addCustomEvent("onPullStatus", (e) =>
			{
				this.pullStatus = e.status;
				console.log("[" + CallUtil.getTimeForLog() + "]: pull status: " + this.pullStatus);
			});

			this._onCallJoinHandler = this._onCallJoin.bind(this);
			this._onCallLeaveHandler = this._onCallLeave.bind(this);
			this._onCallDestroyHandler = this._onCallDestroy.bind(this);
			this._onCallInactiveHandler = this._onCallInactive.bind(this);
			this._onCallActiveHandler = this._onCallActive.bind(this);

			this._onNativeIncomingCallHandler = this._onNativeIncomingCall.bind(this);
			if ("callservice" in window)
			{
				callservice.on("incoming", this._onNativeIncomingCallHandler);
				if (callservice.currentCall())
				{
					setTimeout(() => this._onNativeIncomingCall(callservice.currentCall()), 0);
				}
			}

			this.startWithPush();

			setTimeout(
				() => BX.postComponentEvent("onPullGetStatus", [], "communication"),
				100
			)
		}

		onAppActive()
		{
			for (var callId in this.calls)
			{
				if (this.calls.hasOwnProperty(callId)
					&& (this.calls[callId] instanceof PlainCall)
					&& !this.calls[callId].ready
					&& !this.isNativeCall(callId)
					&& ((new Date()) - this.calls[callId].created) > 30000
				)
				{
					console.warn("Destroying stale call " + callId);
					this.calls[callId].destroy();
				}
			}
			this.startWithPush();
		}

		startWithPush()
		{
			const push = Application.getLastNotification();

			if (!push.id || !push.id.startsWith("IM_CALL_"))
			{
				return;
			}

			let pushParams;
			try
			{
				pushParams = JSON.parse(push.params)
			} catch (e)
			{
				navigator.notification.alert(BX.message("MOBILE_CALL_INTERNAL_ERROR").replace("#ERROR_CODE#", "E005"));
			}

			if (!pushParams.ACTION || !pushParams.ACTION.startsWith('IMINV_') || !pushParams.PARAMS || !pushParams.PARAMS.call)
			{
				return;
			}

			console.log('Starting with PUSH: ', push);
			let callFields = pushParams.PARAMS.call;
			let isVideo = pushParams.PARAMS.video;
			let callId = callFields.ID;
			let timestamp = pushParams.PARAMS.ts;
			let timeAgo = (new Date()).getTime() / 1000 - timestamp;
			console.log("timeAgo: ", timeAgo)
			this._onUnknownCallPing(callId, timeAgo, pingTTLPush).then((result) =>
			{
				if (result && this.calls[callId])
				{
					BX.postComponentEvent("CallEvents::incomingCall", [{
						callId: callId,
						video: isVideo,
						autoAnswer: true,
					}], "calls");
				}
			}).catch(err => console.error(err));
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
			try
			{
				let pushParams = JSON.parse(push.params);
				if (!pushParams.ACTION || !pushParams.ACTION.startsWith('IMINV_') || !pushParams.PARAMS || !pushParams.PARAMS.call)
				{
					return false;
				}

				let callFields = pushParams.PARAMS.call;
				let pushCallId = callFields.ID;
				return callId == pushCallId;
			} catch (e)
			{
				return false;
			}
		}

		_onNativeIncomingCall(nativeCall)
		{
			console.log("_onNativeIncomingCall", nativeCall);
			if (nativeCall.params.type !== 'internal')
			{
				return;
			}
			let isVideo = nativeCall.params.video;
			let callId = nativeCall.params.call.ID;
			let timestamp = nativeCall.params.ts;
			let timeAgo = (new Date()).getTime() / 1000 - timestamp;
			if (timeAgo > 15)
			{
				console.error("Call originated too long time ago");
			}
			if (this.calls[callId])
			{
				console.error("Call " + callId + " is already known");
				return;
			}

			this._instantiateCall(nativeCall.params.call, nativeCall.params.users, nativeCall.params.logToken);
			BX.postComponentEvent("CallEvents::incomingCall", [{
				callId: callId,
				video: isVideo,
				isNative: true,
			}], "calls");
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
			return new Promise((resolve, reject) =>
			{
				let callType = config.type || BX.Call.Type.Instant;
				let callProvider = config.provider || 'Plain';

				if (config.joinExisting)
				{
					for (var callId in this.calls)
					{
						if (this.calls.hasOwnProperty(callId))
						{
							var call = this.calls[callId];
							if (call.provider == config.provider && call.associatedEntity.type == config.entityType && call.associatedEntity.id == config.entityId)
							{
								this.log(callId, "Found existing call, attaching to it");
								return resolve({
									call: call,
									isNew: false
								});
							}
						}
					}
				}

				let callParameters = {
					type: callType,
					provider: callProvider,
					entityType: config.entityType,
					entityId: config.entityId,
					joinExisting: !!config.joinExisting,
					userIds: BX.type.isArray(config.userIds) ? config.userIds : []
				};

				this.getRestClient().callMethod(ajaxActions.createCall, callParameters).then((response) =>
				{
					if (response.error())
					{
						let error = response.error().getError();
						return reject({
							code: error.error,
							message: error.error_description
						});
					}

					let createCallResponse = response.data();
					if (createCallResponse.userData)
					{
						//BX.Call.Util.setUserData(createCallResponse.userData)
					}
					if (createCallResponse.publicChannels)
					{
						BX.PULL.setPublicIds(Object.values(createCallResponse.publicChannels))
					}
					let callFields = createCallResponse.call;
					if (this.calls[callFields['ID']])
					{
						if (this.calls[callFields['ID']] instanceof CallStub)
						{
							this.calls[callFields['ID']].destroy();
						}
						else
						{
							console.warn("Call " + callFields['ID'] + " already exists");
							return resolve({
								call: this.calls[callFields['ID']],
								isNew: false
							});
						}
					}

					let callFactory = this._getCallFactory(callFields['PROVIDER']);
					let call = callFactory.createCall({
						id: parseInt(callFields['ID'], 10),
						instanceId: this.getUuidv4(),
						direction: BX.Call.Direction.Outgoing,
						users: createCallResponse.users,
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
						logToken: createCallResponse.logToken
					});

					this.calls[callFields['ID']] = call;

					if (createCallResponse.isNew)
					{
						this.log(call.id, "Creating new call");
					}
					else
					{
						this.log(call.id, "Server returned existing call, attaching to it");
					}

					BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.recent");
					BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.messenger");

					resolve({
						call: call,
						isNew: createCallResponse.isNew
					});
				}).catch(function (response)
				{
					let error = response.answer || response;
					reject({
						code: error.error || 0,
						message: error.error_description || error
					})
				})
			});
		}

		getCallWithId(id)
		{
			return new Promise((resolve, reject) =>
			{
				if (this.calls[id])
				{
					return resolve({
						call: this.calls[id],
						isNew: false
					});
				}

				this.getRestClient().callMethod(ajaxActions.getCall, {callId: id}).then((response) =>
				{
					let data = response.data();
					if (data.call.END_DATE)
					{
						BX.postComponentEvent("CallEvents::inactive", [id], "im.recent");
						BX.postComponentEvent("CallEvents::inactive", [id], "im.messenger");
						return reject({
							code: "ALREADY_FINISHED"
						});
					}
					resolve({
						call: this._instantiateCall(data.call, data.users, data.logToken),
						isNew: false
					})
				}).catch(function (error)
				{
					if (typeof (error.error) === "function")
					{
						error = error.error().getError();
					}
					reject({
						code: error.error,
						message: error.error_description
					})
				})
			})
		}

		getUuidv4()
		{
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c)
			{
				var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
				return v.toString(16);
			});
		}

		debug(debugFlag)
		{
			if (typeof (debugFlag) != "boolean")
			{
				debugFlag = !this.debugFlag;
			}
			this.debugFlag = debugFlag;
			console.warn("Debug " + (this.debugFlag ? "enabled" : "disabled"))
		}

		log(callId, ...params)
		{
			if (this.calls[callId])
			{
				this.calls[callId].log(...params);
			}
			else
			{
				console.log.apply(console, arguments)
			}
		}

		getRestClient()
		{
			return BX.rest
		}

		getLogService()
		{
			//return "wss://192.168.3.197:9991/call-log";
			//return "wss://192.168.50.40:9991/call-log";
			return BX.componentParameters.get('callLogService', '');
		}

		isCallServerAllowed()
		{
			return BX.componentParameters.get('sfuServerEnabled', true);
		}

		isNativeCall(callId)
		{
			if (!("callservice" in window))
			{
				return false;
			}

			const nativeCall = callservice.currentCall();
			return nativeCall && nativeCall.params.call.ID == callId;
		}

		_onPullEvent(command, params, extra)
		{
			let handlers = {
				'Call::incoming': this._onPullIncomingCall.bind(this),
			};

			if (command.startsWith('Call::') && params.publicIds)
			{
				BX.PULL.setPublicIds(Object.values(params.publicIds));
			}

			if (handlers[command])
			{
				handlers[command].call(this, params, extra);
			}
			else if (command.startsWith('Call::') && (params['call'] || params['callId']))
			{
				let callId = params['call'] ? params['call']['ID'] : params['callId'];
				if (this.calls[callId])
				{
					this.calls[callId]._onPullEvent(command, params, extra);
				}
				else if (command === 'Call::ping')
				{
					this._onUnknownCallPing(params.callId, extra.server_time_ago, pingTTLWebsocket).then((result) =>
					{
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
			if (command.startsWith('Call::') && params['callId'])
			{
				let callId = params['callId'];
				if (this.calls[callId])
				{
					this.calls[callId]._onPullEvent(command, params, extra);
				}
				else if (command === 'Call::ping')
				{
					this._onUnknownCallPing(params.callId, extra.server_time_ago, pingTTLWebsocket).then((result) =>
					{
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
				console.error("Call was started too long time ago");
				return;
			}

			let callFields = params.call;
			let callId = parseInt(callFields.ID, 10);
			let call;

			if (params.userData)
			{
				//BX.Call.Util.setUserData(params.userData);
			}

			if (this.calls[callId])
			{
				call = this.calls[callId];
			}
			else
			{
				let callFactory = this._getCallFactory(callFields.PROVIDER);
				call = callFactory.createCall({
					id: callId,
					instanceId: this.getUuidv4(),
					parentId: callFields.PARENT_ID || null,
					callFromMobile: params.isLegacyMobile === true,
					direction: BX.Call.Direction.Incoming,
					users: params.users,
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
					}
				});

				this.calls[callId] = call;

				BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.recent");
				BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.messenger");
			}

			console.log(call);
			if (call && !(call instanceof CallStub))
			{
				call.addInvitedUsers(params.invitedUsers);
				BX.postComponentEvent("CallEvents::incomingCall", [{
					callId: callId,
					video: params.video === true,
					isLegacyMobile: params.isLegacyMobile === true,
					userData: params.userData || null,
					autoAnswer: this.shouldCallBeAutoAnswered(callId),
				}], "calls");
				call.log("Incoming call " + call.id);
			}
		}

		_onUnknownCallPing(callId, serverTimeAgo, ttl)
		{
			return new Promise((resolve, reject) =>
				{
					callId = parseInt(callId, 10);
					if (serverTimeAgo > ttl)
					{
						this.log(callId, "Error: Ping was sent too long time ago");
						return resolve(false);
					}
					if (this.unknownCalls[callId])
					{
						return resolve(false);
					}
					this.unknownCalls[callId] = true;

					/*if (params.userData)
					{
						BX.Call.Util.setUserData(params.userData);
					}*/

					this.getCallWithId(callId).then((result) =>
					{
						this.unknownCalls[callId] = false;
						resolve(true);
					}).catch((error) =>
					{
						this.unknownCalls[callId] = false;
						this.log(callId, "Error: Could not instantiate call", error);
						resolve(false);
					})
				}
			)
		}

		_instantiateCall(callFields, users, logToken)
		{
			if (this.calls[callFields['ID']])
			{
				console.error("Call " + callFields['ID'] + " already exists");
				return this.calls[callFields['ID']];
			}

			let callFactory = this._getCallFactory(callFields['PROVIDER']);
			let call = callFactory.createCall({
				id: parseInt(callFields['ID'], 10),
				instanceId: this.getUuidv4(),
				initiatorId: parseInt(callFields['INITIATOR_ID'], 10),
				parentId: callFields['PARENT_ID'],
				direction: callFields['INITIATOR_ID'] == env.userId ? BX.Call.Direction.Outgoing : BX.Call.Direction.Incoming,
				users: users,
				associatedEntity: callFields['ASSOCIATED_ENTITY'],
				type: callFields['TYPE'],
				startDate: callFields['START_DATE'],
				logToken: logToken,

				events: {
					[BX.Call.Event.onDestroy]: this._onCallDestroyHandler,
					[BX.Call.Event.onJoin]: this._onCallJoinHandler,
					[BX.Call.Event.onLeave]: this._onCallLeaveHandler,
					[BX.Call.Event.onInactive]: this._onCallInactiveHandler,
					[BX.Call.Event.onActive]: this._onCallActiveHandler,
				}
			});
			this.calls[callFields['ID']] = call;

			BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.recent");
			BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.messenger");

			return call;
		}

		_getCallFields(call)
		{
			return {
				id: call.id,
				provider: call.provider,
				associatedEntity: call.associatedEntity
			}
		}

		_getCallFactory(providerType)
		{
			if (providerType == BX.Call.Provider.Plain)
			{
				return PlainCallFactory;
			}
			else if (providerType == BX.Call.Provider.Voximplant)
			{
				return VoximplantCallFactory;
			}

			throw Error("Unknown call provider type " + providerType)
		}

		_onCallJoin(e)
		{
			let call = this.calls[e.callId];
			if (call && !(call instanceof CallStub))
			{
				console.warn("CallEvents::active", e.callId, call.joinStatus);
				BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.recent");
				BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.messenger");
			}
		}

		_onCallLeave(e)
		{
			let call = this.calls[e.callId];
			if (call && !(call instanceof CallStub))
			{
				console.warn("CallEvents::active", e.callId, call.joinStatus);
				BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.recent");
				BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.messenger");
			}
		}

		_onCallInactive(callId)
		{
			console.warn("CallEvents::inactive", callId);
			BX.postComponentEvent("CallEvents::inactive", [callId], "im.recent");
			BX.postComponentEvent("CallEvents::inactive", [callId], "im.messenger");
		}

		_onCallActive(callId)
		{
			let call = this.calls[callId];
			if (call && !(call instanceof CallStub))
			{
				console.warn("CallEvents::active", callId, call.joinStatus);
				BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.recent");
				BX.postComponentEvent("CallEvents::active", [this._getCallFields(call), call.joinStatus], "im.messenger");
			}
		}

		_onCallDestroy(e)
		{
			let callId = e.call.id;
			let call = this.calls[e.call];
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
				callId: callId,
				onDelete: () =>
				{
					if (this.calls[callId])
					{
						delete this.calls[callId];
					}
				}
			});

			console.warn("CallEvents::inactive", [e.call.id]);
			BX.postComponentEvent("CallEvents::inactive", [e.call.id], "im.recent");
			BX.postComponentEvent("CallEvents::inactive", [e.call.id], "im.messenger");
		}

		destroy()
		{
			BX.removeCustomEvent("onPullEvent-im", this._onPullEventHandler);
			BX.removeCustomEvent("onPullClientEvent-im", this._onPullClientEventHandler);
		}
	}

	let PlainCallFactory =
		{
			createCall(config)
			{
				return new PlainCall(config);
			}
		};

	let VoximplantCallFactory =
		{
			createCall(config)
			{
				return new VoximplantCall(config);
			}
		};

	class CallStub
	{
		constructor(config)
		{
			this.callId = config.callId;
			this.lifetime = config.lifetime || 120;
			this.callbacks = {
				onDelete: BX.type.isFunction(config.onDelete) ? config.onDelete : function ()
				{
				}
			};

			this.deleteTimeout = setTimeout(() =>
			{
				this.callbacks.onDelete({
					callId: this.callId
				})
			}, this.lifetime * 1000);
		}

		_onPullEvent(command, params, extra)
		{
			// do nothing
		};

		isAnyoneParticipating()
		{
			return false;
		};

		addEventListener()
		{
			return false;
		};

		removeEventListener()
		{
			return false;
		};

		destroy()
		{
			clearTimeout(this.deleteTimeout);
			this.callbacks.onDelete = function ()
			{
			};
		};
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
			var usersToUpdate = [];
			for (var i = 0; i < users.length; i++)
			{
				if (this.userData.hasOwnProperty(users[i]))
				{
					continue;
				}

				usersToUpdate.push(users[i]);
			}

			let result = new Promise((resolve, reject) =>
			{
				if (usersToUpdate.length === 0)
				{
					return resolve();
				}

				BX.rest.callMethod("im.call.getUsers", {callId: callId, userIds: usersToUpdate}).then((response) =>
				{
					let result = BX.type.isPlainObject(response.answer.result) ? response.answer.result : {};
					users.forEach((userId) =>
					{
						if (result[userId])
						{
							this.userData[userId] = result[userId];
						}
						delete this.usersInProcess[userId];
					});
					resolve();

				}).catch(function (error)
				{
					reject(error.answer);
				});
			});

			for (let i = 0; i < usersToUpdate.length; i++)
			{
				this.usersInProcess[usersToUpdate[i]] = result;
			}
			return result;
		}

		getUsers(callId, users)
		{
			return new Promise((resolve, reject) =>
			{
				this.updateUserData(callId, users).then(() =>
				{
					let result = {};
					users.forEach((userId) => result[userId] = this.userData[userId] || {});
					return resolve(result);
				}).catch(error => reject(error));
			});
		}

		setUserData(userData)
		{
			for (let userId in userData)
			{
				this.userData[userId] = userData[userId];
			}
		}

		getDateForLog()
		{
			var d = new Date();

			return d.getFullYear() + "-" + this.lpad(d.getMonth() + 1, 2, '0') + "-" + this.lpad(d.getDate(), 2, '0') + " " + this.lpad(d.getHours(), 2, '0') + ":" + this.lpad(d.getMinutes(), 2, '0') + ":" + this.lpad(d.getSeconds(), 2, '0') + "." + d.getMilliseconds();
		}

		getTimeForLog()
		{
			var d = new Date();

			return this.lpad(d.getHours(), 2, '0') + ":" + this.lpad(d.getMinutes(), 2, '0') + ":" + this.lpad(d.getSeconds(), 2, '0') + "." + d.getMilliseconds();
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
			let seconds = timeInSeconds % 60;
			let minutes = (timeInSeconds - seconds) / 60;

			return this.lpad(minutes, 2, '0') + ':' + this.lpad(seconds, 2, '0');
		}

		lpad(str, length, chr)
		{
			str = str.toString();
			chr = chr || ' ';

			if (str.length > length)
			{
				return str;
			}

			var result = '';
			for (var i = 0; i < length - str.length; i++)
			{
				result += chr;
			}

			return result + str;
		}

		isAvatarBlank(url)
		{
			return typeof (url) !== "string" || url == "" || url.endsWith(blankAvatar);
		}

		makeAbsolute(url)
		{
			var result;
			if (typeof (url) !== "string")
			{
				return url;
			}
			if (url.startsWith("http"))
			{
				result = url;
			}
			else
			{
				result = url.startsWith("/") ? currentDomain + url : currentDomain + "/" + url;
			}
			return result;
		}

		getCustomMessage(message, userData)
		{
			var messageText;
			if (!BX.type.isPlainObject(userData))
			{
				userData = {};
			}

			if (userData.gender && BX.message.hasOwnProperty(message + '_' + userData.gender))
			{
				messageText = BX.message(message + '_' + userData.gender);
			}
			else
			{
				messageText = BX.message(message);
			}

			userData = this.convertKeysToUpper(userData);

			return messageText.replace(/#.+?#/gm, function (match)
			{
				var placeHolder = match.substr(1, match.length - 2);
				return userData.hasOwnProperty(placeHolder) ? userData[placeHolder] : match;
			});
		}

		isCallServerAllowed()
		{
			return BX.message('call_server_enabled') === 'Y'
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
			var text = this.getDateForLog();

			for (var i = 0; i < arguments.length; i++)
			{
				if (arguments[i] instanceof Error)
				{
					text = arguments[i].message + "\n" + arguments[i].stack
				}
				else
				{
					try
					{
						text = text + ' | ' + (typeof (arguments[i]) == 'object' ? this.printObject(arguments[i]) : arguments[i]);
					} catch (e)
					{
						text = text + ' | (circular structure)';
					}
				}
			}

			return text;
		}

		printObject(obj)
		{
			let result = "[";

			for (let key in obj)
			{
				if (obj.hasOwnProperty(key))
				{
					let val = obj[key];
					switch (typeof val)
					{
						case 'object':
							result += key + (val === null ? ": null; " : ": (object); ");
							break;
						case 'string':
						case 'number':
						case 'boolean':
							result += key + ": " + val.toString() + "; ";
							break;
						default:
							result += key + ": (" + typeof (val) + "); ";
					}
				}
			}
			return result + "]";
		}

		getUuidv4()
		{
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c)
			{
				var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
				return v.toString(16);
			});
		}

		debounce(fn, timeout, ctx)
		{
			let timer = 0;
			return function ()
			{
				clearTimeout(timer);
				timer = setTimeout(() => fn.apply(ctx, arguments), timeout);
			};
		}

		array_flip(inputObject)
		{
			let result = {};
			for (let key in inputObject)
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

				var onConnectTimeout = function()
				{
					console.error("Timeout while waiting for p&p to connect");
					BX.removeCustomEvent("onPullStatus", onPullStatus);
					reject('connect timeout');
				};
				var connectionTimeout = setTimeout(onConnectTimeout, timeoutSeconds * 1000);

				var onPullStatus = ({status, additional}) =>
				{
					if (!additional)
					{
						additional = {};
					}
					if (status === 'online')
					{
						BX.removeCustomEvent("onPullStatus", onPullStatus);
						clearTimeout(connectionTimeout);
						resolve();
					}

					if (status === 'offline' && additional.isError) // offline is fired on errors too
					{
						BX.removeCustomEvent("onPullStatus", onPullStatus);
						clearTimeout(connectionTimeout);
						reject('connect error');
					}
				};

				BX.addCustomEvent("onPullStatus", onPullStatus);
				BX.postComponentEvent("onPullForceBackgroundConnect", [], "communication");
			});
		}

		showDeviceAccessConfirm(withVideo, acceptCallback = () => { }, declineCallback = () => { })
		{
			return new Promise((resolve) =>
			{
				navigator.notification.confirm(
					withVideo ? BX.message("MOBILE_CALL_MICROPHONE_CAMERA_REQUIRED") : BX.message("MOBILE_CALL_MICROPHONE_REQUIRED"),
					(button) => button == 1 ? acceptCallback() : declineCallback(),
					withVideo ? BX.message("MOBILE_CALL_NO_MICROPHONE_CAMERA_ACCESS") : BX.message("MOBILE_CALL_NO_MICROPHONE_ACCESS"),
					[
						BX.message("MOBILE_CALL_MICROPHONE_SETTINGS"),
						BX.message("MOBILE_CALL_MICROPHONE_CANCEL"),
					],
				);
			});
		}
	}

	class DeviceAccessError extends Error
	{
		constructor(justDenied)
		{
			super("Media access denied");
			this.name = "DeviceAccessError";
			this.justDenied = justDenied;
		}
	}

	class CallJoinedElseWhereError extends Error
	{
		constructor()
		{
			super("Call joined elsewhere");
			this.name = "CallJoinedElseWhereError";
		}
	}

	window.DeviceAccessError = DeviceAccessError;
	window.CallJoinedElseWhereError = CallJoinedElseWhereError;
	window.CallEngine = CallEngine;
	window.CCallUtil = CCallUtil;
	window.CallStub = CallStub;
})
();
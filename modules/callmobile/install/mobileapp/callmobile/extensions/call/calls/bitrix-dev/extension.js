'use strict';

(function() {
	include('Calls');

	BX.DoNothing = function() {};

	const ajaxActions = {
		invite: 'im.call.invite',
		cancel: 'im.call.cancel',
		answer: 'im.call.answer',
		decline: 'im.call.decline',
		hangup: 'im.call.hangup',
		ping: 'im.call.ping',
	};

	const pullEvents = {
		ping: 'Call::ping',
		answer: 'Call::answer',
		hangup: 'Call::hangup',
		userInviteTimeout: 'Call::userInviteTimeout',
		repeatAnswer: 'Call::repeatAnswer',
		switchTrackRecordStatus: 'Call::switchTrackRecordStatus',
	};

	const clientEvents = {
		voiceStarted: 'Call::voiceStarted',
		voiceStopped: 'Call::voiceStopped',
		microphoneState: 'Call::microphoneState',
		cameraState: 'Call::cameraState',
		videoPaused: 'Call::videoPaused',
		screenState: 'Call::screenState',
		floorRequest: 'Call::floorRequest',
		emotion: 'Call::emotion',
		showUsers: 'Call::showUsers',
		showAll: 'Call::showAll',
		hideAll: 'Call::hideAll',
	};

	const BitrixCallDevEvent = {
		onCallConference: 'BitrixCallDev::onCallConference',
	};

	const pingPeriod = 5000;
	const backendPingPeriod = 25000;

	const connectionRestoreTime = 15000;

	class BitrixCallDev
	{
		constructor(params)
		{
			this.id = params.id;
			this.roomId = params.roomId;
			this.instanceId = params.instanceId;
			this.parentId = params.parentId || null;
			this.direction = params.direction;
			this.type = params.type || BX.Call.Type.Instant; // @see {BX.Call.Type}

			this.ready = false;
			this.userId = env.userId;
			this.userData = params.userData;

			this.initiatorId = params.initiatorId || '';
			this.users = BX.type.isArray(params.users) ? params.users.filter((userId) => userId != this.userId) : [];

			this.associatedEntity = BX.type.isPlainObject(params.associatedEntity) ? params.associatedEntity : {};
			this.startDate = new Date(BX.prop.getString(params, 'startDate', ''));

			// media constraints
			this.videoEnabled = params.videoEnabled === true;
			this.videoHd = params.videoHd === true;
			this.cameraId = params.cameraId || '';
			this.microphoneId = params.microphoneId || '';

			this.muted = params.muted === true;

			this.logToken = params.logToken || '';
			if (callEngine.getLogService() && this.logToken)
			{
				this.logger = new CallLogger(callEngine.getLogService(), this.logToken);
			}

			this.connectionData = params.connectionData || {};
			this.isCopilotActive = Boolean(params.isCopilotActive);

			this.bitrixCallDev = null;

			this.signaling = new Signaling({
				call: this,
			});

			this.peers = {};
			this._joinStatus = BX.Call.JoinStatus.None;
			Object.defineProperty(this, 'joinStatus', {
				get: this.getJoinStatus.bind(this),
				set: this.setJoinStatus.bind(this),
			});
			this._active = false; // has remote pings
			Object.defineProperty(this, 'active', {
				get: this.getActive.bind(this),
				set: this.setActive.bind(this),
			});

			this.localVideoShown = false;
			this.clientEventsBound = false;
			this._screenShared = false;

			this.someoneWasConnected = false;

			this.eventEmitter = new JNEventEmitter();
			if (typeof (params.events) === 'object')
			{
				for (const eventName in params.events)
				{
					this.eventEmitter.on(eventName, params.events[eventName]);
				}
			}

			// event handlers
			this.__onLocalVideoStreamReceivedHandler = this.__onLocalVideoStreamReceived.bind(this);
			this.__onLocalVideoStreamRemovedHandler = this.__onLocalVideoStreamRemoved.bind(this);

			this.__onCallDisconnectedHandler = this.__onCallDisconnected.bind(this);
			this.__onCallMessageReceivedHandler = this.__onCallMessageReceived.bind(this);
			this.__onCallEndpointAddedHandler = this.__onCallEndpointAdded.bind(this);
			this.__onCallReconnectedHandler = this.__onCallReconnected.bind(this);

			this.__onSDKLogMessageHandler = this.__onSDKLogMessage.bind(this);

			this.initPeers();

			this.pingUsersInterval = setInterval(this.pingUsers.bind(this), pingPeriod);
			this.pingBackendInterval = setInterval(this.pingBackend.bind(this), backendPingPeriod);

			this.lastPingReceivedTimeout = null;
			this.lastSelfPingReceivedTimeout = null;

			this.created = new Date();
		}

		log()
		{
			const text = CallUtil.getLogMessage.apply(CallUtil, arguments);
			if (console && callEngine.debugFlag)
			{
				const a = [`Call log [${CallUtil.getTimeForLog()}]: `];
				console.log.apply(this, a.concat(Array.prototype.slice.call(arguments)));
			}

			if (this.logger)
			{
				this.logger.log(text);
			}
		}

		on(event, handler)
		{
			this.eventEmitter.on(event, handler);

			return this;
		}

		off(event, handler)
		{
			if (this.eventEmitter)
			{
				this.eventEmitter.off(event, handler);
			}

			return this;
		}

		initPeers()
		{
			this.users.forEach((userId) => {
				userId = parseInt(userId, 10);
				this.peers[userId] = this.createPeer(userId);
			});
		}

		reinitPeers()
		{
			for (const userId in this.peers)
			{
				if (this.peers.hasOwnProperty(userId) && this.peers[userId])
				{
					this.peers[userId].destroy();
					this.peers[userId] = null;
				}
			}

			this.initPeers();
		}

		pingUsers()
		{
			if (this.ready)
			{
				const users = this.users.concat(this.userId);
				this.signaling.sendPingToUsers({ userId: users }, true);
			}
		}

		pingBackend()
		{
			if (this.ready)
			{
				this.signaling.sendPingToBackend();
			}
		}

		repeatAnswerEvents()
		{
			this.signaling.sendRepeatAnswer({ userId: this.userId });
		}

		createPeer(userId)
		{
			let incomingVideoAllowed;
			if (this.videoAllowedFrom === BX.Call.UserMnemonic.all)
			{
				incomingVideoAllowed = true;
			}
			else if (this.videoAllowedFrom === BX.Call.UserMnemonic.none)
			{
				incomingVideoAllowed = false;
			}
			else if (BX.type.isArray(this.videoAllowedFrom))
			{
				incomingVideoAllowed = this.videoAllowedFrom.some((allowedUserId) => {
					return allowedUserId == userId;
				});
			}
			else
			{
				incomingVideoAllowed = true;
			}

			return new Peer({
				call: this,
				userId,
				ready: userId == this.initiatorId,
				isIncomingVideoAllowed: incomingVideoAllowed,

				onStreamReceived: (e) => this.eventEmitter.emit(BX.Call.Event.onStreamReceived, [e.userId, e.stream]),
				onUserVoiceStarted: (e) => this.eventEmitter.emit(BX.Call.Event.onUserVoiceStarted, [e.userId]),
				onUserVoiceStopped: (e) => this.eventEmitter.emit(BX.Call.Event.onUserVoiceStopped, [e.userId]),
				onStateChanged: this.__onPeerStateChanged.bind(this),
				onInviteTimeout: this.__onPeerInviteTimeout.bind(this),
				onInitialState: (e) => {
					this.eventEmitter.emit(BX.Call.Event.onUserFloorRequest, [e.userId, e.floorRequest]);
					this.eventEmitter.emit(BX.Call.Event.onUserMicrophoneState, [e.userId, e.microphoneState]);
				},
				onHandRaised: (e) => this.eventEmitter.emit(BX.Call.Event.onUserFloorRequest, [e.userId, e.isHandRaised]),
			});
		}

		getUsers()
		{
			const result = {};
			for (const userId in this.peers)
			{
				result[userId] = this.peers[userId].calculatedState;
			}

			return result;
		}

		getJoinStatus()
		{
			return this._joinStatus;
		}

		setJoinStatus(newStatus)
		{
			if (newStatus == this._joinStatus)
			{
				return;
			}
			this._joinStatus = newStatus;
			switch (this._joinStatus)
			{
				case BX.Call.JoinStatus.Local:
					this.eventEmitter.emit(BX.Call.Event.onJoin, [{ callId: this.id, local: true }]);
					break;
				case BX.Call.JoinStatus.Remote:
					this.eventEmitter.emit(BX.Call.Event.onJoin, [{ callId: this.id, local: false }]);
					break;
				case BX.Call.JoinStatus.None:
					this.eventEmitter.emit(BX.Call.Event.onLeave, [{ callId: this.id }]);
					break;
			}
		}

		getActive()
		{
			return this._active;
		}

		setActive(newActive)
		{
			if (newActive === this._active)
			{
				return;
			}
			this._active = newActive;
			this.eventEmitter.emit(this.active ? BX.Call.Event.onActive : BX.Call.Event.onInactive, [this.id]);
		}

		bindClientEvents()
		{
			const streamManager = VoxImplant.Hardware.StreamManager.get();

			if (!this.clientEventsBound)
			{
				streamManager.on(VoxImplant.Hardware.HardwareEvents.DevicesUpdated, this.__onLocalDevicesUpdatedHandler);
				streamManager.on(VoxImplant.Hardware.HardwareEvents.MediaRendererAdded, this.__onLocalMediaRendererAddedHandler);
				streamManager.on(VoxImplant.Hardware.HardwareEvents.MediaRendererUpdated, this.__onLocalMediaRendererAddedHandler);
				streamManager.on(VoxImplant.Hardware.HardwareEvents.BeforeMediaRendererRemoved, this.__onBeforeLocalMediaRendererRemovedHandler);
				this.clientEventsBound = true;
			}
		}

		removeClientEvents()
		{
			if (BXClient)
			{
				BXClient.getInstance().off(BXClient.Events.LogMessage, this.__onSDKLogMessageHandler);
			}
		}

		isMuted()
		{
			return this.muted;
		}

		setMuted(muted)
		{
			this.muted = muted;
			if (this.bitrixCallDev)
			{
				this.bitrixCallDev.sendAudio = !this.muted;
				this.signaling.sendMicrophoneState(!this.muted);
			}
		}

		toggleSubscriptionRemoteVideo(toggleList)
		{
			if (this.bitrixCallDev && this.bitrixCallDev.toggleSubscriptionRemoteVideo)
			{
				this.bitrixCallDev.toggleSubscriptionRemoteVideo(toggleList);
			}
		}

		onCentralUserSwitch(userId)
		{
			if (this.bitrixCallDev && this.bitrixCallDev.onCentralUserSwitch)
			{
				this.bitrixCallDev.onCentralUserSwitch(userId);
			}
		}

		switchCamera()
		{
			if (!this.videoEnabled)
			{
				return;
			}
			JNBXCameraManager.useBackCamera = !JNBXCameraManager.useBackCamera;
		}

		isFrontCameraUsed()
		{
			return !JNBXCameraManager.useBackCamera;
		}

		isVideoEnabled()
		{
			return this.videoEnabled;
		}

		setVideoEnabled(videoEnabled)
		{
			videoEnabled = (videoEnabled === true);
			if (this.videoEnabled == videoEnabled)
			{
				return;
			}

			this.videoEnabled = videoEnabled;
			if (this.bitrixCallDev)
			{
				this.bitrixCallDev.setSendVideo(this.videoEnabled && !this.videoPaused);
				this.signaling.sendCameraState(this.videoEnabled);
			}
		}

		setVideoPaused(videoPaused)
		{
			if (this.videoPaused == videoPaused || !this.videoEnabled)
			{
				return;
			}

			this.videoPaused = videoPaused;
			if (this.bitrixCallDev)
			{
				this.bitrixCallDev.setSendVideo(this.videoEnabled && !this.videoPaused);
				this.signaling.sendVideoPaused(this.videoPaused);
			}
		}

		requestFloor(requestActive)
		{
			this.bitrixCallDev.raiseHand(requestActive);
		}

		/**
		 * Updates list of users,
		 * @param {BX.Call.UserMnemonic | int[]} userList
		 */
		allowVideoFrom(userList)
		{
			if (this.videoAllowedFrom == userList)
			{
				return;
			}
			this.videoAllowedFrom = userList;

			if (userList === BX.Call.UserMnemonic.all)
			{
				this.signaling.sendShowAll();
				userList = Object.keys(this.peers);
			}
			else if (userList === BX.Call.UserMnemonic.none)
			{
				this.signaling.sendHideAll();
				userList = [];
			}
			else if (BX.type.isArray(userList))
			{
				this.signaling.sendShowUsers(userList);
			}
			else
			{
				throw new TypeError('userList is in wrong format');
			}

			const users = {};
			userList.forEach((userId) => users[userId] = true);

			for (const userId in this.peers)
			{
				if (!this.peers.hasOwnProperty(userId))
				{
					continue;
				}

				if (users[userId])
				{
					this.peers[userId].allowIncomingVideo(true);
				}
				else
				{
					this.peers[userId].allowIncomingVideo(false);
				}
			}
		}

		/**
		 * Invites users to participate in the call.
		 *
		 * @param {Object} config
		 * @param {int[]} [config.users] Array of ids of the users to be invited.
		 */
		inviteUsers(config = {})
		{
			this.ready = true;
			const users = BX.type.isArray(config.users) ? config.users : this.users;

			this.attachToConference().then(() => {
				this.signaling.sendPingToUsers({ userId: users });
				if (users.length > 0)
				{
					return this.signaling.inviteUsers({
						userIds: users,
						video: this.videoEnabled ? 'Y' : 'N',
					});
				}
			}).then((response) => {
				this.joinStatus = BX.Call.JoinStatus.Local;
				for (const user of users)
				{
					const userId = parseInt(user, 10);
					if (!this.users.includes(userId))
					{
						this.users.push(userId);
					}

					if (!this.peers[userId])
					{
						this.peers[userId] = this.createPeer(userId);
						this.eventEmitter.emit(BX.Call.Event.onUserInvited, [{
							userId,
						}]);
					}
					this.peers[userId].onInvited();
				}
			}).catch(this.onFatalError.bind(this));
		}

		/**
		 * @param {Object} config
		 * @param {bool} [config.useVideo]
		 */
		answer(config)
		{
			this.ready = true;
			if (!BX.type.isPlainObject(config))
			{
				config = {};
			}
			this.videoEnabled = (config.useVideo === true);

			this.sendAnswer();
			this.attachToConference().then(() => {
				this.log('Attached to conference');
				this.joinStatus = BX.Call.JoinStatus.Local;
			}).catch(this.onFatalError.bind(this));
		}

		sendAnswer()
		{
			this.signaling.sendAnswer();
		}

		decline(code)
		{
			this.ready = false;
			const data = {
				callId: this.id,
				callInstanceId: this.instanceId,
			};
			if (code)
			{
				data.code = code;
			}

			BX.rest.callMethod(ajaxActions.decline, data);
		}

		hangup(code, reason)
		{
			if (!this.ready)
			{
				const error = new Error('Hangup in wrong state');
				this.log(error);

				return;
			}

			const tempError = new Error();
			tempError.name = 'Call stack:';
			this.log(`Hangup received \n${tempError.stack}`);

			const data = {};
			this.ready = false;
			if (typeof (code) !== 'undefined')
			{
				data.code = code;
			}

			if (typeof (reason) !== 'undefined')
			{
				data.reason = reason;
			}

			if (reason !== 'SIGNALING_DUPLICATE_PARTICIPANT')
			{
				this.signaling.sendHangup(data);
				// for future reconnections
				this.reinitPeers();
			}
			this.joinStatus = BX.Call.JoinStatus.None;

			data.userId = this.users;
			this.muted = false;

			if (this.bitrixCallDev)
			{
				this.bitrixCallDev._replaceVideoSharing = false;
				try
				{
					this.bitrixCallDev.hangup();
				}
				catch (e)
				{
					CallUtil.error(e);
				}

				this.bitrixCallDev = null;
			}
		}

		cancel()
		{
			this.ready = true;
			this.hangup();
		}

		attachToConference()
		{
			return new Promise((resolve, reject) => {
				if (this.bitrixCallDev/* && this.bitrixCallDev.state() === "CONNECTED" */)
				{
					return resolve();
				}

				const clientOptions = {
					roomId: this.roomId,
					endpoint: this.connectionData.endpoint,
					token: this.connectionData.jwt,
				};

				BXClientWrapper.getClient(clientOptions).then((client) => {
					if (!this.ready)
					{
						return;
					}
					// client.printLogs = true;
					client.on(BXClient.Events.LogMessage, this.__onSDKLogMessageHandler);
					try
					{
						if (typeof (JNBXCameraManager.setResolutionConstraints) === 'function')
						{
							// JNBXCameraManager.setResolutionConstraints(1280, 720);
							JNBXCameraManager.setResolutionConstraints(960, 540); // force 16:9 aspect ratio
						}

						const callOptions = {
							callId: `${this.id}`,
							sendVideo: this.videoEnabled,
							receiveVideo: true,
							enableSimulcast: true,
							userName: this.userData,
							callBetaIosEnabled: callEngine.isCallBetaIosEnabled(),
						};

						this.bitrixCallDev = client.callConference(
							`bx_conf_${this.id}`,
							callOptions,
						);
					}
					catch (e)
					{
						CallUtil.error(e);

						return reject(e);
					}

					if (!this.bitrixCallDev)
					{
						this.log('Error: could not create bitrix dev call');

						return reject({ code: 'BITRIX_DEV_NO_CALL' });
					}

					this.eventEmitter.emit(BitrixCallDevEvent.onCallConference, [{ call: this }]);

					this.bindCallEvents();

					const onCallConnected = () => {
						this.log('Call connected');
						this.bitrixCallDev.off(JNBXCall.Events.Connected, onCallConnected);
						this.bitrixCallDev.off(JNBXCall.Events.Failed, onCallFailed);

						this.bitrixCallDev.on(JNBXCall.Events.Failed, this.__onCallDisconnectedHandler);

						this.bitrixCallDev.sendAudio = !this.muted;
						this.signaling.sendMicrophoneState(!this.muted);
						this.signaling.sendCameraState(this.videoEnabled);

						if (this.videoAllowedFrom == BX.Call.UserMnemonic.none)
						{
							this.signaling.sendHideAll();
						}
						else if (BX.type.isArray(this.videoAllowedFrom))
						{
							this.signaling.sendShowUsers(this.videoAllowedFrom);
						}

						resolve();
					};

					let onCallFailed = (call, error) => {
						this.log('Could not attach to conference', error);
						CallUtil.error('Could not attach to conference', error);
						if (this.bitrixCallDev)
						{
							this.bitrixCallDev.off(JNBXCall.Events.Connected, onCallConnected);
							this.bitrixCallDev.off(JNBXCall.Events.Failed, onCallFailed);
						}
						reject(error);
					};

					this.bitrixCallDev.on(JNBXCall.Events.Connected, onCallConnected);
					this.bitrixCallDev.on(JNBXCall.Events.Failed, onCallFailed);
					this.bitrixCallDev.start();
				}).catch(this.onFatalError.bind(this));
			});
		}

		bindCallEvents()
		{
			this.bitrixCallDev.on(JNBXCall.Events.Disconnected, this.__onCallDisconnectedHandler);
			this.bitrixCallDev.on(JNBXCall.Events.ReceiveMessage, this.__onCallMessageReceivedHandler);
			this.bitrixCallDev.on(JNBXCall.Events.EndpointAdded, this.__onCallEndpointAddedHandler);
			this.bitrixCallDev.on(JNBXCall.Events.LocalVideoStreamAdded, this.__onLocalVideoStreamReceivedHandler);
			this.bitrixCallDev.on(JNBXCall.Events.LocalVideoStreamRemoved, this.__onLocalVideoStreamRemovedHandler);
			this.bitrixCallDev.on(JNBXCall.Events.Reconnected, this.__onCallReconnectedHandler);
		}

		removeCallEvents()
		{
			if (this.bitrixCallDev)
			{
				this.bitrixCallDev.off(JNBXCall.Events.Disconnected, this.__onCallDisconnectedHandler);
				this.bitrixCallDev.off(JNBXCall.Events.ReceiveMessage, this.__onCallMessageReceivedHandler);
				this.bitrixCallDev.off(JNBXCall.Events.EndpointAdded, this.__onCallEndpointAddedHandler);
				this.bitrixCallDev.off(JNBXCall.Events.LocalVideoStreamAdded, this.__onLocalVideoStreamReceivedHandler);
				this.bitrixCallDev.off(JNBXCall.Events.LocalVideoStreamRemoved, this.__onLocalVideoStreamRemovedHandler);
				this.bitrixCallDev.off(JNBXCall.Events.Reconnected, this.__onCallReconnectedHandler);
			}
		}

		/**
		 * Adds new users to call
		 * @param {Number[]} users
		 */
		addJoinedUsers(users)
		{
			for (const user of users)
			{
				const userId = Number(user);
				if (userId == this.userId || this.peers[userId])
				{
					continue;
				}
				this.peers[userId] = this.createPeer(userId);
				if (!this.users.includes(userId))
				{
					this.users.push(userId);
				}
				this.eventEmitter.emit(BX.Call.Event.onUserInvited, [{ userId }]);
			}
		}

		/**
		 * Adds users, invited by you or someone else
		 * @param {Number[]} users
		 */
		addInvitedUsers(users)
		{
			for (const user of users)
			{
				const userId = parseInt(user);
				if (userId == this.userId)
				{
					continue;
				}

				if (this.peers[userId])
				{
					if (this.peers[userId].calculatedState === BX.Call.UserState.Failed || this.peers[userId].calculatedState === BX.Call.UserState.Idle)
					{
						this.peers[userId].onInvited();
					}
				}
				else
				{
					this.peers[userId] = this.createPeer(userId);
					this.peers[userId].onInvited();
				}

				if (!this.users.includes(userId))
				{
					this.users.push(userId);
				}
				this.eventEmitter.emit(BX.Call.Event.onUserInvited, [{ userId }]);
			}
		}

		isAnyoneParticipating(withCalling)
		{
			for (const userId in this.peers)
			{
				if (this.peers[userId].isParticipating(withCalling))
				{
					return true;
				}
			}

			return false;
		}

		getParticipatingUsers()
		{
			const result = [];
			for (const userId in this.peers)
			{
				if (this.peers[userId].isParticipating())
				{
					result.push(userId);
				}
			}

			return result;
		}

		__onPeerStateChanged(e)
		{
			this.eventEmitter.emit(BX.Call.Event.onUserStateChanged, [
				e.userId,
				e.state,
				e.previousState,
			]);

			if (!this.ready)
			{
				return;
			}

			if ((e.state == BX.Call.UserState.Failed || e.state == BX.Call.UserState.Unavailable || e.state == BX.Call.UserState.Declined || e.state == BX.Call.UserState.Idle) && this.type == BX.Call.Type.Instant && !this.isAnyoneParticipating(!this.someoneWasConnected))
			{
				this.hangup();
			}

			if (e.state == BX.Call.UserState.Connected && !this.someoneWasConnected)
			{
				this.someoneWasConnected = true;
			}
		}

		__onPeerInviteTimeout(e)
		{
			if (!this.ready)
			{
				return;
			}
			this.signaling.sendUserInviteTimeout({
				userId: this.users,
				failedUserId: e.userId,
			});
		}

		_onPullEvent(command, params, extra)
		{
			const handlers = {
				'Call::answer': this.__onPullEventAnswer.bind(this),
				'Call::hangup': this.__onPullEventHangup.bind(this),
				'Call::usersJoined': this.__onPullEventUsersJoined.bind(this),
				'Call::usersInvited': this.__onPullEventUsersInvited.bind(this),
				'Call::userInviteTimeout': this.__onPullEventUserInviteTimeout.bind(this),
				'Call::ping': this.__onPullEventPing.bind(this),
				'Call::finish': this.__onPullEventFinish.bind(this),
				[pullEvents.repeatAnswer]: this.__onPullEventRepeatAnswer.bind(this),
				[pullEvents.switchTrackRecordStatus]: this.__onPullEventSwitchTrackRecordStatus.bind(this),
			};

			if (handlers[command])
			{
				this.log(`Signaling: ${command}; Parameters: ${JSON.stringify(params)}`);
				handlers[command].call(this, params, extra);
			}
		}

		__onPullEventAnswer(params, extra)
		{
			const senderId = Number(params.senderId);

			if (senderId == this.userId)
			{
				return this.__onPullEventAnswerSelf(params, extra);
			}

			if (!this.peers[senderId])
			{
				this.peers[senderId] = this.createPeer(senderId);
				this.eventEmitter.emit(BX.Call.Event.onUserInvited, [{ userId: senderId }]);
			}

			if (!this.users.includes(senderId))
			{
				this.users.push(senderId);
			}

			this.peers[senderId].setReady(true);
		}

		__onPullEventAnswerSelf(params, extra)
		{
			if (params.callInstanceId === this.instanceId || extra.server_time_ago > 30)
			{
				return;
			}

			// call was answered elsewhere
			this.joinStatus = BX.Call.JoinStatus.Remote;
		}

		__onPullEventHangup(params)
		{
			const senderId = params.senderId;

			if (this.userId == senderId && this.instanceId != params.callInstanceId)
			{
				// Call declined by the same user elsewhere
				this.joinStatus = BX.Call.JoinStatus.None;
				this.eventEmitter.emit(BX.Call.Event.onHangup);

				return;
			}

			if (!this.peers[senderId])
			{
				return;
			}

			this.peers[senderId].setReady(false);

			if (params.code == 603)
			{
				this.peers[senderId].setDeclined(true);
			}
			else if (params.code == 486)
			{
				this.peers[senderId].setBusy(true);
				CallUtil.error(`user ${senderId} is busy`);
			}

			if (this.ready && this.type == BX.Call.Type.Instant && !this.isAnyoneParticipating())
			{
				this.hangup();
			}
		}

		__onPullEventUsersJoined(params)
		{
			this.log('__onPullEventUsersJoined', params);
			const users = params.users;

			this.addJoinedUsers(users);
		}

		__onPullEventUsersInvited(params)
		{
			this.log('__onPullEventUsersInvited', params);
			const users = params.users;

			this.addInvitedUsers(users);
		}

		__onPullEventUserInviteTimeout(params)
		{
			this.log('__onPullEventUserInviteTimeout', params);
			const failedUserId = params.failedUserId;

			if (this.peers[failedUserId])
			{
				this.peers[failedUserId].onInviteTimeout(false);
			}
			else if (failedUserId == this.userId)
			{
				this.eventEmitter.emit(BX.Call.Event.onPullEventUserInviteTimeout);
			}
		}

		__onPullEventPing(params, extra)
		{
			if (params.callInstanceId == this.instanceId)
			{
				// ignore self ping
				return;
			}

			clearTimeout(this.lastPingReceivedTimeout);
			this.lastPingReceivedTimeout = setTimeout(this.__onNoPingsReceived.bind(this), pingPeriod * 2.1);

			this.active = true;
			const senderId = parseInt(params.senderId, 10);

			if (senderId == this.userId && this.joinStatus == BX.Call.JoinStatus.None)
			{
				this.joinStatus = BX.Call.JoinStatus.Remote;

				clearTimeout(this.lastSelfPingReceivedTimeout);
				this.lastSelfPingReceivedTimeout = setTimeout(this.__onNoSelfPingsReceived.bind(this), pingPeriod * 2.1);
			}

			if (this.peers[senderId])
			{
				this.peers[senderId].setReady(true);
			}
		}

		__onNoPingsReceived()
		{
			if (!this.ready)
			{
				this.active = false;
				if (this._joinStatus == BX.Call.JoinStatus.Remote)
				{
					this._joinStatus = BX.Call.JoinStatus.None; // to prevent event firing
				}
			}
		}

		__onNoSelfPingsReceived()
		{
			if (!this.ready && !this.active)
			{
				this.joinStatus = BX.Call.JoinStatus.None;
			}
		}

		__onPullEventFinish(params)
		{
			this.destroy();
		}

		__onPullEventRepeatAnswer()
		{
			if (this.ready)
			{
				this.signaling.sendAnswer({ userId: this.userId }, true);
			}
		}

		__onPullEventSwitchTrackRecordStatus(e)
		{
			this.eventEmitter.emit(BX.Call.Event.onSwitchTrackRecordStatus, [{
				senderId: e.senderId,
				isTrackRecordOn: e.isTrackRecordOn,
			}]);
		}

		__onLocalDevicesUpdated(e)
		{
			this.log('__onLocalDevicesUpdated', e);
		}

		__onLocalVideoStreamReceived(stream)
		{
			this.log('__onLocalVideoStreamReceived');
			this.eventEmitter.emit(BX.Call.Event.onLocalMediaReceived, [stream]);
		}

		__onLocalVideoStreamRemoved()
		{
			this.eventEmitter.emit(BX.Call.Event.onLocalMediaStopped);
		}

		__onSDKLogMessage(message)
		{
			if (this.logger)
			{
				this.log(message);
			}
		}

		__onCallDisconnected(e)
		{
			let logData = {};

			const evt = e && typeof e === 'object' ? e : {};
			const { headers, leaveInformation } = evt;

			if (headers)
			{
				logData = {
					...logData,
					headers,
				};
			}

			if (leaveInformation)
			{
				logData = {
					...logData,
					leaveInformation,
				};
			}

			this.log('__onCallDisconnected', (Object.keys(logData).length ? logData : null));

			if (this.ready && leaveInformation)
			{
				this.hangup(leaveInformation.code, leaveInformation.reason);
			}

			this.ready = false;
			this.muted = false;
			this.reinitPeers();

			this.removeCallEvents();
			this.bitrixCallDev = null;

			this.joinStatus = BX.Call.JoinStatus.None;
		}

		__onCallReconnected()
		{
			this.eventEmitter.emit(BX.Call.Event.onReconnected);
		}

		onFatalError(error)
		{
			if (error && error.call)
			{
				delete error.call;
			}
			CallUtil.error('onFatalError', error);
			this.log('onFatalError', error);

			this.ready = false;
			this.muted = false;
			this.reinitPeers();

			if (this.bitrixCallDev)
			{
				this.removeCallEvents();
				try
				{
					this.bitrixCallDev.hangup({
						'X-Reason': 'Fatal error',
						'X-Error': typeof (error) === 'string' ? error : error.code || error.name,
					});
				}
				catch
				{ /* nothing :) */
				}
				this.bitrixCallDev = null;
			}

			if (typeof (error) === 'string')
			{
				this.eventEmitter.emit(BX.Call.Event.onCallFailure, [error]);
			}
			else if (error instanceof Error)
			{
				this.eventEmitter.emit(BX.Call.Event.onCallFailure, [error.toString()]);
			}
			else
			{
				this.eventEmitter.emit(BX.Call.Event.onCallFailure);
			}
		}

		__onCallEndpointAdded(endpoint)
		{
			const userName = typeof (endpoint.userDisplayName) === 'string' ? endpoint.userDisplayName : '';
			this.log(`__onCallEndpointAdded (${userName})`);

			if (BX.type.isNotEmptyString(userName) && userName.slice(0, 4) == 'user')
			{
				// user connected to conference
				const userId = parseInt(userName.slice(4));
				if (this.peers[userId])
				{
					this.peers[userId].setEndpoint(endpoint);
				}
			}
			else
			{
				endpoint.on(JNBXEndpoint.Events.InfoUpdated, () => {
					const userName = typeof (endpoint.userDisplayName) === 'string' ? endpoint.userDisplayName : '';
					this.log(`BitrixDev.EndpointEvents.InfoUpdated (${userName})`, endpoint);

					if (userName.slice(0, 4) == 'user')
					{
						// user connected to conference
						const userId = parseInt(userName.slice(4));
						if (this.peers[userId])
						{
							this.peers[userId].setEndpoint(endpoint);
						}
					}
				});

				this.log(`Unknown endpoint ${userName}`);
			}
		}

		__onCallMessageReceived(call, callMessage)
		{
			let message;
			try
			{
				message = JSON.parse(callMessage.message);
			}
			catch (err)
			{
				this.log('Could not parse scenario message.', err);

				return;
			}

			const eventName = message.eventName;
			switch (eventName)
			{
				case clientEvents.voiceStarted:
				{
					this.eventEmitter.emit(BX.Call.Event.onUserVoiceStarted, [message.senderId]);

					break;
				}

				case clientEvents.voiceStopped:
				{
					this.eventEmitter.emit(BX.Call.Event.onUserVoiceStopped, [message.senderId]);

					break;
				}

				case clientEvents.microphoneState:
				{
					this.eventEmitter.emit(BX.Call.Event.onUserMicrophoneState, [
						message.senderId,
						message.microphoneState === 'Y',
					]);

					break;
				}

				case clientEvents.screenState:
				{
					this.eventEmitter.emit(BX.Call.Event.onUserScreenState, [
						message.senderId,
						message.screenState === 'Y',
					]);

					break;
				}

				case clientEvents.videoPaused:
				{
					this.eventEmitter.emit(BX.Call.Event.onUserVideoPaused, [
						message.senderId,
						message.videoPaused === 'Y',
					]);

					break;
				}

				case clientEvents.floorRequest:
				{
					this.eventEmitter.emit(BX.Call.Event.onUserFloorRequest, [
						message.senderId,
						message.requestActive === 'Y',
					]);

					break;
				}

				case clientEvents.emotion:
				{
					this.eventEmitter.emit(BX.Call.Event.onUserEmotion, [
						message.senderId,
						message.toUserId,
						message.emotion,
					]);

					break;
				}

				case 'scenarioLogUrl':
				{
					CallUtil.warn(`scenario log url: ${message.logUrl}`);

					break;
				}

				default:
				{
					this.log(`Unknown scenario event ${eventName}`);
				}
			}
		}

		destroy()
		{
			this.ready = false;
			this._joinStatus = BX.Call.JoinStatus.None;
			if (this.bitrixCallDev)
			{
				this.removeCallEvents();
				if (this.bitrixCallDev /* .state() != "ENDED" */)
				{
					this.bitrixCallDev.hangup();
				}
				this.bitrixCallDev = null;
			}

			for (const userId in this.peers)
			{
				if (this.peers.hasOwnProperty(userId) && this.peers[userId])
				{
					this.peers[userId].destroy();
				}
			}

			if (this.logger)
			{
				this.logger.destroy();
				this.logger = null;
			}

			this.removeClientEvents();

			clearTimeout(this.lastPingReceivedTimeout);
			clearTimeout(this.lastSelfPingReceivedTimeout);
			clearTimeout(this.pingUsersInterval);
			clearTimeout(this.pingBackendInterval);

			this.eventEmitter.emit(BX.Call.Event.onDestroy, [{
				call: this,
			}]);
			this.eventEmitter = null;
			if (this.signaling)
			{
				this.signaling.call = null;
				this.signaling = null;
			}
		}
	}

	const BXClientWrapper = {
		accessToken: null,
		accessTokenTtl: null,

		get token()
		{
			if (this.accessToken && this.accessTokenTtl && Date.now() < this.accessTokenTtl)
			{
				return this.accessToken;
			}

			return null;
		},

		setAccessToken(accessToken, accessExpire)
		{
			this.accessToken = accessToken;
			this.accessTokenTtl = Date.now() + (accessExpire * 1000);
		},

		get server()
		{
			return BX.componentParameters.get('voximplantServer', '');
		},

		set server(value)
		{
			BX.componentParameters.set('voximplantServer', value);
		},

		get login()
		{
			return BX.componentParameters.get('voximplantLogin', '');
		},

		set login(value)
		{
			BX.componentParameters.set('voximplantLogin', value);
		},

		getAuthorization()
		{
			return new Promise((resolve, reject) => {
				if (this.server)
				{
					return resolve({ server: this.server, login: this.login });
				}

				CallUtil.log('Calling voximplant.authorization.get');
				BX.rest.callMethod('voximplant.authorization.get').then((response) => {
					const data = response.data();
					this.server = data.SERVER;
					this.login = data.LOGIN;

					return resolve({ server: this.server, login: this.login });
				}).catch((response) => {
					console.error(response);
					reject(response);
				});
			});
		},

		tryLoginWithToken(client)
		{
			return new Promise((resolve, reject) => {
				if (!('loginWithAccessToken' in client))
				{
					return reject();
				}

				if (this.token)
				{
					CallUtil.log('Trying to login with saved access token');
					this.getAuthorization().then((result) => {
						const login = result.login;
						const server = result.server;

						client.loginWithAccessToken(`${login}@${server}`, this.token).then((result) => {
							CallUtil.log('success', result);
							console.log(result);
							if ('params' in result && 'accessToken' in result.params)
							{
								this.setAccessToken(result.params.accessToken, result.params.accessExpire);
							}

							resolve();
						}).catch((e) => {
							console.error(e);
							reject(e);
						});
					}).catch((e) => {
						console.error(e);
						reject(e);
					});
				}
				else
				{
					return reject();
				}
			});
		},

		tryLoginWithOneTimeKey(client, options)
		{
			return new Promise((resolve, reject) => {
				client.requestOneTimeKey(options.roomId, options.endpoint, options.token).then((oneTimeKey) => {
					CallUtil.warn('ontimekey received');
					CallUtil.warn('ontimekey signed');

					resolve(client);
				})
					.catch((error) => {
						reject(error);
					});
			});
		},

		// return connected and authenticated client
		getClient(options)
		{
			return new Promise((resolve, reject) => {
				const client = BXClient.getInstance();

				if (client.getClientState() === 'LOGGED_IN')
				{
					return resolve(client);
				}

				const onConnected = (client) => {
					CallUtil.warn('connected');

					client.off(BXClient.Events.Failed, onFailed);
					client.off(BXClient.Events.Connected, onConnected);

					this.tryLoginWithToken(client)
						.then(() => resolve(client))
						.catch(() => {
							this.tryLoginWithOneTimeKey(client)
								.then(() => resolve(client))
								.catch((e) => reject(e));
						});
				};

				let onFailed = (client, error) => {
					CallUtil.error(client, error);
					client.off(BXClient.Events.Failed, onFailed);
					client.off(BXClient.Events.Connected, onConnected);
					reject(error);
				};

				this.tryLoginWithOneTimeKey(client, options)
					.then(() => resolve(client))
					.catch((e) => reject(e));
			});
		},
	};

	class Signaling
	{
		constructor(params)
		{
			this.call = params.call;
		}

		isPublishingEnabled()
		{
			return false;
		}

		inviteUsers(data)
		{
			return this.__runRestAction(ajaxActions.invite, data);
		}

		sendAnswer(data, repeated)
		{
			if (repeated)
			{
				this.__sendPullEventOrCallRest(pullEvents.answer, ajaxActions.answer, data, 30);
			}
			else
			{
				return this.__runRestAction(ajaxActions.answer, data);
			}
		}

		sendCancel(data)
		{
			return this.__runRestAction(ajaxActions.cancel, data);
		}

		sendHangup(data)
		{
			if (this.isPublishingEnabled())
			{
				this.__sendPullEvent(pullEvents.hangup, data);
				data.retransmit = false;
				this.__runRestAction(ajaxActions.hangup, data);
			}
			else
			{
				data.retransmit = true;
				this.__runRestAction(ajaxActions.hangup, data);
			}
		}

		sendVoiceStarted(data)
		{
			return this.__sendMessage(clientEvents.voiceStarted, data);
		}

		sendVoiceStopped(data)
		{
			return this.__sendMessage(clientEvents.voiceStopped, data);
		}

		sendMicrophoneState(microphoneState)
		{
			return this.__sendMessage(clientEvents.microphoneState, {
				microphoneState: microphoneState ? 'Y' : 'N',
			});
		}

		sendCameraState(cameraState)
		{
			return this.__sendMessage(clientEvents.cameraState, {
				cameraState: cameraState ? 'Y' : 'N',
			});
		}

		sendVideoPaused(videoPaused)
		{
			return this.__sendMessage(clientEvents.videoPaused, {
				videoPaused: videoPaused ? 'Y' : 'N',
			});
		}

		sendScreenState(screenState)
		{
			return this.__sendMessage(clientEvents.screenState, {
				screenState: screenState ? 'Y' : 'N',
			});
		}

		sendFloorRequest(requestActive)
		{
			return this.__sendMessage(clientEvents.floorRequest, {
				requestActive: requestActive ? 'Y' : 'N',
			});
		}

		sendEmotion(toUserId, emotion)
		{
			return this.__sendMessage(clientEvents.emotion, {
				toUserId,
				emotion,
			});
		}

		sendShowUsers(users)
		{
			this.call.log(`Show video from users ${BX.type.isArray(users) ? users.join('; ') : users}`);

			return this.__sendMessage(clientEvents.showUsers, {
				users,
			});
		}

		sendShowAll()
		{
			return this.__sendMessage(clientEvents.showAll, {});
		}

		sendHideAll()
		{
			return this.__sendMessage(clientEvents.hideAll, {});
		}

		sendPingToUsers(data)
		{
			if (this.isPublishingEnabled())
			{
				this.__sendPullEvent(pullEvents.ping, data, 0);
			}
		}

		sendPingToBackend()
		{
			this.__runRestAction(ajaxActions.ping, { retransmit: false });
		}

		sendRepeatAnswer(data)
		{
			this.__sendPullEvent(pullEvents.repeatAnswer, data);
		}

		sendUserInviteTimeout(data)
		{
			if (this.isPublishingEnabled())
			{
				this.__sendPullEvent(pullEvents.userInviteTimeout, data, 0);
			}
		}

		getPublishingState()
		{
			return BX.PULL.getPublishingState();
		}

		__sendPullEvent(eventName, data, expiry)
		{
			expiry = expiry || 5;
			if (!data.userId)
			{
				throw new Error('userId is not found in data');
			}

			if (!BX.type.isArray(data.userId))
			{
				data.userId = [data.userId];
			}

			if (data.userId.length === 0)
			{
				// nobody to send, exit
				return;
			}

			data.callInstanceId = this.call.instanceId;
			data.senderId = this.call.userId;
			data.callId = this.call.id;
			data.requestId = callEngine.getUuidv4();

			this.call.log(`Sending p2p signaling event ${eventName}; ${JSON.stringify(data)}`);
			BX.PULL.sendMessage(data.userId, 'im', eventName, data, expiry);
		}

		__sendMessage(eventName, data)
		{
			if (!this.call.bitrixCallDev)
			{
				return;
			}

			if (!BX.type.isPlainObject(data))
			{
				data = {};
			}
			data.eventName = eventName;
			data.requestId = callEngine.getUuidv4();

			this.call.bitrixCallDev.sendMessage(JSON.stringify(data));
		}

		__runRestAction(signalName, data)
		{
			if (!BX.type.isPlainObject(data))
			{
				data = {};
			}

			data.callId = this.call.id;
			data.callInstanceId = this.call.instanceId;
			data.requestId = callEngine.getUuidv4();

			return callEngine.getRestClient().callMethod(signalName, data);
		}

		__sendPullEventOrCallRest(eventName, restMethod, data, expiry)
		{
			this.getPublishingState().then((result) => {
				if (result)
				{
					this.__sendPullEvent(eventName, data, expiry);
				}
				else if (restMethod != '')
				{
					this.__runRestAction(restMethod, data);
				}
			}).catch((error) => {
				CallUtil.error(error);
				this.call.log(error);
			});
		}
	}

	class Peer
	{
		constructor(params)
		{
			this.userId = params.userId;
			this.call = params.call;

			this.ready = !!params.ready;
			this.calling = false;
			this.declined = false;
			this.busy = false;
			this.inviteTimeout = false;
			this.endpoint = null;

			this.isIncomingVideoAllowed = params.isIncomingVideoAllowed !== false;

			this.callingTimeout = 0;
			this.connectionRestoreTimeout = 0;

			this.callbacks = {
				onStateChanged: BX.type.isFunction(params.onStateChanged) ? params.onStateChanged : BX.DoNothing,
				onInviteTimeout: BX.type.isFunction(params.onInviteTimeout) ? params.onInviteTimeout : BX.DoNothing,
				onStreamReceived: BX.type.isFunction(params.onStreamReceived) ? params.onStreamReceived : BX.DoNothing,
				onUserVoiceStarted: BX.type.isFunction(params.onUserVoiceStarted) ? params.onUserVoiceStarted : BX.DoNothing,
				onUserVoiceStopped: BX.type.isFunction(params.onUserVoiceStopped) ? params.onUserVoiceStopped : BX.DoNothing,
				onInitialState: BX.type.isFunction(params.onInitialState) ? params.onInitialState : BX.DoNothing,
				onHandRaised: BX.type.isFunction(params.onHandRaised) ? params.onHandRaised : BX.DoNothing,
			};

			// event handlers
			this.__onEndpointRemoteMediaAddedHandler = this.__onEndpointRemoteMediaAdded.bind(this);
			this.__onEndpointRemoteMediaRemovedHandler = this.__onEndpointRemoteMediaRemoved.bind(this);
			this.__onEndpointVoiceStartedHandler = this.__onEndpointVoiceStarted.bind(this);
			this.__onEndpointVoiceStoppedHandler = this.__onEndpointVoiceStopped.bind(this);
			this.__onEndpointRemovedHandler = this.__onEndpointRemoved.bind(this);
			this.__onEndpointHandRaisedHandler = this.__onEndpointHandRaised.bind(this);

			this.calculatedState = this.calculateState();
		}

		setReady(ready)
		{
			ready = !!ready;
			if (this.ready == ready)
			{
				return;
			}
			this.ready = ready;
			if (this.calling)
			{
				clearTimeout(this.callingTimeout);
				this.calling = false;
				this.inviteTimeout = false;
			}

			if (this.ready)
			{
				this.declined = false;
				this.busy = false;
			}
			else
			{
				clearTimeout(this.connectionRestoreTimeout);
			}

			this.updateCalculatedState();
		}

		setDeclined(declined)
		{
			this.declined = declined;
			if (this.calling)
			{
				clearTimeout(this.callingTimeout);
				this.calling = false;
			}

			if (this.declined)
			{
				this.ready = false;
				this.busy = false;
			}
			clearTimeout(this.connectionRestoreTimeout);
			this.updateCalculatedState();
		}

		setBusy(busy)
		{
			this.busy = busy;
			if (this.calling)
			{
				clearTimeout(this.callingTimeout);
				this.calling = false;
			}

			if (this.busy)
			{
				this.ready = false;
				this.declined = false;
			}
			clearTimeout(this.connectionRestoreTimeout);
			this.updateCalculatedState();
		}

		setEndpoint(endpoint)
		{
			this.log(`Adding endpoint with ${endpoint.remoteVideoStreams.length} remote video streams`);

			this.setReady(true);
			this.inviteTimeout = false;
			this.declined = false;
			clearTimeout(this.connectionRestoreTimeout);
			clearTimeout(this.callingTimeout);

			if (this.endpoint)
			{
				this.removeEndpointEventHandlers();
				this.endpoint = null;
			}

			this.endpoint = endpoint;

			if (this.endpoint.remoteVideoStreams.length > 0)
			{
				this.callbacks.onStreamReceived({
					userId: this.userId,
					stream: this.getPriorityStream(),
				});
			}

			this.updateCalculatedState();
			this.bindEndpointEventHandlers();
			if (endpoint.initialState)
			{
				this.callbacks.onInitialState({
					userId: this.userId,
					microphoneState: endpoint.initialState.microphoneState,
					floorRequest: endpoint.initialState.floorRequest,
				});
			}
		}

		allowIncomingVideo(isIncomingVideoAllowed)
		{
			if (this.isIncomingVideoAllowed == isIncomingVideoAllowed)
			{
				return;
			}

			this.isIncomingVideoAllowed = !!isIncomingVideoAllowed;
		}

		bindEndpointEventHandlers()
		{
			this.endpoint.on(JNBXEndpoint.Events.VideoStreamAdded, this.__onEndpointRemoteMediaAddedHandler);
			this.endpoint.on(JNBXEndpoint.Events.VideoStreamRemoved, this.__onEndpointRemoteMediaRemovedHandler);
			this.endpoint.on(JNBXEndpoint.Events.VoiceStarted, this.__onEndpointVoiceStartedHandler);
			this.endpoint.on(JNBXEndpoint.Events.VoiceStopped, this.__onEndpointVoiceStoppedHandler);
			this.endpoint.on(JNBXEndpoint.Events.Removed, this.__onEndpointRemovedHandler);
			this.endpoint.on(JNBXEndpoint.Events.HandRaised, this.__onEndpointHandRaisedHandler);
		}

		removeEndpointEventHandlers()
		{
			this.endpoint.off(JNBXEndpoint.Events.VideoStreamAdded, this.__onEndpointRemoteMediaAddedHandler);
			this.endpoint.off(JNBXEndpoint.Events.VideoStreamRemoved, this.__onEndpointRemoteMediaRemovedHandler);
			this.endpoint.off(JNBXEndpoint.Events.VoiceStarted, this.__onEndpointVoiceStartedHandler);
			this.endpoint.off(JNBXEndpoint.Events.VoiceStopped, this.__onEndpointVoiceStoppedHandler);
			this.endpoint.off(JNBXEndpoint.Events.Removed, this.__onEndpointRemovedHandler);
			this.endpoint.off(JNBXEndpoint.Events.HandRaised, this.__onEndpointHandRaisedHandler);
		}

		calculateState()
		{
			if (this.endpoint)
			{
				return BX.Call.UserState.Connected;
			}

			if (this.calling)
			{
				return BX.Call.UserState.Calling;
			}

			if (this.inviteTimeout)
			{
				return BX.Call.UserState.Unavailable;
			}

			if (this.declined)
			{
				return BX.Call.UserState.Declined;
			}

			if (this.busy)
			{
				return BX.Call.UserState.Busy;
			}

			if (this.ready)
			{
				return BX.Call.UserState.Ready;
			}

			return BX.Call.UserState.Idle;
		}

		updateCalculatedState()
		{
			const calculatedState = this.calculateState();

			if (this.calculatedState != calculatedState)
			{
				this.callbacks.onStateChanged({
					userId: this.userId,
					state: calculatedState,
					previousState: this.calculatedState,
				});
				this.calculatedState = calculatedState;
			}
		}

		isParticipating(withCalling)
		{
			if (!(typeof (withCalling) === 'boolean'))
			{
				withCalling = true;
			}

			if (withCalling)
			{
				return ((this.calling || this.ready || this.endpoint) && !this.declined);
			}

			return ((this.ready || this.endpoint) && !this.declined);
		}

		waitForConnectionRestore()
		{
			clearTimeout(this.connectionRestoreTimeout);
			this.connectionRestoreTimeout = setTimeout(
				this.onConnectionRestoreTimeout.bind(this),
				connectionRestoreTime,
			);
		}

		onInvited()
		{
			this.ready = false;
			this.inviteTimeout = false;
			this.declined = false;
			this.calling = true;

			clearTimeout(this.connectionRestoreTimeout);
			if (this.callingTimeout)
			{
				clearTimeout(this.callingTimeout);
			}
			this.callingTimeout = setTimeout(() => this.onInviteTimeout(true), 30000);
			this.updateCalculatedState();
		}

		onInviteTimeout(internal)
		{
			clearTimeout(this.callingTimeout);
			if (!this.calling)
			{
				return;
			}
			this.calling = false;
			this.inviteTimeout = true;
			if (internal)
			{
				this.callbacks.onInviteTimeout({
					userId: this.userId,
				});
			}
			this.updateCalculatedState();
		}

		onConnectionRestoreTimeout()
		{
			if (this.endpoint || !this.ready)
			{
				return;
			}

			this.log('Done waiting for connection restoration');
			this.setReady(false);
		}

		__onEndpointRemoteMediaAdded(e)
		{
			this.log('RemoteMediaAdded', e);
			this.callbacks.onStreamReceived({
				userId: this.userId,
				stream: this.getPriorityStream(),
			});

			this.updateCalculatedState();
		}

		__onEndpointRemoteMediaRemoved(e)
		{
			console.log(e)
			this.log('Remote media removed');
			this.callbacks.onStreamReceived({
				userId: this.userId,
				stream: this.getPriorityStream()
			});

			this.updateCalculatedState();
		}

		__onEndpointVoiceStarted(e)
		{
			this.log('Voice started');
			this.callbacks.onUserVoiceStarted({
				userId: this.userId,
			});
		}

		__onEndpointVoiceStopped(e)
		{
			this.log('Voice stopped');
			this.callbacks.onUserVoiceStopped({
				userId: this.userId,
			});
		}

		__onEndpointRemoved(e)
		{
			this.log('Endpoint removed');

			if (this.endpoint)
			{
				this.removeEndpointEventHandlers();
				this.endpoint = null;
			}

			if (this.ready)
			{
				this.waitForConnectionRestore();
			}

			this.updateCalculatedState();
		}

		__onEndpointHandRaised(e)
		{
			this.log('Endpoint hand raised');

			this.callbacks.onHandRaised({
				userId: this.userId,
				isHandRaised: e.isHandRaised,
			});
		}

		getPriorityStream()
		{
			let streams = this.endpoint.remoteVideoStreams;
			if (streams.length == 0)
			{
				return null;
			}
			let sharingStream = streams.findLast((stream) => stream.kind === 'sharing');
			if (sharingStream === undefined)
			{
				return streams[streams.length - 1];
			}
			else
			{
				return sharingStream;
			}
		}

		log()
		{
			this.call && this.call.log.apply(this.call, arguments);
		}

		destroy()
		{
			if (this.endpoint)
			{
				this.removeEndpointEventHandlers();
				this.endpoint = null;
			}

			this.callbacks.onStateChanged = BX.DoNothing;
			this.callbacks.onStreamReceived = BX.DoNothing;
			this.callbacks.onUserVoiceStarted = BX.DoNothing;
			this.callbacks.onUserVoiceStopped = BX.DoNothing;
			this.callbacks.onInitialState = BX.DoNothing;
			this.callbacks.onHandRaised = BX.DoNothing;

			clearTimeout(this.callingTimeout);
			clearTimeout(this.connectionRestoreTimeout);
			this.callingTimeout = null;
			this.connectionRestoreTimeout = null;
			this.call = null;
		}
	}

	window.BXClientWrapper = BXClientWrapper;
	window.BitrixCallDev = BitrixCallDev;
})
();


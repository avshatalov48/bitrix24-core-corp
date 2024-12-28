"use strict";
(function ()
{
	include("Calls");

	BX.DoNothing = function ()
	{
	};

	var ajaxActions = {
		invite: "im.call.invite",
		cancel: "im.call.cancel",
		answer: "im.call.answer",
		decline: "im.call.decline",
		hangup: "im.call.hangup",
		ping: "im.call.ping",
	};

	var pullEvents = {
		ping: "Call::ping",
		answer: "Call::answer",
		hangup: "Call::hangup",
		userInviteTimeout: "Call::userInviteTimeout",
		repeatAnswer: "Call::repeatAnswer",
	};

	var clientEvents = {
		voiceStarted: "Call::voiceStarted",
		voiceStopped: "Call::voiceStopped",
		microphoneState: "Call::microphoneState",
		cameraState: "Call::cameraState",
		videoPaused: "Call::videoPaused",
		screenState: "Call::screenState",
		floorRequest: "Call::floorRequest",
		emotion: "Call::emotion",
		showUsers: "Call::showUsers",
		showAll: "Call::showAll",
		hideAll: "Call::hideAll",
	};

	const VoximplantCallEvent = {
		onCallConference: "VoximplantCall::onCallConference",
	};

	var pingPeriod = 5000;
	var backendPingPeriod = 25000;

	var connectionRestoreTime = 15000;

	class VoximplantCall
	{
		constructor(params)
		{
			this.id = params.id;
			this.instanceId = params.instanceId;
			this.parentId = params.parentId || null;
			this.direction = params.direction;
			this.type = params.type || BX.Call.Type.Instant; // @see {BX.Call.Type}

			this.ready = false;
			this.userId = env.userId;

			this.initiatorId = params.initiatorId || "";
			this.users = BX.type.isArray(params.users) ? params.users.filter(userId => userId != this.userId) : [];

			this.associatedEntity = BX.type.isPlainObject(params.associatedEntity) ? params.associatedEntity : {};
			this.startDate = new Date(BX.prop.getString(params, "startDate", ""));

			// media constraints
			this.videoEnabled = params.videoEnabled === true;
			this.videoHd = params.videoHd === true;
			this.cameraId = params.cameraId || "";
			this.microphoneId = params.microphoneId || "";

			this.muted = params.muted === true;

			this.logToken = params.logToken || "";
			if (callEngine.getLogService() && this.logToken)
			{
				this.logger = new CallLogger(callEngine.getLogService(), this.logToken);
			}

			this.voximplantCall = null;

			this.signaling = new Signaling({
				call: this,
			});

			this.peers = {};
			this._joinStatus = BX.Call.JoinStatus.None;
			Object.defineProperty(this, "joinStatus", {
				get: this.getJoinStatus.bind(this),
				set: this.setJoinStatus.bind(this)
			});
			this._active = false; // has remote pings
			Object.defineProperty(this, "active", {
				get: this.getActive.bind(this),
				set: this.setActive.bind(this),
			});

			this.localVideoShown = false;
			this.clientEventsBound = false;
			this._screenShared = false;

			this.someoneWasConnected = false;

			this.eventEmitter = new JNEventEmitter();
			if (typeof (params.events) === "object")
			{
				for (let eventName in params.events)
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
			let text = CallUtil.getLogMessage.apply(CallUtil, arguments);
			if (console && callEngine.debugFlag)
			{
				let a = ["Call log [" + CallUtil.getTimeForLog() + "]: "];
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
			this.users.forEach(userId =>
			{
				userId = parseInt(userId, 10);
				this.peers[userId] = this.createPeer(userId);
			});
		}

		reinitPeers()
		{
			for (var userId in this.peers)
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
				var users = this.users.concat(this.userId);
				this.signaling.sendPingToUsers({userId: users}, true);
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
			this.signaling.sendRepeatAnswer({userId: this.userId});
		}

		createPeer(userId)
		{
			var incomingVideoAllowed;
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
				incomingVideoAllowed = this.videoAllowedFrom.some(function (allowedUserId)
				{
					return allowedUserId == userId;
				});
			}
			else
			{
				incomingVideoAllowed = true;
			}

			return new Peer({
				call: this,
				userId: userId,
				ready: userId == this.initiatorId,
				isIncomingVideoAllowed: incomingVideoAllowed,

				onStreamReceived: e => this.eventEmitter.emit(BX.Call.Event.onStreamReceived, [e.userId, e.stream]),
				onStreamRemoved: e => this.eventEmitter.emit(BX.Call.Event.onStreamRemoved, [e.userId]),
				onStateChanged: this.__onPeerStateChanged.bind(this),
				onInviteTimeout: this.__onPeerInviteTimeout.bind(this),
			});
		}

		getUsers()
		{
			let result = {};
			for (let userId in this.peers)
			{
				result[userId] = this.peers[userId].calculatedState;
			}
			return result;
		}

		getJoinStatus()
		{
			return this._joinStatus
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
					this.eventEmitter.emit(BX.Call.Event.onJoin, [{callId: this.id, local: true}]);
					break;
				case BX.Call.JoinStatus.Remote:
					this.eventEmitter.emit(BX.Call.Event.onJoin, [{callId: this.id, local: false}]);
					break;
				case BX.Call.JoinStatus.None:
					this.eventEmitter.emit(BX.Call.Event.onLeave, [{callId: this.id}]);
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
			var streamManager = VoxImplant.Hardware.StreamManager.get();

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
			if (VIClient)
			{
				VIClient.getInstance().off(VIClient.Events.LogMessage, this.__onSDKLogMessageHandler);
			}
		};

		isMuted()
		{
			return this.muted;
		}

		setMuted(muted)
		{
			if (this.muted == muted)
			{
				return;
			}

			this.muted = muted;
			if (this.voximplantCall)
			{
				this.voximplantCall.sendAudio = !this.muted;
				this.signaling.sendMicrophoneState(!this.muted);
			}
		}

		switchCamera()
		{
			if (!this.videoEnabled)
			{
				return;
			}
			JNVICameraManager.useBackCamera = !JNVICameraManager.useBackCamera;
		}

		isFrontCameraUsed()
		{
			return !JNVICameraManager.useBackCamera;
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
			if (this.voximplantCall)
			{
				this.voximplantCall.setSendVideo(this.videoEnabled && !this.videoPaused);
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
			if (this.voximplantCall)
			{
				this.voximplantCall.setSendVideo(this.videoEnabled && !this.videoPaused);
				this.signaling.sendVideoPaused(this.videoPaused);
			}
		}

		requestFloor(requestActive)
		{
			this.signaling.sendFloorRequest(requestActive);
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
				throw new Error("userList is in wrong format");
			}

			let users = {};
			userList.forEach((userId) => users[userId] = true);

			for (let userId in this.peers)
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
			let users = BX.type.isArray(config.users) ? config.users : this.users;

			this.attachToConference().then(() =>
			{
				this.signaling.sendPingToUsers({userId: users});
				if (users.length > 0)
				{
					return this.signaling.inviteUsers({
						userIds: users,
						video: this.videoEnabled ? "Y" : "N",
					});
				}
			}).then((response) =>
			{
				this.joinStatus = BX.Call.JoinStatus.Local;
				for (let i = 0; i < users.length; i++)
				{
					let userId = parseInt(users[i], 10);
					if (!this.users.includes(userId))
					{
						this.users.push(userId);
					}
					if (!this.peers[userId])
					{
						this.peers[userId] = this.createPeer(userId);
						this.eventEmitter.emit(BX.Call.Event.onUserInvited, [{
							userId: userId,
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
			this.attachToConference().then(() =>
			{
				this.log("Attached to conference");
				this.joinStatus = BX.Call.JoinStatus.Local;
			}).catch(this.onFatalError.bind(this));
		}

		sendAnswer()
		{
			this.signaling.sendAnswer();
		};

		decline(code)
		{
			this.ready = false;
			var data = {
				callId: this.id,
				callInstanceId: this.instanceId,
			};
			if (code)
			{
				data.code = code;
			}

			BX.rest.callMethod(ajaxActions.decline, data);
		};

		hangup(code, reason)
		{
			if (!this.ready)
			{
				var error = new Error("Hangup in wrong state");
				this.log(error);
				return;
			}

			var tempError = new Error();
			tempError.name = "Call stack:";
			this.log("Hangup received \n" + tempError.stack);

			var data = {};
			this.ready = false;
			if (typeof (code) != "undefined")
			{
				data.code = code;
			}
			if (typeof (reason) != "undefined")
			{
				data.reason = reason;
			}
			this.joinStatus = BX.Call.JoinStatus.None;

			data.userId = this.users;
			this.signaling.sendHangup(data);
			this.muted = false;

			// for future reconnections
			this.reinitPeers();

			if (this.voximplantCall)
			{
				this.voximplantCall._replaceVideoSharing = false;
				try
				{
					this.voximplantCall.hangup();
				} catch (e)
				{
					CallUtil.error(e);
				}
			}

		};

		attachToConference()
		{
			return new Promise((resolve, reject) =>
			{
				if (this.voximplantCall/* && this.voximplantCall.state() === "CONNECTED"*/)
				{
					return resolve();
				}

				VIClientWrapper.getClient().then((client) =>
				{
					//client.printLogs = true;
					client.on(VIClient.Events.LogMessage, this.__onSDKLogMessageHandler);
					try
					{
						if (typeof (JNVICameraManager.setResolutionConstraints) === 'function')
						{
							//JNVICameraManager.setResolutionConstraints(1280, 720);
							JNVICameraManager.setResolutionConstraints(960, 540); //force 16:9 aspect ratio
						}

						this.voximplantCall = client.callConference(
							"bx_conf_" + this.id,
							{sendVideo: this.videoEnabled, receiveVideo: true, enableSimulcast: true},
						);
					} catch (e)
					{
						CallUtil.error(e);
						return reject(e);
					}

					if (!this.voximplantCall)
					{
						this.log("Error: could not create voximplant call");
						return reject({code: "VOX_NO_CALL"});
					}

					this.eventEmitter.emit(VoximplantCallEvent.onCallConference, [{call: this}]);

					this.bindCallEvents();

					let onCallConnected = () =>
					{
						this.log("Call connected");
						this.voximplantCall.off(JNVICall.Events.Connected, onCallConnected);
						this.voximplantCall.off(JNVICall.Events.Failed, onCallFailed);

						this.voximplantCall.on(JNVICall.Events.Failed, this.__onCallDisconnectedHandler);

						if (this.muted)
						{
							this.voximplantCall.sendAudio = false;
						}
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

					let onCallFailed = (call, error) =>
					{
						this.log("Could not attach to conference", error);
						CallUtil.error("Could not attach to conference", error);
						this.voximplantCall.off(JNVICall.Events.Connected, onCallConnected);
						this.voximplantCall.off(JNVICall.Events.Failed, onCallFailed);
						reject(error);
					};

					this.voximplantCall.on(JNVICall.Events.Connected, onCallConnected);
					this.voximplantCall.on(JNVICall.Events.Failed, onCallFailed);
					this.voximplantCall.start();
				}).catch(this.onFatalError.bind(this));
			});
		};

		bindCallEvents()
		{
			this.voximplantCall.on(JNVICall.Events.Disconnected, this.__onCallDisconnectedHandler);
			this.voximplantCall.on(JNVICall.Events.ReceiveMessage, this.__onCallMessageReceivedHandler);
			this.voximplantCall.on(JNVICall.Events.EndpointAdded, this.__onCallEndpointAddedHandler);
			this.voximplantCall.on(JNVICall.Events.LocalVideoStreamAdded, this.__onLocalVideoStreamReceivedHandler);
			this.voximplantCall.on(JNVICall.Events.LocalVideoStreamRemoved, this.__onLocalVideoStreamRemovedHandler);
		}

		removeCallEvents()
		{
			if (this.voximplantCall)
			{
				this.voximplantCall.off(JNVICall.Events.Disconnected, this.__onCallDisconnectedHandler);
				this.voximplantCall.off(JNVICall.Events.ReceiveMessage, this.__onCallMessageReceivedHandler);
				this.voximplantCall.off(JNVICall.Events.EndpointAdded, this.__onCallEndpointAddedHandler);
				this.voximplantCall.off(JNVICall.Events.LocalVideoStreamAdded, this.__onLocalVideoStreamReceivedHandler);
				this.voximplantCall.off(JNVICall.Events.LocalVideoStreamRemoved, this.__onLocalVideoStreamRemovedHandler);

			}
		}

		/**
		 * Adds new users to call
		 * @param {Number[]} users
		 */
		addJoinedUsers(users)
		{
			for (var i = 0; i < users.length; i++)
			{
				var userId = Number(users[i]);
				if (userId == this.userId || this.peers[userId])
				{
					continue;
				}
				this.peers[userId] = this.createPeer(userId);
				if (!this.users.includes(userId))
				{
					this.users.push(userId);
				}
				this.eventEmitter.emit(BX.Call.Event.onUserInvited, [{userId: userId}]);
			}
		};

		/**
		 * Adds users, invited by you or someone else
		 * @param {Number[]} users
		 */
		addInvitedUsers(users)
		{
			for (var i = 0; i < users.length; i++)
			{
				var userId = parseInt(users[i]);
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
				this.eventEmitter.emit(BX.Call.Event.onUserInvited, [{userId: userId}]);
			}
		}

		isAnyoneParticipating(withCalling)
		{
			for (var userId in this.peers)
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
			var result = [];
			for (var userId in this.peers)
			{
				if (this.peers[userId].isParticipating())
				{
					result.push(userId);
				}
			}
			return result;
		};

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
			if (e.state == BX.Call.UserState.Failed || e.state == BX.Call.UserState.Unavailable || e.state == BX.Call.UserState.Declined || e.state == BX.Call.UserState.Idle)
			{
				if (this.type == BX.Call.Type.Instant && !this.isAnyoneParticipating(!this.someoneWasConnected))
				{
					this.hangup();
				}
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
		};

		_onPullEvent(command, params, extra)
		{
			var handlers = {
				"Call::answer": this.__onPullEventAnswer.bind(this),
				"Call::hangup": this.__onPullEventHangup.bind(this),
				"Call::usersJoined": this.__onPullEventUsersJoined.bind(this),
				"Call::usersInvited": this.__onPullEventUsersInvited.bind(this),
				"Call::userInviteTimeout": this.__onPullEventUserInviteTimeout.bind(this),
				"Call::ping": this.__onPullEventPing.bind(this),
				"Call::finish": this.__onPullEventFinish.bind(this),
				[pullEvents.repeatAnswer]: this.__onPullEventRepeatAnswer.bind(this),
			};

			if (handlers[command])
			{
				this.log("Signaling: " + command + "; Parameters: " + JSON.stringify(params));
				handlers[command].call(this, params);
			}
		};

		__onPullEventAnswer(params)
		{
			var senderId = Number(params.senderId);

			if (senderId == this.userId)
			{
				return this.__onPullEventAnswerSelf(params);
			}

			if (!this.peers[senderId])
			{
				this.peers[senderId] = this.createPeer(senderId);
				this.eventEmitter.emit(BX.Call.Event.onUserInvited, [{userId: senderId}]);
			}

			if (!this.users.includes(senderId))
			{
				this.users.push(senderId);
			}

			this.peers[senderId].setReady(true);
		};

		__onPullEventAnswerSelf(params)
		{
			if (params.callInstanceId === this.instanceId)
			{
				return;
			}

			// call was answered elsewhere
			this.joinStatus = BX.Call.JoinStatus.Remote;
		}

		__onPullEventHangup(params)
		{
			var senderId = params.senderId;

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
				CallUtil.error("user " + senderId + " is busy");
			}

			if (this.ready && this.type == BX.Call.Type.Instant && !this.isAnyoneParticipating())
			{
				this.hangup();
			}
		};

		__onPullEventUsersJoined(params)
		{
			this.log("__onPullEventUsersJoined", params);
			var users = params.users;

			this.addJoinedUsers(users);
		}

		__onPullEventUsersInvited(params)
		{
			this.log("__onPullEventUsersInvited", params);
			var users = params.users;

			this.addInvitedUsers(users);
		}

		__onPullEventUserInviteTimeout(params)
		{
			this.log("__onPullEventUserInviteTimeout", params);
			var failedUserId = params.failedUserId;

			if (this.peers[failedUserId])
			{
				this.peers[failedUserId].onInviteTimeout(false);
			}
		}

		__onPullEventPing(params)
		{
			if (params.callInstanceId == this.instanceId)
			{
				// ignore self ping
				return;
			}

			clearTimeout(this.lastPingReceivedTimeout);
			this.lastPingReceivedTimeout = setTimeout(this.__onNoPingsReceived.bind(this), pingPeriod * 2.1);

			this.active = true;
			var senderId = parseInt(params.senderId, 10);

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
				this.signaling.sendAnswer({userId: this.userId}, true);
			}
		}

		__onLocalDevicesUpdated(e)
		{
			this.log("__onLocalDevicesUpdated", e);
		}

		__onLocalVideoStreamReceived(stream)
		{
			this.log("__onLocalVideoStreamReceived")
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
			this.log("__onCallDisconnected", e);

			this.ready = false;
			this.muted = false;
			this.reinitPeers();

			this.removeCallEvents();
			this.voximplantCall = null;

			this.joinStatus = BX.Call.JoinStatus.None;
		};

		onFatalError(error)
		{
			if (error && error.call)
			{
				delete error.call;
			}
			CallUtil.error("onFatalError", error);
			this.log("onFatalError", error);

			this.ready = false;
			this.muted = false;
			this.reinitPeers();

			if (this.voximplantCall)
			{
				this.removeCallEvents();
				try
				{
					this.voximplantCall.hangup({
						"X-Reason": "Fatal error",
						"X-Error": typeof (error) === "string" ? error : error.code || error.name,
					});
				} catch (e)
				{ /* nothing :) */
				}
				this.voximplantCall = null;
			}

			if (typeof (error) === "string")
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
			let userName = typeof (endpoint.userDisplayName) === "string" ? endpoint.userDisplayName : "";
			this.log("__onCallEndpointAdded (" + userName + ")");

			if (BX.type.isNotEmptyString(userName) && userName.substr(0, 4) == "user")
			{
				// user connected to conference
				let userId = parseInt(userName.substr(4));
				if (this.peers[userId])
				{
					this.peers[userId].setEndpoint(endpoint);
				}
			}
			else
			{
				endpoint.on(JNVIEndpoint.Events.InfoUpdated, () =>
				{
					let userName = typeof (endpoint.userDisplayName) === "string" ? endpoint.userDisplayName : "";
					this.log("VoxImplant.EndpointEvents.InfoUpdated (" + userName + ")", endpoint);

					if (userName.substr(0, 4) == "user")
					{
						// user connected to conference
						var userId = parseInt(userName.substr(4));
						if (this.peers[userId])
						{
							this.peers[userId].setEndpoint(endpoint);
						}
					}
				});

				this.log("Unknown endpoint " + userName);
			}
		}

		__onCallMessageReceived(call, callMessage)
		{
			let message;
			try
			{
				message = JSON.parse(callMessage.message);
			} catch (err)
			{
				this.log("Could not parse scenario message.", err);
				return;
			}

			var eventName = message.eventName;
			if (eventName === clientEvents.voiceStarted)
			{
				this.eventEmitter.emit(BX.Call.Event.onUserVoiceStarted, [message.senderId]);
			}
			else if (eventName === clientEvents.voiceStopped)
			{
				this.eventEmitter.emit(BX.Call.Event.onUserVoiceStopped, [message.senderId]);
			}
			else if (eventName === clientEvents.microphoneState)
			{
				this.eventEmitter.emit(BX.Call.Event.onUserMicrophoneState, [
					message.senderId,
					message.microphoneState === "Y",
				]);
			}
			else if (eventName === clientEvents.screenState)
			{
				this.eventEmitter.emit(BX.Call.Event.onUserScreenState, [
					message.senderId,
					message.screenState === "Y",
				]);
			}
			else if (eventName === clientEvents.videoPaused)
			{
				this.eventEmitter.emit(BX.Call.Event.onUserVideoPaused, [
					message.senderId,
					message.videoPaused === "Y",
				]);
			}
			else if (eventName === clientEvents.floorRequest)
			{
				this.eventEmitter.emit(BX.Call.Event.onUserFloorRequest, [
					message.senderId,
					message.requestActive === "Y",
				]);
			}
			else if (eventName === clientEvents.emotion)
			{
				this.eventEmitter.emit(BX.Call.Event.onUserEmotion, [
					message.senderId,
					message.toUserId,
					message.emotion,
				]);
			}
			else if (eventName === "scenarioLogUrl")
			{
				CallUtil.warn("scenario log url: " + message.logUrl);
			}
			else
			{
				this.log("Unknown scenario event " + eventName);
			}
		}

		destroy()
		{
			this.ready = false;
			this._joinStatus = BX.Call.JoinStatus.None;
			if (this.voximplantCall)
			{
				this.removeCallEvents();
				if (this.voximplantCall /*.state() != "ENDED"*/)
				{
					this.voximplantCall.hangup();
				}
				this.voximplantCall = null;
			}

			for (var userId in this.peers)
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

	const VIClientWrapper =
	{
		accessToken: null,
		accessTokenTtl: null,

		get token()
		{
			if (this.accessToken && this.accessTokenTtl && Date.now() < this.accessTokenTtl)
			{
				return this.accessToken;
			}
			else
			{
				return null;
			}
		},

		setAccessToken(accessToken, accessExpire)
		{
			this.accessToken = accessToken;
			this.accessTokenTtl = Date.now() + (accessExpire * 1000)
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
			return new Promise((resolve, reject) =>
			{
				if (this.server)
				{
					return resolve({server: this.server, login: this.login});
				}
				else
				{
					CallUtil.log("Calling voximplant.authorization.get");
					BX.rest.callMethod("voximplant.authorization.get").then((response) =>
					{
						let data = response.data();
						this.server = data.SERVER;
						this.login = data.LOGIN;

						return resolve({server: this.server, login: this.login});
					}).catch((response) =>
					{
						console.error(response);
						reject(response);
					});
				}
			})
		},

		tryLoginWithToken(client)
		{
			return new Promise((resolve, reject) =>
			{
				if (!("loginWithAccessToken" in client))
				{
					return reject();
				}

				if (this.token)
				{
					CallUtil.log("Trying to login with saved access token");
					this.getAuthorization().then((result) =>
					{
						let login = result.login;
						let server = result.server;

						client.loginWithAccessToken(`${login}@${server}`, this.token).then((result) =>
						{
							CallUtil.log("success", result);
							console.log(result);
							if ("params" in result && "accessToken" in result.params)
							{
								this.setAccessToken(result.params.accessToken, result.params.accessExpire);
							}

							resolve();
						}).catch(e =>
						{
							console.error(e);
							reject(e);
						});
					}).catch(e =>
					{
						console.error(e);
						reject(e);

					})
				}
				else
				{
					return reject();
				}
			});
		},

		tryLoginWithOneTimeKey(client)
		{
			return new Promise((resolve, reject) =>
			{
				this.getAuthorization().then((result) =>
				{
					let login = result.login;
					let server = result.server;

					client.requestOneTimeKey(`${login}@${server}`).then(oneTimeKey =>
					{
						CallUtil.warn("ontimekey received");
						BX.rest.callMethod("voximplant.authorization.signOneTimeKey", {KEY: oneTimeKey}).then((response) =>
						{
							CallUtil.warn("ontimekey signed");
							let data = response.data();

							client.loginWithOneTimeKey(`${login}@${server}`, data.HASH)
								.then((result) =>
								{
									console.log("login success", result);
									if ("params" in result && "accessToken" in result.params)
									{
										this.setAccessToken(result.params.accessToken, result.params.accessExpire);
									}

									resolve(client);
								})
								.catch(error =>
								{
									BX.rest.callMethod('voximplant.authorization.onError');

									reject(error);
								});
						}).catch((response) =>
						{
							CallUtil.error(response);
						});
					});
				})
			});
		},

		// return connected and authenticated client
		getClient()
		{
			return new Promise((resolve, reject) =>
			{
				let client = VIClient.getInstance();

				if (client.getClientState() === "LOGGED_IN")
				{
					return resolve(client);
				}

				let onConnected = (client) =>
				{
					CallUtil.warn("connected");

					client.off(VIClient.Events.Failed, onFailed);
					client.off(VIClient.Events.Connected, onConnected);

					this.tryLoginWithToken(client)
						.then(() => resolve(client))
						.catch(() =>
						{
							this.tryLoginWithOneTimeKey(client)
								.then(() => resolve(client))
								.catch(e => reject(e))
						})
				};
				let onFailed = (client, error) =>
				{
					CallUtil.error(client, error);
					client.off(VIClient.Events.Failed, onFailed);
					client.off(VIClient.Events.Connected, onConnected);
					reject(error);
				};

				if (client.getClientState() === "CONNECTED")
				{
					onConnected(client);
				}
				else
				{
					client.on(VIClient.Events.Failed, onFailed);
					client.on(VIClient.Events.Connected, onConnected);
					this.getNode().then((result) =>
					{
						try
						{
							client.connectWithNode(result);
						}
						catch (error)
						{
							if (error.name == "TypeError")
							{
								client.connect();
							}
							else
							{
								reject();
							}
						}
					});
					//client.connectWithConnectivityCheck(false, ["web-gw-yy-01-148.voximplant.com"]); // todo: remove
				}
			});
		},

		getNode()
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod('voximplant.user.getNode').then((result) => {
					const data = result.data();

					if (data.error)
					{
						const e = {
							name: 'OtherError',
							code: data.code,
							message: data.message,
						};

						reject(e);
					}
					else
					{
						resolve(data.node.toString());
					}
				}).catch((error) => {
					reject(error);
				})
			})
		}
	}

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
				microphoneState: microphoneState ? "Y" : "N",
			});
		}

		sendCameraState(cameraState)
		{
			return this.__sendMessage(clientEvents.cameraState, {
				cameraState: cameraState ? "Y" : "N",
			});
		}

		sendVideoPaused(videoPaused)
		{
			return this.__sendMessage(clientEvents.videoPaused, {
				videoPaused: videoPaused ? "Y" : "N",
			});
		}

		sendScreenState(screenState)
		{
			return this.__sendMessage(clientEvents.screenState, {
				screenState: screenState ? "Y" : "N",
			});
		}

		sendFloorRequest(requestActive)
		{
			return this.__sendMessage(clientEvents.floorRequest, {
				requestActive: requestActive ? "Y" : "N",
			});
		}

		sendEmotion(toUserId, emotion)
		{
			return this.__sendMessage(clientEvents.emotion, {
				toUserId: toUserId,
				emotion: emotion,
			});
		}

		sendShowUsers(users)
		{
			this.call.log("Show video from users " + (BX.type.isArray(users) ? users.join("; ") : users));
			return this.__sendMessage(clientEvents.showUsers, {
				users: users,
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
			this.__runRestAction(ajaxActions.ping, {retransmit: false});
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
				throw new Error("userId is not found in data");
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

			this.call.log("Sending p2p signaling event " + eventName + "; " + JSON.stringify(data));
			BX.PULL.sendMessage(data.userId, "im", eventName, data, expiry);
		}

		__sendMessage(eventName, data)
		{
			if (!this.call.voximplantCall)
			{
				return;
			}

			if (!BX.type.isPlainObject(data))
			{
				data = {};
			}
			data.eventName = eventName;
			data.requestId = callEngine.getUuidv4();

			this.call.voximplantCall.sendMessage(JSON.stringify(data));
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
			this.getPublishingState().then(result =>
			{
				if (result)
				{
					this.__sendPullEvent(eventName, data, expiry);
				}
				else if (restMethod != "")
				{
					this.__runRestAction(restMethod, data);
				}
			}).catch(error =>
			{
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

			this.stream = null;

			this.isIncomingVideoAllowed = params.isIncomingVideoAllowed !== false;

			this.tracks = {
				audio: null,
				video: null,
				sharing: null,
			};

			this.callingTimeout = 0;
			this.connectionRestoreTimeout = 0;

			this.callbacks = {
				onStateChanged: BX.type.isFunction(params.onStateChanged) ? params.onStateChanged : BX.DoNothing,
				onInviteTimeout: BX.type.isFunction(params.onInviteTimeout) ? params.onInviteTimeout : BX.DoNothing,
				onStreamReceived: BX.type.isFunction(params.onStreamReceived) ? params.onStreamReceived : BX.DoNothing,
				onStreamRemoved: BX.type.isFunction(params.onStreamRemoved) ? params.onStreamRemoved : BX.DoNothing,
			};

			// event handlers
			this.__onEndpointRemoteMediaAddedHandler = this.__onEndpointRemoteMediaAdded.bind(this);
			this.__onEndpointRemoteMediaRemovedHandler = this.__onEndpointRemoteMediaRemoved.bind(this);
			this.__onEndpointRemovedHandler = this.__onEndpointRemoved.bind(this);

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
			this.log("Adding endpoint with " + endpoint.remoteVideoStreams.length + " remote video streams");

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
					stream: this.endpoint.remoteVideoStreams[0],
				});
			}

			this.updateCalculatedState();
			this.bindEndpointEventHandlers();
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
			this.endpoint.on(JNVIEndpoint.Events.VideoStreamAdded, this.__onEndpointRemoteMediaAddedHandler);
			this.endpoint.on(JNVIEndpoint.Events.VideoStreamRemoved, this.__onEndpointRemoteMediaRemovedHandler);
			this.endpoint.on(JNVIEndpoint.Events.Removed, this.__onEndpointRemovedHandler);
		}

		removeEndpointEventHandlers()
		{
			this.endpoint.off(JNVIEndpoint.Events.VideoStreamAdded, this.__onEndpointRemoteMediaAddedHandler);
			this.endpoint.off(JNVIEndpoint.Events.VideoStreamRemoved, this.__onEndpointRemoteMediaRemovedHandler);
			this.endpoint.off(JNVIEndpoint.Events.Removed, this.__onEndpointRemovedHandler);
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
			var calculatedState = this.calculateState();

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
			if (!(typeof (withCalling) === "boolean"))
			{
				withCalling = true;
			}
			if (withCalling)
			{
				return ((this.calling || this.ready || this.endpoint) && !this.declined);
			}
			else
			{
				return ((this.ready || this.endpoint) && !this.declined);
			}
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

			this.log("Done waiting for connection restoration");
			this.setReady(false);
		}

		__onEndpointRemoteMediaAdded(e)
		{
			this.log("RemoteMediaAdded", e);
			this.callbacks.onStreamReceived({
				userId: this.userId,
				stream: this.endpoint.remoteVideoStreams[0],
			});

			this.updateCalculatedState();
		}

		__onEndpointRemoteMediaRemoved(e)
		{
			this.log("Remote media removed");
			this.callbacks.onStreamRemoved({
				userId: this.userId,
			});

			this.updateCalculatedState();
		}

		__onEndpointRemoved(e)
		{
			this.log("Endpoint removed");

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

			this.callbacks["onStateChanged"] = BX.DoNothing;
			this.callbacks["onStreamReceived"] = BX.DoNothing;
			this.callbacks["onStreamRemoved"] = BX.DoNothing;

			clearTimeout(this.callingTimeout);
			clearTimeout(this.connectionRestoreTimeout);
			this.callingTimeout = null;
			this.connectionRestoreTimeout = null;
			this.call = null;
		}
	}

	window.VIClientWrapper = VIClientWrapper;
	window.VoximplantCall = VoximplantCall;
})
();

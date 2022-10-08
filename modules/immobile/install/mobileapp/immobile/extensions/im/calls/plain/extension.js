"use strict";

(function ()
{
	const DoNothing = function ()
	{
	};

	const defaultConnectionOptions = {"OfferToReceiveAudio": "true", "OfferToReceiveVideo": "true"};
	const signalingWaitReplyPeriod = 10000;
	const pingPeriod = 5000;
	const backendPingPeriod = 25000;
	const reinvitePeriod = 5500;

	const ajaxActions = Object.freeze({
		invite: "im.call.invite",
		cancel: "im.call.cancel",
		answer: "im.call.answer",
		decline: "im.call.decline",
		hangup: "im.call.hangup",
		ping: "im.call.ping",
		negotiationNeeded: "im.call.negotiationNeeded",
		connectionOffer: "im.call.connectionOffer",
		connectionAnswer: "im.call.connectionAnswer",
		iceCandidate: "im.call.iceCandidate",
	});

	const PullEvents = {
		answer: "Call::answer",
		ping: "Call::ping",
		negotiationNeeded: "Call::negotiationNeeded",
		connectionOffer: "Call::connectionOffer",
		connectionAnswer: "Call::connectionAnswer",
		iceCandidate: "Call::iceCandidate",
		voiceStarted: "Call::voiceStarted",
		voiceStopped: "Call::voiceStopped",
		microphoneState: "Call::microphoneState",
		cameraState: "Call::cameraState",
		videoPaused: "Call::videoPaused",
		hangup: "Call::hangup",
		userInviteTimeout: "Call::userInviteTimeout",
		repeatAnswer: "Call::repeatAnswer",
	};

	class PlainCall
	{
		constructor(params)
		{
			this.id = params.id;
			this.instanceId = params.instanceId;
			this.parentId = params.parentId;
			this.direction = params.direction;
			this.associatedEntity = params.associatedEntity || {};

			this.userId = parseInt(env.userId, 10);

			this.initiatorId = params.initiatorId || "";
			this.users = BX.type.isArray(params.users) ? params.users.filter(userId => userId != this.userId) : [];

			this.ready = false;
			this._joinStatus = BX.Call.JoinStatus.None;
			Object.defineProperty(this, "joinStatus", {
				get: this.getJoinStatus.bind(this),
				set: this.setJoinStatus.bind(this),
			});
			this._active = false; // has remote pings
			Object.defineProperty(this, "active", {
				get: this.getActive.bind(this),
				set: this.setActive.bind(this),
			});

			this.localStream = null;
			this.videoEnabled = !!params.videoEnabled;
			this.muted = params.muted === true;

			/** @var {Peer[]} */
			this.peers = [];

			this.signaling = new Signaling({
				call: this,
			});

			this.logToken = params.logToken || "";
			if (callEngine.getLogService() && this.logToken)
			{
				this.logger = new CallLogger(callEngine.getLogService(), this.logToken);
			}

			this.eventEmitter = new JNEventEmitter();
			params.events = params.events || {};
			if (params.events)
			{
				for (let eventName in params.events)
				{
					if (params.events.hasOwnProperty(eventName))
					{
						this.eventEmitter.on(eventName, params.events[eventName]);
					}
				}
			}

			this.pingUsersInterval = setInterval(this.pingUsers.bind(this), pingPeriod);
			this.pingBackendInterval = setInterval(this.pingBackend.bind(this), backendPingPeriod);

			this.lastPingReceivedTimeout = null;

			this.created = new Date();

			this.initPeers();
		}

		initPeers()
		{
			this.users.forEach(userId =>
			{
				userId = parseInt(userId, 10);
				this.peers.push(this.createPeer(userId));
			});
		}

		/**
		 * Invites users to participate in the call.
		 *
		 * @param {Object} config
		 * @param {int[]} [config.users] Array of ids of the users to be invited.
		 **/
		inviteUsers(config = {})
		{
			let users = config.users || this.peers.map(peer => peer.userId);
			if (users.length === 0)
			{
				throw new Error("No users to invite");
			}
			this.ready = true;

			this.getLocalMedia().then(() =>
			{
				return this.getSignaling().inviteUsers({
					userIds: users,
					video: this.videoEnabled ? "Y" : "N",
				});
			}).then((response) =>
			{
				for (let i = 0; i < users.length; i++)
				{
					let userId = users[i];
					let peer = this.getPeer(userId);
					if (!peer)
					{
						peer = this.createPeer(userId);
						this.peers.push(peer);
					}
					peer.onInvited();
					this.joinStatus = BX.Call.JoinStatus.Local;
					/*this.runCallback(BX.Call.Event.onUserInvited, {
							userId: userId
						});*/
					//this.scheduleRepeatInvite();
				}
			});
		}

		createPeer(userId)
		{
			userId = parseInt(userId, 10);

			return new Peer({
				call: this,
				userId: userId,
				ready: userId == this.initiatorId,

				onStreamReceived: (track) =>
				{
					this.log("onStreamReceived; track kind: ", track.kind, "id: ", track.id);
					this.eventEmitter.emit(BX.Call.Event.onStreamReceived, [userId, track]);
				},
				onStreamRemoved: (e) =>
				{
					this.log("onStreamRemoved: ", e);
					this.eventEmitter.emit(BX.Call.Event.onStreamRemoved, [userId]);
				},
				onStateChanged: this._onPeerStateChanged.bind(this),
				onInviteTimeout: this._onPeerInviteTimeout.bind(this),
			});
		}

		/**
		 *
		 * @param userId
		 * @returns {Peer|undefined}
		 */
		getPeer(userId)
		{
			return this.peers.find(peer => peer.userId == userId);
		}

		getUsers()
		{
			let result = {};
			this.peers.forEach(peer => result[peer.userId] = peer.calculatedState);
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
			if (this.ready)
			{
				if (this.videoEnabled)
				{
					if (this.localStream.getVideoTracks().length > 0)
					{
						MediaDevices.startCapture();
						this.peers.forEach(peer => peer.replaceMedia());
						this.eventEmitter.emit(BX.Call.Event.onLocalMediaReceived, [this.localStream]);
					}
					else
					{
						this.getLocalMedia().then(() =>
						{
							this.peers.forEach(peer => peer.replaceMedia());
						});
					}
				}
				else
				{
					MediaDevices.stopCapture();
					this.peers.forEach(peer => peer.replaceMedia());
					this.eventEmitter.emit(BX.Call.Event.onLocalMediaStopped);
				}
			}

			this.signaling.sendCameraState(this.users, this.videoEnabled);
		}

		setVideoPaused(videoPaused)
		{
			if (this.videoPaused == videoPaused)
			{
				return;
			}

			this.videoPaused = videoPaused;
			this.log("call setVideoPaused " + this.videoPaused.toString());
			if (this.localStream && this.localStream.getVideoTracks().length > 0)
			{
				this.localStream.getVideoTracks().forEach(track => track.enabled = !this.videoPaused);
				this.signaling.sendVideoPaused(this.users, this.videoPaused);
			}
		}

		switchCamera()
		{
			if (!this.videoEnabled || !this.localStream)
			{
				return;
			}
			MediaDevices.switchVideoSource();
		}

		isFrontCameraUsed()
		{
			return MediaDevices.cameraDirection === "front";
		}

		setMuted(muted)
		{
			muted = !!muted;
			if (this.muted == muted)
			{
				return;
			}

			this.muted = muted;
			if (this.localStream)
			{
				var audioTracks = this.localStream.getAudioTracks();
				if (audioTracks[0])
				{
					audioTracks[0].enabled = !this.muted;
				}
			}

			this.signaling.sendMicrophoneState(this.users, !this.muted);
		}

		isMuted()
		{
			return this.muted;
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

		/**
		 * @returns {Signaling}
		 */
		getSignaling()
		{
			return this.signaling;
		}

		getLocalMedia()
		{
			return new Promise((resolve) =>
			{
				MediaDevices.getUserMedia({audio: true, video: this.videoEnabled}).then((stream) =>
				{
					if (this.videoEnabled)
					{
						this.eventEmitter.emit(BX.Call.Event.onLocalMediaReceived, [stream]);
					}
					if (!this.videoEnabled && this.localStream)
					{
						this.eventEmitter.emit(BX.Call.Event.onLocalMediaStopped);
					}
					this.localStream = stream;
					resolve();
				});
			});
		}

		isReady()
		{
			return this.ready;
		}

		sendLocalStream(userId)
		{
			let peer = this.getPeer(userId);
			if (!peer)
			{
				return;
			}

			if (!peer.isReady())
			{
				return;
			}

			peer.sendMedia();
		}

		/**
		 * @param {Object} config
		 * @param {bool} [config.useVideo]
		 * @param {bool} [config.enableMicAutoParameters]
		 * @param {MediaStream} [config.localStream]
		 */
		answer(config)
		{
			if (!BX.type.isPlainObject(config))
			{
				config = {};
			}
			if (this.direction !== BX.Call.Direction.Incoming)
			{
				throw new Error("Only incoming call could be answered");
			}

			this.ready = true;
			this.videoEnabled = (config.useVideo === true);
			this.enableMicAutoParameters = (config.enableMicAutoParameters !== false);

			return new Promise((resolve, reject) =>
			{
				if (this.joinStatus != BX.Call.JoinStatus.None)
				{
					return reject(new CallJoinedElseWhereError());
				}
				this.getLocalMedia().then(() =>
					{
						this.joinStatus = BX.Call.JoinStatus.Local;
						return this.sendAnswer();
					},
					(e) =>
					{
						this.eventEmitter.emit(BX.Call.Event.onCallFailure, [e]);
					},
				).then(() => resolve());
			});
		};

		sendAnswer()
		{
			this.signaling.sendAnswer();
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

				let peer = this.getPeer(userId);
				if (peer)
				{
					if (peer.calculatedState === BX.Call.UserState.Failed || peer.calculatedState === BX.Call.UserState.Idle)
					{
						peer.onInvited();
					}
				}
				else
				{
					peer = this.createPeer(userId);
					this.peers.push(peer);
					peer.onInvited();
				}
				if (!this.users.includes(userId))
				{
					this.users.push(userId);
				}
				this.eventEmitter.emit(BX.Call.Event.onUserInvited, [{userId: userId}]);
			}
		}

		isAnyoneParticipating()
		{
			return this.peers.some(peer => peer.isParticipating());
		}

		decline(code, reason)
		{
			this.ready = false;

			let data = {
				callId: this.id,
				callInstanceId: this.instanceId,
			};

			if (typeof (code) != "undefined")
			{
				data.code = code;
			}
			if (typeof (reason) != "undefined")
			{
				data.reason = reason;
			}

			callEngine.getRestClient().callMethod(ajaxActions.decline, data).then(() => this.destroy());
		};

		hangup()
		{
			if (!this.ready)
			{
				let error = new Error("Hangup in wrong state");
				this.log(error);
				return;
			}

			let tempError = new Error();
			tempError.name = "Call stack:";
			this.log("Hangup received \n" + tempError.stack);

			this.ready = false;

			return new Promise((resolve, reject) =>
			{
				this.peers.forEach(peer => peer.disconnect());
				this.joinStatus = BX.Call.JoinStatus.None;

				this.signaling.sendHangup({userId: this.users});
			});
		};

		pingUsers()
		{
			if (this.ready)
			{
				this.signaling.sendPingToUsers({userId: this.users});
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

		runCallback(eventName, event)
		{

		}

		_onPullEvent(command, params, extra)
		{
			let handlers = {
				"Call::answer": this._onPullEventAnswer.bind(this),
				"Call::hangup": this._onPullEventHangup.bind(this),
				"Call::ping": this._onPullEventPing.bind(this),
				"Call::negotiationNeeded": this._onPullEventNegotiationNeeded.bind(this),
				"Call::connectionOffer": this._onPullEventConnectionOffer.bind(this),
				"Call::connectionAnswer": this._onPullEventConnectionAnswer.bind(this),
				"Call::iceCandidate": this._onPullEventIceCandidate.bind(this),
				"Call::voiceStarted": this._onPullEventVoiceStarted.bind(this),
				"Call::voiceStopped": this._onPullEventVoiceStopped.bind(this),
				"Call::microphoneState": this._onPullEventMicrophoneState.bind(this),
				"Call::videoPaused": this._onPullEventVideoPaused.bind(this),
				"Call::usersJoined": this._onPullEventUsersJoined.bind(this),
				"Call::usersInvited": this._onPullEventUsersInvited.bind(this),
				"Call::userInviteTimeout": this._onPullEventUserInviteTimeout.bind(this),
				"Call::associatedEntityReplaced": this._onPullEventAssociatedEntityReplaced.bind(this),
				"Call::finish": this._onPullEventFinish.bind(this),
				[PullEvents.repeatAnswer]: this._onPullEventRepeatAnswer.bind(this),
			};

			if (handlers[command])
			{
				this.log("Signaling: " + command + "; Parameters: " + JSON.stringify(params));
				handlers[command].call(this, params);
			}
		};

		_onPullEventAnswer(params)
		{
			let senderId = params.senderId;

			this.log("_onPullEventAnswer", senderId, this.userId);
			if (senderId == this.userId)
			{
				return this._onPullEventAnswerSelf(params);
			}
			if (!this.ready)
			{
				return;
			}
			let peer = this.getPeer(senderId);
			if (!peer)
			{
				return;
			}

			peer.setSignalingConnected(true);
			peer.setReady(true);
			peer.isLegacyMobile = params.isLegacyMobile === true;
			if (this.ready)
			{
				peer.sendMedia();
			}
		}

		_onPullEventAnswerSelf(params)
		{
			if (params.callInstanceId === this.instanceId)
			{
				return;
			}

			// call was answered elsewhere
			this.log("Call was answered elsewhere");
			this.joinStatus = BX.Call.JoinStatus.Remote;
		}

		_onPullEventHangup(params)
		{
			let senderId = params.senderId;

			if (this.userId == senderId)
			{
				if (this.instanceId != params.callInstanceId)
				{
					// self hangup elsewhere
					this.joinStatus = BX.Call.JoinStatus.None;
				}
				return;
			}

			let peer = this.getPeer(senderId);
			if (!peer)
			{
				return;
			}

			peer.disconnect(params.code);
			peer.setReady(false);

			if (params.code == 603)
			{
				peer.setDeclined(true);
			}

			if (!this.isAnyoneParticipating())
			{
				this.hangup();
			}
		}

		_onPullEventPing(params)
		{
			if (params.callInstanceId == this.instanceId)
			{
				// ignore self ping
				return;
			}

			clearTimeout(this.lastPingReceivedTimeout);
			this.lastPingReceivedTimeout = setTimeout(this._onNoPingsReceived.bind(this), pingPeriod * 2.1);

			let senderId = params.senderId;
			if (this.userId == senderId && !this.ready)
			{
				this.joinStatus = BX.Call.JoinStatus.Remote;

				return;
			}

			let peer = this.getPeer(senderId);
			if (!peer)
			{
				return;
			}

			peer.setSignalingConnected(true);
			this.active = true;
		}

		_onPullEventNegotiationNeeded(params)
		{
			if (!this.ready)
			{
				return;
			}
			let peer = this.getPeer(params.senderId);
			if (!peer)
			{
				return;
			}

			peer.setReady(true);
			if (params.restart)
			{
				peer.reconnect();
			}
			else
			{
				peer.onNegotiationNeeded();
			}
		}

		_onPullEventConnectionOffer(params)
		{
			if (!this.ready)
			{
				return;
			}
			let peer = this.getPeer(params.senderId);
			if (!peer)
			{
				return;
			}

			peer.setReady(true);
			peer.setUserAgent(params.userAgent);
			peer.setConnectionOffer(params.connectionId, params.sdp, params.tracks);
		}

		_onPullEventConnectionAnswer(params)
		{
			let peer = this.getPeer(params.senderId);
			if (!this.ready)
			{
				return;
			}
			if (!peer)
			{
				return;
			}

			let connectionId = params.connectionId;
			peer.setUserAgent(params.userAgent);
			peer.setConnectionAnswer(connectionId, params.sdp, params.tracks);
		}

		_onPullEventIceCandidate(params)
		{
			if (!this.ready)
			{
				return;
			}
			let peer = this.getPeer(params.senderId);
			let candidates;
			if (!peer)
			{
				return;
			}

			try
			{
				candidates = params.candidates;
				for (var i = 0; i < candidates.length; i++)
				{
					peer.addIceCandidate(params.connectionId, candidates[i]);
				}
			} catch (e)
			{
				this.log("Error parsing serialized candidate: ", e);
			}
		}

		_onPullEventVoiceStarted(params)
		{

		}

		_onPullEventVoiceStopped(params)
		{

		}

		_onPullEventMicrophoneState(params)
		{
			this.eventEmitter.emit(BX.Call.Event.onUserMicrophoneState, [
				params.senderId,
				params.microphoneState,
			]);
		}

		_onPullEventVideoPaused(params)
		{
			this.eventEmitter.emit(BX.Call.Event.onUserVideoPaused, [
				params.senderId,
				params.videoPaused,
			]);
		}

		_onPullEventUsersJoined(params)
		{

		}

		_onPullEventUsersInvited(params)
		{
			if (!this.ready)
			{
				return;
			}
			let users = params.users;
			this.addInvitedUsers(users);
		}

		_onPullEventUserInviteTimeout(params)
		{
			this.log("__onPullEventUserInviteTimeout", params);
			var failedUserId = params.failedUserId;

			if (this.getPeer(failedUserId))
			{
				this.getPeer(failedUserId).onInviteTimeout(false);
			}
		}

		_onPullEventAssociatedEntityReplaced(params)
		{

		}

		_onPullEventFinish(params)
		{
			this.destroy();
		}

		_onPullEventRepeatAnswer()
		{
			if (this.ready)
			{
				this.signaling.sendAnswer({userId: this.userId}, true)
			}
		}

		_onPeerStateChanged(e)
		{
			this.eventEmitter.emit(BX.Call.Event.onUserStateChanged, [
				e.userId,
				e.state,
				e.previousState,
				e.isLegacyMobile,
			]);

			if (e.state == BX.Call.UserState.Failed || e.state == BX.Call.UserState.Unavailable)
			{
				if (!this.isAnyoneParticipating())
				{
					this.hangup().then(this.destroy.bind(this)).catch((e) =>
						{
							//this.runCallback(BX.Call.Event.onCallFailure, e);
							this.destroy();
						},
					);
				}
			}
			else if (e.state == BX.Call.UserState.Connected)
			{
				this.signaling.sendMicrophoneState(e.userId, !this.muted);
				this.signaling.sendCameraState(e.userId, this.videoEnabled);
			}
		}

		_onPeerInviteTimeout()
		{

		}

		_onNoPingsReceived()
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

		destroy()
		{
			this.ready = null;
			this._active = false;
			this._joinStatus = BX.Call.JoinStatus.None;
			this.peers.forEach(peer => peer.destroy());
			if (this.localStream)
			{
				MediaDevices.stopStreaming();
				this.localStream = null;
			}
			if (this.logger)
			{
				this.logger.destroy();
				this.logger = null;
			}

			clearTimeout(this.pingUsersInterval);
			clearTimeout(this.pingBackendInterval);
			clearTimeout(this.reinviteTimeout);
			clearTimeout(this.lastPingReceivedTimeout);

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

	class Peer
	{
		constructor(params = {})
		{
			this.userId = params.userId;
			this.ready = !!params.ready;
			this.calling = false;
			this.inviteTimeout = false;
			this.declined = false;
			this.busy = false;

			/** @var {PlainCall} this.call */
			this.call = params.call;

			this.userAgent = "";
			this.isLegacyMobile = !!params.isLegacyMobile;

			this.peerConnection = null;
			this.pendingIceCandidates = {};
			this.localIceCandidates = [];

			this.trackList = {};

			this._incomingVideoTrack = null;
			this._incomingScreenTrack = null;
			Object.defineProperty(this, 'incomingVideoTrack', {
				get: () => this._incomingVideoTrack,
				set: (track) => {
					if (this._incomingVideoTrack != track)
					{
						this._incomingVideoTrack = track;
						if (this._incomingScreenTrack)
						{
							// do nothing
						}
						else
						{
							if (this._incomingVideoTrack)
							{
								this.callbacks.onStreamReceived(this._incomingVideoTrack);
							}
							else
							{
								this.callbacks.onStreamRemoved();
							}
						}
					}
				}
			});
			Object.defineProperty(this, 'incomingScreenTrack', {
				get: () => this._incomingScreenTrack,
				set: (track) => {
					if (this._incomingScreenTrack != track)
					{
						this._incomingScreenTrack = track;
						if (this._incomingScreenTrack)
						{
							this.callbacks.onStreamReceived(track);
						}
						else
						{
							if (this._incomingVideoTrack)
							{
								this.callbacks.onStreamReceived(this._incomingVideoTrack);
							}
							else
							{
								this.callbacks.onStreamRemoved();
							}
						}
					}
				}
			});

			this.callbacks = {
				onStateChanged: BX.type.isFunction(params.onStateChanged) ? params.onStateChanged : DoNothing,
				onInviteTimeout: BX.type.isFunction(params.onInviteTimeout) ? params.onInviteTimeout : DoNothing,
				onStreamReceived: BX.type.isFunction(params.onStreamReceived) ? params.onStreamReceived : DoNothing,
				onStreamRemoved: BX.type.isFunction(params.onStreamRemoved) ? params.onStreamRemoved : DoNothing,
				onRTCStatsReceived: BX.type.isFunction(params.onRTCStatsReceived) ? params.onRTCStatsReceived : DoNothing,
			};

			this._onPeerConnectionIceCandidateHandler = this._onPeerConnectionIceCandidate.bind(this);
			this._onPeerConnectionIceConnectionStateChangeHandler = this._onPeerConnectionIceConnectionStateChange.bind(this);
			this._onPeerConnectionIceGatheringStateChangeHandler = this._onPeerConnectionIceGatheringStateChange.bind(this);
			this._onPeerConnectionNegotiationNeededHandler = this._onPeerConnectionNegotiationNeeded.bind(this);
			this._onPeerConnectionTrackHandler = this._onPeerConnectionTrack.bind(this);
			this._onPeerConnectionRemoveTrackHandler = this._onPeerConnectionRemoveTrack.bind(this);
			this._onPeerConnectionAddStreamHandler = this._onPeerConnectionAddStream.bind(this);
			this._onPeerConnectionRemoveStreamHandler = this._onPeerConnectionRemoveStream.bind(this);

			// debounce is important to prevent possible deadlocks in the application (this event can occur while calling setRemoteDescription)
			this._onPeerConnectionSignalingStateChangeHandler = CallUtil.debounce(this._onPeerConnectionSignalingStateChange.bind(this), 100);

			// intervals and timeouts
			this.answerTimeout = null;
			this.callingTimeout = null;
			this.connectionTimeout = null;
			this.signalingConnectionTimeout = null;
			this.candidatesTimeout = null;

			this.connectionAttempt = 0;
		}

		setReady(ready)
		{
			if (this.ready == ready)
			{
				return;
			}
			this.ready = ready;
			if (this.ready)
			{
				this.declined = false;
				this.busy = false;
			}
			if (this.calling)
			{
				clearTimeout(this.callingTimeout);
				this.calling = false;
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
			this.updateCalculatedState();
		}

		onInvited()
		{
			this.ready = false;
			this.inviteTimeout = false;
			this.declined = false;
			this.calling = true;

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

		setSignalingConnected(signalingConnected)
		{

		}

		setUserAgent(userAgent)
		{
			this.userAgent = userAgent;
			this.isLegacyMobile = userAgent === "Bitrix Legacy Mobile";
		}

		isReady()
		{
			return this.ready;
		}

		isInitiator()
		{
			return this.call && (this.call.userId < this.userId);
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
					isLegacyMobile: this.isLegacyMobile,
				});
				this.calculatedState = calculatedState;
			}
		};

		calculateState()
		{
			if (this.peerConnection)
			{
				if (this.peerConnection.iceConnectionState === "connected" || this.peerConnection.iceConnectionState === "completed")
				{
					return BX.Call.UserState.Connected;
				}

				return BX.Call.UserState.Connecting;
			}
			else
			{
				if (this.failureReason)
				{
					return BX.Call.UserState.Failed;
				}
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
		};

		isParticipating()
		{
			if (this.calling)
			{
				return true;
			}

			if (this.declined || this.busy)
			{
				return false;
			}

			if (this.peerConnection)
			{
				// todo: maybe we should check iceConnectionState as well.
				var iceConnectionState = this.peerConnection.iceConnectionState;
				if (iceConnectionState == "checking" || iceConnectionState == "connected" || iceConnectionState == "completed")
				{
					return true;
				}
			}

			return false;
		}

		isRenegotiationSupported()
		{
			return true;
		}

		getSignaling()
		{
			return this.call ? this.call.signaling : null;
		}

		log()
		{
			this.call && this.call.log.apply(this.call, arguments);
		}

		sendIceCandidates()
		{
			if (this.candidatesTimeout)
			{
				clearTimeout(this.candidatesTimeout);
				this.candidatesTimeout = null;
			}
			if (!this.call)
			{
				return;
			}

			this.log("User " + this.userId + ": sending ICE candidates due to the timeout");

			if (this.localIceCandidates.length > 0)
			{
				this.getSignaling().sendIceCandidate({
					userId: this.userId,
					connectionId: this.peerConnection._id,
					candidates: this.localIceCandidates,
				});
				this.localIceCandidates = [];
			}
			else
			{
				this.log("User " + this.userId + ": ICE candidates pool is empty");
			}
		}

		sendMedia()
		{
			if (this.peerConnection)
			{
				this.log("Error: Peer connection already exists");
				return;
			}

			if (!this.call.localStream)
			{
				this.log(new Error("Local media stream is not found"));
			}

			if (this.isInitiator())
			{
				let connectionId = callEngine.getUuidv4();
				this._createPeerConnection(connectionId);
				this.call.localStream.getTracks().forEach(track => this.peerConnection.addTrack(track, this.call.localStream));

				this.createAndSendOffer();
			}
			else
			{
				this.sendNegotiationNeeded();
			}
		}

		updateOutgoingTracks()
		{
			let videoTrack = this.call.videoEnabled && this.call.localStream.getVideoTracks()[0];
			let audioTrack = this.call.localStream.getAudioTracks()[0];

			let videoSender = this.peerConnection.getSenders().find(
				sender => sender.track && sender.track.kind == "video",
			);
			if (videoSender && videoTrack)
			{
				// do nothing >:-)
			}
			if (!videoSender && videoTrack)
			{
				this.peerConnection.addTrack(videoTrack, this.call.localStream);
			}
			if (videoSender && !videoTrack)
			{
				this.peerConnection.removeTrack(videoSender);
			}

			let audioSender = this.peerConnection.getSenders().find(
				sender => sender.track && sender.track.kind == "audio",
			);
			if (audioSender && audioTrack)
			{
				// do nothing >:-)
			}
			if (!audioSender && audioTrack)
			{
				this.peerConnection.addTrack(audioTrack, this.call.localStream);
			}
			if (audioSender && !audioTrack)
			{
				this.peerConnection.removeTrack(audioSender);
			}
		}

		replaceMedia()
		{
			if (!this.isReady())
			{
				return;
			}
			if (this.isRenegotiationSupported())
			{
				this.updateOutgoingTracks();
				this.createAndSendOffer();
			}
			else
			{
				this.reconnect();
			}
		}

		setConnectionOffer(connectionId, sdp, trackList)
		{
			this.log("User " + this.userId + ": received connection offer for connection " + connectionId);

			clearTimeout(this.negotiationNeededReplyTimeout);
			this.negotiationNeededReplyTimeout = null;

			if (!this.call.isReady())
			{
				return;
			}

			this.setReady(true);

			if (trackList)
			{
				this.trackList = CallUtil.array_flip(trackList);
			}

			if (this.peerConnection)
			{
				if (this.peerConnection._id !== connectionId)
				{
					this._destroyPeerConnection();
					this._createPeerConnection(connectionId);
				}
			}
			else
			{
				this._createPeerConnection(connectionId);
			}

			this.applyOfferAndSendAnswer(sdp);
		};

		createAndSendOffer(config)
		{
			let connectionConfig = Object.assign({}, defaultConnectionOptions, config);

			this.peerConnection.createOffer(connectionConfig).then((offer) =>
			{
				this.log("User " + this.userId + ": Created connection offer.");
				this.log("Applying local description");
				this.pendingLocalDescription = offer;
				this.sendOffer();
				//return this.peerConnection.setLocalDescription(offer);
			});/*.then(() =>
			{
				this.sendOffer();
			})*/
		};

		sendOffer()
		{
			let connectionId = this.peerConnection._id;
			clearTimeout(this.connectionOfferReplyTimeout);
			this.connectionOfferReplyTimeout = setTimeout(() => this._onConnectionOfferReplyTimeout(connectionId), signalingWaitReplyPeriod);

			this.getSignaling().sendConnectionOffer({
				userId: this.userId,
				connectionId: connectionId,
				sdp: this.pendingLocalDescription.sdp,
				userAgent: "Bitrix Mobile",
			});
		};

		onNegotiationNeeded()
		{
			if (this.peerConnection)
			{
				if (this.peerConnection.signalingState == "have-local-offer")
				{
					this.sendOffer();
				}
				else
				{
					this.createAndSendOffer({iceRestart: true});
				}
			}
			else
			{
				this.sendMedia();
			}
		}

		sendNegotiationNeeded(restart)
		{
			restart = restart === true;
			clearTimeout(this.negotiationNeededReplyTimeout);
			this.negotiationNeededReplyTimeout = setTimeout(() => this._onNegotiationNeededReplyTimeout(), signalingWaitReplyPeriod);

			let params = {
				userId: this.userId,
			};
			if (restart)
			{
				params.restart = true;
			}

			this.getSignaling().sendNegotiationNeeded(params);
		};

		applyOfferAndSendAnswer(sdp)
		{
			let sessionDescription = {
				type: "offer",
				sdp: sdp,
			};

			this.log("User: " + this.userId + "; Applying remote offer");
			this.log("User: " + this.userId + "; Peer ice connection state ", this.peerConnection.iceConnectionState);

			this.peerConnection.setRemoteDescription(sessionDescription).then(() =>
			{
				if (this.peerConnection.iceConnectionState === "new")
				{
					this.call.localStream.getTracks().forEach(track => this.peerConnection.addTrack(track, this.call.localStream));
				}

				return this.peerConnection.createAnswer();
			}).then((answer) =>
			{
				this.log("Created connection answer.");
				this.log("Applying local description.");
				return this.peerConnection.setLocalDescription(answer);
			}).then(() =>
			{
				this.applyPendingIceCandidates();
				this.getSignaling().sendConnectionAnswer({
					userId: this.userId,
					connectionId: this.peerConnection._id,
					sdp: this.peerConnection.localDescription().sdp,
					userAgent: "Bitrix Mobile",
				});
			}).catch((e) =>
			{
				this.failureReason = e.toString();
				this.updateCalculatedState();
				this.log("Could not apply remote offer", e);
				console.error("Could not apply remote offer", e);
			});
		};

		setConnectionAnswer(connectionId, sdp, trackList)
		{
			if (!this.peerConnection)
			{
				return;
			}

			clearTimeout(this.connectionOfferReplyTimeout);

			if (this.peerConnection._id != connectionId)
			{
				this.log("Could not apply answer, for unknown connection " + connectionId);
				return;
			}

			if (trackList)
			{
				this.trackList = CallUtil.array_flip(trackList);
			}

			let sessionDescription = {
				type: "answer",
				sdp: sdp,
			};

			if (this.peerConnection.signalingState !== "have-local-offer" && !this.pendingLocalDescription)
			{
				this.log("Could not apply answer, wrong peer connection signaling state " + this.peerConnection.signalingState);
				return;
			}

			this.log("User: " + this.userId + "; Applying remote answer");

			this.maybeSetPendingLocalOffer().then(() =>
			{
				return this.peerConnection.setRemoteDescription(sessionDescription);
			}).then(() =>
			{
				return this.applyPendingIceCandidates();
			}).catch((e) =>
			{
				this.failureReason = e.toString();
				this.updateCalculatedState();
				this.log(e);
			});
		}

		maybeSetPendingLocalOffer()
		{
			return new Promise((resolve, reject) =>
			{
				if (this.pendingLocalDescription)
				{
					this.peerConnection.setLocalDescription(this.pendingLocalDescription)
						.then(() =>
						{
							this.pendingLocalDescription = null;
							resolve();
						})
						.catch(err => reject(err));
				}
				else
				{
					resolve();
				}
			});
		}

		addIceCandidate(connectionId, candidate)
		{
			if (!this.peerConnection)
			{
				return;
			}

			if (this.peerConnection._id != connectionId)
			{
				this.log("Error: Candidate for unknown connection " + connectionId);
				return;
			}

			if (this.peerConnection.remoteDescription() && this.peerConnection.remoteDescription().type)
			{
				this.peerConnection.addIceCandidate(candidate).then(() =>
				{
					this.log("User: " + this.userId + "; Added remote ICE candidate: " + (candidate ? candidate.candidate : candidate));
				}).catch((e) =>
				{
					this.log(e);
				});
			}
			else
			{
				if (!this.pendingIceCandidates[connectionId])
				{
					this.pendingIceCandidates[connectionId] = [];
				}
				this.pendingIceCandidates[connectionId].push(candidate);
			}
		};

		applyPendingIceCandidates()
		{
			if (!this.peerConnection || !this.peerConnection.remoteDescription().type)
			{
				return;
			}

			var connectionId = this.peerConnection._id;
			if (BX.type.isArray(this.pendingIceCandidates[connectionId]))
			{
				this.pendingIceCandidates[connectionId].forEach((candidate) =>
				{
					this.peerConnection.addIceCandidate(candidate).then(() =>
					{
						this.log("User: " + this.userId + "; Added remote ICE candidate: " + (candidate ? candidate.candidate : candidate));
					});
				});

				this.pendingIceCandidates[connectionId] = [];
			}
		};

		updateCandidatesTimeout()
		{
			if (this.candidatesTimeout)
			{
				clearTimeout(this.candidatesTimeout);
			}

			this.candidatesTimeout = setTimeout(this.sendIceCandidates.bind(this), 500);
		}

		reconnect()
		{
			clearTimeout(this.reconnectAfterDisconnectTimeout);
			this.connectionAttempt++;
			if (!this.call)
			{
				return;
			}

			if (this.connectionAttempt > 3)
			{
				this.log("Error: Too many reconnection attempts, giving up");
				this.failureReason = "Could not connect to user in time";
				this._destroyPeerConnection();
				this.updateCalculatedState();
				return;
			}

			console.trace("Trying to restore ICE connection. Attempt " + this.connectionAttempt);
			this.log("Trying to restore ICE connection. Attempt " + this.connectionAttempt);
			if (this.isInitiator())
			{
				this._destroyPeerConnection();
				this.sendMedia();
			}
			else
			{
				this.sendNegotiationNeeded(true);
			}
		}

		_createPeerConnection(id)
		{
			const turnServer = BX.componentParameters.get("turnServer", "");
			const turnServerLogin = BX.componentParameters.get("turnServerLogin", "");
			const turnServerPassword = BX.componentParameters.get("turnServerPassword", "");
			let connectionConfig = {
				"iceServers": [
					{
						urls: ["stun:" + turnServer],
						username: turnServerLogin,
						credential: turnServerPassword,
					},
					{
						urls: ["turn:" + turnServer],
						username: turnServerLogin,
						credential: turnServerPassword,
					},
				],
				//iceTransportPolicy: 'relay'
			};

			this.log("creating peer connection " + id);

			this.localIceCandidates = [];

			this.peerConnection = new RTCPeerConnection(connectionConfig);
			this.peerConnection._id = id;

			this.peerConnection.on(JNRTCPeerConnection.Events.IceCandidate, this._onPeerConnectionIceCandidateHandler);
			this.peerConnection.on(JNRTCPeerConnection.Events.IceConnectionStateChange, this._onPeerConnectionIceConnectionStateChangeHandler);
			this.peerConnection.on(JNRTCPeerConnection.Events.IceGatheringStateChange, this._onPeerConnectionIceGatheringStateChangeHandler);
			this.peerConnection.on(JNRTCPeerConnection.Events.NegotiationNeeded, this._onPeerConnectionNegotiationNeededHandler);
			this.peerConnection.on(JNRTCPeerConnection.Events.Track, this._onPeerConnectionTrackHandler);
			this.peerConnection.on(JNRTCPeerConnection.Events.RemoveTrack, this._onPeerConnectionRemoveTrackHandler);
			this.peerConnection.on(JNRTCPeerConnection.Events.AddStream, this._onPeerConnectionAddStreamHandler);
			this.peerConnection.on(JNRTCPeerConnection.Events.RemoveStream, this._onPeerConnectionRemoveStreamHandler);
			this.peerConnection.on(JNRTCPeerConnection.Events.SignalingStateChange, this._onPeerConnectionSignalingStateChangeHandler);
		}

		_destroyPeerConnection()
		{
			if (!this.peerConnection)
			{
				return;
			}

			var connectionId = this.peerConnection._id;

			this.log("User " + this.userId + ": Destroying peer connection " + connectionId);

			this.peerConnection.off(JNRTCPeerConnection.Events.IceCandidate, this._onPeerConnectionIceCandidateHandler);
			this.peerConnection.off(JNRTCPeerConnection.Events.IceConnectionStateChange, this._onPeerConnectionIceConnectionStateChangeHandler);
			this.peerConnection.off(JNRTCPeerConnection.Events.IceGatheringStateChange, this._onPeerConnectionIceGatheringStateChangeHandler);
			this.peerConnection.off(JNRTCPeerConnection.Events.NegotiationNeeded, this._onPeerConnectionNegotiationNeededHandler);
			this.peerConnection.off(JNRTCPeerConnection.Events.Track, this._onPeerConnectionTrackHandler);
			this.peerConnection.off(JNRTCPeerConnection.Events.RemoveStream, this._onPeerConnectionRemoveStreamHandler);
			this.peerConnection.off(JNRTCPeerConnection.Events.SignalingStateChange, this._onPeerConnectionSignalingStateChangeHandler);

			this.localIceCandidates = [];
			if (this.pendingIceCandidates[connectionId])
			{
				delete this.pendingIceCandidates[connectionId];
			}

			this.peerConnection.close();
			this.peerConnection = null;
		}

		_hasIncomingVideo()
		{
			if (!this.peerConnection)
			{
				return false;
			}
			return this.peerConnection.getTransceivers().some((tr) =>
			{
				return (tr.currentDirection == "sendrecv" || tr.currentDirection == "recvonly") && (tr.receiver && tr.receiver.track && tr.receiver.track.kind === "video");
			});
		};

		_onPeerConnectionIceCandidate(candidate)
		{
			this.log("User " + this.userId + ": ICE candidate discovered. Candidate: " + JSON.stringify(candidate));

			if (candidate)
			{
				this.getSignaling().getPublishingState().then(result =>
				{
					if (result)
					{
						this.getSignaling().sendIceCandidate({
							userId: this.userId,
							connectionId: this.peerConnection._id,
							candidates: [candidate],
						});
					}
					else
					{
						this.localIceCandidates.push(candidate);
						this.updateCandidatesTimeout();
					}
				}).catch(error => console.error(error));
			}
		}

		_onPeerConnectionIceConnectionStateChange()
		{
			this.log("User " + this.userId + ": ICE connection state changed. New state: " + this.peerConnection.iceConnectionState);

			if (this.peerConnection.iceConnectionState === "connected" || this.peerConnection.iceConnectionState === "completed")
			{
				this.connectionAttempt = 0;
				this.failureReason = "";
				clearTimeout(this.reconnectAfterDisconnectTimeout);
			}
			else if (this.peerConnection.iceConnectionState === "failed")
			{
				this.log("ICE connection failed. Trying to restore connection immediately");
				this.reconnect();
			}
			else if (this.peerConnection.iceConnectionState === "disconnected")
			{
				this.log("ICE connection lost. Waiting 5 seconds before trying to restore it");
				clearTimeout(this.reconnectAfterDisconnectTimeout);
				this.reconnectAfterDisconnectTimeout = setTimeout(() => this.reconnect(), 5000);
			}

			this.updateCalculatedState();
		}

		_onPeerConnectionIceGatheringStateChange()
		{

		}

		_onPeerConnectionNegotiationNeeded()
		{
			this.log("_onPeerConnectionNegotiationNeeded");

			/*if (this.isInitiator())
			{
				this.createAndSendOffer();
			}
			else
			{
				this.sendNegotiationNeeded();
			}*/
		}

		_getTrackMid(trackId)
		{
			if (!this.peerConnection)
			{
				return null;
			}
			let tr = this.peerConnection.getTransceivers().find(
				tr => tr.receiver && tr.receiver.track && tr.receiver.track.id == trackId
			);
			if (!tr)
			{
				return null;
			}
			return tr.mid;
		}

		_onPeerConnectionTrack(e)
		{
			let {track} = e;

			this.log("_onPeerConnectionTrack; kind: ", track.kind, "id: ", track.id);
		}

		_onPeerConnectionRemoveTrack(e)
		{
			let {track} = e;
			this.call.log("_onPeerConnectionRemoveTrack; kind: ", track.kind, "id: ", track.id);
		}

		_onPeerConnectionAddStream(e)
		{
			this.call.log("_onPeerConnectionAddStream", e);
		}

		_onPeerConnectionRemoveStream(e)
		{
			this.call.log("_onPeerConnectionRemoveStream", e);
		}

		_onPeerConnectionSignalingStateChange()
		{
			let screenTrack = null;
			let videoTrack = null;
			if (this.peerConnection.signalingState == "stable")
			{
				this.peerConnection.getTransceivers().forEach(tr => {
					if (
						(tr.currentDirection == "sendrecv" || tr.currentDirection == "recvonly")
						&& (tr.receiver && tr.receiver.track)
					)
					{
						let track = tr.receiver.track;
						console.log(`track received. mid: ${tr.mid} kind: ${track.kind}`);
						if (track.kind === 'audio')
						{
							// do nothing
						}
						if (track.kind === 'video')
						{
							if (this.trackList[tr.mid] === 'screen')
							{
								screenTrack = track;
							}
							else
							{
								videoTrack = track;
							}
						}
					}
				})
			}
			this.incomingScreenTrack = screenTrack;
			this.incomingVideoTrack = videoTrack;
		}

		stopSignalingTimeout()
		{
			clearTimeout(this.signalingConnectionTimeout);
		}

		refreshSignalingTimeout()
		{
			clearTimeout(this.signalingConnectionTimeout);
			this.signalingConnectionTimeout = setTimeout(this._onLostSignalingConnection.bind(this), signalingConnectionRefreshPeriod);
		}

		_onLostSignalingConnection()
		{
			this.setSignalingConnected(false);
		}

		_onConnectionOfferReplyTimeout(connectionId)
		{
			this.log("did not receive connection answer for connection " + connectionId);
			this.reconnect();
		}

		_onNegotiationNeededReplyTimeout()
		{
			this.log("did not receive connection offer in time");
			this.reconnect();
		};

		disconnect()
		{
			clearTimeout(this.reconnectAfterDisconnectTimeout);
			this._destroyPeerConnection();
		}

		destroy()
		{
			this._destroyPeerConnection();

			clearTimeout(this.answerTimeout);
			this.answerTimeout = null;

			clearTimeout(this.connectionTimeout);
			this.connectionTimeout = null;

			clearTimeout(this.signalingConnectionTimeout);
			this.signalingConnectionTimeout = null;

			clearTimeout(this.reconnectAfterDisconnectTimeout);
			this.reconnectAfterDisconnectTimeout = null;

			this.callbacks.onStateChanged = DoNothing;
			this.callbacks.onStreamReceived = DoNothing;
			this.callbacks.onStreamRemoved = DoNothing;
			this.call = null;
		}

	}

	class Signaling
	{
		/**
		 * @param {object} params
		 * @param {PlainCall} params.call
		 */
		constructor(params)
		{
			this.call = params.call;
		}

		isIceTricklingAllowed()
		{
			return this.isPublishingSupported();
		};

		getPublishingState()
		{
			return BX.PULL.getPublishingState();
		}

		inviteUsers(data)
		{
			return this.__runRestAction(ajaxActions.invite, data);
		};

		sendAnswer(data, repeated)
		{
			if (repeated)
			{
				this.__sendPullEventOrCallRest(PullEvents.answer, ajaxActions.answer, data, 30);
			}
			else
			{
				return this.__runRestAction(ajaxActions.answer, data);
			}
		};

		sendConnectionOffer(data)
		{
			this.__sendPullEventOrCallRest(PullEvents.connectionOffer, ajaxActions.connectionOffer, data, 0);
		};

		sendConnectionAnswer(data)
		{
			this.__sendPullEventOrCallRest(PullEvents.connectionAnswer, ajaxActions.connectionAnswer, data, 0);
		};

		sendIceCandidate(data)
		{
			this.__sendPullEventOrCallRest(PullEvents.iceCandidate, ajaxActions.iceCandidate, data, 0);
		};

		sendNegotiationNeeded(data)
		{
			this.__sendPullEventOrCallRest(PullEvents.negotiationNeeded, ajaxActions.negotiationNeeded, data, 0);
		};

		sendVoiceStarted(data)
		{
			this.__sendPullEventOrCallRest(PullEvents.voiceStarted, "", data, 0);
		};

		sendVoiceStopped(data)
		{
			this.__sendPullEventOrCallRest(PullEvents.voiceStopped, "", data, 0);
		};

		sendMicrophoneState(users, microphoneState)
		{
			this.__sendPullEventOrCallRest(PullEvents.microphoneState, "",
				{
					userId: users,
					microphoneState: microphoneState,
				},
				0,
			);
		};

		sendCameraState(users, cameraState)
		{
			this.__sendPullEventOrCallRest(PullEvents.cameraState, "",
				{
					userId: users,
					cameraState: cameraState,
				},
				0,
			);
		};

		sendVideoPaused(users, videoPaused)
		{
			this.__sendPullEventOrCallRest(PullEvents.videoPaused, "",
				{
					userId: users,
					videoPaused: videoPaused,
				},
				0,
			);
		};

		sendPingToUsers(data)
		{
			this.__sendPullEventOrCallRest(PullEvents.ping, "", data, 0);
		};

		sendRepeatAnswer(data)
		{
			this.__sendPullEvent(PullEvents.repeatAnswer, data);
		}

		sendPingToBackend()
		{
			BX.PULL.getPublishingState().then(result =>
			{
				let retransmit = !result;
				this.__runRestAction(ajaxActions.ping, {retransmit: retransmit});
			});
		};

		sendUserInviteTimeout(data)
		{
			this.__sendPullEventOrCallRest(PullEvents.userInviteTimeout, "", data, 0);
		};

		sendHangup(data)
		{
			this.getPublishingState().then(result =>
			{
				if (result)
				{
					this.__sendPullEvent(PullEvents.hangup, data);
					let dataForRest = Object.assign({}, data);
					dataForRest.retransmit = false;
					return this.__runRestAction(ajaxActions.hangup, dataForRest);
				}
				else
				{
					data.retransmit = true;
					return this.__runRestAction(ajaxActions.hangup, data);
				}
			});
		};

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
				console.error(error);
				this.call.log("__sendPullEventOrCallRest error: ", error);
			});
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
			data.callInstanceId = this.call.instanceId;
			data.senderId = this.call.userId;
			data.callId = this.call.id;
			data.requestId = callEngine.getUuidv4();

			this.call.log("Sending p2p signaling event " + eventName + "; " + JSON.stringify(data));
			BX.PULL.sendMessage(data.userId, "im", eventName, data, expiry);
		};

		__runRestAction(signalName, data)
		{
			if (!BX.type.isPlainObject(data))
			{
				data = {};
			}

			data.callId = this.call.id;
			data.callInstanceId = this.call.instanceId;
			data.requestId = callEngine.getUuidv4();

			this.call.log("Sending ajax-based signaling event " + signalName + "; " + JSON.stringify(data));
			return callEngine.getRestClient().callMethod(signalName, data).catch(function (e)
			{
				console.error(e);
			});
		};
	}

	window.PlainCall = PlainCall;
	window.PlainCallPeer = Peer;

})();
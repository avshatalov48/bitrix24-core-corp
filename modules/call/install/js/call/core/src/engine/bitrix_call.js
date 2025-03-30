import {Type} from 'main.core';

import {AbstractCall} from './abstract_call';
import {
	CallEngine,
	EndpointDirection,
	UserState,
	Quality,
	UserMnemonic,
	CallEvent,
	CallState,
	CallType,
	Provider
} from './engine';
import {Call, CALL_STATE, MediaStreamsKinds} from '../call_api.js';
import {View} from '../view/view';
import {SimpleVAD} from './simple_vad'
import {Hardware} from '../hardware';
import Util from '../util'

/**
 * Implements Call interface
 * Public methods:
 * - inviteUsers
 * - answer
 * - decline
 * - hangup
 *
 * Events:
 * - onCallStateChanged //not sure about this.
 * - onUserStateChanged
 * - onStreamReceived
 * - onStreamRemoved
 * - onDestroy
 */

const ajaxActions = {
	invite: 'im.call.invite',
	answer: 'im.call.answer',
	decline: 'im.call.decline',
	hangup: 'im.call.hangup',
	ping: 'im.call.ping',
	finish: 'im.call.finish',
};

const pullEvents = {
	ping: 'Call::ping',
	answer: 'Call::answer',
	hangup: 'Call::hangup',
	userInviteTimeout: 'Call::userInviteTimeout',
	repeatAnswer: 'Call::repeatAnswer',
};

const clientEvents = {
	voiceStarted: 'Call::voiceStarted',
	voiceStopped: 'Call::voiceStopped',
	microphoneState: 'Call::microphoneState',
	cameraState: 'Call::cameraState',
	videoPaused: 'Call::videoPaused',
	screenState: 'Call::screenState',
	recordState: 'Call::recordState',
	emotion: 'Call::emotion',
	customMessage: 'Call::customMessage',
	showUsers: 'Call::showUsers',
	showAll: 'Call::showAll',
	hideAll: 'Call::hideAll',
};

const scenarioEvents = {
	viewerJoined: 'Call::viewerJoined',
	viewerLeft: 'Call::viewerLeft',
};

const BitrixCallEvent = {
	onCallConference: 'BitrixCall::onCallConference'
};

const MediaKinds = {
	[MediaStreamsKinds.Camera]: 'video',
	[MediaStreamsKinds.Microphone]: 'audio',
	[MediaStreamsKinds.Screen]: 'sharing',
	[MediaStreamsKinds.ScreenAudio]: 'sharingAudio',
};

const pingPeriod = 5000;
const backendPingPeriod = 25000;
const reinvitePeriod = 5500;

// const MAX_USERS_WITHOUT_SIMULCAST = 6;

export class BitrixCall extends AbstractCall
{
	static Event = BitrixCallEvent
	peers: { [key: number]: Peer }
	localVAD: ?SimpleVAD

	constructor(config)
	{
		super(config);

		this.videoQuality = Quality.VeryHigh; // initial video quality. will drop on new peers connecting

		this.BitrixCall = null;

		this.signaling = new Signaling({
			call: this
		});

		this.peers = {};
		this.peersWithBadConnection = new Set();
		this.joinedElsewhere = false;
		this.joinedAsViewer = false;
		this.localVideoShown = false;
		this._localUserState = UserState.Idle;
		this.clientEventsBound = false;
		this._screenShared = false;
		this.videoAllowedFrom = UserMnemonic.all;
		this.direction = EndpointDirection.SendRecv;
		this.floorRequestActive = false;

		this.recordState = {
			state: View.RecordState.Stopped,
			userId: 0,
			date: {
				start: null,
				pause: []
			},
		};

		this.microphoneLevelInterval = null;

		window.addEventListener("unload", this.#onWindowUnload);

		this.initPeers();

		this.pingUsersInterval = setInterval(this.pingUsers.bind(this), pingPeriod);
		this.pingBackendInterval = setInterval(this.pingBackend.bind(this), backendPingPeriod);

		this.lastPingReceivedTimeout = null;
		this.lastSelfPingReceivedTimeout = null;

		this.reinviteTimeout = null;

		this._reconnectionEventCount = 0;

		this.pullEventHandlers = {
			'Call::answer': this.#onPullEventAnswer,
			'Call::hangup': this.#onPullEventHangup,
			'Call::usersJoined': this.#onPullEventUsersJoined,
			'Call::usersInvited': this.#onPullEventUsersInvited,
			'Call::userInviteTimeout': this.#onPullEventUserInviteTimeout,
			'Call::ping': this.#onPullEventPing,
			'Call::finish': this.#onPullEventFinish,
			'Call::repeatAnswer': this.#onPullEventRepeatAnswer,
			'Call::switchTrackRecordStatus': this.#onPullEventSwitchTrackRecordStatus,
		};

		this._isCopilotActive = config.isCopilotActive;
		this._isCopilotFeaturesEnabled = true;
		this._isRecordWhenCopilotActivePopupAlreadyShow = false;
		this._isBoostExpired = false;
	};

	get provider()
	{
		return Provider.Bitrix;
	}

	get screenShared()
	{
		return this._screenShared;
	}

	get isCopilotActive()
	{
		return this._isCopilotActive;
	}

	set isCopilotActive(isCopilotActive)
	{
		if (isCopilotActive !== this._isCopilotActive)
		{
			this._isCopilotActive = isCopilotActive;
		}
	}

	get isBoostExpired()
	{
		return this._isBoostExpired;
	}

	set isBoostExpired(isBoostExpired)
	{
		if (isBoostExpired !== this._isBoostExpired)
		{
			this._isBoostExpired = isBoostExpired;
		}
	}

	get isCopilotFeaturesEnabled()
	{
		return this._isCopilotFeaturesEnabled;
	}

	set isCopilotFeaturesEnabled(isCopilotFeaturesEnabled)
	{
		if (isCopilotFeaturesEnabled !== this._isCopilotFeaturesEnabled)
		{
			this._isCopilotFeaturesEnabled = isCopilotFeaturesEnabled;
		}
	}

	get isRecordWhenCopilotActivePopupAlreadyShow()
	{
		return this._isRecordWhenCopilotActivePopupAlreadyShow;
	}

	set isRecordWhenCopilotActivePopupAlreadyShow(isRecordWhenCopilotActivePopupAlreadyShow)
	{
		if (isRecordWhenCopilotActivePopupAlreadyShow !== this._isRecordWhenCopilotActivePopupAlreadyShow)
		{
			this._isRecordWhenCopilotActivePopupAlreadyShow = isRecordWhenCopilotActivePopupAlreadyShow;
		}
	}

	set screenShared(screenShared)
	{
		if (screenShared !== this._screenShared)
		{
			this._screenShared = screenShared;
			this.signaling.sendScreenState(this._screenShared);
		}
	}

	get localUserState()
	{
		return this._localUserState
	}

	set localUserState(state)
	{
		if (state === this._localUserState)
		{
			return;
		}
		this.runCallback(CallEvent.onUserStateChanged, {
			userId: this.userId,
			state: state,
			previousState: this._localUserState,
			direction: this.direction,
		});
		this._localUserState = state;
	}

	get reconnectionEventCount()
	{
		return this._reconnectionEventCount;
	}

	set reconnectionEventCount(newValue)
	{
		if (this._reconnectionEventCount === 0 && newValue > 0)
		{
			this.runCallback(CallEvent.onReconnecting, {
				reconnectionEventCount: newValue,
			});
		}
		if (newValue === 0)
		{
			this.runCallback(CallEvent.onReconnected);
		}
		this._reconnectionEventCount = newValue;
	}

	initPeers()
	{
		this.users.forEach((userId) =>
		{
			userId = Number(userId);
			this.peers[userId] = this.createPeer(userId);
		});
	};

	reinitPeers()
	{
		for (let userId in this.peers)
		{
			if (this.peers.hasOwnProperty(userId) && this.peers[userId])
			{
				this.peers[userId].destroy();
				this.peers[userId] = null;
			}
		}

		this.initPeers();
	};

	pingUsers()
	{
		if (this.ready)
		{
			const users = this.users.concat(this.userId);
			this.signaling.sendPingToUsers({userId: users}, true);
		}
	};

	pingBackend()
	{
		if (this.ready)
		{
			this.signaling.sendPingToBackend();
		}
	};

	createPeer(userId)
	{
		let incomingVideoAllowed;
		if (this.videoAllowedFrom === UserMnemonic.all)
		{
			incomingVideoAllowed = true;
		}
		else if (this.videoAllowedFrom === UserMnemonic.none)
		{
			incomingVideoAllowed = false;
		}
		else if (Type.isArray(this.videoAllowedFrom))
		{
			incomingVideoAllowed = this.videoAllowedFrom.some(allowedUserId => allowedUserId == userId);
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

			onMediaReceived: (e) =>
			{
				this.runCallback(CallEvent.onRemoteMediaReceived, e);
				if (e.kind === 'video')
				{
					this.runCallback(CallEvent.onUserVideoPaused, {
						userId: userId,
						videoPaused: false
					});
				}
			},
			onMediaRemoved: (e) =>
			{
				this.runCallback(CallEvent.onRemoteMediaStopped, e);
			},
			onStateChanged: this.#onPeerStateChanged,
			onInviteTimeout: this.#onPeerInviteTimeout,

		})
	};

	getUsers()
	{
		let result = {};
		for (let userId in this.peers)
		{
			result[userId] = this.peers[userId].calculatedState;
		}

		return result;
	};

	getUserCount()
	{
		return Object.keys(this.peers).length;
	};

	canChangeMediaDevices()
	{
		return !this.BitrixCall?.isMediaMutedBySystem;
	};

	setMuted = (event) =>
	{
		if (this.muted === event.data.isMicrophoneMuted)
		{
			return;
		}

		this.muted = event.data.isMicrophoneMuted;

		if (this.BitrixCall)
		{
			this.signaling.sendMicrophoneState(!this.muted);

			if (this.muted)
			{
				this.BitrixCall.disableAudio();
			}
			else
			{
				if (!this.BitrixCall.isAudioPublished())
				{
					this.#setPublishingState(MediaStreamsKinds.Microphone, true);
				}
				this.BitrixCall.enableAudio();
			}
		}
	};

	setVideoEnabled = (event) =>
	{
		if (this.videoEnabled === event.data.isCameraOn)
		{
			return;
		}

		this.videoEnabled = event.data.isCameraOn;

		if (this.BitrixCall)
		{
			this.signaling.sendCameraState(this.videoEnabled);

			if (this.videoEnabled)
			{
				if (!this.BitrixCall.isVideoPublished())
				{
					this.#setPublishingState(MediaStreamsKinds.Camera, true);
				}
				this.localVideoShown = true;
				this.BitrixCall.enableVideo();
			}
			else
			{
				if (this.localVideoShown)
				{
					this.localVideoShown = false;
					this.BitrixCall.disableVideo();
				}
			}
		}
	};

	setCameraId(cameraId)
	{
		if (this.cameraId === cameraId)
		{
			return;
		}
		this.cameraId = cameraId;

		if (this.BitrixCall)
		{
			if (!cameraId)
			{
				this.#onBeforeLocalMediaRendererRemoved(MediaStreamsKinds.Camera);
				return;
			}

			if (this.BitrixCall.isVideoPublished())
			{
				this.#setPublishingState(MediaStreamsKinds.Camera, true);
			}

			this.BitrixCall.switchActiveVideoDevice(this.cameraId)
				.then(() =>
				{
					if (Hardware.isCameraOn)
					{
						this.runCallback('onUpdateLastUsedCameraId');

						if (this.BitrixCall.isVideoPublished() && this.canChangeMediaDevices())
						{
							this.BitrixCall.getLocalVideo()
								.then(track => {
									const mediaRenderer = new MediaRenderer({
										kind: 'video',
										track,
									});
									this.runCallback(CallEvent.onLocalMediaReceived, {
										mediaRenderer,
										tag: 'main',
										stream: mediaRenderer.stream,
									});
								})
								.finally(() => {
									if (this.BitrixCall.isVideoPublished())
									{
										this.#setPublishingState(MediaStreamsKinds.Camera, false);
									}
								});
						}
						else if (!this.canChangeMediaDevices())
						{
							this.runCallback(CallEvent.onNeedResetMediaDevicesState);
						}
						else
						{
							this.#setPublishingState(MediaStreamsKinds.Camera, true);
							this.localVideoShown = true;
							this.BitrixCall.enableVideo(true);
						}
					}
					else
					{
						this.#setPublishingState(MediaStreamsKinds.Camera, false);
					}
				})
				.catch((e) =>
				{
					this.log(e);
					console.error(e);
					this.#onBeforeLocalMediaRendererRemoved(MediaStreamsKinds.Camera);
				});
		}
	};

	setMicrophoneId(microphoneId)
	{
		if (this.microphoneId === microphoneId)
		{
			return;
		}

		this.microphoneId = microphoneId;

		if (this.BitrixCall)
		{
			if (!microphoneId)
			{
				this.#onBeforeLocalMediaRendererRemoved(MediaStreamsKinds.Microphone);
				return;
			}

			this.#setPublishingState(MediaStreamsKinds.Microphone, true);
			this.#onEndpointVoiceEnd({userId: this.userId});

			this.BitrixCall.switchActiveAudioDevice(this.microphoneId)
				.then(() =>
				{
					this.BitrixCall.getLocalAudio()
						.then(track => {
							this.#onMicAccessResult({
								result: true,
								stream: new MediaStream([track]),
							})
						});
				})
				.catch((e) =>
				{
					this.log(e);
					console.error(e);
					this.runCallback(CallEvent.onUserMicrophoneState, {
						userId: this.userId,
						microphoneState: false,
					})
				})
				.finally(() => {
					this.#setPublishingState(MediaStreamsKinds.Microphone, false);

					if (Hardware.isMicrophoneMuted && !this.canChangeMediaDevices())
					{
						this.BitrixCall.disableAudio();
					}
				});
		}
	};

	#setPublishingState(deviceType, publishing)
	{
		if (deviceType === MediaStreamsKinds.Camera)
		{
			this.runCallback(CallEvent.onCameraPublishing, {
				publishing
			});
		}
		else if (deviceType === MediaStreamsKinds.Microphone)
		{
			this.runCallback(CallEvent.onMicrophonePublishing, {
				publishing
			});
		}
	}

	useHdVideo(flag)
	{
		this.videoHd = (flag === true);
	};

	setMainStream(userId)
	{
		if (userId && userId !== this.userId)
		{
			const participant = this.peers[userId]?.participant;
			const kind = participant?.screenSharingEnabled ? MediaStreamsKinds.Screen : MediaStreamsKinds.Camera;
			this.BitrixCall.setMainStream(userId, kind);
		}
		else
		{
			this.BitrixCall.resetMainStream();
		}
	}

	requestFloor(requestActive)
	{
		if (this.floorRequestActive === requestActive)
		{
			return;
		}
		this.floorRequestActive = requestActive;
		this.BitrixCall.raiseHand(requestActive);
	};

	sendRecordState(recordState)
	{
		recordState.senderId = this.userId;

		if (!this.#changeRecordState(recordState))
		{
			return;
		}

		this.runCallback(CallEvent.onUserRecordState, {
			userId: this.userId,
			recordState: this.recordState
		})
		this.signaling.sendRecordState(this.userId, this.recordState);
	};

	sendCustomMessage(message, repeatOnConnect)
	{
		this.signaling.sendCustomMessage(message, repeatOnConnect);
	};

	/**
	 * Updates list of users,
	 */
	allowVideoFrom(userList: $Keys<typeof UserMnemonic> | number[])
	{
		if (this.videoAllowedFrom == userList)
		{
			return;
		}
		this.videoAllowedFrom = userList;

		if (userList === UserMnemonic.all)
		{
			this.signaling.sendShowAll();
			userList = Object.keys(this.peers);
		}
		else if (userList === UserMnemonic.none)
		{
			this.signaling.sendHideAll();
			userList = [];
		}
		else if (Type.isArray(userList))
		{
			this.signaling.sendShowUsers(userList)
		}
		else
		{
			throw new Error("userList is in wrong format");
		}

		let users = {};
		userList.forEach(userId => users[userId] = true);

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
	};

	startScreenSharing()
	{
		if (!this.BitrixCall)
		{
			return;
		}

		this.waitingLocalScreenShare = true;
		this.runCallback(CallEvent.onUserScreenState, {
			userId: this.userId,
			screenState: true,
		});

		this.BitrixCall.startScreenShare();
	};

	stopScreenSharing()
	{
		this.#onBeforeLocalMediaRendererRemoved(MediaStreamsKinds.Screen);
	};

	isScreenSharingStarted()
	{
		return this.screenShared || this.waitingLocalScreenShare;
	};

	isGetUserMediaFulfilled()
	{
		return this.getUserMediaFulfilled;
	};

	/**
	 * Invites users to participate in the call.
	 */
	inviteUsers(config: { users: number[], show?: boolean } = {})
	{
		this.ready = true;
		let users = Type.isArray(config.users) ? config.users : this.users;

		this.attachToConference()
			.then(() =>
			{
				this.signaling.sendPingToUsers({userId: users});

				if (users.length > 0)
				{
					return this.signaling.inviteUsers({
						userIds: users,
						video: Hardware.isCameraOn ? 'Y' : 'N',
						show: config.show === true ? 'Y' : 'N',
					})
				}
			})
			.then(() =>
			{
				this.state = CallState.Connected;
				this.runCallback(CallEvent.onJoin, {
					local: true
				});
				for (let i = 0; i < users.length; i++)
				{
					const userId = parseInt(users[i]);
					if (!this.users.includes(userId))
					{
						this.users.push(userId);
					}
					if (!this.peers[userId])
					{
						this.peers[userId] = this.createPeer(userId);

						if (this.type === CallType.Instant)
						{
							this.runCallback(CallEvent.onUserInvited, {
								userId: userId
							});
						}
					}
					if (this.type === CallType.Instant)
					{
						this.peers[userId].onInvited();
						this.scheduleRepeatInvite();
					}
				}
			})
			.catch((e) =>
			{
				this.#onFatalError(e)
			})
		;
	};

	scheduleRepeatInvite()
	{
		clearTimeout(this.reinviteTimeout);
		this.reinviteTimeout = setTimeout(() => this.repeatInviteUsers(), reinvitePeriod)
	};

	repeatInviteUsers()
	{
		clearTimeout(this.reinviteTimeout);
		if (!this.ready)
		{
			return;
		}
		let usersToRepeatInvite = [];
		for (let userId in this.peers)
		{
			if (this.peers.hasOwnProperty(userId) && this.peers[userId].calculatedState === UserState.Calling)
			{
				usersToRepeatInvite.push(userId);
			}
		}

		if (usersToRepeatInvite.length === 0)
		{
			return;
		}
		const inviteParams = {
			userIds: usersToRepeatInvite,
			video: Hardware.isCameraOn ? 'Y' : 'N',
			repeated: 'Y',
		}
		this.signaling.inviteUsers(inviteParams).then(() => this.scheduleRepeatInvite());
	};

	/**
	 * @param {Object} config
	 * @param {bool?} [config.useVideo]
	 * @param {bool?} [config.joinAsViewer]
	 */
	answer(config = {})
	{
		this.ready = true;
		const joinAsViewer = config.joinAsViewer === true;
		this.videoEnabled = Hardware.isCameraOn;
		this.muted = Hardware.isMicrophoneMuted;

		clearTimeout(this.lastSelfPingReceivedTimeout);

		if (!joinAsViewer)
		{
			this._outgoingAnswer = this.signaling.sendAnswer();
		}
		this.attachToConference({joinAsViewer: joinAsViewer})
			.then(() =>
			{
				this.log("Attached to conference");
				this.state = CallState.Connected;
				this.runCallback(CallEvent.onJoin, {
					local: true
				});
			})
			.catch((err) =>
			{
				this.#onFatalError(err);
			})
		;
	};

	decline(code)
	{
		this.ready = false;
		const data = {
			callId: this.id,
			callInstanceId: this.instanceId,
		};
		if (code)
		{
			data.code = code
		}

		CallEngine.getRestClient().callMethod(ajaxActions.decline, data);
	};

	hangup(code, reason, finishCall = false)
	{
		if (!this.ready)
		{
			const error = new Error("Hangup in wrong state");
			this.log(error);
			return;
		}

		const tempError = new Error();
		tempError.name = "Call stack:";
		this.log("Hangup received \n" + tempError.stack);

		if (this.localVAD)
		{
			this.localVAD.destroy();
			this.localVAD = null;
		}
		clearInterval(this.microphoneLevelInterval);

		let data = {};
		this.ready = false;
		if (typeof (code) != 'undefined')
		{
			data.code = code;
		}
		if (typeof (reason) != 'undefined')
		{
			data.reason = reason;
		}
		this.state = CallState.Proceeding;
		this.runCallback(CallEvent.onLeave, {local: true});

		//clone users and append current user id to send event to all participants of the call
		data.userId = this.users.slice(0).concat(this.userId);
		if (this._outgoingAnswer && !finishCall)
		{
			this._outgoingAnswer.then(() => this.signaling.sendHangup(data));
		}
		else if (finishCall)
		{
			this.signaling.sendFinish(data);
		}
		else if (reason !== 'SIGNALING_DUPLICATE_PARTICIPANT')
		{
			this.signaling.sendHangup(data)
		}

		if (reason !== 'SIGNALING_DUPLICATE_PARTICIPANT') {
			// for future reconnections
			this.reinitPeers();
		}

		if (this.BitrixCall)
		{
			this.BitrixCall._replaceVideoSharing = false;
			this.BitrixCall.hangup();
			this.BitrixCall = null;
		}
		else
		{
			this.log("Tried to hangup, but this.BitrixCall points nowhere");
			console.error("Tried to hangup, but this.BitrixCall points nowhere");
		}

		this.screenShared = false;
		this.localVideoShown = false;
	};

	attachToConference(options: { joinAsViewer: ?boolean } = {})
	{
		const joinAsViewer = options.joinAsViewer === true;
		if (this.BitrixCall && this.BitrixCall.getState() === CALL_STATE.CONNECTED)
		{
			if (this.joinedAsViewer === joinAsViewer)
			{
				return Promise.resolve();
			}
			else
			{
				return Promise.reject("Already joined call in another mode");
			}
		}

		return new Promise((resolve, reject) =>
		{
			this.direction = joinAsViewer ? EndpointDirection.RecvOnly : EndpointDirection.SendRecv;
			this.sendTelemetryEvent("call");

			try
			{
				this.localUserState = UserState.Connecting;
				this.BitrixCall = new Call(this.userId);

				if (Hardware.isCameraOn)
				{
					this.localVideoShown = true;
				}

				this.joinedAsViewer = joinAsViewer;

				if (!this.BitrixCall)
				{
					this.log("Error: could not create Bitrix call");
					return reject({code: "BITRIX_NO_CALL"});
				}

				this.runCallback(BitrixCallEvent.onCallConference, {
					call: this
				});

				this.bindCallEvents();
				this.subscribeHardwareChanges();

				this.BitrixCall.on('Connected', () => {
					this.#onCallConnected();
					resolve();
				})
				this.BitrixCall.on('Failed', (e) =>
				{
					this.#onCallFailed(e);
					reject(e);
				});

				if (!this.ready)
				{
					// for rare cases with fast quit
					return reject({code: "BITRIX_NO_CALL"});
				}

				this.BitrixCall.connect({
					roomId: this.id,
					userId: this.userId,
					endpoint: this.connectionData.endpoint,
					jwt: this.connectionData.jwt,
					videoBitrate: 1000000,
					videoSimulcast: true,
					audioDeviceId: this.microphoneId,
					videoDeviceId: this.cameraId,
				});
			}
			catch (e)
			{
				this.#onFatalError(e);
			}
		});
	};

	#onCallConnected()
	{
		this.reconnectionEventCount = 0;
		this.log("Call connected");
		this.sendTelemetryEvent("connect");
		this.localUserState = UserState.Connected;

		this.BitrixCall.on('Failed', this.#onCallDisconnected);

		this.signaling.sendMicrophoneState(!Hardware.isMicrophoneMuted);
		this.signaling.sendCameraState(Hardware.isCameraOn);

		if (!this.BitrixCall.isAudioPublished())
		{
			this.#setPublishingState(MediaStreamsKinds.Microphone, true);
		}
		this.BitrixCall.enableAudio(Hardware.isMicrophoneMuted);

		if (Hardware.isCameraOn)
		{
			if (!this.BitrixCall.isVideoPublished())
			{
				this.#setPublishingState(MediaStreamsKinds.Camera, true);
			}
			this.BitrixCall.enableVideo();
		}

		if (this.videoAllowedFrom == UserMnemonic.none)
		{
			this.signaling.sendHideAll();
		}
		else if (Type.isArray(this.videoAllowedFrom))
		{
			this.signaling.sendShowUsers(this.videoAllowedFrom);
		}
	};

	#onCallFailed(e)
	{
		this.log("Could not attach to conference", e);
		this.sendTelemetryEvent("connect_failure");
		this.localUserState = UserState.Failed;

		this.BitrixCall.enableSilentLogging(false);
		this.BitrixCall.setLoggerCallback(null);
	};

	bindCallEvents()
	{
		this.BitrixCall.on('PublishSucceed', this.#onLocalMediaRendererAdded);
		this.BitrixCall.on('PublishPaused', this.#onLocalMediaRendererMuteToggled);
		this.BitrixCall.on('MediaMutedBySystem', this.#onMediaMutedBySystem);
		this.BitrixCall.on('PublishFailed', this.#onLocalMediaRendererEnded);
		this.BitrixCall.on('PublishEnded', this.#onLocalMediaRendererEnded);
		this.BitrixCall.on('GetUserMediaStarted', this.#onGetUserMediaStarted.bind(this));
		this.BitrixCall.on('GetUserMediaEnded', this.#onGetUserMediaEnded);
		this.BitrixCall.on('GetUserMediaSuccess', this.#onGetUserMediaSuccess.bind(this));
		this.BitrixCall.on('RemoteMediaAdded', this.#onRemoteMediaAdded);
		this.BitrixCall.on('RemoteMediaRemoved', this.#onRemoteMediaRemoved);
		this.BitrixCall.on('RemoteMediaMuted', this.#onRemoteMediaMuteToggled);
		this.BitrixCall.on('RemoteMediaUnmuted', this.#onRemoteMediaMuteToggled);
		this.BitrixCall.on('ParticipantJoined', this.#onParticipantJoined);
		this.BitrixCall.on('ParticipantStateUpdated', () => console.log('handleParticipantStateUpdated'));
		this.BitrixCall.on('ParticipantLeaved', this.#onParticipantLeaved);
		this.BitrixCall.on('MessageReceived', this.#onCallMessageReceived);
		this.BitrixCall.on('HandRaised', this.#onCallHandRaised);
		this.BitrixCall.on('VoiceStarted', this.#onEndpointVoiceStart);
		this.BitrixCall.on('VoiceEnded', this.#onEndpointVoiceEnd);
		this.BitrixCall.on('Reconnecting', this.#onCallReconnecting);
		this.BitrixCall.on('Reconnected', this.#onCallReconnected);
		this.BitrixCall.on('Disconnected', this.#onCallDisconnected);
		// if (Util.shouldCollectStats())
		// {
			this.BitrixCall.on('CallStatsReceived', this.#onCallStatsReceived);
		// }
		this.BitrixCall.on('UpdatePacketLoss', this.#onUpdatePacketLoss);
		this.BitrixCall.on('ConnectionQualityChanged', this.#onConnectionQualityChanged);
		this.BitrixCall.on('ToggleRemoteParticipantVideo', this.#onToggleRemoteParticipantVideo);
	};

	removeCallEvents()
	{
		if (this.BitrixCall)
		{
			this.BitrixCall.on('Failed', BX.DoNothing);
			this.BitrixCall.on('PublishSucceed', BX.DoNothing);
			this.BitrixCall.on('PublishFailed', BX.DoNothing);
			this.BitrixCall.on('PublishEnded', BX.DoNothing);
			this.BitrixCall.on('GetUserMediaEnded', BX.DoNothing);
			this.BitrixCall.on('RemoteMediaAdded', BX.DoNothing);
			this.BitrixCall.on('RemoteMediaRemoved', BX.DoNothing);
			this.BitrixCall.on('ParticipantJoined', BX.DoNothing);
			this.BitrixCall.on('ParticipantStateUpdated', BX.DoNothing);
			this.BitrixCall.on('ParticipantLeaved', BX.DoNothing);
			this.BitrixCall.on('MessageReceived', BX.DoNothing);
			this.BitrixCall.on('HandRaised', BX.DoNothing);
			this.BitrixCall.on('VoiceStarted', BX.DoNothing);
			this.BitrixCall.on('VoiceEnded', BX.DoNothing);
			this.BitrixCall.on('Reconnecting', BX.DoNothing);
			this.BitrixCall.on('Reconnected', BX.DoNothing);
			this.BitrixCall.on('Disconnected', BX.DoNothing);
			// if (Util.shouldCollectStats())
			// {
				this.BitrixCall.on('CallStatsReceived', BX.DoNothing);
			// }
			this.BitrixCall.on('UpdatePacketLoss', BX.DoNothing);
			this.BitrixCall.on('ConnectionQualityChanged', BX.DoNothing);
			this.BitrixCall.on('ToggleRemoteParticipantVideo', BX.DoNothing);
		}
	};

	subscribeHardwareChanges()
	{
		Hardware.subscribe(Hardware.Events.onChangeMicrophoneMuted, this.setMuted);
		Hardware.subscribe(Hardware.Events.onChangeCameraOn, this.setVideoEnabled);
	};

	unsubscribeHardwareChanges()
	{
		Hardware.unsubscribe(Hardware.Events.onChangeMicrophoneMuted, this.setMuted);
		Hardware.unsubscribe(Hardware.Events.onChangeCameraOn, this.setVideoEnabled);
	};

	/**
	 * Adds new users to call
	 * @param {Number[]} users
	 */
	addJoinedUsers(users)
	{
		for (let i = 0; i < users.length; i++)
		{
			const userId = Number(users[i]);
			if (userId == this.userId || this.peers[userId])
			{
				continue;
			}
			this.peers[userId] = this.createPeer(userId);
			if (!this.users.includes(userId))
			{
				this.users.push(userId);
			}
			this.runCallback(CallEvent.onUserInvited, {
				userId: userId
			});
		}
	};

	/**
	 * Adds users, invited by you or someone else
	 * @param {Number[]} users
	 */
	addInvitedUsers(users, show)
	{
		for (let i = 0; i < users.length; i++)
		{
			const userId = Number(users[i]);
			if (userId == this.userId)
			{
				continue;
			}

			if (!this.peers[userId])
			{
				this.peers[userId] = this.createPeer(userId);
				this.runCallback(CallEvent.onUserInvited, {
					userId: userId
				});
			}

			if (this.type === CallType.Instant && this.peers[userId].calculatedState !== UserState.Calling)
			{
				this.peers[userId].onInvited(show);
			}

			if (!this.users.includes(userId))
			{
				this.users.push(userId);
			}
		}
	};

	toggleRemoteParticipantVideo(participantIds, showVideo, isPaginateToggle = false) {
		if (this.BitrixCall) {
			this.BitrixCall.toggleRemoteParticipantVideo(participantIds, showVideo, isPaginateToggle)
		}
	}

	isAnyoneParticipating()
	{
		for (let userId in this.peers)
		{
			if (this.peers[userId].isParticipating())
			{
				return true;
			}
		}

		return false;
	};

	getParticipatingUsers()
	{
		let result = [];
		for (let userId in this.peers)
		{
			if (this.peers[userId].isParticipating())
			{
				result.push(userId);
			}
		}
		return result;
	};

	#onPeerStateChanged = (e) =>
	{
		this.runCallback(CallEvent.onUserStateChanged, e);

		if (!this.ready)
		{
			return;
		}
		if (e.state === UserState.Failed || e.state === UserState.Unavailable || e.state === UserState.Declined || e.state === UserState.Idle)
		{
			if (this.type == CallType.Instant && !this.isAnyoneParticipating())
			{
				this.hangup();
			}
		}
	};

	#onPeerInviteTimeout = (e) =>
	{
		if (!this.ready)
		{
			return;
		}
		this.signaling.sendUserInviteTimeout({
			userId: this.users,
			failedUserId: e.userId
		})
	};

	#changeRecordState(params)
	{
		if (params.action !== View.RecordState.Started && this.recordState.userId != params.senderId)
		{
			return false;
		}

		if (params.action === View.RecordState.Started)
		{
			if (this.recordState.state !== View.RecordState.Stopped)
			{
				return false;
			}

			this.recordState.state = View.RecordState.Started;
			this.recordState.userId = params.senderId;
			this.recordState.date.start = params.date;
			this.recordState.date.pause = [];
		}
		else if (params.action === View.RecordState.Paused)
		{
			if (this.recordState.state !== View.RecordState.Started)
			{
				return false;
			}

			this.recordState.state = View.RecordState.Paused;
			this.recordState.date.pause.push(
				{start: params.date, finish: null}
			);
		}
		else if (params.action === View.RecordState.Resumed)
		{
			if (this.recordState.state !== View.RecordState.Paused)
			{
				return false;
			}

			this.recordState.state = View.RecordState.Started;
			const pauseElement = this.recordState.date.pause.find(function (element)
			{
				return element.finish === null;
			});
			if (pauseElement)
			{
				pauseElement.finish = params.date;
			}
		}
		else if (params.action === View.RecordState.Stopped)
		{
			this.recordState.state = View.RecordState.Stopped;
			this.recordState.userId = 0;
			this.recordState.date.start = null;
			this.recordState.date.pause = [];
		}

		return true;
	};

	__onPullEvent(command, params, extra)
	{
		if (this.pullEventHandlers[command])
		{
			if (command !== 'Call::ping')
			{
				this.log("Signaling: " + command + "; Parameters: " + JSON.stringify(params));
			}
			this.pullEventHandlers[command].call(this, params);
		}
	};

	#onPullEventAnswer = (params) =>
	{
		const senderId = Number(params.senderId);

		if (senderId == this.userId)
		{
			return this.__onPullEventAnswerSelf(params);
		}

		if (!this.peers[senderId])
		{
			this.peers[senderId] = this.createPeer(senderId);
			this.runCallback(CallEvent.onUserInvited, {
				userId: senderId
			});
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
		this.joinedElsewhere = true;
		this.runCallback(CallEvent.onJoin, {
			local: false
		});
	};

	#onPullEventHangup = (params) =>
	{
		const senderId = params.senderId;
		if (this.userId === senderId && params.callInstanceId && this.instanceId !== params.callInstanceId)
		{
			// Call declined by the same user elsewhere
			this.runCallback(CallEvent.onLeave, {local: false});
			return;
		}

		if (!this.peers[senderId])
		{
			return;
		}

		this.peers[senderId].participant = null;
		this.peers[senderId].setReady(false);

		if (params.code == 603)
		{
			this.peers[senderId].setDeclined(true);
		}
		else if (params.code == 486)
		{
			this.peers[senderId].setBusy(true);
			console.error("user " + senderId + " is busy");
		}

		if (this.ready && this.type == CallType.Instant && !this.isAnyoneParticipating())
		{
			this.hangup();
		}
	};

	#onPullEventUsersJoined = (params) =>
	{
		this.log('__onPullEventUsersJoined', params);
		const users = params.users;

		this.addJoinedUsers(users);
	};

	#onPullEventUsersInvited = (params) =>
	{
		this.log('__onPullEventUsersInvited', params);
		const users = params.users;
		const show = params.show;

		if (this.type === CallType.Instant)
		{
			this.addInvitedUsers(users, show);
		}
	};

	#onPullEventUserInviteTimeout = (params) =>
	{
		this.log('__onPullEventUserInviteTimeout', params);
		const failedUserId = params.failedUserId;

		if (this.peers[failedUserId])
		{
			this.peers[failedUserId].onInviteTimeout(false);
		}
	};

	#onPullEventPing = (params) =>
	{
		if (params.callInstanceId === this.instanceId)
		{
			// ignore self ping
			return;
		}

		const senderId = Number(params.senderId);

		if (senderId == this.userId)
		{
			if (!this.joinedElsewhere)
			{
				this.runCallback(CallEvent.onJoin, {
					local: false
				});
				this.joinedElsewhere = true;
			}
			clearTimeout(this.lastSelfPingReceivedTimeout);
			this.lastSelfPingReceivedTimeout = setTimeout(this.#onNoSelfPingsReceived, pingPeriod * 2.1);
		}
		clearTimeout(this.lastPingReceivedTimeout);
		this.lastPingReceivedTimeout = setTimeout(this.#onNoPingsReceived, pingPeriod * 2.1);
		if (this.peers[senderId])
		{
			this.peers[senderId].setReady(true);
		}
	};

	#onNoPingsReceived = () =>
	{
		if (!this.ready)
		{
			this.destroy();
		}
	};

	#onNoSelfPingsReceived = () =>
	{
		this.runCallback(CallEvent.onLeave, {
			local: false
		});
		this.joinedElsewhere = false;
	};

	#onPullEventFinish = () =>
	{
		this.destroy();
	};

	#onPullEventRepeatAnswer = () =>
	{
		if (this.ready)
		{
			this.signaling.sendAnswer({userId: this.userId}, true);
		}
	}

	#onPullEventSwitchTrackRecordStatus = (e) =>
	{
		this.runCallback(CallEvent.onSwitchTrackRecordStatus, {
			isTrackRecordOn: e.isTrackRecordOn,
			errorCode: e.errorCode,
		});
	}

	#onLocalMediaRendererAdded = (e) =>
	{
		const kind = MediaKinds[e];
		if (!kind)
		{
			this.log(`Wrong kind for local mediaRenderer: ${e}`);
			return;
		}

		this.log('__onLocalMediaRendererAdded', kind);

		switch (e) {
			case MediaStreamsKinds.Camera:
				this.BitrixCall.getLocalVideo()
					.then((track) =>
					{
						if (!this.videoEnabled)
						{
							return;
						}
						const mediaRenderer = new MediaRenderer({
							kind,
							track,
						});
						this.runCallback(CallEvent.onLocalMediaReceived, {
							mediaRenderer,
							tag: 'main',
							stream: mediaRenderer.stream,
						});
					});
				break;
			case MediaStreamsKinds.Screen:
				this.log("Screen shared");
				this.screenShared = true;
				this.waitingLocalScreenShare = false;

				this.BitrixCall.getLocalScreen()
					.then((tracks) =>
					{
						const mediaRenderer = new MediaRenderer({
							kind,
							track: tracks.video,
						});
						this.runCallback(CallEvent.onLocalMediaReceived, {
							mediaRenderer,
							tag: 'screen',
							stream: new MediaStream(),
						});
					});
				break;
			case MediaStreamsKinds.Microphone:
				this.BitrixCall.getLocalAudio()
					.then((track) =>
					{
						this.#setPublishingState(MediaStreamsKinds.Microphone, false);
						this.#onMicAccessResult({
							result: true,
							stream: new MediaStream([track]),
						})
					});
				break;
		}
	};

	#onLocalMediaRendererMuteToggled = (e) =>
	{
		if (e === MediaStreamsKinds.Microphone)
		{
			this.#setPublishingState(MediaStreamsKinds.Microphone, false);
		}
		else if (e === MediaStreamsKinds.Camera)
		{
			this.#setPublishingState(MediaStreamsKinds.Camera, false);
		}
	}

	#onMediaMutedBySystem = (muted) =>
	{
		const microphoneState = muted ? false : !Hardware.isMicrophoneMuted;
		const cameraState = muted ? false : Hardware.isCameraOn;
		this.signaling.sendMicrophoneState(microphoneState);
		this.signaling.sendCameraState(cameraState);
	}

	#onLocalMediaRendererEnded = (e, interrupted) =>
	{
		const kind = MediaKinds[e];
		if (!kind)
		{
			this.log(`Wrong kind for mediaRenderer: ${e}`);
			return;
		}

		if (!this.BitrixCall)
		{
			return;
		}

		switch (e)
		{
			case MediaStreamsKinds.Camera:
			case MediaStreamsKinds.Microphone:
				if (!interrupted)
				{
					this.#onBeforeLocalMediaRendererRemoved(e);
				}
				break;
			case MediaStreamsKinds.Screen:
				this.#onBeforeLocalMediaRendererRemoved(e);
				break;
		}
	}

	#onGetUserMediaStarted = (options) =>
	{
		this.getUserMediaFulfilled = false;

		if (options.video)
		{
			this.signaling.sendCameraState(false);
		}

		if (options.audio)
		{
			this.signaling.sendMicrophoneState(false);
		}
	};

	#onGetUserMediaSuccess = (options) =>
	{
		if (options.video)
		{
			this.signaling.sendCameraState(true);
		}

		if (options.audio)
		{
			this.signaling.sendMicrophoneState(true);
		}
	}

	#onGetUserMediaEnded = () =>
	{
		this.getUserMediaFulfilled = true;
	};

	#onBeforeLocalMediaRendererRemoved = (e) =>
	{
		const kind = MediaKinds[e];
		if (!kind)
		{
			this.log(`Wrong kind for mediaRenderer: ${e}`);
			return;
		}

		if (!this.BitrixCall)
		{
			return;
		}

		this.log("__onBeforeLocalMediaRendererRemoved", kind);

		const mediaRenderer = new MediaRenderer({
			kind,
		});

		switch (e) {
			case MediaStreamsKinds.Camera:
				this.runCallback(CallEvent.onLocalMediaReceived, {
					mediaRenderer,
					tag: 'main',
					stream: new MediaStream(),
					removed: true,
				});
				break;
			case MediaStreamsKinds.Microphone:
				this.#setPublishingState(MediaStreamsKinds.Microphone, false);
				this.signaling.sendMicrophoneState(false);
				break;
			case MediaStreamsKinds.Screen:
				this.BitrixCall.stopScreenShare();
				this.log("Screen is no longer shared");
				this.runCallback(CallEvent.onUserScreenState, {
					userId: this.userId,
					screenState: false,
				});
				this.screenShared = false;
				this.waitingLocalScreenShare = false;
				this.runCallback(CallEvent.onLocalMediaReceived, {
					mediaRenderer,
					tag: 'screen',
					stream: new MediaStream(),
					removed: true,
				});
				break;
		}
	};

	#onRemoteMediaAdded = (p, t) =>
	{
		const kind = MediaKinds[t.source];
		if (!kind)
		{
			this.log(`Wrong kind for mediaRenderer: ${t.source}`);
			return;
		}

		const e = {
			mediaRenderer: new MediaRenderer({
				kind,
				track: t.track
			})
		};

		const peer = this.peers[p.userId];
		if (peer)
		{
			peer.addMediaRenderer(e.mediaRenderer);
			// temporary solution to play new streams
			// todo: need to find what cause the problem itself
			if (!peer.participant)
			{
				peer.participant = p;
				peer.updateCalculatedState();
			}
		}

		switch (t.source)
		{
			case MediaStreamsKinds.Camera:
				this.runCallback(CallEvent.onUserCameraState, {
					userId: p.userId,
					cameraState: !p.isMutedVideo
				});
				break;

			case MediaStreamsKinds.Microphone:
				this.runCallback(CallEvent.onUserMicrophoneState, {
					userId: p.userId,
					microphoneState: !p.isMutedAudio
				});
				break;
		}

		console.log(`[RemoteMediaAdded]: UserId: ${p.userId}, source: ${MediaKinds[t.source]}`)
	};

	#onRemoteMediaRemoved = (p, t) =>
	{
		const kind = MediaKinds[t.source];
		if (!kind)
		{
			this.log(`Wrong kind for mediaRenderer: ${t.source}`);
			return;
		}

		const e = {
			mediaRenderer: new MediaRenderer({
				kind,
				track: t.track
			})
		};

		const peer = this.peers[p.userId];
		if (peer)
		{
			peer.removeMediaRenderer(e.mediaRenderer);
		}

		console.log(`[RemoteMediaRemoved]: UserId: ${p.userId}, source: ${MediaKinds[t.source]}`)
	};

	#onRemoteMediaMuteToggled = (p, t) =>
	{
		if (t.source === MediaStreamsKinds.Microphone)
		{
			this.runCallback(CallEvent.onUserMicrophoneState, {
				userId: p.userId,
				microphoneState: !p.isMutedAudio
			});
		}
	}

	#onParticipantJoined = (p) =>
	{
		const peer = this.peers[p.userId];
		if (peer)
		{
			if (!p.audioEnabled || p.isMutedAudio)
			{
				this.runCallback(CallEvent.onUserMicrophoneState, {
					userId: p.userId,
					microphoneState: false
				});
			}

			if (!p.videoEnabled || p.isMutedVideo)
			{
				this.runCallback(CallEvent.onUserCameraState, {
					userId: p.userId,
					cameraState: false
				});
			}

			if (p.isHandRaised)
			{
				this.runCallback(CallEvent.onUserFloorRequest, {
					userId: p.userId,
					requestActive: p.isHandRaised
				})
			}

			peer.participant = p;
			peer.updateCalculatedState();

			if (this.recordState.state !== View.RecordState.Stopped && this.recordState.userId === this.userId)
			{
				this.signaling.sendRecordState(this.userId, this.recordState);
			}
		}
	};

	#onParticipantLeaved = (p) =>
	{
		const peer = this.peers[p.userId];
		if (peer)
		{
			peer.participant = null;
			for (let type in MediaStreamsKinds)
			{
				const source = MediaStreamsKinds[type];
				const kind = MediaKinds[source];
				const e = {
					mediaRenderer: new MediaRenderer({
						kind,
					}),
				};
				peer.removeMediaRenderer(e.mediaRenderer);
			}
			peer.updateCalculatedState();
		}
	};

	#onMicAccessResult = (e) =>
	{
		if (e.result)
		{
			if (e.stream.getAudioTracks().length > 0)
			{
				if (this.localVAD)
				{
					this.localVAD.destroy();
				}
				this.localVAD = new SimpleVAD({
					mediaStream: e.stream,
					onVoiceStarted: () =>
					{
						if (!Hardware.isMicrophoneMuted)
						{
							this.requestFloor(false);
						}
						this.#onEndpointVoiceStart({userId: this.userId});
					},
					onVoiceStopped: () =>
					{
						this.#onEndpointVoiceEnd({userId: this.userId});
					},
				});

				clearInterval(this.microphoneLevelInterval);
				this.microphoneLevelInterval = setInterval(
					() =>this.microphoneLevel = this.localVAD.currentVolume,
					200
				);
			}
		}
	};

	#onCallReconnecting = () =>
	{
		if (this.reconnectionEventCount++)
		{
			return;
		}

		for (let userId in this.peers)
		{
			if (userId !== this.userId && this.peers.hasOwnProperty(userId) && this.peers[userId].calculatedState === UserState.Connected)
			{
				for (let type in MediaStreamsKinds)
				{
					const source = MediaStreamsKinds[type];
					const kind = MediaKinds[source];
					const e = {
						mediaRenderer: new MediaRenderer({
							kind,
						}),
					};
					this.peers[userId].removeMediaRenderer(e.mediaRenderer);
				}
			}
		}
	}

	#onCallReconnected = () =>
	{
		this.reconnectionEventCount = 0;
		this.log("Call reconnected");
		this.sendTelemetryEvent("reconnect");
		this.localUserState = UserState.Connected;

		this.signaling.sendMicrophoneState(!Hardware.isMicrophoneMuted);
		if (!this.BitrixCall.isAudioPublished())
		{
			this.#setPublishingState(MediaStreamsKinds.Microphone, true);
		}
		this.BitrixCall.enableAudio(Hardware.isMicrophoneMuted);

		this.signaling.sendCameraState(Hardware.isCameraOn);
		if (Hardware.isCameraOn)
		{
			if (!this.BitrixCall.isVideoPublished())
			{
				this.#setPublishingState(MediaStreamsKinds.Camera, true);
			}
			this.BitrixCall.enableVideo();
		}

		if (this.screenShared || this.waitingLocalScreenShare)
		{
			this.BitrixCall.startScreenShare();
		}

		if (this.videoAllowedFrom == UserMnemonic.none)
		{
			this.signaling.sendHideAll();
		}
		else if (Type.isArray(this.videoAllowedFrom))
		{
			this.signaling.sendShowUsers(this.videoAllowedFrom);
		}

		this.BitrixCall.raiseHand(this.floorRequestActive);
	};

	#onCallDisconnected = (e) =>
	{
		let logData = {};

		const evt = e && typeof e === 'object' ? e : {}
		const {headers, leaveInformation} = evt;

		if (headers) {
			logData = {
				...logData,
				headers,
			}
		}

		if (leaveInformation) {
			logData = {
				...logData,
				leaveInformation,
			}
		}

		this.log("__onCallDisconnected", (Object.keys(logData).length ? logData : null));

		if (this.ready && leaveInformation) {
			this.hangup(leaveInformation.code, leaveInformation.reason)
		}

		this.sendTelemetryEvent("disconnect");

		this.localUserState = UserState.Idle;

		this.ready = false;
		this.joinedAsViewer = false;
		this.reinitPeers();

		this.localVideoShown = false;
		this.removeCallEvents();
		this.unsubscribeHardwareChanges();

		this.state = CallState.Proceeding;
		this.runCallback(CallEvent.onLeave, {
			local: true
		});
	};

	#onWindowUnload = () =>
	{
		if (this.ready && this.BitrixCall)
		{
			this.signaling.sendHangup({
				userId: this.users
			});
		}
	};

	#onFatalError = (error) =>
	{
		if (error && error.call)
		{
			delete error.call;
		}
		this.log("onFatalError", error);

		this.ready = false;
		this.localUserState = UserState.Failed;
		this.reinitPeers();

		this.localVideoShown = false;
		if (this.BitrixCall)
		{
			this.removeCallEvents();
			this.unsubscribeHardwareChanges();
			try
			{
				this.BitrixCall.hangup({
					'X-Reason': 'Fatal error',
					'X-Error': typeof (error) === 'string' ? error : error.code || error.name
				})
			} catch (e)
			{
				this.log("Bitrix hangup error: ", e);
				console.error("Bitrix hangup error: ", e);
			}
			this.BitrixCall = null;
		}

		if (typeof (error) === "string")
		{
			this.runCallback(CallEvent.onCallFailure, {
				name: error
			});
		}
		else if (error.name)
		{
			this.runCallback(CallEvent.onCallFailure, error);
		}
	};

	#onCallStatsReceived = (stats) =>
	{
		const usersToSendReports = {};
		// to order local stats by track quality
		const statsIndexByRid = { f: 2, h: 1, q: 0 };

		stats.sender.forEach((report) =>
		{
			if (report.userId && (report.kind === 'video' || report.kind === 'audio'))
			{
				if (!usersToSendReports[report.userId])
				{
					usersToSendReports[report.userId] = {};
				}

				if (report.kind === 'video')
				{
					if (!usersToSendReports[report.userId][report.source])
					{
						usersToSendReports[report.userId][report.source] = [];
					}

					const index = statsIndexByRid[report.rid] || 0;
					usersToSendReports[report.userId][report.source][index] = report;
				}
				else if (report.kind === 'audio')
				{
					usersToSendReports[report.userId][report.source] = report;
				}

			}
		});

		stats.recipient.forEach((report) =>
		{
			if (report.userId && (report.kind === 'video' || report.kind === 'audio'))
			{
				if (!usersToSendReports[report.userId])
				{
					usersToSendReports[report.userId] = {};
				}
				usersToSendReports[report.userId][report.source] = report;
			}
		});

		for (let userId in usersToSendReports)
		{
			this.runCallback(CallEvent.onUserStatsReceived, {
				userId,
				report: usersToSendReports[userId]
			});
		}

		// todo: need to correct stats format
		// if (this.logger)
		// {
		// 	this.logger.sendStat(transformVoxStats(e.stats, this.BitrixCall));
		// }
	}

	#onUpdatePacketLoss = (participants) =>
	{
		const prevPeersWithBadConnection = new Set([...this.peersWithBadConnection.values()]);
		participants.forEach(userId =>
		{
			const peer = this.peers[userId];
			if (peer)
			{
				if (!prevPeersWithBadConnection.has(userId))
				{
					this.peersWithBadConnection.add(userId);
				}
				else
				{
					prevPeersWithBadConnection.delete(userId);
				}
			}
		});
	}

	#onConnectionQualityChanged = participants =>
	{
		Object.keys(participants).forEach(participantId => {
			this.runCallback(
				CallEvent.onConnectionQualityChanged,
				{
					userId: Number(participantId),
					score: participants[participantId],
				}
			);
		})
	}

	#onToggleRemoteParticipantVideo = isVideoShown =>
	{
		this.runCallback(
			CallEvent.onToggleRemoteParticipantVideo,
			{ isVideoShown }
		);
	}

	#onCallMessageReceived = (e) =>
	{
		let message;
		let peer;

		try
		{
			message = JSON.parse(e.text);
		} catch (err)
		{
			this.log("Could not parse scenario message.", err);
			return;
		}

		const eventName = message.eventName;
		if (eventName === clientEvents.cameraState)
		{
			this.runCallback(CallEvent.onUserCameraState, {
				userId: message.senderId,
				cameraState: message.cameraState === "Y"
			});
		}
		else if (eventName === clientEvents.videoPaused)
		{
			if (message.senderId === this.userId)
			{
				return;
			}
			this.runCallback(CallEvent.onUserVideoPaused, {
				userId: message.senderId,
				videoPaused: message.videoPaused === "Y"
			});
		}
		else if (eventName === clientEvents.screenState)
		{
			this.runCallback(CallEvent.onUserScreenState, {
				userId: message.senderId,
				screenState: message.screenState === "Y"
			});
		}
		else if (eventName === clientEvents.recordState)
		{
			this.runCallback(CallEvent.onUserRecordState, {
				userId: message.senderId,
				recordState: message.recordState
			});
		}
		else if (eventName === clientEvents.emotion)
		{
			this.runCallback(CallEvent.onUserEmotion, {
				userId: message.senderId,
				toUserId: message.toUserId,
				emotion: message.emotion
			})
		}
		else if (eventName === clientEvents.customMessage)
		{
			this.runCallback(CallEvent.onCustomMessage, {
				message: message.message
			})
		}
		else if (eventName === scenarioEvents.viewerJoined)
		{
			console.log("viewer " + message.senderId + " joined");
			peer = this.peers[message.senderId];
			if (peer)
			{
				peer.setDirection(EndpointDirection.RecvOnly);
				peer.setReady(true);
			}
		}
		else if (eventName === scenarioEvents.viewerLeft)
		{
			console.log("viewer " + message.senderId + " left");
			peer = this.peers[message.senderId];
			if (peer)
			{
				peer.setReady(false);
			}
		}
		else
		{
			this.log("Unknown scenario event " + eventName);
		}
	};

	#onCallHandRaised = (p) =>
	{
		this.runCallback(CallEvent.onUserFloorRequest, {
			userId: p.userId,
			requestActive: p.isHandRaised
		})
	}

	#onEndpointVoiceStart = (p) =>
	{
		// for local user we need to send extra signal to show unmute hint
		if (p.userId === this.userId)
		{
			this.runCallback(CallEvent.onUserVoiceStarted, {
				userId: p.userId,
				local: true,
			});

			if (Hardware.isMicrophoneMuted)
			{
				return;
			}
		}

		this.runCallback(CallEvent.onUserVoiceStarted, {
			userId: p.userId,
		});
	};

	#onEndpointVoiceEnd = (p) =>
	{
		this.runCallback(CallEvent.onUserVoiceStopped, {
			userId: p.userId,
		});
	}

	sendTelemetryEvent(eventName)
	{
		Util.sendTelemetryEvent({
			call_id: this.id,
			user_id: this.userId,
			kind: "Bitrix",
			event: eventName,
		})
	};

	destroy()
	{
		this.ready = false;
		this.joinedAsViewer = false;
		this.localVideoShown = false;
		if (this.localVAD)
		{
			this.localVAD.destroy();
			this.localVAD = null;
		}
		clearInterval(this.microphoneLevelInterval);
		if (this.BitrixCall)
		{
			this.removeCallEvents();
			this.unsubscribeHardwareChanges();
			this.BitrixCall.hangup();
			this.BitrixCall = null;
		}

		for (let userId in this.peers)
		{
			if (this.peers.hasOwnProperty(userId) && this.peers[userId])
			{
				this.peers[userId].destroy();
			}
		}

		clearTimeout(this.lastPingReceivedTimeout);
		clearTimeout(this.lastSelfPingReceivedTimeout);
		clearInterval(this.pingUsersInterval);
		clearInterval(this.pingBackendInterval);

		window.removeEventListener("unload", this.#onWindowUnload);
		return super.destroy();
	};
}

class Signaling
{
	constructor(params)
	{
		this.call = params.call;
	};

	inviteUsers(data)
	{
		return this.#runRestAction(ajaxActions.invite, data);
	};

	sendAnswer(data, repeated)
	{
		if (repeated && CallEngine.getPullClient().isPublishingEnabled())
		{
			this.#sendPullEvent(pullEvents.answer, data);
		}
		else
		{
			return this.#runRestAction(ajaxActions.answer, data);
		}
	};

	sendHangup(data)
	{
		if (CallEngine.getPullClient().isPublishingEnabled())
		{
			this.#sendPullEvent(pullEvents.hangup, data);
			data.retransmit = false;
			this.#runRestAction(ajaxActions.hangup, data);
		}
		else
		{
			data.retransmit = true;
			this.#runRestAction(ajaxActions.hangup, data);
		}
	};

	sendFinish(data)
	{
		if (CallEngine.getPullClient().isPublishingEnabled())
		{
			this.#sendPullEvent(pullEvents.hangup, data);
			data.retransmit = false;
			this.#runRestAction(ajaxActions.finish, data);
		}
		else
		{
			data.retransmit = true;
			this.#runRestAction(ajaxActions.finish, data);
		}
	};

	sendCameraState(cameraState)
	{
		return this.#sendMessage(clientEvents.cameraState, {
			cameraState: cameraState ? "Y" : "N"
		});
	};

	sendVideoPaused(videoPaused)
	{
		return this.#sendMessage(clientEvents.videoPaused, {
			videoPaused: videoPaused ? "Y" : "N"
		});
	};

	sendMicrophoneState(microphoneState)
	{
		return this.#sendMessage(clientEvents.microphoneState, {
			microphoneState: microphoneState ? "Y" : "N"
		});
	};

	sendScreenState(screenState)
	{
		return this.#sendMessage(clientEvents.screenState, {
			screenState: screenState ? "Y" : "N"
		});
	};

	sendRecordState(userId, recordState)
	{
		this.#sendMessage(clientEvents.recordState, {
			senderId: userId,
			recordState: recordState
		});
	};

	sendCustomMessage(message, repeatOnConnect)
	{
		return this.#sendMessage(clientEvents.customMessage, {
			message: message,
			repeatOnConnect: !!repeatOnConnect
		});
	};

	sendShowUsers(users)
	{
		return this.#sendMessage(clientEvents.showUsers, {
			users: users
		});
	};

	sendShowAll()
	{
		return this.#sendMessage(clientEvents.showAll, {});
	};

	sendHideAll()
	{
		return this.#sendMessage(clientEvents.hideAll, {});
	};

	sendPingToUsers(data)
	{
		if (CallEngine.getPullClient().isPublishingEnabled())
		{
			this.#sendPullEvent(pullEvents.ping, data, 0);
		}
	};

	sendPingToBackend()
	{
		this.#runRestAction(ajaxActions.ping, {retransmit: false});
	};

	sendUserInviteTimeout(data)
	{
		if (CallEngine.getPullClient().isPublishingEnabled())
		{
			this.#sendPullEvent(pullEvents.userInviteTimeout, data, 0);
		}
	};

	#sendPullEvent(eventName, data, expiry)
	{
		expiry = expiry || 5;
		if (!data.userId)
		{
			throw new Error('userId is not found in data');
		}

		if (!Type.isArray(data.userId))
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
		data.requestId = Util.getUuidv4();

		this.call.log('Sending p2p signaling event ' + eventName + '; ' + JSON.stringify(data));
		CallEngine.getPullClient().sendMessage(data.userId, 'im', eventName, data, expiry);
	};

	#sendMessage(eventName, data)
	{
		if (!this.call.BitrixCall)
		{
			return;
		}

		if (!Type.isPlainObject(data))
		{
			data = {};
		}
		data.eventName = eventName;
		data.requestId = Util.getUuidv4();
		data.senderId = this.call.userId;

		this.call.BitrixCall.sendMessage(JSON.stringify(data));
	};

	#runRestAction(signalName, data)
	{
		if (!Type.isPlainObject(data))
		{
			data = {};
		}

		data.callId = this.call.id;
		data.callInstanceId = this.call.instanceId;
		data.requestId = Util.getUuidv4();
		return CallEngine.getRestClient().callMethod(signalName, data);
	};
}

class Peer
{
	calculatedState: string

	constructor(params)
	{
		this.userId = params.userId;
		this.call = params.call;

		this.ready = !!params.ready;
		this.calling = false;
		this.backgroundCalling = false;
		this.declined = false;
		this.busy = false;
		this.inviteTimeout = false;
		this.direction = params.direction || EndpointDirection.SendRecv;

		this.stream = null;
		this.mediaRenderers = [];

		this.isIncomingVideoAllowed = params.isIncomingVideoAllowed !== false;

		this.callingTimeout = 0;

		this.callbacks = {
			onStateChanged: Type.isFunction(params.onStateChanged) ? params.onStateChanged : BX.DoNothing,
			onInviteTimeout: Type.isFunction(params.onInviteTimeout) ? params.onInviteTimeout : BX.DoNothing,
			onMediaReceived: Type.isFunction(params.onMediaReceived) ? params.onMediaReceived : BX.DoNothing,
			onMediaRemoved: Type.isFunction(params.onMediaRemoved) ? params.onMediaRemoved : BX.DoNothing,
		};

		this.calculatedState = this.calculateState();
	};

	setReady(ready)
	{
		ready = !!ready;
		if (this.ready === ready)
		{
			return;
		}
		this.ready = ready;
		if (this.calling || this.backgroundCalling)
		{
			clearTimeout(this.callingTimeout);
			this.calling = false;
			this.backgroundCalling = false;
			this.inviteTimeout = false;
		}
		if (this.ready)
		{
			this.declined = false;
			this.busy = false;
		}

		this.updateCalculatedState();
	}

	setDirection(direction)
	{
		if (this.direction === direction)
		{
			return;
		}
		this.direction = direction;
	}

	setDeclined(declined)
	{
		this.declined = declined;
		if (this.calling || this.backgroundCalling)
		{
			clearTimeout(this.callingTimeout);
			this.calling = false;
			this.backgroundCalling = false;
		}
		if (this.declined)
		{
			this.ready = false;
			this.busy = false;
		}
		this.updateCalculatedState();
	}

	setBusy(busy)
	{
		this.busy = busy;
		if (this.calling || this.backgroundCalling)
		{
			clearTimeout(this.callingTimeout);
			this.calling = false;
			this.backgroundCalling = false;
		}
		if (this.busy)
		{
			this.ready = false;
			this.declined = false;
		}
		this.updateCalculatedState();
	}

	allowIncomingVideo(isIncomingVideoAllowed)
	{
		if (this.isIncomingVideoAllowed == isIncomingVideoAllowed)
		{
			return;
		}

		this.isIncomingVideoAllowed = !!isIncomingVideoAllowed;
	}

	addMediaRenderer(mediaRenderer)
	{
		this.log('Adding media renderer for user' + this.userId, mediaRenderer);

		this.mediaRenderers.push(mediaRenderer);

		this.callbacks.onMediaReceived({
			userId: this.userId,
			kind: mediaRenderer.kind,
			mediaRenderer: mediaRenderer
		});
		this.updateCalculatedState();
	}

	removeMediaRenderer(mediaRenderer)
	{
		this.log('Removing media renderer for user' + this.userId, mediaRenderer);

		let i
		this.mediaRenderers.forEach((el, index) => {
			if (el.kind === mediaRenderer.kind) {
				i = index;
			}
		})

		if (i >= 0)
		{
			this.mediaRenderers.splice(i, 1);
		}
		this.callbacks.onMediaRemoved({
			userId: this.userId,
			kind: mediaRenderer.kind,
			mediaRenderer: mediaRenderer
		});
		this.updateCalculatedState();
	}

	calculateState()
	{
		if (this.participant)
		{
			return UserState.Connected;
		}

		if (this.calling)
		{
			return UserState.Calling;
		}

		if (this.inviteTimeout)
		{
			return UserState.Unavailable;
		}

		if (this.declined)
		{
			return UserState.Declined;
		}

		if (this.busy)
		{
			return UserState.Busy;
		}

		if (this.ready)
		{
			return UserState.Ready;
		}

		return UserState.Idle;
	}

	updateCalculatedState()
	{
		const calculatedState = this.calculateState();

		if (this.calculatedState !== calculatedState)
		{
			this.callbacks.onStateChanged({
				userId: this.userId,
				state: calculatedState,
				previousState: this.calculatedState,
				direction: this.direction,
			});
			this.calculatedState = calculatedState;
		}
	}

	isParticipating()
	{
		return ((this.calling || this.backgroundCalling || this.ready || this.participant) && !this.declined);
	}

	onInvited(showUser = false)
	{
		this.ready = false;
		this.inviteTimeout = false;
		this.declined = false;
		if (showUser)
		{
			this.calling = true;
		}
		else
		{
			this.backgroundCalling = true;
		}

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
		if (!(this.calling || this.backgroundCalling))
		{
			return;
		}
		this.calling = false;
		this.backgroundCalling = false;
		this.inviteTimeout = true;
		if (internal)
		{
			this.callbacks.onInviteTimeout({
				userId: this.userId
			});
		}
		this.updateCalculatedState();
	}

	log()
	{
		this.call.log.apply(this.call, arguments);
	}

	destroy()
	{
		if (this.stream)
		{
			Util.stopMediaStream(this.stream);
			this.stream = null;
		}

		this.callbacks.onStateChanged = BX.DoNothing;
		this.callbacks.onInviteTimeout = BX.DoNothing;
		this.callbacks.onMediaReceived = BX.DoNothing;
		this.callbacks.onMediaRemoved = BX.DoNothing;

		clearTimeout(this.callingTimeout);
		this.callingTimeout = null;
	}
}

const transformVoxStats = function (s, BitrixCall)
{
	let result = {
		connection: s.connection,
		outboundAudio: [],
		outboundVideo: [],
		inboundAudio: [],
		inboundVideo: [],
	}

	let endpoints = {};
	if (BitrixCall.getEndpoints)
	{
		BitrixCall.getEndpoints().forEach(endpoint => endpoints[endpoint.id] = endpoint)
	}

	if (!result.connection.timestamp)
	{
		result.connection.timestamp = Date.now();
	}
	for (let trackId in s.outbound)
	{
		if (!s.outbound.hasOwnProperty(trackId))
		{
			continue;
		}
		const statGroup = s.outbound[trackId];
		for (let i = 0; i < statGroup.length; i++)
		{
			let stat = statGroup[i];
			stat.trackId = trackId;
			if ('audioLevel' in stat)
			{
				result.outboundAudio.push(stat)
			}
			else
			{
				result.outboundVideo.push(stat)
			}
		}
	}
	for (let trackId in s.inbound)
	{
		if (!s.inbound.hasOwnProperty(trackId))
		{
			continue;
		}
		let stat = s.inbound[trackId];
		if (!('endpoint' in stat))
		{
			continue;
		}
		stat.trackId = trackId;
		if ('audioLevel' in stat)
		{
			result.inboundAudio.push(stat)
		}
		else
		{
			if (endpoints[stat.endpoint])
			{
				let videoRenderer = endpoints[stat.endpoint].mediaRenderers.find(r => r.id == stat.trackId)
				if (videoRenderer && videoRenderer.element)
				{
					stat.actualHeight = videoRenderer.element.videoHeight;
					stat.actualWidth = videoRenderer.element.videoWidth;
				}
			}

			result.inboundVideo.push(stat)
		}
	}
	return result;

}

class MediaRenderer
{
	element = null;

	constructor(params)
	{
		params = params || {};
		this.kind = params.kind || 'video';
		if (params.track)
		{
			this.stream = new MediaStream([params.track]);
		}
		else
		{
			this.stream = new MediaStream();
		}
	}

	render(el)
	{
		if (!el.srcObject || !el.srcObject.active || el.srcObject.id !== this.stream.id)
		{
			el.srcObject = this.stream;
		}
	}

	requestVideoSize()
	{
	}
}

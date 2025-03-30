import Util from './util';

export const MediaStreamsKinds = {
	Camera: 1,
	Microphone: 2,
	Screen: 3,
	ScreenAudio: 4,
}

class Track {
	id = null;
	source = '';
	track = {};

	constructor(id, track) {
		this.id = id;
		this.source = track.source;
		this.track = track
	}
}

class Message {
	text
	from
	timestamp

	constructor(message) {
		this.text = message.message
		this.from = message.senderSid
		this.timestamp = Math.floor(Date.now() / 1000)
	}
}

const STREAM_QUALITY = {
	HIGH: 2,
	MEDIUM: 1,
	LOW: 0,
}

export const CALL_STATE = {
	CONNECTED: 'Connected',
	PROGRESSING: 'Progressing',
	TERMINATED: 'Terminated',
}

const VIDEO_QUEUE = {
	INITIAL: '',
	ENABLE: 'enable',
	DISABLE: 'disable',
};

const LOG_LEVEL = {
	INFO: 'INFO',
	WARNING: 'WARNING',
	ERROR: 'ERROR',
};

const MONITORING_EVENTS_NAME_LIST = {
	TRACK_SUBSCRIPTION_FAILED: 3,
	TRACK_SUBSCRIPTION_DELAY: 4,
	HIGH_PACKET_LOSS_SEND: 7,
	HIGH_PACKET_LOSS_RECEIVE: 8,
	CPU_ISSUES: 9,
	NETWORK_ISSUES: 10,
	PEER_CONNECTION_REFUSED: 11,
	PEER_CONNECTION_ISSUES: 12,
	USER_CAMERA_DISABLED: 14,
	USER_RECONNECTED: 15,
	LOCAL_VIDEO_STREAM_RECEIVING_FAILED: 18,
	LOCAL_MICROPHONE_STREAM_RECEIVING_FAILED: 19,
	LOCAL_SCREEN_STREAM_RECEIVING_FAILED: 20,
	LOCAL_VIDEO_STREAM_PUBLICATION_FAILED: 21,
	LOCAL_MICROPHONE_STREAM_PUBLICATION_FAILED: 22,
	LOCAL_SCREEN_STREAM_PUBLICATION_FAILED: 23,
};

const MONITORING_EVENTS_LIST = {
	metrics: {
		PACKET_LOST_SEND: [],
		PACKET_LOST_RECEIVE: [],
		COUNT_TRACKS: {video: 0, audio: 0},
		BITRATE_IN: [],
		BITRATE_OUT: [],
		CONN_SCORE_CURRENT: [],
		JITTER: [],
		TOTAL_FREEZE_DURATION: [],
		FREEZE_COUNT: [],
		FRAMES_DECODED: [],
		FRAMES_DROPPED: [],
		FRAMES_RECEIVED: [],
		FRAMES_LOSS: [],
	},
	startTimestamp: 0,
	events: []
};

const ReconnectionReason = {
	NetworkError: 'NetworkError',
	PingPongMissed: 'PingPongMissed',
	PeerConnectionFailed: 'PeerConnectionFailed',
	WsTransportClosed: 'WsTransportClosed',
	LeaveCommand: 'LeaveCommand',
	JoinResponseError: 'JoinResponseError',
};

const ErrorPreventingReconnection = {
	InputError: 2,
	AccessDenied: 3,
	RoomNotFound: 5,
	MalfunctioningSignaling: 7,
};

export class Call {
	sender = null;
	recipient = null;
	#privateProperties = {
		clientVersion: '1.0.0',
		clientPlatform: 'web',
		codec: 'vp8',
		canReconnect: true,
		logs: {},
		isloggingEnable: true,
		loggerCallback: null,
		abortController: new AbortController(),
		isWaitAnswer: false,
		reportsForIncomingTracks: {},
		reportsForOutgoingTracks: {},
		prevParticipantsWithLargeDataLoss: new Set(),
		tracksDataFromSocket: {},
		realTracksIds: {}, // todo: check why track ids are different in a stream and in the track itself
		url: null,
		roomId: null,
		token: null,
		endpoint: null,
		jwt: null,
		options: null,
		iceServers: null,
		socketConnect: null,
		peerConnectionFailed: false,
		pendingCandidates: {
			recipient: [],
			sender: [],
		},
		pendingPublications: {},
		pendingSubscriptions: {},
		publicationTimeout: 10000,
		subscriptionTimeout: 1500,
		subscriptionTries: 5,
		cameraStream: null,
		microphoneStream: null,
		screenStream: null,
		mediaMutedBySystem: false,
		needToEnableAudioAfterSystemMuted: false,
		needToDisableAudioAfterPublish: false,
		localTracks: {},
		localConnectionQuality: 0,
		minimalConnectionQuality: 2,
		rtt: 0,
		pingIntervalDuration: 0,
		pingTimeoutDuration: 0,
		ontrackData: {},
		remoteTracks: {},
		remoteParticipants: {},
		mainStream: {},
		pingPongTimeout: null,
		pingPongInterval: null,
		userId: '',
		localParticipantSid: '',
		defaultVideoResolution: {
			width: 1280,
			height: 720
		},
		defaultSimulcastBitrate: {
			q: 120000,
			h: 300000,
			f: 1000000
		},
		defaultRemoteStreamsQuality: STREAM_QUALITY.MEDIUM,
		audioBitrate: 70000,
		videoBitrate: 1500000,
		screenBitrate: 1500000,
		videoSimulcast: true,
		screenSimulcast: false,
		events: new Map(),
		offersStack: 0,
		audioDeviceId: '',
		switchActiveAudioDeviceInProgress: null,
		switchActiveAudioDevicePending: null,
		videoDeviceId: '',
		switchActiveVideoDeviceInProgress: null,
		switchActiveVideoDevicePending: null,
		isReconnecting: false,
		reconnectionAttempt: 0,
		reconnectionTimeout: null,
		lastReconnectionReason: null,
		fastReconnectionDelay: 1000,
		reconnectionDelay: 5000,
		callStatsInterval: null,
		callState: '',
		wasConnected: false,
		packetLostThreshold: 7,
		statsTimeout: 3000,
		videoQueue: VIDEO_QUEUE.INITIAL,
		videoStreamSetupErrorList: {},
	}

	constructor() {
		this.sendLeaveBound = this.#sendLeave.bind(this);
		this.beforeDisconnectBound = this.#beforeDisconnect.bind(this);

		this.monitoringEvents = [];
		this.monitoringDelayTime = 60000;
		this.monitoringIntervalTime = 10000;
		this.countMetricsInMetricsInterval = this.monitoringDelayTime / this.#privateProperties.statsTimeout;
		this.monitoringInterval = null;
		this.currentMonitoringEventsObject = JSON.parse(JSON.stringify(MONITORING_EVENTS_LIST));
		this.startTimestampGrabMetrics = null;

		this.prevInboundRtpStatsSum = {
			freezeCount: 0,
			totalFreezesDuration: 0,
			jitter: 0,
			framesDecoded: 0,
			framesDropped: 0,
			framesReceived: 0,
		};
	}

	get isMediaMutedBySystem()
	{
		return this.#privateProperties.mediaMutedBySystem;
	}

	checkQosFeatureAndExecutionCallback(callback)
	{
		if (typeof callback === 'function' && Util.isNewQOSEnabled())
		{
			callback();
		}
	}

	clearMonitoringEvents()
	{
		this.monitoringEvents = [];
	}

	addMonitoringEvents({ name, value = undefined, withCounter = false })
	{
		const currentEventIndex = this.currentMonitoringEventsObject.events.findIndex(evt => evt.name === name);
		const lastTimestamp = Date.now();

		if (withCounter && currentEventIndex !== -1)
		{
			this.currentMonitoringEventsObject.events[currentEventIndex].lastTimestamp = lastTimestamp;
			this.currentMonitoringEventsObject.events[currentEventIndex].value += 1;
			return;
		}

		if (withCounter && currentEventIndex === -1)
		{
			this.currentMonitoringEventsObject.events.push({name, value: 1, lastTimestamp})
			return;
		}

		this.currentMonitoringEventsObject.events.push({name, value, lastTimestamp});
	}

	setClearValuesForCurrentMonitoringEventsObject()
	{
		this.currentMonitoringEventsObject = JSON.parse(JSON.stringify(MONITORING_EVENTS_LIST));
	}

	getCountRemoteTracks()
	{
		let countVideoTracks = 0;
		let countAudioTracks = 0;

		Object.values(this.#privateProperties.remoteParticipants).forEach(participant =>
		{
			if (participant.getTrack(MediaStreamsKinds.Camera) && !participant.isMutedVideo && !participant.isLocalVideoMute)
			{
				countVideoTracks += 1;
			}

			if (participant.getTrack(MediaStreamsKinds.Microphone) && !participant.isMutedAudio)
			{
				countAudioTracks += 1;
			}

			if (participant.getTrack(MediaStreamsKinds.Screen))
			{
				countVideoTracks += 1;
			}

			if (participant.getTrack(MediaStreamsKinds.ScreenAudio))
			{
				countAudioTracks += 1;
			}
		});

		return {
			countVideoTracks,
			countAudioTracks,
		}
	}

	startAggregateMonitoringEvents()
	{
		this.startTimestampGrabMetrics = Date.now();
		this.currentMonitoringEventsObject.startTimestamp = this.startTimestampGrabMetrics;

		this.monitoringInterval = setInterval(() => {
			const { countVideoTracks, countAudioTracks } = this.getCountRemoteTracks();

			this.currentMonitoringEventsObject.metrics.COUNT_TRACKS = {
				video: countVideoTracks,
				audio: countAudioTracks,
			};
		}, this.monitoringIntervalTime);

		this.monitoringTimeout = setTimeout(() => {
			clearTimeout(this.monitoringTimeout);
			this.monitoringTimeout = null;

			if (this.#privateProperties.isReconnecting)
			{
				return;
			}

			this.sendMonitoringEvents();
		}, this.monitoringDelayTime);

	}

	sendMonitoringEvents(withRestart = true)
	{
		const eventsToSend = [];
		const eventsToLog = [];

		this.currentMonitoringEventsObject.events.forEach(evt =>
		{
			let eventName = '';
			const eventList = Object.entries(MONITORING_EVENTS_NAME_LIST);
			for (let i = 0; i < eventList.length; i++)
			{
				if (eventList[i][1] === evt.name)
				{
					eventName = eventList[i][0];
					break;
				}
			}

			const eventData = {
				mediaServerId: 0, // TODO: should be replaced with real value for large rooms; 0 - only for small rooms
				message: '',
				value: JSON.stringify(evt.value),
				eventTime: Math.round(evt.lastTimestamp / 1000)
			};

			eventsToSend.push({
				eventTypeId: evt.name,
				...eventData,
			});

			eventsToLog.push({
				eventTypeId: eventName,
				...eventData,
			});
		});

		const objForSend = {
			audioSubscribed: this.currentMonitoringEventsObject.metrics.COUNT_TRACKS.audio,
			videoSubscribed: this.currentMonitoringEventsObject.metrics.COUNT_TRACKS.video,
			packetLoss: this.currentMonitoringEventsObject.metrics.PACKET_LOST_RECEIVE,
			bitrateIn: this.currentMonitoringEventsObject.metrics.BITRATE_IN,
			bitrateOut: this.currentMonitoringEventsObject.metrics.BITRATE_OUT,
			connScore:  this.currentMonitoringEventsObject.metrics.CONN_SCORE_CURRENT,
			framesLoss: this.currentMonitoringEventsObject.metrics.FRAMES_LOSS,
			freezeCount: this.currentMonitoringEventsObject.metrics.FREEZE_COUNT,
			totalFreezesDuration: this.currentMonitoringEventsObject.metrics.TOTAL_FREEZE_DURATION,
			jitter: this.currentMonitoringEventsObject.metrics.JITTER,
			events: eventsToSend,
		};

		objForSend.events = eventsToSend;

		this.#sendSignal({ sendQoS: objForSend });

		clearInterval(this.monitoringInterval);
		this.monitoringInterval = null;
		clearTimeout(this.monitoringTimeout);
		this.monitoringTimeout = null;
		this.setClearValuesForCurrentMonitoringEventsObject();

		if (withRestart)
		{
			this.startAggregateMonitoringEvents();
		}
	}

	onPublishFailed(kind)
	{
		this.triggerEvents('PublishFailed', [kind]);
		let eventName;
		if (kind === MediaStreamsKinds.Camera)
		{
			eventName = MONITORING_EVENTS_NAME_LIST.LOCAL_VIDEO_STREAM_PUBLICATION_FAILED;
		}
		else if (kind === MediaStreamsKinds.Microphone)
		{
			eventName = MONITORING_EVENTS_NAME_LIST.LOCAL_MICROPHONE_STREAM_PUBLICATION_FAILED;
		}
		else if (kind === MediaStreamsKinds.Screen)
		{
			eventName = MONITORING_EVENTS_NAME_LIST.LOCAL_SCREEN_STREAM_PUBLICATION_FAILED;
		}

		this.checkQosFeatureAndExecutionCallback(() =>
		{
			this.addMonitoringEvents({
				name: eventName,
				withCounter: true,
			});
		});
	}

	async connect(options) {
		this.setLog(`Connecting to the call (desktop: ${Util.isDesktop()})`, LOG_LEVEL.INFO);
		this.#privateProperties.callState = CALL_STATE.PROGRESSING

		for (let key in options) {
			this.#privateProperties[`${key}`] = options[key]
		}

		if (!this.#privateProperties.endpoint) {
			this.setLog(`Missing required param 'endpoint' from backend, disconnecting`, LOG_LEVEL.ERROR);
			this.triggerEvents('Failed', [{name: 'AUTHORIZE_ERROR', message: `Missing required param 'endpoint'`}]);
			return;
		}
		if (!this.#privateProperties.jwt) {
			this.setLog(`Missing required param 'jwt' from backend, disconnecting`, LOG_LEVEL.ERROR);
			this.triggerEvents('Failed', [{name: 'AUTHORIZE_ERROR', message: `Missing required param 'jwt'`}]);
			return;
		}

		this.#privateProperties.endpoint = this.#privateProperties.endpoint.replace(/\/+$/, '');

		try
		{
			const mediaServerInfo = await this.getMediaServerInfo();
			this.#privateProperties.url = mediaServerInfo.url;
			this.#privateProperties.token = mediaServerInfo.token;
			this.#privateProperties.data = mediaServerInfo.data;
		}
		catch (error)
		{
			if (error.name !== 'AbortError' && !this.#privateProperties.abortController.signal.aborted)
			{
				this.#reconnect();
			}
			else if (this.#privateProperties.abortController.signal.aborted)
			{
				this.triggerEvents('Failed', [error]);
			}
			return;
		}

		if (this.#privateProperties.abortController.signal.aborted)
		{
			this.#beforeDisconnect();
			return;
		}
		this.#privateProperties.abortController.signal.addEventListener('abort', this.beforeDisconnectBound);

		this.#privateProperties.socketConnect = new WebSocket(`${this.#privateProperties.url}?access_token=${this.#privateProperties.token}&auto_subscribe=1&sdk=js&version=1.6.7&protocol=8&roomData=${this.#privateProperties.data}`);
		this.#privateProperties.socketConnect.onmessage = (e) => this.socketOnMessageHandler(e);
		this.#privateProperties.socketConnect.onopen = () => this.socketOnOpenHandler();
		this.#privateProperties.socketConnect.onerror = () => this.socketOnErrorHandler();
		this.#privateProperties.socketConnect.onclose = (e) => this.socketOnCloseHandler(e);
	};

	#reconnect() {
		this.#privateProperties.isReconnecting = true;
		this.#privateProperties.videoQueue = VIDEO_QUEUE.INITIAL;

		const reconnect = () =>
		{
			const reconnectionDelay = this.#privateProperties.lastReconnectionReason !== ReconnectionReason.JoinResponseError
				? this.#privateProperties.fastReconnectionDelay
				: this.#privateProperties.reconnectionDelay;
			this.setLog(`Reconnecting attempt: ${++this.#privateProperties.reconnectionAttempt}`, LOG_LEVEL.WARNING);
			this.#privateProperties.reconnectionTimeout = setTimeout(this.connect.bind(this), reconnectionDelay);
		};

		reconnect();
		this.checkQosFeatureAndExecutionCallback(() =>
		{
			this.addMonitoringEvents({
				name: MONITORING_EVENTS_NAME_LIST.USER_RECONNECTED,
				withCounter: true,
			});
		});
		this.triggerEvents('Reconnecting');
	};

	#beforeDisconnect() {
		window.removeEventListener('unload', this.sendLeaveBound);
		this.#clearPingInterval();
		this.#clearPingTimeout();
		clearInterval(this.#privateProperties.callStatsInterval);

		this.#privateProperties.localTracks = {};
		this.#privateProperties.isWaitAnswer = false;

		if (this.#privateProperties.socketConnect)
		{
			this.#privateProperties.socketConnect.onmessage = null;
			this.#privateProperties.socketConnect.onopen = null;
			this.#privateProperties.socketConnect.onerror = null;
			this.#privateProperties.socketConnect.onclose = null;
			this.#privateProperties.socketConnect.close();
			this.#privateProperties.socketConnect = null;
		}
	}

	getMediaServerInfo() {
		return new Promise(async (resolve, reject) => {
			let url = `${this.#privateProperties.endpoint}/join`;
				url += `?token=${this.#privateProperties.jwt}`;
				url += `&clientVersion=${this.#privateProperties.clientVersion}`;
				url += `&clientPlatform=${this.#privateProperties.clientPlatform}`;
			let response;
			let data;

			try
			{
				response = await fetch(url, {
					method: 'GET',
					signal: this.#privateProperties.abortController.signal,
				});
				if (!response.ok)
				{
					throw new Error(`Got response code ${response.status}`);
				}
			}
			catch (error)
			{
				if (error.name === 'AbortError')
				{
					reject(error);
				}
				else
				{
					this.#privateProperties.lastReconnectionReason = ReconnectionReason.NetworkError;
					reject({name: 'MEDIASERVER_UNREACHABLE', message: error.message});
				}
				return;
			}

			try
			{
				data = await response.json();
			}
			catch (error)
			{
				this.#privateProperties.lastReconnectionReason = ReconnectionReason.JoinResponseError;
				reject({name: 'MEDIASERVER_UNEXPECTED_ANSWER', message: error.message});
				return;
			}

			if (data.result?.mediaServerUrl && data.result?.tokenToAccessMediaServer && data.result?.roomData)
			{
				resolve({
					url: data.result.mediaServerUrl,
					token: data.result.tokenToAccessMediaServer,
					data: data.result.roomData,
				});
			}
			else
			{
				if (
					data?.error?.code === ErrorPreventingReconnection.InputError
					|| data?.error?.code === ErrorPreventingReconnection.AccessDenied
					|| data?.error?.code === ErrorPreventingReconnection.RoomNotFound
					|| data?.error?.code === ErrorPreventingReconnection.MalfunctioningSignaling
				)
				{
					this.#privateProperties.abortController.abort();
					reject(data.error);
				}
				else
				{
					this.#privateProperties.lastReconnectionReason = ReconnectionReason.JoinResponseError;
					reject({name: 'MEDIASERVER_MISSING_PARAMS', message: `Incorrect signaling response`});
				}
			}
		});
	}
	async sendOffer() {
		if (this.#privateProperties.offersStack > 0 && !this.#privateProperties.isWaitAnswer) {
			this.setLog('Start sending an offer', LOG_LEVEL.INFO);
			this.#privateProperties.isWaitAnswer = true;
			this.#privateProperties.offersStack--;

			try {
				let offer = await this.sender.createOffer();
				if (Util.useTcpSdp())
				{
					offer = {
						type: offer.type,
						sdp: this.#removeUdpFromSdp(offer.sdp),
					};
				}

				await this.sender.setLocalDescription(offer);
				this.#sendSignal({ offer });
				this.setLog('Sending an offer succeeded', LOG_LEVEL.INFO);
			}
			catch (e)
			{
				this.setLog(`Sending an offer failed: ${e}`, LOG_LEVEL.ERROR);
				this.#beforeDisconnect();
				this.#reconnect();
			}
		}
	}

	async startStream() {
		const videoTrack = await this.getLocalVideo();
		if (videoTrack) {
			await this.publishTrack(MediaStreamsKinds.Camera, videoTrack);
		} else {
			this.#releaseStream(MediaStreamsKinds.Camera);
			this.onPublishFailed(MediaStreamsKinds.Camera);
		}

		const audioTrack = await this.getLocalAudio();
		if (audioTrack) {
			await this.publishTrack(MediaStreamsKinds.Microphone, audioTrack);
		} else {
			this.#releaseStream(MediaStreamsKinds.Microphone);
			this.onPublishFailed(MediaStreamsKinds.Microphone);
		}
	}

	async socketOnMessageHandler(event) {
		if (typeof event.data !== 'string') return;

		let data

		try
		{
			data = JSON.parse(event.data);
		}
		catch (err)
		{
			this.setLog(`Could not parse a socket message: ${event.data}`, LOG_LEVEL.WARNING);
			return;
		}

		if (data?.answer){
			await this.#answerHandler(data);
		} else if (data?.offer) {
			await this.#offerHandler(data);
		} else if (data?.joinResponse) {
			this.#privateProperties.abortController.signal.removeEventListener('abort', this.beforeDisconnectBound);

			this.#privateProperties.iceServers = data.joinResponse.iceServers;
			this.#privateProperties.localParticipantSid = data.joinResponse.localParticipant.sid;
			this.#createPeerConnection()

			const connectedEvent = this.#privateProperties.isReconnecting && this.wasConnected
				? 'Reconnected'
				: 'Connected';

			this.#privateProperties.callState = CALL_STATE.CONNECTED;
			this.wasConnected = true;
			this.setLog(`${connectedEvent} to the call ${this.#privateProperties.roomId} on the mediaserver after ${this.#privateProperties.reconnectionAttempt} attempts`, LOG_LEVEL.INFO);
			this.#privateProperties.isReconnecting = false;
			this.#privateProperties.reconnectionAttempt = 0;
			this.triggerEvents(connectedEvent);

			this.checkQosFeatureAndExecutionCallback(() =>
			{
				if (connectedEvent === 'Reconnected' && this.monitoringEvents.length)
				{
					this.sendMonitoringEvents();
				}
			});

			const participantsToDelete = {...this.#privateProperties.remoteParticipants};
			Object.values(data.joinResponse.otherParticipants).forEach( p => {
				if (participantsToDelete[p.userId])
				{
					delete participantsToDelete[p.userId];
				}
				this.setLog(`Adding an early connected participant with id ${p.userId} (sid: ${p.sid})`, LOG_LEVEL.INFO);
				this.#setRemoteParticipant(p);
			})

			for (let userId in participantsToDelete)
			{
				const participant = this.#privateProperties.remoteParticipants[userId];
				this.setLog(`Deleting a missing participant with id ${participant.userId} (sid: ${participant.sid})`, LOG_LEVEL.INFO);
				this.triggerEvents('ParticipantLeaved', [participant]);
				delete this.#privateProperties.remoteTracks[userId];
				delete this.#privateProperties.remoteParticipants[userId];
			}

			this.#privateProperties.pingIntervalDuration = data.joinResponse.pingInterval * 1000
			this.#privateProperties.pingTimeoutDuration = this.#privateProperties.pingIntervalDuration * 2
			this.#startPingInterval()
		} else if (data?.participantJoined) {
			this.setLog(`Adding a new participant with id ${data.participantJoined.participant.userId} (sid: ${data.participantJoined.participant.sid})`, LOG_LEVEL.INFO);
			this.#setRemoteParticipant(data.participantJoined.participant);
		} else if (data?.participantLeft) {
			const participantId = data.participantLeft.userId;
			const participant = this.#privateProperties.remoteParticipants[participantId];
			const pendingSubscriptions = this.#privateProperties.pendingSubscriptions[participantId];

			if (pendingSubscriptions)
			{
				for (let trackId in pendingSubscriptions)
				{
					clearTimeout(pendingSubscriptions[trackId].timeout);
					this.setLog(`A participant with id ${participantId} (sid: ${data.participantLeft.sid}) left during subscription attempt, cancel it`, LOG_LEVEL.WARNING);
				}

				delete this.#privateProperties.pendingSubscriptions[participantId];
			}

			if (participant)
			{
				this.setLog(`Deleting a participant with id ${participant.userId} (sid: ${participant.sid})`, LOG_LEVEL.INFO);
				this.triggerEvents('ParticipantLeaved', [participant]);
				delete this.#privateProperties.remoteTracks[participantId];
				delete this.#privateProperties.remoteParticipants[participantId];
			}
			else
			{
				this.setLog(`Got participantLeft signal for a non-existent participant with id ${participantId} (sid: ${data.participantLeft.sid})`, LOG_LEVEL.WARNING);
			}
		} else if (data?.trackCreated) {
			const participantId = data.trackCreated.userId;
			const cid = data.trackCreated.cid;
			const track = data.trackCreated.track;
			const trackId = track.sid;
			track.userId = participantId
			if (participantId == this.#privateProperties.userId) {
				const timeout = this.#privateProperties.pendingPublications[cid];
				if (timeout)
				{
					clearTimeout(this.#privateProperties.pendingPublications[cid]);
					delete this.#privateProperties.pendingPublications[cid];

					this.setLog(`Publishing a local track with kind ${track.source} (sid: ${trackId}) succeeded`, LOG_LEVEL.INFO);
					this.#privateProperties.localTracks[track.source] = track
					this.triggerEvents('PublishSucceed', [track.source]);
					if (track.source === MediaStreamsKinds.Microphone && this.#privateProperties.needToDisableAudioAfterPublish)
					{
						this.#privateProperties.needToDisableAudioAfterPublish = false;
						this.disableAudio();
					}
					else if (track.source === MediaStreamsKinds.Camera && this.#privateProperties.videoQueue)
					{
						this.#processVideoQueue();
					}
					return;
				}

				this.setLog(`Got trackCreated signal for a non-existent local track with kind ${track.source} (sid: ${trackId})`, LOG_LEVEL.WARNING);
			} else {
				this.#privateProperties.tracksDataFromSocket[trackId] = track;
				const participant = this.#privateProperties.remoteParticipants[participantId];
				if (participant)
				{
					this.setLog(`Got a track info with kind ${track.source} (sid: ${data.trackCreated.track.sid}) for a participant with id ${participantId} (sid: ${participant.sid}), waiting for it`, LOG_LEVEL.INFO);
					switch (track.source)
					{
						case MediaStreamsKinds.Camera:
							participant.videoEnabled = true;
							break;
						case MediaStreamsKinds.Microphone:
							participant.audioEnabled = true;
							break;
						case MediaStreamsKinds.Screen:
							participant.screenSharingEnabled = true;
							break;
					}

					const ontrackData = this.#privateProperties.ontrackData[trackId];
					delete this.#privateProperties.ontrackData[trackId];

					if (ontrackData)
					{
						this.#createRemoteTrack(trackId, ontrackData);
					}
					else
					{
						this.#addPendingSubscription(participant, track);
					}
				}
				else
				{
					this.setLog(`Got a track info with kind ${track.source} (sid: ${data.trackCreated.track.sid}) for a non-existent participant with id ${participantId}`, LOG_LEVEL.WARNING);
				}
			}
		} else if (data?.trackDeleted) {
			try
			{
				const participantId = data.trackDeleted.publisher;
				const trackId = data.trackDeleted.shortId;
				const pendingSubscription = this.#privateProperties.pendingSubscriptions[participantId]?.[trackId];

				if (pendingSubscription)
				{
					clearTimeout(pendingSubscription.timeout);
					delete this.#privateProperties.pendingSubscriptions[participantId][trackId];
					this.setLog(`Track with id ${trackId} was deleted during subscription attempt, cancel it`, LOG_LEVEL.WARNING);
				}

				if (participantId == this.#privateProperties.userId) return;
				this.setLog(`Start deleting a track with id ${trackId} from a participant with id ${participantId} `, LOG_LEVEL.INFO);
				const participant = this.#privateProperties.remoteParticipants[participantId];
				if (!participant)
				{
					this.setLog(`Deleting a track with id ${trackId} failed: can't find a participant with id ${participantId}`, LOG_LEVEL.WARNING);
					return
				}
				const track = Object.values(participant.tracks)?.find(track => track?.id === trackId);
				delete this.#privateProperties.tracksDataFromSocket[trackId];

				if (track)
				{
					if (track.source === MediaStreamsKinds.Microphone)
					{
						participant.audioEnabled = false;
					}
					else if (track.source === MediaStreamsKinds.Camera)
					{
						participant.videoEnabled = false;
					}
					else if (track.source === MediaStreamsKinds.Screen)
					{
						participant.screenSharingEnabled = false;
					}
					participant.removeTrack(track.source);
					this.setLog(`Deleting a track with id ${trackId} from a participant with id ${participantId} (sid: ${participant.sid}) succeeded`, LOG_LEVEL.INFO);
					this.triggerEvents('RemoteMediaRemoved', [participant, track]);
				}
				else
				{
					this.setLog(`Deleting a track with id ${trackId} from a participant with id ${participantId} (sid: ${participant.sid}) failed: can't find a track`, LOG_LEVEL.WARNING);
				}
			}
			catch (e)
			{
				this.setLog(`Deleting a track with id ${trackId} from a participant with id ${participantId} failed: ${e}`, LOG_LEVEL.ERROR);
			}
		} else if (data?.trackMuted) {
			const participant = this.#privateProperties.remoteParticipants[data.trackMuted.track.publisher];
			const trackId = data.trackMuted.track.shortId;
			if (data.trackMuted.track.publisher == this.#privateProperties.userId)
			{
				const track = Object.values(this.#privateProperties.localTracks)?.find(track => track?.sid === trackId);
				if (track)
				{
					if (track.source === MediaStreamsKinds.Camera)
					{
						if (!data.trackMuted.muted && track.muted && !this.#privateProperties.mediaMutedBySystem)
						{
							this.triggerEvents('PublishSucceed', [track.source]);
						}
						else if (!this.#privateProperties.mediaMutedBySystem)
						{
							this.triggerEvents('PublishPaused', [track.source]);
						}

						if (this.#privateProperties.videoQueue)
						{
							this.#processVideoQueue();
						}
					}
					else if(track.source === MediaStreamsKinds.Microphone)
					{
						this.triggerEvents('PublishPaused', [track.source, data.trackMuted.muted]);
					}
				}
				return;
			}

			if (!participant) {
				if (data.trackMuted.track.publisher != this.#privateProperties.userId)
				{
					this.setLog(`Got mute signal (${data.trackMuted.muted}) for a non-existent participant with id ${data.trackMuted.track.publisher}`, LOG_LEVEL.WARNING);
				}
				return;
			}

			const track = Object.values(participant.tracks)?.find(track => track?.id === trackId);
			const awaitedTrack = this.#privateProperties.tracksDataFromSocket[trackId];
			if (awaitedTrack && !track)
			{
				this.#privateProperties.tracksDataFromSocket[trackId].muted = data.trackMuted.muted;
				this.setLog(`Got mute signal (${data.trackMuted.muted}) for a non-received track with id ${trackId}`, LOG_LEVEL.WARNING);
				return;
			}
			else if (!track)
			{
				this.setLog(`Got mute signal (${data.trackMuted.muted}) for a non-existent track with id ${trackId}`, LOG_LEVEL.WARNING);
				return;
			}

			if (track.source === MediaStreamsKinds.Microphone)
			{
				participant.isMutedAudio = data.trackMuted.muted;
				const eventName = data.trackMuted.muted
					? 'RemoteMediaMuted'
					: 'RemoteMediaUnmuted';
				this.setLog(`Got mute signal (${data.trackMuted.muted}) for audio from a participant with id ${participant.userId} (sid: ${participant.sid})`, LOG_LEVEL.INFO);
				this.triggerEvents(eventName, [participant, track]);
			}
			else if (track.source === MediaStreamsKinds.Camera)
			{
				participant.isMutedVideo = data.trackMuted.muted;
				const eventName = data.trackMuted.muted
					? 'RemoteMediaRemoved'
					: 'RemoteMediaAdded';
				this.setLog(`Got mute signal (${data.trackMuted.muted}) for video from a participant with id ${participant.userId} (sid: ${participant.sid})`, LOG_LEVEL.INFO);
				this.triggerEvents(eventName, [participant, track]);
			}
		} else if (data?.trickle) {
			this.#addIceCandidate(data.trickle);
		} else if (data?.newMessage) {
			const message = new Message(data.newMessage)
			this.triggerEvents('MessageReceived', [message]);
		} else if (data?.handRaised) {
			const participant = this.#privateProperties.remoteParticipants[data.handRaised.participantId];
			if (participant)
			{
				participant.isHandRaised = data.handRaised.isHandRaised;
				this.triggerEvents('HandRaised', [participant]);
			}
		} else if (data?.speakersChanged) {
			this.#speakerChangedHandler(data)
		} else if (data?.subscribedQualityUpdate) {
			console.log(data)
		} else if (data?.connectionQuality) {
			console.log(data);
			if (!data.connectionQuality.updates)
			{
				return;
			}
			const participants = {};
			const participantsToUpdate = { ...this.#privateProperties.remoteParticipants };

			data.connectionQuality.updates.forEach(participant => {
				Object.values(participantsToUpdate).forEach(remoteParticipant => {

					const hasGoodQuality = participant.score > this.#privateProperties.minimalConnectionQuality;
					if (
						participant.participantSid === remoteParticipant.sid
						&& (
							!remoteParticipant.isMutedVideo
							|| !remoteParticipant.connectionQuality
						)
						&& !remoteParticipant.screenSharingEnabled
					) {
						participants[remoteParticipant.userId] = participant.score;
						this.setLog(`Quality of connection with a participant with id ${remoteParticipant.userId} (sid: ${remoteParticipant.sid}) changed to ${participant.score}`, hasGoodQuality ? LOG_LEVEL.INFO : LOG_LEVEL.WARNING);
						this.#privateProperties.remoteParticipants[remoteParticipant.userId].connectionQuality = participant.score;
					}

					const isLocalVideoMuted = this.#privateProperties.localTracks[MediaStreamsKinds.Camera]
						&& this.#privateProperties.localTracks[MediaStreamsKinds.Camera].muted;

					this.checkQosFeatureAndExecutionCallback(() =>
					{
						if (participant.participantSid === this.#privateProperties.localParticipantSid)
						{
							this.currentMonitoringEventsObject.metrics.CONN_SCORE_CURRENT.push(participant.score);
						}
					});

					if (
						participant.participantSid === this.#privateProperties.localParticipantSid
						&& (
							!isLocalVideoMuted
							|| !this.#privateProperties.localConnectionQuality
						)
						&& !this.#privateProperties.localTracks[MediaStreamsKinds.Screen]
					)
					{
						participants[this.#privateProperties.userId] = participant.score;
						this.#privateProperties.localConnectionQuality =  participant.score;

						this.setLog(`Quality of connection with a mediaserver changed to ${participant.score}`, hasGoodQuality ? LOG_LEVEL.INFO : LOG_LEVEL.WARNING);
						// this.#toggleRemoteParticipantVideo(Object.keys(participantsToUpdate), hasGoodQuality);
					}
				});
			});
			this.triggerEvents('ConnectionQualityChanged', [participants]);
		} else if (data.pongResp) {
			this.#privateProperties.rtt = Date.now() - data.pongResp.lastPingTimestamp
			this.#resetPingTimeout()
		} else if (data.leave) {
			this.setLog(`Got leave signal with ${data.leave.reason} reason`, LOG_LEVEL.WARNING);
			this.#beforeDisconnect();

			if (
				(data.leave.canReconnect || data.leave.reason === 'CHANGING_MEDIA_SERVER') &&
				data.leave.reason !== "SIGNALING_DUPLICATE_PARTICIPANT")
			{
				this.#privateProperties.lastReconnectionReason = ReconnectionReason.LeaveCommand;
				this.#reconnect();
			}
			else
			{
				this.#privateProperties.canReconnect = false;
				this.triggerEvents('Failed', [
					{
						name: data.leave.reason,
						leaveInformation: {code: data.leave.code, reason: data.leave.reason}
					}]);
			}
		}
	};
	socketOnOpenHandler() {
		window.addEventListener('unload', this.sendLeaveBound)
	};

	socketOnCloseHandler(e) {
		this.#beforeDisconnect();

		if (e?.code && e?.code !== 1005)
		{

			this.setLog(`Socket closed with a code ${e.code}, reconnecting`, LOG_LEVEL.ERROR);
			this.#privateProperties.lastReconnectionReason = ReconnectionReason.WsTransportClosed;
			this.#reconnect();
		}
		else
		{
			this.setLog(`Socket closed with a code ${e.code}`, LOG_LEVEL.ERROR);
		}
	};
	socketOnErrorHandler() {
		this.setLog(`Got a socket error`, LOG_LEVEL.ERROR);
	};
	async onIceCandidate(target, event) {
		if (!event.candidate) return;

		if (Util.useTcpSdp() && event.candidate.protocol !== 'tcp')
		{
			return;
		}

		const trickle = {
			candidateInit: JSON.stringify({
				candidate: event.candidate.candidate,
				sdpMid: event.candidate?.sdpMid,
				sdpMLineIndex: event.candidate?.sdpMLineIndex,
				usernameFragment: event.candidate?.usernameFragment
			})
		};

		if (target) {
			trickle.target = target;
		}

		this.#sendSignal({ trickle });
	};
	onConnectionStateChange(subscriber) {
		const state = subscriber
			? this.recipient.connectionState
			: this.sender.connectionState

		if (state === 'failed' && !this.#privateProperties.isReconnecting)
		{
			this.setLog(`State of ${subscriber ? 'recipient' : 'sender'} peer connection changed to ${state}, reconnecting`, LOG_LEVEL.WARNING);
			if (this.#privateProperties.peerConnectionFailed)
			{
				return;
			}

			this.#privateProperties.peerConnectionFailed = true;
			this.checkQosFeatureAndExecutionCallback(() =>
			{
				this.addMonitoringEvents({
					name: MONITORING_EVENTS_NAME_LIST.PEER_CONNECTION_REFUSED,
					withCounter: true,
				});
			});
			this.#beforeDisconnect();
			if (this.#privateProperties.canReconnect)
			{
				this.#privateProperties.lastReconnectionReason = ReconnectionReason.PeerConnectionFailed;
				this.#reconnect();
			}
		}
	}

	#resetPingTimeout() {
		this.#clearPingTimeout()
		if (!this.#privateProperties.pingTimeoutDuration) {
			return;
		}
		this.#privateProperties.pingTimeout = setTimeout(() => {
			this.setLog('Ping signal was not received, reconnecting', LOG_LEVEL.WARNING);
			this.#beforeDisconnect();
			this.#privateProperties.lastReconnectionReason = ReconnectionReason.PingPongMissed;
			this.#reconnect();
		}, this.#privateProperties.pingTimeoutDuration);
	}


	#clearPingTimeout() {
		if (this.#privateProperties.pingTimeout) {
			clearTimeout(this.#privateProperties.pingTimeout);
		}
	}

	#startPingInterval() {
		this.#clearPingInterval()
		this.#resetPingTimeout()
		if (!this.#privateProperties.pingIntervalDuration) {
			return;
		}
		this.#privateProperties.pingPongInterval = setInterval(() => {
			this.#sendPing();
		}, this.#privateProperties.pingIntervalDuration);
	}

	#clearPingInterval() {
		this.#clearPingTimeout();
		if (this.#privateProperties.pingPongInterval) {
			clearInterval(this.#privateProperties.pingPongInterval);
		}
	}

	#sendPing() {
		this.#sendSignal({
			pingReq: {
				timestamp: Date.now(),
				rtt: this.#privateProperties.rtt
			}
		});
	}

	on(eventType, handler) {
		this.#privateProperties.events.set(eventType, handler)
		return this
	}
	off(eventType) {
		if (this.#privateProperties.events.has(eventType)) {
			return this.#privateProperties.events.delete(eventType)
		}
		return this
	}

	triggerEvents(eventType, args) {
		if (this.#privateProperties.events.has(eventType)) {
			const event = this.#privateProperties.events.get(eventType)
			if (args) {
				event(...args)
			} else {
				event()
			}
		}
	}

	isRecordable() {
		console.log('isRecordable')
	}

	setBitrate(bitrate, MediaStreamKind) {
		this.setLog('Start setting bitrate', LOG_LEVEL.INFO);
		let track;
		let isSimulcast;
		switch (MediaStreamKind) {
			case MediaStreamsKinds.Camera:
				track = this.#privateProperties.cameraStream.getVideoTracks[0];
				isSimulcast = this.#privateProperties.videoSimulcast;
				break;
			case MediaStreamsKinds.Microphone:
				track = this.#privateProperties.microphoneStream.getAudioTracks[0];
				break;
			case MediaStreamsKinds.Screen:
				track = this.#privateProperties.screenStream.getVideoTracks[0];
				break;
		}
		const senders = this.sender.getSenders()

		senders.forEach( (sender) => {
			const params = sender.getParameters();
			if(!params || !params.encodings || params.encodings.length === 0)
			{
				this.setLog('Setting bitrate failed: has no encodings in the sender parameters', LOG_LEVEL.WARNING);
			}
			else
			{
				params.encodings.forEach(encoding => {
					if (isSimulcast) {
						encoding.maxBitrate = bitrate < this.#privateProperties.defaultSimulcastBitrate[encoding.rid] ? bitrate : this.#privateProperties.defaultSimulcastBitrate[encoding.rid]
					} else {
						encoding.maxBitrate = bitrate
					}
				})
				sender.setParameters(params);
				this.setLog('Setting bitrate succeeded', LOG_LEVEL.INFO);
			}
		});
	}

	#addPendingPublication(trackId, source)
	{
		this.#privateProperties.pendingPublications[trackId] = setTimeout(() =>
		{
			delete this.#privateProperties.pendingPublications[trackId];
			this.onPublishFailed(source);
		}, this.#privateProperties.publicationTimeout);
	}

	#addPendingSubscription(participant, track, tries)
	{
		clearTimeout(this.#privateProperties.pendingSubscriptions[participant.userId]?.[track.sid]?.timeout);
		if (!this.#privateProperties.pendingSubscriptions[participant.userId])
		{
			this.#privateProperties.pendingSubscriptions[participant.userId] = {};
		}

		if (tries === undefined)
		{
			tries = this.#privateProperties.subscriptionTries;
		}

		const timeout = setTimeout(() => {
			this.setLog(`Track ${track.sid} with kind ${track.source} for a participant with id ${participant.userId} (sid: ${participant.sid}) was not received, trying to subscribe to it`, LOG_LEVEL.WARNING);
			this.checkQosFeatureAndExecutionCallback(() =>
			{
				this.addMonitoringEvents({
					name: MONITORING_EVENTS_NAME_LIST.TRACK_SUBSCRIPTION_DELAY,
					withCounter: true,
				});
			});

			if (tries)
			{
				this.#addPendingSubscription(participant, track, tries - 1)
				this.#changeSubscriptionToTrack(track.sid, participant.sid, true);
			}
			else
			{
				this.setLog(`Subscription to track ${track.sid} with kind ${track.source} for a participant with id ${participant.userId} (sid: ${participant.sid}) failed`, LOG_LEVEL.ERROR);
				this.checkQosFeatureAndExecutionCallback(() =>
				{
					this.addMonitoringEvents({
						name: MONITORING_EVENTS_NAME_LIST.TRACK_SUBSCRIPTION_FAILED,
						withCounter: true
					});
				});
			}
		}, this.#privateProperties.subscriptionTimeout);

		this.#privateProperties.pendingSubscriptions[participant.userId][track.sid] = {
			timeout,
			tries,
		};
	}

	setCodec(transceiver)
	{
		if (!('getCapabilities' in RTCRtpReceiver)) {
			return;
		}
		const cap = RTCRtpReceiver.getCapabilities('video');
		if (!cap) return;
		const matched = [];
		const partialMatched = [];
		const unmatched = [];
		cap.codecs.forEach((c) => {
			const codec = c.mimeType.toLowerCase();
			if (codec === 'audio/opus') {
				matched.push(c);
				return;
			}
			const matchesVideoCodec = codec === `video/${this.#privateProperties.codec}`;
			if (!matchesVideoCodec) {
				unmatched.push(c);
				return;
			}
			// for h264 codecs that have sdpFmtpLine available, use only if the
			// profile-level-id is 42e01f for cross-browser compatibility
			if (this.#privateProperties.codec === 'h264') {
				if (c.sdpFmtpLine && c.sdpFmtpLine.includes('profile-level-id=42e01f')) {
					matched.push(c);
				} else {
					partialMatched.push(c);
				}
				return;
			}

			matched.push(c);
		});

		if ('setCodecPreferences' in transceiver) {
			// console.log('setCodecPreferences', this.#privateProperties.codec, matched, partialMatched, unmatched);
			transceiver.setCodecPreferences(matched.concat(partialMatched, unmatched));
		}
	}

	async publishTrack(MediaStreamKind, MediaStreamTrack, StreamQualityOptions = {}) {
		if (!this.sender)
		{
			this.setLog(`Publishing a track before a peer connection was created, ignoring`, LOG_LEVEL.WARNING);
			return;
		}

		this.setLog(`Start publishing a track with kind ${MediaStreamKind}`, LOG_LEVEL.INFO);

		try {
			for (let keys in StreamQualityOptions ) {
				this.#privateProperties[`${keys}`] = StreamQualityOptions[keys]
			}

			const source = MediaStreamKind;
			MediaStreamTrack.source = source;
			if (source === MediaStreamsKinds.Camera) {
				if( this.#privateProperties.videoSimulcast) {
					const width = MediaStreamTrack.getSettings().width;
					const height = MediaStreamTrack.getSettings().height;

					const sender = this.#getSender(MediaStreamsKinds.Camera);
					if (sender)
					{
						await sender.replaceTrack(MediaStreamTrack);
						this.#updateVideoEncodings(sender, MediaStreamTrack);
						this.setLog(`Publishing a track with kind ${MediaStreamKind} via replace track succeeded`, LOG_LEVEL.INFO);
						this.triggerEvents('PublishSucceed', [MediaStreamsKinds.Camera]);
						return;
					}
					else
					{
						const encodings = this.#getEncodingsFromVideoWidth(width);

						const transceiver = this.sender.addTransceiver(MediaStreamTrack, {
							direction: 'sendonly',
							streams: [this.#privateProperties.cameraStream],
							sendEncodings: MediaStreamTrack.sendEncodings || encodings,
						});

						this.setCodec(transceiver)

						this.#addPendingPublication(MediaStreamTrack.id, source);

						this.#sendSignal({
							"addTrack":  {
								"cid":  MediaStreamTrack.id,
								"type":  "VIDEO",
								"width":  width,
								"height":  height,
								"source":  source,
								"layers":  this.#getLayersFromEncodings(width, height, encodings),
							}
						});
					}
				} else {
					const sender = this.#getSender(MediaStreamsKinds.Camera);
					if (sender)
					{
						await sender.replaceTrack(MediaStreamTrack);
						this.setLog(`Publishing a track with kind ${MediaStreamKind} via replace track succeeded`, LOG_LEVEL.INFO);
						this.triggerEvents('PublishSucceed', [MediaStreamsKinds.Camera]);
						return;
					}
					else
					{
						this.sender.addTransceiver(MediaStreamTrack, {
							direction: 'sendonly'
						});
						this.setBitrate(this.#privateProperties.videoBitrate, MediaStreamsKinds.Camera)

						const width = MediaStreamTrack.getSettings().width
						const height = MediaStreamTrack.getSettings().height

						this.#addPendingPublication(MediaStreamTrack.id, source);

						this.#sendSignal({
							"addTrack":  {
								"cid":  MediaStreamTrack.id,
								"type":  "VIDEO",
								"width":  width,
								"height":  height,
								"source":  source,
							}
						});
					}
				}
			} else if (source === MediaStreamsKinds.Microphone) {
				const sender = this.#getSender(MediaStreamsKinds.Microphone);
				if (sender)
				{
					await sender.replaceTrack(MediaStreamTrack);
					this.setLog(`Publishing a track with kind ${MediaStreamKind} via replace track succeeded`, LOG_LEVEL.INFO);
					this.triggerEvents('PublishSucceed', [MediaStreamsKinds.Microphone]);
					return;
				}
				else
				{
					this.sender.addTransceiver(MediaStreamTrack, {
						direction: 'sendonly'
					});

					this.#addPendingPublication(MediaStreamTrack.id, source);

					this.#sendSignal({
						"addTrack":  {
							"cid" : MediaStreamTrack.id,
							"source":  source
						}
					});
				}
			} else if (source === MediaStreamsKinds.Screen) {
				this.sender.addTransceiver(MediaStreamTrack, {
					direction: 'sendonly'
				});
				const width = MediaStreamTrack.getSettings().width
				const height = MediaStreamTrack.getSettings().height

				this.#addPendingPublication(MediaStreamTrack.id, source);

				this.#sendSignal({
					"addTrack":  {
						"cid":  MediaStreamTrack.id,
						"type":  "VIDEO",
						"width":  width,
						"height":  height,
						"source":  source,
					}
				});
			} else if (source === MediaStreamsKinds.ScreenAudio) {
				this.sender.addTransceiver(MediaStreamTrack, {
					direction: 'sendonly'
				});
				this.sender.addTransceiver(MediaStreamTrack, {
					direction: 'sendonly'
				});

				this.#addPendingPublication(MediaStreamTrack.id, source);

				this.#sendSignal({
					"addTrack":  {
						"cid" : MediaStreamTrack.id,
						"source":  source
					}
				});
			}

			this.#privateProperties.offersStack++;
			await this.sendOffer();
		}
		catch (e)
		{
			if (MediaStreamKind === MediaStreamsKinds.Microphone)
			{
				this.#privateProperties.needToDisableAudioAfterPublish = false;
			}
			this.setLog(`Publishing a track with kind ${MediaStreamKind} failed: ${e}`, LOG_LEVEL.ERROR);
			this.#releaseStream(MediaStreamKind);
			this.onPublishFailed(MediaStreamKind);
		}
	}

	#getMaxEncodingsByVideoWidth(width)
	{
		// https://source.chromium.org/chromium/chromium/src/+/main:third_party/webrtc/video/config/simulcast.cc;l=76;
		if (width >= 960)
		{
			return 3;
		}
		else if (width >= 480)
		{
			return 2;
		}
		return 1;
	}

	#getEncodingsFromVideoWidth(width)
	{
		const maxEncodings = this.#getMaxEncodingsByVideoWidth(width);
		const rids = ['q', 'h', 'f'];
		const encodings = [];

		for (let i = 0; i < 3; i++)
		{
			const rid = rids[i];
			encodings.push({
				rid,
				active: i < maxEncodings,
				maxBitrate: this.#privateProperties.defaultSimulcastBitrate[rid],
				scaleResolutionDownBy: 2 ** Math.max(0, (maxEncodings - 1 - i))
			});
		}

		return encodings;
	};

	#getLayersFromEncodings(width, height, encodings)
	{
		return encodings.map((encoding, index) =>
		{
			return {
				quality: index,
				width: width / encoding.scaleResolutionDownBy,
				height: height / encoding.scaleResolutionDownBy,
				bitrate: this.#privateProperties.defaultSimulcastBitrate[encoding.rid],
			}
		});
	}

	#updateVideoEncodings(sender, track)
	{
		const params = sender.getParameters();
		const width = track.getSettings().width;
		const encodings = this.#getEncodingsFromVideoWidth(width);

		if (params && params.encodings && params.encodings.length)
		{
			params.encodings.forEach((encoding) =>
			{
				const encodingByRid = encodings.find(el => el.rid === encoding.rid);
				if (encodingByRid)
				{
					encoding.active = encodingByRid.active;
					encoding.maxBitrate = encodingByRid.maxBitrate;
					encoding.scaleResolutionDownBy = encodingByRid.scaleResolutionDownBy;
				}
			});
			sender.setParameters(params);
		}
	}

	async changeStreamQuality(StreamQualityOptions) {
		for (let key in StreamQualityOptions) {
			if (this.#privateProperties[`${key}`] !== StreamQualityOptions[key]) {
				this.#privateProperties[`${key}`] = StreamQualityOptions[key]

				if (key === 'videoSimulcast' || (key === 'screenSimulcast' && this.#privateProperties.screenStream)) {
					const kind = key === 'videoSimulcast'
						? MediaStreamsKinds.Camera
						: MediaStreamsKinds.Screen

					if (this.#getSender(kind)) {
						await this.republishTrack(kind)
					}
				} else if ( ['videoBitrate', 'audioBitrate', 'screenBitrate'].includes(key)) {
					const kind = key === 'videoBitrate'
						? MediaStreamsKinds.Camera
						: key === 'screenBitrate'
							? MediaStreamsKinds.Screen
							: MediaStreamsKinds.Microphone

					await this.setBitrate(StreamQualityOptions[key], kind)
				}
			}
		}
	}

	async republishTrack(MediaStreamKind) {
		this.setLog(`Start republishing a track with kind ${MediaStreamKind}`, LOG_LEVEL.INFO);
		await this.unpublishTrack(MediaStreamKind);
		const track = await this.getTrack(MediaStreamKind);
		if (track) {
			await this.publishTrack(MediaStreamKind, track);
		} else {
			this.setLog(`Republishing a track with kind ${MediaStreamKind} failed: ${error}`, LOG_LEVEL.ERROR);
			this.#releaseStream(MediaStreamKind);
			this.triggerEvents('PublishFailed', [MediaStreamKind])
		}
	}

	async unpublishTrack(MediaStreamKind) {
		this.setLog(`Start unpublishing a track with kind ${MediaStreamKind}`, LOG_LEVEL.INFO);
		const sender = this.#getSender(MediaStreamKind);

		if (sender) {
			this.sender.removeTrack(sender);
			this.#privateProperties.offersStack++;
			await this.sendOffer();
			this.setLog(`Unpublishing a track with kind ${MediaStreamKind} succeeded`, LOG_LEVEL.INFO);
		}
		else
		{
			this.setLog(`Unpublishing a track with kind ${MediaStreamKind} failed: has no sender for a track`, LOG_LEVEL.ERROR);
		}
	}

	toggleRemoteParticipantVideo(participantIds, showVideo, isPaginateToggle = false) {
		this.#toggleRemoteParticipantVideo(participantIds, showVideo, isPaginateToggle);
	}

	#changeSubscriptionToTrack(trackId, participantId, subscribe)
	{
		this.#sendSignal({
			subscription: {
				trackSids: [trackId],
				subscribe,
				participantTracks: [
					{
						participantSid: participantId,
						trackSids: [trackId],
					},
				],
			}
		});
	}

	#pauseRemoteTrack(userId, trackId, trackSource, pause)
	{
		const participant = this.#privateProperties.remoteParticipants[userId];
		if (!participant)
		{
			this.setLog(`Trying to pause a track with kind ${trackSource} (sid: ${trackId}) for a non-existent participant with id ${userId}`, LOG_LEVEL.WARNING);
			return;
		}

		participant.videoPaused = pause;
		this.#sendSignal({
			trackSetting: {
				trackSids: [trackId],
				disabled: pause,
				quality: this.#calculateVideoQualityForUser(userId, trackSource),
			}
		});
	}

	#calculateVideoQualityForUser(userId, source)
	{
		const participant = this.#privateProperties.remoteParticipants[userId];
		const exactUser = this.#privateProperties.mainStream.userId == userId;
		const exactTrack = this.#privateProperties.mainStream.kind === source;

		let quality = STREAM_QUALITY.LOW;
		if (exactUser && (exactTrack || !participant.screenSharingEnabled))
		{
			quality = STREAM_QUALITY.HIGH;
		}
		else if (!this.#privateProperties.mainStream.userId)
		{
			quality = this.#privateProperties.defaultRemoteStreamsQuality;
		}

		return quality;
	}

	#changeRoomStreamsQuality(userId, kind) {
		this.setLog(`Start changing a streams quality`, LOG_LEVEL.INFO);
		Object.values(this.getParticipants()).forEach(p =>
		{
			let quality = this.#privateProperties.defaultRemoteStreamsQuality;
			if (userId)
			{
				const exactUser = userId == p.userId;
				quality = exactUser && kind === MediaStreamsKinds.Camera ? STREAM_QUALITY.HIGH : STREAM_QUALITY.MEDIUM;

				if (exactUser)
				{
					this.#privateProperties.mainStream = { userId, kind };
				}
			}
			else
			{
				this.#privateProperties.mainStream = {};
			}

			p.setStreamQuality(quality);
			this.setLog(`Quality of video for a participant with id ${p.userId} (sid: ${p.sid}) was changed to ${quality}`, LOG_LEVEL.INFO);
		})
	}

	#toggleRemoteParticipantVideo(participantIds, showVideo, isPaginateToggle = false)
	{
		participantIds.forEach(participantId => {
			const remoteParticipant = this.#privateProperties.remoteParticipants[participantId];
			if (remoteParticipant && remoteParticipant.tracks[MediaStreamsKinds.Camera] && remoteParticipant.isLocalVideoMute === showVideo)
			{
				remoteParticipant.isLocalVideoMute = !showVideo;

				if (remoteParticipant.isMutedVideo)
				{
					return;
				}

				this.#pauseRemoteTrack(
					remoteParticipant.userId,
					remoteParticipant.tracks[MediaStreamsKinds.Camera].id,
					remoteParticipant.tracks[MediaStreamsKinds.Camera].source,
					remoteParticipant.isLocalVideoMute
				);
			}
		});

		if (!isPaginateToggle)
		{
			this.triggerEvents('ToggleRemoteParticipantVideo', [showVideo]);
		}
	}

	hangup() {
		if (this.#privateProperties.callState === CALL_STATE.TERMINATED)
		{
			return;
		}

		this.#privateProperties.abortController.abort();

		this.#privateProperties.callState = CALL_STATE.TERMINATED;

		this.setLog(`Disconnecting from the call`, LOG_LEVEL.INFO);
		this.#sendLeave();
		this.#beforeDisconnect();
		this.#destroyPeerConnection();

		clearTimeout(this.#privateProperties.reconnectionTimeout);

		for (let trackId in this.#privateProperties.pendingPublications)
		{
			clearTimeout(this.#privateProperties.pendingPublications[trackId]);
		}
		this.#privateProperties.pendingPublications = {};

		for (let userId in this.#privateProperties.pendingSubscriptions)
		{
			for (let trackId in this.#privateProperties.pendingSubscriptions[userId])
			{
				clearTimeout(this.#privateProperties.pendingSubscriptions[userId][trackId].timeout);
			}
		}
		this.#privateProperties.pendingSubscriptions = {};

		this.#privateProperties.url = null;
		this.#privateProperties.token = null;
		this.#privateProperties.endpoint = null;
		this.#privateProperties.jwt = null;
		this.#privateProperties.options = null;
		this.#privateProperties.iceServers = null;
		this.#privateProperties.lastReconnectionReason = null;

		this.#releaseStream(MediaStreamsKinds.Camera);
		this.#releaseStream(MediaStreamsKinds.Microphone);
		this.#releaseStream(MediaStreamsKinds.Screen);

		this.#privateProperties.rtt = 0;
		this.#privateProperties.remoteTracks = {};
		this.#privateProperties.isReconnecting = false;
		this.#privateProperties.reconnectionAttempt = 0;
		this.#privateProperties.mainStream = {};

		this.triggerEvents('Disconnected');
	}

	isConnected() {
		return this.#privateProperties.callState === CALL_STATE.CONNECTED
	}

	setVideoStreamSetupErrorList(userId, kind)
	{
		const errorInVideoStreamSetupErrorList = this.#privateProperties.videoStreamSetupErrorList[userId];
		const kindInListKinds = !!errorInVideoStreamSetupErrorList && errorInVideoStreamSetupErrorList.includes(kind);
		const kindsArray = errorInVideoStreamSetupErrorList || [];

		if (!kindInListKinds)
		{
			this.#privateProperties.videoStreamSetupErrorList[userId] = [kind, ...kindsArray];
		}
	}

	setMainStream(userId, kind)
	{
		// userId always exists, check in bitrix_call
		if (!this.#privateProperties.remoteParticipants[userId])
		{
			this.setVideoStreamSetupErrorList(userId, kind);
			this.setLog(`Setting main stream error (No remoteParticipant with ID - ${userId} in list)`, LOG_LEVEL.ERROR);
			return;
		}

		this.setLog(`Setting main stream for a participant width id ${userId} (sid: ${this.#privateProperties.remoteParticipants[userId].sid})`, LOG_LEVEL.INFO);
		this.#changeRoomStreamsQuality(userId, kind);
	}

	resetMainStream() {
		this.setLog(`Resetting main stream`, LOG_LEVEL.INFO);
		this.#changeRoomStreamsQuality()
	}

	removeTrack(mediaStreamKind) {
		const trackSid = this.#privateProperties.localTracks[mediaStreamKind]?.sid
		if (trackSid)
		{
			this.setLog(`Sending removeTrack signal for a track with kind ${mediaStreamKind} (sid: ${trackSid})`, LOG_LEVEL.INFO);
			delete this.#privateProperties.localTracks[mediaStreamKind];
			this.#sendSignal({
				removeTrack: {
					sid: trackSid
				}
			});
		}
		else
		{
			this.setLog(`Sending removeTrack signal for a non-existent track with kind ${mediaStreamKind}`, LOG_LEVEL.WARNING);
		}
	}

	pauseTrack(mediaStreamKind, keepTrack) {
		const trackSid = this.#privateProperties.localTracks[mediaStreamKind]?.sid;
		if (trackSid)
		{
			this.setLog(`Sending pause signal (keep: ${keepTrack}) for a track with kind ${mediaStreamKind} (sid: ${trackSid})`, LOG_LEVEL.INFO);
			if (!keepTrack)
			{
				delete this.#privateProperties.localTracks[mediaStreamKind];
			}
			this.#sendSignal({
				mute: {
					sid: trackSid,
					muted: true
				}
			});
		}
		else
		{
			this.setLog(`Sending pause signal for a non-existent track with kind ${mediaStreamKind}`, LOG_LEVEL.WARNING);
		}
	}

	unpauseTrack(mediaStreamKind) {
		const trackSid = this.#privateProperties.localTracks[mediaStreamKind]?.sid;
		if (trackSid)
		{
			this.setLog(`Sending unpause signal for a track with kind ${mediaStreamKind} (sid: ${trackSid})`, LOG_LEVEL.INFO);
			this.#sendSignal({
				mute: {
					sid: trackSid,
					muted: false
				}
			});
		}
		else
		{
			this.setLog(`Sending unpause signal for a non-existent track with kind ${mediaStreamKind}`, LOG_LEVEL.WARNING);
		}
	}

	disableAudio(bySystem = false) {
		if (this.#privateProperties.mediaMutedBySystem)
		{
			return;
		}

		this.setLog('Start disabling audio', LOG_LEVEL.INFO);
		const track = this.#privateProperties.microphoneStream?.getAudioTracks()[0];
		if (track)
		{
			this.#privateProperties.needToEnableAudioAfterSystemMuted = bySystem ? track.enabled : false;
			track.enabled = false;
			this.pauseTrack(MediaStreamsKinds.Microphone, true);
		}
		else
		{
			this.setLog('Disabling audio failed: has no track', LOG_LEVEL.ERROR);
		}
	}

	async enableAudio(disabled = false)
	{
		this.setLog('Start enabling audio', LOG_LEVEL.INFO);
		this.#privateProperties.needToEnableAudioAfterSystemMuted = false;
		if (this.#privateProperties.switchActiveAudioDeviceInProgress)
		{
			try
			{
				await this.#privateProperties.switchActiveAudioDeviceInProgress;
			}
			catch (e)
			{
				this.onPublishFailed(MediaStreamsKinds.Microphone);
			}
		}
		let track = this.#privateProperties.microphoneStream?.getAudioTracks()[0];
		const canUseUnpauseSignal = track && this.#privateProperties.localTracks[MediaStreamsKinds.Microphone];
		const needToGetNewTrack = track?.readyState !== 'live';

		if (needToGetNewTrack)
		{
			track = await this.getLocalAudio();
		}

		if (!track)
		{

			this.setLog('Enabling audio failed: has no track', LOG_LEVEL.ERROR);
			this.#releaseStream(MediaStreamsKinds.Microphone);
			this.triggerEvents('PublishFailed', [MediaStreamsKinds.Microphone]);
		}

		if (canUseUnpauseSignal)
		{
			this.setLog('Enabling audio via unpause signal', LOG_LEVEL.INFO);
			track.enabled = true;
			await this.publishTrack(MediaStreamsKinds.Microphone, track);
			this.unpauseTrack(MediaStreamsKinds.Microphone);
		}
		else
		{
			this.setLog('Enabling audio via publish', LOG_LEVEL.INFO);
			track.enabled = !disabled;
			if (disabled)
			{
				this.#privateProperties.needToDisableAudioAfterPublish = true;
			}
			await this.publishTrack(MediaStreamsKinds.Microphone, track);
		}
	}

	async disableVideo(bySystem = false)
	{
		if (this.#privateProperties.isReconnecting)
		{
			return;
		}
		const hasQueue = this.#privateProperties.videoQueue !== VIDEO_QUEUE.INITIAL;
		this.#privateProperties.videoQueue = VIDEO_QUEUE.DISABLE;
		if (hasQueue)
		{
			return;
		}
		this.setLog('Start disabling video', LOG_LEVEL.INFO);
		const track = this.#privateProperties.cameraStream?.getVideoTracks()[0];
		if (track && this.#privateProperties.localTracks[MediaStreamsKinds.Camera])
		{
			if (this.#privateProperties.mediaMutedBySystem)
			{
				this.#privateProperties.videoQueue = VIDEO_QUEUE.INITIAL;
				this.#privateProperties.mediaMutedBySystem = false;
				track.stop();
				this.checkQosFeatureAndExecutionCallback(() =>
				{
					this.addMonitoringEvents({
						name: MONITORING_EVENTS_NAME_LIST.USER_CAMERA_DISABLED,
						withCounter: true
					});
				});
				this.triggerEvents('MediaMutedBySystem', [false]);
				this.triggerEvents('PublishEnded', [MediaStreamsKinds.Camera]);
				return;
			}

			this.#privateProperties.localTracks[MediaStreamsKinds.Camera].muted = true;
			this.pauseTrack(MediaStreamsKinds.Camera, true);
			if (!bySystem)
			{
				this.#privateProperties.videoQueue = VIDEO_QUEUE.INITIAL;
				track.stop();
				this.checkQosFeatureAndExecutionCallback(() =>
				{
					this.addMonitoringEvents({
						name: MONITORING_EVENTS_NAME_LIST.USER_CAMERA_DISABLED,
						withCounter: true
					});
				});
				this.triggerEvents('PublishEnded', [MediaStreamsKinds.Camera]);
			}
		}
		else
		{
			this.setLog('Disabling video failed: has no track', LOG_LEVEL.ERROR);
			this.#privateProperties.videoQueue = VIDEO_QUEUE.INITIAL;
		}
	}

	async enableVideo(skipUnpause = false) {
		if (this.#privateProperties.isReconnecting)
		{
			return;
		}
		const hasQueue = this.#privateProperties.videoQueue !== VIDEO_QUEUE.INITIAL;
		this.#privateProperties.videoQueue = VIDEO_QUEUE.ENABLE;
		if (hasQueue)
		{
			return;
		}
		if (this.#privateProperties.switchActiveVideoDeviceInProgress)
		{
			try
			{
				await this.#privateProperties.switchActiveVideoDeviceInProgress;
			}
			catch (e)
			{
				this.onPublishFailed(MediaStreamsKinds.Camera);
			}
		}
		this.setLog('Start enabling video', LOG_LEVEL.INFO);
		let hasTrack = !!this.#privateProperties.cameraStream?.getVideoTracks()[0];
		let track = await this.getLocalVideo();

		if (track && hasTrack && this.#privateProperties.localTracks[MediaStreamsKinds.Camera])
		{

			if (this.#privateProperties.mediaMutedBySystem)
			{
				this.#privateProperties.mediaMutedBySystem = false;
				this.triggerEvents('MediaMutedBySystem', [false]);
			}
			if (skipUnpause)
			{
				this.setLog('Re-enabling video after automatic camera change', LOG_LEVEL.INFO);
			}
			else
			{
				this.setLog('Enabling video via unpause signal', LOG_LEVEL.INFO);
			}
			this.#privateProperties.localTracks[MediaStreamsKinds.Camera].muted = false;
			await this.publishTrack(MediaStreamsKinds.Camera, track);
			if (skipUnpause)
			{
				this.#privateProperties.videoQueue = VIDEO_QUEUE.INITIAL;
			}
			else
			{
				this.unpauseTrack(MediaStreamsKinds.Camera);
			}
		}
		else if (track)
		{
			this.setLog('Enabling video via publish', LOG_LEVEL.INFO);
			await this.publishTrack(MediaStreamsKinds.Camera, track);
		}
		else
		{
			this.setLog('Enabling video failed: has no track', LOG_LEVEL.ERROR);
			this.#privateProperties.videoQueue = VIDEO_QUEUE.INITIAL;
			this.#releaseStream(MediaStreamsKinds.Camera);
			this.triggerEvents('PublishFailed', [MediaStreamsKinds.Camera]);
		}
	}

	#processVideoQueue()
	{
		const videoQueue = this.#privateProperties.videoQueue;
		this.#privateProperties.videoQueue = VIDEO_QUEUE.INITIAL;
		if (videoQueue === VIDEO_QUEUE.ENABLE && this.#privateProperties.cameraStream?.getVideoTracks()[0].readyState !== 'live')
		{
			this.enableVideo();
		}
		else if (videoQueue === VIDEO_QUEUE.DISABLE && this.#privateProperties.cameraStream?.getVideoTracks()[0].readyState === 'live' && !this.#privateProperties.mediaMutedBySystem)
		{
			this.disableVideo();
		}
	}

	async startScreenShare() {
		this.setLog('Start enabling screen sharing', LOG_LEVEL.INFO);
		const tracks = await this.getLocalScreen()
		const videoTrack = tracks?.video;
		const audioTrack = tracks?.audio;

		if (!videoTrack)
		{
			this.setLog('Enabling screen sharing failed: has no track', LOG_LEVEL.ERROR);
			this.#releaseStream(MediaStreamsKinds.Screen);
			this.onPublishFailed(MediaStreamsKinds.Screen);
			this.onPublishFailed(MediaStreamsKinds.ScreenAudio);
			return;
		}

		await this.publishTrack(MediaStreamsKinds.Screen, videoTrack);

		if (audioTrack)
		{
			await this.publishTrack(MediaStreamsKinds.ScreenAudio, audioTrack);
		}
	}

	async stopScreenShare() {
		this.setLog('Start disabling screen sharing', LOG_LEVEL.INFO);
		this.#releaseStream(MediaStreamsKinds.Screen);
		this.removeTrack(MediaStreamsKinds.Screen);
		this.removeTrack(MediaStreamsKinds.ScreenAudio);
		await this.unpublishTrack(MediaStreamsKinds.Screen);
		await this.unpublishTrack(MediaStreamsKinds.ScreenAudio);
	}

	sendMessage(message) {
		this.#sendSignal({ sendMessage: { message } });
	}

	raiseHand(raised) {
		this.#sendSignal({ raiseHand: { raised } });
	}

	async getLocalVideo() {
		if (this.#privateProperties.cameraStream?.getVideoTracks()[0].readyState !== 'live')
		{
			await this.getTrack(MediaStreamsKinds.Camera);
		}

		return this.#privateProperties.cameraStream?.getVideoTracks()[0];
	}
	async getLocalAudio() {
		if (!this.#privateProperties.microphoneStream)
		{
			await this.getTrack(MediaStreamsKinds.Microphone);
		}

		return this.#privateProperties.microphoneStream?.getAudioTracks()[0];
	}
	async getLocalScreen() {
		if (!this.#privateProperties.screenStream)
		{
			await this.getTrack(MediaStreamsKinds.Screen);
		}

		return {
			video: this.#privateProperties.screenStream?.getVideoTracks()[0],
			audio: this.#privateProperties.screenStream?.getAudioTracks()[0]
		};
	}

	async #getUserMedia(options, fallbackMode = false) {
		this.triggerEvents('GetUserMediaStarted', [options]);

		this.setLog(`Start getting user media with options: ${JSON.stringify(options)}`, LOG_LEVEL.INFO);
		const constraints = {
			audio: false,
			video: false,
		}

		let stream = null

		try {
			if (options.video) {
				constraints.video = {};

				if (!fallbackMode)
				{
					constraints.video.width = { ideal: this.#privateProperties.defaultVideoResolution.width };
					constraints.video.height = { ideal: this.#privateProperties.defaultVideoResolution.height };
				}

				if (this.#privateProperties.videoDeviceId) {
					constraints.video.deviceId = {exact: this.#privateProperties.videoDeviceId}
				}
			} else if (options.audio) {
				if (this.#privateProperties.audioDeviceId) {
					constraints.audio = {
						deviceId: {exact: this.#privateProperties.audioDeviceId}
					}
				} else {
					constraints.audio = true
				}
			}

			stream = await navigator.mediaDevices.getUserMedia(constraints);

			if (options.video && stream.getVideoTracks()[0])
			{
				this.triggerEvents('GetUserMediaSuccess', [{video: true}]);
			}

			if (options.audio && stream.getAudioTracks()[0])
			{
				this.triggerEvents('GetUserMediaSuccess', [{audio: true}]);
			}

			if (options.video)
			{
				this.#addTrackMuteHandlers(stream.getVideoTracks()[0]);
			}
			this.setLog(`Getting user media with constraints: ${JSON.stringify(constraints)} succeeded`, LOG_LEVEL.INFO);
		} catch (e) {
			if (options.video)
			{
				this.setLog(`Getting user media with constraints: ${JSON.stringify(constraints)} failed (fallbackMode: ${fallbackMode}): ${e}`, LOG_LEVEL.ERROR);

				if (!fallbackMode)
				{
					this.checkQosFeatureAndExecutionCallback(() =>
					{
						this.addMonitoringEvents({
							name: MONITORING_EVENTS_NAME_LIST.LOCAL_VIDEO_STREAM_RECEIVING_FAILED,
							withCounter: true,
						});
					});
					stream = await this.#getUserMedia(options, true);
				}
			}

			if (options.audio)
			{
				this.checkQosFeatureAndExecutionCallback(() =>
				{
					this.addMonitoringEvents({
						name: MONITORING_EVENTS_NAME_LIST.LOCAL_MICROPHONE_STREAM_RECEIVING_FAILED,
						withCounter: true,
					});
				});
			}

			this.setLog(`Getting user media with constraints: ${JSON.stringify(constraints)} failed: ${e}`, LOG_LEVEL.ERROR);
			this.checkQosFeatureAndExecutionCallback(() =>
			{
				this.addMonitoringEvents({ name: MONITORING_EVENTS_NAME_LIST.GET_LOCAL_TRACK_ERROR,
					value: {
						type: 'main'
					}
				});
			});
		} finally {
			this.triggerEvents('GetUserMediaEnded');
			return stream
		}
	}

	async #getDisplayMedia() {
		this.setLog('Start getting display media', LOG_LEVEL.INFO);
		let stream = null;

		try {
			if (window["BXDesktopSystem"] && window["BXDesktopSystem"].GetProperty('versionParts')[3] < 78) {
				stream = await navigator.mediaDevices.getUserMedia({
					video: {
						mandatory: {
							chromeMediaSource: 'screen',
							maxWidth: 1920,
							maxHeight: 1080,
							maxFrameRate: 5
						}
					}
				});
			}
			else
			{
				stream = await navigator.mediaDevices.getDisplayMedia({
					video: {
						cursor: 'always',
						width: {
							ideal: 1920
						},
						height: {
							ideal: 1080
						}
					},
					systemAudio: "include",
					audio: true,
				});
			}
			this.setLog('Getting display media succeeded', LOG_LEVEL.INFO);
		} catch (e) {
			this.setLog(`Getting display media failed: ${e}`, LOG_LEVEL.ERROR);
			this.checkQosFeatureAndExecutionCallback(() => {
				this.addMonitoringEvents({
					name: MONITORING_EVENTS_NAME_LIST.LOCAL_SCREEN_STREAM_RECEIVING_FAILED,
					withCounter: true,
				});
			});
		} finally {
			return stream;
		}
	}

	async getTrack(MediaStreamKind) {
		if (MediaStreamKind === MediaStreamsKinds.Camera && this.#privateProperties.cameraStream?.getVideoTracks().readyState !== 'live')
		{
			this.#privateProperties.cameraStream = await this.#getUserMedia({video: true});
		}
		else if (MediaStreamKind === MediaStreamsKinds.Microphone && !this.#privateProperties.microphoneStream)
		{
			this.#privateProperties.microphoneStream = await this.#getUserMedia({audio: true});
		}
		else if (MediaStreamKind === MediaStreamsKinds.Screen && !this.#privateProperties.screenStream)
		{
			this.#privateProperties.screenStream = await this.#getDisplayMedia();
		}

		if (this.#privateProperties.abortController.signal.aborted)
		{
			this.#releaseStream(MediaStreamKind);
			return;
		}

		let track;

		if (MediaStreamKind === MediaStreamsKinds.Screen)
		{
			track = this.#privateProperties.screenStream?.getVideoTracks()[0];
			if (track && track.readyState !== 'live')
			{
				this.#privateProperties.screenStream = null;
				track = this.getLocalScreen();
			}
		}
		else if (MediaStreamKind === MediaStreamsKinds.Camera)
		{
			track = this.#privateProperties.cameraStream?.getVideoTracks()[0];
			if (track && track.readyState !== 'live')
			{
				this.#privateProperties.cameraStream = null;
				track = this.getLocalVideo();
			}
		}
		else if (MediaStreamKind === MediaStreamsKinds.Microphone)
		{
			track = this.#privateProperties.microphoneStream?.getAudioTracks()[0];
			if (track && track.readyState !== 'live')
			{
				this.#privateProperties.microphoneStream = null;
				track = this.getLocalAudio();
			}
		}

		if (track && !track.onended)
		{
			const interrupted = MediaStreamKind === MediaStreamsKinds.Microphone
				|| MediaStreamKind === MediaStreamsKinds.Camera;

			track.onended = () => {
				if (this.#privateProperties.localTracks[MediaStreamKind])
				{
					this.#privateProperties.localTracks[MediaStreamKind].muted = true;
				}
				this.triggerEvents('PublishEnded', [MediaStreamKind, interrupted]);
			};
		}

		return track;
	}

	async switchActiveAudioDevice(deviceId, force) {
		if (this.#privateProperties.switchActiveAudioDeviceInProgress && !force)
		{
			this.setLog(`Got another request to switch an audio device to ${deviceId}, saving it`, LOG_LEVEL.INFO);
			this.#privateProperties.switchActiveAudioDevicePending = deviceId;
			return;
		}

		let error;
		let fulfilled = false;
		this.setLog(`Start switching an audio device to ${deviceId}`, LOG_LEVEL.INFO);
		const promise = new Promise(async (resolve, reject) =>
		{
			this.#privateProperties.audioDeviceId = deviceId;
			const prevStream = this.#privateProperties.microphoneStream;

			try
			{
				const prevTrack = this.#privateProperties.microphoneStream?.getAudioTracks()[0];
				this.#privateProperties.microphoneStream = null;
				let prevTrackEnabledState = true;
				let prevTrackId = '';
				if (prevTrack)
				{
					prevTrackEnabledState = prevTrack.enabled;
					prevTrackId = prevTrack.id;
					prevTrack.stop();
				}
				const audioTrack = await this.getLocalAudio();
				audioTrack.source = MediaStreamsKinds.Microphone;
				audioTrack.enabled = prevTrackEnabledState;
				const sender = this.#getSender(MediaStreamsKinds.Microphone);
				if (
					sender
					&& (this.isAudioPublished() || sender.track.id !== audioTrack.id || audioTrack.id !== prevTrackId)
				)
				{
					this.setLog('Have sender for audio, start replacing track', LOG_LEVEL.INFO);
					await sender.replaceTrack(audioTrack);
				}
				this.setLog('Switching an audio device succeeded', LOG_LEVEL.INFO);
			}
			catch (e)
			{
				error = e;
				this.setLog(`Switching an audio device failed: ${e}`, LOG_LEVEL.ERROR);
				if (!this.#privateProperties.microphoneStream)
				{
					this.#privateProperties.microphoneStream = prevStream;
				}
			}
			finally
			{
				if (this.#privateProperties.switchActiveAudioDevicePending)
				{
					const deviceId = this.#privateProperties.switchActiveAudioDevicePending;
					this.#privateProperties.switchActiveAudioDevicePending = null;
					resolve(this.switchActiveAudioDevice(deviceId , true));
				}
				else
				{
					fulfilled = true;
					this.#privateProperties.switchActiveAudioDeviceInProgress = null;
					return error ? reject(error) : resolve();
				}
			}
		});

		if (!force && !fulfilled)
		{
			this.#privateProperties.switchActiveAudioDeviceInProgress = promise;
		}

		return promise;
	}

	async switchActiveVideoDevice(deviceId, force) {
		if (this.#privateProperties.switchActiveVideoDeviceInProgress && !force)
		{
			this.setLog(`Got another request to switch a video device to ${deviceId}, saving it`, LOG_LEVEL.INFO);
			this.#privateProperties.switchActiveVideoDevicePending = deviceId;
			return;
		}

		let error;
		let fulfilled = false;
		this.setLog(`Start switching a video device to ${deviceId}`, LOG_LEVEL.INFO);
		const promise = new Promise(async (resolve, reject) =>
		{
			this.#privateProperties.videoDeviceId = deviceId;
			const prevStream = this.#privateProperties.cameraStream;

			try
			{
				const sender = this.#getSender(MediaStreamsKinds.Camera);
				if (sender && this.isVideoPublished())
				{
					this.setLog('Have sender for video, start replacing track', LOG_LEVEL.INFO);
					this.#privateProperties.cameraStream?.getVideoTracks()[0].stop();
					this.#privateProperties.cameraStream = null;

					const videoTrack = await this.getLocalVideo();
					videoTrack.source = MediaStreamsKinds.Camera;
					await sender.replaceTrack(videoTrack);
					this.#updateVideoEncodings(sender, videoTrack);
				}
				this.setLog('Switching a video device succeeded', LOG_LEVEL.INFO);
			}
			catch (e)
			{
				error = e;
				this.setLog(`Switching a video device failed: ${e}`, LOG_LEVEL.ERROR);
				if (!this.#privateProperties.cameraStream)
				{
					this.#privateProperties.cameraStream = prevStream;
				}
			}
			finally
			{
				if (this.#privateProperties.switchActiveVideoDevicePending)
				{
					const deviceId = this.#privateProperties.switchActiveVideoDevicePending;
					this.#privateProperties.switchActiveVideoDevicePending = null;
					resolve(this.switchActiveVideoDevice(deviceId , true));
				}
				else
				{
					fulfilled = true;
					this.#privateProperties.switchActiveVideoDeviceInProgress = null;
					return error ? reject(error) : resolve();
				}
			}
		});

		if (!force && !fulfilled)
		{
			this.#privateProperties.switchActiveVideoDeviceInProgress = promise;
		}

		return promise;
	}

	isAudioPublished()
	{
		return this.#privateProperties.localTracks[MediaStreamsKinds.Microphone] && this.#privateProperties.localTracks[MediaStreamsKinds.Microphone]?.muted !== true;
	}

	isVideoPublished()
	{
		return this.#privateProperties.localTracks[MediaStreamsKinds.Camera] && this.#privateProperties.localTracks[MediaStreamsKinds.Camera]?.muted !== true ;
	}

	#addTrackMuteHandlers(track)
	{
		track.onmute = (event) =>
		{
			this.#privateProperties.mediaMutedBySystem = false;
			this.disableVideo(true);
			this.disableAudio(true);
			this.#privateProperties.mediaMutedBySystem = true;
			this.triggerEvents('MediaMutedBySystem', [true]);
		};

		track.onunmute = (event) =>
		{
			if (this.#privateProperties.mediaMutedBySystem)
			{
				this.#privateProperties.mediaMutedBySystem = false;
				this.triggerEvents('MediaMutedBySystem', [false]);
			}
			this.enableVideo();
			if (this.#privateProperties.needToEnableAudioAfterSystemMuted)
			{
				this.enableAudio();
			}
		};
	}

	getParticipants() {
		return this.#privateProperties.remoteParticipants
	}

	getState() {
		return this.#privateProperties.callState
	}

	setLog(log, level) {
		level = LOG_LEVEL[level] || LOG_LEVEL.info;

		if (this.#privateProperties.isloggingEnable)
		{
			const desktopVersion = window['BXDesktopSystem']?.ApiVersion?.();
			const version = this.#privateProperties.clientVersion + (desktopVersion ? ` (desktopApi: ${desktopVersion})` : '');
			const data = {
				timestamp: Math.floor(Date.now() / 1000),
				event: log,
				client: this.#privateProperties.clientPlatform,
				appVersion: version,
			};
			const logLength = Object.values(this.#privateProperties.logs).length;
			this.#privateProperties.logs[logLength] = {
				level,
				data,
			};
			let lastSentLog = 0;

			for (let index in this.#privateProperties.logs)
			{
				if (!this.#sendLog(this.#privateProperties.logs[index].data, this.#privateProperties.logs[index].level))
				{
					break;
				}
				lastSentLog = index;
			}

			if (lastSentLog)
			{
				this.#privateProperties.logs = Object.values(this.#privateProperties.logs).slice(lastSentLog + 1);
			}

			if (this.#privateProperties.loggerCallback) {
				this.#privateProperties.loggerCallback();
			}
		}
	}

	#sendLog(log, level) {
		const signal = {
			sendLog: {
				userName: `${this.#privateProperties.userId}`,
				data: JSON.stringify(log),
				msgLevel: level,
			}
		};

		return this.isConnected() ? this.#sendSignal(signal) : false;
	}

	setLoggerCallback(callback) {
		this.#privateProperties.loggerCallback = callback;
	}

	enableSilentLogging(enable) {
		this.#privateProperties.isloggingEnable = enable;
	}

	#removeUdpFromSdp(sdp)
	{
		const updatedSdp = [];
		sdp.split(/(\r\n|\r|\n)/).filter(RegExp.prototype.test.bind(/^([a-z])=(.*)/)).forEach((el) =>
		{
			if (!el.startsWith('a=candidate') || el.includes('tcp'))
			{
				updatedSdp.push(el);
			}
		});

		sdp = updatedSdp.join('\r\n') + '\r\n';

		return sdp;
	}

	async #answerHandler(data) {
		this.setLog('Start handling a remote answer', LOG_LEVEL.INFO);
		let hasError = false;
		try
		{
			if (Util.useTcpSdp())
			{
				data.answer.sdp = this.#removeUdpFromSdp(data.answer.sdp);
			}
			await this.sender.setRemoteDescription(data.answer);
			this.#privateProperties.pendingCandidates.sender.forEach((candidate) =>
			{
				this.sender.addIceCandidate(candidate);
				this.setLog('Added a deferred ICE candidate', LOG_LEVEL.INFO);
			});
			this.#privateProperties.pendingCandidates.sender = [];
		}
		catch (e)
		{
			this.setLog(`Handling a remote answer failed: ${e}`, LOG_LEVEL.ERROR);
			hasError = true;
			this.#beforeDisconnect();
			this.#reconnect();
			this.checkQosFeatureAndExecutionCallback(() =>
			{
				this.addMonitoringEvents({
					name: MONITORING_EVENTS_NAME_LIST.PEER_CONNECTION_ISSUES,
					value: 1,
				});
			});
		}
		finally
		{
			if (!hasError)
			{
				this.setLog('Handling a remote answer succeeded', LOG_LEVEL.INFO);
				this.#privateProperties.isWaitAnswer = false;
				await this.sendOffer();
			}
		}
	}

	async #offerHandler(data) {
		this.setLog('Handling a remote offer', LOG_LEVEL.INFO);
		try
		{
			if (Util.useTcpSdp())
			{
				data.offer.sdp = this.#removeUdpFromSdp(data.offer.sdp);
			}
			await this.recipient.setRemoteDescription(data.offer);
			this.#privateProperties.pendingCandidates.recipient.forEach((candidate) =>
			{
				this.recipient.addIceCandidate(candidate);
				this.setLog('Added a deferred ICE candidate', LOG_LEVEL.INFO);
			});
			this.#privateProperties.pendingCandidates.recipient = [];
			let answer = await this.recipient.createAnswer();
			if (Util.useTcpSdp())
			{
				answer = {
					type: answer.type,
					sdp: this.#removeUdpFromSdp(answer.sdp),
				};
			}

			await this.recipient.setLocalDescription(answer);
			this.#sendSignal({ answer });
			this.setLog('Handling a remote offer succeeded', LOG_LEVEL.INFO);
		}
		catch (e)
		{
			this.setLog(`Handling a remote offer failed: ${e}`, LOG_LEVEL.ERROR);
			this.#beforeDisconnect();
			this.#reconnect();
			this.checkQosFeatureAndExecutionCallback(() =>
			{
				this.addMonitoringEvents({
					name: MONITORING_EVENTS_NAME_LIST.PEER_CONNECTION_ISSUES,
					value: 2,
				});
			});
		}
	}

	#addIceCandidate(trickle) {
		this.setLog('Start adding an ICE candidate', LOG_LEVEL.INFO);
		try
		{
			const candidate = JSON.parse(trickle.candidateInit);

			if (Util.useTcpSdp() && !candidate.candidate.includes('tcp'))
			{
				return;
			}

			if (trickle.target)
			{
				if (this.recipient.remoteDescription)
				{
					this.recipient.addIceCandidate(candidate);
					this.setLog('Adding an ICE candidate succeeded', LOG_LEVEL.INFO);
					return;
				}

				this.#privateProperties.pendingCandidates.recipient.push(candidate);
				this.setLog('Adding an ICE candidate deferred: has no remote description', LOG_LEVEL.INFO);

			}
			else
			{
				if (this.sender.remoteDescription)
				{
					this.sender.addIceCandidate(candidate);
					this.setLog('Adding an ICE candidate succeeded', LOG_LEVEL.INFO);
					return;
				}

				this.#privateProperties.pendingCandidates.sender.push(candidate);
				this.setLog('Adding an ICE candidate deferred: has no remote description', LOG_LEVEL.INFO);
			}
		}
		catch (e)
		{
			this.setLog(`Adding an ICE candidate failed: ${e}`, LOG_LEVEL.ERROR);
			this.checkQosFeatureAndExecutionCallback(() =>
			{
				this.addMonitoringEvents({
					name: MONITORING_EVENTS_NAME_LIST.PEER_CONNECTION_ISSUES,
					value: 3,
				});
			});
		}
	}

	#setRemoteParticipant(participant) {
		const userId = participant.userId
		const participantEvent = this.#privateProperties.remoteParticipants[userId]
			? 'ParticipantStateUpdated'
			: 'ParticipantJoined'
		const remoteParticipant = new Participant(participant, this.#privateProperties.socketConnect)
		this.#privateProperties.remoteParticipants[userId] = remoteParticipant;
		this.triggerEvents(participantEvent, [remoteParticipant]);
		if (participant.participantTracks)
		{
			Object.values(participant.participantTracks).forEach( track => {
				track.userId = userId
				this.#privateProperties.tracksDataFromSocket[track.sid] = track
				if (track.muted && track.source === MediaStreamsKinds.Microphone)
				{
					remoteParticipant.isMutedAudio = true;
				}
				if (track.muted && track.source === MediaStreamsKinds.Camera)
				{
					remoteParticipant.isMutedVideo = true;
				}
				switch (track.source)
				{
					case MediaStreamsKinds.Camera:
						remoteParticipant.videoEnabled = true;
						break;
					case MediaStreamsKinds.Microphone:
						remoteParticipant.audioEnabled = true;
						break;
					case MediaStreamsKinds.Screen:
						remoteParticipant.screenSharingEnabled = true;
						break;
				}

				this.setLog(`A participant with id ${userId} (sid: ${participant.sid}) has a track info with kind ${track.source} (sid: ${track.sid}, waiting for it`, LOG_LEVEL.INFO);

				const ontrackData = this.#privateProperties.ontrackData[track.sid];
				delete this.#privateProperties.ontrackData[track.sid];

				if (ontrackData)
				{
					this.#createRemoteTrack(track.sid, ontrackData);
				}
				else
				{
					this.#addPendingSubscription(participant, track);
				}
			})
		}

		if (this.#privateProperties.videoStreamSetupErrorList[userId]) {
			const kindArray = [...this.#privateProperties.videoStreamSetupErrorList[userId]];
			delete this.#privateProperties.videoStreamSetupErrorList[userId];

			kindArray.forEach((kind) =>
			{
				this.setMainStream(userId, kind);
			});
		}
	};

	#createRemoteTrack(trackId, ontrackData)
	{
		const trackData = this.#privateProperties.tracksDataFromSocket[trackId];
		const userId = trackData.userId;
		const participant = this.#privateProperties.remoteParticipants[userId];
		const track = ontrackData.track;
		const trackMuted = !!trackData.muted;

		this.#privateProperties.realTracksIds[track.id] = trackId;
		track.source = trackData.source;
		track.layers = trackData.layers || null;

		if (!this.#privateProperties.remoteTracks?.[userId])
		{
			this.#privateProperties.remoteTracks[userId] = {}
		}
		const remoteTrack = new Track(trackId, track);
		this.#privateProperties.remoteTracks[userId][trackId] = remoteTrack;

		if (remoteTrack.source === MediaStreamsKinds.Camera)
		{
			participant.isMutedVideo = trackMuted;
		}
		else if (remoteTrack.source === MediaStreamsKinds.Microphone)
		{
			participant.isMutedAudio = trackMuted;
			if (trackMuted)
			{
				this.setLog(`Trigger mute signal (${trackMuted}) for received audio from a participant with id ${participant.userId} (sid: ${participant.sid})`, LOG_LEVEL.INFO);
				this.triggerEvents('RemoteMediaMuted', [participant, remoteTrack]);
			}
		}

		if (this.#privateProperties.pendingSubscriptions[userId]?.[trackId]?.timeout)
		{
			clearTimeout(this.#privateProperties.pendingSubscriptions[userId][trackId].timeout);
			delete this.#privateProperties.pendingSubscriptions[userId][trackId];
		}

		this.setLog(`Got an expected track with kind ${remoteTrack.source} (sid: ${trackId}) for a participant with id ${participant.userId} (sid: ${participant.sid})`, LOG_LEVEL.INFO);
		participant.addTrack(remoteTrack.source, remoteTrack);
		if (remoteTrack.source !== MediaStreamsKinds.Camera || !participant.isMutedVideo)
		{
			this.triggerEvents('RemoteMediaAdded', [participant, remoteTrack]);
		}

		if (remoteTrack.source === MediaStreamsKinds.Camera)
		{
			const quality = this.#calculateVideoQualityForUser(userId, remoteTrack.source);
			this.setLog(`Quality of video for a participant with id ${participant.userId} (sid: ${participant.sid}) was changed to ${quality} after receiving`, LOG_LEVEL.INFO);
			participant.setStreamQuality(quality);
		}

		if (track.kind === 'video')
		{
			const streamRemovingId = Util.getUuidv4();
			participant.streamRemovingId = streamRemovingId;
			ontrackData.streams[0].onremovetrack = () =>
			{
				// we need to check if a participant is still exists
				// otherwise tracks were deleted when participant left room
				const participant = this.#privateProperties.remoteParticipants[userId];
				if (participant?.streamRemovingId === streamRemovingId)
				{
					this.setLog(`Track with kind ${track.source} (sid: ${track.id}) for a participant with id ${userId} (sid: ${participant.sid || 'unknown'}) was removed from peer connection`, LOG_LEVEL.WARNING);
					this.triggerEvents('RemoteMediaRemoved', [participant, remoteTrack]);
				}
				else
				{
					this.setLog(`Track with kind ${track.source} (sid: ${track.id}) was removed from a disconnected participant with id ${userId} (sid: unknown) before it was removed from peer connection`, LOG_LEVEL.WARNING);
				}
			};
		}
	}

	#speakerChangedHandler(data) {
		data.speakersChanged.speakers.forEach((speaker) => {
			const participant = Object.values(this.#privateProperties.remoteParticipants).find(p => p.sid === speaker.sid)
			if (participant && participant?.userId != this.#privateProperties.userId) {
				participant.isSpeaking = speaker?.active || false
				if (speaker?.active) {
					this.triggerEvents('VoiceStarted', [participant]);
				} else {
					this.triggerEvents('VoiceEnded', [participant]);
				}
			}
		})
	}

	#createPeerConnection() {
		this.#destroyPeerConnection()

		const config = {};
		if (this.#privateProperties.iceServers)
		{
			config.iceServers = this.#privateProperties.iceServers;
		}

		this.sender = new RTCPeerConnection(config);
		this.sender.addEventListener('icecandidate', e => this.onIceCandidate(null, e));
		this.sender.addEventListener('connectionstatechange', e => this.onConnectionStateChange());

		this.recipient = new RTCPeerConnection(config);
		this.recipient.ontrack = (event) => {
			const ids = event.streams[0].id.split('|');
			const trackId = ids[1];
			const userId = this.#privateProperties.tracksDataFromSocket[trackId]?.userId;

			if (this.#privateProperties.remoteParticipants[userId] && this.#privateProperties.tracksDataFromSocket[trackId])
			{
				this.#createRemoteTrack(trackId, event);
			}
			else
			{
				this.setLog(`Got a track with kind ${event.track.kind} (sid: ${trackId}) without a participant, saving it`, LOG_LEVEL.WARNING);
				this.#privateProperties.ontrackData[trackId] = event;
			}
		};
		this.recipient.addEventListener('icecandidate', e => this.onIceCandidate('SUBSCRIBER', e));
		this.recipient.addEventListener('connectionstatechange', e => this.onConnectionStateChange(true));

		const getStatsHandle = async () => {
			try
			{
				const statsAll = {};
				let packetLostEventAdded = false;
				await this.sender.getStats(null).then((stats) =>
				{
					let statsOutput = [];
					const codecs = {};
					const reportsWithoutCodecs = {};
					const remoteReports = {};
					const reportsWithoutRemoteInfo = {};
					let isQualityLimitationSent = false;
					let totalBitrateOut = 0;

					stats.forEach((report) =>
					{
						statsOutput.push(report);


						if (report.type === 'codec')
						{
							Util.processReportsWithoutCodecs(report, codecs, reportsWithoutCodecs);
						}

						if (report.type === 'remote-inbound-rtp')
						{
							const reportId = report.localId;
							if (reportsWithoutRemoteInfo[reportId])
							{
								const packetsLostData = Util.calcLocalPacketsLost(reportsWithoutRemoteInfo[reportId], this.#privateProperties.reportsForOutgoingTracks[reportId], report);
								const { currentPercentPacketLost } = packetsLostData;

								this.checkQosFeatureAndExecutionCallback(() =>
								{
									this.currentMonitoringEventsObject.metrics.PACKET_LOST_SEND.push(currentPercentPacketLost);
								});

								if (!packetLostEventAdded && currentPercentPacketLost > this.#privateProperties.packetLostThreshold)
								{
									packetLostEventAdded = true;
									this.checkQosFeatureAndExecutionCallback(() =>
									{
										this.addMonitoringEvents({
											name: MONITORING_EVENTS_NAME_LIST.HIGH_PACKET_LOSS_SEND,
											withCounter: true
										});
									});
								}

								reportsWithoutRemoteInfo[reportId].packetsLostData = packetsLostData;
								reportsWithoutRemoteInfo[reportId].packetsLost = packetsLostData.totalPacketsLost;
								reportsWithoutRemoteInfo[reportId].packetsLostExtended = Util.formatPacketsLostData(packetsLostData);
								this.#privateProperties.reportsForOutgoingTracks[reportId] = reportsWithoutRemoteInfo[reportId];
								delete reportsWithoutRemoteInfo[reportId];
								return;
							}

							remoteReports[report.localId] = report;
						}

						if (report.type === 'outbound-rtp')
						{
							report.bitrate = Util.calcBitrate(report, this.#privateProperties.reportsForOutgoingTracks[report.id], true);
							report.userId = this.#privateProperties.userId;

							if (report.kind === 'audio')
							{
								report.source = MediaStreamsKinds.Microphone;
							}
							else if (report.kind === 'video' )
							{
								report.source = report.contentType === 'screenshare'
									? MediaStreamsKinds.Screen
									: MediaStreamsKinds.Camera;
							}

							totalBitrateOut += report.bitrate;

							if (report.qualityLimitationReason && report.qualityLimitationReason !== 'none' && !isQualityLimitationSent)
							{
								this.checkQosFeatureAndExecutionCallback(() =>
								{
									if (report.qualityLimitationReason === 'cpu') {
										this.addMonitoringEvents({
											name: MONITORING_EVENTS_NAME_LIST.CPU_ISSUES,
											withCounter: true
										});
									}

									if (report.qualityLimitationReason === 'bandwidth') {
										this.addMonitoringEvents({
											name: MONITORING_EVENTS_NAME_LIST.NETWORK_ISSUES,
											withCounter: true
										});
									}
								});

								isQualityLimitationSent = true;
								this.setLog(`Local user have problems with sending video: ${report.qualityLimitationReason} (${Object.entries(report.qualityLimitationDurations).reduce(
									(accumulator, value, index) => accumulator + `${index ? ', ' : ''}` + `${value[0]}: ${value[1]}`,
									'',
								)})`, LOG_LEVEL.WARNING);
							}

							if (!Util.setCodecToReport(report, codecs, reportsWithoutCodecs))
							{
								Util.saveReportWithoutCodecs(report, reportsWithoutCodecs);
							}
							if (Util.setLocalPacketsLostOrSaveReport(report, remoteReports, reportsWithoutRemoteInfo))
							{
								this.#privateProperties.reportsForOutgoingTracks[report.id] = report;
							}
						}
					});

					this.checkQosFeatureAndExecutionCallback(() =>
					{
						const formattedBitrateOut = Number.parseFloat((totalBitrateOut / 1000000).toFixed(2));

						if (this.currentMonitoringEventsObject.metrics.BITRATE_OUT.length < this.countMetricsInMetricsInterval)
						{
							this.currentMonitoringEventsObject.metrics.BITRATE_OUT.push(formattedBitrateOut);
						}
					});

					statsAll.sender = statsOutput;
				});

				await this.recipient.getStats(null).then((stats) =>
				{
					let statsOutput = [];
					const participantsWithLargeDataLoss = new Map();
					const codecs = {};
					const reportsWithoutCodecs = {};
					let totalBitrateIn = 0;
					let currentPacketLostReceiveCount = 0;

					const inboundRtpStatsArray = {
						freezeCount: [],
						totalFreezesDuration: [],
						jitter: [],
						framesDecoded: [],
						framesDropped: [],
						framesReceived: [],
						framesLoss: [],
					};

					stats.forEach((report) =>
					{
						if (report.type === 'inbound-rtp' && report.kind === 'video')
						{
							const { freezeCount, totalFreezesDuration, jitter, framesDecoded, framesDropped, framesReceived } = report;

							inboundRtpStatsArray.jitter.push(jitter);
							inboundRtpStatsArray.totalFreezesDuration.push(totalFreezesDuration);
							inboundRtpStatsArray.freezeCount.push(freezeCount);
							inboundRtpStatsArray.framesDecoded.push(framesDecoded);
							inboundRtpStatsArray.framesDropped.push(framesDropped);
							inboundRtpStatsArray.framesReceived.push(framesReceived);
							inboundRtpStatsArray.framesLoss.push((framesDropped / framesReceived) * 100);
						}

						statsOutput.push(report);

						const needCheckPacketLosts = (report?.trackIdentifier
							&& report.hasOwnProperty('packetsLost')
							&& report.hasOwnProperty('packetsReceived'));

						if (needCheckPacketLosts)
						{
							const packetsLostData = Util.calcRemotePacketsLost(report, this.#privateProperties.reportsForIncomingTracks[report.trackIdentifier]);

							report.packetsLostExtended = Util.formatPacketsLostData(packetsLostData);
							this.#privateProperties.reportsForIncomingTracks[report.trackIdentifier] = report;

							const realTrackId = this.#privateProperties.realTracksIds[report.trackIdentifier];
							const track = this.#privateProperties.tracksDataFromSocket[realTrackId];
							if (track)
							{
								const prevReport = track.report || {};
								track.report = report;

								report.bitrate = Util.calcBitrate(report, prevReport);
								report.userId = track.userId;
								report.source = track.source;

								const { currentPercentPacketLost } = packetsLostData;

								if (track.source === MediaStreamsKinds.Camera)
								{
									currentPacketLostReceiveCount = currentPercentPacketLost;
								}

								totalBitrateIn += report.bitrate;

								if (!Util.setCodecToReport(report, codecs, reportsWithoutCodecs))
								{
									Util.saveReportWithoutCodecs(report, reportsWithoutCodecs);
								}
							}

							if (packetsLostData.currentPercentPacketLost > this.#privateProperties.packetLostThreshold)
							{
								const participant = Object.values(this.#privateProperties.remoteParticipants)
									.find(p => p?.tracks?.[MediaStreamsKinds.Camera]?.track?.id === report.trackIdentifier);
								if (participant && participant.userId != this.#privateProperties.userId)
								{
									participantsWithLargeDataLoss.set(participant.userId, `userId: ${ participant.userId} (${packetsLostData.currentPercentPacketLost}%)`);
									this.#privateProperties.prevParticipantsWithLargeDataLoss.delete(participant.userId);
								}
							}
						}

						if (report.type === 'codec')
						{
							Util.processReportsWithoutCodecs(report, codecs, reportsWithoutCodecs);
						}
					});

					this.checkQosFeatureAndExecutionCallback(() =>
					{
						const { countVideoTracks, countAudioTracks } = this.getCountRemoteTracks();

						const round = (number, decimal) => Math.round(number * Math.pow(10, decimal)) / Math.pow(10, decimal);

						const addValueInboundRTCStatsToMetrics = ({ key, metricsKey, isSum, decimal = 0, interval }) =>
						{
							if (this.currentMonitoringEventsObject.metrics[metricsKey].length >= this.countMetricsInMetricsInterval)
							{
								return;
							}

							const inboundRtpStatsArraySumByKey = inboundRtpStatsArray[key].reduce((acc, number) => acc + number, 0) / (interval ? (this.#privateProperties.statsTimeout / 1000) : 1);
							const prevInboundRtpStatsArraySumByKey = this.prevInboundRtpStatsSum[key];
							const diffInboundRtpStatsArraySumByKey = prevInboundRtpStatsArraySumByKey && !!isSum ? inboundRtpStatsArraySumByKey - prevInboundRtpStatsArraySumByKey : inboundRtpStatsArraySumByKey;
							const metricValue = !!countVideoTracks ? diffInboundRtpStatsArraySumByKey / countVideoTracks : 0;

							this.currentMonitoringEventsObject.metrics[metricsKey].push(round(metricValue, decimal));
							this.prevInboundRtpStatsSum[key] = inboundRtpStatsArraySumByKey;
						}

						addValueInboundRTCStatsToMetrics({ key: 'jitter', metricsKey: 'JITTER', isSum: false, decimal: 3 });
						addValueInboundRTCStatsToMetrics({ key: 'freezeCount', metricsKey: 'FREEZE_COUNT', isSum: true, interval: true });
						addValueInboundRTCStatsToMetrics({ key: 'totalFreezesDuration', metricsKey: 'TOTAL_FREEZE_DURATION', isSum: true, decimal: 3, interval: true });
						addValueInboundRTCStatsToMetrics({ key:'framesDecoded', metricsKey:'FRAMES_DECODED', isSum:true, interval: true });
						addValueInboundRTCStatsToMetrics({ key:'framesDropped', metricsKey: 'FRAMES_DROPPED', isSum: true, interval: true });
						// addValueInboundRTCStatsToMetrics({ key: 'framesLoss', metricsKey: 'FRAMES_LOSS', isSum: true });

						if (this.currentMonitoringEventsObject.metrics.FRAMES_LOSS.length < this.countMetricsInMetricsInterval)
						{
							const currentFramesReceived = this.currentMonitoringEventsObject.metrics.FRAMES_RECEIVED.slice(-1)[0];
							const currentFramesDropped = this.currentMonitoringEventsObject.metrics.FRAMES_DROPPED.slice(-1)[0];
							const currentFramesLoss = !!currentFramesReceived ? currentFramesDropped / currentFramesReceived : 0;
							this.currentMonitoringEventsObject.metrics.FRAMES_LOSS.push(currentFramesLoss > 0 ? round(currentFramesLoss * 100, 0)  : 0);
						}

						const formattedBitrateIn = Number.parseFloat((totalBitrateIn / 1000000).toFixed(2));

						if (this.currentMonitoringEventsObject.metrics.BITRATE_IN.length < this.countMetricsInMetricsInterval)
						{
							this.currentMonitoringEventsObject.metrics.BITRATE_IN.push(formattedBitrateIn);
						}

						if (this.currentMonitoringEventsObject.metrics.PACKET_LOST_RECEIVE.length < this.countMetricsInMetricsInterval)
						{
							this.currentMonitoringEventsObject.metrics.PACKET_LOST_RECEIVE.push(currentPacketLostReceiveCount);
						}

						if (currentPacketLostReceiveCount > this.#privateProperties.packetLostThreshold)
						{
							this.addMonitoringEvents({name: MONITORING_EVENTS_NAME_LIST.HIGH_PACKET_LOSS_RECEIVE, withCounter: true} )
						}
					});

					statsAll.recipient = statsOutput;
					if (participantsWithLargeDataLoss.size || this.#privateProperties.prevParticipantsWithLargeDataLoss.size)
					{
						if (participantsWithLargeDataLoss.size)
						{
							this.setLog(`Have high packetsLost on users: ${[...participantsWithLargeDataLoss.values()]}`, LOG_LEVEL.WARNING);
						}
						this.triggerEvents('UpdatePacketLoss', [[...participantsWithLargeDataLoss.keys()]] );
					}
					this.#privateProperties.prevParticipantsWithLargeDataLoss = participantsWithLargeDataLoss;
				});

				this.triggerEvents('CallStatsReceived', [statsAll]);
			}
			catch (e)
			{
				// if we're here it's almost okay
				// we tried to get stats during reconnection
			}
		}


		getStatsHandle().finally(() => {
			this.checkQosFeatureAndExecutionCallback(() =>
			{
				this.startAggregateMonitoringEvents();
			});
			this.#privateProperties.callStatsInterval = setInterval( async () => {
				await getStatsHandle()
			}, this.#privateProperties.statsTimeout);
		});
	}

	#destroyPeerConnection() {
		if (this.sender) {
			this.sender.close()
			this.sender = null
		}

		if (this.recipient) {
			this.recipient.close()
			this.recipient = null
		}

		this.#privateProperties.peerConnectionFailed = false;
	}

	#releaseStream(kind) {
		let streamType

		switch (kind) {
			case MediaStreamsKinds.Camera:
				streamType = 'cameraStream'
				break
			case MediaStreamsKinds.Microphone:
				streamType = 'microphoneStream'
				break
			case MediaStreamsKinds.Screen:
				streamType = 'screenStream'
				break
		}

		if (streamType) {
			this.#privateProperties[streamType]?.getTracks?.()?.forEach( track => {
				track.onended = null
				track.stop()
			})
			this.#privateProperties[streamType] = null
		}
	}

	#getSender(kind) {
		const senders = this.sender?.getSenders?.();
		let sender = null;

		if (senders?.length > 0) {
			for (const s of senders) {
				if (s.track?.source === kind) {
					sender = s
					break;
				}
			}
		}

		return sender;
	}

	#sendSignal(signal) {
		if (this.#privateProperties.socketConnect?.readyState === 1)
		{
			this.#privateProperties.socketConnect.send(JSON.stringify(signal));
			return true;
		}

		return false;
	}

	#sendLeave() {
		if (this.#privateProperties.socketConnect?.readyState === 1) {
			this.#sendSignal({
				leave:  {
					reason: 'CLIENT_INITIATED'
				}
			});
			this.checkQosFeatureAndExecutionCallback(() =>
			{
				this.sendMonitoringEvents(false);
			});
		}
	}
}

class Participant {
	#socketConnect;

	name = '';
	image = '';
	userId = '';
	videoEnabled = false;
	audioEnabled = false;
	screenSharingEnabled = false;
	isSpeaking = false;
	tracks = {};
	sid = '';
	isMutedVideo = false;
	isMutedAudio = false;
	isHandRaised = false;
	videoPaused = false;
	isLocalVideoMute = false;
	streamRemovingId = '';
	cameraStreamQuality: STREAM_QUALITY.MEDIUM;

	constructor(participant, socket) {
		this.name = participant?.name || '';
		this.image = participant?.image || '';
		this.userId = participant?.userId || '';
		this.sid = participant?.sid || '';
		this.videoEnabled = participant?.videoEnabled || false;
		this.audioEnabled = participant?.audioEnabled || false;
		this.screenSharingEnabled = participant?.screenSharingEnabled || false;
		this.isSpeaking = participant?.isSpeaking || false;
		this.isHandRaised = participant?.isHandRaised || false;
		this.#socketConnect = socket;
	}

	subscribeTrack(MediaStreamKind) {};

	unsubscribeTrack(MediaStreamKind) {};

	attachTrack(MediaStreamKind) {};

	detachTrack(MediaStreamKind) {};

	disableAudio() {
		this.tracks[MediaStreamsKinds.Microphone].track.enabled = false;
		this.isMutedAudio = true;
	};

	enableAudio() {
		this.tracks[MediaStreamsKinds.Microphone].track.enabled = true;
		this.isMutedAudio = false;
	};

	disableVideo() {
		this.tracks[MediaStreamsKinds.Camera].track.enabled = false;
		this.isMutedVideo = true;
	};

	enableVideo() {
		this.tracks[MediaStreamsKinds.Camera].track.enabled = true;
		this.isMutedVideo = false;
	};

	addTrack(MediaStreamKind, Track) {
		this.tracks[MediaStreamKind] = Track;
	};

	removeTrack(MediaStreamKind, Track) {
		delete this.tracks[MediaStreamKind];
	};

	getTrack(MediaStreamKind) {
		return this.tracks?.[MediaStreamKind]
	}

	setStreamQuality(quality) {
		if (this.cameraStreamQuality === quality || this.videoPaused)
		{
			return;
		}

		if (this.tracks?.[MediaStreamsKinds.Camera]) {
			this.cameraStreamQuality = quality;
			const trackId = this.tracks[MediaStreamsKinds.Camera].id;
			this.tracks[MediaStreamsKinds.Camera].track.currentVideoQuality =  quality;
			const signal = {
				trackSetting: {
					trackSids: [trackId],
					quality: quality,
				}
			};

			this.#socketConnect.send(JSON.stringify(signal));
		}
	}
}

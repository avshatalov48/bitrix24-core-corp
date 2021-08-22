/**
* @bxjs_lang_path component.php
*/
BX.listeners = {};

// region Constants

var Sound = {
	incoming: "incoming",
	startCall: "startcall"
};

var TelephonyUiState = {
	INCOMING: "INCOMING",
	FINISHED: "FINISHED",
	STARTED: "STARTED",
	OUTGOING: "OUTGOING",
	WAITING: "WAITING",
	CALLBACK: "CALLBACK"
};

var TelephonyUiEvent = {
	onHangup: "onHangupCallClicked",
	onSpeakerphoneChanged: "onSpeakerphoneCallClicked",
	onMuteChanged: "onMuteCallClicked",
	onPauseChanged: "onPauseCallClicked",
	onNumpadClicked: "onNumpadClicked",
	onFormFolded: "onFoldCallClicked",
	onFormExpanded: "onUnfoldIconClicked",
	onCloseClicked: "onCloseCallClicked",
	onSkipClicked: "onSkipCallClicked",
	onAnswerClicked: "onAnswerCallClicked",
	onNumpadClosed: "onNumpadClosed",
	onNumpadButtonClicked: "onNumpadButtonClicked",
	onPhoneNumberReceived: "onPhoneNumberReceived",
	onContactListChoose: "onContactListChoose",
	onContactListMenuChoose: "onContactListMenuChoose"
};

var CallEvent = {
	Connected: "onCallConnected",
	Disconnected: "onCallDisconnected",
	Failed: "onCallFailed",
	ProgressToneStart: "onProgressToneStart",
	ProgressToneStop: "onProgressToneStop"
};

var CallState = {
	CONNECTED: "connected",
	DISCONNECTED: "disconnected",
	CONNECTING: "connecting"
};

var RestMethods = {
	create: "im.call.create",
	invite: "im.call.invite",
	answer: "im.call.answer",
	decline: "im.call.decline",
	ping: "im.call.ping",
	negotiationneeded: "im.call.negotiationneeded",
	connectionoffer: "im.call.connectionoffer",
	connectionanswer: "im.call.connectionanswer",
	icecandidate: "im.call.icecandidate",
	hangup: "im.call.hangup"
};

var CallType = {
	Instant: 1,
	Permanent: 2
};

var CallProvider = {
	Plain: 'Plain',
	Voximplant: 'Voximplant',
	Janus: 'Janus'
};

var eventTimeRange = 30;
var crmPathTemplate = "mobile/crm/#ENTITY#/?page=view&#ENTITY#_id=#ID#";
var entityTypes = ["lead", "contact", "company", "deal"];

var BLANK_AVATAR = '/bitrix/js/im/images/blank.gif';

// endregion Constants

// region Common functions
function getSecondsAgo(timestamp)
{
	let now = (new Date()).getTime();
	return Math.round(Math.abs(now - timestamp) / 1000);
}

function preparePush(push)
{
	if (typeof (push) !== 'object' || typeof (push.params) === 'undefined')
	{
		return {'ACTION': 'NONE'};
	}

	let result = {};
	try {
		result = JSON.parse(push.params);
	}
	catch (e)
	{
		result = {'ACTION': push.params};
	}

	return result;
}

function getCrmShowPath(entityType, entityId)
{
	entityType = entityType.toLowerCase();
	if(entityTypes.indexOf(entityType) === -1)
		return "";

	return currentDomain + BX.componentParameters.get("siteDir", "/") +  crmPathTemplate.replace(/#ENTITY#/g, entityType).replace(/#ID#/g, entityId);
}

function decodeHtml(input)
{
	intput = input.toString();
    return input.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&amp;/g, '&').replace(/&nbsp;/g, ' ');
}

function getUuidv4()
{
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
		return v.toString(16);
	});
}

// endregion Common functions

// region Mobile Webrtc

if(typeof calls == "undefined")
{
	include("Calls");
	var calls = new WebRTC();
}

if (Application.getApiVersion() >= 36 && typeof media == "undefined")
{
	include("media");
}

var callsModuleWrapper = function ()
{

};

callsModuleWrapper.prototype =
    {
		useCustomTurnServer: BX.componentParameters.get('useCustomTurnServer', false),
		turnServer: BX.componentParameters.get('turnServer', ''),
		turnServerLogin: BX.componentParameters.get('turnServerLogin', ''),
		turnServerPassword: BX.componentParameters.get('turnServerPassword', ''),

        UI: {
            state: {
                "OUTGOING_CALL": "outgoing_call",
                "INCOMING_CALL": "incoming_call",
                "CONVERSATION": "conversation",
                "FAIL_CALL": "fail_call"
            },
            show: function (state, options)
            {
                var params = options || {};
                params.state = state;
                calls.showUi(params);

            },
            close: function (params)
            {
                calls.closeUi(params);
            },
            showLocalVideo: function (params)
            {
                calls.showLocalVideo(params);
            }
        },
        createPeerConnection: function (params)
        {
        	if(this.useCustomTurnServer && this.turnServer && 'setIceServers' in calls)
			{
				var turnConfig = [
					{
						urls: "stun:" + this.turnServer
					},
					{
						urls: "turn:" + this.turnServer,
						username: this.turnServerLogin,
						credential: this.turnServerPassword
					}
				];

				console.log("setting custom TURN servers");
				calls.setIceServers(turnConfig)
			}

            calls.createPeerConnection();
        },
        destroyPeerConnection: function (params)
        {
            calls.destroyPeerConnection();
        },
        createOffer: function (params)
        {
            calls.createOffer();
        },
        createAnswer: function (params)
        {
            calls.createAnswer(params);
        },
        addIceCandidates:function (params)
        {
            calls.addIceCandidates(params);
        },
        setRemoteDescription:function (params)
        {
			console.log("setRemoteDescription");
            calls.setRemoteDescription(params);
        },
        getUserMedia:function (params)
        {
            calls.getUserMedia(params);
        },
        onReconnect:function (params)
        {
        	console.log("onReconnect");

            calls.onReconnect(params);
        },
        setEventListeners:function (params)
        {
            calls.setEventListeners(params);
        }
    };

var webrtc = new callsModuleWrapper();

MobileWebrtc = function ()
{
    this.siteDir = (typeof mobileSiteDir == "undefined" ? "/" : mobileSiteDir);
    this.callUserId = 0;
    this.debug = true;
    this.incomingCallTimeOut = 2;
    this.delayedIncomingCall = {};
    this.incomingCallTimeOutId = null;
    this.callChatId = 0;
    this.callId = 0;

	this.peerConnectionId = '';
	this.callInstanceId = '';

	this.opponentReady = false;
	this.opponentIsMobile = false;
    this.waitTimeout = false;
    this.cancelCallTimeout = 0;
    this.callGroupUsers = [];
    this.sessionDescription = {};
    this.remoteSessionDescription = {};
    this.iceCandidates = [];
    this.iceCandidatesToSend = [];
    this.iceCandidateTimeout = 0;
    this.connectedAtLeastOnce = false;
    this.userData = {};
    this.redialParameters = {
		userId: 0,
		video: false
	};

	this.ready = false;
	this.peerConnectionInited = false;
	this.iceConnectionState = '';

	this.mediaCallback = false;

	this.connectionAttempt = 0;
	this.waitConnectionAnswerTimeout = null;
	this.waitConnectionOfferTimeout = null;

	this.signalingHandlers = {
		'Call::answer': this.logged('Call::answer', this.onPullCommandAnswer.bind(this)),
		'Call::hangup': this.logged('Call::hangup', this.onPullCommandHangup.bind(this)),
		'Call::finish': this.logged('Call::finish', this.onPullCommandFinish.bind(this)),
		'Call::ping': this.logged('Call::ping', this.onPullCommandPing.bind(this)),
		'Call::negotiationNeeded': this.logged('Call::negotiationNeeded', this.onPullCommandNegotiationNeeded.bind(this)),
		'Call::connectionOffer': this.logged('Call::connectionOffer', this.onPullCommandConnectionOffer.bind(this)),
		'Call::connectionAnswer': this.logged('Call::connectionAnswer', this.onPullCommandConnectionAnswer.bind(this)),
		'Call::iceCandidate': this.logged('Call::iceCandidate', this.onPullCommandIceCandidate.bind(this)),
		'Call::voiceStarted': this.logged('Call::voiceStarted', this.onPullCommandVoiceStarted.bind(this)),
		'Call::voiceStopped': this.logged('Call::voiceStopped', this.onPullCommandVoiceStopped.bind(this)),
		'Call::usersInvited': this.logged('Call::usersInvited', this.onPullCommandUsersInvited.bind(this)),
	};

	this.init();
};

MobileWebrtc.prototype.init = function ()
{
	BX.addCustomEvent("onCallInvite", e =>
	{
		console.log("onCallInvite: ", e);
		if(e['userData'])
		{
			this.appendUserData(e['userData']);
		}
		if ('userId' in e)
		{
			this.startCall(e.userId, e.video);
		}
		else if ('dialogId' in e && !e.dialogId.startsWith('chat'))
		{
			this.startCall(e.dialogId, e.video);
		}
		else
		{
			navigator.notification.alert(BX.message("MOBILE_CALL_UNSUPPORTED_VERSION"));
		}
	});
	BX.addCustomEvent("onPullEvent-im", this.onPullEvent.bind(this));
	BX.addCustomEvent("onPullClientEvent-im", this.onPullClientEvent.bind(this));

	BX.addCustomEvent("onAppActive", this.onAppActive.bind(this));
	this.onAppActive();

	this.attachListeners();
	this.checkActiveCall();
};

MobileWebrtc.prototype.checkActiveCall = function ()
{
	if(!call || call.currentState != 'onConnectCall')
	{
		//console.error("wrong call " + call.payload.id + " state ", call.currentState);
		return;
	}

	console.log("starting call ", call);

	this.onCallKitConnectCall(call.payload);
};

MobileWebrtc.prototype.onCallKitConnectCall = function(data)
{
	let callId = data.id;
	let video = false;

	let extra = {
		server_time_ago: 5
	};

	let callParams;
	let pushParams;

	try {
		pushParams = JSON.parse(data.params)
	}
	catch (e)
	{
		console.error(e);
		return;
	}

	console.warn(pushParams);

	if(Application.isBackground())
	{
		console.warn("waking up p&p");
		BX.postComponentEvent("onPullForceBackgroundConnect", [], "communication");
	}

	callParams = pushParams.PARAMS;

	this.onPullCommandInvite(callParams, extra).then(() =>
	{
		setTimeout(() =>
		{
			webrtc.UI.show(
				webrtc.UI.state.CONVERSATION,
				{
					"data": {},
					"video": this.video,
					"caller": {
						"name": this.getUserName(this.callUserId),
						"avatar": this.getUserAvatar(this.callUserId)
					}
				}
			);

			setTimeout(() =>
			{
				this.onUiAnswer();
			}, 1100);

		}, 50);
	}).catch(err => console.error(err));
};

MobileWebrtc.prototype.onCallKitEndCall = function(data)
{
	console.log("onCallKitEndCall", data);
	//todo: check call uuid

	if(this.callId > 0)
	{
		this.connectedAtLeastOnce ? this.sendHangup() : this.sendDecline();
		this.finishDialog();
		this.resetState();
	}
};

MobileWebrtc.prototype.onCallKitOutgoingCall = function(data)
{
	console.log("onCallKitOutgoingCall", data);

	if (this.callId > 0)
	{
		console.error("Call already exists");
		return;
	}

	var userId = data.senderId;
	var video = data.hasVideo;

	if(!userId)
	{
		navigator.notification.alert(BX.message("IM_M_CALL_ERR_NO_USER_ID"));
		return;
	}

	this.startCall(userId, video);
};

MobileWebrtc.prototype.onAppActive = function ()
{
	let push = preparePush(Application.getLastNotification());
	if (push.TAG)
	{
		push.ACTION = push.TAG;
	}
	if (push.ACTION && push.ACTION.substr(0, 6) === 'IMINV_' && push.PARAMS)
	{
		console.log('Starting with PUSH: ', push);

		let pushParams = push.ACTION.split("_");
		let callId = parseInt(pushParams[1]);
		let callTime = parseInt(pushParams[2]);
		let video = ((pushParams.length >=4) && pushParams[3] === "Y");

		let callParams = push.PARAMS;

		let extra = {
			server_time_ago: getSecondsAgo(callTime * 1000)
		};
		//setTimeout(() => this.startCall(userId, video), 1500);
		this.onPullCommandInvite(callParams, extra).then(() =>
		{
			setTimeout(() =>
			{
				webrtc.UI.show(
					webrtc.UI.state.CONVERSATION,
					{
						"data": {},
						"video": this.video,
						"caller": {
							"name": this.getUserName(this.callUserId),
							"avatar": this.getUserAvatar(this.callUserId)
						}
					}
				);

				setTimeout(() =>
				{
					this.onUiAnswer();
				}, 1100);

			}, 50);
		}).catch(err => console.error(err));
	}
};

MobileWebrtc.prototype.signalingLink = function() {
    return currentDomain + '/mobile/ajax.php?mobile_action=calls&'
};

MobileWebrtc.prototype.attachListeners = function ()
{
    calls.setEventListeners(
        {
            //UI callbacks
            "onAnswer": this.logged("onUiAnswer", this.onUiAnswer),
            "onDecline": this.logged("onUiDecline", this.onUiDecline),
            "onCallback": this.logged("onUiCallback", this.onUiCallback),
            "onClose": this.logged("onUiClose", this.onUiClose),
            //WebRTC callbacks
            "onUserMediaSuccess": this.logged("_onUserMediaSuccess", this._onUserMediaSuccess),
            "onDisconnect": this.logged("_onDisconnect", this._onDisconnect),
            "onPeerConnectionCreated": this.logged("_onPeerConnectionCreated", this._onPeerConnectionCreated),
            "onIceCandidateDiscovered": this.logged("_onIceCandidateDiscovered", this._onIceCandidateDiscovered),
            "onLocalSessionDescriptionCreated": this.logged("_onLocalSessionDescriptionCreated", this._onLocalSessionDescriptionCreated),
            "onIceConnectionStateChanged": this.logged("_onIceConnectionStateChanged", this._onIceConnectionStateChanged),
            "onIceGatheringStateChanged": this.logged("_onIceGatheringStateChanged", this._onIceGatheringStateChanged),
            "onSignalingStateChanged": this.logged("_onSignalingStateChanged", this._onSignalingStateChanged),
            "onError": this.logged("_onError", this._onError)
        }
    );
};

/**
 * Returns identifier of the current user
 */
MobileWebrtc.prototype.getUserId = function ()
{
    return parseInt(BX.componentParameters.get('userId', 0), 10);
};

MobileWebrtc.prototype.getUserAvatar = function(userId)
{
	if(!this.userData.hasOwnProperty(userId))
		return '';

	return this.userData[userId]['hrPhoto'] || this.userData[userId]['avatar'] || '';
};

MobileWebrtc.prototype.getUserName = function(userId)
{
	if(!this.userData.hasOwnProperty(userId))
		return '';

	return this.userData[userId]['name'] || '';
};

/**
 * Invites user
 * @param userId
 * @param video
 */
MobileWebrtc.prototype.startCall = function (userId, video)
{
	if (typeof(fabric) === "object")
	{
		fabric.Answers.sendCustomEvent("outgoingCallInternal", {});
	}

	if (userId === this.getUserId() || this.callId)
	{
		return;
	}

    if (this.delayedIncomingCall.chatId && this.delayedIncomingCall.senderId == userId)
    {
        this.clearDelayedCallData();
    }

    this.video = (video === true);
	this.callUserId = parseInt(userId);

	BX.rest.callMethod(RestMethods.create, {
		type: CallType.Instant,
		provider: CallProvider.Plain,
		entityType: 'chat',
		entityId: userId
	}).then(result =>
	{
		let data = result.data();

		if(data.userData)
		{
			this.appendUserData(data.userData);
		}

		this.callId = data.call.ID;
		var callUserId = data.users.filter(userId => userId != this.getUserId())[0];
		this.callUserId = parseInt(callUserId);

		webrtc.UI.show(
			webrtc.UI.state.OUTGOING_CALL,
			{
				"data": {},
				"video": this.video,
				"recipient": {
					"avatar": this.getUserAvatar(this.callUserId),
					"name": this.getUserName(this.callUserId)
				}
			}
		);

		this.sendInvite();
		this.cancelCallTimeout = setTimeout(this.cancelCall.bind(this), 30 * 1000);
	}).catch(err =>
	{
		navigator.notification.alert(err.error_description ? err.error_description() : BX.message("IM_M_CALL_ERR"), () => {}, BX.message("MOBILEAPP_ERROR_AUTH"));
		console.error(err);
		this.finishDialog();
		this.resetState();
	});
};

MobileWebrtc.prototype.cancelCall = function()
{
	if(!this.opponentReady && !this.peerConnectionInited && this.callId)
	{
		BX.rest.callMethod(RestMethods.hangup, {
			callId: this.callId,
			callInstanceId: this.callInstanceId
		}).catch(e => console.error(e));

		this.showError(BX.message('IM_M_CALL_ST_TIMEOUT'));
		this.resetState();
	}
};

/**
 * @param repeat
 */
MobileWebrtc.prototype.sendInvite = function(repeat)
{
	BX.rest.callMethod(RestMethods.invite, {
		callId: this.callId,
		userIds: [this.callUserId],
		video: this.video ? 'Y' : 'N',
		legacyMobile: 'Y'
	}).catch(e => {
		console.error(e);

		this.showError(BX.message('MOBILEAPP_SOME_ERROR'));
		this.resetState();
	});
};

/**
 * Shows incoming call screen
 */
MobileWebrtc.prototype.showIncomingCall = function ()
{
	if (typeof(fabric) === "object")
	{
		fabric.Answers.sendCustomEvent("incomingCallInternal", {});
	}

	let options = {
		"data": {},
		"video": this.video,
		"caller": {
			"name": this.getUserName(this.callUserId),
			"avatar": this.getUserAvatar(this.callUserId)
		}
	};

    webrtc.UI.show(
        webrtc.UI.state.INCOMING_CALL,
      	options
    );
};

/**
 * Clears data for delayed incoming call
 */
MobileWebrtc.prototype.clearDelayedCallData = function ()
{
    // clearTimeout(this.incomingCallTimeOutId);
    this.incomingCallTimeOutId = null;
    this.delayedIncomingCall = {};
};

/**
 * Resets all variables, connection data and states
 */
MobileWebrtc.prototype.resetState = function ()
{
    this.video = false;
    this.callId = 0;
    this.callInstanceId = '';
    this.callChatId = 0;
    this.callUserId = 0;
    this.isMobile = false;
    this.peerConnectionInited = false;
    this.peerConnectionId = '';
    this.iceCandidates = [];
    this.iceCandidatesToSend = [];
    this.opponentReady = false;
    this.opponentIsMobile = false;
    this.connectedAtLeastOnce = false;

    this.connectionAttempt = 0;
    clearTimeout(this.waitConnectionAnswerTimeout);
    clearTimeout(this.waitConnectionOfferTimeout);
    clearTimeout(this.checkConnectionTimeout);

    webrtc.destroyPeerConnection();
};

MobileWebrtc.prototype.storeRedialParameters = function ()
{
	this.redialParameters.userId = this.callUserId;
	this.redialParameters.video = this.video;
};

MobileWebrtc.prototype.resetRedialParameters = function ()
{
	this.redialParameters.userId = 0;
	this.redialParameters.video = false;
};

/**
 * Send command to chat with chatId
 *
 * Available commands and them meaning:
 * <pre>
 * busy - you are already have the conversation with someone and can't pick up the phone
 * busy_self - informs the partner that you already have the conversation with him
 * ready - informs the partner you have front camera and microphone, local video stream is created and you are ready for the peerdata exchange
 * wait - informs the partner to keep waiting for the answer for the 30 seconds
 * decline - informs the partner that you've declined his incoming call or hung up while the call was active
 * </pre>
 * @param command
 * @param params
 */
MobileWebrtc.prototype.callCommand = function (command, params)
{
	params = typeof(params) == 'object' ? params : {};

	let ajaxParams = {
		'COMMAND': command,
		'RECIPIENT_ID': this.callUserId,
		'PARAMS': JSON.stringify(params)
	};

	if(this.callChatId > 0)
	{
		ajaxParams.CHAT_ID = this.callChatId;
	}
	else if(this.callUserId)
	{
		ajaxParams.USER_ID = this.callUserId
	}
	else
	{
		console.log('Could not send call command');
		return;
	}

	console.log('callCommand ', command, ajaxParams);
	this.ajaxCall(
		"CALL_SHARED",
		ajaxParams
	);
};

/**
 * Finishes the conversation with closing UI
 */
MobileWebrtc.prototype.finishDialog = function ()
{
    webrtc.UI.close();
};

MobileWebrtc.prototype.showError = function(errorMessage)
{
	console.trace("error");
	this.storeRedialParameters();
	webrtc.UI.show(
		webrtc.UI.state.FAIL_CALL,
		{
			'message': errorMessage
		}
	);
};

MobileWebrtc.prototype.initReconnect = function ()
{
	this.connectionAttempt++;
	if (this.connectionAttempt > 3)
	{
		console.error("Was not able to establish connection after 3 attempts");
		navigator.notification.alert(BX.message("IM_M_CALL_ERR"), () => {}, BX.message("MOBILEAPP_ERROR_NETWORK"));
		this.finishDialog();
		this.resetState();
		return;
	}

	this.log("Reconnection attempt #" + this.connectionAttempt);

	if(this.isInitiator())
	{
		webrtc.onReconnect();
		this.peerConnectionInited = false;
		this.peerConnectionId = "";
		this.iceCandidates = [];
		this.iceCandidatesToSend = [];
		this.remoteSessionDescription = null;

		webrtc.createPeerConnection();
	}
	else
	{
		this.sendNegotiationNeeded(true);
	}
};

/**
 * Handles peer data signals
 *
 * @param userId
 * @param peerData
 */
MobileWebrtc.prototype.signalingPeerData = function (userId, peerData)
{
};

/**
 * @param reqParam - request param for the ajax request
 * @param reqData - post data
 * @param onfailure - failure callback function
 * @param onsuccess - success callback function
 */
MobileWebrtc.prototype.ajaxCall = function (reqParam, reqData, onsuccess, onfailure)
{
    var data = reqData;
    data["MOBILE"] = "Y";
    data["IS_MOBILE"] = "Y";
    data["IM_CALL"] = "Y";
    data["IM_AJAX_CALL"] = "Y";
    data["sessid"] = BX.bitrix_sessid();

    BX.ajax({
        url: this.signalingLink() + reqParam,
        method: 'POST',
        dataType: 'json',
        timeout: 30,
        async: true,
        data: data,
        onsuccess: onsuccess,
        onfailure: onfailure
    });
};

MobileWebrtc.prototype.appendUserData = function(userData)
{
	if(BX.type.isPlainObject(userData))
	{
		for (let userId in userData)
		{
			this.userData[userId] = userData[userId];
			this.userData[userId]['name'] = decodeHtml(this.userData[userId]['name']);
			this.userData[userId]['hrPhoto'] = userData[userId]['avatar_hr'] || userData[userId]['avatar'];
		}
	}
};

MobileWebrtc.prototype.onUiDecline = function (params)
{
	// looks like this event is fired not only on decline, but on normal hangup too

	if(this.callId > 0)
	{
		this.connectedAtLeastOnce ? this.sendHangup() : this.sendDecline();
	}

    this.resetState();
};

MobileWebrtc.prototype.onUiAnswer = function ()
{
    webrtc.UI.show(
        webrtc.UI.state.CONVERSATION
    );

    this.getLocalMedia({video: this.video}).then(() =>
	{
		console.log("getLocalMedia success, try to execute im.call.answer");
    	BX.rest.callMethod(RestMethods.answer, {
    		callId: this.callId,
			callInstanceId: this.callInstanceId,
			legacyMobile: "Y"
		}).then(response => {
			console.warn("success!");
		}).catch(err =>
		{
			navigator.notification.alert(err.error_description ? err.error_description() : BX.message("IM_M_CALL_ERR"), () => {}, BX.message("MOBILEAPP_ERROR_AUTH"));
			console.error(err);
			this.finishDialog();
			this.resetState();
		});
	});
};

MobileWebrtc.prototype.onUiCallback = function ()
{
    if (this.redialParameters.userId > 0)
	{
		this.startCall(this.redialParameters.userId, this.redialParameters.video);
		this.resetRedialParameters();
	}
};

MobileWebrtc.prototype.onUiClose = function ()
{
    this.resetState();
    this.resetRedialParameters();
};

MobileWebrtc.prototype._onDisconnect = function ()
{
	// looks like this event is fired when UI is closed, not when rtc is disconnected

	if(this.callId > 0)
	{
		this.connectedAtLeastOnce ? this.sendHangup() : this.sendDecline();
	}

	this.resetState();
};

MobileWebrtc.prototype._onIceCandidateDiscovered = function (params)
{
    this.iceCandidatesToSend.push(params.candidate);

    clearTimeout(this.iceCandidateTimeout);
    this.iceCandidateTimeout = setTimeout(this.sendIceCandidates.bind(this), 250);
};

MobileWebrtc.prototype._onPeerConnectionCreated = function ()
{
	clearTimeout(this.checkConnectionTimeout);
    this.peerConnectionInited = true;
    if(!this.peerConnectionId)
	{
		this.peerConnectionId = getUuidv4();
	}
    if (this.isInitiator())
    {
        webrtc.createOffer();
    }
    else {
        webrtc.createAnswer({
            "sdp": this.remoteSessionDescription
        });
    }
};

MobileWebrtc.prototype._onIceConnectionStateChanged = function (state)
{
	this.log("ICE connection state changed " + state);

    this.iceConnectionState = state.toLowerCase();

    if(this.iceConnectionState === "connected")
	{
		this.connectedAtLeastOnce = true;
		this.connectionAttempt = 0;
		clearTimeout(this.waitConnectionOfferTimeout);
		clearTimeout(this.waitConnectionAnswerTimeout);
		clearTimeout(this.checkConnectionTimeout);
	}
    else if (this.iceConnectionState === "failed")
	{
		this.initReconnect();
	}
    else if (this.iceConnectionState === "disconnected")
	{
		this.checkConnectionTimeout = setTimeout(() =>
			{
				this.initReconnect();
			}, 5000
		);
	}
};

MobileWebrtc.prototype._onIceGatheringStateChanged = function (params)
{
    //TODO to do something
};

MobileWebrtc.prototype._onSignalingStateChanged = function (params)
{
    //TODO to do something
};

MobileWebrtc.prototype._onLocalSessionDescriptionCreated = function (params)
{
    this.sessionDescription = params;
    if (this.iceCandidates.length > 0)
    {
    	this.log("Applying pending ice candidates");
        webrtc.addIceCandidates(this.iceCandidates);
        this.iceCandidates = [];
    }

    if(this.isInitiator())
	{
		this.sendConnectionOffer();
	}
	else
	{
		this.sendConnectionAnswer();
	}
};

MobileWebrtc.prototype._onError = function (errorData)
{
	console.error(errorData);
	if (typeof(errorData) !== "object")
	{
		errorData = {}
	}
	if (typeof(errorData.error) !== "string")
	{
		errorData.error = "";
	}
	//TODO handle error better

	if(errorData.code == 400 && errorData.error.startsWith("SDP error"))
	{
		navigator.notification.alert(BX.message("MOBILEAPP_ERROR_VIDEO"), () => {}, BX.message("IM_M_CALL_ERR"));
	}
	else
	{

	}
	this.sendHangup();
	this.finishDialog();
	this.resetState();
};

MobileWebrtc.prototype.onPullEvent = function(command, params, extra)
{
	if(command === 'Call::incoming')
	{
		this.onPullCommandInvite(params, extra).then(this.showIncomingCall.bind(this)).catch(err => console.error(err));
		return;
	}

	if(params['callId'] != this.callId)
	{
		return;
	}

	if (this.signalingHandlers.hasOwnProperty(command))
	{
		this.signalingHandlers[command](params, extra);
	}
};

MobileWebrtc.prototype.onPullClientEvent = function(command, params, extra)
{
	if(params['callId'] != this.callId)
	{
		return;
	}

	if (this.signalingHandlers.hasOwnProperty(command))
	{
		this.signalingHandlers[command](params, extra);
	}
};

MobileWebrtc.prototype.onPullCommandInvite = function(params, extra)
{
	console.log("Incoming call invite", params);

	return new Promise((resolve, reject) =>
	{
		//return reject("debug");
		const isTooLate = extra.server_time_ago >= eventTimeRange;

		if(isTooLate)
		{
			return reject("Call was started too long time ago");
		}

		if(params.users.length > 2)
		{
			return reject("Call to group");
		}

		if(params.call.PROVIDER != 'Plain')
		{
			return reject("Only peer-to-peer calls are supported");
		}

		if(this.callId)
		{
			if (params.call.ID == this.callId)
			{
				return reject("Already processing call " + params.call.ID);
			}
			else
			{
				BX.rest.callMethod(RestMethods.decline, {
					callId: params.call.ID,
					callInstanceId: getUuidv4(),
					code: 486
				});
			}
			return reject("User is busy");
		}

		if(params.userData)
		{
			this.appendUserData(params.userData);
		}

		this.callId = params.call.ID;
		this.callUserId = parseInt(params.senderId);
		this.callInstanceId = getUuidv4();
		this.video = params.video;
		this.opponentIsMobile = params.isMobile === true;

		resolve();
	});
};


MobileWebrtc.prototype.onPullCommandHangup = function(params)
{
	if(params.senderId == this.getUserId())
	{
		if(params.callInstanceId !== this.callInstanceId)
		{
			// This user have declined the call somewhere else
			this.resetState();
			this.finishDialog();
		}

		return;
	}

	this.resetState();
	this.finishDialog();
};

MobileWebrtc.prototype.onPullCommandFinish = function(params)
{
	this.resetState();
	this.finishDialog();
};

MobileWebrtc.prototype.onPullCommandPing = function(params)
{

};

MobileWebrtc.prototype.onPullCommandNegotiationNeeded = function(params)
{
	if(this.isInitiator())
	{
		if(params.restart)
		{
			this.peerConnectionInited = false;
			this.peerConnectionId = "";
			webrtc.onReconnect();
		}

		if(!this.peerConnectionInited)
		{
			webrtc.createPeerConnection();
		}
	}
};

MobileWebrtc.prototype.onPullCommandConnectionOffer = function(params)
{
	clearTimeout(this.waitConnectionOfferTimeout);
	console.log("this.peerConnectionInited: " + (this.peerConnectionInited ? "true" : "false"));
	console.log("this.peerConnectionId: " + this.peerConnectionId);
	console.log("params.connectionId" + params.connectionId);
	if(this.peerConnectionInited && this.peerConnectionId != params.connectionId)
	{
		console.log("New connection " + params.connectionId + " offer received, initializing new connection");
		webrtc.onReconnect();
		this.peerConnectionInited = false;
		this.peerConnectionId = "";
		this.iceCandidates = [];
		this.iceCandidatesToSend = [];
	}

	this.remoteSessionDescription = params.sdp;
	this.peerConnectionId = params.connectionId;
	if(this.peerConnectionInited)
	{
		webrtc.createAnswer({"sdp": this.remoteSessionDescription});
	}
	else
	{
		webrtc.createPeerConnection();
	}
};

MobileWebrtc.prototype.onPullCommandConnectionAnswer = function(params)
{
	if(this.peerConnectionInited && this.peerConnectionId != params.connectionId)
	{
		console.error("Ignoring connection answer for unknown connection " + params.connectionId);
		return;
	}

	clearTimeout(this.waitConnectionAnswerTimeout);
	webrtc.setRemoteDescription({type: "answer", sdp: params.sdp});
};

MobileWebrtc.prototype.onPullCommandIceCandidate = function(params)
{
	var candidates = [];
	for (var i = 0; i < params.candidates.length; i++)
	{
		var candidate = {
			type: 'candidate',
			label: params.candidates[i].sdpMLineIndex,
			id: params.candidates[i].sdpMid,
			candidate: params.candidates[i].candidate
		};
		candidates.push(candidate);
	}
	if (this.peerConnectionInited)
	{
		webrtc.addIceCandidates(candidates);
	}
	else
	{
		this.iceCandidates = this.iceCandidates.concat(candidates);
	}
};

MobileWebrtc.prototype.onPullCommandVoiceStarted = function(params)
{

};

MobileWebrtc.prototype.onPullCommandVoiceStopped = function(params)
{

};

MobileWebrtc.prototype.onPullCommandUsersInvited = function(params)
{

};

MobileWebrtc.prototype.onPullCommandAnswer = function(params)
{
	if(params.senderId == this.getUserId())
	{
		if(params.callInstanceId !== this.callInstanceId)
		{
			// This user have answered the call somewhere else
			console.log("This call is answered by the same user elsewhere");
			this.resetState();
			this.finishDialog();
		}

		return;
	}

	this.opponentReady = true;
	this.opponentIsMobile = params.isMobile === true;
	clearTimeout(this.cancelCallTimeout);

	webrtc.UI.show(webrtc.UI.state.CONVERSATION);
	this.getLocalMedia({video: this.video}).then(() =>
	{
		this.sendMedia();
	});
};

MobileWebrtc.prototype.onPullCommandReconnect = function(params)
{
	if(this.callChatId != params.chatId)
		return;

	webrtc.onReconnect();
};

MobileWebrtc.prototype.getLocalMedia = function(constraints)
{
	return new Promise((resolve, reject) =>
	{
		this.mediaCallback = () =>
		{
			this.mediaCallback = null;
			resolve();
		};

		webrtc.getUserMedia(constraints);
	});
};

MobileWebrtc.prototype._onUserMediaSuccess = function ()
{
	webrtc.UI.showLocalVideo();

	if (BX.type.isFunction(this.mediaCallback))
	{
		this.mediaCallback.call(this, arguments);
	}
};

MobileWebrtc.prototype.startWaitForConnectionOffer = function()
{
	clearTimeout(this.waitConnectionOfferTimeout);

	this.waitConnectionOfferTimeout = setTimeout(() =>
		{
			console.error("Did not receive connection offer in time");
			this.initReconnect();
		},
		10000
	);
};

MobileWebrtc.prototype.startWaitForConnectionAnswer = function()
{
	clearTimeout(this.waitConnectionAnswerTimeout);

	this.waitConnectionAnswerTimeout = setTimeout(() =>
	{
		console.error("Did not receive connection answer in time");
		this.initReconnect();
	},
	10000
	)
};

MobileWebrtc.prototype.sendMedia = function()
{
	if(this.isInitiator())
	{
		if(!this.peerConnectionInited)
		{
			webrtc.createPeerConnection();
		}
	}
	else
	{
		this.sendNegotiationNeeded();
	}
};

MobileWebrtc.prototype.sendConnectionOffer = function()
{
	console.log('Sending connection offer for connection ' + this.peerConnectionId);
	this.startWaitForConnectionAnswer();

	if(!this.callId)
	{
		this.log("Error: call is already finished");
		return;
	}

	BX.rest.callMethod(RestMethods.connectionoffer, {
		callId: this.callId,
		userId: this.callUserId,
		connectionId: this.peerConnectionId,
		sdp: this.sessionDescription.sdp,
		userAgent: 'Bitrix Legacy Mobile'
	}).catch(err => {
		console.error(err);
		navigator.notification.alert(err.error_description ? err.error_description() : BX.message("IM_M_CALL_ERR"), () => {}, BX.message("MOBILEAPP_ERROR_AUTH"));
		this.finishDialog();
		this.resetState();
	});
};

MobileWebrtc.prototype.sendConnectionAnswer = function()
{
	this.log('Sending connection answer for connection ' + this.peerConnectionId);
	if(!this.callId)
	{
		this.log("Error: call is already finished");
		return;
	}
	BX.rest.callMethod(RestMethods.connectionanswer, {
		callId: this.callId,
		userId: this.callUserId,
		connectionId: this.peerConnectionId,
		sdp: this.sessionDescription.sdp,
		userAgent: 'Bitrix Legacy Mobile'
	}).catch(err => {
		console.error(err);
		navigator.notification.alert(err.error_description ? err.error_description() : BX.message("IM_M_CALL_ERR"), () => {}, BX.message("MOBILEAPP_ERROR_AUTH"));
		this.finishDialog();
		this.resetState();
	});

};

MobileWebrtc.prototype.sendNegotiationNeeded = function(restart)
{
	restart = !!restart;
	console.log("sending negotiation needed");

	if(!this.callId)
	{
		this.log("Error: call is already finished");
		return;
	}

	this.startWaitForConnectionOffer();
	BX.rest.callMethod(RestMethods.negotiationneeded, {
		callId: this.callId,
		userId: this.callUserId,
		connectionTag: 'main',
		restart: restart
	}).catch(e => console.error(e));
};

MobileWebrtc.prototype.sendHangup = function()
{
	if(!this.callId)
	{
		this.log("Error: call is already finished");
		return;
	}

	BX.rest.callMethod(RestMethods.hangup, {
		callId: this.callId,
		callInstanceId: this.callInstanceId
	}).catch(e=>console.error(e));
};

MobileWebrtc.prototype.sendDecline = function()
{
	if(!this.callId)
	{
		this.log("Error: call is already finished");
		return;
	}

	BX.rest.callMethod(RestMethods.hangup, {
		callId: this.callId,
		callInstanceId: this.callInstanceId
	}).catch(e=>console.error(e));
};

MobileWebrtc.prototype.sendIceCandidates = function()
{
	if (this.iceCandidatesToSend.length === 0)
		return false;

	console.log("sending ice candidate", {
		callId: this.callId,
		userId: this.callUserId,
		connectionId: this.peerConnectionId,
		candidates: this.iceCandidatesToSend
	});

	if(!this.callId)
	{
		console.log("call is already finished");
		return;
	}

	BX.rest.callMethod(RestMethods.icecandidate, {
		callId: this.callId,
		userId: this.callUserId,
		connectionId: this.peerConnectionId,
		candidates: this.iceCandidatesToSend
	}).catch(err => {
		console.error(err);
		navigator.notification.alert(err.error_description ? err.error_description() : BX.message("IM_M_CALL_ERR"), () => {}, BX.message("MOBILEAPP_ERROR_NETWORK"));
		this.finishDialog();
		this.resetState();
	});

	this.iceCandidatesToSend = [];
};

MobileWebrtc.prototype.isInitiator = function()
{
	return this.getUserId() < this.callUserId;
};

MobileWebrtc.prototype.logged = function(name, cb)
{
	let self = this;
	return function()
	{
		let params = [name].concat(arguments);
		this.log.apply(this, params);
		cb.apply(self, arguments);
	}.bind(this)
};

MobileWebrtc.prototype.lpad = function(str, length, chr)
{
	str = str.toString();
	chr = chr || ' ';

	if(str.length > length)
	{
		return str;
	}

	var result = '';
	for(var i = 0; i < length - str.length; i++)
	{
		result += chr;
	}

	return result + str;
};

MobileWebrtc.prototype.getDateForLog = function()
{
	var d = new Date();
	return d.getFullYear() + "-" + this.lpad(d.getMonth(), 2, '0') + "-" + this.lpad(d.getDate(), 2, '0') + " " + this.lpad(d.getHours(), 2, '0') + ":" + this.lpad(d.getMinutes(), 2, '0') + ":" + this.lpad(d.getSeconds(), 2, '0') + "." + d.getMilliseconds();
};

MobileWebrtc.prototype.log = function()
{
	console.log.apply(null, arguments);

	var text = this.getDateForLog();

	var callId = this.callId;
	if(!callId)
	{
		return;
	}

	for (var i = 0; i < arguments.length; i++)
	{
		try
		{
			text = text+' | '+(typeof(arguments[i]) == 'object'? JSON.stringify(arguments[i]): arguments[i]);
		}
		catch (e)
		{
			text = text+' | (circular structure)';
		}
	}

	if (BX && BX.MessengerDebug)
	{
		//BX.MessengerDebug.addLog(callId, text);
	}
};


// endregion Mobile webrtc

// region Mobile telephony

MobileTelephony = function()
{
	this.ui = null;
	this.mobileVoximplant = null;

	//from checkout
	this.userId = parseInt(BX.componentParameters.get('userId', 0));

	Object.defineProperties(this, {
		isAdmin: {
			get: () => BX.componentParameters.get('isAdmin', false)
		},
		server: {
			get: () => BX.componentParameters.get('voximplantServer', '')
		},
		login: {
			get: () => BX.componentParameters.get('voximplantLogin', '')
		},
		voximplantInstalled: {
			get: () => BX.componentParameters.get('voximplantInstalled', false)
		},
		canPerformCalls: {
			get: () => BX.componentParameters.get('canPerformCalls', false)
		},
		lines: {
			get: () => BX.componentParameters.get('lines',  {})
		},
		defaultLineId: {
			get: () => BX.componentParameters.get('defaultLineId', ''),
			set: (lineId) => BX.componentParameters.set('defaultLineId', lineId)
		},
	})

	this.call = null;
	this.callId = '';
	this.phoneNumber = null;
	this.phoneFullNumber = null;
	this.lineNumber = '';
	this.phoneRinging = 0;
	this.callConfig = {};
	this.callDevice = 'PHONE';
	this.crmData = {};
	this.transferUser = 0;

	// flags
	this.callInit = false;
	this.callActive = false;
	this.debug = true;
	this.connected = false;
	this.authorized = false;
	this.ignoreAnswerSelf = false;
	this.isIncoming = false;
	this.isTransfer = false;
	this.isRestCall = false;
	this.formShown = false;
	this.portalCall = false;

	this.promises = {
		authorization: null,
		connection: null
	};

	this.init();
};

MobileTelephony.prototype.init = function()
{
	this.ui = new VoiceCallForm({
		eventListener: this.onUiEvent.bind(this)
	});

	this.mobileVoximplant = new Voximplant({
		listeners: {
			onIncomingCall: this._onIncomingCall.bind(this),
			onConnectionEstablished: this._onConnectionEstablished.bind(this),
			onConnectionClosed: this._onConnectionClosed.bind(this),
			onConnectionFailed: this._onConnectionFailed.bind(this),
			onAuthResult: this._onAuthResult.bind(this),
			onOneTimeKeyGenerated: this._onOneTimeKeyGenerated.bind(this)
		}
	});

	//VIClient.getInstance().on(VIClient.Events.LogMessage, (m) => console.log(m));

	BX.addCustomEvent("onPhoneTo", this.onPhoneTo.bind(this));
	BX.addCustomEvent("onNumpadRequestShow", this.onNumpadRequestShow.bind(this));
	BX.addCustomEvent("onPullEvent-voximplant", this.onPullEvent.bind(this));
	BX.addCustomEvent("onAppActive", this.onAppActive.bind(this));
	this.onAppActive();
};

MobileTelephony.prototype.onAppActive = function()
{
	let push = preparePush(Application.getLastNotification());
	if (BX.type.isNotEmptyString(push['ACTION']) && push['ACTION'].indexOf('VI_CALL_') === 0)
	{
		this.log("Starting application from push", push);
		let params = push.PARAMS || {};
		if(params.callId)
		{
			this._onCallInvite(params);
		}
	}
};

MobileTelephony.prototype.onPhoneTo = function(e)
{
	this.log("onPhoneTo", e);
	let number = e.number;
	let params = e.params;

	params = params || {};
	if (typeof(params) != 'object')
	{
		try { params = JSON.parse(params); } catch(e) { params = {}; }
	}

	if (this.canUseTelephony())
	{
		this.phoneCall(number, params);
	}
};

MobileTelephony.prototype.onNumpadRequestShow = function()
{
	this.log("onNumpadRequestShow");

	if(Application.getApiVersion() >= 22)
	{
		if (this.canUseTelephony())
		{
			this.ui.showNumpad();
		}
	}
};

MobileTelephony.prototype.canUseTelephony = function()
{
	return this.voximplantInstalled && this.canPerformCalls;
};

MobileTelephony.prototype.onUiEvent = function(params)
{
	this.log("onUiEvent: ", params);
	let eventName = params.eventName;

	let handlers = {
		[TelephonyUiEvent.onHangup]: this._onUiHangup,
		[TelephonyUiEvent.onSpeakerphoneChanged]: this._onUiSpeakerphoneChanged,
		[TelephonyUiEvent.onMuteChanged]: this._onUiMuteChanged,
		[TelephonyUiEvent.onPauseChanged]: this._onUiPauseChanged,
		[TelephonyUiEvent.onCloseClicked]: this._onUiCloseClicked,
		[TelephonyUiEvent.onSkipClicked]: this._onUiSkipClicked,
		[TelephonyUiEvent.onAnswerClicked]: this._onUiAnswerClicked,
		[TelephonyUiEvent.onNumpadButtonClicked]: this._onUiNumpadButtonClicked,
		[TelephonyUiEvent.onPhoneNumberReceived]: this._onUiPhoneNumberReceived,
		//[TelephonyUiEvent.onContactListChoose]: this._onUiContactListChoose,
		//[TelephonyUiEvent.onContactListMenuChoose]: this._onUiContactListMenuChoose
	};

	if(BX.type.isFunction(handlers[eventName]))
	{
		handlers[eventName].call(this, params);
	}
	else if(eventName.substr(0, 4) == "crm_")
	{
		let crmParams = eventName.split("_");
		this._onUiCrmLinkClick({
			'entityType': crmParams[1],
			'entityId': crmParams[2]
		});
	}
};

MobileTelephony.prototype.onPullEvent = function(command, params, extra)
{
	this.log("onPullEvent-voximplant", command, params, extra);

	let handlers = {
		'invite': this._onPullEventInvite.bind(this),
		'answer_self': this._onPullEventAnswerSelf.bind(this),
		'timeout': this._onPullEventTimeout.bind(this),
		'outgoing': this._onPullEventOutgoing.bind(this),
		'start': this._onPullEventStart.bind(this),
		'hold': this._onPullEventHold.bind(this),
		'unhold': this._onPullEventUnHold.bind(this),
		'updatePortalUser': this._onPullEventUpdatePortalUser.bind(this),
		'update_crm': this._onPullEventUpdateCrm.bind(this),
		'completeTransfer': this._onPullEventCompleteTransfer,
		//'phoneDeviceActive': this._onPullEventPhoneDeviceActive,
		'changeDefaultLineId': this._onPullEventChangeDefaultLineId.bind(this),
		'replaceCallerId': this._onPullEventReplaceCallerId.bind(this),
		//'showExternalCall': this._onPullEventShowExternalCall,
		//'hideExternalCall': this._onPullEventHideExternalCall
	};

	if (BX.type.isFunction(handlers[command]))
	{
		handlers[command](params, extra);
	}
};

MobileTelephony.prototype.answerCall = function()
{
	this.setUiState(TelephonyUiState.WAITING);
	this.setUiStateLabel(BX.message('IM_M_CALL_ST_CONNECT'));
	BX.rest.callMethod(
		'voximplant.call.answer', {'CALL_ID' : this.callId}
	).catch(
		(data) =>
		{
			let answer = data.answer;
			this.log('voximplant.call.answer error: ', answer);

			// call could be already finished by this moment
			if(!this.callInit && !this.callActive)
				return;

			this.setUiState(TelephonyUiState.FINISHED);
			this.ui.stopSound();
			if(answer.error === 'ERROR_NOT_FOUND')
				this.setUiStateLabel(BX.message("IM_M_CALL_ALREADY_FINISHED"));
			else if(answer.error === 'ERROR_WRONG_STATE')
				this.setUiStateLabel(BX.message("IM_M_CALL_ALREADY_ANSWERED"));
			else
				this.setUiStateLabel(BX.message("IM_PHONE_ERROR"));
		}
	).then(
		() => this.connect()
	).then(
		() => this.authorize()
	).then(
		() => BX.rest.callMethod('voximplant.call.sendReady', {'CALL_ID' : this.callId})
	);
};

MobileTelephony.prototype.phoneCall = function(number, params)
{
	if (!this.canUseTelephony())
		return false;

	this.log("phoneCall", number, params);
	if (typeof(fabric) === "object")
	{
		fabric.Answers.sendCustomEvent("outgoingCallTelephony", {});
	}

	let correctNumber = this.phoneCorrect(number);

	if (!BX.type.isPlainObject(params))
		params = {};

	if (correctNumber.length <= 0)
	{
		//this.BXIM.openConfirm({title: BX.message('IM_PHONE_WRONG_NUMBER'), message: BX.message('IM_PHONE_WRONG_NUMBER_DESC')});
		this.log("Wrong number");
		return false;
	}

	if (this.callActive || this.callInit)
		return false;

	this.ui.playSound({soundId: Sound.startCall});

	if(this.isRestLine(this.defaultLineId))
	{
		this.startCallViaRest(number, this.defaultLineId, params);
		return;
	}

	this.callInit = true;
	this.callActive = false;
	this.phoneNumber = correctNumber;
	this.phoneFullNumber = correctNumber;

	this.phoneParams = params;
	var matches = /(\+?\d+)([;#]*)([\d,]*)/.exec(correctNumber);
	if(matches)
	{
		this.phoneNumber = matches[1];
	}

	if (this.phoneFullNumber != this.phoneNumber)
	{
		this.phoneParams['FULL_NUMBER'] = this.phoneFullNumber;
	}

	this.showCallForm({
		status : BX.message('IM_M_CALL_ST_CONNECT'),
		state : TelephonyUiState.OUTGOING
	});

	this.connect().then(this.authorize.bind(this)).then(this.startCall.bind(this));
};

MobileTelephony.prototype.startCallViaRest = function(number, lineId, params)
{
	let appName = this.getRestAppName(lineId);
	this.isRestCall = true;
	this.phoneNumber = number;
	this.showCallForm({
		status : BX.message('IM_PHONE_OUTGOING_REST').replace('#APP_NAME#', appName),
		state : TelephonyUiState.FINISHED
	});
	BX.rest.callMethod('voximplant.call.startViaRest',
	{
		'NUMBER': number,
		'LINE_ID': lineId,
		'PARAMS': params
	}).then((result) =>
	{
		this.log('voximplant.call.startViaRest: ', result);
		let data = result.data();
		if(data.DATA && data.DATA.CRM)
			this.setCrmData(data.DATA.CRM);
	});
};

MobileTelephony.prototype.startCall = function()
{
	this.call = new TelephonyCall(this.phoneNumber, false, this.phoneParams, this.mobileVoximplant);

	this.call.addEventListener(CallEvent.Connected, this._onCallConnected.bind(this));
	this.call.addEventListener(CallEvent.Disconnected, this._onCallDisconnected.bind(this));
	this.call.addEventListener(CallEvent.Failed, this._onCallFailed.bind(this));
	this.call.addEventListener(CallEvent.ProgressToneStart, this._onCallProgressToneStart.bind(this));
	this.call.addEventListener(CallEvent.ProgressToneStop, this._onCallProgressToneStop.bind(this));

	this.call.start();
};

MobileTelephony.prototype.finishCall = function()
{
	if(this.call)
	{
		this.call.hangup();
		this.call = null;
	}

	if(this.promises.connection)
	{
		this.promises.connection.reject();
		this.promises.connection = null;
	}

	if(this.promises.authorization)
	{
		this.promises.authorization.reject();
		this.promises.authorization = null;
	}

	this.callId = '';
	this.phoneNumber = null;
	this.lineNumber = '';
	this.phoneRinging = 0;
	this.callConfig = {};
	this.callDevice = 'PHONE';
	this.crmData = {};
	this.transferUser = 0;

	// flags
	this.callInit = false;
	this.callActive = false;
	this.ignoreAnswerSelf = false;
	this.isIncoming = false;
	this.isTransfer = false;
	this.isRestCall = false;
	this.portalCall = false;

	this.ui.pauseTimer();
};

MobileTelephony.prototype.sendSkip = function()
{
	if (this.callInit && this.isIncoming)
	{
		BX.rest.callMethod('voximplant.call.skip', {'CALL_ID': this.callId});
	}
};

MobileTelephony.prototype.authorize = function()
{
	this.log('authorize');
	let result = new BX.Promise();
	if(this.connected && this.authorized)
	{
		result.fulfill();
	}
	else
	{
		this.promises.authorization = result;
		if(this.login && this.server)
		{
			this.mobileVoximplant.requestOneTimeKeyWithUsername(this.login + '@' + this.server);
		}
		else
		{
			BX.rest.callMethod('voximplant.authorization.get').then(result =>
			{
				let data = result.data();
				this.log('authorization data: ', data);
				this.login = data.LOGIN;
				this.server = data.SERVER;
				this.mobileVoximplant.requestOneTimeKeyWithUsername(this.login + '@' + this.server);
			}).catch((data) =>
			{
				let error = data.error();
				let answer = data.answer;

				this.log('voximplant.authorization.get error', error);
				if(answer.error_description)
					this.setUiStateLabel(answer.error_description);
				else if(error.status == '401')
					this.setUiStateLabel(BX.message('IM_PHONE_401'));
				else
					this.setUiStateLabel(BX.message('IM_PHONE_403'));

				this.setUiState(TelephonyUiState.FINISHED);
			});
		}
	}

	return result;
};

MobileTelephony.prototype.loginWithOneTimeKey = function(oneTimeKey)
{
	this.log('loginWithOneTimeKey');
	BX.rest.callMethod('voximplant.authorization.signOneTimeKey', {KEY: oneTimeKey}).then(result =>
	{
		let data = result.data();
		let hash = data.HASH;

		this.mobileVoximplant.setAuthData({
			login: this.login + '@' + this.server,
			hash: hash
		});

		this.mobileVoximplant.loginWithOneTimeKey();
	}).catch(e =>
	{
		this.log('voximplant.authorization.signOneTimeKey error', e.error());
		this.setUiStateLabel(BX.message('IM_PHONE_403'));
		this.setUiState(TelephonyUiState.FINISHED);
	})
};

MobileTelephony.prototype.connect = function()
{
	this.log('connect');
	let result = new BX.Promise();

	if(this.connected)
	{
		result.fulfill();
	}
	else
	{
		this.mobileVoximplant.connect();
		this.promises.connection = result;
	}

	return result;
};

MobileTelephony.prototype.disconnect = function()
{
	this.log('disconnect');
	this.mobileVoximplant.disconnect();
};

MobileTelephony.prototype.setCallHold = function(holdState)
{
	if (!this.call)
		return false;

	this.call.sendMessage({'COMMAND': (holdState ? 'hold' : 'unhold')});
};

MobileTelephony.prototype.getUiFields = function()
{
	let headerLabels = {};
	let middleLabels = {};
	let middleButtons = {};
	let avatarUrl = '';

	if (this.crmData.FOUND == 'Y')
	{
		let crmContactName = this.crmData.CONTACT && this.crmData.CONTACT.NAME? this.crmData.CONTACT.NAME: '';
		let crmContactPhoto = this.crmData.CONTACT && this.crmData.CONTACT.PHOTO? this.crmData.CONTACT.PHOTO: '';
		let crmContactPost = this.crmData.CONTACT && this.crmData.CONTACT.POST? this.crmData.CONTACT.POST: '';
		let crmCompanyName = this.crmData.COMPANY? this.crmData.COMPANY: '';

		if (!this.portalCall && !this.isRestCall && this.callConfig.hasOwnProperty('RECORDING'))
		{
			if (this.callConfig.RECORDING == "Y")
			{
				headerLabels.thirdSmallHeader = {'text': BX.message('IM_PHONE_REC_ON'), textColor: "#ecd748"};
			}
			else
			{
				headerLabels.thirdSmallHeader = {'text': BX.message('IM_PHONE_REC_OFF'), textColor: "#ee423f"};
			}
		}

		if (crmContactName)
			headerLabels.firstHeader = {'text': crmContactName};
		if (crmContactPost)
			headerLabels.firstSmallHeader = {'text': crmContactPost};
		if (crmCompanyName)
			headerLabels.secondSmallHeader = {'text': crmCompanyName};

		avatarUrl = "";
		if(crmContactPhoto && crmContactPhoto != BLANK_AVATAR)
		{
			if(crmContactPhoto.startsWith("http"))
			{
				avatarUrl = encodeURI(crmContactPhoto);
			}
			else
			{
				avatarUrl = currentDomain + encodeURI(crmContactPhoto);
			}
		}

		if (this.crmData.DEALS && this.crmData.DEALS.length > 0)
		{
			middleLabels = {
				infoTitle: {
					text: ""
				},
				infoDesc: {
					text: this.crmData.DEALS[0].TITLE
				},
				infoHeader: {
					text: this.crmData.DEALS[0].STAGE,
					textColor: this.crmData.DEALS[0].STAGE_COLOR
				},
				infoSum: {
					text: decodeHtml(this.crmData.DEALS[0].OPPORTUNITY)
				}
			};

			if (this.crmData.DEAL_URL)
			{
				middleButtons['button1'] = {
					text: BX.message('IM_PHONE_ACTION_T_DEAL'),
					sort: 100,
					eventName: "crm_deal_"+this.crmData.DEALS[0].ID
				};
			}
		}

		let dataSelect = [];
		if (this.crmData.COMPANY_DATA && this.crmData.CONTACT_DATA)
		{
			dataSelect = ['CONTACT_DATA', 'COMPANY_DATA', 'LEAD_DATA'];
		}
		else if (this.crmData.CONTACT_DATA && this.crmData.LEAD_DATA)
		{
			dataSelect = ['CONTACT_DATA', 'LEAD_DATA'];
		}
		else if (this.crmData.LEAD_DATA && this.crmData.COMPANY_DATA)
		{
			dataSelect = ['LEAD_DATA', 'COMPANY_DATA'];
		}
		else
		{
			if (this.crmData.CONTACT_DATA)
			{
				dataSelect = ['CONTACT_DATA'];
			}
			else if (this.crmData.COMPANY_DATA)
			{
				dataSelect = ['COMPANY_DATA'];
			}
			else if (this.crmData.LEAD_DATA)
			{
				dataSelect = ['LEAD_DATA'];
			}
		}

		for (let i = 0; i < dataSelect.length; i++)
		{
			let type = dataSelect[i];
			if (this.crmData[type])
			{
				if (type == 'CONTACT_DATA')
				{
					middleButtons['buttonData'+i] = {
						text: BX.message('IM_PHONE_ACTION_T_CONTACT'),
						sort: 200+i,
						eventName: "crm_contact_"+this.crmData[type].ID
					};
				}
				else if (type == 'COMPANY_DATA')
				{
					middleButtons['buttonData'+i] = {
						text: BX.message('IM_PHONE_ACTION_T_COMPANY'),
						sort: 200+i,
						eventName: "crm_company_"+this.crmData[type].ID
					};
				}
				else if (type == 'LEAD_DATA')
				{
					middleButtons['buttonData'+i] = {
						text: BX.message('IM_PHONE_ACTION_T_LEAD'),
						sort: 200+i,
						eventName: "crm_lead_"+this.crmData[type].ID
					};
				}
			}
		}
		if(this.portalCall)
		{
			middleLabels.imageStub = {backgroundColor: '#464f58', display: 'visible'};
		}
	}
	else
	{
		let phoneNumber;
		if (!this.phoneNumber || this.phoneNumber == 'hidden')
		{
			phoneNumber = BX.message('IM_PHONE_HIDDEN_NUMBER');
		}
		else
		{
			phoneNumber = this.phoneNumber.toString();

			if (phoneNumber.substr(0, 1) !== '8' && phoneNumber.substr(0, 1) !== '+' && !isNaN(parseInt(phoneNumber)) && phoneNumber.length >= 10)
			{
				phoneNumber = '+' + phoneNumber;
			}
		}
		headerLabels.firstHeader = {'text': phoneNumber};

		headerLabels.firstSmallHeader = {};
		if(this.isIncoming)
		{
			headerLabels.firstSmallHeader.text = this.lineNumber ?  BX.message('IM_PHONE_CALL_TO_PHONE').replace('#PHONE#', this.lineNumber): BX.message('IM_VI_CALL');
		}
		else
		{
			headerLabels.firstSmallHeader.text = BX.message('IM_PHONE_OUTGOING');
		}
		headerLabels.firstSmallHeader.textColor = "#999999";

		if (!this.portalCall && !this.isRestCall && this.callConfig.hasOwnProperty('RECORDING'))
		{
			if (this.callConfig.RECORDING == "Y")
			{
				headerLabels.thirdSmallHeader = {text: BX.message('IM_PHONE_REC_ON'), textColor: "#ecd748"};
			}
			else
			{
				headerLabels.thirdSmallHeader = {text: BX.message('IM_PHONE_REC_OFF'), textColor: "#ee423f"};
			}
		}

		middleLabels.imageStub = {backgroundColor: '#464f58', display: 'visible'};

		/*let middleButtons = {
			button1: {
				text: BX.message('IM_CRM_BTN_NEW_LEAD'),
				sort:100,
				eventName: "button1"
			},
			button2: {
				text: BX.message('IM_CRM_BTN_NEW_CONTACT'),
				sort:1,
				eventName: "button2"
			}
		};
		this.ui.updateMiddle({}, middleButtons);*/
	}

	return {headerLabels, middleLabels, middleButtons, avatarUrl};
};


MobileTelephony.prototype.showCallForm = function(params)
{
	let callFormParams = this.getUiFields();

	if (params.status)
	{
		callFormParams.footerLabels =
		{
			callStateLabel:
			{
				text: params.status
			}
		};
	}
	if (params.state)
	{
		callFormParams.state = params.state;
	}

	this.log('callFormParams: ', callFormParams);
	this.ui.show(callFormParams);
	this.formShown = true;

	device.setProximitySensorEnabled(true);
	device.setIdleTimerDisabled(true);
};

MobileTelephony.prototype.updateCallForm = function()
{
	if(!this.formShown)
		return false;

	let {headerLabels, middleLabels, middleButtons, avatarUrl} = this.getUiFields();

	this.ui.updateHeader({headerLabels, avatarUrl});
	this.ui.updateMiddle({middleLabels, middleButtons});
};

MobileTelephony.prototype.closeCallForm = function()
{
	this.ui.closeNumpad();
	this.ui.close();
	this.formShown = false;

	device.setProximitySensorEnabled(false);
	device.setIdleTimerDisabled(false);
};

MobileTelephony.prototype.setCrmData = function(crmData)
{
	if(!BX.type.isPlainObject(crmData))
		crmData = {FOUND: 'N'};

	this.crmData = crmData;
	if(this.crmData.FOUND === 'Y')
	{
		this.updateCallForm();
	}
};

MobileTelephony.prototype.setUiStateLabel = function(stateLabel)
{
	if(this.formShown)
	{
		this.ui.updateFooter({footerLabels: {callStateLabel: {text: stateLabel}}});
	}
};

MobileTelephony.prototype.setUiState = function(uiState)
{
	if(this.formShown)
	{
		this.ui.updateFooter({state: uiState})
	}
};

MobileTelephony.prototype.setProgress = function(progress)
{
	if (progress == 'connect')
	{
	}
	else if (progress == 'wait')
	{
		this.setUiState(TelephonyUiState.WAITING);
	}
	else if (progress == 'online')
	{
		if (!this.portalCall)
		{
			let headerLabels = {};
			if (this.callConfig.RECORDING == "Y")
			{
				headerLabels.thirdSmallHeader = {'text' : BX.message('IM_PHONE_REC_NOW'), textColor : "#7fc62c"};
			}
			else
			{
				headerLabels.thirdSmallHeader = {'text': BX.message('IM_PHONE_REC_OFF'), textColor: "#ee423f"};
			}

			this.ui.updateHeader({headerLabels});
		}

		this.setUiState(TelephonyUiState.STARTED);
	}
	else if (progress == 'offline' || progress == 'error')
	{
		if (progress == 'offline')
		{
			if (!this.portalCall)
			{
				let headerLabels = {};
				if (this.callConfig.RECORDING == "Y" && this.phoneCallTime > 0)
				{
					headerLabels.thirdSmallHeader = {'text' : BX.message('IM_PHONE_REC_DONE'), textColor : "#7fc62c"};
				}
				else
				{
					headerLabels.thirdSmallHeader = {'text' : ''};
				}
				this.ui.updateHeader({headerLabels});

				let footerLabels = {};
				if (this.crmData.LEAD_DATA && !this.crmData.CONTACT_DATA && !this.crmData.COMPANY_DATA && this.callConfig.CRM_CREATE == 'lead')
				{
					footerLabels.actionDoneHint = {'text': BX.message('IM_PHONE_LEAD_SAVED')};
				}
				else
				{
					footerLabels.actionDoneHint = {'text': ''};
				}
				this.ui.updateFooter({footerLabels});
			}
		}
		else
		{
			let headerLabels = {};
			headerLabels.thirdSmallHeader = {'text': ''};
			this.ui.updateHeader({headerLabels});

			let footerLabels = {};
			footerLabels.actionDoneHint = {'text': ''};
			this.ui.updateFooter({footerLabels});
		}

		this.setUiState(TelephonyUiState.FINISHED);
		if(this.formShown)
		{
			this.ui.expand();
		}
	}
};

MobileTelephony.prototype.isRestLine = function(lineId)
{
	return (this.lines.hasOwnProperty(lineId) && this.lines[lineId]['TYPE'] === 'REST');
};

MobileTelephony.prototype.getRestAppName = function(lineId)
{
	if(!this.lines.hasOwnProperty(lineId))
		return '';

	if(this.lines[lineId]['TYPE'] !== 'REST')
		return '';

	let lineName = this.lines[lineId]['FULL_NAME'];
	return lineName.substr(lineName.indexOf(':') + 2);
};

MobileTelephony.prototype._onIncomingCall = function(e)
{
	this.log("_onIncomingCall", e);
	if(this.call)
	{
		this.log('call already exists');
		return;
	}

	this.call = new TelephonyCall(e.displayName, e.videoCall, e.headers, this.mobileVoximplant, e.callId);
	this.call.addEventListener(CallEvent.Connected, this._onCallConnected.bind(this));
	this.call.addEventListener(CallEvent.Disconnected, this._onCallDisconnected.bind(this));
	this.call.addEventListener(CallEvent.Failed, this._onCallFailed.bind(this));
	this.call.answer();
};

MobileTelephony.prototype._onConnectionEstablished = function(e)
{
	this.log("_onConnectionEstablished", e);
	console.log("_onConnectionEstablished", e);
	this.connected = true;
	if(this.promises.connection)
	{
		this.promises.connection.fulfill();
		this.promises.connection = null;
	}
};

MobileTelephony.prototype._onConnectionClosed = function(e)
{
	this.log("_onConnectionClosed", e);
	if(this.promises.connection)
	{
		this.promises.connection.reject();
		this.promises.connection = null;
	}
	this.connected = false;
	this.authorized = false;
	if(this.callInit || this.callActive)
	{
		this.setProgress('error');
		this.setUiStateLabel(BX.message('IM_M_CALL_ERR'));
		this.finishCall();
	}
};

MobileTelephony.prototype._onConnectionFailed = function(e)
{
	this.log("_onConnectionFailed", e);
	this.connected = false;
	this.authorized = false;

	if(this.callInit || this.callActive)
    {
		this.setProgress('error');
		this.setUiStateLabel(BX.message('IM_M_CALL_ERR'));
		this.finishCall();
	}
};

MobileTelephony.prototype._onOneTimeKeyGenerated = function(e)
{
	this.log("_onOneTimeKeyGenerated", e);
	let oneTimeKey = e.key;
	this.loginWithOneTimeKey(oneTimeKey);
};

MobileTelephony.prototype._onAuthResult = function(e)
{
	this.log("_onAuthResult", e);
	if(e.hasOwnProperty('key'))
	{
		// android sdk fires event onAuthResult instead of onOneTimeKeyGenerated
		return this._onOneTimeKeyGenerated(e);
	}
	if (typeof(e.result) === "string")
	{
		// in some versions of mobile, e.result is string, like "true" or "false"
		e.result = (e.result === "true");
	}
	if(e.result)
	{
		if(this.promises.authorization)
		{
			this.promises.authorization.fulfill();
			this.promises.authorization = null;
		}
		this.authorized = true;
	}
	else
	{
		this.finishCall();
		this.setUiState(TelephonyUiState.FINISHED);
		if (e.code == 401 || e.code == 400 || e.code == 403 || e.code == 404 || e.code == 302)
		{
			this.setUiStateLabel(BX.message('IM_PHONE_401'));
			BX.rest.callMethod('voximplant.authorization.onError');
		}
		else
		{
			this.setUiStateLabel(BX.message('IM_M_CALL_ERR'));
		}
	}
};

MobileTelephony.prototype._onCallConnected = function(e)
{
	this.log("_onCallConnected", e);

	this.setProgress('online');
	this.setUiStateLabel(BX.message('IM_M_CALL_ST_ONLINE'));
	this.callActive = true;
};

MobileTelephony.prototype._onCallDisconnected = function(e)
{
	this.log("_onCallDisconnected", e);
	this.finishCall();
	this.closeCallForm();
};

MobileTelephony.prototype._onCallFailed = function(e)
{
	this.log("_onCallFailed", e);

	let errorText = BX.message('IM_PHONE_END');
	if (this.phoneNumber == 911 || this.phoneNumber == 112)
	{
		errorText = BX.message('IM_PHONE_NO_EMERGENCY');
	}
	else if (e.code == 603)
	{
		errorText = BX.message('IM_PHONE_DECLINE');
	}
	else if (e.code == 380)
	{
		errorText = BX.message('IM_PHONE_ERR_SIP_LICENSE');
	}
	else if (e.code == 436)
	{
		errorText = BX.message('IM_PHONE_ERR_NEED_RENT');
	}
	else if (e.code == 438)
	{
		errorText = BX.message('IM_PHONE_ERR_BLOCK_RENT');
	}
	else if (e.code == 400)
	{
		errorText = BX.message('IM_PHONE_ERR_LICENSE');
	}
	else if (e.code == 401)
	{
		errorText = BX.message('IM_PHONE_401');
	}
	else if (e.code == 480 || e.code == 503)
	{
		errorText = BX.message('IM_PHONE_UNAVAILABLE');
	}
	else if (e.code == 484 || e.code == 404)
	{
		errorText = BX.message('IM_PHONE_INCOMPLETED');
	}
	else if (e.code == 402)
	{
		errorText = BX.message('IM_PHONE_NO_MONEY')+(this.isAdmin? ' '+BX.message('IM_PHONE_PAY_URL_NEW'): '');
	}
	else if (e.code == 486 && this.phoneRinging > 1)
	{
		errorText = BX.message('IM_M_CALL_ST_DECLINE');
	}
	else if (e.code == 486)
	{
		errorText = BX.message('IM_PHONE_ERROR_BUSY');
	}
	else if (e.code == 403)
	{
		errorText = BX.message('IM_PHONE_403');
	}

	this.finishCall();
	this.setUiState(TelephonyUiState.FINISHED);
	this.setUiStateLabel(errorText);
};

MobileTelephony.prototype._onCallProgressToneStart = function(e)
{
	this.log("_onCallProgressToneStart", e);
	this.phoneRinging++;
	this.setUiState(TelephonyUiState.WAITING);
	this.setUiStateLabel(BX.message('IM_PHONE_WAIT_ANSWER'));
};

MobileTelephony.prototype._onCallProgressToneStop = function(e)
{
	this.log("_onCallProgressToneStop", e);
};

MobileTelephony.prototype._onUiHangup = function(e)
{
	this.ui.cancelDelayedClosing();
	this.finishCall();
	this.closeCallForm();
};

MobileTelephony.prototype._onUiSpeakerphoneChanged = function(e)
{
	let params = e.params;
	let speakerState = params.selected;
	if (!this.call)
		return false;

	this.call.setUseLoudSpeaker(speakerState);
};

MobileTelephony.prototype._onUiMuteChanged = function(e)
{
	let params = e.params;
	let micState = params.selected;
	if (!this.call)
		return false;

	if (micState)
		this.call.muteMicrophone();
	else
		this.call.unmuteMicrophone();
};

MobileTelephony.prototype._onUiPauseChanged = function(e)
{
	let params = e.params;
	let holdState = params.selected;
	this.setCallHold(holdState);
};

MobileTelephony.prototype._onUiCloseClicked = function(e)
{
	this.finishCall();
	this.formShown = false;
};

MobileTelephony.prototype._onUiSkipClicked = function(e)
{
	this.sendSkip();
	this.finishCall();
	this.closeCallForm();
};

MobileTelephony.prototype._onUiAnswerClicked = function(e)
{
	this.ignoreAnswerSelf = true;
	this.ui.stopSound();
	this.answerCall();
};

MobileTelephony.prototype._onUiNumpadButtonClicked = function(e)
{
	let key = e.params;

	if(this.call)
	{
		this.call.sendTone(key);
	}
};

MobileTelephony.prototype._onUiPhoneNumberReceived = function(data)
{
	if(Application.getApiVersion() >= 22)
	{
		var number = data.params;
		this.phoneCall(number);
	}
};

MobileTelephony.prototype._onUiCrmLinkClick = function(params)
{
	let entityType = params.entityType;
	let entityId = params.entityId;
	let crmUrl = getCrmShowPath(entityType, entityId);

	if(crmUrl)
	{
		PageManager.openPage({
			'url' : crmUrl,
			'bx24ModernStyle' : true
		});
		this.ui.rollUp();
	}
};

MobileTelephony.prototype._onCallInvite = function (params)
{
	this.log("_onCallInvite", params);

	if (typeof(fabric) === "object")
	{
		fabric.Answers.sendCustomEvent("incomingCallTelephony", {});
	}

	if(this.callInit || this.callActive)
		return false;

	this.crmData = (params.CRM && params.CRM.FOUND) ? params.CRM : {};
	this.portalCall = params.portalCall === true;

	if (this.portalCall && params.portalCallData)
	{
		//params.callerId = this.BXIM.messenger.users[params.portalCallUserId].name;
		params.phoneNumber = '';

		if (params.portalCallUserId)
		{
			this.crmData.FOUND = 'Y';
			this.crmData.CONTACT = {
				'NAME': params.portalCallData.users[params.portalCallUserId].name,
				'PHOTO': params.portalCallData.users[params.portalCallUserId].avatar
			};
		}
		else
		{

		}
	}

	this.callConfig = params.config? params.config: {};
	this.phoneCallTime = 0;

	this.ui.playSound({soundId: Sound.incoming});

	/*chatId, callId, callerId, companyPhoneNumber, isCallback*/
	params.isCallback = !!params.isCallback;
	params.isTransfer = !!params.isTransfer;

	this.phoneNumberUser =params.callerId;
	params.callerId = params.callerId.replace(/[^a-zA-Z0-9\.]/g, '');

	this.callInit = true;
	this.callActive = false;
	this.isIncoming = true;
	this.phoneCallTime = 0;
	this.callId = params.callId;
	this.phoneNumber = params.callerId;
	this.phoneParams = {};
	this.isTransfer = params.isTransfer;
	this.lineNumber = params.companyPhoneNumber;

	this.showCallForm({
		status : BX.message('IM_PHONE_INITIALIZATION'),
		state : TelephonyUiState.OUTGOING
	});

	BX.rest.callMethod('voximplant.call.sendWait', {'CALL_ID' : params.callId, 'DEBUG_INFO': this.getDebugInfo()}).then((result) =>
	{
		let data = result.data();
		this.log('voximplant.call.sendWait data:', data);

		// call could be already finished by this moment
		if(!this.callInit && !this.callActive)
			return;

		if(data.SUCCESS)
		{
			this.setUiState(TelephonyUiState.INCOMING);
			this.setUiStateLabel(params.isCallback ? BX.message('IM_PHONE_INVITE_CALLBACK') : BX.message('IM_PHONE_INVITE'));
		}
		else
		{
			this.setUiState(TelephonyUiState.FINISHED);
			this.setUiStateLabel(BX.message("IM_PHONE_ERROR"));
			this.ui.stopSound();
		}
	}).catch((data) =>
	{
		let answer = data.answer;
		this.log('voximplant.call.sendWait' + ' error: ', answer);

		// call could be already finished by this moment
		if(!this.callInit && !this.callActive)
			return;

		this.setUiState(TelephonyUiState.FINISHED);
		this.ui.stopSound();
		if(answer.error === 'ERROR_NOT_FOUND')
			this.setUiStateLabel(BX.message("IM_M_CALL_ALREADY_FINISHED"));
		else if(answer.error === 'ERROR_WRONG_STATE')
			this.setUiStateLabel(BX.message("IM_M_CALL_ALREADY_ANSWERED"));
		else
			this.setUiStateLabel(BX.message("IM_PHONE_ERROR"));
	});
};

MobileTelephony.prototype._onPullEventInvite = function (params, extra)
{
	if(extra.server_time_ago >= eventTimeRange)
	{
		this.log("Call was started too long time ago");
		return;
	}

	if (this.callInit || this.callActive)
	{
		// todo: set and proceed busy status in b_voximplant_queue
		/*BX.MessengerCommon.phoneCommand('busy', {'CALL_ID' : params.callId});*/
		return false;
	}

	this._onCallInvite(params);
};

MobileTelephony.prototype._onPullEventAnswerSelf = function (params)
{
	if (this.ignoreAnswerSelf || this.callId != params.callId)
		return false;

	this.finishCall();
	this.ui.stopSound();
	this.closeCallForm();

	this.callInit = true;
	this.callId = params.callId;
};

MobileTelephony.prototype._onPullEventTimeout = function (params)
{
	if (this.callId != params.callId)
		return false;

	this.ui.stopSound();
	this.closeCallForm();
	this.finishCall();
};

MobileTelephony.prototype._onPullEventOutgoing = function (params, extra)
{
	if(extra.server_time_ago >= eventTimeRange)
	{
		this.log("Call was started too long time ago");
		return;
	}

	this.portalCall = params.portalCall === true;

	if (this.callInit && this.phoneNumber == params.phoneNumber)
	{
		if (!this.callId)
		{
			this.setProgress('connect');
			this.setUiStateLabel(BX.message('IM_PHONE_WAIT_ANSWER'));

			this.callConfig = params.config || {};
			this.callId = params.callId;
			this.phoneCallTime = 0;
			this.setCrmData(params.CRM);
		}

		if (this.portalCall)
		{
			if (params.portalCallUserId)
			{
				this.setCrmData({
					FOUND: 'Y',
					CONTACT:
						{
							'NAME': params.portalCallData.users[params.portalCallUserId].name,
							'PHOTO': params.portalCallData.users[params.portalCallUserId].avatar
						}
				});
			}
			else
			{
				this.setCrmData({
					FOUND: 'Y',
					CONTACT:
						{
							'NAME': params.portalCallQueueName,
						}
				});
			}
		}
	}
};

MobileTelephony.prototype._onPullEventStart = function (params)
{
	// not sure if we need this handler in the mobile telephony at all.

	if (this.callId != params.callId)
		return false;

	this.ui.startTimer();
	this.ui.stopSound();
	this.callActive = true;

	if (params.CRM)
		this.setCrmData(params.CRM);
};

MobileTelephony.prototype._onPullEventHold = function(params)
{
	if (this.callId == params.callId)
	{
		this.phoneHolded = true;
	}
};

MobileTelephony.prototype._onPullEventUnHold = function(params)
{
	this.phoneHolded = false;
};

MobileTelephony.prototype._onPullEventUpdatePortalUser = function(params)
{
	if (this.callId == params.callId && params.portalCallUserId)
	{
		this.setCrmData({
			FOUND: 'Y',
			CONTACT:
				{
					'NAME': params.portalCallData.users[params.portalCallUserId].name,
					'PHOTO': params.portalCallData.users[params.portalCallUserId].avatar
				}
		});
	}
};

MobileTelephony.prototype._onPullEventUpdateCrm = function (params)
{
	if (this.callId == params.callId && params.CRM)
	{
		this.setCrmData(params.CRM);
	}
};

MobileTelephony.prototype._onPullEventCompleteTransfer = function (params)
{
	if (this.callId != params.callId)
	{
		return false;
	}

	this.callId = params.newCallId;
	this.isTransfer = false;
};

MobileTelephony.prototype._onPullEventPhoneDeviceActive = function (params)
{
	//nop
};

MobileTelephony.prototype._onPullEventChangeDefaultLineId = function (params)
{
	this.defaultLineId = params.defaultLineId;
	if(!this.lines.hasOwnProperty(this.defaultLineId))
	{
		this.lines[this.defaultLineId] = params.line;
	}
};

MobileTelephony.prototype._onPullEventReplaceCallerId = function (params)
{
	let callTitle = BX.message('IM_PHONE_CALL_TRANSFER').replace('#PHONE#', params.callerId);
	this.setCallOverlayTitle(callTitle);
	if (params.CRM)
	{
		this.setCrmData(params.CRM);
	}
};

MobileTelephony.prototype.getDebugInfo = function()
{
	return {
		isMobile: 'Y',
		callInit: this.callInit ? 'Y' : 'N',
		callActive: this.callActive ? 'Y' : 'N',
		appVersion: Application.getAppVersion(),
		apiVersion: Application.getApiVersion(),
		buildVersion: Application.getBuildVersion()
	}
};

MobileTelephony.prototype.phoneCorrect = function(number)
{
	return number.toString().replace(/[^0-9+#*;,]/g, '');
};

MobileTelephony.prototype.log = function()
{
	if(this.debug)
	{
		console.log.apply(null, arguments);
	}
};

MobileTelephony.prototype.logged = function(cb)
{
	let self = this;
	return function()
	{
		let params = [cb.name].concat(arguments);
		console.log.apply(null, params);
		cb.apply(self, arguments);
	}
};

var TelephonyCall = function (number, useVideo, callParams, mobileVoximplant, callId)
{
	this.callState = CallState.DISCONNECTED;
	this.listeners = {};
	this.mobileVoximplant = mobileVoximplant;
	this.params = {
		callId: callId ? callId : null,
		phoneNumber: number,
		useVideo: ((typeof useVideo == "undefined") ? false : useVideo),
		callParams: JSON.stringify(callParams),
		onCreateCallback: this.onCreate.bind(this),
		listeners: {
			"onCallConnected": this.onConnected.bind(this),
			"onCallDisconnected": this.onDisconnected.bind(this),
			"onCallFailed": this.onFailed.bind(this),
			"onProgressToneStart": this.onProgressToneStart.bind(this),
			"onProgressToneStop": this.onProgressToneStop.bind(this)
		}
	}
};

TelephonyCall.prototype.addEventListener = function (eventName, handler)
{
	this.listeners[eventName] = handler;
};

TelephonyCall.prototype.onCreate = function (result)
{
	this.params.callId = result.callId;
};

TelephonyCall.prototype.start = function ()
{
	if (this.params.callId !== null)
		return;

	this.mobileVoximplant.createCallAndStart(this.params);
};

TelephonyCall.prototype.removeEventListener = function (eventName)
{
	delete this.listeners[eventName];
};

TelephonyCall.prototype.answer = function ()
{
	if (this.params.callId === null)
		return;

	this.mobileVoximplant.answer(this.params);
};

TelephonyCall.prototype.hangup = function ()
{
	this.mobileVoximplant.hangup(this.params);
};

TelephonyCall.prototype.decline = function ()
{
	this.mobileVoximplant.decline(this.params);
};

TelephonyCall.prototype.id = function ()
{
	return this.params.callId
};

TelephonyCall.prototype.state = function ()
{
	return this.callState;
};

TelephonyCall.prototype.muteMicrophone = function ()
{
	this.mobileVoximplant.setMute({
		mute: true
	});
};

TelephonyCall.prototype.unmuteMicrophone = function ()
{
	this.mobileVoximplant.setMute({
		mute: false
	});
};
/**
 * Switches off/on loud speaker
 * @param {boolean} enabled
 */
TelephonyCall.prototype.setUseLoudSpeaker = function (enabled)
{
	this.mobileVoximplant.setUseLoudSpeaker({
		enabled: (typeof enabled == "boolean" ? enabled : false)
	});
};

/**
 * Sends instant message within this call
 * @param {object} message
 * @param {object} [headers] headers of message
 */
TelephonyCall.prototype.sendMessage = function (message, headers)
{
	let messageObject = {
		callId: this.id(),
		text: JSON.stringify(message),
		headers: (typeof headers != "object") ? {} : headers
	};

	this.mobileVoximplant.sendMessage(messageObject);
};
/**
 * Sends DTMF digit in this call.
 * @param {string} digit [0-9|*|#]
 */
TelephonyCall.prototype.sendTone = function (digit)
{
	let correctDigit = digit;
	if(correctDigit.search(/^[\d*#]$/) === 0)
	{
		/* DTMF code can be 0-9 for 0-9, 10 for * and 11 for # */
		if(correctDigit === '*')
			correctDigit = '10';
		else if(correctDigit === '#')
			correctDigit = '11';

		this.mobileVoximplant.sendDTMF({
			callId: this.id(),
			digit: correctDigit
		});
	}
};

TelephonyCall.prototype.onDisconnected = function ()
{
	this.callState = CallState.DISCONNECTED;
	this.executeCallback(CallEvent.Disconnected);
};

TelephonyCall.prototype.onConnected = function ()
{
	this.callState = CallState.CONNECTED;
	this.executeCallback(CallEvent.Connected);
};

TelephonyCall.prototype.onFailed = function (data)
{
	this.callState = CallState.DISCONNECTED;
	this.executeCallback(CallEvent.Failed, data);
};

TelephonyCall.prototype.onProgressToneStart = function ()
{
	this.callState = CallState.CONNECTING;
	this.executeCallback(CallEvent.ProgressToneStart);
};

TelephonyCall.prototype.onProgressToneStop = function ()
{
	this.executeCallback(CallEvent.ProgressToneStop);
};

TelephonyCall.prototype.executeCallback = function (eventName, data)
{
	if (typeof this.listeners[eventName] == "function")
	{
		return this.listeners[eventName](data);
	}
};


// endregion Mobile telephony

// region Initialization

if (Application.getApiVersion() < 36)
{
	var mwebrtc = new MobileWebrtc();
}
var mtelephony = new MobileTelephony();

console.log("Initialized");

// endregion Initialization
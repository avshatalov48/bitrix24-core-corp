import { Browser, Type, Text, Runtime, Loc, Reflection } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { DesktopApi } from 'im.v2.lib.desktop-api';
import { DesktopDownload } from 'intranet.desktop-download';

import { Keypad } from './view/keypad'
import { PhoneCallView, Direction, UiState, CallState, CallProgress } from './view/view'
import { BackgroundWorker } from './view/background-worker';
import { FoldedCallView } from './view/folded-view';
import { Popup } from 'main.popup';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';

const lsKeys = {
	callInited: 'viInitedCall',
	externalCall: 'viExternalCard',
	vite: 'vite',
	dialHistory: 'vox-dial-history',
	foldedView: 'vox-folded-call-card',
	callView: 'bx-vox-call-view',
	currentCall: 'bx-vox-current-call',
}

const Events = {
	onCallCreated: 'onCallCreated',
	onCallConnected: 'onCallConnected',
	onCallDestroyed: 'onCallDestroyed',
	onDeviceCallStarted: 'onDeviceCallStarted',
};

export type MessengerFacade = {
	isThemeDark: () => boolean,
	isDesktop: () => boolean,
	hasActiveCall: () => boolean,

	repeatSound: (string, number, boolean) => void,
	stopRepeatSound: (name: string) => void,
	playSound: (name: string, force: boolean) => void,

	setLocalConfig: (key: string, value: any, ttl: ?number) => void,
	getLocalConfig: (key: string) => any,

	getAvatar: (userId: number) => ?string,
}

type PhoneCallsControllerOptions = {
	phoneEnabled: boolean,
	userId: number,
	userEmail: string,
	isAdmin: boolean,
	callCardRestApps: Array,
	canInterceptCall: boolean,
	restApps: Array,

	deviceActive: boolean,
	deviceCall: boolean, //?
	defaultLineId: string,
	availableLines: Array,

	messengerFacade: MessengerFacade,
	events: { [key: string]: () => {} },
}

const DeviceType = {
	Webrtc: 'WEBRTC',
	Phone: 'PHONE',
}

export class PhoneCallsController extends EventEmitter
{
	messengerFacade: MessengerFacade
	userEmail: string

	callView: ?PhoneCallView
	foldedCallView: FoldedCallView
	backgroundWorker: BackgroundWorker
	keypad: ?Keypad
	defaultLineId: string

	_currentCall: ?VoxImplant.Call
	voximplantClient: ?VoxImplant.Client

	callId: string
	isCallHold: boolean
	isMuted: boolean
	isCallTransfer: boolean

	hasSipPhone: boolean
	deviceType: ?string
	/** @see DeviceType */

	hasExternalCall: boolean

	phoneTransferTargetId: number
	phoneTransferTargetType: string
	phoneTransferCallId: string
	phoneTransferEnabled: boolean
	phoneCrm: Object

	initiator: boolean
	callInitUserId: number
	callActive: boolean
	callUserId: number
	phoneNumber: string

	debug: boolean

	constructor(options: PhoneCallsControllerOptions)
	{
		super();
		this.setEventNamespace('BX.Voximplant.PhoneCallsControllerOptions');
		this.subscribeFromOptions(options.events)

		this.debug = false;

		this.phoneEnabled = Type.isBoolean(options.phoneEnabled) ? options.phoneEnabled : false;

		this.userId = options.userId;
		this.userEmail = options.userEmail;
		this.isAdmin = options.isAdmin;

		const history = BX.localStorage.get(lsKeys.dialHistory);
		this.dialHistory = Type.isArray(history) ? history : [];

		this.availableLines = Type.isArray(options.availableLines) ? options.availableLines : [];
		this.defaultLineId = Type.isString(options.defaultLineId) ? options.defaultLineId : '';
		this.callInterceptAllowed = options.canInterceptCall || false;
		this.restApps = options.restApps;

		this.hasSipPhone = options.deviceActive === true;

		this.skipIncomingCallTimer = null;

		this.hasActiveCallView = false;

		this.readDefaults();

		if (Browser.isLocalStorageSupported())
		{
			BX.addCustomEvent(window, "onLocalStorageSet", this.storageSet.bind(this));
		}
		BX.addCustomEvent("onPullEvent-voximplant", this.#onPullEvent.bind(this));

		// call event handlers
		this.onCallConnectedHandler = this.#onCallConnected.bind(this);
		this.onCallDisconnectedHandler = this.#onCallDisconnected.bind(this);
		this.onCallFailedHandler = this.#onCallFailed.bind(this);
		this.onProgressToneStartHandler = this.#onProgressToneStart.bind(this);
		this.onProgressToneStopHandler = this.#onProgressToneStop.bind(this);

		this.messengerFacade = options.messengerFacade;
		this.backgroundWorker = new BackgroundWorker();
		this.foldedCallView = new FoldedCallView({
			events: {
				[FoldedCallView.Events.onUnfold]: (event) => {
					const data = event.getData();
					this.startCallList(data.callListId, data.callListParams)
				}
			}
		});

		this.restoreFoldedCallView();
		BX.garbage(() =>
		{
			if (this.hasActiveCall() && this.callView && this.callView.canBeUnloaded() && (this.hasExternalCall || this.deviceType === 'PHONE'))
			{
				BX.localStorage.set(lsKeys.foldedView, {
					callId: this.callId,
					phoneCrm: this.phoneCrm,
					deviceType: this.deviceType,
					hasExternalCall: this.hasExternalCall,
					callView: this.callView.getState()
				}, 15);
			}
		})
	}

	hasActiveCall(): boolean
	{
		return Boolean(this._currentCall || this.callView)
	}

	get currentCall(): ?VoxImplant.Call
	{
		return this._currentCall
	}

	set currentCall(call: ?VoxImplant.Call)
	{
		if (this._currentCall)
		{
			this.#removeCallEventListeners(this._currentCall);

			this.emit(Events.onCallDestroyed, {
				call: this._currentCall
			});
		}
		call?.id()
			? BX.localStorage.set(lsKeys.currentCall, call?.id(), 86400)
			: BX.localStorage.remove(lsKeys.currentCall)
		;

		this._currentCall = call;
		this.hasActiveCallView = Boolean(this._currentCall);

		if (this._currentCall)
		{
			this.#setCallEventListeners(call);
			this.emit(Events.onCallCreated, {
				call: call
			});
		}
	}

	#setCallEventListeners(call: VoxImplant.Call)
	{
		call.addEventListener(VoxImplant.CallEvents.Connected, this.onCallConnectedHandler);
		call.addEventListener(VoxImplant.CallEvents.Disconnected, this.onCallDisconnectedHandler);
		call.addEventListener(VoxImplant.CallEvents.Failed, this.onCallFailedHandler);
		call.addEventListener(VoxImplant.CallEvents.ProgressToneStart, this.onProgressToneStartHandler);
		call.addEventListener(VoxImplant.CallEvents.ProgressToneStop, this.onProgressToneStopHandler);
	}

	#removeCallEventListeners(call: VoximplantCall)
	{
		call.removeEventListener(VoxImplant.CallEvents.Connected, this.onCallConnectedHandler);
		call.removeEventListener(VoxImplant.CallEvents.Disconnected, this.onCallDisconnectedHandler);
		call.removeEventListener(VoxImplant.CallEvents.Failed, this.onCallFailedHandler);
		call.removeEventListener(VoxImplant.CallEvents.ProgressToneStart, this.onProgressToneStartHandler);
		call.removeEventListener(VoxImplant.CallEvents.ProgressToneStop, this.onProgressToneStopHandler);
	}

	ready()
	{
		return true; // TODO ??
	}

	async readDefaults()
	{
		if (!localStorage)
		{
			return;
		}

		this.defaultMicrophone = localStorage.getItem('bx-im-settings-default-microphone');
		this.defaultCamera = localStorage.getItem('bx-im-settings-default-camera');
		this.defaultSpeaker = localStorage.getItem('bx-im-settings-default-speaker');
		this.enableMicAutoParameters = (localStorage.getItem('bx-im-settings-enable-mic-auto-parameters') !== 'N');

		if (
			!navigator.mediaDevices
			|| !navigator.mediaDevices.enumerateDevices
			|| !this.defaultMicrophone
		)
		{
			return;
		}

		const deviceList = await navigator.mediaDevices.enumerateDevices();
		const result = deviceList.filter(device => device.kind === 'audioinput' && device.deviceId === this.defaultMicrophone);
		this.defaultMicrophone = result.length ? this.defaultMicrophone : null;
	}

	#onPullEvent(command, params)
	{
		const handlers = {
			'invite': this.#onPullInvite,
			'answer_self': this.#onPullAnswerSelf,
			'timeout': this.#onPullTimeout,
			'outgoing': this.#onPullOutgoing,
			'start': this.#onPullStart,
			'hold': this.#onPullHold,
			'unhold': this.#onPullUnhold,
			'update_crm': this.#onPullUpdateCrm,
			'updatePortalUser': this.#onPullUpdatePortalUser,
			'completeTransfer': this.#onPullCompleteTransfer,
			'phoneDeviceActive': this.#onPullPhoneDeviceActive,
			'changeDefaultLineId': this.#onPullChangeDefaultLineId,
			'replaceCallerId': this.#onPullReplaceCallerId,
			'showExternalCall': this.#onPullShowExternalCall,
			'hideExternalCall': this.#onPullHideExternalCall,
		}

		if (handlers.hasOwnProperty(command))
		{
			handlers[command].apply(this, [params]);
		}
	}

	#onPullInvite(params)
	{
		if (!this.phoneSupport())
		{
			return false;
		}

		const popupConditions = this.callView?.popup
			&& !this.callView?.commentShown
			&& !this.callView?.autoCloseTimer
			&& !this.hasActiveCallView
		;

		if (
			(this.callView && !this.callView?.popup && !this.currentCall)
			|| (popupConditions && !this.currentCall)
			|| (this.callView && this.callView?.isFolded() && !this.currentCall)
			|| this.callView && !this.voximplantClient
			|| (this.callView && this.voximplantClient && !this.voximplantClient.connected())
		)
		{
			console.log('Close a stuck call view');
			this.#onCallViewClose();
		}

		if (
			BX.localStorage.get(lsKeys.callView)
			&& this.callView?.popup
			&& !Boolean(this._currentCall)
			&& !this.isCallListMode()
			&& !this.messengerFacade.hasActiveCall()
		)
		{
			this.showCallViewBalloon();
		}

		if (this.hasActiveCall() || this.isCallListMode() || this.messengerFacade.hasActiveCall())
		{
			BX.rest.callMethod('voximplant.call.busy', {
				CALL_ID: params.callId,
				DEBUG_INFO: this.getDebugInfo(),
			});
			return false;
		}

		if (
			BX.localStorage.get(lsKeys.callInited)
			|| BX.localStorage.get(lsKeys.externalCall)
		)
		{
			return false;
		}

		this.checkDesktop().then(() => {
			if (params.CRM && params.CRM.FOUND)
			{
				this.phoneCrm = params.CRM;
			}
			else
			{
				this.phoneCrm = {};
			}

			this.phonePortalCall = !!params.portalCall;
			if (this.phonePortalCall && params.portalCallData)
			{
				const userData = params.portalCallData[params.portalCallUserId];
				if (userData)
				{
					params.callerId = userData.name;
				}

				params.phoneNumber = '';
			}

			this.phoneCallConfig = params.config ? params.config : {};
			this.phoneCallTime = 0;

			this.messengerFacade.repeatSound('ringtone', 5000, true);

			BX.rest.callMethod('voximplant.call.sendWait', {
				'CALL_ID': params.callId,
				'DEBUG_INFO': this.getDebugInfo()
			})

			this.isCallTransfer = !!params.isTransfer;

			this.displayIncomingCall({
				chatId: params.chatId,
				callId: params.callId,
				callerId: params.callerId,
				lineNumber: params.lineNumber,
				companyPhoneNumber: params.phoneNumber,
				isCallback: params.isCallback,
				showCrmCard: params.showCrmCard,
				crmEntityType: params.crmEntityType,
				crmEntityId: params.crmEntityId,
				crmActivityId: params.crmActivityId,
				crmActivityEditUrl: params.crmActivityEditUrl,
				portalCall: params.portalCall,
				portalCallUserId: params.portalCallUserId,
				portalCallData: params.portalCallData,
				config: params.config
			});
		}).catch(() => {});
	}

	#onPullAnswerSelf(params)
	{
		this.clearSkipIncomingCallTimer();
		if (this.callSelfDisabled || this.callId != params.callId)
		{
			return false;
		}

		this.messengerFacade.stopRepeatSound('ringtone');
		this.messengerFacade.stopRepeatSound('dialtone');

		this.phoneCallFinish();
		this.callAbort();
		this.callView.close();

		this.callId = params.callId;
	}

	#onPullTimeout(params)
	{
		this.clearSkipIncomingCallTimer();

		if (this.phoneTransferCallId === params.callId)
		{
			return this.errorInviteTransfer(params.failedCode, params.failedReason);
		}
		else if (this.callId != params.callId)
		{
			return false;
		}

		clearInterval(this.phoneConnectedInterval);
		BX.localStorage.remove(lsKeys.callInited);

		var external = this.hasExternalCall;

		this.messengerFacade.stopRepeatSound('ringtone');
		this.messengerFacade.stopRepeatSound('dialtone');

		this.phoneCallFinish();
		this.callAbort();

		if (!this.callView)
		{
			return
		}

		this.showCallViewBalloon();
		this.callView.setCallState(CallState.idle, {failedCode: params.failedCode});
		if (external && params.failedCode == 486)
		{
			this.callView.setProgress(CallProgress.offline);
			this.callView.setStatusText(Loc.getMessage('IM_PHONE_ERROR_BUSY_PHONE'));
			this.callView.setUiState(UiState.sipPhoneError);
		}
		else if (external && params.failedCode == 480)
		{
			this.callView.setProgress(CallProgress.error);
			this.callView.setStatusText(Loc.getMessage('IM_PHONE_ERROR_NA_PHONE'));
			this.callView.setUiState(UiState.sipPhoneError);
		}
		else
		{
			if (this.isCallListMode())
			{
				this.callView.setStatusText('');
				this.callView.setUiState(UiState.outgoing);
			}
			else
			{
				this.callView.setStatusText(Loc.getMessage('IM_PHONE_END'));
				this.callView.setUiState(UiState.idle);
				this.callView.autoClose();
			}
		}
	}

	#onPullOutgoing(params)
	{
		if (this.phoneNumber && (this.phoneNumber === params.phoneNumber || params.phoneNumber.indexOf(this.phoneNumber) >= 0))
		{
			this.deviceType = params.callDevice == DeviceType.Phone ? DeviceType.Phone : DeviceType.Webrtc;
			this.phonePortalCall = !!params.portalCall;

			this.phoneNumber = params.phoneNumber;

			if (this.hasExternalCall && this.deviceType == DeviceType.Phone)
			{
				this.callView.setProgress(CallProgress.connect);
				this.callView.setStatusText(Loc.getMessage('IM_PHONE_WAIT_ANSWER'));
			}

			this.phoneCallConfig = params.config ? params.config : {};
			this.callId = params.callId;
			this.phoneCallTime = 0;
			this.phoneCrm = params.CRM;
			if (this.callView && params.showCrmCard)
			{
				this.callView.setCrmData(params.CRM);
				this.callView.setCrmEntity({
					type: params.crmEntityType,
					id: params.crmEntityId,
					activityId: params.crmActivityId,
					activityEditUrl: params.crmActivityEditUrl,
					bindings: params.crmBindings
				});
				this.callView.setConfig(params.config);
				this.callView.setCallId(params.callId);
				if (params.lineNumber)
				{
					this.callView.setLineNumber(params.lineNumber);
				}

				if (params.lineName)
				{
					this.callView.setCompanyPhoneNumber(params.lineName);
				}

				this.callView.reloadCrmCard();
			}

			if (this.callView && this.phonePortalCall)
			{
				this.callView.setPortalCall(true);
				this.callView.setPortalCallData(params.portalCallData);
				this.callView.setPortalCallUserId(params.portalCallUserId);
				this.callView.setPortalCallQueueName(params.portalCallQueueName);
			}
		}
		else if (!this.hasActiveCall() && params.callDevice === DeviceType.Phone)
		{
			this.checkDesktop().then(() =>
			{
				this.deviceType = params.callDevice === DeviceType.Phone ? DeviceType.Phone : DeviceType.Webrtc;
				this.phonePortalCall = !!params.portalCall;
				this.callId = params.callId;
				this.phoneCallTime = 0;
				this.phoneCallConfig = params.config ? params.config : {};
				this.phoneCrm = params.CRM;

				this.phoneDisplayExternal({
					callId: params.callId,
					config: params.config ? params.config : {},
					phoneNumber: params.phoneNumber,
					portalCall: params.portalCall,
					portalCallUserId: params.portalCallUserId,
					portalCallData: params.portalCallData,
					portalCallQueueName: params.portalCallQueueName,
					showCrmCard: params.showCrmCard,
					crmEntityType: params.crmEntityType,
					crmEntityId: params.crmEntityId
				});
			}).catch(() => {});
		}
	}

	#onPullStart(params)
	{
		this.clearSkipIncomingCallTimer();

		if (this.phoneTransferCallId === params.callId)
		{
			this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_TRANSFER_CONNECTED'));
			return;
		}
		if (this.callId != params.callId)
		{
			return;
		}

		this.callOverlayTimer('start');
		this.messengerFacade.stopRepeatSound('ringtone');
		if (this.callId == params.callId && this.deviceType == DeviceType.Phone && (this.deviceType == params.callDevice || this.phonePortalCall))
		{
			this.#onCallConnected();
		}
		else if (this.callId == params.callId && params.callDevice == DeviceType.Phone && this.phoneIncoming)
		{
			this.deviceType = DeviceType.Phone;
			if (this.callView)
			{
				this.callView.setDeviceCall(true);
			}
			this.#onCallConnected();
		}
		if (params.CRM)
		{
			this.phoneCrm = params.CRM;
		}

		if (this.phoneNumber !== '')
		{
			this.phoneNumberLast = this.phoneNumber;
			this.messengerFacade.setLocalConfig('phone_last', this.phoneNumber);
		}
	}

	#onPullHold(params)
	{
		if (this.callId == params.callId)
		{
			this.isCallHold = true;
		}
	}

	#onPullUnhold(params)
	{
		if (this.callId == params.callId)
		{
			this.isCallHold = false;
		}
	}

	#onPullUpdateCrm(params)
	{
		if (this.callId == params.callId && params.CRM && params.CRM.FOUND)
		{
			this.phoneCrm = params.CRM;

			if (this.callView)
			{
				this.callView.setCrmData(params.CRM);
				if (params.showCrmCard)
				{
					this.callView.setCrmEntity({
						type: params.crmEntityType,
						id: params.crmEntityId,
						activityId: params.crmActivityId,
						activityEditUrl: params.crmActivityEditUrl,
						bindings: params.crmBindings
					});
					this.callView.reloadCrmCard();
				}
			}
		}
	}

	#onPullUpdatePortalUser(params)
	{
		if (this.callId == params.callId && this.callView)
		{
			this.callView.setPortalCall(true);
			this.callView.setPortalCallData(params.portalCallData);
			this.callView.setPortalCallUserId(params.portalCallUserId);
		}
	}

	#onPullCompleteTransfer(params)
	{
		if (this.callId != params.callId)
		{
			return false;
		}

		this.callId = params.newCallId;

		this.phoneTransferTargetId = 0;
		this.phoneTransferTargetType = '';
		this.phoneTransferCallId = '';
		this.phoneTransferEnabled = false;
		BX.localStorage.set(lsKeys.vite, false, 1);

		this.deviceType = params.callDevice == DeviceType.Phone ? DeviceType.Phone : DeviceType.Webrtc;
		if (this.deviceType == DeviceType.Phone)
		{
			this.callView.setDeviceCall(true);
		}
		this.callView.setTransfer(false);
		this.#onCallConnected();
	}

	#onPullPhoneDeviceActive(params)
	{
		this.hasSipPhone = params.active == 'Y';
	}

	#onPullChangeDefaultLineId(params)
	{
		this.defaultLineId = params.defaultLineId;
	}

	#onPullReplaceCallerId(params)
	{
		var callTitle = Loc.getMessage('IM_PHONE_CALL_TRANSFER').replace('#PHONE#', params.callerId);
		this.setCallOverlayTitle(callTitle);
		this.callView.setPhoneNumber(params.callerId);
		if (params.CRM)
		{
			this.phoneCrm = params.CRM;
			this.callView.setCrmData(params.CRM);
			if (params.showCrmCard)
			{
				this.callView.setCrmEntity({
					type: params.crmEntityType,
					id: params.crmEntityId,
					activityId: params.crmActivityId,
					activityEditUrl: params.crmActivityEditUrl,
					bindings: params.crmBindings
				});
				this.callView.reloadCrmCard();
			}
		}
	}

	#onPullShowExternalCall(params)
	{
		if (this.messengerFacade.hasActiveCall())
		{
			return false;
		}

		if (BX.localStorage.get(lsKeys.callInited) || BX.localStorage.get(lsKeys.externalCall))
		{
			return false;
		}

		this.checkDesktop().then(() =>
		{
			if (params.CRM && params.CRM.FOUND)
			{
				this.phoneCrm = params.CRM;
			}
			else
			{
				this.phoneCrm = {};
			}

			this.showExternalCall({
				callId: params.callId,
				fromUserId: params.fromUserId,
				toUserId: params.toUserId,
				isCallback: params.isCallback,
				phoneNumber: params.phoneNumber,
				lineNumber: params.lineNumber,
				companyPhoneNumber: params.companyPhoneNumber,
				showCrmCard: params.showCrmCard,
				crmEntityType: params.crmEntityType,
				crmEntityId: params.crmEntityId,
				crmBindings: params.crmBindings,
				crmActivityId: params.crmActivityId,
				crmActivityEditUrl: params.crmActivityEditUrl,
				config: params.config,
				portalCall: params.portalCall,
				portalCallData: params.portalCallData,
				portalCallUserId: params.portalCallUserId
			});
		}).catch(() => {});
	}

	#onPullHideExternalCall(params)
	{
		if (this.hasActiveCall() && this.hasExternalCall && this.callId == params.callId)
		{
			this.hideExternalCall();
		}
	}

	correctPhoneNumber(number)
	{
		return number.toString().replace(/[^0-9+#*;,]/g, '');
	}

	#onIncomingCall(params)
	{
		// we can't use hasActiveCall here because the call view is open
		if (this.currentCall)
		{
			return false;
		}

		this.currentCall = params.call;
		this.currentCall.answer();
	}

	getCallParams()
	{
		let result = Type.isPlainObject(this.phoneParams) ? Runtime.clone(this.phoneParams) : {};
		if (this.phoneFullNumber != this.phoneNumber)
		{
			result['FULL_NUMBER'] = this.phoneFullNumber;
		}
		return JSON.stringify(result);
	}

	#startCall()
	{
		this.phoneParams['CALLER_ID'] = '';
		this.phoneParams['USER_ID'] = this.userId;
		this.phoneLog('Call params: ', this.phoneNumber, this.phoneParams);
		if (!this.voximplantClient.connected())
		{
			this.#phoneOnSDKReady();
			return false;
		}

		this.currentCall = this.voximplantClient.call(this.phoneNumber, false, this.getCallParams());

		const initParams = {
			'NUMBER': this.phoneNumber,
			'NUMBER_USER': Text.decode(this.phoneNumberUser),
			'IM_AJAX_CALL': 'Y',
		}
		BX.rest.callMethod('voximplant.call.init', initParams).then((response) =>
		{
			const data = response.data();
			if (!(data.HR_PHOTO.length === 0))
			{
				this.callOverlayUserId = data.DIALOG_ID;
			}
			else
			{
				this.callOverlayChatId = data.DIALOG_ID.substring(4);
			}
		})
	}

	phoneCallFinish()
	{
		clearInterval(this.phoneConnectedInterval);
		clearInterval(this.phoneCallTimeInterval);
		BX.localStorage.remove(lsKeys.callInited);

		this.callOverlayTimer('pause');
		this.showCallViewBalloon();

		if (this.currentCall)
		{
			try
			{
				this.currentCall.hangup({
					"X-Disconnect-Code": 200,
					"X-Disconnect-Reason": "Normal hangup"
				});
			} catch (e)
			{}
			this.currentCall = null;
			this.phoneLog('Call hangup call');
		}
		else
		{
			this.scheduleApiDisconnect();
		}

		if (this.keypad)
		{
			this.keypad.close();
		}

		BX.localStorage.set(lsKeys.vite, false, 1);

		this.phoneRinging = 0;
		this.phoneIncoming = false;
		this.callActive = false;
		this.callId = '';
		this.hasExternalCall = false;
		this.deviceType = DeviceType.Webrtc;
		//this.phonePortalCall = false;
		this.phoneNumber = '';
		this.phoneNumberUser = '';
		this.phoneParams = {};
		this.callOverlayOptions = {};
		this.callSelfDisabled = false;
		//this.phoneCrm = {};
		this.isMuted = false;
		this.isCallHold = false;
		this.isCallTransfer = false;
		this.phoneMicAccess = false;
		this.phoneTransferTargetType = '';
		this.phoneTransferTargetId = 0;
		this.phoneTransferCallId = '';
		this.phoneTransferEnabled = false;
	}

	phoneOnAuthResult()
	{
		if (this.deviceType == DeviceType.Phone)
		{
			return false;
		}

		if (this.phoneIncoming)
		{
			BX.rest.callMethod('voximplant.call.sendReady', {'CALL_ID': this.callId});
		}
		else if (this.callInitUserId == this.userId)
		{
			this.#startCall();
		}
	}

	#onCallFailed(e)
	{
		const headers = e.headers || {};
		this.phoneLog('Call failed', e.code, e.reason);

		var reason = Loc.getMessage('IM_PHONE_END');
		if (e.code == 603)
		{
			reason = Loc.getMessage('IM_PHONE_DECLINE');
		}
		else if (e.code == 380)
		{
			reason = Loc.getMessage('IM_PHONE_ERR_SIP_LICENSE');
		}
		else if (e.code == 436)
		{
			reason = Loc.getMessage('IM_PHONE_ERR_NEED_RENT');
		}
		else if (e.code == 438)
		{
			reason = Loc.getMessage('IM_PHONE_ERR_BLOCK_RENT');
		}
		else if (e.code == 400)
		{
			reason = Loc.getMessage('IM_PHONE_ERR_LICENSE');
		}
		else if (e.code == 401)
		{
			reason = Loc.getMessage('IM_PHONE_401');
		}
		else if (e.code == 480 || e.code == 503)
		{
			if (this.phoneNumber == 911 || this.phoneNumber == 112)
			{
				reason = Loc.getMessage('IM_PHONE_NO_EMERGENCY');
			}
			else
			{
				reason = Loc.getMessage('IM_PHONE_UNAVAILABLE');
			}
		}
		else if (e.code == 484 || e.code == 404)
		{
			if (this.phoneNumber == 911 || this.phoneNumber == 112)
			{
				reason = Loc.getMessage('IM_PHONE_NO_EMERGENCY');
			}
			else
			{
				reason = Loc.getMessage('IM_PHONE_INCOMPLETED');
			}
		}
		else if (e.code == 402)
		{
			if (headers.hasOwnProperty('X-Reason') && headers['X-Reason'] === "SIP_PAYMENT_REQUIRED")
			{
				reason = Loc.getMessage('IM_PHONE_ERR_SIP_LICENSE');
			}
			else
			{
				reason = Loc.getMessage('IM_PHONE_NO_MONEY') + (this.isAdmin ? ' ' + Loc.getMessage('IM_PHONE_PAY_URL_NEW') : '');
			}
		}
		else if (e.code == 486 && this.phoneRinging > 1)
		{
			reason = Loc.getMessage('IM_M_CALL_ST_DECLINE');
		}
		else if (e.code == 486)
		{
			reason = Loc.getMessage('IM_PHONE_ERROR_BUSY');
		}
		else if (e.code == 403)
		{
			reason = Loc.getMessage('IM_PHONE_403');
			this.phoneServer = '';
			this.phoneLogin = '';
			this.phoneCheckBalance = true;
		}
		else if (e.code == 504)
		{
			reason = Loc.getMessage('IM_PHONE_ERROR_CONNECT');
		}
		else
		{
			reason = Loc.getMessage('IM_PHONE_ERROR');
		}

		if (e.code == 408 || e.code == 403)
		{
			this.scheduleApiDisconnect();
		}
		this.callOverlayProgress('offline');
		this.callAbort(reason);

		this.callView.setUiState(UiState.error);
		this.callView.setCallState(CallState.idle);
	}

	scheduleApiDisconnect()
	{
		if (this.voximplantClient && this.voximplantClient.connected())
		{
			setTimeout(() =>
			{
				if (this.voximplantClient && this.voximplantClient.connected())
				{
					this.voximplantClient.disconnect();
				}
			}, 500);
		}
	}

	#onCallDisconnected(e)
	{
		this.phoneLog('Call disconnected', this.currentCall ? this.currentCall.id() : '-', this.currentCall ? this.currentCall.state() : '-');

		if (this.currentCall)
		{
			this.phoneCallFinish();
			this.callOverlayDeleteEvents();
			this.callOverlayStatus(Loc.getMessage('IM_M_CALL_ST_END'));

			this.messengerFacade.playSound('stop');
			this.callView.setCallState(CallState.idle);
			if (this.isCallListMode())
			{
				this.callView.setUiState(UiState.outgoing);
			}
			else
			{
				this.callView.setStatusText(Loc.getMessage('IM_PHONE_END'));
				this.callView.setUiState(UiState.idle);
				this.callView.autoClose();
			}
		}

		this.scheduleApiDisconnect();
	}

	#onProgressToneStart(e)
	{
		if (!this.currentCall)
		{
			return false;
		}

		this.phoneLog('Progress tone start', this.currentCall.id());
		this.phoneRinging++;
		this.callOverlayStatus(Loc.getMessage('IM_PHONE_WAIT_ANSWER'));
	}

	#onProgressToneStop(e)
	{
		if (!this.currentCall)
		{
			return false;
		}
		this.phoneLog('Progress tone stop', this.currentCall.id());
	}

	#onConnectionEstablished(e)
	{
		this.phoneLog('Connection established', this.voximplantClient.connected());
	}

	#onConnectionFailed(e)
	{
		this.phoneLog('Connection failed');
		this.phoneCallFinish();
		this.callAbort(Loc.getMessage('IM_M_CALL_ERR'));
	}

	#onConnectionClosed(e)
	{
		this.phoneLog('Connection closed');
	}

	#onMicResult(e)
	{
		this.phoneMicAccess = e.result;
		this.phoneLog('Mic Access Allowed', e.result);

		if (e.result)
		{
			this.callOverlayProgress('connect');
			this.callOverlayStatus(Loc.getMessage('IM_M_CALL_ST_CONNECT'));
		}
		else
		{
			this.phoneCallFinish();
			this.callOverlayProgress('offline');
			this.callAbort(Loc.getMessage('IM_M_CALL_ST_NO_ACCESS'));

			this.callView.setUiState(UiState.error);
			this.callView.setCallState(CallState.idle);
		}
	}

	#onNetStatsReceived(e)
	{
		if (!this.currentCall || this.currentCall.state() != "CONNECTED")
		{
			return false;
		}

		const percent = (100 - parseInt(e.stats.packetLoss));
		const grade = this.displayCallQuality(percent);

		this.currentCall.sendMessage(JSON.stringify({
			'COMMAND': 'meter',
			'PACKETLOSS': e.stats.packetLoss,
			'PERCENT': percent,
			'GRADE': grade
		}));
	}

	holdCall()
	{
		this.toggleCallHold(true);
	}

	unholdCall()
	{
		this.toggleCallHold(false);
	}

	toggleCallHold(state)
	{
		if (!this.currentCall && this.deviceType == DeviceType.Webrtc)
		{
			return false;
		}

		if (typeof (state) != 'undefined')
		{
			this.isCallHold = !state;
		}

		if (this.isCallHold)
		{
			if (this.deviceType === DeviceType.Webrtc)
			{
				this.currentCall.sendMessage(JSON.stringify({'COMMAND': 'unhold'}));
			}
			else
			{
				BX.rest.callMethod('voximplant.call.unhold', {'CALL_ID': this.callId});
			}
		}
		else
		{
			if (this.deviceType === DeviceType.Webrtc)
			{
				this.currentCall.sendMessage(JSON.stringify({'COMMAND': 'hold'}));
			}
			else
			{
				BX.rest.callMethod('voximplant.call.hold', {'CALL_ID': this.callId});
			}
		}
		this.isCallHold = !this.isCallHold;
	}

	sendDTMF(key)
	{
		if (!this.currentCall)
		{
			return false;
		}

		this.phoneLog('Send DTMF code', this.currentCall.id(), key);

		this.currentCall.sendTone(key);
	}

	startCallViaRestApp(number, lineId, params)
	{
		BX.rest.callMethod(
			'voximplant.call.startViaRest',
			{
				'NUMBER': number,
				'LINE_ID': lineId,
				'PARAMS': params,
				'SHOW': 'Y'
			}
		);
	}

	phoneSupport()
	{
		return this.phoneEnabled && (this.hasSipPhone || this.ready());
	}

	muteCall()
	{
		if (!this.currentCall)
		{
			return false;
		}

		this.isMuted = true;
		this.currentCall.muteMicrophone();
	}

	unmuteCall()
	{
		if (!this.currentCall)
		{
			return false;
		}

		this.isMuted = false;
		this.currentCall.unmuteMicrophone();
	}

	toggleCallAudio()
	{
		if (!this.currentCall)
		{
			return false;
		}

		if (this.isMuted)
		{
			this.currentCall.unmuteMicrophone();
			this.callView.setMuted(false);
		}
		else
		{
			this.currentCall.muteMicrophone();
		}
		this.isMuted = !this.isMuted;
	}

	phoneDeviceCall(status)
	{
		let result = true;
		if (typeof (status) == 'boolean')
		{
			this.messengerFacade.setLocalConfig('viDeviceCallBlock', !status);
			BX.localStorage.set('viDeviceCallBlock', !status, 86400);
			if (this.callView)
			{
				this.callView.setDeviceCall(status);
			}
		}
		else
		{
			let deviceCallBlock = this.messengerFacade.getLocalConfig('viDeviceCallBlock');
			if (!deviceCallBlock)
			{
				deviceCallBlock = BX.localStorage.get('viDeviceCallBlock');
			}
			result = this.hasSipPhone && !deviceCallBlock;
		}
		return result;

	}

	openKeyPad(e = {})
	{
		if (Loc.getMessage["voximplantCanMakeCalls"] == "N")
		{
			Runtime.loadExtension("voximplant.common").then(() => BX.Voximplant.openLimitSlider());
			return;
		}

		this.loadPhoneLines().then(() => this.#doOpenKeyPad(e));
	}

	#doOpenKeyPad(e)
	{
		if (!this.phoneSupport() && !this.isRestLine(this.defaultLineId))
		{
			this.showUnsupported();
			return false;
		}

		if (this.hasActiveCall() || BX.localStorage.get(lsKeys.callInited) || BX.localStorage.get(lsKeys.externalCall))
		{
			return false;
		}

		if (this.keypad)
		{
			this.keypad.close();
			return false;
		}

		this.keypad = new Keypad({
			bindElement: e.bindElement,
			offsetTop: e.offsetTop,
			offsetLeft: e.offsetLeft,
			anglePosition: e.anglePosition,
			angleOffset: e.angleOffset,
			defaultLineId: this.defaultLineId,
			lines: this.phoneLines,
			availableLines: this.availableLines,
			history: this.dialHistory,
			callInterceptAllowed: this.callInterceptAllowed,

			onDial: this.onKeyPadDial.bind(this),
			onIntercept: this.onKeyPadIntercept.bind(this),
			onClose: () =>
			{
				this.onKeyPadClose();
				if (Type.isFunction(e.onClose))
				{
					e.onClose()
				}
			}
		});
		this.keypad.show();
	}

	onKeyPadDial(e)
	{
		let params = {};
		this.closeKeyPad()

		if (e.lineId)
		{
			params['LINE_ID'] = e.lineId;
		}

		this.phoneCall(e.phoneNumber, params);
	}

	onKeyPadIntercept(e)
	{
		if (!this.callInterceptAllowed)
		{
			this.keypad.close();
			if ('UI' in BX && 'InfoHelper' in BX.UI)
			{
				BX.UI.InfoHelper.show('limit_contact_center_telephony_intercept');
			}
			return;
		}

		BX.rest.callMethod('voximplant.call.intercept').then((response) =>
		{
			const data = response.data();

			if (!data.FOUND || data.FOUND == 'Y')
			{
				this.keypad.close();
			}
			else
			{
				if (data.ERROR)
				{
					this.interceptErrorPopup = new Popup({
						id: 'intercept-call-error',
						bindElement: e.interceptButton,
						targetContainer: document.body,
						content: Text.encode(data.ERROR),
						autoHide: true,
						closeByEsc: true,
						cacheable: false,
						bindOptions: {
							position: 'bottom'
						},
						angle: {
							offset: 40
						},
						events: {
							onPopupClose: (e) => this.interceptErrorPopup = null,
						}
					});
					this.interceptErrorPopup.show();
				}
			}
		});
	}

	onKeyPadClose()
	{
		this.keypad = null;
	}

	closeKeyPad()
	{
		if (this.keypad)
		{
			this.keypad.close();
		}
	}

	phoneDisplayExternal(params)
	{
		var number = params.phoneNumber;
		this.phoneLog(number, params);

		this.phoneNumberUser = Text.encode(number);

		number = this.correctPhoneNumber(number);
		if (typeof (params) != 'object')
		{
			params = {};
		}

		if (this.callActive)
		{
			return;
		}

		if (this.callView)
		{
			return;
		}

		this.initiator = true;
		this.callInitUserId = this.userId;
		this.callActive = false;
		this.callUserId = 0;
		this.phoneNumber = number;

		this.callView = new PhoneCallView({
			callId: params.callId,
			config: params.config,
			direction: Direction.outgoing,
			phoneNumber: this.phoneNumber,
			statusText: Loc.getMessage('IM_M_CALL_ST_CONNECT'),
			hasSipPhone: true,
			deviceCall: true,
			portalCall: params.portalCall,
			portalCallUserId: params.portalCallUserId,
			portalCallData: params.portalCallData,
			portalCallQueueName: params.portalCallQueueName,
			crm: params.showCrmCard,
			crmEntityType: params.crmEntityType,
			crmEntityId: params.crmEntityId,
			crmData: this.phoneCrm,
			foldedCallView: this.foldedCallView,
			backgroundWorker: this.backgroundWorker,
			messengerFacade: this.messengerFacade,
			restApps: this.restApps,
		});
		this.#bindPhoneViewCallbacks(this.callView);
		this.callView.setUiState(UiState.idle);
		this.callView.setCallState(CallState.connected);
		this.callView.show();
	}

	loadPhoneLines()
	{
		const cachedLines = BX.localStorage.get('bx-im-phone-lines');
		if (cachedLines)
		{
			this.phoneLines = cachedLines;
			return Promise.resolve(cachedLines);
		}

		return new Promise((resolve, reject) =>
		{
			if (this.phoneLines)
			{
				return resolve(this.phoneLines);
			}

			BX.ajax.runAction("voximplant.callView.getLines").then((response) =>
			{
				this.phoneLines = response.data;
				BX.localStorage.set('bx-im-phone-lines', this.phoneLines, 86400);
				{
					resolve(this.phoneLines);
				}
			}).catch((err) =>
			{
				console.error(err);
				reject(err)
			})
		})
	}

	isRestLine(lineId)
	{
		if (!this.phoneLines)
		{
			throw new Error("Phone lines are not loaded. Call PhoneCallsController.loadPhoneLines prior to using this method")
		}

		if (this.phoneLines.hasOwnProperty(lineId))
		{
			return this.phoneLines[lineId].TYPE === 'REST';
		}
		else
		{
			return false;
		}
	}

	setPhoneNumber(phoneNumber)
	{
		const matches = /(\+?\d+)([;#]*)([\d,]*)/.exec(phoneNumber);
		this.phoneFullNumber = phoneNumber;
		if (matches)
		{
			this.phoneNumber = matches[1];
		}
	}

	phoneCall(number, params)
	{
		this.loadPhoneLines().then(() => this.#doPhoneCall(number, params));
	}

	#doPhoneCall(number, params = {})
	{
		if (BX.localStorage.get(lsKeys.callInited) || this.callView || this.hasActiveCall())
		{
			return false;
		}

		if (!this.phoneSupport())
		{
			this.showUnsupported();
			return false;
		}

		if (this.keypad)
		{
			this.keypad.close();
		}

		if (Type.isStringFilled(number))
		{
			this.addToHistory(number);
		}

		const lineId = Type.isStringFilled(params['LINE_ID']) ? params['LINE_ID'] : this.defaultLineId;
		if (this.isRestLine(lineId))
		{
			this.startCallViaRestApp(number, lineId, params);
			return true;
		}

		this.phoneLog(number, params);

		this.phoneNumberUser = Text.encode(number);
		let numberOriginal = number;

		if (typeof (params) != 'object')
		{
			params = {};
		}

		const internationalNumber = this.correctPhoneNumber(number);

		if (internationalNumber.length <= 0)
		{
			MessageBox.alert(Loc.getMessage('IM_PHONE_WRONG_NUMBER_DESC'), Loc.getMessage('IM_PHONE_WRONG_NUMBER'))
			return false;
		}

		this.setPhoneNumber(internationalNumber);

		this.initiator = true;
		this.callInitUserId = this.userId;
		this.callActive = false;
		this.callUserId = 0;
		this.hasExternalCall = this.phoneDeviceCall();
		this.phoneParams = params;

		this.callView = new PhoneCallView({
			darkMode: this.messengerFacade.isThemeDark(),

			phoneNumber: this.phoneFullNumber,
			callTitle: this.phoneNumberUser,
			fromUserId: this.userId,
			direction: Direction.outgoing,
			uiState: UiState.connectingOutgoing,
			status: Loc.getMessage('IM_M_CALL_ST_CONNECT'),
			hasSipPhone: this.hasSipPhone,
			deviceCall: this.hasExternalCall,
			crmData: this.phoneCrm,
			autoFold: (params['AUTO_FOLD'] === true),
			foldedCallView: this.foldedCallView,
			backgroundWorker: this.backgroundWorker,
			messengerFacade: this.messengerFacade,
			restApps: this.restApps,
		});
		this.#bindPhoneViewCallbacks(this.callView);
		this.callView.show();

		this.messengerFacade.playSound("start");

		if (this.hasExternalCall)
		{
			this.deviceType = DeviceType.Phone;
			this.callView.setProgress(CallProgress.wait);
			this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_PHONE_NOTICE'));

			const callStartParams = {
				'NUMBER': numberOriginal.toString().replace(/[^0-9+*#,;]/g, ''),
				'PARAMS': params,
			};

			BX.rest.callMethod('voximplant.call.startWithDevice', callStartParams)
				.then((response) =>
				{
					const data = response.data();
					this.callId = data.CALL_ID;
					this.hasExternalCall = (data.EXTERNAL === true);
					this.phoneCallConfig = data.CONFIG;
					this.callView.setProgress(CallProgress.wait);
					this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_WAIT_PHONE'));
					this.callView.setUiState(UiState.connectingOutgoing);
					this.callView.setCallState(CallState.connecting);

					this.emit(Events.onDeviceCallStarted, {
						callId: data.CALL_ID,
						config: data.CONFIG
					});
				})
				.catch(err =>
				{
					this.callView.setProgress(CallProgress.error);
					this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_PHONE_ERROR'));
					this.callView.setUiState(UiState.error);
					this.callView.setCallState(CallState.idle);
				});
		}
		else
		{
			this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_CALL_INIT'));

			this.phoneApiInit().then(() => this.#phoneOnSDKReady());
		}
	}

	showUnsupported()
	{
		const messageBox = new MessageBox({
			message: Loc.getMessage('IM_CALL_NO_WEBRT'),
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('IM_M_CALL_BTN_DOWNLOAD'),
			cancelCaption: Loc.getMessage('IM_NOTIFY_CONFIRM_CLOSE'),
			onOk: () =>
			{
				const url = DesktopDownload.getLinkForCurrentUser();
				window.open(url, "desktopApp");
				return true;
			},
		})
		messageBox.show();
	}

	addToHistory(phoneNumber: string)
	{
		let oldHistory = this.dialHistory;
		const phoneIndex = oldHistory.indexOf(phoneNumber);

		if (phoneIndex === 0)
		{
			//it's the first element already, nothing to do
		}
		else if (phoneIndex > 0)
		{
			//moving number to the top
			oldHistory.splice(phoneIndex, phoneIndex);
			this.dialHistory = [phoneNumber].concat(oldHistory);
		}
		else
		{
			//adding as the top element of history
			this.dialHistory = [phoneNumber].concat(oldHistory.slice(0, 4));
		}

		BX.localStorage.set(lsKeys.dialHistory, this.dialHistory, 31536000)
		this.messengerFacade.setLocalConfig('phone-history', this.dialHistory);
	}

	startCallList(callListId, params)
	{
		callListId = Number(callListId);
		if (callListId === 0 || this.currentCall || this.callView || this.isCallListMode())
		{
			return false;
		}
		this.foldedCallView.destroy();

		this.callListId = callListId;
		this.callView = new PhoneCallView({
			crm: true,
			callListId: callListId,
			callListStatusId: params.callListStatusId,
			callListItemIndex: params.callListItemIndex,
			direction: Direction.outgoing,
			makeCall: (params.makeCall === true),
			uiState: UiState.outgoing,
			webformId: params.webformId || 0,
			webformSecCode: params.webformSecCode || '',
			hasSipPhone: this.hasSipPhone,
			deviceCall: this.phoneDeviceCall(),
			crmData: this.phoneCrm,
			foldedCallView: this.foldedCallView,
			backgroundWorker: this.backgroundWorker,
			messengerFacade: this.messengerFacade,
			restApps: this.restApps,
		});

		this.#bindPhoneViewCallbacks(this.callView);
		this.callView.show();

		return true;
	}

	isCallListMode()
	{
		return (this.callListId > 0);
	}

	callListMakeCall(e)
	{
		this.loadPhoneLines().then(() => this.#doCallListMakeCall(e));
	}

	#doCallListMakeCall(e)
	{
		if (this.isRestLine(this.defaultLineId))
		{
			this.startCallViaRestApp(
				e.phoneNumber,
				this.defaultLineId,
				{
					'ENTITY_TYPE': 'CRM_' + e.crmEntityType,
					'ENTITY_ID': e.crmEntityId,
					'CALL_LIST_ID': e.callListId
				}
			);
			return true;
		}

		if (BX.localStorage.get(lsKeys.callInited))
		{
			return false;
		}

		if (this.callActive)
		{
			return false;
		}

		if (!this.callView)
		{
			return false;
		}

		this.lastCallListCallParams = e;

		if (!this.phoneSupport())
		{
			this.callView.setStatusText(Loc.getMessage('IM_CALL_NO_WEBRT'));
			this.callView.setUiState(UiState.error);
			this.callView.setCallState(CallState.idle);
			return false;
		}

		const number = e.phoneNumber;
		const numberOriginal = number;
		const internationalNumber = this.correctPhoneNumber(number);

		if (internationalNumber.length <= 0)
		{
			this.callView.setStatusText(Loc.getMessage('IM_PHONE_WRONG_NUMBER_DESC').replace("<br/>", "\n"));
			return false;
		}

		this.initiator = true;
		this.callInitUserId = this.userId;
		this.callActive = false;
		this.callUserId = 0;
		this.hasExternalCall = this.phoneDeviceCall();
		this.setPhoneNumber(internationalNumber);
		this.phoneParams = {
			'ENTITY_TYPE': 'CRM_' + e.crmEntityType,
			'ENTITY_ID': e.crmEntityId,
			'CALL_LIST_ID': e.callListId
		};

		this.messengerFacade.playSound("start");

		if (this.hasExternalCall)
		{
			this.deviceType = DeviceType.Phone;
			this.callView.setProgress(CallProgress.wait);
			this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_PHONE_NOTICE'));
			this.callView.setUiState(UiState.connectingOutgoing);
			this.callView.setCallState(CallState.connecting);
			const callStartParams = {
				'NUMBER': numberOriginal.toString().replace(/[^0-9+*#,;]/g, ''),
				'PARAMS': this.phoneParams
			};
			BX.rest.callMethod('voximplant.call.startWithDevice', callStartParams)
				.then((response) =>
				{
					const data = response.data();
					this.callId = data.CALL_ID;

					// TODO: is this necessary? It did not work previously
					this.hasExternalCall = (data.EXTERNAL === true);
					this.phoneCallConfig = data.CONFIG;

					this.callView.setProgress(CallProgress.wait);
					this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_WAIT_PHONE'));
					this.callView.setUiState(UiState.connectingOutgoing);
					this.callView.setCallState(CallState.connecting);

					this.emit(Events.onDeviceCallStarted, {
						callId: data.CALL_ID,
						config: data.CONFIG
					});
				})
				.catch(err =>
				{
					this.callView.setProgress(CallProgress.error);
					this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_PHONE_ERROR'));
					this.callView.setUiState(UiState.error);
					this.callView.setCallState(CallState.idle);
				});
		}
		else
		{
			this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_CALL_INIT'));
			this.callView.setUiState(UiState.connectingOutgoing);
			this.callView.setCallState(CallState.connecting);
			this.phoneApiInit().then(() => this.#phoneOnSDKReady());
		}
	}

	phoneIncomingAnswer()
	{
		this.clearSkipIncomingCallTimer();
		this.messengerFacade.stopRepeatSound('ringtone');
		this.callSelfDisabled = true;
		BX.rest.callMethod('voximplant.call.answer', {'CALL_ID': this.callId});

		if (this.keypad)
		{
			this.keypad.close();
		}

		this.callView.setUiState(UiState.connectingIncoming);
		this.callView.setCallState(CallState.connecting);

		this.phoneApiInit().then(
			() => BX.rest.callMethod('voximplant.call.sendReady', {'CALL_ID': this.callId})
		);
	}

	phoneApiInit()
	{
		if (!this.phoneSupport())
		{
			return Promise.reject('Telephony is not supported');
		}

		if (this.voximplantClient && this.voximplantClient.connected())
		{
			if (this.defaultMicrophone)
			{
				this.voximplantClient.useAudioSource(this.defaultMicrophone);
			}
			if (this.defaultSpeaker)
			{
				VoxImplant.Hardware.AudioDeviceManager.get().setDefaultAudioSettings({
					outputId: this.defaultSpeaker
				});
			}

			return Promise.resolve();
		}

		let phoneApiParameters = {
			useRTCOnly: true,
			micRequired: true,
			videoSupport: false,
			progressTone: false
		};

		if (this.enableMicAutoParameters === false)
		{
			phoneApiParameters.audioConstraints = {
				optional: [
					{echoCancellation: false},
					{googEchoCancellation: false},
					{googEchoCancellation2: false},
					{googDAEchoCancellation: false},
					{googAutoGainControl: false},
					{googAutoGainControl2: false},
					{mozAutoGainControl: false},
					{googNoiseSuppression: false},
					{googNoiseSuppression2: false},
					{googHighpassFilter: false},
					{googTypingNoiseDetection: false},
					{googAudioMirroring: false}
				]
			};
		}

		return new Promise((resolve, reject) =>
		{
			BX.Voximplant.getClient({
				debug: this.debug,
				apiParameters: phoneApiParameters
			}).then((client) =>
			{
				this.voximplantClient = client;

				if (this.defaultMicrophone)
				{
					this.voximplantClient.useAudioSource(this.defaultMicrophone);
				}
				if (this.defaultSpeaker)
				{
					VoxImplant.Hardware.AudioDeviceManager.get().setDefaultAudioSettings({
						outputId: this.defaultSpeaker
					});
				}

				if (this.messengerFacade.isDesktop() && Type.isFunction(this.voximplantClient.setLoggerCallback))
				{
					this.voximplantClient.enableSilentLogging();
					this.voximplantClient.setLoggerCallback(e => this.phoneLog(e.label + ": " + e.message))
				}

				this.voximplantClient.addEventListener(VoxImplant.Events.ConnectionFailed, this.#onConnectionFailed.bind(this));
				this.voximplantClient.addEventListener(VoxImplant.Events.ConnectionClosed, this.#onConnectionClosed.bind(this));
				this.voximplantClient.addEventListener(VoxImplant.Events.IncomingCall, this.#onIncomingCall.bind(this));
				this.voximplantClient.addEventListener(VoxImplant.Events.MicAccessResult, this.#onMicResult.bind(this));
				this.voximplantClient.addEventListener(VoxImplant.Events.SourcesInfoUpdated, this.phoneOnInfoUpdated.bind(this));
				this.voximplantClient.addEventListener(VoxImplant.Events.NetStatsReceived, this.#onNetStatsReceived.bind(this));
				resolve();
			}).catch((e) =>
			{
				BX.rest.callMethod('voximplant.call.onConnectionError', {
					'CALL_ID': this.callId,
					'ERROR': e
				})

				this.phoneCallFinish();
				this.messengerFacade.playSound('error');
				this.callOverlayProgress('offline');
				this.callAbort(Loc.getMessage('IM_PHONE_ERROR'));
				this.callView.setUiState(UiState.error);
				this.callView.setCallState(CallState.idle);

				reject('Could not connect to Voximplant cloud');
			});
		})
	}

	#phoneOnSDKReady(params)
	{
		this.phoneLog('SDK ready');

		params = params || {};
		params.delay = params.delay || false;

		if (!params.delay && this.hasSipPhone)
		{
			if (!this.phoneIncoming && !this.phoneDeviceCall())
			{
				this.callOverlayProgress('wait');
				this.callDialogAllowTimeout = setTimeout(() => this.#phoneOnSDKReady({delay: true}), 5000);
				return false;
			}
		}

		this.phoneLog('Connection exists');

		this.callView.setProgress(CallProgress.connect);
		this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_CONNECT'));
		this.phoneOnAuthResult({result: true});
		this.callView.setCallState(CallState.connecting);
		if (this.phoneIncoming)
		{
			this.callView.setUiState(UiState.connectingIncoming);
		}
		else
		{
			this.callView.setUiState(UiState.connectingOutgoing);
		}
	}

	phoneOnInfoUpdated(e)
	{
		this.phoneLog('Info updated', this.voximplantClient.audioSources(), this.voximplantClient.videoSources());
	}

	#onCallConnected(e)
	{
		this.clearSkipIncomingCallTimer();
		this.messengerFacade.stopRepeatSound('ringtone', 5000);

		BX.localStorage.set(lsKeys.callInited, true, 7);
		clearInterval(this.phoneConnectedInterval);
		this.phoneConnectedInterval = setInterval(
			() => BX.localStorage.set(lsKeys.callInited, true, 7),
			5000
		);

		// this.desktop.closeTopmostWindow();

		this.phoneLog('Call connected', e);

		if (this.callView)
		{
			BX.localStorage.set(lsKeys.callView, this.callView.callId, 86400);
			this.callView.setUiState(UiState.connected);
			this.callView.setCallState(CallState.connected);
			this.callView.setProgress(CallProgress.online);
			this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_ONLINE'));
		}

		this.callActive = true;

		this.emit(Events.onCallConnected, {
			call: this.currentCall,
		    isIncoming: this.phoneIncoming,
			isDeviceCall: this.phoneDeviceCall(),
		})
	}

	#bindPhoneViewCallbacks(callView)
	{
		if (!callView instanceof PhoneCallView)
		{
			return false;
		}

		callView.setCallback('mute', this.#onCallViewMute.bind(this));
		callView.setCallback('unmute', this.#onCallViewUnmute.bind(this));
		callView.setCallback('hold', this.#onCallViewHold.bind(this));
		callView.setCallback('unhold', this.#onCallViewUnhold.bind(this));
		callView.setCallback('answer', this.#onCallViewAnswer.bind(this));
		callView.setCallback('skip', this.#onCallViewSkip.bind(this));
		callView.setCallback('hangup', this.#onCallViewHangup.bind(this));
		callView.setCallback('transfer', this.#onCallViewTransfer.bind(this));
		callView.setCallback('cancelTransfer', this.#onCallViewCancelTransfer.bind(this));
		callView.setCallback('completeTransfer', this.#onCallViewCompleteTransfer.bind(this));
		callView.setCallback('callListMakeCall', this.#onCallViewCallListMakeCall.bind(this));
		callView.setCallback('close', this.#onCallViewClose.bind(this));
		callView.setCallback('switchDevice', this.#onCallViewSwitchDevice.bind(this));
		callView.setCallback('qualityGraded', this.#onCallViewQualityGraded.bind(this));
		callView.setCallback('dialpadButtonClicked', this.#onCallViewDialpadButtonClicked.bind(this));
		callView.setCallback('saveComment', this.#onCallViewSaveComment.bind(this));
	}

	#onCallViewMute()
	{
		this.muteCall();
	}

	#onCallViewUnmute()
	{
		this.unmuteCall();
	}

	#onCallViewHold()
	{
		this.holdCall();
	}

	#onCallViewUnhold()
	{
		this.unholdCall();
	}

	#onCallViewAnswer()
	{
		this.phoneIncomingAnswer();
	}

	#onCallViewSkip()
	{
		BX.rest.callMethod('voximplant.call.skip', {'CALL_ID': this.callId});

		this.phoneCallFinish();
		this.callAbort();
		this.callView.close();
	}

	#onCallViewHangup()
	{
		if (this.hasExternalCall && this.callId)
		{
			BX.rest.callMethod('voximplant.call.hangupDevice', {'CALL_ID': this.callId});
		}

		this.phoneCallFinish();
		this.messengerFacade.playSound('stop');

		if (!this.callView)
		{
			return;
		}

		this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_FINISHED'));
		this.callView.setCallState(CallState.idle);
		if (this.isCallListMode())
		{
			this.callView.setUiState(UiState.outgoing);
			if (this.callView.isFolded())
			{
				this.callView.unfold();
			}
		}
		else
		{
			this.callView.close();
		}
	}

	#onCallViewTransfer(e)
	{
		if (e.type == 'user' || e.type == 'pstn' || e.type == 'queue')
		{
			this.phoneTransferTargetType = e.type;
			this.phoneTransferTargetId = e.target;
			this.sendInviteTransfer();
		}
		else
		{
			console.error('Unknown transfer type', e);
		}
	}

	#onCallViewCancelTransfer(e)
	{
		this.cancelInviteTransfer(e);
	}

	#onCallViewCompleteTransfer(e)
	{
		this.completeTransfer(e)
	}

	#onCallViewCallListMakeCall(e)
	{
		this.callListMakeCall(e)
	}

	#onCallViewClose()
	{
		this.clearSkipIncomingCallTimer();
		this.messengerFacade.stopRepeatSound('ringtone');
		this.messengerFacade.stopRepeatSound('dialtone');

		this.callListId = 0;
		if (this.callView)
		{
			this.callView.dispose();
			this.closeCallViewBalloon();
			this.callView = null;
		}
		this.hasActiveCallView = false;

		if (this.deviceType == DeviceType.Phone)
		{
			this.callId = '';
			this.callActive = false;
			this.hasExternalCall = false;
			this.callSelfDisabled = false;
			clearInterval(this.phoneConnectedInterval);

			BX.localStorage.set(lsKeys.externalCall, false);
		}
	}

	#onCallViewSwitchDevice(e)
	{
		var phoneNumber = e.phoneNumber;
		var lastCallListCallParams = this.lastCallListCallParams;
		if (this.hasExternalCall && this.callId)
		{
			BX.rest.callMethod('voximplant.call.hangupDevice', {'CALL_ID': this.callId});
		}
		this.phoneCallFinish();
		this.callAbort();
		this.phoneDeviceCall(!this.phoneDeviceCall());
		this.callView.setDeviceCall(this.phoneDeviceCall());
		if (this.isCallListMode())
		{
			this.callListMakeCall(lastCallListCallParams);
		}
		else
		{
			this.callView.close();
			this.phoneCall(phoneNumber);
		}
	}

	#onCallViewQualityGraded(grade)
	{
		var message = {
			COMMAND: 'gradeQuality',
			grade: grade
		};
		if (this.currentCall)
		{
			this.currentCall.sendMessage(JSON.stringify(message));
		}
	}

	#onCallViewDialpadButtonClicked(key)
	{
		this.sendDTMF(key);
	}

	#onCallViewSaveComment(e)
	{
		BX.rest.callMethod("voximplant.call.saveComment", {
			'CALL_ID': e.callId,
			'COMMENT': e.comment
		})
	}

	displayIncomingCall(params)
	{
		/*chatId, callId, callerId, lineNumber, companyPhoneNumber, isCallback*/
		params.isCallback = !!params.isCallback;
		this.phoneLog('incoming call', params);

		if (!this.phoneSupport())
		{
			this.showUnsupported();
			return false;
		}

		this.phoneNumberUser = Text.encode(params.callerId);
		params.callerId = params.callerId.replace(/[^a-zA-Z0-9\.]/g, '');

		if (this.callActive)
		{
			return false;
		}

		this.initiator = true;
		this.callInitUserId = 0;
		this.callActive = false;
		this.callUserId = 0;
		this.phoneIncoming = true;
		this.callId = params.callId;
		this.phoneNumber = params.callerId;
		this.phoneParams = {};

		const direction = params.isCallback ? Direction.callback : Direction.incoming;

		this.callView = new PhoneCallView({
			userId: this.userId,
			phoneNumber: this.phoneNumber,
			lineNumber: params.lineNumber,
			companyPhoneNumber: params.companyPhoneNumber,
			callTitle: this.phoneNumberUser,
			direction: direction,
			transfer: this.isCallTransfer,
			statusText: (params.isCallback ? Loc.getMessage('IM_PHONE_INVITE_CALLBACK') : Loc.getMessage('IM_PHONE_INVITE')),
			crm: params.showCrmCard,
			crmEntityType: params.crmEntityType,
			crmEntityId: params.crmEntityId,
			crmActivityId: params.crmActivityId,
			crmActivityEditUrl: params.crmActivityEditUrl,
			callId: this.callId,
			crmData: this.phoneCrm,
			foldedCallView: this.foldedCallView,
			backgroundWorker: this.backgroundWorker,
			messengerFacade: this.messengerFacade,
			restApps: this.restApps,
		});
		this.#bindPhoneViewCallbacks(this.callView);
		this.callView.setUiState(UiState.incoming);
		this.callView.setCallState(CallState.connecting);
		if (params.config)
		{
			this.callView.setConfig(params.config);
		}

		this.callView.show();

		if (params.portalCall)
		{
			this.callView.setPortalCall(true);
			this.callView.setPortalCallData(params.portalCallData);
			this.callView.setPortalCallUserId(params.portalCallUserId);
		}

		this.hasActiveCallView = true;
		this.skipIncomingCallTimer = setTimeout(() => {
			console.log('Skip phone call by timer');
			if (!this.currentCall)
			{
				this.callView?._onSkipButtonClick();
			}
			this.skipIncomingCallTimer = null;
		}, 40000);
	}

	sendInviteTransfer()
	{
		if (!this.currentCall && this.deviceType == DeviceType.Webrtc)
		{
			return false;
		}

		if (!this.phoneTransferTargetType || !this.phoneTransferTargetId)
		{
			return false;
		}
		const transferParams = {
			'CALL_ID': this.callId,
			'TARGET_TYPE': this.phoneTransferTargetType,
			'TARGET_ID': this.phoneTransferTargetId
		};

		BX.rest.callMethod('voximplant.call.startTransfer', transferParams).then((response) =>
		{
			const data = response.data();

			if (data.SUCCESS == 'Y')
			{
				this.phoneTransferEnabled = true;
				BX.localStorage.set(lsKeys.vite, true, 1);

				this.phoneTransferCallId = data.DATA.CALL.CALL_ID;
				this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_TRANSFER'));
				this.callView.setUiState(UiState.transferring);
			}
			else
			{
				console.error("Could not start call transfer. Error: ", data.ERRORS);
			}
		});
	}

	cancelInviteTransfer()
	{
		if (!this.currentCall && this.deviceType == DeviceType.Webrtc)
		{
			return false;
		}

		this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_ONLINE'));
		this.callView.setUiState(UiState.connected);

		if (this.phoneTransferCallId !== '')
		{
			BX.rest.callMethod('voximplant.call.cancelTransfer', {'CALL_ID': this.phoneTransferCallId});
		}

		this.phoneTransferTargetId = 0;
		this.phoneTransferTargetType = '';
		this.phoneTransferCallId = '';
		this.phoneTransferEnabled = false;
		BX.localStorage.set(lsKeys.vite, false, 1);
	}

	errorInviteTransfer(code, reason)
	{
		if (code == '403' || code == '410' || code == '486')
		{
			this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_TRANSFER_' + code));
		}
		else
		{
			this.callView.setStatusText(Loc.getMessage('IM_M_CALL_ST_TRANSFER_1'));
		}
		this.messengerFacade.playSound('error', true);
		this.callView.setUiState(UiState.transferFailed);

		this.phoneTransferTargetId = 0;
		this.phoneTransferTargetType = '';
		this.phoneTransferCallId = '';
		this.phoneTransferEnabled = false;
		BX.localStorage.set(lsKeys.vite, false, 1);
	}

	completeTransfer()
	{
		BX.rest.callMethod('voximplant.call.completeTransfer', {'CALL_ID': this.phoneTransferCallId});
	}

	showExternalCall(params)
	{
		var direction;
		if (this.callView)
		{
			return;
		}

		setTimeout(() => BX.localStorage.set(lsKeys.externalCall, true, 5), 100);
		clearInterval(this.phoneConnectedInterval);
		this.phoneConnectedInterval = setInterval(() =>
		{
			if (this.hasExternalCall)
			{
				BX.localStorage.set(lsKeys.externalCall, true, 5);
			}
		}, 5000);

		this.callId = params.callId;
		this.callActive = true;
		this.hasExternalCall = true;

		if (params.isCallback)
		{
			direction = Direction.callback;
		}
		else if (params.fromUserId > 0)
		{
			direction = Direction.outgoing;
		}
		else
		{
			direction = Direction.incoming;
		}

		this.callView = new PhoneCallView({
			callId: params.callId,
			direction: direction,
			phoneNumber: params.phoneNumber,
			lineNumber: params.lineNumber,
			companyPhoneNumber: params.companyPhoneNumber,
			fromUserId: params.fromUserId,
			toUserId: params.toUserId,
			crm: params.showCrmCard,
			crmEntityType: params.crmEntityType,
			crmEntityId: params.crmEntityId,
			crmBindings: params.crmBindings,
			crmActivityId: params.crmActivityId,
			crmActivityEditUrl: params.crmActivityEditUrl,
			crmData: this.phoneCrm,
			isExternalCall: true,
			foldedCallView: this.foldedCallView,
			backgroundWorker: this.backgroundWorker,
			messengerFacade: this.messengerFacade,
			restApps: this.restApps,
		});
		this.bindPhoneViewCallbacksExternalCall(this.callView);
		this.callView.setUiState(UiState.externalCard);
		this.callView.setCallState(CallState.connected);
		this.callView.setConfig(params.config);
		this.callView.show();

		if (params.portalCall)
		{
			this.callView.setPortalCall(true);
			this.callView.setPortalCallData(params.portalCallData);
			this.callView.setPortalCallUserId(params.portalCallUserId);
		}
	}

	bindPhoneViewCallbacksExternalCall(callView)
	{
		callView.setCallback('close', () =>
		{
			if (this.callView)
			{
				this.callView.dispose();
				this.closeCallViewBalloon();
				this.callView = null;
			}
			this.hasActiveCallView = false;

			this.callId = '';
			this.callActive = false;
			this.hasExternalCall = false;
			this.callSelfDisabled = false;
			clearInterval(this.phoneConnectedInterval);
			BX.localStorage.set(lsKeys.externalCall, false);
		});
		callView.setCallback('saveComment', this.#onCallViewSaveComment.bind(this));
	}

	hideExternalCall(clearFlag)
	{
		if (this.callView && !this.callView.isCallListMode())
		{
			this.callView.autoClose();
		}
	}

	phoneLog()
	{
		if (this.messengerFacade.isDesktop())
		{
			let text = '';
			for (let i = 0; i < arguments.length; i++)
			{
				if (BX.type.isPlainObject(arguments[i]))
				{
					try
					{
						text = text + ' | ' + JSON.stringify(arguments[i]);
					} catch (e)
					{
						text = text + ' | (circular structure)';
					}
				}
				else
				{
					text = text + ' | ' + arguments[i];
				}
			}
			DesktopApi.writeToLogFile('phone.' + this.userEmail + '.log', text.substring(3));
		}
		if (this.debug)
		{
			if (console)
			{
				try
				{
					console.log('Phone Log', JSON.stringify(arguments));
				} catch (e)
				{
					console.log('Phone Log', arguments[0]);
				}

			}
		}
	}

	/**
	 * Returns promise which will be resolved if
	 *  - either Bitrix Desktop is found and this code is running inside it
	 *  - or no Bitrix Desktop found
	 * @returns {Promise}
	 */
	checkDesktop()
	{
		if (Reflection.getClass('BX.Messenger.v2.Lib.DesktopManager'))
		{
			return new Promise((resolve) => {
				const desktop = BX.Messenger.v2.Lib.DesktopManager.getInstance();
				desktop.checkStatusInDifferentContext()
					.then((result) => {
						if (result === false)
						{
							resolve();
						}
					});
			});
		}

		if (Reflection.getClass('BX.desktopUtils'))
		{
			return new Promise((resolve) => {
				BX.desktopUtils.runningCheck(
					() => {},
					() => resolve()
				);
			});
		}

		return Promise.resolve();
	}

	restoreFoldedCallView()
	{
		const callProperties = BX.localStorage.get(lsKeys.foldedView);

		if (!Type.isPlainObject(callProperties))
		{
			return;
		}

		this.callActive = true;
		this.callId = callProperties.callId;
		this.phoneCrm = callProperties.phoneCrm;
		this.deviceType = callProperties.phoneCallDevice;
		this.hasExternalCall = callProperties.hasExternalCall;

		let callViewProperties = callProperties.callView;
		callViewProperties.foldedCallView = this.foldedCallView;
		callViewProperties.backgroundWorker = this.backgroundWorker;
		callViewProperties.messengerFacade = this.messengerFacade;
		this.callView = new PhoneCallView(callProperties.callView);
		if (this.hasExternalCall)
		{
			this.callView.setUiState(UiState.externalCard);
			this.callView.setCallState(CallState.connected);
			this.bindPhoneViewCallbacksExternalCall(this.callView);
		}
		else
		{
			this.#bindPhoneViewCallbacks(this.callView);
		}

		if (this.hasExternalCall)
		{
			BX.localStorage.set(lsKeys.externalCall, true, 5);
			this.phoneConnectedInterval = setInterval(
				() =>
				{
					if (this.hasExternalCall)
					{
						BX.localStorage.set(lsKeys.externalCall, true, 5);
					}
				},
				5000
			);
		}

		BX.rest.callMethod('voximplant.call.get', {'CALL_ID': this.callId}).catch(() =>
		{
			// call is not found

			this.callId = '';
			this.callActive = false;
			this.hasExternalCall = false;
			this.callSelfDisabled = false;
			clearInterval(this.phoneConnectedInterval);
			BX.localStorage.set(lsKeys.externalCall, false);
			if (this.callView)
			{
				this.callView.dispose();
				this.closeCallViewBalloon();
				this.callView = null;
			}
			this.hasActiveCallView = false;
		});
	}

	displayCallQuality(percent)
	{
		if (!this.currentCall || this.currentCall.state() != "CONNECTED")
		{
			return false;
		}

		let grade = 5;
		if (100 == percent)
		{
			grade = 5;
		}
		else if (percent >= 99)
		{
			grade = 4;
		}
		else if (percent >= 97)
		{
			grade = 3;
		}
		else if (percent >= 95)
		{
			grade = 2;
		}
		else
		{
			grade = 1;
		}

		this.callView.setQuality(grade);
		return grade;
	}

	callOverlayProgress(progress)
	{
		if (this.callView)
		{
			this.callView.setProgress(progress);
			if (progress === 'offline')
			{
				this.messengerFacade.playSound('error');
			}
		}
	}

	callOverlayStatus(status)
	{
		if (!Type.isStringFilled(status))
		{
			return false;
		}

		if (this.callView)
		{
			this.callView.setStatusText(status);
		}
	}

	setCallOverlayTitle(title)
	{
		if (this.callView)
		{
			this.callView.setTitle(title);
		}
	}

	callOverlayTimer(state) // TODO not ready yet
	{
		state = typeof (state) == 'undefined' ? 'start' : state;

		if (state == 'start')
		{
			this.phoneCallTimeInterval = setInterval(() => this.phoneCallTime++, 1000);
		}
		else
		{
			clearInterval(this.phoneCallTimeInterval);
		}
	}

	callAbort(reason)
	{
		this.callOverlayDeleteEvents();

		if (reason && this.callView)
		{
			if (this.callView)
			{
				this.callView.setStatusText(reason);
			}
		}
	}

	callOverlayDeleteEvents()
	{
		// this.desktop.closeTopmostWindow();

		this.phoneCallFinish();

		this.clearSkipIncomingCallTimer();
		this.messengerFacade.stopRepeatSound('ringtone');
		this.messengerFacade.stopRepeatSound('dialtone');

		clearTimeout(this.callDialogAllowTimeout);
		if (this.callDialogAllow)
		{
			this.callDialogAllow.close();
		}

	}

	storageSet(params)
	{
		if (params.key == lsKeys.vite)
		{
			if (params.value === true || !this.callSelfDisabled)
			{
				this.phoneTransferEnabled = params.value;
			}
		}
		else if (params.key == lsKeys.externalCall)
		{
			if (params.value === false)
			{
				this.hideExternalCall();
			}
		}
	}

	getDebugInfo()
	{
		return {
			vInitedCall: BX.localStorage.get('vInitedCall') ? 'Y' : 'N',
			isDesktop: this.messengerFacade.isDesktop() ? 'Y' : 'N',
			appVersion: navigator.appVersion,
			hasActiveCall: this.messengerFacade.hasActiveCall() ? 'Y' : 'N',
			isCallListMode: this.isCallListMode() ? this.callListId : 'N',
			currentCall: this.currentCall ? this.currentCall.id() : 'N',
			callView: this.callView ? this.callView.callId : 'N',
			callViewPopup: this.callView?.popup ? 'Y' : 'N',
			hasActiveCallView: this.hasActiveCallView ? 'Y' : 'N',
			isFoldedCallView: this.callView?.isFolded() ? 'Y' : 'N',
			voximplantClient: this.voximplantClient ? this.voximplantClient?.connected() : 'N',
		};
	}

	showNotification(notificationText, actions, params = {})
	{
		if (!actions)
		{
			actions = [];
		}

		const options = {
			content: Text.encode(notificationText),
			position: "top-right",
			closeButton: true,
			actions: actions
		};

		if (params.autoHideDelay)
		{
			options.autoHideDelay = params.autoHideDelay;
		}
		else
		{
			options.autoHide = false;
		}

		return BX.UI.Notification.Center.notify(options);
	}

	showCallViewBalloon()
	{
		if (!this.openedCallViewBalloon && this.callView)
		{
			this.openedCallViewBalloon = this.showNotification(Loc.getMessage('VOXIMPLANT_WARN_CLOSE_CALL_VIEW'));
		}
	}

	closeCallViewBalloon()
	{
		if (this.openedCallViewBalloon)
		{
			this.openedCallViewBalloon.close();
			this.openedCallViewBalloon = null;
		}
	}

	clearSkipIncomingCallTimer()
	{
		if (this.skipIncomingCallTimer)
		{
			console.log('Clear skip incoming call timer: ' + this.skipIncomingCallTimer);
			clearTimeout(this.skipIncomingCallTimer);
			this.skipIncomingCallTimer = null;
		}
	}

	testSimple()
	{
		const callId = 'test-call'
		this.callView = new PhoneCallView({
			callId,
			restApps: this.restApps,
			foldedCallView: this.foldedCallView,
			backgroundWorker: this.backgroundWorker,
			messengerFacade: this.messengerFacade,
			darkMode: this.messengerFacade.isThemeDark(),
			events: {
				close: () =>
				{
					console.trace('close')
					this.callView?.dispose();
					this.callView = null;
				},
				hangup: () => this.callView.close(),
				transfer: (e) => console.log('transfer', e),
				dialpadButtonClicked: (e) => console.log('dialpadButtonClicked', e),
				hold: () => console.log('hold'),
				unhold: () => console.log('unhold'),
				mute: () => console.log('mute'),
				unmute: () => console.log('unmute'),
			}
		})
		this.callView.show();
	}

	testCrm()
	{

	}

	testUser()
	{
		this.callView = new PhoneCallView({
			messengerFacade: this.messengerFacade,
		})
	}

	static Events = Events;
}
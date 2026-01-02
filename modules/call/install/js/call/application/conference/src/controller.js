/**
 * Bitrix Im
 * Conference application
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2021 Bitrix
 */

// call
import * as Call from 'call.core';
import { Analytics } from 'call.lib.analytics';
import { CallTokenManager } from 'call.lib.call-token-manager';
import { CallSettingsManager } from 'call.lib.settings-manager';
import './css/view.css';
import { Util } from 'call.core';
import { Hardware } from 'call.core';

// im
import 'im.debug';
import 'im.application.launch';
import 'im.component.conference.conference-public';
import {DesktopApi} from 'im.v2.lib.desktop-api';
import { ConferenceModel, CallModel } from "im.model";
import { Controller } from 'im.controller';
import { Utils } from "im.lib.utils";
import { Cookie } from "im.lib.cookie";
import { LocalStorage } from "im.lib.localstorage";
import { Logger } from "im.lib.logger";
import { Clipboard } from 'im.lib.clipboard';
import { Desktop } from "im.lib.desktop";
import {
	EventType,
	ConferenceErrorCode,
	ConferenceRightPanelMode as RightPanelMode
} from "im.const";

//ui
import {Notifier, NotificationOptions} from 'ui.notification-manager';
import 'ui.notification';
import 'ui.buttons';
import 'ui.progressround';
import 'ui.viewer';
import { VueVendorV2 } from "ui.vue";
import { VuexBuilder } from "ui.vue.vuex";

import { JoinResponseError, ParticipantsPermissionPopup } from 'call.core';

// core
import { Loc, Tag, Dom, Text, Type } from "main.core";
import "promise";
import 'main.date';
import {BaseEvent, EventEmitter} from 'main.core.events';

// pull and rest
import { PullClient } from "pull.client";
import { ImCallPullHandler } from "im.provider.pull";

import { ConferenceChannel } from './conference-channel';
import { CallRestClient } from "./utils/restclient";

const BALLOON_OFFSET_CLASS_NAME = 'bx-call-control-notification-right-offset';

class ConferenceApplication
{
	/* region 01. Initialize */
	constructor(params = {})
	{
		this.inited = false;
		this.hardwareInited = false;
		this.dialogInited = false;
		this.initPromise = new BX.Promise;

		this.params = params;
		this.params.userId = this.params.userId? parseInt(this.params.userId): 0;
		this.params.siteId = this.params.siteId || '';
		this.params.chatId = this.params.chatId? parseInt(this.params.chatId): 0;
		this.params.dialogId = this.params.chatId? 'chat'+this.params.chatId.toString(): '0';
		this.params.passwordRequired = !!this.params.passwordRequired;
		this.params.isBroadcast = !!this.params.isBroadcast;

		BX.Messenger.Lib.Logger.setConfig(params.loggerConfig);

		this.messagesQueue = [];

		this.template = null;
		this.rootNode = this.params.node || document.createElement('div');

		this.event = new VueVendorV2;
		this.callContainer = null;
		// this.callView = null;
		this.preCall = null;
		this.currentCall = null;
		this.callToken = this.params.callToken ?? null;
		this.videoStrategy = null;
		this.callDetails = {};
		this.showFeedback = true;
		this.callScheme = null;

		this.promotedToAdminTimeoutValue = 10 * 1000; // 10 sec
		this.promotedToAdminTimeout = null;

		this.featureConfig = {};
		(params.featureConfig || []).forEach(limit => {
			this.featureConfig[limit.id] = limit;
		});

		this.localVideoStream = null;

		// save reconnect camera
		this.lastUsedCameraId = null;
		this.reconnectingCameraId = null;

		this.conferencePageTagInterval = null;

		this.onCallUserInvitedHandler = this.onCallUserInvited.bind(this);
		this.onCallUserJoinedHandler = this.onCallUserJoined.bind(this);
		this.onCallUserStateChangedHandler = this.onCallUserStateChanged.bind(this);
		this.onCallUserMicrophoneStateHandler = this.onCallUserMicrophoneState.bind(this);
		this.onCallUserCameraStateHandler = this.onCallUserCameraState.bind(this);
		this.onNeedResetMediaDevicesStateHandler = this.onNeedResetMediaDevicesState.bind(this);
		this.onCallUserVideoPausedHandler = this.onCallUserVideoPaused.bind(this);
		this.onCallLocalMediaReceivedHandler = BX.debounce(this.onCallLocalMediaReceived.bind(this), 1000);
		this.onCallRemoteMediaReceivedHandler = this.onCallRemoteMediaReceived.bind(this);
		this.onCallRemoteMediaStoppedHandler = this.onCallRemoteMediaStopped.bind(this);
		this.onCallUserVoiceStartedHandler = this.onCallUserVoiceStarted.bind(this);
		this.onCallUserVoiceStoppedHandler = this.onCallUserVoiceStopped.bind(this);
		this.onUserStatsReceivedHandler = this.onUserStatsReceived.bind(this);
		this.onCallUserScreenStateHandler = this.onCallUserScreenState.bind(this);
		this.onCallUserRecordStateHandler = this.onCallUserRecordState.bind(this);
		this.onCallUserFloorRequestHandler = this.onCallUserFloorRequest.bind(this);
		this.onMicrophoneLevelHandler = this.onMicrophoneLevel.bind(this);
		this._onCallJoinHandler = this.onCallJoin.bind(this);
		this.onCallFailureHandler = this.onCallFailure.bind(this);
		this.onCallLeaveHandler = this.onCallLeave.bind(this);
		this.onCallDestroyHandler = this.onCallDestroy.bind(this);
		this.onInputFocusHandler = this.onInputFocus.bind(this);
		this.onInputBlurHandler = this.onInputBlur.bind(this);
		this.onReconnectingHandler = this.onReconnecting.bind(this);
		this.onReconnectedHandler = this.onReconnected.bind(this);
		this.onReconnectingFailedHandler = this.onReconnectingFailed.bind(this);
		this.onUpdateLastUsedCameraIdHandler = this.onUpdateLastUsedCameraId.bind(this);
		this.onCallConnectionQualityChangedHandler = this.onCallConnectionQualityChanged.bind(this);
		this.onCallToggleRemoteParticipantVideoHandler = this.onCallToggleRemoteParticipantVideo.bind(this);
		this._onGetUserMediaEndedHandler = this.onGetUserMediaEnded.bind(this);
		this._onSwitchTrackRecordStatusHandler = this.onUpdateCallCopilotState.bind(this);
		this.onCameraPublishingHandler = this.onCameraPublishing.bind(this);
		this.onMicrophonePublishingdHandler = this.onMicrophonePublishingd.bind(this);

		this._onAllParticipantsAudioMutedHandler = this._onAllParticipantsAudioMuted.bind(this);
		this._onAllParticipantsVideoMutedHandler = this._onAllParticipantsVideoMuted.bind(this);
		this._onAllParticipantsScreenshareHandler = this._onAllParticipantsScreenshareMuted.bind(this);
		this._onYouMuteAllParticipantsHandler = this._onYouMuteAllParticipants.bind(this);
		this._onRoomSettingsChangedHandler = this._onRoomSettingsChanged.bind(this);
		this._onUserPermissionsChangedHandler = this._onUserPermissionsChanged.bind(this);
		this._onUserRoleChangedHandler = this._onUserRoleChanged.bind(this);
		this._onParticipantMutedHandler = this._onParticipantMuted.bind(this);

		this.onPreCallDestroyHandler = this.onPreCallDestroy.bind(this);
		this.onPreCallUserStateChangedHandler = this.onPreCallUserStateChanged.bind(this);

		this.waitingForCallStatus = false;
		this.waitingForCallStatusTimeout = null;
		this.callEventReceived = false;
		this.callRecordState = Call.View.RecordState.Stopped;
		this.callRecordInitiatorId = null;
		this.callRecordInfo = null;

		this.floatingScreenShareWindow = null;
		this.webScreenSharePopup = null;
		this.screenShareStartTime = null;

		this.mutePopup = null;
		this.riseYouHandToTalkPopup = null;
		this.allowMutePopup = true;

		this.loopTimers = {};

		this.isFileChooserActive = false;
		this.pictureInPictureDebounceForOpen = null;
		this.isWindowFocus = true;

		if (DesktopApi.isDesktop())
		{
			ConferenceChannel.getInstance().setExecuter((callUuid) => {
				const currentCallUuid = this.params.alias;
				const hasView = Boolean(this.callView);

				const hasActiveCall = currentCallUuid === callUuid && hasView;
				if (hasActiveCall)
				{
					BXDesktopSystem.SetActiveTab();
				}

				return hasActiveCall;
			});
		}

		this.initDesktopEvents()
			.then(() => this.initAdditionalEvents())
			.then(() => this.initRestClient())
			.then(() => this.subscribePreCallChanges())
			.then(() => this.subscribeNotifierEvents())
			.then(() => this.initPullClient())
			.then(() => this.initCore())
			.then(() => this.setModelData())
			.then(() => this.initComponent())
			.then(() => this.initCallInterface())
			.then(() => this.initHardware())
			.then(() => this.initUserComplete())
			.catch((error) => {
				console.error('Init error', error);
			})
		;
	}
	/* region 01. Initialize methods */
	initDesktopEvents()
	{
		if (!DesktopApi.isDesktop())
		{
			return new Promise((resolve, reject) => resolve());
		}

		this.floatingScreenShareWindow = new Call.FloatingScreenShare({
			onBackToCallClick: this.onFloatingScreenShareBackToCallClick.bind(this),
			onStopSharingClick: this.onFloatingScreenShareStopClick.bind(this),
			onChangeScreenClick: this.onFloatingScreenShareChangeScreenClick.bind(this)
		});

		if (this.floatingScreenShareWindow)
		{
			DesktopApi.subscribe("BXScreenMediaSharing", (id, title, x, y, width, height, app) =>
			{
				this.floatingScreenShareWindow.setSharingData({
					title: title,
					x: x,
					y: y,
					width: width,
					height: height,
					app: app
				}).then(() => {
					this.floatingScreenShareWindow.show();
				}).catch(error => {
					Logger.error('setSharingData error', error);
				});
			});
		}

		DesktopApi.subscribe('bxImUpdateCounterMessage', (counter) =>
		{
			if (!this.controller)
			{
				return false;
			}

			this.controller.getStore().commit('conference/common', {
				messageCount: counter
			});
		});

		EventEmitter.subscribe(EventType.textarea.focus, this.onInputFocusHandler);
		EventEmitter.subscribe(EventType.textarea.blur, this.onInputBlurHandler);
		EventEmitter.subscribe(EventType.conference.userRenameFocus, this.onInputFocusHandler);
		EventEmitter.subscribe(EventType.conference.userRenameBlur, this.onInputBlurHandler);

		return new Promise((resolve, reject) => resolve());
	}

	initAdditionalEvents()
	{
		window.addEventListener('focus', () => {
			this.onWindowFocus();
		});

		window.addEventListener('blur', () => {
			this.onWindowBlur();
		});

		document.body.addEventListener('click', (evt) =>
		{
			this.onDocumentBodyClick(evt);
		});

		return new Promise((resolve, reject) => resolve());
	}

	initRestClient()
	{
		this.restClient = new CallRestClient({endpoint: this.getHost()+'/rest'});
		this.restClient.setConfId(this.params.conferenceId);

		return new Promise((resolve, reject) => resolve());
	}

	subscribePreCallChanges()
	{
		BX.addCustomEvent(window, 'CallEvents::callCreated', this.onCallCreated.bind(this));
	}

	subscribeNotifierEvents()
	{
		Notifier.subscribe('click', (event: BaseEvent<NotifierClickParams>) => {
			const { id } = event.getData();
			if (id.startsWith('im-videconf'))
			{
				this.toggleChat();
			}
		});
	}

	initPullClient()
	{
		if (!this.params.isIntranetOrExtranet)
		{
			this.pullClient = new PullClient({
				serverEnabled: true,
				userId: this.params.userId,
				siteId: this.params.siteId,
				restClient: this.restClient,
				skipStorageInit: true,
				configTimestamp: 0,
				skipCheckRevision: true,
				getPublicListMethod: 'im.call.channel.public.list'
			});

			return new Promise((resolve, reject) => resolve());
		}
		else
		{
			this.pullClient = BX.PULL;

			return this.pullClient.start().then(() => {
				return new Promise((resolve, reject) => resolve());
			});
		}
	}

	initCore()
	{
		this.controller = new Controller({
			host: this.getHost(),
			siteId: this.params.siteId,
			userId: this.params.userId,
			languageId: this.params.language,
			pull: {client: this.pullClient},
			rest: {client: this.restClient},
			vuexBuilder: {
				database: !Utils.browser.isIe(),
				databaseName: 'imol/call',
				databaseType: VuexBuilder.DatabaseType.localStorage,
				models: [
					ConferenceModel.create(),
					CallModel.create()
				],
			}
		});

		window.BX.Messenger.Application.Core = {
			controller: this.controller
		};

		return new Promise((resolve, reject) => {
			this.controller.ready().then(() => resolve());
		});
	}

	setModelData()
	{
		this.controller.getStore().commit('application/set', {
			dialog: {
				chatId: this.getChatId(),
				dialogId: this.getDialogId()
			},
			options: {
				darkBackground: true
			}
		});

		//set presenters ID list
		const presentersIds = this.params.presenters.map(presenter => presenter['id']);
		this.controller.getStore().dispatch('conference/setBroadcastMode', {broadcastMode: this.params.isBroadcast});
		this.controller.getStore().dispatch('conference/setPresenters', {presenters: presentersIds});

		//set presenters info in users model
		this.params.presenters.forEach(presenter => {
			this.controller.getStore().dispatch('users/set', presenter);
		});

		if (this.params.passwordRequired)
		{
			this.controller.getStore().commit('conference/common', {
				passChecked: false,
			});
		}

		if (this.params.conferenceTitle)
		{
			this.controller.getStore().dispatch('conference/setConferenceTitle', {
				conferenceTitle: this.params.conferenceTitle,
			});
		}

		if (this.params.alias)
		{
			this.controller.getStore().commit('conference/setAlias', {
				alias: this.params.alias,
			});
		}

		return new Promise((resolve, reject) => resolve());
	}

	initComponent()
	{
		if (this.getStartupErrorCode())
		{
			this.setError(this.getStartupErrorCode());
		}

		return new Promise((resolve, reject) =>
		{
			this.controller.createVue(this, {
				el: this.rootNode,
				data: () =>
				{
					return {
						dialogId: this.getDialogId()
					};
				},
				template: `<bx-im-component-conference-public :dialogId="dialogId"/>`,
			}).then(vue =>
			{
				this.template = vue;
				resolve();
			}).catch(error => reject(error));
		});
	}

	initCallInterface()
	{
		return new Promise((resolve, reject) =>
		{
			try {
				this.callContainer = document.getElementById('bx-im-component-call-container');

				let hiddenButtons = ['document'];
				if (this.isViewerMode())
				{
					hiddenButtons = ['camera', 'microphone', 'screen', 'record', 'floorRequest', 'document'];
				}
				if (!this.params.isIntranetOrExtranet)
				{
					hiddenButtons.push('record');
				}

				if (!Util.isConferenceChatEnabled())
				{
					hiddenButtons.push('chat');
				}

				this.callView = new Call.View({
					container: this.callContainer,
					showChatButtons: true,
					showUsersButton: true,
					showShareButton: this.getFeatureState('screenSharing') !== ConferenceApplication.FeatureState.Disabled,
					showRecordButton: this.getFeatureState('record') !== ConferenceApplication.FeatureState.Disabled,
					userLimit: Util.getUserLimit(),
					isIntranetOrExtranet: !!this.params.isIntranetOrExtranet,
					language: this.params.language,
					layout: Utils.device.isMobile() ? Call.View.Layout.Mobile : Call.View.Layout.Centered,
					uiState: Call.View.UiState.Preparing,
					blockedButtons: ['camera', 'microphone', 'floorRequest', 'screen', 'record', 'copilot'],
					localUserState: Call.UserState.Idle,
					hiddenTopButtons: !this.isBroadcast() || this.getBroadcastPresenters().length > 1? []: ['grid'],
					hiddenButtons: hiddenButtons,
					broadcastingMode: this.isBroadcast(),
					broadcastingPresenters: this.getBroadcastPresenters(),
					isCopilotFeaturesEnabled: false,
					isCopilotActive: false,
					isWindowFocus: this.isWindowFocus,
					isVideoconf: true,
				});

				this.callView.subscribe(Call.View.Event.onButtonClick, this.onCallButtonClick.bind(this));
				this.callView.subscribe(Call.View.Event.onReplaceCamera, this.onCallReplaceCamera.bind(this));
				this.callView.subscribe(Call.View.Event.onReplaceMicrophone, this.onCallReplaceMicrophone.bind(this));
				this.callView.subscribe(Call.View.Event.onReplaceSpeaker, this.onCallReplaceSpeaker.bind(this));
				this.callView.subscribe(Call.View.Event.onHasMainStream, this.onCallViewHasMainStream.bind(this));
				this.callView.subscribe(Call.View.Event.onChangeHdVideo, this.onCallViewChangeHdVideo.bind(this));
				this.callView.subscribe(Call.View.Event.onChangeMicAutoParams, this.onCallViewChangeMicAutoParams.bind(this));
				this.callView.subscribe(Call.View.Event.onChangeFaceImprove, this.onCallViewChangeFaceImprove.bind(this));
				this.callView.subscribe(Call.View.Event.onUserRename, this.onCallViewUserRename.bind(this));
				this.callView.subscribe(Call.View.Event.onUserPinned, this.onCallViewUserPinned.bind(this));
				this.callView.subscribe(Call.View.Event.onToggleSubscribe, this.onCallToggleSubscribe.bind(this));

				this.callView.setCallback(Call.View.Event.onTurnOffParticipantMic, this._onCallViewTurnOffParticipantMic.bind(this));
				this.callView.setCallback(Call.View.Event.onTurnOffParticipantCam, this._onCallViewTurnOffParticipantCam.bind(this));
				this.callView.setCallback(Call.View.Event.onTurnOffParticipantScreenshare, this._onCallViewTurnOffParticipantScreenshare.bind(this));
				this.callView.setCallback(Call.View.Event.onAllowSpeakPermission, this._onCallViewAllowSpeakPermission.bind(this));
				this.callView.setCallback(Call.View.Event.onDisallowSpeakPermission, this._onCallViewDisallowSpeakPermission.bind(this));

				this.callView.blockAddUser();
				this.callView.blockHistoryButton();

				if (!Utils.device.isMobile())
				{
					this.callView.show();
				}

				resolve()
			} catch (error)
			{
				Logger.error('creating call interface conference', error);

				let errorCode = 'UNKNOWN_ERROR';
				if (Type.isString(error))
				{
					errorCode = error;
				}
				else if (Type.isPlainObject(error) && error.code)
				{
					errorCode = error.code == 'access_denied' ? 'ACCESS_DENIED' : error.code
				}

				this.onCallFailure({
					code: errorCode,
					message: error.message || "",
				})

				reject('call interface error');
			}
		})
	}

	initUserComplete()
	{
		return new Promise((resolve, reject) => {
			this.initUser()
				.then(() => this.tryJoinExistingCall())
				.then(() => this.initCall())
				.then(() => this.startPageTagInterval())
				.then(() => this.initPullHandlers())
				.then(() => this.subscribeToStoreChanges())
				.then(() => this.initComplete())
				.then(() => resolve)
				.catch((error) => reject(error));
		})
	}
	/* endregion 01. Initialize methods */

	/* region 02. initUserComplete methods */
	initUser()
	{
		return new Promise((resolve, reject) => {
			if (this.getStartupErrorCode() || !this.getConference().common.passChecked)
			{
				return reject();
			}

			if (this.params.userId > 0)
			{
				this.controller.setUserId(this.params.userId);

				if (this.params.isIntranetOrExtranet)
				{
					this.switchToSessAuth();

					this.controller.getStore().commit('conference/user', {
						id: this.params.userId
					});
				}
				else
				{
					let hashFromCookie = this.getUserHashCookie();
					if (hashFromCookie)
					{
						CallTokenManager.setQueryParams({
							call_auth_id: hashFromCookie,
							videoconf_id: this.params.conferenceId,
						});
						this.restClient.setAuthId(hashFromCookie);
						this.restClient.setChatId(this.getChatId());
						this.controller.getStore().commit('conference/user', {
							id: this.params.userId,
							hash: hashFromCookie
						});

						this.pullClient.start();
					}
				}

				this.controller.getStore().commit('conference/common', {
					inited: true
				});

				return resolve();
			}
			else
			{
				this.restClient.setAuthId('guest');
				this.restClient.setChatId(this.getChatId());

				if (typeof BX.SidePanel !== 'undefined')
				{
					BX.SidePanel.Instance.disableAnchorBinding();
				}

				return this.restClient.callMethod('im.call.user.register', {
					alias: this.params.alias,
					user_hash: this.getUserHashCookie() || '',
				}).then((result) => {
					BX.message.USER_ID = result.data().id;
					this.controller.getStore().commit('conference/user', {
						id: result.data().id,
						hash: result.data().hash,
					});

					this.controller.setUserId(result.data().id);
					this.callView.setLocalUserId(result.data().id);

					Call.Engine.setCurrentUserId(this.controller.getUserId());
					Call.EngineLegacy.setCurrentUserId(this.controller.getUserId());

					CallTokenManager.setUserToken(result.data().userToken);

					if (result.data().created)
					{
						this.params.userCount++;
					}

					this.controller.getStore().commit('conference/common', {
						inited: true,
					});

					CallTokenManager.setQueryParams({
						call_auth_id: result.data().hash,
						videoconf_id: this.params.conferenceId,
					});

					this.restClient.setAuthId(result.data().hash);
					this.pullClient.start();

					return resolve();
				});
			}
		});
	}

	startPageTagInterval()
	{
		return new Promise((resolve) => {
			clearInterval(this.conferencePageTagInterval);
			this.conferencePageTagInterval = setInterval(() => {
				LocalStorage.set(this.params.siteId, this.params.userId, this.callEngine.getConferencePageTag(this.params.dialogId), "Y", 2);
			}, 1000);
			resolve();
		})
	}

	tryJoinExistingCall()
	{
		return new Promise((resolve, reject) => {
			const url = CallSettingsManager.jwtCallsEnabled
				? 'call.Call.tryJoinCall'
				: 'im.call.tryJoinCall';

			const callTypeKey = CallSettingsManager.jwtCallsEnabled
				? 'callType'
				: 'type';

			BX.ajax.runAction(url, {
					data: {
						entityType: 'chat',
						entityId: this.params.dialogId,
						provider: Call.Provider.Bitrix,
						[callTypeKey]: Call.Type.Permanent,
					},
				})
				.then((result) => {
					const data = result.data;
					Logger.warn('tryJoinCall', data);

					if (data.success)
					{
						this.waitingForCallStatus = true;
						this.callScheme = data.call.SCHEME;

						if (this.callScheme === Call.CallScheme.jwt)
						{
							this.callToken = data.callToken;
							Call.Engine.instantiateCall(data.call, data.callToken, data.logToken, data.userData);
						}
						else
						{
							Call.EngineLegacy.instantiateCall(data.call, data.users, data.logToken, data.connectionData, data.userData);
						}
						this.waitingForCallStatusTimeout = setTimeout(() => {
							this.waitingForCallStatus = false;
							if (!this.callEventReceived)
							{
								this.setConferenceStatus(false);
							}
							this.callEventReceived = false;
						}, 5000);
					}
					else
					{
						this.setConferenceStatus(false);
					}

					resolve();
				})
				.catch(() => {
					this.setConferenceStatus(false);
					resolve();
				});
		});

	}

	initCall()
	{
		return new Promise((resolve) => {
			if (this.callScheme)
			{
				this.callEngine = this.callScheme === Call.CallScheme.jwt
					? Call.Engine
					: Call.EngineLegacy;
			}
			else
			{
				this.callEngine = CallSettingsManager.jwtCallsEnabled
					? Call.Engine
					: Call.EngineLegacy;
			}

			Call.Engine.setRestClient(this.restClient);
			Call.Engine.setPullClient(this.pullClient);

			// this is a workaround to use actual parameters for conference guests in Util
			// since we don't know if a call is legacy or not when we make a REST request
			Call.EngineLegacy.setRestClient(this.restClient);
			Call.EngineLegacy.setPullClient(this.pullClient);
			this.callView.unblockButtons(['chat']);

			resolve();
		})
	}

	initPullHandlers()
	{
		this.pullClient.subscribe(
			new ImCallPullHandler({
				store: this.controller.getStore(),
				application: this,
				controller: this.controller,
			})
		);

		return new Promise((resolve, reject) => resolve());
	}

	subscribeToStoreChanges()
	{
		this.controller.getStore().subscribe((mutation, state) => {
			const { payload, type } = mutation;
			if (type === 'users/update' && payload.fields.name)
			{
				if (!this.callView)
				{
					return false;
				}

				this.callView.updateUserData(
					{[payload.id]: {name: payload.fields.name}}
				);
			}
			else if (type === 'dialogues/set')
			{
				if (payload[0].dialogId !== this.getDialogId())
				{
					return false;
				}

				if (!Utils.platform.isBitrixDesktop())
				{
					this.callView.setButtonCounter('chat', payload[0].counter);
				}
			}
			else if (type === 'dialogues/update')
			{
				if (payload.dialogId !== this.getDialogId())
				{
					return false;
				}

				if (typeof payload.fields.counter === 'number' && this.callView)
				{
					if (Utils.platform.isBitrixDesktop())
					{
						if (
							payload.actionName === "decreaseCounter"
							&& !payload.dialogMuted
							&& typeof payload.fields.previousCounter === 'number'
						)
						{
							let counter = payload.fields.counter;
							if (this.getConference().common.messageCount)
							{
								counter = this.getConference().common.messageCount - (payload.fields.previousCounter - counter);
								if (counter < 0)
								{
									counter = 0;
								}
							}
							this.callView.setButtonCounter('chat', counter);
						}
					}
					else
					{
						this.callView.setButtonCounter('chat', payload.fields.counter);
					}
				}

				if (typeof payload.fields.name !== 'undefined')
				{
					document.title = payload.fields.name.toString();
				}
			}
			else if (type === 'conference/common' && typeof payload.messageCount === 'number')
			{
				if (this.callView)
				{
					this.callView.setButtonCounter('chat', payload.messageCount);
				}
			}
		});
	}

	initComplete()
	{
		if (this.isExternalUser())
		{
			this.callView.localUser.userModel.allowRename = true;
		}

		if (this.getConference().common.inited)
		{
			this.inited = true;
			this.initPromise.resolve(this);
		}

		if (DesktopApi.isDesktop())
		{
			DesktopApi.emitToMainWindow('bxConferenceLoadComplete', []);
		}

		return new Promise((resolve, reject) => resolve());
	}
	/* endregion 02. initUserComplete methods */
	/* endregion 01. Initialize */

	/* region 02. Methods */

	/* region 01. Call methods */
	initHardware()
	{
		return new Promise((resolve, reject) =>
		{
			Call.Hardware.init().then(() => {
				if (this.hardwareInited)
				{
					resolve();
					return true;
				}

				if (Object.values(Call.Hardware.microphoneList).length === 0)
				{
					this.setError(ConferenceErrorCode.missingMicrophone);
				}

				if (!this.isViewerMode())
				{
					this.checkAvailableCamera();
					this.checkAvailableMicrophone();
					this.callView.enableMediaSelection();
				}

				Call.Hardware.subscribe(Call.Hardware.Events.deviceChanged, this._onDeviceChange.bind(this));

				this.hardwareInited = true;
				resolve();
			}).catch(error => {
				if (error === 'NO_WEBRTC' && this.isHttps())
				{
					this.setError(ConferenceErrorCode.unsupportedBrowser);
				}
				else if (error === 'NO_WEBRTC' && !this.isHttps())
				{
					this.setError(ConferenceErrorCode.unsafeConnection);
				}
				Logger.error('Init hardware error', error);
				reject(error)
			})
		});
	}

	_onDeviceChange(e)
	{
		if (!this.currentCall || !this.currentCall.ready)
		{
			this.checkAvailableCamera();
			this.checkAvailableMicrophone();
			return;
		}

		const allAddedDevice = e.data.added;
		const allRemovedDevice = e.data.removed;
		const removed = Call.Hardware.getRemovedUsedDevices(
			e.data.removed,
			{
				microphoneId: this.currentCall.microphoneId,
				cameraId: this.currentCall.cameraId,
				speakerId: this.callView.speakerId,
			}
		);

		if (allAddedDevice)
		{
			setTimeout(() => this.useDevicesInCurrentCall(allAddedDevice), 500);
		}

		if (removed.length > 0)
		{
			BX.UI.Notification.Center.notify({
				content: BX.message("IM_CALL_DEVICES_DETACHED") + "<br><ul>" + removed.map(function (deviceInfo)
				{
					return "<li>" + deviceInfo.label
				}) + "</ul>",
				position: "top-right",
				autoHideDelay: 10000,
				closeButton: true,
				//category: "call-device-change",
				actions: [{
					title: BX.message("IM_CALL_DEVICES_CLOSE"),
					events: {
						click: function (event, balloon)
						{
							balloon.close();
						}
					}
				}]
			});
		}

		if (allRemovedDevice)
		{
			setTimeout(() => this.removeDevicesFromCurrentCall(allRemovedDevice), 500);
		}
	}

	setDefaultCameraIfNeeded()
	{
		if (!Call.Hardware.hasCamera() || (!!Call.Hardware.defaultCamera && !!Call.Hardware.cameraList.length && Call.Hardware.cameraList.some(cameraItem => cameraItem.deviceId === Call.Hardware.defaultCamera)))
		{
			return;
		}


		if (!Call.Hardware.defaultCamera && !!Call.Hardware.cameraList.length)
		{
			Call.Hardware.defaultCamera = Call.Hardware.cameraList[0].deviceId;

			return;
		}

		Call.Hardware.defaultCamera = '';
	}

	setDefaultMicrophoneIfNeeded()
	{
		if (!Call.Hardware.hasMicrophone() || (!!Call.Hardware.defaultMicrophone && !!Call.Hardware.microphoneList.length && Call.Hardware.microphoneList.some(micItem => micItem.deviceId === Call.Hardware.defaultMicrophone)))
		{
			return;
		}


		if (!Call.Hardware.defaultMicrophone && !!Call.Hardware.microphoneList.length)
		{
			Call.Hardware.defaultMicrophone = Call.Hardware.microphoneList[0].deviceId;

			return;
		}

		Call.Hardware.defaultMicrophone = '';
	}

	onBlockCameraButton()
	{
		if (!this.callView)
		{
			return;
		}

		this.callView.blockSwitchCamera();
	}

	onUnblockCameraButton()
	{
		if (!this.callView) {
			return;
		}

		this.callView.unblockSwitchCamera();
	}

	onBlockMicrophoneButton()
	{
		if (!this.callView) {
			return;
		}

		this.callView.blockSwitchMicrophone();
	}

	onUnblockMicrophoneButton()
	{
		if (!this.callView) {
			return;
		}

		this.callView.unblockSwitchMicrophone();
	}

	checkAvailableCamera()
	{
		const isCameraButtonHasBlocked = this.callView.isButtonBlocked('camera');

		if (!this.currentCall && !Call.Hardware.hasCamera())
		{
			this.onBlockCameraButton();
		}
		else
		{
			this.onUnblockCameraButton();
		}

		this.setDefaultCameraIfNeeded();

		const isActiveState = Call.Hardware.hasCamera() && Call.Hardware.defaultCamera;

		if (!this.currentCall && isCameraButtonHasBlocked)
		{
			this.template.$emit('setCameraState', isActiveState);
			this.template.$emit('cameraSelected', Call.Hardware.defaultCamera);
		}

		this.callView.updateButtons();
	}

	checkAvailableMicrophone()
	{
		if (!this.currentCall && !Call.Hardware.hasMicrophone())
		{
			this.onBlockMicrophoneButton();
		}
		else
		{
			this.onUnblockMicrophoneButton();
		}

		this.setDefaultMicrophoneIfNeeded();

		const isActiveState = Call.Hardware.hasMicrophone();

		if (!this.currentCall)
		{
			this.template.$emit('setMicState', isActiveState);
			this.template.$emit('micSelected', Call.Hardware.defaultCamera);
		}

		this.callView.updateButtons();
	}

	useDevicesInCurrentCall(deviceList, isForceUse = false)
	{
		if (!this.currentCall || !this.currentCall.ready)
		{
			return;
		}

		for (let i = 0; i < deviceList.length; i++)
		{
			const deviceInfo = deviceList[i];

			switch (deviceInfo.kind)
			{
				case "audioinput":
					if (deviceInfo.deviceId === 'default' || isForceUse)
					{
						const newDeviceId = Call.Hardware.getDefaultDeviceIdByGroupId(deviceInfo.groupId, 'audioinput');
						this.currentCall.setMicrophoneId(newDeviceId);
						this.callView.setMicrophoneId(newDeviceId);
					}

					this.checkAvailableMicrophone();

					break;
				case "videoinput":
					if (deviceInfo.deviceId === 'default' || isForceUse)
					{
						this.currentCall.setCameraId(deviceInfo.deviceId);
					}

					if (this.reconnectingCameraId === deviceInfo.deviceId && !Call.Hardware.isCameraOn)
					{
						this.updateCameraSettingsInCurrentCallAfterReconnecting(deviceInfo.deviceId)
					}

					this.checkAvailableCamera();

					break;
				case "audiooutput":
					if (this.callView && deviceInfo.deviceId === 'default' || isForceUse)
					{
						const newDeviceId = Call.Hardware.getDefaultDeviceIdByGroupId(deviceInfo.groupId, 'audiooutput');
						this.callView.setSpeakerId(newDeviceId);
					}

					break;
			}
		}
	}

	removeDevicesFromCurrentCall(deviceList)
	{
		if (!this.currentCall || !this.currentCall.ready)
		{
			return;
		}

		for (let i = 0; i < deviceList.length; i++)
		{
			const deviceInfo = deviceList[i];

			switch (deviceInfo.kind)
			{
				case "audioinput":
					if (this.currentCall.microphoneId == deviceInfo.deviceId)
					{
						const microphoneIds = Object.keys(Call.Hardware.microphoneList);
						let deviceId;

						if (microphoneIds.includes('default'))
						{
							const deviceGroup = Call.Hardware.getDeviceGroupIdByDeviceId('default', 'audioinput');
							deviceId = Call.Hardware.getDefaultDeviceIdByGroupId(deviceGroup, 'audioinput');
						}

						if (!deviceId)
						{
							deviceId = microphoneIds.length > 0 ? microphoneIds[0] : "";
						}

						this.currentCall.setMicrophoneId(deviceId);

						if (this.currentCall.provider === Call.Provider.Bitrix)
						{
							this.callView.setMicrophoneId(deviceId);
						}
					}

					this.checkAvailableMicrophone();

					break;
				case "videoinput":
					if (this.currentCall.cameraId == deviceInfo.deviceId)
					{
						const cameraIds = Object.keys(Call.Hardware.cameraList);
						this.currentCall.setCameraId(cameraIds.length > 0 ? cameraIds[0] : "");
					}

					this.checkAvailableCamera();

					break;
				case "audiooutput":
					if (this.callView && this.callView.speakerId == deviceInfo.deviceId)
					{
						const speakerIds = Object.keys(Call.Hardware.audioOutputList);
						let deviceId;

						if (speakerIds.includes('default'))
						{
							const deviceGroup = Call.Hardware.getDeviceGroupIdByDeviceId('default', 'audiooutput');
							deviceId = Call.Hardware.getDefaultDeviceIdByGroupId(deviceGroup, 'audiooutput');
						}

						if (!deviceId)
						{
							this.callView.setSpeakerId(speakerIds.length > 0 ? speakerIds[0] : "");
						}
					}

					break;
			}
		}
	}

	startCall(videoEnabled, viewerMode = false, withCopilot)
	{
		if (this.initCallPromise)
		{
			return;
		}

		if (Utils.device.isMobile())
		{
			this.callView.show();
			this.callView.setButtonCounter('chat', this.getDialogData().counter);
		}
		else
		{
			this.callView.setLayout(Call.View.Layout.Grid);
		}

		this.callView.setUiState(Call.View.UiState.Calling);

		if (videoEnabled && !Call.Hardware.hasCamera())
		{
			this.showNotification(BX.message('IM_CALL_NO_CAMERA_ERROR'));
			videoEnabled = false;
		}

		if (this.localVideoStream)
		{
			if (!videoEnabled)
			{
				this.stopLocalVideoStream();
			}
		}
		this.controller.getStore().commit('conference/startCall');

		let callTokenPromise = Promise.resolve(this.callToken);
		if (CallSettingsManager.jwtCallsEnabled && !this.callToken)
		{
			callTokenPromise = CallTokenManager.getToken(this.params.chatId);
		}

		callTokenPromise
			.then((callToken) => {
				this.callToken = callToken;
				CallTokenManager.setToken(this.params.chatId, this.callToken);

				return this.callEngine.createCall(this.getCallConfig(videoEnabled))
					.then(e =>
					{
						console.warn('call created', e);
						Logger.warn('call created', e);

						this.currentCall = e.call;
						//this.currentCall.useHdVideo(Call.Hardware.preferHdQuality);
						this.currentCall.useHdVideo(true);

						if (this.promotedToAdminTimeout)
						{
							clearTimeout(this.promotedToAdminTimeout);
						}

						if (!CallSettingsManager.jwtCallsEnabled)
						{
							this.onUpdateCallCopilotState({
								isTrackRecordOn: this.currentCall.isCopilotActive,
							});
						}

						if (Call.Hardware.defaultMicrophone)
						{
							this.currentCall.setMicrophoneId(Call.Hardware.defaultMicrophone);
						}
						if (Call.Hardware.defaultCamera)
						{
							this.currentCall.setCameraId(Call.Hardware.defaultCamera);
						}

						this.checkAvailableMicrophone();
						this.checkAvailableCamera();

						if (!Utils.device.isMobile())
						{
							this.callView.setLayout(Call.View.Layout.Grid);
						}

						if (CallSettingsManager.jwtCallsEnabled)
						{
							const userData = this.controller.getStore().getters['users/get'](this.controller.getUserId(), true);
							this.callView.appendUsers([userData.id]);
							this.callView.updateUserData({[userData.id]: userData});
						}
						else
						{
							this.callView.appendUsers(this.currentCall.getUsers());
							Util.getUsers(this.currentCall.id, this.getCallUsers(true)).then(userData => {
								this.controller.getStore().dispatch('users/set', Object.values(userData));
								this.controller.getStore().dispatch('conference/setUsers', {users: Object.keys(userData)});
								this.callView.updateUserData(userData)
							});
						}

						this.releasePreCall();
						this.bindCallEvents();
						this.updateCallUser(this.currentCall.userId, { microphoneState: !Call.Hardware.isMicrophoneMuted });
						if (e.isNew)
						{
							Analytics.getInstance().onStartVideoconf({
								callId: this.currentCall?.uuid,
								withVideo: videoEnabled,
								mediaParams: {
									video: Call.Hardware.isCameraOn,
									audio: !Call.Hardware.isMicrophoneMuted,
								},
								status: Analytics.AnalyticsStatus.success,
								isCopilotActive: this.currentCall.isCopilotActive,
								userCounter: this.currentCall.associatedEntity?.userCounter,
							});

							this.currentCall.inviteUsers();
						}
						else
						{
							this.currentCall.answer({
								joinAsViewer: viewerMode
							});
							Analytics.getInstance().onJoinVideoconf({
								callId: this.currentCall?.uuid,
								withVideo: videoEnabled,
								mediaParams: {
									video: Call.Hardware.isCameraOn,
									audio: !Call.Hardware.isMicrophoneMuted,
								},
								status: Analytics.AnalyticsStatus.success,
							});
						}

						this.onUpdateLastUsedCameraId();
					});
			})
			.catch(error => {
				Logger.error('creating call error', error);
				let errorCode = 'UNKNOWN_ERROR';
				if (Type.isString(error))
				{
					errorCode = error;
				}
				else if (Type.isObject(error) && error instanceof JoinResponseError)
				{
					errorCode = error.name;
				}
				else if (Type.isPlainObject(error) && error.code)
				{
					errorCode = error.code == 'access_denied' ? 'ACCESS_DENIED' : error.code
				}

				Analytics.getInstance().onStartCallError({
					callType: Analytics.AnalyticsType.videoconf,
					errorCode,
					errorMessage: error?.message,
				});

				this.initCallPromise = null;
			});
	}

	/**
	 * @param {int} callUuid
	 * @param {object} options
	 */
	joinCall(callId, callUuid, options)
	{
		if (this.initCallPromise)
		{
			return;
		}

		let video = BX.prop.getBoolean(options, "video", false);
		let joinAsViewer = BX.prop.getBoolean(options, "joinAsViewer", false);
		Call.Hardware.isCameraOn = !!video;

		if (Utils.device.isMobile())
		{
			this.callView.show();
		}
		else
		{
			this.callView.setLayout(Call.View.Layout.Grid);
		}

		if (joinAsViewer)
		{
			this.callView.setLocalUserDirection(Call.EndpointDirection.RecvOnly);
		}
		else
		{
			this.callView.setLocalUserDirection(Call.EndpointDirection.SendRecv);
		}

		this.callView.setUiState(Call.View.UiState.Calling);

		const isLegacyCall = Boolean(callId)
			|| this.callScheme === Call.CallScheme.classic
			|| (!this.callScheme && !CallSettingsManager.jwtCallsEnabled);

		this.initCallPromise = Promise.resolve(this.callToken);
		if (!isLegacyCall && !this.callToken)
		{
			this.initCallPromise = CallTokenManager.getToken(this.params.chatId);
		}

		this.initCallPromise
			.then((callToken) => {
				if (isLegacyCall)
				{
					return Call.EngineLegacy.getCallWithId(callId, this.getCallConfig(video));
				}

				this.callToken = callToken;
				CallTokenManager.setToken(this.params.chatId, this.callToken);

				return Call.Engine.getCallWithId(callUuid, this.getCallConfig(video, callUuid));
			})
			.then((result) => {
				this.currentCall = result.call;
				this.releasePreCall();
				this.bindCallEvents();

				if (this.promotedToAdminTimeout)
				{
					clearTimeout(this.promotedToAdminTimeout);
				}

				if (this.currentCall?.scheme === Call.CallScheme.classic || !CallSettingsManager.jwtCallsEnabled)
				{
					this.onUpdateCallCopilotState({
						isTrackRecordOn: this.currentCall.isCopilotActive,
					});
				}

				this.controller.getStore().commit('conference/startCall');

				if (this.currentCall?.scheme === Call.CallScheme.jwt)
				{
					const userData = this.controller.getStore().getters['users/get'](this.controller.getUserId(), true);
					this.callView.appendUsers([userData.id]);
					this.callView.updateUserData({[userData.id]: userData});
				}
				else
				{
					this.callView.appendUsers(this.currentCall.getUsers());
					Util.getUsers(this.currentCall.id, this.getCallUsers(true)).then(userData => {
						this.controller.getStore().dispatch('users/set', Object.values(userData));
						this.controller.getStore().dispatch('conference/setUsers', {users: Object.keys(userData)});
						this.callView.updateUserData(userData)
					});
				}

				if (!joinAsViewer)
				{
					//this.currentCall.useHdVideo(Call.Hardware.preferHdQuality);
					this.currentCall.useHdVideo(true);
					if (Call.Hardware.defaultMicrophone)
					{
						this.currentCall.setMicrophoneId(Call.Hardware.defaultMicrophone);
					}
					if (Call.Hardware.defaultCamera)
					{
						this.currentCall.setCameraId(Call.Hardware.defaultCamera);
					}


					this.checkAvailableMicrophone();
					this.checkAvailableCamera();

					this.updateCallUser(this.currentCall.userId, {microphoneState: !Call.Hardware.isMicrophoneMuted});
				}

				this.currentCall.answer({
					joinAsViewer: joinAsViewer
				});
				this.onUpdateLastUsedCameraId();
			})
			.catch((error) =>
			{
				console.error(error);

				let errorCode = 'UNKNOWN_ERROR';
				if (Type.isString(error))
				{
					errorCode = error;
				}
				else if (Type.isObject(error) && error instanceof JoinResponseError)
				{
					errorCode = error.name;
				}
				else if (Type.isPlainObject(error) && error.code)
				{
					errorCode = error.code === 'access_denied' ? 'ACCESS_DENIED' : error.code;
				}

				Analytics.getInstance().onJoinCallError({
					callType: Analytics.AnalyticsType.videoconf,
					errorCode,
					callId: callUuid,
					errorMessage: error?.message,
				});

				this.initCallPromise = null;
			});
	}

	endCall(finishCall = false)
	{
		if (this.isRecording())
		{
			Analytics.getInstance().onRecordStop({
				callId: this.currentCall.uuid,
				callType: Analytics.AnalyticsType.videoconf,
				subSection: finishCall ? Analytics.AnalyticsSubSection.contextMenu : Analytics.AnalyticsSubSection.window,
				element: finishCall ? Analytics.AnalyticsElement.finishForAllButton : Analytics.AnalyticsElement.disconnectButton,
				recordTime: Util.getRecordTimeText(this.callRecordInfo),
			});
			this.callRecordInfo = null;

			BXDesktopSystem.CallRecordStop();
		}

		this.#stopRecordCall();

		this.setConferenceHasErrorInCall(false);
		this.showFeedback = !!this.currentCall?.wasConnected;
		if (this.currentCall)
		{
			this.callDetails = {
				id: this.currentCall.uuid,
				provider: this.currentCall.provider,
				userCount: this.currentCall.users.length,
				browser: Util.getBrowserForStatistics(),
				isMobile: BX.browser.IsMobile(),
				isConference: true
			}

			this.removeCallEvents();
			this.removeAdditionalEvents();
			this.currentCall.hangup(false, '', finishCall);
		}

		if (Utils.platform.isBitrixDesktop())
		{
			if (this.floatingScreenShareWindow)
			{
				this.floatingScreenShareWindow.destroy();
				this.floatingScreenShareWindow = null;
			}

			window.close();
			// if the conference was opened incorrectly, then "window.close();" may not work in some cases
			// as a workaround, we can redirect the user back to the portal's front page.
			location.href = '/';
		}
		else
		{
			this.callView.releaseLocalMedia();
			this.callView.close();
			this.closeReconnectionBaloon();
			this.setError(ConferenceErrorCode.userLeftCall);
			this.controller.getStore().commit('conference/endCall');
		}

		if (this.riseYouHandToTalkPopup)
		{
			this.riseYouHandToTalkPopup.close();
			this.riseYouHandToTalkPopup = null;
		}

		EventEmitter.unsubscribe(EventType.textarea.focus, this.onInputFocusHandler);
		EventEmitter.unsubscribe(EventType.textarea.blur, this.onInputBlurHandler);
		EventEmitter.unsubscribe(EventType.conference.userRenameFocus, this.onInputFocusHandler);
		EventEmitter.unsubscribe(EventType.conference.userRenameBlur, this.onInputBlurHandler);
	}

	restart()
	{
		console.trace(" restart");
		if(this.currentCall)
		{
			this.removeCallEvents();
			this.currentCall = null;
		}

		if (this.promotedToAdminTimeout)
		{
			clearTimeout(this.promotedToAdminTimeout);
		}

		if(this.callView)
		{
			this.callView.releaseLocalMedia();
			this.callView.close();
			this.closeReconnectionBaloon();
			this.callView.destroy();
			this.callView = null;
		}
		this.initCallInterface();
		this.initCall();
		this.controller.getStore().commit('conference/endCall');
	}

	kickFromCall()
	{
		this.setError(ConferenceErrorCode.kickedFromCall);
		this.pullClient.disconnect();
		this.endCall();
	}

	getCallUsers(includeSelf)
	{
		if (!this.currentCall)
		{
			return [];
		}

		let result = Object.keys(this.currentCall.getUsers());
		if (includeSelf)
		{
			result.push(this.currentCall.userId);
		}
		return result;
	}

	getActiveCallUsers()
	{
		const userStates = this.currentCall.getUsers();
		let activeUsers = [];

		for (let userId in userStates)
		{
			if (userStates.hasOwnProperty(userId))
			{
				if (
					userStates[userId] === Call.UserState.Connected
					|| userStates[userId] === Call.UserState.Connecting
					|| userStates[userId] === Call.UserState.Calling
				)
				{
					activeUsers.push(userId);
				}
			}
		}
		return activeUsers;
	}

	setLocalVideoStream(stream)
	{
		this.localVideoStream = stream;
	}

	updateMediaDevices() {
		Call.Hardware.getCurrentDeviceList()
		console.log('updateMediaDevices')
	}

	stopLocalVideoStream()
	{
		if (this.localVideoStream)
		{
			this.localVideoStream.getTracks().forEach(tr => tr.stop());
		}
		this.localVideoStream = null;
	}

	setSelectedCamera(cameraId)
	{
		if (this.callView)
		{
			this.callView.setCameraId(cameraId)
		}
	}

	setSelectedMic(micId)
	{
		if (this.callView)
		{
			this.callView.setMicrophoneId(micId);
		}
	}

	getFeature(id)
	{
		if (typeof this.featureConfig[id] === 'undefined')
		{
			return {
				id,
				state: ConferenceApplication.FeatureState.Enabled,
				articleCode: ''
			}
		}

		return this.featureConfig[id];
	}

	getFeatureState(id)
	{
		return this.getFeature(id).state;
	}

	canRecord()
	{
		return Utils.platform.isBitrixDesktop() && Utils.platform.getDesktopVersion() >= 54;
	}

	isRecording()
	{
		return this.canRecord() && this.callRecordState != Call.View.RecordState.Stopped;
	}

	showFeatureLimitSlider(id)
	{
		const articleCode = this.getFeature(id).articleCode;
		if (!articleCode || !window.BX.UI.InfoHelper)
		{
			console.warn('Limit article not found', id);
			return false;
		}

		window.BX.UI.InfoHelper.show(articleCode);

		return true;
	}

	showNotification(notificationText, actions)
	{
		if (!actions)
		{
			actions = [];
		}
		BX.UI.Notification.Center.notify({
			content: Text.encode(notificationText),
			position: "top-right",
			autoHideDelay: 5000,
			closeButton: true,
			actions: actions
		});
	}

	showMicMutedNotification()
	{
		if (this.mutePopup || !this.callView || this.riseYouHandToTalkPopup || !Util.havePermissionToBroadcast('mic'))
		{
			return;
		}

		this.mutePopup = new Call.Hint({
			bindElement: this.callView.buttons.microphone.elements.icon,
			targetContainer: this.callView.container,
			buttons: [
				this.createUnmuteButton()
			],
			onClose: () =>
			{
				this.allowMutePopup = false;
				this.mutePopup.destroy();
				this.mutePopup = null;
			},
		});
		this.mutePopup.show();
	}
	createUnmuteButton()
	{
		return new BX.UI.Button({
			baseClass: "ui-btn ui-btn-icon-mic",
			text: BX.message("IM_CALL_UNMUTE_MIC"),
			size: BX.UI.Button.Size.EXTRA_SMALL,
			color: BX.UI.Button.Color.LIGHT_BORDER,
			noCaps: true,
			round: true,
			events: {
				click: () =>
				{
					this.onCallViewToggleMuteButtonClick({
						data: {
							muted: false
						}
					});
					this.mutePopup.destroy();
					this.mutePopup = null;
				}
			}
		})
	}

	showWebScreenSharePopup()
	{
		if (this.webScreenSharePopup)
		{
			this.webScreenSharePopup.show();

			return;
		}

		this.webScreenSharePopup = new Call.WebScreenSharePopup({
			bindElement: this.callView.buttons.screen.elements.root,
			targetContainer: this.callView.container,
			onClose: function ()
			{
				this.webScreenSharePopup.destroy();
				this.webScreenSharePopup = null;
			}.bind(this),
			onStopSharingClick: function ()
			{
				this.onCallViewToggleScreenSharingButtonClick();
				this.webScreenSharePopup.destroy();
				this.webScreenSharePopup = null;
			}.bind(this)
		});
		this.webScreenSharePopup.show();
	}

	isViewerMode()
	{
		let viewerMode = false;
		const isBroadcast = this.isBroadcast();
		if (isBroadcast)
		{
			const presenters = this.getBroadcastPresenters();
			const currentUserId = this.controller.getStore().state.application.common.userId;
			const isCurrentUserPresenter = presenters.includes(currentUserId);
			viewerMode = isBroadcast && !isCurrentUserPresenter;
		}
		return viewerMode;
	}

	onCallCreated(e)
	{
		Logger.warn('we got event onCallCreated', e);
		if(this.preCall || this.currentCall)
		{
			return;
		}
		let call = e.call;
		if (call.associatedEntity.type === 'chat' && call.associatedEntity.id === this.params.dialogId)
		{
			this.preCall = e.call;
			this.updatePreCallCounter();
			this.preCall.addEventListener(Call.Event.onUserStateChanged, this.onPreCallUserStateChangedHandler);
			this.preCall.addEventListener(Call.Event.onDestroy, this.onPreCallDestroyHandler);

			if (this.waitingForCallStatus)
			{
				this.callEventReceived = true;
			}
			this.setConferenceStatus(true);
			this.setConferenceStartDate(e.call.startDate);
		}

		const userReadyToJoin = this.getConference().common.userReadyToJoin;
		if (userReadyToJoin)
		{
			let viewerMode = this.isViewerMode();

			const videoEnabled = this.getConference().common.joinWithVideo;
			Logger.warn('ready to join call after waiting', videoEnabled, viewerMode);
			setTimeout(() => {
				Call.Hardware.init().then(() => {
					if (viewerMode && this.preCall)
					{
						this.joinCall(this.preCall.id, this.preCall.uuid, {
							joinAsViewer: true
						})
					}
					else
					{
						this.joinCall(this.preCall.id, this.preCall.uuid, {
							video: videoEnabled
						});
					}
				});
			}, 1000);
		}
	}

	releasePreCall()
	{
		if(this.preCall)
		{
			this.preCall.removeEventListener(Call.Event.onUserStateChanged, this.onPreCallUserStateChangedHandler);
			this.preCall.removeEventListener(Call.Event.onDestroy, this.onPreCallDestroyHandler);
			this.preCall = null;
		}
	}

	onPreCallDestroy(e)
	{
		if (this.waitingForCallStatusTimeout)
		{
			clearTimeout(this.waitingForCallStatusTimeout);
		}
		this.setConferenceStatus(false);

		this.releasePreCall();
	}

	onPreCallUserStateChanged(e)
	{
		this.updatePreCallCounter();
	}

	updatePreCallCounter()
	{
		if(this.preCall)
		{
			this.controller.getStore().commit('conference/common', {
				userInCallCount: this.preCall.getParticipatingUsers().length
			});
		}
		else
		{
			this.controller.getStore().commit('conference/common', {
				userInCallCount: 0
			});
		}
	}

	createVideoStrategy()
	{
		if (this.videoStrategy)
		{
			this.videoStrategy.destroy();
		}

		var strategyType = Utils.device.isMobile() ? VideoStrategy.Type.OnlySpeaker : VideoStrategy.Type.AllowAll;

		this.videoStrategy = new VideoStrategy({
			call: this.currentCall,
			callView: this.callView,
			strategyType: strategyType
		});
	}

	removeVideoStrategy()
	{
		if (this.videoStrategy)
		{
			this.videoStrategy.destroy();
		}
		this.videoStrategy = null;
	}

	onCallReplaceCamera(event)
	{
		let cameraId = event.data.deviceId;

		if (this.reconnectingCameraId) {
			this.setReconnectingCameraId(null);
		}

		Call.Hardware.defaultCamera = cameraId;
		if (this.currentCall)
		{
			this.currentCall.setCameraId(cameraId);
		}
		else
		{
			this.template.$emit('cameraSelected', cameraId);
		}
	}

	onCallReplaceMicrophone(event)
	{
		let microphoneId = event.data.deviceId;
		Call.Hardware.defaultMicrophone = microphoneId.deviceId;
		if (this.callView)
		{
			this.callView.setMicrophoneId(microphoneId);
		}
		if (this.currentCall)
		{
			this.currentCall.setMicrophoneId(microphoneId);
		}
		else
		{
			this.template.$emit('micSelected', event.data.deviceId);
		}
	}

	onCallReplaceSpeaker(event)
	{
		Call.Hardware.defaultSpeaker = event.data.deviceId;
	}

	onCallViewHasMainStream(event)
	{
		if (this.currentCall && this.currentCall.provider === Call.Provider.Bitrix)
		{
			this.currentCall.setMainStream(event.data.userId);
		}
	}

	_onCallViewTurnOffParticipantMic(e)
	{
		this.currentCall.turnOffParticipantStream({typeOfStream: 'mic', userId: e.userId, fromUserId: this.callEngine.getCurrentUserId()});

		Analytics.getInstance().onTurnOffParticipantStream({
			callId: this._getCallIdentifier(this.currentCall),
			callType: this.getCallType(),
			typeOfSetting: 'mic',
		});
	}

	_onCallViewTurnOffParticipantCam(e)
	{
		this.currentCall.turnOffParticipantStream({typeOfStream: 'cam', userId: e.userId, fromUserId: this.callEngine.getCurrentUserId()});

		Analytics.getInstance().onTurnOffParticipantStream({
			callId: this._getCallIdentifier(this.currentCall),
			callType: this.getCallType(),
			typeOfSetting: 'cam',
		});
	}

	_onCallViewTurnOffParticipantScreenshare(e)
	{
		this.currentCall.turnOffParticipantStream({typeOfStream: 'screenshare', userId: e.userId, fromUserId: this.callEngine.getCurrentUserId()});

		Analytics.getInstance().onTurnOffParticipantStream({
			callId: this._getCallIdentifier(this.currentCall),
			callType: this.getCallType(),
			typeOfSetting: 'screenshare',
		});
	}

	_onCallViewAllowSpeakPermission(e)
	{
		this.currentCall.allowSpeakPermission({allow: true, userId: e.userId});

		Analytics.getInstance().onAllowPermissionToSpeakResponse({
			callId: this._getCallIdentifier(this.currentCall),
			callType: this.getCallType(),
		});
	}

	_onCallViewDisallowSpeakPermission(e)
	{
		this.currentCall.allowSpeakPermission({allow: false, userId: e.userId});

		Analytics.getInstance().onDisallowPermissionToSpeakResponse({
			callId: this._getCallIdentifier(this.currentCall),
			callType: this.getCallType(),
		});
	}

	onCallViewChangeHdVideo(event)
	{
		Call.Hardware.preferHdQuality = event.data.allowHdVideo;
	}

	onCallViewChangeMicAutoParams(event)
	{
		Call.Hardware.enableMicAutoParameters = event.data.allowMicAutoParams;
	}

	onCallViewChangeFaceImprove(event)
	{
		if (!DesktopApi.isDesktop())
		{
			return;
		}

		DesktopApi.setCameraSmoothingStatus(event.data.faceImproveEnabled);
	}

	onCallViewUserRename(event)
	{
		const newName = event.data.newName;

		if (!this.isExternalUser())
		{
			return false;
		}

		if (Utils.device.isMobile())
		{
			this.renameGuestMobile(newName)
		}
		else
		{
			this.renameGuest(newName);
		}
	}

	onCallViewUserPinned(event)
	{
		if (event.data.userId)
		{
			this.updateCallUser(event.data.userId, {pinned: true});

			return true;
		}

		this.controller.getStore().dispatch('call/unpinUser');

		return true;
	}

	onCallToggleSubscribe(e) {
		if (this.currentCall && this.currentCall.provider === Call.Provider.Bitrix && e.data)
		{
			this.currentCall.toggleRemoteParticipantVideo(e.data.participantIds, e.data.showVideo, true)
		}
	}

	renameGuest(newName)
	{
		this.callView.localUser.userModel.renameRequested = true;
		this.setUserName(newName).then(() => {
			this.callView.localUser.userModel.wasRenamed = true;
			Logger.log('setting name to', newName);
		}).catch(error => {
			Logger.error('error setting name', error);
		});
	}

	renameGuestMobile(newName)
	{
		this.setUserName(newName).then(() => {
			Logger.log('setting mobile name to', newName);
			if (this.callView.renameSlider)
			{
				this.callView.renameSlider.close();
			}
		}).catch(error => {
			Logger.error('error setting name', error);
		});
	}

	onCallButtonClick(event)
	{
		const buttonName = event.data.buttonName;
		Logger.warn('Button clicked!', buttonName);

		const handlers = {
			hangup: this.onCallViewHangupButtonClick.bind(this),
			hangupOptions: this._onCallViewHangupOptionsButtonClick.bind(this),
			close: this.onCallViewCloseButtonClick.bind(this),
			//inviteUser: this.onCallViewInviteUserButtonClick.bind(this),
			toggleMute: this.onCallViewToggleMuteButtonClick.bind(this),
			toggleScreenSharing: this.onCallViewToggleScreenSharingButtonClick.bind(this),
			record: this.onCallViewRecordButtonClick.bind(this),
			toggleVideo: this.onCallViewToggleVideoButtonClick.bind(this),
			toggleSpeaker: this.onCallViewToggleSpeakerButtonClick.bind(this),
			showChat: this.onCallViewShowChatButtonClick.bind(this),
			toggleUsers: this.onCallViewToggleUsersButtonClick.bind(this),
			share: this.onCallViewShareButtonClick.bind(this),
			fullscreen: this.onCallViewFullScreenButtonClick.bind(this),
			floorRequest: this.onCallViewFloorRequestButtonClick.bind(this),
			feedback: this.onCallViewFeedbackButtonClick.bind(this),
			callcontrol: this._onCallcontrolButtonClick.bind(this),
			onUserClick: this.onCallUserClick.bind(this),
			copilot: this.onCallCopilotButtonClick.bind(this),
		};

		if(handlers[buttonName])
		{
			handlers[buttonName](event);
		}
		else
		{
			Logger.error('Button handler not found!', buttonName);
		}
	}

	onCallViewHangupButtonClick(e)
	{
		Analytics.getInstance().onDisconnectCall({
			callId: this.currentCall?.uuid,
			callType: Analytics.AnalyticsType.videoconf,
			subSection: Analytics.AnalyticsSubSection.finishButton,
			mediaParams: {
				video: Call.Hardware.isCameraOn,
				audio: !Call.Hardware.isMicrophoneMuted,
			},
		});
		this.stopLocalVideoStream();
		this.endCall();
	}

	_onCallViewHangupOptionsButtonClick()
	{
		if (this.hangupOptionsMenu)
		{
			this.hangupOptionsMenu.destroy();
			return;
		}

		const targetNodeWidth = this.callView.buttons.hangupOptions.elements.root.offsetWidth;

		let menuItems = [
			{
				text: BX.message("CALL_M_BTN_HANGUP_OPTION_FINISH"),
				onclick: () => {
					Analytics.getInstance().onFinishCall({
						callId: this.currentCall?.uuid,
						callType: Analytics.AnalyticsType.videoconf,
						status: Analytics.AnalyticsStatus.finishedForAll,
						chatId: this.currentCall?.associatedEntity.id,
						callUsersCount: this.getCallUsers(true).length,
						callLength: Util.getTimeText(this.currentCall?.startDate),
					});

					this.stopLocalVideoStream();
					this.endCall(true);
				},
			},
			{
				text: BX.message("CALL_M_BTN_HANGUP_OPTION_LEAVE"),
				onclick: () => {
					Analytics.getInstance().onDisconnectCall({
						callId: this.currentCall?.uuid,
						callType: Analytics.AnalyticsType.videoconf,
						subSection: Analytics.AnalyticsSubSection.contextMenu,
						mediaParams: {
							video: Call.Hardware.isCameraOn,
							audio: !Call.Hardware.isMicrophoneMuted,
						},
					});
					this.stopLocalVideoStream();
					this.endCall(false);
				},
			},
		];

		this.hangupOptionsMenu = new BX.PopupMenuWindow({
			className: 'bx-messenger-videocall-hangup-options-container',
			background: '#22272B',
			contentBackground: '#22272B',
			darkMode: true,
			contentBorderRadius: '6px',
			angle: false,
			bindElement: this.callView.buttons.hangupOptions.elements.root,
			targetContainer: this.container,
			offsetTop: -15,
			bindOptions: {position: "top"},
			cacheable: false,
			subMenuOptions: {
				maxWidth: 450
			},
			events: {
				onShow: (event) =>
				{
					const popup = event.getTarget();
					popup.getPopupContainer().style.display = 'block'; // bad hack

					const offsetLeft = (targetNodeWidth / 2) - popup.getPopupContainer().offsetWidth / 2;
					popup.setOffset({offsetLeft: offsetLeft + 40, offsetTop: 0});
					popup.setAngle({offset: popup.getPopupContainer().offsetWidth / 2 - 17});
				},
				onDestroy: () => this.hangupOptionsMenu = null
			},
			items: menuItems,
		});

		this.hangupOptionsMenu.show();
	}

	onCallViewCloseButtonClick(e)
	{
		this.stopLocalVideoStream();
		this.endCall();
	}

	onCallViewToggleMuteButtonClick(event)
	{
		/*if (!Call.Hardware.hasMicrophone() &&  !event.data.muted)
		{
			return;
		}*/

		Analytics.getInstance().onToggleMicrophone({
			muted: event.data.muted,
			callId: this.currentCall ? this.currentCall.uuid : 0,
			callType: Analytics.AnalyticsType.videoconf,
		});

		/*if (this.currentCall && !this.currentCall.microphoneId && !event.data.muted)
		{
			this.currentCall.setMicrophoneId(Call.Hardware.defaultMicrophone);
		}

		Call.Hardware.isMicrophoneMuted = event.data.muted;
		if (!this.currentCall)
		{
			this.template.$emit('setMicState', !event.data.muted);
		}

		if (this.isRecording())
		{
			BXDesktopSystem.CallRecordMute(event.data.muted);
		}

		if (this.currentCall?.userId)
		{
			this.updateCallUser(this.currentCall.userId, {microphoneState: !event.data.muted});
		}*/

		this._onCallViewToggleMuteHandler(event.data);
	}

	_onCallViewToggleMuteHandler(e)
	{
		if (!e.muted && !Hardware?.hasMicrophone())
		{
			return;
		}

		const currentRoom = this.currentCall && this.currentCall.currentRoom && this.currentCall.currentRoom();
		if (currentRoom && currentRoom.speaker != this.userId && !e.muted)
		{
			this.currentCall.requestRoomSpeaker();
			return;
		}

		if (this.currentCall && !this.currentCall.microphoneId && !e.muted)
		{
			this.currentCall.setMicrophoneId(Hardware.defaultMicrophone);
		}

		Hardware.setIsMicrophoneMuted({isMicrophoneMuted: e.muted, calledProgrammatically: !!e.calledProgrammatically});

		if (this.floatingWindow)
		{
			this.floatingWindow.setAudioMuted(e.muted);
		}

		if (this.mutePopup)
		{
			this.mutePopup.close();
		}
		if (!e.muted)
		{
			if (!Util.havePermissionToBroadcast('mic'))
			{
				this.showRiseYouHandToTalkNotification({initiatorName: this.lastCalledChangeSettingsUserName});
			}
			else
			{
				this.allowMutePopup = true;
			}
		}
		if (this.isRecording())
		{
			BXDesktopSystem.CallRecordMute(e.muted);
		}

		if (this.currentCall?.userId)
		{
			this.updateCallUser(this.currentCall.userId, {microphoneState: !e.muted});
		}

		if (!this.currentCall)
		{
			this.template.$emit('setMicState', !e.muted);
		}
	}

	onCallViewToggleScreenSharingButtonClick()
	{
		Analytics.getInstance().onScreenShareBtnClick({
			callId: this.currentCall.uuid,
			callType: Analytics.AnalyticsType.videoconf,
		});

		if (this.getFeatureState('screenSharing') === ConferenceApplication.FeatureState.Limited)
		{
			this.showFeatureLimitSlider('screenSharing');
			return;
		}

		if (this.getFeatureState('screenSharing') === ConferenceApplication.FeatureState.Disabled)
		{
			return;
		}

		if (this.currentCall.isScreenSharingStarted())
		{
			this.currentCall.stopScreenSharing();

			if (this.isRecording())
			{
				BXDesktopSystem.CallRecordStopSharing();
			}

			if (this.floatingScreenShareWindow)
			{
				this.floatingScreenShareWindow.close();
			}

			if (this.webScreenSharePopup)
			{
				this.webScreenSharePopup.close();
			}
		}
		else
		{
			BX.ajax.runAction('call.Call.onShareScreen', { data: { callUuid: this.currentCall.uuid } });
			this.currentCall.startScreenSharing();
			this.togglePictureInPictureCallWindow();
		}
	}

	togglePictureInPictureCallWindow(config = {})
	{
		const isActiveStatePictureInPictureCallWindow = this.currentCall && (this.currentCall.isScreenSharingStarted() || config.isForceOpen) && !config.isForceClose;

		if (!this.callView)
		{
			return;
		}

		this.callView.isActivePiPFromController = isActiveStatePictureInPictureCallWindow;
		this.callView.toggleStatePictureInPictureCallWindow(isActiveStatePictureInPictureCallWindow);

	}

	onCallViewRecordButtonClick(event)
	{
		Analytics.getInstance().onRecordBtnClick({
			callId: this.currentCall.uuid,
			callType: Analytics.AnalyticsType.videoconf,
		});

		if (event.data.recordState === Call.View.RecordState.Started)
		{
			if (this.getFeatureState('record') === ConferenceApplication.FeatureState.Limited)
			{
				this.showFeatureLimitSlider('record');
				return;
			}

			if (this.getFeatureState('record') === ConferenceApplication.FeatureState.Disabled)
			{
				return;
			}

			if (this.canRecord())
			{
				// TODO: create popup menu with choice type of record - im/install/js/im/call/controller.js:1635
				// Call.View.RecordType.Video / Call.View.RecordType.Audio

				this.callView.setButtonActive('record', true);
			}
			else
			{
				if (window.BX.Helper)
				{
					window.BX.Helper.show("redirect=detail&code=22079566");
				}

				return;
			}
		}
		else if (event.data.recordState === Call.View.RecordState.Paused)
		{
			if (this.canRecord())
			{
				BXDesktopSystem.CallRecordPause(true);
			}
		}
		else if (event.data.recordState === Call.View.RecordState.Resumed)
		{
			if (this.canRecord())
			{
				BXDesktopSystem.CallRecordPause(false);
			}
		}
		else if (event.data.recordState === Call.View.RecordState.Stopped)
		{
			this.callView.setButtonActive('record', false);
		}

		this.currentCall.sendRecordState({
			action: event.data.recordState,
			date: new Date()
		});

		this.callRecordState = event.data.recordState;
	}

	_onCallViewToggleVideoButtonClickHandler(e)
	{
		if (!Hardware.initialized)
		{
			return;
		}

		if (e.video && Object.values(Hardware.cameraList).length === 0)
		{
			this.showNotification(Loc.getMessage('IM_CALL_CAMERA_ERROR_FALLBACK_TO_MIC'));

			return;
		}

		Hardware.setIsCameraOn({ isCameraOn: e.video, calledProgrammatically: Boolean(e.calledProgrammatically) });

		if (this.currentCall && !e.video && !this.currentCall.isScreenSharingStarted())
		{
			this.callView.releaseLocalMedia();
		}

		if (this.currentCall && !this.currentCall.cameraId && e.video)
		{
			this.currentCall.setCameraId(Hardware.defaultCamera);
		}

		if (!this.currentCall)
		{
			this.template.$emit('setCameraState', e.video);
		}
	}

	onCallViewToggleVideoButtonClick(event)
	{
		/*if (!Call.Hardware.hasCamera() &&  event.data.video)
		{
			this.showNotification(BX.message('IM_CALL_NO_CAMERA_ERROR'));
			return;
		}*/

		Analytics.getInstance().onToggleCamera({
			video: event.data.video,
			callId: this.currentCall ? this.currentCall.uuid : 0,
			callType: Analytics.AnalyticsType.videoconf,
		});

		this._onCallViewToggleVideoButtonClickHandler(event.data);

		/*Call.Hardware.isCameraOn = event.data.video;
		if (this.currentCall)
		{
			if (!Call.Hardware.initialized)
			{
				return;
			}
			if (event.data.video && Object.values(Call.Hardware.cameraList).length === 0)
			{
				return;
			}
			if(!event.data.video)
			{
				this.callView.releaseLocalMedia();
			}

			if (!this.currentCall.cameraId && event.data.video)
			{
				this.currentCall.setCameraId(Call.Hardware.defaultCamera);
			}
		}
		else
		{
			this.template.$emit('setCameraState', event.data.video);
		}*/
	}

	onCallViewToggleSpeakerButtonClick(event)
	{
		this.callView.muteSpeaker(!event.data.speakerMuted);

		if (event.data.fromHotKey)
		{
			BX.UI.Notification.Center.notify({
				content: BX.message(this.callView.speakerMuted? 'IM_M_CALL_MUTE_SPEAKERS_OFF': 'IM_M_CALL_MUTE_SPEAKERS_ON'),
				position: "top-right",
				autoHideDelay: 3000,
				closeButton: true
			});
		}
	}

	onCallViewShareButtonClick()
	{
		let notifyWidth = 400;
		if (Utils.device.isMobile() && document.body.clientWidth < 400)
		{
			notifyWidth = document.body.clientWidth - 40;
		}

		BX.UI.Notification.Center.notify({
			content: Loc.getMessage('BX_IM_VIDEOCONF_LINK_COPY_DONE'),
			autoHideDelay: 4000,
			width: notifyWidth
		});

		Clipboard.copy(this.getDialogData().public.link);
	}

	onCallViewFullScreenButtonClick()
	{
		this.toggleFullScreen();
	}

	onFloatingScreenShareBackToCallClick()
	{
		DesktopApi.activateWindow();
		DesktopApi.changeTab("im");
		if (this.floatingScreenShareWindow)
		{
			this.floatingScreenShareWindow.hide();
		}
	}

	onFloatingScreenShareStopClick()
	{
		DesktopApi.activateWindow();
		DesktopApi.changeTab("im");
		this.onCallViewToggleScreenSharingButtonClick();
	}

	onFloatingScreenShareChangeScreenClick()
	{
		if (this.currentCall)
		{
			this.currentCall.startScreenSharing(true);
		}
	}

	updateWindowFocusState(isActive)
	{
		if (isActive === this.isWindowFocus)
		{
			return;
		}

		this.isWindowFocus = isActive;

		if (this.callView)
		{
			this.callView.setWindowFocusState(this.isWindowFocus);
		}
	}

	clearPictureInPictureDebounceForOpen()
	{
		if (this.pictureInPictureDebounceForOpen)
		{
			clearTimeout(this.pictureInPictureDebounceForOpen);
			this.pictureInPictureDebounceForOpen = null;
		}
	}

	onInputFileOpenedStateUpdate(isActive)
	{
		if (isActive)
		{
			this.clearPictureInPictureDebounceForOpen();
			this.togglePictureInPictureCallWindow({ isForceClose: true });
			this.isFileChooserActive = true;
		}

		if (!isActive && !this.pictureInPictureDebounceForOpen)
		{
			this.pictureInPictureDebounceForOpen = setTimeout(() => {
				this.togglePictureInPictureCallWindow();
				this.isFileChooserActive = false;
				this.pictureInPictureDebounceForOpen = null;
			}, 1000);
		}
	}

	onDocumentBodyClick()
	{
		const { target } = event;

		if (target.matches('input[type="file"]'))
		{
			this.onInputFileOpenedStateUpdate(true);
		}
	}

	onWindowFocus()
	{
		if (DesktopApi.isDesktop())
		{
			this.onWindowDesktopFocus();
		}

		if (DesktopApi.isDesktop() && this.isFileChooserActive)
		{
			this.onInputFileOpenedStateUpdate(false);
		}

		this.updateWindowFocusState(true);
	}

	onWindowBlur()
	{
		if (DesktopApi.isDesktop())
		{
			this.onWindowDesktopBlur();
		}

		this.updateWindowFocusState(false);
	}

	onWindowDesktopFocus()
	{
		if (this.floatingScreenShareWindow)
		{
			this.floatingScreenShareWindow.hide();
		}
	}

	onWindowDesktopBlur()
	{
		if(this.floatingScreenShareWindow && this.currentCall && this.currentCall.isScreenSharingStarted())
		{
			this.floatingScreenShareWindow.show();
		}
	}

	isFullScreen ()
	{
		if ("webkitFullscreenElement" in document)
		{
			return (!!document.webkitFullscreenElement);
		}
		else if ("fullscreenElement" in document)
		{
			return (!!document.fullscreenElement);
		}
		return false;
	}

	toggleFullScreen ()
	{
		if(this.isFullScreen())
		{
			this.exitFullScreen();
		}
		else
		{
			this.enterFullScreen();
		}
	}

	enterFullScreen ()
	{
		if (!this.callView)
		{
			return;
		}

		const element = this.callView.elements.root;

		try
		{
			const requestFullscreen = element.requestFullscreen
				|| element.webkitRequestFullscreen
				|| element.mozRequestFullScreen
				|| element.msRequestFullscreen
			;

			if (requestFullscreen)
			{
				requestFullscreen.call(element).catch((error) => {
					console.error('Failed to enter fullscreen mode:', error);
				});
			}
			else
			{
				console.warn('Fullscreen API is not supported in this browser');
			}
		}
		catch (e)
		{
			console.error('Error attempting to enable fullscreen mode:', e);
		}
	}

	exitFullScreen()
	{
		try
		{
			const exitFullscreen = document.exitFullscreen
				|| document.mozCancelFullScreen
				|| document.webkitExitFullscreen
				|| document.msExitFullscreen
				|| document.cancelFullScreen
			;

			if (exitFullscreen)
			{
				exitFullscreen.call(document).catch((error) => {
					console.error('Failed to exit fullscreen mode:', error);
				});
			}
			else
			{
				console.warn('Fullscreen API is not fully supported in this browser');
			}
		}
		catch (e)
		{
			console.error('Error attempting to exit fullscreen mode:', e);
		}
	}

	onCallViewShowChatButtonClick()
	{
		Analytics.getInstance().onShowChat({
			callId: this.currentCall?.uuid,
			callType: Analytics.AnalyticsType.videoconf,
		});

		this.toggleChat();
	}

	onCallViewToggleUsersButtonClick()
	{
		this.toggleUserList();
	}

	_onCallcontrolButtonClick(e)
	{
		if (!Util.canControlChangeSettings())
		{
			return;
		}

		if (this.participantsPermissionPopup)
		{
			this.participantsPermissionPopup.close();
			return;
		}

		this.participantsPermissionPopup = new ParticipantsPermissionPopup({
			turnOffAllParticipansStream: (options) => {
				this._onCallViewTurnOffAllParticipansStreamButtonClick(options);

				Analytics.getInstance().onTurnOffAllParticipansStream({
					callId: this._getCallIdentifier(this.currentCall),
					callType: this.getCallType(),
					typeOfStream: options.data?.typeOfStream,
				});
			},
			onPermissionChanged: (options) => {
				this.currentCall.changeSettings(options);

				if (!options.settingEnabled) // send only when it turned off
				{
					Analytics.getInstance().onCallSettingsChanged({
						callId: this._getCallIdentifier(this.currentCall),
						callType: this.getCallType(),
						typeOfSetting: options.typeOfSetting,
						settingEnabled: options.settingEnabled,
					});
				}
			},
			onClose: () => {
				this.participantsPermissionPopup = null;
				this._afterCloseParticipantsPermissionPopup();
			},
			onOpen: () => {
				this._afterOpenParticipantsPermissionPopup();

				Analytics.getInstance().onOpenCallSettings({
					callId: this._getCallIdentifier(this.currentCall),
					callType: this.getCallType(),
				});
			},
		});

		if (this.participantsPermissionPopup)
		{
			this.participantsPermissionPopup.toggle();
		}
	}

	onCallViewFeedbackButtonClick()
	{
		BX.loadExt('ui.feedback.form').then(() => {
			BX.UI.Feedback.Form.open({
				id: `call_feedback_${this.currentCall.uuid}-${this.currentCall.instanceId}-${Math.random()}`,
				forms: [
					{ zones: ['ru', 'by', 'kz'], id: 406, sec: '9lhjhn', lang: 'ru' },
					{ zones: ['de'], id: 754, sec: '6upe49', lang: 'de' },
					{ zones: ['es'], id: 750, sec: 'whk4la', lang: 'es' },
					{ zones: ['com.br'], id: 752, sec: 'is01cs', lang: 'com.br' },
					{ zones: ['en'], id: 748, sec: 'pds0h6', lang: 'en' },
				],
				presets: {
					sender_page: 'call',
					call_type: this.currentCall.provider,
					call_amount: this.currentCall.users.length + 1,
					call_id: `id: ${this.currentCall.uuid}, instanceId: ${this.currentCall.instanceId}`,
					id_of_user: this.currentCall.userId,
					from_domain: location.origin
				},
			});
		})
	}

	onCallUserClick(e)
	{
		Analytics.getInstance().onClickUser({
			callId: this.currentCall.uuid,
			callType: Analytics.AnalyticsType.videoconf,
			layout: Object.keys(Call.View.Layout).find(key => Call.View.Layout[key] === e.layout),
		});
	}

	onCallCopilotButtonClick()
	{
		this.onChangeStateCopilot();

		// todo: code below supports only legacy provider;
		// to support the new provider, logic from the common call controller must be added
		/* if (!Util.isAIServiceEnabled())
		{
			BX.SidePanel.Instance.open(CallAI.serviceEnabled, {
				cacheable: false
			});
			return;
		}

		this.copilotPopup = new Call.CopilotPopup({
			isCopilotActive: this.currentCall.isCopilotActive,
			isCopilotFeaturesEnabled: this.currentCall.isCopilotFeaturesEnabled,
			updateCopilotState: () => {
				this.onChangeStateCopilot();
			},
			onClose: () => {
				this.copilotPopup = null;
			}
		});

		if (this.copilotPopup)
		{
			this.copilotPopup.toggle();
		} */
	}

	onUpdateCallCopilotState({ isTrackRecordOn })
	{
		const updateCopilotActive = this.currentCall.scheme
			? this.currentCall.scheme === Call.CallScheme.classic
			: !CallSettingsManager.jwtCallsEnabled;

		if (updateCopilotActive)
		{
			this.currentCall.isCopilotActive = isTrackRecordOn;
		}
		this.callView.updateCopilotState(this.currentCall.isCopilotActive);
	}

	_onBlockCameraButton()
	{
		if (!this.callView)
		{
			return;
		}

		this.callView.blockSwitchCamera();
	}

	_onUnblockCameraButton()
	{
		if (!this.callView) {
			return;
		}

		this.callView.unblockSwitchCamera();
	}

	_onBlockMicrophoneButton()
	{
		if (!this.callView) {
			return;
		}

		this.callView.blockSwitchMicrophone();
	}

	_onUnblockMicrophoneButton()
	{
		if (!this.callView) {
			return;
		}

		this.callView.unblockSwitchMicrophone();
	}

	onCameraPublishing(e)
	{
		if (e.publishing)
		{
			this.onBlockCameraButton();
		}
		else
		{
			this.onUnblockCameraButton();
		}

		if (this.callView)
		{
			this.callView.updateButtons();
		}
	}

	onMicrophonePublishingd(e)
	{
		if (!this.callView)
		{
			return;
		}

		if (e.publishing)
		{
			this.onBlockMicrophoneButton();
		}
		else
		{
			this.onUnblockMicrophoneButton();
		}

		if (this.callView)
		{
			this.callView.updateButtons();
		}
	}

	onChangeStateCopilot()
	{
		const action = !this.currentCall.isCopilotActive ? 'call.Track.start' : 'call.Track.stop';
		BX.ajax.runAction(action, {
			data: { callId: this.currentCall.id }
		}).then(() => {
			this.onUpdateCallCopilotState({
				isTrackRecordOn: !this.currentCall.isCopilotActive,
			});
		});
	}

	onCallViewFloorRequestButtonClick()
	{
		Analytics.getInstance().onFloorRequest({
			callId: this.currentCall.uuid,
			callType: Analytics.AnalyticsType.videoconf,
		});

		const floorState = this.callView.getUserFloorRequestState(this.callEngine.getCurrentUserId());
		const talkingState = this.callView.getUserTalking(this.callEngine.getCurrentUserId());

		this.callView.setUserFloorRequestState(this.callEngine.getCurrentUserId(), !floorState);

		if (this.currentCall)
		{
			this.currentCall.requestFloor(!floorState);
		}

		clearTimeout(this.callViewFloorRequestTimeout);
		if (talkingState && !floorState)
		{
			this.callViewFloorRequestTimeout = setTimeout(() =>
			{
				if (this.currentCall)
				{
					this.currentCall.requestFloor(false);
				}
			}, 1500);
		}

		if (this.riseYouHandToTalkPopup && !floorState)
		{
			this.riseYouHandToTalkPopup.close();
			this.riseYouHandToTalkPopup = null;
		}
	}

	_onCallViewTurnOffAllParticipansStreamButtonClick(options)
	{
		if (this.currentCall)
		{
			this.currentCall.turnOffAllParticipansStream(options);
		}
	}

	bindCallEvents()
	{
		this.currentCall.addEventListener(Call.Event.onUserInvited, this.onCallUserInvitedHandler);
		this.currentCall.addEventListener(Call.Event.onUserJoined, this.onCallUserJoinedHandler);
		this.currentCall.addEventListener(Call.Event.onDestroy, this.onCallDestroyHandler);
		this.currentCall.addEventListener(Call.Event.onUserStateChanged, this.onCallUserStateChangedHandler);
		this.currentCall.addEventListener(Call.Event.onUserMicrophoneState, this.onCallUserMicrophoneStateHandler);
		this.currentCall.addEventListener(Call.Event.onUserCameraState, this.onCallUserCameraStateHandler);
		this.currentCall.addEventListener(Call.Event.onNeedResetMediaDevicesState, this.onNeedResetMediaDevicesStateHandler);
		this.currentCall.addEventListener(Call.Event.onUserVideoPaused, this.onCallUserVideoPausedHandler);
		this.currentCall.addEventListener(Call.Event.onLocalMediaReceived, this.onCallLocalMediaReceivedHandler);
		this.currentCall.addEventListener(Call.Event.onRemoteMediaReceived, this.onCallRemoteMediaReceivedHandler);
		this.currentCall.addEventListener(Call.Event.onRemoteMediaStopped, this.onCallRemoteMediaStoppedHandler);
		this.currentCall.addEventListener(Call.Event.onUserVoiceStarted, this.onCallUserVoiceStartedHandler);
		this.currentCall.addEventListener(Call.Event.onUserVoiceStopped, this.onCallUserVoiceStoppedHandler);
		this.currentCall.addEventListener(Call.Event.onUserStatsReceived, this.onUserStatsReceivedHandler);
		this.currentCall.addEventListener(Call.Event.onUserScreenState, this.onCallUserScreenStateHandler);
		this.currentCall.addEventListener(Call.Event.onUserRecordState, this.onCallUserRecordStateHandler);
		this.currentCall.addEventListener(Call.Event.onUserFloorRequest, this.onCallUserFloorRequestHandler);
		this.currentCall.addEventListener(Call.Event.onMicrophoneLevel, this.onMicrophoneLevelHandler);
		//this.currentCall.addEventListener(Call.Event.onDeviceListUpdated, this._onCallDeviceListUpdatedHandler);
		this.currentCall.addEventListener(Call.Event.onCallFailure, this.onCallFailureHandler);
		this.currentCall.addEventListener(Call.Event.onJoin, this._onCallJoinHandler);
		this.currentCall.addEventListener(Call.Event.onLeave, this.onCallLeaveHandler);
		this.currentCall.addEventListener(Call.Event.onReconnecting, this.onReconnectingHandler);
		this.currentCall.addEventListener(Call.Event.onReconnected, this.onReconnectedHandler);
		this.currentCall.addEventListener(Call.Event.onReconnectingFailed, this.onReconnectingFailedHandler);
		this.currentCall.addEventListener(Call.Event.onUpdateLastUsedCameraId, this.onUpdateLastUsedCameraIdHandler);
		this.currentCall.addEventListener(Call.Event.onConnectionQualityChanged, this.onCallConnectionQualityChangedHandler);
		this.currentCall.addEventListener(Call.Event.onToggleRemoteParticipantVideo, this.onCallToggleRemoteParticipantVideoHandler);
		this.currentCall.addEventListener(Call.Event.onGetUserMediaEnded, this._onGetUserMediaEndedHandler);
		this.currentCall.addEventListener(Call.Event.onSwitchTrackRecordStatus, this._onSwitchTrackRecordStatusHandler);
		this.currentCall.addEventListener(Call.Event.onCameraPublishing, this.onCameraPublishingHandler);
		this.currentCall.addEventListener(Call.Event.onMicrophonePublishing, this.onMicrophonePublishingdHandler);

		this.currentCall.addEventListener(Call.Event.onAllParticipantsAudioMuted, this._onAllParticipantsAudioMutedHandler);
		this.currentCall.addEventListener(Call.Event.onAllParticipantsVideoMuted, this._onAllParticipantsVideoMutedHandler);
		this.currentCall.addEventListener(Call.Event.onAllParticipantsScreenshareMuted, this._onAllParticipantsScreenshareHandler);
		this.currentCall.addEventListener(Call.Event.onRoomSettingsChanged, this._onRoomSettingsChangedHandler);
		this.currentCall.addEventListener(Call.Event.onUserPermissionsChanged, this._onUserPermissionsChangedHandler);
		this.currentCall.addEventListener(Call.Event.onUserRoleChanged, this._onUserRoleChangedHandler);
		this.currentCall.addEventListener(Call.Event.onYouMuteAllParticipants, this._onYouMuteAllParticipantsHandler);
		this.currentCall.addEventListener(Call.Event.onParticipantMuted, this._onParticipantMutedHandler);
	}

	removeCallEvents()
	{
		this.currentCall.removeEventListener(Call.Event.onUserInvited, this.onCallUserInvitedHandler);
		this.currentCall.removeEventListener(Call.Event.onUserJoined, this.onCallUserJoinedHandler);
		this.currentCall.removeEventListener(Call.Event.onDestroy, this.onCallDestroyHandler);
		this.currentCall.removeEventListener(Call.Event.onUserStateChanged, this.onCallUserStateChangedHandler);
		this.currentCall.removeEventListener(Call.Event.onUserMicrophoneState, this.onCallUserMicrophoneStateHandler);
		this.currentCall.removeEventListener(Call.Event.onUserCameraState, this.onCallUserCameraStateHandler);
		this.currentCall.removeEventListener(Call.Event.onNeedResetMediaDevicesState, this.onNeedResetMediaDevicesStateHandler);
		this.currentCall.removeEventListener(Call.Event.onUserVideoPaused, this.onCallUserVideoPausedHandler);
		this.currentCall.removeEventListener(Call.Event.onLocalMediaReceived, this.onCallLocalMediaReceivedHandler);
		this.currentCall.removeEventListener(Call.Event.onRemoteMediaReceived, this.onCallRemoteMediaReceivedHandler);
		this.currentCall.removeEventListener(Call.Event.onRemoteMediaStopped, this.onCallRemoteMediaStoppedHandler);
		this.currentCall.removeEventListener(Call.Event.onUserVoiceStarted, this.onCallUserVoiceStartedHandler);
		this.currentCall.removeEventListener(Call.Event.onUserVoiceStopped, this.onCallUserVoiceStoppedHandler);
		this.currentCall.removeEventListener(Call.Event.onUserStatsReceived, this.onUserStatsReceivedHandler);
		this.currentCall.removeEventListener(Call.Event.onUserScreenState, this.onCallUserScreenStateHandler);
		this.currentCall.removeEventListener(Call.Event.onUserRecordState, this.onCallUserRecordStateHandler);
		this.currentCall.removeEventListener(Call.Event.onUserFloorRequest, this.onCallUserFloorRequestHandler);
		this.currentCall.removeEventListener(Call.Event.onMicrophoneLevel, this.onMicrophoneLevelHandler);
		this.currentCall.removeEventListener(Call.Event.onConnectionQualityChanged, this.onCallConnectionQualityChangedHandler);
		this.currentCall.removeEventListener(Call.Event.onToggleRemoteParticipantVideo, this.onCallToggleRemoteParticipantVideoHandler);
		//this.currentCall.removeEventListener(Call.Event.onDeviceListUpdated, this._onCallDeviceListUpdatedHandler);
		this.currentCall.removeEventListener(Call.Event.onCallFailure, this.onCallFailureHandler);
		this.currentCall.removeEventListener(Call.Event.onLeave, this.onCallLeaveHandler);
		this.currentCall.removeEventListener(Call.Event.onReconnecting, this.onReconnectingHandler);
		this.currentCall.removeEventListener(Call.Event.onReconnected, this.onReconnectedHandler);
		this.currentCall.removeEventListener(Call.Event.onReconnectingFailed, this.onReconnectingFailedHandler);
		this.currentCall.removeEventListener(Call.Event.onUpdateLastUsedCameraId, this.onUpdateLastUsedCameraIdHandler);
		this.currentCall.removeEventListener(Call.Event.onGetUserMediaEnded, this._onGetUserMediaEndedHandler);
		this.currentCall.removeEventListener(Call.Event.onSwitchTrackRecordStatus, this._onSwitchTrackRecordStatusHandler);
		this.currentCall.removeEventListener(Call.Event.onCameraPublishing, this.onCameraPublishingHandler);
		this.currentCall.removeEventListener(Call.Event.onMicrophonePublishing, this.onMicrophonePublishingdHandler);

		this.currentCall.removeEventListener(Call.Event.onAllParticipantsAudioMuted, this._onAllParticipantsAudioMutedHandler);
		this.currentCall.removeEventListener(Call.Event.onAllParticipantsVideoMuted, this._onAllParticipantsVideoMutedHandler);
		this.currentCall.removeEventListener(Call.Event.onAllParticipantsScreenshareMuted, this._onAllParticipantsScreenshareHandler);
		this.currentCall.removeEventListener(Call.Event.onRoomSettingsChanged, this._onRoomSettingsChangedHandler);
		this.currentCall.removeEventListener(Call.Event.onUserPermissionsChanged, this._onUserPermissionsChangedHandler);
		this.currentCall.removeEventListener(Call.Event.onUserRoleChanged, this._onUserRoleChangedHandler);
		this.currentCall.removeEventListener(Call.Event.onYouMuteAllParticipants, this._onYouMuteAllParticipantsHandler);
		this.currentCall.removeEventListener(Call.Event.onParticipantMuted, this._onParticipantMutedHandler);
	}

	removeAdditionalEvents()
	{
		window.removeEventListener('focus', () => {
			this.onWindowFocus();
		});

		window.removeEventListener('blur', () => {
			this.onWindowBlur();
		});

		document.body.removeEventListener('click',(evt) =>
		{
			this.onDocumentBodyClick(evt);
		});
	}

	onCallUserInvited(e)
	{
		this.callView.addUser(e.userId);

		Util.getUsers(this.currentCall.id, [e.userId]).then(userData => {
			this.controller.getStore().dispatch('users/set', Object.values(userData));
			this.controller.getStore().dispatch('conference/setUsers', {users: Object.keys(userData)});
			this.callView.updateUserData(userData)
		});
	}

	onCallUserJoined(e)
	{
		if (this.callView)
		{
			this.callView.updateUserData(e.userData);
			this.callView.addUser(e.userId, Call.UserState.Connected);
		}
	}

	onCallUserStateChanged(e)
	{
		if (e.state !== Call.UserState.Connected && this.loopTimers[e.userId] === undefined)
		{
			this.loopConnectionQuality(e.userId, 1);
		}

		if (e.state === Call.UserState.Connected)
		{
			this.clearConnectionQualityTimer(e.userId);
			this.callView.setUserConnectionQuality(e.userId, 5);

			if (false && CallAI.serviceEnabled)
			{
				this.callView.unblockButtons(['copilot']);
			}
		}

		if (
			e.state === Call.UserState.Ready
			&& e.previousState === Call.UserState.Connected
			&& !Object.keys(this.currentCall).includes(e.userId)
		)
		{
			e.state = Call.UserState.Idle;
		}

		if (e.state === Call.UserState.Idle && e.userId === this.callRecordInitiatorId)
		{
			this.callRecordState = Call.View.RecordState.Stopped;
			this.callView.setRecordState(this.callView.getDefaultRecordState());
			this.callView.setButtonActive('record', false);
		}

		this.callView.setUserState(e.userId, e.state);
		this.updateCallUser(e.userId,{state: e.state});

		if (!this.isRecording())
		{
			this.callView.getConnectedUserCount(false)
				? this.callView.unblockButtons(['record'])
				: this.callView.blockButtons(['record'])
			;
		}
		/*if (e.direction)
		{
			this.callView.setUserDirection(e.userId, e.direction);
		}*/
	}

	onCallUserMicrophoneState(e)
	{
		if (e.userId == this.currentCall.userId)
		{
			Call.Hardware.isMicrophoneMuted = !e.microphoneState;
		}
		else
		{
			this.callView.setUserMicrophoneState(e.userId, e.microphoneState);
			this.updateCallUser(e.userId, {microphoneState: e.microphoneState});
		}
	}

	onCallUserCameraState(e)
	{
		this.callView.setUserCameraState(e.userId, e.cameraState);
		this.updateCallUser(e.userId, {cameraState: e.cameraState});
	}

	onNeedResetMediaDevicesState(e)
	{
		Call.Hardware.isMicrophoneMuted = true;
		Call.Hardware.isCameraOn = false;
	}

	onCallUserVideoPaused(e)
	{
		this.callView.setUserVideoPaused(e.userId, e.videoPaused);
	}

	onCallLocalMediaReceived(e)
	{
		console.log("Received local media stream " + e);
		if (this.callView)
		{
			const flipVideo = (e.tag == "main" || e.mediaRenderer) ? Call.Hardware.enableMirroring : false;

			this.callView.setLocalStream(e);
			this.callView.flipLocalVideo(flipVideo);

			this.callView.setButtonActive("screen", this.currentCall.isScreenSharingStarted());
			if (this.currentCall.isScreenSharingStarted())
			{
				this.screenShareStartTime = new Date();
				Analytics.getInstance().onScreenShareStarted({
					callId: this.currentCall.uuid,
					callType: Analytics.AnalyticsType.videoconf,
				});

				this.togglePictureInPictureCallWindow();

				if (!DesktopApi.isDesktop())
				{
					this.showWebScreenSharePopup();
				}

				this.callView.updateButtons();
			}
			else
			{
				Analytics.getInstance().onScreenShareStopped({
					callId: this.currentCall.uuid,
					callType: Analytics.AnalyticsType.videoconf,
					status: Analytics.AnalyticsStatus.success,
					screenShareLength: Util.getTimeText(this.screenShareStartTime),
				});
				this.screenShareStartTime = null;

				this.togglePictureInPictureCallWindow();

				if (this.floatingScreenShareWindow)
				{
					this.floatingScreenShareWindow.close();
				}

				if (this.webScreenSharePopup)
				{
					this.webScreenSharePopup.close();
				}
			}

			if(!this.currentCall.callFromMobile && !this.isViewerMode())
			{
				this.checkAvailableCamera();
				this.checkAvailableMicrophone();
			}
		}

		if (this.currentCall && Call.Hardware.isCameraOn && e.tag === 'main' && e.stream.getVideoTracks().length === 0)
		{
			Call.Hardware.isCameraOn = false;
		}
	}

	onCallRemoteMediaReceived(e)
	{
		const getStreamType = (stream) =>
		{
			if (stream?.getVideoTracks()?.length)
			{
				return 'video';
			}

			if (stream?.getAudioTracks()?.length)
			{
				return 'audio';
			}

			return null;
		}

		if (this.callView)
		{
			if ('track' in e)
			{
				this.callView.setUserMedia(e.userId, e.kind, e.track)
			}
			if ('mediaRenderer' in e && e.mediaRenderer.kind === 'audio' && getStreamType(e.mediaRenderer.stream) === 'audio')
			{
				this.callView.setUserMedia(e.userId, 'audio', e.mediaRenderer.stream.getAudioTracks()[0]);
			}
			if ('mediaRenderer' in e && e.mediaRenderer.kind === 'sharing' && getStreamType(e.mediaRenderer.stream) === 'audio')
			{
				this.callView.setUserMedia(e.userId, 'sharingAudio', e.mediaRenderer.stream.getAudioTracks()[0]);
			}
			if ('mediaRenderer' in e && (e.mediaRenderer.kind === 'video' || e.mediaRenderer.kind === 'sharing') && getStreamType(e.mediaRenderer.stream) === 'video')
			{
				this.callView.setVideoRenderer(e.userId, e.mediaRenderer);
			}
		}
	}

	onCallRemoteMediaStopped(e)
	{
		if (this.callView)
		{
			if ('mediaRenderer' in e)
			{
				if (e.kind === 'video' || e.kind === 'sharing')
				{
					e.mediaRenderer.stream = null;
					this.callView.setVideoRenderer(e.userId, e.mediaRenderer);
				}
			}
			else
			{
				this.callView.setUserMedia(e.userId, e.kind, null);
			}
		}
	}

	isLegacyCall(provider, scheme = null): boolean
	{
		if (scheme)
		{
			return scheme === Call.CallScheme.classic;
		}

		const isLegacyPlainCall = provider === Call.Provider.Plain && !CallSettingsManager.isJwtInPlainCallsEnabled();
		const isLegacyBitrixCall = provider === Call.Provider.Bitrix && !CallSettingsManager.jwtCallsEnabled;

		return isLegacyPlainCall || isLegacyBitrixCall;
	}

	_getCallIdentifier(call)
	{
		if (!call)
		{
			return null;
		}

		return this.isLegacyCall(call.provider, call.scheme) ? call.id : call.uuid;
	}

	getCallType()
	{
		return Analytics.AnalyticsType.videoconf;
	}

	loopConnectionQuality(userId, quality, timeout = 200)
	{
		if (this.callView)
		{
			this.loopTimers[userId] = setTimeout(() => {
				this.callView.setUserConnectionQuality(userId, quality);
				const newQuality = quality >= 4 ? 1 : quality + 1;
				this.loopConnectionQuality(userId, newQuality, timeout);
			}, timeout);
		}
	}

	clearConnectionQualityTimer(userId)
	{
		if (this.loopTimers[userId] !== undefined)
		{
			clearTimeout(this.loopTimers[userId]);
			delete this.loopTimers[userId];
		}
	}

	onCallConnectionQualityChanged(e)
	{
		this.clearConnectionQualityTimer(e.userId);

		this.callView.setUserConnectionQuality(e.userId, e.score);
	}

	onGetUserMediaEnded()
	{
		this.updateMediaDevices();
	}

	onCallToggleRemoteParticipantVideo(e)
	{
		if (this.toogleParticipantsVideoBaloon)
		{
			if (e.isVideoShown)
			{
				this.toogleParticipantsVideoBaloon.close();
			}

			return;
		}

		if (!e.isVideoShown)
		{
			this.toogleParticipantsVideoBaloon = BX.UI.Notification.Center.notify({
				content: Text.encode(BX.message('IM_M_CALL_REMOTE_PARTICIPANTS_VIDEO_MUTED')),
				autoHide: false,
				position: "top-right",
				closeButton: false,
			})
		}
	}

	onCallUserVoiceStarted(e)
	{
		if (e.local)
		{
			if (this.currentCall.muted && this.allowMutePopup)
			{
				this.showMicMutedNotification();
			}
			return;
		}

		this.callView.setUserTalking(e.userId, true);

		if (e.userId == this.callView.localUser.id)
		{
			this.callView.setUserFloorRequestState(e.userId, false);
		}

		this.updateCallUser(e.userId, {talking: true, floorRequestState: false});
	}

	onCallUserVoiceStopped(e)
	{
		this.callView.setUserTalking(e.userId, false);
		this.updateCallUser(e.userId, {talking: false});
	}

	_onBlockUnblockCamMicButtons()
	{
		if (Util.havePermissionToBroadcast('cam'))
		{
			this._onUnblockCameraButton();
		}
		else
		{
			this._onBlockCameraButton();
		}

		if (Util.havePermissionToBroadcast('mic'))
		{
			this._onUnblockMicrophoneButton();
		}
		else
		{
			this._onBlockMicrophoneButton();
		}
	}

	_onAllParticipantsAudioMuted(e)
	{
		const userModel = this.callView.userRegistry.get(e.userId);

		let content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-mic-muted"></div>'
			+ (Text.encode(Util.getCustomMessage("CALL_USER_TURNED_OFF_MIC_FOR_ALL_MSGVER_1", {
				gender: (userModel.data.gender? userModel.data.gender.toUpperCase() : 'M'),
				name: userModel.data.name
			})));

		if (content && (!e.reason || e.reason !== 'settings'))
		{
			this.createCallControlNotify({content: content, isAllow: false});
		}

		if (Util.isRegularUser(Util.getCurrentUserRole()))
		{
			this._onCallViewToggleMuteHandler({muted: true, calledProgrammatically: true});
		}
	}

	_onAllParticipantsVideoMuted(e)
	{

		const userModel = this.callView.userRegistry.get(e.userId);

		let content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-cam-muted"></div>'
			+ (Text.encode(Util.getCustomMessage("CALL_USER_TURNED_OFF_CAM_FOR_ALL_MSGVER_1", {
				gender: (userModel.data.gender? userModel.data.gender.toUpperCase() : 'M'),
				name: userModel.data.name
			})));

		if (content && (!e.reason || e.reason !== 'settings'))
		{
			this.createCallControlNotify({content: content, isAllow: false});
		}

		if (Util.isRegularUser(Util.getCurrentUserRole()))
		{
			this._onCallViewToggleVideoButtonClickHandler({video: false, calledProgrammatically: true});
		}
	}

	_onAllParticipantsScreenshareMuted(e)
	{
		const userModel = this.callView.userRegistry.get(e.userId);

		let content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-cam-muted"></div>'
			+ (Text.encode(Util.getCustomMessage("CALL_USER_TURNED_OFF_SCREENSHARE_FOR_ALL_MSGVER_1", {
				gender: (userModel.data.gender? userModel.data.gender.toUpperCase() : 'M'),
				name: userModel.data.name
			})));

		if (content && (!e.reason || e.reason !== 'settings'))
		{
			this.createCallControlNotify({content: content, isAllow: false});
		}

		if (this.currentCall.isScreenSharingStarted() && Util.isRegularUser(Util.getCurrentUserRole()))
		{
			this.onCallViewToggleScreenSharingButtonClick();
		}
	}

	_onParticipantMuted(e)
	{
		if (e.data?.track.muted) // tbh always should be in "true"..
		{
			let contentIcon = 'mic';
			let contentPhrase = '';
			const initiatorUserModel = this.callView.userRegistry.get(e.data.fromUserId);
			const targetUserModel = this.callView.userRegistry.get(e.data.toUserId);
			const initiatorGender = (initiatorUserModel.data.gender ? initiatorUserModel.data.gender.toUpperCase() : 'M');

			if (e.data.toUserId == this.callEngine.getCurrentUserId())
			{
				if (e.data.track.type === 0)
				{
					contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_YOUR_MIC_MSGVER_1' + '_' + initiatorGender;
					this._onCallViewToggleMuteHandler({muted: true, calledProgrammatically: true});
				}
				else if (e.data.track.type === 1)
				{
					contentIcon = 'cam';
					contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_YOUR_CAM_MSGVER_1' + '_' + initiatorGender;
					this._onCallViewToggleVideoButtonClickHandler({video: false, calledProgrammatically: true});
				}
				else if (e.data.track.type === 2)
				{
					contentIcon = 'screenshare';
					contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_YOUR_SCREENSHARE' + '_' + initiatorGender;
					if (this.currentCall.isScreenSharingStarted())
					{
						this.onCallViewToggleScreenSharingButtonClick();
					}
				}
			}
			else
			{
				if (e.data.fromUserId == this.callEngine.getCurrentUserId())
				{
					if (e.data.track.type === 0)
					{
						contentPhrase = 'CALL_CONTROL_YOU_TURNED_OFF_USER_MIC';
					}
					else if (e.data.track.type === 1)
					{
						contentIcon = 'cam';
						contentPhrase = 'CALL_CONTROL_YOU_TURNED_OFF_USER_CAM';
					}
					else if (e.data.track.type === 2)
					{
						contentIcon = 'screenshare';
						contentPhrase = 'CALL_CONTROL_YOU_TURNED_OFF_USER_SCREENSHARE';
					}
				}
				else
				{
					if (e.data.track.type === 0)
					{
						contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_USER_MIC_MSGVER_1' + '_' + initiatorGender;
					}
					else if (e.data.track.type === 1)
					{
						contentIcon = 'cam';
						contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_USER_CAM_MSGVER_1' + '_' + initiatorGender;
					}
					else if (e.data.track.type === 2)
					{
						contentIcon = 'screenshare';
						contentPhrase = 'CALL_CONTROL_MODERATOR_TURNED_OFF_USER_SCREENSHARE' + '_' + initiatorGender;
					}
				}
			}

			let content =
				'<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-'+contentIcon+'-muted"></div>'
				+ (Text.encode(Util.getCustomMessage(contentPhrase, {
					gender: initiatorGender,
					initiator_name: initiatorUserModel.data.name,
					target_name: targetUserModel.data.name,
				})));

			this.createCallControlNotify({content: content, isAllow: false});
		}
	}

	_onYouMuteAllParticipants(e)
	{
		let typesOfMute = {0: 'mic', 1: 'cam', 2: 'screenshare'};
		let typesOfMuteMessage = {0: 'CALL_YOU_TURNED_OFF_MIC_FOR_ALL_MSGVER_1', 1: 'CALL_YOU_TURNED_OFF_CAM_FOR_ALL_MSGVER_1', 2: 'CALL_YOU_TURNED_OFF_SCREENSHARE_FOR_ALL_MSGVER_1'};

		let content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-'+(typesOfMute[e.data.track.type])+'-muted"></div>'
			+ (BX.message[(typesOfMuteMessage[e.data.track.type])]);

		this.createCallControlNotify({content: content, isAllow: false});
	}

	_onUserPermissionsChanged(e)
	{
		const initiatorUserModel = this.callView.userRegistry.get(e.data.fromUserId);
		const initiatorGender = (initiatorUserModel.data.gender ? initiatorUserModel.data.gender.toUpperCase() : 'M');

		let contentPhrase = 'CALL_ADMIN_ALLOWED_TURN_ON_ALL_FOR_YOU_BY_HANDRAISE_' + initiatorGender;

		if (!e.data.allow)
		{
			contentPhrase = 'CALL_ADMIN_NOT_ALLOWED_TURN_ON_ALL_FOR_YOU_BY_HANDRAISE_' + initiatorGender;
			const floorState = this.callView.getUserFloorRequestState(this.callEngine.getCurrentUserId());

			if (floorState)
			{
				this.onCallViewFloorRequestButtonClick();
			}
		}

		const content = Text.encode(Util.getCustomMessage(contentPhrase, {
			gender: initiatorGender,
			initiator_name: initiatorUserModel.data.name,
		}));

		if (content)
		{
			this.createCallControlNotify({content: content, isAllow: e.data.allow});
		}

		if (this.callView)
		{
			this.callView.setUserPermissionToSpeakState(e.data.toUserId, e.data.allow);
		}

		this.callView.updateButtons();
		this._onBlockUnblockCamMicButtons();
	}

	_onUserRoleChanged(e)
	{
		if (e.data.toUserId == this.callEngine.getCurrentUserId())
		{
			const content = BX.message('CALL_YOU_HAVE_BEEN_APPOINTED_AS_ADMIN');

			if (content)
			{
				this.promotedToAdminTimeout = setTimeout(
					() => this.createCallControlNotify({content: content, isAllow: true}),
					this.promotedToAdminTimeoutValue
				);
			}

			if (this.riseYouHandToTalkPopup)
			{
				this.riseYouHandToTalkPopup.close();
				this.riseYouHandToTalkPopup = null;
			}

			if (this.callView)
			{
				this.callView.updateButtons();
				this.callView.updateFloorRequestNotification();
			}

			this._onBlockUnblockCamMicButtons();
		}
	}

	_onRoomSettingsChanged(e)
	{
		let typesOfMute = {'audio': 'mic', 'video': 'cam', 'screen_share': 'screenshare'};

		let content = '';
		let isAllow = false;

		const initiatorUserModel = this.callView.userRegistry.get(e.data.fromUserId);
		const initiatorGender = (initiatorUserModel.data.gender ? initiatorUserModel.data.gender.toUpperCase() : 'M');

		if (e.data.eft === true)
		{
			if (e.data.fromUserId == this.currentCall.userId)
			{
				const typesOfMuteMessage =
					{
						'audio': 'CALL_YOU_PROHIBITED_MIC_FOR_ALL_BY_SETTINGS',
						'video': 'CALL_YOU_PROHIBITED_CAM_FOR_ALL_BY_SETTINGS',
						'screen_share': 'CALL_YOU_PROHIBITED_SCREENSHARE_FOR_ALL_BY_SETTINGS',
					};

				content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-'+(typesOfMute[e.data.act])+'-muted"></div>'
					+ (BX.message[(typesOfMuteMessage[e.data.act])]);

			}
			else
			{
				const typesOfMuteMessage =
					{
						'audio': 'CALL_ADMIN_PROHIBITED_MIC_FOR_ALL_BY_SETTINGS',
						'video': 'CALL_ADMIN_PROHIBITED_CAM_FOR_ALL_BY_SETTINGS',
						'screen_share': 'CALL_ADMIN_PROHIBITED_SCREENSHARE_FOR_ALL_BY_SETTINGS',
					};

				let contentPhrase = typesOfMuteMessage[e.data.act] + '_' + initiatorGender;

				content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-'+(typesOfMute[e.data.act])+'-muted"></div>'
					+ (Text.encode(Util.getCustomMessage(contentPhrase, {
						gender: initiatorGender,
						initiator_name: initiatorUserModel.data.name,
					})));
			}
		}
		else
		{
			isAllow = true;
			if (e.data.fromUserId == this.currentCall.userId)
			{
				if (this.riseYouHandToTalkPopup)
				{
					this.riseYouHandToTalkPopup.close();
					this.riseYouHandToTalkPopup = null;
				}
				const typesOfMuteMessage =
					{
						'audio': 'CALL_YOU_ALLOWED_MIC_FOR_ALL_BY_SETTINGS',
						'video': 'CALL_YOU_ALLOWED_CAM_FOR_ALL_BY_SETTINGS',
						'screen_share': 'CALL_YOU_ALLOWED_SCREENSHARE_FOR_ALL_BY_SETTINGS',
					};

				content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-'+(typesOfMute[e.data.act])+'-unmuted"></div>'
					+ (BX.message[(typesOfMuteMessage[e.data.act])]);
			}
			else
			{
				const typesOfMuteMessage =
					{
						'audio': 'CALL_ADMIN_ALLOWED_MIC_FOR_ALL_BY_SETTINGS',
						'video': 'CALL_ADMIN_ALLOWED_CAM_FOR_ALL_BY_SETTINGS',
						'screen_share': 'CALL_ADMIN_ALLOWED_SCREENSHARE_FOR_ALL_BY_SETTINGS',
					};

				let contentPhrase = typesOfMuteMessage[e.data.act] + '_' + initiatorGender;

				content = '<div class = "bx-call-view-participants-control-stream-notify-icon bx-call-view-'+(typesOfMute[e.data.act])+'-unmuted"></div>'
					+ (Text.encode(Util.getCustomMessage(contentPhrase, {
						gender: initiatorGender,
						initiator_name: initiatorUserModel.data.name,
					})));

				if (this.riseYouHandToTalkPopup && e.data.act === 'audio')
				{
					this.riseYouHandToTalkPopup.close();
					this.riseYouHandToTalkPopup = null;
				}

			}
		}

		if (this.callView && !isAllow)
		{
			this.callView.setAllUserPermissionToSpeakState(false);
		}

		if (content)
		{
			this.createCallControlNotify({content: content, isAllow: isAllow});
		}

		if (this.participantsPermissionPopup)
		{
			this.participantsPermissionPopup.updateStatePermissions();
		}

		if (e.data.eft === true && e.data.act === 'audio' && !Util.havePermissionToBroadcast('mic'))
		{
			this.lastCalledChangeSettingsUserName = initiatorUserModel.data.name;
			this.showRiseYouHandToTalkNotification({initiatorName: this.lastCalledChangeSettingsUserName});
		}

		this.callView.updateButtons();
		this._onBlockUnblockCamMicButtons();
	}

	showRiseYouHandToTalkNotification(params)
	{
		if (!this.callView)
		{
			return;
		}

		if (this.riseYouHandToTalkPopup)
		{
			return;
		}

		if (this.mutePopup)
		{
			this.mutePopup.close();
			this.mutePopup = null;
		}

		this.riseYouHandToTalkPopup = new Call.Hint({
			callFolded: false,
			bindElement: this.callView.buttons.microphone.elements.icon,
			targetContainer: this.callView.container,
			icon: 'raise-hand',
			showAngle: true,
			initiatorName: params.initiatorName,
			customClassName: 'bx-call-view-popup-call-hint-rise-hand-to-talk',
			autoCloseDelay: (30 * 60 * 1000), // show it 30 minutes
			customRender: function(){
				let handRaiseContentElement = Dom.create("div", {
					props: {className: "bx-call-view-popup-call-hint-rise-block"},
					children: [
						Loc.getMessage('CALL_ADMIN_PROHIBITED_TURN_ON_PARTICIPANTS_MICROPHONES_HINT', {
							'#INITIATOR_NAME#': this.initiatorName,
							'[hint-label]': `<div class="bx-call-view-popup-call-hint-text">`,
							'[/hint-label]': '</div>',
							'#RISE_HAND_ICON#': `<div class="bx-call-view-popup-call-hint-hand-raise-icon"></div>`,
							'[label-or]': `<div class="bx-call-view-popup-call-hint-hand-raise-or-label">`,
							'[/label-or]': '</div>',
							'#REQUEST_NOW_BUTTON#': `<div class = "bx-call-view-popup-call-hint-button-placeholder"></div>`,
						}),
					],
				});

				let buttonPlaceholder = handRaiseContentElement.getElementsByClassName('bx-call-view-popup-call-hint-button-placeholder')[0];

				buttonPlaceholder.replaceWith(this.createAskSpeakButton().render());

				return handRaiseContentElement;
			},
			buttons: [],
			onClose: () =>
			{
				this.riseYouHandToTalkPopup.close();
				this.riseYouHandToTalkPopup = null;
			},
			onAskSpeakButtonClicked: () =>
			{
				this.onCallViewFloorRequestButtonClick();

				if (this.riseYouHandToTalkPopup)
				{
					this.riseYouHandToTalkPopup.close();
					this.riseYouHandToTalkPopup = null;
				}
			},
		});
		this.riseYouHandToTalkPopup.show();
	}

	_afterOpenParticipantsPermissionPopup()
	{
		if (this.participantsPermissionPopup)
		{
			let balloons = BX.UI.Notification.Center.balloons;

			for (let baloonId in balloons)
			{
				balloons[baloonId].container?.classList.add(BALLOON_OFFSET_CLASS_NAME);
				balloons[baloonId].offsetClassNameSetted = true;
			}
		}
	}

	_afterCloseParticipantsPermissionPopup()
	{
		let balloons = BX.UI.Notification.Center.balloons;

		for (let baloonId in balloons)
		{
			balloons[baloonId].container?.classList.remove(BALLOON_OFFSET_CLASS_NAME);
			balloons[baloonId].offsetClassNameSetted = false;
		}
	}

	createCallControlNotify(_p)
	{
		if (!this.callView)
		{
			return;
		}

		let balloonClassName = 'ui-notification-balloon-content bx-call-control-notification ';

		if (_p.isAllow === false)
		{
			balloonClassName += 'bx-call-control-notification-disallow ';
		}
		else
		{
			balloonClassName += 'bx-call-control-notification-allow ';
		}

		BX.UI.Notification.Center.notify({
			content: _p.content,
			position: "top-right",
			autoHideDelay: 8000,
			category: _p.category || '',
			closeButton: true,
			render: function() {

				const actions = this.getActions().map(action => action.getContainer());

				return BX.create("div", {
					props: {
						className: balloonClassName,
					},
					children: [
						BX.create("div", {
							props: {
								className: "ui-notification-balloon-message",
							},
							html: this.getContent(),
						}),
						BX.create("div", {
							props: {
								className: "ui-notification-balloon-actions"
							},
							children: actions
						}),
						this.isCloseButtonVisible() ?  this.getCloseButton(): null
					]
				});
			},
		});

		this._afterOpenParticipantsPermissionPopup();
	}

	onUserStatsReceived(e)
	{
		if (this.callView)
		{
			this.callView.setUserStats(e.userId, e.report);
		}
	}

	onCallUserScreenState(e)
	{
		if(this.callView)
		{
			this.callView.setUserScreenState(e.userId, e.screenState);
		}
		this.updateCallUser(e.userId, {screenState: e.screenState});
	}

	onCallUserRecordState(event)
	{
		this.callRecordState = event.recordState.state;
		this.callView.setRecordState(event.recordState);
		this.callRecordInitiatorId = event.userId;

		if (!this.canRecord() || event.userId != this.controller.getUserId())
		{
			return true;
		}

		if (event.recordState.state === Call.View.RecordState.Stopped)
		{
			this.callRecordInitiatorId = null;
		}
		else
		{
			this.callRecordInfo = event.recordState;
		}

		if (
			event.recordState.state === Call.View.RecordState.Started
			&& event.recordState.userId == this.controller.getUserId()
		)
		{
			const windowId = window.bxdWindowId || window.document.title;
			let fileName = BX.message('IM_CALL_RECORD_NAME');
			let dialogId = this.currentCall.associatedEntity.id;
			let dialogName = this.currentCall.associatedEntity.name;
			let callId = this.currentCall.uuid;
			let callDate = BX.Main.Date.format(this.params.formatRecordDate || 'd.m.Y');

			if (fileName)
			{
				fileName = fileName
					.replace('#CHAT_TITLE#', dialogName)
					.replace('#CALL_ID#', callId)
					.replace('#DATE#', callDate)
				;
			}
			else
			{
				fileName = `call_record_${callId}`;
			}

			this.callEngine.getRestClient().callMethod("call.Call.onStartRecord", {callUuid: this.currentCall.uuid});

			Analytics.getInstance().onRecordStart({
				callId: this.currentCall.uuid,
				callType: Analytics.AnalyticsType.videoconf,
			});

			BXDesktopSystem.CallRecordStart({
				windowId,
				fileName,
				callId, // now not used
				callDate,
				dialogId,
				dialogName,
				muted: Call.Hardware.isMicrophoneMuted,
				cropTop: 72,
				cropBottom: 90,
				shareMethod: 'im.disk.record.share',
				callType: Analytics.AnalyticsType.videoconf,
			});
		}
		else if (event.recordState.state === Call.View.RecordState.Stopped)
		{
			Analytics.getInstance().onRecordStop({
				callId: this.currentCall.uuid,
				callType: Analytics.AnalyticsType.videoconf,
				subSection: Analytics.AnalyticsSubSection.window,
				element: Analytics.AnalyticsElement.recordButton,
				recordTime: Util.getRecordTimeText(this.callRecordInfo),
			});
			this.callRecordInfo = null;

			BXDesktopSystem.CallRecordStop();
		}

		return true;
	}

	onCallUserFloorRequest(e)
	{
		this.callView.setUserFloorRequestState(e.userId, e.requestActive);
		this.updateCallUser(e.userId, {floorRequestState: e.requestActive});
	}

	onMicrophoneLevel(e)
	{
		this.callView.setMicrophoneLevel(e.level);
	}

	onCallJoin(e)
	{
		if (!e.local)
		{
			return;
		}

		if (!this.isViewerMode())
		{
			this.callView.unblockButtons(['floorRequest', 'screen']);
			this.checkAvailableCamera();
			this.checkAvailableMicrophone();
		}

		if (this.callView.getConnectedUserCount(false))
		{
			this.callView.unblockButtons(['record']);
		}

		this.callView.setUiState(Call.View.UiState.Connected);
	}

	onCallFailure(e)
	{
		this.setConferenceHasErrorInCall(true);
		const errorCode = e.code || e.name || e.error;

		let errorMessage = '';
		let isUnknownError = false;

		if (e.name === 'VoxConnectionError' || e.name === 'AuthResult')
		{
			Util.reportConnectionResult(e.call.id, false);
		}

		switch (errorCode)
		{
			case Call.StartCallErrorCode.ErrorUnexpectedAnswer:
				errorMessage = Loc.getMessage('IM_CALL_ERROR_UNEXPECTED_ANSWER');
				break;

			case Call.StartCallErrorCode.BlankAnswerWithErrorCode:
				errorMessage = Loc.getMessage('IM_CALL_ERROR_BLANK_ANSWER');
				break;

			case Call.StartCallErrorCode.BlankAnswer:
				errorMessage = Loc.getMessage('IM_CALL_ERROR_BLANK_ANSWER');
				break;

			case Call.StartCallErrorCode.AccessDenied:
				errorMessage = Loc.getMessage('IM_CALL_ERROR_ACCESS_DENIED');
				break;

			case Call.StartCallErrorCode.NoWebrtc:
				errorMessage = Loc.getMessage(this.isHttps ? 'IM_CALL_NO_WEBRT' : 'IM_CALL_ERROR_HTTPS_REQUIRED');
				break;

			case Call.StartCallErrorCode.UnknownError:
				isUnknownError = true;
				errorMessage = Loc.getMessage('IM_CALL_ERROR_UNKNOWN');
				break;

			case Call.StartCallErrorCode.NetworkError:
				errorMessage = Loc.getMessage('IM_CALL_ERROR_NETWORK');
				break;

			case Call.StartCallErrorCode.NotAllowedError:
				errorMessage = Loc.getMessage('IM_CALL_ERROR_HARDWARE_ACCESS_DENIED');
				break;

			case Call.StartCallErrorCode.NotReadableError:
				errorMessage = Loc.getMessage('IM_CALL_ERROR_HARDWARE');
				break;

			default:
				if (errorCode === Call.StartCallErrorCode.AuthorizeError || e.name === 'AuthResult')
				{
					errorMessage = Loc.getMessage('IM_CALL_ERROR_AUTHORIZATION');
				}
				else if (errorCode == 403 && e.name === 'Failed')
				{
					errorMessage = Loc.getMessage('IM_CALL_ERROR_HARDWARE_ACCESS_DENIED');
				}
				else
				{
					isUnknownError = true;
					errorMessage = Loc.getMessage('IM_CALL_ERROR_UNKNOWN_WITH_CODE', { '#ERROR_CODE#': errorCode });
				}
		}

		if (this.callView)
		{
			if (isUnknownError)
			{
				this.callView.showSelfTest();
			}
			else if (errorCode === Call.DisconnectReason.SecurityKeyChanged)
			{
				this.callView.showSecurityKeyError();
			}
			else
			{
				this.callView.showFatalError({ text: errorMessage });
			}
		}
		else
		{
			this.showNotification(errorMessage);
		}

		this.autoCloseCallView = false;
		if (this.currentCall)
		{
			this.removeVideoStrategy();
			this.removeCallEvents();

			if (this.currentCallIsNew)
			{
				// todo: possibly delete
				this.callEngine.getRestClient().callMethod('im.call.interrupt', {callId: this.currentCall.id});
			}

			this.currentCall.destroy();
			this.currentCall = null;
			this.currentCallIsNew = false;
		}

		if (this.promotedToAdminTimeout)
		{
			clearTimeout(this.promotedToAdminTimeout);
		}

		Call.Hardware.isMicrophoneMuted = false;
	}

	onCallLeave(e)
	{
		if (!e.local)
		{
			return;
		}

		if (this.webScreenSharePopup)
		{
			this.webScreenSharePopup.close();
		}

		this.togglePictureInPictureCallWindow({ isForceClose: true });

		if (!this.getActiveCallUsers().length)
		{
			Analytics.getInstance().onFinishCall({
				callId: this.currentCall?.uuid,
				callType: Analytics.AnalyticsType.videoconf,
				status: Analytics.AnalyticsStatus.lastUserLeft,
				chatId: this.currentCall?.associatedEntity.id,
				callUsersCount: this.getCallUsers(true).length,
				callLength: Util.getTimeText(this.currentCall.startDate),
			});
		}

		this.endCall();
	}

	onCallDestroy(e)
	{
		this.currentCall = null;

		if (this.promotedToAdminTimeout)
		{
			clearTimeout(this.promotedToAdminTimeout);
		}

		if (this.floatingScreenShareWindow)
		{
			this.floatingScreenShareWindow.close;
		}

		if (this.webScreenSharePopup)
		{
			this.webScreenSharePopup.close();
		}

		if (this.riseYouHandToTalkPopup)
		{
			this.riseYouHandToTalkPopup.close();
			this.riseYouHandToTalkPopup = null;
		}

		this.restart();
	}

	onCheckDevicesSave(changedValues)
	{
		if (changedValues['camera'])
		{
			Call.Hardware.defaultCamera = changedValues['camera'];
		}

		if (changedValues['microphone'])
		{
			Call.Hardware.defaultMicrophone = changedValues['microphone'];
		}

		if (changedValues['audioOutput'])
		{
			Call.Hardware.defaultSpeaker = changedValues['audioOutput'];
		}

		if (changedValues['preferHDQuality'])
		{
			Call.Hardware.preferHdQuality = changedValues['preferHDQuality'];
		}

		if (changedValues['enableMicAutoParameters'])
		{
			Call.Hardware.enableMicAutoParameters = changedValues['enableMicAutoParameters'];
		}
	}

	setCameraState(state)
	{
		Call.Hardware.isCameraOn = state;
	}

	#stopRecordCall()
	{
		if (this.isRecording())
		{
			this.callRecordState = Call.View.RecordState.Stopped;
			this.callView.setRecordState(this.callView.getDefaultRecordState());
			this.callView.setButtonActive('record', false);

			this.currentCall.sendRecordState({
				action: Call.View.RecordState.Stopped,
				userId: this.currentCall.userId,
			});
		}
	}
	/* endregion 01. Call methods */

	/* region 02. Component methods */
	/* region 01. General actions */
	isChatShowed()
	{
		return this.getConference().common.showChat;
	}

	toggleChat()
	{
		const rightPanelMode = this.getConference().common.rightPanelMode;
		if (rightPanelMode === RightPanelMode.hidden)
		{
			this.controller.getStore().dispatch('conference/changeRightPanelMode', {mode: RightPanelMode.chat});
			this.callView.setButtonActive('chat', true);
		}
		else if (rightPanelMode === RightPanelMode.chat)
		{
			this.controller.getStore().dispatch('conference/changeRightPanelMode', {mode: RightPanelMode.hidden});
			this.callView.setButtonActive('chat', false);
		}
		else if (rightPanelMode === RightPanelMode.users)
		{
			this.controller.getStore().dispatch('conference/changeRightPanelMode', {mode: RightPanelMode.split});
			this.callView.setButtonActive('chat', true);
		}
		else if (rightPanelMode === RightPanelMode.split)
		{
			this.controller.getStore().dispatch('conference/changeRightPanelMode', {mode: RightPanelMode.users});
			this.callView.setButtonActive('chat', false);
		}
	}

	toggleUserList()
	{
		const rightPanelMode = this.getConference().common.rightPanelMode;
		if (rightPanelMode === RightPanelMode.hidden)
		{
			this.controller.getStore().dispatch('conference/changeRightPanelMode', {mode: RightPanelMode.users});
			this.callView.setButtonActive('users', true);
		}
		else if (rightPanelMode === RightPanelMode.users)
		{
			this.controller.getStore().dispatch('conference/changeRightPanelMode', {mode: RightPanelMode.hidden});
			this.callView.setButtonActive('users', false);
		}
		else if (rightPanelMode === RightPanelMode.chat)
		{
			this.controller.getStore().dispatch('conference/changeRightPanelMode', {mode: RightPanelMode.split});
			this.callView.setButtonActive('users', true);
		}
		else if (rightPanelMode === RightPanelMode.split)
		{
			this.controller.getStore().dispatch('conference/changeRightPanelMode', {mode: RightPanelMode.chat});
			this.callView.setButtonActive('users', false);
		}
	}

	pinUser(user)
	{
		if (!this.callView)
		{
			return false;
		}
		this.callView.pinUser(user.id);
		this.callView.setLayout(Call.View.Layout.Centered);
	}

	unpinUser()
	{
		if (!this.callView)
		{
			return false;
		}
		this.callView.unpinUser();
	}

	changeBackground()
	{
		if (!Call.Hardware)
		{
			return false;
		}
		Call.BackgroundDialog.open();
	}

	openChat(user)
	{
		DesktopApi.emitToMainWindow('bxConferenceOpenChat', [user.id]);
	}

	openProfile(user)
	{
		DesktopApi.emitToMainWindow('bxConferenceOpenProfile', [user.id]);
	}

	setDialogInited()
	{
		this.dialogInited = true;
		let dialogData = this.getDialogData();
		document.title = dialogData.name;
	}

	changeVideoconfUrl(newUrl)
	{
		window.history.pushState("", "", newUrl);
	}

	sendNewMessageNotify(params)
	{
		const MAX_LENGTH = 40;
		const AUTO_HIDE_TIME = 4000;

		if (!this.checkIfMessageNotifyIsNeeded(params))
		{
			return false;
		}
		const text = Utils.text.purify(params.message.text, params.message.params, params.files);
		let avatar = '';
		let userName = '';

		// avatar and username only for non-system messages
		if (params.message.senderId > 0 && params.message.system !== 'Y')
		{
			const messageAuthor = this.controller.getStore().getters['users/get'](params.message.senderId, true);
			userName = messageAuthor.name;
			avatar = messageAuthor.avatar;
		}

		Notifier.notify({
			id: `im-videconf-${params.message.id}`,
			title: userName,
			icon: avatar,
			text
		});

		return true;
	}

	checkIfMessageNotifyIsNeeded(params)
	{
		if (!Util.isConferenceChatEnabled())
		{
			return false;
		}

		const rightPanelMode = this.getConference().common.rightPanelMode;
		return !Utils.device.isMobile()
			&& params.chatId === this.getChatId()
			&& (rightPanelMode !== RightPanelMode.chat || rightPanelMode !== RightPanelMode.split)
			&& params.message.senderId !== this.controller.getUserId()
			&& !this.getConference().common.error;
	}

	onInputFocus(e)
	{
		this.callView.setHotKeyTemporaryBlock(true);
	}

	onInputBlur(e)
	{
		this.callView.setHotKeyTemporaryBlock(false);
	}

	closeReconnectionBaloon()
	{
		if (this.reconnectionBaloon)
		{
			this.reconnectionBaloon.close();
			this.reconnectionBaloon = null;
		}
	}

	setReconnectingCameraId(id)
	{
		this.reconnectingCameraId = id;

		if (id) {
			this.updateCameraSettingsInCurrentCallAfterReconnecting(id)
		}
	}

	updateCameraSettingsInCurrentCallAfterReconnecting(cameraId)
	{
		if (this.currentCall.cameraId === cameraId) {
			return;
		}

		const devicesList = Call.Hardware.getCameraList();

		if (!devicesList.find(device => device.deviceId === cameraId))
		{
			return;
		}

		this.currentCall.setCameraId(cameraId);
		this.setReconnectingCameraId(null);
	}

	onUpdateLastUsedCameraId()
	{
		const cameraId = this.currentCall.cameraId;
		if (cameraId)
		{
			this.lastUsedCameraId = cameraId;
		}
	}

	onReconnecting(e)
	{
		if (!(this.currentCall.provider === Call.Provider.Bitrix || this.currentCall.provider === Call.Provider.Plain))
		{
			// todo: restore after fixing balloon resurrection issue
			// related to multiple simultaneous calls to the balloon manager
			// now it's enabled for Bitrix24 calls as a temp solution
			return false;
		}

		Analytics.getInstance().onReconnect({
			callId: this.currentCall.uuid,
			callType: Analytics.AnalyticsType.videoconf,
			reconnectionReason: e.reconnectionReason,
			reconnectionReasonInfo: e.reconnectionReasonInfo,
			reconnectionEventCount: e.reconnectionEventCount,
		});

		// noinspection UnreachableCodeJS

		if (this.reconnectionBaloon)
		{
			return;
		}

		this.reconnectionBaloon = BX.UI.Notification.Center.notify({
			content: Text.encode(BX.message('IM_CALL_RECONNECTING')),
			autoHide: false,
			position: "top-right",
			closeButton: false,
		})
	}

	onReconnected()
	{
		this.setReconnectingCameraId(this.lastUsedCameraId)
		if (!(this.currentCall.provider === Call.Provider.Bitrix || this.currentCall.provider === Call.Provider.Plain))
		{
			// todo: restore after fixing balloon resurrection issue
			// related to multiple simultaneous calls to the balloon manager
			// now it's enabled for Bitrix24 calls as a temp solution
			return false;
		}

		// noinspection UnreachableCodeJS
		this.closeReconnectionBaloon();
	}

	onReconnectingFailed(e)
	{
		Analytics.getInstance().onReconnectError({
			callId: this.currentCall?.id,
			callType: Analytics.AnalyticsType.videoconf,
			errorCode: e?.code,
		});
	}

	setUserWasRenamed()
	{
		if (this.callView)
		{
			this.callView.localUser.userModel.wasRenamed = true;
		}
	}
	/* endregion 01. General actions */

	/* region 02. Store actions */
	setError(errorCode)
	{
		const currentError = this.getConference().common.error;
		// if user kicked from call - dont show him end of call form
		if (currentError && currentError === ConferenceErrorCode.kickedFromCall)
		{
			return;
		}

		this.controller.getStore().commit('conference/setError', {errorCode});
	}

	toggleSmiles()
	{
		this.controller.getStore().commit('conference/toggleSmiles');
	}

	setJoinType(joinWithVideo)
	{
		this.controller.getStore().commit('conference/setJoinType', {joinWithVideo});
	}

	setConferenceStatus(conferenceStarted)
	{
		this.controller.getStore().commit('conference/setConferenceStatus', {conferenceStarted});
	}

	setConferenceHasErrorInCall(hasErrorInCall)
	{
		this.controller.getStore().commit('conference/setConferenceHasErrorInCall', {hasErrorInCall});
	}

	setConferenceStartDate(conferenceStartDate)
	{
		this.controller.getStore().commit('conference/setConferenceStartDate', {conferenceStartDate});
	}

	setUserReadyToJoin()
	{
		this.controller.getStore().commit('conference/setUserReadyToJoin');
	}

	updateCallUser(userId, fields)
	{
		this.controller.getStore().dispatch('call/updateUser', {id: userId, fields});
	}
	/* endregion 02. Store actions */

	/* region 03. Rest actions */
	setUserName(name)
	{
		return new Promise((resolve, reject) => {
			this.restClient.callMethod('im.call.user.update', {
				name: name,
				chat_id: this.getChatId()
			}).then(() => {
				resolve();
			}).catch((error) => {
				reject(error)
			});
		});
	}

	checkPassword(password)
	{
		return new Promise((resolve, reject) => {
			this.restClient.callMethod('im.videoconf.password.check', { password, alias: this.params.alias })
				.then(result => {
					if (result.data() === true)
					{
						this.restClient.setPassword(password);
						this.controller.getStore().commit('conference/common', {
							passChecked: true
						});
						this.initUserComplete();
						resolve();
					}
					else
					{
						reject();
					}
				}).catch(result => {
				console.error('Password check error', result);
			});
		});
	}

	changeLink()
	{
		return new Promise((resolve, reject) => {
			this.restClient.callMethod('im.videoconf.share.change', {
				dialog_id: this.getDialogId()
			}).then(() => {
				resolve();
			}).catch((error) => {
				reject(error)
			});
		});
	}
	/* endregion 03. Rest actions */
	/* endregion 02. Component methods */

	/* endregion 02. Methods */

	/* region 03. Utils */
	ready()
	{
		if (this.inited)
		{
			let promise = new BX.Promise;
			promise.resolve(this);

			return promise;
		}

		return this.initPromise;
	}

	getConference()
	{
		return this.controller.getStore().state.conference;
	}

	isBroadcast()
	{
		return this.getConference().common.isBroadcast;
	}

	getBroadcastPresenters()
	{
		return this.getConference().common.presenters;
	}

	isExternalUser()
	{
		return !!this.getUserHash();
	}

	getCallConfig(videoEnabled: Boolean, callUuid: String): Object
	{
		const callConfig = {
			videoEnabled,
			type: Call.Type.Permanent,
			entityType: 'chat',
			entityId: this.getDialogId(),
			provider: Call.Provider.Bitrix,
			enableMicAutoParameters: Call.Hardware.enableMicAutoParameters,
			joinExisting: true,
			token: this.callToken,
			chatInfo: {
				advanced: {
					chatType: 'videoconf',
					entityData1: '',
					entityData2: '',
					entityData3: '',
					entityId: this.getDialogId(),
					entityType: 'VIDEOCONF',
				},
				avatar: '/bitrix/js/im/images/blank.gif',
				avatarColor: '#ab7761',
				id: this.getDialogId(),
				chatId: this.getChatId(),
				name: this.params.conferenceTitle,
				type: 'chat',
				userCounter: this.getDialogData()?.userCounter,
			},
		};

		if (callUuid)
		{
			callConfig.roomId = callUuid;
		}

		return callConfig;
	}

	getChatId()
	{
		return parseInt(this.params.chatId);
	}

	getDialogId()
	{
		return this.params.dialogId;
	}

	getDialogData()
	{
		if (!this.dialogInited)
		{
			return false;
		}

		return this.controller.getStore().getters['dialogues/get'](this.getDialogId());
	}

	getHost()
	{
		return location.origin || '';
	}

	getStartupErrorCode()
	{
		return this.params.startupErrorCode? this.params.startupErrorCode : '';
	}

	isHttps()
	{
		return location.protocol === 'https:';
	}

	getUserHash()
	{
		return this.getConference().user.hash;
	}

	getUserHashCookie()
	{
		let userHash = '';

		let cookie = Cookie.get(null, 'BITRIX_CALL_HASH');
		if (typeof cookie === 'string' && cookie.match(/^[a-f0-9]{32}$/))
		{
			userHash = cookie;
		}

		return userHash;
	}

	switchToSessAuth()
	{
		this.restClient.restClient.queryParams = undefined;

		return true;
	}

	/* endregion 03. Utils */
}

ConferenceApplication.FeatureState = {
	Enabled: 'enabled',
	Disabled: 'disabled',
	Limited: 'limited',
};

export { ConferenceApplication, ConferenceChannel };
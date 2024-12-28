'use strict';

jn.define('call/calls/controller', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics, DialogType } = require('call/const');
	const { CallLayout } = require('call/calls/layout');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { Theme } = require('im/lib/theme');
	const { Icon } = require('assets/icons');
	const { Loc } = require('loc');
	const { Tourist } = require('tourist');

	const pathToExtension = `${currentDomain}/bitrix/mobileapp/callmobile/extensions/call/calls/controller/`;

	class CallController
	{
		constructor()
		{
			this.callView = null;
			this.callViewPromise = null;

			this.startCallPromise = null;
			this.localVideoPromise = null;
			this.localVideoStream = null;

			this.callProvider = null;
			this._currentCall = null;
			Object.defineProperty(this, 'currentCall', {
				get: () => this._currentCall,
				set: (call) => {
					if (this._currentCall != call)
					{
						BX.postComponentEvent('CallEvents::hasActiveCall', [!!call], 'communication');
						this._currentCall = call;
					}
				},
			});
			this.childCall = null;
			this.callStartTime = null;
			this.callTimerInterval = null;
			this.callWithLegacyMobile = false;
			this.nativeCall = null;
			this.chatCounter = 0;

			this.callInviteTime = null;
			this.callDeclineTimeout = 35000;

			this.callVideoEnabled = false; // for proximity sensor

			this.ignoreJoinAnalyticsEvent = false;
			this.ignoreLeaveAnalyticsEvent = false;

			this.onCallUserInvitedHandler = this.onCallUserInvited.bind(this);
			this.onCallUserStateChangedHandler = this.onCallUserStateChanged.bind(this);
			this.onCallUserMicrophoneStateHandler = this.onCallUserMicrophoneState.bind(this);
			this.onCallUserScreenStateHandler = this.onCallUserScreenState.bind(this);
			this.onCallUserVideoPausedHandler = this.onCallUserVideoPaused.bind(this);
			this.onCallUserVoiceStartedHandler = this.onCallUserVoiceStarted.bind(this);
			this.onCallUserVoiceStoppedHandler = this.onCallUserVoiceStopped.bind(this);
			this.onCallUserFloorRequestHandler = this.onCallUserFloorRequest.bind(this);
			this.onCallUserEmotionHandler = this.onCallUserEmotion.bind(this);
			this.onCallLocalMediaReceivedHandler = this.onCallLocalMediaReceived.bind(this);
			this.onCallLocalMediaStoppedHandler = this.onCallLocalMediaStopped.bind(this);
			this.onCallRTCStatsReceivedHandler = this.onCallRTCStatsReceived.bind(this);
			this.onCallCallFailureHandler = this.onCallCallFailure.bind(this);
			this.onCallStreamReceivedHandler = this.onCallStreamReceived.bind(this);
			this.onCallStreamRemovedHandler = this.onCallStreamRemoved.bind(this);
			this.onCallJoinHandler = this.onCallJoin.bind(this);
			this.onCallLeaveHandler = this.onCallLeave.bind(this);
			this.onCallDestroyHandler = this.onCallDestroy.bind(this);
			this.onCallHangupHandler = this.onCallHangup.bind(this);
			this.onChildCallFirstStreamHandler = this.onChildCallFirstStream.bind(this);
			this.onCallTimeoutHandler = this.onCallTimeout.bind(this);
			this.onCallReconnectedHandler = this.onCallReconnected.bind(this);
			this.onUpdateCallCopilotStateHandler = this.onUpdateCallCopilotState.bind(this);

			this.onMicButtonClickHandler = this.onMicButtonClick.bind(this);
			this.onFloorRequestButtonClickHandler = this.onFloorRequestButtonClick.bind(this);
			this.onCameraButtonClickHandler = this.onCameraButtonClick.bind(this);
			this.onReplaceCameraClickHandler = this.onReplaceCameraClick.bind(this);
			this.onChatButtonClickHandler = this.onChatButtonClick.bind(this);
			this.onPrivateChatButtonClickHandler = this.onPrivateChatButtonClick.bind(this);
			this.onAnswerButtonClickHandler = this.onAnswerButtonClick.bind(this);
			this.onHangupButtonClickHandler = this.onHangupButtonClick.bind(this);
			this.onDeclineButtonClickHandler = this.onDeclineButtonClick.bind(this);
			this.onSetCentralUserHandler = this.onSetCentralUser.bind(this);
			this.onSelectAudioDeviceHandler = this.onSelectAudioDevice.bind(this);
			this.onToggleSubscriptionRemoteVideoHandler = this.onToggleSubscriptionRemoteVideo.bind(this);
			this.onChangeStateCopilotHandler = this.onChangeStateCopilot.bind(this);

			this.onNativeCallAnsweredHandler = this.onNativeCallAnswered.bind(this);
			this.onNativeCallEndedHandler = this.onNativeCallEnded.bind(this);
			this.onNativeCallMutedHandler = this.onNativeCallMuted.bind(this);
			this.onNativeCallVideoIntentHandler = this.onNativeCallVideoIntent.bind(this);

			this._nativeAnsweredAction = null;
			this.ignoreNativeCallAnswer = false;

			this.onProximitySensorDebounced = CallUtil.debounce(this.onProximitySensor.bind(this), 500);
			this.init();

			this.isAppPaused = false;
			this.canProximitySensorBeEnabled = false;
		}

		init()
		{
			// init outgoing call (from chat)
			BX.addCustomEvent('onCallInvite', this.onCallInvite.bind(this));
			// new incoming call (from CallEngine)
			BX.addCustomEvent('CallEvents::incomingCall', this.onIncomingCall.bind(this));

			// try join existing call (from chat)
			BX.addCustomEvent('CallEvents::joinCall', this.onJoinCall.bind(this));

			BX.addCustomEvent('onAppActive', this.onAppActive.bind(this));
			BX.addCustomEvent('onAppPaused', this.onAppPaused.bind(this));
			BX.addCustomEvent('ImRecent::counter::messages', this.onImMessagesCounter.bind(this));

			BX.PULL.subscribe({
				type: 'server',
				moduleId: 'im',
				command: 'callUserNameUpdate',
				callback: this.onCallUserNameUpdate.bind(this),
			});

			device.on('proximityChanged', this.onProximitySensorDebounced);
		}

		getCallType()
		{
			if (!this.currentCall)
			{
				return '';
			}

			const isVideoconf = this.currentCall.associatedEntity.type === 'chat'
				&& this.currentCall.associatedEntity.advanced['chatType'] === 'videoconf'
			;
			const callType = this.callProvider === BX.Call.Provider.Plain
				? Analytics.AnalyticsType.private
				: Analytics.AnalyticsType.group
			;

			return isVideoconf ? Analytics.AnalyticsType.videoconf : callType;
		}

		getAssociatedEntityType()
		{
			if (!this.currentCall)
			{
				return '';
			}

			return this.currentCall.associatedEntity.advanced.chatType;
		}

		getAssociatedEntityId()
		{
			if (!this.currentCall)
			{
				return '';
			}

			return this.currentCall.associatedEntity.advanced.entityId;
		}

		sendStartCallAnalytics()
		{
			const analytics = new AnalyticsEvent()
				.setTool(Analytics.AnalyticsTool.im)
				.setCategory(Analytics.AnalyticsCategory.call)
				.setEvent(Analytics.AnalyticsEvent.startCall)
				.setType(this.getCallType())
				.setStatus(Analytics.AnalyticsStatus.success)
				.setP1(
					this.currentCall.isVideoEnabled()
						? Analytics.AnalyticsEvent.cameraOn
						: Analytics.AnalyticsEvent.cameraOff,
				)
				.setP2(
					this.currentCall.isMuted()
						? Analytics.AnalyticsEvent.micOff
						: Analytics.AnalyticsEvent.micOn,
				)
				.setP3(
					this.currentCall.isCopilotActive
						? Analytics.AnalyticsAIStatus.aiOn
						: Analytics.AnalyticsAIStatus.aiOff
				)
				.setP5(`callId_${this.currentCall.id}`)
			;

			if (this.getAssociatedEntityType() === DialogType.collab)
			{
				const collabId = this.getAssociatedEntityId();
				analytics.setP4(`collabId_${collabId}`);
			}

			analytics.send();
		}

		sendJoinCallAnalytics(status, mediaParams, section, element)
		{
			const analytics = new AnalyticsEvent()
				.setTool(Analytics.AnalyticsTool.im)
				.setCategory(Analytics.AnalyticsCategory.call)
				.setEvent(Analytics.AnalyticsEvent.connect)
				.setType(this.getCallType())
				.setStatus(status)
				.setP3(this.getAnalyticsUserType())
				.setP5(`callId_${this.currentCall.id}`)
			;

			if (mediaParams)
			{
				analytics.setP1(
					mediaParams.video
						? Analytics.AnalyticsEvent.cameraOn
						: Analytics.AnalyticsEvent.cameraOff,
				);

				analytics.setP2(
					mediaParams.audio
						? Analytics.AnalyticsEvent.micOff
						: Analytics.AnalyticsEvent.micOn,
				);
			}

			if (section)
			{
				analytics.setSection(section);
			}

			if (element)
			{
				analytics.setElement(element);
			}

			if (this.getAssociatedEntityType() === DialogType.collab)
			{
				const collabId = this.getAssociatedEntityId();
				analytics.setP4(`collabId_${collabId}`);
			}

			analytics.send();
		}

		sendStartCopilotRecordAnalytics(errorCode = null)
		{
			const errorCodes = {
				AI_UNAVAILABLE_ERROR: Analytics.AnalyticsStatus.errorB24,
				AI_SETTINGS_ERROR: Analytics.AnalyticsStatus.errorB24,
				AI_AGREEMENT_ERROR: Analytics.AnalyticsStatus.errorAgreement,
				AI_NOT_ENOUGH_BAAS_ERROR: Analytics.AnalyticsStatus.errorLimitBaas,
			};

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.AnalyticsTool.ai)
				.setCategory(Analytics.AnalyticsCategory.callsOperations)
				.setEvent(Analytics.AnalyticsEvent.aiRecordStart)
				.setType(this.getCallType())
				.setStatus(errorCode ? errorCodes[errorCode] : Analytics.AnalyticsStatus.success)
				.setSection(Analytics.AnalyticsSection.callFollowup)
				.setP5(`callId_${this.currentCall.id}`)
			;

			const userCount = this.getActiveCallUsers().length;
			if (userCount)
			{
				analytics.setP3(userCount + 1);
			}

			analytics.send();
		}

		onCallInvite(e)
		{
			let dialogId;
			let
				dialogData;
			if ('dialogId' in e)
			{
				if (e.dialogId.startsWith('chat'))
				{
					dialogData = e.chatData || {};
				}
				else
				{
					dialogData = e.userData && e.userData[e.dialogId] || {};
				}
				dialogId = e.dialogId;
			}
			else if ('userId' in e)
			{
				dialogData = e.userData && e.userData[e.userId] || {};
				dialogId = e.userId;
			}
			else
			{
				CallUtil.error('Can not start call. No userId or dialogId in event');
				navigator.notification.alert(BX.message('MOBILE_CALL_INTERNAL_ERROR').replace('#ERROR_CODE#', 'E001'));

				return;
			}
			let { name, avatar, color, type } = dialogData;
			avatar = decodeURI(avatar);
			this.startCall(dialogId, e.video, { name, avatar, color, type });
		}

		maybeShowLocalVideo(show)
		{
			if (this.localVideoPromise)
			{
				return this.localVideoPromise;
			}

			this.localVideoPromise = new Promise((resolve, reject) => {
				if (!show)
				{
					return resolve();
				}

				MediaDevices.getUserMedia({ audio: true, video: true }).then((stream) => {
					this.localVideoStream = stream;
					if (!this.startCallPromise)
					{
						this.stopLocalVideoStream(true);
					}
					else if (this.callView)
					{
						this.callView.setVideoStream(env.userId, this.localVideoStream, (MediaDevices.cameraDirection === 'front'));
					}
					resolve();
				}).catch((err) => reject(err));
			});

			return this.localVideoPromise;
		}

		stopLocalVideoStream(destroy = false)
		{
			if (this.localVideoStream)
			{
				MediaDevices.stopCapture();

				if (destroy)
				{
					this.localVideoStream = null;
					return;
				}
			}
		}

		startCall(dialogId, video, associatedDialogData = {})
		{
			console.log('CallController.startCall', dialogId, video, associatedDialogData);
			if (!CallUtil.isDeviceSupported())
			{
				CallUtil.error(BX.message('MOBILE_CALL_UNSUPPORTED_VERSION'));
				navigator.notification.alert(BX.message('MOBILE_CALL_UNSUPPORTED_VERSION'));

				return;
			}

			if (this.callView || this.currentCall)
			{
				if (this.currentCall?.associatedEntity.id === dialogId)
				{
					this.onJoinCall(this.currentCall.id);
					return;
				}
				else
				{
					Notification.showToastWithParams({
						message: Loc.getMessage('CALLMOBILE_MESSAGE_HAS_ACTIVE_CALL_HINT'),
						icon: Icon.ALERT,
						backgroundColor: Theme.colors.accentMainAlert,
					});
					return;
				}
			}
			dialogId = dialogId.toString();
			let provider = BX.Call.Provider.Plain;
			const isGroupChat = dialogId.toString().slice(0, 4) === 'chat';
			if (callEngine.isCallServerAllowed() && isGroupChat)
			{
				provider = BX.Call.Provider.Voximplant;
			}

			if (isGroupChat && callEngine.isBitrixCallServerEnabled())
			{
				provider = BX.Call.Provider.BitrixDev;

				this.startCallPromise = this.requestDeviceAccess(video).then(() => {
					// we have to start media stream retrieving
					// so we can use it in the callView before we connect to the call
					this.maybeShowLocalVideo(video);

					return this.openCallView({
						status: 'outgoing',
						isGroupCall: isGroupChat,
						associatedEntityName: associatedDialogData.name,
						associatedEntityAvatar: associatedDialogData.avatar,
						associatedEntityAvatarColor: associatedDialogData.color,
						cameraState: video,
						chatCounter: this.chatCounter,
						copilotAvailable: CallUtil.isAIServiceEnabled(associatedDialogData.type === 'videoconf'),
						copilotEnabled: false, // will be changed after the backend's response
					}, video);
				}).then(() => {
					BX.postComponentEvent('CallEvents::viewOpened', []);
					BX.postWebEvent('CallEvents::viewOpened', {});
					this.bindViewEvents();
					media.audioPlayer().playSound('call_start');

					return this.maybeShowLocalVideo(video);
				}).then(() => {
					this.callProvider = provider;

					return callEngine.createCall({
						type: associatedDialogData.type === 'videoconf' ? BX.Call.Type.Permanent : BX.Call.Type.Instant,
						entityType: 'chat',
						entityId: dialogId,
						provider,
						videoEnabled: Boolean(video),
						joinExisting: isGroupChat,
					});
				})
					.then((createResult) => {
						console.log('startCall.BitrixDev.createCall.createResult', createResult);

						this.currentCall = createResult.call;
						this.bindCallEvents();
						if (!this.callView)
						{
							if (this.currentCall)
							{
								this.currentCall.cancel();
							}
							this.clearEverything();
							return;
						}
						callInterface.indicator().setMode('outgoing');
						device.setIdleTimerDisabled(true);
						this.changeProximitySensorStatus(true);

						this.callView.appendUsers(this.currentCall.getUsers());
						this.callView.updateCopilotState(this.currentCall.isCopilotActive);
						CallUtil.getUsers(this.currentCall.id, this.getCallUsers(true)).then(
							(userData) => this.callView.updateUserData(userData),
						);

						media.audioPlayer().playSound('call_outgoing', 10);

						if (createResult.isNew)
						{
							this.sendStartCallAnalytics();
							this.ignoreJoinAnalyticsEvent = true;

							if (this.currentCall.isCopilotActive)
							{
								this.sendStartCopilotRecordAnalytics();
							}

							this.currentCall.inviteUsers();
						}
						else
						{
							if (this.getCallUsers(true).length > this.getMaxActiveMicrophonesCount())
							{
								this.currentCall.setMuted(true);
								this.callView.setMuted(true);
							}

							this.sendJoinCallAnalytics(
								Analytics.AnalyticsStatus.success,
								{
									video: this.currentCall.isVideoEnabled(),
									audio: this.currentCall.isMuted(),
								},
								Analytics.AnalyticsSection.chatWindow,
								Analytics.AnalyticsElement.videocall,
							);

							this.ignoreJoinAnalyticsEvent = true;

							this.callView.setState({
								status: 'call',
							});

							this.currentCall.answer({
								useVideo: video,
							});
						}
					})
					.catch((error) => {
						console.error('startCall.BitrixDev.createCall.createResult.catch', error);

						CallUtil.error(error);
						if (error instanceof DeviceAccessError)
						{
							CallUtil.showDeviceAccessConfirm(video, () => Application.openSettings());
						}
						else if (error instanceof CallJoinedElseWhereError)
						{
							navigator.notification.alert(BX.message('MOBILE_CALL_ALREADY_JOINED'));
						}
						else if ('code' in error && error.code === 'ALREADY_FINISHED')
						{
							navigator.notification.alert('MOBILE_CALL_ALREADY_FINISHED');
						}
						else
						{
							navigator.notification.alert(BX.message('MOBILE_CALL_INTERNAL_ERROR').replace('#ERROR_CODE#', `${error.code}`));
						}
						this.clearEverything();
					});

				return;
			}

			this.startCallPromise = this.requestDeviceAccess(video).then(() => {
				// we have to start media stream retrieving
				// so we can use it in the callView before we connect to the call
				this.maybeShowLocalVideo(video);

				return this.openCallView({
					status: 'outgoing',
					isGroupCall: isGroupChat,
					associatedEntityName: associatedDialogData.name,
					associatedEntityAvatar: associatedDialogData.avatar,
					associatedEntityAvatarColor: associatedDialogData.color,
					cameraState: video,
					chatCounter: this.chatCounter,
					copilotAvailable: CallUtil.isAIServiceEnabled(false),
					copilotEnabled: false,
				}, video);
			}).then(() => {
				BX.postComponentEvent('CallEvents::viewOpened', []);
				BX.postWebEvent('CallEvents::viewOpened', {});
				this.bindViewEvents();
				media.audioPlayer().playSound('call_start');

				return this.maybeShowLocalVideo(video);
			}).then(() => {
				this.callProvider = provider;

				return callEngine.createCall({
					entityType: 'chat',
					entityId: dialogId,
					provider,
					videoEnabled: !!video,
					joinExisting: isGroupChat,
				});
			})
				.then((createResult) => {
					this.currentCall = createResult.call;
					this.bindCallEvents();
					callInterface.indicator().setMode('outgoing');
					device.setIdleTimerDisabled(true);
					this.changeProximitySensorStatus(true);

					this.callView.appendUsers(this.currentCall.getUsers());
					this.callView.updateCopilotState(this.currentCall.isCopilotActive);
					CallUtil.getUsers(this.currentCall.id, this.getCallUsers(true)).then(
						(userData) => this.callView.updateUserData(userData),
					);

					media.audioPlayer().playSound('call_outgoing', 10);
					if (createResult.isNew)
					{
						this.sendStartCallAnalytics();
						this.currentCall.inviteUsers();

						if (this.currentCall.isCopilotActive)
						{
							this.sendStartCopilotRecordAnalytics();
						}
					}
					else
					{
						this.sendJoinCallAnalytics(
							Analytics.AnalyticsStatus.success,
							{
								video: this.currentCall.isVideoEnabled(),
								audio: this.currentCall.isMuted(),
							},
						);

						this.callView.setState({
							status: 'call',
						});

						this.currentCall.answer({
							useVideo: video,
						});
					}
				})
				.catch((error) => {
					CallUtil.error(error);
					if (error instanceof DeviceAccessError)
					{
						CallUtil.showDeviceAccessConfirm(video, () => Application.openSettings());
					}
					else if (error instanceof CallJoinedElseWhereError)
					{
						navigator.notification.alert(BX.message('MOBILE_CALL_ALREADY_JOINED'));
					}
					else if ('code' in error && error.code === 'ALREADY_FINISHED')
					{
						navigator.notification.alert('MOBILE_CALL_ALREADY_FINISHED');
					}
					else
					{
						navigator.notification.alert(BX.message('MOBILE_CALL_INTERNAL_ERROR').replace('#ERROR_CODE#', `${error.code}`));
					}
					this.clearEverything();
				});
		}

		joinCall(callId, video)
		{
			if (this.callView || this.currentCall)
			{
				return;
			}

			this.requestDeviceAccess(video).then(() => {
				return callEngine.getCallWithId(callId);
			}).then((result) => {
				this.currentCall = result.call;
				device.setIdleTimerDisabled(true);
				this.changeProximitySensorStatus(true);

				const isVideoconf = this.currentCall.associatedEntity.type === 'chat'
					&& this.currentCall.associatedEntity.advanced['chatType'] === 'videoconf'
				;

				return this.openCallView({
					status: 'call',
					chatCounter: this.chatCounter,
					isGroupCall: true, // it's impossible to join any other call but the group one
					associatedEntityName: this.currentCall.associatedEntity.name,
					associatedEntityAvatar: this.currentCall.associatedEntity.avatar ? CallUtil.makeAbsolute(this.currentCall.associatedEntity.avatar) : '',
					associatedEntityAvatarColor: this.currentCall.associatedEntity.avatarColor,
					copilotAvailable: CallUtil.isAIServiceEnabled(isVideoconf),
					copilotEnabled: this.currentCall.isCopilotActive,
				});
			}).then(() => {
				// call could have been finished by this moment
				if (!this.currentCall)
				{
					this.clearEverything();

					return;
				}

				this.bindViewEvents();
				this.callView.appendUsers(this.currentCall.getUsers());

				if (this.getCallUsers(true).length > this.getMaxActiveMicrophonesCount())
				{
					this.currentCall.setMuted(true);
					this.callView.setMuted(true);
				}

				CallUtil.getUsers(this.currentCall.id, this.getCallUsers(true)).then(
					(userData) => this.callView.updateUserData(userData),
				);
				this.bindCallEvents();

				this.currentCall.answer({
					useVideo: !!video,
				});
			})
				.catch((error) => {
					CallUtil.error(error);
					if (error.code && error.code == 'ALREADY_FINISHED')
					{
						navigator.notification.alert(BX.message('MOBILE_CALL_ALREADY_FINISHED'));
					}
					else if (error instanceof CallJoinedElseWhereError)
					{
						navigator.notification.alert(BX.message('MOBILE_CALL_ALREADY_JOINED'));
					}
					else if (error instanceof DeviceAccessError)
					{
						CallUtil.showDeviceAccessConfirm(video, () => Application.openSettings());
					}
					else
					{
						navigator.notification.alert(BX.message('MOBILE_CALL_INTERNAL_ERROR').replace('#ERROR_CODE#', `${error.code}`));
					}
				});
		}

		onIncomingCall(e)
		{
			CallUtil.warn('CallController.onIncomingCall', e);

			const newCall = callEngine.calls[e.callId];

			if (!this.canCallBeAnswered(newCall))
			{
				return;
			}

			this.callInviteTime = Date.now();

			if (!CallUtil.isDeviceSupported())
			{
				navigator.notification.alert(BX.message('MOBILE_CALL_UNSUPPORTED_VERSION'));
				return;
			}

			const provider = e.provider;
			const isDevCallEnabled = provider === BX.Call.Provider.Bitrix && callEngine.isBitrixCallServerEnabled();

			if (!isDevCallEnabled && provider !== BX.Call.Provider.Plain && provider !== BX.Call.Provider.Voximplant)
			{
				return;
			}

			this.callWithLegacyMobile = (e.isLegacyMobile === true);

			if (newCall instanceof CallStub)
			{
				CallUtil.error('This call is already finished');

				return;
			}

			if (this.currentCall)
			{
				if (this.currentCall.id == newCall.id)
				{
					// do nothing
				}
				else if (this.currentCall.id == newCall.parentId)
				{
					if (!this.childCall)
					{
						this.childCall = newCall;
						this.childCall.users.forEach(
							(userId) => this.callView.addUser(userId, BX.Call.UserState.Calling),
						);
						if (e.userData)
						{
							this.callView.updateUserData(e.userData);
						}

						this.callView.setState({
							isGroupCall: true,
							associatedEntityName: this.childCall.associatedEntity.name,
							associatedEntityAvatar: this.childCall.associatedEntity.avatar ? CallUtil.makeAbsolute(this.childCall.associatedEntity.avatar) : '',
							associatedEntityAvatarColor: this.childCall.associatedEntity.avatarColor,
						});

						this.answerChildCall();
					}
				}
				else
				{
					CallUtil.warn('can\'t participate in two calls');

					const isVideoconf = newCall.associatedEntity.type === 'chat'
						&& newCall.associatedEntity.advanced['chatType'] === 'videoconf'
					;
					const newCallType = provider === BX.Call.Provider.Plain
						? Analytics.AnalyticsType.private
						: Analytics.AnalyticsType.group
					;

					const callType = isVideoconf ? Analytics.AnalyticsType.videoconf : newCallType;

					const analytics = new AnalyticsEvent()
						.setTool(Analytics.AnalyticsTool.im)
						.setCategory(Analytics.AnalyticsCategory.call)
						.setEvent(Analytics.AnalyticsEvent.connect)
						.setType(callType)
						.setStatus(Analytics.AnalyticsStatus.busy)
						.setP5(`callId_${newCall.id}`);

					analytics.send();

					newCall.decline(486);
				}

				return;
			}

			if (this.callView)
			{
				CallUtil.error('call view already exists');

				return;
			}

			const video = e.video === true;

			this.currentCall = callEngine.calls[newCall.id];

			if ('callservice' in window)
			{
				const nativeCall = callservice.currentCall();
				if (nativeCall && nativeCall.params.type === 'internal' && nativeCall.params.call.ID == newCall.id)
				{
					this.nativeCall = nativeCall;
					if (Application.isBackground())
					{
						CallUtil.warn('Waking up p&p');
						CallUtil.forceBackgroundConnectPull(10).then(() => {
							if (this.currentCall)
							{
								this.currentCall.repeatAnswerEvents();

								CallUtil.warn('checking self state');
								callEngine.getRestClient().callMethod('im.call.getUserState', { callId: this.currentCall.id }).then((response) => {
									const data = response.data();
									const myState = data.STATE;

									if (Application.isBackground() && myState !== 'calling')
									{
										this.clearEverything();
									}
								}).catch((response) => {
									CallUtil.error(response);
									if (Application.isBackground())
									{
										Application.isBackground();
									}
								});
							}
						}).catch((err) => {
							CallUtil.error('Could not connect to p&p', err);

							this.clearEverything();
						});
					}
				}
			}

			device.setIdleTimerDisabled(true);
			this.changeProximitySensorStatus(true);
			this.bindCallEvents();
			this.bindNativeCallEvents();
			this.currentCall.setVideoEnabled(video);
			this.callProvider = provider;
			this.showIncomingCall({
				video,
				viewStatus: e.autoAnswer ? 'call' : 'incoming',
				isGroupCall: provider !== BX.Call.Provider.Plain,
			}).then(() => {
				CallUtil.warn('showIncomingCall success');
				if (this.currentCall && e.autoAnswer)
				{
					CallUtil.warn('auto-answer A');
					this.onAnswerButtonClick(video);
				}

				if (this.nativeCall && this.nativeCall.connected && !this.callAnswered)
				{
					CallUtil.warn('Native call is connected, but we did not receive answered event.');
					this.answerCurrentCall(this.nativeCall.params.video);
				}
			}).catch((error) => {
				CallUtil.error(error);
				this.clearEverything();
			});
		}

		answerCurrentCall(useVideo)
		{
			media.audioPlayer().stopPlayingSound();
			if (!this.currentCall)
			{
				CallUtil.error('no call to answer');
				this.clearEverything();

				return;
			}
			this.currentCall.setVideoEnabled(useVideo);
			if (this.callAnswered)
			{
				CallUtil.log('Call already answered');
			}
			this.callAnswered = true;

			this.requestDeviceAccess(useVideo).then(() => {
				if (!useVideo)
				{
					this.onCallLocalMediaStopped();
					this.stopLocalVideoStream(true);
				}
				this.currentCall.answer({
					useVideo,
				});
				this.callView.setState({
					status: 'connecting',
				});
			}).catch((error) => {
				CallUtil.error(error);
				if (error instanceof DeviceAccessError)
				{
					if (this.currentCall)
					{
						this.currentCall.decline();
					}
					CallUtil.showDeviceAccessConfirm(
						useVideo,
						() => Application.openSettings(),
						() => {},
					);
				}
				else if (error instanceof CallJoinedElseWhereError)
				{
					navigator.notification.alert(BX.message('MOBILE_CALL_ALREADY_JOINED'));
				}
				else
				{
					navigator.notification.alert(BX.message('MOBILE_CALL_INTERNAL_ERROR').replace('#ERROR_CODE#', `${error.code}`));
				}
				this.clearEverything();
			});
		}

		answerChildCall()
		{
			this.removeCallEvents();
			// this.removeVideoStrategy();
			this.childCall.on(BX.Call.Event.onStreamReceived, this.onChildCallFirstStreamHandler);
			this.childCall.on(BX.Call.Event.onLocalMediaReceived, this.onCallLocalMediaReceivedHandler);

			this.childCall.answer({
				useVideo: this.currentCall.isVideoEnabled(),
			});
		}

		showIncomingCall(params)
		{
			if (typeof (params) !== 'object')
			{
				params = {};
			}

			const isVideoconf = this.currentCall.associatedEntity.type === 'chat'
				&& this.currentCall.associatedEntity.advanced['chatType'] === 'videoconf'
			;

			params.video = params.video === true;

			this.startCallPromise = this.requestDeviceAccess(params.video).then(() => {
				// we have to start media stream retrieving
				// so we can use it in the callView before we connect to the call
				this.maybeShowLocalVideo(params.video);

				return this.openCallView({
					status: params.viewStatus || 'incoming',
					isGroupCall: params.isGroupCall,
					associatedEntityName: this.currentCall.associatedEntity.name,
					associatedEntityAvatar: this.currentCall.associatedEntity.avatar ? CallUtil.makeAbsolute(this.currentCall.associatedEntity.avatar) : '',
					associatedEntityAvatarColor: this.currentCall.associatedEntity.avatarColor,
					isVideoCall: params.video,
					cameraState: false,
					chatCounter: this.chatCounter,
					copilotAvailable: CallUtil.isAIServiceEnabled(isVideoconf),
					copilotEnabled: this.currentCall.isCopilotActive,
				}, params.video);
			}).then(() => {
				return this.maybeShowLocalVideo(params.video);
			}).then(() => {
				if (!this.currentCall)
				{
					return Promise.reject(new Error('ALREADY_FINISHED'));
				}
				if (params.viewStatus == 'incoming')
				{
					media.audioPlayer().playSound('call_incoming', 10);
				}
				callInterface.indicator().setMode('incoming');
				this.bindViewEvents();
				const userStates = this.currentCall.getUsers();
				for (const userId in userStates)
				{
					this.callView.addUser(userId, userStates[userId]);
				}

				if (this.getCallUsers(true).length > this.getMaxActiveMicrophonesCount())
				{
					this.currentCall.setMuted(true);
					this.callView.setMuted(true);
				}

				BX.rest.callMethod('im.call.getUsers', {
					callId: this.currentCall.id,
					AVATAR_HR: 'Y',
				}).then((response) => {
					if (this.callView)
					{
						this.callView.updateUserData(response.data());
					}
				});

				Promise.resolve();
			}).catch((error) => {
				CallUtil.error(error);
				if (error instanceof DeviceAccessError)
				{
					CallUtil.showDeviceAccessConfirm(params.video, () => Application.openSettings());
				}
				else if (error.code && error.code == 'ALREADY_FINISHED')
				{
					return reject('ALREADY_FINISHED');
				}
			});

			return this.startCallPromise;

			// this.scheduleCancelNotification();
			// window.BXIM.repeatSound('ringtone', 3500, true);
		}

		onJoinCall(callId)
		{
			if (!CallUtil.isDeviceSupported())
			{
				navigator.notification.alert(BX.message('MOBILE_CALL_UNSUPPORTED_VERSION'));

				return;
			}

			if (this.currentCall)
			{
				if (this.currentCall.id == callId)
				{
					this.unfold();
				}
				else
				{
					CallUtil.error('cannot join 2 calls yet');
				}
			}
			else
			{
				let outsideOfDialogTap = 0;
				let startCallWithVideo = 1;
				let startCallWithoutVideo = 2;
				let openChatButton = 3;
				navigator.notification.confirm(
					'',
					(button) => {
						if (button == 4 || button == outsideOfDialogTap)
						{
							return;
						}

						if (button == openChatButton)
						{
							BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [{ callId }], 'im.messenger');

							return;
						}

						if (button == startCallWithVideo || button == startCallWithoutVideo)
						{
							this.joinCall(callId, button == startCallWithVideo);
						}
					},
					BX.message('MOBILE_CALL_JOIN_GROUP_CALL'),
					[
						BX.message('MOBILE_CALL_WITH_VIDEO'),
						BX.message('MOBILE_CALL_WITHOUT_VIDEO'),
						BX.message('MOBILE_CALL_OPEN_CHAT'),
						BX.message('MOBILE_CALL_MICROPHONE_CANCEL'),
					],
				)
				;
			}
		}

		onCallHangup()
		{
			this.clearEverything();
		}

		bindViewEvents()
		{
			if (!this.callView)
			{
				return;
			}
			this.callView.on(CallLayout.Event.MicButtonClick, this.onMicButtonClickHandler);
			this.callView.on(CallLayout.Event.CameraButtonClick, this.onCameraButtonClickHandler);
			this.callView.on(CallLayout.Event.ReplaceCamera, this.onReplaceCameraClickHandler);
			this.callView.on(CallLayout.Event.FloorRequestButtonClick, this.onFloorRequestButtonClickHandler);
			this.callView.on(CallLayout.Event.ChatButtonClick, this.onChatButtonClickHandler);
			this.callView.on(CallLayout.Event.PrivateChatButtonClick, this.onPrivateChatButtonClickHandler);
			this.callView.on(CallLayout.Event.AnswerButtonClick, this.onAnswerButtonClickHandler);
			this.callView.on(CallLayout.Event.HangupButtonClick, this.onHangupButtonClickHandler);
			this.callView.on(CallLayout.Event.DeclineButtonClick, this.onDeclineButtonClickHandler);
			this.callView.on(CallLayout.Event.SetCentralUser, this.onSetCentralUserHandler);
			this.callView.on(CallLayout.Event.SelectAudioDevice, this.onSelectAudioDeviceHandler);
			this.callView.on(CallLayout.Event.ToggleSubscriptionRemoteVideo, this.onToggleSubscriptionRemoteVideoHandler);
			this.callView.on(CallLayout.Event.ToggleCopilot, this.onChangeStateCopilotHandler);
		}

		removeViewEvents()
		{
			if (!this.callView)
			{
				return;
			}
			this.callView.off(CallLayout.Event.MicButtonClick, this.onMicButtonClickHandler);
			this.callView.off(CallLayout.Event.CameraButtonClick, this.onCameraButtonClickHandler);
			this.callView.off(CallLayout.Event.ReplaceCamera, this.onReplaceCameraClickHandler);
			this.callView.off(CallLayout.Event.FloorRequestButtonClick, this.onFloorRequestButtonClickHandler);
			this.callView.off(CallLayout.Event.ChatButtonClick, this.onChatButtonClickHandler);
			this.callView.off(CallLayout.Event.PrivateChatButtonClick, this.onPrivateChatButtonClickHandler);
			this.callView.off(CallLayout.Event.AnswerButtonClick, this.onAnswerButtonClickHandler);
			this.callView.off(CallLayout.Event.HangupButtonClick, this.onHangupButtonClickHandler);
			this.callView.off(CallLayout.Event.DeclineButtonClick, this.onDeclineButtonClickHandler);
			this.callView.off(CallLayout.Event.SetCentralUser, this.onSetCentralUserHandler);
			this.callView.off(CallLayout.Event.SelectAudioDevice, this.onSelectAudioDeviceHandler);
			this.callView.off(CallLayout.Event.ToggleSubscriptionRemoteVideo, this.onToggleSubscriptionRemoteVideoHandler);
			this.callView.off(CallLayout.Event.ToggleCopilot, this.onChangeStateCopilotHandler);
		}

		bindCallEvents()
		{
			if (!this.currentCall)
			{
				return;
			}
			this.currentCall
				.on(BX.Call.Event.onUserInvited, this.onCallUserInvitedHandler)
				.on(BX.Call.Event.onUserStateChanged, this.onCallUserStateChangedHandler)
				.on(BX.Call.Event.onUserMicrophoneState, this.onCallUserMicrophoneStateHandler)
				.on(BX.Call.Event.onUserScreenState, this.onCallUserScreenStateHandler)
				.on(BX.Call.Event.onUserVideoPaused, this.onCallUserVideoPausedHandler)
				.on(BX.Call.Event.onUserVoiceStarted, this.onCallUserVoiceStartedHandler)
				.on(BX.Call.Event.onUserVoiceStopped, this.onCallUserVoiceStoppedHandler)
				.on(BX.Call.Event.onUserFloorRequest, this.onCallUserFloorRequestHandler)
				.on(BX.Call.Event.onUserEmotion, this.onCallUserEmotionHandler)
				.on(BX.Call.Event.onLocalMediaReceived, this.onCallLocalMediaReceivedHandler)
				.on(BX.Call.Event.onLocalMediaStopped, this.onCallLocalMediaStoppedHandler)
				.on(BX.Call.Event.onRTCStatsReceived, this.onCallRTCStatsReceivedHandler)
				.on(BX.Call.Event.onCallFailure, this.onCallCallFailureHandler)
				.on(BX.Call.Event.onStreamReceived, this.onCallStreamReceivedHandler)
				.on(BX.Call.Event.onStreamRemoved, this.onCallStreamRemovedHandler)
				.on(BX.Call.Event.onJoin, this.onCallJoinHandler)
				.on(BX.Call.Event.onLeave, this.onCallLeaveHandler)
				.on(BX.Call.Event.onDestroy, this.onCallDestroyHandler)
				.on(BX.Call.Event.onHangup, this.onCallHangupHandler)
				.on(BX.Call.Event.onPullEventUserInviteTimeout, this.onCallTimeoutHandler)
				.on(BX.Call.Event.onReconnected, this.onCallReconnectedHandler)
				.on(BX.Call.Event.onSwitchTrackRecordStatus, this.onUpdateCallCopilotStateHandler)

			;
		}

		removeCallEvents()
		{
			if (!this.currentCall)
			{
				return;
			}
			this.currentCall
				.off(BX.Call.Event.onUserInvited, this.onCallUserInvitedHandler)
				.off(BX.Call.Event.onUserStateChanged, this.onCallUserStateChangedHandler)
				.off(BX.Call.Event.onUserMicrophoneState, this.onCallUserMicrophoneStateHandler)
				.off(BX.Call.Event.onUserScreenState, this.onCallUserScreenStateHandler)
				.off(BX.Call.Event.onUserVoiceStarted, this.onCallUserVoiceStartedHandler)
				.off(BX.Call.Event.onUserVoiceStopped, this.onCallUserVoiceStoppedHandler)
				.off(BX.Call.Event.onUserFloorRequest, this.onCallUserFloorRequestHandler)
				.off(BX.Call.Event.onUserEmotion, this.onCallUserEmotionHandler)
				.off(BX.Call.Event.onLocalMediaReceived, this.onCallLocalMediaReceivedHandler)
				.off(BX.Call.Event.onLocalMediaStopped, this.onCallLocalMediaStoppedHandler)
				.off(BX.Call.Event.onRTCStatsReceived, this.onCallRTCStatsReceivedHandler)
				.off(BX.Call.Event.onCallFailure, this.onCallCallFailureHandler)
				.off(BX.Call.Event.onStreamReceived, this.onCallStreamReceivedHandler)
				.off(BX.Call.Event.onStreamRemoved, this.onCallStreamRemovedHandler)
				.off(BX.Call.Event.onJoin, this.onCallJoinHandler)
				.off(BX.Call.Event.onLeave, this.onCallLeaveHandler)
				.off(BX.Call.Event.onDestroy, this.onCallDestroyHandler)
				.off(BX.Call.Event.onHangup, this.onCallHangupHandler)
				.off(BX.Call.Event.onPullEventUserInviteTimeout, this.onCallTimeoutHandler)
				.off(BX.Call.Event.onReconnected, this.onCallReconnectedHandler)
				.off(BX.Call.Event.onSwitchTrackRecordStatus, this.onUpdateCallCopilotStateHandler)
			;
		}

		bindNativeCallEvents()
		{
			if (!this.nativeCall)
			{
				return;
			}
			this.nativeCall
				.on('answered', this.onNativeCallAnsweredHandler)
				.on('ended', this.onNativeCallEndedHandler)
				.on('muted', this.onNativeCallMutedHandler)
				.on('videointent', this.onNativeCallVideoIntentHandler)
			;
		}

		prepareWidgetLayer()
		{
			return new Promise((resolve, reject) => {
				if (uicomponent.widgetLayer() && this.rootWidget)
				{
					return resolve(this.rootWidget);
				}

				uicomponent.createWidgetLayer('layout', { backdrop: {} })
					.then((rootWidget) => resolve(rootWidget))
					.catch((error) => reject(error));
			});
		}

		openWidgetLayer()
		{
			return new Promise((resolve, reject) => {
				this.prepareWidgetLayer()
					.then((rootWidget) => {
						this.rootWidget = rootWidget;
						this.rootWidget.setListener((eventName) => {
							if (eventName === 'onViewRemoved')
							{
								if (uicomponent.widgetLayer())
								{
									uicomponent.widgetLayer().close().then(() => {
										this.rootWidget = null;
									});
								}
								else
								{
									this.rootWidget = null;
								}
							}
						});

						return uicomponent.widgetLayer().show();
					})
					.then(() => resolve())
					.catch((error) => reject(error));
			});
		}

		openCallView(viewProps = {}, showLocalVideo)
		{
			if (this.callViewPromise)
			{
				return this.callViewPromise;
			}

			this.callViewPromise = new Promise((resolve, reject) => {
				this.openWidgetLayer()
					.then(() => {
						CallUtil.warn('creating new CallLayout');
						this.callView = new CallLayout(viewProps);
						this.rootWidget.showComponent(this.callView);
						this.callViewPromise = null;

						if (showLocalVideo && this.localVideoStream)
						{
							this.callView.setVideoStream(env.userId, this.localVideoStream, (MediaDevices.cameraDirection === 'front'));
						}

						this.startCheckOutputDevice();

						resolve();
					})
					.catch((error) => {
						CallUtil.error(error);
						this.callViewPromise = null;
					});
			});

			return this.callViewPromise;
		}

		fold()
		{
			if (!this.currentCall || !this.callView)
			{
				return;
			}
			let associatedAvatar = `${pathToExtension}img/blank.png`;
			if (this.currentCall.associatedEntity
				&& this.currentCall.associatedEntity.avatar
				&& !CallUtil.isAvatarBlank(this.currentCall.associatedEntity.avatar)
			)
			{
				associatedAvatar = encodeURI(CallUtil.makeAbsolute(this.currentCall.associatedEntity.avatar));
			}
			uicomponent.widgetLayer().hide().then(() => {
				callInterface.indicator().setMode('active');
				callInterface.indicator().imageUrl = associatedAvatar;
				callInterface.indicator().show();
				callInterface.indicator().once('tap', () => this.unfold());
				this.changeProximitySensorStatus(false);

				BX.postComponentEvent('CallEvents::viewClosed', []);
				BX.postWebEvent('CallEvents::viewClosed', {});
			});
		}

		unfold()
		{
			callInterface.indicator().close();
			if (!this.currentCall || !this.callView)
			{
				return;
			}
			uicomponent.widgetLayer().show();
			this.changeProximitySensorStatus(true);

			BX.postComponentEvent('CallEvents::viewOpened', []);
			BX.postWebEvent('CallEvents::viewOpened', {});
		}

		startCallTimer()
		{
			this.callTimerInterval = setInterval(() => {
				callInterface.indicator().setTime(CallUtil.formatSeconds((Date.now() - this.callStartTime) / 1000));
			}, 1000);
		}

		stopCallTimer()
		{
			clearTimeout(this.callTimerInterval);
			this.callTimerInterval = null;
		}

		getCallUsers(includeSelf)
		{
			if (!this.currentCall)
			{
				return [];
			}
			const result = Object.keys(this.currentCall.getUsers());
			if (includeSelf)
			{
				result.push(this.currentCall.userId);
			}

			return result;
		}

		getMaxActiveCallUsers()
		{
			const userStates = this.currentCall.getUsers();
			let activeUsers = [];

			for (let userId in userStates)
			{
				if (userStates.hasOwnProperty(userId))
				{
					if (
						userStates[userId] !== BX.Call.UserState.Declined
						&& userStates[userId] !== BX.Call.UserState.Busy
						&& userStates[userId] !== BX.Call.UserState.Unavailable
					)
					{
						activeUsers.push(userId);
					}
				}
			}
			return activeUsers;
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
						userStates[userId] === BX.Call.UserState.Connected
						|| userStates[userId] === BX.Call.UserState.Connecting
						|| userStates[userId] === BX.Call.UserState.Calling
					)
					{
						activeUsers.push(userId);
					}
				}
			}
			return activeUsers;
		}

		onCallUserNameUpdate(params)
		{
			const { userId, name } = params;
			if (this.callView)
			{
				this.callView.updateUserData({
					[userId]: { name },
				});
			}
		}

		onImMessagesCounter(counter)
		{
			this.chatCounter = counter;
			if (this.callView)
			{
				this.callView.setChatCounter(counter);
			}
		}

		onAppActive()
		{
			this.isAppPaused = false;
			CallUtil.log('onAppActive');
			if (!this.currentCall)
			{
				CallUtil.warn('no current call');

				return;
			}

			const push = Application.getLastNotification();

			if (
				Application.getPlatform() === 'android'
				&& BX.type.isFunction(this.currentCall.isReady)
				&& !this.currentCall.isReady()
				&& push.hasOwnProperty('id')
				&& push.id.startsWith('IM_CALL_')
			)
			{
				CallUtil.log('check push');
				try
				{
					const pushParams = JSON.parse(push.params);
					CallUtil.log(pushParams);

					const callFields = pushParams.PARAMS.call;
					const isVideo = pushParams.PARAMS.video;
					const callId = callFields.ID;

					if (callId == this.currentCall.id)
					{
						CallUtil.warn('auto-answer B');
						this.answerCurrentCall(isVideo);
					}
				}
				catch (e)
				{
					// do nothing
					CallUtil.error(e);
				}
			}
			else
			{
				CallUtil.log('onAppActive');
				this.currentCall.log('onAppActive');
				this.currentCall.setVideoPaused(false);
			}
		}

		onAppPaused()
		{
			this.isAppPaused = true;
			if (!this.currentCall)
			{
				return;
			}

			CallUtil.log('onAppPaused');
			this.currentCall.log('onAppPaused');
			this.currentCall.setVideoPaused(true);
		}

		onProximitySensor()
		{
			if (!this.currentCall || this.isAppPaused)
			{
				return;
			}

			if (device.proximityState)
			{
				this.currentCall.setVideoPaused(true);
			}
			else
			{
				this.currentCall.setVideoPaused(false);
			}
		}

		onMicButtonClick()
		{
			if (!this.currentCall)
			{
				return;
			}
			const muted = !this.currentCall.isMuted();

			const analyticsEvent = muted ? Analytics.AnalyticsEvent.micOff : Analytics.AnalyticsEvent.micOn;

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.AnalyticsTool.im)
				.setCategory(Analytics.AnalyticsCategory.call)
				.setEvent(analyticsEvent)
				.setType(this.getCallType())
				.setSection(Analytics.AnalyticsSection.callWindow)
				.setP5(`callId_${this.currentCall.id}`);

			analytics.send();

			this.currentCall.setMuted(muted);
			this.callView.setMuted(muted);
			if (this.nativeCall)
			{
				this.nativeCall.mute(muted);
			}
		}

		onCameraButtonClick()
		{
			if (!this.currentCall)
			{
				return;
			}

			const cameraEnabled = !this.currentCall.isVideoEnabled();

			const analyticsEvent = cameraEnabled
				? Analytics.AnalyticsEvent.cameraOn
				: Analytics.AnalyticsEvent.cameraOff
			;

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.AnalyticsTool.im)
				.setCategory(Analytics.AnalyticsCategory.call)
				.setEvent(analyticsEvent)
				.setType(this.getCallType())
				.setSection(Analytics.AnalyticsSection.callWindow)
				.setP5(`callId_${this.currentCall.id}`);

			analytics.send();

			if (this.callWithLegacyMobile)
			{
				navigator.notification.alert(
					BX.message('MOBILE_CALL_NO_CAMERA_WITH_LEGACY_APP'),
					() => {},
					BX.message('MOBILE_CALL_ERROR'),
				);

				return;
			}

			if (cameraEnabled)
			{
				MediaDevices.requestCameraAccess().then(() => {
					this.currentCall.setVideoEnabled(cameraEnabled);
					this.callView.setCameraState(cameraEnabled); // should we update button state only if we received local video?
					this.changeProximitySensorStatus(this.canProximitySensorBeEnabled, null, true);
				}).catch(() => {
					navigator.notification.alert(
						BX.message('MOBILE_CALL_MICROPHONE_CAN_NOT_ACCESS_CAMERA'),
						() => {},
						BX.message('MOBILE_CALL_MICROPHONE_ACCESS_DENIED'),
					);
					this.changeProximitySensorStatus(this.canProximitySensorBeEnabled, null, false);
				});
			}
			else
			{
				this.currentCall.setVideoEnabled(cameraEnabled);
				this.callView.setCameraState(cameraEnabled); // should we update button state only if we received local video?
				this.changeProximitySensorStatus(this.canProximitySensorBeEnabled, null, false);
			}
		}

		onReplaceCameraClick()
		{
			if (!this.currentCall && !this.currentCall.videoEnabled)
			{
				return;
			}
			this.currentCall.switchCamera();
			if (Application.getPlatform() === 'android')
			{
				setTimeout(() => this.callView.setMirrorLocalVideo(this.currentCall.isFrontCameraUsed()), 1000);
			}
			else
			{
				this.callView.setHideLocalVideo(true);
				setTimeout(() => {
					this.callView.setMirrorLocalVideo(this.currentCall.isFrontCameraUsed());
					this.callView.setHideLocalVideo(false);
				}, 100);
			}
		}

		onChatButtonClick()
		{
			const analytics = new AnalyticsEvent()
				.setTool(Analytics.AnalyticsTool.im)
				.setCategory(Analytics.AnalyticsCategory.call)
				.setEvent(Analytics.AnalyticsEvent.clickChat)
				.setType(this.getCallType())
				.setSection(Analytics.AnalyticsSection.callWindow)
				.setP5(`callId_${this.currentCall.id}`);

			analytics.send();

			this.fold();
			if (this.currentCall)
			{
				BX.postComponentEvent('onOpenDialog', [{ dialogId: this.currentCall.associatedEntity.id }, true], 'im.recent');
				BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [{ dialogId: this.currentCall.associatedEntity.id }], 'im.messenger');
			}
		}

		onPrivateChatButtonClick(userId)
		{
			this.fold();
			BX.postComponentEvent('onOpenDialog', [{ dialogId: userId }, true], 'im.recent');
			BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [{ dialogId: userId }], 'im.messenger');
		}

		onFloorRequestButtonClick()
		{
			const analytics = new AnalyticsEvent()
				.setTool(Analytics.AnalyticsTool.im)
				.setCategory(Analytics.AnalyticsCategory.call)
				.setEvent(Analytics.AnalyticsEvent.handOn)
				.setType(this.getCallType())
				.setSection(Analytics.AnalyticsSection.callWindow)
				.setP5(`callId_${this.currentCall.id}`);

			analytics.send();

			const floorState = this.callView.getUserFloorRequestState(env.userId);
			const talkingState = this.callView.getUserTalking(env.userId);

			this.callView.setUserFloorRequestState(env.userId, !floorState);

			if (this.currentCall)
			{
				this.currentCall.requestFloor(!floorState);
			}

			clearTimeout(this.callViewFloorRequestTimeout);
			if (talkingState && !floorState)
			{
				this.callViewFloorRequestTimeout = setTimeout(() => {
					if (this.currentCall)
					{
						this.currentCall.requestFloor(false);
					}
				}, 1500);
			}
		}

		onAnswerButtonClick(useVideo)
		{
			CallUtil.log('onAnswerButtonClick');

			this.sendJoinCallAnalytics(
				Analytics.AnalyticsStatus.success,
				{
					video: useVideo,
					audio: this.currentCall.isMuted(),
				},
				Analytics.AnalyticsSection.callPopup,
				Analytics.AnalyticsElement.answerButton,
			);
			this.ignoreJoinAnalyticsEvent = true;

			this.answerCurrentCall(useVideo);
			if (this.nativeCall)
			{
				CallUtil.log('looks like the native call is not answered , calling answer');
				this.ignoreNativeCallAnswer = true;
				this.nativeCall.answer();
			}
		}

		onHangupButtonClick()
		{
			const analytics = new AnalyticsEvent()
				.setTool(Analytics.AnalyticsTool.im)
				.setCategory(Analytics.AnalyticsCategory.call)
				.setEvent(Analytics.AnalyticsEvent.disconnect)
				.setType(this.getCallType())
				.setSection(Analytics.AnalyticsSection.callWindow)
				.setSubSection(Analytics.AnalyticsSubSection.finishButton)
				.setStatus(Analytics.AnalyticsStatus.quit)
				.setP1(
					this.currentCall.isVideoEnabled()
						? Analytics.AnalyticsEvent.cameraOn
						: Analytics.AnalyticsEvent.cameraOff,
				)
				.setP2(
					this.currentCall.isMuted()
						? Analytics.AnalyticsEvent.micOff
						: Analytics.AnalyticsEvent.micOn,
				)
				.setP5(`callId_${this.currentCall.id}`);

			this.ignoreLeaveAnalyticsEvent = true;
			analytics.send();

			if (this.currentCall)
			{
				this.currentCall.hangup();
			}
			this.clearEverything();
		}

		onDeclineButtonClick()
		{
			this.sendJoinCallAnalytics(Analytics.AnalyticsStatus.decline);

			this.declineCall();
		}

		onToggleSubscriptionRemoteVideo(toggleList)
		{
			if (this.currentCall.toggleSubscriptionRemoteVideo)
			{
				this.currentCall.toggleSubscriptionRemoteVideo(toggleList);
			}
		}

		onSetCentralUser(userId)
		{
			if (this.currentCall.allowVideoFrom)
			{
				this.currentCall.allowVideoFrom([userId]);
			}
			if (this.currentCall.onCentralUserSwitch)
			{
				this.currentCall.onCentralUserSwitch(userId);
			}
		}

		onSelectAudioDevice(deviceName)
		{
			CallUtil.getSdkAudioManager().selectAudioDevice(deviceName);
			this.changeProximitySensorStatus(this.canProximitySensorBeEnabled, deviceName, null);

			this.callView.setSoundOutputDevice(deviceName);
		}

		onChangeStateCopilot(copilotEnabled)
		{
			if (!this.currentCall)
			{
				return;
			}
			const action = copilotEnabled ? 'call.Track.start' : 'call.Track.stop';
			BX.ajax.runAction(action, {
				data: { callId: this.currentCall.id },
			}).then(() => {
				const newCopilotState = !this.currentCall.isCopilotActive;
				this.onUpdateCallCopilotState({
					isTrackRecordOn: newCopilotState,
					senderId: env.userId,
				});

				if (newCopilotState)
				{
					this.sendStartCopilotRecordAnalytics();
				}
			}).catch((error) => {
				const errorCode = error.errors[0].code;

				this.sendStartCopilotRecordAnalytics(errorCode);
			});
		}

		onCallUserInvited(e)
		{
			if (this.callView)
			{
				this.callView.addUser(e.userId);
				CallUtil.getUsers(this.currentCall.id, [e.userId]).then(
					(userData) => this.callView.updateUserData(userData),
				);
			}
		}

		onCallUserStateChanged(userId, state, prevState, isLegacyMobile)
		{
			console.log('onCallUserStateChanged', userId, state);
			if (this.callView)
			{
				this.callView.setUserState(userId, state);
			}

			if (state === BX.Call.UserState.Connecting || state === BX.Call.UserState.Connected)
			{
				media.audioPlayer().stopPlayingSound();
			}

			if (state === BX.Call.UserState.Connected && !this.callStartTime)
			{
				this.callStartTime = Date.now();
				callInterface.indicator().setMode('active');
				this.startCallTimer();
			}

			if (state === BX.Call.UserState.Connected && this._nativeAnsweredAction)
			{
				this._nativeAnsweredAction.fullfill();
				this._nativeAnsweredAction = null;
			}

			if (isLegacyMobile)
			{
				this.callWithLegacyMobile = true;
			}
		}

		onCallUserMicrophoneState(userId, microphoneState)
		{
			if (this.callView)
			{
				this.callView.setUserMicrophoneState(userId, microphoneState);
			}
		}

		onCallUserScreenState(userId, screenState)
		{
			if (this.callView)
			{
				this.callView.setUserScreenState(userId, screenState);
			}
		}

		onCallUserVideoPaused(userId, videoPaused)
		{
			if (this.callView)
			{
				this.callView.setUserVideoPaused(userId, videoPaused);
			}
		}

		onCallUserVoiceStarted(userId)
		{
			if (this.callView)
			{
				this.callView.setUserTalking(userId, true);
				// this.callView.setUserFloorRequestState(userId, false); (is set in layout automatically to prevent racing condition
			}
		}

		onCallUserVoiceStopped(userId)
		{
			if (this.callView)
			{
				this.callView.setUserTalking(userId, false);
			}
		}

		onCallUserFloorRequest(userId, requestActive)
		{
			if (this.callView)
			{
				this.callView.setUserFloorRequestState(userId, requestActive);
			}
		}

		onCallUserEmotion()
		{}

		onCallLocalMediaReceived(localStream)
		{
			if (this.callView)
			{
				this.callView.setVideoStream(env.userId, localStream, this.currentCall.isFrontCameraUsed());
			}

			if (!(this.currentCall instanceof PlainCall))
			{
				this.stopLocalVideoStream();
			}
		}

		onCallLocalMediaStopped()
		{
			if (this.callView)
			{
				this.callView.setVideoStream(env.userId, null);
			}
		}

		onCallRTCStatsReceived()
		{}

		onCallCallFailure()
		{
			CallUtil.error('onCallFailure');
			this.clearEverything();
			navigator.notification.alert(BX.message('MOBILE_CALL_INTERNAL_ERROR').replace('#ERROR_CODE#', 'E003'));
		}

		onCallStreamReceived(userId, stream)
		{
			if (this.callView)
			{
				this.callView.setVideoStream(userId, stream);
			}
		}

		onCallStreamRemoved(userId)
		{
			if (this.callView)
			{
				this.callView.setVideoStream(userId, null);
			}
		}

		onChildCallFirstStream(userId, stream)
		{
			this.currentCall.log('Finishing one-to-one call, switching to group call');

			this.callView.setVideoStream(userId, stream);
			this.childCall.off(BX.Call.Event.onStreamReceived, this.onChildCallFirstStreamHandler);

			this.removeCallEvents();
			const oldCall = this.currentCall;

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.AnalyticsTool.im)
				.setCategory(Analytics.AnalyticsCategory.call)
				.setEvent(Analytics.AnalyticsEvent.finishCall)
				.setType(this.getCallType())
				.setSection(Analytics.AnalyticsSection.callWindow)
				.setStatus(Analytics.AnalyticsStatus.privateToGroup)
				.setP1(`callLength_${CallUtil.getTimeInSeconds(this.callStartTime)}`)
				.setP3(`maxUserCount_${this.getMaxActiveCallUsers().length}`)
				.setP4(`chatId_${this.normalizeChatId(this.currentCall.associatedEntity.id)}`)
				.setP5(`callId_${this.currentCall.id}`);

			analytics.send();

			oldCall.hangup();

			this.currentCall = this.childCall;
			this.childCall = null;

			if (oldCall.muted)
			{
				this.currentCall.setMuted(true);
			}

			this.bindCallEvents();
			// this.createVideoStrategy();
		}

		onCallTimeout()
		{
			this.sendJoinCallAnalytics(Analytics.AnalyticsStatus.noAnswer);

			this.declineCall();
		}

		declineCall()
		{
			if (this.currentCall)
			{
				this.currentCall.decline();
			}
			this.clearEverything();
		}

		onCallJoin(e)
		{
			if (!this.ignoreJoinAnalyticsEvent && !e.local)
			{
				this.sendJoinCallAnalytics(
					Analytics.AnalyticsStatus.success,
					{
						video: this.currentCall.isVideoEnabled(),
						audio: this.currentCall.isMuted(),
					},
					Analytics.AnalyticsSection.chatList,
					Analytics.AnalyticsElement.joinButton,
				);
			}
			this.ignoreJoinAnalyticsEvent = false;

			if (e.local)
			{
				CallUtil.warn('joined local call');

				return;
			}

			if (this.currentCall)
			{
				this.currentCall.hangup();
			}

			// remote answer, stop ringing and hide incoming cal notification
			this.clearEverything();
		}

		onCallLeave(e)
		{
			if (!this.getActiveCallUsers().length && !this.ignoreLeaveAnalyticsEvent)
			{
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.AnalyticsTool.im)
					.setCategory(Analytics.AnalyticsCategory.call)
					.setEvent(Analytics.AnalyticsEvent.finishCall)
					.setType(this.getCallType())
					.setSection(Analytics.AnalyticsSection.callWindow)
					.setStatus(Analytics.AnalyticsStatus.lastUserLeft)
					.setP1(`callLength_${CallUtil.getTimeInSeconds(this.callStartTime)}`)
					.setP3(`maxUserCount_${this.getMaxActiveCallUsers().length}`)
					.setP4(`chatId_${this.normalizeChatId(this.currentCall.associatedEntity.id)}`)
					.setP5(`callId_${this.currentCall.id}`);

				analytics.send();
			}
			this.ignoreLeaveAnalyticsEvent = false;

			if (!e.local && this.currentCall && this.currentCall.ready)
			{
				CallUtil.error(new Error('received remote leave with active call!'));

				return;
			}

			this.clearEverything();
		}

		onCallDestroy()
		{
			this.clearEverything();
		}

		onCallReconnected()
		{
			if (!this.currentCall)
			{
				return;
			}
			const muted = this.currentCall.isMuted();
			this.currentCall.setMuted(muted);
			this.callView.setMuted(muted);
			if (this.nativeCall)
			{
				this.nativeCall.mute(muted);
			}
		}

		onUpdateCallCopilotState({ isTrackRecordOn, senderId })
		{
			if (!this.currentCall)
			{
				return;
			}

			this.currentCall.isCopilotActive = isTrackRecordOn;
			this.callView.updateCopilotState(this.currentCall.isCopilotActive);
			if (isTrackRecordOn && senderId !== env.userId)
			{
				Tourist.ready()
					.then(() => {
						if (Tourist.rememberFirstTime('call_copilot_popup_shown'))
						{
							this.callView.toggleCopilotPopup(true);
						}
					})
					.catch(err => {
						console.error(err);
					});
			}
		}

		onNativeCallAnswered(nativeAction)
		{
			CallUtil.log('onNativeCallAnswered');

			if (this.nativeCall)
			{
				this._nativeAnsweredAction = nativeAction;

				if (!this.ignoreNativeCallAnswer)
				{
					// call view can be not initialized yet
					if (this.callViewPromise)
					{
						this.callViewPromise.then(() => {
							this.answerCurrentCall(this.nativeCall.params.video);
						});
					}
					else if (this.callView)
					{
						this.answerCurrentCall(this.nativeCall.params.video);
					}
					else
					{
						CallUtil.error('callView is not initialized');
					}
				}
			}
		}

		onNativeCallEnded(nativeAction)
		{
			if (this.nativeCall && this.nativeCall.connected)
			{
				this.onHangupButtonClick();
			}
			else
			{
				this.declineCall();
			}

			// todo: remove if (nativeAction) :)
			if (nativeAction)
			{
				setTimeout(() => nativeAction.fullfill(), 500);
			}
		}

		onNativeCallMuted(muted)
		{
			this.currentCall.setMuted(muted);
			this.callView.setMuted(muted);
		}

		onNativeCallVideoIntent()
		{
			setTimeout(() => this.onCameraButtonClick(), 1000);
		}

		getMaxActiveMicrophonesCount()
		{
			return 4;
		}

		canCallBeAnswered(call)
		{
			const isEnoughTimeAfterDecline = (this.callInviteTime + this.callDeclineTimeout) < Date.now();
			const isSwitchToGroupCall = this.currentCall && !this.currentCall.parentId && call.parentId;
			if (!this.callInviteTime || isEnoughTimeAfterDecline || isSwitchToGroupCall)
			{
				return true;
			}

			return false;
		}

		clearEverything()
		{
			if (this.currentCall)
			{
				this.currentCall.hangup();
				this.removeCallEvents();
				this.currentCall = null;
			}

			if (this._nativeAnsweredAction)
			{
				this._nativeAnsweredAction.fail();
				this._nativeAnsweredAction = null;
			}

			if (this.nativeCall)
			{
				this.nativeCall.finish();
				this.nativeCall = null;
			}
			this.ignoreNativeCallAnswer = false;

			if (uicomponent.widgetLayer())
			{
				uicomponent.widgetLayer().close().then(() => {
					this.rootWidget = null;
				});
			}
			else
			{
				this.rootWidget = null;
			}

			this.stopLocalVideoStream(true);
			this.callView = null;
			this.removeViewEvents();
			this.stopCheckOutputDevice();
			callInterface.indicator().close();
			this.callStartTime = null;
			this.callViewPromise = null;
			this.callAnswered = false;
			this.stopCallTimer();
			media.audioPlayer().stopPlayingSound();
			device.setIdleTimerDisabled(false);
			this.changeProximitySensorStatus(false);
			this.callWithLegacyMobile = false;
			this.callVideoEnabled = false;

			this.startCallPromise = null;
			this.localVideoPromise = null;

			BX.postComponentEvent('CallEvents::viewClosed', []);
			BX.postWebEvent('CallEvents::viewClosed', {});
		}

		requestDeviceAccess(withVideo)
		{
			return new Promise((resolve, reject) => {
				MediaDevices.requestMicrophoneAccess().then(
					() => {
						if (!withVideo)
						{
							return resolve();
						}
						MediaDevices.requestCameraAccess().then(
							() => resolve(),
						).catch(({ justDenied }) => reject(new DeviceAccessError(justDenied)));
					},
				).catch(({ justDenied }) => reject(new DeviceAccessError(justDenied)));
			});
		}

		normalizeChatId(chatId)
		{
			if (!chatId)
			{
				return 0;
			}

			if (chatId.includes('chat'))
			{
				chatId = chatId.replace('chat', '');
			}

			return chatId;
		}

		getAnalyticsUserType()
		{
			const userData = BX.componentParameters.get('currentUserData', {});
			const userTypes = {
				user: 'user_intranet',
				extranet: 'user_extranet',
				collaber: 'user_collaber',
			};

			return userTypes[userData.type] ?? userTypes.user;
		}

		changeProximitySensorStatus(status, audioDevice, isVideoEnabled)
		{
			this.canProximitySensorBeEnabled = status;
			if (!this.currentCall || this.isAppPaused)
			{
				device.setProximitySensorEnabled(false);
				return;
			}
			if (audioDevice == null)
			{
				audioDevice = CallUtil.getSdkAudioManager().currentDevice;
			}
			if (isVideoEnabled == null)
			{
				isVideoEnabled = this.currentCall.isVideoEnabled();
			}
			if (audioDevice != 'receiver' || isVideoEnabled)
			{
				device.setProximitySensorEnabled(false);
				return;
			}
			device.setProximitySensorEnabled(status);
		}

		async test(status = 'call', viewProps = {})
		{
			const users = [4, 5, 6, 7, 8, 9, 10, 11, 12 /*13, 14, 15 */, 464, 473];
			this.currentCall = new DummyCall();

			await this.openCallView({
				status,
				isGroupCall: true,
				isVideoCall: true,
				associatedEntityName: 'Very very long chat name. Very very long chat name. Very very long chat name. And again.',
				associatedEntityAvatar: '',
				...viewProps,
			});
			this.bindCallEvents();
			this.bindViewEvents();
			users.forEach((userId) => this.callView.addUser(userId, userId > 10 ? BX.Call.UserState.Connected : BX.Call.UserState.Idle));
			this.callView.unpinUser();
			users.forEach(userId => this.callView.setUserFloorRequestState(userId, true));

			this.currentCall.getLocalMedia();

			const response = await BX.rest.callMethod('im.user.list.get', {
				ID: users.concat(env.userId),
				AVATAR_HR: 'Y',
			});
			this.callView.updateUserData(response.data());

			const stream = await MediaDevices.getUserMedia({ audio: true, video: true });
			CallUtil.log('trying get video');
			CallUtil.log('video track received');
			CallUtil.log(stream.getTracks());
			this.stream = stream;
			this.callView.setVideoStream(env.userId, stream);
			this.callView.setVideoStream(4, stream);
			this.callView.setVideoStream(5, stream);
		}

		startCheckOutputDevice()
		{
			this.checkOutputDeviceInterval = setInterval(() => this.checkOutputDevice(), 1000);
		}

		stopCheckOutputDevice()
		{
			clearInterval(this.checkOutputDeviceInterval);
		}

		checkOutputDevice()
		{
			if (this.callView && this.callView.state.soundOutputDevice !== CallUtil.getSdkAudioManager().currentDevice)
			{
				this.callView.setSoundOutputDevice(CallUtil.getSdkAudioManager().currentDevice);
			}
		}

		testMenu(count = 5, withSeparators = false)
		{
			const items = [];
			for (let i = 0; i < count; i++)
			{
				items.push({
					text: `items ${i}`,
					iconClass: 'returnToSpeaker',
					onClick: () => CallUtil.log(`item ${i} click`),
				});
				if (withSeparators)
				{
					items.push({ separator: true });
				}
			}

			this.menu = new CallMenu({
				items: [
					{
						text: 'zxc iop',
						iconClass: 'participants',
						showSubMenu: true,
						color: '#FF0000',
						onClick: () => {
							CallUtil.log(456);
						},
					},
					{ separator: true },
					...items,
				],
				onClose: () => {
					CallUtil.log('test menu closed');
					this.menu && this.menu.destroy();
					uicomponent.widgetLayer() && uicomponent.widgetLayer().close();
				},
				onDestroy: () => {
					this.menu = null;
				},
			});

			this.openWidgetLayer().then(() => this.menu.show())
				.then(() => CallUtil.log('success'))
				.catch((e) => CallUtil.error(e));
		}

		testDevices()
		{
			const menuItems = CallUtil.getSdkAudioManager().availableAudioDevices.map((deviceAlias) => {
				return {
					text: deviceAlias,
					selected: deviceAlias === CallUtil.getSdkAudioManager().currentDevice,
					onClick: () => {
						if (this.menu)
						{
							this.menu.close();
						}
						CallUtil.getSdkAudioManager().selectAudioDevice(deviceAlias);
					},
				};
			});
			menuItems.push({
				separator: true,
			});
			menuItems.push({
				text: BX.message('MOBILE_CALL_MENU_CANCEL'),
				onClick: () => {
					if (this.menu)
					{
						this.menu.close();
					}
				},
			});

			CallUtil.log(menuItems);

			this.menu = new CallMenu({
				items: menuItems,
				onClose: () => this.menu.destroy(),
				onDestroy: () => {
					this.menu = null;
					uicomponent.widgetLayer().close();
				},
			});

			this.openWidgetLayer().then(() => this.menu.show())
				.then(() => CallUtil.log('success'))
				.catch((e) => CallUtil.error(e));
		}

		ttt()
		{
			CallUtil.log(`${pathToExtension}img/blank.png`);
			callInterface.indicator().setMode('active');
			callInterface.indicator().imageUrl = `${pathToExtension}img/blank.png`;
			callInterface.indicator().show();
		}
	}

	class DummyCall
	{
		videoEnabled = false;
		associatedEntity = {
			type: 'chat',
			advanced: {
				chat: {},
			},
		};

		constructor()
		{
			this.eventEmitter = new JNEventEmitter();
		}

		on(event, handler)
		{
			this.eventEmitter.on(event, handler);
			return this;
		}

		isVideoEnabled()
		{
			return this.videoEnabled;
		}

		setVideoEnabled(videoEnabled)
		{
			videoEnabled = (videoEnabled === true);
			if (this.videoEnabled === videoEnabled)
			{
				return;
			}

			this.videoEnabled = videoEnabled;

			if (this.videoEnabled)
			{
				if (this.localStream.getVideoTracks().length > 0)
				{
					MediaDevices.startCapture();
					this.eventEmitter.emit(BX.Call.Event.onLocalMediaReceived, [this.localStream]);
				}
			}
			else
			{
				MediaDevices.stopCapture();

				this.eventEmitter.emit(BX.Call.Event.onLocalMediaStopped);
			}
		}

		getLocalMedia()
		{
			return new Promise((resolve) => {
				MediaDevices.getUserMedia({ audio: true, video: this.videoEnabled }).then((stream) => {
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

		switchCamera()
		{
			MediaDevices.switchVideoSource();
		}

		isFrontCameraUsed()
		{
			return MediaDevices.cameraDirection === "front";
		}

		hangup() {}
	}

	if ('uicomponent' in window && uicomponent.widgetLayer())
	{
		uicomponent.widgetLayer().close();
	}

	module.exports = {
		CallController,
	};
});

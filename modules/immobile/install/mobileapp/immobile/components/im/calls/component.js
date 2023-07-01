/**
 * @bxjs_lang_path component.php
 */

// region Constants

(function ()
{
	const { CallsCardController } = jn.require('im/calls-card');

	const Sound = {
		incoming: "incoming",
		startCall: "startcall"
	}

	const TelephonyUiState = {
		INCOMING: "INCOMING",
		FINISHED: "FINISHED",
		STARTED: "STARTED",
		OUTGOING: "OUTGOING",
		WAITING: "WAITING",
		CALLBACK: "CALLBACK"
	}

	const TelephonyUiEvent = {
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
	}

	const eventTimeRange = 30;
	const crmPathTemplate = "mobile/crm/#ENTITY#/?page=view&#ENTITY#_id=#ID#";
	const entityTypes = ["lead", "contact", "company", "deal"];
	const inAppOpener = () => {
		try
		{
			const { inAppUrl } = jn.require('in-app-url');

			return inAppUrl;
		}
		catch (e)
		{
			console.log(e, 'In-app-url not found');

			return null;
		}
	};
// endregion Constants

// region Common functions
	function getSecondsAgo(timestamp)
	{
		const now = (new Date()).getTime();
		return Math.round(Math.abs(now - timestamp) / 1000);
	}

	function preparePush(push)
	{
		if (typeof (push) !== 'object' || typeof (push.params) === 'undefined')
		{
			return {'ACTION': 'NONE'};
		}

		let result = {};
		try
		{
			result = JSON.parse(push.params);
		} catch (e)
		{
			result = {'ACTION': push.params};
		}

		return result;
	}

	function getCrmShowPath(entityType, entityId)
	{
		entityType = entityType.toLowerCase();
		if (entityTypes.indexOf(entityType) === -1)
		{
			return "";
		}

		return currentDomain + BX.componentParameters.get("siteDir", "/") + crmPathTemplate.replace(/#ENTITY#/g, entityType).replace(/#ID#/g, entityId);
	}

	function decodeHtml(input)
	{
		input = input.toString()
			.replace(/&lt;/g, '<')
			.replace(/&gt;/g, '>')
			.replace(/&quot;/g, '"')
			.replace(/&amp;/g, '&')
			.replace(/&nbsp;/g, ' ')
			.replaceAll(/&#(\d+);/g, (_, code) => String.fromCharCode(code))
		return input;
	}

// endregion Common functions

	include("Calls");

	if (Application.getApiVersion() >= 36 && typeof media == "undefined")
	{
		include("media");
	}

// region Mobile telephony
	const isNewCallUI = Application.getApiVersion() > 48;

	class MobileTelephony
	{
		constructor()
		{
			this.ui = this.createUI();

			//from checkout
			this.userId = parseInt(BX.componentParameters.get('userId', 0));

			Object.defineProperties(this, {
				isAdmin: {
					get: () => BX.componentParameters.get('isAdmin', false)
				},
				voximplantInstalled: {
					get: () => BX.componentParameters.get('voximplantInstalled', false)
				},
				canPerformCalls: {
					get: () => BX.componentParameters.get('canPerformCalls', false)
				},
				lines: {
					get: () => BX.componentParameters.get('lines', {}),
					set: (value) => BX.componentParameters.set('lines', value)
				},
				defaultLineId: {
					get: () => BX.componentParameters.get('defaultLineId', ''),
					set: (lineId) => BX.componentParameters.set('defaultLineId', lineId)
				},
			})

			this._callId = null;
			Object.defineProperty(this, "callId", {
				get: () => this._callId,
				set: (newCallId) => {
					if (this._callId != newCallId)
					{
						this._callId = newCallId;
						BX.postComponentEvent("CallEvents::hasActiveTelephonyCall", [!!this._callId], "communication");
					}
				}
			})

			this.call = null;
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
			this.answered = false;
			this.debug = true;
			this.connected = false;
			this.authorized = false;
			this.ignoreAnswerSelf = false;
			this.isIncoming = false;
			this.isTransfer = false;
			this.isRestCall = false;
			this.formShown = false;
			this.portalCall = false;
			this.showName = true;

			Object.defineProperty(this, "nativeCall", {
				get: () => {
					if ("callservice" in window)
					{
						const call = callservice.currentCall();
						return call && call.params.type === 'telephony' ? call :null;
					}
					else
					{
						return null;
					}
				}
			})

			// event handlers
			this._onNativeCallAnsweredHandler = this._onNativeCallAnswered.bind(this);
			this._onNativeCallEndedHandler = this._onNativeCallEnded.bind(this);
			this._onNativeCallMutedHandler = this._onNativeCallMuted.bind(this);
			this.init();
		}

		createUI()
		{
			if (isNewCallUI)
			{
				return  new CallsCardController({
					onUiEvent: this.onUiEvent.bind(this)
				})
			}

			return new VoiceCallForm({
				eventListener: this.onUiEvent.bind(this)
			});
		}

		/**
		 * @typedef {Object} params
		 * VoiceCallForm properties
		 * @property {?String} headerLabels.firstHeader.text - phoneNumber / crmContactName
		 * @property {?String} headerLabels.firstHeader.textColor
		 * @property {?String} headerLabels.firstSmallHeader.text - example IM_PHONE_CALL_TO_PHONE
		 * @property {?String} headerLabels.firstSmallHeader.textColor
		 * @property {?String} headerLabels.secondSmallHeader.text - crmCompanyName
		 * @property {?String} headerLabels.secondSmallHeader.textColor
		 * @property {?String} headerLabels.thirdSmallHeader.text - record text
		 * @property {?String} headerLabels.thirdSmallHeader.textColor
		 *
		 * @property {?String} middleLabels.infoTitle.text
		 * @property {?String} middleLabels.infoHeader.text - stage name
		 * @property {?String} middleLabels.infoHeader.textColor - stage color
		 * @property {?String} middleLabels.infoDesc.text - deal title
		 * @property {?String} middleLabels.infoSum.text - deal opportunity
		 * @property {?String} middleLabels.imageStub.backgroundColor
		 * @property {?String} middleLabels.imageStub.display
		 *
		 * @property {?Object} middleButtons - links to crm entities
		 * @property {?{text: string, textColor: string, eventName: string, sort: number}} middleButtons.buttons
		 *
		 * @property {?String} footerLabels.callStateLabel.text - state info / error text
		 * @property {?String} avatarUrl
		 * @property {?String} state - see TelephonyUiState
		 *
		 * CallsCardController properties
		 * @property {?String} crmContactName
		 * @property {?String} phoneNumber
		 * @property {?String} status
		 * @property {?String} statusColor
		 * @property {?String} errorText
		 * @property {?String} type - see TelephonyUiState
		 * @property {?String} avatarUrl
		 * @property {?String} recordText
		 * @property {?Function} onUiEvent
		 *
		 * @property {?Object} crmData
		 * @property {?String} crmData.TITLE
		 * @property {?String} crmData.REPEATED_TEXT
		 * @property {?number} crmData.OPPORTUNITY_VALUE
		 * @property {?String} crmData.CURRENCY_ID
		 * @property {?String} crmData.STAGE_COLOR
		 * @property {?String} crmData.STAGE
		 * @property {?Number} crmData.CREATED_TIME
		 *
		 * @property {?Object} crmStatus
		 *
		 * @property {?String} crmData.openLeadEventName
		 * @property {?String} crmData.createLeadEventName
		 * @property {?String} crmData.openDealEventName
		 * @property {?String} crmData.createDealEventName
		 * @property {?String} crmData.openContactEventName
		 * @property {?String} crmData.createContactEventName
		 * @property {?String} crmData.openCompanyEventName
		 * @property {?String} crmData.createCompanyEventName
 		 */
		updateUI(params)
		{
			if (!this.formShown)
			{
				return;
			}

			const {
				headerLabels,
				middleLabels,
				middleButtons,
				footerLabels,
				state,
				...restParams
			} = params;

			if (isNewCallUI)
			{
				this.ui.update({...restParams});
			}
			else
			{
				if (headerLabels)
				{
					this.ui.updateHeader({headerLabels});
				}

				if (restParams.avatarUrl)
				{
					this.ui.updateHeader({avatarUrl: restParams.avatarUrl});
				}

				if (middleLabels && middleButtons)
				{
					this.ui.updateMiddle({middleLabels});
				}

				if (middleButtons)
				{
					this.ui.updateMiddle({middleButtons});
				}

				if (footerLabels)
				{
					this.ui.updateFooter({footerLabels});
				}

				if (state)
				{
					this.ui.updateFooter({state});
				}
			}
		}

		init()
		{
			// VIClient.getInstance().on(VIClient.Events.LogMessage, (m) => console.log(m));

			BX.addCustomEvent("onPhoneTo", this.onPhoneTo.bind(this));
			BX.addCustomEvent("onNumpadRequestShow", this.onNumpadRequestShow.bind(this));
			BX.addCustomEvent("onPullEvent-voximplant", this.onPullEvent.bind(this));
			BX.addCustomEvent("onAppActive", this.onAppActive.bind(this));

			VIClient.getInstance().on(VIClient.Events.IncomingCall, this._onIncomingCall.bind(this))

			this._onNativeIncomingCallHandler = this._onNativeIncomingCall.bind(this);
			if ("callservice" in window)
			{
				callservice.on("incoming", this._onNativeIncomingCallHandler);
				if (this.nativeCall)
				{
					setTimeout(() => this._onNativeIncomingCall(this.nativeCall), 0);
				}
			}

			this.onAppActive();
		}

		onAppActive()
		{
			let push = preparePush(Application.getLastNotification());
			if (BX.type.isNotEmptyString(push['ACTION']) && push['ACTION'].indexOf('VI_CALL_') === 0)
			{
				this.log("Starting application from push", push);
				let params = push.PARAMS || {};
				if (params.callId)
				{
					params.autoAnswer = true;
					this._onCallInvite(params);
				}
			}
		}

		onPhoneTo(e)
		{
			this.log("onPhoneTo", e);
			let number = e.number;
			let params = e.params;

			params = params || {};
			if (typeof (params) != 'object')
			{
				try
				{ params = JSON.parse(params); } catch (e)
				{ params = {}; }
			}

			if (this.canUseTelephony())
			{
				this.phoneCall(number, params);
			}
		}

		onNumpadRequestShow()
		{
			this.log("onNumpadRequestShow");

			if (Application.getApiVersion() >= 22)
			{
				if (this.canUseTelephony())
				{
					this.ui.showNumpad();
				}
			}
		}

		canUseTelephony()
		{
			return this.voximplantInstalled && this.canPerformCalls;
		}

		onUiEvent(params)
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

			if (BX.type.isFunction(handlers[eventName]))
			{
				handlers[eventName].call(this, params);
			}
			else if (eventName.substring(0, 4) == "crm_")
			{
				let crmParams = eventName.split("_");
				this._onUiCrmLinkClick({
					'entityType': crmParams[1],
					'entityId': crmParams[2]
				});
			}
			else if (eventName.substring(0, 12) === "create_deal_")
			{
				const crmParams = eventName.split("_");
				this._onUiCrmCreateDealLinkClick(crmParams[1], crmParams[2]);
			}
			else if (eventName.substring(0, 16) === "create_by_phone_")
			{
				const crmParams = eventName.split("_");
				this._onUiCrmCreateByPhoneLinkClick(crmParams[3], crmParams[4], crmParams[5]);
			}
		}

		_onUiCrmCreateDealLinkClick(entityType, contactId)
		{
			const crmUrl = getCrmShowPath(entityType, 0);
			if(!crmUrl)
			{
				return;
			}
			const inAppUrl = inAppOpener();

			if (Application.getApiVersion() >= 45 && inAppUrl)
			{
				inAppUrl.open(
					`/crm/${entityType}/details/0/?contact_id=${contactId}`,
					{
						categoryId: 0,
						canOpenInDefault: true,
						bx24ModernStyle: true,
						linkText: BX.message(`IM_M_CALL_NEW_${entityType.toUpperCase()}`),
					},
					() => {
						this._onOpenPage(crmUrl);
					}
				);
			}

			this.ui.rollUp();
		}

		_onUiCrmCreateByPhoneLinkClick(entityType, phone, originId)
		{
			const crmUrl = getCrmShowPath(entityType, 0);
			if(!crmUrl)
			{
				return;
			}

			const inAppUrl = inAppOpener();
			if (Application.getApiVersion() >= 45 && inAppUrl)
			{
				inAppUrl.open(
					`/crm/${entityType}/details/0/?phone=${phone}&page=edit&origin_id=${this.crmData.ORIGIN_ID}`,
					{
						categoryId: 0,
						canOpenInDefault: true,
						bx24ModernStyle: true,
						linkText: BX.message(`IM_M_CALL_NEW_${entityType.toUpperCase()}`),
					},
					() => {
						this._onOpenPage(crmUrl);
					}
				);
			}

			this.ui.rollUp();
		}

		onPullEvent(command, params, extra)
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
		}

		requestMicrophoneAccess()
		{
			return new Promise((resolve, reject) =>
			{
				MediaDevices.requestMicrophoneAccess()
					.then(() => resolve())
					.catch(({justDenied}) => reject(new DeviceAccessError(justDenied)));
			});
		}

		answerCall()
		{
			if (this.answered)
			{
				return;
			}
			this.answered = true;
			this.updateUI({
				state: TelephonyUiState.WAITING,
				footerLabels: {
					callStateLabel: {
						text: BX.message('IM_M_CALL_ST_CONNECT'),
					},
				},

				type: TelephonyUiState.WAITING,
				status: BX.message('IM_M_CALL_ST_CONNECT'),
			});

			TelephonySignaling.sendAnswer((this.callId)).catch(
				(data) =>
				{
					let answer = data.answer;
					let statusLabel = null;

					this.log('voximplant.call.answer error: ', answer);

					// call could be already finished by this moment
					if (!this.callInit && !this.callActive)
					{
						return;
					}

					this.ui.stopSound();
					if (answer.error === 'ERROR_NOT_FOUND')
					{
						statusLabel = BX.message("IM_M_CALL_ALREADY_FINISHED");
					}
					else if (answer.error === 'ERROR_WRONG_STATE')
					{
						statusLabel = BX.message("IM_M_CALL_ALREADY_ANSWERED");
					}
					else
					{
						statusLabel = BX.message("IM_PHONE_ERROR");
					}

					this.updateUI({
						footerLabels: {
							callStateLabel: {
								text: statusLabel,
							},
						},
						state: TelephonyUiState.FINISHED,

						type: TelephonyUiState.FINISHED,
						error: statusLabel,
					});
				}
			).then(
				() => VIClientWrapper.getClient()
			).then(
				() =>
				{
					CallUtil.log("voximplant.call.sendReady")
					BX.rest.callMethod('voximplant.call.sendReady', {'CALL_ID': this.callId})
				}
			);
		}

		phoneCall(number, params)
		{
			if (!this.canUseTelephony())
			{
				return false;
			}

			if (!CallUtil.isDeviceSupported())
			{
				navigator.notification.alert(BX.message("MOBILE_CALL_UNSUPPORTED_VERSION"));
				return;
			}

			this.log("phoneCall", number, params);
			if (typeof (fabric) === "object")
			{
				fabric.Answers.sendCustomEvent("outgoingCallTelephony", {});
			}

			let correctNumber = this.phoneCorrect(number);

			if (!BX.type.isPlainObject(params))
			{
				params = {};
			}

			if (correctNumber.length <= 0)
			{
				//this.BXIM.openConfirm({title: BX.message('IM_PHONE_WRONG_NUMBER'), message: BX.message('IM_PHONE_WRONG_NUMBER_DESC')});
				this.log("Wrong number");
				return false;
			}

			if (this.callActive || this.callInit)
			{
				return false;
			}

			this.ui.playSound({soundId: Sound.startCall});

			if (this.isRestLine(this.defaultLineId))
			{
				this.startCallViaRest(number, this.defaultLineId, params);
				return;
			}

			this.callInit = true;
			this.callActive = false;
			this.phoneNumber = correctNumber;
			this.phoneFullNumber = correctNumber;

			if (params['NAME'])
			{
				this.crmData = {
					CONTACT: {
						NAME: params['NAME'],
					},
				};
			}

			if (typeof params['SHOW_NAME'] === 'boolean')
			{
				this.showName = params['SHOW_NAME'];
			}

			this.phoneParams = params;
			var matches = /(\+?\d+)([;#]*)([\d,]*)/.exec(correctNumber);
			if (matches)
			{
				this.phoneNumber = matches[1];
			}

			if (this.phoneFullNumber != this.phoneNumber)
			{
				this.phoneParams['FULL_NUMBER'] = this.phoneFullNumber;
			}

			this.showCallForm({
				status: BX.message('IM_M_CALL_ST_CONNECT'),
				state: TelephonyUiState.OUTGOING
			});

			this.requestMicrophoneAccess().then(() =>
			{
				return VIClientWrapper.getClient();
			}).then(client =>
			{
				this.call = client.call(this.phoneNumber, this.phoneParams);

				this.call.on(JNVICall.Events.Connected, this._onCallConnected.bind(this));
				this.call.on(JNVICall.Events.Disconnected, this._onCallDisconnected.bind(this));
				this.call.on(JNVICall.Events.Failed, this._onCallFailed.bind(this));
				this.call.on(JNVICall.Events.Ringing, this._onCallProgressToneStart.bind(this));
				this.call.start();
			}).catch((error) =>
			{
				console.error(error);
				let stateLabel = null;

				if (error instanceof DeviceAccessError)
				{
					stateLabel = BX.message("IM_PHONE_ERR_NO_MIC");

					CallUtil.showDeviceAccessConfirm(false, () => Application.openSettings());
				}
				else
				{
					stateLabel = BX.message("MOBILEAPP_SOME_ERROR");
				}

				this.updateUI({
					state: TelephonyUiState.FINISHED,
					footerLabels: {
						callStateLabel: {
							text: stateLabel,
						},
					},

					type: TelephonyUiState.FINISHED,
					error: stateLabel,
				});

				this.finishCall();
			});
		}

		startCallViaRest(number, lineId, params)
		{
			let appName = this.getRestAppName(lineId);
			this.isRestCall = true;
			this.phoneNumber = number;
			this.showCallForm({
				status: BX.message('IM_PHONE_OUTGOING_REST').replace('#APP_NAME#', appName),
				state: TelephonyUiState.FINISHED
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
				if (data.DATA && data.DATA.CRM)
				{
					this.setCrmData(data.DATA.CRM);
				}
			});
		}

		finishCall()
		{
			if (this.call)
			{
				this.call.hangup();
				this.call = null;
			}

			if (this.nativeCall)
			{
				this.nativeCall.finish();
			}

			this.callId = null;
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
			this.answered = false;
			this.ignoreAnswerSelf = false;
			this.isIncoming = false;
			this.isTransfer = false;
			this.isRestCall = false;
			this.portalCall = false;
			this.showName = true;

			this.ui.pauseTimer();
			device.setProximitySensorEnabled(false);
		}

		sendSkip()
		{
			if (this.callInit && this.isIncoming)
			{
				BX.rest.callMethod('voximplant.call.skip', {'CALL_ID': this.callId});
			}
		}

		setCallHold(holdState)
		{
			if (!this.call)
			{
				return false;
			}

			this.call.sendMessage(JSON.stringify({'COMMAND': (holdState ? 'hold' : 'unhold')}));
		}

		getUiFields()
		{
			let headerLabels = {};
			let middleLabels = {};
			let middleButtons = {};
			let avatarUrl = '';
			const crmData = {};
			let crmContactName = null;
			let crmCompanyName = null;
			let recordText = null;
			let crmStatus = null;

			if (this.crmData.FOUND == 'Y')
			{
				crmContactName = this.crmData.CONTACT && this.crmData.CONTACT.NAME ? this.crmData.CONTACT.NAME : '';
				let crmContactPhoto = this.crmData.CONTACT && this.crmData.CONTACT.PHOTO ? this.crmData.CONTACT.PHOTO : '';
				let crmContactPost = this.crmData.CONTACT && this.crmData.CONTACT.POST ? this.crmData.CONTACT.POST : '';
				crmCompanyName = this.crmData.COMPANY ? this.crmData.COMPANY : '';
				crmStatus = {
					text: this.crmData.STATUS_TEXT || null,
					color: this.crmData.STATUS_COLOR || null,
				}

				if (!this.portalCall && !this.isRestCall && this.callConfig.hasOwnProperty('RECORDING'))
				{
					if (this.callConfig.RECORDING == "Y")
					{
						headerLabels.thirdSmallHeader = {'text': BX.message('IM_PHONE_REC_ON'), textColor: "#ecd748"};
						recordText = BX.message('IM_PHONE_REC_ON');
					}
					else
					{
						headerLabels.thirdSmallHeader = {'text': BX.message('IM_PHONE_REC_OFF'), textColor: "#ee423f"};
						recordText = BX.message('IM_PHONE_REC_OFF');
					}
				}

				if (crmContactName)
				{
					headerLabels.firstHeader = {'text': crmContactName};
				}
				if (crmContactPost)
				{
					headerLabels.firstSmallHeader = {'text': crmContactPost};
				}
				if (crmCompanyName)
				{
					headerLabels.secondSmallHeader = {'text': crmCompanyName};
				}

				avatarUrl = "";
				if (!CallUtil.isAvatarBlank(crmContactPhoto))
				{
					if (crmContactPhoto.startsWith("http"))
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
					crmData.deal = this.crmData.DEALS[0];

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
						crmData.openDealEventName = "crm_deal_" + this.crmData.DEALS[0].ID;
						middleButtons['button1'] = {
							text: BX.message('IM_PHONE_ACTION_T_DEAL'),
							sort: 100,
							eventName: "crm_deal_" + this.crmData.DEALS[0].ID
						};
					}
				}
				else if (this.crmData.LEAD && this.crmData.LEAD.ID)
				{
					crmData.lead = this.crmData.LEAD ? this.crmData.LEAD : null;
					crmData.openLeadEventName = "crm_lead_" + this.crmData.LEAD.ID;
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
							crmData.openContactEventName = "crm_contact_" + this.crmData[type].ID;

							middleButtons['buttonData' + i] = {
								text: BX.message('IM_PHONE_ACTION_T_CONTACT'),
								sort: 200 + i,
								eventName: "crm_contact_" + this.crmData[type].ID
							};
						}
						else if (type == 'COMPANY_DATA')
						{
							crmData.openCompanyEventName = "crm_company_" + this.crmData[type].ID;

							middleButtons['buttonData' + i] = {
								text: BX.message('IM_PHONE_ACTION_T_COMPANY'),
								sort: 200 + i,
								eventName: "crm_company_" + this.crmData[type].ID
							};
						}
						else if (type == 'LEAD_DATA')
						{
							crmData.openLeadEventName = "crm_lead_" + this.crmData[type].ID;

							middleButtons['buttonData' + i] = {
								text: BX.message('IM_PHONE_ACTION_T_LEAD'),
								sort: 200 + i,
								eventName: "crm_lead_" + this.crmData[type].ID
							};
						}
					}
				}
				if (this.portalCall)
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

				if (isNewCallUI)
				{
					if (this.crmData.CONTACT && this.crmData.CONTACT.NAME)
					{
						crmContactName = this.crmData.CONTACT.NAME;
					}

					if (this.crmData.COMPANY && this.crmData.COMPANY.NAME)
					{
						crmCompanyName = this.crmData.COMPANY.NAME;
					}
				}
				else
				{
					crmContactName = phoneNumber;
				}


				headerLabels.firstSmallHeader = {};
				if (this.isIncoming)
				{
					headerLabels.firstSmallHeader.text = this.lineNumber ? BX.message('IM_PHONE_CALL_TO_PHONE').replace('#PHONE#', this.lineNumber) : BX.message('IM_VI_CALL');
				}
				else
				{
					headerLabels.firstSmallHeader.text = BX.message('IM_PHONE_OUTGOING');
				}
				headerLabels.firstSmallHeader.textColor = "#959ca4";

				if (!this.portalCall && !this.isRestCall && this.callConfig.hasOwnProperty('RECORDING'))
				{
					if (this.callConfig.RECORDING == "Y")
					{
						headerLabels.thirdSmallHeader = {text: BX.message('IM_PHONE_REC_ON'), textColor: "#ecd748"};
						recordText = BX.message('IM_PHONE_REC_ON');
					}
					else
					{
						headerLabels.thirdSmallHeader = {text: BX.message('IM_PHONE_REC_OFF'), textColor: "#ee423f"};
						recordText = BX.message('IM_PHONE_REC_OFF');
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

			if (this.crmData.LEAD_URL && this.crmData.CAN_CREATE_LEAD)
			{
				crmData.createLeadEventName = 'create_by_phone_lead_' + this.phoneNumber;
			}

			if (this.crmData.CONTACT_URL)
			{
				crmData.createContactEventName = 'create_by_phone_contact_' + this.phoneNumber;
			}

			if (this.crmData.DEAL_URL && this.crmData.CONTACT_DATA)
			{
				crmData.createDealEventName = 'create_deal_' + this.crmData.CONTACT_DATA.ID;
			}


			return {
				headerLabels,
				middleLabels,
				middleButtons,
				avatarUrl,
				phoneNumber: this.phoneNumber && this.phoneNumber.toString(),
				crmData,
				crmContactName,
				crmCompanyName,
				recordText,
				crmStatus,
				showName: this.showName,
			};
		}

		showCallForm(params)
		{
			let callFormParams = this.getUiFields();

			if (params.status)
			{
				callFormParams.status = params.status;

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
				callFormParams.type = params.state;
			}

			this.log('callFormParams: ', callFormParams);
			this.ui.show(callFormParams);
			this.formShown = true;

			device.setProximitySensorEnabled(true);
			device.setIdleTimerDisabled(true);
		}

		closeCallForm()
		{
			this.ui.closeNumpad();
			this.ui.close();
			this.formShown = false;

			device.setProximitySensorEnabled(false);
			device.setIdleTimerDisabled(false);
		}

		setCrmData(crmData)
		{
			if (!BX.type.isPlainObject(crmData))
			{
				crmData = {FOUND: 'N'};
			}

			this.crmData = crmData;
			if (this.crmData.FOUND === 'Y')
			{
				this.updateUI(this.getUiFields());
			}
		}

		setProgress(progress, stateLabel = '')
		{
			if (progress == 'connect')
			{
			}
			else if (progress == 'wait')
			{
				this.updateUI({
					footerLabels: {
						callStateLabel: {
							text: stateLabel,
						},
					},
					state: TelephonyUiState.WAITING,
					type: TelephonyUiState.WAITING,
					error: stateLabel,
				});
			}
			else if (progress == 'online')
			{
				let headerLabels = {
					thirdSmallHeader: {
						text: ''
					},
				};
				if (!this.portalCall && this.callConfig.hasOwnProperty('RECORDING'))
				{
					if (this.callConfig.RECORDING == "Y")
					{
						if (!isNewCallUI)
						{
							headerLabels.thirdSmallHeader = {'text': BX.message('IM_PHONE_REC_NOW'), textColor: "#7fc62c"};
						}
					}
					else
					{
						headerLabels.thirdSmallHeader = {'text': BX.message('IM_PHONE_REC_OFF'), textColor: "#ee423f"};
					}
				}

				this.updateUI({
					headerLabels,
					footerLabels: {
						callStateLabel: {
							text: stateLabel,
						},
					},
					status: stateLabel,
					state: TelephonyUiState.STARTED,
					type: TelephonyUiState.STARTED,
					recordText: headerLabels.thirdSmallHeader.text,
				});
			}
			else if (progress == 'offline' || progress == 'error')
			{
				let headerLabels = {};
				let footerLabels = {
					callStateLabel: {
						text: stateLabel,
					},
				};
				let recordText = null;

				if (progress == 'offline')
				{
					if (!this.portalCall)
					{
						if (this.callConfig.RECORDING == "Y" && this.phoneCallTime > 0)
						{
							headerLabels.thirdSmallHeader = {
								'text': BX.message('IM_PHONE_REC_DONE'),
								textColor: "#7fc62c"
							};
							recordText = BX.message('IM_PHONE_REC_DONE');
						}
						else
						{
							headerLabels.thirdSmallHeader = {'text': ''};
						}

						let footerLabels = {};
						if (this.crmData.LEAD_DATA && !this.crmData.CONTACT_DATA && !this.crmData.COMPANY_DATA && this.callConfig.CRM_CREATE == 'lead')
						{
							footerLabels.actionDoneHint = {'text': BX.message('IM_PHONE_LEAD_SAVED')};
						}
						else
						{
							footerLabels.actionDoneHint = {'text': ''};
						}
					}
				}
				else
				{
					headerLabels.thirdSmallHeader = {'text': ''};
					footerLabels.actionDoneHint = {'text': ''};
				}

				this.updateUI({
					headerLabels,
					footerLabels,
					state: TelephonyUiState.FINISHED,
					type: TelephonyUiState.FINISHED,
					recordText,
					error: stateLabel,
				});
				if (this.formShown)
				{
					this.ui.expand();
				}
			}
		}

		toggleMute(muted)
		{
			if (!this.call)
			{
				return;
			}

			this.call.sendAudio = !muted;
		}

		isRestLine(lineId)
		{
			return (this.lines.hasOwnProperty(lineId) && this.lines[lineId]['TYPE'] === 'REST');
		}

		getRestAppName(lineId)
		{
			if (!this.lines.hasOwnProperty(lineId))
			{
				return '';
			}

			if (this.lines[lineId]['TYPE'] !== 'REST')
			{
				return '';
			}

			let lineName = this.lines[lineId]['FULL_NAME'];
			return lineName.substr(lineName.indexOf(':') + 2);
		}

		_onNativeIncomingCall(nativeCall)
		{
			if (nativeCall.params.type !== 'telephony')
			{
				return;
			}
			let timestamp = nativeCall.params.ts;
			let timeAgo = (new Date()).getTime() / 1000 - timestamp;
			if (timeAgo > 15)
			{
				CallUtil.error("Call originated too long time ago");
			}

			this._onCallInvite(nativeCall.params)
			this._bindNativeCallEvents(nativeCall);

			if (Application.isBackground())
			{
				CallUtil.log("Application in background, starting p&p")
				CallUtil.forceBackgroundConnectPull(15).then(() =>
				{
					CallUtil.log("p&p connected")
				}).catch((error) =>
				{
					CallUtil.error(error)
				})
			}
			else
			{
				CallUtil.log("Received native call in foreground", nativeCall.params)
			}
		}

		_bindNativeCallEvents(nativeCall)
		{
			nativeCall
				.on("answered", this._onNativeCallAnsweredHandler)
				.on("ended", this._onNativeCallEndedHandler)
				.on("muted", this._onNativeCallMutedHandler)
				// .on("videointent", this.onNativeCallVideoIntentHandler)
		}

		_onIncomingCall(call)
		{
			this.log("_onIncomingCall", call);
			if (this.call)
			{
				this.log('call already exists');
				return;
			}

			if (!("on" in call))
			{
				this.sendSkip();
				this.finishCall();
				navigator.notification.alert(BX.message("MOBILE_CALL_UNSUPPORTED_VERSION"));
			}

			this.call = call;

			this.call.on(JNVICall.Events.Connected, this._onCallConnected.bind(this));
			this.call.on(JNVICall.Events.Disconnected, this._onCallDisconnected.bind(this));
			this.call.on(JNVICall.Events.Failed, this._onCallFailed.bind(this));

			this.requestMicrophoneAccess().then(() =>
			{
				return this.call.answer();
			}).catch((error) =>
			{
				console.error(error);
				let stateLabel = null;
				if (error instanceof DeviceAccessError)
				{
					stateLabel = BX.message("IM_PHONE_ERR_NO_MIC");
					CallUtil.showDeviceAccessConfirm(false, () => Application.openSettings());
				}
				else
				{
					stateLabel = BX.message("MOBILEAPP_SOME_ERROR");
				}

				this.updateUI({
					footerLabels: {
						callStateLabel: {
							text: stateLabel,
						},
					},
					state: TelephonyUiState.FINISHED,
					type: TelephonyUiState.FINISHED,
					error: stateLabel,
				});
				this.finishCall();
			});
		}

		_onConnectionEstablished(e)
		{
			this.log("_onConnectionEstablished", e);
			CallUtil.log("_onConnectionEstablished", e);
			this.connected = true;
		}

		_onConnectionClosed(e)
		{
			this.log("_onConnectionClosed", e);

			this.connected = false;
			this.authorized = false;
			if (this.callInit || this.callActive)
			{
				this.setProgress('error', BX.message('IM_M_CALL_ERR'));
				this.finishCall();
			}
		}

		_onConnectionFailed(e)
		{
			this.log("_onConnectionFailed", e);
			this.connected = false;
			this.authorized = false;

			if (this.callInit || this.callActive)
			{
				this.setProgress('error', BX.message('IM_M_CALL_ERR'));
				this.finishCall();
			}
		}

		_onCallConnected(e)
		{
			this.log("_onCallConnected", e);

			const status = isNewCallUI ? '' : BX.message('IM_M_CALL_ST_ONLINE')
			this.setProgress('online', status);
			this.callActive = true;
		}

		_onCallDisconnected(e)
		{
			this.log("_onCallDisconnected", e);
			this.finishCall();
			this.closeCallForm();
		}

		_onCallFailed(e)
		{
			this.log("_onCallFailed", e);

			let status = null;
			if (isNewCallUI)
			{
				status =  BX.message('IM_PHONE_END');
			}
			let errorText = isNewCallUI ? '' : BX.message('IM_PHONE_END');
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
				errorText = BX.message('IM_PHONE_NO_MONEY') + (this.isAdmin ? ' ' + BX.message('IM_PHONE_PAY_URL_NEW') : '');
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

			this.updateUI({
				state: TelephonyUiState.FINISHED,
				footerLabels: {
					callStateLabel: {
						text: errorText,
					},
				},

				errorText,
				type: TelephonyUiState.FINISHED,
				status,
			});

			setTimeout(() => {
				this.closeCallForm();
			}, 3000);
		}

		_onCallProgressToneStart(e)
		{
			this.log("_onCallProgressToneStart", e);
			this.phoneRinging++;
			this.updateUI({
				state: TelephonyUiState.FINISHED,
				type: TelephonyUiState.WAITING,
			});
		}

		_onCallProgressToneStop(e)
		{
			this.log("_onCallProgressToneStop", e);
		}

		_onUiHangup(e)
		{
			this.ui.cancelDelayedClosing();
			this.finishCall();
			this.closeCallForm();
		}

		_onUiSpeakerphoneChanged(e)
		{
			let params = e.params;
			let speakerState = params.selected;
			if (!this.call)
			{
				return false;
			}

			//todo
			//this.call.setUseLoudSpeaker(speakerState);
			if (isNewCallUI)
			{
				this.selectAudioDevice(speakerState);
			}
		}

		selectAudioDevice(speakerState)
		{
			if (speakerState)
			{
				JNVIAudioManager.selectAudioDevice("speaker");
			}
			else
			{
				JNVIAudioManager.selectAudioDevice("receiver");
			}
		}

		_onUiMuteChanged(e)
		{
			CallUtil.log("_onUiMuteChanged", e);
			let params = e.params;
			let micState = params.selected;
			this.toggleMute(micState);
			if (this.nativeCall)
			{
				this.nativeCall.mute(micState);
			}
		}

		_onUiPauseChanged(e)
		{
			let params = e.params;
			let holdState = params.selected;
			this.setCallHold(holdState);
		}

		_onUiCloseClicked(e)
		{
			this.finishCall();
			this.formShown = false;
		}

		_onUiSkipClicked(e)
		{
			this.sendSkip();
			this.finishCall();
			this.closeCallForm();
		}

		_onUiAnswerClicked()
		{
			this.ignoreAnswerSelf = true;
			this.ui.stopSound();
			this.answerCall();
			if (this.nativeCall)
			{
				this.nativeCall.answer()
			}
		}

		_onUiNumpadButtonClicked(e)
		{
			let key = e.params;

			if (this.call)
			{
				this.call.sendDTMF(key);
			}
		}

		_onUiPhoneNumberReceived(data)
		{
			if (Application.getApiVersion() >= 22)
			{
				var number = data.params;
				this.phoneCall(number);
			}
		}

		_onUiCrmLinkClick(params)
		{
			let entityType = params.entityType;
			let entityId = params.entityId;
			let crmUrl = getCrmShowPath(entityType, entityId);


			if(!crmUrl) {
				return;
			}

			if (Application.getApiVersion() >= 45)
			{
				if (typeof BX.MobileTools !== 'undefined')
				{
					const openWidget = BX.MobileTools.resolveOpenFunction(crmUrl);
					if (openWidget)
					{
						openWidget();
					}
				}
				else
				{
					const inAppUrl = inAppOpener();
					if(inAppUrl)
					{
						inAppUrl.open(
							`/crm/${entityType}/details/${entityId}/`,
							{
								canOpenInDefault: true,
								bx24ModernStyle: true,
							},
							() => {
								this._onOpenPage(crmUrl);
							}
						);
					}
				}
			}
			else
			{
				this._onOpenPage(crmUrl);
			}

			this.ui.rollUp();
		}

		_onOpenPage(url)
		{
			PageManager.openPage({
				'url': url,
				'bx24ModernStyle': true,
			});
		}

		_onNativeCallAnswered(nativeAction)
		{
			CallUtil.log("onNativeCallAnswered");

			this.ignoreAnswerSelf = true;
			this.ui.stopSound();
			this.answerCall();
		}

		_onNativeCallEnded(nativeAction)
		{
			CallUtil.log("onNativeCallEnded");
			if (nativeAction)
			{
				setTimeout(() => nativeAction.fullfill(), 500);
			}

			if (!this.callId)
			{
				return;
			}

			if (this.callActive)
			{
				this._onUiHangup();
			}
			else if (this.callInit)
			{
				this._onUiSkipClicked();
			}
		}

		_onNativeCallMuted(muted)
		{
			CallUtil.log("onNativeCallMuted ", muted);
			this.toggleMute(muted);

			if (this.ui.setUiMicEnabled)
			{
				this.ui.setUiMicEnabled(muted);
			}
		}

		_onCallInvite(params)
		{
			this.log("_onCallInvite", params);

			if (!CallUtil.isDeviceSupported())
			{
				navigator.notification.alert(BX.message("MOBILE_CALL_UNSUPPORTED_VERSION"));
				return;
			}

			if (typeof (fabric) === "object")
			{
				fabric.Answers.sendCustomEvent("incomingCallTelephony", {});
			}

			if (this.callInit || this.callActive)
			{
				return false;
			}

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

			this.callConfig = params.config ? params.config : {};
			this.phoneCallTime = 0;

			if (!this.nativeCall)
			{
				this.ui.playSound({soundId: Sound.incoming});
			}

			/*chatId, callId, callerId, companyPhoneNumber, isCallback*/
			params.isCallback = !!params.isCallback;
			params.isTransfer = !!params.isTransfer;

			this.phoneNumberUser = params.callerId;
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

			CallUtil.log("this.showCallForm");

			this.showCallForm({
				status: BX.message('IM_PHONE_INITIALIZATION'),
				state: TelephonyUiState.INCOMING,
			});

			BX.rest.callMethod('voximplant.call.sendWait', {
				'CALL_ID': params.callId,
				'DEBUG_INFO': this.getDebugInfo()
			}).then((result) =>
			{
				let data = result.data();
				this.log('voximplant.call.sendWait data:', data);

				// the call could have been already finished by this moment
				if (!this.callInit && !this.callActive)
				{
					return;
				}

				if (data.SUCCESS)
				{
					this.updateUI({
						state: TelephonyUiState.INCOMING,
						footerLabels: {
							callStateLabel: {
								text: params.isCallback ? BX.message('IM_PHONE_INVITE_CALLBACK') : BX.message('IM_PHONE_INVITE'),
							},
						},

						type: TelephonyUiState.INCOMING,
						status: params.isCallback ? BX.message('IM_PHONE_INVITE_CALLBACK') : BX.message('IM_PHONE_INVITE'),
					});

					if (params.autoAnswer)
					{
						this._onUiAnswerClicked();
					}
				}
				else
				{
					this.updateUI({
						state: TelephonyUiState.FINISHED,
						footerLabels: {
							callStateLabel: {
								text: BX.message('IM_PHONE_ERROR'),
							},
						},

						type: TelephonyUiState.FINISHED,
						error: BX.message("IM_PHONE_ERROR"),
					});
					this.ui.stopSound();
				}
			}).catch((data) =>
			{
				let answer = data.answer;
				let errorText = null;
				this.log('voximplant.call.sendWait' + ' error: ', answer);

				// call could be already finished by this moment
				if (!this.callInit && !this.callActive)
				{
					return;
				}

				this.ui.stopSound();
				if (answer.error === 'ERROR_NOT_FOUND')
				{
					errorText = BX.message("IM_M_CALL_ALREADY_FINISHED");
				}
				else if (answer.error === 'ERROR_WRONG_STATE')
				{
					errorText = BX.message("IM_M_CALL_ALREADY_ANSWERED");
				}
				else
				{
					errorText = BX.message("IM_PHONE_ERROR");
				}

				this.updateUI({
					state: TelephonyUiState.FINISHED,
					footerLabels: {
						callStateLabel: {
							text: errorText,
						},
					},

					type: TelephonyUiState.FINISHED,
					error: errorText,
				});
			});
		}

		_onPullEventInvite(params, extra)
		{
			if (extra.server_time_ago >= eventTimeRange)
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
		}

		_onPullEventAnswerSelf(params)
		{
			if (this.ignoreAnswerSelf || this.callId != params.callId)
			{
				return false;
			}

			this.finishCall();
			this.ui.stopSound();
			this.closeCallForm();

			this.callInit = true;
			this.callId = params.callId;
		}

		_onPullEventTimeout(params)
		{
			if (this.callId != params.callId)
			{
				return false;
			}

			this.ui.stopSound();
			this.closeCallForm();
			this.finishCall();
		}

		_onPullEventOutgoing(params, extra)
		{
			if (extra.server_time_ago >= eventTimeRange)
			{
				this.log("Call was started too long time ago");
				return;
			}

			this.portalCall = params.portalCall === true;

			if (this.callInit && this.phoneNumber == params.phoneNumber)
			{
				if (!this.callId)
				{
					this.setProgress('connect', BX.message('IM_PHONE_WAIT_ANSWER'));

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
		}

		_onPullEventStart(params)
		{
			// not sure if we need this handler in the mobile telephony at all.

			if (this.callId != params.callId)
			{
				return false;
			}

			this.ui.startTimer();
			this.ui.stopSound();
			this.callActive = true;

			if (params.CRM)
			{
				this.setCrmData(params.CRM);
			}
		}

		_onPullEventHold(params)
		{
			if (this.callId == params.callId)
			{
				this.phoneHolded = true;
			}
		}

		_onPullEventUnHold(params)
		{
			this.phoneHolded = false;
		}

		_onPullEventUpdatePortalUser(params)
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
		}

		_onPullEventUpdateCrm(params)
		{
			if (this.callId == params.callId && params.CRM)
			{
				this.setCrmData(params.CRM);
			}
		}

		_onPullEventCompleteTransfer(params)
		{
			if (this.callId != params.callId)
			{
				return false;
			}

			this.callId = params.newCallId;
			this.isTransfer = false;
		}

		_onPullEventPhoneDeviceActive(params)
		{
			//nop
		}

		_onPullEventChangeDefaultLineId(params)
		{
			this.defaultLineId = params.defaultLineId;
			if (!this.lines.hasOwnProperty(this.defaultLineId))
			{
				this.lines[this.defaultLineId] = params.line;
			}
		}

		_onPullEventReplaceCallerId(params)
		{
			let callTitle = BX.message('IM_PHONE_CALL_TRANSFER').replace('#PHONE#', params.callerId);
			this.setCallOverlayTitle(callTitle);
			if (params.CRM)
			{
				this.setCrmData(params.CRM);
			}
		}

		getDebugInfo()
		{
			return {
				isMobile: 'Y',
				callInit: this.callInit ? 'Y' : 'N',
				callActive: this.callActive ? 'Y' : 'N',
				appVersion: Application.getAppVersion(),
				apiVersion: Application.getApiVersion(),
				buildVersion: Application.getBuildVersion()
			}
		}

		phoneCorrect(number)
		{
			return number.toString().replace(/[^0-9+#*;,]/g, '');
		}

		log()
		{
			if (this.debug)
			{
				console.log.apply(null, arguments);
			}
		}

		logged(cb)
		{
			let self = this;
			return function ()
			{
				let params = [cb.name].concat(arguments);
				console.log.apply(null, params);
				cb.apply(self, arguments);
			}
		}
	}

	class TelephonySignaling
	{
		static sendAnswer(callId)
		{
			return new Promise((resolve, reject) =>
			{
				BX.rest.callMethod('voximplant.call.answer', {'CALL_ID': callId}).then(resolve).catch(reject);
			})
		}
	}

// endregion Mobile telephony

// region Initialization

	if ('callEngine' in window)
	{
		// window.callEngine.destroy();
	}

	window.CallUtil = new CCallUtil();
	window.callEngine = new CallEngine();
	window.callController = new CallController();

	window.mtelephony = new MobileTelephony();
	window.TelephonyUiState = TelephonyUiState;

	console.log("Telephony initialized");

// endregion Initialization

})();
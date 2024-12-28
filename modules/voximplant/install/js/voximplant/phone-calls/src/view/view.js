/**
 * @bxjs_lang_path js_phone_call_view.php
 */

import {Dom, Type, Text, Runtime, Loc, Uri} from 'main.core';
import {Popup, Menu} from 'main.popup';
import {DesktopApi} from 'im.v2.lib.desktop-api';
import {BackgroundWorker, backgroundWorkerEvents} from './background-worker';
import {FormManager} from './form-manager';
import {CallList} from './call-list';
import {FoldedCallView} from './folded-view';
import {Desktop} from './desktop';
import {Keypad} from './keypad';
import type {Dialog} from 'ui.entity-selector';
import {baseZIndex, nop} from './common';

import type {MessengerFacade} from '../controller';

export const Direction = {
	incoming: 'incoming',
	outgoing: 'outgoing',
	callback: 'callback',
}

export const UiState = {
	incoming: 1,
	transferIncoming: 2,
	outgoing: 3,
	connectingIncoming: 4,
	connectingOutgoing: 5,
	connected: 6,
	transferring: 7,
	transferFailed: 8,
	transferConnected: 9,
	idle: 10,
	error: 11,
	moneyError: 12,
	sipPhoneError: 13,
	redial: 14,
	externalCard: 15,
}

export const CallState = {
	idle: 'idle',
	connecting: 'connecting',
	connected: 'connected',
}

export const CallProgress = {
	connect: 'connect',
	error: 'error',
	offline: 'offline',
	online: 'online',
	wait: 'wait'
}

const ButtonLayouts = {
	centered: 'centered',
	spaced: 'spaced'
};

/* Phone Call UI */
const layouts = {
	simple: 'simple',
	crm: 'crm'
}

const initialSize = {
	simple: {
		width: 550,
		height: 492
	},
	crm: {
		width: 550,
		height: 650
	}
};

const lsKeys = {
	height: 'im-phone-call-view-height',
	width: 'im-phone-call-view-width',
	callView: 'bx-vox-call-view',
	callInited: 'viInitedCall',
	externalCall: 'viExternalCard',
	currentCall: 'bx-vox-current-call',
};

export const desktopEvents = {
	setTitle: 'phoneCallViewSetTitle',
	setStatus: 'phoneCallViewSetStatus',
	setUiState: 'phoneCallViewSetUiState',
	setDeviceCall: 'phoneCallViewSetDeviceCall',
	setCrmEntity: 'phoneCallViewSetCrmEntity',
	setPortalCall: 'phoneCallViewSetPortalCall',
	setPortalCallUserId: 'phoneCallViewSetPortalCallUserId',
	setPortalCallQueueName: 'phoneCallViewSetPortalCallQueueName',
	setPortalCallData: 'phoneCallViewSetPortalCallData',
	setConfig: 'phoneCallViewSetConfig',
	setCallState: 'phoneCallViewSetCallState',
	reloadCrmCard: 'phoneCallViewReloadCrmCard',
	setCallId: 'phoneCallViewSetCallId',
	setLineNumber: 'phoneCallViewSetLineNumber',
	setPhoneNumber: 'phoneCallViewSetPhoneNumber',
	setCompanyPhoneNumber: 'phoneCallViewSetCompanyPhoneNumber',
	setTransfer: 'phoneCallViewSetTransfer',
	closeWindow: 'phoneCallViewCloseWindow',

	onHold: 'phoneCallViewOnHold',
	onUnHold: 'phoneCallViewOnUnHold',
	onMute: 'phoneCallViewOnMute',
	onUnMute: 'phoneCallViewOnUnMute',
	onMakeCall: 'phoneCallViewOnMakeCall',
	onCallListMakeCall: 'phoneCallViewOnCallListMakeCall',
	onAnswer: 'phoneCallViewOnAnswer',
	onSkip: 'phoneCallViewOnSkip',
	onHangup: 'phoneCallViewOnHangup',
	onClose: 'phoneCallViewOnClose',
	onStartTransfer: 'phoneCallViewOnStartTransfer',
	onCompleteTransfer: 'phoneCallViewOnCompleteTransfer',
	onCancelTransfer: 'phoneCallViewOnCancelTransfer',
	onBeforeUnload: 'phoneCallViewOnBeforeUnload',
	onSwitchDevice: 'phoneCallViewOnSwitchDevice',
	onQualityGraded: 'phoneCallViewOnQualityGraded',
	onDialpadButtonClicked: 'phoneCallViewOnDialpadButtonClicked',
	onCommentShown: 'phoneCallViewOnCommentShown',
	onSaveComment: 'phoneCallViewOnSaveComment',
	onSetAutoClose: 'phoneCallViewOnSetAutoClose',
};

const blankAvatar = '/bitrix/js/im/images/blank.gif';

export type PhoneCallViewParams = {
	darkMode: boolean,
	phoneNumber: ?string,
	lineNumber: ?string,
	companyPhoneNumber: ?string,
	direction: string, /** @see Direction */
	uiState: number, /** @see UiState */
	backgroundWorker: BackgroundWorker,
	foldedCallView: FoldedCallView,

	fromUserId: ?number,
	toUserId: ?number,
	config: Object,
	callId: ?string,
	crmEntityType: ?string,
	crmEntityId: ?number,
	crmActivityId: ?number,
	crmActivityEditUrl: ?string,
	crmData: ?Object,
	crmBindings: Array,

	portalCallData: ?Object,
	portalCallUserId: ?number,
	portalCallQueueName: ?string,

	hasSipPhone: ?boolean,
	deviceCall: ?boolean,
	portalCall: ?boolean,
	crm: ?boolean,
	recording: ?boolean,
	makeCall: ?boolean,
	folded: ?boolean,
	autoFold: ?boolean,
	transfer: ?boolean,

	statusText: ?string,
	initialTimestamp: ?number,
	events: EventHandlers,

	callListId: ?number,
	callListStatusId: ?number,
	callListItemIndex: ?number,
	webformId: ?string,
	webformSecCode: ?string,
	slave: ?boolean,
	skipOnResize: ?boolean,

	isExternalCall: ?boolean,
	isDesktop: ?boolean,

	restApps: RestApp[],
}

type EventHandlers = {
	hold: () => void,
	unhold: () => void,
	mute: () => void,
	unmute: () => void,
	makeCall: () => void,
	callListMakeCall: () => void,
	answer: () => void,
	skip: () => void,
	hangup: () => void,
	close: () => void,
	transfer: (TransferTarget) => void,
	completeTransfer: () => void,
	cancelTransfer: () => void,
	switchDevice: () => void,
	qualityGraded: () => void,
	dialpadButtonClicked: () => void,
	saveComment: () => void,
	notifyAdmin: () => void
}

type RestApp = {
	id: number,
	name: string
}

export class PhoneCallView
{
	popup: ?Popup
	restApps: RestApp[]
	formManager: ?FormManager
	foldedCallView: FoldedCallView
	messengerFacade: ?MessengerFacade
	backgroundWorker: BackgroundWorker

	constructor(params: PhoneCallViewParams)
	{
		this.id = 'im-phone-call-view';
		this.darkMode = params.darkMode === true;

		//params
		this.phoneNumber = params.phoneNumber || 'hidden';
		this.lineNumber = params.lineNumber || '';
		this.companyPhoneNumber = params.companyPhoneNumber || '';
		this.direction = params.direction || Direction.incoming;
		this.fromUserId = params.fromUserId;
		this.toUserId = params.toUserId;
		this.config = params.config || {};
		this.callId = params.callId || '';
		this.callState = CallState.idle;

		//associated crm entities
		this.crmEntityType = BX.prop.getString(params, 'crmEntityType', '');
		this.crmEntityId = BX.prop.getInteger(params, 'crmEntityId', 0);
		this.crmActivityId = BX.prop.getInteger(params, 'crmActivityId', 0);
		this.crmActivityEditUrl = BX.prop.getString(params, 'crmActivityEditUrl', '');
		this.crmData = BX.prop.getObject(params, 'crmData', {});
		this.crmBindings = BX.prop.getArray(params, 'crmBindings', []);
		this.externalRequests = {};

		//portal call
		this.portalCallData = params.portalCallData;
		this.portalCallUserId = params.portalCallUserId;
		this.portalCallQueueName = params.portalCallQueueName;

		//flags
		this.hasSipPhone = (params.hasSipPhone === true);
		this.deviceCall = (params.deviceCall === true);
		this.portalCall = (params.portalCall === true);
		this.crm = (params.crm === true);
		this.held = false;
		this.muted = false;
		this.recording = (params.recording === true);
		this.makeCall = (params.makeCall === true); // emulate pressing on "dial" button right after showing call view
		this.closable = false;
		this.allowAutoClose = true;
		this.folded = (params.folded === true);
		this.autoFold = (params.autoFold === true);
		this.transfer = (params.transfer === true);

		this.title = '';
		this._uiState = params.uiState || UiState.idle;
		this.statusText = params.statusText || '';
		this.progress = '';
		this.quality = 0;
		this.qualityPopup = null;
		this.qualityGrade = 0;
		this.comment = '';
		this.commentShown = false;

		//timer
		this.initialTimestamp = params.initialTimestamp || 0;
		this.timerInterval = null;
		this.autoCloseTimer = null;
		this.autoCloseTimeout = 65000;

		this.elements = this.getInitialElements();
		this.sections = this.getInitialSections();

		var uiStateButtons = this.getUiStateButtons(this._uiState);
		this.buttonLayout = uiStateButtons.layout;
		this.buttons = uiStateButtons.buttons;

		this.restApps = params.restApps || [];

		if (!Type.isPlainObject(params.events))
		{
			params.events = {};
		}

		this.callbacks = {
			hold: Type.isFunction(params.events.hold) ? params.events.hold : nop,
			unhold: Type.isFunction(params.events.unhold) ? params.events.unhold : nop,
			mute: Type.isFunction(params.events.mute) ? params.events.mute : nop,
			unmute: Type.isFunction(params.events.unmute) ? params.events.unmute : nop,
			makeCall: Type.isFunction(params.events.makeCall) ? params.events.makeCall : nop,
			callListMakeCall: Type.isFunction(params.events.callListMakeCall) ? params.events.callListMakeCall : nop,
			answer: Type.isFunction(params.events.answer) ? params.events.answer : nop,
			skip: Type.isFunction(params.events.skip) ? params.events.skip : nop,
			hangup: Type.isFunction(params.events.hangup) ? params.events.hangup : nop,
			close: Type.isFunction(params.events.close) ? params.events.close : nop,
			transfer: Type.isFunction(params.events.transfer) ? params.events.transfer : nop,
			completeTransfer: Type.isFunction(params.events.completeTransfer) ? params.events.completeTransfer : nop,
			cancelTransfer: Type.isFunction(params.events.cancelTransfer) ? params.events.cancelTransfer : nop,
			switchDevice: Type.isFunction(params.events.switchDevice) ? params.events.switchDevice : nop,
			qualityGraded: Type.isFunction(params.events.qualityGraded) ? params.events.qualityGraded : nop,
			dialpadButtonClicked: Type.isFunction(params.events.dialpadButtonClicked) ? params.events.dialpadButtonClicked : nop,
			saveComment: Type.isFunction(params.events.saveComment) ? params.events.saveComment : nop,
			notifyAdmin: Type.isFunction(params.events.notifyAdmin) ? params.events.notifyAdmin : nop,
		};

		this.popup = null;

		// event handlers
		this._onBeforeUnloadHandler = this._onBeforeUnload.bind(this);
		this._onDblClickHandler = this._onDblClick.bind(this);
		this._onHoldButtonClickHandler = this._onHoldButtonClick.bind(this);
		this._onMuteButtonClickHandler = this._onMuteButtonClick.bind(this);
		this._onTransferButtonClickHandler = this._onTransferButtonClick.bind(this);
		this._onTransferCompleteButtonClickHandler = this._onTransferCompleteButtonClick.bind(this);
		this._onTransferCancelButtonClickHandler = this._onTransferCancelButtonClick.bind(this);
		this._onDialpadButtonClickHandler = this._onDialpadButtonClick.bind(this);
		this._onHangupButtonClickHandler = this._onHangupButtonClick.bind(this);
		this._onCloseButtonClickHandler = this._onCloseButtonClick.bind(this);
		this._onMakeCallButtonClickHandler = this._onMakeCallButtonClick.bind(this);
		this._onNextButtonClickHandler = this._onNextButtonClick.bind(this);
		this._onRedialButtonClickHandler = this._onRedialButtonClick.bind(this);
		this._onFoldButtonClickHandler = this._onFoldButtonClick.bind(this);
		this._onAnswerButtonClickHandler = this._onAnswerButtonClick.bind(this);
		this._onSkipButtonClickHandler = this._onSkipButtonClick.bind(this);
		this._onSwitchDeviceButtonClickHandler = this._onSwitchDeviceButtonClick.bind(this);
		this._onQualityMeterClickHandler = this._onQualityMeterClick.bind(this);
		this._onPullEventCrmHandler = this._onPullEventCrm.bind(this);

		// tabs
		this.hiddenTabs = [];
		this.currentTabName = '';
		this.moreTabsMenu = null;

		//customTabs
		this.customTabs = {};

		// callList
		this.callListId = params.callListId || 0;
		this.callListStatusId = params.callListStatusId || null;
		this.callListItemIndex = params.callListItemIndex || null;
		this.callListView = null;
		this.currentEntity = null;
		this.callingEntity = null;
		this.numberSelectMenu = null;

		// webform
		this.webformId = params.webformId || 0;
		this.webformSecCode = params.webformSecCode || '';
		this.webformLoaded = false;

		// partner data
		this.restAppLayoutLoaded = false;
		this.restAppLayoutLoading = false;
		this.restAppInterface = null;

		// desktop integration
		this.callWindow = null;
		this.slave = params.slave === true;
		this.skipOnResize = params.skipOnResize === true;
		this.desktop = new Desktop({
			parentPhoneCallView: this,
			closable: (this.callListId > 0 ? true : this.closable),
		});

		this.currentLayout = (this.callListId > 0 ? layouts.crm : layouts.simple);

		this.backgroundWorker = params.backgroundWorker;
		this.backgroundWorker.setCallCard(this);
		this.backgroundWorker.setExternalCall(!!params.isExternalCall);

		this._isDesktop = params.messengerFacade ? params.messengerFacade.isDesktop() : params.isDesktop === true;
		this.messengerFacade = params.messengerFacade;
		this.foldedCallView = params.foldedCallView;

		this.init();

		if (this.backgroundWorker.isDesktop())
		{
			this.backgroundWorker.removeDesktopEventHandlers();
		}
		this.backgroundWorker.platformWorker.emitInitializeEvent(this.getPlacementOptions());
		this.createTitle().then(title => this.setTitle(title));
		if (params.hasOwnProperty('uiState'))
		{
			this.setUiState(params['uiState']);
		}
	}

	getInitialElements()
	{
		return {
			main: null,
			title: null,
			sections: {
				status: null,
				timer: null,
				crmButtons: null,
			},
			avatar: null,
			progress: null,
			timer: null,
			status: null,
			commentEditorContainer: null,
			commentEditor: null,
			qualityMeter: null,
			crmCard: null,
			crmButtonsContainer: null,
			crmButtons: {},
			buttonsContainer: null,
			topLevelButtonsContainer: null,
			topButtonsContainer: null, //well..
			buttons: {},
			sidebarContainer: null,
			tabsContainer: null,
			tabsBodyContainer: null,
			tabs: {
				callList: null,
				webform: null,
				app: null,
				custom: null,
			},
			tabsBody: {
				callList: null,
				webform: null,
				app: null
			},
			moreTabs: null
		};
	};

	getInitialSections()
	{
		return {
			status: {visible: false},
			timer: {visible: false},
			crmButtons: {visible: false},
			commentEditor: {visible: false}
		}
	};

	init()
	{
		if (DesktopApi.isChatWindow() && !this.slave)
		{
			console.log('Init phone call view window:', location.href);
			this.desktop.openCallWindow('', null, {
				width: this.getInitialWidth(),
				height: this.getInitialHeight(),
				resizable: (this.currentLayout == layouts.crm),
				minWidth: this.elements.sidebarContainer ? 950 : 550,
				minHeight: 650
			});
			this.bindMasterDesktopEvents();

			window.addEventListener('beforeunload', this.#onWindowUnload); //master window unload
			return;
		}

		this.elements.main = this.createLayout();
		this.updateView();

		if (this.isDesktop() && this.slave)
		{
			document.body.appendChild(this.elements.main);
			this.bindSlaveDesktopEvents();
		}
		else if (!this.isDesktop() && this.isFolded())
		{
			document.body.appendChild(this.elements.main);
		}
		else if (!this.isDesktop())
		{
			this.popup = this.createPopup();
			BX.addCustomEvent(window, "onLocalStorageSet", this.#onExternalEvent);
		}

		if (this.callListId > 0)
		{
			if (this.callListView)
			{
				this.callListView.reinit({
					node: this.elements.tabsBody.callList
				});
			}
			else
			{
				this.callListView = new CallList({
					node: this.elements.tabsBody.callList,
					id: this.callListId,
					statusId: this.callListStatusId,
					itemIndex: this.callListItemIndex,
					makeCall: this.makeCall,
					isDesktop: this.isDesktop,
					onSelectedItem: this.onCallListSelectedItem.bind(this)
				});

				this.callListView.init(() =>
				{
					if (this.makeCall)
					{
						this._onMakeCallButtonClick();
					}
				});
				this.setUiState(UiState.outgoing);
			}
		}
		else if (this.crm && !this.isFolded())
		{
			this.loadCrmCard(this.crmEntityType, this.crmEntityId);
		}

		BX.addCustomEvent("onPullEvent-crm", this._onPullEventCrmHandler);
		if (!this.isDesktop())
		{
			window.addEventListener('beforeunload', this._onBeforeUnloadHandler);
		}
	};

	reinit()
	{
		this.elements = this.getInitialElements();

		let unloadHandler = this.isDesktop() ? this.#onWindowUnload : this._onBeforeUnloadHandler;
		window.removeEventListener('beforeunload', unloadHandler);
		BX.removeCustomEvent(window, "onLocalStorageSet", this.#onExternalEvent);
		BX.removeCustomEvent("onPullEvent-crm", this._onPullEventCrmHandler);

		this.init();
	};

	show()
	{
		if (!this.popup && this.isDesktop())
		{
			return;
		}
		if (!this.popup)
		{
			this.reinit();
		}

		if (!this.isDesktop() && !this.isFolded())
		{
			this.disableDocumentScroll();
		}

		this.popup.show();
		BX.localStorage.set(lsKeys.callView, this.callId, 86400);

		return this;
	};

	createPopup(): Popup
	{
		return new Popup({
			id: this.getId(),
			bindElement: null,
			targetContainer: document.body,
			content: this.elements.main,
			closeIcon: false,
			noAllPaddings: true,
			zIndex: baseZIndex,
			offsetLeft: 0,
			offsetTop: 0,
			closeByEsc: false,
			draggable: {restrict: false},
			overlay: {backgroundColor: 'black', opacity: 30},
			events: {
				onPopupClose: () =>
				{
					if (this.isFolded())
					{
						// this.destroy();
					}
					else
					{
						this.callbacks.close();
					}
				},
				onPopupDestroy: () => this.popup = null
			}
		});
	};

	createLayout()
	{
		if (this.isFolded())
		{
			return this.createLayoutFolded();
		}
		else if (this.currentLayout == layouts.crm)
		{
			return this.createLayoutCrm();
		}
		else
		{
			return this.createLayoutSimple();
		}
	};

	createLayoutCrm()
	{
		var result = Dom.create("div", {
			props: {className: 'im-phone-call-top-level'},
			events: {
				dblclick: this._onDblClickHandler
			},
			children: [
				this.elements.topLevelButtonsContainer = Dom.create("div"),
				this.elements.phoneCallWrapper = Dom.create("div", {
					props: {className: 'im-phone-call-wrapper' + (this.hasSideBar() ? '' : ' im-phone-call-wrapper-without-sidebar')},
					children: [
						Dom.create("div", {
							props: {className: 'im-phone-call-container' + (this.hasSideBar() ? '' : ' im-phone-call-container-without-sidebar')},
							children: [
								Dom.create("div", {
									props: {className: 'im-phone-call-header-container'}, children: [
										Dom.create("div", {
											props: {className: 'im-phone-call-header'}, children: [
												this.elements.title = Dom.create('div', {
													props: {className: 'im-phone-call-title-text'},
													html: this.renderTitle()
												})
											]
										})
									]
								}),
								this.elements.crmCard = Dom.create("div", {props: {className: 'im-phone-call-crm-card'}}),
								this.elements.sections.status = Dom.create("div", {
									props: {className: 'im-phone-call-section'},
									style: this.sections.status.visible ? {} : {display: 'none'},
									children: [
										Dom.create("div", {
											props: {className: 'im-phone-call-status-description'}, children: [
												this.elements.status = Dom.create("div", {
													props: {className: 'im-phone-call-status-description-item'},
													text: this.statusText
												})
											]
										})
									]
								}),
								this.elements.sections.timer = Dom.create("div", {
									props: {className: 'im-phone-call-section'},
									style: this.sections.timer.visible ? {} : {display: 'none'},
									children: [
										Dom.create("div", {
											props: {className: 'im-phone-call-status-timer'}, children: [
												Dom.create("div", {
													props: {className: 'im-phone-call-status-timer-item'}, children: [
														this.elements.timer = Dom.create("span")
													]
												})
											]
										})
									]
								}),
								this.elements.commentEditorContainer = Dom.create("div", {
									props: {className: 'im-phone-call-section'},
									style: this.commentShown ? {} : {display: 'none'},
									children: [
										Dom.create("div", {
											props: {className: 'im-phone-call-comments'}, children: [
												this.elements.commentEditor = Dom.create("textarea", {
													props: {
														className: 'im-phone-call-comments-textarea',
														value: this.comment,
														placeholder: Loc.getMessage('IM_PHONE_CALL_COMMENT_PLACEHOLDER')
													},
													events: {
														bxchange: this._onCommentChanged.bind(this)
													}
												})
											]
										})
									]
								}),
								this.elements.sections.crmButtons = Dom.create("div", {
									props: {className: 'im-phone-call-section'},
									style: this.sections.crmButtons.visible ? {} : {display: 'none'},
									children: [
										this.elements.crmButtonsContainer = Dom.create("div", {props: {className: 'im-phone-call-crm-buttons'}})
									]
								}),
								this.elements.buttonsContainer = Dom.create("div", {props: {className: 'im-phone-call-buttons-container'}}),
								this.elements.topButtonsContainer = Dom.create("div", {props: {className: 'im-phone-call-buttons-container-top'}})
							]
						})
					]
				})
			]
		});

		if (this.hasSideBar())
		{
			this.createSidebarLayout();
			if (this.elements.sidebarContainer)
			{
				result.appendChild(this.elements.sidebarContainer);
			}

			setTimeout(() => this.checkMoreButton(), 0);
		}

		if (this.isDesktop())
		{
			result.style.position = 'fixed';
			result.style.top = 0;
			result.style.bottom = 0;
			result.style.left = 0;
			result.style.right = 0;
		}
		else
		{
			result.style.width = this.getInitialWidth() + 'px';
			result.style.height = this.getInitialHeight() + 'px';
		}

		return result;
	};

	/**
	 * @return boolean
	 */
	hasSideBar()
	{
		if (this.isDesktop() && !this.desktop.isFeatureSupported('iframe'))
		{
			return this.callListId > 0;
		}
		else
		{
			return (this.callListId > 0 || this.webformId > 0 || this.restApps.length > 0 || Object.keys(this.customTabs).length > 0);
		}
	};

	getInitialWidth()
	{
		const storedWidth = (window.localStorage) ? parseInt(window.localStorage.getItem(lsKeys.width)) : 0;

		if (this.currentLayout == layouts.simple)
		{
			return initialSize.simple.width;
		}
		else if (this.hasSideBar())
		{
			if (storedWidth > 0)
			{
				return storedWidth;
			}
			else
			{
				return Math.min(Math.floor(screen.width * 0.8), 1200);
			}
		}
		else
		{
			return initialSize.crm.width;
		}
	};

	getInitialHeight()
	{
		const storedHeight = (window.localStorage) ? parseInt(window.localStorage.getItem(lsKeys.height)) : 0;

		if (this.currentLayout == layouts.simple)
		{
			return initialSize.simple.height
		}
		else if (storedHeight > 0)
		{
			return storedHeight;
		}
		else
		{
			return initialSize.crm.height;
		}
	};

	saveInitialSize(width, height)
	{
		if (!window.localStorage)
		{
			return false;
		}

		if (this.currentLayout == layouts.crm)
		{
			window.localStorage.setItem(lsKeys.height, height.toString());
			if (this.hasSideBar())
			{
				window.localStorage.setItem(lsKeys.width, width);
			}
		}
	};

	showSections(sections)
	{
		if (!Type.isArray(sections))
		{
			return;
		}

		sections.forEach((sectionName) =>
		{
			if (this.elements.sections[sectionName])
			{
				this.elements.sections[sectionName].style.removeProperty('display');
			}

			if (this.sections[sectionName])
			{
				this.sections[sectionName].visible = true;
			}
		});
	};

	hideSections(sections)
	{
		if (!Type.isArray(sections))
		{
			return;
		}

		sections.forEach((sectionName) =>
		{
			if (this.elements.sections[sectionName])
			{
				this.elements.sections[sectionName].style.display = 'none';
			}

			if (this.sections[sectionName])
			{
				this.sections[sectionName].visible = false;
			}
		});
	};

	showOnlySections(sections)
	{
		if (!Type.isArray(sections))
		{
			return;
		}

		let sectionsIndex = {};
		sections.forEach(sectionName => sectionsIndex[sectionName] = true);

		for (var sectionName in this.elements.sections)
		{
			if (!this.elements.sections.hasOwnProperty(sectionName) || !Type.isDomNode(this.elements.sections[sectionName]))
			{
				continue;
			}

			if (sectionsIndex[sectionName])
			{
				this.elements.sections[sectionName].style.removeProperty('display');
				if (this.sections.hasOwnProperty(sectionName))
				{
					this.sections[sectionName].visible = true;
				}
			}
			else
			{
				this.elements.sections[sectionName].style.display = 'none';
				if (this.sections.hasOwnProperty(sectionName))
				{
					this.sections[sectionName].visible = false;
				}
			}
		}
	};

	createSidebarLayout()
	{
		let tabs = [];
		let tabsBody = [];
		
		if (Object.keys(this.customTabs).length > 0)
		{
			Object.keys(this.customTabs).forEach(tabKey => {
				const customTabId = this.customTabs[tabKey].id;
				const tabTitle = this.customTabs[tabKey].title;
				const tabId = `custom${customTabId}`;

				this.elements.tabs[tabId] = Dom.create("span", {
					props: {className: 'im-phone-sidebar-tab'},
					dataset: {tabId: tabId, tabBodyId: `custom${customTabId}`},
					text: Text.encode(tabTitle),
					events: {click: this._onTabHeaderClick.bind(this)}
				});
				tabs.push(this.elements.tabs[tabId]);


				if (!this.elements.tabsBody[tabId])
				{
					this.elements.tabsBody[tabId] = Dom.create('div', {
						props: {
							className: `voximplant-phone-call-${tabId}-container`
						},
						children: [
							Dom.create('div', {
								props: {
									className: `voximplant-phone-call-${tabId}-tab-content voximplant-phone-call-custom-container`
								},
							}),
						]
					});
				}
				tabsBody.push(this.elements.tabsBody[tabId]);
			})
		}

		if (this.callListId > 0)
		{
			this.elements.tabs.callList = Dom.create("span", {
				props: {className: 'im-phone-sidebar-tab'},
				dataset: {tabId: 'callList', tabBodyId: 'callList'},
				text: Loc.getMessage('IM_PHONE_CALL_VIEW_CALL_LIST_TITLE'),
				events: {click: this._onTabHeaderClick.bind(this)}
			});
			tabs.push(this.elements.tabs.callList);

			if (!this.elements.tabsBody.callList)
			{
				this.elements.tabsBody.callList = Dom.create('div');
			}
			tabsBody.push(this.elements.tabsBody.callList);
		}

		if (this.webformId > 0 && this.isWebformSupported())
		{
			this.elements.tabs.webform = Dom.create("span", {
				props: {className: 'im-phone-sidebar-tab'},
				dataset: {tabId: 'webform', tabBodyId: 'webform'},
				text: Loc.getMessage('IM_PHONE_CALL_VIEW_WEBFORM_TITLE'),
				events: {click: this._onTabHeaderClick.bind(this)}
			});
			tabs.push(this.elements.tabs.webform);

			if (!this.elements.tabsBody.webform)
			{
				this.elements.tabsBody.webform = Dom.create('div', {props: {className: 'im-phone-call-form-container'}});
			}
			tabsBody.push(this.elements.tabsBody.webform);

			if (!this.formManager)
			{
				this.formManager = new FormManager({
					node: this.elements.tabsBody.webform,
					onFormSend: this._onFormSend.bind(this)
				})
			}
		}

		if (this.restApps.length > 0 && this.isRestAppsSupported())
		{
			this.restApps.forEach((restApp) =>
			{
				const restAppId = restApp.id;
				const tabId = 'restApp' + restAppId;
				this.elements.tabs[tabId] = Dom.create("span", {
					props: {className: 'im-phone-sidebar-tab'},
					dataset: {tabId: tabId, tabBodyId: 'app', restAppId: restAppId},
					text: Text.encode(restApp.name),
					events: {click: this._onTabHeaderClick.bind(this)}
				});
				tabs.push(this.elements.tabs[tabId]);

			});
			if (!this.elements.tabsBody.app)
			{
				this.elements.tabsBody.app = Dom.create('div', {props: {className: 'im-phone-call-app-container'}});
			}
			tabsBody.push(this.elements.tabsBody.app);
		}

		this.elements.tabsTitleListContainer = Dom.create("div", {
			props: {className: 'im-phone-sidebar-tabs-container'}, children: [
				this.elements.tabsContainer = Dom.create("div", {
					props: {className: 'im-phone-sidebar-tabs-left'},
					children: tabs
				}),
				Dom.create("div", {
					props: {className: 'im-phone-sidebar-tabs-right'}, children: [
						this.elements.moreTabs = Dom.create("span", {
							props: {className: 'im-phone-sidebar-tab im-phone-sidebar-tab-more'},
							style: {display: 'none'},
							dataset: {},
							text: Loc.getMessage('IM_PHONE_CALL_VIEW_MORE'),
							events: {click: this._onTabMoreClick.bind(this)}
						})
					]
				})
			]
		});

		this.elements.tabsBodyContainer = Dom.create("div", {
			props: {className: 'im-phone-sidebar-tabs-body-container'},
			children: tabsBody
		})

		if (this.elements.sidebarContainer)
		{
			this.elements.sidebarContainer.replaceChild(
				this.elements.tabsTitleListContainer,
				this.elements.sidebarContainer.firstChild
			);
			this.elements.sidebarContainer.replaceChild(
				this.elements.tabsBodyContainer,
				this.elements.sidebarContainer.lastChild
			);
			setTimeout(() => this.checkMoreButton(), 0);
		}
		else
		{
			this.elements.sidebarContainer = Dom.create("div", {
				props: {className: 'im-phone-sidebar-wrap'}, children: [
					this.elements.tabsTitleListContainer,
					this.elements.tabsBodyContainer,
				]
			});
		}

		if (Object.keys(this.customTabs).length > 0)
		{
			const selectedCustomTab = Object.keys(this.customTabs)[0];
			this.setActiveTab({
				tabId: `custom${this.customTabs[selectedCustomTab].id}`,
				tabBodyId: `custom${this.customTabs[selectedCustomTab].id}`,
				hidden: this.customTabs[selectedCustomTab].visible,
			});
		}
		else if (this.callListId > 0)
		{
			this.setActiveTab({tabId: 'callList', tabBodyId: 'callList'});
		}
		else if (this.webformId > 0 && this.isWebformSupported())
		{
			this.setActiveTab({tabId: 'webform', tabBodyId: 'webform'});
		}
		else if (this.restApps.length > 0 && this.isRestAppsSupported())
		{
			this.setActiveTab({
				tabId: 'restApp' + this.restApps[0].id,
				tabBodyId: 'app',
				restAppId: this.restApps[0].id
			});
		}
	};

	createLayoutSimple()
	{
		var portalCallUserImage = '';
		if (this.isPortalCall()
			&& this.portalCallData.hrphoto
			&& this.portalCallData.hrphoto[this.portalCallUserId]
			&& this.portalCallData.hrphoto[this.portalCallUserId] != blankAvatar
		)
		{
			portalCallUserImage = this.portalCallData.hrphoto[this.portalCallUserId];
		}
		var result = Dom.create("div", {
			props: {className: 'im-phone-call-wrapper'}, children: [
				Dom.create("div", {
					props: {className: 'im-phone-call-container'}, children: [
						Dom.create("div", {
							props: {className: 'im-phone-calling-section'}, children: [
								this.elements.title = Dom.create("div", {props: {className: 'im-phone-calling-text'}})
							]
						}),
						Dom.create("div", {
							props: {className: 'im-phone-call-section im-phone-calling-progress-section'}, children: [
								Dom.create("div", {
									props: {className: 'im-phone-calling-progress-container'}, children: [
										Dom.create("div", {
											props: {className: 'im-phone-calling-progress-container-block-l'},
											children: [
												Dom.create("div", {props: {className: 'im-phone-calling-progress-phone'}})
											]
										}),
										this.elements.progress = Dom.create("div", {props: {className: 'im-phone-calling-progress-container-block-c'}}),
										Dom.create("div", {
											props: {className: 'im-phone-calling-progress-container-block-r'},
											children: [
												this.elements.avatar = Dom.create("div", {
													props: {className: 'im-phone-calling-progress-customer'},
													style: Type.isStringFilled(portalCallUserImage) ? {'background-image': 'url(\'' + portalCallUserImage + '\')'} : {}
												})
											]
										})
									]
								})
							]
						}),
						Dom.create("div", {
							props: {className: 'im-phone-call-section'}, children: [
								this.elements.status = Dom.create("div", {props: {className: 'im-phone-calling-process-status'}})
							]
						}),
						this.elements.buttonsContainer = Dom.create("div", {props: {className: 'im-phone-call-buttons-container'}}),
						this.elements.topButtonsContainer = Dom.create("div", {props: {className: 'im-phone-call-buttons-container-top'}})
					]
				})
			]
		});

		result.style.width = this.getInitialWidth() + 'px';
		result.style.height = this.getInitialHeight() + 'px';

		return result;
	};

	createLayoutFolded()
	{
		return Dom.create("div", {
			props: {className: "im-phone-call-panel-mini"},
			style: {zIndex: baseZIndex},
			children: [
				this.elements.sections.timer = this.elements.timer = Dom.create("div", {
					props: {className: "im-phone-call-panel-mini-time"},
					style: this.sections.timer.visible ? {} : {display: 'none'}
				}),
				this.elements.buttonsContainer = Dom.create("div", {props: {className: 'im-phone-call-panel-mini-buttons'}}),
				Dom.create("div", {
					props: {className: "im-phone-call-panel-mini-expand"},
					events: {click: () => this.unfold()}
				})
			]
		});
	};

	addTab(tabName, tabId = '')
	{
		if (!tabId)
		{
			tabId = Math.random().toString(36).substr(2, 9);
		}

		return new Promise((resolve) => {
			const tab = {
				title: tabName,
				id: tabId,
				callId: this.callId,
				contentContainerId: `voximplant-phone-call-${tabId}-container`,
				visible: true,
				visibilityChangeCallback: null,

				getContentContainerId: () => {
					return this.elements.tabsBody[`custom${tabId}`];
				},
				setContent: (content: Dom) => {
					this.elements.tabsBody[`custom${tabId}`].replaceChild(
						content,
						this.elements.tabsBody[`custom${tabId}`].firstChild
					);
				},
				setVisibilityChangeCallback(callback) {
					this.visibilityChangeCallback = callback;
				},
				setTitle: (newTitle: string) => {
					this.customTabs[tabId].title = newTitle;
					this.elements.tabs[`custom${tabId}`].innerText = newTitle;
				},
				setVisibility: (newValue: boolean) => {
					this.customTabs[tabId].visible = newValue;
					this.createSidebarLayout();
				},
				remove: () => {
					delete this.customTabs[tabId];

					if (!this.hasSideBar())
					{
						this.elements.main.removeChild(this.elements.sidebarContainer);
						this.elements.sidebarContainer = null;
						this.resizeCallCard()

						return;
					}

					this.createSidebarLayout();
				}
			};
			this.customTabs[tabId] = tab;

			if (this.elements.sidebarContainer)
			{
				this.createSidebarLayout();
			}
			else
			{
				this.createSidebarLayout();
				this.elements.main.appendChild(this.elements.sidebarContainer);
				this.elements.phoneCallWrapper.classList.remove('im-phone-call-wrapper-without-sidebar')
				this.resizeCallCard()
			}

			resolve(tab);
		});
	}

	resizeCallCard()
	{
		if (this.isDesktop())
		{
			this.elements.main.style.position = 'fixed';
			this.elements.main.style.top = 0;
			this.elements.main.style.bottom = 0;
			this.elements.main.style.left = 0;
			this.elements.main.style.right = 0;
		}
		else
		{
			this.elements.main.style.width = this.getInitialWidth() + 'px';
			this.elements.main.style.height = this.getInitialHeight() + 'px';
		}

		if (this.isDesktop())
		{
			this.resizeWindow(this.getInitialWidth(), this.getInitialHeight());
		}
		this.adjust();
	}

	setActiveTab(params)
	{
		const tabId = params.tabId;
		const tabBodyId = params.tabBodyId;
		const restAppId = params.restAppId || '';
		params.hidden = params.hidden === true;
		for (let tab in this.elements.tabs)
		{
			if (this.elements.tabs.hasOwnProperty(tab) && Type.isDomNode(this.elements.tabs[tab]))
			{
				this.elements.tabs[tab].classList.toggle('im-phone-sidebar-tab-active', tab == tabId)
			}
		}

		this.elements.moreTabs.classList.toggle('im-phone-sidebar-tab-active', params.hidden);

		for (let tab in this.elements.tabsBody)
		{
			if (this.elements.tabsBody.hasOwnProperty(tab) && Type.isDomNode(this.elements.tabsBody[tab]))
			{
				if (tab == tabBodyId)
				{
					this.elements.tabsBody[tab].style.removeProperty('display');
				}
				else
				{
					this.elements.tabsBody[tab].style.display = 'none';
				}
			}
		}

		this.currentTabName = tabId;

		if (tabId === 'webform' && !this.webformLoaded)
		{
			this.loadForm({
				id: this.webformId,
				secCode: this.webformSecCode
			})
		}

		if (restAppId !== '')
		{
			this.loadRestApp({
				id: restAppId,
				callId: this.callId,
				node: this.elements.tabsBody.app
			});
		}
	};

	isCurrentTabHidden()
	{
		let result = false;
		for (let i = 0; i < this.hiddenTabs.length; i++)
		{
			if (this.hiddenTabs[i].dataset.tabId == this.currentTabName)
			{
				result = true;
				break;
			}
		}
		return result;
	};

	checkMoreButton()
	{
		if (!this.elements.tabsContainer)
		{
			return;
		}

		var tabs = this.elements.tabsContainer.children;
		var currentTab;
		this.hiddenTabs = [];

		for (var i = 0; i < tabs.length; i++)
		{
			currentTab = tabs.item(i);
			if (currentTab.offsetTop > 7)
			{
				this.hiddenTabs.push(currentTab);
			}
		}
		if (this.hiddenTabs.length > 0)
		{
			this.elements.moreTabs.style.removeProperty('display');
		}
		else
		{
			this.elements.moreTabs.style.display = 'none';
		}

		if (this.isCurrentTabHidden())
		{
			Dom.addClass(this.elements.moreTabs, 'im-phone-sidebar-tab-active');
		}
		else
		{
			Dom.removeClass(this.elements.moreTabs, 'im-phone-sidebar-tab-active');
		}
	};

	_onTabHeaderClick(e)
	{
		if (this.moreTabsMenu)
		{
			this.moreTabsMenu.close();
		}

		this.setActiveTab({
			tabId: e.target.dataset.tabId,
			tabBodyId: e.target.dataset.tabBodyId,
			restAppId: e.target.dataset.restAppId || '',
			hidden: false
		});
	};

	_onTabMoreClick()
	{
		if (this.hiddenTabs.length === 0)
		{
			return;
		}

		if (this.moreTabsMenu)
		{
			this.moreTabsMenu.close();
			return;
		}

		var menuItems = [];
		this.hiddenTabs.forEach((tabElement) =>
		{
			menuItems.push({
				id: "selectTab_" + tabElement.dataset.tabId,
				text: tabElement.innerText,
				onclick: () =>
				{
					this.moreTabsMenu.close();
					this.setActiveTab({
						tabId: tabElement.dataset.tabId,
						tabBodyId: tabElement.dataset.tabBodyId,
						restAppId: tabElement.dataset.restAppId || '',
						hidden: true
					});
				}
			})
		});

		this.moreTabsMenu = new Menu(
			'phoneCallViewMoreTabs',
			this.elements.moreTabs,
			menuItems,
			{
				autoHide: true,
				offsetTop: 0,
				offsetLeft: 0,
				angle: {position: "top"},
				zIndex: baseZIndex + 100,
				events: {
					onPopupClose: () => this.moreTabsMenu.destroy(),
					onPopupDestroy: () => this.moreTabsMenu = null
				}
			}
		);
		this.moreTabsMenu.show();
	};

	getId()
	{
		return this.id;
	};

	createTitle()
	{
		let callTitle = '';

		return new Promise((resolve) =>
		{
			BX.PhoneNumberParser.getInstance().parse(this.phoneNumber).then((parsedNumber) =>
			{
				if (this.phoneNumber == 'unknown')
				{
					resolve(Loc.getMessage('IM_PHONE_CALL_VIEW_NUMBER_UNKNOWN'));
					return;
				}
				if (this.phoneNumber == 'hidden')
				{
					callTitle = Loc.getMessage('IM_PHONE_HIDDEN_NUMBER');
				}
				else
				{
					callTitle = this.phoneNumber.toString();

					if (parsedNumber.isValid())
					{
						callTitle = parsedNumber.format();

						if (parsedNumber.isInternational() && callTitle.charAt(0) != '+')
						{
							callTitle = '+' + callTitle;
						}
					}
					else
					{
						callTitle = this.phoneNumber.toString();
					}
				}

				if (this.isCallback())
				{
					callTitle = Loc.getMessage('IM_PHONE_CALLBACK_TO').replace('#PHONE#', callTitle);
				}
				else if (this.isPortalCall())
				{
					switch (this.direction)
					{
						case Direction.incoming:
							if (this.portalCallUserId)
							{
								callTitle = Loc.getMessage("IM_M_CALL_VOICE_FROM").replace('#USER#', this.portalCallData.users[this.portalCallUserId].name);
							}
							break;
						case Direction.outgoing:
							if (this.portalCallUserId)
							{
								callTitle = Loc.getMessage("IM_M_CALL_VOICE_TO").replace('#USER#', this.portalCallData.users[this.portalCallUserId].name);
							}
							else
							{
								callTitle = Loc.getMessage("IM_M_CALL_VOICE_TO").replace('#USER#', this.portalCallQueueName) + ' (' + this.phoneNumber + ')';
							}
							break;
					}
				}
				else
				{
					callTitle = Loc.getMessage(this.direction === Direction.incoming ? 'IM_PHONE_CALL_VOICE_FROM' : 'IM_PHONE_CALL_VOICE_TO').replace('#PHONE#', callTitle);

					if (this.direction === Direction.incoming && this.companyPhoneNumber)
					{
						callTitle = callTitle + ', ' + Loc.getMessage('IM_PHONE_CALL_TO_PHONE').replace('#PHONE#', this.companyPhoneNumber);
					}

					if (this.isTransfer())
					{
						callTitle = callTitle + ' ' + Loc.getMessage('IM_PHONE_CALL_TRANSFERED');
					}
				}

				resolve(callTitle);
			});
		})
	};

	renderTitle()
	{
		return Text.encode(this.title);
	};

	renderAvatar()
	{
		let portalCallUserImage = '';
		if (this.isPortalCall()
			&& this.elements.avatar
			&& this.portalCallData.hrphoto
			&& this.portalCallData.hrphoto[this.portalCallUserId]
			&& this.portalCallData.hrphoto[this.portalCallUserId] != blankAvatar
		)
		{
			portalCallUserImage = this.portalCallData.hrphoto[this.portalCallUserId];

			Dom.adjust(this.elements.avatar, {
				style: portalCallUserImage === '' ? {} : {'background-image': 'url(\'' + portalCallUserImage + '\')'}
			});
		}
	};

	_getCrmEditUrl(entityTypeName, entityId)
	{
		if (!Type.isStringFilled(entityTypeName))
		{
			return '';
		}

		entityId = Number(entityId);

		return '/crm/' + entityTypeName.toLowerCase() + '/edit/' + entityId.toString() + '/';
	};

	_generateExternalContext()
	{
		return this._getRandomString(16);
	};

	_getRandomString(len)
	{
		const charSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		let randomString = '';
		for (let i = 0; i < len; i++)
		{
			const randomPoz = Math.floor(Math.random() * charSet.length);
			randomString += charSet.substring(randomPoz, randomPoz + 1);
		}
		return randomString;
	};

	setPhoneNumber(phoneNumber)
	{
		this.phoneNumber = phoneNumber;
		this.setOnSlave(desktopEvents.setPhoneNumber, [phoneNumber]);
	};

	setTitle(title)
	{
		this.title = title;
		if (this.isDesktop())
		{
			if (this.slave)
			{
				BXDesktopWindow.SetProperty('title', title);
			}
			else
			{
				DesktopApi.emit(desktopEvents.setTitle, [title]);
			}
		}

		if (this.elements.title)
		{
			this.elements.title.innerHTML = this.renderTitle();
		}
	};

	getTitle()
	{
		return this.title;
	};

	setQuality(quality)
	{
		this.quality = quality;

		if (this.elements.qualityMeter)
		{
			this.elements.qualityMeter.style.width = this.getQualityMeterWidth();
		}
	};

	getQualityMeterWidth(): string
	{
		if (this.quality > 0 && this.quality <= 5)
		{
			return this.quality * 20 + '%';
		}
		else
		{
			return '0';
		}
	};

	setProgress(progress: $Keys<typeof CallProgress>)
	{
		if (this.progress === progress)
		{
			return;
		}
		this.progress = progress;

		if (!this.elements.progress)
		{
			return;
		}

		Dom.clean(this.elements.progress);
		this.elements.progress.appendChild(this.renderProgress(this.progress));
	};

	setStatusText(statusText)
	{
		if (this.isDesktop() && !this.slave)
		{
			DesktopApi.emit(desktopEvents.setStatus, [statusText]);
			return;
		}

		this.statusText = statusText;
		if (this.elements.status)
		{
			this.elements.status.innerText = this.statusText;
		}
	};

	setConfig(config)
	{
		if (!Type.isPlainObject(config))
		{
			return;
		}

		this.config = config;
		if (!this.isDesktop() || this.slave)
		{
			this.renderCrmButtons();
		}
		this.setOnSlave(desktopEvents.setConfig, [config]);
	};

	setCallId(callId)
	{
		this.callId = callId;
		this.setOnSlave(desktopEvents.setCallId, [callId]);
	};

	setLineNumber(lineNumber)
	{
		this.lineNumber = lineNumber;
		this.setOnSlave(desktopEvents.setLineNumber, [lineNumber]);
	};

	setCompanyPhoneNumber(companyPhoneNumber)
	{
		this.companyPhoneNumber = companyPhoneNumber;
		this.setOnSlave(desktopEvents.setCompanyPhoneNumber, [companyPhoneNumber]);
	};

	setButtons(buttons, layout)
	{
		if (!ButtonLayouts[layout])
		{
			layout = ButtonLayouts.centered;
		}

		this.buttonLayout = layout;
		this.buttons = buttons;
		this.renderButtons();
	};

	setUiState(uiState)
	{
		this._uiState = uiState;

		var stateButtons = this.getUiStateButtons(uiState);
		this.buttons = stateButtons.buttons;
		this.buttonLayout = stateButtons.layout;

		switch (uiState)
		{
			case UiState.incoming:
				this.setClosable(false);
				this.showOnlySections(['status']);
				this.renderCrmButtons();
				this.stopTimer();
				break;
			case UiState.transferIncoming:
				this.setClosable(false);
				this.showOnlySections(['status']);
				this.renderCrmButtons();
				this.stopTimer();
				break;
			case UiState.outgoing:
				this.setClosable(true);
				this.showOnlySections(['status']);
				this.renderCrmButtons();
				this.stopTimer();
				this.hideCallIcon();
				break;
			case UiState.connectingIncoming:
				this.setClosable(false);
				this.showOnlySections(['status']);
				this.renderCrmButtons();
				this.stopTimer();
				break;
			case UiState.connectingOutgoing:
				this.setClosable(false);
				this.showOnlySections(['status']);
				this.renderCrmButtons();
				this.showCallIcon();
				this.stopTimer();
				break;
			case UiState.connected:
				if (this.deviceCall)
				{
					this.setClosable(true);
				}
				else
				{
					this.setClosable(false);
				}

				this.showSections(['status', 'timer']);
				this.renderCrmButtons();
				this.showCallIcon();
				this.startTimer();
				break;
			case UiState.transferring:
				this.setClosable(false);
				this.showSections(['status', 'timer']);
				this.renderCrmButtons();
				break;
			case UiState.idle:
				this.setClosable(true);
				this.stopTimer();
				this.hideCallIcon();
				this.showOnlySections(['status']);
				this.renderCrmButtons();
				break;
			case UiState.error:
				this.setClosable(true);
				this.stopTimer();
				this.hideCallIcon();
				break;
			case UiState.moneyError:
				this.setClosable(true);
				this.stopTimer();
				this.hideCallIcon();
				break;
			case UiState.sipPhoneError:
				this.setClosable(true);
				this.stopTimer();
				this.hideCallIcon();
				break;
			case UiState.redial:
				this.setClosable(true);
				this.stopTimer();
				this.hideCallIcon();
				break;
		}

		if (this.isDesktop() && !this.slave)
		{
			DesktopApi.emit(desktopEvents.setUiState, [uiState]);
			return;
		}
		this.renderButtons();
	};

	/**
	 * @param {string} callState
	 * @param {object} additionalParams
	 * @see CallState
	 */
	setCallState(callState, additionalParams)
	{
		if (this.callState === callState)
		{
			return;
		}

		this.callState = callState;

		if (!Type.isPlainObject(additionalParams))
		{
			additionalParams = {};
		}

		this.renderButtons();
		if (callState === CallState.connected && this.isAutoFoldAllowed())
		{
			this.fold();
		}

		BX.onCustomEvent(window, "CallCard::CallStateChanged", [callState, additionalParams]);
		this.setOnSlave(desktopEvents.setCallState, [callState, additionalParams]);
	};

	isAutoFoldAllowed()
	{
		return (this.autoFold === true && !this.isDesktop() && !this.isFolded() && this.restApps.length === 0);
	};

	isHeld()
	{
		return this.held;
	};

	setHeld(held)
	{
		this.held = held;
	};

	setRecording(recording)
	{
		this.recording = recording;
	};

	isRecording()
	{
		return this.recording;
	};

	isMuted()
	{
		return this.muted;
	};

	setMuted(muted)
	{
		this.muted = muted;
	};

	isTransfer()
	{
		return this.transfer;
	};

	setTransfer(transfer)
	{
		transfer = (transfer === true);
		if (this.transfer == transfer)
		{
			return;
		}

		this.transfer = transfer;
		this.setOnSlave(desktopEvents.setTransfer, [transfer]);
		this.setUiState(this._uiState);
	};

	isCallback()
	{
		return (this.direction === Direction.callback);
	};

	isPortalCall()
	{
		return this.portalCall;
	};

	setCallback(eventName, callback)
	{
		if (!this.callbacks.hasOwnProperty(eventName))
		{
			return false;
		}

		this.callbacks[eventName] = Type.isFunction(callback) ? callback : nop;
	};

	setDeviceCall(deviceCall)
	{
		this.deviceCall = deviceCall;

		if (this.elements.buttons.sipPhone)
		{
			if (deviceCall)
			{
				Dom.addClass(this.elements.buttons.sipPhone, 'active');
			}
			else
			{
				Dom.removeClass(this.elements.buttons.sipPhone, 'active');
			}
		}

		if (this.isDesktop() && !this.slave)
		{
			DesktopApi.emit(desktopEvents.setDeviceCall, [deviceCall]);
		}
	};

	setCrmEntity(params)
	{
		this.crmEntityType = params.type;
		this.crmEntityId = params.id;
		this.crmActivityId = params.activityId || '';
		this.crmActivityEditUrl = params.activityEditUrl || '';
		this.crmBindings = Type.isArray(params.bindings) ? params.bindings : [];

		if (this.isDesktop() && !this.slave)
		{
			DesktopApi.emit(desktopEvents.setCrmEntity, [params]);
		}
	};

	setCrmData(crmData)
	{
		if (!Type.isPlainObject(crmData))
		{
			return;
		}

		this.crm = true;
		this.crmData = crmData;
	};

	loadCrmCard(entityType, entityId)
	{
		BX.onCustomEvent(window, 'CallCard::EntityChanged', [{
			'CRM_ENTITY_TYPE': entityType,
			'CRM_ENTITY_ID': entityId,
			'PHONE_NUMBER': this.phoneNumber
		}]);
		this.backgroundWorker.emitEvent(backgroundWorkerEvents.entityChanged, {
			'CRM_ENTITY_TYPE': entityType,
			'CRM_ENTITY_ID': entityId,
			'PHONE_NUMBER': this.phoneNumber
		});

		let enableCopilotReplacement = 'Y';

		if (this.isCallListMode())
		{
			enableCopilotReplacement = 'N';
		}

		BX.ajax.runAction("voximplant.callview.getCrmCard", {
			data: {
				entityType: entityType,
				entityId: entityId,
				isEnableCopilotReplacement: enableCopilotReplacement,
			}
		}).then((response) =>
		{
			if (this.currentLayout == layouts.simple)
			{
				this.currentLayout = layouts.crm;
				this.crm = true;
				var newMainElement = this.createLayoutCrm();

				this.elements.main.parentNode.replaceChild(newMainElement, this.elements.main);
				this.elements.main = newMainElement;
				this.setUiState(this._uiState);
				this.setStatusText(this.statusText);
			}

			if (this.elements.crmCard)
			{
				BX.html(this.elements.crmCard, response.data.html);
				setTimeout(() =>
				{
					if (this.isDesktop())
					{
						this.resizeWindow(this.getInitialWidth(), this.getInitialHeight());
					}
					this.adjust();
					this.bindCrmCardEvents();
				}, 100);
			}

			this.renderCrmButtons();
		}).catch((response) => console.error("Could not load crm card: ", response.errors[0]));
	};

	reloadCrmCard()
	{
		if (this.isDesktop() && !this.slave)
		{
			DesktopApi.emit(desktopEvents.reloadCrmCard, []);
		}
		else
		{
			this.loadCrmCard(this.crmEntityType, this.crmEntityId);
		}
	};

	bindCrmCardEvents()
	{
		if (!this.elements.crmCard)
		{
			return;
		}

		if (!BX.Crm || !BX.Crm.Page)
		{
			return;
		}

		var anchors = this.elements.crmCard.querySelectorAll('a[data-use-slider=Y]');
		for (var i = 0; i < anchors.length; i++)
		{
			BX.bind(anchors[i], 'click', this.onCrmAnchorClick.bind(this));
		}
	};

	onCrmAnchorClick(e)
	{
		if (BX.Crm.Page.isSliderEnabled(e.currentTarget.href))
		{
			if (!this.isFolded())
			{
				this.fold();
			}
		}
	};

	setPortalCallUserId(userId)
	{
		this.portalCallUserId = userId;
		this.setOnSlave(desktopEvents.setPortalCallUserId, [userId]);

		if (this.portalCallData && this.portalCallData.users[this.portalCallUserId])
		{
			this.renderAvatar();
			this.createTitle().then(title => this.setTitle(title));
		}
	};

	setPortalCallQueueName(queueName)
	{
		this.portalCallQueueName = queueName;
		this.setOnSlave(desktopEvents.setPortalCallQueueName, [queueName]);

		this.createTitle().then(title => this.setTitle(title));
	};

	setPortalCall(portalCall)
	{
		this.portalCall = (portalCall === true);
		this.setOnSlave(desktopEvents.setPortalCall, [portalCall]);
	};

	setPortalCallData(data)
	{
		this.portalCallData = data;
		this.setOnSlave(desktopEvents.setPortalCallData, [data]);
	};

	setOnSlave(message, parameters)
	{
		if (this.isDesktop() && !this.slave)
		{
			DesktopApi.emit(message, parameters);
		}
	};

	updateView()
	{
		if (this.elements.title)
		{
			this.elements.title.innerHTML = this.renderTitle();
		}

		if (this.elements.progress)
		{
			Dom.clean(this.elements.progress);
			this.elements.progress.appendChild(this.renderProgress(this.progress));
		}

		if (this.elements.status)
		{
			this.elements.status.innerText = this.statusText;
		}

		this.renderButtons();
		this.renderTimer();
	};

	renderProgress(progress: $Keys<typeof CallProgress>): HTMLElement
	{
		let result;
		switch (progress)
		{
			case CallProgress.connect:
				result = Dom.create("div", {
					props: {className: 'bx-messenger-call-overlay-progress'}, children: [
						Dom.create("img", {props: {className: 'bx-messenger-call-overlay-progress-status bx-messenger-call-overlay-progress-status-anim-1'}}),
						Dom.create("img", {props: {className: 'bx-messenger-call-overlay-progress-status bx-messenger-call-overlay-progress-status-anim-2'}})
					]
				});
				break;
			case CallProgress.online:
				result = Dom.create("div", {
					props: {className: 'bx-messenger-call-overlay-progress bx-messenger-call-overlay-progress-online'},
					children: [
						Dom.create("img", {props: {className: 'bx-messenger-call-overlay-progress-status bx-messenger-call-overlay-progress-status-anim-3'}})
					]
				});
				break;
			case CallProgress.error:
				progress = CallProgress.offline;
			// fallthrough to default
			default:
				result = Dom.create("div", {props: {className: 'bx-messenger-call-overlay-progress bx-messenger-call-overlay-progress-' + progress}});
		}

		return result;
	};

	/**
	 * @param uiState UiState
	 * @returns object {buttons: string[], layout: string}
	 */
	getUiStateButtons(uiState)
	{
		var result = {
			buttons: [],
			layout: ButtonLayouts.centered
		};
		switch (uiState)
		{
			case UiState.incoming:
				result.buttons = ['answer', 'skip'];

				break;
			case UiState.transferIncoming:
				result.buttons = ['answer', 'skip'];
				break;
			case UiState.outgoing:
				result.buttons = ['call'];

				if (this.callListId > 0)
				{
					result.buttons.push('next');
					result.buttons.push('fold');

					if (!this.isDesktop())
					{
						result.buttons.push('topClose');
					}
				}
				break;
			case UiState.connectingIncoming:
				result.buttons = ['hangup'];

				break;
			case UiState.connectingOutgoing:
				if (this.hasSipPhone)
				{
					result.buttons.push('sipPhone');
				}
				result.buttons.push('hangup');
				break;
			case UiState.error:
				if (this.hasSipPhone)
				{
					result.buttons.push('sipPhone');
				}
				if (this.callListId > 0)
				{
					result.buttons.push('redial', 'next', 'topClose');
				}
				else
				{
					result.buttons.push('close');
				}
				break;
			case UiState.moneyError:
				result.buttons = ['notifyAdmin', 'close'];
				break;
			case UiState.sipPhoneError:
				result.buttons = ['sipPhone', 'close'];
				break;
			case UiState.connected:
				result.buttons = this.isTransfer() ? [] : ['hold'];
				if (!this.deviceCall)
				{
					result.buttons.push('mute', 'qualityMeter');
				}
				result.buttons.push('fold');

				if (!this.callListId && !this.isTransfer())
				{
					result.buttons.push('transfer');
				}

				if (this.deviceCall)
				{
					result.buttons.push('close');
				}
				else
				{
					result.buttons.push('dialpad', 'hangup');
				}

				result.layout = ButtonLayouts.spaced;
				break;
			case UiState.transferring:
				result.buttons = ['transferComplete', 'transferCancel'];
				break;
			case UiState.transferFailed:
				result.buttons = ['transferCancel'];
				break;
			case UiState.transferConnected:
				result.buttons = ['hangup'];
				break;
			case UiState.idle:
				if (this.hasSipPhone)
				{
					result.buttons = ['close'];
				}
				else if (this.direction == Direction.incoming)
				{
					result.buttons = ['close'];
				}
				else if (this.direction == Direction.outgoing)
				{
					result.buttons = ['redial'];
					if (this.callListId > 0)
					{
						result.buttons.push('next');
						result.buttons.push('fold');
					}
					else
					{
						result.buttons.push('close');
					}
				}
				if (this.callListId > 0 && !this.isDesktop())
				{
					result.buttons.push('topClose');
				}
				break;
			case UiState.redial:
				result.buttons = ['redial'];
				break;
			case UiState.externalCard:
				result.buttons = ['close'];
				result.buttons.push('fold');
				break;
		}

		return result;
	};

	renderButtons()
	{
		if (this.isFolded())
		{
			this.renderButtonsFolded();
		}
		else
		{
			this.renderButtonsDefault();
		}
	};

	renderButtonsDefault()
	{
		var buttonsFragment = document.createDocumentFragment();
		var topButtonsFragment = document.createDocumentFragment();
		var topLevelButtonsFragment = document.createDocumentFragment();
		var subContainers = {
			left: null,
			right: null
		};
		this.elements.buttons = {};
		if (this.buttonLayout == ButtonLayouts.spaced)
		{
			subContainers.left = Dom.create('div', {props: {className: 'im-phone-call-buttons-container-left'}});
			subContainers.right = Dom.create('div', {props: {className: 'im-phone-call-buttons-container-right'}});
			buttonsFragment.appendChild(subContainers.left);
			buttonsFragment.appendChild(subContainers.right);
		}

		this.buttons.forEach((buttonName) =>
		{
			let buttonNode;

			switch (buttonName)
			{
				case 'hold':
					buttonNode = renderSimpleButton('', 'im-phone-call-btn-hold', this._onHoldButtonClickHandler);
					if (this.isHeld())
					{
						Dom.addClass(buttonNode, 'active');
					}

					if (this.buttonLayout == ButtonLayouts.spaced)
					{
						subContainers.left.appendChild(buttonNode);
					}
					else
					{
						buttonsFragment.appendChild(buttonNode);
					}

					break;
				case 'mute':
					buttonNode = renderSimpleButton('', 'im-phone-call-btn-mute', this._onMuteButtonClickHandler);
					if (this.isMuted())
					{
						Dom.addClass(buttonNode, 'active');
					}

					if (this.buttonLayout == ButtonLayouts.spaced)
					{
						subContainers.left.appendChild(buttonNode);
					}
					else
					{
						buttonsFragment.appendChild(buttonNode);
					}

					break;
				case 'transfer':
					buttonNode = renderSimpleButton('', 'im-phone-call-btn-transfer', this._onTransferButtonClickHandler);
					if (this.buttonLayout == ButtonLayouts.spaced)
					{
						subContainers.left.appendChild(buttonNode);
					}
					else
					{
						buttonsFragment.appendChild(buttonNode);
					}

					break;
				case 'transferComplete':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_M_CALL_BTN_TRANSFER'),
						'im-phone-call-btn im-phone-call-btn-blue im-phone-call-btn-arrow',
						this._onTransferCompleteButtonClickHandler
					);
					buttonsFragment.appendChild(buttonNode);
					break;
				case 'transferCancel':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_M_CALL_BTN_RETURN'),
						'im-phone-call-btn im-phone-call-btn-red',
						this._onTransferCancelButtonClickHandler
					);
					buttonsFragment.appendChild(buttonNode);
					break;
				case 'dialpad':
					buttonNode = renderSimpleButton('', 'im-phone-call-btn-dialpad', this._onDialpadButtonClickHandler);
					if (this.buttonLayout == ButtonLayouts.spaced)
					{
						subContainers.left.appendChild(buttonNode);
					}
					else
					{
						buttonsFragment.appendChild(buttonNode);
					}

					break;
				case 'call':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_PHONE_CALL'),
						'im-phone-call-btn im-phone-call-btn-green',
						this._onMakeCallButtonClickHandler
					);
					buttonsFragment.appendChild(buttonNode);
					break;
				case 'answer':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_PHONE_BTN_ANSWER'),
						'im-phone-call-btn im-phone-call-btn-green',
						this._onAnswerButtonClickHandler
					);
					buttonsFragment.appendChild(buttonNode);
					break;
				case 'skip':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_PHONE_BTN_BUSY'),
						'im-phone-call-btn im-phone-call-btn-red',
						this._onSkipButtonClickHandler
					);
					buttonsFragment.appendChild(buttonNode);
					break;
				case 'hangup':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_M_CALL_BTN_HANGUP'),
						'im-phone-call-btn im-phone-call-btn-red  im-phone-call-btn-tube',
						this._onHangupButtonClickHandler
					);
					if (this.buttonLayout == ButtonLayouts.spaced)
					{
						subContainers.right.appendChild(buttonNode);
					}
					else
					{
						buttonsFragment.appendChild(buttonNode);
					}

					break;
				case 'close':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_M_CALL_BTN_CLOSE'),
						'im-phone-call-btn im-phone-call-btn-red',
						this._onCloseButtonClickHandler
					);
					if (this.buttonLayout == ButtonLayouts.spaced)
					{
						subContainers.right.appendChild(buttonNode);
					}
					else
					{
						buttonsFragment.appendChild(buttonNode);
					}

					break;
				case 'topClose':
					if (!this.isDesktop())
					{
						buttonNode = Dom.create("div", {
							props: {className: 'im-phone-call-top-close-btn'},
							events: {
								click: this._onCloseButtonClickHandler
							}
						});
						topLevelButtonsFragment.appendChild(buttonNode);
					}
					break;
				case 'notifyAdmin':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_M_CALL_BTN_NOTIFY_ADMIN'),
						'im-phone-call-btn im-phone-call-btn-blue im-phone-call-btn-arrow',
						() =>
						{
							this.backgroundWorker.isUsed
								? this.backgroundWorker.emitEvent(backgroundWorkerEvents.notifyAdminButtonClick)
								: this.callbacks.notifyAdmin()
							;
						}
					);
					buttonsFragment.appendChild(buttonNode);
					break;
				case 'sipPhone':
					buttonNode = renderSimpleButton('', (this.deviceCall ? 'im-phone-call-btn-phone active' : 'im-phone-call-btn-phone'), this._onSwitchDeviceButtonClickHandler);
					if (this.buttonLayout == ButtonLayouts.spaced)
					{
						subContainers.left.appendChild(buttonNode);
					}
					else
					{
						buttonsFragment.appendChild(buttonNode);
					}
					break;
				case 'qualityMeter':
					buttonNode = Dom.create("span", {
						props: {className: 'im-phone-call-btn-signal'},
						events: {click: this._onQualityMeterClickHandler},
						children: [
							Dom.create("span", {
								props: {className: 'im-phone-call-btn-signal-icon-container'}, children: [
									Dom.create("span", {props: {className: 'im-phone-call-btn-signal-background'}}),
									this.elements.qualityMeter = Dom.create("span", {
										props: {className: 'im-phone-call-btn-signal-active'},
										style: {width: this.getQualityMeterWidth()}
									})
								]
							})
						]
					});
					buttonsFragment.appendChild(buttonNode);

					break;
				case 'settings':
					// todo
					break;
				case 'next':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_M_CALL_BTN_NEXT'),
						'im-phone-call-btn im-phone-call-btn-gray im-phone-call-btn-arrow',
						this._onNextButtonClickHandler
					);
					buttonsFragment.appendChild(buttonNode);
					break;
				case 'redial':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_M_CALL_BTN_RECALL'),
						'im-phone-call-btn im-phone-call-btn-green',
						this._onMakeCallButtonClickHandler
					);
					buttonsFragment.appendChild(buttonNode);
					break;
				case 'fold':
					if (!this.isDesktop() && this.canBeFolded())
					{
						buttonNode = Dom.create("div", {
							props: {className: 'im-phone-btn-arrow'},
							text: Loc.getMessage('IM_PHONE_CALL_VIEW_FOLD'),
							events: {
								click: this._onFoldButtonClickHandler
							}
						});
						topButtonsFragment.appendChild(buttonNode);
					}
					break;
				default:
					throw "Unknown button " + buttonName;
			}

			if (buttonNode)
			{
				this.elements.buttons[buttonName] = buttonNode;
			}
		});
		if (this.elements.buttonsContainer)
		{
			Dom.clean(this.elements.buttonsContainer);
			this.elements.buttonsContainer.appendChild(buttonsFragment);
		}
		if (this.elements.topButtonsContainer)
		{
			Dom.clean(this.elements.topButtonsContainer);
			this.elements.topButtonsContainer.appendChild(topButtonsFragment);
		}
		if (this.elements.topLevelButtonsContainer)
		{
			Dom.clean(this.elements.topLevelButtonsContainer);
			this.elements.topLevelButtonsContainer.appendChild(topLevelButtonsFragment);
		}
	};

	renderButtonsFolded()
	{
		let buttonsFragment = document.createDocumentFragment();
		let buttonNode;
		this.elements.buttons = {};

		this.buttons.forEach((buttonName) =>
		{
			switch (buttonName)
			{
				case 'hangup':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_M_CALL_BTN_HANGUP'),
						'im-phone-call-panel-mini-cancel',
						this._onHangupButtonClickHandler
					);
					buttonsFragment.appendChild(buttonNode);

					break;
				case 'close':
					buttonNode = renderSimpleButton(
						Loc.getMessage('IM_M_CALL_BTN_CLOSE'),
						'im-phone-call-panel-mini-cancel',
						this._onCloseButtonClickHandler
					);
					buttonsFragment.appendChild(buttonNode);
					break;
			}
		});

		if (this.elements.buttonsContainer)
		{
			Dom.clean(this.elements.buttonsContainer);
			this.elements.buttonsContainer.appendChild(buttonsFragment);
		}
	};

	renderCrmButtons()
	{
		let buttonsFragment = document.createDocumentFragment();
		this.elements.crmButtons = {};

		if (!this.elements.crmButtonsContainer)
		{
			return;
		}

		let buttons = ['addComment'];

		if (this.crmEntityType == 'CONTACT')
		{
			buttons.push('addDeal');
			buttons.push('addInvoice');
		}
		else if (this.crmEntityType == 'COMPANY')
		{
			buttons.push('addDeal');
			buttons.push('addInvoice');
		}
		else if (!this.crmEntityType && this.config.CRM_CREATE == 'none')
		{
			buttons.push('addLead');
			buttons.push('addContact');
		}

		if (buttons.length > 0)
		{
			buttons.forEach((buttonName) =>
			{
				let buttonNode;
				switch (buttonName)
				{
					case 'addComment':
						buttonNode = Dom.create("div", {
							props: {className: 'im-phone-call-crm-button im-phone-call-crm-button-comment' + (this.commentShown ? ' im-phone-call-crm-button-active' : '')},
							children: [
								this.elements.crmButtons.addCommentLabel = Dom.create("div", {
									props: {className: 'im-phone-call-crm-button-item'},
									text: this.commentShown ? Loc.getMessage('IM_PHONE_CALL_VIEW_SAVE') : Loc.getMessage('IM_PHONE_ACTION_CRM_COMMENT')
								})],
							events: {click: this._onAddCommentButtonClick.bind(this)}
						});
						break;
					case 'addDeal':
						buttonNode = Dom.create("div", {
							props: {className: 'im-phone-call-crm-button'}, children: [
								Dom.create("div", {
									props: {className: 'im-phone-call-crm-button-item'},
									text: Loc.getMessage('IM_PHONE_ACTION_CRM_DEAL')
								})],
							events: {click: this._onAddDealButtonClick.bind(this)}
						});
						break;
					case 'addInvoice':
						buttonNode = Dom.create("div", {
							props: {className: 'im-phone-call-crm-button'}, children: [
								Dom.create("div", {
									props: {className: 'im-phone-call-crm-button-item'},
									text: Loc.getMessage('IM_PHONE_ACTION_CRM_INVOICE')
								})],
							events: {click: this._onAddInvoiceButtonClick.bind(this)}
						});
						break;
					case 'addLead':
						buttonNode = Dom.create("div", {
							props: {className: 'im-phone-call-crm-button'}, children: [
								Dom.create("div", {
									props: {className: 'im-phone-call-crm-button-item'},
									text: Loc.getMessage('IM_CRM_BTN_NEW_LEAD')
								})],
							events: {click: this._onAddLeadButtonClick.bind(this)}
						});
						break;
					case 'addContact':
						buttonNode = Dom.create("div", {
							props: {className: 'im-phone-call-crm-button'}, children: [
								Dom.create("div", {
									props: {className: 'im-phone-call-crm-button-item'},
									text: Loc.getMessage('IM_CRM_BTN_NEW_CONTACT')
								})],
							events: {click: this._onAddContactButtonClick.bind(this)}
						});
						break;
				}
				if (buttonNode)
				{
					buttonsFragment.appendChild(buttonNode);
					this.elements.crmButtons[buttonName] = buttonNode;
				}
			});

			Dom.clean(this.elements.crmButtonsContainer);
			this.elements.crmButtonsContainer.appendChild(buttonsFragment);
			this.showSections(['crmButtons']);
		}
		else
		{
			Dom.clean(this.elements.crmButtonsContainer);
			this.hideSections(['crmButtons']);
		}
	};

	loadForm(params)
	{
		if (!this.formManager)
		{
			return;
		}

		this.formManager.load({
			id: params.id,
			secCode: params.secCode
		})
	};

	unloadForm()
	{
		if (!this.formManager)
		{
			return;
		}

		this.formManager.unload();
		Dom.clean(this.elements.tabsBody.webform);
	};

	_onFormSend(e)
	{
		if (!this.callListView)
		{
			return;
		}

		var currentElement = this.callListView.getCurrentElement();
		this.callListView.setWebformResult(currentElement.ELEMENT_ID, e.resultId);
	};

	loadRestApp(params)
	{
		var restAppId = params.id;
		var node = params.node;

		if (this.restAppLayoutLoaded)
		{
			BX.rest.AppLayout.getPlacement('CALL_CARD').load(restAppId, this.getPlacementOptions());
			return;
		}

		if (this.restAppLayoutLoading)
		{
			return;
		}
		this.restAppLayoutLoading = true;

		BX.ajax.runAction("voximplant.callView.loadRestApp", {
			data: {
				'appId': restAppId,
				'placementOptions': this.getPlacementOptions()
			}
		}).then((response) =>
		{
			if (!this.popup && !this.isDesktop())
			{
				return;
			}
			Runtime.html(node, response.data.html);
			this.restAppLayoutLoaded = true;
			this.restAppLayoutLoading = false;
			this.restAppInterface = BX.rest.AppLayout.initializePlacement('CALL_CARD');
			this.initializeAppInterface(this.restAppInterface);
		});
	};

	unloadRestApps()
	{
		if (!BX.rest || !BX.rest.AppLayout)
		{
			return false;
		}

		var placement = BX.rest.AppLayout.getPlacement('CALL_CARD');
		if (this.restAppLayoutLoaded && placement)
		{
			placement.destroy();
			this.restAppLayoutLoaded = false;
		}
	};

	initializeAppInterface(appInterface)
	{
		appInterface.prototype.events.push('CallCard::EntityChanged');
		appInterface.prototype.events.push('CallCard::BeforeClose');
		appInterface.prototype.events.push('CallCard::CallStateChanged');
		appInterface.prototype.getStatus = (params, cb) =>
		{
			cb(this.getPlacementOptions());
		};

		appInterface.prototype.disableAutoClose = (params, cb) =>
		{
			this.disableAutoClose();
			cb([]);
		};

		appInterface.prototype.enableAutoClose = (params, cb) =>
		{
			this.enableAutoClose();
			cb([]);
		};
	};

	getPlacementOptions()
	{
		return {
			'CALL_ID': this.callId,
			'PHONE_NUMBER': this.phoneNumber === "unknown" ? undefined : this.phoneNumber,
			'LINE_NUMBER': this.lineNumber,
			'LINE_NAME': this.companyPhoneNumber,
			'CRM_ENTITY_TYPE': this.crmEntityType,
			'CRM_ENTITY_ID': this.crmEntityId,
			'CRM_ACTIVITY_ID': this.crmActivityId === 0 ? undefined : this.crmActivityId,
			'CRM_BINDINGS': this.crmBindings,
			'CALL_DIRECTION': this.direction,
			'CALL_STATE': this.callState,
			'CALL_LIST_MODE': this.callListId > 0
		}
	};

	isUnloadAllowed()
	{
		if (this.backgroundWorker.isActiveIntoCurrentCall())
		{
			return false;
		}

		return this.folded &&
			(
				this.deviceCall ||
				this._uiState === UiState.idle ||
				this._uiState === UiState.error ||
				this._uiState === UiState.externalCard
			);
	};

	_onBeforeUnload(e)
	{
		if (!this.isUnloadAllowed())
		{
			e.returnValue = Loc.getMessage('IM_PHONE_CALL_VIEW_DONT_LEAVE');
			return Loc.getMessage('IM_PHONE_CALL_VIEW_DONT_LEAVE');
		}
	};

	_onDblClick(e: Event)
	{
		e.preventDefault()
		if (!this.isFolded() && this.canBeFolded())
		{
			this.fold();
		}
	};

	_onHoldButtonClick()
	{
		if (this.isHeld())
		{
			this.held = false;
			Dom.removeClass(this.elements.buttons.hold, 'active');
			if (this.isDesktop() && this.slave)
			{
				DesktopApi.emit(desktopEvents.onUnHold, []);
			}
			else
			{
				this.callbacks.unhold();
			}
		}
		else
		{
			this.held = true;
			Dom.addClass(this.elements.buttons.hold, 'active');
			if (this.isDesktop() && this.slave)
			{
				DesktopApi.emit(desktopEvents.onHold, []);
			}
			else
			{
				this.callbacks.hold();
			}
		}
		this.backgroundWorker.emitEvent(backgroundWorkerEvents.holdButtonClick, this.isHeld())
	};

	_onMuteButtonClick()
	{
		if (this.isMuted())
		{
			this.muted = false;
			Dom.removeClass(this.elements.buttons.mute, 'active');
			if (this.isDesktop() && this.slave)
			{
				DesktopApi.emit(desktopEvents.onUnMute, []);
			}
			else
			{
				this.callbacks.unmute();
			}
		}
		else
		{
			this.muted = true;
			Dom.addClass(this.elements.buttons.mute, 'active');
			if (this.isDesktop() && this.slave)
			{
				DesktopApi.emit(desktopEvents.onMute, []);
			}
			else
			{
				this.callbacks.mute();
			}
		}
		this.backgroundWorker.emitEvent(backgroundWorkerEvents.muteButtonClick, this.isMuted());
	};

	_onTransferButtonClick()
	{
		this.selectTransferTarget((result) =>
		{
			this.backgroundWorker.emitEvent(backgroundWorkerEvents.transferButtonClick, result)
			if (this.isDesktop() && this.slave)
			{
				DesktopApi.emit(desktopEvents.onStartTransfer, [result]);
			}
			else
			{
				this.callbacks.transfer(result);
			}
		});
	};

	_onTransferCompleteButtonClick()
	{
		this.backgroundWorker.emitEvent(backgroundWorkerEvents.completeTransferButtonClick);
		if (this.isDesktop() && this.slave)
		{
			DesktopApi.emit(desktopEvents.onCompleteTransfer, []);
		}
		else
		{
			this.callbacks.completeTransfer();
		}
	};

	_onTransferCancelButtonClick()
	{
		this.backgroundWorker.emitEvent(backgroundWorkerEvents.cancelTransferButtonClick);
		if (this.isDesktop() && this.slave)
		{
			DesktopApi.emit(desktopEvents.onCancelTransfer, []);
		}
		else
		{
			this.callbacks.cancelTransfer();
		}
	};

	_onDialpadButtonClick()
	{
		this.keypad = new Keypad({
			bindElement: this.elements.buttons.dialpad,
			hideDial: true,
			onButtonClick: (e) =>
			{
				var key = e.key;
				if (this.isDesktop() && this.slave)
				{
					DesktopApi.emit(desktopEvents.onDialpadButtonClicked, [key]);
				}
				else
				{
					this.callbacks.dialpadButtonClicked(key);
				}
				this.backgroundWorker.emitEvent(backgroundWorkerEvents.dialpadButtonClick, key);
			},
			onClose: () =>
			{
				this.keypad.destroy();
				this.keypad = null;
			}
		});
		this.keypad.show();
	};

	_onHangupButtonClick()
	{
		this.backgroundWorker.emitEvent(backgroundWorkerEvents.hangupButtonClick);
		if (this.isDesktop() && this.slave)
		{
			DesktopApi.emit(desktopEvents.onHangup, []);
		}
		else
		{
			this.callbacks.hangup();
		}
	};

	_onCloseButtonClick()
	{
		this.backgroundWorker.emitEvent(backgroundWorkerEvents.closeButtonClick);
		if (this.isDesktop() && this.slave)
		{
			DesktopApi.emit(desktopEvents.onClose, []);
		}
		else
		{
			this.close();
		}
	};

	_onMakeCallButtonClick()
	{
		this.backgroundWorker.emitEvent(backgroundWorkerEvents.makeCallButtonClick);
		var event = {};
		if (this.callListId > 0)
		{
			this.callingEntity = this.currentEntity;

			if (this.currentEntity.phones.length === 0)
			{
				// show keypad and dial entered number
				this.keypad = new Keypad({
					bindElement: this.elements.buttons.call ? this.elements.buttons.call : null,
					onClose: () =>
					{
						this.keypad.destroy();
						this.keypad = null;
					},
					onDial: (e) =>
					{
						this.keypad.close();
						this.phoneNumber = e.phoneNumber;
						this.createTitle().then(title => this.setTitle(title));

						event = {
							phoneNumber: e.phoneNumber,
							crmEntityType: this.crmEntityType,
							crmEntityId: this.crmEntityId,
							callListId: this.callListId
						};

						if (this.isDesktop() && this.slave)
						{
							DesktopApi.emit(desktopEvents.onCallListMakeCall, [event]);
						}
						else
						{
							this.callbacks.callListMakeCall(event);
						}
					}
				});
				this.keypad.show();
			}
			else if (this.currentEntity.phones.length == 1)
			{
				// just dial the number
				event.phoneNumber = this.currentEntity.phones[0].VALUE;
				event.crmEntityType = this.crmEntityType;
				event.crmEntityId = this.crmEntityId;
				event.callListId = this.callListId;
				if (this.isDesktop() && this.slave)
				{
					DesktopApi.emit(desktopEvents.onCallListMakeCall, [event]);
				}
				else
				{
					this.callbacks.callListMakeCall(event);
				}
			}
			else
			{
				// allow user to select the number
				this.showNumberSelectMenu({
					bindElement: this.elements.buttons.call ? this.elements.buttons.call : null,
					phoneNumbers: this.currentEntity.phones,
					onSelect: (e) =>
					{
						this.closeNumberSelectMenu();
						this.phoneNumber = e.phoneNumber;
						this.createTitle().then(title => this.setTitle(title));

						event = {
							phoneNumber: e.phoneNumber,
							crmEntityType: this.crmEntityType,
							crmEntityId: this.crmEntityId,
							callListId: this.callListId
						};

						if (this.isDesktop() && this.slave)
						{
							DesktopApi.emit(desktopEvents.onCallListMakeCall, [event]);
						}
						else
						{
							this.callbacks.callListMakeCall(event);
						}
					}
				});
			}
		}
		else
		{
			if (this.isDesktop() && this.slave)
			{
				DesktopApi.emit(desktopEvents.onMakeCall, [this.phoneNumber]);
			}
			else
			{
				this.callbacks.makeCall(this.phoneNumber);
			}
		}
	};

	_onNextButtonClick()
	{
		if (!this.callListView)
		{
			return;
		}

		this.backgroundWorker.emitEvent(backgroundWorkerEvents.nextButtonClick);
		this.setUiState(UiState.outgoing);
		this.callListView.moveToNextItem();
		this.setStatusText('');
	};

	_onRedialButtonClick(e)
	{

	};

	_onCommentChanged()
	{
		this.comment = this.elements.commentEditor.value;
		//Update callView close timer when printing a comment
		this.updateAutoCloseTimer();
	};

	_onAddCommentButtonClick()
	{
		this.commentShown = !this.commentShown;
		if (this.isDesktop() && this.slave)
		{
			DesktopApi.emit(desktopEvents.onCommentShown, [this.commentShown]);
		}

		if (this.commentShown)
		{
			if (this.elements.crmButtons.addComment)
			{
				Dom.addClass(this.elements.crmButtons.addComment, 'im-phone-call-crm-button-active');
				this.elements.crmButtons.addCommentLabel.innerText = Loc.getMessage('IM_PHONE_CALL_VIEW_SAVE');
			}
			if (this.elements.commentEditor)
			{
				this.elements.commentEditor.value = this.comment;
				this.elements.commentEditor.focus();
			}

			if (this.elements.commentEditorContainer)
			{
				this.elements.commentEditorContainer.style.removeProperty('display');
			}
		}
		else
		{

			if (this.elements.crmButtons.addComment)
			{
				Dom.removeClass(this.elements.crmButtons.addComment, 'im-phone-call-crm-button-active');
				this.elements.crmButtons.addCommentLabel.innerText = Loc.getMessage('IM_PHONE_ACTION_CRM_COMMENT');
			}

			if (this.elements.commentEditorContainer)
			{
				this.elements.commentEditorContainer.style.display = 'none';
			}

			if (this.isDesktop() && this.slave)
			{
				DesktopApi.emit(desktopEvents.onSaveComment, [this.comment]);
			}
			else
			{
				this.saveComment();
			}

			this.backgroundWorker.emitEvent(backgroundWorkerEvents.addCommentButtonClick, this.comment);
		}
	};

	_onAddDealButtonClick()
	{
		var url = this._getCrmEditUrl('DEAL', 0);
		var externalContext = this._generateExternalContext();
		if (this.crmEntityType === 'CONTACT')
		{
			url = Uri.addParam(url, {contact_id: this.crmEntityId});
		}
		else if (this.crmEntityType === 'COMPANY')
		{
			url = Uri.addParam(url, {company_id: this.crmEntityId});
		}

		url = Uri.addParam(url, {external_context: externalContext});
		if (this.callListId > 0)
		{
			url = Uri.addParam(url, {call_list_id: this.callListId});
			url = Uri.addParam(url, {call_list_element: this.currentEntity.id});
		}

		this.externalRequests[externalContext] = {
			type: 'add',
			context: externalContext,
			window: window.open(url)
		};
	};

	_onAddInvoiceButtonClick()
	{
		let url = this._getCrmEditUrl('INVOICE', 0);
		const externalContext = this._generateExternalContext();

		url = Uri.addParam(url, {redirect: "y"});
		if (this.crmEntityType === 'CONTACT')
		{
			url = Uri.addParam(url, {contact: this.crmEntityId});
		}
		else if (this.crmEntityType === 'COMPANY')
		{
			url = Uri.addParam(url, {company: this.crmEntityId});
		}

		url = Uri.addParam(url, {external_context: externalContext});
		if (this.callListId > 0)
		{
			url = Uri.addParam(url, {call_list_id: this.callListId});
			url = Uri.addParam(url, {call_list_element: this.currentEntity.id});
		}

		this.externalRequests[externalContext] = {
			type: 'add',
			context: externalContext,
			window: window.open(url)
		};
	};

	_onAddLeadButtonClick()
	{
		let url = this._getCrmEditUrl('LEAD', 0);
		url = Uri.addParam(url, {
			phone: this.phoneNumber,
			origin_id: 'VI_' + this.callId
		});
		window.open(url);
	};

	_onAddContactButtonClick()
	{
		let url = this._getCrmEditUrl('CONTACT', 0);
		url = Uri.addParam(url, {
			phone: this.phoneNumber,
			origin_id: 'VI_' + this.callId
		});
		window.open(url);
	};

	_onFoldButtonClick()
	{
		this.fold();
	};

	_onAnswerButtonClick()
	{
		this.backgroundWorker.emitEvent(backgroundWorkerEvents.answerButtonClick);
		if (this.isDesktop() && this.slave)
		{
			DesktopApi.emit(desktopEvents.onAnswer, []);
		}
		else
		{
			this.callbacks.answer();
		}
	};

	_onSkipButtonClick()
	{
		this.backgroundWorker.emitEvent(backgroundWorkerEvents.skipButtonClick);
		if (this.isDesktop() && this.slave)
		{
			DesktopApi.emit(desktopEvents.onSkip, []);
		}
		else
		{
			this.callbacks.skip();
		}
	};

	_onSwitchDeviceButtonClick()
	{
		if (this.isDesktop() && this.slave)
		{
			DesktopApi.emit(desktopEvents.onSwitchDevice, [{
				phoneNumber: this.phoneNumber
			}]);
		}
		else
		{
			this.callbacks.switchDevice({
				phoneNumber: this.phoneNumber
			});
		}
	};

	_onQualityMeterClick()
	{
		this.showQualityPopup({
			onSelect: (qualityGrade) =>
			{
				this.backgroundWorker.emitEvent(backgroundWorkerEvents.qualityMeterClick, qualityGrade);
				this.qualityGrade = qualityGrade;
				this.closeQualityPopup();
				if (this.isDesktop() && this.slave)
				{
					DesktopApi.emit(desktopEvents.onQualityGraded, [qualityGrade]);
				}
				else
				{
					this.callbacks.qualityGraded(qualityGrade);
				}
			}
		});
	};

	#onExternalEvent = (params) =>
	{
		console.warn('#onExternalEvent', params)
		return
		params = Type.isPlainObject(params) ? params : {};
		params.key = params.key || '';

		var value = params.value || {};
		value.entityTypeName = value.entityTypeName || '';
		value.context = value.context || '';
		value.isCanceled = Type.isBoolean(value.isCanceled) ? value.isCanceled : false;

		if (value.isCanceled)
		{
			return;
		}

		if (params.key === "onCrmEntityCreate" && this.externalRequests[value.context])
		{
			if (this.externalRequests[value.context])
			{
				if (this.externalRequests[value.context]['type'] == 'create')
				{
					this.crmEntityType = value.entityTypeName;
					this.crmEntityId = value.entityInfo.id;
					this.loadCrmCard(this.crmEntityType, this.crmEntityId);
				}
				else if (this.externalRequests[value.context]['type'] == 'add')
				{
					// reload crm card
					this.loadCrmCard(this.crmEntityType, this.crmEntityId);
				}

				if (this.externalRequests[value.context]['window'])
				{
					this.externalRequests[value.context]['window'].close();
				}

				delete this.externalRequests[value.context];
			}
		}
	};

	_onPullEventCrm(command, params)
	{
		if (command === 'external_event')
		{
			if (params.NAME === 'onCrmEntityCreate' && params.IS_CANCELED == false)
			{
				var eventParams = params.PARAMS;
				if (this.externalRequests[eventParams.context])
				{
					var crmEntityType = eventParams.entityTypeName;
					var crmEntityId = eventParams.entityInfo.id;

					if (this.callListView)
					{
						var currentElement = this.callListView.getCurrentElement();
					}
				}
			}
		}
	};

	onCallListSelectedItem(entity)
	{
		this.currentEntity = entity;
		this.crmEntityType = entity.type;
		this.crmEntityId = entity.id;
		this.comment = "";

		if (Type.isArray(entity.bindings))
		{
			this.crmBindings = entity.bindings.map((value) =>
				{
					return {'ENTITY_TYPE': value.type, 'ENTITY_ID': value.id}
				}
			)
		}
		else
		{
			this.crmBindings = [];
		}

		if (entity.phones.length > 0)
		{
			this.phoneNumber = entity.phones[0].VALUE;
		}
		else
		{
			this.phoneNumber = 'unknown';
		}

		this.createTitle().then(title => this.setTitle(title));
		this.loadCrmCard(entity.type, entity.id);
		if (this.currentTabName === 'webform')
		{
			this.formManager.unload();
			this.formManager.load({
				id: this.webformId,
				secCode: this.webformSecCode,
				lang: Loc.getMessage("LANGUAGE_ID"),
			})
		}
		if (this._uiState === UiState.redial)
		{
			this.setUiState(UiState.outgoing);
		}

		this.updateView();
	};

	#onWindowUnload = () =>
	{
		console.log('onWindowUnload call view event', location.href, DesktopApi.isChatWindow());
		this.close();
	}

	showCallIcon()
	{
		if (!this.callListView)
		{
			return;
		}

		if (!this.callingEntity)
		{
			return;
		}

		this.callListView.setCallingElement(this.callingEntity.statusId, this.callingEntity.index);
	};

	hideCallIcon()
	{
		if (!this.callListView)
		{
			return;
		}

		this.callListView.resetCallingElement();
	};

	isTimerStarted()
	{
		return !!this.timerInterval;
	}

	startTimer()
	{
		if (this.isTimerStarted())
		{
			return;
		}

		if (this.initialTimestamp === 0)
		{
			this.initialTimestamp = (new Date()).getTime();
		}
		this.timerInterval = setInterval(this.renderTimer.bind(this), 1000);
		this.renderTimer();
	};

	renderTimer()
	{
		if (!this.elements.timer)
		{
			return;
		}

		let currentTimestamp = (new Date()).getTime();
		let elapsedMilliSeconds = (currentTimestamp - this.initialTimestamp);

		let elapsedSeconds = Math.floor(elapsedMilliSeconds / 1000);
		let minutes = Math.floor(elapsedSeconds / 60).toString();
		if (minutes.length < 2)
		{
			minutes = '0' + minutes;
		}
		let seconds = (elapsedSeconds % 60).toString();
		if (seconds.length < 2)
		{
			seconds = '0' + seconds;
		}
		const template = (this.isRecording() ? Loc.getMessage('IM_PHONE_TIMER_WITH_RECORD') : Loc.getMessage('IM_PHONE_TIMER_WITHOUT_RECORD'));

		if (this.isFolded())
		{
			this.elements.timer.innerText = minutes + ':' + seconds;
		}
		else
		{
			this.elements.timer.innerText = template.replace('#MIN#', minutes).replace('#SEC#', seconds);
		}
	};

	stopTimer()
	{
		if (!this.isTimerStarted())
		{
			return;
		}

		clearInterval(this.timerInterval);
		this.timerInterval = null;
	};

	showQualityPopup(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		if (!Type.isFunction(params.onSelect))
		{
			params.onSelect = nop;
		}

		const elements = {
			'1': null,
			'2': null,
			'3': null,
			'4': null,
			'5': null
		};

		this.qualityPopup = new Popup({
			id: 'PhoneCallViewQualityGrade',
			bindElement: this.elements.qualityMeter,
			targetContainer: document.body,
			darkMode: true,
			closeByEsc: true,
			autoHide: true,
			zIndex: baseZIndex + 200,
			noAllPaddings: true,
			overlay: {
				backgroundColor: 'white',
				opacity: 0
			},
			bindOptions: {
				position: 'top'
			},
			angle: {
				position: 'bottom',
				offset: 30
			},
			cacheable: false,
			content: Dom.create("div", {
				props: {className: 'im-phone-popup-rating'}, children: [
					Dom.create("div", {
						props: {className: 'im-phone-popup-rating-title'},
						text: Loc.getMessage('IM_PHONE_CALL_VIEW_RATE_QUALITY')
					}),
					Dom.create("div", {
						props: {className: 'im-phone-popup-rating-stars'}, children: [
							elements['1'] = createStar(1, this.qualityGrade == '1', params.onSelect),
							elements['2'] = createStar(2, this.qualityGrade == '2', params.onSelect),
							elements['3'] = createStar(3, this.qualityGrade == '3', params.onSelect),
							elements['4'] = createStar(4, this.qualityGrade == '4', params.onSelect),
							elements['5'] = createStar(5, this.qualityGrade == '5', params.onSelect)
						], events: {
							mouseover: () =>
							{
								if (elements[this.qualityGrade])
								{
									Dom.removeClass(elements[this.qualityGrade], 'im-phone-popup-rating-stars-item-active');
								}
							},
							mouseout: () =>
							{
								if (elements[this.qualityGrade])
								{
									Dom.addClass(elements[this.qualityGrade], 'im-phone-popup-rating-stars-item-active');
								}
							}
						}
					})
				]
			}),
			events: {
				onPopupClose: () => this.qualityPopup = null,
			}
		});

		this.qualityPopup.show();
	};

	closeQualityPopup()
	{
		if (this.qualityPopup)
		{
			this.qualityPopup.close();
		}
	};

	saveComment()
	{
		this.callbacks.saveComment({
			callId: this.callId,
			comment: this.comment
		})
	};

	showNumberSelectMenu(params)
	{
		var menuItems = [];
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		if (!Type.isArray(params.phoneNumbers))
		{
			return;
		}

		params.onSelect = Type.isFunction(params.onSelect) ? params.onSelect : BX.DoNothing;
		params.phoneNumbers.forEach((phoneNumber) =>
		{
			menuItems.push({
				id: 'number-select-' + BX.util.getRandomString(10),
				text: phoneNumber.VALUE,
				onclick: () =>
				{
					params.onSelect({
						phoneNumber: phoneNumber.VALUE
					})
				}
			})
		});

		this.numberSelectMenu = new Menu(
			'im-phone-call-view-number-select',
			params.bindElement,
			menuItems,
			{
				autoHide: true,
				offsetTop: 0,
				offsetLeft: 40,
				angle: {position: "top"},
				zIndex: baseZIndex + 200,
				closeByEsc: true,
				overlay: {
					backgroundColor: 'white',
					opacity: 0
				},
				events: {
					onPopupClose: () => this.numberSelectMenu.destroy(),
					onPopupDestroy: () => this.numberSelectMenu = null
				}
			}
		);
		this.numberSelectMenu.show();
	};

	closeNumberSelectMenu()
	{
		if (this.numberSelectMenu)
		{
			this.numberSelectMenu.close();
		}
	};

	fold()
	{
		if (!this.canBeFolded())
		{
			return false;
		}

		if (this.callListId > 0 && this.callState === CallState.idle)
		{
			this.foldCallView();
		}
		else
		{
			this.foldCall();
		}
	};

	unfold()
	{
		if (!this.isDesktop() && this.isFolded())
		{
			Dom.remove(this.elements.main);
			this.folded = false;
			this.elements = this.unfoldedElements;
			this.show();
		}
	};

	foldCall()
	{
		if (this.isDesktop() || !this.popup)
		{
			return;
		}

		let popupNode = this.popup.getPopupContainer();
		let overlayNode = this.popup.overlay.element;

		Dom.addClass(popupNode, 'im-phone-call-view-folding');
		Dom.addClass(overlayNode, 'popup-window-overlay-im-phone-call-view-folding');
		setTimeout(() =>
		{
			this.folded = true;
			this.popup.close();
			this.unfoldedElements = this.elements;
			Dom.removeClass(popupNode, 'im-phone-call-view-folding');
			Dom.removeClass(overlayNode, 'popup-window-overlay-im-phone-call-view-folding');
			this.reinit();
			this.enableDocumentScroll();
		}, 300);
	};

	foldCallView()
	{
		const popupNode = this.popup.getPopupContainer();
		const overlayNode = this.popup.overlay.element;

		Dom.addClass(popupNode, 'im-phone-call-view-folding');
		Dom.addClass(overlayNode, 'popup-window-overlay-im-phone-call-view-folding');
		setTimeout(
			() =>
			{
				this.close();
				this.foldedCallView.fold({
					callListId: this.callListId,
					webformId: this.webformId,
					webformSecCode: this.webformSecCode,
					currentItemIndex: this.callListView.currentItemIndex,
					currentItemStatusId: this.callListView.currentStatusId,
					statusList: this.callListView.statuses,
					entityType: this.callListView.entityType
				}, true);
			},
			300
		);
	};

	bindSlaveDesktopEvents()
	{
		DesktopApi.subscribe(desktopEvents.setTitle, this.setTitle.bind(this));
		DesktopApi.subscribe(desktopEvents.setStatus, this.setStatusText.bind(this));
		DesktopApi.subscribe(desktopEvents.setUiState, this.setUiState.bind(this));
		DesktopApi.subscribe(desktopEvents.setDeviceCall, this.setDeviceCall.bind(this));
		DesktopApi.subscribe(desktopEvents.setCrmEntity, this.setCrmEntity.bind(this));
		DesktopApi.subscribe(desktopEvents.reloadCrmCard, this.reloadCrmCard.bind(this));
		DesktopApi.subscribe(desktopEvents.setPortalCall, this.setPortalCall.bind(this));
		DesktopApi.subscribe(desktopEvents.setPortalCallUserId, this.setPortalCallUserId.bind(this));
		DesktopApi.subscribe(desktopEvents.setPortalCallQueueName, this.setPortalCallQueueName.bind(this));
		DesktopApi.subscribe(desktopEvents.setPortalCallData, this.setPortalCallData.bind(this));
		DesktopApi.subscribe(desktopEvents.setConfig, this.setConfig.bind(this));
		DesktopApi.subscribe(desktopEvents.setCallId, this.setCallId.bind(this));
		DesktopApi.subscribe(desktopEvents.setLineNumber, this.setLineNumber.bind(this));
		DesktopApi.subscribe(desktopEvents.setCompanyPhoneNumber, this.setCompanyPhoneNumber.bind(this));
		DesktopApi.subscribe(desktopEvents.setPhoneNumber, this.setPhoneNumber.bind(this));
		DesktopApi.subscribe(desktopEvents.setTransfer, this.setTransfer.bind(this));
		DesktopApi.subscribe(desktopEvents.setCallState, this.setCallState.bind(this));
		DesktopApi.subscribe(desktopEvents.closeWindow, () => window.close());

		BX.bind(window, "beforeunload", () =>
		{
			BX.unbindAll(window, "beforeunload");
			DesktopApi.emit(desktopEvents.onBeforeUnload, []);
		});

		BX.bind(window, "resize", Runtime.debounce(() =>
		{
			if (this.skipOnResize)
			{
				this.skipOnResize = false;
				return;
			}

			this.saveInitialSize(window.innerWidth, window.innerHeight)
		}, 100));

		BX.addCustomEvent("SidePanel.Slider:onOpen", (event) =>
		{
			if (!event.getSlider().isSelfContained())
			{
				event.denyAction();
				window.open(event.slider.url);
			}
		});

		/*BX.bind(window, "keydown", function(e)
		{
			if(e.keyCode === 27)
			{
				DesktopApi.emit(desktopEvents.onBeforeUnload, []);
			}
		}.bind(this));*/
	};

	bindMasterDesktopEvents()
	{
		DesktopApi.subscribe(desktopEvents.onHold, () => this.callbacks.hold());
		DesktopApi.subscribe(desktopEvents.onUnHold, () => this.callbacks.unhold());
		DesktopApi.subscribe(desktopEvents.onMute, () => this.callbacks.mute());
		DesktopApi.subscribe(desktopEvents.onUnMute, () => this.callbacks.unmute());
		DesktopApi.subscribe(desktopEvents.onMakeCall, (phoneNumber) => this.callbacks.makeCall(phoneNumber));
		DesktopApi.subscribe(desktopEvents.onCallListMakeCall, (e) => this.callbacks.callListMakeCall(e));
		DesktopApi.subscribe(desktopEvents.onAnswer, () => this.callbacks.answer());
		DesktopApi.subscribe(desktopEvents.onSkip, () => this.callbacks.skip());
		DesktopApi.subscribe(desktopEvents.onHangup, () => this.callbacks.hangup());
		DesktopApi.subscribe(desktopEvents.onClose, () => this.close());
		DesktopApi.subscribe(desktopEvents.onStartTransfer, (e) => this.callbacks.transfer(e));
		DesktopApi.subscribe(desktopEvents.onCompleteTransfer, () => this.callbacks.completeTransfer());
		DesktopApi.subscribe(desktopEvents.onCancelTransfer, () => this.callbacks.cancelTransfer());
		DesktopApi.subscribe(desktopEvents.onSwitchDevice, (e) => this.callbacks.switchDevice(e));
		DesktopApi.subscribe(desktopEvents.onBeforeUnload, () =>
		{
			this.desktop.window = null;
			this.callbacks.hangup();
			this.callbacks.close();
		}); //slave window unload
		DesktopApi.subscribe(desktopEvents.onQualityGraded, (grade) => this.callbacks.qualityGraded(grade));
		DesktopApi.subscribe(desktopEvents.onDialpadButtonClicked, (grade) => this.callbacks.dialpadButtonClicked(grade));
		DesktopApi.subscribe(desktopEvents.onCommentShown, (commentShown) => this.commentShown = commentShown);
		DesktopApi.subscribe(desktopEvents.onSaveComment, (comment) =>
		{
			this.comment = comment;
			this.saveComment();
		});
		DesktopApi.subscribe(desktopEvents.onSetAutoClose, (autoClose) => this.autoClose = autoClose);

	};

	unbindDesktopEvents()
	{
		for (let eventId in desktopEvents)
		{
			if (desktopEvents.hasOwnProperty(eventId))
			{
				DesktopApi.unsubscribe(desktopEvents[eventId]);
			}
		}
	};

	isDesktop()
	{
		return this._isDesktop;
	};

	isFolded()
	{
		return this.folded;
	};

	canBeFolded()
	{
		return this.allowAutoClose && (this.callState === CallState.connected || (this.callState === CallState.idle && this.callListId > 0));
	};

	getFoldedHeight()
	{
		if (!this.folded)
		{
			return 0;
		}

		if (!this.elements.main)
		{
			return 0;
		}

		return this.elements.main.clientHeight + (this.elements.sections.status ? this.elements.sections.status.clientHeight : 0);
	};

	isWebformSupported()
	{
		return (!this.isDesktop() || this.desktop.isFeatureSupported('iframe'));
	};

	isRestAppsSupported()
	{
		return (!this.isDesktop() || this.desktop.isFeatureSupported('iframe'));
	};

	setClosable(closable)
	{
		closable = (closable === true);
		this.closable = closable;
		if (this.isDesktop())
		{
			//this.desktop.setClosable(closable);
		}
		else if (this.popup)
		{
			this.popup.setClosingByEsc(closable);
			//this.popup.setAutoHide(closable);
		}
	};

	isClosable()
	{
		return this.closable;
	};

	adjust()
	{
		if (this.popup)
		{
			this.popup.adjustPosition();
		}

		if (this.isDesktop() && this.slave)
		{
			if (this.currentLayout == layouts.simple)
			{
				this.desktop.setResizable(false);
			}
			else
			{
				this.desktop.setResizable(true);
				this.desktop.setMinSize((this.elements.sidebarContainer ? 900 : 550), 650);
			}
			this.desktop.center();
		}
	};

	resizeWindow(width, height)
	{
		if (!this.isDesktop() || !this.slave)
		{
			return false;
		}

		this.skipOnResize = true;
		this.desktop.resize(width, height);
	};

	close()
	{
		BX.onCustomEvent(window, 'CallCard::BeforeClose', []);

		if (this.isFolded() && this.elements.main)
		{
			Dom.addClass(this.elements.main, 'im-phone-call-panel-mini-closing');
			setTimeout(() =>
				{
					Dom.remove(this.elements.main);
					this.elements = this.getInitialElements();
				},
				300
			);
		}

		if (this.popup)
		{
			this.popup.close();
		}

		if (this.desktop.window)
		{
			DesktopApi.emit(desktopEvents.closeWindow, []);
			//this.desktop.window.ExecuteCommand('close');
			//this.desktop.window = null;
		}

		this.enableDocumentScroll();

		this.callbacks.close();
		BX.onCustomEvent(window, 'CallCard::AfterClose', []);
	};

	disableAutoClose()
	{
		this.allowAutoClose = false;
		if (this.isDesktop() && this.slave)
		{
			DesktopApi.emit(desktopEvents.onSetAutoClose, [this.allowAutoClose]);
		}
		this.renderButtons();

		//Update callView close timer on every call disableAutoClose()
		this.updateAutoCloseTimer();
	};

	enableAutoClose()
	{
		this.allowAutoClose = true;
		if (this.isDesktop() && this.slave)
		{
			DesktopApi.emit(desktopEvents.onSetAutoClose, [this.allowAutoClose]);
		}
		this.renderButtons();

		if (this.autoCloseTimer)
		{
			clearTimeout(this.autoCloseTimer);
			this.autoCloseTimer = null;
			this.autoCloseAfterTimeout();
		}
	};

	autoClose()
	{
		if (this.allowAutoClose && !this.commentShown)
		{
			this.close();
		}
		else
		{
			BX.onCustomEvent(window, 'CallCard::BeforeClose', []);
			this.autoCloseTimer = setTimeout(() => this.autoCloseAfterTimeout(), this.autoCloseTimeout);
		}
	};

	autoCloseAfterTimeout()
	{
		console.log('Auto close after timeout', this.commentShown, this.autoCloseTimer, BX.localStorage.get(lsKeys.currentCall));
		if (this.commentShown)
		{
			this._onAddCommentButtonClick();
		}

		if (!BX.localStorage.get(lsKeys.currentCall))
		{
			this.close();
		}

		this.autoCloseTimer = null;
	};

	updateAutoCloseTimer()
	{
		if (this.autoCloseTimer)
		{
			clearTimeout(this.autoCloseTimer);
			this.autoCloseTimer = setTimeout(() => this.autoCloseAfterTimeout(), this.autoCloseTimeout);
		}
	}

	disableDocumentScroll()
	{
		const scrollWidth = window.innerWidth - document.documentElement.clientWidth;
		document.body.style.setProperty('padding-right', scrollWidth + "px");
		document.body.classList.add('im-phone-call-disable-scroll');
		const imBar = document.getElementById('bx-im-bar');
		if (imBar)
		{
			imBar.style.setProperty('right', scrollWidth + "px");
		}
	};

	enableDocumentScroll()
	{
		document.body.classList.remove('im-phone-call-disable-scroll');
		document.body.style.removeProperty('padding-right');
		const imBar = document.getElementById('bx-im-bar');
		if (imBar)
		{
			imBar.style.removeProperty('right');
		}
	};

	dispose()
	{
		window.removeEventListener('beforeunload', this._onBeforeUnloadHandler);
		BX.removeCustomEvent("onPullEvent-crm", this._onPullEventCrmHandler);
		this.unloadRestApps();
		this.unloadForm();

		if (this.isFolded() && this.elements.main)
		{
			Dom.addClass(this.elements.main, 'im-phone-call-panel-mini-closing');
			setTimeout(() => Dom.remove(this.elements.main), 300);
		}

		if (this.backgroundWorker)
		{
			this.backgroundWorker.setCallCard(null);
			this.backgroundWorker = null;
		}

		if (this.popup)
		{
			this.popup.destroy();
			this.popup = null;
		}

		if (this.qualityPopup)
		{
			this.qualityPopup.close();
		}

		if (this.keypad)
		{
			this.keypad.close();
		}

		if (this.numberSelectMenu)
		{
			this.closeNumberSelectMenu();
		}

		this.enableDocumentScroll();

		if (this.isDesktop())
		{
			this.unbindDesktopEvents();
			if (this.desktop.window)
			{
				DesktopApi.emit(desktopEvents.closeWindow, []);
				//this.desktop.window.ExecuteCommand('close');
				this.desktop.window = null;
			}
			if (!this.slave)
			{
				window.removeEventListener('beforeunload', this.#onWindowUnload); //master window unload
			}
		}
		else
		{
			window.removeEventListener('beforeunload', this._onBeforeUnloadHandler);
		}

		if (!BX.localStorage.get(lsKeys.callInited) && !BX.localStorage.get(lsKeys.externalCall))
		{
			BX.localStorage.remove(lsKeys.callView);
		}
	};

	canBeUnloaded()
	{
		if (this.backgroundWorker.isUsed())
		{
			return false;
		}

		return this.allowAutoClose && this.isFolded();
	};

	isCallListMode()
	{
		return (this.callListId > 0);
	};

	getState()
	{
		return {
			callId: this.callId,
			folded: this.folded,
			uiState: this._uiState,
			phoneNumber: this.phoneNumber,
			companyPhoneNumber: this.companyPhoneNumber,
			direction: this.direction,
			fromUserId: this.fromUserId,
			toUserId: this.toUserId,
			statusText: this.statusText,
			crm: this.crm,
			hasSipPhone: this.hasSipPhone,
			deviceCall: this.deviceCall,
			transfer: this.transfer,
			crmEntityType: this.crmEntityType,
			crmEntityId: this.crmEntityId,
			crmActivityId: this.crmActivityId,
			crmActivityEditUrl: this.crmActivityEditUrl,
			callListId: this.callListId,
			callListStatusId: this.callListStatusId,
			callListItemIndex: this.callListItemIndex,
			config: (this.config ? this.config : '{}'),
			portalCall: (this.portalCall ? 'true' : 'false'),
			portalCallData: (this.portalCallData ? this.portalCallData : '{}'),
			portalCallUserId: this.portalCallUserId,
			webformId: this.webformId,
			webformSecCode: this.webformSecCode,
			initialTimestamp: this.initialTimestamp,
			crmData: this.crmData
		};
	};

	selectTransferTarget(resultCallback: (TransferTarget) => void)
	{
		resultCallback = Type.isFunction(resultCallback) ? resultCallback : BX.DoNothing;

		Runtime.loadExtension('ui.entity-selector').then((exports: EntitySelector) =>
		{
			const config =
				this.backgroundWorker.isUsed()
					? this.getDialogConfigForBackgroundApp(resultCallback)
					: this.getDefaultDialogConfig(resultCallback)
			;
			const Dialog: Dialog = exports.Dialog;
			const transferDialog = new Dialog(config);

			transferDialog.show();
		});
	};

	getDialogConfigForBackgroundApp(resultCallback)
	{
		return {
			targetNode: this.elements.buttons.transfer,
			multiple: false,
			cacheable: false,
			hideOnSelect: false,
			enableSearch: true,
			entities: [
				{
					id: 'user',
					options: {
						inviteEmployeeLink: false,
						selectFields: ['personalPhone', 'personalMobile', 'workPhone']
					}
				},
				{
					id: 'department'
				},
			],
			events: {
				'Item:onSelect': (event) =>
				{
					event.target.deselectAll();

					const item = event.data.item;

					if (item.getEntityId() === 'user')
					{
						var customData = item.getCustomData();
						if (customData.get('personalPhone') || customData.get('personalMobile') || customData.get('workPhone'))
						{
							this.showTransferToUserMenu({
								userId: item.getId(),
								customData: Object.fromEntries(customData),
								darkMode: this.darkMode,
								onSelect: (result: TransferTarget) =>
								{
									event.target.hide();
									resultCallback({
										phoneNumber: this.phoneNumber,
										target: result.target
									})
								}
							})
						}
						else
						{
							event.target.hide();
							resultCallback({
								phoneNumber: this.phoneNumber,
								target: item.getId()
							})
						}
					}
				}
			}
		}
	}

	getDefaultDialogConfig(resultCallback: (TransferTarget) => void)
	{
		return {
			targetNode: this.elements.buttons.transfer,
			multiple: false,
			cacheable: false,
			hideOnSelect: false,
			enableSearch: true,
			entities: [
				{
					id: 'user',
					options: {
						inviteEmployeeLink: false,
						selectFields: ['personalPhone', 'personalMobile', 'workPhone']
					}
				},
				{
					id: 'department'
				},
				{
					id: 'voximplant_group'
				},
			],
			events: {
				'Item:onSelect': (event) =>
				{
					event.target.deselectAll();

					var item = event.data.item;

					if (item.getEntityId() === 'user')
					{
						var customData = item.getCustomData();
						if (customData.get('personalPhone') || customData.get('personalMobile') || customData.get('workPhone'))
						{
							this.showTransferToUserMenu({
								userId: item.getId(),
								customData: Object.fromEntries(customData),
								darkMode: this.darkMode,
								onSelect: (result: TransferTarget) =>
								{
									event.target.hide();
									resultCallback(result)
								}
							})
						}
						else
						{
							event.target.hide();
							resultCallback({
								type: 'user',
								target: item.getId()
							})
						}
					}
					else if (item.getEntityId() === 'voximplant_group')
					{
						event.target.hide();
						resultCallback({
							type: 'queue',
							target: item.getId()
						})
					}
				}
			}
		};
	}

	showTransferToUserMenu(options: TransferOptions = {})
	{
		const userId = Type.isInteger(options.userId) ? options.userId : 0;
		const userCustomData = Type.isPlainObject(options.customData) ? options.customData : {};
		const darkMode = options.darkMode === true;
		const onSelect = Type.isFunction(options.onSelect) ? options.onSelect : nop;
		let popup;

		const onMenuItemClick = (e) =>
		{
			const type = e.currentTarget.dataset["type"];
			const target = e.currentTarget.dataset["target"];
			onSelect({
				type: type,
				target: target,
			});
			popup.close();
		};

		let menuItems = [
			{
				icon: 'bx-messenger-menu-call-voice',
				text: Loc.getMessage('IM_PHONE_INNER_CALL'),
				dataset: {
					type: 'user',
					target: userId
				},
				onclick: onMenuItemClick
			},
			{
				delimiter: true
			},
		];

		if (userCustomData["personalMobile"])
		{
			menuItems.push({
				html: renderTransferMenuItem(Loc.getMessage("IM_PHONE_PERSONAL_MOBILE"), Text.encode(userCustomData["personalMobile"])),
				dataset: {
					type: 'pstn',
					target: userCustomData["personalMobile"]
				},
				onclick: onMenuItemClick,
			});
		}
		if (userCustomData["personalPhone"])
		{
			menuItems.push({
				type: "call",
				html: renderTransferMenuItem(Loc.getMessage("IM_PHONE_PERSONAL_PHONE"), Text.encode(userCustomData["personalPhone"])),
				dataset: {
					type: 'pstn',
					target: userCustomData["personalPhone"]
				},
				onclick: onMenuItemClick,
			});
		}
		if (userCustomData["workPhone"])
		{
			menuItems.push({
				html: renderTransferMenuItem(Loc.getMessage("IM_PHONE_WORK_PHONE"), Text.encode(userCustomData["workPhone"])),
				dataset: {
					type: 'pstn',
					target: userCustomData["workPhone"]
				},
				onclick: onMenuItemClick,
			});
		}

		popup = new Menu({
			id: "bx-messenger-phone-transfer-menu",
			bindElement: null,
			targetContainer: document.body,
			darkMode: darkMode,
			lightShadow: true,
			autoHide: true,
			closeByEsc: true,
			cacheable: false,
			overlay: {
				backgroundColor: '#FFFFFF',
				opacity: 0
			},
			items: menuItems,
		});
		popup.show();
	}
}

function renderTransferMenuItem(surTitle: string, text: string): string
{
	return `<div class="transfer-menu-item-surtitle">${Text.encode(surTitle)}</div><div class="transfer-menu-item-text">${Text.encode(text)}</div>`
}

function renderSimpleButton(text, className, clickCallback)
{
	let params = {};
	if (Type.isStringFilled(text))
	{
		params.text = text;
	}

	if (Type.isStringFilled(className))
	{
		params.props = {className: className};
	}

	if (Type.isFunction(clickCallback))
	{
		params.events = {click: clickCallback};
	}

	return Dom.create('span', params);
}

function createStar(grade, active, onSelect)
{
	return Dom.create("div", {
		props: {className: 'im-phone-popup-rating-stars-item ' + (active ? 'im-phone-popup-rating-stars-item-active' : '')},
		dataset: {grade: grade},
		events: {
			click: (e) =>
			{
				e.preventDefault();
				const grade = e.currentTarget.dataset.grade;
				onSelect(grade);
			}
		}
	})
}

type TransferOptions = {
	userId: number,
	customData: Object,
	darkMode: boolean,
	onSelect: (TransferTarget) => void,
}

type TransferTarget = {
	type: string,
	target: string,
}
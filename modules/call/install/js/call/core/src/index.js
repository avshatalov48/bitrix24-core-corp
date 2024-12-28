import {applyHacks} from './hacks';
import {BackgroundDialog} from './dialogs/background_dialog';
import {IncomingNotificationContent} from './dialogs/incoming_notification';
import {NotificationConferenceContent} from './dialogs/conference_notification';
import {FloatingScreenShare} from './floating_screenshare';
import {FloatingScreenShareContent} from './floating_screenshare';
import {CallHint} from './call_hint_popup'
import {CallController} from './controller';
import {CallEngine, CallEvent, EndpointDirection, UserState, Provider, CallType, CallState} from './engine/engine';
import {Hardware} from './hardware';
import Util from './util';
import { CallAI } from './call_ai';
import {VideoStrategy} from './video_strategy';
import {View} from './view/view';
import { CopilotPopup } from './view/copilot-popup';
import {WebScreenSharePopup} from './web_screenshare_popup';
import { UserListPopup } from 'call.component.user-list-popup';
import { UserList } from 'call.component.user-list';
import 'loader';
import 'resize_observer';
import 'webrtc_adapter';
import 'im.lib.localstorage';
import 'ui.hint';
import 'voximplant';

applyHacks();

export {
	BackgroundDialog,
	CallController as Controller,
	CallEngine as Engine,
	CallEvent as Event,
	CallHint as Hint,
	CallState as State,
	EndpointDirection,
	FloatingScreenShare,
	FloatingScreenShareContent,
	IncomingNotificationContent,
	NotificationConferenceContent,
	Hardware,
	Provider,
	CallType as Type,
	UserState,
	Util,
	VideoStrategy,
	View,
	WebScreenSharePopup,
	UserListPopup,
	CopilotPopup,
	UserList,
	CallAI,
};

// compatibility
BX.CallEngine = CallEngine;
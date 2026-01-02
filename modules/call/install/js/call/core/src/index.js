import {applyHacks} from './hacks';
import {BackgroundDialog} from './dialogs/background_dialog';
import {IncomingNotificationContent} from './dialogs/incoming_notification';
import {NotificationConferenceContent} from './dialogs/conference_notification';
import {FloatingScreenShare} from './floating_screenshare';
import {FloatingScreenShareContent} from './floating_screenshare';
import {CallHint} from './call_hint_popup'
import {CallController} from './controller';
import {
	CallEngine,
	CallEvent,
	EndpointDirection,
	UserState,
	Provider,
	CallType,
	CallState,
	StartCallErrorCode,
	DisconnectReason,
	CallScheme,
} from './engine/engine';
import {CallEngineLegacy} from './engine/engine_legacy';
import {Hardware} from './call_hardware';
import Util from './util';
import { CallAI } from './call_ai';
import {VideoStrategy} from './video_strategy';
import { JoinResponseError } from './call_api';
import {View} from './view/view';
import { CopilotPopup } from './view/copilot-popup';
import { ParticipantsPermissionPopup } from './view/participants-permission-popup';
import {WebScreenSharePopup} from './web_screenshare_popup';
import { UserListPopup } from 'call.component.user-list-popup';
import { UserList } from 'call.component.user-list';
import { CallMultiChannel } from './call_multi_channel';
import 'loader';
import 'resize_observer';
import 'webrtc_adapter';
import 'im.lib.localstorage';
import 'ui.hint';
import 'voximplant';

applyHacks();

export {
	JoinResponseError,
	BackgroundDialog,
	CallController as Controller,
	CallEngine as Engine,
	CallEvent as Event,
	CallHint as Hint,
	CallState as State,
	CallEngineLegacy as EngineLegacy,
	EndpointDirection,
	StartCallErrorCode,
	DisconnectReason,
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
	CallScheme,
	ParticipantsPermissionPopup,
	CallMultiChannel,
};

// compatibility
BX.CallEngine = CallEngine;
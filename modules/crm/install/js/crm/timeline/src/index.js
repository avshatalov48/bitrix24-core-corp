import Manager from "./manager";
import Stream from "./stream";
import History from "./streams/history";
import FixedHistory from "./streams/fixedhistory";
import EntityChat from "./streams/entitychat";
import Schedule from "./streams/schedule";
import SchedulePostponeController from "./tools/schedule-postpone-controller";
import AudioPlaybackRateSelector from "./tools/audio-playback-rate-selector";
import * as Types from "./types";
import Action from "./action";
import Activity from "./actions/activity";
import {Call, HistoryCall, ScheduleCall} from "./actions/call";
import {Email, HistoryEmail, ScheduleEmail} from "./actions/email";
import {OpenLine} from "./actions/openline";
import SchedulePostpone from "./actions/schedule-postpone";
import CompatibleItem from "./items/compatible-item";
import HistoryItem from "./items/history";
import HistoryActivity from "./items/history-activity";
import CommentItem from "./items/comment";
import Modification from "./items/modification";
import Mark from "./items/mark";
import Creation from "./items/creation";
import Restoration from "./items/restoration";
import Relation from "./items/relation";
import Link from "./items/link";
import Unlink from "./items/unlink";
import EmailItem from "./items/email";
import CallItem from "./items/call";
import Meeting from "./items/meeting";
import Task from "./items/task";
import WebForm from "./items/webform";
import WaitItem from "./items/wait";
import Document from "./items/document";
import Sender from "./items/sender";
import Bizproc from "./items/bizproc";
import Request from "./items/request";
import RestItem from "./items/rest";
import OpenLineItem from "./items/openline";
import Zoom from "./items/zoom";
import Conversion from "./items/conversion";
import Visit from "./items/visit";
import Scoring from "./items/scoring";
import ExternalNoticeModification from "./items/external-notice-modification";
import ExternalNoticeStatusModification from "./items/external-notice-status-modification";
import Scheduled from "./items/scheduled";
import ScheduledActivity from "./items/scheduled/activity";
import ScheduledEmail from "./items/scheduled/email";
import ScheduledCall from "./items/scheduled/call";
import CallTracker from "./items/scheduled/call-tracker";
import ScheduledMeeting from "./items/scheduled/meeting";
import ScheduledTask from "./items/scheduled/task";
import ScheduledWebForm from "./items/scheduled/webform";
import ScheduledWait from "./items/scheduled/wait";
import ScheduledRequest from "./items/scheduled/request";
import ScheduledRest from "./items/scheduled/rest";
import ScheduledOpenLine from "./items/scheduled/openline";
import ScheduledZoom from "./items/scheduled/zoom";
import AnimationItem from "./animations/item";
import ItemNew from "./animations/item-new";
import Expand from "./animations/expand";
import Shift from "./animations/shift";
import AnimationComment from "./animations/comment";
import Fasten from "./animations/fasten";

const Streams = {
	History,
	FixedHistory,
	EntityChat,
	Schedule,
}

const Tools = {
	SchedulePostponeController,
	AudioPlaybackRateSelector,
}

const Actions = {
	Activity,
	Call,
	HistoryCall,
	ScheduleCall,
	Email,
	HistoryEmail,
	ScheduleEmail,
	OpenLine,
	SchedulePostpone,
}

const ScheduledItems = {
	Activity: ScheduledActivity,
	Email: ScheduledEmail,
	Call: ScheduledCall,
	CallTracker,
	Meeting: ScheduledMeeting,
	Task: ScheduledTask,
	WebForm: ScheduledWebForm,
	Wait: ScheduledWait,
	Request: ScheduledRequest,
	Rest: ScheduledRest,
	OpenLine: ScheduledOpenLine,
	Zoom: ScheduledZoom,
}

const Items = {
	History: HistoryItem,
	HistoryActivity,
	Comment: CommentItem,
	Modification,
	Mark,
	Creation,
	Restoration,
	Relation,
	Link,
	Unlink,
	Email: EmailItem,
	Call: CallItem,
	Meeting,
	Task,
	WebForm,
	Wait: WaitItem,
	Document,
	Sender,
	Bizproc,
	Request,
	Rest: RestItem,
	OpenLine: OpenLineItem,
	Zoom,
	Conversion,
	Visit,
	Scoring,
	ExternalNoticeModification,
	ExternalNoticeStatusModification,
	ScheduledBase: Scheduled,
	Scheduled: ScheduledItems,
}

const Animations = {
	Item: AnimationItem,
	ItemNew,
	Expand,
	Shift,
	Comment: AnimationComment,
	Fasten,
};

export {
	Manager,
	Stream,
	Streams,
	Tools,
	Types,
	Action,
	Actions,
	Items,
	Animations,
	CompatibleItem,
};

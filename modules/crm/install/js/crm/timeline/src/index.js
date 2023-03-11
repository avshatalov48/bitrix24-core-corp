import Notification from './notification/component';
import DeliveryActivity from './delivery-activity/component';
import DeliveryMessage from './delivery-message/component';
import DeliveryCalculation from './delivery-calculation/component';
import Manager from "./manager";
import Stream from "./stream";
import Editor from "./editor";
import History from "./streams/history";
import FixedHistory from "./streams/fixedhistory";
import EntityChat from "./streams/entitychat";
import Schedule from "./streams/schedule";
import Comment from "./editors/comment";
import Wait from "./editors/wait";
import Rest from "./editors/rest";
import Sms from "./editors/sms";
import WaitConfigurationDialog from "./tools/wait-configuration-dialog";
import SchedulePostponeController from "./tools/schedule-postpone-controller";
import MenuBar from "./tools/menubar";
import {SmsWatcher} from "./tools/sms-watcher";
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
import SmsItem from "./items/sms";
import Request from "./items/request";
import RestItem from "./items/rest";
import OpenLineItem from "./items/openline";
import Zoom from "./items/zoom";
import Conversion from "./items/conversion";
import Visit from "./items/visit";
import Scoring from "./items/scoring";
import OrderCreation from "./items/order-creation";
import OrderModification from "./items/order-modification";
import FinalSummaryDocuments from "./items/final-summary-documents";
import FinalSummary from "./items/final-summary";
import ExternalNoticeModification from "./items/external-notice-modification";
import ExternalNoticeStatusModification from "./items/external-notice-status-modification";
import OrderCheck from "./items/order-check";
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
import CompilationOrderNotice from './product-compilation/order-notice/component';
import ProductCompilationViewed from './product-compilation/compilation-viewed/component';
import NewDealCreated from './product-compilation/deal-created/component';

const Streams = {
	History,
	FixedHistory,
	EntityChat,
	Schedule,
}

const Editors = {
	Comment,
	Wait,
	Rest,
	Sms
}

const Tools = {
	WaitConfigurationDialog,
	SchedulePostponeController,
	MenuBar,
	SmsWatcher,
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
	Sms: SmsItem,
	Request,
	Rest: RestItem,
	OpenLine: OpenLineItem,
	Zoom,
	Conversion,
	Visit,
	Scoring,
	OrderCreation,
	OrderModification,
	FinalSummaryDocuments,
	FinalSummary,
	ExternalNoticeModification,
	ExternalNoticeStatusModification,
	OrderCheck,
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
	Notification,
	DeliveryActivity,
	DeliveryMessage,
	DeliveryCalculation,
	Manager,
	Stream,
	Streams,
	Editor,
	Editors,
	Tools,
	Types,
	Action,
	Actions,
	Items,
	Animations,
	CompatibleItem,
	CompilationOrderNotice,
	ProductCompilationViewed,
	NewDealCreated,
};

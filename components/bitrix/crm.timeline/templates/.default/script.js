BX.CrmTimelineManager = BX.Crm.Timeline.Manager;
BX.CrmTimeline = BX.Crm.Timeline.Stream;

BX.CrmHistory = BX.Crm.Timeline.Streams.History;

BX.CrmFixedHistory = BX.Crm.Timeline.Streams.FixedHistory;

BX.CrmSchedule = BX.Crm.Timeline.Streams.Schedule;

BX.CrmEntityChatLayoutType = BX.Crm.Timeline.Streams.EntityChat.LayoutType;

BX.CrmEntityChat = BX.Crm.Timeline.Streams.EntityChat;

BX.CrmTimelineType = BX.Crm.Timeline.Types.Item;

BX.CrmTimelineMarkType = BX.Crm.Timeline.Types.Mark;

BX.CrmTimelineDeliveryType = BX.Crm.Timeline.Types.Delivery;

BX.CrmTimelineOrderType = BX.Crm.Timeline.Types.Order;

//region Base Actions
BX.CrmTimelineAction = BX.Crm.Timeline.Action;

BX.CrmTimelineActivityAction = BX.Crm.Timeline.Actions.Activity;

BX.CrmTimelineEmailAction = BX.Crm.Timeline.Actions.Email;

BX.CrmTimelineCallAction = BX.Crm.Timeline.Actions.Call;

BX.CrmTimelineOpenLineAction = BX.Crm.Timeline.Actions.OpenLine;
//endregion

//region History Actions
BX.CrmHistoryEmailAction = BX.Crm.Timeline.Actions.HistoryEmail;

BX.CrmHistoryCallAction = BX.Crm.Timeline.Actions.HistoryCall;

BX.CrmHistoryOpenLineAction = BX.Crm.Timeline.Actions.OpenLine;
//endregion

//region Schedule Actions
BX.CrmScheduleEmailAction = BX.Crm.Timeline.Actions.ScheduleEmail;

BX.CrmSchedulePostponeController = BX.Crm.Timeline.Tools.SchedulePostponeController;

BX.CrmSchedulePostponeAction = BX.Crm.Timeline.Actions.SchedulePostpone;

BX.CrmScheduleCallAction = BX.Crm.Timeline.Actions.ScheduleCall;

BX.CrmScheduleOpenLineAction = BX.Crm.Timeline.Actions.OpenLine;
//endregion

//region Base Item
BX.CrmTimelineItem = BX.Crm.Timeline.CompatibleItem;
//endregion

BX.Crm.TimelineEditorMode = BX.Crm.Timeline.Types.EditorMode;

//region History Items
BX.CrmHistoryItem = BX.Crm.Timeline.Items.History;

BX.CrmHistoryItemActivity = BX.Crm.Timeline.Items.HistoryActivity;

BX.CrmHistoryItemComment = BX.Crm.Timeline.Items.Comment;

BX.CrmHistoryItemModification = BX.Crm.Timeline.Items.Modification;

BX.CrmHistoryItemMark = BX.Crm.Timeline.Items.Mark;

BX.CrmHistoryItemCreation = BX.Crm.Timeline.Items.Creation;

BX.CrmHistoryItemRestoration = BX.Crm.Timeline.Items.Restoration;

BX.CrmHistoryItemRelation = BX.Crm.Timeline.Items.Relation;

BX.CrmHistoryItemLink = BX.Crm.Timeline.Items.Link;

BX.CrmHistoryItemUnlink = BX.Crm.Timeline.Items.Unlink;

BX.CrmHistoryItemEmail = BX.Crm.Timeline.Items.Email;

BX.CrmHistoryItemCall = BX.Crm.Timeline.Items.Call;

BX.CrmHistoryItemMeeting = BX.Crm.Timeline.Items.Meeting;

BX.CrmHistoryItemTask = BX.Crm.Timeline.Items.Task;

BX.CrmHistoryItemWebForm = BX.Crm.Timeline.Items.WebForm;

BX.CrmHistoryItemWait = BX.Crm.Timeline.Items.Wait;

BX.CrmHistoryItemDocument = BX.Crm.Timeline.Items.Document;

BX.CrmHistoryItemSender = BX.Crm.Timeline.Items.Sender;

BX.CrmHistoryItemBizproc = BX.Crm.Timeline.Items.Bizproc;

BX.CrmHistoryItemSms = BX.Crm.Timeline.Items.Sms;

BX.CrmHistoryItemActivityRequest = BX.Crm.Timeline.Items.Request;

BX.CrmHistoryItemActivityRestApplication = BX.Crm.Timeline.Items.Rest;

BX.CrmHistoryItemOpenLine = BX.Crm.Timeline.Items.OpenLine;

BX.CrmHistoryItemZoom = BX.Crm.Timeline.Items.Zoom;

BX.CrmHistoryItemCallTracker = BX.Crm.Timeline.Items.Call;

BX.CrmHistoryItemConversion = BX.Crm.Timeline.Items.Conversion;

BX.CrmHistoryItemVisit = BX.Crm.Timeline.Items.Visit;

BX.CrmHistoryItemScoring = BX.Crm.Timeline.Items.Scoring;

BX.CrmHistoryItemOrderModification = BX.Crm.Timeline.Items.OrderModification;

BX.CrmHistoryItemExternalNoticeModification = BX.Crm.Timeline.Items.ExternalNoticeModification;

BX.CrmHistoryItemExternalNoticeStatusModification = BX.Crm.Timeline.Items.ExternalNoticeStatusModification;

//endregion

//region Schedule Items
BX.CrmScheduleItem = BX.Crm.Timeline.Items.ScheduledBase;

BX.CrmScheduleItemActivity = BX.Crm.Timeline.Items.Scheduled.Activity;

BX.CrmScheduleItemEmail = BX.Crm.Timeline.Items.Scheduled.Email;

BX.CrmScheduleItemCall = BX.Crm.Timeline.Items.Scheduled.Call;

BX.CrmScheduleItemCallTracker = BX.Crm.Timeline.Items.Scheduled.CallTracker

BX.CrmScheduleItemMeeting = BX.Crm.Timeline.Items.Scheduled.Meeting;

BX.CrmScheduleItemTask = BX.Crm.Timeline.Items.Scheduled.Task;

BX.CrmScheduleItemStoreDocument = BX.Crm.Timeline.Items.Scheduled.StoreDocument;

BX.CrmScheduleItemWebForm = BX.Crm.Timeline.Items.Scheduled.WebForm;

BX.CrmScheduleItemDelivery = BX.Crm.Timeline.Items.Scheduled.Activity;

BX.CrmScheduleItemWait = BX.Crm.Timeline.Items.Scheduled.Wait;

BX.CrmScheduleItemActivityRequest = BX.Crm.Timeline.Items.Scheduled.Request;

BX.CrmScheduleItemActivityRestApplication = BX.Crm.Timeline.Items.Scheduled.Rest;

BX.CrmScheduleItemActivityOpenLine = BX.Crm.Timeline.Items.Scheduled.OpenLine;

BX.CrmScheduleItemActivityZoom = BX.Crm.Timeline.Items.Scheduled.Zoom;
//endregion

//region Animation
BX.CrmTimelineItemAnimation = BX.Crm.Timeline.Animations.Item;

BX.CrmTimelineItemAnimationNew = BX.Crm.Timeline.Animations.ItemNew;

BX.CrmTimelineItemExpand = BX.Crm.Timeline.Animations.Expand;

BX.CrmTimelineItemShift = BX.Crm.Timeline.Animations.Shift;

BX.CrmCommentAnimation = BX.Crm.Timeline.Animations.Comment;

BX.CrmTimelineItemFasten = BX.Crm.Timeline.Animations.Fasten;
//endregion

//region Menu Bar
BX.CrmTimelineMenuBar = BX.Crm.Timeline.Tools.MenuBar;
//endregion

//region Watchers
BX.CrmSmsWatcher = BX.Crm.Timeline.Tools.SmsWatcher;
//endregion

BX.CrmTimelineAudioPlaybackRateSelector = BX.Crm.Timeline.Tools.AudioPlaybackRateSelector;

BX.CrmTimelineWorkflowEventManager = BX.Crm.Timeline.Tools.WorkflowEventManager;

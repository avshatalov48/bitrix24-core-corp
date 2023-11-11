/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var Analytics = /*#__PURE__*/function () {
	  function Analytics() {
	    babelHelpers.classCallCheck(this, Analytics);
	    this.action = 'tasks.analytics.hit';
	  }
	  babelHelpers.createClass(Analytics, [{
	    key: "sendLabel",
	    value: function sendLabel(label) {
	      main_core.ajax.runAction(this.action, {
	        analyticsLabel: {
	          scenario: label
	        }
	      }).then(function (response) {});
	    }
	  }, {
	    key: "sendData",
	    value: function sendData() {
	      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      main_core.ajax.runAction(this.action, {
	        analyticsLabel: data
	      }).then(function (response) {});
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (Analytics.instance === null) {
	        Analytics.instance = new this();
	      }
	      return Analytics.instance;
	    }
	  }]);
	  return Analytics;
	}();
	babelHelpers.defineProperty(Analytics, "instance", null);

	var AnalyticsLabel = function AnalyticsLabel() {
	  babelHelpers.classCallCheck(this, AnalyticsLabel);
	};
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_VIEW", 'task_view');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_ADD", 'task_add');
	babelHelpers.defineProperty(AnalyticsLabel, "DEADLINE_EDIT", 'deadline_edit');
	babelHelpers.defineProperty(AnalyticsLabel, "REMIND_ADD", 'remind_add');
	babelHelpers.defineProperty(AnalyticsLabel, "REMIND_ADD_DATE", 'remind_add_date');
	babelHelpers.defineProperty(AnalyticsLabel, "REMIND_ADD_DEADLINE", 'remind_add_deadline');
	babelHelpers.defineProperty(AnalyticsLabel, "REMIND_ADD_RESPONSIBLE", 'remind_add_responsible');
	babelHelpers.defineProperty(AnalyticsLabel, "REMIND_ADD_CREATOR", 'remind_add_creator');
	babelHelpers.defineProperty(AnalyticsLabel, "REMIND_ADD_AUTHOR", 'remind_add_author');
	babelHelpers.defineProperty(AnalyticsLabel, "REMIND_ADD_MESSAGE", 'remind_add_message');
	babelHelpers.defineProperty(AnalyticsLabel, "REMIND_ADD_EMAIL", 'remind_add_email');
	babelHelpers.defineProperty(AnalyticsLabel, "AUTOMATION_ADD", 'automation_add');
	babelHelpers.defineProperty(AnalyticsLabel, "SCORE_ADD", 'score_add');
	babelHelpers.defineProperty(AnalyticsLabel, "VIDEOCALL_START", 'videocall_start');
	babelHelpers.defineProperty(AnalyticsLabel, "OPEN_CHAT", 'open_chat');
	babelHelpers.defineProperty(AnalyticsLabel, "POST_ADD", 'post_add');
	babelHelpers.defineProperty(AnalyticsLabel, "EVENT_ADD", 'event_add');
	babelHelpers.defineProperty(AnalyticsLabel, "CREATOR_CHANGE", 'creator_change');
	babelHelpers.defineProperty(AnalyticsLabel, "RESPONSIBLE_CHANGE", 'responsible_change');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_DELEGATE", 'task_delegate');
	babelHelpers.defineProperty(AnalyticsLabel, "PARTICIPANT_ADD", 'participant_add');
	babelHelpers.defineProperty(AnalyticsLabel, "PARTICIPANT_CHANGE", 'participant_change');
	babelHelpers.defineProperty(AnalyticsLabel, "OBSERVER_ADD", 'observer_add');
	babelHelpers.defineProperty(AnalyticsLabel, "OBSERVER_CHANGE", 'observer_change');
	babelHelpers.defineProperty(AnalyticsLabel, "TAG_ADD", 'tag_add');
	babelHelpers.defineProperty(AnalyticsLabel, "TAG_CHANGE", 'tag_change');
	babelHelpers.defineProperty(AnalyticsLabel, "TAG_VIEW", 'tag_view');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_ADD", 'checklist_add');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_ITEM_ADD", 'checklist_item_add');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_DEL", 'checklist_del');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_RENAME", 'checklist_rename');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_FILES_VIEW", 'checklist_files_view');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_ITEM_IMPORTANT", 'checklist_item_important');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_ITEM_MOVE", 'checklist_item_move');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_ITEM_DEL", 'checklist_item_del');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_GROUP_ACT", 'checklist_group_act');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_ITEM_SHIFT_RIGHT", 'checklist_item_shift_right');
	babelHelpers.defineProperty(AnalyticsLabel, "CHECKLIST_ITEM_SHIFT_LEFT", 'checklist_item_shift_left');
	babelHelpers.defineProperty(AnalyticsLabel, "PROJECT_ADD", 'project_add');
	babelHelpers.defineProperty(AnalyticsLabel, "PROJECT_REMOVE", 'project_remove');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_LIKE", 'task_like');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_START", 'task_start');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_FINISH", 'task_finish');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_PAUSE", 'task_pause');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_DEFER", 'task_defer');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_COPY", 'task_copy');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_DEL", 'task_del');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_EDIT", 'task_edit');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_MARKET", 'task_market');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_LINK_COPY", 'task_link_copy');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_PRIORITY_ENB", 'task_priority_enb');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_PRIORITY_DSB", 'task_priority_dsb');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_FAV_ENB", 'task_fav_enb');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_FAV_DSB", 'task_fav_dsb');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_MUTE_ENB", 'task_mute_enb');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_MUTE_DSB", 'task_mute_dsb');
	babelHelpers.defineProperty(AnalyticsLabel, "NEW_TEMPLATE_TASK_ADD", 'new_template_task_add');
	babelHelpers.defineProperty(AnalyticsLabel, "TEMPLATES_VIEW", 'templates_view');
	babelHelpers.defineProperty(AnalyticsLabel, "NEW_SUBTASK_ADD", 'new_subtask_add');
	babelHelpers.defineProperty(AnalyticsLabel, "SUBTASK_VIEW", 'subtask_view');
	babelHelpers.defineProperty(AnalyticsLabel, "SUBTASK_SET", 'subtask_set');
	babelHelpers.defineProperty(AnalyticsLabel, "SUBTASK_START", 'subtask_start');
	babelHelpers.defineProperty(AnalyticsLabel, "SUBTASK_ACTIONS", 'subtask_actions');
	babelHelpers.defineProperty(AnalyticsLabel, "COMM_VIEW", 'comm_view');
	babelHelpers.defineProperty(AnalyticsLabel, "COMM_SEND", 'comm_send');
	babelHelpers.defineProperty(AnalyticsLabel, "GOTO_COMM", 'goto_comm');
	babelHelpers.defineProperty(AnalyticsLabel, "COMM_LINK_COPY", 'comm_link_copy');
	babelHelpers.defineProperty(AnalyticsLabel, "COMM_EDIT", 'comm_edit');
	babelHelpers.defineProperty(AnalyticsLabel, "COMM_DEL", 'comm_del');
	babelHelpers.defineProperty(AnalyticsLabel, "COMM_LIKE", 'comm_like');
	babelHelpers.defineProperty(AnalyticsLabel, "COMM_REPLY", 'comm_reply');
	babelHelpers.defineProperty(AnalyticsLabel, "SUMMARY_ADD", 'summary_add');
	babelHelpers.defineProperty(AnalyticsLabel, "SUMMARY_REMOVE", 'summary_remove');
	babelHelpers.defineProperty(AnalyticsLabel, "SUMMARY_MORE", 'summary_more');
	babelHelpers.defineProperty(AnalyticsLabel, "HISTORY_VIEW", 'history_view');
	babelHelpers.defineProperty(AnalyticsLabel, "TIME_VIEW", 'time_view');
	babelHelpers.defineProperty(AnalyticsLabel, "TIME_ADD", 'time_add');
	babelHelpers.defineProperty(AnalyticsLabel, "TIME_EDIT", 'time_edit');
	babelHelpers.defineProperty(AnalyticsLabel, "TIME_DEL", 'time_del');
	babelHelpers.defineProperty(AnalyticsLabel, "OBJECTIONS_VIEW", 'objections_view');
	babelHelpers.defineProperty(AnalyticsLabel, "DESCRIPTION_VIEW", 'description_view');
	babelHelpers.defineProperty(AnalyticsLabel, "FILE_ADD", 'file_add');
	babelHelpers.defineProperty(AnalyticsLabel, "DESCRIPTION_ADD", 'description_add');
	babelHelpers.defineProperty(AnalyticsLabel, "DOC_ADD", 'doc_add');
	babelHelpers.defineProperty(AnalyticsLabel, "MENTION_ADD", 'mention_add');
	babelHelpers.defineProperty(AnalyticsLabel, "QUOTE_ADD", 'quote_add');
	babelHelpers.defineProperty(AnalyticsLabel, "EDITOR_ENB", 'editor_enb');
	babelHelpers.defineProperty(AnalyticsLabel, "TIME_PLAN_ENB", 'time_plan_enb');
	babelHelpers.defineProperty(AnalyticsLabel, "START_TIME_SET", 'start_time_set');
	babelHelpers.defineProperty(AnalyticsLabel, "FINISH_TIME_SET", 'finish_time_set');
	babelHelpers.defineProperty(AnalyticsLabel, "DURATION_SET", 'duration_set');
	babelHelpers.defineProperty(AnalyticsLabel, "DEADLINE_OPTIONS", 'deadline_options');
	babelHelpers.defineProperty(AnalyticsLabel, "DEADLINE_CHANGE_ALLOW", 'deadline_change_allow');
	babelHelpers.defineProperty(AnalyticsLabel, "SKIP_HOLIDAYS", 'skip_holidays');
	babelHelpers.defineProperty(AnalyticsLabel, "SET_HOLIDAYS", 'set_holidays');
	babelHelpers.defineProperty(AnalyticsLabel, "SUBTASK_DATES_DERIVATION", 'subtask_dates_derivation');
	babelHelpers.defineProperty(AnalyticsLabel, "SUBTASK_AUTO_COMPLETE", 'subtask_auto_complete');
	babelHelpers.defineProperty(AnalyticsLabel, "ADD_TO_PLAN", 'add_to_plan');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_CHECK_ENB", 'task_check_enb');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_RESULT_ENB", 'task_result_enb');
	babelHelpers.defineProperty(AnalyticsLabel, "PROJECT_CHANGE", 'project_change');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_CRM_ADD", 'task_crm_add');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_OPEN_MORE", 'task_open_more');
	babelHelpers.defineProperty(AnalyticsLabel, "TIME_TRACK_ENB", 'time_track_enb');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_HOURS_SET", 'task_hours_set');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_MIN_SET", 'task_min_set');
	babelHelpers.defineProperty(AnalyticsLabel, "CUST_FIELD_ADD", 'cust_field_add');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_FIELD_HIDE", 'task_field_hide');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_FIELD_EDIT", 'task_field_edit');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_FIELD_SET", 'task_field_set');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_FIELD_DRAG_ENB", 'task_field_drag_enb');
	babelHelpers.defineProperty(AnalyticsLabel, "ADD_DEPEND_TASK", 'add_depend_task');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_SAVE_ASTEMPLATE", 'task_save_astemplate');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_ALL_FIELDS", 'task_all_fields');
	babelHelpers.defineProperty(AnalyticsLabel, "COMM_CLOSE", 'comm_close');
	babelHelpers.defineProperty(AnalyticsLabel, "TASK_PING", 'task_ping');
	babelHelpers.defineProperty(AnalyticsLabel, "FILTER_PRESET_APPLY", 'filter_preset_apply');
	babelHelpers.defineProperty(AnalyticsLabel, "FILTERS_SET", 'filters_set');
	babelHelpers.defineProperty(AnalyticsLabel, "FILTER_APPLY", 'filter_apply');

	exports.Analytics = Analytics;
	exports.AnalyticsLabel = AnalyticsLabel;

}((this.BX.Tasks = this.BX.Tasks || {}),BX));
//# sourceMappingURL=analytics.bundle.js.map

/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports) {
	'use strict';

	const AhaMoment = Object.freeze({
	  AuditorsInCard: 'auditors_in_card'
	});

	const Tool = Object.freeze({
	  Tasks: 'tasks'
	});
	const Category = Object.freeze({
	  TaskOperations: 'task_operations'
	});
	const Event = Object.freeze({
	  DeadlineSet: 'deadline_set',
	  AssigneeChange: 'assignee_change',
	  TaskComplete: 'task_complete',
	  TaskCreate: 'task_create',
	  AddChecklist: 'add_checklist',
	  TaskCreateWithChecklist: 'task_create_with_checklist',
	  AttachFile: 'attach_file',
	  AddProject: 'add_project',
	  DescriptionTask: 'description_task',
	  FillTaskFormView: 'full_task_form_view',
	  ClickCreate: 'click_create',
	  RoleClick: 'role_click',
	  RoleClickType: 'role_click_type'
	});
	const Type = Object.freeze({
	  TaskMini: 'task_mini',
	  Task: 'task'
	});
	const Section = Object.freeze({
	  test: 'test'
	});
	const TaskState = Object.freeze({
	  New: 'new',
	  Existing: 'existing'
	});
	const SubSection = Object.freeze({
	  FullTaskForm: 'full_task_form',
	  TaskCard: 'task_card',
	  MyFiles: 'my_files',
	  Bitrix24Files: 'bitrix24_files'
	});
	const Element = Object.freeze({
	  FullFormButton: 'full_form_button',
	  ProjectButton: 'project_button',
	  Calendar: 'calendar',
	  DeadlinePreset: 'deadline_preset',
	  ChangeButton: 'change_button',
	  UploadButton: 'upload_button',
	  CheckListButton: 'checklist_button',
	  RoleButton: 'role_button',
	  RoleAllButton: 'role_all_button',
	  RoleHelpButton: 'role_help_button',
	  RoleDoingButton: 'role_doing_button',
	  RoleAssignedButton: 'role_assigned_button',
	  RoleWatchingButton: 'role_watching_button'
	});
	const Status = Object.freeze({
	  Success: 'success',
	  Error: 'error',
	  Attempt: 'attempt',
	  Cancel: 'cancel'
	});
	const Params = Object.freeze({
	  CollabId: collabId => `collabId_${collabId}`,
	  ViewersCount: count => `viewersCount_${count}`,
	  CoexecutorsCount: count => `coexecutorsCount_${count}`,
	  ChecklistCount: (count, itemsCount) => `checklistCount_${count}-${itemsCount}`,
	  ChecklistPointsCount: count => `checklistpointsCount_${count}`,
	  HasDescription: has => `description_${has ? 'true' : 'false'}`,
	  HasScroll: has => `scroll_${has ? 'true' : 'false'}`,
	  FileSize: size => `size_${size}`,
	  FilesCount: count => `filesCount_${count}`,
	  FileExtension: extension => `ext_${extension}`,
	  IsDemo: isDemo => `isDemo_${isDemo ? 'Y' : 'N'}`,
	  FilterEnabled: enabled => `filter_${enabled ? 'on' : 'off'}`
	});
	const Analytics = Object.freeze({
	  Tool,
	  Category,
	  Event,
	  Type,
	  Section,
	  SubSection,
	  Element,
	  Status,
	  Params,
	  TaskState
	});

	const CardType = Object.freeze({
	  Compact: 'task_mini',
	  Full: 'task'
	});

	const DurationUnit = Object.freeze({
	  Minutes: 'minutes',
	  Hours: 'hours',
	  Days: 'days'
	});

	const EntitySelectorEntity = Object.freeze({
	  Epic: 'epic-selector',
	  Flow: 'flow',
	  Project: 'project',
	  Tag: 'task-tag',
	  User: 'user',
	  StructureNode: 'structure-node',
	  Deal: 'deal',
	  Contact: 'contact',
	  Company: 'company',
	  Lead: 'lead',
	  SmartInvoice: 'smart_invoice',
	  DynamicMultiple: 'dynamic_multiple',
	  Task: 'task'
	});

	const EventName = Object.freeze({
	  CloseCard: 'tasks:card:close',
	  CloseFullCard: 'tasks:full-card:close',
	  OpenFullCard: 'tasks:card:openFullCard',
	  OpenCompactCard: 'tasks:card:openCompactCard',
	  TaskAdd: 'tasks:card:taskAdd',
	  TaskUpdate: 'tasks:card:taskUpdate',
	  OpenSliderCard: 'tasks:card:openSliderCard',
	  OpenGrid: 'tasks:card:openGrid',
	  ShowOverlay: 'tasks:card:showOverlay',
	  HideOverlay: 'tasks:card:hideOverlay',
	  AdjustPosition: 'tasks:card:adjustPosition',
	  CloseCheckList: 'tasks:card:check-list:close',
	  NotifyGrid: 'tasksTaskEvent'
	});

	const GroupType = Object.freeze({
	  Group: 'group',
	  Project: 'project',
	  Scrum: 'scrum',
	  Collab: 'collab'
	});

	const Limit = Object.freeze({
	  RelationList: 3
	});

	const Model = Object.freeze({
	  CheckList: 'checklist',
	  CrmItems: 'crm-items',
	  Epics: 'epics',
	  Flows: 'flows',
	  Groups: 'groups',
	  Interface: 'interface',
	  Stages: 'stages',
	  Tasks: 'tasks',
	  Users: 'users'
	});

	const Module = Object.freeze({
	  Tasks: 'tasks'
	});

	const Option = Object.freeze({
	  // AhaMoments
	  AhaAuditorsInCompactFormPopup: 'aha_auditors_compact_form'
	});

	const TaskStatus = Object.freeze({
	  Pending: 'pending',
	  InProgress: 'in_progress',
	  Completed: 'completed',
	  SupposedlyCompleted: 'supposedly_completed',
	  Deferred: 'deferred'
	});

	const TaskField = Object.freeze({
	  Accomplices: 'accomplicesIds',
	  Auditors: 'auditorsIds',
	  CheckList: 'checklist',
	  Creator: 'creatorId',
	  Crm: 'crmItemIds',
	  DatePlan: 'datePlan',
	  Deadline: 'deadlineTs',
	  Files: 'fileIds',
	  Flow: 'flowId',
	  Group: 'groupId',
	  Parent: 'parentId',
	  RelatedTasks: 'relatedTasks',
	  Responsible: 'responsibleId',
	  Status: 'status',
	  SubTasks: 'subTasks',
	  Tags: 'tags',
	  Title: 'title'
	});

	exports.AhaMoment = AhaMoment;
	exports.Analytics = Analytics;
	exports.CardType = CardType;
	exports.DurationUnit = DurationUnit;
	exports.EntitySelectorEntity = EntitySelectorEntity;
	exports.EventName = EventName;
	exports.GroupType = GroupType;
	exports.Limit = Limit;
	exports.Model = Model;
	exports.Module = Module;
	exports.Option = Option;
	exports.TaskStatus = TaskStatus;
	exports.TaskField = TaskField;

}((this.BX.Tasks.V2.Const = this.BX.Tasks.V2.Const || {})));
//# sourceMappingURL=const.bundle.js.map

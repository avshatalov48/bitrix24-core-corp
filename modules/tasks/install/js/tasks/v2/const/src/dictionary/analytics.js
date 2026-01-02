const Tool = Object.freeze({
	Tasks: 'tasks',
});

const Category = Object.freeze({
	TaskOperations: 'task_operations',
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
	RoleClickType: 'role_click_type',
});

const Type = Object.freeze({
	TaskMini: 'task_mini',
	Task: 'task',
});

const Section = Object.freeze({
	test: 'test',
});

const TaskState = Object.freeze({
	New: 'new',
	Existing: 'existing',
});

const SubSection = Object.freeze({
	FullTaskForm: 'full_task_form',
	TaskCard: 'task_card',
	MyFiles: 'my_files',
	Bitrix24Files: 'bitrix24_files',
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
	RoleWatchingButton: 'role_watching_button',
});

const Status = Object.freeze({
	Success: 'success',
	Error: 'error',
	Attempt: 'attempt',
	Cancel: 'cancel',
});

const Params = Object.freeze({
	CollabId: (collabId: number): string => `collabId_${collabId}`,
	ViewersCount: (count: number): string => `viewersCount_${count}`,
	CoexecutorsCount: (count: number): string => `coexecutorsCount_${count}`,
	ChecklistCount: (count: number, itemsCount: number): string => `checklistCount_${count}-${itemsCount}`,
	ChecklistPointsCount: (count: number): string => `checklistpointsCount_${count}`,
	HasDescription: (has: number): string => `description_${has ? 'true' : 'false'}`,
	HasScroll: (has: number): string => `scroll_${has ? 'true' : 'false'}`,
	FileSize: (size: number): string => `size_${size}`,
	FilesCount: (count: number): string => `filesCount_${count}`,
	FileExtension: (extension: string): string => `ext_${extension}`,
	IsDemo: (isDemo: string): string => `isDemo_${isDemo ? 'Y' : 'N'}`,
	FilterEnabled: (enabled: string): string => `filter_${enabled ? 'on' : 'off'}`,
});

export const Analytics = Object.freeze({
	Tool,
	Category,
	Event,
	Type,
	Section,
	SubSection,
	Element,
	Status,
	Params,
	TaskState,
});

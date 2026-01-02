import { Outline } from 'ui.icon-set.api.core';

export const PanelSection = Object.freeze({
	Important: 'important',
	Attachments: 'attachments',
	Movement: 'movement',
	Accomplice: 'accomplice',
	Auditor: 'auditor',
	Forward: 'forward',
	Delete: 'delete',
	Cancel: 'cancel',
});

export const PanelAction = Object.freeze({
	SetImportant: 'setImportant',
	AttachFile: 'attachFile',
	MoveRight: 'moveRight',
	MoveLeft: 'moveLeft',
	AssignAccomplice: 'assignAccomplice',
	AssignAuditor: 'assignAuditor',
	Forward: 'forward',
	Delete: 'delete',
	Cancel: 'cancel',
});

export const PanelMeta = Object.freeze({
	defaultSections: [
		{
			name: PanelSection.Important,
			items: [
				{
					icon: Outline.FIRE,
					activeIcon: Outline.FIRE_SOLID,
					action: PanelAction.SetImportant,
					hint: 'TASKS_V2_CHECK_LIST_ITEM_IMPORTANT_HINT',
					className: '--important',
					hoverable: false,
				},
			],
		},
		{
			name: PanelSection.Attachments,
			items: [
				{
					icon: Outline.ATTACH,
					action: PanelAction.AttachFile,
					hint: 'TASKS_V2_CHECK_LIST_ITEM_ATTACH_HINT',
				},
			],
		},
		{
			name: PanelSection.Movement,
			items: [
				{
					icon: Outline.POINT_RIGHT,
					action: PanelAction.MoveRight,
					hint: 'TASKS_V2_CHECK_LIST_ITEM_MOVE_RIGHT_HINT',
				},
				{
					icon: Outline.POINT_LEFT,
					action: PanelAction.MoveLeft,
					hint: 'TASKS_V2_CHECK_LIST_ITEM_MOVE_LEFT_HINT',
				},
			],
		},
		{
			name: PanelSection.Accomplice,
			items: [
				{
					icon: Outline.PERSON,
					action: PanelAction.AssignAccomplice,
					hint: 'TASKS_V2_CHECK_LIST_ITEM_ACCOMPLICE_HINT',
				},
			],
		},
		{
			name: PanelSection.Auditor,
			items: [
				{
					icon: Outline.OBSERVER,
					action: PanelAction.AssignAuditor,
					hint: 'TASKS_V2_CHECK_LIST_ITEM_AUDITOR_HINT',
				},
			],
		},
		{
			name: PanelSection.Forward,
			items: [
				{
					icon: Outline.FORWARD,
					action: PanelAction.Forward,
					hint: 'TASKS_V2_CHECK_LIST_ITEM_FORWARD_HINT',
				},
			],
		},
		{
			name: PanelSection.Delete,
			items: [
				{
					icon: Outline.TRASHCAN,
					action: PanelAction.Delete,
					hint: 'TASKS_V2_CHECK_LIST_ITEM_REMOVE_HINT',
				},
			],
		},
		{
			name: PanelSection.Cancel,
			items: [
				{
					icon: Outline.CROSS_L,
					action: PanelAction.Cancel,
					hint: 'TASKS_V2_CHECK_LIST_ITEM_CANCEL_HINT',
				},
			],
		},
	],
});

export type Section = {
	name: string,
	items: Item[],
};

export type Item = {
	icon: string,
	activeIcon?: string,
	action: string,
	label?: string,
	hint?: string,
	active?: boolean,
	hoverable?: boolean,
	disabled?: boolean,
	className?: string,
};

export type VisibleSections = Section[];
export type VisibleActions = string[];
export type ActiveActions = string[];

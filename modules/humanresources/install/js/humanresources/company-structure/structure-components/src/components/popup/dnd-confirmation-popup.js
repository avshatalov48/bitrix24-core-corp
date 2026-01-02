import { Loc, type JsonObject } from 'main.core';
import { Menu, PopupManager } from 'main.popup';
import { getMemberRoles } from 'humanresources.company-structure.api';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { ConfirmationPopup } from '../popup/confirmation-popup';

import type { MemberRolesType } from 'humanresources.company-structure.api';

import './styles/dnd-confirmation-popup.css';

// @vue/component
export const MoveEmployeeConfirmationPopup = {
	name: 'MoveEmployeeConfirmationPopup',
	components: { ConfirmationPopup },
	props: {
		title: {
			type: String,
			default: '',
		},
		description: {
			type: String,
			required: true,
		},
		confirmButtonText: {
			type: String,
			default: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_CONFIRM_BTN'),
		},
		targetType: {
			type: String,
			default: 'department',
		},
		sourceType: {
			type: String,
			default: 'department',
		},
		showRoleSelect: {
			type: Boolean,
			default: false,
		},
		showCombineCheckbox: {
			type: Boolean,
			default: false,
		},
		isCombineOnly: {
			type: Boolean,
			default: false,
		},
		excludeEmployeeRole: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['confirm', 'close'],
	data(): JsonObject
	{
		return {
			selectedRole: null,
			combinePosition: false,
		};
	},
	computed: {
		memberRoles(): ?MemberRolesType
		{
			return getMemberRoles(this.targetType);
		},
		selectedRoleLabel(): string
		{
			switch (this.selectedRole)
			{
				case this.memberRoles.head:
					return this.isTeamTarget
						? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_TEAM_HEAD')
						: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_HEAD');
				case this.memberRoles.deputyHead:
					return this.isTeamTarget
						? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_TEAM_DEPUTY')
						: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_DEPUTY');
				case this.memberRoles.employee:
					return this.isTeamTarget
						? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_MEMBER')
						: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_EMPLOYEE');
				default:
					return '';
			}
		},
		isCombineCheckboxEnabled(): boolean
		{
			return this.isCombineOnly
				|| (this.sourceType === EntityTypes.team && this.isTeamTarget);
		},
		isTeamTarget(): boolean
		{
			return this.targetType === EntityTypes.team;
		},
		popupTitle(): string
		{
			if (this.title)
			{
				return this.title;
			}

			return this.isTeamTarget
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_TITLE_TEAM')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_TITLE_DEPT');
		},
		popupCheckboxText(): string
		{
			return this.isTeamTarget
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_CHECKBOX_TEAM')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_CHECKBOX_DEPT');
		},
		showCombineNotice(): boolean
		{
			const isDeptToTeam = this.sourceType === EntityTypes.department && this.isTeamTarget;

			return this.isCombineOnly && isDeptToTeam;
		},
	},
	created()
	{
		if (this.showRoleSelect)
		{
			this.selectedRole = this.excludeEmployeeRole ? this.memberRoles.head : this.memberRoles.employee;
		}
		this.combinePosition = this.isCombineCheckboxEnabled;
	},
	methods: {
		loc(phrase: string): string
		{
			return Loc.getMessage(phrase);
		},
		handleConfirm(): void
		{
			const payload = {
				role: this.selectedRole,
				roleLabel: this.selectedRoleLabel,
				isCombineMode: this.combinePosition,
			};

			if (this.selectedRole !== this.memberRoles.employee)
			{
				payload.badgeText = this.selectedRoleLabel;
			}

			this.$emit('confirm', payload);
		},
		toggleRoleMenu(): void
		{
			const menuId = 'dnd-confirmation-role-menu';
			const bindElement = this.$refs.roleSelect;

			if (PopupManager.getPopupById(menuId))
			{
				PopupManager.getPopupById(menuId).destroy();

				return;
			}

			const menu = new Menu({
				id: menuId,
				bindElement,
				width: 334,
				items: this.roleMenuItems(),
				events: {
					onPopupClose: () => {
						menu.destroy();
					},
				},
			});
			menu.show();
		},
		roleMenuItems(): array
		{
			const items = [
				{
					id: this.memberRoles.head,
					text: this.isTeamTarget
						? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_TEAM_HEAD')
						: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_HEAD'),
				},
				{
					id: this.memberRoles.deputyHead,
					text: this.isTeamTarget
						? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_TEAM_DEPUTY')
						: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_DEPUTY'),
				},
			];

			if (!this.excludeEmployeeRole)
			{
				const employeeRoleText = this.isTeamTarget
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_MEMBER')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_BADGE_EMPLOYEE');

				items.push({
					id: this.memberRoles.employee,
					text: employeeRoleText,
				});
			}

			return items.map((item) => ({
				...item,
				onclick: (event, menuItem) => {
					this.selectedRole = menuItem.id;
					menuItem.getMenuWindow().close();
				},
			}));
		},
	},
	template: `
		<ConfirmationPopup
			:title="popupTitle"
			:width="364"
			:confirmBtnText="confirmButtonText"
			@action="handleConfirm"
			@close="$emit('close')"
		>
			<template v-slot:content>
				<div class="hr-dnd-confirmation_block">
					<div v-html="description" class="hr-dnd-confirmation_description"></div>
					<div v-if="showRoleSelect"
						 class="ui-ctl ui-ctl-w100 ui-ctl-sm ui-ctl-after-icon ui-ctl-dropdown hr-dnd-confirmation_select"
						 @click="toggleRoleMenu"
						 ref="roleSelect"
					>
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<div class="ui-ctl-element">{{ selectedRoleLabel }}</div>
					</div>
					<div v-if="showCombineCheckbox">
						<div
							v-if="showCombineNotice"
							class="hr-dnd-confirmation_notice"
						>
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_NOTICE') }}
						</div>
						<div 
							v-else
							class="ui-ctl ui-ctl-checkbox hr-dnd-confirmation_checkbox"
						>
							<input
								type="checkbox"
								class="ui-ctl-element"
								v-model="combinePosition"
								:disabled="isCombineOnly"
								id="dnd-confirmation-combine-checkbox"
							>
							<label for="dnd-confirmation-combine-checkbox" class="ui-ctl-label-text">
								{{ popupCheckboxText }}
							</label>
						</div>
					</div>
				</div>
			</template>
		</ConfirmationPopup>
	`,
};

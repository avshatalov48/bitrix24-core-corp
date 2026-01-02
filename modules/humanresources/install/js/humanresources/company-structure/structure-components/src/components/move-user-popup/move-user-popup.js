import { Text, Type } from 'main.core';
import { type BaseEvent } from 'main.core.events';
import { TagSelector } from 'ui.entity-selector';
import { UI } from 'ui.notification';
import { mapState } from 'ui.vue3.pinia';
import { Set as IconSet } from 'ui.icon-set.api.core';
import { BIcon } from 'ui.icon-set.api.vue';
import { getMemberRoles, type MemberRolesType } from 'humanresources.company-structure.api';
import { useChartStore, UserService } from 'humanresources.company-structure.chart-store';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { ConfirmationPopup } from '../popup/confirmation-popup';
import { MoveAPI } from './api';

import './styles/move-user-popup.css';

// @vue/component
export const MoveUserPopup = {
	name: 'MoveUserPopup',

	components: {
		ConfirmationPopup,
		BIcon,
	},

	props: {
		originalNodeId: {
			type: Number,
			required: true,
		},
		user: {
			type: Object,
			required: true,
		},
		entityType: {
			type: String,
			required: true,
		},
		executeAction: {
			type: Boolean,
			default: true,
		},
		onlyMove: {
			type: Boolean,
			default: true,
		},
	},

	emits: ['close', 'action', 'remove'],

	data(): Object
	{
		return {
			showMoveUserActionLoader: false,
			hasPermission: true,
			showUserAlreadyBelongsToDepartmentPopup: false,
			accessDenied: false,
			selectedParentDepartment: null,
		};
	},

	computed:
	{
		...mapState(useChartStore, ['departments', 'focusedNode']),
		includedNodeEntityTypesInDialog(): string[]
		{
			return this.isTeamEntity ? ['team'] : ['department'];
		},
		getMoveUserActionPhrase(): ?string
		{
			let phraseCode = '';

			if (this.isTeamEntity)
			{
				phraseCode = 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_TEAM_REMOVE_USER_DESCRIPTION';
			}
			else if (this.onlyMove)
			{
				phraseCode = 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_DESCRIPTION_ONLY_MOVE';
			}
			else
			{
				return null;
			}

			phraseCode += this.user.gender === 'F' ? '_F' : '_M';

			return this.getStandardPhrase(phraseCode, this.originalNodeId);
		},
		getMoveUserActionPhraseWarning(): ?string
		{
			if (this.isTeamEntity)
			{
				return null;
			}

			const departmentName = Text.encode(this.departments.get(this.selectedParentDepartment ?? 0).name ?? '');
			const userName = Text.encode(this.user.name ?? '');

			return this.loc(
				'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO',
				{
					'#USER_NAME#': userName,
					'#DEPARTMENT_NAME#': departmentName,
				},
			)
				.replace(
					'[link]',
					'',
				)
				.replace('[/link]', '')
			;
		},
		getUserAlreadyBelongsToDepartmentPopupPhrase(): string
		{
			let phraseCode = '';
			if (this.isTeamEntity)
			{
				phraseCode = 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO_TEAM_DESCRIPTION';
				phraseCode += this.user.gender === 'F' ? '_F' : '_M';
			}
			else
			{
				phraseCode = 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO_DEPARTMENT_DESCRIPTION';
			}

			return this.getStandardPhrase(phraseCode, this.selectedParentDepartment);
		},
		memberRoles(): MemberRolesType
		{
			return getMemberRoles(this.entityType);
		},
		isTeamEntity(): boolean
		{
			return this.entityType === EntityTypes.team;
		},
		confirmTitle(): string
		{
			if (this.isTeamEntity)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_TEAM_POPUP_CONFIRM_TITLE');
			}

			if (this.onlyMove)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_TITLE_ONLY_MOVE');
			}

			return this.userHasOtherDepartments
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_MULTIROLE_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_TITLE')
			;
		},
		confirmDescription(): string
		{
			if (this.isTeamEntity)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_TEAM_POPUP_ACTION_SELECT_TEAM_DESCRIPTION');
			}

			if (this.onlyMove)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_SELECT_DEPARTMENT_DESCRIPTION');
			}

			return this.userHasOtherDepartments
				? this.getStandardPhrase('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_MULTIROLE_DESCRIPTION', this.originalNodeId)
				: this.getStandardPhrase('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_DESCRIPTION', this.originalNodeId)
			;
		},
		isInSelectedNode(): boolean
		{
			const store = useChartStore();
			const nodeIds = store.multipleUsers[this.user.id];

			return this.selectedParentDepartment && Type.isArray(nodeIds) && nodeIds.includes(this.selectedParentDepartment);
		},
		parentNodeId(): ?number
		{
			const originalNode = this.departments.get(this.originalNodeId);
			const parentId = originalNode.parentId;

			if (!parentId)
			{
				return this.originalNodeId;
			}
			const parentNode = this.departments.get(parentId);

			return (parentNode && parentNode.entityType === originalNode.entityType) ? parentId : this.originalNodeId;
		},
		iconSet(): IconSet
		{
			return IconSet;
		},
		userHasOtherDepartments(): boolean
		{
			const store = useChartStore();
			const nodeIds = store.multipleUsers[this.user.id];

			return Type.isArray(nodeIds) && nodeIds.length > 1;
		},
		confirmButtonText(): string
		{
			if (this.onlyMove)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_MOVE_BUTTON_ONLY_MOVE');
			}

			return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_MOVE_BUTTON');
		},
		lockMoveUserActionButton(): boolean
		{
			return !this.hasPermission
				|| (!this.userHasOtherDepartments && !this.selectedParentDepartment)
				|| (this.onlyMove && !this.selectedParentDepartment)
			;
		},
		isWarningVisible(): boolean
		{
			return this.isInSelectedNode && this.getMoveUserActionPhraseWarning;
		},
	},

	created(): void
	{
		this.permissionChecker = PermissionChecker.getInstance();

		if (!this.permissionChecker)
		{
			return;
		}

		this.action = this.isTeamEntity
			? PermissionActions.teamAddMember
			: PermissionActions.employeeAddToDepartment
		;
		this.selectedDepartmentId = 0;
	},

	mounted(): void
	{
		const departmentContainer = this.$refs['department-selector'];
		this.departmentSelector = this.createTagSelector();
		this.departmentSelector.renderTo(departmentContainer);
	},

	methods:
	{
		createTagSelector(): TagSelector
		{
			return new TagSelector({
				events: {
					onTagAdd: (event: BaseEvent) => {
						this.accessDenied = false;
						const { tag } = event.data;
						this.selectedParentDepartment = tag.id;
						if (PermissionChecker.hasPermission(this.action, tag.id))
						{
							this.hasPermission = true;

							return;
						}

						this.accessDenied = true;
						this.hasPermission = false;
					},
					onTagRemove: () => {
						this.selectedParentDepartment = null;
					},
				},
				multiple: false,
				dialogOptions: {
					width: 425,
					height: 350,
					dropdownMode: true,
					hideOnDeselect: true,
					entities: [
						{
							id: 'structure-node',
							options: {
								selectMode: 'departmentsOnly',
								restricted: 'addMember',
								includedNodeEntityTypes: this.includedNodeEntityTypesInDialog,
								useMultipleTabs: true,
							},
						},
					],
				},
			});
		},
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		async confirmMoveUser(): Promise<void>
		{
			const departmentId = this.focusedNode;
			const userId = this.user.id;
			const targetNodeId = this.selectedParentDepartment;

			if (!targetNodeId)
			{
				this.$emit('remove');

				return;
			}

			if (!this.executeAction)
			{
				this.$emit('action', targetNodeId);

				return;
			}

			this.showMoveUserActionLoader = true;
			try
			{
				await MoveAPI.moveUserToDepartment(
					departmentId,
					userId,
					targetNodeId,
				);
			}
			catch (error)
			{
				this.showMoveUserActionLoader = false;

				const code = error.code ?? 0;

				if (code === 'MEMBER_ALREADY_BELONGS_TO_NODE')
				{
					this.showUserAlreadyBelongsToDepartmentPopup = true;
				}
				else
				{
					const phraseCode = this.isTeamEntity
						? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_TEAM_ERROR')
						: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_ERROR')
					;
					UI.Notification.Center.notify({
						content: phraseCode,
						autoHideDelay: 2000,
					});
					this.$emit('close');
				}

				return;
			}

			const departmentName = Text.encode(this.departments.get(targetNodeId)?.name ?? '');
			const phraseCode = this.isTeamEntity
				? 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_TEAM_SUCCESS_MESSAGE'
				: 'HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_SUCCESS_MESSAGE'
			;
			UI.Notification.Center.notify({
				content: this.loc(
					phraseCode,
					{
						'#DEPARTMENT_NAME#': departmentName,
					},
				),
				autoHideDelay: 2000,
			});

			UserService.moveUserToEntity(
				departmentId,
				userId,
				targetNodeId,
				this.user.role ?? this.memberRoles.employee,
			);

			this.$emit('action', targetNodeId);
			this.showMoveUserActionLoader = false;
		},
		closeAction(): void
		{
			this.$emit('close');
		},
		closeUserAlreadyBelongsToDepartmentPopup(): void
		{
			this.showUserAlreadyBelongsToDepartmentPopup = false;
			this.closeAction();
		},
		getStandardPhrase(phrase: string, departmentId: ?number): string
		{
			const departmentName = Text.encode(this.departments.get(departmentId ?? 0)?.name ?? '');
			const userName = Text.encode(this.user.name ?? '');

			return this.loc(
				phrase,
				{
					'#USER_NAME#': userName,
					'#DEPARTMENT_NAME#': departmentName,
				},
			)
				.replace(
					'[link]',
					`<a class="hr-department-detail-content__move-user-department-user-link" href="${this.user.url}">`,
				)
				.replace('[/link]', '</a>')
			;
		},
	},

	template: `
		<ConfirmationPopup
			@action="confirmMoveUser"
			@close="closeAction"
			:showActionButtonLoader="showMoveUserActionLoader"
			:lockActionButton="lockMoveUserActionButton"
			:title="confirmTitle"
			:confirmBtnText = "confirmButtonText"
			:width="364"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
					<div v-if="getMoveUserActionPhrase" v-html="getMoveUserActionPhrase"/>
					<span v-html="confirmDescription"/>
				</div>
				<div
					class="hr-department-detail-content__move-user-department-selector"
					ref="department-selector"
					:class="{ 'ui-ctl-warning': accessDenied }"
				/>
				<div v-if="isWarningVisible" class="hr-department-detail-content__move-user-department_item-warning">
					<BIcon
						:name="iconSet.WARNING"
						color="#FFA900"
						:size="20"
					></BIcon>
					<span v-html="getMoveUserActionPhraseWarning"/>
				</div>
				<div
					v-if="accessDenied"
					class="hr-department-detail-content__move-user-department_item-error"
				>
					<div class="ui-icon-set --warning"></div>
					<span
						class="hr-department-detail-content__move-user-department_item-error-message"
					>
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_PERMISSION_ERROR') }}
					</span>
				</div>
			</template>
		</ConfirmationPopup>
		<ConfirmationPopup
			@action="closeUserAlreadyBelongsToDepartmentPopup"
			@close="closeUserAlreadyBelongsToDepartmentPopup"
			v-if="showUserAlreadyBelongsToDepartmentPopup"
			:withoutTitleBar = true
			:onlyConfirmButtonMode = true
			:confirmBtnText = "loc('HUMANRESOURCES_COMPANY_STRUCTURE_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_CLOSE_BUTTON')"
			:width="300"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
					<div 
						class="hr-department-detail-content__user-belongs-to-department-text-container"
						v-html="getUserAlreadyBelongsToDepartmentPopupPhrase"
					/>
				</div>
				<div class="hr-department-detail-content__move-user-department-selector" ref="department-selector"></div>
			</template>
		</ConfirmationPopup>
	`,
};

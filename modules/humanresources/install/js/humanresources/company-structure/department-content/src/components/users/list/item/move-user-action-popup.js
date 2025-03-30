import { memberRoles } from 'humanresources.company-structure.api';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { ConfirmationPopup } from 'humanresources.company-structure.structure-components';
import { Text } from 'main.core';
import { TagSelector } from 'ui.entity-selector';
import { UI } from 'ui.notification';
import { mapState } from 'ui.vue3.pinia';
import { DepartmentContentActions } from '../../../../actions';
import { DepartmentAPI } from '../../../../api';

import './styles/move-user-action-popup.css';

export const MoveUserActionPopup = {
	name: 'MoveUserActionPopup',
	components: { ConfirmationPopup },
	emits: ['close', 'action'],

	props: {
		parentId: {
			type: Number,
			required: true,
		},
		user: {
			type: Object,
			required: true,
		},
	},

	created(): void
	{
		this.permissionChecker = PermissionChecker.getInstance();

		if (!this.permissionChecker)
		{
			return;
		}

		this.action = PermissionActions.employeeAddToDepartment;
		this.selectedDepartmentId = 0;
	},

	data(): Object
	{
		return {
			showMoveUserActionLoader: false,
			lockMoveUserActionButton: false,
			showUserAlreadyBelongsToDepartmentPopup: false,
			accessDenied: false,
		};
	},

	mounted(): void
	{
		const departmentContainer = this.$refs['department-selector'];
		this.departmentSelector = this.getTagSelector(departmentContainer);
		this.departmentSelector.renderTo(departmentContainer);
	},

	computed: {
		...mapState(useChartStore, ['departments', 'focusedNode']),
		getMoveUserActionPhrase(): string
		{
			const departmentName = Text.encode(this.departments.get(this.focusedNode).name ?? '');
			const userName = Text.encode(this.user.name ?? '');

			return this.loc(
				'HUMANRESOURCES_DEPARTMENT_CONTENT_TAB_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_REMOVE_USER_DESCRIPTION',
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
		getUserAlreadyBelongsToDepartmentPopupPhrase(): string
		{
			const departmentName = Text.encode(this.departments.get(this.selectedParentDepartment ?? 0).name ?? '');
			const userName = Text.encode(this.user.name ?? '');

			let phrase = this.loc(
				'HUMANRESOURCES_DEPARTMENT_CONTENT_TAB_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO_DEPARTMENT_DESCRIPTION',
				{
					'#USER_NAME#': userName,
					'#DEPARTMENT_NAME#': departmentName,
				},
			);
			phrase = phrase.replace('[link]', `<a class="hr-department-detail-content__move-user-department-user-link" href="${this.user.url}">`);
			phrase = phrase.replace('[/link]', '</a>');

			return phrase;
		},
		memberRoles(): typeof memberRoles
		{
			return memberRoles;
		},
	},

	methods: {
		getTagSelector(): TagSelector
		{
			return new TagSelector({
				events: {
					onTagAdd: (event: BaseEvent) => {
						this.accessDenied = false;
						const { tag } = event.data;
						this.selectedParentDepartment = tag.id;
						if (PermissionChecker.hasPermission(this.action, tag.id))
						{
							this.lockMoveUserActionButton = false;

							return;
						}

						this.accessDenied = true;
						this.lockMoveUserActionButton = true;
					},
					onTagRemove: () => {
						this.lockMoveUserActionButton = true;
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
							},
						},
					],
					preselectedItems: [['structure-node', this.parentId]],
				},
				tagBgColor: '#ade7e4',
				tagTextColor: '#207976',
				tagFontWeight: '700',
				tagAvatar: '/bitrix/js/humanresources/entity-selector/src/images/department.svg',
			});
		},
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		async confirmMoveUser(): Promise<void>
		{
			this.showMoveUserActionLoader = true;
			const departmentId = this.focusedNode;
			const userId = this.user.id;
			const targetNodeId = this.selectedParentDepartment;

			try
			{
				await DepartmentAPI.moveUserToDepartment(
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
					UI.Notification.Center.notify({
						content: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_ERROR'),
						autoHideDelay: 2000,
					});
					this.$emit('close');
				}

				return;
			}

			const departmentName = Text.encode(this.departments.get(targetNodeId)?.name ?? '');
			UI.Notification.Center.notify({
				content: this.loc(
					'HUMANRESOURCES_DEPARTMENT_CONTENT_TAB_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_SUCCESS_MESSAGE',
					{
						'#DEPARTMENT_NAME#': departmentName,
					},
				),
				autoHideDelay: 2000,
			});

			DepartmentContentActions.moveUserToDepartment(
				departmentId,
				userId,
				targetNodeId,
				this.user.role ?? memberRoles.employee,
			);

			this.$emit('action');
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
	},

	template: `
		<ConfirmationPopup
			@action="confirmMoveUser"
			@close="closeAction"
			:showActionButtonLoader="showMoveUserActionLoader"
			:lockActionButton="lockMoveUserActionButton"
			:title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_CONFIRM_TITLE')"
			:confirmBtnText = "loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_CONFIRM_BUTTON')"
			:width="364"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
					<div v-html="getMoveUserActionPhrase"/>
					<span>
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_POPUP_ACTION_SELECT_DEPARTMENT_DESCRIPTION')}}
					</span>
				</div>
				<div class="hr-department-detail-content__move-user-department-selector" ref="department-selector"></div>
				<div
					class="hr-department-detail-content__move-user-department-selector"
					ref="department-selector"
					:class="{ 'ui-ctl-warning': accessDenied }"
				/>
				<div
					v-if="accessDenied"
					class="hr-department-detail-content__move-user-department_item-error"
				>
					<div class="ui-icon-set --warning"></div>
					<span
						class="hr-department-detail-content__move-user-department_item-error-message"
					>
							{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_PERMISSION_ERROR')}}
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
			:confirmBtnText = "loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO_DEPARTMENT_CLOSE_BUTTON')"
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

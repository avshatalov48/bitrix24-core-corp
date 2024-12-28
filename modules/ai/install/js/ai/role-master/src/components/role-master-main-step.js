import 'ui.layout-form';
import type { UploaderFile } from 'ui.uploader.core';

import { RoleMasterAvatarUploader } from './role-master-avatar-uploader';
import { RoleMasterStep } from './role-master-step';
import { RoleMasterUserSelector } from './role-master-user-selector';
import { RoleMasterEditor } from './role-master-editor';

import '../css/role-master-main-step.css';

export const RoleMasterMainStep = {
	components: {
		RoleMasterStep,
		RoleMasterUserSelector,
		RoleMasterAvatarUploader,
		RoleMasterEditor,
	},
	emits: ['uploadAvatarFile', 'removeAvatarFile', 'update:name', 'update:description', 'update:items-with-access'],
	props: {
		stepNumber: Number,
		avatar: String,
		name: String,
		description: String,
		itemsWithAccess: {
			type: Array,
			required: false,
			default: () => {
				return [];
			},
		},
		undeselectedItemsWithAccess: {
			type: Array,
			required: false,
			default: () => {
				return [];
			},
		},
	},
	methods: {
		handleAvatarFileUpload(file: UploaderFile): void
		{
			this.$emit('uploadAvatarFile', file);
		},
		handleAvatarFileRemove(): void
		{
			this.$emit('removeAvatarFile');
		},
		handleAvatarFileLoad(file: UploaderFile): void
		{
			this.$emit('loadAvatarFile', file);
		},
	},
	mounted() {
		requestAnimationFrame(() => {
			this.$refs.roleNameInput.focus();
		});
	},
	template: `
		<RoleMasterStep
			:title="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_MAIN_STEP_TITLE')"
			:step-number="stepNumber"
		>
			<slot>
				<div class="ai__role-master_main-step">
					<div class="ui-form">
						<div class="ui-form-row-inline">
							<div class="ui-form-row">
								<div class="ui-form-label">
									<div class="ui-ctl-label-text">
										{{ $Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_AVATAR_AND_NAME_FIELD') }}
									</div>
								</div>
								<div class="ui-form-content">
									<div class="ui-ctl">
										<RoleMasterAvatarUploader
											:avatar-url="avatar"
											@upload-avatar-file="handleAvatarFileUpload"
											@remove-avatar-file="handleAvatarFileRemove"
											@load-avatar-file="handleAvatarFileLoad"
										/>
									</div>
									<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
										<input
											ref="roleNameInput"
											:value="name"
											@input="$emit('update:name', $event.target.value)"
											type="text"
											:minlength="1"
											:maxlength="70"
											class="ui-ctl-element"
											:placeholder="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_AVATAR_AND_NAME_FIELD_PLACEHOLDER')"
										/>
									</div>
								</div>
							</div>
						</div>
						<div class="ui-form-row">
							<div class="ui-form-label">
								<div class="ui-ctl-label-text">
									{{ $Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_USERS_WITH_ACCESS_FIELD') }}
								</div>
							</div>
							<div class="ui-form-content">
								<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
									<RoleMasterUserSelector
										:selected-items="itemsWithAccess"
										:undeselected-items="undeselectedItemsWithAccess"
										@update:selected-items="items => $emit('update:items-with-access', items)"
									/>
								</div>
							</div>
						</div>
						<div class="ui-form-row --with-textarea">
							<div class="ui-form-label">
								<div class="ui-ctl-label-text">
									{{ $Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_DESCRIPTION_FIELD') }}
								</div>
							</div>
							<div class="ui-form-content">
								<div class="ui-ctl ui-ctl-textarea ui-ctl-w100 ui-ctl-no-resize">
									<RoleMasterEditor
										:min-text-length="0"
										:max-text-length="150"
										:text="description"
										:placeholder="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_DESCRIPTION_FIELD_PLACEHOLDER')"
										@update:text="$emit('update:description', $event)"
									/>
								</div>
							</div>
						</div>
					</div>
				</div>
			</slot>
		</RoleMasterStep>
	`,
};

import { Reflection, Extension, ajax, Http } from 'main.core';
import { EventEmitter } from 'main.core.events';
import type { UploaderFile } from 'ui.uploader.core';
import { RoleMasterTextStep } from './role-master-text-step';
import { RoleMasterBtn } from './role-master-btn';
import { RoleMasterMainStep } from './role-master-main-step';
import { RoleMasterSavingStatus } from './role-master-saving-status';
import { RoleMasterWarning } from './role-master-warning';
import { ButtonState } from 'ui.buttons';
import { BIcon } from 'ui.icon-set.api.vue';
import { Actions } from 'ui.icon-set.api.core';
import 'ui.icon-set.actions';
import { toRaw } from 'ui.vue3';
import { loadImageAsFile } from '../helpers';

const currentUserId = Extension.getSettings('ai.role-master').get('currentUserId');

type RoleMasterData = {
	currentStepNumber: number;
	roleText: string;
	roleName: string;
	roleAvatar: string;
	roleDescription: string;
	roleItemsWithAccess: [];
	uploadedAvatarFile: UploaderFile;
}

export const RoleSavingStatus = Object.freeze({
	NONE: 'none',
	SAVING: 'saving',
	SAVING_ERROR: 'saving-error',
	SAVING_SUCCESS: 'saving-success',
});

export const RoleMaster = {
	name: 'RoleMaster',
	components: {
		RoleMasterTextStep,
		RoleMasterMainStep,
		RoleMasterSavingStatus,
		RoleMasterBtn,
		RoleMasterWarning,
		BIcon,
	},
	props: {
		id: {
			type: String,
			required: false,
			default: '',
		},
		authorId: String,
		text: String,
		name: String,
		avatar: String,
		description: String,
		avatarUrl: String,
		itemsWithAccess: {
			type: Array,
			required: false,
			default: () => [],
		},
	},
	data(): RoleMasterData {
		return {
			currentStepNumber: 1,
			roleText: this.text,
			roleName: this.name,
			roleAvatar: this.avatar,
			roleAvatarUrl: this.avatarUrl,
			roleDescription: this.description,
			roleItemsWithAccess: this.itemsWithAccess.length > 0 ? this.itemsWithAccess : [['user', currentUserId]],
			uploadedAvatarFile: null,
			roleSavingStatus: RoleSavingStatus.NONE,
		};
	},
	computed: {
		isEditRoleMode(): boolean {
			return this.id;
		},
		maxTextLength(): number {
			return 2000;
		},
		minTextLength(): number {
			return 1;
		},
		isEditMode(): boolean {
			return false;
		},
		uploadedAvatarFilePreview(): ?string {
			return this.uploadedAvatarFile?.getPreviewUrl() ?? null;
		},
		RoleSavingStatus(): typeof RoleSavingStatus {
			return RoleSavingStatus;
		},
		ButtonState(): ButtonState {
			return ButtonState;
		},
		isRoleTextValid(): boolean {
			return this.roleText.length >= this.minTextLength && this.roleText.length <= this.maxTextLength;
		},
		isRoleNameValid(): boolean {
			return this.roleName.length > 0 && this.roleName.length <= 70;
		},
		isRoleDescriptionValid(): boolean {
			return this.roleDescription.length > 0 && this.roleDescription.length <= 150;
		},
		isRoleDataValid(): boolean {
			return this.isRoleTextValid
				&& this.isRoleNameValid
				&& this.isRoleDescriptionValid
				&& this.roleItemsWithAccess.length > 0
			;
		},
		nextStepBtnState(): string {
			return this.isRoleTextValid ? '' : this.ButtonState.DISABLED;
		},
		saveBtnState(): string {
			if (this.isRoleDataValid === false)
			{
				return ButtonState.DISABLED;
			}

			if (this.roleSavingStatus === RoleSavingStatus.SAVING)
			{
				return ButtonState.CLOCKING;
			}

			return '';
		},
		backIconProps(): { size: number, icon: string } {
			return {
				size: 16,
				icon: Actions.CHEVRON_LEFT,
			};
		},
		warningText(): string {
			return this.isEditRoleMode && this.itemsWithAccess.length > 1 ? this.$Bitrix.Loc.getMessage('ROLE_MASTER_EDIT_WARNING_TEXT') : '';
		},
		undeselectedItemsWithAccess(): Array
		{
			if (this.isEditRoleMode)
			{
				return [['user', this.authorId]];
			}

			return [['user', currentUserId]];
		},
	},
	methods: {
		async saveRole(): Promise<void>
		{
			const action = this.isEditRoleMode ? 'change' : 'create';

			let isLoadFinished = false;

			try
			{
				setTimeout(() => {
					if (isLoadFinished === false)
					{
						this.roleSavingStatus = RoleSavingStatus.SAVING;
					}
				}, 100);
				const roleAvatar = await this.getAvatarFile();

				const data = {
					roleText: this.roleText,
					roleTitle: this.roleName,
					roleAvatar,
					roleAvatarUrl: this.roleAvatarUrl,
					roleDescription: this.roleDescription,
					accessCodes: this.roleItemsWithAccess,
				};

				if (action === 'change')
				{
					data.roleCode = this.id;
				}

				await ajax.runAction(
					`ai.shareRole.${action}`,
					{
						data: Http.Data.convertObjectToFormData(data),
					},
				);
				this.roleSavingStatus = RoleSavingStatus.SAVING_SUCCESS;
				EventEmitter.emit('AI.RoleMasterApp:Save-success', data);
			}
			catch (e)
			{
				console.error(e);
				this.roleSavingStatus = RoleSavingStatus.SAVING_ERROR;
				EventEmitter.emit('AI.RoleMasterApp:Save-failed');
			}
			finally
			{
				isLoadFinished = true;
			}
		},
		async getAvatarFile(): File | Promise<File> | string {
			if (!this.uploadedAvatarFile)
			{
				const pathToDefaultAvatar = '/bitrix/js/ai/role-master/images/role-master-default-avatar.svg';

				return loadImageAsFile(pathToDefaultAvatar);
			}

			if (!this.uploadedAvatarFile.getBinary())
			{
				return this.avatarUrl;
			}

			return this.uploadedAvatarFile.getBinary();
		},
		handleAvatarFileUpload(file: UploaderFile): void {
			this.uploadedAvatarFile = file;
		},
		handleAvatarFileRemove(): void {
			this.uploadedAvatarFile = null;
			this.roleAvatar = null;
		},
		handleAvatarFileLoad(file: UploaderFile): void
		{
			this.uploadedAvatarFile = toRaw(file);
		},
		closeMaster(): void {
			EventEmitter.emit('AI.RoleMasterApp:Close');
		},
		backToEditor(): void {
			this.roleSavingStatus = RoleSavingStatus.NONE;
		},
		openHelpdeskSlider(): void
		{
			const articleCode = '23184474';

			const Helper = Reflection.getClass('top.BX.Helper');

			if (Helper)
			{
				Helper.show(`redirect=detail&code=${articleCode}`);
			}
		},
	},
	template: `
		<div>
			<header></header>
			<main>
				<RoleMasterTextStep
					v-if="currentStepNumber === 1"
					:step-number="1"
					:role-text="roleText"
					:warning-text="warningText"
					@update:role-text="roleText = $event"
					:max-text-length="maxTextLength"
					:min-text-length="minTextLength"
				/>
				<RoleMasterMainStep
					v-if="currentStepNumber === 2"
					:step-number="2"
					:name="roleName"
					:description="roleDescription"
					:avatar="uploadedAvatarFilePreview || roleAvatar"
					:items-with-access="roleItemsWithAccess"
					:undeselected-items-with-access="undeselectedItemsWithAccess"
					@upload-avatar-file="handleAvatarFileUpload"
					@remove-avatar-file="handleAvatarFileRemove"
					@load-avatar-file="handleAvatarFileLoad"
					@update:name="roleName = $event"
					@update:description="roleDescription = $event"
					@update:avatar="roleAvatar = $event"
					@update:items-with-access="roleItemsWithAccess = $event"
				/>
			</main>
			<footer class="ai__role-master-app_footer">
				<a
					v-if="currentStepNumber === 1"
					@click="openHelpdeskSlider"
					class="ai__role-master_about-link"
					href="#"
				>
					{{ $Bitrix.Loc.getMessage('ROLE_MASTER_ABOUT_ROLE_MASTER_LINK') }}
				</a>
				<button
					v-if="currentStepNumber > 1"
					@click="currentStepNumber -= 1"
					class="ai__role-master_back-btn"
				>
					<BIcon
						:size="backIconProps.size"
						:name="backIconProps.icon"
					/>
					{{ $Bitrix.Loc.getMessage('ROLE_MASTER_PREV_BUTTON') }}
				</button>
				<RoleMasterBtn
					v-if="currentStepNumber < 2"
					@click="currentStepNumber += 1"
					:state="nextStepBtnState"
					:text="$Bitrix.Loc.getMessage('ROLE_MASTER_NEXT_BUTTON')"
				/>
				<RoleMasterBtn
					v-else
					@click="saveRole"
					:state="saveBtnState"
					:text="$Bitrix.Loc.getMessage('ROLE_MASTER_SAVE_BUTTON')"
				/>
			</footer>

			<div v-if="roleSavingStatus !== RoleSavingStatus.NONE" class="ai__role-master_saving-status-container">
				<RoleMasterSavingStatus
					:status="roleSavingStatus"
					@close-master="closeMaster"
					@back-to-editor="backToEditor"
					@repeat-request="saveRole"
				/>
			</div>
		</div>
	`,
};

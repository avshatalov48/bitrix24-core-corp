import { ButtonColor } from 'ui.buttons';
import { RoleSavingStatus } from './role-master';
import { RoleMasterBtn } from './role-master-btn';
import { Loader } from 'main.loader';
import { BIcon, Set as IconSet } from 'ui.icon-set.api.vue';

// ButtonColor.LIGHT_BORDER
import 'ui.icon-set.main';

import '../css/role-master-saving-status.css';

export const RoleMasterSavingStatus = {
	props: {
		status: String,
	},
	emits: ['repeat-request', 'back-to-editor', 'close-master'],
	components: {
		BIcon,
		RoleMasterBtn,
	},
	computed: {
		savingStatus(): RoleSavingStatus {
			return RoleSavingStatus;
		},
		IconSet(): IconSet {
			return IconSet;
		},
		ButtonColor(): ButtonColor {
			return ButtonColor;
		},
	},
	methods: {
		showLoader(): void {
			const loader = new Loader({
				size: 80,
				color: getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary') ?? '#8e52ec',
				strokeWidth: 4,
			});

			loader.show(this.$refs.loaderContainer);
		},
	},
	mounted(): void {
		if (this.status === RoleSavingStatus.SAVING)
		{
			this.showLoader();
		}
	},
	updated(): void {
		if (this.status === RoleSavingStatus.SAVING)
		{
			this.showLoader();
		}
	},
	template: `
		<div class="ai__role-master_saving-status">
			<div v-if="status === savingStatus.SAVING" class="ai__role-master_saving-loading">
				<div ref="loaderContainer" class="ai__role-master_saving-loader"></div>
				<div class="ai__role-master_saving-loading-text">
					{{ $Bitrix.Loc.getMessage('ROLE_MASTER_LOADER_TEXT') }}
				</div>
			</div>
			<div v-else-if="status === savingStatus.SAVING_SUCCESS" class="ai__role-master_saving-success">
				<div class="ai__role-master_saving-success-top">
					<div class="ai__role-master_saving-success-icon">
						<BIcon
							:name="IconSet.CHECK"
							:size="58"
						/>
					</div>
					<div class="ai__role-master_saving-success-text">
						{{ $Bitrix.Loc.getMessage('ROLE_MASTER_SAVING_DONE') }}
					</div>
				</div>
				<div class="ai__role-master_saving-success-bottom">
					<RoleMasterBtn
						@click="$emit('close-master')"
						:text="$Bitrix.Loc.getMessage('ROLE_MASTER_CLOSE_BTN')"
						:color="ButtonColor.LIGHT_BORDER"
					/>
				</div>
			</div>
			<div v-else-if="status === savingStatus.SAVING_ERROR" class="ai__role-master_saving-error">
				<div class="ai__role-master_saving-error-center">
					<div class="ai__role-master_saving-error-icon">
						<BIcon
							:name="IconSet.NOTE_CIRCLE"
							:size="66"
						/>
					</div>
					<div class="ai__role-master_saving-error-status-text">
						{{ $Bitrix.Loc.getMessage('ROLE_MASTER_SAVE_PROMPT_ON_ERROR_SHORT_TEXT') }}
					</div>
					<RoleMasterBtn
						@click="$emit('repeat-request')"
						:text="$Bitrix.Loc.getMessage('ROLE_MASTER_REPEAT_REQUEST_BTN')"
						:color="ButtonColor.LIGHT_BORDER"
					/>
				</div>
				<div class="ai__role-master_saving-error-bottom">
					<p class="ai__role-master_saving-error-description-text">
						{{ $Bitrix.Loc.getMessage('ROLE_MASTER_SAVE_PROMPT_ON_ERROR_TEXT') }}
					</p>
					<button
						@click="$emit('back-to-editor')"
						class="ai__role-master_back-to-editor-btn"
					>
						<BIcon
							:size="16"
							:name="IconSet.CHEVRON_LEFT"
						/>
						<span class="ai__role-master_back-to-editor-btn-text">
							{{ $Bitrix.Loc.getMessage('ROLE_MASTER_BACK_TO_EDITOR_BTN') }}
						</span>
					</button>
				</div>
			</div>
		</div>
	`,
};

import { Button } from 'ui.buttons';
import { BIcon } from 'ui.icon-set.api.vue';
import { PromptMasterBtn } from './prompt-master-btn';
import { Main as MainIconSet, Actions as ActionsIconSet } from 'ui.icon-set.api.core';

import '../css/prompt-master-error-screen.css';

export const PromptMasterSaveErrorScreen = {
	components: {
		PromptMasterBtn,
		BIcon,
	},
	computed: {
		errorIcon(): string {
			return MainIconSet.NOTE_CIRCLE;
		},
		errorIconColor(): string {
			return getComputedStyle(document.body).getPropertyValue('--ui-color-text-alert');
		},
		backBtnIconName(): string {
			return ActionsIconSet.CHEVRON_LEFT;
		},
		repeatBtnColor(): string {
			return Button.Color.LIGHT_BORDER;
		},
	},
	methods: {
		emitRepeatRequest(): void {
			this.$emit('click-repeat-btn');
		},
		emitBackBtnClick(): void {
			this.$emit('click-back-btn');
		},
	},
	template: `
		<div class="ai__prompt-master_error-screen">
			<div class="ai__prompt-master_error-screen-icon">
				<BIcon
					:name="errorIcon"
					:color="errorIconColor"
					:size="66"
				/>
			</div>
			<span class="ai__prompt-master_error-screen-error">
				{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_ERROR') }}
			</span>
			<div class="ai__prompt-master_error-screen-repeat-btn">
				<PromptMasterBtn
					@click="emitRepeatRequest"
					:color="repeatBtnColor"
					:text="$Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_REPEAT_BTN')"
				/>
			</div>
			<p class="ai__prompt-master_error-screen-warning-message">
				{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_ERROR_WARNING_MESSAGE') }}
			</p>
			<div @click="emitBackBtnClick" class="ai__prompt-master_error-screen-back-btn">
				<BIcon :name="backBtnIconName" :size="16"  />
				<span class="ai__prompt-master_error-screen-back-btn-text">
					{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_ERROR_BACK_BTN') }}
				</span>
			</div>
		</div>
	`,
};

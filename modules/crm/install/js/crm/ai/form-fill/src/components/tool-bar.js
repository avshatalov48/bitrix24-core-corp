import '../css/tool-bar.css';
import { mapActions, mapGetters } from 'ui.vue3.vuex';
import { ConflictField } from '../store/types';
import { Loc } from 'main.core';

export const ToolBar = {
	name: 'ToolBar',
	computed: {
		...mapGetters(['conflictFields']),
		conflictCount(): number {
			return this.conflictFields.length;
		},
		resolvedCount(): number {
			return this.conflictFields.filter((f: ConflictField) => f.isAiValuesUsed).length;
		},
		isApplyAllDisabled(): boolean {
			return this.conflictCount === this.resolvedCount;
		},
		isRevertDisabled(): boolean {
			return this.resolvedCount === 0;
		},
		titleText(): string {
			return Loc.getMessage('CRM_AI_FORM_FILL_TOOLBAR_CONFLICT_COUNT_TITLE');
		},
		applyAllBtnText(): string {
			return Loc.getMessage('CRM_AI_FORM_FILL_TOOLBAR_BUTTON_APPLY_ALL');
		},
		revertText(): string {
			return Loc.getMessage('CRM_AI_FORM_FILL_TOOLBAR_BUTTON_ROLLBACK');
		},
	},
	methods: {
		...mapActions(['applyAllAiFields', 'revertAllAiFields']),
	},
	template: `
		<div class="bx-crm-ai-form-fill__toolbar">
			<div class="bx-crm-ai-form-fill__toolbar__conflict_count">
				{{ titleText }}<span class="bx-crm-ai-form-fill__toolbar__conflict_count__count">{{conflictCount}}</span>
			</div>
			<div
				class="bx-crm-ai-form-fill__toolbar__button"
				@click="applyAllAiFields"
			>{{ applyAllBtnText }}</div>
			<div
				class="bx-crm-ai-form-fill__toolbar__button"
				@click="revertAllAiFields"
			>{{ revertText }}</div>
		</div>
	`,
};

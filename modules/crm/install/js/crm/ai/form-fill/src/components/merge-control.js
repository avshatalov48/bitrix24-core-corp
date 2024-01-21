import '../css/merge-control.css';
import { Loc } from 'main.core';
import { watch, nextTick } from 'ui.vue3';
import { mapActions, mapGetters, mapMutations } from 'ui.vue3.vuex';

export const MergeControl = {
	name: 'MergeControl',
	props: {
		field: {
			type: Object,
			required: true,
		},
		tmp: Number,
	},
	data() {
		return {
			hasLargeContent: true,
			isExpanded: false,
			coveredByAnother: false,
		};
	},
	computed: {
		...mapGetters(['getexpandedConflictControls', 'eeControlPositions']),
		replaceBtnText(): string {
			return this.field.isAiValuesUsed
				? Loc.getMessage('CRM_AI_FORM_FILL_MERGER_REPLACE_BTN_BACK')
				: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_REPLACE_BTN_FORTH');
		},
		value(): string {
			return this.field.isAiValuesUsed ? this.field.originalValue : this.field.aiValue;
		},
	},
	methods: {
		...mapActions(['setEditorFieldValue', 'showEntityEditorControlOutline']),
		...mapMutations(['toggleExpandedConflictControls']),
		async toggleAiValue(field) {
			await this.setEditorFieldValue(field);
			await this.expand(false);
			this.hasLargeContent = this.checkHasLargeContent();
		},
		async expand(expand: boolean) {
			if (!this.hasLargeContent)
			{
				return;
			}

			this.isExpanded = expand;
			await nextTick();
			this.toggleExpandedConflictControls({
				fieldId: this.field.name,
				size: this.$refs.root.getBoundingClientRect().height,
				isExpanded: expand,
			});
		},
		onControlsExpandedModeChange() {
			let coveredByAnother = false;
			const selfPosY = this.eeControlPositions.get(this.field.name, 0);

			for (const [fieldName, size] of this.getexpandedConflictControls)
			{
				const expandedPosY = this.eeControlPositions.get(fieldName, 0);

				if (selfPosY > expandedPosY && selfPosY - expandedPosY < size)
				{
					coveredByAnother = true;
					break;
				}
			}
			this.coveredByAnother = coveredByAnother;
		},
		checkHasLargeContent(): boolean {
			return this.$refs.fieldValue.scrollWidth > this.$refs.fieldValue.clientWidth;
		},
		onMouseenter(e) {
			this.showEntityEditorControlOutline({ fieldName: this.field.name, isShow: true });
			this.expand(true);
		},
		onMouseleave(e) {
			this.showEntityEditorControlOutline({ fieldName: this.field.name, isShow: false });
			this.expand(false);
		},
	},
	mounted()
	{
		this.hasLargeContent = this.checkHasLargeContent();
		watch(this.getexpandedConflictControls, this.onControlsExpandedModeChange);
	},
	template: `
		<div 
			class="bx-crm-ai-form-fill-merge-control__container"
			:class="{'expanded': isExpanded, 'covered': coveredByAnother}"
			@mouseenter="onMouseenter"
			@mouseleave="onMouseleave"
			ref="root"
		>
			<div 
				class="bx-crm-ai-form-fill-merge-control-icon"
				@click="toggleAiValue(field)"
			>

			</div>
			<div class="bx-crm-ai-form-fill-merge-control-field">
				<div
					class="bx-crm-ai-form-fill-merge-control-field-title"
					:title="field.title"
				>{{ field.title }}</div>
				<div class="bx-crm-ai-form-fill-merge-control-field-value-container">
					<div
						ref="fieldValue"
						class="bx-crm-ai-form-fill-merge-control-field-value-container__value"
						:class="{'expanded': isExpanded, 'ai-value': !field.isAiValuesUsed}"
						:title="this.value"
					>{{ this.value }}</div>
					<div
						class="bx-crm-ai-form-fill-merge-control-field-value-container__control"
						:class="{'expanded': isExpanded}"
						:style="{display: hasLargeContent ? 'block': 'none'}"
					></div>
				</div>
			</div>
			<div
				class="bx-crm-ai-form-fill-merge-control-right-column"
				@click="toggleAiValue(field)"
			>
				<div 
					class="bx-crm-ai-form-fill-merge-control-button">
					{{ replaceBtnText }}
				</div>
			</div>
		</div>
	`,
};

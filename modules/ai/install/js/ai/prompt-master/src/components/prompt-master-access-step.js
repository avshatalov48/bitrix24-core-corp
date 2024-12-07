import { bind, Reflection, Tag } from 'main.core';
import { PromptMasterIconSelector } from './prompt-master-icon-selector';
import { PromptMasterUserSelector } from './prompt-master-user-selector';
import { PromptMasterCategoriesSelector } from './prompt-master-categories-selector';
import { clickableHint } from '../directives/prompt-master-hover-hint';
import { PromptMasterHint } from './prompt-master-hint';

import '../css/prompt-master-access-step.css';

export const PromptMasterAccessStep = {
	directives: {
		hoverHint: clickableHint,
	},
	components: {
		PromptMasterIconSelector,
		PromptMasterUserSelector,
		PromptMasterCategoriesSelector,
		PromptMasterHint,
	},
	props: {
		promptTitle: {
			type: String,
			required: false,
			default: '',
		},
		promptIcon: {
			type: String,
			required: false,
			default: '',
		},
		promptAuthorId: {
			type: String,
			required: false,
			default: '-1',
		},
		selectedItems: {
			type: Array,
			required: false,
			default: () => {
				return [];
			},
		},
		selectedCategories: {
			type: Array,
			required: false,
			default: () => {
				return [];
			},
		},
		isShown: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	computed: {
		accessPointsHintHtml(): string {
			const htmlMessage = this.$Bitrix.Loc.getMessage('PROMPT_MASTER_ACCESS_STEP_POINTS_HINT', {
				'<link>': '<a style="display: inline-block;" href="#">',
				'</link>': '</a>',
			});

			const elem = Tag.render`<div style="font-size: 14px;">${htmlMessage}</div>`;
			const link = elem.querySelector('a');

			bind(link, 'click', () => {
				const articleCode = '21979776';

				const Helper = Reflection.getClass('top.BX.Helper');

				if (Helper)
				{
					Helper.show(`redirect=detail&code=${articleCode}`);
				}
			});

			return elem;
		},
	},
	mounted()
	{
		this.$refs.promptTitleInput.focus();
	},
	methods: {
		selectIcon(iconCode: string): void {
			this.$emit('select-icon', iconCode);
		},
		selectItem(item: Object): void {
			this.$emit('select-item', item);
		},
		deselectItem(item: Object): void {
			this.$emit('deselect-item', item);
		},
		handleNameInput(e): void {
			this.$emit('input-name', e.target.value);
		},
		selectCategory(categoryId: string) {
			this.$emit('select-category', categoryId);
		},
		deselectCategory(categoryId: string) {
			this.$emit('deselect-category', categoryId);
		},
	},
	watch: {
		isShown(newValue: boolean, oldValue: boolean): void {
			if (newValue === true && oldValue === false)
			{
				requestAnimationFrame(() => {
					this.$refs.promptTitleInput.focus();
				});
			}
		},
	},
	template: `
		<div class="ai__prompt-master-access-step">
			<div class="ai__prompt-master-access-step_section">
				<div class="ai__prompt-master-access-step_section-title">
					{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_ACCESS_STEP_NAME_AND_ICON') }}
				</div>
				<div class="ai__prompt-master-access-step_section-content --row">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						<input
							ref="promptTitleInput"
							maxlength="70"
							type="text"
							:value="promptTitle"
							@input="handleNameInput"
							class="ui-ctl-element"
							:placeholder="$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_TITLE_PLACEHOLDER')"
						/>
					</div>
					<div class="ai__prompt-master-access-step_icon-selector">
						<PromptMasterIconSelector @select="selectIcon" :selected-icon="promptIcon"/>
					</div>
				</div>
			</div>
			<div class="ai__prompt-master-access-step_section">
				<div class="ai__prompt-master-access-step_section-title">
					{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_ACCESS_STEP_GENERAL_ACCESS') }}
				</div>
				<div
					class="ai__prompt-master-access-step_section-content"
				>
					<PromptMasterUserSelector
						:selected-items="selectedItems"
						:undeselected-items="[['user', this.promptAuthorId]]"
						@select-item="selectItem"
						@deselect-item="deselectItem"
					/>
				</div>
			</div>
			<div class="ai__prompt-master-access-step_section">
				<div class="ai__prompt-master-access-step_section-title">
					<span>{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_ACCESS_STEP_POINTS') }}</span>
					<PromptMasterHint :html="accessPointsHintHtml"></PromptMasterHint>
				</div>
				<div class="ai__prompt-master-access-step_section-content">
					<div
						class="ai__prompt-master-access-step_section-content"
					>
						<PromptMasterCategoriesSelector
							:selected-category-ids="selectedCategories"
							@select="selectCategory"
							@deselect="deselectCategory"
						/>
					</div>
				</div>
			</div>
		</div>
	`,
};

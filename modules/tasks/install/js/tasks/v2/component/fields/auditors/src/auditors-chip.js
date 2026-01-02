import { Item } from 'ui.entity-selector';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { Chip, ChipDesign } from 'tasks.v2.component.elements.chip';
import { participantMeta } from 'tasks.v2.component.elements.participant-list';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import { UserSelectorDialog } from 'tasks.v2.lib.user-selector-dialog';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { auditorsMeta } from './auditors-meta';
import { AuditorsMixin } from './auditors-mixin';

// @vue/component
export const AuditorsChip = {
	components: {
		Chip,
	},
	mixins: [AuditorsMixin],
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	setup(): Object
	{
		return {
			Outline,
			auditorsMeta,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		preselected(): [string, number][]
		{
			return this.task.auditorsIds.map((id: number) => ['user', id]);
		},
		design(): string
		{
			return this.isSelected ? ChipDesign.ShadowAccent : ChipDesign.ShadowNoAccent;
		},
		isSelected(): boolean
		{
			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, auditorsMeta.id);
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
	},
	unmounted(): void
	{
		this.selector?.destroy();
	},
	methods: {
		handleClick(): void
		{
			if (this.isSelected)
			{
				this.highlightField();

				return;
			}

			this.selector ??= new UserSelectorDialog({
				taskId: this.taskId,
				preselected: this.preselected,
				dialogOptions: participantMeta.dialogOptions(this.taskId, 'auditors'),
				events: {
					onHide: () => {
						const users = this.selector.getDialog().getSelectedItems().map((item: Item) => ({
							id: item.getId(),
							name: item.getTitle(),
							image: item.getAvatar(),
						}));

						if (!this.isSelected && users.length > 0)
						{
							this.highlightField();
						}

						this.update(users);
					},
				},
			});

			this.selector.selectItemsByIds(this.preselected);
			this.selector.show(this.$el);
		},
		highlightField(): void
		{
			void fieldHighlighter.setContainer(this.$root.$el).highlight(auditorsMeta.id);
		},
	},
	template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.OBSERVER"
			:text="loc('TASKS_V2_AUDITORS_TITLE_CHIP')"
			:data-task-id="taskId"
			:data-task-chip-id="auditorsMeta.id"
			:data-task-chip-value="task.auditorsIds.join(',')"
			@click="handleClick"
		/>
	`,
};

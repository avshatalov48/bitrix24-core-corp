import { Runtime } from 'main.core';

import { hint, type HintParams } from 'ui.vue3.directives.hint';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { tooltip } from 'tasks.v2.component.elements.hint';
import { EntitySelectorEntity, Model } from 'tasks.v2.const';
import { EntitySelectorDialog } from 'tasks.v2.lib.entity-selector-dialog';
import { Color } from 'tasks.v2.lib.color';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { EpicModel } from 'tasks.v2.model.epics';
import type { TaskModel } from 'tasks.v2.model.tasks';

import './epic.css';

// @vue/component
export const Epic = {
	components: {
		BIcon,
	},
	directives: { hint },
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
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		epic(): ?EpicModel
		{
			return this.$store.getters[`${Model.Epics}/getById`](this.task.epicId);
		},
		preselectedEpic(): [string, number][]
		{
			return this.epic ? [['epic-selector', this.epic.id]] : [];
		},
		epicColor(): string
		{
			if (!this.epic)
			{
				return '';
			}

			return new Color(this.epic.color).toRgb();
		},
		backgroundColor(): string
		{
			if (!this.epic)
			{
				return '';
			}

			return new Color(this.epic.color).setOpacity(0.3).limit(250).toRgb();
		},
		isDarkColor(): boolean
		{
			if (!this.epic)
			{
				return false;
			}

			return new Color(this.epic.color).isDark();
		},
		tooltip(): Function
		{
			return (): HintParams => tooltip({
				text: this.loc('TASKS_V2_GROUP_EPIC_HINT'),
				popupOptions: {
					offsetLeft: this.$el.offsetWidth / 2,
				},
			});
		},
	},
	methods: {
		handleClick(): void
		{
			this.showDialog();
		},
		showDialog(): void
		{
			this.handleEpicSelectedDebounced ??= Runtime.debounce(this.handleEpicSelected, 10, this);

			this.dialog ??= new EntitySelectorDialog({
				multiple: false,
				dropdownMode: true,
				enableSearch: true,
				compactView: true,
				hideOnDeselect: true,
				entities: [
					{
						id: EntitySelectorEntity.Epic,
						options: {
							groupId: this.task.groupId,
						},
						dynamicLoad: true,
						dynamicSearch: true,
					},
				],
				preselectedItems: this.preselectedEpic,
				events: {
					'Item:onSelect': this.handleEpicSelectedDebounced,
					'Item:onDeselect': this.handleEpicSelectedDebounced,
				},
			});

			this.dialog.selectItemsByIds(this.preselectedEpic);
			this.dialog.showTo(this.$el);
		},
		handleEpicSelected(): void
		{
			const item = this.dialog.getSelectedItems()[0];
			if (item)
			{
				void this.$store.dispatch(`${Model.Epics}/insert`, {
					id: item.getId(),
					title: item.getTitle(),
					color: item.getAvatarOption('bgColor'),
				});
			}

			void taskService.update(
				this.taskId,
				{
					epicId: item?.getId() ?? 0,
				},
			);
		},
	},
	template: `
		<div
			v-hint="tooltip"
			class="tasks-field-group-scrum-epic"
			:class="{ '--dark': isDarkColor, '--filled': epic }"
			:style="{
				'--epic-color': epicColor,
				'--epic-background': backgroundColor,
			}"
			@click="handleClick"
		>
			<span class="tasks-field-group-scrum-epic-title">
				{{ epic?.title || loc('TASKS_V2_GROUP_EPIC_EMPTY') }}
			</span>
			<BIcon :name="Outline.CHEVRON_DOWN_S"/>
		</div>
	`,
};

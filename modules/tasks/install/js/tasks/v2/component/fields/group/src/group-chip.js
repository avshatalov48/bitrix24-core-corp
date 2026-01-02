import { Runtime } from 'main.core';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Core } from 'tasks.v2.core';
import { Model } from 'tasks.v2.const';
import { Chip, ChipDesign, type ChipImage } from 'tasks.v2.component.elements.chip';
import { Hint } from 'tasks.v2.component.elements.hint';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import { analytics } from 'tasks.v2.lib.analytics';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { GroupModel } from 'tasks.v2.model.groups';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { groupMeta } from './group-meta';
import { groupDialog } from './group-dialog';
import { GroupPopup } from './group-popup/group-popup';

// @vue/component
export const GroupChip = {
	components: {
		Chip,
		Hint,
		GroupPopup,
	},
	inject: ['analytics', 'cardType'],
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		isAutonomous: {
			type: Boolean,
			default: false,
		},
	},
	setup(): Object
	{
		return {
			groupMeta,
		};
	},
	data(): Object
	{
		return {
			doShowHint: false,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		group(): ?GroupModel
		{
			return this.$store.getters[`${Model.Groups}/getById`](this.task.groupId);
		},
		design(): string
		{
			return {
				[!this.isAutonomous && !this.isSelected]: ChipDesign.ShadowNoAccent,
				[!this.isAutonomous && this.isSelected]: ChipDesign.ShadowAccent,
				[this.isAutonomous && !this.isSelected]: ChipDesign.OutlineNoAccent,
				[this.isAutonomous && this.isSelected]: ChipDesign.OutlineAccent,
			}.true;
		},
		isSelected(): boolean
		{
			if (this.isAutonomous)
			{
				return this.task.groupId > 0;
			}

			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, groupMeta.id);
		},
		isFilled(): boolean
		{
			return Boolean(this.isAutonomous && this.group);
		},
		isFlowFilled(): boolean
		{
			return this.task.flowId > 0;
		},
		text(): string
		{
			if (this.isFilled)
			{
				return this.group?.name ?? this.loc('TASKS_V2_GROUP_HIDDEN');
			}

			return this.loc('TASKS_V2_GROUP_TITLE_CHIP');
		},
		icon(): ?string
		{
			if (this.isFilled)
			{
				return null;
			}

			return Outline.FOLDER;
		},
		image(): ?ChipImage
		{
			if (!this.isFilled)
			{
				return null;
			}

			return {
				src: encodeURI(this.group?.image),
				alt: this.group?.name,
			};
		},
		canChange(): boolean
		{
			return (this.task.flowId ?? 0) <= 0;
		},
		hintText(): boolean
		{
			return this.loc('TASKS_V2_GROUP_CANT_CHANGE_FLOW');
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
		isLocked(): boolean
		{
			return !Core.getParams().limits.project;
		},
	},
	created(): void
	{
		groupDialog.setTaskId(this.taskId).init();
	},
	methods: {
		handleClick(): void
		{
			if (this.isLocked)
			{
				void Runtime.loadExtension('tasks.limit').then((exports) => {
					const { Limit } = exports;
					Limit.showInstance({
						featureId: Core.getParams().limits.projectFeatureId,
					});
				});

				return;
			}

			if (!this.isAutonomous && this.isSelected)
			{
				this.highlightField();

				return;
			}

			if (this.isFlowFilled)
			{
				this.doShowHint = true;

				return;
			}

			groupDialog.setTaskId(this.taskId).showTo(this.$refs.chip.$el);

			if (!this.isAutonomous)
			{
				groupDialog.onUpdateOnce(this.highlightField);
			}

			groupDialog.onHide(this.handleFieldClose);
			groupDialog.onUpdate(this.handleUpdate);
		},
		highlightField(): void
		{
			void fieldHighlighter.setContainer(this.$root.$el).highlight(groupMeta.id);
		},
		handleClear(): void
		{
			void taskService.update(
				this.taskId,
				{
					groupId: 0,
					stageId: 0,
				},
			);
		},
		handleFieldClose(): void
		{
			if (this.isAutonomous)
			{
				this.$refs.chip?.$el.focus();
			}
		},
		handleUpdate(): void
		{
			analytics.sendAddProject(this.analytics, {
				cardType: this.cardType,
				viewersCount: this.task.auditorsIds.length,
				coexecutorsCount: this.task.accomplicesIds.length,
			});
		},
	},
	template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="icon"
			:image="image"
			:text="text"
			:withClear="isFilled && !isFlowFilled"
			:lock="isLocked"
			:trimmable="isFilled"
			:data-task-id="taskId"
			:data-task-chip-id="groupMeta.id"
			:data-task-chip-value="task.groupId"
			ref="chip"
			@click="handleClick"
			@clear="handleClear"
		/>
		<Hint
			v-if="doShowHint"
			:bindElement="$refs.chip.$el"
			@close="doShowHint = false"
		>
			{{ hintText }}
		</Hint>
		<GroupPopup v-if="isAutonomous" :taskId="taskId" :getBindElement="() => $refs.chip.$el"/>
	`,
};

import { Tag } from 'main.core';
import { hint, type HintParams } from 'ui.vue3.directives.hint';
import { BMenu, type MenuOptions, type MenuItemOptions } from 'ui.vue3.components.menu';
import { BIcon } from 'ui.icon-set.api.vue';
import { CRM, Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.crm';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { Loader } from 'tasks.v2.component.elements.user-custom-tag-selector';
import { tooltip } from 'tasks.v2.component.elements.hint';
import { Color } from 'tasks.v2.lib.color';
import { groupService } from 'tasks.v2.provider.service.group-service';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { GroupModel } from 'tasks.v2.model.groups';
import type { StageModel } from 'tasks.v2.model.stages';
import type { TaskModel } from 'tasks.v2.model.tasks';

import './stage.css';

// @vue/component
export const Stage = {
	components: {
		BIcon,
		Loader,
		BMenu,
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
	data(): Object
	{
		return {
			isMenuShown: false,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		isEdit(): boolean
		{
			return Number.isInteger(this.taskId) && this.taskId > 0;
		},
		group(): GroupModel
		{
			return this.$store.getters[`${Model.Groups}/getById`](this.task.groupId);
		},
		groupId(): number
		{
			return this.task.groupId;
		},
		stage(): StageModel
		{
			return this.$store.getters[`${Model.Stages}/getById`](this.task.stageId) ?? null;
		},
		menuOptions(): Function
		{
			return (): MenuOptions => ({
				id: 'tasks-field-group-stage-menu',
				bindElement: this.$refs.stage,
				offsetTop: 8,
				items: this.menuItems,
				maxHeight: window.innerHeight / 2,
				targetContainer: document.body,
			});
		},
		menuItems(): MenuItemOptions[]
		{
			const stages = this.$store.getters[`${Model.Stages}/getByIds`](this.group.stagesIds ?? []);

			return stages?.map((stage: StageModel): MenuItemOptions => ({
				title: stage.title,
				svg: this.getStageSvg(new Color(stage.color).limit(250).toRgb()),
				isSelected: stage.id === this.stage.id,
				onClick: () => this.setStage(stage.id),
			}));
		},
		stageColor(): string
		{
			return `#${this.stage.color}`;
		},
		backgroundColor(): string
		{
			return new Color(this.stage.color).setOpacity(0.1).limit(250).toRgb();
		},
		isDarkColor(): boolean
		{
			return new Color(this.stage.color).isDark();
		},
		tooltip(): Function
		{
			return (): HintParams => tooltip({
				text: this.loc('TASKS_V2_GROUP_STAGE_HINT'),
				popupOptions: {
					offsetLeft: this.$refs.stage.offsetWidth / 2,
				},
			});
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
	},
	watch: {
		groupId(): void
		{
			void this.loadStagesForCreation();
		},
	},
	created(): void
	{
		void this.loadStagesForCreation();
	},
	methods: {
		getStageSvg(color: string): SVGElement
		{
			return Tag.render`
				<div class="ui-icon-set --${CRM.STAGE}" style="--ui-icon-set__icon-color: ${color}"></div>
			`;
		},
		async handleClick(): Promise<void>
		{
			if (this.readonly)
			{
				return;
			}

			if (!this.group.stagesIds)
			{
				await groupService.getStages(this.groupId);
			}

			this.isMenuShown = true;
		},
		setStage(stageId: number): void
		{
			void taskService.update(
				this.taskId,
				{ stageId },
			);
		},
		async loadStagesForCreation(): Promise<void>
		{
			if (this.isEdit || this.group.stagesIds)
			{
				return;
			}

			await groupService.getStages(this.groupId);

			if (!this.task.stageId)
			{
				this.setStage(this.group.stagesIds[0]);
			}
		},
	},
	template: `
		<div
			v-if="stage"
			v-hint="tooltip"
			class="tasks-field-group-stage"
			:class="{ '--dark': isDarkColor, '--readonly': readonly }"
			:style="{
				'--stage-color': stageColor,
				'--stage-background': backgroundColor,
			}"
			:data-task-id="taskId"
			:data-task-field-id="'stageId'"
			:data-task-field-value="task.stageId"
			:data-task-stage-title="stage?.title"
			ref="stage"
			@click="handleClick"
		>
			<div class="tasks-field-group-stage-text-container">
				<div class="tasks-field-group-stage-text">{{ stage.title }}</div>
			</div>
			<div class="tasks-field-group-stage-arrow"></div>
			<BIcon v-if="!readonly" :name="Outline.CHEVRON_DOWN_S"/>
		</div>
		<div v-else class="tasks-field-group-stage-loader">
			<Loader/>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions()" @close="isMenuShown = false"/>
	`,
};

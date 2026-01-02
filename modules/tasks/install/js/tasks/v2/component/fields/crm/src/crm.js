import { TextSm } from 'ui.system.typography.vue';
import { BLine } from 'ui.system.skeleton.vue';
import { Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { AddBackground } from 'tasks.v2.component.elements.add-background';
import { FieldAdd } from 'tasks.v2.component.elements.field-add';
import { crmService } from 'tasks.v2.provider.service.crm-service';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { CrmItemModel } from 'tasks.v2.model.crm-items';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { CrmItem } from './crm-item';
import { crmMeta } from './crm-meta';
import { crmDialog } from './crm-dialog';
import './crm.css';

const maxCount = 7;

// @vue/component
export const Crm = {
	components: {
		AddBackground,
		TextSm,
		BLine,
		FieldAdd,
		CrmItem,
	},
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
			crmMeta,
		};
	},
	data(): Object
	{
		return {
			isDialogShown: false,
			isExpanded: false,
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
		crmItems(): CrmItemModel[]
		{
			return this.$store.getters[`${Model.CrmItems}/getByIds`](this.task.crmItemIds);
		},
		visibleItems(): CrmItemModel[]
		{
			return this.crmItems.slice(0, maxCount);
		},
		collapsedItems(): CrmItemModel[]
		{
			return this.crmItems.slice(maxCount);
		},
		isLoading(): boolean
		{
			return this.task.crmItemIds?.length && !this.crmItems?.length;
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
		expandButtonText(): string
		{
			if (this.isExpanded)
			{
				return this.loc('TASKS_V2_CRM_COLLAPSE');
			}

			return this.loc('TASKS_V2_CRM_AND_COUNT', {
				'#COUNT#': this.collapsedItems.length,
			});
		},
	},
	mounted(): void
	{
		void crmService.list(this.taskId, this.task.crmItemIds);
	},
	methods: {
		handleClick(): void
		{
			if (this.readonly)
			{
				return;
			}

			this.showDialog();
		},
		showDialog(): void
		{
			crmDialog.setTaskId(this.taskId).onCloseOnce(this.handleClose).showTo(this.$refs.anchor);

			this.isDialogShown = true;
		},
		handleClose(): void
		{
			this.isDialogShown = false;
		},
		handleClear(crmItemId: string): void
		{
			const crmItemIds = this.task.crmItemIds.filter((id) => id !== crmItemId);

			void taskService.update(
				this.taskId,
				{ crmItemIds },
			);
		},
	},
	template: `
		<AddBackground v-if="!readonly" :isActive="isDialogShown" @click="handleClick"/>
		<div
			class="tasks-field-crm"
			:data-task-id="taskId"
			:data-task-field-id="crmMeta.id"
			:data-task-crm-item-ids="task.crmItemIds?.join(',')"
		>
			<FieldAdd v-if="!task.crmItemIds?.length" :icon="Outline.CRM"/>
			<div v-if="isLoading" class="tasks-field-crm-skeleton">
				<template v-for="crmItemId in task.crmItemIds" :key="crmItemId">
					<BLine :height="20"/>
				</template>
			</div>
			<template v-for="item in visibleItems" :key="item.id">
				<CrmItem
					:item="item"
					:isEdit="isEdit"
					:readonly="readonly"
					@edit="showDialog"
					@clear="handleClear(item.id)"
				/>
			</template>
			<template v-if="isExpanded" v-for="item in collapsedItems" :key="item.id">
				<CrmItem
					:item="item"
					:isEdit="isEdit"
					:readonly="readonly"
					@edit="showDialog"
					@clear="handleClear(item.id)"
				/>
			</template>
			<TextSm
				v-if="collapsedItems.length > 0"
				class="tasks-field-crm-expand"
				@click.capture.stop="isExpanded = !isExpanded"
			>
				{{ expandButtonText }}
			</TextSm>
		</div>
		<div class="tasks-field-crm-anchor" ref="anchor"></div>
	`,
};

import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { AddBackground } from 'tasks.v2.component.elements.add-background';
import { FieldAdd } from 'tasks.v2.component.elements.field-add';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { tagsMeta } from './tags-meta';
import { tagsDialog } from './tags-dialog';
import './tags.css';

// @vue/component
export const Tags = {
	components: {
		BIcon,
		AddBackground,
		FieldAdd,
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
			tagsMeta,
		};
	},
	data(): Object
	{
		return {
			isDialogShown: false,
			tagsIndexes: {},
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		tags(): string[]
		{
			return [...this.task.tags].sort((a, b) => this.tagsIndexes[a] - this.tagsIndexes[b]);
		},
		isFilled(): boolean
		{
			return this.tags.length > 0;
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
	},
	watch: {
		tags(): void
		{
			tagsDialog.setTaskId(this.taskId).updateItems();
		},
	},
	created(): void
	{
		this.rememberTagsIndexes(this.tags);
	},
	methods: {
		handleClick(): void
		{
			if (this.readonly)
			{
				return;
			}

			tagsDialog.setTaskId(this.taskId).onCloseOnce(this.handleClose).showTo(this.$refs.anchor);
			tagsDialog.onUpdateOnce(this.rememberTagsIndexes);

			this.isDialogShown = true;
		},
		handleClose(): void
		{
			this.isDialogShown = false;
		},
		handleCrossClick(tag: string): void
		{
			const tags = this.tags.filter((it) => it !== tag);
			void taskService.update(this.taskId, { tags });
		},
		rememberTagsIndexes(tags: string[]): void
		{
			this.tagsIndexes = tags.reduce((acc, tag, index) => {
				acc[tag] = index;

				return acc;
			}, {});
		},
	},
	template: `
		<div
			class="tasks-field-tags"
			:class="{ '--empty': !isFilled }"
			:data-task-id="taskId"
			:data-task-field-id="tagsMeta.id"
			:data-task-field-value="task.tags.join(',')"
			@click="handleClick"
		>
			<AddBackground v-if="!readonly && isFilled" :isActive="isDialogShown"/>
			<template v-for="tag in tags" :key="tag">
				<div class="tasks-field-tag">
					<span>{{ tag }}</span>
					<div v-if="!readonly" class="tasks-field-tag-cross" @click.capture.stop="handleCrossClick(tag)">
						<BIcon :name="Outline.CROSS_L" :hoverable="true"/>
					</div>
				</div>
			</template>
			<FieldAdd v-if="!isFilled" :icon="Outline.TAG"/>
			<div class="tasks-field-tags-anchor" ref="anchor"></div>
		</div>
	`,
};

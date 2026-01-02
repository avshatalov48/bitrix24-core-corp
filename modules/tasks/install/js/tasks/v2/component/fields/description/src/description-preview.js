import { Dom, Type } from 'main.core';
import { HtmlFormatterComponent } from 'ui.bbcode.formatter.html-formatter';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import type { VueUploaderAdapter } from 'ui.uploader.vue';
import 'ui.icon-set.outline';

import type { UserFieldWidgetOptions } from 'disk.uploader.user-field-widget';
import { UserFieldWidgetComponent } from 'disk.uploader.user-field-widget';

import { Model } from 'tasks.v2.const';
import { fileService } from 'tasks.v2.provider.service.file-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import './description.css';

// @vue/component
export const DescriptionPreview = {
	name: 'TaskDescriptionPreview',
	components: {
		UserFieldWidgetComponent,
		HtmlFormatterComponent,
		BIcon,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		files: {
			type: Array,
			required: true,
		},
		filesCount: {
			type: Number,
			required: true,
		},
		isMiniFormShown: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['previewButtonClick'],
	setup(props): { uploaderAdapter: VueUploaderAdapter }
	{
		return {
			BIcon,
			Outline,
			uploaderAdapter: fileService.get(props.taskId).getAdapter(),
		};
	},
	data(): Object
	{
		return {
			isOverflowing: false,
			opened: false,
			isMouseDown: false,
			selectionMade: false,
			showRightShadow: null,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		taskDescription(): string
		{
			return this.task.description ?? '';
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
		isEdit(): boolean
		{
			return Type.isNumber(this.taskId) && this.taskId > 0;
		},
		widgetOptions(): UserFieldWidgetOptions
		{
			return {
				isEmbedded: true,
				withControlPanel: false,
				canCreateDocuments: false,
				tileWidgetOptions: {
					compact: true,
					hideDropArea: true,
					readonly: this.readonly,
					enableDropzone: false,
					autoCollapse: false,
					removeFromServer: !this.isEdit,
				},
			};
		},
		hidden(): boolean
		{
			if (this.opened)
			{
				return false;
			}

			return this.filesCount || this.isOverflowing;
		},
		iconSize(): number
		{
			return 20;
		},
	},
	watch: {
		async taskDescription(): void
		{
			await this.$nextTick();

			this.updateIsOverflowing();
		},
	},
	async mounted(): Promise<void>
	{
		setTimeout(this.updateIsOverflowing, 400);

		if (this.isMiniFormShown)
		{
			this.openPreview();
		}
	},
	methods: {
		updateIsOverflowing(): void
		{
			if (this.opened || !this.$refs.htmlFormatter)
			{
				return;
			}

			this.showRightShadow = this.$refs.htmlFormatter.$el.offsetHeight < 50;
			this.isOverflowing = this.$refs.preview.offsetHeight - 20 < this.$refs.htmlFormatter.$el.offsetHeight;
		},
		onPreviewClick(): void
		{
			if (this.readonly && this.hidden)
			{
				this.openPreview();
			}

			if (!this.readonly)
			{
				this.$emit('previewButtonClick', { doOpenInEditMode: false });
			}
		},
		openPreview(): void
		{
			if (this.$refs.htmlFormatter)
			{
				Dom.style(this.$refs.preview, 'maxHeight', `${this.$refs.htmlFormatter.$el.offsetHeight + 32}px`);
			}

			this.opened = true;
		},
		onHideClick(): void
		{
			Dom.style(this.$refs.preview, 'maxHeight', '120px');

			this.opened = false;
		},
		onMouseDown(event): void
		{
			if (event.button === 0)
			{
				this.isMouseDown = true;
				this.selectionMade = false;
			}
		},
		onMouseMove(): void
		{
			if (this.selectionMade)
			{
				return;
			}

			if (this.isMouseDown)
			{
				const selection = window.getSelection();
				if (selection.toString().length > 0)
				{
					this.selectionMade = true;
				}
			}
		},
		onMouseUp(): void
		{
			this.isMouseDown = false;
			if (!this.selectionMade)
			{
				this.onPreviewClick();
			}
		},
	},
	template: `
		<div class="tasks-full-card-field-container">
			<div
				v-if="taskDescription.length > 0"
				class="tasks-card-description-preview"
				ref="preview"
			>
				<HtmlFormatterComponent
					:bbcode="taskDescription"
					:options="{ fileMode: 'disk' }"
					:formatData="{ files }"
					ref="htmlFormatter"
					@mousedown="onMouseDown"
					@mousemove="onMouseMove"
					@mouseup="onMouseUp"
				/>
				<template v-if="hidden">
					<div class="tasks-card-description-shadow">
						<div v-if="showRightShadow === true" class="tasks-card-description-shadow-white-right"/>
						<div v-else-if="showRightShadow === false" class="tasks-card-description-shadow-white-bottom"/>
					</div>
				</template>
				<div
					v-if="hidden"
					class="tasks-card-description-preview-button"
					:style="{ 'bottom': showRightShadow ? '16px' : '12px' }"
					@click="onPreviewClick"
				>
					<span class="tasks-card-description-preview-button-files" v-if="filesCount">
						<BIcon
							:name="Outline.ATTACH"
							:size="iconSize"
							class="tasks-card-description-field-icon-link"
						/>
						<span class="tasks-card-description-preview-button-text">
							{{ filesCount }}
						</span>
					</span>
					<span v-if="isOverflowing" class="tasks-card-description-preview-button-text">
						{{ loc('TASKS_V2_DESCRIPTION_PREVIEW_BUTTON_MORE') }}
					</span>
				</div>
			</div>
			<div
				v-if="opened && filesCount"
				class="tasks-card-description-editor-files --read-only"
				:class="{ '--with-description': taskDescription.length > 0 }"
				ref="filesWrapper"
			>
				<UserFieldWidgetComponent
					:uploaderAdapter="uploaderAdapter"
					:widgetOptions="widgetOptions"
				/>
			</div>
			<div
				v-if="opened && !isMiniFormShown"
				class="tasks-card-description-preview-button --hide"
				@click="onHideClick"
			>
				<div class="tasks-card-description-preview-button-more">
					<span class="tasks-card-description-preview-button-text">
						{{ loc('TASKS_V2_DESCRIPTION_BUTTON_COLLAPSE') }}
					</span>
				</div>
			</div>
		</div>
	`,
};

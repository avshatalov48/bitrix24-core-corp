import { Dom } from 'main.core';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import { BLine } from 'ui.system.skeleton.vue';
import type { VueUploaderAdapter } from 'ui.uploader.vue';

import { UserFieldWidgetComponent, type UserFieldWidgetOptions } from 'disk.uploader.user-field-widget';

import { Model } from 'tasks.v2.const';
import { GrowingTextArea } from 'tasks.v2.component.elements.growing-text-area';
import { UserAvatarList } from 'tasks.v2.component.elements.user-avatar-list';
import { fileService, EntityTypes } from 'tasks.v2.provider.service.file-service';

import { CheckListManager } from '../../../lib/check-list-manager';
import { CheckListItemMixin } from './check-list-item-mixin';

// @vue/component
export const CheckListChildItem = {
	name: 'CheckListChildItem',
	components: {
		BIcon,
		BLine,
		GrowingTextArea,
		UserAvatarList,
		UserFieldWidgetComponent,
	},
	mixins: [
		CheckListItemMixin,
	],
	inject: ['setItemsRef'],
	props: {
		itemOffset: {
			type: String,
			default: '0',
		},
	},
	emits: [
		'toggleGroupModeSelected',
	],
	setup(props: Object): { uploaderAdapter: VueUploaderAdapter }
	{
		const fileServiceInstance = fileService.get(
			props.id,
			EntityTypes.CheckListItem,
			{ parentEntityId: props.taskId },
		);

		return {
			Outline,
			fileService: fileServiceInstance,
			uploaderAdapter: fileServiceInstance.getAdapter(),
		};
	},
	data(): Object
	{
		return {
			uploadingFiles: this.fileService.getFiles(),
			filesLoading: false,
		};
	},
	computed: {
		widgetOptions(): UserFieldWidgetOptions
		{
			return {
				isEmbedded: true,
				withControlPanel: false,
				canCreateDocuments: false,
				tileWidgetOptions: {
					compact: true,
					hideDropArea: true,
					enableDropzone: false,
					readonly: this.isPreview,
					autoCollapse: false,
					removeFromServer: !this.isEdit,
				},
			};
		},
		hasAttachments(): boolean
		{
			return this.hasUsers || this.hasFilesAttach;
		},
		hasFilesAttach(): boolean
		{
			return (
				this.hasFiles
				|| this.fileService.isUploading()
				|| this.fileService.hasUploadingError()
			);
		},
		hasFiles(): boolean
		{
			return this.filesNumber > 0;
		},
		filesNumber(): boolean
		{
			if (!this.files)
			{
				return 0;
			}

			return this.files.length;
		},
		hasTrashcanIcon(): boolean
		{
			return (
				this.isHovered
				&& !this.item.panelIsShown
				&& !this.groupMode
				&& !this.isPreview
			);
		},
		readOnly(): boolean
		{
			return this.isPreview;
		},
	},
	created(): void
	{
		if (this.hasFilesAttach)
		{
			void this.loadFiles();
		}

		this.checkListManager = new CheckListManager({
			computed: {
				checkLists: () => this.checkLists,
			},
		});
	},
	mounted(): void
	{
		if (this.setItemsRef)
		{
			this.setItemsRef(this.id, this);
		}
	},
	beforeUnmount(): void
	{
		if (this.setItemsRef)
		{
			this.setItemsRef(this.id, null);
		}
	},
	methods: {
		toggleGroupModeSelected(): void
		{
			this.$store.dispatch(`${Model.CheckList}/update`, {
				id: this.id,
				fields: {
					groupMode: {
						active: true,
						selected: !this.groupModeSelected,
					},
				},
			});

			this.$emit('toggleGroupModeSelected', this.id);
		},
		async loadFiles(): Promise<void>
		{
			this.filesLoading = true;

			const ids = this.files?.map((file) => file?.id ?? file);

			await this.fileService.list(ids ?? []);

			this.filesLoading = false;
		},
		handleEnter(): void
		{
			if (!this.item)
			{
				return;
			}

			this.addItem(this.item.sortIndex + 1);
		},
		handleClick(event: PointerEvent): void
		{
			const filesWidget = this.$refs['files-widget'];

			if (this.isClickInsideFilesWidget(filesWidget?.$el, event.target))
			{
				return;
			}

			if (this.groupMode)
			{
				this.toggleGroupModeSelected();
			}

			if (this.isPreview)
			{
				this.complete(!this.completed);
			}
		},
		isClickInsideFilesWidget(filesNode: HTMLElement, target: HTMLElement): boolean
		{
			if (!filesNode || !target)
			{
				return false;
			}

			const excludedClasses = ['ui-tile-uploader-items'];

			const isInsideWidget = filesNode.contains(target);
			if (!isInsideWidget)
			{
				return false;
			}

			const hasExcludedClass = excludedClasses.some((className: string) => Dom.hasClass(target, className));

			return !hasExcludedClass;
		},
	},
	template: `
		<div
			ref="item"
			class="check-list-widget-child-item"
			:class="{
				'--complete': completed,
				'--group-mode': groupMode,
				'--group-mode-selected': groupModeSelected,
				'--preview': isPreview,
				'--toggleable': canToggle,
			}"
			:data-id="id"
			:style="{ marginLeft: itemOffset }"
			@mouseover="isHovered = true"
			@mouseleave="isHovered = false"
			@click="handleClick"
		>
			<div class="check-list-widget-child-item-base">
				<label
					class="check-list-widget-child-item-checkbox"
					:class="{'--important': !item.isImportant}"
				>
					<input
						ref="checkbox"
						type="checkbox"
						:checked="completed"
						:disabled="!canToggle || groupMode"
						@click.stop="complete(!completed)"
					>
				</label>
				<div
					v-if="item.isImportant"
					class="check-list-widget-child-item-important"
				>
					<BIcon :name="Outline.FIRE_SOLID" :hoverable="false"/>
				</div>
				<GrowingTextArea
					ref="growingTextArea"
					class="check-list-widget-child-item-title"
					:data-check-list-id="'check-list-child-item-title-' + item.id"
					:modelValue="item.title"
					:placeholder="loc('TASKS_V2_CHECK_LIST_ITEM_PLACEHOLDER')"
					:readonly="groupMode || isPreview"
					:fontColor="textColor"
					:fontSize="15"
					:lineHeight="20"
					@update:modelValue="updateTitle"
					@input="updateTitle"
					@focus="handleFocus"
					@blur="handleBlur"
					@emptyBlur="handleEmptyBlur"
					@emptyFocus="focusToItem"
					@enterBlur="handleEnter"
				/>
				<div
					v-if="hasTrashcanIcon"
					class="check-list-widget-child-item-action"
					@click="removeItem"
				>
					<BIcon :name="Outline.TRASHCAN"/>
				</div>
				<div v-else-if="groupMode" class="check-list-widget-child-item-action-checkbox">
					<input
						ref="checkbox"
						type="checkbox"
						:checked="groupModeSelected"
					>
				</div>
				<div v-else class="check-list-widget-child-item-action-stub"></div>
			</div>
			<template v-if="hasAttachments">
				<div class="check-list-widget-item-attach">
					<div v-if="hasUsers" class="check-list-widget-item-attach-users">
						<div v-if="hasAccomplices" class="check-list-widget-item-attach-users-list">
							<BIcon :name="Outline.GROUP"/>
							<UserAvatarList :users="accomplices"/>
						</div>
						<div v-if="hasAuditors" class="check-list-widget-item-attach-users-list">
							<BIcon :name="Outline.OBSERVER"/>
							<UserAvatarList :users="auditors"/>
						</div>
					</div>
					<div v-if="hasFilesAttach" class="check-list-widget-item-attach-files">
						<div class="check-list-widget-item-attach-files-list">
							<template v-if="filesLoading">
								<div class="check-list-widget-item-attach-files-list-skeleton">
									<BLine
										v-for="index in filesNumber"
										:key="index"
										:width="114"
										:height="90"
									/>
								</div>
							</template>
							<template v-else>
								<UserFieldWidgetComponent
									ref="files-widget"
									:uploaderAdapter="uploaderAdapter"
									:widgetOptions="widgetOptions"
								/>
							</template>
						</div>
					</div>
				</div>
			</template>
		</div>
	`,
};

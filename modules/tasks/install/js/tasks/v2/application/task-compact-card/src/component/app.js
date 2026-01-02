import { Event, Text, Type } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { Popup } from 'main.popup';

import { mapActions, mapGetters } from 'ui.vue3.vuex';
import { Notifier } from 'ui.notification-manager';
import { DragOverMixin } from 'ui.uploader.tile-widget';
import { Button as UiButton, AirButtonStyle, ButtonSize } from 'ui.vue3.components.button';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { Core } from 'tasks.v2.core';
import { EventName, Model, CardType, GroupType } from 'tasks.v2.const';
import { AddTaskButton } from 'tasks.v2.component.add-task-button';
import { Title as FieldTitle } from 'tasks.v2.component.fields.title';
import { Importance } from 'tasks.v2.component.fields.importance';
import { DescriptionInline } from 'tasks.v2.component.fields.description';
import { FieldList } from 'tasks.v2.component.elements.field-list';
import { DropZone } from 'tasks.v2.component.drop-zone';
import { Responsible, responsibleMeta } from 'tasks.v2.component.fields.responsible';
import { Deadline, deadlineMeta } from 'tasks.v2.component.fields.deadline';
import { CheckListChip, CheckList } from 'tasks.v2.component.fields.check-list';
import { FilesChip } from 'tasks.v2.component.fields.files';
import { GroupChip } from 'tasks.v2.component.fields.group';
import { analytics } from 'tasks.v2.lib.analytics';
import { fileService } from 'tasks.v2.provider.service.file-service';
import { taskService } from 'tasks.v2.provider.service.task-service';
import { checkListService } from 'tasks.v2.provider.service.check-list-service';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { GroupModel } from 'tasks.v2.model.groups';
import type { CheckListModel } from 'tasks.v2.model.check-list';

import { FullCardButton } from './full-card-button/full-card-button';
import './app.css';

// @vue/component
export const App = {
	name: 'TaskCompactCard',
	components: {
		BIcon,
		FieldTitle,
		DescriptionInline,
		Importance,
		FieldList,
		UiButton,
		AddTaskButton,
		CheckList,
		FullCardButton,
		DropZone,
	},
	mixins: [DragOverMixin],
	provide(): Object
	{
		return {
			analytics: this.analytics,
			cardType: CardType.Compact,
		};
	},
	props: {
		taskId: {
			type: [Number, String],
			default: () => Text.getRandom(),
		},
		groupId: {
			type: [Number],
			default: null,
		},
		parentId: {
			type: [Number],
			default: null,
		},
		relatedToTaskId: {
			type: [Number],
			default: null,
		},
		deadlineTs: {
			type: [Number, null],
			default: null,
		},
		title: {
			type: [String, null],
			default: '',
		},
		description: {
			type: [String, null],
			default: '',
		},
		auditorsIds: {
			type: Array,
			default: () => [],
		},
		analytics: {
			type: Object,
			default: () => ({}),
		},
		source: {
			type: Object,
			default: () => ({}),
		},
	},
	setup(): Object
	{
		return {
			ButtonSize,
			AirButtonStyle,
			Outline,
		};
	},
	data(): Object
	{
		return {
			openingFullCard: false,
			isCheckListPopupShown: false,
			chipsEventHandlers: {
				showCheckList: this.showCheckListPopup,
			},
			creationError: false,
			popupCount: 0,
		};
	},
	computed: {
		...mapGetters({
			titleFieldOffsetHeight: `${Model.Interface}/titleFieldOffsetHeight`,
			currentUserId: `${Model.Interface}/currentUserId`,
			defaultDeadline: `${Model.Interface}/defaultDeadline`,
		}),
		hasDefaultDeadline(): boolean
		{
			return this.defaultDeadline.defaultDeadlineDate !== '';
		},
		defaultDeadlineTs(): number
		{
			return new Date(this.defaultDeadline.defaultDeadlineDate).getTime();
		},
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		group(): GroupModel
		{
			return this.$store.getters[`${Model.Groups}/getById`](this.task.groupId);
		},
		checkLists(): CheckListModel[]
		{
			return this.task?.checklist
				? this.$store.getters[`${Model.CheckList}/getByIds`](this.task.checklist)
				: [];
		},
		isEdit(): boolean
		{
			return Type.isNumber(this.taskId) && this.taskId > 0;
		},
		primaryFields(): Object[]
		{
			return [
				{
					title: responsibleMeta.title,
					component: Responsible,
					props: {
						taskId: this.taskId,
						context: this.$options.name,
					},
				},
				{
					title: deadlineMeta.title,
					component: Deadline,
					props: {
						taskId: this.taskId,
					},
				},
			];
		},
		chips(): Object[]
		{
			return [
				FilesChip,
				CheckListChip,
				...(Core.getParams().features.isProjectsEnabled ? [GroupChip] : []),
			];
		},
	},
	created(): void
	{
		if (!this.isEdit)
		{
			this.destroy();

			const payload = {
				id: this.taskId,
				creatorId: this.currentUserId,
				responsibleId: this.currentUserId,
				...(this.groupId ? { groupId: this.groupId } : {}),
				...(this.title ? { title: this.title } : ''),
				...(this.description ? { description: this.description } : ''),
				...(this.auditorsIds ? { auditorsIds: this.auditorsIds } : []),
				...(this.parentId ? { parentId: this.parentId } : {}),
				...(this.relatedToTaskId ? { relatedToTaskId: this.relatedToTaskId } : {}),
				...(this.source ? { source: this.source } : {}),
			};

			if (this.deadlineTs !== null)
			{
				payload.deadlineTs = this.deadlineTs;
			}
			else if (this.hasDefaultDeadline)
			{
				payload.deadlineTs = this.defaultDeadlineTs;
			}

			if (this.auditorsIds !== null && this.auditorsIds.length > 0)
			{
				this.auditorsIds.forEach((auditorId) => {
					this.insertUser({
						id: auditorId,
					});
				});
			}

			this.insert(payload);
		}

		this.fileService = fileService.get(this.taskId);

		analytics.sendOpenCard(this.analytics, {
			collabId: this.group?.type === GroupType.Collab ? this.group.id : null,
		});
	},
	mounted(): void
	{
		this.resizeObserver = new ResizeObserver(async (entries) => {
			const title = entries.find(({ target }) => target === this.$refs.title);
			if (title)
			{
				await this.updateTitleFieldOffsetHeight(title.contentRect.height);
			}
		});

		this.resizeObserver.observe(this.$refs.title);
		this.subscribeEvents();
	},
	beforeUnmount(): void
	{
		if (this.resizeObserver)
		{
			this.resizeObserver.disconnect();
		}
		this.unsubscribeEvents();
	},
	unmounted(): void
	{
		if (!this.isEdit && this.openingFullCard === false)
		{
			this.destroy();
		}
	},
	methods: {
		...mapActions(Model.Tasks, [
			'insert',
			'delete',
		]),
		...mapActions(Model.Interface, [
			'updateTitleFieldOffsetHeight',
		]),
		...mapActions(Model.Users, {
			insertUser: 'insert',
		}),
		close(): void
		{
			Event.EventEmitter.emit(`${EventName.CloseCard}:${this.taskId}`);
		},
		async addTask(): void
		{
			const [id, error] = await taskService.add(this.task);

			if (!id)
			{
				this.creationError = true;

				Notifier.notifyViaBrowserProvider({
					id: 'task-notify-add-error',
					text: error.message,
				});

				this.sendAddTaskAnalytics(false);

				return;
			}

			this.sendAddTaskAnalytics(true);

			analytics.sendDescription(this.analytics, {
				hasDescription: Type.isStringFilled(this.task?.description),
				hasScroll: this.$refs?.description?.hasScroll(),
			});

			if (this.checkLists.length > 0)
			{
				await checkListService.save(id, this.checkLists);
			}

			fileService.replace(this.taskId, id);

			const baseEvent = new BaseEvent({
				data: id,
				compatData: [
					'ADD',
					{
						task: this.task,
						options: {},
					},
				],
			});

			Event.EventEmitter.emit(EventName.NotifyGrid, baseEvent);

			const features = Core.getParams().features;
			if (features.isMiniformEnabled && !features.isV2Enabled)
			{
				this.close();

				return;
			}

			this.close();
		},
		sendAddTaskAnalytics(isSuccess: boolean): void
		{
			const collabId = this.group?.type === GroupType.Collab ? this.group.id : null;
			if (this.checkLists.length > 0)
			{
				const checkLists = this.checkLists.filter(({ parentId }) => parentId === 0);
				const checkListsItems = this.checkLists.filter(({ parentId }) => parentId !== 0);
				analytics.sendAddTaskWithCheckList(this.analytics, {
					isSuccess,
					collabId,
					viewersCount: this.task.auditorsIds.length,
					checklistCount: checkLists.length,
					checklistItemsCount: checkListsItems.length,
				});
			}
			else
			{
				analytics.sendAddTask(this.analytics, {
					isSuccess,
					collabId,
					viewersCount: this.task.auditorsIds.length,
					coexecutorsCount: this.task.accomplicesIds.length,
				});
			}
		},
		handleShowingPopup(event: BaseEvent): void
		{
			Event.EventEmitter.emit(`${EventName.ShowOverlay}:${this.taskId}`);
			Event.EventEmitter.emit(`${EventName.AdjustPosition}:${this.taskId}`);
			this.externalPopup = event.popupInstance;
			this.adjustCardPopup(true);
		},
		handleHidingPopup(): void
		{
			Event.EventEmitter.emit(`${EventName.HideOverlay}:${this.taskId}`);
			Event.EventEmitter.emit(`${EventName.AdjustPosition}:${this.taskId}`);
			this.externalPopup = null;
		},
		handleResizingPopup(): void
		{
			this.adjustCardPopup();
		},
		adjustCardPopup(animate: boolean = false): void
		{
			if (!this.externalPopup)
			{
				Event.EventEmitter.emit(`${EventName.AdjustPosition}:${this.taskId}`);

				return;
			}

			Event.EventEmitter.emit(`${EventName.AdjustPosition}:${this.taskId}`, {
				titleFieldHeight: this.titleFieldOffsetHeight,
				innerPopup: this.externalPopup,
				animate,
			});
		},
		showCheckListPopup(): void
		{
			this.isCheckListPopupShown = true;
		},
		closeCheckListPopup(): void
		{
			this.isCheckListPopupShown = false;
		},
		getEventListeners(chip, handlers): Object
		{
			const listeners = {};

			if (chip.emits)
			{
				Object.keys(handlers).forEach((event: string) => {
					if (chip.emits.includes(event))
					{
						listeners[event] = handlers[event];
					}
				});
			}

			return listeners;
		},
		subscribeEvents(): void
		{
			EventEmitter.subscribe('BX.Main.Popup:onShow', this.handlePopupShow);
			Event.bind(document, 'keydown', this.handleKeyDown, { capture: true });
		},
		unsubscribeEvents(): void
		{
			EventEmitter.unsubscribe('BX.Main.Popup:onShow', this.handlePopupShow);
			Event.unbind(document, 'keydown', this.handleKeyDown, { capture: true });
		},
		handlePopupShow(event): void
		{
			const popup: Popup = event.getCompatData()[0];

			const onClose = (): void => {
				popup.unsubscribe('onClose', onClose);
				popup.unsubscribe('onDestroy', onClose);

				this.popupCount--;
			};

			popup.subscribe('onClose', onClose);
			popup.subscribe('onDestroy', onClose);

			this.popupCount++;
		},
		handleKeyDown(event: KeyboardEvent): void
		{
			if (this.popupCount > 0)
			{
				return;
			}

			if (event.key === 'Enter' && (event.ctrlKey || event.metaKey))
			{
				this.$refs.addTaskButton.handleClick();
			}
			else if (event.key === 'Escape')
			{
				this.close();
			}
		},
		destroy(): void
		{
			this.delete(this.taskId);
			fileService.delete(this.taskId);
		},
	},
	template: `
		<div v-drop class="tasks-compact-card-container">
			<div v-if="task" class="tasks-compact-card" :data-task-id="taskId" data-task-compact>
				<div class="tasks-compact-card-fields">
					<div
						class="tasks-compact-card-fields-title"
						:class="{'--no-gap': task.description.length > 0}"
						ref="title"
					>
						<FieldTitle :taskId="taskId" :disabled="isCheckListPopupShown"/>
						<Importance :taskId="taskId"/>
						<BIcon
							class="tasks-compact-card-fields-close"
							:name="Outline.CROSS_L"
							data-task-button-id="close"
							@click="close"
						/>
					</div>
					<DescriptionInline ref="description" :taskId="taskId"/>
					<div class="tasks-compact-card-fields-list">
						<FieldList :fields="primaryFields"/>
					</div>
					<CheckList
						v-if="isCheckListPopupShown"
						:taskId="taskId"
						:isAutonomous="true"
						@show="handleShowingPopup"
						@close="handleHidingPopup(); closeCheckListPopup();"
						@resize="handleResizingPopup"
					/>
				</div>
				<div class="tasks-compact-card-footer">
					<div class="tasks-compact-card-chips">
						<template v-for="(chip, index) of chips" :key="index">
							<component
								:is="chip"
								v-bind="{ taskId, isAutonomous: true }"
								v-on="getEventListeners(chip, chipsEventHandlers)"
							/>
						</template>
					</div>
					<div class="tasks-compact-card-buttons">
						<div class="tasks-compact-card-main-buttons">
							<AddTaskButton
								ref="addTaskButton"
								:taskId="taskId"
								:size="ButtonSize.MEDIUM"
								v-model:hasError="creationError"
								@addTask="addTask"
							/>
							<UiButton
								:text="loc('TASKS_V2_TCC_CANCEL_BTN')"
								:size="ButtonSize.MEDIUM"
								:style="AirButtonStyle.PLAIN"
								:dataset="{
									taskButtonId: 'cancel',
								}"
								@click="close"
							/>
						</div>
						<FullCardButton v-model:isOpening="openingFullCard" :taskId="taskId"/>
					</div>
				</div>
			</div>
			<DropZone v-if="!isCheckListPopupShown" :taskId="taskId" :bottom="18"/>
		</div>
	`,
};

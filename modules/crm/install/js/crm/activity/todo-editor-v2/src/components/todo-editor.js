import { DatetimeConverter } from 'crm.timeline.tools';
import { Runtime, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { DateTimeFormat } from 'main.date';
import { TextEditor, TextEditorComponent } from 'ui.text-editor';
import { ElementIds, EventIds } from '../analytics';
import type { BlockSettings } from '../todo-editor';
import BlockFactory from './block-factory';
import {
	TodoEditorBlocksAddress,
	TodoEditorBlocksCalendar,
	TodoEditorBlocksClient,
	TodoEditorBlocksFile,
	TodoEditorBlocksLink,
} from './block/index';
import { TodoEditorColorSelector } from './color-selector/todo-editor-color-selector';
import { TodoEditorPingSelector } from './todo-editor-ping-selector';
import { TodoEditorResponsibleUserSelector } from './todo-editor-responsible-user-selector';

const ADD_MODE = 'add';

export const Events = {
	EVENT_RESPONSIBLE_USER_CHANGE: 'crm:timeline:todo:responsible-user-changed',
	EVENT_DEADLINE_CHANGE: 'crm:timeline:todo:deadline-changed',
	EVENT_CALENDAR_CHANGE: 'crm:timeline:todo:calendar-changed',
	EVENT_ACTIONS_POPUP_ITEM_CLICK: 'crm:timeline:todo:actions-popup-item-click',
	EVENT_UPDATE_CLICK: 'crm:timeline:todo:update',
	EVENT_REPEAT_CLICK: 'crm:timeline:todo:repeat',
};

const CALENDAR_BLOCK_ID = TodoEditorBlocksCalendar.methods.getId();

export const TodoEditor = {
	components: {
		TodoEditorResponsibleUserSelector,
		TodoEditorPingSelector,
		TodoEditorBlocksCalendar,
		TodoEditorBlocksClient,
		TodoEditorBlocksLink,
		TodoEditorBlocksFile,
		TodoEditorBlocksAddress,
		TodoEditorColorSelector,
		TextEditorComponent,
	},
	props: {
		deadline: Date,
		defaultTitle: {
			type: String,
			required: false,
			default: '',
		},
		currentUser: Object,
		pingSettings: Object,
		colorSettings: Object,
		mode: {
			type: String,
			required: false,
			default: ADD_MODE,
		},
		actionsPopup: Object,
		blocks: {
			type: Array,
			default: [],
		},
		activityId: {
			type: Number,
			default: null,
			required: false,
		},
		itemIdentifier: {
			type: Object,
			default: {},
			required: true,
		},
		analytics: {
			type: Object,
			default: null,
			required: false,
		},
		textEditor: TextEditor,
	},

	data(): Object
	{
		const currentDeadline = this.deadline ?? new Date();
		const calendarDateTo = Runtime.clone(currentDeadline);
		calendarDateTo.setHours(currentDeadline.getHours() + 1);

		const blocksData = Runtime.clone(this.blocks);

		Object.keys(blocksData).forEach((blockId: string) => {
			this.prepareBlockDataWithEditorParams(blocksData[blockId], { currentDeadline });
		});

		return {
			currentActivityId: this.activityId,
			title: this.defaultTitle,
			currentDeadline,
			calendarDateTo,
			pingOffsets: this.pingSettings.selectedValues,
			colorId: this.colorSettings.selectedValueId,
			responsibleUserId: this.currentUser.userId,
			wasUsed: false,
			blocksData,
			modeData: this.mode,
			currentUserData: this.currentUser,
		};
	},

	computed: {
		deadlineFormatted(): string
		{
			let converter = new DatetimeConverter(this.currentDeadline);

			let deadlineFormatted = converter.toDatetimeString({
				withDayOfWeek: true,
				delimiter: ', ',
			});

			// @todo use event here
			const calendarBlock = this.getBlockDataById(CALENDAR_BLOCK_ID);
			if (calendarBlock?.active)
			{
				converter = new DatetimeConverter(this.calendarDateTo);
				const calendarDateTo = converter.toTimeString();
				deadlineFormatted = `${deadlineFormatted}-${calendarDateTo}`;
			}

			return deadlineFormatted;
		},
		context(): Object
		{
			return {
				userId: this.responsibleUserId,
				activityId: this.currentActivityId,
				itemIdentifier: this.itemIdentifier,
			};
		},
		placeholderTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_ADD_TITLE_PLACEHOLDER');
		},
		popupMenuButtonTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_SHOW_ACTIONS_POPUP');
		},
		orderedBlocksData(): BlockSettings[]
		{
			return this.blocksData.sort((a, b) => b.sort - a.sort);
		},
	},

	methods: {
		/* @internal */
		prepareBlockDataWithEditorParams(blocksData: BlockSettings, { currentDeadline }): void
		{
			this.prepareBlockData(blocksData, {
				currentDeadline,
				responsibleUserId: this.responsibleUserId || this.currentUser.userId,
				userId: this.currentUser.userId,
			});
		},

		prepareBlockData(blockData: BlockSettings, params: Object): void
		{
			// eslint-disable-next-line no-param-reassign
			blockData.active = Type.isBoolean(blockData.active) ? blockData.active : false;
			// eslint-disable-next-line no-param-reassign
			blockData.sort = Type.isNil(blockData.sort) ? 0 : blockData.sort;

			const blockInstance = BlockFactory.getInstance(blockData.id);
			if (Type.isFunction(blockInstance.methods.prepareDataOnBlockConstruct))
			{
				// eslint-disable-next-line no-param-reassign
				blockData = {
					...blockData,
					...blockInstance.methods.prepareDataOnBlockConstruct(blockData, params),
				};
			}
		},

		setData({ title, description, deadline, id, colorId, currentUser, pingOffsets }): void
		{
			this.title = title;
			this.textEditor.setText(description);
			this.currentDeadline = new Date(deadline);
			this.currentActivityId = id;

			this.currentUserData = currentUser;
			this.responsibleUserId = currentUser.userId;

			void this.$nextTick(() => {
				this.$refs.userSelector?.resetToDefault();
			});

			this.setPingOffsets(pingOffsets);
			this.$refs.pingSelector?.setValue(pingOffsets);

			this.$refs.colorSelector.setValue(colorId);
		},

		setMode(mode: string): void
		{
			this.modeData = mode;
		},

		resetCurrentActivityId(): void
		{
			this.currentActivityId = null;
		},

		setBlockFilledValues(id: string, filledValues: Object): void
		{
			const blockData = this.getBlockDataById(id);

			if (blockData)
			{
				blockData.filledValues = filledValues;
			}
		},

		setBlockActive(id: string, value: boolean = true): void
		{
			const blockData = this.getBlockDataById(id);

			if (blockData)
			{
				blockData.active = value;
			}
		},

		resetTitleAndDescription(): void
		{
			this.setTitle(this.defaultTitle);
			this.textEditor.setText(this.defaultDescription);
		},

		setTitle(title: string): void
		{
			this.title = title;
		},

		onDeadlineClick(): void
		{
			// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
			BX.calendar({
				node: this.$refs.deadline,
				bTime: true,
				bHideTime: false,
				bSetFocus: false,
				value: DateTimeFormat.format(DatetimeConverter.getSiteDateTimeFormat(), this.currentDeadline),
				callback: this.onSetDeadlineByCalendar.bind(this),
			});
		},

		onSetDeadlineByCalendar(deadline: Date): void
		{
			this.setDeadline(deadline);

			this.sendAnalyticsDeadlineChange();
		},

		setDeadline(deadline: Date): void
		{
			this.currentDeadline = deadline;

			this.$Bitrix.eventEmitter.emit(Events.EVENT_DEADLINE_CHANGE, { deadline });
		},

		onResponsibleUserChange(event: BaseEvent): void
		{
			const data = event.getData();
			if (data)
			{
				this.setResponsibleUserId(data.responsibleUserId);

				if (!this.responsibleUserSelectorChangeSended)
				{
					this.responsibleUserSelectorChangeSended = true;
					this.sendAnalytics(EventIds.activityView, ElementIds.responsibleUserId);
				}
			}
		},

		onCalendarChange(event: BaseEvent): void
		{
			const data = event.getData();
			if (data)
			{
				const currentDeadlineTimestamp = this.currentDeadline.getTime();

				this.setDeadline(new Date(data.from));
				this.calendarDateTo = new Date(data.to);

				if (currentDeadlineTimestamp !== data.from)
				{
					this.sendAnalyticsDeadlineChange();
				}

				if (Type.isNumber(data.sectionId))
				{
					this.sendAnalyticsCalendarSectionChange();
				}
			}
		},

		setPingOffsets(offsets: number[]): void
		{
			this.pingOffsets = offsets;
		},

		setResponsibleUserId(userId: number): void
		{
			this.responsibleUserId = userId;
		},

		resetPingOffsetsToDefault(): void
		{
			this.setPingOffsets(this.pingSettings.selectedValues);
			this.$refs.pingSelector?.setValue(this.pingSettings.selectedValues);
		},

		resetResponsibleUserToDefault(user: ?Object): void
		{
			if (user)
			{
				this.currentUserData = { ...this.currentUserData, ...user };
			}
			else
			{
				this.currentUserData = { ...this.currentUserData, ...this.currentUser };
			}

			this.setResponsibleUserId(this.currentUserData.userId);

			const userSelector = this.$refs.userSelector;
			if (userSelector)
			{
				userSelector.resetToDefault();
			}
		},

		resetColorSelectorToDefault(): void
		{
			const colorSelector = this.$refs.colorSelector;
			if (colorSelector)
			{
				colorSelector.resetToDefault();
			}
		},

		getData(): Object
		{
			return {
				title: (
					(Type.isString(this.title) && Type.isStringFilled(this.title.trim()))
						? this.title.trim()
						: null
				),
				description: this.textEditor.getText(),
				deadline: this.currentDeadline,
				responsibleUserId: this.responsibleUserId,
				pingOffsets: this.$refs.pingSelector?.getValue(),
				colorId: this.$refs.colorSelector?.getValue(),
				isCalendarSectionChanged: this.isCalendarSectionChanged,
			};
		},

		onColorSelectorValueChange(): void
		{
			if (!this.colorSelectorChangeSended)
			{
				this.colorSelectorChangeSended = true;
				this.sendAnalytics(EventIds.activityView, ElementIds.colorSettings);
			}
		},

		onPingSettingsSelectorValueChange(): void
		{
			if (!this.pingSettingsSelectorChangeSended)
			{
				this.pingSettingsSelectorChangeSended = true;
				this.sendAnalytics(EventIds.activityView, ElementIds.pingSettings);
			}
		},

		sendAnalyticsDeadlineChange(): void
		{
			if (!this.isDeadlineChanged)
			{
				this.sendAnalytics(EventIds.activityView, ElementIds.deadline);
				this.isDeadlineChanged = true;
			}
		},

		sendAnalyticsCalendarSectionChange(): void
		{
			if (!this.isCalendarSectionChanged)
			{
				this.sendAnalytics(EventIds.activityView, ElementIds.calendarSection);
				this.isCalendarSectionChanged = true;
			}
		},

		sendAnalytics(event: string, element: string): void
		{
			if (this.analytics === null)
			{
				return;
			}

			this.analytics
				.setEvent(event)
				.setElement(element)
				.send()
			;
		},

		onTitleInput(event: InputEvent): void
		{
			const { value } = event.target;

			this.setTitle(value);
		},

		onTitleFocus(event: FocusEvent): void
		{
			this.titleBeforeFocus = event.target.value;
		},

		onTitleBlur(event: FocusEvent): void
		{
			const { value } = event.target;

			if (value !== this.defaultTitle && value !== this.titleBeforeFocus)
			{
				this.sendAnalytics(EventIds.activityView, ElementIds.title);
			}
		},

		handleTextEditorFocus(event): void
		{
			this.descriptionBeforeFocus = this.textEditor.getText();
		},

		handleTextEditorBlur(event: FocusEvent): void
		{
			const description = this.textEditor.getText();
			if (Type.isStringFilled(description) && description !== this.descriptionBeforeFocus)
			{
				this.sendAnalytics(EventIds.activityView, ElementIds.description);
			}
		},

		onActionsPopupItemClick({ data: { id, componentId, componentParams } }): void
		{
			const actionId = Type.isNil(componentId) ? id : componentId;

			if (!Type.isStringFilled(actionId))
			{
				return;
			}

			this.blocksData.forEach((block: BlockSettings) => {
				// eslint-disable-next-line no-param-reassign
				block.focused = false;
			});

			const block = this.getBlockDataById(actionId);

			if (Type.isPlainObject(componentParams))
			{
				block.settings = {
					...componentParams,
					...this.getBlockDataFromPropsById(actionId).settings,
				};
			}
			else
			{
				block.settings = Runtime.clone(this.getBlockDataFromPropsById(actionId).settings);
			}

			this.prepareBlockDataWithEditorParams(
				block,
				{
					currentDeadline: this.currentDeadline,
				},
			);

			block.active = true;
			block.focused = true;
			block.sort = this.getNextBlockSortValue();

			this.textEditor.focus();

			if (!this.addBlockSended)
			{
				this.addBlockSended = true;
				this.sendAnalytics(EventIds.activityView, ElementIds.addBlock);
			}
		},

		getNextBlockSortValue(): number
		{
			let maxSortValue = 0;

			this.blocksData.forEach((data) => {
				if (maxSortValue < data.sort)
				{
					maxSortValue = data.sort;
				}
			});

			return ++maxSortValue;
		},

		showActionsPopup(event: BaseEvent): void
		{
			this.actionsPopup.bindElement(event.target).show();
		},

		onBlockCloseClick(id: string): void
		{
			const block = this.getBlockDataById(id);

			this.resetBlock(block);
		},

		closeBlocks(): void
		{
			this.blocksData.forEach((block: BlockSettings) => {
				this.resetBlock(block);
			});
		},

		resetBlock(block: BlockSettings): void
		{
			// eslint-disable-next-line no-param-reassign
			block.active = false;
			// eslint-disable-next-line no-param-reassign
			block.focused = false;
			// eslint-disable-next-line no-param-reassign
			block.sort = 0;

			if (Type.isFunction(this.$refs[block.id]?.reset))
			{
				this.$refs[block.id].reset();
			}

			const blockData = this.getBlockDataById(block.id);
			if (Type.isObject(blockData))
			{
				delete blockData.filledValues;
			}
		},

		getExecutedBlocksData(): Object[]
		{
			const data = [];
			this.blocksData.filter((block) => block.active).forEach((block: BlockSettings) => {
				data.push({
					...this.$refs[block.id][0].getExecutedData(),
					id: block.id,
				});
			});

			return data;
		},

		getBlockComponentName(blockId: string): string
		{
			return `TodoEditorBlocks${blockId.charAt(0).toUpperCase()}${blockId.slice(1)}`;
		},

		getBlockDataById(blockId: string): BlockSettings
		{
			return this.blocksData.find((block) => block.id === blockId);
		},

		getBlockDataFromPropsById(blockId: string): BlockSettings
		{
			return this.blocks.find((block) => block.id === blockId);
		},

		updateFilledValues(blockId: string, data: Object): void
		{
			const blockData = this.getBlockDataById(blockId);

			if (blockData)
			{
				blockData.filledValues = { ...blockData.filledValues, ...data };
			}
		},
	},

	mounted()
	{
		this.$Bitrix.eventEmitter.subscribe(Events.EVENT_RESPONSIBLE_USER_CHANGE, this.onResponsibleUserChange);
		this.$Bitrix.eventEmitter.subscribe(Events.EVENT_CALENDAR_CHANGE, this.onCalendarChange);

		EventEmitter.subscribe(this.actionsPopup, Events.EVENT_ACTIONS_POPUP_ITEM_CLICK, this.onActionsPopupItemClick);
	},

	beforeUnmount()
	{
		this.$Bitrix.eventEmitter.unsubscribe(Events.EVENT_RESPONSIBLE_USER_CHANGE, this.onResponsibleUserChange);
		this.$Bitrix.eventEmitter.unsubscribe(Events.EVENT_CALENDAR_CHANGE, this.onCalendarChange);

		EventEmitter.unsubscribe(this.actionsPopup, Events.EVENT_ACTIONS_POPUP_ITEM_CLICK, this.onActionsPopupItemClick);
	},

	template: `
		<div class="crm-activity__todo-editor-v2_container">
			<div class="crm-activity__todo-editor-v2_editor">
				<button
					class="crm-activity__todo-show-actions-popup-button ui-btn ui-btn-sm ui-btn-base-light"
					@click="showActionsPopup"
				>
					{{ popupMenuButtonTitle }}
					<span class="crm-activity__todo-show-actions-popup-button-arrow"></span>
				</button>
				<TextEditorComponent :events="{ onFocus: this.handleTextEditorFocus, onBlur: this.handleTextEditorBlur }" :editor-instance="textEditor">
					<template #header>
						<div class="crm-activity__todo-editor-v2_header">
							<input
								type="text"
								ref="title"
								class="crm-activity__todo-editor-v2_input_control --title"
								:value="title"
								:placeholder="placeholderTitle"
								maxlength="40"
								@input="onTitleInput"
								@focus="onTitleFocus"
								@blur="onTitleBlur"
							>
							<TodoEditorColorSelector
								ref="colorSelector"
								:valuesList="colorSettings.valuesList"
								:selectedValueId="colorSettings.selectedValueId"
								@onChange="onColorSelectorValueChange"
							/>
							<TodoEditorResponsibleUserSelector
								:userId="currentUserData.userId"
								:userName="currentUserData.title"
								:imageUrl="currentUserData.imageUrl"
								ref="userSelector"
								class="crm-activity__todo-editor-v2_action-btn"
							/>
						</div>
					</template>
					<template #footer>
						<div class="crm-activity__todo-editor-v2_tools">
							<div class="crm-activity__todo-editor-v2_left_tools">
								<div
									ref="deadline"
									@click="onDeadlineClick"
									class="crm-activity__todo-editor-v2_deadline"
								>
								<span class="crm-activity__todo-editor-v2_deadline-pill">
									<span class="crm-activity__todo-editor-v2_deadline-icon"></span>
									<span class="crm-activity__todo-editor-v2_deadline-text">{{ deadlineFormatted }}</span>
								</span>
								</div>
								<TodoEditorPingSelector
									ref="pingSelector"
									:valuesList="pingSettings.valuesList"
									:selectedValues="pingOffsets"
									:deadline="currentDeadline"
									@onChange="onPingSettingsSelectorValueChange"
									class="crm-activity__todo-editor-v2_ping_selector"
								/>
							</div>
						</div>
					</template>
				</TextEditorComponent>
			</div>

			<div
				class="crm-activity__todo-editor-v2_block-wrapper"
				v-for="block in orderedBlocksData"
				key="block.id"
				v-show="block?.active"
			>
				<component
					v-if="block?.active"
					v-bind:is="getBlockComponentName(block.id)"
					:ref="block.id"
					@close="onBlockCloseClick"
					:id="getBlockDataById(block.id).id"
					:title="getBlockDataById(block.id).title"
					:icon="getBlockDataById(block.id).icon"
					:settings="getBlockDataById(block.id).settings ?? {}"
					:filledValues="getBlockDataById(block.id).filledValues"
					:isFocused="getBlockDataById(block.id).focused"
					:context="context"
					@updateFilledValues="updateFilledValues"
				/>
			</div>
		</div>
	`,
};

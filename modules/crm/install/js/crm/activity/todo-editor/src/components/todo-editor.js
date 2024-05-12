import { CopilotTextarea, Events as CopilotTextareaEvents } from 'crm.ai.copilot-textarea';
import { DatetimeConverter } from 'crm.timeline.tools';
import { Browser, Dom, Loc, Text, Type } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { TodoEditorActionBtn } from './todo-editor-action-btn';
import { TodoEditorActionDelimiter } from './todo-editor-action-delimiter';
import { TodoEditorPingSelector } from './todo-editor-ping-selector';
import { TodoEditorResponsibleUserSelector } from './todo-editor-responsible-user-selector';

const TEXTAREA_MAX_HEIGHT = 126;

export const Events = {
	EVENT_RESPONSIBLE_USER_CHANGE: 'crm:timeline:todo:responsible-user-changed',
};

export const TodoEditor = {
	components: {
		TodoEditorActionBtn,
		TodoEditorActionDelimiter,
		TodoEditorResponsibleUserSelector,
		TodoEditorPingSelector,
	},

	props: {
		onFocus: Function,
		onChangeDescription: Function,
		onSaveHotkeyPressed: Function,
		deadline: Date,
		defaultDescription: {
			type: String,
			required: false,
			default: '',
		},
		additionalButtons: Array,
		popupMode: Boolean,
		currentUser: Object,
		pingSettings: Object,
		copilotSettings: Object,
	},

	data(): Object
	{
		const isCopilotEnabled = Type.isPlainObject(this.copilotSettings);

		return {
			description: this.defaultDescription,
			currentDeadline: this.deadline ?? new Date(),
			pingOffsets: this.pingSettings.selectedValues,
			responsibleUserId: this.currentUser.userId,
			showFileUploader: false,
			isTextareaToLong: false,
			wasUsed: false,
			isCopilotEnabled: isCopilotEnabled,
			placeholderText: Loc.getMessage(
				isCopilotEnabled
					? 'CRM_ACTIVITY_TODO_ADD_PLACEHOLDER_WITH_COPILOT'
					: 'CRM_ACTIVITY_TODO_ADD_PLACEHOLDER',
			),
		};
	},

	computed: {
		deadlineFormatted(): string
		{
			const converter = new DatetimeConverter(this.currentDeadline);

			return converter.toDatetimeString({ withDayOfWeek: true, delimiter: ', ' });
		},
	},

	watch: {
		description(): void
		{
			Dom.style(this.$refs.textarea, 'height', 'auto');
			void this.$nextTick(() => {
				const currentTextareaHeight = this.$refs.textarea.scrollHeight;
				Dom.style(this.$refs.textarea, 'height', `${currentTextareaHeight}px`);
				if (this.popupMode === true)
				{
					this.isTextareaToLong = currentTextareaHeight > TEXTAREA_MAX_HEIGHT;
				}
			});
		},
	},

	methods: {
		clearDescription(): void
		{
			this.description = '';
			Dom.style(this.$refs.textarea, 'height', 'auto');
		},

		setDescription(description): void
		{
			this.description = description;
		},

		onTextareaFocus(): void
		{
			this.wasUsed = true;
			this.onFocus();
		},

		onTextareaKeydown(event): void
		{
			if (event.keyCode !== 13)
			{
				return;
			}

			const isMacCtrlKeydown = Browser.isMac() && (event.metaKey === true || event.altKey === true);

			if (event.ctrlKey === true || isMacCtrlKeydown)
			{
				this.onSaveHotkeyPressed();
			}
		},

		setTextareaFocused(): void
		{
			this.wasUsed = true;
			this.$refs.textarea.focus();
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
				callback: this.setDeadline.bind(this),
			});
		},

		setDeadline(newDeadline): void
		{
			this.currentDeadline = newDeadline;
		},

		onResponsibleUserChange(event: BaseEvent): void
		{
			const data = event.getData();
			if (data)
			{
				this.setResponsibleUserId(data.responsibleUserId);
			}
		},

		setPingOffsets(offsets: Array): void
		{
			this.pingOffsets = offsets;
		},

		setResponsibleUserId(userId: Number): void
		{
			this.responsibleUserId = userId;
		},

		resetPingOffsetsToDefault(): void
		{
			this.setPingOffsets(this.pingSettings.selectedValues);
			this.$refs.pingSelector?.setValue(this.pingSettings.selectedValues);
		},

		resetResponsibleUserToDefault(): void
		{
			this.setResponsibleUserId(this.currentUser.userId);

			const userSelector = this.$refs.userSelector;
			if (userSelector)
			{
				userSelector.resetToDefault();
			}
		},

		getData(): Object
		{
			return {
				description: this.description,
				deadline: this.currentDeadline,
				responsibleUserId: this.responsibleUserId,
				pingOffsets: this.$refs.pingSelector?.getValue(),
			};
		},

		onTextareaInput(event)
		{
			const value = event.target.value;

			this.setDescription(value);
			this.onChangeDescription(value);
		},

		onCopilotTextareaValueChange(event: BaseEvent): void
		{
			const copilotId = this.isCopilotEnabled ? this.copilotTextarea.getId() : '';
			const id = event.getData().id;

			if (this.wasUsed && copilotId === id)
			{
				const value = event.getData().value;

				this.setDescription(value);
				this.onChangeDescription(value);
			}
		},
	},

	mounted()
	{
		this.$Bitrix.eventEmitter.subscribe(Events.EVENT_RESPONSIBLE_USER_CHANGE, this.onResponsibleUserChange);

		if (this.isCopilotEnabled)
		{
			this.copilotTextarea = new CopilotTextarea({
				id: Text.getRandom(),
				target: this.$refs.textarea,
				copilotParams: this.copilotSettings,
			});

			EventEmitter.subscribe(CopilotTextareaEvents.EVENT_VALUE_CHANGE, this.onCopilotTextareaValueChange);
		}
	},

	beforeUnmount()
	{
		this.$Bitrix.eventEmitter.unsubscribe(Events.EVENT_RESPONSIBLE_USER_CHANGE, this.onResponsibleUserChange);

		if (this.isCopilotEnabled)
		{
			EventEmitter.unsubscribe(CopilotTextareaEvents.EVENT_VALUE_CHANGE, this.onCopilotTextareaValueChange);
		}
	},

	template: `
		<label class="crm-activity__todo-editor_body">
			<textarea
				rows="2"
				ref="textarea"
				@focus="onTextareaFocus"
				@keydown="onTextareaKeydown"
				class="crm-activity__todo-editor_textarea"
				:placeholder="placeholderText"
				@input="onTextareaInput"
				:value="description"
				:class="{ '--has-scroll': isTextareaToLong }"
			></textarea>
			<div class="crm-activity__todo-editor_tools" v-if="wasUsed">
				<div class="crm-activity__todo-editor_left_tools">
					<div
						ref="deadline"
						@click="onDeadlineClick"
						class="crm-activity__todo-editor_deadline"
					>
						<span class="crm-activity__todo-editor_deadline-icon"><i></i></span>
						<span class="crm-activity__todo-editor_deadline-pill">
							<span class="crm-activity__todo-editor_deadline-text">{{ deadlineFormatted }}</span>
							<span class="crm-timeline__date-pill_caret"></span>
						</span>
					</div>
					<div class="crm-activity__todo-editor_ping_selector_wrapper">
						<span class="crm-activity__todo-editor_ping-selector-icon"><i></i></span>
						<TodoEditorPingSelector
							ref="pingSelector"
							:valuesList="pingSettings.valuesList"
							:selectedValues="pingSettings.selectedValues"
							class="crm-activity__todo-editor_ping_selector"
						>
						</TodoEditorPingSelector>
					</div>
				</div>
				<div class="crm-activity__todo-editor_action-btns">
					<TodoEditorActionBtn
						v-for="btn in additionalButtons"
						:key="btn.id"
						:icon="btn.icon"
						:description="btn.description"
						:action="btn.action"
					/>
					<TodoEditorActionDelimiter/>
					<TodoEditorResponsibleUserSelector
						:userId="currentUser.userId"
						:userName="currentUser.title"
						:imageUrl="currentUser.imageUrl"
						ref="userSelector"
						class="crm-activity__todo-editor_action-btn"
					>
					</TodoEditorResponsibleUserSelector>
				</div>
			</div>
		</label>
	`,
};

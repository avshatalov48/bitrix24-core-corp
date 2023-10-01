import { DateTimeFormat } from 'main.date';
import { Browser, Dom } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { DatetimeConverter } from 'crm.timeline.tools';
import { TodoEditorActionBtn } from './todo-editor-action-btn';
import { TodoEditorActionDelimiter } from './todo-editor-action-delimiter';
import { TodoEditorResponsibleUserSelector } from './todo-editor-responsible-user-selector';

const TEXTAREA_MAX_HEIGHT = 126;

export const Events = {
	EVENT_RESPONSIBLE_USER_CHANGE: 'crm:timeline:todo:responsible-user-changed',
};

export const TodoEditor = {
	components: {
		TodoEditorActionBtn,
		TodoEditorActionDelimiter,
		TodoEditorResponsibleUserSelector
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
	},

	data(): Object
	{
		return {
			description: this.defaultDescription,
			currentDeadline: this.deadline ?? new Date(),
			responsibleUserId: this.currentUser.userId,
			showFileUploader: false,
			isTextareaToLong: false,
			wasUsed: false,
		};
	},

	computed: {
		deadlineFormatted(): string
		{
			const converter = new DatetimeConverter(this.currentDeadline);

			return converter.toDatetimeString({ withDayOfWeek: true, delimiter:', ' });
		}
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

		setResponsibleUserId(userId: Number): void
		{
			this.responsibleUserId = userId;
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
			};
		},

		onTextareaInput(event)
		{
			this.setDescription(event.target.value);
			this.onChangeDescription(event.target.value);
		},
	},

	mounted()
	{
		this.$Bitrix.eventEmitter.subscribe(Events.EVENT_RESPONSIBLE_USER_CHANGE, this.onResponsibleUserChange);
	},

	beforeUnmount()
	{
		this.$Bitrix.eventEmitter.unsubscribe(Events.EVENT_RESPONSIBLE_USER_CHANGE, this.onResponsibleUserChange);
	},

	template: `
		<label class="crm-activity__todo-editor_body">
			<textarea
				rows="1"
				ref="textarea"
				@focus="onTextareaFocus"
				@keydown="onTextareaKeydown"
				class="crm-activity__todo-editor_textarea"
				:placeholder="$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_ADD_PLACEHOLDER')"
				@input="onTextareaInput"
				:value="description"
				:class="{ '--has-scroll': isTextareaToLong }"
			></textarea>
			<div class="crm-activity__todo-editor_tools" v-if="wasUsed">
				<div
					ref="deadline"
					@click="onDeadlineClick"
					class="crm-activity__todo-editor_deadline"
				>
					<span class="crm-activity__todo-editor_deadline-icon"><i></i></span>
					<span class="crm-activity__todo-editor_deadline-text">{{ deadlineFormatted }}</span>
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
	`
};

import { DatetimeConverter } from "crm.timeline.tools";
import { TodoEditorActionBtn } from './todo-editor-action-btn';
import { DateTimeFormat } from "main.date";
import { Dom, Browser } from 'main.core';

export const TodoEditor = {
	components: {
		TodoEditorActionBtn,
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
	},
	data(): Object
	{
		return {
			description: this.defaultDescription,
			currentDeadline: this.deadline ?? new Date(),
			showFileUploader: false
		}
	},
	computed: {
		deadlineFormatted(): string
		{
			return (new DatetimeConverter(this.currentDeadline)).toDatetimeString({ withDayOfWeek: true, delimiter:', ' });
		}
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
			Dom.style(this.$refs.textarea, 'height', 'auto');
			Dom.style(this.$refs.textarea, 'height', `${this.$refs.textarea.scrollHeight}px`);
		},

		onTextareaFocus(): void
		{
			this.onFocus();
		},

		onTextareaKeydown(event): void
		{
			if (
				event.keyCode === 13
				&& (
					event.ctrlKey === true ||
					( Browser.isMac() && (event.metaKey === true || event.altKey === true) )
				)
			)
			{
				this.onSaveHotkeyPressed();
			}
		},

		setTextareaFocused(): void
		{
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
				callback: this.setDeadlineValue.bind(this)
			});
		},

		setDeadlineValue(newDeadline): void
		{
			this.currentDeadline = newDeadline;
		},

		getData(): Object
		{
			return {
				description: this.description,
				deadline: this.currentDeadline,
			};
		},

		onTextareaInput(event)
		{
			Dom.style(event.target, 'height', 'auto');
			Dom.style(event.target, 'height', `${event.target.scrollHeight}px`);

			this.description = event.target.value;

			this.onChangeDescription(event.target.value);
		}
	},
	template: `
			<textarea 
				rows="1" 
				ref="textarea"
				@focus="onTextareaFocus"
				@keydown="onTextareaKeydown"
				class="crm-activity__todo-editor_textarea"
				:placeholder="$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_ADD_PLACEHOLDER')"
				@input="onTextareaInput"
				:value="description"
			></textarea>
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
			</div>
	`
};

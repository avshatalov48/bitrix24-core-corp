import { DatetimeConverter } from 'crm.timeline.tools';
import { TodoEditorActionBtn } from './todo-editor-action-btn';
import { DateTimeFormat } from 'main.date';
import { Dom, Browser } from 'main.core';

const TEXTAREA_MAX_HEIGHT = 126;

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
		popupMode: Boolean,
	},
	data(): Object
	{
		return {
			description: this.defaultDescription,
			currentDeadline: this.deadline ?? new Date(),
			showFileUploader: false,
			isTextareaToLong: false,
		}
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

		getData(): Object
		{
			return {
				description: this.description,
				deadline: this.currentDeadline,
			};
		},

		onTextareaInput(event)
		{
			this.setDescription(event.target.value);
			this.onChangeDescription(event.target.value);
		},
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
			<div class="crm-activity__todo-editor_tools">
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
			</div>
		</label>
	`
};

import { CopilotTextarea, Events as CopilotTextareaEvents } from 'crm.ai.copilot-textarea';
import { Browser, Loc, Runtime, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

import { Action } from '../../action';
import { Button } from '../layout/button';
import { ButtonState } from '../enums/button-state';
import { ButtonType } from '../enums/button-type';
import { EditableDescriptionHeight } from '../enums/editable-description-height';
import { EditableDescriptionBackgroundColor } from '../enums/editable-description-background-color';

export const EditableDescription = {
	components: {
		Button,
	},
	props: {
		text: {
			type: String,
			required: false,
			default: '',
		},
		saveAction: {
			type: Object,
			required: false,
			default: null,
		},
		editable: {
			type: Boolean,
			required: false,
			default: true,
		},
		height: {
			type: String,
			required: false,
			default: EditableDescriptionHeight.SHORT,
		},
		backgroundColor: {
			type: String,
			required: false,
			default: '',
		},
		copilotSettings: {
			type: Array,
			required: false,
			default: [],
		},
	},

	data(): Object
	{
		return {
			value: this.text,
			oldValue: this.text,
			isEdit: false,
			isSaving: false,
			isLongText: false,
			isCollapsed: false,
			isCopilotEnabled: Type.isPlainObject(this.copilotSettings),
			placeholderText: Loc.getMessage(
				Type.isPlainObject(this.copilotSettings)
					? 'CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_PLACEHOLDER_WITH_COPILOT'
					: 'CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_PLACEHOLDER',
			),
		};
	},

	inject: [
		'isReadOnly',
		'isLogMessage',
	],

	computed: {
		className(): Array
		{
			return [
				'crm-timeline__editable-text',
				[String(this.heightClassnameModifier), String(this.bgColorClassnameModifier)],
				{
					'--is-read-only': this.isLogMessage,
					'--is-edit': this.isEdit,
					'--is-long': this.isLongText,
					'--is-expanded': this.isCollapsed || !this.isLongText,
				},
			];
		},

		heightClassnameModifier(): string
		{
			switch (this.height)
			{
				case EditableDescriptionHeight.LONG: return '--height-long';
				case EditableDescriptionHeight.SHORT: return '--height-short';
				default: return '--height-short';
			}
		},

		bgColorClassnameModifier(): ?string
		{
			switch (this.backgroundColor)
			{
				case EditableDescriptionBackgroundColor.YELLOW: return '--bg-color-yellow';
				case EditableDescriptionBackgroundColor.WHITE: return '--bg-color-white';
				default: return '';
			}
		},

		isEditable(): boolean
		{
			return this.editable && this.saveAction && !this.isReadOnly;
		},

		saveTextButtonProps(): Object
		{
			return {
				state: this.saveTextButtonState,
				type: ButtonType.PRIMARY,
				title: this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_SAVE'),
			};
		},

		cancelEditingButtonProps(): Object
		{
			return {
				type: ButtonType.LIGHT,
				title: this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_CANCEL'),
				state: this.isSaving ? ButtonState.DISABLED : ButtonState.DEFAULT,
			};
		},

		saveTextButtonState(): string
		{
			const trimValue = this.value.trim();

			if (trimValue.length === 0)
			{
				return ButtonState.DISABLED;
			}

			if (this.isSaving)
			{
				return ButtonState.DISABLED;
			}

			return ButtonState.DEFAULT;
		},

		expandButtonText(): string
		{
			return this.isCollapsed
				? this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_HIDE_MSGVER_1')
				: this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_SHOW_MSGVER_1');
		},

		isEditButtonVisible(): boolean
		{
			return !(this.isReadOnly || this.isEdit);
		},
	},

	methods: {
		startEditing(): void
		{
			this.destroyCopilot();

			this.isEdit = true;
			this.isCollapsed = true;
			this.$nextTick(() => {
				const textarea = this.$refs.textarea;

				this.createCopilot(textarea);
				this.adjustHeight(textarea);

				textarea.focus();
			});

			this.emitEvent('EditableDescription:StartEdit');
		},

		emitEvent(eventName: string): void
		{
			const action = new Action({
				type: 'jsEvent',
				value: eventName,
			});

			action.execute(this);
		},

		adjustHeight(elem): void
		{
			elem.style.height = 0;
			elem.style.height = `${elem.scrollHeight}px`;
		},

		onPressEnter(event): void
		{
			if (
				event.ctrlKey === true
				|| (Browser.isMac() && (event.metaKey === true || event.altKey === true))
			)
			{
				this.saveText();
			}
		},

		saveText(): void
		{
			if (
				this.saveTextButtonState === ButtonState.DISABLED
				|| this.saveTextButtonState === ButtonState.LOADING
				|| !this.isEdit
			)
			{
				return;
			}

			if (this.value.trim() === this.oldValue)
			{
				this.isEdit = false;
				this.emitEvent('EditableDescription:FinishEdit');
			}

			this.isSaving = true;
			const encodedTrimText = this.value.trim();

			// eslint-disable-next-line promise/catch-or-return
			this.executeSaveAction(encodedTrimText).then(() => {
				this.isEdit = false;
				this.oldValue = encodedTrimText;
				this.value = encodedTrimText;
				this.$nextTick(() => {
					this.isLongText = this.checkIsLongText();
				});
				this.emitEvent('EditableDescription:FinishEdit');
			}).finally(() => {
				this.isSaving = false;
			});
		},

		executeSaveAction(text: string): ?Promise
		{
			if (!this.saveAction)
			{
				return;
			}

			if (!this.value)
			{
				return;
			}

			// to avoid unintended props mutation
			const actionDescription = Runtime.clone(this.saveAction);

			actionDescription.actionParams ??= {};
			actionDescription.actionParams.value = text;

			const action = new Action(actionDescription);

			// eslint-disable-next-line consistent-return
			return action.execute(this);
		},

		cancelEditing(): void
		{
			if (!this.isEdit || this.isSaving)
			{
				return;
			}

			this.value = this.oldValue;
			this.isEdit = false;
			this.emitEvent('EditableDescription:FinishEdit');
		},

		clearText(): void
		{
			if (this.isSaving)
			{
				return;
			}
			this.value = '';
			this.$refs.textarea.focus();
		},

		toggleIsCollapsed(): void
		{
			this.isCollapsed = !this.isCollapsed;
		},

		checkIsLongText(): boolean
		{
			const textBlock = this.$refs.text;
			if (!textBlock)
			{
				return false;
			}

			const textBlockMaxHeightStyle = window.getComputedStyle(textBlock).getPropertyValue('--crm-timeline__editable-text_max-height');
			const textBlockMaxHeight = parseFloat(textBlockMaxHeightStyle.slice(0, -2));
			const parentComputedStyles = this.$refs.rootElement ? window.getComputedStyle(this.$refs.rootElement) : {};

			// eslint-disable-next-line no-unsafe-optional-chaining
			const parentHeight = this.$refs.rootElement?.offsetHeight
				- parseFloat(parentComputedStyles.paddingTop)
				- parseFloat(parentComputedStyles.paddingBottom)
			;

			return parentHeight > textBlockMaxHeight;
		},

		isInViewport(): boolean
		{
			const rect = this.$el.getBoundingClientRect();

			return (
				rect.top >= 0
				&& rect.left >= 0
				&& rect.bottom <= (window.innerHeight || document.documentElement.clientHeight)
				&& rect.right <= (window.innerWidth || document.documentElement.clientWidth)
			);
		},

		onCopilotTextareaValueChange(event: BaseEvent): void
		{
			const copilotId = this.isCopilotEnabled ? this.copilotTextarea.getId() : '';
			const id = event.getData().id;

			if (this.isEdit && copilotId === id)
			{
				this.value = event.getData().value;
			}
		},

		createCopilot(textarea: HTMLElement): void
		{
			if (this.isCopilotEnabled)
			{
				this.copilotTextarea = new CopilotTextarea({
					id: Text.getRandom(),
					target: textarea,
					copilotParams: this.copilotSettings,
				});

				EventEmitter.subscribe(CopilotTextareaEvents.EVENT_VALUE_CHANGE, this.onCopilotTextareaValueChange);
			}
		},

		destroyCopilot(): void
		{
			if (this.isCopilotEnabled)
			{
				EventEmitter.unsubscribe(CopilotTextareaEvents.EVENT_VALUE_CHANGE, this.onCopilotTextareaValueChange);
				delete this.copilotTextarea;
			}
		},
	},

	watch: {
		text(newTextValue): void
		{
			// update text from push
			this.value = newTextValue;
			this.oldValue = newTextValue;
			this.$nextTick(() => {
				this.isLongText = this.checkIsLongText();
			});
		},

		value(): void
		{
			if (!this.isEdit)
			{
				return;
			}

			this.$nextTick(() => {
				this.adjustHeight(this.$refs.textarea);
			});
		},

		isCollapsed(isCollapsed): void
		{
			if (isCollapsed === false && this.isInViewport() === false)
			{
				requestAnimationFrame(() => {
					this.$el.scrollIntoView({
						behavior: 'smooth',
						block: 'center',
					});
				});
			}
		},
	},

	mounted(): void
	{
		this.$nextTick(() => {
			this.isLongText = this.checkIsLongText();
		});
	},

	beforeUnmount(): void
	{
		this.destroyCopilot();
	},

	template: `
		<div class="crm-timeline__editable-text_wrapper">
			<div ref="rootElement" :class="className">
				<button
					v-if="isEdit && isEditable"
					:disabled="isSaving"
					@click="clearText"
					class="crm-timeline__editable-text_clear-btn"
				>
					<i class="crm-timeline__editable-text_clear-icon"></i>
				</button>
				<button
					v-if="isLongText && !isEdit && isEditable && isEditButtonVisible"
					:disabled="isSaving"
					@click="startEditing"
					class="crm-timeline__editable-text_edit-btn"
				>
					<i class="crm-timeline__editable-text_edit-icon"></i>
				</button>
				<div class="crm-timeline__editable-text_inner">
					<div class="crm-timeline__editable-text_content">
						<textarea
							v-if="isEdit"
							ref="textarea"
							v-model="value"
							:disabled="!isEdit || isSaving"
							:placeholder="placeholderText"
							@keydown.esc="cancelEditing"
							@keydown.enter="onPressEnter"
							class="crm-timeline__editable-text_text"
						></textarea>
						<span
							v-else
							ref="text"
							class="crm-timeline__editable-text_text"
						>
							{{value}}
						</span>
						<span
							v-if="!isEdit && !isLongText && isEditable && isEditButtonVisible"
							@click="startEditing"
							class="crm-timeline__editable-text_text-edit-icon"
						>
							<span class="crm-timeline__editable-text_edit-icon"></span>
						</span>
					</div>
					<div
						v-if="isEdit"
						class="crm-timeline__editable-text_actions"
					>
						<div class="crm-timeline__editable-text_action">
							<Button
								v-bind="saveTextButtonProps"
								@click="saveText"
							/>
						</div>
						<div class="crm-timeline__editable-text_action">
							<Button
								v-bind="cancelEditingButtonProps"
								@click="cancelEditing"
							/>
						</div>
					</div>
				</div>
				<button
					v-if="isLongText && !isEdit"
					@click="toggleIsCollapsed"
					class="crm-timeline__editable-text_collapse-btn"
				>
					{{ expandButtonText }}
				</button>
			</div>
		</div>
	`,
};

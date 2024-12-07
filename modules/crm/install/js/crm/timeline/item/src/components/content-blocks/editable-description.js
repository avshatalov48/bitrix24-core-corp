import { Loc, Runtime, Text, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Popup } from 'main.popup';

import { Action } from '../../action';
import { Button } from '../layout/button';
import { ButtonState } from '../enums/button-state';
import { ButtonType } from '../enums/button-type';
import { EditableDescriptionHeight } from '../enums/editable-description-height';
import { EditableDescriptionBackgroundColor } from '../enums/editable-description-background-color';
import { TextEditorComponent, BasicEditor } from 'ui.text-editor';
import { HtmlFormatterComponent } from 'ui.bbcode.formatter.html-formatter';

export const EditableDescription = {
	components: {
		Button,
		TextEditorComponent,
		HtmlFormatterComponent,
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
		copied: {
			type: Boolean,
			required: false,
			default: false,
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
			type: Object,
			required: false,
			default: [],
		},
	},

	beforeCreate()
	{
		this.textEditor = null;
	},

	data(): Object
	{
		return {
			isEdit: false,
			isSaving: false,
			isLongText: false,
			isCollapsed: false,
			bbcode: this.text,
			isContentEmpty: Type.isString(this.text) && this.text.trim() === '',
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

		isCopied(): boolean
		{
			return !this.isEdit && this.copied;
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
			if (this.isContentEmpty)
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
			this.isEdit = true;
			this.isCollapsed = true;
			this.$nextTick(() => {
				this.getTextEditor().focus(null, { defaultSelection: 'rootEnd' });
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

			const encodedTrimText = this.getTextEditor().getText().trim();
			if (encodedTrimText === this.bbcode)
			{
				this.isEdit = false;
				this.emitEvent('EditableDescription:FinishEdit');

				return;
			}

			this.isSaving = true;

			// eslint-disable-next-line promise/catch-or-return
			this.executeSaveAction(encodedTrimText).then(() => {
				this.isEdit = false;
				this.bbcode = encodedTrimText;
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

			this.isEdit = false;
			this.emitEvent('EditableDescription:FinishEdit');
		},

		clearText(): void
		{
			if (this.isSaving)
			{
				return;
			}

			this.getTextEditor().clear();
			this.getTextEditor().focus(null, { defaultSelection: 'rootEnd' });
		},

		copyText(): void
		{
			const selection = window.getSelection();
			selection.removeAllRanges();

			const range = document.createRange();
			const referenceNode = this.$refs.text;
			range.selectNodeContents(referenceNode);
			selection.addRange(range);

			let isSuccess = false;
			try
			{
				isSuccess = document.execCommand('copy');
			}
			catch (err)
			{
				// just in case
			}

			selection.removeAllRanges();

			if (isSuccess)
			{
				new Popup({
					id: `copyTextHint_${Text.getRandom(8)}`,
					content: Loc.getMessage('CRM_TIMELINE_ITEM_TEXT_IS_COPIED'),
					bindElement: this.$refs.copyTextBtn,
					darkMode: true,
					autoHide: true,
					events: {
						onAfterPopupShow() {
							setTimeout(() => {
								this.close();
							}, 2000);
						},
					},
				}).show();
			}
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

		getTextEditor(): BasicEditor
		{
			if (this.textEditor !== null)
			{
				return this.textEditor;
			}

			this.textEditor = new BasicEditor({
				removePlugins: ['BlockToolbar'],
				maxHeight: 600,
				content: this.bbcode,
				paragraphPlaceholder: Loc.getMessage(
					Type.isPlainObject(this.copilotSettings)
						? 'CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_PLACEHOLDER_WITH_COPILOT'
						: null,
				),
				toolbar: [],
				floatingToolbar: [
					'bold', 'italic', 'underline', 'strikethrough',
					'|',
					'link', 'copilot',
				],
				visualOptions: {
					colorBackground: 'transparent',
					borderWidth: '0px',
					blockSpaceInline: '0px',
					blockSpaceStack: '0px',
				},
				copilot: {
					copilotOptions: Type.isPlainObject(this.copilotSettings) ? this.copilotSettings : null,
					triggerBySpace: true,
				},
				events: {
					onMetaEnter: () => {
						this.saveText();
					},
					onEscape: () => {
						this.cancelEditing();
					},
					onEmptyContentToggle: (event: BaseEvent) => {
						this.isContentEmpty = event.getData().isEmpty;
					},
				},
			});

			return this.textEditor;
		},
	},

	watch: {
		text(newTextValue): void
		{
			this.bbcode = newTextValue;

			this.$nextTick(() => {
				this.isLongText = this.checkIsLongText();
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

		isSaving(value: boolean): void
		{
			if (this.textEditor !== null) // CommentContent uses this method as well
			{
				this.getTextEditor().setEditable(!value);
			}
		},

		isEdit(value: boolean): void
		{
			if (value === false && this.textEditor !== null)
			{
				this.textEditor.destroy();
				this.textEditor = null;
			}
		},
	},

	mounted(): void
	{
		this.$nextTick(() => {
			this.isLongText = this.checkIsLongText();
		});
	},

	template: `
		<div class="crm-timeline__editable-text_wrapper">
			<div ref="rootElement" :class="className">
				<button
					v-if="this.isCopied"
					ref="copyTextBtn"
					@click="copyText"
					class="crm-timeline__text_copy-btn"
				>
					<i class="crm-timeline__editable-text_fixed-icon --copy"></i>
				</button>
				<button
					v-if="isEdit && isEditable"
					:disabled="isSaving"
					@click="clearText"
					class="crm-timeline__editable-text_clear-btn"
				>
					<i class="crm-timeline__editable-text_fixed-icon --clear"></i>
				</button>
				<button
					v-if="!isEdit && isEditable && isEditButtonVisible"
					:disabled="isSaving"
					@click="startEditing"
					class="crm-timeline__editable-text_edit-btn"
				>
					<i class="crm-timeline__editable-text_edit-icon"></i>
				</button>
				<div class="crm-timeline__editable-text_inner">
					<div class="crm-timeline__editable-text_content">
						<TextEditorComponent
							v-if="isEdit"
							:editor-instance="this.getTextEditor()"
						/>
						<span
							v-else
							ref="text"
							class="crm-timeline__editable-text_text"
						>
							<HtmlFormatterComponent :bbcode="bbcode" />
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

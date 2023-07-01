import { Action } from "../../action";
import { Browser, Runtime } from "main.core";
import { Button } from '../layout/button';
import { ButtonState } from '../enums/button-state';
import { ButtonType } from '../enums/button-type';
import { EditableDescriptionHeight } from "../enums/editable-description-height";
import { EditableDescriptionBackgroundColor } from "../enums/editable-description-background-color";

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
		}
	},

	data() {
		return {
			value: this.text,
			oldValue: this.text,
			isEdit: false,
			isSaving: false,
			isLongText: false,
			isCollapsed: false,
		};
	},

	inject: ['isReadOnly', 'isLogMessage'],

	computed: {
		className()
		{
			return [
				'crm-timeline__editable-text',
				[`${this.heightClassnameModifier}`, `${this.bgColorClassnameModifier}`],
				{
				'--is-read-only': this.isLogMessage,
				'--is-edit': this.isEdit,
				'--is-long': this.isLongText,
				'--is-expanded': this.isCollapsed || !this.isLongText,
				}
			]
		},

		heightClassnameModifier()
		{
			switch (this.height)
			{
				case EditableDescriptionHeight.LONG: return '--height-long';
				case EditableDescriptionHeight.SHORT: return '--height-short';
				default: return '--height-short';
			}
		},

		bgColorClassnameModifier()
		{
			switch (this.backgroundColor)
			{
				case EditableDescriptionBackgroundColor.YELLOW: return '--bg-color-yellow';
				case EditableDescriptionBackgroundColor.WHITE: return '--bg-color-white';
				default: return '';
			};
		},

		isEditable()
		{
			return this.editable && this.saveAction && !this.isReadOnly;
		},

		saveTextButtonProps()
		{
			return {
				state: this.saveTextButtonState,
				type: ButtonType.PRIMARY,
				title: this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_SAVE'),
			}
		},

		cancelEditingButtonProps()
		{
			return {
				type: ButtonType.LIGHT,
				title: this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_CANCEL'),
				state: this.isSaving ? ButtonState.DISABLED : ButtonState.DEFAULT,
			}
		},

		saveTextButtonState()
		{
			const trimValue = this.value.trim();
			if (trimValue.length === 0) {
				return ButtonState.DISABLED;
			} else if (this.isSaving) {
				return ButtonState.LOADING;
			} else {
				return ButtonState.DEFAULT;
			}

		},

		expandButtonText()
		{
			return this.isCollapsed
				? this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_HIDE')
				: this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_SHOW');
		},

		isEditButtonVisible(): Boolean
		{
			return !(this.isReadOnly || this.isEdit);
		},
	},

	methods: {
		startEditing() {
			this.isEdit = true;
			this.isCollapsed = true;
			this.$nextTick(() => {
				const textarea = this.$refs.textarea;
				this.adjustHeight(textarea);
				textarea.focus();
			});

			this.emitEvent('EditableDescription:StartEdit');
		},

		emitEvent(eventName: string)
		{
			const action = new Action({
				type: 'jsEvent',
				value: eventName
			});
			action.execute(this);
		},

		adjustHeight(elem) {
			elem.style.height = 0;
			elem.style.height = (elem.scrollHeight)+"px";
		},

		onPressEnter(event) {
			if (
				event.ctrlKey === true ||
				( Browser.isMac() && (event.metaKey === true || event.altKey === true) )
			)
			{
				this.saveText();
			}
		},

		saveText() {
			if (this.saveTextButtonState === ButtonState.DISABLED || this.saveTextButtonState === ButtonState.LOADING || !this.isEdit)
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

		executeSaveAction(text: string): void {
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

			return action.execute(this);
		},

		cancelEditing() {
			if (!this.isEdit || this.isSaving)
			{
				return;
			}

			this.value = this.oldValue;
			this.isEdit = false;
			this.emitEvent('EditableDescription:FinishEdit');
		},

		clearText() {
			if (this.isSaving)
			{
				return;
			}
			this.value = '';
			this.$refs.textarea.focus();
		},

		toggleIsCollapsed() {
			this.isCollapsed = !this.isCollapsed;
		},

		checkIsLongText() {
			const textBlock = this.$refs.text;
			if (!textBlock) return false;
			const textBlockMaxHeightStyle = window.getComputedStyle(textBlock).getPropertyValue('--crm-timeline__editable-text_max-height');
			const textBlockMaxHeight = parseFloat(textBlockMaxHeightStyle.slice(0, -2));
			const parentComputedStyles = this.$refs.rootElement ? window.getComputedStyle(this.$refs.rootElement) : {};
			const parentHeight = this.$refs.rootElement?.offsetHeight -  parseFloat(parentComputedStyles.paddingTop) - parseFloat(parentComputedStyles.paddingBottom);

			return parentHeight > textBlockMaxHeight;
		},
	},

	watch: {
		text(newTextValue) {
			// update text from push
			this.value = newTextValue;
			this.oldValue = newTextValue;
			this.$nextTick(() => {
				this.isLongText = this.checkIsLongText();
			});
		},

		value() {
			if (!this.isEdit) return;
			this.$nextTick(() => {
				this.adjustHeight(this.$refs.textarea);
			});
		},
	},

	mounted() {
		this.$nextTick(() => {
			this.isLongText = this.checkIsLongText();
		});
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
							:placeholder="$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_EDITABLE_DESCRIPTION_PLACEHOLDER')"
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
	`
};

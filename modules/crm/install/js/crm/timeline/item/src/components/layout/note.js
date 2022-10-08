import {User} from './header/user';
import {Action} from '../../action';
import {Button} from './button';
import {ButtonType} from '../enums/button-type';
import {ButtonState} from '../enums/button-state';
import {Text} from 'main.core';

export const Note = {
	components: {
		User,
		Button,
	},
	props: {
		text: {
			type: String,
			required: false,
			default: '',
		},
		saveNoteAction: Object,
		deleteNoteAction: Object,
		user: Object,
	},
	data() {
		return {
			isEdit: false,
			note: this.text,
			oldNote: this.text,
		}
	},
	computed: {
		ButtonType() {
			return ButtonType;
		},
		saveButtonState() {
			return this.note.trim().length ? ButtonState.DEFAULT : ButtonState.DISABLED;
		},
		encodedText() {
			return Text.encode(this.text);
		},
	},
	methods: {
		startEditing() {
			this.isEdit = true;
			this.setCaretToEnd();
		},

		setCaretToEnd() {
			const el = this.$refs.noteText;
			const selection = window.getSelection();
			const range = document.createRange();
			selection.removeAllRanges();
			range.selectNodeContents(el);
			range.collapse(false);
			selection.addRange(range);
			el.focus();
		},

		onEdit(e) {
			this.note =  e.target.innerText;
		},

		onEnterHandle(e) {
			if (e.ctrlKey) {
				this.finishEditing();
			}
		},

		finishEditing() {
			if (this.saveButtonState === ButtonState.DISABLED || this.state === ButtonState.LOADING) {
				return;
			}
			const note = this.$refs.noteText.innerText.trim();
			this.$refs.noteText.innerText = note;
			this.note = note;
			this.oldNote = note;
			this.isEdit = false;
			this.saveNote();
		},

		cancelEditing() {
			this.note = this.oldNote;
			this.$refs.noteText.innerText = this.oldNote;
			this.isEdit = false;
		},

		deleteNote() {
			this.executeAction(this.deleteNoteAction);
		},

		saveNote() {
			this.executeAction({...this.saveNoteAction, value: this.note});
		},

		executeAction(actionObject) {
			if (!actionObject) {
				return;
			}

			const action = new Action(actionObject);
			action.execute(this);
		},
	},
	template: `
		<div class="crm-timeline__card-note">
			<div class="crm-timeline__card-note_user">
				<User v-bind="user"></User>
			</div>
			<div class="crm-timeline__card-note_area">
				<div class="crm-timeline__card-note_value">
					<span
						ref="noteText"
						@input="onEdit"
						@keydown.esc="cancelEditing"
						@keydown.enter="onEnterHandle"
						v-html="encodedText"
						:contenteditable="isEdit"
						tabindex="0"
						class="crm-timeline__card-note_text"
					></span>
					<span
						v-if="!note"
						@click.prevent="setCaretToEnd"
						class="crm-timeline__card-note_placeholder"
					>
						{{ $Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_PLACEHOLDER') }}
					</span>
					<span
						v-if="!isEdit"
						class="crm-timeline__card-note_edit"
						@click.prevent.stop="startEditing"
					>
						<i></i>
					</span>
				</div>
				<div v-if="isEdit" class="crm-timeline__card-note__controls">
					<div class="crm-timeline__card-note__control --save">
						<Button @click="finishEditing" :state="saveButtonState" :type="ButtonType.PRIMARY" :title="$Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_SAVE')" />
					</div>
					<div class="crm-timeline__card-note__control --cancel">
						<Button @click="cancelEditing" :type="ButtonType.LIGHT" :title="$Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_CANCEL')" />
					</div>
				</div>
			</div>
			<div class="crm-timeline__card-note_cross" @click="deleteNote">
				<i></i>
			</div>
		</div>
		`
};

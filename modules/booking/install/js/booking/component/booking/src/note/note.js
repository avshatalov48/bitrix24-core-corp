import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { NotePopup } from 'booking.component.note-popup';

import './note.css';

export type { NotePopupSavePayload } from 'booking.component.note-popup';

// @vue/component
export const Note = {
	name: 'BaseNote',
	components: {
		NotePopup,
	},
	expose: [
		'showViewPopup',
		'closeViewPopup',
	],
	props: {
		id: {
			type: [Number, String],
			required: true,
		},
		bindElement: {
			type: Function,
			required: true,
		},
		note: {
			type: String,
			default: '',
		},
		dataId: {
			type: [String, Number],
			default: '',
		},
		dataElementPrefix: {
			type: String,
			default: '',
		},
		className: {
			type: [String, Object, Array],
			default: '',
		},
		dataAttributes: {
			type: Object,
			default: null,
		},
	},
	emits: ['save'],
	data(): Object
	{
		return {
			isPopupShown: false,
			isEditMode: false,
		};
	},
	computed: {
		...mapGetters({
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
		hasNote(): boolean
		{
			return Boolean(this.note);
		},
	},
	methods: {
		showViewPopup(): void
		{
			if (this.isPopupShown || !this.hasNote)
			{
				return;
			}

			this.isEditMode = false;
			this.isPopupShown = true;
		},
		closeViewPopup(): void
		{
			if (this.isEditMode)
			{
				return;
			}

			this.isPopupShown = false;
		},
		showEditPopup(): void
		{
			this.isEditMode = true;
			this.isPopupShown = true;
		},
		closeEditPopup(): void
		{
			if (!this.isEditMode)
			{
				return;
			}

			this.isPopupShown = false;
		},
	},
	template: `
		<div class="booking--booking-base-note">
			<div
				class="booking--booking-base-note-button"
				:class="[className, {'--has-note': hasNote}].flat(1)"
				v-bind="$props.dataAttributes"
				@click="showEditPopup"
			>
				<div class="ui-icon-set --note"></div>
			</div>
		</div>
		<NotePopup
			v-if="isPopupShown"
			:isEditMode="isEditMode && isFeatureEnabled"
			:id="id"
			:text="note"
			:bindElement
			:dataId
			:dataElementPrefix
			@close="closeEditPopup"
			@save="$emit('save', $event)"
		/>
	`,
};

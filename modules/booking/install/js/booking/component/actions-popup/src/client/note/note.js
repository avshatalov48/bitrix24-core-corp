import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';

import { Model } from 'booking.const';
import { NotePopup } from 'booking.component.note-popup';
import type { NotePopupSavePayload } from 'booking.component.note-popup';

import './note.css';

export type { NotePopupSavePayload as UpdateNotePayload };

// @vue/component
export const Note = {
	props: {
		id: {
			type: [Number, String],
			required: true,
		},
		note: {
			type: String,
			default: '',
		},
		dataId: {
			type: [Number, String],
			default: '',
		},
		dataElementPrefix: {
			type: String,
			default: '',
		},
		dataAttributes: {
			type: Object,
			default: null,
		},
	},
	emits: ['popupShown', 'popupClosed', 'updateNote'],
	data(): Object
	{
		return {
			IconSet,
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
		onMouseEnter(): void
		{
			this.showNoteTimeout = setTimeout(() => this.showViewPopup(), 100);
		},
		onMouseLeave(): void
		{
			clearTimeout(this.showNoteTimeout);
			this.closeViewPopup();
		},
		showViewPopup(): void
		{
			if (this.isPopupShown || !this.hasNote)
			{
				return;
			}

			this.isEditMode = false;
			this.showPopup();
		},
		closeViewPopup(): void
		{
			if (this.isEditMode)
			{
				return;
			}

			this.closePopup();
		},
		showEditPopup(): void
		{
			this.isEditMode = true;
			this.showPopup();
		},
		closeEditPopup(): void
		{
			if (!this.isEditMode)
			{
				return;
			}

			this.closePopup();
		},
		showPopup(): void
		{
			this.isPopupShown = true;
			this.$emit('popupShown');
		},
		closePopup(): void
		{
			this.isPopupShown = false;
			this.$emit('popupClosed');
		},
		saveBookingNote({ note }: NotePopupSavePayload): void
		{
			this.$emit('updateNote', {
				id: this.id,
				note,
			});
		},
	},
	components: {
		NotePopup,
		Icon,
	},
	template: `
		<div
			class="booking-actions-popup__item-client-note"
			:data-element="dataElementPrefix + '-menu-note'"
			:data-has-note="hasNote"
			:class="{'--empty': !hasNote}"
			v-bind="dataAttributes"
			ref="note"
		>
			<div
				class="booking-actions-popup__item-client-note-inner"
				:data-element="dataElementPrefix + '-menu-note-add'"
				v-bind="dataAttributes"
				@mouseenter="onMouseEnter"
				@mouseleave="onMouseLeave"
				@click="() => hasNote ? showViewPopup() : showEditPopup()"
			>
				<template v-if="hasNote">
					<div
						class="booking-actions-popup__item-client-note-text"
						:data-element="dataElementPrefix + '-menu-note-text'"
						v-bind="dataAttributes"
					>
						{{ note }}
					</div>
					<div
						v-if="isFeatureEnabled"
						class="booking-actions-popup__item-client-note-edit"
						:data-element="dataElementPrefix + '-menu-note-edit'"
						v-bind="dataAttributes"
						@click="showEditPopup"
					>
						<Icon :name="IconSet.PENCIL_40"/>
					</div>
				</template>
				<template v-else>
					<Icon :name="IconSet.PLUS_20"/>
					<div class="booking-actions-popup__item-client-note-text">
						{{ loc('BB_ACTIONS_POPUP_ADD_NOTE') }}
					</div>
				</template>
			</div>
		</div>
		<NotePopup
			v-if="isPopupShown"
			:isEditMode="isEditMode && isFeatureEnabled"
			:id="id"
			:text="note"
			:bindElement="() => $refs.note"
			:dataId="dataId"
			:dataElementPrefix="dataElementPrefix"
			@close="closeEditPopup"
			@save="saveBookingNote"
		/>
	`,
};

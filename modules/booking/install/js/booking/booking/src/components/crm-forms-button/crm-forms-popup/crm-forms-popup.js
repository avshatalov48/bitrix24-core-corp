import { PopupOptions } from 'main.popup';

import { Popup } from 'booking.component.popup';
import { HelpDeskLoc } from 'booking.component.help-desk-loc';

import { CrmFormsPopupLayout } from './layout/crm-forms-popup-layout';
import { CrmFormsList } from './crm-forms-list/crm-forms-list';
import { CrmFormsToolbar } from './toolbar/crm-forms-toolbar';
import { AddCrmFormButton } from './add-crm-form-button/add-crm-form-button';
import { AllCrmFormsButton } from './all-crm-forms-button/all-crm-forms-button';

import './crm-forms-popup.css';

// @vue/component
export const CrmFormsPopup = {
	name: 'CrmFormsPopup',
	components: {
		HelpDeskLoc,
		Popup,
		CrmFormsList,
		CrmFormsPopupLayout,
		CrmFormsToolbar,
		AddCrmFormButton,
		AllCrmFormsButton,
	},
	props: {
		visible: {
			type: Boolean,
			default: false,
		},
		bindElement: {
			type: HTMLElement,
			required: true,
		},
	},
	emits: ['update:visible'],
	computed: {
		config(): PopupOptions
		{
			return {
				className: 'booking--booking--crm-forms-popup',
				bindElement: this.bindElement,
				offsetTop: 10,
				padding: 24,
				minHeight: 376,
				minWidth: 488,
				maxWidth: 500,
				bindOptions: {
					forceBindPosition: true,
					position: 'bottom',
				},
				angle: {
					offset: this.bindElement.offsetWidth / 2,
				},
			};
		},
	},
	methods: {
		closePopup(): void
		{
			this.$emit('update:visible', false);
		},
	},
	template: `
		<Popup
			ref="popup"
			id="booking-crm-forms-popup"
			:config
			@close="closePopup"
		>
			<CrmFormsPopupLayout>
				<template #toolbar>
					<CrmFormsToolbar />
				</template>
				<CrmFormsList />
				<template #footer>
					<div class="booking--booking--crm-forms-popup--footer-buttons-bar">
						<AllCrmFormsButton />
						<AddCrmFormButton />
					</div>
				</template>
			</CrmFormsPopupLayout>
		</Popup>
	`,
};

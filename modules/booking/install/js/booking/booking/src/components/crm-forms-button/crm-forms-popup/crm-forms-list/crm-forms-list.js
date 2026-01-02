import { Model } from 'booking.const';
import type { FormsMenuItem } from 'booking.model.forms-menu';

import { CrmFormListEmpty } from './crm-form-list-empty/crm-form-list-empty';
import { CrmFormsListItem } from './crm-forms-list-item/crm-forms-list-item';

import './crm-forms-list.css';

// @vue/component
export const CrmFormsList = {
	name: 'CrmFormsList',
	components: {
		CrmFormListEmpty,
		CrmFormsListItem,
	},
	computed: {
		canEdit(): boolean
		{
			return this.$store.state[Model.FormsMenu].canEdit;
		},
		formList(): FormsMenuItem[]
		{
			return this.$store.getters[`${Model.FormsMenu}/formList`];
		},
	},
	template: `
		<div class="booking--booking--crm-forms-popup--list__container">
			<div class="booking--booking--crm-forms-popup--list-items">
				<template v-if="formList.length === 0">
					<CrmFormListEmpty />
				</template>
				<template v-else>
					<CrmFormsListItem
						v-for="item in formList"
						:key="item.id"
						:item="item"
						:canEdit
					/>
				</template>
			</div>
		</div>
	`,
};

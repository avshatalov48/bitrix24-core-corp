import { BitrixVueComponentProps } from 'ui.vue3';
import { type MenuOptions } from 'main.popup';

import {
	Field,
	FieldTitle,
	FieldValue,
	FieldValueList,
	ValueEllipsis,
} from '../../layout/index';

import { ShowMoreMenu } from '../../show-more/show-more-menu';

declare type Phone = {
	value: string,
	href: string,
	onclick: string,
};

const SHOWABLE_PHONES_LIMIT = 2;

import './phone-field.css';

export const PhoneField: BitrixVueComponentProps = {
	name: 'PhoneField',

	components: {
		Field,
		FieldTitle,
		FieldValue,
		FieldValueList,
		ValueEllipsis,
		ShowMoreMenu,
	},

	props: {
		title: {
			type: String,
			required: true,
		},
		/** @type Phone[] */
		phones: {
			type: Array,
			required: true,
		},
	},

	computed: {
		showablePhones(): Phone[]
		{
			return this.phones.slice(0, SHOWABLE_PHONES_LIMIT);
		},
		hiddenPhones(): Phone[]
		{
			return this.phones.slice(SHOWABLE_PHONES_LIMIT);
		},
		hiddenPhoneItems(): MenuOptions[]
		{
			return this.hiddenPhones.map((phone: Phone): MenuOptions => {
				return {
					text: phone.value,
					href: phone.href,
					onclick: phone.onclick,
				};
			});
		},
	},

	template: `
		<Field class="--phone">
			<FieldTitle>{{ title }}</FieldTitle>
			<FieldValueList>
				<FieldValue v-for="phone in showablePhones">
					<a class="--value-link" :href="phone.href" :onclick="phone.onclick">
						<ValueEllipsis :title="phone.value">{{ phone.value }}</ValueEllipsis>
					</a>
				</FieldValue>
				<ShowMoreMenu
					v-if="hiddenPhones.length > 0"
					:items="hiddenPhoneItems" 
				/>
			</FieldValueList>
		</Field>
	`,
};

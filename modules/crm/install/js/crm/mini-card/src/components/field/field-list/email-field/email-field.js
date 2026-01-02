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
import { ServiceLocator } from '../../../../lib/service-locator';

declare type Email = {
	value: String,
	ownerTypeId: number,
	ownerId: number,
};

const SHOWABLE_EMAILS_LIMIT = 2;

import './email-field.css';

export const EmailField: BitrixVueComponentProps = {
	name: 'EmailField',
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
		emails: {
			/** @type Email[] */
			type: Array,
			required: true,
		},
	},
	computed: {
		showableEmails(): Email[]
		{
			return this.emails.slice(0, SHOWABLE_EMAILS_LIMIT);
		},
		hiddenEmails(): Email[]
		{
			return this.emails.slice(SHOWABLE_EMAILS_LIMIT);
		},
		hiddenEmailItems(): MenuOptions[]
		{
			return this.hiddenEmails.map((email: Email) => {
				return {
					text: email.value,
					onclick: () => {
						this.addEmail(email);
					},
				};
			});
		},
	},
	methods: {
		addEmail(email: Email): void
		{
			void ServiceLocator
				.getInstance()
				.getCommunicationService()
				.communicateByEmail({
					email: email.value,
					ownerTypeId: email.ownerTypeId,
					ownerId: email.ownerId,
					entityTypeId: email.ownerTypeId,
					entityId: email.ownerId,
					activityEditorContainer: this.$refs.activityEditorContainer,
				})
			;
		},
	},
	template: `
		<Field class="--email">
			<FieldTitle>{{ title }}</FieldTitle>
			<FieldValueList>
				<FieldValue v-for="email in showableEmails">
					<a class="--value-link" @click="addEmail(email)">
						<ValueEllipsis :title="email.value">{{ email.value }}</ValueEllipsis>
					</a>
				</FieldValue>
				<ShowMoreMenu
					v-if="hiddenEmails.length > 0"
					:items="hiddenEmailItems"
				/>
			</FieldValueList>
			<div ref="activityEditorContainer" hidden></div>
		</Field>
	`,
};

import { BitrixVueComponentProps } from 'ui.vue3';

import {
	Field,
	FieldValue,
	FieldValueList,
	FieldTitle,
	ValueEllipsis,
} from '../../layout/index';

import { CommunicationControl } from './components/communication-control';

declare type Entity = {
	entityTypeId: number,
	entityId: number,
	ownerTypeId: number,
	ownerId: number,
};

declare type Client = {
	fullName: string,
	openUrl: string,
	communications: Array,
	entity: Entity,
};

import './client-field.css';

export const ClientField: BitrixVueComponentProps = {
	name: 'ClientField',

	components: {
		Field,
		FieldTitle,
		FieldValue,
		FieldValueList,
		ValueEllipsis,

		CommunicationControl,
	},

	props: {
		title: {
			type: String,
			required: true,
		},
		clients: {
			/** @type Array<Client> */
			type: Array,
			required: true,
		},
	},

	computed: {
		clientsWithOpenUrlFirst(): Client[]
		{
			return this.clients.sort((client: Client) => {
				if (client.openUrl === null)
				{
					return 1;
				}

				return 0;
			});
		},
	},

	template: `
		<Field class="--client">
			<FieldTitle>{{ title }}</FieldTitle>
			<FieldValueList>
				<FieldValue v-for="client in clientsWithOpenUrlFirst">
					<ValueEllipsis
						v-if="client.openUrl === null"
						:title="client.fullName"
					>
						{{ client.fullName }}
					</ValueEllipsis>
					<a v-else class="--value-link" :href="client.openUrl">
						<ValueEllipsis :title="client.fullName">{{ client.fullName }}</ValueEllipsis>
					</a>

					<CommunicationControl
						v-if="client.entity !== null"
						:communications="client.communications"
						:entity="client.entity"
					/>
				</FieldValue>
			</FieldValueList>
		</Field>
	`,
};

import { Store, BuilderModel } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';

import { CrmEntity, Model, Module } from 'booking.const';
import type { ClientData, ClientModel, ClientsState } from './types';

export class Clients extends BuilderModel
{
	getName(): string
	{
		return Model.Clients;
	}

	getState(): ClientsState
	{
		return {
			providerModuleId: null,
			contactCollection: {},
			companyCollection: {},
		};
	}

	getElementState(): ClientModel
	{
		return {
			id: 0,
			name: '',
			type: {
				module: Module.Crm,
				code: CrmEntity.Contact,
			},
			contactId: null,
			phones: [],
			emails: [],
		};
	}

	getGetters(): GetterTree
	{
		return {
			/** @function clients/providerModuleId */
			providerModuleId: (state: ClientsState): ClientModel[] => state.providerModuleId,
			/** @function clients/getContacts */
			getContacts: (state: ClientsState): ClientModel[] => Object.values(state.contactCollection),
			/** @function clients/getCompanies */
			getCompanies: (state: ClientsState): ClientModel[] => Object.values(state.companyCollection),
			/** @function clients/getByClientData */
			getByClientData: (state: ClientsState): ClientModel => (clientData: ClientData): ClientModel => {
				const contact = state.contactCollection[clientData.id];
				const company = state.companyCollection[clientData.id];

				switch (clientData.type.code)
				{
					case CrmEntity.Contact:
						return contact ? ({ ...contact }) : undefined;
					case CrmEntity.Company:
						return company ? ({ ...company }) : undefined;
					default:
						return null;
				}
			},
		};
	}

	getActions(): ActionTree
	{
		return {
			/** @function clients/setProviderModuleId */
			setProviderModuleId: (store: Store, providerModuleId: string | null): void => {
				store.commit('setProviderModuleId', providerModuleId);
			},
			/** @function clients/insertMany */
			insertMany: (store: Store, clients: ClientModel[]): void => {
				clients.forEach((client: ClientModel) => store.commit('insert', client));
			},
			/** @function clients/upsert */
			upsert: (store: Store, client: ClientModel): void => {
				store.commit('upsert', client);
			},
			/** @function clients/upsertMany */
			upsertMany: (store: Store, clients: ClientModel[]): void => {
				clients.forEach((client: ClientModel) => store.commit('upsert', client));
			},
			/** @function clients/update */
			update: (store: Store, payload: { id: number | string, client: ClientModel }): void => {
				store.commit('update', payload);
			},
		};
	}

	getMutations(): MutationTree
	{
		return {
			setProviderModuleId: (state: ClientsState, providerModuleId: string | null): void => {
				state.providerModuleId = providerModuleId;
			},
			insert: (state: ClientsState, client: ClientModel): void => {
				if (!client)
				{
					return;
				}

				if (client.type.code === CrmEntity.Contact)
				{
					state.contactCollection[client.id] ??= client;
				}

				if (client.type.code === CrmEntity.Company)
				{
					state.companyCollection[client.id] ??= client;
				}
			},
			upsert: (state: ClientsState, client: ClientModel): void => {
				if (!client)
				{
					return;
				}

				if (client.type.code === CrmEntity.Contact)
				{
					state.contactCollection[client.id] ??= client;
					Object.assign(state.contactCollection[client.id], client);
				}

				if (client.type.code === CrmEntity.Company)
				{
					state.companyCollection[client.id] ??= client;
					Object.assign(state.companyCollection[client.id], client);
				}
			},
			update: (state: ClientsState, { id, client }: { id: number | string, client: ClientModel }): void => {
				if (client.type.code === CrmEntity.Contact)
				{
					const updatedClient = { ...state.contactCollection[id], ...client };
					delete state.contactCollection[id];
					state.contactCollection[client.id] = updatedClient;
				}

				if (client.type.code === CrmEntity.Company)
				{
					const updatedClient = { ...state.companyCollection[id], ...client };
					delete state.companyCollection[id];
					state.companyCollection[client.id] = updatedClient;
				}
			},
		};
	}
}

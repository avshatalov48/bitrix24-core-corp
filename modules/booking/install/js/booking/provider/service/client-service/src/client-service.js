import { Tag, Type } from 'main.core';
import { CrmEntity, Module } from 'booking.const';
import { Core } from 'booking.core';
import type { ClientData, ClientModel } from 'booking.model.clients';

type RestMethods = {
	[methodName: string]: { method: string, params: any } | [string, any],
};

const VALUE_TYPE = 'WORK';

const MethodName = Object.freeze({
	AddFormattedName: 'crm.controller.integration.booking.contact.addFormattedName',
	ParseFormattedName: 'crm.controller.integration.booking.contact.parseFormattedName',
	CompanyAdd: 'crm.company.add',
	ContactAdd: 'crm.contact.add',
	CompanyGet: 'crm.company.get',
	ContactGet: 'crm.contact.get',
	GetCompanyContacts: 'crm.company.contact.items.get',
	CompanyUpdate: 'crm.company.update',
	ContactUpdate: 'crm.contact.update',
});

const RequestKey = Object.freeze({
	AddFormattedName: 'add_formatted_name',
	ParseName: 'parse_name_#id#',
	CompanyAdd: 'company_add_#id#',
	ContactAdd: 'contact_add_#id#',
	CompanyGet: 'company_get_#id#',
	ContactGet: 'contact_get_#id#',
	GetCompanyContacts: 'get_company_contacts',
	CompanyUpdate: 'company_update_#id#',
	ContactUpdate: 'contact_update_#id#',
});

class ClientService
{
	async saveMany(clients: ClientModel[]): Promise<{clients: ClientData[], error: Error}>
	{
		try
		{
			const data = await this.#requestSaveMany(clients);

			await Core.getStore().dispatch('clients/upsertMany', data);

			return {
				clients: data.map(({ id, type }: ClientModel): ClientData => ({ id, type })),
			};
		}
		catch (error)
		{
			console.error('ClientService: saveMany error', error);

			return { error };
		}
	}

	async getLinkedContactByCompany(companyData: ClientData): Promise<ClientModel | undefined>
	{
		const company = Core.getStore().getters['clients/getByClientData'](companyData);

		company.contactId ??= await this.#requestLinkedContactId(companyData);

		await Core.getStore().dispatch('clients/update', { id: company.id, client: company });

		return Core.getStore().getters['clients/getByClientData']({
			id: company.contactId,
			type: {
				module: Module.Crm,
				code: CrmEntity.Contact,
			},
		});
	}

	async #requestSaveMany(clients: ClientModel[]): Promise<ClientModel[]>
	{
		const companies = clients.filter((client) => client.type.code === CrmEntity.Company);
		const contacts = clients.filter((client) => client.type.code === CrmEntity.Contact);
		const companiesToAdd = companies.filter((client) => !client.id);
		const companiesToUpdate = companies.filter((client) => this.#isClientToUpdate(client));
		const contactsToAdd = contacts.filter((client) => !client.id);
		const contactsToUpdate = contacts.filter((client) => this.#isClientToUpdate(client));
		const clientsToRequest = [...companiesToAdd, ...companiesToUpdate, ...contactsToAdd, ...contactsToUpdate];

		clientsToRequest.forEach((client, index) => {
			client.index = index;
		});

		const restMethods = {
			...this.#getParseNameMethods([...contactsToAdd, ...contactsToUpdate]),
			...this.#getCompanyAddMethods(companiesToAdd),
			...this.#getContactAddMethods(contactsToAdd, companies),
			...this.#getCompanyGetMethods(companiesToUpdate),
			...this.#getCompanyUpdateMethods(companiesToUpdate),
			...this.#getContactGetMethods(contactsToUpdate),
			...this.#getContactUpdateMethods(contactsToUpdate),
		};

		const result = await new Promise((resolve) => {
			if (Object.keys(restMethods).length === 0)
			{
				resolve([]);
			}

			BX.rest.callBatch(restMethods, (batchResult) => resolve(batchResult));
		});

		const errors = Object.values(result)
			.map((ajaxResult) => ajaxResult.answer.error?.error_description)
			.filter((error) => error)
		;

		if (Type.isArrayFilled(errors))
		{
			throw new Error(Tag.render`<span>${errors[0]}</span>`.textContent);
		}

		companiesToAdd.forEach((client) => {
			client.id = result[this.#getRequestKey(RequestKey.CompanyAdd, client.index)].data();
		});

		contactsToAdd.forEach((client) => {
			client.id = result[this.#getRequestKey(RequestKey.ContactAdd, client.index)].data();
		});

		return clients;
	}

	#isClientToUpdate(client: ClientModel): boolean
	{
		if (!client.id)
		{
			return false;
		}

		const currentClient = Core.getStore().getters['clients/getByClientData'](client);

		return client.name !== currentClient.name
			|| client.phones[0] !== currentClient.phones[0]
			|| client.emails[0] !== currentClient.emails[0]
		;
	}

	#getParseNameMethods(contacts: ClientModel[]): RestMethods
	{
		return contacts.reduce((methods, client) => ({
			...methods,
			[this.#getRequestKey(RequestKey.ParseName, client.index)]: {
				method: MethodName.ParseFormattedName,
				params: {
					fields: {
						FORMATTED_NAME: client.name,
					},
				},
			},
		}), {});
	}

	#getCompanyAddMethods(companiesToAdd: ClientModel[]): RestMethods
	{
		return companiesToAdd.reduce((methods, client) => ({
			...methods,
			[this.#getRequestKey(RequestKey.CompanyAdd, client.index)]: {
				method: MethodName.CompanyAdd,
				params: {
					fields: {
						TITLE: client.name,
						PHONE: client.phones.map((VALUE) => ({ VALUE, VALUE_TYPE })),
						EMAIL: client.emails.map((VALUE) => ({ VALUE, VALUE_TYPE })),
					},
					params: { REGISTER_SONET_EVENT: 'Y' },
				},
			},
		}), {});
	}

	#getContactAddMethods(contactsToAdd: ClientModel[], companies: ClientModel[]): RestMethods
	{
		return contactsToAdd.reduce((methods, client) => {
			const COMPANY_ID = companies[0]?.id ?? `$result[${this.#getRequestKey(RequestKey.CompanyAdd)}]`;

			return {
				...methods,
				[this.#getRequestKey(RequestKey.ContactAdd, client.index)]: {
					method: MethodName.ContactAdd,
					params: {
						fields: {
							COMPANY_ID: companies.length > 0 ? COMPANY_ID : undefined,
							...this.#prepareContactNameFields(client.index),
							PHONE: client.phones.map((VALUE) => ({ VALUE, VALUE_TYPE })),
							EMAIL: client.emails.map((VALUE) => ({ VALUE, VALUE_TYPE })),
						},
						params: { REGISTER_SONET_EVENT: 'Y' },
					},
				},
			};
		}, {});
	}

	#getCompanyGetMethods(companies: ClientModel[]): RestMethods
	{
		return companies.reduce((methods, { id }) => ({
			...methods,
			[this.#getRequestKey(RequestKey.CompanyGet, id)]: [MethodName.CompanyGet, { id }],
		}), {});
	}

	#getContactGetMethods(contacts: ClientModel[]): RestMethods
	{
		return contacts.reduce((methods, { id }) => ({
			...methods,
			[this.#getRequestKey(RequestKey.ContactGet, id)]: [MethodName.ContactGet, { id }],
		}), {});
	}

	#getCompanyUpdateMethods(companiesToUpdate: ClientModel[]): RestMethods
	{
		return companiesToUpdate.reduce((methods, client) => ({
			...methods,
			[this.#getRequestKey(RequestKey.CompanyUpdate, client.id)]: {
				method: MethodName.CompanyUpdate,
				params: {
					id: client.id,
					fields: {
						TITLE: client.name,
						...this.#prepareCommunicationsForUpdate(client),
					},
					params: { REGISTER_SONET_EVENT: 'Y' },
				},
			},
		}), {});
	}

	#getContactUpdateMethods(contactsToUpdate: ClientModel[]): RestMethods
	{
		return contactsToUpdate.reduce((methods, client) => ({
			...methods,
			[this.#getRequestKey(RequestKey.ContactUpdate, client.id)]: {
				method: MethodName.ContactUpdate,
				params: {
					id: client.id,
					fields: {
						...this.#prepareContactNameFields(client.index),
						...this.#prepareCommunicationsForUpdate(client),
					},
					params: { REGISTER_SONET_EVENT: 'Y' },
				},
			},
		}), {});
	}

	#prepareContactNameFields(index: number): Object
	{
		return {
			NAME: `$result[${this.#getRequestKey(RequestKey.ParseName, index)}][NAME]`,
			SECOND_NAME: `$result[${this.#getRequestKey(RequestKey.ParseName, index)}][SECOND_NAME]`,
			LAST_NAME: `$result[${this.#getRequestKey(RequestKey.ParseName, index)}][LAST_NAME]`,
		};
	}

	#prepareCommunicationsForUpdate(client: ClientModel): Object
	{
		const currentClient = Core.getStore().getters['clients/getByClientData'](client);
		const requestKey = client.type.code === CrmEntity.Company
			? this.#getRequestKey(RequestKey.CompanyGet, client.id)
			: this.#getRequestKey(RequestKey.ContactGet, client.id)
		;

		const PHONE = [{
			ID: currentClient.phones[0] ? `$result[${requestKey}][PHONE][0][ID]` : undefined,
			VALUE: client.phones[0],
			VALUE_TYPE,
		}];

		const EMAIL = [{
			ID: currentClient.emails[0] ? `$result[${requestKey}][EMAIL][0][ID]` : undefined,
			VALUE: client.emails[0],
			VALUE_TYPE,
		}];

		return {
			PHONE: client.phones.length > 0 ? PHONE : undefined,
			EMAIL: client.emails.length > 0 ? EMAIL : undefined,
		};
	}

	async #requestLinkedContactId(company: ClientData): Promise<number>
	{
		try
		{
			const id = company.id;
			const client = await new Promise((resolve) => {
				BX.rest.callBatch({
					[this.#getRequestKey(RequestKey.GetCompanyContacts)]: [MethodName.GetCompanyContacts, { id }],
					[this.#getRequestKey(RequestKey.ContactGet)]: {
						method: MethodName.ContactGet,
						params: {
							id: `$result[${this.#getRequestKey(RequestKey.GetCompanyContacts)}][0][CONTACT_ID]`,
						},
					},
					[this.#getRequestKey(RequestKey.AddFormattedName)]: {
						method: MethodName.AddFormattedName,
						params: {
							fields: `$result[${this.#getRequestKey(RequestKey.ContactGet)}]`,
						},
					},
				}, (result) => {
					const data = result[this.#getRequestKey(RequestKey.AddFormattedName)].data();
					if (!data?.ID)
					{
						resolve(null);
					}

					resolve({
						id: Number(data.ID),
						name: data.FORMATTED_NAME,
						image: data.PHOTO?.showUrl,
						type: {
							module: Module.Crm,
							code: CrmEntity.Contact,
						},
						phones: data.PHONE?.map(({ VALUE }) => VALUE) ?? [],
						emails: data.EMAIL?.map(({ VALUE }) => VALUE) ?? [],
					});
				});
			});

			if (client === null)
			{
				return 0;
			}

			await Core.getStore().dispatch('clients/upsert', client);

			return client.id;
		}
		catch (error)
		{
			console.error('ClientService: loadLinkedContactByCompany error', error);

			return 0;
		}
	}

	#getRequestKey(template: string, id: number = 0): string
	{
		return template.replace('#id#', id);
	}
}

export const clientService = new ClientService();

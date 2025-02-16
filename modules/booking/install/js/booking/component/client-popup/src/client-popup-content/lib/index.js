import { Type } from 'main.core';
import { isRef, isReactive, isProxy, toRaw } from 'ui.vue3';

import { Module } from 'booking.const';
import type { ClientModel } from 'booking.model.clients';

import type { Item } from '../types';

export function getEmptyClient(item: Item, code: string): ClientModel
{
	let name = '';
	const phones: string[] = [];

	if (BX.validation.checkIfPhone(item.title))
	{
		phones.push(item.title);
	}
	else
	{
		name = item.title;
	}

	return {
		name,
		type: {
			module: Module.Crm,
			code,
		},
		phones,
		emails: [],
	};
}

export function clientToItem(client: ClientModel): Item
{
	return {
		id: client.id,
		module: client.type.module,
		type: client.type.code,
		title: client.name,
		attributes: {
			phone: client.phones.map((value: string) => ({ value })),
			email: client.emails.map((value: string) => ({ value })),
		},
	};
}

export function itemToClient(item: Item): ClientModel
{
	return {
		id: item.id,
		name: item.title,
		type: {
			module: item.module,
			code: item.type,
		},
		phones: item.attributes?.phone?.map(({ value }) => value) ?? [],
		emails: item.attributes?.email?.map(({ value }) => value) ?? [],
	};
}

export function deepToRawClientModel<T>(clientModel: T): T
{
	const modelIterator = (data: any) => {
		if (Array.isArray(data))
		{
			return data.map((item) => modelIterator(item));
		}
		if (isRef(data) || isReactive(data) || isProxy(data))
		{
			return modelIterator(toRaw(data));
		}
		if (Type.isObject(data))
		{
			return Object.keys(data).reduce((acc, key) => {
				acc[key] = modelIterator(data[key]);

				return acc;
			}, {});
		}

		return data;
	};

	return modelIterator(clientModel);
}

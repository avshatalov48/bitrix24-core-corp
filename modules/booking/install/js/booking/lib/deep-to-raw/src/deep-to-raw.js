import { Type } from 'main.core';
import { isProxy, isReactive, isRef, toRaw } from 'ui.vue3';
import type { VueRefValue } from 'ui.vue3';

export function deepToRaw<T>(refObj: VueRefValue<T> | T): T
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

	return modelIterator(refObj);
}

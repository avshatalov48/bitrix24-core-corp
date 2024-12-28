import { Type } from 'main.core';

import { convertToNumber, convertToString } from 'im.v2.model';

import type { FieldsConfig } from 'im.v2.model';

export const queueFieldsConfig: FieldsConfig = [
	{
		fieldName: ['id', 'queueId'],
		targetFieldName: 'id',
		formatFunction: convertToNumber,
	},
	{
		fieldName: ['lineName', 'name'],
		targetFieldName: 'lineName',
		checkFunction: Type.isString,
		formatFunction: convertToString,
	},
	{
		fieldName: ['type', 'queueType'],
		targetFieldName: 'type',
		checkFunction: Type.isString,
		formatFunction: convertToString,
	},
	{
		fieldName: ['isActive'],
		targetFieldName: 'isActive',
		checkFunction: Type.isBoolean,
	},
];

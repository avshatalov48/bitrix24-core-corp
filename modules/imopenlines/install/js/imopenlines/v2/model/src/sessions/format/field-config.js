import { Type } from 'main.core';

import { convertToNumber, convertToString } from 'im.v2.model';

import type { FieldsConfig } from 'im.v2.model';

export const sessionsFieldsConfig: FieldsConfig = [
	{
		fieldName: ['id', 'sessionId'],
		targetFieldName: 'id',
		checkFunction: Type.isNumber,
		formatFunction: convertToNumber,
	},
	{
		fieldName: 'chatId',
		targetFieldName: 'chatId',
		checkFunction: Type.isNumber,
		formatFunction: convertToNumber,
	},
	{
		fieldName: 'operatorId',
		targetFieldName: 'operatorId',
		checkFunction: Type.isNumber,
		formatFunction: convertToNumber,
	},
	{
		fieldName: 'status',
		targetFieldName: 'status',
		checkFunction: Type.isString,
		formatFunction: convertToString,
	},
	{
		fieldName: 'queueId',
		targetFieldName: 'queueId',
		checkFunction: Type.isNumber,
		formatFunction: convertToNumber,
	},
	{
		fieldName: 'pinned',
		targetFieldName: 'pinned',
		checkFunction: Type.isBoolean,
	},
	{
		fieldName: 'isClosed',
		targetFieldName: 'isClosed',
		checkFunction: Type.isBoolean,
	},
];

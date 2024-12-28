import { Type } from 'main.core';

import { convertToNumber, convertToString, isNumberOrString, prepareDraft } from 'im.v2.model';

import type { FieldsConfig } from 'im.v2.model';

export const recentFieldsConfig: FieldsConfig = [
	{
		fieldName: ['id', 'dialogId'],
		targetFieldName: 'dialogId',
		checkFunction: isNumberOrString,
		formatFunction: convertToString,
	},
	{
		fieldName: ['chatId'],
		targetFieldName: 'chatId',
		checkFunction: Type.isNumber,
		formatFunction: convertToNumber,
	},
	{
		fieldName: 'messageId',
		targetFieldName: 'messageId',
		checkFunction: isNumberOrString,
	},
	{
		fieldName: 'sessionId',
		targetFieldName: 'sessionId',
		checkFunction: Type.isNumber,
		formatFunction: convertToNumber,
	},
	{
		fieldName: 'draft',
		targetFieldName: 'draft',
		checkFunction: Type.isPlainObject,
		formatFunction: prepareDraft,
	},
	{
		fieldName: 'unread',
		targetFieldName: 'unread',
		checkFunction: Type.isBoolean,
	},
	{
		fieldName: 'pinned',
		targetFieldName: 'pinned',
		checkFunction: Type.isBoolean,
	},
	{
		fieldName: 'liked',
		targetFieldName: 'liked',
		checkFunction: Type.isBoolean,
	},
];

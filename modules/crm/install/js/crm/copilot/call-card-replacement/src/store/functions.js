import { Type } from 'main.core';

export function correctCallAssessmentIdOrNull(id: any): ?number
{
	if (Type.isNumber(id) && id > 0)
	{
		return id;
	}

	return null;
}

export function correctStringOrNull(value: any): ?string
{
	if (Type.isStringFilled(value))
	{
		return value;
	}

	return null;
}

export function prepareCallAssessment(callAssessment: Object): Object
{
	return {
		id: correctCallAssessmentIdOrNull(callAssessment?.id),
		title: correctStringOrNull(callAssessment?.title),
		prompt: correctStringOrNull(callAssessment?.prompt),
	};
}

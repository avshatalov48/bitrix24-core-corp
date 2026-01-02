import { Runtime, Text, Type } from 'main.core';

export type ShowPartnerOrderFormParams = {
	title: string | null;
	source: string;
};

export async function showPartnerOrderForm(params: ShowPartnerOrderFormParams)
{
	if (Type.isObject(params) === false)
	{
		return;
	}

	const resultParams = {
		...params,
		id: `intranet-license-partner-form-${Text.getRandom()}`,
	};

	const { PartnerForm } = await Runtime.loadExtension('ui.feedback.partnerform');

	PartnerForm.show(resultParams);
}

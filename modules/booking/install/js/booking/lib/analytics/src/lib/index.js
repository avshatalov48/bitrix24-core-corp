import { Core } from 'booking.core';
import { AnalyticsCSection, Model, Module } from 'booking.const';

export function getCSection(): $Values<typeof AnalyticsCSection>
{
	const embedItems = Core.getStore()?.state?.[Model.Interface]?.embedItems || [];
	const fromCrm = embedItems.some((item) => item.moduleId === Module.Crm);

	return fromCrm ? AnalyticsCSection.crm : AnalyticsCSection.booking;
}

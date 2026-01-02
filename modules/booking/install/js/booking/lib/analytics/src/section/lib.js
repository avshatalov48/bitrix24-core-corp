import { EntityTypeId, Module } from 'booking.const';
import type { DealData } from 'booking.model.bookings';

import type { OpenSectionCSubSection, OpenSectionCSection } from './types';

export function getOpenSectionCSection(editingBookingId: number, embedItems: DealData[] = []): OpenSectionCSection
{
	return embedItems.some(({ moduleId }) => moduleId === Module.Crm) || editingBookingId > 0
		? 'crm'
		: 'main_menu';
}

export function getOpenSectionCSubSection(editingBookingId: number, embedItems: DealData[] = []): OpenSectionCSubSection
{
	if (editingBookingId > 0)
	{
		return 'crm_business';
	}

	if (embedItems.length === 0)
	{
		return '';
	}

	const entityTypeSet = new Set(embedItems.map(({ entityTypeId }) => entityTypeId));
	const isType = (entityTypeId: $Values<typeof EntityTypeId>): boolean => entityTypeSet.has(entityTypeId);
	const isSmart = (): boolean => {
		const regExp = /^DYNAMIC_\d+$/;
		for (const entityType of entityTypeSet)
		{
			if (regExp.test(entityType))
			{
				return true;
			}
		}

		return false;
	};

	if (isType(EntityTypeId.Lead))
	{
		return 'lead';
	}

	if (isType(EntityTypeId.Deal))
	{
		return 'deal';
	}

	if (isSmart())
	{
		return 'smart';
	}

	if (isType(EntityTypeId.Company))
	{
		return 'company';
	}

	if (isType(EntityTypeId.Contact))
	{
		return 'contact';
	}

	return '';
}

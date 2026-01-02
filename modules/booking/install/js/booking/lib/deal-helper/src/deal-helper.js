import { Uri } from 'main.core';
import { SidePanel as SidePanelMain } from 'main.sidepanel';

import { CrmEntity, Module } from 'booking.const';
import type { DealData } from 'booking.model.bookings';
import type { ClientData } from 'booking.model.clients';

import type { CreateCrmDealParams } from './types';

const SidePanel = SidePanelMain || BX.SidePanel;

export class DealHelper
{
	openDealSidePanel({ deal, onClose }: { deal: DealData, onClose: (DealData | null) => void }): void
	{
		SidePanel.Instance.open(`/crm/deal/details/${deal.value}/`, {
			events: {
				onClose: () => onClose(deal),
			},
		});
	}

	createCrmDeal({
		itemId,
		itemIdQueryParamName,
		queryParams = {},
		clients,
		onLoad,
		onClose,
	}: CreateCrmDealParams): void
	{
		const createDealUrl = new Uri('/crm/deal/details/0/');
		createDealUrl.setQueryParam(itemIdQueryParamName, itemId);

		Object.keys(queryParams).forEach((queryParamsKey: string) => {
			createDealUrl.setQueryParam(queryParamsKey, queryParams[queryParamsKey]);
		});

		clients.forEach((client: ClientData) => {
			const paramName = {
				[CrmEntity.Contact]: 'contact_id',
				[CrmEntity.Company]: 'company_id',
			}[client.type.code];

			createDealUrl.setQueryParam(paramName, client.id);
		});

		SidePanel.Instance.open(createDealUrl.toString(), {
			events: {
				onLoad: ({ slider }) => {
					slider.getWindow().BX.Event.EventEmitter.subscribe('onCrmEntityCreate', (event) => {
						const [data] = event.getData();
						onLoad({
							isDeal: data.entityTypeName === CrmEntity.Deal,
							isCanceled: data.isCanceled,
							itemIdFromQuery: parseInt(new Uri(data.sliderUrl).getQueryParam(itemIdQueryParamName), 10),
							dealData: this.mapEntityInfoToDeal(data.entityInfo),
						});
					});
				},
				onClose: () => onClose(),
			},
		});
	}

	mapEntityInfoToDeal(info: Object): DealData
	{
		return {
			moduleId: Module.Crm,
			entityTypeId: info.typeName,
			value: info.id,
			data: [],
		};
	}
}

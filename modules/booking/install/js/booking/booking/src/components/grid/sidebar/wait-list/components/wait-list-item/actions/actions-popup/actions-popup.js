import { ActionsPopup, FullForm, Info } from 'booking.component.actions-popup';
import type { PopupMakerItem, PopupOptions } from 'booking.component.actions-popup';
import type { WaitListItemModel } from 'booking.model.wait-list';

import { WaitListItemClient } from './components/client';
import { WaitListItemConfirmation } from './components/confirmation';
import { WaitListItemDeal } from './components/deal';
import { WaitListItemDocument } from './components/document';
import { WaitListItemMessage } from './components/message';
import { WaitListItemRemove } from './components/remove';
import { WaitListItemVisit } from './components/visit';

const ActionsPopupActionEnum = Object.freeze({
	client: 'client',
	confirmation: 'confirmation',
	deal: 'deal',
	document: 'document',
	fullForm: 'fullForm',
	message: 'message',
	visit: 'visit',
	overbooking: 'overbooking',
	remove: 'remove',
	waitList: 'waitList',
});

// @vue/component
export const WaitListItemActionsPopup = {
	name: 'WaitListItemActionsPopup',
	components: {
		ActionsPopup,
		FullForm,
		WaitListItemClient,
		WaitListItemConfirmation,
		WaitListItemDeal,
		WaitListItemDocument,
		WaitListItemMessage,
		WaitListItemRemove,
		WaitListItemVisit,
	},
	props: {
		/**
		 * @type {WaitListItemModel}
		 */
		waitListItem: {
			type: Object,
			required: true,
		},
		bindElement: {
			type: HTMLElement,
			required: true,
		},
	},
	emits: ['close'],
	computed: {
		config(): PopupOptions
		{
			return {
				offsetTop: -100,
				offsetLeft: -500,
				className: 'booking--wait-list--wait-list-item--sticky-popup',
			};
		},
		contentStructure(): Array<PopupMakerItem | Array<PopupMakerItem>>
		{
			const waitListItemId = this.waitListItem.id;

			return [
				{
					id: ActionsPopupActionEnum.client,
					props: {
						waitListItemId,
					},
					component: WaitListItemClient,
				},
				[
					{
						id: ActionsPopupActionEnum.deal,
						props: {
							waitListItemId,
						},
						component: WaitListItemDeal,
					},
					{
						id: ActionsPopupActionEnum.document,
						props: {
							waitListItemId,
						},
						component: WaitListItemDocument,
					},
				],
				[
					{
						id: ActionsPopupActionEnum.fullForm,
						props: {
							waitListItemId,
						},
						component: FullForm,
					},
					{
						id: ActionsPopupActionEnum.info,
						class: '--shrink',
						props: {
							waitListItemId,
						},
						component: Info,
					},
				],
			];
		},
	},
	// language=Vue
	template: `
		<ActionsPopup
			:popupId="waitListItem.id"
			:bindElement="bindElement"
			:contentStructure="contentStructure"
			:popupOptions="config"
			@close="$emit('close')"
		>
			<template #footer>
				<WaitListItemRemove
					:waitListItemId="waitListItem.id"
					@close="$emit('close')"
				/>
			</template>
		</ActionsPopup>
	`,
};

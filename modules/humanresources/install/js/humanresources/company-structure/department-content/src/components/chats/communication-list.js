import { TabList } from '../base-components/list/list';
import { CommunicationListItem } from './list-item';
import { Loc } from 'main.core';
import { ListDictionary, ListDictionaryItem } from './dictionaries/list-dictionary';
import { CommunicationsTypeDict } from 'humanresources.company-structure.structure-components';

// @vue/component
export const CommunicationList = {
	name: 'communicationList',

	components: {
		TabList,
		CommunicationListItem,
	},

	props: {
		/** @type CommunicationDetailed[] */
		communications: {
			type: Array,
			required: true,
		},
		communicationsNoAccess: {
			type: Number,
			required: true,
		},
		/** @type CommunicationDetailed[] */
		filteredCommunications: {
			type: Array,
			required: true,
		},
		isTeamEntity: {
			type: Boolean,
			required: true,
		},
		canEdit: {
			type: Boolean,
			required: true,
		},
		searchQuery: {
			type: String,
			required: true,
		},
		focusedNode: {
			type: [String, Number],
			required: true,
		},
		communicationType: {
			type: String,
			required: true,
			validator: (value) => Object.values(CommunicationsTypeDict).includes(value),
		},
		dataTestId: {
			type: Object,
			required: true,
		},
	},

	data(): { dictionary: ListDictionaryItem | {} }
	{
		return {
			dictionary: {},
		};
	},

	computed:
	{
		communicationMenuItems(): Object[]
		{
			return this.canEdit ? this.dictionary.menuItems : [];
		},
		communicationsNoAccessText(): string
		{
			const phrase = (this.communications.length > 0)
				? this.dictionary.moreHiddenText
				: this.dictionary.emptyHiddenText
			;

			return this.locPlural(phrase, this.communicationsNoAccess);
		},
		emptyCommunicationTitle(): string
		{
			if (this.canEdit)
			{
				return this.loc(this.dictionary.emptyItemTitle);
			}

			return this.isTeamEntity
				? this.loc(this.dictionary.noTeamText)
				: this.loc(this.dictionary.noDepartmentText)
			;
		},
		hideEmptyCommunicationItem(): boolean
		{
			return this.searchQuery.length > 0 || this.communicationsNoAccess > 0;
		},
	},

	created(): void
	{
		this.dictionary = ListDictionary[this.communicationType] || {};
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		locPlural(phraseCode: string, count: number): string
		{
			return Loc.getMessagePlural(phraseCode, count, { '#COUNT#': count });
		},
		onActionMenuItemClick(...args): void
		{
			this.$emit('tabListAction', ...args);
		},
	},

	template: `
		<TabList
			:id='dictionary.tabListId'
			:title="loc(dictionary.listTitle)"
			:count="communications.length + communicationsNoAccess"
			:menuItems="communicationMenuItems"
			:listItems="filteredCommunications"
			:emptyItemTitle="emptyCommunicationTitle"
			emptyItemImageClass="hr-department-detail-content__chat-empty-tab-list-item_tab-icon"
			:hideEmptyItem="hideEmptyCommunicationItem"
			:withAddPermission="canEdit"
			@tabListAction="onActionMenuItemClick"
			:dataTestIds="dataTestId"
		>
			<template v-slot="{ item }">
				<CommunicationListItem :communication="item" :nodeId="focusedNode"/>
			</template>
			<template v-if="communicationsNoAccess > 0" v-slot:footer>
				<div
					class="hr-department-detail-content__tab-list_communications-hidden"
					:data-test-id="dictionary.footerDataTestId"
				>
					{{ communicationsNoAccessText }}
				</div>
			</template>
		</TabList>
	`,
};

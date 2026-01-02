import { useChartStore } from 'humanresources.company-structure.chart-store';
import {
	ManagementDialog,
	CommunicationsTypeDict,
	getCommunicationsRecentTabOptions,
} from 'humanresources.company-structure.structure-components';
import type { TabOptions } from 'ui.entity-selector';
import { ChildrenModeSelector } from './children-mode-selector';
import { DepartmentAPI } from '../../api';
import { DepartmentContentActions } from '../../actions';
import { DialogDictionary, DialogDictionaryItem } from './dictionaries/dialog-dictionary';

// @vue/component
export const LinkDialog = {
	name: 'LinkDialog',

	components: { ManagementDialog, ChildrenModeSelector },

	props: {
		/** @type CommunicationDetailed[] */
		communications: {
			type: Array,
			required: true,
		},
		communicationType: {
			type: String,
			required: true,
			validator: (value) => Object.values(CommunicationsTypeDict).includes(value),
		},
		focusedNode: {
			type: [String, Number],
			required: true,
		},
		isTeamEntity: {
			type: Boolean,
			required: true,
		},
		entityType: {
			type: String,
			required: true,
		},
		isVisible: {
			type: Boolean,
			required: true,
		},
		canAddWithChildren: {
			type: Boolean,
			required: true,
		},
	},

	emits: ['close', 'reloadList'],

	data(): { isLinkActive: boolean, addWithChildren: boolean, dictionary: DialogDictionaryItem | {} }
	{
		return {
			isLinkActive: false,
			addWithChildren: false,
			dictionary: {},
		};
	},

	computed:
	{
		dialogEntities(): Object[]
		{
			const entity = this.dictionary.getDialogEntity();
			entity.options.excludeIds = this.communications.map((item) => item.id);

			return [entity];
		},
		dialogRecentTabOptions(): TabOptions
		{
			return getCommunicationsRecentTabOptions(this.entityType, this.communicationType);
		},
		linkedIds(): string[] | number[]
		{
			return this.communications.map((item) => this.dictionary.getDialogIdFromId(item));
		},
		addDescription(): string
		{
			return this.isTeamEntity
				? this.loc(this.dictionary.description.team)
				: this.loc(this.dictionary.description.default)
			;
		},
	},

	created(): void
	{
		this.dictionary = DialogDictionary[this.communicationType] || {};
	},

	methods:
	{
		loc(phraseCode: string, replacements = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		async linkEntities(items: Array): Promise<void>
		{
			this.isLinkActive = true;
			const mappedIds = items.map((item) => this.dictionary.getIdFromDialogId(item));

			try
			{
				const nodeId = this.focusedNode;
				const addWithChildren = Number(this.addWithChildren);

				switch (this.communicationType)
				{
					case CommunicationsTypeDict.chat:
						await DepartmentAPI.saveChats(nodeId, mappedIds, [], addWithChildren);
						break;
					case CommunicationsTypeDict.channel:
						await DepartmentAPI.saveChannel(nodeId, mappedIds, [], addWithChildren);
						break;
					case CommunicationsTypeDict.collab:
						await DepartmentAPI.saveCollab(nodeId, mappedIds, [], addWithChildren);
						break;
					default:
						break;
				}

				this.$emit('reloadList');
				if (this.addWithChildren)
				{
					const store = useChartStore();
					store.updateChatsInChildrenNodes(nodeId);
				}
			}
			catch
			{ /* empty */ }
			this.addWithChildren = false;
			this.isLinkActive = false;
			this.$emit('close');
		},
	},

	template: `
		<ManagementDialog
			v-if="isVisible"
			:id="dictionary.dialogId"
			:entities="dialogEntities"
			:recentTabOptions="dialogRecentTabOptions"
			:hiddenItemsIds="linkedIds"
			:title="loc(dictionary.title)"
			:description="addDescription"
			:isActive="isLinkActive"
			@managementDialogAction="linkEntities"
			@close="$emit('close')"
			:dataTestIds="dictionary.dataTestIds"
		>
			<template v-slot:extra-subtitle>
				<ChildrenModeSelector
					:isTeamEntity="isTeamEntity"
					:dataTestId="dictionary.dataTestIds.addWithChildrenDataTestId"
					:hasPermission="canAddWithChildren"
					:text="loc(dictionary.childrenModeText)"
					@saveChildrenMode="addWithChildren = $event"
				/>
			</template>
		</ManagementDialog>
	`,
};

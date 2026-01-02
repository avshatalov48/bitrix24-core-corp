import {
	getCommunicationsRecentTabOptions,
	CommunicationsTypeDict,
	DefaultHint,
} from 'humanresources.company-structure.structure-components';
// eslint-disable-next-line no-unused-vars
import type { CommunicationDetailed } from 'humanresources.company-structure.utils';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { Item, TagSelector } from 'ui.entity-selector';
import { Set as IconSet } from 'ui.icon-set.api.core';
import { BIcon } from 'ui.icon-set.api.vue';
import { AbstractSelectorDictionary } from './selector-dictionary';
import { createSelectorDictionary } from './dictionaries/selector-dictionary-factory';

const DEFAULT_TAG_ID = 'default';

// Component for selecting chats or channels in the wizard. Consists of selector, default button, hint and warning
// @vue/component
export const CommunicationSelector = {
	name: 'communicationSelector',

	components: {
		DefaultHint,
		BIcon,
	},

	props: {
		entityId: {
			type: Number,
			required: true,
		},
		name: {
			type: String,
			required: true,
		},
		headsCreated: {
			type: Boolean,
			required: true,
		},
		hasCurrentUser: {
			type: Boolean,
			required: true,
		},
		isTeamEntity: {
			type: Boolean,
			required: true,
		},
		isEditMode: {
			type: Boolean,
			required: true,
		},
		/** @type CommunicationDetailed[] */
		initCommunications: {
			type: Array,
			required: true,
		},
		/** @type CommunicationsTypeDict.chat | ChatTypeDict.channel | ChatTypeDict.collab */
		type: {
			type: String,
			required: true,
		},
	},

	emits: ['applyData'],

	data(): Object
	{
		return {
			createDefault: false,
			permissionChecker: null,
		};
	},

	computed:
	{
		selectorDictionary(): AbstractSelectorDictionary
		{
			return createSelectorDictionary(this.type);
		},
		isChannel(): boolean
		{
			return this.type === CommunicationsTypeDict.channel;
		},
		set(): IconSet
		{
			return IconSet;
		},
		hintText(): string
		{
			return this.loc(this.selectorDictionary.getPhrase('hintText', this.isTeamEntity));
		},
		createText(): string
		{
			return this.loc(this.selectorDictionary.getPhrase('createText', this.isTeamEntity));
		},
		removeText(): string
		{
			return this.loc(this.selectorDictionary.getRemovePhrase(this.hasCurrentUser, this.isTeamEntity));
		},
		warningText(): string
		{
			return this.loc(this.selectorDictionary.getPhrase('warningText', this.isTeamEntity));
		},
		canBeEdit(): boolean
		{
			if (!this.isEditMode)
			{
				return true;
			}

			if (this.type === CommunicationsTypeDict.chat)
			{
				return this.isTeamEntity
					? this.permissionChecker.hasPermission(PermissionActions.teamChatEdit, this.entityId)
					: this.permissionChecker.hasPermission(PermissionActions.departmentChatEdit, this.entityId)
				;
			}

			if (this.type === CommunicationsTypeDict.channel)
			{
				return this.isTeamEntity
					? this.permissionChecker.hasPermission(PermissionActions.teamChannelEdit, this.entityId)
					: this.permissionChecker.hasPermission(PermissionActions.departmentChannelEdit, this.entityId)
				;
			}

			if (this.type === CommunicationsTypeDict.collab)
			{
				return this.isTeamEntity
					? this.permissionChecker.hasPermission(PermissionActions.teamCollabEdit, this.entityId)
					: this.permissionChecker.hasPermission(PermissionActions.departmentCollabEdit, this.entityId)
				;
			}

			return false;
		},
	},

	watch:
	{
		createDefault(value): void
		{
			if (value)
			{
				this.selector.getDialog().getItem({ id: DEFAULT_TAG_ID, entityId: DEFAULT_TAG_ID }).select();
			}
			else
			{
				this.selector.getDialog().getItem({ id: DEFAULT_TAG_ID, entityId: DEFAULT_TAG_ID }).deselect();
			}
		},
		name(value): void
		{
			const defaultItem = this.selector.getDialog().getItem({ id: DEFAULT_TAG_ID, entityId: DEFAULT_TAG_ID });
			defaultItem.setTitle(value);

			if (this.createDefault)
			{
				defaultItem.deselect();
				defaultItem.select();
			}
		},
		headsCreated(value): void
		{
			if (!value)
			{
				this.createDefault = false;
			}
		},
		initCommunications:
		{
			handler(payload: ChatOrChannelDetailed[]): void
			{
				payload.forEach((item) => this.initialItemsSet.add(this.selectorDictionary.getTagId(item)));
				const preselectedItems = payload.map((item) => [
					this.selectorDictionary.getEntityName(),
					this.selectorDictionary.getTagId(item),
				]);
				const { dialog } = this.selector;
				dialog.setPreselectedItems(preselectedItems);
				dialog.load();
			},
		},
	},

	created(): void
	{
		this.permissionChecker = PermissionChecker.getInstance();
		this.communications = [];
		// store initial values to control applyData method in tagSelector
		this.initialItemsSet = this.initCommunications
			.reduce((set, item) => set.add(this.selectorDictionary.getTagId(item)), new Set())
		;
		this.selector = this.getSelector();
	},

	mounted(): void
	{
		this.selector.renderTo(this.$refs['communications-selector']);
	},

	methods:
	{
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		addTag(tag): void
		{
			if (!tag.searchable)
			{
				const existingItem = this.initCommunications
					.find((item) => this.selectorDictionary.getTagId(item) === tag.id)
				;

				if (existingItem?.title)
				{
					tag.setTitle(existingItem.title);
					if (existingItem.hasAccess)
					{
						tag.setDeselectable(true);
					}
				}
			}

			this.communications.push(this.selectorDictionary.getItemId(tag));
		},
		getSelector(): TagSelector
		{
			const entity = this.selectorDictionary.getEntity();

			const options = {
				multiple: true,
				events: {
					onTagAdd: (event: BaseEvent) => {
						const { tag } = event.getData();
						if (tag.id === DEFAULT_TAG_ID)
						{
							this.createDefault = true;
						}
						else
						{
							this.addTag(tag);
						}

						if (this.initialItemsSet.has(tag.id))
						{
							this.initialItemsSet.delete(tag.id);
						}
						else
						{
							this.applyData();
						}
					},
					onTagRemove: (event: BaseEvent) => {
						const { tag } = event.getData();
						const intId = this.selectorDictionary.getItemId(tag);
						if (tag.id === DEFAULT_TAG_ID)
						{
							this.createDefault = false;
						}
						else
						{
							this.communications = this.communications.filter((item) => item !== intId);
						}
						this.applyData();
					},
				},
				locked: !this.canBeEdit,
				dialogOptions: {
					enableSearch: true,
					height: 250,
					width: 380,
					dropdownMode: true,
					events: this.selectorDictionary.getDialogEvents(this.entityId, this.isTeamEntity, this.isEditMode),
					recentTabOptions: getCommunicationsRecentTabOptions(this.entityType, this.type),
					items: [this.getDefaultItem(entity.tagOptions?.default, entity.itemOptions?.default)],
					preselectedItems: this.initCommunications.map((item) => [
						this.selectorDictionary.getEntityName(),
						this.selectorDictionary.getTagId(item),
					]),
					entities: [entity],
				},
			};

			return new TagSelector(options);
		},
		getDefaultItem(tagOptions: Object = {}, itemOptions: Object = {}): Item
		{
			return {
				id: DEFAULT_TAG_ID,
				entityId: DEFAULT_TAG_ID,
				title: this.name,
				searchable: false,
				tagOptions,
				...itemOptions,
			};
		},
		applyData(): void
		{
			this.$emit('applyData', {
				type: this.type,
				communications: this.communications,
				createDefault: this.createDefault,
			});
		},
	},

	template: `
		<div
			class="chart-wizard__chat_selector"
			ref="communications-selector"
			:data-test-id="selectorDictionary.getTestId('hr-company-structure_chart-wizard__chat-selector')"
		/>
		<div
			v-if="!createDefault"
			class="chart-wizard__bind-chat__item-checkbox_container"
		>
			<div
				@click="createDefault = headsCreated && canBeEdit"
				class="chart-wizard__bind-chat__item-create"
				:class="{ '--disabled': !headsCreated || !canBeEdit }"
				:data-test-id="selectorDictionary.getTestId('hr-company-structure_chart-wizard__make-default-chat-create')"
				v-html="createText"
			>
			</div>
			<DefaultHint :content="hintText" />
		</div>
		<div v-else class="chart-wizard__bind-chat__item-checkbox_container">
			<div class="ui-icon-set --o-circle-check"></div>
			<div
				class="chart-wizard__bind-chat__item-remove"
				:class="{ '--disabled': !headsCreated || !canBeEdit }"
				:data-test-id="selectorDictionary.getTestId('hr-company-structure_chart-wizard__make-default-chat-remove')"
			>
				{{ removeText }}
			</div>
		</div>
		<div v-if="!headsCreated" class="chart-wizard__bind-chat__item-checkbox_warning">
			<BIcon
				:name="set.WARNING"
				color="#FFA900"
				:size="16"
			></BIcon>
			<span>
				{{ warningText }}
			</span>
		</div>
	`,
};

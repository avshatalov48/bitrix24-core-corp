import { getMemberRoles } from 'humanresources.company-structure.api';
import { PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { EntityTypes, WizardApiEntityChangedDict, type CommunicationDetailed } from 'humanresources.company-structure.utils';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { CommunicationSelector } from '../communication-selector/communication-selector';
import { CommunicationsTypeDict } from 'humanresources.company-structure.structure-components';

// @vue/component
export const BindChat = {
	name: 'bindChat',

	components: { CommunicationSelector },

	props: {
		entityId: {
			type: Number,
			required: true,
		},
		heads: {
			type: Array,
			required: false,
			default: () => [],
		},
		employees: {
			type: Array,
			required: false,
			default: () => [],
		},
		employeesIds: {
			type: Array,
			required: false,
			default: () => [],
		},
		name: {
			type: String,
			required: true,
		},
		entityType: {
			type: String,
			required: true,
		},
		isEditMode: {
			type: Boolean,
			required: true,
		},
		/** @type CommunicationDetailed[] */
		initChats: {
			type: Array,
			required: true,
		},
		/** @type CommunicationDetailed[] */
		initChannels: {
			type: Array,
			required: true,
		},
		/** @type CommunicationDetailed[] */
		initCollabs: {
			type: Array,
			required: true,
		},
	},

	emits: ['applyData'],

	computed:
	{
		isCollabsAvailable(): boolean
		{
			return PermissionChecker.getInstance().isCollabsAvailable;
		},
		headsCreated(): boolean
		{
			const memberRoles = getMemberRoles(this.entityType);

			return this.heads.some((item) => item.role === memberRoles.head);
		},
		hasCurrentUser(): boolean
		{
			return this.heads.some((item) => item.id === this.userId)
				|| this.employeesIds.includes(this.userId)
				|| this.employees.some((item) => item.id === this.userId)
			;
		},
		isTeamEntity(): boolean
		{
			return this.entityType === EntityTypes.team;
		},
		hints(): string[]
		{
			if (this.isCollabsAvailable)
			{
				if (this.isTeamEntity)
				{
					return [
						this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_1_W_COLLABS'),
						this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_2_MSGVER_1'),
						this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_3_W_COLLABS'),
					];
				}

				return [
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_1_W_COLLABS'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_2_MSGVER_2'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_3_W_COLLABS'),
				];
			}

			if (this.isTeamEntity)
			{
				return [
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_1'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_2_MSGVER_1'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_3'),
				];
			}

			return [
				this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_1_MSGVER_1'),
				this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_2_MSGVER_2'),
				this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_3'),
			];
		},
		ChatTypeDict(): Record<string, string>
		{
			return CommunicationsTypeDict;
		},
		hintTitle(): string
		{
			if (this.isCollabsAvailable)
			{
				return this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_TITLE_W_COLLABS')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_TITLE_W_COLLABS')
				;
			}

			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_HINT_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_TITLE')
			;
		},
		...mapState(useChartStore, ['userId']),
	},

	watch:
	{
		initChats(value: CommunicationDetailed[]): void
		{
			this.chats = value.map((item) => item.id);
		},
		initChannels(value: CommunicationDetailed[]): void
		{
			this.channels = value.map((item) => item.id);
		},
		initCollabs(value: CommunicationDetailed[]): void
		{
			this.collabs = value.map((item) => item.id);
		},
	},

	created(): void
	{
		this.chats = this.initChats.map((item) => item.id);
		this.channels = this.initChannels.map((item) => item.id);
		this.collabs = this.initCollabs.map((item) => Number(item.id));
		this.createDefaultChat = false;
		this.createDefaultChannel = false;
		this.createDefaultCollab = false;
	},

	activated(): void
	{
		this.$emit('applyData', {
			isDepartmentDataChanged: false,
			isValid: true,
		});
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		applyData(): void
		{
			this.$emit('applyData', {
				apiEntityChanged: WizardApiEntityChangedDict.bindChats,
				chats: this.chats,
				channels: this.channels,
				collabs: this.collabs,
				createDefaultChat: this.createDefaultChat,
				createDefaultChannel: this.createDefaultChannel,
				createDefaultCollab: this.createDefaultCollab,
				isDepartmentDataChanged: true,
			});
		},
		onCommunicationSelectorChanged(data: { type: string, communications: Array, createDefault: boolean }): void
		{
			if (data.type === CommunicationsTypeDict.chat)
			{
				this.chats = data.communications;
				this.createDefaultChat = data.createDefault;
			}
			else if (data.type === CommunicationsTypeDict.channel)
			{
				this.channels = data.communications;
				this.createDefaultChannel = data.createDefault;
			}
			else
			{
				this.collabs = data.communications;
				this.createDefaultCollab = data.createDefault;
			}

			this.applyData();
		},
	},

	template: `
		<div class="chart-wizard__bind-chat">
			<div class="chart-wizard__bind-chat__item" :class="{ '--team': isTeamEntity }">
				<div v-if="!isEditMode" class="chart-wizard__bind-chat__item-hint">
					<div class="chart-wizard__bind-chat__item-hint_logo"></div>
					<div class="chart-wizard__bind-chat__item-hint_text">
						<div
							class="chart-wizard__bind-chat__item-hint_title"
							v-html="hintTitle"
						>
						</div>
						<div v-for="hint in hints"
							 class="chart-wizard__bind-chat__item-hint_text-item"
						>
							<div class="chart-wizard__bind-chat__item-hint_text-item_icon"></div>
							<span>{{ hint }}</span>
						</div>
					</div>
				</div>
				<div class="chart-wizard__bind-chat__item-options" v-if="isCollabsAvailable">
					<div class="chart-wizard__bind-chat__item-options__item-content_title">
						<div class="chart-wizard__bind-chat__item-options__item-content_title-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_TITLE') }}
						</div>
					</div>
					<span class="chart-wizard__bind-chat__item-description">
						{{
							isTeamEntity
								? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_DESCRIPTION')
								: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_DESCRIPTION')
						}}
					</span>
					<CommunicationSelector
						:entityId="entityId"
						:name="name"
						:headsCreated="headsCreated"
						:hasCurrentUser="hasCurrentUser"
						:initCommunications="initCollabs"
						:type="ChatTypeDict.collab"
						:isTeamEntity="isTeamEntity"
						:isEditMode="isEditMode"
						@applyData="onCommunicationSelectorChanged"
					/>
				</div>
				<div class="chart-wizard__bind-chat__item-options">
					<div class="chart-wizard__bind-chat__item-options__item-content_title">
						<div class="chart-wizard__bind-chat__item-options__item-content_title-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_TITLE') }}
						</div>
					</div>
					<span class="chart-wizard__bind-chat__item-description">
						{{
							isTeamEntity
								? loc(
									'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_DESCRIPTION')
								: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_DESCRIPTION')
						}}
					</span>
					<CommunicationSelector
						:entityId="entityId"
						:name="name"
						:headsCreated="headsCreated"
						:hasCurrentUser="hasCurrentUser"
						:initCommunications="initChannels"
						:type="ChatTypeDict.channel"
						:isTeamEntity="isTeamEntity"
						:isEditMode="isEditMode"
						@applyData="onCommunicationSelectorChanged"
					/>
				</div>
				<div class="chart-wizard__bind-chat__item-options">
					<div class="chart-wizard__bind-chat__item-options__item-content_title">
						<div class="chart-wizard__bind-chat__item-options__item-content_title-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHATS_TITLE') }}
						</div>
					</div>
					<span class="chart-wizard__bind-chat__item-description">
						{{
							isTeamEntity
								? loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHATS_DESCRIPTION')
								: loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHATS_DESCRIPTION')
						}}
					</span>
					<CommunicationSelector
						:entityId="entityId"
						:name="name"
						:headsCreated="headsCreated"
						:hasCurrentUser="hasCurrentUser"
						:initCommunications="initChats"
						:type="ChatTypeDict.chat"
						:isTeamEntity="isTeamEntity"
						:isEditMode="isEditMode"
						@applyData="onCommunicationSelectorChanged"
					/>
				</div>
			</div>
		</div>
	`,
};

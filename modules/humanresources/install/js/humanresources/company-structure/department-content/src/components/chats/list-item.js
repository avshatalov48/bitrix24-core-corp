import { useChartStore } from 'humanresources.company-structure.chart-store';
import { events } from 'humanresources.company-structure.org-chart';
import { EventEmitter } from 'main.core.events';
import { Messenger } from 'im.public.iframe';
import { EntityTypes, getColorCode, ChatTypes } from 'humanresources.company-structure.utils';
import { mapState } from 'ui.vue3.pinia';
import { CommunicationListItemActionButton } from './action-button';
import { Hint, ResponsiveHint } from 'humanresources.company-structure.structure-components';
import { ItemDictionary, ItemDictionaryType } from './dictionaries/item-dictionary';

// @vue/component
export const CommunicationListItem = {
	name: 'communicationListItem',

	components: { ChatListItemActionButton: CommunicationListItemActionButton, ResponsiveHint },

	directives: { hint: Hint },

	props: {
		/** @type CommunicationDetailed */
		communication: {
			type: Object,
			required: true,
		},
		nodeId: {
			type: Number,
			required: true,
		},
	},

	data(): Object
	{
		return {
			avatar: null,
		};
	},

	computed:
	{
		dictionary(): ItemDictionaryType
		{
			return ItemDictionary[this.communication.type] || ItemDictionary[ChatTypes.channel];
		},
		originalNodeName(): string
		{
			return this.departments.get(this.communication.originalNodeId)?.name;
		},
		hiddenNodeName(): string
		{
			return this.structureMap.get(this.communication.originalNodeId)?.entityType === EntityTypes.team
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_HIDDEN_TEAM')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_HIDDEN_DEPARTMENT')
			;
		},
		/**
		 * Prepare subtitle with #NAME# placeholder where #NAME# can be a vue component
		 */
		preparedIndirectSubtitle(): { beforeText: ?string, afterText: ?string }
		{
			let phrase = '';
			if (this.structureMap.get(this.communication.originalNodeId)?.entityType === EntityTypes.team)
			{
				phrase = this.loc(this.dictionary.subtitleForTeam);
			}
			else
			{
				phrase = this.loc(this.dictionary.subtitleForDepartment);
			}

			const parts = phrase.split('#NAME#');

			return {
				beforeText: parts[0] || null,
				afterText: parts[1] || null,
			};
		},
		lockHint(): string
		{
			// parent node is a team so child node must be a team too
			if (this.structureMap.get(this.communication.originalNodeId)?.entityType === EntityTypes.team)
			{
				return this.loc(this.dictionary.parentTeamHint);
			}

			// parent node is a department so we check if child node is a department too
			if (this.structureMap.get(this.nodeId)?.entityType === EntityTypes.department)
			{
				return this.loc(this.dictionary.parentDepartmentOfDepartmentHint);
			}

			// parent node is a department but child node is a team
			return this.loc(this.dictionary.parentDepartmentOfTeamHint);
		},
		...mapState(useChartStore, ['structureMap', 'departments']),
	},

	watch:
	{
		communication(): void
		{
			this.prepareAvatar();
		},
	},

	created(): void
	{
		this.prepareAvatar();
	},

	methods:
	{
		prepareAvatar(): void
		{
			if (this.communication.avatar)
			{
				this.communication.color = getColorCode('whiteBase');
			}

			const avatarOptions = {
				size: 32,
				userName: this.communication.title,
				baseColor: this.isExtranet() && !this.communication.avatar ? getColorCode('extranetColor') : this.communication.color,
				events: {
					click: () => {
						this.onChatItemClick();
					},
				},
			};

			if (this.communication.avatar)
			{
				avatarOptions.userpicPath = this.communication.avatar;
			}

			this.avatar = this.dictionary.getAvatar(avatarOptions);
		},
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onChatItemClick(): void
		{
			if (this.communication.hasAccess)
			{
				Messenger.openChat(this.communication.dialogId);
			}
		},
		isExtranet(): boolean
		{
			return this.communication.isExtranet;
		},
		locateToOriginalDepartment(): void
		{
			EventEmitter.emit(events.HR_ORG_CHART_LOCATE_TO_DEPARTMENT, { nodeId: this.communication.originalNodeId });
		},
	},

	template: `
		<div
			:key="communication.id"
			class="hr-department-detail-content__tab-list_item-wrapper --chat"
			:class="{ '--isExtranet': isExtranet() }"
			:data-test-id="dictionary.dataTestIdPrefix + communication.id"
		>
			<div
				class="hr-department-detail-content__tab-list_item-avatar-container"
				:class="{ '--not-clickable': !communication.hasAccess }"
				v-html="this.avatar.getContainer().outerHTML"
				@click="onChatItemClick"
			/>
			<div class="hr-department-detail-content__tab-list_item-text-container">
				<div class="hr-department-detail-content__tab-list_item-title-container --chat-item">
					<span
						class="hr-department-detail-content__tab-list_item-title"
						:class="{ '--not-clickable': !communication.hasAccess }"
						:data-test-id="'hr-department-content_chats-tab__list_chat-item-' + communication.id + '-title'"
						@click="onChatItemClick"
					>{{ communication.title }}</span>
					<ResponsiveHint
						v-if="communication.originalNodeId"
						:content="lockHint"
						:extraClasses="{ 'hr-department-detail-content__tab-list_hint-container': true }"
					>
						<span class="ui-icon-set --lock hr-department-detail-content__tab-list_lock-icon"></span>
					</ResponsiveHint>
				</div>
				<div v-if="communication.originalNodeId && originalNodeName"
					 class="hr-department-detail-content__tab-list_item-subtitle --chat-item">
					<span class="hr-department-detail-content__tab-list_item-subtitle_before">
						{{ preparedIndirectSubtitle.beforeText }}
					</span>
					<ResponsiveHint
						:content="originalNodeName"
						defaultClass="hr-department-detail-content__tab-list_orig-node-name"
						:checkScrollWidth="true"
						:width="null"
						@click="locateToOriginalDepartment"
					>
						{{ originalNodeName }}
					</ResponsiveHint>
					<span class="hr-department-detail-content__tab-list_item-subtitle_after">
						{{ preparedIndirectSubtitle.afterText }}
					</span>
				</div>
				<div v-else-if="communication.originalNodeId"
					 class="hr-department-detail-content__tab-list_item-subtitle --chat-item">
					<span class="hr-department-detail-content__tab-list_item-subtitle_before">
						{{ preparedIndirectSubtitle.beforeText }}
					</span>
					<span class="hr-department-detail-content__tab-list_orig-node-hidden-name">
						{{ hiddenNodeName }}
					</span>
					<span class="hr-department-detail-content__tab-list_item-subtitle_after">
						{{ preparedIndirectSubtitle.afterText }}
					</span>
				</div>
				<div v-else class="hr-department-detail-content__tab-list_item-subtitle">
					{{ communication.subtitle }}
				</div>
			</div>
			<ChatListItemActionButton
				:communication="communication"
				:nodeId="nodeId"
			/>
		</div>
	`,
};

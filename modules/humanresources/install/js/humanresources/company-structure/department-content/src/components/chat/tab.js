import './styles/tab.css';

export const ChatTab = {
	name: 'chatTab',

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
	},

	computed:
	{
		unavailableChatList(): Array<{ text: string; }>
		{
			return [
				{ text: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_TAB_CONTENT_UNAVAILABLE_CHATS_LIST_ITEM_1') },
				{ text: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_TAB_CONTENT_UNAVAILABLE_CHATS_LIST_ITEM_2') },
			];
		},
	},

	template: `
		<div class="hr-department-detail-content__tab-container --empty">
			<div class="hr-department-detail-content__tab-entity-icon --chat --default"></div>
			<div class="hr-department-detail-content__tab-entity-content">
				<div class="hr-department-detail-content__empty-tab-entity-title">
					{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_TAB_CONTENT_UNAVAILABLE_CHATS_TITLE') }}
				</div>
				<div class="hr-department-detail-content__empty-tab-entity-list">
					<div
						v-for="item in unavailableChatList"
						class="hr-department-detail-content__empty-tab-entity-item --check"
					>
						{{ item.text }}
					</div>
				</div>
			</div>
		</div>
	`,
};

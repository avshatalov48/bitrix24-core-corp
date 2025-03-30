import 'imopenlines.v2.css.tokens';

import { Extension } from 'main.core';

import { ChatSearchInput } from 'im.v2.component.search.chat-search-input';
import { ChatSearch } from 'im.v2.component.search.chat-search';
import { Layout } from 'im.v2.const';
import { RecentList } from 'imopenlines.v2.component.list.items.recent';

import './css/recent-container.css';

// @vue/component
export const RecentListContainer = {
	name: 'RecentListContainer',
	components: { RecentList, ChatSearchInput, ChatSearch },
	emits: ['selectEntity'],
	created()
	{
		const settings = Extension.getSettings('im.v2.application.messenger');
		this.$store.dispatch('queue/set', settings.get('queueConfig'));
	},
	methods:
	{
		onChatClick(dialogId: string)
		{
			this.$emit('selectEntity', { layoutName: Layout.openlinesV2.name, entityId: dialogId });
		},
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<div class="bx-imol-list-container-recent__container bx-imol-messenger__scope">
			<div class="bx-imol-list-container-recent__header_container">
				<h2 class="bx-imol-list-container-recent__header_title">{{ loc('IMOL_LIST_RECENT_CONTAINER_HEADING') }}</h2>
			</div>
			<div class="bx-imol-list-container-recent__elements_container">
				<div class="bx-imol-list-container-recent__elements">
					<RecentList @chatClick="onChatClick" />
				</div>
			</div>
		</div>
	`,
};

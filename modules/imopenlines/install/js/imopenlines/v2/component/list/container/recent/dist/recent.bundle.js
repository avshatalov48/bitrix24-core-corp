/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
this.BX.OpenLines.v2.Component = this.BX.OpenLines.v2.Component || {};
(function (exports,imopenlines_v2_css_tokens,main_core,im_v2_component_search_chatSearchInput,im_v2_component_search_chatSearch,im_v2_const,imopenlines_v2_component_list_items_recent) {
	'use strict';

	// @vue/component
	const RecentListContainer = {
	  name: 'RecentListContainer',
	  components: {
	    RecentList: imopenlines_v2_component_list_items_recent.RecentList,
	    ChatSearchInput: im_v2_component_search_chatSearchInput.ChatSearchInput,
	    ChatSearch: im_v2_component_search_chatSearch.ChatSearch
	  },
	  emits: ['selectEntity'],
	  created() {
	    const settings = main_core.Extension.getSettings('im.v2.application.messenger');
	    this.$store.dispatch('queue/set', settings.get('queueConfig'));
	  },
	  methods: {
	    onChatClick(dialogId) {
	      this.$emit('selectEntity', {
	        layoutName: im_v2_const.Layout.openlinesV2.name,
	        entityId: dialogId
	      });
	    },
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
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
	`
	};

	exports.RecentListContainer = RecentListContainer;

}((this.BX.OpenLines.v2.Component.List = this.BX.OpenLines.v2.Component.List || {}),BX.OpenLines.v2.Css,BX,BX.Messenger.v2.Component,BX.Messenger.v2.Component,BX.Messenger.v2.Const,BX.OpenLines.v2.Component.List));
//# sourceMappingURL=recent.bundle.js.map

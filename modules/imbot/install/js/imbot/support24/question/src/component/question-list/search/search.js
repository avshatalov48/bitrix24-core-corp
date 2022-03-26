import { BitrixVue } from 'ui.vue';
import { Runtime } from 'main.core'
import { SearchEvent } from './search-event';
import { Theme } from '../../../mixin/theme';
import 'ui.forms';

import './search.css';

export const Search = BitrixVue.localComponent('imbot-support24-question-component-question-list-search',{
	directives:
	{
		focus:
		{
			inserted(element, params)
			{
				element.focus();
			}
		}
	},
	mixins: [Theme],
	data: function() {
		return {
			searchQuery: '',
			scheduleSearch: Runtime.debounce(this.search, 500, this),
		}
	},
	computed:
	{
		inputClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-search-input');
		}
	},
	methods:
	{
		search()
		{
			this.$emit('search', new SearchEvent({
				data: {
					searchQuery: this.searchQuery
				},
			}));
		}
	},
	// language=Vue
	template: `
		<div class="bx-imbot-support24-question-list-search">
			<div class="ui-ctl ui-ctl-textbox ui-ctl-block ui-ctl-w100 ui-ctl-sm bx-imbot-support24-question-list-search-hover">
				<input
					class="ui-ctl-element"
					:class="inputClass"
					type="text"
					v-model="searchQuery"
					v-focus
					@input="scheduleSearch()"
					:placeholder="$Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_SEARCH')"
				>
			</div>
		</div>
	`
});
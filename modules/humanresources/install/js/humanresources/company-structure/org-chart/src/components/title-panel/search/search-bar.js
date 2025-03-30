import { useChartStore } from 'humanresources.company-structure.chart-store';
import { Event } from 'main.core';
import { Dialog } from 'ui.entity-selector';
import { BIcon, Set } from 'ui.icon-set.api.vue';
import { mapState } from 'ui.vue3.pinia';
import { sendData as analyticsSendData } from 'ui.analytics';
import { OrgChartActions } from '../../../actions';

import './style.css';

export const SearchBar = {
	components: {
		BIcon,
	},
	directives: {
		focus: {
			mounted(el)
			{
				el.focus();
			},
		},
	},
	data(): { canEditPermissions: boolean; showSearchBar: boolean; }
	{
		return {
			canEditPermissions: false,
			showSearchBar: false,
		};
	},

	created()
	{
		this.searchDialog = this.getSearchDialog();
	},

	name: 'search-bar',

	emits: [
		'locate',
	],
	computed: {
		set(): Set
		{
			return Set;
		},
		...mapState(useChartStore, ['departments']),
	},
	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		showSearchbar(): void
		{
			if (this.showSearchBar)
			{
				this.showSearchBar = false;

				return;
			}

			analyticsSendData({ tool: 'structure', category: 'structure', event: 'search' });

			this.showSearchBar = true;
		},
		hideSearchbar(): void
		{
			this.showSearchBar = false;
		},
		getSearchDialog(): Dialog
		{
			const dialog = new Dialog({
				width: 425,
				height: 320,
				multiple: false,
				entities: [
					{
						id: 'user',
						searchFields: [
							{ name: 'supertitle', type: 'string', system: true, searchable: true },
							{ name: 'position', type: 'string' },
						],
						options: {
							intranetUsersOnly: true,
							emailUsers: false,
							inviteEmployeeLink: false,
						},
					},
					{
						id: 'structure-node',
						options: {
							selectMode: 'onlyDepartments',
							flatMode: true,
							fillRecentTab: true,
						},
					},
				],
				recentTabOptions: {
					id: 'recents',
					visible: true,
				},
				dropdownMode: true,
				enableSearch: false,
				hideOnDeselect: false,
				context: 'HR_STRUCTURE',
				events: {
					'Item:onSelect': (event) => {
						const item = event.getData().item;

						if (item.entityType === 'employee')
						{
							this.$emit('locate', item.customData.get('nodeId'));
							OrgChartActions.searchUserInDepartment(item.id);
							dialog.recentItemsToSave.push(item);
							dialog.saveRecentItems();

							return;
						}

						dialog.recentItemsToSave.push(item);
						dialog.saveRecentItems();

						this.$emit('locate', item.id);
					},
					onLoad: (event) => {
						event.target.items.get('user').forEach((item) => {
							if (!item.getSubtitle())
							{
								item.setSubtitle(item.customData.get('position'));
							}
						});
					},
					'SearchTab:onLoad': (event) => {
						event.target.items.get('user').forEach((item) => {
							if (!item.getSubtitle())
							{
								item.setSubtitle(item.customData.get('position'));
							}
						});
					},
					onDestroy: () => {
						this.searchDialog = this.getSearchDialog();
					},
				},
			});

			return dialog;
		},

		onEnter(): void
		{
			if (this.$refs.searchName)
			{
				this.searchDialog.setTargetNode(this.$refs.searchName);
				if (!this.searchDialog.isOpen())
				{
					this.searchDialog.show();
				}
				Event.bind(window, 'mousedown', this.handleClickOutside);
			}
		},
		handleClickOutside(event): void
		{
			if (this.$refs.searchName
				&& !this.$refs.searchName.parentElement.contains(event.target) && !this.searchDialog.isOpen())
			{
				this.clearSearch();
				this.hideSearchbar();
				Event.unbind(window, 'mousedown', this.handleClickOutside);
			}
		},
		search(value: string): void
		{
			if (!this.searchDialog.isOpen())
			{
				this.searchDialog.show();
			}

			this.searchDialog.search(value);
		},
		clearSearch()
		{
			this.searchDialog.getSearchTab().clearResults();
			this.searchDialog.selectTab('recents');

			if (this.$refs.searchName)
			{
				this.$refs.searchName.value = '';
				this.$refs.searchName.focus();
			}
		},
	},

	watch: {
		departments: {
			handler(): void
			{
				this.searchDialog.destroy();
			},
			deep: true,
		},
	},

	template: `
		<div
		    class="humanresources-title-panel-search-bar-container"
		    :class="{'--opened': showSearchBar}"
		>
		  <div
		      class="humanresources-title-panel-search-bar-block__search"
		      @click="showSearchbar"
		  >
		    <BIcon :name="set.SEARCH_2" :size="24" class="hr-title-search-icon"></BIcon>
		  </div>
		  <transition name="humanresources-title-panel-search-bar-fade" mode="in-out" @after-enter="onEnter">
		    <div v-if="showSearchBar"
		         class="humanresources-title-panel-search-bar-block__search-bar"
		    >
		      <input
		          ref="searchName"
		          key="searchInput"
		          type="text"
		          :placeholder="loc('HUMANRESOURCES_SEARCH_PLACEHOLDER')"
		          v-focus
		          class="humanresources-title-panel-search-bar-block__search-input"
		          @click="onEnter"
		          @input="search($event.target.value)"
		      >
		      <div
		          key="searchReset"
		          @click="clearSearch"
		          class="humanresources-title-panel-search-bar-block__search-reset"
		      >
		        <div class="humanresources-title-panel-search-bar-block__search-cursor"></div>
		        <BIcon
		            :name="set.CROSS_CIRCLE_50"
		            :size="24"
		            color="#2FC6F6"
		        ></BIcon>
		      </div>
		    </div>
		  </transition>
		</div>
	`,
};

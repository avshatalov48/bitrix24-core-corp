import { AddButton } from './add-button/add-button.js';
import { SearchBar } from './search/search-bar.js';
import { BurgerMenuButton } from './burger-menu-button/burger-menu-button.js';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { AnalyticsSourceType } from 'humanresources.company-structure.api';
import { UI } from 'ui.notification';
import { Dom } from 'main.core';
import { BIcon, Set } from 'ui.icon-set.api.vue';

import './style.css';

export const TitlePanel = {
	components: {
		AddButton,
		BurgerMenuButton,
		SearchBar,
		BIcon,
		Set,
	},

	data(): Object
	{
		return {
			canEditPermissions: false,
			canAddNode: false,
			toolbarStarActive: false,
			isHovered: false,
		};
	},

	created(): void
	{
		this.toolbarStarElement = document.getElementById('uiToolbarStar');
	},

	mounted()
	{
		try
		{
			const permissionChecker = PermissionChecker.getInstance();
			this.canEditPermissions = permissionChecker
				&& permissionChecker.hasPermissionOfAction(PermissionActions.accessEdit);
			this.canAddNode = permissionChecker
				&& permissionChecker.hasPermissionOfAction(PermissionActions.departmentCreate);
		}
		catch (error)
		{
			console.error('Failed to fetch data:', error);
		}

		const observer = new MutationObserver(() => {
			this.toolbarStarActive = Dom.hasClass(this.toolbarStarElement, 'ui-toolbar-star-active');
		});

		observer.observe(this.toolbarStarElement, { attributes: true, attributeFilter: ['class'] });

		this.toolbarStarActive = Dom.hasClass(this.toolbarStarElement, 'ui-toolbar-star-active');
	},

	name: 'title-panel',

	emits: ['showWizard', 'locate'],

	computed: {
		set(): Set
		{
			return Set;
		},
		toolbarStarIcon(): string
		{
			return this.toolbarStarActive ? this.set.FAVORITE_1 : this.set.FAVORITE_0;
		},
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		addDepartment(): void
		{
			this.$emit('showWizard', { source: AnalyticsSourceType.HEADER });
		},
		onLocate(nodeId: number)
		{
			this.$emit('locate', nodeId);
		},
		triggerFavoriteStar()
		{
			this.toolbarStarElement.click();

			UI.Notification.Center.notify({
				content: this.toolbarStarActive
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_LEFT_MENU_UN_SAVED')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_LEFT_MENU_SAVED'),
				autoHideDelay: 2000,
			});
		},
	},

	template: `
		<div class="humanresources-title-panel">
		  <p class="humanresources-title-panel__title">
		    {{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_TITLE') }}
		  </p>
		  <BIcon :name="isHovered ? set.FAVORITE_1 : toolbarStarIcon" :size="24" class="humanresources-title-panel__star"
		               @mouseover="isHovered = true"
		               @mouseleave="isHovered = false" @click="triggerFavoriteStar"
		  ></BIcon>
		  <div class="humanresources-title-panel__separator"></div>
		  <AddButton
			  v-if="canAddNode"
		      @addDepartment="addDepartment"
		  />
		  <div class="humanresources-title-panel__separator" v-if="canAddNode"></div>
		  <BurgerMenuButton v-if="canEditPermissions"/>
		  <div class="humanresources-title-panel__separator" v-if="canEditPermissions"></div>
		  <SearchBar @locate="onLocate"/>
		</div>
	`,
};

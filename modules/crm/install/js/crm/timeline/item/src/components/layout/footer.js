import {Menu} from './footer/menu';
import {Buttons} from './footer/buttons';
import {Type} from 'main.core';
import {Button} from './button';
import {ButtonState} from '../enums/button-state';
import {ButtonScope} from '../enums/button-scope';
import {ButtonType} from '../enums/button-type';

export const Footer = {
	components: {
		Buttons,
		Menu,
		Button,
	},
	props: {
		buttons: Object,
		menu: Object,
		additionalButtons: {
			type: Object,
			required: false,
			default: () => ({}),
		},
		maxBaseButtonsCount: {
			type: Number,
			required: false,
			default: 3,
		},

	},
	inject: [
		'isReadOnly',
	],
	computed: {
		baseButtons(): Array
		{
			return this.visibleAndSortedButtons.slice(0, this.maxBaseButtonsCount);
		},

		moreButtons(): Array
		{
			return this.visibleAndSortedButtons.slice(this.maxBaseButtonsCount);
		},


		visibleAndSortedButtons() {
			return this.visibleButtons.sort(this.buttonsSorter);
		},

		visibleAndSortedAdditionalButtons() {
			return this.visibleAdditionalButtons.sort(this.buttonsSorter);
		},

		visibleButtons(): Array
		{
			return this.buttons
				? Object.values(this.buttons).filter(this.visibleButtonsFilter)
				: [];
		},

		visibleAdditionalButtons(): Array {
			return this.additionalButtonsArray
				? Object.values(this.additionalButtonsArray).filter(this.visibleButtonsFilter)
				: [];
		},

		additionalButtonsArray() {
			return Object.entries(this.additionalButtons).map(([id, button]) => {
				return {id, type: ButtonType.ICON, ...button};
			});
		},

		hasMenu(): boolean
		{
			return this.moreButtons.length || (Type.isPlainObject(this.menu) && Object.keys(this.menu).length);
		},
	},
	methods: {
		visibleButtonsFilter(buttonItem) {
			return buttonItem.state !== ButtonState.HIDDEN
				&& buttonItem.scope !== ButtonScope.MOBILE
				&& (!this.isReadOnly || !buttonItem.hideIfReadonly);
		},

		buttonsSorter(buttonA, buttonB) {
			return buttonA?.sort - buttonB?.sort;
		}
	},
	template: `
		<div class="crm-timeline__card-action">
			<Buttons :items="baseButtons" />
			<div class="crm-timeline__card-action_menu">
				<Button
					v-for="button in visibleAndSortedAdditionalButtons"
					:key="button.id"
					v-bind="button"
					class="crm-timeline__card-action_menu-item"
				/>
				<Menu v-if="hasMenu" :buttons="moreButtons" v-bind="menu" />
			</div>
		</div>
	`
};

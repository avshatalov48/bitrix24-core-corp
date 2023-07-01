import { Menu } from './footer/menu';
import { Buttons } from './footer/buttons';
import { Type } from 'main.core';
import { Button } from './button';
import { ButtonState } from '../enums/button-state';
import { ButtonScope } from '../enums/button-scope';
import { ButtonType } from '../enums/button-type';
import { AdditionalButton } from './footer/add-button';

export const Footer = {
	components: {
		Buttons,
		Menu,
		Button,
		AdditionalButton,
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
		containerClassname(): Array {
			return [
				'crm-timeline__card-action', {
				'--no-margin-top': this.baseButtons.length < 1,
				}
			]
		},
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
			if (!Type.isPlainObject(this.buttons))
			{
				return [];
			}

			return this.buttons
				?  Object.keys(this.buttons)
					.map((id) => ({id, ...this.buttons[id]}))
					.filter(this.visibleButtonsFilter)
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
		},

		getButtonById(buttonId: string): ?Object
		{
			if (this.$refs.buttons)
			{
				const foundButton = this.$refs.buttons.getButtonById(buttonId);
				if (foundButton)
				{
					return foundButton;
				}
			}

			if (this.$refs.additionalButtons)
			{
				return this.visibleAndSortedAdditionalButtons.reduce((found, button, index) =>
				{
					if (found)
					{
						return found;
					}
					if (button.id === buttonId)
					{
						return buttons[index];
					}

					return null;
				}, null);
			}

			return null;
		},

		getMenu(): ?Object
		{
			if (this.$refs.menu)
			{
				return this.$refs.menu;
			}

			return null;
		}
	},
	template: `
		<div :class="containerClassname">
			<Buttons ref="buttons" :items="baseButtons" />
			<div class="crm-timeline__card-action_menu">
				<div
					v-for="button in visibleAndSortedAdditionalButtons"
					:key="button.id"
					class="crm-timeline__card-action_menu-item"
				>
					<additional-button
						v-bind="button"
					>
					</additional-button>
				</div>
				<Menu v-if="hasMenu" :buttons="moreButtons" v-bind="menu" ref="menu"/>
			</div>
		</div>
	`
};

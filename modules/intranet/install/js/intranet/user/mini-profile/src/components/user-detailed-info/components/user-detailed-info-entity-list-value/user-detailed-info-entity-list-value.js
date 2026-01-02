import { Type } from 'main.core';
import { RichMenuPopup } from 'ui.vue3.components.rich-menu';
import { type PopupOptions } from 'main.popup';

import { PopupPrefixId } from '../../../../user-mini-profile';
import { EntityMenuItem } from '../entity-menu-item/entity-menu-item';

import './style.css';

// @vue/component
export const UserDetailedInfoEntityListValue = {
	name: 'UserDetailedInfoEntityListValue',
	components: {
		RichMenuPopup,
		EntityMenuItem,
	},
	props: {
		items: {
			type: Array,
			required: true,
		},
		entityType: {
			type: String,
			required: false,
			default: '',
		},
	},
	emits: ['click'],

	data(): { isMenuShow: boolean }
	{
		return {
			isMenuShow: false,
		};
	},

	computed: {
		popupOptions(): PopupOptions
		{
			const formatPopupId = (type: String) => {
				return Type.isStringFilled(type) ? `${PopupPrefixId}entity-list-${type}` : undefined;
			};

			return {
				id: formatPopupId(this.entityType),
				bindElement: this.$refs.counter,
				width: 240,
				maxHeight: 270,
				autoHide: true,
			};
		},
		firstItem(): { id?: number, title?: string } | null
		{
			return this.items[0] ?? null;
		},
		isCounterShow(): boolean
		{
			return this.items.length > 1;
		},
		counterTitle(): string
		{
			return this.items.length - 1;
		},
	},

	methods: {
		openMenu(): void
		{
			this.isMenuShow = true;
		},
		onElementClick(id: ?number): void
		{
			this.$emit('click', id);
		},
	},
	template: `
		<div class="intranet-user-mini-profile__user-detailed-info__list-value" v-if="items.length" :data-test-id="'usermp_entity-list-' + entityType">
			<div class="intranet-user-mini-profile__user-detailed-info__list-value__element-container">
					<div v-if="this.$slots.default"
						class="intranet-user-mini-profile__user-detailed-info__list-value__before-element"
					>
						<slot 
							:item="firstItem"
						>
						</slot>
					</div>
				<div class="intranet-user-mini-profile__user-detailed-info__list-value__element">
					<a
						class="intranet-user-mini-profile__user-detailed-info__list-value__element-text"
						:title="firstItem.title"
						:data-test-id="'usermp_entity-' + entityType + '-item'"
						@click="onElementClick(firstItem.id)"
					>
						{{ firstItem.title }}
					</a>
					<div v-if="isCounterShow"
						 class="intranet-user-mini-profile__user-detailed-info__list-value__counter ui-counter"
						 ref="counter"
						 data-test-id="usermp_entity-counter"
						 @click="openMenu"
					>
						<div class="ui-counter-inner">
							+{{ counterTitle }}
						</div>
					</div>
				</div>
			</div>
			<RichMenuPopup v-if="isMenuShow"
				class="intranet-user-mini-profile__user-detailed-info__list-value__entity-menu"
				:popup-options="popupOptions"
				@close="isMenuShow = false"
			>
				<EntityMenuItem v-for="item in items"
					:title="item.title"
					:image="item.image"
					:data-test-id="'usermp_entity-' + entityType + '-menu-item'"
					@click="onElementClick(item.id)"
				/>
			</RichMenuPopup>
		</div>
	`,
};

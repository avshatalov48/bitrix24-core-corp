import { Text } from 'main.core';
import { Item } from 'ui.entity-selector';
import { BMenu, type MenuOptions, type MenuItemOptions } from 'ui.vue3.components.menu';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';
import 'ui.tooltip';

import { UserAvatar } from 'tasks.v2.component.elements.user-avatar';
import { EntitySelectorDialog, type ItemId } from 'tasks.v2.lib.entity-selector-dialog';
import { userService } from 'tasks.v2.provider.service.user-service';
import type { UserModel } from 'tasks.v2.model.users';
export type { ItemId };

import { Loader } from './loader';
import './user-custom-tag-selector.css';

// @vue/component
export const UserCustomTagSelector = {
	name: 'UiUserCustomTagSelector',
	components: {
		BIcon,
		UserAvatar,
		Loader,
		BMenu,
	},
	props: {
		/** @type DialogOptions */
		dialogOptions: {
			type: Object,
			required: true,
		},
		items: {
			type: Array,
			default: () => ([]),
		},
		/** @type UserModel */
		userInfo: {
			type: Object,
			default: null,
		},
		withActionMenu: {
			type: Boolean,
			default: false,
		},
		clickHandler: {
			type: Function,
			default: null,
		},
		readonly: {
			type: Boolean,
			default: false,
		},
		avatarOnly: {
			type: Boolean,
			default: false,
		},
	},
	emits: [
		'freeze',
		'unfreeze',
		'select',
	],
	setup(): Object
	{
		return {
			Outline,
		};
	},
	data(): Object
	{
		return {
			localContext: `${this.dialogOptions.context}-${this.$options.name}`,
			dialogId: `${this.dialogOptions.context}-${this.$options.name}-${Text.getRandom()}`,
			/** @type Item */
			user: null,
			previousUser: null,
			loading: true,
			isMenuShown: false,
		};
	},
	computed: {
		selected(): boolean
		{
			return Boolean(this.user);
		},
		userId(): number
		{
			return parseInt(this.user?.getId() ?? 0, 10);
		},
		userName(): string
		{
			return this.user.getTitle();
		},
		userAvatar(): string
		{
			return this.user.getAvatar();
		},
		userType(): string
		{
			return this.user.getEntityType();
		},
		userProfileLink(): string
		{
			return userService.getUrl(this.userId);
		},
		testId(): string
		{
			return `${this.localContext}-${Text.getRandom()}`;
		},
		menuOptions(): MenuOptions
		{
			return {
				id: `${this.localContext}-${Text.getRandom()}-${this.userId}`,
				bindElement: this.$refs.user,
				offsetTop: 8,
				items: this.menuItems,
				targetContainer: document.body,
			};
		},
		menuItems(): MenuItemOptions[]
		{
			return [
				{
					title: this.loc('UCTS_MENU_PROFILE'),
					icon: Outline.PERSON,
					dataset: {
						id: `MenuProfile-${this.testId}`,
					},
					onClick: () => BX.SidePanel.Instance.open(this.userProfileLink),
				},
				{
					title: this.loc('UCTS_MENU_EDIT'),
					icon: Outline.EDIT_L,
					dataset: {
						id: `MenuEdit-${this.testId}`,
					},
					onClick: () => this.showDialog(),
				},
			];
		},
	},
	watch: {
		userInfo(): void
		{
			if (this.userInfo)
			{
				this.user = this.mapUserInfoToItem(this.userInfo);
			}
		},
		items(): void
		{
			if (this.dialog?.isLoaded())
			{
				this.user = this.dialog.getItemsByIds(this.items)[0];
			}
		},
	},
	created(): void
	{
		if (this.userInfo)
		{
			this.user = this.mapUserInfoToItem(this.userInfo);

			this.$emit('select', this.mapItemToUserInfo(this.user));

			this.loading = false;
		}
		else
		{
			this.dialog ??= this.createDialog();
		}
	},
	unmounted(): void
	{
		this.dialog?.destroy();
	},
	methods: {
		freeze(): void
		{
			this.$emit('freeze');
		},
		unfreeze(): void
		{
			if (this.dialog?.isOpen())
			{
				return;
			}

			this.$emit('unfreeze');
		},
		select(): void
		{
			this.previousUser = this.user;

			const selectedUser = this.dialog?.getSelectedItems()[0];
			this.user = selectedUser || this.previousUser;

			this.$emit('select', this.mapItemToUserInfo(this.user));

			this.loading = false;
		},
		handleClick(): void
		{
			if (this.readonly)
			{
				BX.SidePanel.Instance.open(this.userProfileLink);

				return;
			}

			if (this.withActionMenu)
			{
				this.showMenu();
			}
			else
			{
				void this.showDialog();
			}
		},
		async showDialog(): Promise<void>
		{
			if (this.clickHandler && await this.clickHandler() === false)
			{
				return;
			}

			this.dialog ??= this.createDialog();
			this.dialog.selectItemsByIds(this.items);
			this.dialog.showTo(this.$refs.container);
		},
		createDialog(): EntitySelectorDialog
		{
			return new EntitySelectorDialog({
				id: this.dialogId,
				preselectedItems: this.items,
				events: {
					'Item:onSelect': this.select,
					'Item:onDeselect': this.select,
					onLoad: this.select,
					onShow: this.freeze,
					onHide: this.unfreeze,
				},
				...this.dialogOptions,
			});
		},
		showMenu(): void
		{
			this.isMenuShown = true;
		},
		mapUserInfoToItem(userInfo: UserModel): Item
		{
			return new Item({
				id: userInfo.id,
				entityId: 'user',
				title: userInfo.name,
				avatar: userInfo?.image ?? '',
				entityType: userInfo?.type ?? 'employee',
			});
		},
		mapItemToUserInfo(user: Item): UserModel
		{
			return {
				id: user.getId(),
				name: user.getTitle(),
				image: user.getAvatar(),
				type: user.getEntityType(),
			};
		},
	},
	template: `
		<div class="b24-user-selector" ref="container">
			<Loader v-if="loading"/>
			<template v-else>
				<div
					v-if="selected"
					class="b24-user-selector-user"
					:class="'--' + userType"
					@mouseover="showAction = true"
					@mouseleave="showAction = false"
				>
					<div
						ref="user"
						class="b24-user-selector-user-label"
						:data-id="'Label-' + testId"
						:bx-tooltip-user-id="userId"
						bx-tooltip-context="b24"
						@click="handleClick"
					>
						<div class="b24-user-selector-user-image">
							<UserAvatar :src="userAvatar" :type="userType"/>
						</div>
						<div v-if="!avatarOnly" class="b24-user-selector-user-name">
							{{ userName }}
						</div>
					</div>
				</div>
				<div
					v-else
					class="b24-user-selector-user-placeholder"
					:data-id="'Add-' + testId"
					@click="showDialog"
				>
					<UserAvatar/>
					<template v-if="!avatarOnly">
						{{ loc('UCTS_ADD_BTN') }}
					</template>
				</div>
			</template>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions" @close="isMenuShown = false"/>
	`,
};

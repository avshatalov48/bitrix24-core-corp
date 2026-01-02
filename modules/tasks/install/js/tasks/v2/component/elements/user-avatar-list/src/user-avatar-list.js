import type { PopupOptions } from 'main.popup';

import { Popup } from 'ui.vue3.components.popup';
import 'ui.tooltip';

import { hrefClick } from 'tasks.v2.lib.href-click';
import { userService } from 'tasks.v2.provider.service.user-service';
import { UserAvatar } from 'tasks.v2.component.elements.user-avatar';
import type { UserModel } from 'tasks.v2.model.users';

import { UserAvatarListUsers } from './user-avatar-list-users';
import './user-avatar-list.css';

// @vue/component
export const UserAvatarList = {
	name: 'UiUserAvatarList',
	components: {
		Popup,
		UserAvatar,
		UserAvatarListUsers,
	},
	props: {
		users: {
			type: Array,
			required: true,
		},
		visibleAmount: {
			type: Number,
			default: 3,
		},
	},
	data(): Object
	{
		return {
			isPopupShown: false,
		};
	},
	computed: {
		visibleUsers(): UserModel[]
		{
			return this.users.slice(0, this.visibleAmount);
		},
		popupUsers(): UserModel[]
		{
			return this.users.slice(this.visibleAmount);
		},
		count(): number
		{
			return this.users.length;
		},
		invisibleCount(): number
		{
			return this.count - this.visibleAmount;
		},
		popupOptions(): Function
		{
			return (): PopupOptions => ({
				id: 'ui-user-avatar-list-more-popup',
				bindElement: this.$refs.more,
				padding: 18,
				maxWidth: 300,
				maxHeight: window.innerHeight / 2 - 40,
				offsetTop: 8,
				offsetLeft: -18,
				targetContainer: document.body,
			});
		},
	},
	methods: {
		showListUsers(): void
		{
			this.isPopupShown = true;
		},
		handleClickFromListUsers(userId: number): void
		{
			hrefClick(userService.getUrl(userId));
		},
	},
	template: `
		<div
			ref="container"
			class="b24-user-avatar-list"
		>
			<UserAvatar
				v-for="user in visibleUsers"
				:key="user.id"
				:id="user.id"
				:src="user.image"
				:type="user.type"
				:bx-tooltip-user-id="user.id"
				bx-tooltip-context="b24"
				class="b24-user-avatar-list-item"
			/>
			<div
				v-if="count > visibleAmount"
				ref="more"
				class="b24-user-avatar-list-more"
				@click="showListUsers"
			>
				+{{ invisibleCount }}
			</div>
			<Popup
				v-if="isPopupShown"
				:options="popupOptions()"
				@close="isPopupShown = false"
			>
				<div class="b24-user-avatar-list-users --popup">
					<UserAvatarListUsers
						:users="popupUsers"
						:withCross="false"
						ref="popupUserList"
						@onClick="(userId) => handleClickFromListUsers(userId)"
					/>
				</div>
			</Popup>
		</div>
	`,
};

import { CallLoader } from 'call.component.elements';
import { UserListPopup } from 'call.component.user-list-popup';

import './css/user-list.css';

import type { JsonObject } from 'main.core';

// @vue/component
export const UserList = {
	name: 'UserList',
	components: { CallLoader, UserListPopup },
	props:
	{
		usersData: {
			type: Array,
			required: true,
		},
		loading: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data(): JsonObject
	{
		return {
			hasError: false,
			isLoadingUsers: false,
			showPopup: false,
		};
	},
	computed: {
		isLoading(): boolean
		{
			return this.loading || this.isLoadingUsers;
		},
		displayedUsers()
		{
			const maxDisplayCount = 5;
			const usersToShow = this.usersData.slice(0, maxDisplayCount);
			const remainingCount = this.usersData.length - maxDisplayCount;

			if (remainingCount > 0) {
				usersToShow.push({ remainingCount });
			}

			return usersToShow;
		},
	},
	methods:
	{
		backgroundStyle(user): {backgroundColor: string}
		{
			return {
				backgroundColor: user.color,
			};
		},
		showUsersPopup()
		{
			this.showPopup = true;
		},
		onClosePopup()
		{
			this.showPopup = false;
		}
	},
	template: `
		<div class="bx-call-user-list-component bx-call-user-list-component-scope" ref="users-popup-bind" @click="showUsersPopup">
			<template v-if="!isLoading && !hasError">
				<div  v-for="user in displayedUsers" :key="user.id || 'remaining'" class="bx-call-user-list-component__user-item">
					<img
						v-if="user.avatar"
						:src="encodeURI(user.avatar)"
						:alt="user.name"
						class="bx-call-user-list-component__user-avatar"
						draggable="false"
					/>
					<div v-else-if="user.name" class="bx-call-user-list-component__user-avatar --icon" :style="backgroundStyle(user)"></div>

					<div v-else class="bx-call-user-list-component__user-avatar --more">
						+{{ user.remainingCount }}
					</div>
				</div>
			</template>

			<UserListPopup
				v-if="showPopup"
				:bindElement="$refs['users-popup-bind']"
				:loading="isLoading"
				:usersData="usersData"
				@close="onClosePopup"
			/>
			<CallLoader v-else-if="isLoading" :isLight="true" />
		</div>
	`,
};

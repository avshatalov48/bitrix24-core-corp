import type { MenuItemOptions } from 'main.popup';
import { CopilotChatAvatar } from './copilot-chat-avatar';
import { CopilotChatHeaderMenu } from './copilot-chat-header-menu';
import { BIcon, Set } from 'ui.icon-set.api.vue';
import 'ui.icon-set.actions';

import '../css/copilot-chat-header.css';

export const CopilotChatHeader = {
	components: {
		CopilotChatAvatar,
		CopilotChatHeaderMenu,
		BIcon,
	},
	emits: ['clickOnCloseIcon'],
	props: {
		title: String,
		subtitle: String,
		avatar: String,
		useCloseIcon: {
			type: Boolean,
			required: false,
			default: false,
		},
		menu: Object,
	},
	computed: {
		closeIconProps(): { name: string, size: number } {
			return {
				name: Set.CROSS_40,
				size: 24,
			};
		},
		isMenuExists(): boolean {
			return this.menu && this.menu.items && this.menu.items.length > 0;
		},
		menuItems(): MenuItemOptions {
			return this.menu?.items ?? [];
		},
	},
	methods: {
		handleClickOnCloseIcon(): void {
			this.$emit('clickOnCloseIcon');
		},
	},
	template: `
		<div class="ai__copilot-chat-header">
			<button
				v-if="useCloseIcon"
				@click="handleClickOnCloseIcon"
				class="ai__copilot-chat-header_close-icon"
			>
				<b-icon
					v-bind="closeIconProps"
				 />
			</button>
			<div class="ai__copilot-chat-header_avatar">
				<CopilotChatAvatar
					:src="avatar"
					:alt="title"
				/>
			</div>
			<div class="ai__copilot-chat-header_info">
				<h4 class="ai__copilot-chat-header_title">
					{{ title }}
				</h4>
				<div class="ai__copilot-chat-header_subtitle">
					{{ subtitle }}
				</div>
			</div>
			<div
				v-if="isMenuExists"
				class="ai__copilot-chat-header_menu"
			>
				<copilot-chat-header-menu :menu-items="menuItems"></copilot-chat-header-menu>
			</div>
		</div>
	`,
};

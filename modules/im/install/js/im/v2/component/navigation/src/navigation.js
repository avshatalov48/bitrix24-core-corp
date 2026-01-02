import { Type } from 'main.core';

import { Logger } from 'im.v2.lib.logger';
import { MessengerSlider } from 'im.v2.lib.slider';
import { ActionByUserType, Layout, NavigationMenuItem } from 'im.v2.const';
import { DesktopApi } from 'im.v2.lib.desktop-api';
import { PhoneManager } from 'im.v2.lib.phone';
import { Feature, FeatureManager } from 'im.v2.lib.feature';
import { PermissionManager } from 'im.v2.lib.permission';

import { UserSettings } from './components/user-settings';
import { MarketApps } from './components/market-apps';

import './css/navigation.css';

import type { JsonObject } from 'main.core';
import type { NavigationMenuItemParams } from 'im.v2.lib.navigation';

type MenuItem = {
	id: string,
	text: string,
	counter?: number,
	showCondition?: () => boolean,
};

const LayoutToAction = Object.freeze({
	[Layout.market]: ActionByUserType.getMarket,
	[Layout.openlines]: ActionByUserType.getOpenlines,
	[Layout.channel]: ActionByUserType.getChannels,
});

// @vue/component
export const MessengerNavigation = {
	name: 'MessengerNavigation',
	components: { UserSettings, MarketApps },
	props: {
		currentLayoutName: {
			type: String,
			required: true,
		},
	},
	emits: ['navigationClick'],
	data(): JsonObject
	{
		return {
			needTopShadow: false,
			needBottomShadow: false,
		};
	},
	computed: {
		NavigationMenuItem: () => NavigationMenuItem,
		menuItems(): MenuItem[]
		{
			return [
				{
					id: NavigationMenuItem.chat,
					text: this.prepareNavigationText('IM_NAVIGATION_CHATS'),
					counter: this.formatCounter(this.$store.getters['counters/getTotalChatCounter']),
				},
				{
					id: NavigationMenuItem.copilot,
					text: this.prepareNavigationText('IM_NAVIGATION_COPILOT'),
					counter: this.formatCounter(this.$store.getters['counters/getTotalCopilotCounter']),
					showCondition: () => FeatureManager.isFeatureAvailable(Feature.copilotAvailable),
				},
				{
					id: NavigationMenuItem.collab,
					text: this.prepareNavigationText('IM_NAVIGATION_COLLAB'),
					counter: this.formatCounter(this.$store.getters['counters/getTotalCollabCounter']),
					showCondition: () => FeatureManager.isFeatureAvailable(Feature.collabAvailable),
				},
				{
					id: NavigationMenuItem.channel,
					text: this.prepareNavigationText('IM_NAVIGATION_CHANNELS'),
				},
				{
					id: NavigationMenuItem.openlines,
					text: this.prepareNavigationText('IM_NAVIGATION_OPENLINES'),
					counter: this.formatCounter(this.$store.getters['counters/getTotalLinesCounter']),
					showCondition: () => {
						return !this.isOptionOpenLinesV2Activated();
					},
				},
				{
					id: NavigationMenuItem.openlinesV2,
					text: this.prepareNavigationText('IM_NAVIGATION_OPENLINES'),
					counter: this.formatCounter(this.$store.getters['counters/getTotalLinesCounter']),
					showCondition: this.isOptionOpenLinesV2Activated,
				},
				{
					id: NavigationMenuItem.notification,
					text: this.prepareNavigationText('IM_NAVIGATION_NOTIFICATIONS'),
					counter: this.formatCounter(this.$store.getters['notifications/getCounter']),
					showCondition: () => !FeatureManager.isFeatureAvailable(Feature.isNotificationsStandalone),
				},
				{
					id: NavigationMenuItem.call,
					text: this.prepareNavigationText('IM_NAVIGATION_CALLS_V2'),
					showCondition: PhoneManager.getInstance().canCall.bind(PhoneManager.getInstance()),
				},
				{
					id: NavigationMenuItem.timemanager,
					text: this.prepareNavigationText('IM_NAVIGATION_TIMEMANAGER'),
					showCondition: this.isTimeManagerActive,
				},
				{
					id: NavigationMenuItem.homepage,
					text: this.prepareNavigationText('IM_NAVIGATION_MAIN_PAGE'),
					showCondition: this.isMainPageActive,
				},
				{
					id: NavigationMenuItem.market,
				},
				{
					id: NavigationMenuItem.settings,
					text: this.prepareNavigationText('IM_NAVIGATION_SETTINGS'),
				},
			];
		},
		showCloseIcon(): boolean
		{
			return !DesktopApi.isChatTab();
		},
	},
	created()
	{
		Logger.warn('Navigation created');
	},
	mounted()
	{
		const container = this.$refs.navigation;
		this.needBottomShadow = container && container.scrollTop + container.clientHeight !== container.scrollHeight;
	},
	methods:
	{
		onItemClick(item: MenuItem, event: PointerEvent)
		{
			this.$emit('navigationClick', {
				id: item.id,
				target: event.target,
			});
		},
		onMarketItemClick(item: NavigationMenuItemParams)
		{
			this.$emit('navigationClick', item);
		},
		closeSlider()
		{
			MessengerSlider.getInstance().getCurrent().close();
		},
		getMenuItemClasses(item: MenuItem): Object<string, boolean>
		{
			return {
				'--selected': item.id === this.currentLayoutName,
				'--with-counter': item.counter && item.id !== this.currentLayoutName,
			};
		},
		formatCounter(counter: number): string
		{
			if (counter === 0)
			{
				return '';
			}

			return counter > 99 ? '99+' : String(counter);
		},
		prepareNavigationText(phraseCode: string): string
		{
			return this.loc(phraseCode, {
				'#BR#': '</br>',
			});
		},
		needToShowMenuItem(item: MenuItem): boolean
		{
			if (!this.hasLayoutAccess(item))
			{
				return false;
			}

			if (!Type.isFunction(item.showCondition))
			{
				return true;
			}

			return item.showCondition() === true;
		},
		hasLayoutAccess(item: MenuItem): boolean
		{
			const action = LayoutToAction[item.id];

			return PermissionManager.getInstance().canPerformActionByUserType(action);
		},
		onScroll(event: Event & { target: HTMLElement })
		{
			const scrollPosition = Math.round(event.target.scrollTop + event.target.clientHeight);
			this.needBottomShadow = scrollPosition !== event.target.scrollHeight;

			if (event.target.scrollTop === 0)
			{
				this.needTopShadow = false;

				return;
			}

			this.needTopShadow = true;
		},
		onClickScrollDown()
		{
			this.$refs.navigation.scrollTo({
				top: this.$refs.navigation.scrollHeight,
				behavior: 'smooth',
			});
		},
		onClickScrollUp()
		{
			this.$refs.navigation.scrollTo({
				top: 0,
				behavior: 'smooth',
			});
		},
		isTimeManagerActive(): boolean
		{
			return Boolean(BX.Timeman?.Monitor?.isEnabled());
		},
		isOptionOpenLinesV2Activated(): boolean
		{
			return FeatureManager.isFeatureAvailable(Feature.openLinesV2);
		},
		isMainPageActive(): boolean
		{
			return DesktopApi.isChatWindow();
		},
		loc(phraseCode: string, replacements: {[string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
	},
	template: `
		<div class="bx-im-navigation__scope bx-im-navigation__container">
			<div v-if="needTopShadow" class="bx-im-navigation__shadow --top">
				<div class="bx-im-navigation__scroll-button" @click="onClickScrollUp"></div>
			</div>
			<div class="bx-im-navigation__top" @scroll="onScroll" ref="navigation">
				<template v-if="showCloseIcon">
					<!-- Close -->
					<div class="bx-im-navigation__close_container" @click="closeSlider">
						<div class="bx-im-navigation__close"></div>
					</div>
					<!-- Separator -->
					<div class="bx-im-navigation__separator_container">
						<div class="bx-im-navigation__close_separator"></div>
					</div>
				</template>
				<!-- Menu items -->
				<template v-for="item in menuItems">
					<MarketApps
						v-if="needToShowMenuItem(item) && item.id === NavigationMenuItem.market"
						@clickMarketItem="onMarketItemClick"
					/>
					<div
						v-else-if="needToShowMenuItem(item)"
						:key="item.id"
						:ref="item.id"
						@click="onItemClick(item, $event)"
						class="bx-im-navigation__item_container"
					>
						<div :class="getMenuItemClasses(item)" class="bx-im-navigation__item">
							<div :class="'--' + item.id" class="bx-im-navigation__item_icon"></div>
							<div class="bx-im-navigation__item_text" :title="item.text" v-html="item.text"></div>
							<div v-if="item.counter" class="bx-im-navigation__item_counter">
								<div class="bx-im-navigation__item_counter-text">
									{{ item.counter }}
								</div>
							</div>
						</div>
					</div>
				</template>
			</div>
			<div v-if="needBottomShadow" class="bx-im-navigation__shadow --bottom">
				<div class="bx-im-navigation__scroll-button --bottom" @click="onClickScrollDown"></div>
			</div>
			<!-- Avatar -->
			<div class="bx-im-navigation__user_container">
				<UserSettings />
			</div>
		</div>
	`,
};

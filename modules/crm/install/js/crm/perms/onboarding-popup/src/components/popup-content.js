import { Lottie } from 'ui.lottie';
import { BIcon as Icon } from 'ui.icon-set.api.vue';
import { Set } from 'ui.icon-set.api.core';

import 'ui.design-tokens';
import WorkflowAnimation from './workflow-animation.json';
import '../styles/popup-content.css';
import { Button, ButtonColor, ButtonSize, ButtonStyle } from 'ui.buttons';

export const PopupContent = {
	components: {
		Icon,
	},

	props: {
		popup: {
			type: Object,
			required: true,
		},
	},

	mounted(): void
	{
		Lottie.loadAnimation({
			container: this.$refs.workflowAnimation,
			renderer: 'svg',
			animationData: WorkflowAnimation,
			autoplay: true,
		});
	},

	computed: {
		title(): string
		{
			return this.loc('CRM_PERMISSIONS_ONBOARDING_POPUP_TITLE');
		},
		points(): Array
		{
			return [
				this.loc('CRM_PERMISSIONS_ONBOARDING_POPUP_POINT_1'),
				this.loc('CRM_PERMISSIONS_ONBOARDING_POPUP_POINT_2'),
				this.loc('CRM_PERMISSIONS_ONBOARDING_POPUP_POINT_3'),
			];
		},
		iconCircle(): { name: string, color: string, size: number }
		{
			return {
				name: Set.CIRCLE_CHECK,
				color: 'rgba(255, 255, 255, 0.6)',
				size: 20,
			};
		},
		startBtnClass(): Array
		{
			return [
				Button.BASE_CLASS,
				ButtonColor.SUCCESS,
				ButtonSize.MEDIUM,
			];
		},

		moreBtnClass(): Array
		{
			return [
				Button.BASE_CLASS,
				ButtonColor.LIGHT_BORDER,
				ButtonSize.MEDIUM,
				ButtonStyle.DEPEND_ON_THEME,
			];
		},
	},

	methods: {
		loc(phrase: string): ?string
		{
			return this.$Bitrix.Loc.getMessage(phrase);
		},

		close(): void
		{
			this.popup.close();
		},

		openHelpdesk(): void
		{
			this.popup.openHelpdesk();
		},
	},

	template: `
		<div class="crm-permissions-onboarding-popup__wrapper bitrix24-light-theme">
			<div class="crm-permissions-onboarding-popup__info">
				<span class="crm-permissions-onboarding-popup__title" v-html="title" />
				<div class="crm-permissions-onboarding-popup__points">
					<div class="crm-permissions-onboarding-popup__point-item" v-for="point in points">
						<Icon v-bind="iconCircle"/>
						<span class="crm-permissions-onboarding-popup__point-text">{{ point }}</span>
					</div>
				</div>
				<div class="crm-permissions-onboarding-popup__buttons">
					<button @click="close" :class="startBtnClass">
						<span class="ui-btn-text">{{ loc('CRM_PERMISSIONS_ONBOARDING_POPUP_START_BUTTON') }}</span>
					</button>
					<button @click="openHelpdesk" :class="moreBtnClass">
						<span class="ui-btn-text">{{ loc('CRM_PERMISSIONS_ONBOARDING_POPUP_MORE_BUTTON') }}</span>
					</button>
				</div>
			</div>
			<div ref="workflowAnimation" class="crm-permissions-onboarding-popup__workflow-animation"></div>
		</div>
	`,
};

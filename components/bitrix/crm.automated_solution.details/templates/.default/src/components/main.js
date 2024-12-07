import { Builder, Dictionary } from 'crm.integration.analytics';
import { Dom, Type } from 'main.core';
import { type BaseEvent, EventEmitter } from 'main.core.events';
import { sendData as sendAnalyticsData } from 'ui.analytics';
import { createSaveAnalyticsBuilder, wrapPromiseInAnalytics } from '../helpers/analytics';
import type { Error } from '../store';
import { CommonTab } from './tabs/common-tab';
import { TypesTab } from './tabs/types-tab';

import '../css/main.css';

export const Main = {
	components: {
		CommonTab,
		TypesTab,
	},
	props: {
		initialActiveTabId: String,
	},
	data(): Object
	{
		return {
			tabs: {
				// tab show flags
				common: this.initialActiveTabId === 'common',
				types: this.initialActiveTabId === 'types',
			},

			isCancelEventAlreadyRegistered: false,
		};
	},

	computed: {
		allTabIds(): string[]
		{
			return Object.keys(this.tabs);
		},
		saveButton(): HTMLElement
		{
			return document.getElementById('ui-button-panel-save');
		},
		cancelButton(): HTMLElement
		{
			return document.getElementById('ui-button-panel-cancel');
		},
		deleteButton(): ?HTMLElement
		{
			return document.getElementById('ui-button-panel-remove');
		},
		allButtons(): HTMLElement[]
		{
			const buttons = [this.saveButton, this.cancelButton];

			if (this.deleteButton)
			{
				buttons.push(this.deleteButton);
			}

			return buttons;
		},
		slider(): BX.SidePanel.Slider
		{
			return BX.SidePanel.Instance.getSliderByWindow(window);
		},

		errors(): Error[]
		{
			return this.$store.state.errors;
		},

		hasErrors(): boolean
		{
			return Type.isArrayFilled(this.errors);
		},

		// eslint-disable-next-line max-len
		analyticsBuilder(): Builder.Automation.AutomatedSolution.CreateEvent | Builder.Automation.AutomatedSolution.EditEvent
		{
			return createSaveAnalyticsBuilder(this.$store);
		},
	},

	mounted()
	{
		EventEmitter.subscribe('BX.Crm.AutomatedSolution.Details:showTab', this.showTabFromEvent);
		EventEmitter.subscribe('BX.Crm.AutomatedSolution.Details:save', this.save);
		EventEmitter.subscribe('BX.Crm.AutomatedSolution.Details:delete', this.delete);
		EventEmitter.subscribe('BX.Crm.AutomatedSolution.Details:close', this.onCloseByCancelButton);
		EventEmitter.subscribe('SidePanel.Slider:onCloseByEsc', this.onCloseByEsc);
		EventEmitter.subscribe('SidePanel.Slider:onClose', this.onClose);
	},

	beforeUnmount()
	{
		EventEmitter.unsubscribe('BX.Crm.AutomatedSolution.Details:showTab', this.showTabFromEvent);
		EventEmitter.unsubscribe('BX.Crm.AutomatedSolution.Details:save', this.save);
		EventEmitter.unsubscribe('BX.Crm.AutomatedSolution.Details:delete', this.delete);
		EventEmitter.unsubscribe('BX.Crm.AutomatedSolution.Details:close', this.onCloseByCancelButton);
		EventEmitter.unsubscribe('SidePanel.Slider:onCloseByEsc', this.onCloseByEsc);
		EventEmitter.unsubscribe('SidePanel.Slider:onClose', this.onClose);
	},

	methods: {
		showTabFromEvent(event: BaseEvent): void
		{
			const { tabId } = event.getData();

			this.showTab(tabId);
		},

		showTab(tabId: string): void
		{
			if (!this.allTabIds.includes(tabId))
			{
				throw new Error('invalid tab id');
			}

			for (const id of this.allTabIds)
			{
				this.tabs[id] = false;
			}
			this.tabs[tabId] = true;
		},

		save(): void
		{
			const builder = this.analyticsBuilder
				.setElement(Dictionary.ELEMENT_CREATE_BUTTON)
			;

			wrapPromiseInAnalytics(this.$store.dispatch('save'), builder)
				.then(() => {
					// don't register cancel event when this slider closes
					this.isCancelEventAlreadyRegistered = true;

					this.$Bitrix.Application.get().closeSliderOrRedirect();
				})
				.catch(() => {}) // errors will be displayed reactively
				.finally(() => this.unlockButtons())
			;
		},

		delete(): void
		{
			const builder = (new Builder.Automation.AutomatedSolution.DeleteEvent())
				.setId(this.$store.state.automatedSolution.id)
				.setElement(Dictionary.ELEMENT_DELETE_BUTTON)
			;

			// don't register cancel event when this slider closes.
			// we set this flag here because for some reason slider starts to close before the promise is resolved
			this.isCancelEventAlreadyRegistered = true;

			wrapPromiseInAnalytics(this.$store.dispatch('delete'), builder)
				.then(() => {
					this.$Bitrix.Application.get().closeSliderOrRedirect();
				})
				.catch(() => {
					// errors will be displayed reactively

					// okay, may be the slider won't be closed after all since we've failed
					this.isCancelEventAlreadyRegistered = false;
				})
				.finally(() => this.unlockButtons())
			;
		},

		onCloseByCancelButton(): void
		{
			if (this.isCancelEventAlreadyRegistered)
			{
				return;
			}

			this.isCancelEventAlreadyRegistered = true;

			sendAnalyticsData(
				this.analyticsBuilder
					.setElement(Dictionary.ELEMENT_CANCEL_BUTTON)
					.setStatus(Dictionary.STATUS_CANCEL)
					.buildData()
				,
			);
		},

		onCloseByEsc(event: BaseEvent<BX.SidePanel.Event[]>): void
		{
			if (this.isCancelEventAlreadyRegistered)
			{
				return;
			}

			const [sliderEvent] = event.getData();

			if (sliderEvent.getSlider() !== this.slider)
			{
				return;
			}

			this.isCancelEventAlreadyRegistered = true;

			sendAnalyticsData(
				this.analyticsBuilder
					.setElement(Dictionary.ELEMENT_ESC_BUTTON)
					.setStatus(Dictionary.STATUS_CANCEL)
					.buildData()
				,
			);
		},

		onClose(event: BaseEvent<BX.SidePanel.Event[]>): void
		{
			if (this.isCancelEventAlreadyRegistered)
			{
				return;
			}

			const [sliderEvent] = event.getData();

			if (sliderEvent.getSlider() !== this.slider)
			{
				return;
			}

			this.isCancelEventAlreadyRegistered = true;

			sendAnalyticsData(
				this.analyticsBuilder
					.setElement(null)
					.setStatus(Dictionary.STATUS_CANCEL)
					.buildData()
				,
			);
		},

		unlockButtons(): void
		{
			this.allButtons.forEach((button: HTMLElement) => {
				Dom.removeClass(button, 'ui-btn-wait');
			});
		},

		hideError(error: Error): void
		{
			this.$store.dispatch('removeError', error);
		},
	},

	template: `
		<div class="crm-automated-solution-details">
			<form class="ui-form">
				<div v-if="hasErrors" class="ui-alert ui-alert-danger">
					<template
						v-for="error in errors"
						:key="error.message"
					>
						<span class="ui-alert-message">{{error.message}}</span>
						<span class="ui-alert-close-btn" @click="hideError(error)"></span>
					</template>
				</div>
				<CommonTab v-show="tabs.common"/>
				<TypesTab v-show="tabs.types"/>
			</form>
		</div>
	`,
};

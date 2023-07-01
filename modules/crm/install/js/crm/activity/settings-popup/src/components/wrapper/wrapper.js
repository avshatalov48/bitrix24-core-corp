import { Type } from 'main.core';
import { Events } from 'crm.activity.settings-popup';

import { Section as WrapperSection } from '../section/section';
import { Calendar as SettingsPopupCalendar } from '../calendar/calendar';
import { Ping as SettingsPopupPing } from '../ping/ping';

export const Wrapper = {
	components: {
		WrapperSection,
		SettingsPopupCalendar,
		SettingsPopupPing,
	},
	props: {
		onSettingsChangeCallback: {
			type: Function,
			required: true,
		},
		onSettingsValidationCallback: {
			type: Function,
		},
		sections: {
			type: Array,
			required: true,
		},
	},

	data() {
		return {
			settings: new Map(),
		}
	},

	computed: {
		activeSettingsIds(): Object
		{
			const result = {};
			this.sections.forEach(section => {
				result[section.id] = Boolean(section.active);
			})

			return result;
		}
	},

	methods: {
		getSectionTitle(id: string): string
		{
			id = id.toUpperCase();

			const defaultCode = 'CRM_SETTINGS_POPUP_SECTION_SWITCH';
			const code = `${defaultCode}_${id}`;

			return this.$Bitrix.Loc.getMessage(code) || this.$Bitrix.Loc.getMessage(defaultCode);
		},

		getSectionToggle(showToggleSelector: boolean): boolean
		{
			if (Type.isBoolean(showToggleSelector))
			{
				return showToggleSelector
			}

			return true;
		},

		prepareSettings(): void
		{
			for (const sectionName in this.activeSettingsIds)
			{
				if (!this.activeSettingsIds[sectionName])
				{
					continue;
				}

				const section = this.sections.find(section => section.id === sectionName);
				if (!section)
				{
					continue;
				}

				const data = {
					id: section.id,
					...section.params,
				}

				this.settings.set(sectionName, data);
			}
		},

		onSettingsChange({ data }): void
		{
			if (data.id)
			{
				if (data.active)
				{
					this.settings.set(data.id, data);
				}
				else
				{
					this.settings.delete(data.id);
				}
			}

			if (this.onSettingsChangeCallback)
			{
				this.onSettingsChangeCallback(this.exportParams());
			}
		},

		onSettingsValidation({ data }): void
		{
			if (this.onSettingsChangeCallback)
			{
				this.onSettingsValidationCallback(data);
			}
		},

		exportParams(): Object
		{
			const settings = Object.fromEntries(this.settings);

			let result = {};

			for (const id in this.activeSettingsIds)
			{
				if (this.activeSettingsIds[id] && settings[id])
				{
					result[id] = settings[id];
				}
			}

			return result;
		},

		onToggleSettingsSection({ id, isActive }): void
		{
			if (this.activeSettingsIds.hasOwnProperty(id))
			{
				this.activeSettingsIds[id] = isActive;
			}
		},

		updateSettings(data: Object): void
		{
			this.sections.forEach(section => {
				if (this.$refs['section-' + section.id] && this.$refs['section-' + section.id][0])
				{
					this.$refs['section-' + section.id][0].updateSettings(data);
				}
			});
		},
	},

	mounted()
	{
		this.prepareSettings();
		this.$Bitrix.eventEmitter.subscribe(Events.EVENT_SETTINGS_CHANGE, this.onSettingsChange);
		this.$Bitrix.eventEmitter.subscribe(Events.EVENT_SETTINGS_VALIDATION, this.onSettingsValidation);
	},

	beforeUnmount()
	{
		this.$Bitrix.eventEmitter.unsubscribe(Events.EVENT_SETTINGS_CHANGE, this.onSettingsChange);
		this.$Bitrix.eventEmitter.unsubscribe(Events.EVENT_SETTINGS_VALIDATION, this.onSettingsValidation);
	},

	template: `
		<div class="crm-activity__settings-popup_body">
			<WrapperSection
				v-for="section in sections"
				:id="section.id"
				:toggle-title="getSectionTitle(section.id)"
				:toggle-enabled="activeSettingsIds[section.id]"
				:toggle-visible="getSectionToggle(section.showToggleSelector)"
				@onToggle="onToggleSettingsSection"
			>
				<component 
					v-bind:is="section.component"
					:params="section.params || {}"
					:ref="'section-' + section.id"
				></component>
			</WrapperSection>
		</div>
	`
};

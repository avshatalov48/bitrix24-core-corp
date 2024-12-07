import { SectionSelector as CalendarSectionSelector } from 'calendar.controls';
import { SectionManager } from 'calendar.sectionmanager';
import { Type } from 'main.core';
import { Dialog } from 'ui.entity-selector';
import { SidePanel } from 'ui.sidepanel';

export const SectionSelector = {
	props: {
		userId: {
			type: Number,
			required: true,
		},
		trackingUsersList: {
			type: Array,
			required: true,
		},
		sections: {
			type: Array,
			required: true,
		},
		selectedSectionId: {
			type: Number,
		},
		readOnly: {
			type: Boolean,
			required: false,
			default: false,
		},
	},

	emits: [
		'change',
	],

	methods: {
		show(): void
		{
			setTimeout(() => {
				this.getSelectorDialog().openPopup();
			}, 5);
		},

		hide(): void
		{
			this.getSelectorDialog()?.getPopup()?.close();
			this.selectorDialog = null;
		},

		isShown(): boolean
		{
			return this.isShownValue;
		},

		showCalendar(): void
		{
			SidePanel.Instance.open(
				`/company/personal/user/${this.userId}/calendar/?IFRAME=Y`,
				{
					width: 1000,
					allowChangeHistory: false,
				},
			);
		},

		getSelectorDialog(): ?Dialog
		{
			if (Type.isNil(this.selectorDialog))
			{
				this.selectorDialog = new CalendarSectionSelector({
					outerWrap: this.$refs.container,
					defaultCalendarType: 'user',
					defaultOwnerId: this.userId,
					sectionList: this.sections,
					sectionGroupList: SectionManager.getSectionGroupList({
						type: 'user',
						ownerId: this.userId,
						userId: this.userId,
						trackingUsersList: this.trackingUsersList,
					}),
					mode: 'inline',
					zIndex: 1200,
					getCurrentSection: () => {
						return this.sections.find((section) => section.ID === this.selectedSectionId);
					},
					selectCallback: (sectionValue) => {
						this.onSelectSection(sectionValue.ID);
					},
					openPopupCallback: () => {
						this.isShownValue = true;
					},
					closePopupCallback: () => {
						this.isShownValue = false;
					},
				});
			}

			return this.selectorDialog;
		},

		onSelectSection(id): void
		{
			this.$emit('change', Number(id));
		},
	},

	computed: {
		currentSectionTitle(): string
		{
			return this.sections.find((section) => section.ID === this.selectedSectionId)?.NAME ?? '';
		},
		hasSections(): boolean
		{
			return Type.isArrayFilled(this.sections);
		},
	},

	template: `
		<span v-if="hasSections && !readOnly" class="crm-activity__todo-editor-v2_block-header-data__calendar-container">
			<span @click="showCalendar">
				{{ currentSectionTitle }}
			</span>
			<span ref="container"></span>
		</span>
		<span v-else-if="hasSections && readOnly">
			<span @click="showCalendar">
				{{ currentSectionTitle }}
			</span>
		</span>
		<span v-else class="crm-activity__todo-editor-v2_block-header-data__calendar-container --skeleton"></span>
	`,
};

import { Dictionary } from 'crm.integration.analytics';
import { Router } from 'crm.router';
import { type BaseEvent } from 'main.core.events';
import { MessageBox } from 'ui.dialogs.messagebox';
import { TagSelector } from 'ui.entity-selector';
import { createSaveAnalyticsBuilder, wrapPromiseInAnalytics } from '../../helpers/analytics';
import { Card } from '../card';

export const TypesTab = {
	components: {
		Card,
	},
	// tag selector should not be reactive (recreated on store mutations)
	boundTypesTagSelector: null,
	externalTypesTagSelector: null,
	crmTypesTagSelector: null,

	computed: {
		isShowPermissionsResetAlert(): boolean
		{
			if (!this.$store.state.isPermissionsLayoutV2Enabled)
			{
				return false;
			}

			const currentTypeIds: number[] = [...this.$store.state.automatedSolution.typeIds];
			const originallyTypeIds: number[] = [...this.$store.state.automatedSolutionOrigTypeIds];

			return currentTypeIds.some((id) => !originallyTypeIds.includes(id))
				|| originallyTypeIds.some((id) => !currentTypeIds.includes(id))
			;
		},
	},

	mounted()
	{
		this.boundTypesTagSelector = new TagSelector({
			multiple: true,
			showAddButton: false,
			showCreateButton: true,
			// all preselected items go to this selector
			items: this.$store.state.automatedSolution.typeIds.map(
				(typeId: number) => {
					return {
						id: typeId,
						entityId: 'dynamic_type',
						title: this.$store.state.dynamicTypesTitles[typeId],
					};
				},
			),
			events: {
				onCreateButtonClick: this.handleCreateTypeClick,
				onTagRemove: this.removeTypeIdByTagRemoveEvent,
			},
		});
		this.boundTypesTagSelector.renderTo(this.$refs.boundTypesTagSelectorContainer);

		// these selectors contain types only if there were added here in the app lifetime by user interaction
		// selector states are not synced reactively in the app lifetime
		this.crmTypesTagSelector = this.initFilteredTagSelector(true, false, !this.$store.state.permissions.canMoveSmartProcessFromCrm);
		this.crmTypesTagSelector.renderTo(this.$refs.crmTypesTagSelectorContainer);

		this.externalTypesTagSelector = this.initFilteredTagSelector(false, true, !this.$store.state.permissions.canMoveSmartProcessFromAnotherAutomatedSolution);
		this.externalTypesTagSelector.renderTo(this.$refs.externalTypesTagSelectorContainer);
	},

	methods: {
		initFilteredTagSelector(
			isOnlyCrmTypes,
			isOnlyExternalTypes,
			locked,
		): TagSelector
		{
			const tagSlector = new TagSelector({
				multiple: true,
				addButtonCaption: this.$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TAG_SELECTOR_ADD_BUTTON_CAPTION'),
				addButtonCaptionMore: this.$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TAG_SELECTOR_ADD_BUTTON_CAPTION'),
				dialogOptions: {
					height: 200,
					enableSearch: true,
					context: 'crm.automated_solutions.details',
					dropdownMode: true,
					showAvatars: false,
					entities: [
						{
							id: 'dynamic_type',
							dynamicLoad: true,
							dynamicSearch: true,
							options: {
								showAutomatedSolutionBadge: true,
								isOnlyExternalTypes,
								isOnlyCrmTypes,
							},
						},
					],
				},
				events: {
					onTagAdd: this.addTypeIdByTagAddEvent,
					onTagRemove: this.removeTypeIdIfNotContainsInBoundTypes,
				},

			});
			if (locked)
			{
				tagSlector.setLocked(true);
			}

			return tagSlector;
		},

		addTypeIdByTagAddEvent(event: BaseEvent): void
		{
			const { tag } = event.getData();

			this.$store.dispatch('addTypeId', tag.getId());
		},

		removeTypeIdByTagRemoveEvent(event: BaseEvent): void
		{
			const { tag } = event.getData();

			this.$store.dispatch('removeTypeId', tag.getId());
		},

		removeTypeIdIfNotContainsInBoundTypes(event: BaseEvent): void
		{
			const { tag } = event.getData();
			const boundSelectedTags = this.boundTypesTagSelector.getTags();
			const isTagContainsInBoundTags = boundSelectedTags.some((boundTag) => boundTag.getId() === tag.getId());
			if (!isTagContainsInBoundTags)
			{
				this.removeTypeIdByTagRemoveEvent(event);
			}
		},

		handleCreateTypeClick(): void
		{
			if (this.$store.getters.isSaved)
			{
				this.openTypeCreationSlider();

				return;
			}

			MessageBox.confirm(
				this.$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_NEED_SAVE_POPUP_MESSAGE'),
				(messageBox) => {
					return this.save()
						.then(() => this.openTypeCreationSlider())
						.finally(() => messageBox.close())
					;
				},
				this.$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_NEED_SAVE_POPUP_YES_CAPTION'),
			);
		},

		openTypeCreationSlider(): void
		{
			void Router.Instance.openTypeDetail(
				0,
				null,
				{
					automatedSolutionId: this.$store.state.automatedSolution.id,
					activeTabId: 'common',
					isExternal: 'Y',
				},
			).then(() => {
				this.$Bitrix.Application.get().reloadWithNewUri(this.$store.state.automatedSolution.id, {
					activeTabId: 'types',
				});
			});
		},

		save(): Promise
		{
			const builder = createSaveAnalyticsBuilder(this.$store)
				.setElement(Dictionary.ELEMENT_SAVE_IS_REQUIRED_TO_PROCEED_POPUP)
			;

			return wrapPromiseInAnalytics(this.$store.dispatch('save'), builder);
		},
	},
	template: `
		<div>
			<div class="ui-title-3">{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TAB_TITLE_TYPES') }}</div>
			<Card
				:title="$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_CARD_TYPES_TITLE')"
				:description="$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_CARD_TYPES_DESCRIPTION')"
			/>
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_FIELD_LABEL_CREATE_TYPE') }}
					</div>
				</div>
				<div class="ui-form-content">
					<div ref="boundTypesTagSelectorContainer"></div>
				</div>
			</div>
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_FIELD_LABEL_CRM_TYPES') }}
					</div>
				</div>
				<div class="ui-form-content">
					<div ref="crmTypesTagSelectorContainer"></div>
				</div>
			</div>
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_FIELD_LABEL_EXTERNAL_TYPES') }}
					</div>
				</div>
				<div class="ui-form-content">
					<div ref="externalTypesTagSelectorContainer"></div>
				</div>
			</div>
			<div v-if="isShowPermissionsResetAlert" class="ui-alert ui-alert-warning">
				<span class="ui-alert-message">
					{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_PERMISSIONS_WILL_BE_RESET_ALERT') }}
				</span>
			</div>
		</div>
	`,
};

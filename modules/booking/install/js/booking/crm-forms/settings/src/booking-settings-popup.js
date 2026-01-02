import { Dom, Loc, Tag, Type } from 'main.core';
import { Loader } from 'main.loader';
import { EventEmitter } from 'main.core.events';
import type { ListItemOptions } from 'landing.ui.component.listitem';

import { resourceStore } from './services/resource-store';
import { BookingSettingsDataModel } from './model/booking-settings-data-model';
import { ResourcesManager } from './components/resources-manager/resources-manager';
import {
	HasSlotsAllAvailableResourcesField,
	HintField,
	HintVisibilityField,
	LabelField,
	PlaceholderField,
} from './components/fields';
import type { BookingSettingsData } from './types';
import type { ResourceModel } from 'booking.model.resources';

type BookingSettingsPopupOptions = {
	listItemOptions: ListItemOptions & { sourceOptions: { settingsData: ?BookingSettingsData } },
	isAutoSelectionOn: boolean,
	templateId: string;
}

export class BookingSettingsPopup extends EventEmitter
{
	#options: ListItemOptions;
	#layout: {
		label: LabelField,
		placeholder: PlaceholderField,
		hint: HintField,
		isVisibleHint: HintVisibilityField,
		hasSlotsAllAvailableResources: HasSlotsAllAvailableResourcesField,
	};

	#bookingSettingsDataModel: BookingSettingsDataModel;
	#isAutoSelectionOn: boolean;

	#resourceLoader: ?Loader = null;
	#loadingResources: boolean = false;

	#resourcesManagerButton: ?HTMLElement = null;

	constructor({ listItemOptions, isAutoSelectionOn, templateId }: BookingSettingsPopupOptions)
	{
		super();
		this.setEventNamespace('BX.Booking.CrmForm.BookingSettingsPopup');

		this.#options = listItemOptions;
		this.#isAutoSelectionOn = isAutoSelectionOn;

		this.#bookingSettingsDataModel = new BookingSettingsDataModel(
			listItemOptions.sourceOptions.settingsData || {},
			isAutoSelectionOn,
			templateId,
		);

		this.#initFields(this.#bookingSettingsDataModel.form);
	}

	#initFields(settingsData: Object): void
	{
		const changeField = (field: { [key: string]: mixed }) => {
			this.#updateSettings(field);
		};

		this.#layout = {};
		this.#layout.label = new LabelField(
			settingsData.label || '',
			(label: string) => changeField({ label }),
		);
		this.#layout.placeholder = new PlaceholderField(
			settingsData.textHeader || '',
			(textHeader: string) => changeField({ textHeader }),
		);
		this.#layout.hint = new HintField(
			settingsData.hint || '',
			(hint: string) => changeField({ hint }),
		);
		this.#layout.isVisibleHint = new HintVisibilityField(
			Boolean(settingsData?.isVisibleHint),
			(isVisibleHint) => changeField({ isVisibleHint }),
		);
		this.#layout.hasSlotsAllAvailableResources = new HasSlotsAllAvailableResourcesField(
			Boolean(settingsData?.hasSlotsAllAvailableResources),
			(hasSlotsAllAvailableResources) => changeField({ hasSlotsAllAvailableResources }),
			this.#isAutoSelectionOn,
		);
	}

	async show(): void
	{
		await this.#loadResources(this.#getResourceIds());

		const container = this.#getBodyContainer();

		Dom.append(this.#renderContent(), container);
		BX.UI.Hint.init(container);
		this.#updateResourcesCounter();
		Dom.style(container, 'display', 'block');
	}

	async #loadResources(ids: number): Promise<void>
	{
		if (ids.length === 0)
		{
			return;
		}

		this.#setLoadingResources(true);

		await resourceStore.ensure(this.#getResourceIds());
		this.#setResourceIds(this.#filterAvailableResourceIds(ids));

		this.#setLoadingResources(false);
	}

	#setLoadingResources(loading: boolean): void
	{
		const container = this.#getHeaderContainer();

		this.#loadingResources = loading;
		if (this.#loadingResources)
		{
			this.#resourceLoader ??= new Loader({ size: 40 });

			Dom.style(container, 'opacity', 0.8);
			void this.#resourceLoader.show(container);
		}
		else
		{
			Dom.style(container, 'opacity', 1);
			void this.#resourceLoader.hide();
		}
	}

	#filterAvailableResourceIds(ids: number[]): number[]
	{
		const availableResources: ResourceModel[] = resourceStore.getAll();
		const availableResourceIds: Set<number> = new Set(
			availableResources.map((resource: ResourceModel): number => resource.id),
		);

		return ids.filter((id: number) => availableResourceIds.has(id));
	}

	close(): void
	{
		const container = this.#getBodyContainer();
		this.emit('onClose');

		Dom.style(container, 'display', 'none');
		Dom.clean(container);
	}

	getSettings(): Object
	{
		this.#updateSettings();

		return this.#options.sourceOptions.settingsData;
	}

	#getHeaderContainer(): HTMLElement
	{
		return document.querySelector(`.landing-ui-component-list-item[data-id="${this.#options.id}"] .landing-ui-component-list-item-header`);
	}

	#getBodyContainer(): HTMLElement
	{
		return document.querySelector(`.landing-ui-component-list-item[data-id="${this.#options.id}"] .landing-ui-component-list-item-body`);
	}

	#renderContent(): HTMLElement
	{
		return Tag.render`
			<div class="landing-ui-form landing-ui-form-form-settings booking-crm-forms-settings">
				<div class="landing-ui-form-description"></div>
				${this.#renderResource()}
				${this.#renderLabelField()}
				${this.#renderPlaceholderField()}
				${this.#renderHintField()}
				${this.#renderIsVisibleHint()}
				${this.#renderHasSlotsAllAvailableResources()}
			</div>
		`;
	}

	#renderResource(): HTMLElement
	{
		this.#resourcesManagerButton = Tag.render`
			<button
				id="booking--crm-forms--resource-manager-button"
				type="button"
				class="btn btn-primary g-btn-size-l"
				onclick="${this.#showResourcesManager.bind(this)}"
			>
				${this.#getResourceIds().length > 0
			? Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_CHANGE_BUTTON')
			: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_ADD_BUTTON')
		}
			</button>
		`;

		return Tag.render`
			<div class="landing-ui-field d-flex">
				<div class="flex-grow-1">
					<div class="g-line-height-1_7 g-font-size-18 g-font-weight-500 g-color-gray-dark-v2">
						${Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_LABEL')}
					</div>
					<div class="crm-form--booking-resources-count"></div>
				</div>
				<div style="align-self: flex-end">
					${this.#resourcesManagerButton}
				</div>
			</div>
		`;
	}

	#showResourcesManager(): void
	{
		const resourcesManager = new ResourcesManager({
			target: this.#resourcesManagerButton,
			selectedIds: this.#getResourceIds(),
			onUpdateResourceIds: (resourceIds: number[]) => {
				this.#setResourceIds(resourceIds);
				this.#updateSettings();
				this.#updateResourcesCounter();
				this.#updateResourceManagerButton();
			},
		});
		resourcesManager.show();
	}

	#getResourceIds(): number[]
	{
		return this.#bookingSettingsDataModel.form.resourceIds;
	}

	#setResourceIds(resourceIds: number[] | mixin): void
	{
		this.#bookingSettingsDataModel.setSettingsData({
			resourceIds: Type.isArray(resourceIds) ? resourceIds : [],
		});
		this.#updateSettings();
	}

	#updateResourcesCounter(): void
	{
		const counterEl = this.#getBodyContainer().querySelector('.crm-form--booking-resources-count');

		if (Type.isDomNode(counterEl))
		{
			counterEl.innerText = this.#getResourceIds().length > 0
				? Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_TEXT', {
					'#COUNT#': this.#getResourceIds().length,
				})
				: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_EMPTY');
		}
	}

	#updateResourceManagerButton(): void
	{
		if (Type.isDomNode(this.#resourcesManagerButton))
		{
			this.#resourcesManagerButton.innerText = this.#getResourceIds().length > 0
				? Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_CHANGE_BUTTON')
				: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCES_FIELD_ADD_BUTTON');
		}
	}

	#renderLabelField(): HTMLElement
	{
		return this.#layout.label.getLayout();
	}

	#renderPlaceholderField(): HTMLElement
	{
		return this.#layout.placeholder.getLayout();
	}

	#renderHintField(): HTMLElement
	{
		if (this.#isAutoSelectionOn && !this.#bookingSettingsDataModel.form.hint)
		{
			this.#layout.hint.setValue(
				Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_HINT_DEFAULT_VALUE'),
			);
		}

		return this.#layout.hint.getLayout();
	}

	#renderIsVisibleHint(): HTMLElement
	{
		return this.#layout.isVisibleHint.getLayout();
	}

	#renderHasSlotsAllAvailableResources(): HTMLElement | string
	{
		return this.#layout.hasSlotsAllAvailableResources.getLayout();
	}

	#updateSettings(settings = null): void
	{
		this.#bookingSettingsDataModel.setSettingsData(settings);

		this.#options.sourceOptions.settingsData = {
			...this.#options.sourceOptions.settingsData,
			isAutoSelectionOn: this.#isAutoSelectionOn,
			...this.#bookingSettingsDataModel.settingsData,
		};

		this.emit('onChange');
		this.#options.form.emit('onChange');
	}
}

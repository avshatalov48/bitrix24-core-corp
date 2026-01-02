import { Dom, Event, Loc, Tag, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Dialog, Item } from 'ui.entity-selector';

import { EntitySelectorEntity, EntitySelectorTab } from 'booking.const';
import type { ResourceModel } from 'booking.model.resources';

import { ContentHeader } from './content-header/content-header';
import { ResourcesSelector } from './resources-selector/resources-selector';
import { resourceStore } from '../../services/resource-store';

import './resources-manager.css';

type ResourcesManagerOptions = {
	target: HTMLElement,
	selectedIds: number[],
	onUpdateResourceIds: (number[]) => void,
	onClose: (number[]) => void | null,
}

const subHeaderClassName = 'crm-form--booking--resources-manager--header-resources-section';

export class ResourcesManager
{
	dialog: ?Dialog = null;
	#targetNode: HTMLElement;
	#selectedIds: number[];
	#resourcesIds: number[];
	#loadingResources: boolean = false;
	#options: ResourcesManagerOptions;
	#btnDeleteResources: HTMLElement;
	#btnChangeResources: HTMLElement;

	constructor(options: ResourcesManagerOptions)
	{
		this.#resourcesIds = options.selectedIds || [];
		this.#selectedIds = [];
		this.#targetNode = options.target;
		this.#options = options;
	}

	async show(): void
	{
		if (!this.dialog)
		{
			this.#initDialog();

			await this.#loadResources(this.#resourcesIds);

			const renderInitialContent = this.#resourcesIds.length > 0
				? this.#addSelectedItems.bind(this)
				: this.#appendEmptyState.bind(this)
			;

			renderInitialContent();
		}

		this.dialog.show();

		Event.bind(document, 'scroll', this.adjustPosition, true);
	}

	close(): void
	{
		if (this.dialog)
		{
			this.#options.onUpdateResourceIds(this.#resourcesIds);
			if (Type.isFunction(this.#options.onClose))
			{
				this.#options.onClose(this.#resourcesIds);
			}
			this.dialog.destroy();
			Event.unbind(document, 'scroll', this.adjustPosition, true);
		}
	}

	adjustPosition(): void
	{
		if (this.dialog)
		{
			this.dialog.adjustPosition();
		}
	}

	get selectedResources(): ResourceModel[]
	{
		if (this.#resourcesIds.length === 0)
		{
			return [];
		}

		return resourceStore.getByIds(this.#resourcesIds);
	}

	#initDialog(): void
	{
		this.dialog = new Dialog({
			targetNode: this.#targetNode,
			id: 'booking-crm-form-resource-selector',
			height: Math.max(window.innerHeight - 300, 500),
			width: 356,
			offsetLeft: this.#targetNode.offsetWidth + 5,
			offsetTop: -300,
			addTagOnSelect: false,
			showAvatars: false,
			focusOnFirst: false,
			dropdownMode: true,
			enableSearch: true,
			searchOptions: {
				allowCreateItem: false,
			},
			header: ContentHeader,
			headerOptions: {
				content: this.#getHeaderContent(),
			},
			popupOptions: {
				className: 'crm-form--booking--resource-manager-popup',
				angle: {
					position: 'left',
				},
			},
			tagSelectorOptions: {
				textBoxWidth: '90%',
				placeholder: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_SEARCH_PLACEHOLDER'),
			},
			events: {
				onHide: () => this.#options.onClose?.(this.#resourcesIds),
				'Item:onSelect': (event: BaseEvent) => {
					this.#selectItem(event.getData().item);
				},
				'Item:onDeselect': (event: BaseEvent) => {
					this.#deselectItem(event.getData().item);
				},
			},
		});

		if (this.#resourcesIds.length > 0)
		{
			this.#appendSubHeaderContent();
		}

		this.dialog.removeItems();
	}

	#selectItem(item: Item): void
	{
		this.#selectedIds.push(item.id);

		if (this.#selectedIds.length > 0)
		{
			this.#showDeleteButton();
		}
	}

	#deselectItem(item: Item): void
	{
		this.#selectedIds = this.#selectedIds.filter((id) => id !== item.id);

		if (this.#selectedIds.length === 0)
		{
			this.#hideDeleteButton();
		}
	}

	#deleteResources(): void
	{
		if (this.#selectedIds.length === 0)
		{
			return;
		}

		this.#setResourceIds(
			this.#resourcesIds.filter((id) => !this.#selectedIds.includes(id)),
		);
		this.#selectedIds = [];
		this.#hideDeleteButton();
	}

	#appendEmptyState(): void
	{
		const container = this.dialog.getPopup().getContentContainer();

		if (Type.isDomNode(container.querySelector('.crm-forms--booking--resources-manager-empty-state')))
		{
			return;
		}

		const emptyState = Tag.render`
			<div class="crm-forms--booking--resources-manager-empty-state d-flex h-100 w-100 justify-content-center">
				<div class="d-flex flex-column align-items-center p-4">
					<div class="mb-3 crm-forms--booking--resources-manager-empty-state_icon"></div>
					<div class="mb-3 crm-forms--booking--resources-manager-empty-state_title fw-medium fs-6 lh-base">
						${Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_EMPTY_TITLE')}
					</div>
					<div class="mb-3 crm-forms--booking--resources-manager-empty-state_text fw-normal">
						${Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_EMPTY_MESSAGE')}
					</div>
					<div>
						<button
							class="btn btn-primary g-btn-size-l crm-forms--booking--resources-manager__empty-state-btn-add"
							type="button"
							onclick="${this.#openResourceSelector.bind(this)}"
						>
							${Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_ADD_RESOURCES_BUTTON')}
						</button>
					</div>
				</div>
			</div>
		`;

		Dom.insertBefore(emptyState, container.querySelector('.ui-selector-items'));
	}

	#removeEmptyState(): void
	{
		const emptyStateEl = this.dialog.getPopup().getContentContainer().querySelector(
			'.crm-forms--booking--resources-manager-empty-state',
		);
		removeElement(emptyStateEl);
	}

	#updateResourcesCount(resourcesCount: number): void
	{
		const resourcesCountEl = this.dialog.getPopup().getContentContainer().querySelector(
			'.crm-form--booking--resources-manager--header-resources-count',
		);
		if (Type.isDomNode(resourcesCountEl))
		{
			resourcesCountEl.innerText = resourcesCount;
		}
	}

	async #loadResources(ids: number[]): Promise<void>
	{
		if (ids.length === 0)
		{
			return;
		}

		this.#setLoadingResources(true);
		await resourceStore.ensure(ids);
		this.#setLoadingResources(false);
	}

	#addSelectedItems(): void
	{
		this.dialog.removeItems();

		const resources: ResourceModel[] = resourceStore.getByIds(this.#resourcesIds);
		for (const resource: ResourceModel of resources)
		{
			this.dialog.addItem({
				id: resource.id,
				entityId: EntitySelectorEntity.Resource,
				title: resource.name,
				subtitle: resource.typeName,
				tabs: EntitySelectorTab.Recent,
			});
		}
	}

	#setLoadingResources(loading: boolean): void
	{
		this.#loadingResources = loading;
		if (this.#loadingResources)
		{
			this.dialog.showLoader();
		}
		else
		{
			this.dialog.hideLoader();
		}
	}

	#setResourceIds(selectedIds: number[]): void
	{
		this.#resourcesIds = selectedIds;
		this.#addSelectedItems();
		this.#updateResourcesCount(selectedIds.length);
		this.#options.onUpdateResourceIds(selectedIds);

		if (selectedIds.length > 0)
		{
			this.#appendSubHeaderContent();
			this.#removeEmptyState();
		}
		else
		{
			this.#appendEmptyState();
			this.#removeSubHeadContent();
		}
	}

	#openResourceSelector(event): void
	{
		const resourceSelector = new ResourcesSelector({
			targetNode: event?.target || null,
			selectedIds: this.#resourcesIds,
			onSave: this.#saveResourceSelector.bind(this),
			onClose: this.#closeResourceSelector.bind(this),
		}).createSelector();
		this.dialog.freeze();
		resourceSelector.show();
	}

	async #saveResourceSelector({ resourceIds }: { resourceIds: number[] }): void
	{
		await this.#loadResources(resourceIds);
		this.#setResourceIds(resourceIds);
	}

	#closeResourceSelector(): void
	{
		this.dialog.unfreeze();
	}

	#getHeaderContent(): HTMLElement
	{
		return Tag.render`
			<div class="w-100 pt-3 px-3">
				<div class="d-flex align-items-end w-100">
					<h5 class="flex-grow-1">
						${Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_HEADER_TITLE')}
					</h5>
					<div
						class="landing-ui-button-icon-remove" 
						role="button"
						tabindex="0"
						onclick="${this.close.bind(this)}"
					></div>
				</div>
			</div>
		`;
	}

	#appendSubHeaderContent(): HTMLElement
	{
		if (this.dialog.getPopup().getPopupContainer().querySelector(`.${subHeaderClassName}`))
		{
			return;
		}

		const buttonChangeResourcesLabel = Loc.getMessage(
			'BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_HEADER_ADD_RESOURCES_BUTTON',
			{
				'#PLUS#': '+',
			},
		);
		const buttonDeleteResourcesLabel = Loc.getMessage(
			'BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_HEADER_DELETE_RESOURCES_BUTTON',
			{
				'#ICON#': '×',
			},
		);
		const resourcesCount = Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_MANAGER_RESOURCES_COUNT', {
			'#COUNT#': `<span class="crm-form--booking--resources-manager--header-resources-count">${this.#resourcesIds.length}</span>`,
		});

		const { root, btnDeleteResources, btnChangeResources } = Tag.render`
			<div class="${subHeaderClassName} d-flex align-items-center">
				<div class="crm-form--booking--resources-manager--header-resources flex-grow-1">
					<span class="pr-2 crm-form--booking--resources-manager--header-resources-title">
						${resourcesCount}
					</span>
				</div>
				<div>
					<button
						ref="btnDeleteResources"
						class="crm-form--booking--resources-manager--header__btn --none btn btn-outline-danger g-btn-size-sm"
						type="button"
						onclick="${this.#deleteResources.bind(this)}"
					>
						${buttonDeleteResourcesLabel}
					</button>
					<button
						ref="btnChangeResources"
						class="crm-form--booking--resources-manager--header__btn btn btn-primary g-btn-size-sm"
						type="button"
						onclick="${this.#openResourceSelector.bind(this)}"
					>
						${buttonChangeResourcesLabel}
					</button>
				</div>
			</div>
		`;
		this.#btnChangeResources = btnChangeResources;
		this.#btnDeleteResources = btnDeleteResources;

		const searchEl = this.dialog.getPopup().getPopupContainer().querySelector('.ui-selector-search');

		Dom.insertAfter(root, searchEl);
	}

	#showDeleteButton(): void
	{
		if (Type.isDomNode(this.#btnDeleteResources) && Type.isDomNode(this.#btnChangeResources))
		{
			Dom.addClass(this.#btnChangeResources, '--none');
			Dom.removeClass(this.#btnDeleteResources, '--none');
		}
	}

	#hideDeleteButton(): void
	{
		if (Type.isDomNode(this.#btnDeleteResources) && Type.isDomNode(this.#btnChangeResources))
		{
			Dom.removeClass(this.#btnChangeResources, '--none');
			Dom.addClass(this.#btnDeleteResources, '--none');
		}
	}

	#removeSubHeadContent(): void
	{
		const subHeadEl = document.querySelector(`.${subHeaderClassName}`);
		removeElement(subHeadEl);
	}
}

function removeElement(el: any): void
{
	if (Type.isDomNode(el))
	{
		Dom.remove(el);
	}
}

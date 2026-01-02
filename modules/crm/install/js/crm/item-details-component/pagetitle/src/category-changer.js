import { CategoryModel } from 'crm.category-model';
import { ajax as Ajax, Dom, Loc, Tag, Text, Type, Uri } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { type MenuItemOptions, MenuManager } from 'main.popup';
import 'ui.notification';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { type Logger, logger } from './logger';

export type CategoryChangerParams = {
	entityTypeId: number,
	entityId: number,
	categoryId: number,
	categories: CategoryModel[],
	editorGuid: string,
	labelTemplate: ?string,
};

/**
 * Renders category change control, typically in the details card header near title.
 * WARNING! On successful change, the page is reloaded with window.location.reload or window.location.href.
 * It's a feature :)
 *
 * @memberOf BX.Crm.ItemDetailsComponent.PageTitle
 *
 * @mixes EventEmitter
 *
 * @emits BX.Crm.ItemDetailsComponent.CategoryChanger:onProgressStart
 * @emits BX.Crm.ItemDetailsComponent.CategoryChanger:onProgressStop
 */
export class CategoryChanger
{
	#entityTypeId: number;
	#id: number;
	#categoryId: number;
	#categories: CategoryModel[];
	#editorGuid: ?string = null;
	#labelTemplate: string;
	#logger: Logger;
	#isProgress: boolean = false;

	constructor({ entityTypeId, entityId, categoryId, categories, editorGuid, labelTemplate }: CategoryChangerParams)
	{
		this.#entityTypeId = entityTypeId;
		this.#id = entityId;
		this.#categoryId = categoryId;
		this.#categories = categories;
		this.#editorGuid = editorGuid;
		this.#labelTemplate = Type.isStringFilled(labelTemplate) ? labelTemplate : '#CATEGORY#';

		this.#logger = logger;

		EventEmitter.makeObservable(this, 'BX.Crm.ItemDetailsComponent.CategoryChanger');
	}

	static renderToTarget(target: string | HTMLElement, params: CategoryChangerParams & {categories: Array[]}): ?CategoryChanger
	{
		const changer = new CategoryChanger({
			...params,
			categories: params.categories.map((category) => new CategoryModel(category)),
		});

		const realTarget = Type.isDomNode(target) ? target : document.querySelector(target);
		if (!realTarget)
		{
			logger.warn('target not found, skip rendering');

			return null;
		}

		Dom.append(changer.render(), realTarget);

		return changer;
	}

	render(): ?HTMLElement
	{
		const current = this.#getCurrentCategory();
		if (!current)
		{
			this.#logger.warn('current category not found, skip rendering');

			return null;
		}

		const label = this.#labelTemplate.replace('#CATEGORY#', current.getName());

		const element = Tag.render`
			<div class="crm-details-pagetitle-legend-container">
				<a href="#" onclick="${this.#onCategorySelectorClick.bind(this)}">
					${Text.encode(label)}
				</a>
			</div>
		`;

		if (this.#isNew())
		{
			Dom.addClass(element, '--new');
		}

		return element;
	}

	#onCategorySelectorClick(event: BaseEvent): void
	{
		const items: MenuItemOptions[] = [];
		for (const category of this.#getAllCategoriesExceptCurrent())
		{
			items.push({
				text: category.getName(),
				onclick: this.#onCategorySelect.bind(this, category.getId()),
			});
		}

		MenuManager.show({
			id: `item-category-changer-${this.#entityTypeId}-${this.#id}`,
			bindElement: event.target,
			items,
		});
	}

	#onCategorySelect(wantCategoryId: number): void
	{
		if (this.#isProgress)
		{
			return;
		}

		if (this.#isEditorChanged())
		{
			this.#showUnsavedChangesDialog(this.#changeCategory.bind(this, wantCategoryId));
		}
		else
		{
			this.#changeCategory(wantCategoryId);
		}
	}

	#changeCategory(wantCategoryId: number): void
	{
		this.#startProgress();

		if (this.#isNew())
		{
			this.#reloadPageWhenCategoryChanged(wantCategoryId);
		}
		else
		{
			this.#changeCategoryViaAjax(wantCategoryId);
		}
	}

	#reloadPageWhenCategoryChanged(wantCategoryId: number): void
	{
		const url = new Uri(window.location.href);
		// for factory-based details
		url.setQueryParam('categoryId', wantCategoryId);
		// for crm.deal.details
		url.setQueryParam('category_id', wantCategoryId);

		setTimeout(() => {
			window.location.href = url.toString();
			this.#stopProgress();
		});
	}

	#changeCategoryViaAjax(wantCategoryId: number): void
	{
		Ajax.runAction('crm.controller.item.update', {
			analyticsLabel: 'crmItemDetailsChangeCategory',
			data: {
				entityTypeId: this.#entityTypeId,
				id: this.#id,
				fields: {
					categoryId: wantCategoryId,
				},
			},
		}).then(() => {
			setTimeout(() => {
				window.location.reload();
				this.#stopProgress();
			});
		}).catch((response) => {
			this.#logger.warn('error changing category via ajax', response);

			const message = response?.errors?.[0]?.message || 'Something went wrong';

			BX.UI.Notification.Center.notify({
				content: message,
				position: 'top-right',
				autoHideDelay: 3000,
			});

			this.#stopProgress();
		});
	}

	#showUnsavedChangesDialog(onOk: () => void): void
	{
		const entityTypeName = BX.CrmEntityType?.resolveName(this.#entityTypeId);

		const message = Loc.getMessage(`CRM_ITEM_PAGETITLE_CATEGORY_CHANGER_CONFIRM_DIALOG_MESSAGE_${entityTypeName}`)
			|| Loc.getMessage('CRM_ITEM_PAGETITLE_CATEGORY_CHANGER_CONFIRM_DIALOG_MESSAGE')
		;

		MessageBox.show({
			modal: true,
			title: Loc.getMessage('CRM_ITEM_PAGETITLE_CATEGORY_CHANGER_CONFIRM_DIALOG_TITLE'),
			message,
			minHeight: 100,
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('CRM_ITEM_PAGETITLE_CATEGORY_CHANGER_CONFIRM_DIALOG_OK_BTN'),
			onOk: (messageBox) => {
				messageBox.close();
				onOk();
			},
			onCancel: (messageBox) => messageBox.close(),
		});
	}

	#startProgress(): void
	{
		if (this.#isProgress)
		{
			return;
		}

		this.#isProgress = true;
		this.emit('onProgressStart');
	}

	#stopProgress(): void
	{
		if (!this.#isProgress)
		{
			return;
		}

		this.#isProgress = false;
		this.emit('onProgressStop');
	}

	#isEditorChanged(): boolean
	{
		const editor = this.#getEditor();

		return Boolean(editor?.hasChangedControls() || editor?.hasChangedControllers());
	}

	#getEditor(): ?BX.Crm.EntityEditor
	{
		if (this.#editorGuid)
		{
			return BX.Crm?.EntityEditor?.get(this.#editorGuid);
		}

		return BX.Crm?.EntityEditor?.getDefault();
	}

	#isNew(): boolean
	{
		return this.#id <= 0;
	}

	#getCurrentCategory(): ?CategoryModel
	{
		return this.#categories.find((category) => category.getId() === this.#categoryId);
	}

	#getAllCategoriesExceptCurrent(): CategoryModel[]
	{
		return this.#categories.filter((category) => category.getId() !== this.#categoryId);
	}
}

import { Loc, Tag, Reflection, Dom, Text, Type, Event, ajax as Ajax } from 'main.core';
import { UI } from 'ui.notification';
import { MessageBox } from 'ui.dialogs.messagebox';
import { SidePanel } from 'main.sidepanel';
import 'ui.alerts';

type Props = {
	gridId: ?string,
};

class SupersetDashboardTagGridManager
{
	#grid: BX.Main.grid;

	constructor(props: Props)
	{
		this.#grid = BX.Main.gridManager.getById(props.gridId)?.instance;
	}

	getGrid(): BX.Main.grid
	{
		return this.#grid;
	}

	#notifyErrors(errors: Array): void
	{
		if (errors[0] && errors[0].message)
		{
			BX.UI.Notification.Center.notify({
				content: Text.encode(errors[0].message),
			});
		}
	}

	#buildTitleEditor(
		id: number,
		title: string,
		onCancel: () => void,
		onSave: (innerTitle: string) => Promise,
	): HTMLElement
	{
		const input = Tag.render`
			<input class="main-grid-editor main-grid-editor-text" type="text">
		`;
		input.value = title;

		const saveInputValue = () => {
			const value = input.value;

			Dom.removeClass(input, 'tag-title-input-danger');
			if (value.trim() === '')
			{
				UI.Notification.Center.notify({
					content: Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_TITLE_ERROR_EMPTY'),
				});

				Dom.addClass(input, 'tag-title-input-danger');

				return;
			}

			onSave(input.value)
				.then(() => {
					Dom.style(buttons, 'display', 'none');
					Dom.attr(input, 'disabled', true);
				})
				.catch(() => {
					Dom.addClass(input, 'tag-title-input-danger');
				})
			;
		};

		Event.bind(input, 'keydown', (event) => {
			if (event.keyCode === 13)
			{
				saveInputValue();
				event.preventDefault();
			}
			else if (event.keyCode === 27)
			{
				onCancel();
				event.preventDefault();
			}
		});

		const applyButton = Tag.render`
			<a>
				<i
					class="ui-icon-set --check"
					style="--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-40);"
				></i>
			</a>
		`;

		const cancelButton = Tag.render`
			<a>
				<i
					class="ui-icon-set --cross-60"
					style="--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-40);"
				></i>
			</a>
		`;

		const buttons = Tag.render`
			<div class="tag-title-wrapper__buttons">
				${applyButton}
				${cancelButton}
			</div>
		`;

		Event.bind(cancelButton, 'click', () => {
			onCancel();
		});

		Event.bind(applyButton, 'click', saveInputValue);

		return Tag.render`
			<div class="tag-title-wrapper__item tag-title-edit">
				${input}
				<div class="tag-title-wrapper__buttons-wrapper">
					${buttons}
				</div>
			</div>
		`;
	}

	#getTitlePreview(tagId: number): ?HTMLElement
	{
		const grid = this.getGrid();
		const row = grid.getRows().getById(tagId);
		if (!row)
		{
			return null;
		}

		const wrapper = row.getCellById('TITLE')?.querySelector('.tag-title-wrapper');
		if (!wrapper)
		{
			return null;
		}

		const previewSection = wrapper.querySelector('.tag-title-preview');
		if (previewSection)
		{
			return previewSection;
		}

		return null;
	}

	renameTag(tagId: number): void
	{
		const grid = this.getGrid();
		const row = grid.getRows().getById(tagId);

		if (!row)
		{
			return;
		}

		const rowNode = row.getNode();
		Dom.removeClass(rowNode, 'tag-title-edited');

		const wrapper = row.getCellById('TITLE')?.querySelector('.tag-title-wrapper');
		if (!wrapper)
		{
			return;
		}

		const editor = this.#buildTitleEditor(
			tagId,
			row.getEditData().TITLE,
			() => {
				this.#cancelRename(tagId);
			},
			(innerTitle) => {
				const oldTitle = this.#getTitlePreview(tagId).querySelector('span').innerText;
				this.#getTitlePreview(tagId).querySelector('span').innerText = innerTitle;

				const rowEditData = row.getEditData();
				rowEditData.TITLE = innerTitle;
				const editableData = grid.getParam('EDITABLE_DATA');
				if (Type.isPlainObject(editableData))
				{
					editableData[row.getId()] = rowEditData;
				}

				return new Promise((resolve, reject) => {
					this.#saveTitle(tagId, innerTitle)
						.then(() => {
							Dom.addClass(rowNode, 'tag-title-edited');
							const msg = Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_RENAME_TITLE_SUCCESS', {
								'#NEW_TITLE#': Text.encode(innerTitle),
							});

							UI.Notification.Center.notify({
								content: msg,
							});

							this.#cancelRename(tagId);

							this.#sendChangeEventMessage(tagId, innerTitle);

							resolve();
						})
						.catch((response) => {
							if (response.errors)
							{
								this.#notifyErrors(response.errors);
							}
							this.#getTitlePreview(tagId).querySelector('span').innerText = oldTitle;
							rowEditData.TITLE = oldTitle;

							reject();
						});
				});
			},
		);

		const preview = wrapper.querySelector('.tag-title-preview');
		if (preview)
		{
			Dom.style(preview, 'display', 'none');
		}
		Dom.append(editor, wrapper);

		const editBtn = row.getCellById('EDIT_URL')?.querySelector('a');

		const actionsClickHandler = () => {
			Event.unbind(row.getActionsButton(), 'click', actionsClickHandler);
			if (editBtn)
			{
				Event.unbind(editBtn, 'click', actionsClickHandler);
			}

			this.#cancelRename(tagId);
		};

		Event.bind(row.getActionsButton(), 'click', actionsClickHandler);
		if (editBtn)
		{
			Event.bind(editBtn, 'click', actionsClickHandler);
		}
	}

	deleteTag(tagId: number): void
	{
		const grid = this.getGrid();
		const row = grid.getRows().getById(tagId);
		const count = row.getEditData().DASHBOARD_COUNT;
		if (!count)
		{
			this.#delete(tagId);

			return;
		}

		const messageBox = MessageBox.confirm(
			Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_DELETE_POPUP'),
			() => {
				this.#delete(tagId);
				messageBox.close();
			},
			Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_DELETE_POPUP_YES'),
		);
	}

	#delete(tagId: number): Promise
	{
		return Ajax.runAction('biconnector.dashboardTag.delete', {
			data: {
				id: tagId,
			},
		})
			.then(() => {
				this.getGrid().removeRow(tagId, null, null, () => {
					this.#sendDeleteEventMessage(tagId);
				});
				const msg = Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_DELETE_SUCCESS');

				UI.Notification.Center.notify({
					content: msg,
				});
			})
			.catch((response) => {
				if (response.errors)
				{
					this.#notifyErrors(response.errors);
				}
			})
		;
	}

	#sendChangeEventMessage(tagId: number, title: number): void
	{
		if (SidePanel.Instance)
		{
			SidePanel.Instance.postMessage(window, 'BIConnector.Superset.DashboardTagGrid:onTagChange', { tagId, title });
		}
	}

	#sendDeleteEventMessage(tagId: number): void
	{
		if (SidePanel.Instance)
		{
			SidePanel.Instance.postMessage(window, 'BIConnector.Superset.DashboardTagGrid:onTagDelete', { tagId });
		}
	}

	#cancelRename(tagId: number): void
	{
		const row = this.getGrid().getRows().getById(tagId);
		if (!row)
		{
			return;
		}

		const editSection = row.getCellById('TITLE')?.querySelector('.tag-title-edit');
		const previewSection = row.getCellById('TITLE')?.querySelector('.tag-title-preview');

		if (editSection)
		{
			Dom.remove(editSection);
		}

		if (previewSection)
		{
			Dom.style(previewSection, 'display', 'flex');
		}
	}

	#saveTitle(tagId: number, title: string): Promise
	{
		return Ajax.runAction('biconnector.dashboardTag.rename', {
			data: {
				id: tagId,
				title,
			},
		});
	}
}

Reflection.namespace('BX.BIConnector').SupersetDashboardTagGridManager = SupersetDashboardTagGridManager;

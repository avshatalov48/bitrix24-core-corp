import { EditableColumnManager } from 'biconnector.grid.editable-columns';
import { Loc, Reflection, Text, ajax as Ajax } from 'main.core';
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

		EditableColumnManager.init(
			props.gridId,
			[
				{
					name: 'TITLE',
					saveEndpoint: 'biconnector.dashboardTag.rename',
					onValueCheck: (newValue) => {
						if (newValue.trim() === '')
						{
							UI.Notification.Center.notify({
								content: Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_TITLE_ERROR_EMPTY'),
							});

							return false;
						}

						return true;
					},
					onSave: (rowId, newValue) => {
						const msg = Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_RENAME_TITLE_SUCCESS', {
							'#NEW_TITLE#': Text.encode(newValue),
						});

						UI.Notification.Center.notify({
							content: msg,
						});

						this.#sendChangeEventMessage(rowId, newValue);
					},
				},
			],
		);
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
}

Reflection.namespace('BX.BIConnector').SupersetDashboardTagGridManager = SupersetDashboardTagGridManager;

import {Dom} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {CellActionState} from '../../../../../../../../main/install/components/bitrix/main.ui.grid/templates/.default/src/js/cell-action-state';

export class ActionsController
{
	static options;

	static setOptions(options)
	{
		ActionsController.options = options;
	}

	static setActionsPanel(actionsPanel)
	{
		ActionsController.actionsPanel = actionsPanel;
	}

	static doAction(action, groupId)
	{
		return new Promise((resolve, reject) => {
			BX.ajax.runComponentAction('bitrix:tasks.projects', 'processAction', {
				mode: 'class',
				data: {
					action,
					ids: ActionsController.getActionIds(groupId) || [],
				},
				signedParameters: ActionsController.options.signedParameters,
			}).then(
				() => resolve(),
				() => reject()
			).catch(
				() => reject()
			);

			ActionsController.hideActionsPanel();
			ActionsController.unselectRows();
		});
	}

	static getActionIds(id)
	{
		if (id !== undefined)
		{
			return [id];
		}

		const selected = ActionsController.getSelectedRows();
		if (selected.length === 0)
		{
			return [];
		}

		return selected.map((row) => {
			return row.getDataset().id;
		});
	}

	static getSelectedRows()
	{
		return ActionsController.getGridInstance().getRows().getSelected();
	}

	static sendJoinRequest(button)
	{
		if (Dom.hasClass(button, 'ui-btn-clock'))
		{
			return;
		}
		Dom.addClass(button, 'ui-btn-clock');

		BX.ajax({
			url: button.getAttribute('bx-request-url'),
			method: 'POST',
			dataType: 'json',
			data: {
				ajax_request: 'Y',
				save: 'Y',
				sessid: BX.bitrix_sessid(),
			},
			onsuccess: () => Dom.removeClass(button, 'ui-btn-clock'),
			onfailure: () => Dom.removeClass(button, 'ui-btn-clock'),
		});

		ActionsController.hideActionsPanel();
		ActionsController.unselectRows();
	}

	static sendAcceptRequest(userGroupRelationId, url)
	{
		BX.ajax({
			url: url,
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'accept',
				max_count: 1,
				checked_0: 'Y',
				type_0: 'INVITE_GROUP',
				id_0: userGroupRelationId,
				type: 'in',
				ajax_request: 'Y',
				sessid: BX.bitrix_sessid(),
			},
			onsuccess: result => {},
			onfailure: result => console.log(result),
		});

		ActionsController.hideActionsPanel();
		ActionsController.unselectRows();
	}

	static sendDenyRequest(userGroupRelationId, url)
	{
		ActionsController.sendCancelRequest(userGroupRelationId, url);
	}

	static sendCancelRequest(userGroupRelationId, url)
	{
		BX.ajax({
			url: url,
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'reject',
				max_count: 1,
				checked_0: 'Y',
				type_0: 'INVITE_GROUP',
				id_0: userGroupRelationId,
				type: 'out',
				ajax_request: 'Y',
				sessid: BX.bitrix_sessid(),
			},
			onsuccess: result => {},
			onfailure: result => console.log(result),
		});

		ActionsController.hideActionsPanel();
		ActionsController.unselectRows();
	}

	static hideActionsPanel()
	{
		ActionsController.options.actionsPanel.hidePanel();
	}

	static unselectRows()
	{
		ActionsController.getGridInstance().getRows().unselectAll();
	}

	static getGridInstance()
	{
		return BX.Main.gridManager.getById(ActionsController.options.gridId).instance;
	}

	static changePin(event: BaseEvent)
	{
		const {button, row} = event.getData();

		if (Dom.hasClass(button, CellActionState.ACTIVE))
		{
			ActionsController.doAction('unpin', row.getId()).then(() => {
				Dom.removeClass(button, CellActionState.ACTIVE);
				Dom.addClass(button, CellActionState.SHOW_BY_HOVER);
			});
		}
		else
		{
			ActionsController.doAction('pin', row.getId()).then(() => {
				Dom.addClass(button, CellActionState.ACTIVE);
				Dom.removeClass(button, CellActionState.SHOW_BY_HOVER);
			});
		}
	}

	static onCounterClick(event: BaseEvent)
	{
		const {row} = event.getData();

		BX.SidePanel.Instance.open(ActionsController.options.groupTaskPath.replace('#group_id#', row.getId()));
	}
}
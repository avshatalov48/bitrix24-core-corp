(function() {

	"use strict";

	/**
	 * @namespace BX.Disk
	 */
	BX.namespace("BX.Disk.Viewer");

	/**
	 *
	 * @param {object} parameters
	 * @constructor
	 */
	BX.Disk.Viewer.Actions = function(parameters)
	{
	};

	BX.Disk.Viewer.Actions.prototype =
	{};

	BX.Disk.Viewer.Actions.checkFirstRun = function ()
	{
		if (!BX.Disk.getDocumentService())
		{
			BX.Disk.InformationPopups.openWindowForSelectDocumentService({});

			return true;
		}

		return false;
	};

	BX.Disk.Viewer.Actions.runActionDefaultEdit = function (item, params, additionalParams)
	{
		if (BX.Disk.Viewer.Actions.checkFirstRun())
		{
			if (additionalParams.modalWindow)
			{
				additionalParams.modalWindow.close();
			}

			return;
		}

		additionalParams = additionalParams || {};
		if (BX.Disk.getDocumentService() !== 'l')
		{
			BX.UI.Viewer.Instance.openByNode(item.sourceNode);
		}

		var paramsToEdit = {
			objectId: params.objectId,
			attachedObjectId: params.attachedObjectId,
			name: params.name,
			serviceCode: BX.Disk.getDocumentService(),
			modalWindow: additionalParams.modalWindow
		};

		BX.Disk.Viewer.Actions.runActionEdit(paramsToEdit);
	};

	BX.Disk.Viewer.Actions.runActionEdit = function (params)
	{
		params = params || {};

		console.log('runActionEdit', params);
		if (params.serviceCode === 'l')
		{
			if (BX.Disk.Document.Local.Instance.isEnabled())
			{
				BX.Disk.Document.Local.Instance.editFile(params);
			}
			else
			{
				BX.Disk.InformationPopups.getHelpDialogToUseLocalService().show();
			}
		}
		else
		{
			var editProcess = new BX.Disk.Document.EditProcess({
				objectId: params.objectId,
				attachedObjectId: params.attachedObjectId,
				serviceCode: params.serviceCode,
				modalWindow: params.modalWindow,
				onAfterSave: function(response) {
					if (response.status === 'success' && BX.getClass('BX.UI.Viewer.Instance'))
					{
						BX.UI.Viewer.Instance.reloadCurrentItem();
					}
				}
			});

			editProcess.start();
		}
	};

	BX.Disk.Viewer.Actions.runActionCopyToMe = function(item, params)
	{
		BX.Disk.showActionModal({
			html: BX.message('DISK_VIEWER_DESCR_PROCESS_SAVE_FILE_TO_OWN_FILES').replace('#NAME#', '<a href="#" class="bx-viewer-file-link">' + item.title +'</a>'),
			showLoaderIcon: true,
			autoHide: false
		});

		var promise;
		if (params.attachedObjectId)
		{
			promise = BX.ajax.runAction('disk.attachedObject.copyTome', {
				analyticsLabel: 'uf.copyTome',
				data: {
					attachedObjectId: params.attachedObjectId
				}
			});
		}
		else if(params.objectId)
		{
			promise = BX.ajax.runAction('disk.api.file.copyTome', {
				analyticsLabel: 'folder.list.copyTome',
				data: {
					fileId: params.objectId
				}
			});
		}

		if(promise)
		{
			promise.then(function (response) {
				BX.Disk.showActionModal({
					html: BX.message('DISK_VIEWER_DESCR_SAVE_FILE_TO_OWN_FILES').replace('#NAME#', '<a target="_blank" href="' + response.data.file.extra.showInGridUri + '" class="bx-viewer-file-link">' + item.title +'</a>'),
					showLoaderIcon: false,
					autoHide: true
				});
			}.bind(this))
		}
	};

})();
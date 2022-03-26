(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Document
	 */
	BX.namespace("BX.Disk.Document");

	/**
	 *
	 * @param {object} parameters
	 * @extends {BX.Disk.Document.EditProcess}
	 * @constructor
	 */
	BX.Disk.Document.CreateProcess = function(parameters)
	{
		BX.Disk.Document.EditProcess.apply(this, arguments);

		this.typeFile = parameters.typeFile;
		this.targetFolderId = parameters.targetFolderId;
		this.serviceCode = parameters.serviceCode;
		this.additionalQueryParameters = parameters.additionalQueryParameters || {};
		this.service = null;
		this.popupConfirm = null;
	};

	BX.Disk.Document.CreateProcess.prototype =
	{
		__proto__: BX.Disk.Document.EditProcess.prototype,
		constructor: BX.Disk.Document.CreateProcess,

		getSliderQueryParameters: function()
		{
			return Object.assign({
				action: 'disk.api.documentService.goToCreate',
				serviceCode: this.serviceCode,
				typeFile: this.typeFile,
				targetFolderId: this.targetFolderId
			}, this.additionalQueryParameters)
		},

		getSliderData: function ()
		{
			return {
				process: 'create',
			}
		},

		buildModalWindow: function ()
		{
			return this.openModal(BX.util.add_url_param('/bitrix/services/main/ajax.php', this.getSliderQueryParameters()));
		},

		getConfirmMessages: function ()
		{
			return {
				title: BX.message('JS_DISK_DOC_PROCESS_NOW_CREATING_IN_SERVICE').replace('#SERVICE#', this.service.name),
				text: BX.message('JS_DISK_DOC_PROCESS_CREATE_DESCR_SAVE_DOC_F').replace('#SAVE_AS_DOC#', BX.message('JS_DISK_DOC_PROCESS_SAVE_AS')),
				saveButton: BX.message('JS_DISK_DOC_PROCESS_SAVE_AS')
			};
		},

		save: function ()
		{
			return this.commit().then(function(response){
				this.objectId = response.objectId;
				this.showSaveFileDialog(response);
			}.bind(this));
		},

		showSaveFileDialog: function (params)
		{
			var extension = params.extension;
			var nameWithoutExtension = params.nameWithoutExtension;

			var saveDialog = BX.create('div', {
				props: {
					className: 'bx-disk-document-edit-confirm'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'bx-disk-document-edit-confirm-title'
						},
						text: BX.message('JS_DISK_DOC_PROCESS_NOW_CREATING_IN_SERVICE').replace('#SERVICE#', this.service.name),
						children: []
					}),
					BX.create('div', {
						props: {
							className: 'bx-disk-document-edit-confirm-text-wrap bx-disk-document-edit-confirm-center'
						},
						children: [
							BX.create('input', {
								props: {
									id: 'wd-new-create-filename',
									className: 'bx-disk-document-edit-name-input',
									type: 'text',
									value: nameWithoutExtension
								}
							}),
							BX.create('span', {
								props: {
									className: 'bx-disk-document-edit-confirm-extension'
								},
								text: extension
							})
						]
					})
				]
			});

			var self = this;
			var saveFileDialog = BX.PopupWindowManager.create('document-save-as-confirm', null, {
	 			content: saveDialog,
				overlay: true,
				buttons: [
					new BX.PopupWindowCustomButton({
						text : BX.message('DISK_JS_BTN_SAVE'),
						className : "ui-btn ui-btn-success",
						events : {
							click: function () {
								var newName = BX('wd-new-create-filename').value;
								if (!newName) {
									BX.focus(BX('wd-new-create-filename'));
									return;
								}

								this.addClassName('ui-btn-clock');
								self.rename({
									newName: newName,
									oldName: nameWithoutExtension,
									extension: extension
								}).then(function(response){
									this.onAfterSave.call(this, response, {
										object: {
											id: params.objectId,
											name: params.name,
											size: params.size,
											sizeInt: params.sizeInt,
											extension: params.extension
										},
										folderName: params.folderName
									});
									saveFileDialog.close();
								}.bind(self));
							}
						}
					}),
					new BX.PopupWindowCustomButton({
						text: BX.message('DISK_JS_BTN_CLOSE'),
						className: 'ui-btn ui-btn-link',
						events: {
							click: function () {
								this.discard();
								saveFileDialog.close();
								this.closeModal();
							}.bind(this)
						}
					})
				],
	 			autoHide: false,
				closeByEsc: false,
				events: { onPopupClose : function() { this.destroy() }}
			});

			saveFileDialog.show();
		},

		rename: function (params)
		{
			var promise = new BX.Promise();

			var newName = params.newName;
			var oldName = params.nameWithoutExtension;
			var extension = params.extension;

			if (newName === oldName || (newName + '.' + extension) === oldName)
			{
				promise.fulfill({
					status: 'success',
					data: {
						newName: oldName
					}
				});

				return promise;
			}

			var renameUrl = this.urlHelper().getUrlRenameFile(
				BX.Disk.Document.EditProcess.prototype.buildLinkToCommit.apply(this)
			);

			return BX.ajax.promise({
				method: 'POST',
				dataType: 'json',
				url: renameUrl,
				data:  {
					objectId: this.objectId,
					newName: newName + '.' + extension,
					sessid: BX.bitrix_sessid()
				}
			});
		},

		commit: function ()
		{
			return BX.Disk.Document.EditProcess.prototype.commit.apply(this, arguments).then(function (response) {
				console.log('create file', response);

				return response;
			});
		},

		buildLinkToCommit: function ()
		{
			return this.urlHelper().getUrlCommitBlank(
				BX.Disk.Document.EditProcess.prototype.buildLinkToCommit.apply(this),
				this.typeFile,
				this.targetFolderId
			);
		},

		buildLinkToDiscard: function ()
		{
			return this.urlHelper().getUrlDiscardBlankFile(
				this.buildLinkToCommit()
			);
		}
	};
})();
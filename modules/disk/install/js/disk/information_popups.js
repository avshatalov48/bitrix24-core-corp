BX.namespace("BX.Disk.InformationPopups");
BX.Disk.InformationPopups = (function ()
{
	return {
		getContentWarningLockedDocument: function (data)
		{
			return '<div class="disk-locked-document-popup">' +
					'<div class="disk-locked-document-popup-container">' +
						'<div class="disk-locked-document-popup-img-container">' +
							'<div class="disk-locked-document-popup-img"></div>' +
						'</div>' +
						'<div class="disk-locked-document-popup-content">' +
							'<h3 class="disk-locked-document-popup-content-title">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_TITLE')+ '</h3>' +
							'<div class="disk-locked-document-popup-content-info">' +
								'<span class="disk-locked-document-popup-content-text">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_WAS_LOCKED_FORKED_COPY').replace('#LINK#', data.link) + '</span>' +
							'</div>' +
							'<a href="#" class="webform-button webform-button-create disk-locked-document-popup-content-button">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_GO_TO_FILE') + '</a>' +
						'</div>' +
					'</div>' +
				'</div>'
			;
		},
		getContentWarningLockedDocumentDesktop: function (data)
		{
			return '<div class="disk-locked-document-popup">' +
					'<div class="disk-locked-document-popup-desktop-container">' +
						'<div class="disk-locked-document-popup-desktop-img-container">' +
							'<div class="disk-locked-document-popup-desktop-img"></div>' +
						'</div>' +
						'<div class="disk-locked-document-popup-desktop-content">' +
							'<h3 class="disk-locked-document-popup-desktop-content-title">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_TITLE')+ '</h3>' +
							'<div class="disk-locked-document-popup-desktop-content-info">' +
								'<span class="disk-locked-document-popup-desktop-content-text">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_WAS_LOCKED_FORKED_COPY').replace('#LINK#', data.link) + '</span>' +
							'</div>' +
							'<span onclick="document.location=\'' + data.link + '\'" class="popup-window-button popup-window-button-accept disk-locked-document-popup-desktop-content-button">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_GO_TO_FILE') + '</span>' +
						'</div>' +
					'</div>' +
				'</div>';
		},
		getContentConflictBetweenFiles: function (forkedFileData, originFileData)
		{
			var originFileLink = '<a class="disk-locked-document-popup-content-link js-disk-open-filefolder" data-href="' + originFileData.path + '" href="#">' + originFileData.name + '</a>';
			var forkedFileLink = '<a class="disk-locked-document-popup-content-link js-disk-open-filefolder" data-href="' + forkedFileData.path + '" href="#">' + forkedFileData.name + '</a>';

			var helpMessage = BX.message('disk_bdisk_file_conflict_between_versions')
				.replace('#FILE#', function() {return originFileLink; })
				.replace('#FILE#', function() {return originFileLink; })
				.replace('#FORKED_FILE#', forkedFileLink)
				.replace('#A#', '<a href="' + BX.message('disk_bdisk_file_conflict_between_versions_helpdesk') + '" target="_blank">')
				.replace('#A_END#', '</a>')
			;

			return '<div class="disk-locked-document-popup">' +
					'<div class="disk-locked-document-popup-desktop-container">' +
						'<div class="disk-locked-document-popup-desktop-img-container">' +
							'<div class="disk-locked-document-popup-desktop-img"></div>' +
						'</div>' +
						'<div class="disk-locked-document-popup-desktop-content">' +
							'<a href="#" class="bx-notifier-item-delete"></a>' +
							'<h3 class="disk-locked-document-popup-desktop-content-title">' + BX.message('disk_bdisk_file_conflict_between_versions_title')+ '</h3>' +
							'<div class="disk-locked-document-popup-desktop-content-info">' +
								'<span class="disk-locked-document-popup-desktop-content-text">' + helpMessage + '</span>' +
							'</div>' +
						'</div>' +
					'</div>' +
				'</div>';
		},
		getContentSwitchOnBDisk: function ()
		{
			var helpMessage = BX.message('DISK_JS_INF_POPUPS_SWITCH_ON_BDISK_DESCR')
				.replace('#A#', '<a href="#" id="bx-open-bdisk-settings">')
				.replace('#A_END#', '</a>')
			;

			return '<div class="disk-locked-document-popup">' +
					'<div class="disk-locked-document-popup-desktop-container">' +
						'<div class="disk-locked-document-popup-desktop-img-container">' +
							'<div class="disk-locked-document-popup-desktop-img"></div>' +
						'</div>' +
						'<div class="disk-locked-document-popup-desktop-content">' +
							'<a href="#" class="bx-notifier-item-delete"></a>' +
							'<h3 class="disk-locked-document-popup-desktop-content-title">' + BX.message('DISK_JS_INF_POPUPS_SWITCH_ON_BDISK_TITLE')+ '</h3>' +
							'<div class="disk-locked-document-popup-desktop-content-info">' +
								'<span class="disk-locked-document-popup-desktop-content-text">' + helpMessage + '</span>' +
							'</div>' +
						'</div>' +
					'</div>' +
				'</div>';
		},
		getContentLockedByProgram: function (fileData, program)
		{
			var originFileLink = '<a class="disk-locked-document-popup-content-link js-disk-open-filefolder" data-href="' + fileData.path + '" href="#">' + fileData.name + '</a>';

			var helpMessage = BX.message('disk_bdisk_file_conflict_locked_by_app')
				.replace('#FILE#', function() {return originFileLink; })
				.replace('#FILE#', function() {return originFileLink; })
				.replace('#PROGRAM#', function() {return program; })
				.replace('#PROGRAM#', function() {return program; })
				.replace('#A#', '<a href="' + BX.message('disk_bdisk_file_conflict_locked_by_app_helpdesk') + '" target="_blank">')
				.replace('#A_END#', '</a>')
			;

			return '<div class="disk-locked-document-popup">' +
					'<div class="disk-locked-document-popup-desktop-container">' +
						'<div class="disk-locked-document-popup-desktop-img-container">' +
							'<div class="disk-locked-document-popup-desktop-img"></div>' +
						'</div>' +
						'<div class="disk-locked-document-popup-desktop-content">' +
							'<a href="#" class="bx-notifier-item-delete"></a>' +
							'<h3 class="disk-locked-document-popup-desktop-content-title">' + BX.message('disk_bdisk_file_conflict_locked_by_app_title')+ '</h3>' +
							'<div class="disk-locked-document-popup-desktop-content-info">' +
								'<span class="disk-locked-document-popup-desktop-content-text">' + helpMessage + '</span>' +
							'</div>' +
						'</div>' +
					'</div>' +
				'</div>';
		},
		showWarningLockedDocument: function (data)
		{
			(new BX.PopupWindow('testy', null, {
				content: BX.create('div', {html: BX.Disk.InformationPopups.getContentWarningLockedDocument({
					link: data.link
				})}),
				autoHide: true,
				lightShadow: true,
				closeIcon: {right: "20px", top: "10px"},
				events: {
					onPopupClose: function ()
					{
						this.destroy();
					}
				},
				buttons: []
			})).show();
		},
		openWindowForSelectDocumentService: function (params) {
			var viewInUf = params.viewInUf || false;
			var currentSelection = BX.Disk.getDocumentService();
			var newSelectedService = '';
			var defaultOnSave = function (service) {
				if (service === 'l' && !BX.Disk.Document.Local.Instance.isEnabled())
				{
					this.getHelpDialogToUseLocalService().show();
					return;
				}

				BX.Disk.saveDocumentService(service);
				BX.PopupWindowManager.getCurrentPopup().destroy();
			}.bind(this);

			var buttons = [
				new BX.PopupWindowButton({
					text: BX.message('DISK_JS_BTN_SAVE'),
					className: "popup-window-button-accept",
					events: {
						click: function (e) {
							defaultOnSave(newSelectedService);
							if(BX.type.isFunction(params.onSave))
							{
								params.onSave(newSelectedService)
							}

							e.preventDefault();
						}
					}
				}),
				new BX.PopupWindowButton({
					text: BX.message('DISK_JS_BTN_CLOSE'),
					events: {
						click: function (e) {
							BX.PopupWindowManager.getCurrentPopup().destroy();
							BX.PreventDefault(e);
							return false;
						}
					}
				})
			];

			var currentServiceIsCloud = false;
			if (currentSelection !== 'l' && currentSelection !== 'onlyoffice' && currentSelection)
			{
				currentServiceIsCloud = true;
			}
			if (!currentSelection && BX.Disk.isAvailableOnlyOffice())
			{
				currentSelection = 'onlyoffice';
			}
			else if(!currentSelection)
			{
				currentSelection = 'l';
			}
			newSelectedService = currentSelection;

			var suffix = viewInUf? '' : '2';
			var lang = BX.message('LANGUAGE_ID');
			var imageSrc = '/bitrix/images/disk/disk_description' + suffix + '_en.png';
			if(lang == 'kz')
				lang = 'ru';
			switch(lang)
			{
				case 'ru':
				case 'en':
				case 'de':
				case 'ua':
				case 'br':
				case 'la':
				case 'sc':
				case 'tc':
					imageSrc = '/bitrix/images/disk/disk_description' + suffix + '_' + lang + '.png';
					break;
			}

			var content =
				'<div class="bx-disk-info-popup-cont-title">' +
					BX.message('DISK_JS_SERVICE_CHOICE_TITLE') +
				'</div>' +
				'<div class="bx-disk-info-popup-btn-wrap">' +
					'<span data-service="l" id="bx-disk-info-popup-btn-local" class="bx-disk-info-popup-btn bx-disk-info-popup-btn-local ' + (currentSelection === 'l' ? 'bx-disk-info-popup-btn-active' : '') + ' ">' +
						'<span class="bx-disk-info-popup-btn-text">' + BX.message('DISK_JS_SERVICE_LOCAL_TITLE') + '</span>' +
						'<span class="bx-disk-info-popup-btn-descript">' +
							BX.message('DISK_JS_SERVICE_LOCAL_TEXT') +
						'</span>' +
						'<span class="bx-disk-info-popup-btn-check"></span>' +
					'</span>' +
					'<span data-service="gdrive" id="bx-disk-info-popup-btn-cloud" class="bx-disk-info-popup-btn bx-disk-info-popup-btn-cloud ' + (currentServiceIsCloud ? 'bx-disk-info-popup-btn-active' : '') + ' ">' +
						'<span class="bx-disk-info-popup-btn-text">' + BX.message('DISK_JS_SERVICE_CLOUD_TITLE') + '</span>' +
						'<span class="bx-disk-info-popup-btn-descript">' +
							BX.message('DISK_JS_SERVICE_CLOUD_TEXT') +
						'</span>' +
						'<span class="bx-disk-info-popup-btn-check"></span>' +
					'</span>' +
					'<span data-service="onlyoffice" ' + (BX.Disk.isAvailableOnlyOffice()? '' : 'style="display:none;"') +' id="bx-disk-info-popup-btn-b24" class="bx-disk-info-popup-btn bx-disk-info-popup-btn-b24 ' + (currentSelection === 'onlyoffice'? 'bx-disk-info-popup-btn-active' : '') + ' ">' +
						'<span class="bx-disk-info-popup-btn-text">' + BX.message('DISK_JS_SERVICE_B24_DOCS_TITLE') + '</span>' +
						'<span class="bx-disk-info-popup-btn-descript">' +
							BX.message('DISK_JS_SERVICE_B24_DOCS_TEXT') +
						'</span>' +
					'	<span class="bx-disk-info-popup-btn-check"></span>' +
					'</span>' +
				'</div>' +
				'<div class="bx-disk-info-descript">' +
					(viewInUf? BX.message('DISK_JS_SERVICE_HELP_TEXT') : BX.message('DISK_JS_SERVICE_HELP_TEXT_2')) +
					'<img style="height: 182px;" class="bx-disk-info-descript-img" src="' + imageSrc + '" alt=""/>' +
				'</div>' +
				'<div style="margin-top: 10px">' +
					'<a href="/" id="bx-disk-info-popup-helpdesk" style="font-size: 14px">' + BX.message('DISK_JS_HELP_WITH_BDISK') + '</a>' +
				'</div>'
				;
			var contentNode = BX.create('div', {html: content});

			var popup = BX.Disk.modalWindow({
				modalId: 'bx-disk-select-doc-service',
				events: {
					onAfterPopupShow: function () {
						BX.bind(BX('bx-disk-info-popup-helpdesk'), 'click', function (e) {
							if (top.BX.Helper)
							{
								top.BX.Helper.show("redirect=detail&code=8626407");
							}
							e.preventDefault();
							popup.destroy();
						});

						BX.bindDelegate(contentNode, 'click', {className: 'bx-disk-info-popup-btn'}, function(e) {
							var targetNode = this;
							newSelectedService = targetNode.dataset.service;

							if (BX.hasClass(targetNode, 'bx-disk-info-popup-btn-active'))
							{
								return;
							}

							contentNode.querySelector('.bx-disk-info-popup-btn-active').classList.remove('bx-disk-info-popup-btn-active');
							BX.toggleClass(targetNode, 'bx-disk-info-popup-btn-active');
						});
					},
					onPopupClose: function () {
						this.destroy();
					}
				},
				title: BX.message('DISK_JS_SERVICE_CHOICE_TITLE_SMALL'),
				content: [contentNode],
				buttons: buttons
			});

		},
		getHelpDialogToUseLocalService: function ()
		{
			var title = BX.message('DISK_JS_INF_POPUPS_EDIT_IN_LOCAL_SERVICE');
			var message = BX.message('DISK_JS_INF_POPUPS_SERVICE_LOCAL_INSTALL_DESKTOP');
			var helpDiskDialog = BX.create('div', {
				props: {
					className: 'bx-viewer-confirm'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'bx-viewer-confirm-title'
						},
						text: title,
						children: []
					}),
					BX.create('div', {
						props: {
							className: 'bx-viewer-confirm-text-wrap'
						},
						children: [
							BX.create('span', {
								props: {
									className: 'bx-viewer-confirm-text-alignment'
								}
							}),
							BX.create('span', {
								props: {
									className: 'bx-viewer-confirm-text'
								},
								html: message
							})
						]
					})
				]
			});

			var popup = BX.PopupWindowManager.create('helpDialogToUseLocalService', null, {
				content: helpDiskDialog,
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message('DISK_JS_BTN_DOWNLOAD'),
						className: "popup-window-button-accept",
						events: {
							click: function () {
								document.location.href = (BX.browser.IsMac() ? "https://dl.bitrix24.com/b24/bitrix24_desktop.dmg" : "https://dl.bitrix24.com/b24/bitrix24_desktop.exe");
							}
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('DISK_JS_BTN_CANCEL'),
						events: {
							click: function () {
								popup.close();
							}
						}
					})
				]
			});

			return popup;
		}
	};
})();

import {Text} from "main.core";
import {SaveButton} from "ui.buttons";

export default class LegacyPopup
{
	userBoxNode: HTMLElement = null;
	isChangedRights: boolean = false;
	storageNewRights: Object = {};
	originalRights: Object = {};
	detachedRights: Object = {};
	moduleTasks: Object = {};

	entityToNewShared: Object = {};
	loadedReadOnlyEntityToNewShared: Object = {};
	entityToNewSharedMaxTaskName: string = '';
	ajaxUrl: string = '/bitrix/components/bitrix/disk.folder.list/ajax.php';
	destFormName: string = 'folder-list-destFormName';

	constructor()
	{
	}

	showSharingDetailWithChangeRights(params)
	{
		this.entityToNewShared = {};
		this.loadedReadOnlyEntityToNewShared = {};

		params = params || {};
		const objectId = params.object.id;

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSharingDetailChangeRights'),
			{
				id: 'folder_list_sharing_detail_object_' + objectId,
				responseType: 'json',
				postData: {
					objectId: objectId
				},
				afterSuccessLoad: (response) => {
					if (response.status !== 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message
						})
					}

					const objectOwner = {
						name: response.owner.name,
						avatar: response.owner.avatar,
						link: response.owner.link
					};

					BX.Disk.modalWindow({
						modalId: 'bx-disk-detail-sharing-folder-change-right',
						title: BX.message('JS_DISK_SHARING_LEGACY_POPUP_TITLE_MODAL_3'),
						contentClassName: '',
						contentStyle: {},
						events: {
							onAfterPopupShow: () => {

								BX.addCustomEvent('onChangeRightOfSharing', this.onChangeRightOfSharing.bind(this));

								for (let i in response.members)
								{
									if (!response.members.hasOwnProperty(i))
									{
										continue;
									}

									this.entityToNewShared[response.members[i].entityId] = {
										item: {
											id: response.members[i].entityId,
											name: response.members[i].name,
											avatar: response.members[i].avatar
										},
										type: response.members[i].type,
										right: response.members[i].right
									};
								}

								BX.SocNetLogDestination.init({
									name: this.destFormName,
									searchInput: BX('feed-add-post-destination-input'),
									bindMainPopup: {
										'node': BX('feed-add-post-destination-container'),
										'offsetTop': '5px',
										'offsetLeft': '15px'
									},
									bindSearchPopup: {
										'node': BX('feed-add-post-destination-container'),
										'offsetTop': '5px',
										'offsetLeft': '15px'
									},
									callback: {
										select: this.onSelectDestination.bind(this),
										unSelect: this.onUnSelectDestination.bind(this),
										openDialog: this.onOpenDialogDestination.bind(this),
										closeDialog: this.onCloseDialogDestination.bind(this),
										openSearch: this.onOpenSearchDestination.bind(this),
										closeSearch: this.onCloseSearchDestination.bind(this)
									},
									items: response.destination.items,
									itemsLast: response.destination.itemsLast,
									itemsSelected: response.destination.itemsSelected
								});

								const BXSocNetLogDestinationFormName = this.destFormName;
								BX.bind(BX('feed-add-post-destination-container'), 'click', function (e) {
									BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);
									BX.PreventDefault(e);
								});
								BX.bind(BX('feed-add-post-destination-input'), 'keyup', this.onKeyUpDestination.bind(this));
								BX.bind(BX('feed-add-post-destination-input'), 'keydown', this.onKeyDownDestination.bind(this));

							},
							onPopupClose: () => {
								if (BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog())
								{
									BX.SocNetLogDestination.closeDialog()
								}
								BX.removeCustomEvent('onChangeRightOfSharing', this.onChangeRightOfSharing.bind(this));
							}
						},
						content: [
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content'
								},
								children: [
									BX.create('table', {
										props: {
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_OWNER') + '</td>' +
													'</tr>'
											}),
											BX.create('tr', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href="' + objectOwner.link + '"><span class="bx-disk-filepage-used-people-avatar" style="background-image: url(\'' + encodeURI(objectOwner.avatar) + '\');"></span>' + Text.encode(objectOwner.name) + '</a></td>' +
													'</tr>'
											})
										]
									}),
									BX.create('table', {
										props: {
											id: 'bx-disk-popup-shared-people-list',
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS_USER') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col3"></td>' +
													'</tr>'
											})
										]
									}),
									BX.create('div', {
										props: {
											id: 'feed-add-post-destination-container',
											className: 'feed-add-post-destination-wrap'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'feed-add-post-destination-item'
												}
											}),
											BX.create('span', {
												props: {
													id: 'feed-add-post-destination-input-box',
													className: 'feed-add-destination-input-box'
												},
												style: {
													background: 'transparent'
												},
												children: [
													BX.create('input', {
														props: {
															type: 'text',
															value: '',
															id: 'feed-add-post-destination-input',
															className: 'feed-add-destination-inp'
														}
													})
												]
											}),
											BX.create('a', {
												props: {
													href: '#',
													id: 'bx-destination-tag',
													className: 'feed-add-destination-link'
												},
												style: {
													background: 'transparent'
												},
												text: BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_ADD_RIGHTS_USER'),
												events: {
													click: () => {}
												}
											})
										]
									})
								]
							})
						],
						buttons: [
							new SaveButton({
								events: {
									click: () => {
										BX.Disk.ajax({
											method: 'POST',
											dataType: 'json',
											url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'changeSharingAndRights'),
											data: {
												objectId: objectId,
												entityToNewShared: this.entityToNewShared
											},
											onsuccess: (response) => {
												if (!response)
												{
													return;
												}
												response.message = BX.message('JS_DISK_SHARING_LEGACY_POPUP_OK_FILE_SHARE_MODIFIED').replace('#FILE#', params.object.name);

												BX.Disk.showModalWithStatusAction(response);
											}
										});

										BX.PopupWindowManager.getCurrentPopup().close();
									}
								}
							}),
							new BX.UI.CloseButton({
								events: {
									click: function () {
										BX.PopupWindowManager.getCurrentPopup().close();
									}
								}
							}),
						]
					});
				}
			}
		);
	};

	showSharingDetailWithSharing(params)
	{
		this.entityToNewShared = {};
		this.loadedReadOnlyEntityToNewShared = {};

		params = params || {};
		const objectId = params.object.id;

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSharingDetailAppendSharing'),
			{
				id: 'folder_list_sharing_detail_object_' + objectId,
				responseType: 'json',
				postData: {
					objectId: objectId
				},
				afterSuccessLoad: (response) => {
					if (response.status !== 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message
						})
					}

					const objectOwner = {
						name: response.owner.name,
						avatar: response.owner.avatar,
						link: response.owner.link
					};
					this.entityToNewSharedMaxTaskName = response.owner.maxTaskName;

					BX.Disk.modalWindow({
						modalId: 'bx-disk-detail-sharing-folder-change-right',
						title: BX.message('JS_DISK_SHARING_LEGACY_POPUP_TITLE_MODAL_3'),
						contentClassName: '',
						contentStyle: {},
						events: {
							onAfterPopupShow: () => {

								BX.addCustomEvent('onChangeRightOfSharing', this.onChangeRightOfSharing.bind(this));

								for (let i in response.members)
								{
									if (!response.members.hasOwnProperty(i))
									{
										continue;
									}

									this.entityToNewShared[response.members[i].entityId] = {
										item: {
											id: response.members[i].entityId,
											name: response.members[i].name,
											avatar: response.members[i].avatar
										},
										type: response.members[i].type,
										right: response.members[i].right
									};
								}
								this.loadedReadOnlyEntityToNewShared = BX.clone(this.entityToNewShared, true);

								BX.SocNetLogDestination.init({
									name : this.destFormName,
									searchInput : BX('feed-add-post-destination-input'),
									bindMainPopup : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
									bindSearchPopup : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
									callback : {
										select: this.onSelectDestination.bind(this),
										unSelect: this.onUnSelectDestination.bind(this),
										openDialog: this.onOpenDialogDestination.bind(this),
										closeDialog: this.onCloseDialogDestination.bind(this),
										openSearch: this.onOpenSearchDestination.bind(this),
										closeSearch: this.onCloseSearchDestination.bind(this)
									},
									items: response.destination.items,
									itemsLast: response.destination.itemsLast,
									itemsSelected : response.destination.itemsSelected
								});

								const BXSocNetLogDestinationFormName = this.destFormName;
								BX.bind(BX('feed-add-post-destination-container'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);BX.PreventDefault(e); });
								BX.bind(BX('feed-add-post-destination-input'), 'keyup', this.onKeyUpDestination.bind(this));
								BX.bind(BX('feed-add-post-destination-input'), 'keydown', this.onKeyDownDestination.bind(this));
							},
							onPopupClose: () => {
								if(BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog())
								{
									BX.SocNetLogDestination.closeDialog()
								}

								BX.removeCustomEvent('onChangeRightOfSharing', this.onChangeRightOfSharing.bind(this));
							}
						},
						content: [
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content'
								},
								children: [
									BX.create('table', {
										props: {
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_OWNER') + '</td>' +
												'</tr>'
											}),
											BX.create('tr', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href="' + objectOwner.link + '"><span class="bx-disk-filepage-used-people-avatar" style="background-image: url(\'' + encodeURI(objectOwner.avatar) + '\');"></span>' + Text.encode(objectOwner.name) + '</a></td>' +
												'</tr>'
											})
										]
									}),
									BX.create('table', {
										props: {
											id: 'bx-disk-popup-shared-people-list',
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS_USER') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col3"></td>' +
												'</tr>'
											})
										]
									}),
									BX.create('div', {
										props: {
											id: 'feed-add-post-destination-container',
											className: 'feed-add-post-destination-wrap'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'feed-add-post-destination-item'
												}
											}),
											BX.create('span', {
												props: {
													id: 'feed-add-post-destination-input-box',
													className: 'feed-add-destination-input-box'
												},
												style: {
													background: 'transparent'
												},
												children: [
													BX.create('input', {
														props: {
															type: 'text',
															value: '',
															id: 'feed-add-post-destination-input',
															className: 'feed-add-destination-inp'
														}
													})
												]
											}),
											BX.create('a', {
												props: {
													href: '#',
													id: 'bx-destination-tag',
													className: 'feed-add-destination-link'
												},
												style: {
													background: 'transparent'
												},
												text: BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_ADD_RIGHTS_USER'),
												events: {
													click: () => {}
												}
											})
										]
									})
								]
							})
						],
						buttons: [
							new SaveButton({
								events: {
									click: () => {
										BX.Disk.ajax({
											method: 'POST',
											dataType: 'json',
											url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'appendSharing'),
											data: {
												objectId: objectId,
												entityToNewShared: this.entityToNewShared
											},
											onsuccess: (response) => {
												if (!response)
												{
													return;
												}

												BX.Disk.showModalWithStatusAction(response);
											}
										});

										BX.PopupWindowManager.getCurrentPopup().close();
									}
								}
							}),
							new BX.UI.CloseButton({
								events: {
									click: function () {
										BX.PopupWindowManager.getCurrentPopup().close();
									}
								}
							}),
						]
					});
				}
			}
		);
	}

	showSharingDetailWithoutEdit(params)
	{
		params = params || {};

		BX.Disk.showSharingDetailWithoutEdit({
			ajaxUrl: '/bitrix/components/bitrix/disk.folder.list/ajax.php',
			object: params.object,
		});
	}

	onSelectDestination(item, type, search)
	{
		this.entityToNewShared[item.id] = this.entityToNewShared[item.id] || {};
		BX.Disk.appendNewShared({
			maxTaskName: this.entityToNewSharedMaxTaskName,
			readOnly: !!this.loadedReadOnlyEntityToNewShared[item.id],
			destFormName: this.destFormName,
			item: item,
			type: type,
			right: this.entityToNewShared[item.id].right || 'disk_access_edit'
		});

		this.entityToNewShared[item.id] = {
			item: item,
			type: type,
			right: this.entityToNewShared[item.id].right || 'disk_access_edit'
		};
	};

	onUnSelectDestination(item, type, search)
	{
		const entityId = item.id;

		if (!!this.loadedReadOnlyEntityToNewShared[entityId])
		{
			return false;
		}

		delete this.entityToNewShared[entityId];

		let child = BX.findChild(BX('bx-disk-popup-shared-people-list'), {attribute: {'data-dest-id': '' + entityId + ''}}, true);
		if (child)
		{
			BX.remove(child);
		}
	};

	onChangeRightOfSharing(entityId, taskName)
	{
		if (this.entityToNewShared[entityId])
		{
			this.entityToNewShared[entityId].right = taskName;
		}
	};

	onOpenDialogDestination()
	{
		BX.style(BX('feed-add-post-destination-input-box'), 'display', 'inline-block');
		BX.style(BX('bx-destination-tag'), 'display', 'none');
		BX.focus(BX('feed-add-post-destination-input'));
		if (BX.SocNetLogDestination.popupWindow)
		{
			BX.SocNetLogDestination.popupWindow.adjustPosition({forceTop: true});
		}
	};

	onCloseDialogDestination()
	{
		let input = BX('feed-add-post-destination-input');
		if (!BX.SocNetLogDestination.isOpenSearch() && input && input.value.length <= 0)
		{
			BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
			BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
		}
	};

	onOpenSearchDestination()
	{
		if (BX.SocNetLogDestination.popupSearchWindow)
		{
			BX.SocNetLogDestination.popupSearchWindow.adjustPosition({forceTop: true});
		}
	};

	onCloseSearchDestination()
	{
		let input = BX('feed-add-post-destination-input');
		if (!BX.SocNetLogDestination.isOpenSearch() && input && input.value.length > 0)
		{
			BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
			BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
			BX('feed-add-post-destination-input').value = '';
		}
	};

	onKeyDownDestination(event)
	{
		let BXSocNetLogDestinationFormName = this.destFormName;
		if (event.keyCode == 8 && BX('feed-add-post-destination-input').value.length <= 0)
		{
			BX.SocNetLogDestination.sendEvent = false;
			BX.SocNetLogDestination.deleteLastItem(BXSocNetLogDestinationFormName);
		}

		return true;
	};

	onKeyUpDestination(event)
	{
		const BXSocNetLogDestinationFormName = this.destFormName;
		if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
		{
			return false;
		}

		if (event.keyCode == 13)
		{
			BX.SocNetLogDestination.selectFirstSearchItem(BXSocNetLogDestinationFormName);
			return BX.PreventDefault(event);
		}
		if (event.keyCode == 27)
		{
			BX('feed-add-post-destination-input').value = '';
		}
		else
		{
			BX.SocNetLogDestination.search(BX('feed-add-post-destination-input').value, true, BXSocNetLogDestinationFormName);
		}

		if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
		{
			BX.SocNetLogDestination.closeDialog();
		}

		if (event.keyCode == 8)
		{
			BX.SocNetLogDestination.sendEvent = true;
		}
		return BX.PreventDefault(event);
	};
}
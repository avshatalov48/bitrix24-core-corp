import { Text, Tag, Loc, Event } from 'main.core';
import { SaveButton } from 'ui.buttons';

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
	{}

	showSharingDetailWithChangeRights(params)
	{
		this.entityToNewShared = {};
		this.loadedReadOnlyEntityToNewShared = {};

		params = params || {};
		const objectId = params.object.id;

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSharingDetailChangeRights'),
			{
				id: `folder_list_sharing_detail_object_${objectId}`,
				responseType: 'json',
				postData: {
					objectId,
				},
				afterSuccessLoad: (response) => {
					if (response.status !== 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message,
						});
					}

					if (response.unifiedLink)
					{
						const accessLevel = response.unifiedLink.availableAccessLevels.find((accessLevel) => {
							return accessLevel.value === response.unifiedLink.currentAccessLevel;
						});
						const accessLevelNode = Tag.render`<span class="bx-disk-filepage-used-people-permission">${accessLevel.name}</span>`;

						Event.bind(accessLevelNode, 'click', (event) => {
							const menuId = 'access-level-menu';
							const menuItems = response.unifiedLink.availableAccessLevels.map((accessLevel) => {
								return {
									id: accessLevel.value,
									text: accessLevel.name,
									onclick: () => {
										accessLevelNode.textContent = accessLevel.name;
										if (this.entityToNewShared.unifiedLink.currentAccessLevel !== accessLevel.value)
										{
											this.entityToNewShared.unifiedLink.newAccessLevel = accessLevel.value;
										}
										BX.PopupMenu.destroy(menuId);
									},
								};
							});

							BX.PopupMenu.show(menuId, event.target, menuItems, {
								autoHide: true,
								offsetTop: 0,
								offsetLeft: 0,
								angle: {
									position: 'top',
									offset: 45,
								},
								overlay: {
									opacity: 0.01,
								},
								events: {
									onPopupClose() {
										BX.PopupMenu.destroy(menuId);
									},
								},
							});
						});

						var unifiedLinkElement = Tag.render`
							<table class="bx-disk-popup-shared-people-list" id="bx-disk-popup-shared-universal-list">
								<tr>
									<td class="bx-disk-popup-shared-people-list-head-col1"></td>
									<td class="bx-disk-popup-shared-people-list-head-col2">${Loc.getMessage('JS_DISK_SHARING_LEGACY_POPUP_SHARING_LABEL_NAME_RIGHTS')}</td>
									<td class="bx-disk-popup-shared-people-list-head-col3"></td>
								</tr>
								<tr>
									<td class="bx-disk-popup-shared-people-list-head-col1">
										<span href="" class="bx-disk-filepage-used-people-link">
											<span class="bx-disk-filepage-used-people-avatar link" style="--ui-icon-set__icon-size: 15px;"></span>
											${Loc.getMessage('JS_DISK_SHARING_LEGACY_POPUP_UNIFIED_RIGHT_USERS')}
										</span>
									</td>
									<td class="bx-disk-popup-shared-people-list-head-col2">
										${accessLevelNode}
									</td>
									<td class="bx-disk-popup-shared-people-list-head-col3"></td>
								</tr>
							</table>
						`;
					}

					const objectOwner = {
						name: response.owner.name,
						avatar: response.owner.avatar,
						link: response.owner.link,
					};

					BX.Disk.modalWindow({
						modalId: 'bx-disk-detail-sharing-folder-change-right',
						title: BX.message('JS_DISK_SHARING_LEGACY_POPUP_TITLE_MODAL_3'),
						contentClassName: '',
						contentStyle: {},
						events: {
							onAfterPopupShow: () => {
								BX.addCustomEvent('onChangeRightOfSharing', this.onChangeRightOfSharing.bind(this));

								for (const i in response.members)
								{
									if (!response.members.hasOwnProperty(i))
									{
										continue;
									}

									this.entityToNewShared[response.members[i].entityId] = {
										item: {
											id: response.members[i].entityId,
											name: response.members[i].name,
											avatar: response.members[i].avatar,
										},
										type: response.members[i].type,
										right: response.members[i].right,
									};
								}

								if (response.unifiedLink)
								{
									this.entityToNewShared.unifiedLink = response.unifiedLink;
								}

								BX.SocNetLogDestination.init({
									name: this.destFormName,
									searchInput: BX('feed-add-post-destination-input'),
									bindMainPopup: {
										node: BX('feed-add-post-destination-container'),
										offsetTop: '5px',
										offsetLeft: '15px',
									},
									bindSearchPopup: {
										node: BX('feed-add-post-destination-container'),
										offsetTop: '5px',
										offsetLeft: '15px',
									},
									callback: {
										select: this.onSelectDestination.bind(this),
										unSelect: this.onUnSelectDestination.bind(this),
										openDialog: this.onOpenDialogDestination.bind(this),
										closeDialog: this.onCloseDialogDestination.bind(this),
										openSearch: this.onOpenSearchDestination.bind(this),
										closeSearch: this.onCloseSearchDestination.bind(this),
									},
									items: response.destination.items,
									itemsLast: response.destination.itemsLast,
									itemsSelected: response.destination.itemsSelected,
								});

								const BXSocNetLogDestinationFormName = this.destFormName;
								BX.bind(BX('feed-add-post-destination-container'), 'click', (e) => {
									BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);
									BX.PreventDefault(e);
								});
								BX.bind(BX('feed-add-post-destination-input'), 'keyup', this.onKeyUpDestination.bind(this));
								BX.bind(BX('feed-add-post-destination-input'), 'keydown', this.onKeyDownDestination.bind(this));
							},
							onPopupClose: () => {
								if (BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog())
								{
									BX.SocNetLogDestination.closeDialog();
								}
								BX.removeCustomEvent('onChangeRightOfSharing', this.onChangeRightOfSharing.bind(this));
							},
						},
						content: [
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content',
								},
								children: [
									BX.create('table', {
										props: {
											className: 'bx-disk-popup-shared-people-list',
										},
										children: [
											BX.create('thead', {
												html: '<tr>'
													+ `<td class="bx-disk-popup-shared-people-list-head-col1">${BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_OWNER')}</td>`
													+ '</tr>',
											}),
											BX.create('tr', {
												html: '<tr>'
													+ `<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href="${objectOwner.link}"><span class="bx-disk-filepage-used-people-avatar" style="background-image: url('${encodeURI(objectOwner.avatar)}');"></span>${Text.encode(objectOwner.name)}</a></td>`
													+ '</tr>',
											}),
										],
									}),
									unifiedLinkElement,
									BX.create('table', {
										props: {
											id: 'bx-disk-popup-shared-people-list',
											className: 'bx-disk-popup-shared-people-list',
										},
										children: [
											BX.create('thead', {
												html: '<tr>'
													+ `<td class="bx-disk-popup-shared-people-list-head-col1">${BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS_USER')}</td>`
													+ `<td class="bx-disk-popup-shared-people-list-head-col2">${BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS')}</td>`
													+ '<td class="bx-disk-popup-shared-people-list-head-col3"></td>'
													+ '</tr>',
											}),
										],
									}),
									BX.create('div', {
										props: {
											id: 'feed-add-post-destination-container',
											className: 'feed-add-post-destination-wrap',
										},
										children: [
											BX.create('span', {
												props: {
													className: 'feed-add-post-destination-item',
												},
											}),
											BX.create('span', {
												props: {
													id: 'feed-add-post-destination-input-box',
													className: 'feed-add-destination-input-box',
												},
												style: {
													background: 'transparent',
												},
												children: [
													BX.create('input', {
														props: {
															type: 'text',
															value: '',
															id: 'feed-add-post-destination-input',
															className: 'feed-add-destination-inp',
														},
													}),
												],
											}),
											BX.create('a', {
												props: {
													href: '#',
													id: 'bx-destination-tag',
													className: 'feed-add-destination-link',
												},
												style: {
													background: 'transparent',
												},
												text: BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_ADD_RIGHTS_USER'),
												events: {
													click: () => {},
												},
											}),
										],
									}),
								],
							}),
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
												objectId,
												entityToNewShared: this.entityToNewShared,
											},
											onsuccess: (response) => {
												if (!response)
												{
													return;
												}
												let name = params.object.name.split('.');
												const ext = name.pop().toLowerCase();
												name = name.join('.');
												if (name && ext === 'board')
												{
													response.message = BX.message('JS_DISK_SHARING_LEGACY_POPUP_OK_BOARD_SHARE_MODIFIED').replace('#FILE#', name);
												}
												else
												{
													response.message = BX.message('JS_DISK_SHARING_LEGACY_POPUP_OK_FILE_SHARE_MODIFIED').replace('#FILE#', params.object.name);
												}

												BX.Disk.showModalWithStatusAction(response);
											},
										});

										BX.PopupWindowManager.getCurrentPopup().close();
									},
								},
							}),
							new BX.UI.CloseButton({
								events: {
									click() {
										BX.PopupWindowManager.getCurrentPopup().close();
									},
								},
							}),
						],
					});
				},
			},
		);
	}

	showSharingDetailWithSharing(params)
	{
		this.entityToNewShared = {};
		this.loadedReadOnlyEntityToNewShared = {};

		params = params || {};
		const objectId = params.object.id;

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSharingDetailAppendSharing'),
			{
				id: `folder_list_sharing_detail_object_${objectId}`,
				responseType: 'json',
				postData: {
					objectId,
				},
				afterSuccessLoad: (response) => {
					if (response.status !== 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message,
						});
					}

					const objectOwner = {
						name: response.owner.name,
						avatar: response.owner.avatar,
						link: response.owner.link,
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

								for (const i in response.members)
								{
									if (!response.members.hasOwnProperty(i))
									{
										continue;
									}

									this.entityToNewShared[response.members[i].entityId] = {
										item: {
											id: response.members[i].entityId,
											name: response.members[i].name,
											avatar: response.members[i].avatar,
										},
										type: response.members[i].type,
										right: response.members[i].right,
									};
								}
								this.loadedReadOnlyEntityToNewShared = BX.clone(this.entityToNewShared, true);

								BX.SocNetLogDestination.init({
									name: this.destFormName,
									searchInput: BX('feed-add-post-destination-input'),
									bindMainPopup: { node: BX('feed-add-post-destination-container'), offsetTop: '5px', offsetLeft: '15px' },
									bindSearchPopup: { node: BX('feed-add-post-destination-container'), offsetTop: '5px', offsetLeft: '15px' },
									callback: {
										select: this.onSelectDestination.bind(this),
										unSelect: this.onUnSelectDestination.bind(this),
										openDialog: this.onOpenDialogDestination.bind(this),
										closeDialog: this.onCloseDialogDestination.bind(this),
										openSearch: this.onOpenSearchDestination.bind(this),
										closeSearch: this.onCloseSearchDestination.bind(this),
									},
									items: response.destination.items,
									itemsLast: response.destination.itemsLast,
									itemsSelected: response.destination.itemsSelected,
								});

								const BXSocNetLogDestinationFormName = this.destFormName;
								BX.bind(BX('feed-add-post-destination-container'), 'click', (e) =>
								{ BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName); BX.PreventDefault(e);
								});
								BX.bind(BX('feed-add-post-destination-input'), 'keyup', this.onKeyUpDestination.bind(this));
								BX.bind(BX('feed-add-post-destination-input'), 'keydown', this.onKeyDownDestination.bind(this));
							},
							onPopupClose: () => {
								if (BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog())
								{
									BX.SocNetLogDestination.closeDialog();
								}

								BX.removeCustomEvent('onChangeRightOfSharing', this.onChangeRightOfSharing.bind(this));
							},
						},
						content: [
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content',
								},
								children: [
									BX.create('table', {
										props: {
											className: 'bx-disk-popup-shared-people-list',
										},
										children: [
											BX.create('thead', {
												html: '<tr>'
													+ `<td class="bx-disk-popup-shared-people-list-head-col1">${BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_OWNER')}</td>`
												+ '</tr>',
											}),
											BX.create('tr', {
												html: '<tr>'
													+ `<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href="${objectOwner.link}"><span class="bx-disk-filepage-used-people-avatar" style="background-image: url('${encodeURI(objectOwner.avatar)}');"></span>${Text.encode(objectOwner.name)}</a></td>`
												+ '</tr>',
											}),
										],
									}),
									BX.create('table', {
										props: {
											id: 'bx-disk-popup-shared-people-list',
											className: 'bx-disk-popup-shared-people-list',
										},
										children: [
											BX.create('thead', {
												html: '<tr>'
													+ `<td class="bx-disk-popup-shared-people-list-head-col1">${BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS_USER')}</td>`
													+ `<td class="bx-disk-popup-shared-people-list-head-col2">${BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS')}</td>`
													+ '<td class="bx-disk-popup-shared-people-list-head-col3"></td>'
												+ '</tr>',
											}),
										],
									}),
									BX.create('div', {
										props: {
											id: 'feed-add-post-destination-container',
											className: 'feed-add-post-destination-wrap',
										},
										children: [
											BX.create('span', {
												props: {
													className: 'feed-add-post-destination-item',
												},
											}),
											BX.create('span', {
												props: {
													id: 'feed-add-post-destination-input-box',
													className: 'feed-add-destination-input-box',
												},
												style: {
													background: 'transparent',
												},
												children: [
													BX.create('input', {
														props: {
															type: 'text',
															value: '',
															id: 'feed-add-post-destination-input',
															className: 'feed-add-destination-inp',
														},
													}),
												],
											}),
											BX.create('a', {
												props: {
													href: '#',
													id: 'bx-destination-tag',
													className: 'feed-add-destination-link',
												},
												style: {
													background: 'transparent',
												},
												text: BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_ADD_RIGHTS_USER'),
												events: {
													click: () => {},
												},
											}),
										],
									}),
								],
							}),
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
												objectId,
												entityToNewShared: this.entityToNewShared,
											},
											onsuccess: (response) => {
												if (!response)
												{
													return;
												}

												BX.Disk.showModalWithStatusAction(response);
											},
										});

										BX.PopupWindowManager.getCurrentPopup().close();
									},
								},
							}),
							new BX.UI.CloseButton({
								events: {
									click() {
										BX.PopupWindowManager.getCurrentPopup().close();
									},
								},
							}),
						],
					});
				},
			},
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
			readOnly: Boolean(this.loadedReadOnlyEntityToNewShared[item.id]),
			destFormName: this.destFormName,
			item,
			type,
			right: this.entityToNewShared[item.id].right || 'disk_access_edit',
		});

		this.entityToNewShared[item.id] = {
			item,
			type,
			right: this.entityToNewShared[item.id].right || 'disk_access_edit',
		};
	}

	onUnSelectDestination(item, type, search)
	{
		const entityId = item.id;

		if (this.loadedReadOnlyEntityToNewShared[entityId])
		{
			return false;
		}

		delete this.entityToNewShared[entityId];

		const child = BX.findChild(BX('bx-disk-popup-shared-people-list'), { attribute: { 'data-dest-id': `${String(entityId)}` } }, true);
		if (child)
		{
			BX.remove(child);
		}
	}

	onChangeRightOfSharing(entityId, taskName)
	{
		if (this.entityToNewShared[entityId])
		{
			this.entityToNewShared[entityId].right = taskName;
		}
	}

	onOpenDialogDestination()
	{
		BX.style(BX('feed-add-post-destination-input-box'), 'display', 'inline-block');
		BX.style(BX('bx-destination-tag'), 'display', 'none');
		BX.focus(BX('feed-add-post-destination-input'));
		if (BX.SocNetLogDestination.popupWindow)
		{
			BX.SocNetLogDestination.popupWindow.adjustPosition({ forceTop: true });
		}
	}

	onCloseDialogDestination()
	{
		const input = BX('feed-add-post-destination-input');
		if (!BX.SocNetLogDestination.isOpenSearch() && input && input.value.length <= 0)
		{
			BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
			BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
		}
	}

	onOpenSearchDestination()
	{
		if (BX.SocNetLogDestination.popupSearchWindow)
		{
			BX.SocNetLogDestination.popupSearchWindow.adjustPosition({ forceTop: true });
		}
	}

	onCloseSearchDestination()
	{
		const input = BX('feed-add-post-destination-input');
		if (!BX.SocNetLogDestination.isOpenSearch() && input && input.value.length > 0)
		{
			BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
			BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
			BX('feed-add-post-destination-input').value = '';
		}
	}

	onKeyDownDestination(event)
	{
		const BXSocNetLogDestinationFormName = this.destFormName;
		if (event.keyCode == 8 && BX('feed-add-post-destination-input').value.length <= 0)
		{
			BX.SocNetLogDestination.sendEvent = false;
			BX.SocNetLogDestination.deleteLastItem(BXSocNetLogDestinationFormName);
		}

		return true;
	}

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
	}
}

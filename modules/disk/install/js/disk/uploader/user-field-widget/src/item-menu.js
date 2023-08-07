import { Loc, Text, Type, Tag, Runtime, ajax as Ajax } from 'main.core';

import { UploaderFile, getFilenameWithoutExtension, getFileExtension } from 'ui.uploader.core';
import { loadDiskFileDialog } from './helpers/load-disk-file-dialog';

import type { TileWidgetItem } from 'ui.uploader.tile-widget';
import type { Menu, MenuItem } from 'main.popup';
import type { BaseEvent } from 'main.core.events';
import type { Button, ButtonSize, ButtonColor } from 'ui.buttons';
import type UserFieldControl from './user-field-control';
import type MainPostForm from './integration/main-post-form';

import './css/item-rename-form.css';

export default class ItemMenu
{
	#userFieldControl: UserFieldControl = null;
	#item: TileWidgetItem = null;
	#menu: Menu = null;
	#folderDialogId: string = null;

	constructor(userFieldControl: UserFieldControl, item: TileWidgetItem, menu: Menu)
	{
		this.#userFieldControl = userFieldControl;
		this.#item = item;
		this.#menu = menu;
		this.#folderDialogId = `folder-dialog-${Text.getRandom(5)}`;
	}

	build(): void
	{
		const firstItemId: string = this.#menu.getMenuItems()[0]?.id ?? '';

		this.#menu.getPopupWindow().setMaxWidth(500);

		this.#menu.addMenuItem({
			id: 'filesize',
			text: Loc.getMessage('DISK_UF_WIDGET_FILE_SIZE', { '#filesize#': this.#item.sizeFormatted }),
			disabled: true,
		}, firstItemId);

		this.#menu.addMenuItem({ delimiter: true }, firstItemId);

		const postForm: ?MainPostForm = this.#userFieldControl.getMainPostForm();
		if (postForm)
		{
			this.#menu.addMenuItem({
				id: 'insert-into-text',
				text: Loc.getMessage('DISK_UF_WIDGET_INSERT_INTO_THE_TEXT'),
				onclick : (): void => {
					this.#menu.close();
					postForm.getParser().insertFile(this.#item);
				},
			}, firstItemId);
		}

		if (this.#userFieldControl.canItemAllowEdit(this.#item))
		{
			this.#menu.addMenuItem({ delimiter: true });
			this.#menu.addMenuItem({
				id: 'allow-edit',
				className: this.#item.customData['allowEdit'] === true ? 'disk-user-field-item-checked' : '',
				text: Loc.getMessage('DISK_UF_WIDGET_ALLOW_DOCUMENT_EDIT'),
				onclick: (event, menuItem: MenuItem): void => {
					if (this.#item.customData['allowEdit'] === true)
					{
						this.#userFieldControl.setDocumentEdit(this.#item, false);
					}
					else
					{
						this.#userFieldControl.setDocumentEdit(this.#item, true);
					}

					menuItem.getMenuWindow().close();
				}
			});
		}

		if (this.#item.customData['canRename'])
		{
			this.#menu.addMenuItem({ delimiter: true });

			this.#menu.addMenuItem({
				id: 'rename',
				text: Loc.getMessage('DISK_UF_WIDGET_RENAME_FILE_MENU_TITLE'),
				events: {
					'SubMenu:onShow': (event: BaseEvent): void => {
						const renameItem: MenuItem = event.getTarget();
						this.#showRenameMenu(renameItem);
					}
				},
				items: [{
					id: 'rename-textarea',
					html: '<div class="disk-user-field-rename-loading"></div>',
					className: 'disk-user-field-rename-menu-item',
				}]
			});
		}

		if (Type.isStringFilled(this.#item.customData['storage']))
		{
			this.#menu.addMenuItem({
				delimiter: true,
				//text: Loc.getMessage('DISK_UF_WIDGET_SAVED_IN_DISK_FOLDER'),
			});

			if (this.#item.customData['canMove'])
			{
				this.#menu.addMenuItem({
					id: 'storage',
					text: this.#item.customData['storage'] + '&mldr;',
					onclick : (): void => {
						this.openFolderDialog();
						this.#menu.close();
					},
					disabled: this.#item.tileWidgetData.selected === true,
				});
			}
			else
			{
				this.#menu.addMenuItem({
					id: 'storage',
					text: this.#item.customData['storage'],
					disabled: true,
				});
			}
		}
	}

	rename(newName: string): Promise
	{
		return new Promise((resolve, reject): void => {
			Ajax.runAction('disk.api.commonActions.rename', {
					data: {
						objectId: this.#item.customData['objectId'],
						newName: newName,
						autoCorrect: true,
						generateUniqueName: true,
					}
				})
				.then((response): void => {
					if (response?.status === 'success' && response?.data?.object?.name !== this.#item.name)
					{
						const file: UploaderFile = this.#userFieldControl.getFile(this.#item.id);
						const name = response.data.object.name;
						file.setName(name);
					}
					resolve();
				})
				.catch((response): void => {
					BX.Disk.showModalWithStatusAction(response);
					reject();
				})
		});
	}

	#showRenameMenu(renameItem: MenuItem): void
	{
		Runtime.loadExtension('ui.buttons').then((exports): void => {
			const Button: Class<Button> = exports.Button;
			const ButtonSize: Class<ButtonSize> = exports.ButtonSize;
			const ButtonColor: Class<ButtonColor> = exports.ButtonColor;
			const CancelButton: Class<CancelButton> = exports.CancelButton;

			const handleKeydown = (event: KeyboardEvent): void => {
				if (event.code === 'Enter')
				{
					handleRenameClick();
				}
			};

			const nameWithoutExtension = getFilenameWithoutExtension(this.#item.name);
			const handleRenameClick = (): void => {
				const textareaValue: string = textarea.value.trim();
				if (!Type.isStringFilled(textareaValue) || textareaValue === nameWithoutExtension)
				{
					renameItem.getMenuWindow().close();

					return;
				}

				renameBtn.setWaiting(true);
				const newFilename = `${textareaValue}.${getFileExtension(this.#item.name)}`;
				this.rename(newFilename)
					.then((): void => {
						renameBtn.setWaiting(false);
						renameItem.getMenuWindow().close();
					})
					.catch((): void => {
						renameBtn.setWaiting(false);
					})
				;
			};
			const textarea: HTMLTextAreaElement = Tag.render`
				<textarea 
					class="disk-user-field-rename-textarea" 
					onkeydown="${handleKeydown}"
				>${Text.encode(nameWithoutExtension)}</textarea>
			`;

			const renameBtn: Button = new Button({
				text: Loc.getMessage('DISK_UF_WIDGET_RENAME_FILE_BUTTON_TITLE'),
				color: ButtonColor.PRIMARY,
				size: ButtonSize.SMALL,
				onclick: handleRenameClick,
			});

			const cancelBtn: CancelButton = new CancelButton({
				size: ButtonSize.SMALL,
				onclick: (): void => {
					renameItem.getMenuWindow().close();
				}
			});

			const submenu: Menu = renameItem.getSubMenu();
			const textareaItem: MenuItem = submenu.getMenuItem('rename-textarea');

			textareaItem.setText(
				Tag.render`
					<div class="disk-user-field-rename-form">
						${textarea}
						<div class="disk-user-field-rename-buttons">${[renameBtn.render(), cancelBtn.render()]}</div>
					</div>
				`,
				true
			);

			renameItem.showSubMenu();
		});
	}

	openFolderDialog(): void
	{
		loadDiskFileDialog(this.#folderDialogId, { wish: 'fakemove' }).then((): void => {
			BX.DiskFileDialog.obCallback[this.#folderDialogId] = {
				saveButton: (tab, path, selectedItems, folderByPath): void => {
					const selectedItem = Object.values(selectedItems)[0] || folderByPath;
					if (!selectedItem)
					{
						return;
					}

					const folderId = selectedItem.id === 'root' ? tab.rootObjectId : selectedItem.id;

					Ajax.runAction('disk.api.commonActions.move', {
						data: {
							objectId: this.#item.customData['objectId'],
							toFolderId: folderId,
						}
					})
					.then((response): void => {
						if (response?.status === 'success')
						{
							const file: UploaderFile = this.#userFieldControl.getFile(this.#item.id);
							const name = response.data.object.name;
							const id = response.data.object.id;

							file.setServerFileId(`n${id}`);
							file.setName(name);

							if (selectedItem.id === 'root')
							{
								file.setCustomData('storage', `${tab.name} / `);
							}
							else
							{
								file.setCustomData('storage', `${tab.name} / ${selectedItem.name}`);
							}
						}
					})
					.catch((response): void => {
						BX.Disk.showModalWithStatusAction(response);
					});
				},
			};

			if (BX.DiskFileDialog.popupWindow === null)
			{
				BX.DiskFileDialog.openDialog(this.#folderDialogId);
			}
		});
	}
}
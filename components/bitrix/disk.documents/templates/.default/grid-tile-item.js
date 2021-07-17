"use strict";
BX.namespace("BX.Disk.TileGrid");

/**
 *
 * @param options
 * @extends {BX.TileGrid.Item}
 * @constructor
 */
BX.Disk.TileGrid.Item = function(options)
{
	BX.TileGrid.Item.apply(this, arguments);

	this.isDraggable = false;
	this.isDroppable = false;
	this.dblClickDelay = 0;
	this.title = options.name;
	this.isFolder = options.isFolder;
	this.isFile = options.isFile;
	this.canAdd = options.canAdd;
	this.isLocked = options.isLocked;
	this.isSymlink = options.isSymlink;
	this.image = options.image;
	this.actions = options.actions;
	this.link = options.link;
	this.attributes = options.attributes;
	this.item = {
		container: null,
		action: null,
		title: null,
		titleWrapper: null,
		titleLink: null,
		titleInput: null,
		lock: null,
		symlink: null,
		imageBlock: null,
		picture: null,
		fileType: null,
		icons: null
	};
	this.actionsMenu = null;
	this.imageItemHandler = null;

	BX.addCustomEvent(window, 'TileGrid.Grid:onItemDragStart', function() {
		if(this.actionsMenu)
			this.actionsMenu.popupWindow.close();
	}.bind(this));
};

BX.Disk.TileGrid.Item.prototype =
	{
		__proto__: BX.TileGrid.Item.prototype,
		constructor: BX.TileGrid.Item,

		handleDblClick: function()
		{
			// BX.onCustomEvent("Disk.TileItem.Item:onItemDblClick", [this]);
			BX.fireEvent(this.item.titleLink, 'click');
		},

		handleEnter: function()
		{
			BX.onCustomEvent("Disk.TileItem.Item:onItemEnter", [this]);
		},

		/**
		 *
		 * @returns {Element}
		 */
		getContent: function()
		{
			this.item.container = BX.create('div', {
				attrs: {
					className: this.isFile ? 'disk-folder-list-item' : 'disk-folder-list-item disk-folder-list-item-folder'
				},
				children: [
					this.getImage(),
					this.getActionBlock(),
					BX.create('div', {
						props: {
							className: (!this.getLocked() && !this.getSymlink()) ? 'disk-folder-list-item-bottom disk-folder-list-item-bottom-without-icons' : 'disk-folder-list-item-bottom'
						},
						children: [
							this.getTitle(),
							this.getIconsContainer()
						]
					})
				],
				events: {
					contextmenu: function(event) {
						if (event.ctrlKey)
						{
							return;
						}

						this.showActionsMenu(event);
						this.gridTile.resetSelection();
						this.gridTile.selectItem(this);
						event.preventDefault();
					}.bind(this)
				}
			});

			if(this.image)
			{
				this.imageItemHandler = BX.throttle(this.appendImageItem, 20, this);
				BX.bind(window, 'resize', this.imageItemHandler);
				BX.bind(window, 'scroll', this.imageItemHandler);
			}

			return this.item.container
		},

		appendImageItem: function()
		{
			if(this.isVisibleOnFolderList())
			{
				this.item.picture.setAttribute('src', this.image);
				BX.bind(this.item.container, 'animationend', BX.proxy(this.appendImageItem, this));
				BX.unbind(this.item.container, 'animationend', BX.proxy(this.appendImageItem, this));
				BX.unbind(window, 'resize', this.imageItemHandler);
				BX.unbind(window, 'scroll', this.imageItemHandler);
			}
		},

		lock: function()
		{
			this.item.lock.style.display = null;
		},

		unlock: function()
		{
			this.item.lock.style.display = 'none';
		},

		getIconsContainer: function()
		{
			this.item.icons = BX.create("div", {
				props: {
					className: "disk-folder-list-item-icons"
				},
				children: [
					this.getLocked(),
					this.getSymlink()
				]
			});

			return this.item.icons
		},

		getLocked: function()
		{
			this.item.lock = BX.create('div', {
				attrs: {
					className: 'disk-folder-list-item-locked'
				},
				style: {
					display: this.isLocked? null : 'none'
				}
			});

			return this.item.lock
		},

		getSymlink: function()
		{
			this.item.symlink = BX.create('div', {
				attrs: {
					className: 'disk-folder-list-item-shared'
				},
				style: {
					display: this.isSymlink? null : 'none'
				}
			});

			return this.item.symlink
		},

		/**
		 *
		 * @returns {Element}
		 */
		getTitle: function()
		{
			return this.item.title = BX.create('div', {
				props: {
					className: 'disk-folder-list-item-title'
				},
				children: [
					this.item.titleWrapper = BX.create("div", {
						props: {
							className: 'disk-folder-list-item-title-wrapper'
						},
						children: [
							this.getTitleInput(),
							this.item.titleLink = BX.create('a', {
								attrs: {
									className: 'disk-folder-list-item-title-link',
									href: this.link,
									title: this.title,
									id: 'disk_obj_' + this.id
								},
								text: this.title,
								dataset: BX.mergeEx({
									objectId: this.id,
									canAdd: this.canAdd
								}, this.attributes)
							})
						]
					})
				]
			})
		},

		getTitleInput: function()
		{
			this.item.titleInput = BX.create('input', {
				attrs: {
					className: 'disk-folder-list-item-title-input',
					type: 'text',
					value: this.title
				}
			});

			BX.bind(this.item.titleInput, 'click', function(event) {
				event.stopPropagation();
			});

			BX.addCustomEvent(window, 'BX.TileGrid.Grid:resetSelectAllItems', this.cancelRenaming.bind(this));
			BX.addCustomEvent(window, 'BX.TileGrid.Grid:selectItem', this.cancelRenaming.bind(this));

			BX.bind(this.item.titleInput, 'keydown', function(event) {
				if(event.key === 'Escape')
				{
					this.cancelRenaming();

					event.preventDefault();
				}

				if(event.key === 'Enter')
				{
					this.cancelRenaming();
					this.runRename();

					event.preventDefault();
				}

				event.stopPropagation();
			}.bind(this));

			BX.bind(this.item.titleInput, 'blur', function(event){
				this.cancelRenaming();
				this.runRename();
			}.bind(this));

			return this.item.titleInput
		},

		onRename: function()
		{
			this.gridTile.resetSelection();
			jsDD.Disable();

			this.item.titleInput.value = this.title;
			BX.addClass(this.item.title, 'disk-folder-list-item-title-rename');
			this.item.titleInput.focus();
			if (this.isFile)
			{
				this.item.titleInput.setSelectionRange(0, this.title.lastIndexOf("."));
			}
			else
			{
				this.item.titleInput.select();
			}
		},

		cancelRenaming: function()
		{
			BX.removeClass(this.item.title, 'disk-folder-list-item-title-rename');
			this.item.titleInput.blur();

			jsDD.Enable();
		},

		rename: function(newName)
		{
			BX.addClass(this.item.titleLink, 'disk-folder-list-item-title-link-renamed');

			this.item.titleLink.addEventListener('animationend', function(){
				BX.removeClass(this.item.titleLink, 'disk-folder-list-item-title-link-renamed');
			}.bind(this));

			this.item.titleLink.textContent = newName;
			this.item.titleLink.setAttribute('title', newName);
			this.title = newName;
			this.rebuildLinkAfterRename(newName);

			jsDD.Enable();
		},

		rebuildLinkAfterRename: function(name)
		{
			if (this.link !== '')
			{
				this.link = this.link.substring(0, this.link.lastIndexOf('/') + 1) + encodeURIComponent(name);
				this.item.titleLink.href = this.link;
				this.actions.forEach(function(action){
					if (action.id === 'open' && action.href)
					{
						action.href = this.link;
					}
				}, this);
			}
			this.destroyActionsMenu();
		},

		runRename: function()
		{
			if (this.item.titleInput.value === this.title)
			{
				return;
			}

			var oldTitle = this.title;
			this.rename(this.item.titleInput.value);

			BX.Disk.Documents.Backend
				.renameAction(this.getId(), this.title)
				.then(function (response) {
					if(response.data.object.name !== this.title)
					{
						this.rename(response.data.object.name);
					}
				}.bind(this)).catch(function (response) {
					BX.Disk.showModalWithStatusAction(response);
					this.rename(oldTitle);
				}.bind(this));
		},

		afterRender: function()
		{
			if(!this.item.picture)
				return;

			if(this.isVisibleOnFolderList())
			{
				this.appendImageItem();
			}

			BX.bind(this.item.container, 'animationend', BX.proxy(this.appendImageItem, this));

			this.item.picture.onload = function()
			{
				BX.show(this.item.picture);
				BX.hide(this.item.fileType);
			}.bind(this);
		},

		isVisibleOnFolderList: function()
		{
			var rect = this.layout.container.getBoundingClientRect();
			var rectBody = document.body.getBoundingClientRect();
			var itemHeight = this.layout.container.offsetHeight * 2;

			if (rect.top < 0 || rect.bottom < 0)
				return false;

			return rectBody.height > (rect.top - itemHeight) && rectBody.height >= (rect.bottom - itemHeight);
		},

		getImage: function()
		{
			var fileExtension = this.getFileExtension(this.title);

			this.item.imageBlock = BX.create('div', {
				attrs: {
					className: 'disk-folder-list-item-image'
				},
				children: [
					this.item.fileType = BX.create('div', {
						attrs: {
							className: 'ui-icon ui-icon-file ui-icon-file-' + fileExtension
						},
						style: {
							width: this.isFolder ? '85%' : '70%'
						},
						html: '<i></i>'
					}),
					this.item.picture = (this.image? BX.create('img', {
						attrs: {
							className: 'disk-folder-list-item-image-img'
							// src: this.image
						},
						style: {
							display: 'none'
						}
					}) : null)
				]
			});

			return this.item.imageBlock
		},

		markAsShared: function ()
		{
			this.isSymlink = true;

			if (this.isFolder)
			{
				this.item.fileType.classList.add('ui-icon-file-folder-shared');
			}
			else if (this.item.symlink)
			{
				this.item.symlink.style.display = null;
			}
		},

		unmarkAsShared: function ()
		{
			this.isSymlink = false;

			if (this.isFolder)
			{
				this.item.fileType.classList.remove('ui-icon-file-folder-shared');
			}
			else if (this.item.symlink)
			{
				this.item.symlink.style.display = 'none';
			}
		},

		/**
		 *
		 * @returns {string}
		 */
		getFileExtension: function(fileName)
		{
			var fileExtension = fileName.substring(fileName.lastIndexOf('.') + 1);

			switch(fileExtension)
			{
				case 'mp4':
				case 'mkv':
				case 'mpeg':
				case 'avi':
				case '3gp':
				case 'flv':
				case 'm4v':
				case 'ogg':
				case 'swf':
				case 'wmv':
					fileExtension = 'mov';
					break;

				case 'txt':
					fileExtension = 'txt';
					break;

				case 'doc':
				case 'docx':
					fileExtension = 'doc';
					break;

				case 'xls':
				case 'xlsx':
					fileExtension = 'xls';
					break;

				case 'php':
					fileExtension = 'php';
					break;

				case 'pdf':
					fileExtension = 'pdf';
					break;

				case 'ppt':
				case 'pptx':
					fileExtension = 'ppt';
					break;

				case 'rar':
					fileExtension = 'rar';
					break;

				case 'zip':
					fileExtension = 'zip';
					break;

				case 'set':
					fileExtension = 'set';
					break;

				case 'mov':
					fileExtension = 'mov';
					break;

				case 'img':
				case 'jpg':
				case 'jpeg':
				case 'gif':
					fileExtension = 'img';
					break;

				default:
					fileExtension = 'empty'
			}

			this.isFolder ? fileExtension = 'folder' : null;
			this.isSymlink && this.isFolder ? fileExtension = 'folder-shared' : null;

			return fileExtension;

		},

		getActionBlock: function()
		{
			if (!this.item.action)
			{
				this.item.action = BX.create('div', {
					attrs: {
						className: 'disk-folder-list-item-action'
					},
					events: {
						click: function(event) {
							this.showActionsMenu(event, BX.getEventTarget(event));
						}.bind(this)
					}
				});
			}

			return this.item.action;
		},

		getActions: function ()
		{
			return this.actions;
		},

		destroyActionsMenu: function ()
		{
			if (this.actionsMenu)
			{
				this.actionsMenu.destroy();
				this.actionsMenu = null;
			}
		},

		getActionsMenu: function(target)
		{
			if (this.actions.length <= 0)
			{
				return;
			}
			if (this.actionsMenu)
			{
				return this.actionsMenu;
			}

			this.actionsMenu = BX.PopupMenu.create(
				'disk-tile-grid-actions-menu-' + this.getId(),
				target,
				this.actions,
				{
					autoHide: true,
					offsetLeft: 20,
					angle: true
				}
			);
			BX.onCustomEvent('Disk:Documents:TileGrid:MenuAction:FirstShow', [this, this.id, this.actionsMenu]);

			BX.bind(this.actionsMenu.popupWindow.popupContainer, 'click', function(event) {
				var actionsMenu = this.getActionsMenu();
				if (actionsMenu)
				{
					var target = BX.getEventTarget(event);
					var item = BX.findParent(target, {
						className: 'menu-popup-item'
					}, 10);

					if (!item || !item.dataset.preventCloseContextMenu)
					{
						actionsMenu.close();
					}
				}
			}.bind(this));

			return this.actionsMenu;
		},

		showActionsMenu: function(event, bindElement)
		{
			BX.fireEvent(document.body, 'click');

			var actionsMenu = this.getActionsMenu(bindElement);
			actionsMenu.show();

			if(!bindElement)
			{
				actionsMenu.popupWindow.popupContainer.style.top = event.pageY + "px";
				actionsMenu.popupWindow.popupContainer.style.left = (event.pageX - 35) + "px";
			}
			else if (bindElement)
			{
				var pos = BX.pos(bindElement);
				pos.forceBindPosition = true;
				actionsMenu.popupWindow.setBindElement(bindElement);
				actionsMenu.popupWindow.adjustPosition(pos);
			}
		}
	}
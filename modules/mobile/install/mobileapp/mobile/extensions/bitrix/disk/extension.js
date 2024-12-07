/**
 * @bxjs_lang_path extension.php
 */

include('InAppNotifier');

(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/disk/';
	const downloadPath = '/mobile/ajax.php?mobile_action=disk_download_file&action=downloadFile&fileId=';

	class UserDisk
	{
		/**
		 * @param params
		 * @param {String|Integer} params.userId current user id
		 * @param {String|Integer} params.ownerId identifier of storage owner
		 * @param {BaseList} params.list list object
		 * @param {String} params.title title at the navigation bar
		 * @param {String|Integer} params.folderId identifier of folder
		 * @param {String} params.entityType type of entity ("user" | "common" | "group")
		 */
		constructor(params = {})
		{
			this._title = null;
			this.userId = params.userId;
			this.destroyOnRemove = typeof params.destroyOnRemove === 'undefined' ? true : Boolean(params.destroyOnRemove);
			this.entityType = params.entityType || 'user';
			this.ownerId = params.ownerId || this.userId;
			this.firstLoad = true;
			this.list = params.list || null;
			if (params.title)
			{
				this.title = params.title;
			}
			this.items = [];
			this.folderId = params.folderId;
			this.storageId = params.folderId;
			this.request = new RequestExecutor('mobile.disk.folder.getchildren');
			this.searcher = new Searcher(params.list, new ReactDatabase('files'));
			this.request.handler = this.handler.bind(this);
			this.request.onCacheFetched = this.onCacheFetched.bind(this);

			if (!this.ownerId)
			{
				throw new Error('UserDisk: User identifier is not defined');
			}

			BX.onViewLoaded(() => {
				this.list.setSections([
					{ id: 'list' },
					{ id: 'service' },
				]);

				const buttons = [
					{
						type: 'search',
						callback: () => {
							this.list.showSearchBar();
						},
					},
					{
						type: 'more',
						callback: () => {
							this.popupMenu.show();
						},

					}];
				this.list.setRightButtons(buttons);

				this.redrawMenu();
			});
		}

		init(folderId)
		{
			BX.onViewLoaded(
				() => {
					if (folderId)
					{
						this.folderId = folderId;
					}

					this.showLoading();
					this.load(true);
				},
			);
		}

		get popupMenu()
		{
			if (!this._popupMenu)
			{
				this._popupMenu = typeof dialogs.createPopupMenu === 'undefined'
					? dialogs.popupMenu
					: dialogs.createPopupMenu();
			}

			return this._popupMenu;
		}

		set title(title = '')
		{
			this._title = title;
			if (this.list)
			{
				this.list.setTitle({ text: this._title, type:"section" });
			}
		}

		/**
		 *
		 * @param {BaseList} list
		 */
		set list(list)
		{
			this._list = list;
			if (this.list)
			{
				if (this.title)
				{
					this.list.setTitle({ text: this.title, type: 'section' });
				}

				const listener = (event, item) => {
					switch (event)
					{
						case 'onSearchShow':
						{
							this.searcher.showRecentResults();

							break;
						}

						case 'onUserTypeText':
						{
							this.searcher.fetchResults(item);

							break;
						}

						case 'onViewRemoved':
						{
							this.destroy();

							break;
						}
						case 'onItemSelected':
						case 'onSearchItemSelected':
						{
							if (item.id === 'more')
							{
								this.showLoading();
								this.request.callNext();
							}
							else if (item.params.type === 'folder')
							{
								this.list.openWidget(
									'list',
									{
										useSearch: true,
										onReady: (list) => {
											UserDisk.open({
												userId: this.userId,
												ownerId: this.ownerId,
												entityType: this.entityType,
												title: item.title,
												list,
												folderId: item.id,
											});
										},
										titleParams: { text: item.title, type: 'section' },
									},
								);
							}
							else if (item.params.type === 'file')
							{
								if (item.params.contentType === 'image')
								{
									if (event === 'onSearchItemSelected')
									{
										viewer.openImage(item.params.url, item.title);
									}
									else
									{
										this.showImageCollection(item.params.url);
									}
								}
								else
								{
									UserDisk.openFile(item.params.url, item.params.previewUrl, item.params.contentType, item.title);
								}
							}

							if (event === 'onSearchItemSelected')
							{
								this.searcher.addRecentSearchItem(item);
							}

							break;
						}

						case 'onRefresh':
						{
							this.refresh();

							break;
						}

						case 'onItemAction':
						{
							if (item.action.identifier === 'remove')
							{
								const entity = item.item;
								const dialogTitle = entity.params.type === 'file'
									? BX.message('USER_DISK_REMOVE_FILE_CONFIRM').replace('%@', entity.title)
									: BX.message('USER_DISK_REMOVE_FOLDER_CONFIRM').replace('%@', entity.title);

								dialogs.showActionSheet({
									title: dialogTitle,
									callback: (action) => {
										if (action.code === 'Y')
										{
											this.list.removeItem({ id: entity.id });
											UserDisk.remove(entity.id, entity.params.type)
												.then(() => {
													dialogs.showSnackbar(
														{
															title: BX.message('USER_DISK_ROLLBACK').replace('%@', entity.title),
															autoHide: false,
															showCloseButton: true,
															hideOnTap: true,
															backgroundColor: AppTheme.colors.accentSoftBlue2,
															textColor: '525C69',
														},
														(event) => {
															if (event === 'onClick')
															{
																UserDisk.restore(entity.id, entity.params.type)
																	.then(() => this.refresh());
															}
														},
													);
												})
											;
										}
									},
									items: [
										{ title: BX.message('USER_DISK_CONFIRM_YES'), code: 'Y' },
										{ title: BX.message('USER_DISK_CONFIRM_NO'), code: 'N' },
									],
								});
							}
							else if (item.action.identifier === 'share')
							{
								this.showShareMenu(item.item);
							}

							break;
						}
						// No default
					}
				};

				this._list.setListener(listener);
			}
		}

		refresh()
		{
			this.items = [];
			this.load(false);
		}

		load(useCache = false)
		{
			setTimeout(() => {
				this.resolvedFolderId();
				const sort = this.sortSettings();
				const order = {};
				if (!this.mixedSort())
				{
					order.TYPE = 'ASC';
				}
				order[sort.field] = sort.direction;

				this.request.options = {
					entityId: this.ownerId,
					type: this.entityType,
					folderId: this.folderId,
					order,
				};

				this.request.cacheId = Object.toMD5({
					entityId: this.ownerId,
					type: this.entityType,
					folderId: this.folderId,
				});
				this.request.call(useCache);
			});
		}

		get title()
		{
			return this._title;
		}

		/**
		 *
		 * @returns {BaseList}
		 */
		get list()
		{
			return this._list;
		}

		onCacheFetched(result = {})
		{
			if (typeof result === 'object')
			{
				const list = result.items;
				if (list && list.length > 0)
				{
					this.items = list.map((item) => UserDisk.prepareItem(item));
					BX.onViewLoaded(() => {
						if (result.name)
						{
							this.list.setTitle({ text: result.name, type: 'section'});
						}

						this.setItems(this.items);
					});
				}
			}
		}

		redrawMenu()
		{
			let popupPoints = [
				this.makeItemChecked({
					title: BX.message('USER_DISK_MENU_SORT_DATE_CREATE'),
					sectionCode: 'sort',
					id: 'UPDATE_TIME',
				}, true),
				{ title: BX.message('USER_DISK_MENU_SORT_DATE_UPDATE'), sectionCode: 'sort', id: 'CREATE_TIME' },
				{ title: BX.message('USER_DISK_MENU_SORT_TYPE'), sectionCode: 'sort', id: 'TYPE' },
				{ title: BX.message('USER_DISK_MENU_SORT_NAME'), sectionCode: 'sort', id: 'NAME' },
				{ title: BX.message('USER_DISK_MENU_SORT_MIX'), sectionCode: 'mix_sort', id: 'MIXSORT' },
			];

			if (typeof this.list.setFloatingButton === 'undefined')
			{
				popupPoints.unshift({
					title: BX.message('USER_DISK_MENU_UPLOAD'),
					sectionCode: 'usermenu',
					id: 'upload',
				});
			}
			else
			{
				this.list.setFloatingButton({
					icon: 'plus',
					callback: () => {
						bitrix24Disk.show({
							listener: () => this.refresh(),
							folderId: this.folderId,
							storageId: this.storageId,
							multipleUpload: true,
						});
					},
				});
			}

			if (this.ownerId !== this.userId && this.entityType === 'user')
			{
				popupPoints.shift();
			}

			const sortSettings = this.sortSettings();

			popupPoints = popupPoints.map((item) => {
				if (item.sectionCode === 'sort')
				{
					item = this.makeItemChecked(item, (sortSettings.field == item.id))
				}

				if (item.id === 'MIXSORT')
				{
					item = this.makeItemChecked(item, this.mixedSort())
				}


				return item;
			});

			this.popupMenu.setData(
				popupPoints,
				[
					{ id: 'sort', title: BX.message('USER_DISK_MENU_SORT') },
					{ id: 'mix_sort', title: '' }],
				(event, item) => {
					if (event === 'onItemSelected')
					{
						if (item.id === 'upload')
						{
							bitrix24Disk.show({
								listener: () => this.refresh(),
								folderId: this.folderId,
								storageId: this.storageId,
								multipleUpload: true,
							});
						}

						if (item.sectionCode === 'sort')
						{
							this.sort(item.id);
							setTimeout(() => this.redrawMenu(), 1000);
						}

						if (item.id === 'MIXSORT')
						{
							this.setUseMixedSort(!this.mixedSort());
							dialogs.showLoadingIndicator();
							this.refresh();
							setTimeout(() => this.redrawMenu(), 1000);
						}
					}
				},
			);
		}

		makeItemChecked(item, checked = true) {
			if (Application.getApiVersion() >= 54)
			{
				item.checked = checked
			}
			else
			{
				item.iconUrl = checked ? UserDisk.pathToIcon('check.png') : ''
			}

			return item
		}

		resolvedFolderId()
		{
			this.storageId = Application.storage.get(`user.storage_${this.ownerId}`);
			this.rootFolderId = Application.storage.get(`user.storage.root_${this.ownerId}`);
			if (this.folderId)
			{
				return this.folderId;
			}

			if (this.rootFolderId)
			{
				this.folderId = this.rootFolderId;
			}

			return this.folderId;
		}

		onError(result, error, more)
		{
			if ((error instanceof Error) && error.code == 403)
			{
				this.showAccessDenied();
			}
			else
			{
				console.error(error);
				this.showError();
			}
		}

		onStorageDataUpdate(storageId = null)
		{
			Application.storage.set(`user.storage_${this.ownerId}`, storageId);
			this.storageId = storageId;
		}

		onRootFolderIdUpdate(id = null)
		{
			Application.storage.set(`user.storage.root_${this.ownerId}`, id);
			this.rootFolderId = id;
		}

		onResult(result, more)
		{
			BX.onViewLoaded(
				() => {
					if (result)
					{
						const items = result.items || [];

						if (this.request.hasNext())
						{
							this.showMore();
						}
						else
						{
							this.list.setSectionItems([], 'service');
						}

						const files = items.map((item) => UserDisk.prepareItem(item));
						const isEmptyList = this.items.length === 0;
						this.items = more ? this.items.concat(files) : files;
						if (result.name)
						{
							this.list.setTitle({ text: result.name, type: 'section'});
						}

						if (this.items.length === 0)
						{
							this.showEmptyFolder();
						}
						else if (isEmptyList || this.firstLoad)
						{
							this.firstLoad = false;
							this.setItems(this.items);
						}
						else
						{
							this.list.addItems(files);
						}
					}
				},
			);
		}

		handler(result, more, error)
		{
			this.list.stopRefreshing();
			dialogs.hideLoadingIndicator();
			if (error)
			{
				this.onError(result, more, error);
			}

			if (result.storageId)
			{
				this.onStorageDataUpdate(result.storageId);
			}

			if (result.rootFolderId)
			{
				this.onRootFolderIdUpdate(result.rootFolderId);
			}

			if (this.folderId == null && result.folderId)
			{
				this.folderId = result.folderId;
			}

			if (result && result.items)
			{
				this.onResult(result, more);
			}
		}

		setItems(items = [])
		{
			this.list.setSectionItems(items, 'list');
		}

		showLoading()
		{
			const loading = ListHolder.Loading;
			loading.id = 'loading';
			loading.params = {};
			this.list.setSectionItems([loading], 'service');
		}

		showMore()
		{
			const more = ListHolder.MoreButton;
			more.id = 'more';
			more.params = { id: 'more' };
			this.list.setSectionItems([more], 'service');
		}

		showAccessDenied()
		{
			const access = ListHolder.MoreButton;
			access.id = 'noaccess';
			access.title = BX.message('USER_DISK_ACCESS_DENIED');
			access.params = { id: 'noaccess' };
			this.list.setSectionItems([access], 'service');
		}

		showEmptyFolder()
		{
			this.list.setSectionItems([
				{
					title: BX.message('USER_DISK_EMPTY_FOLDER'),
					type: 'button',
					styles: {
						title: {
							font: {
								size: 17,
								fontStyle: 'medium',
							},
						},
					},
					unselectable: true,
				}], 'service');
		}

		showError()
		{
			this.list.setSectionItems([
				{
					title: BX.message('USER_DISK_ERROR'),
					type: 'button',
					styles: {
						title: {
							font: {
								size: 17,
								fontStyle: 'medium',
							},
						},
					},
					unselectable: true,
				}], 'service');
		}

		showShareMenu(item)
		{
			dialogs.showActionSheet({
				callback: (action) => {
					if (action.code === 'public')
					{
						notify.showIndicatorLoading();
						UserDisk.getPublicLink(item.id, 'file').then(
							(link) => {
								Application.copyToClipboard(link);
								notify.showIndicatorSuccess({
									hideAfter: 1000,
									fallbackText: BX.message('USER_DISK_LINK_COPIED'),
									text: BX.message('USER_DISK_LINK_COPIED'),
									title: link,
								}, 500);
							},
						).catch((e) => {
							notify.showIndicatorError({
								hideAfter: 1000,
								fallbackText: BX.message('USER_DISK_LINK_COPIED_FAIL'),
								title: item.title,
							}, 500);
						});
					}
					else if (action.code === 'send_to_user')
					{
						UserList.openPicker({ allowMultipleSelection: false }).then((data) => {
							if (data.length > 0)
							{
								const user = data[0];
								notify.showIndicatorLoading();
								const commitData = { DIALOG_ID: user.params.id, DISK_ID: item.id };
								BX.rest.callMethod('im.disk.file.commit', commitData)
									.then((result) => notify.showIndicatorSuccess({
										hideAfter: 1000,
										fallbackText: BX.message('USER_DISK_FILE_SENT'),
									}, 500))
									.catch((error) => notify.showIndicatorError({
										hideAfter: 1000,
										fallbackText: BX.message('USER_DISK_FILE_NOT_SEND'),
										title: 'Bitrix24',
									}, 500));
							}
						});
					}
				},
				title: item.title,
				items: [
					{
						title: BX.message('USER_DISK_GET_PUBLIC_LINK'),
						code: 'public',
					},
					{
						title: BX.message('USER_DISK_SEND_TO_USER'),
						code: 'send_to_user',
					},
				],
			});
		}

		sort(sortField)
		{
			const currentSort = this.sortSettings();
			if (sortField == currentSort.field)
			{
				currentSort.direction = (currentSort.direction == 'DESC' ? 'ASC' : 'DESC');
			}
			else
			{
				currentSort.field = sortField;
				currentSort.direction = 'DESC';
			}

			this.setSortSettings(currentSort.field, currentSort.direction);
			this.request.options.order = { [currentSort.field]: currentSort.direction };
			dialogs.showLoadingIndicator();
			this.refresh();
		}

		setSortSettings(field = 'UPDATE_TIME', direction = 'DESC')
		{
			const key = `sort_disk_${this.ownerId}`;
			Application.storage.setObject(key, { field, direction });
		}

		/**
		 * @return {{field:String, direction:String}} sort;
		 */
		sortSettings()
		{
			const key = `sort_disk_${this.ownerId}`;

			return Application.storage.getObject(key, {
				field: 'CREATE_TIME',
				direction: 'DESC',
			});
		}

		setUseMixedSort(mixed = true)
		{
			Application.storage.setBoolean(`sort_mixed_${this.ownerId}`, mixed);
		}

		mixedSort()
		{
			return Application.storage.getBoolean(`sort_mixed_${this.ownerId}`, true);
		}

		showImageCollection(defaultUrl = null)
		{
			const collection = this.items.reduce((collection, file) => {
				if (file.params.type === 'file' && file.params.contentType === 'image')
				{
					const data = { ...file.params };
					if (data.url === defaultUrl)
					{
						data.default = true;
					}
					else
					{}

					collection.push(data);
				}

				return collection;
			}, []);

			viewer.openImageCollection(collection);
		}

		destroy()
		{
			if (this.destroyOnRemove)
			{
				this._popupMenu = null;
				this._list = null;
			}
		}

		static prepareItem(item)
		{
			const preparedItem = {
				id: item.REAL_OBJECT_ID,
				title: item.NAME,
				sectionCode: 'list',
				clientSort: {
					UPDATE_TIME: (new Date(item.UPDATE_TIME)).getTime(),
					CREATE_TIME: (new Date(item.CREATE_TIME)).getTime(),
					TYPE: item.TYPE === 'file' ? 1 : 0,
					NAME: item.NAME,
				},
				styles: {
					image: {
						image: { borderRadius: 0 },
					},
					title: {
						font: {
							color: AppTheme.colors.base1,
							size: 17,
							fontStyle: 'medium',
						},
					},
				},
				actions: [
					{
						title: BX.message('USER_DISK_REMOVE'),
						color: AppTheme.colors.accentMainAlert,
						identifier: 'remove',
					},
				],
				params: {
					type: item.TYPE,
				},
			};
			preparedItem.height = 64;

			if (item.TYPE === 'folder')
			{
				const isGroupFolder = item.ID !== item.REAL_OBJECT_ID;
				preparedItem.imageUrl = `${pathToExtension}images/${isGroupFolder ? 'group' : 'folder'}.png?2`;
			}
			else
			{
				preparedItem.type = 'info';

				preparedItem.params.filename = item.NAME;
				preparedItem.params.contentType = UserDisk.typeByFilename(item.NAME);
				preparedItem.params.url = downloadPath + item.ID;
				preparedItem.actions.push({
					title: BX.message('USER_DISK_SHARE'),
					color: AppTheme.colors.accentExtraDarkblue,
					identifier: 'share',
				});

				if (item.PREVIEW_URL && preparedItem.params.contentType === 'image')
				{
					preparedItem.params.previewUrl = item.PREVIEW_URL;
					preparedItem.imageUrl = item.PREVIEW_URL;
					preparedItem.styles.image.image.borderRadius = 10;
				}
				else
				{
					preparedItem.imageUrl = `${pathToExtension}/images/${UserDisk.iconByFileName(item.NAME)}`;
				}

				let size = item.SIZE / 1024;
				size = size < 512 ? `${Math.ceil(size)}KB` : `${(size / 1024).toFixed(1)}MB`;
				preparedItem.subtitle = `${size} ${(new Date(item.UPDATE_TIME)).toLocaleString()}`;
			}

			return preparedItem;
		}

		static remove(id = null, type = null)
		{
			return UserDisk.makeOperation('markdeleted', id, type);
		}

		static restore(id = null, type = null)
		{
			return UserDisk.makeOperation('restore', id, type);
		}

		static getPublicLink(id = null, type = null)
		{
			return UserDisk.makeOperation('getexternallink', id, type);
		}

		static makeOperation(operationName = '', id = '', entityType = '')
		{
			const restNamespace = {
				file: 'disk.file',
				folder: 'disk.folder',
			};

			return new Promise((resolve, reject) => {
				if (restNamespace[entityType])
				{
					const method = `${restNamespace[entityType]}.${operationName}`;
					BX.rest.callMethod(
						method,
						{
							id,
						},
						(result) => {
							if (result.error())
							{
								reject(result.error());
							}
							else
							{
								resolve(result.data());
							}
						},
					);
				}
				else
				{
					reject({ code: -1000, description: `Unknown type of entity (${entityType})` });
				}
			});
		}

		static openFile(url, previewUrl = '', type, name = '')
		{
			if (type === 'video')
			{
				viewer.openVideo(url);
			}
			else if (type === 'image')
			{
				viewer.openImageCollection([{
					url,
					previewUrl,
					name,
				}]);
			}
			else
			{
				viewer.openDocument(url, name);
			}
		}

		static showMessage(message = '', title = '')
		{
			if (typeof InAppNotifier !== 'undefined')
			{
				InAppNotifier.showNotification({
					title,
					backgroundColor: AppTheme.colors.accentSoftElementBlue1,
					time: 2,
					blur: true,
					message,
				});
			}
		}

		static pathToIcon(iconName = null)
		{
			if (iconName == null)
			{
				return null;
			}

			return `${pathToExtension}/images/${iconName}`;
		}

		static iconByFileName(fileName = '')
		{
			const icons = {
				pdf: 'pdf.png',
				jpg: 'img.png',
				png: 'img.png',
				doc: 'doc.png',
				docx: 'doc.png',
				ppt: 'ppt.png',
				pptx: 'ppt.png',
				rar: 'rar.png',
				xls: 'xls.png',
				csv: 'xls.png',
				xlsx: 'xls.png',
				zip: 'zip.png',
				txt: 'txt.png',
				avi: 'movie.png',
				mov: 'movie.png',
				mpeg: 'movie.png',
				mp4: 'movie.png',
			};

			let fileExt = fileName.split('.').pop();
			if (fileExt)
			{
				fileExt = fileExt.toLowerCase();
			}

			return icons[fileExt] ? `${icons[fileExt]}?2` : 'blank.png?21';
		}

		static typeByFilename(fileName = '')
		{
			const types = {
				jpg: 'image',
				jpeg: 'image',
				png: 'image',
				gif: 'image',
				tiff: 'image',
				bmp: 'image',
				avi: 'video',
				mov: 'video',
				mpeg: 'video',
				mp4: 'video',
			};

			let fileExt = fileName.split('.').pop();
			fileExt = fileExt.toLowerCase();

			return types[fileExt] ? types[fileExt] : 'document';
		}

		/**
		 *
		 * @param params
		 * @param params.userId
		 * @param params.ownerId
		 * @param params.list
		 * @param params.title
		 */
		static open(params)
		{
			(new UserDisk(params)).init();
		}
	}

	const tables = {
		files_last_search: {
			name: 'files_last_search',
			fields: [{ name: 'id', unique: true }, 'value'],
		},
	};

	class Searcher
	{
		/**
		 *
		 * @param {BaseList} list
		 * @param {ReactDatabase} db
		 * @param {UserListDelegate} delegate
		 */
		constructor(list = null, db = null, delegate = null)
		{
			/**
			 * @type {RunActionDelayedExecutor}
			 */
			this.searchRequest = new RunActionDelayedExecutor('disk.commonActions.search');
			this.db = db;
			this.delegate = delegate;
			this.list = list;
			this.lastSearchItems = [];
			if (this.db)
			{
				this.db.table(tables.files_last_search).then(
					(table) => table.get().then(
						(items) => {
							if (items.length > 0)
							{
								this.lastSearchItems = JSON.parse(items[0].VALUE);
							}
						},
					),
				);
			}
		}

		fetchResults(data)
		{
			if (this.currentQueryString !== data.text)
			{
				this.currentQueryString = data.text;
				if (data.text.length >= 3)
				{
					this.currentSearchItems = [];
					/**
					 * @type {RunActionDelayedExecutor}
					 */

					this.list.setSearchResultItems([ListHolder.Loading], []);
					this.searchRequest
						.updateOptions({ searchQuery: data.text })
						.setHandler((result, error) => {
							if (result.data)
							{
								result = result.data;
								error = result.error;
							}

							if (result.items)
							{
								if (result.items.length === 0)
								{
									this.list.setSearchResultItems([ListHolder.EmptyResult], []);
								}
								else
								{
									const items = this.prepareItems(result.items);
									this.currentSearchItems = items;
									this.list.setSearchResultItems(items, [{ id: 'files' }, { id: 'service' }]);
								}
							}
							else if (error && error.code !== 'REQUEST_CANCELED')
							{
								this.list.setSearchResultItems([ListHolder.EmptyResult], []);
							}
						})
						.call();
				}
				else if (data.text.length === 0)
				{
					this.showRecentResults();
				}
			}
		}

		prepareItems(items)
		{
			return items.map((item) => {
				item.sectionCode = 'files';
				const preparedItem = {
					sectionCode: 'files',
					id: item.id,
					title: item.title,
					clientSort: {
						TYPE: item.type === 'file' ? 1 : 0,
						NAME: item.title,
					},
					styles: {
						image: {
							image: { borderRadius: 0 },
						},
						title: {
							font: {
								color: AppTheme.colors.base1,
								size: 17,
								fontStyle: 'medium',
							},
						},
					},
					actions: [
						{
							title: BX.message('USER_DISK_REMOVE'),
							color: AppTheme.colors.accentMainAlert,
							identifier: 'remove',
						},
					],
					params: {
						type: item.type,
					},
				};
				preparedItem.height = 64;

				if (item.type === 'folder')
				{
					preparedItem.imageUrl = `${pathToExtension}images/folder.png?2`;
					preparedItem.type = 'default';
				}
				else
				{
					preparedItem.type = 'info';
					preparedItem.params.filename = item.type;
					preparedItem.params.contentType = UserDisk.typeByFilename(item.title);
					preparedItem.params.url = downloadPath + item.id;
					preparedItem.actions.push({
						title: BX.message('USER_DISK_SHARE'),
						color: AppTheme.colors.accentExtraDarkblue,
						identifier: 'share',
					});
					preparedItem.imageUrl = `${pathToExtension}/images/${UserDisk.iconByFileName(item.title)}`;
					preparedItem.subtitle = item.subTitle;
				}

				return preparedItem;
			});
		}

		addRecentSearchItem(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter((item) => item.id !== data.id);
			this.lastSearchItems.unshift(data);

			if (this.lastSearchItems.length > 20)
			{
				this.lastSearchItems = this.lastSearchItems.slice(0, 20);
			}

			this.db.table(tables.files_last_search).then((table) => {
				table.delete().then(() => table.add({ value: this.lastSearchItems }));
			});
		}

		removeRecentSearchItem(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter((item) => item.params.id != data.item.params.id);
			this.db.table(tables.files_last_search).then(
				(table) => table.delete()
					.then(() => table.add({ value: this.lastSearchItems })
						.then(() => console.info('Last search changed'))),
			);
		}

		showRecentResults()
		{
			const prepared = this.lastSearchItems.map((item) => {
				item.type = 'info';
				item.height = 64;
				item.styles = {
					image: {
						image: { borderRadius: 0 },
					},
					title: {
						font: {
							color: AppTheme.colors.base1,
							size: 17,
							fontStyle: 'medium',
						},
					},
				};

				return item;
			});

			this.list.setSearchResultItems(prepared, [
				{
					id: 'files',
					title: this.lastSearchItems.length > 0 ? BX.message('RECENT_SEARCH') : '',
				},
			]);
		}
	}

	this.UserDisk = UserDisk;
})();

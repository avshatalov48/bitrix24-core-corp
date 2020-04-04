/**
 * @bxjs_lang_path extension.php
 */

include("InAppNotifier");

(() =>
{
	const pathToExtension = `/bitrix/mobileapp/mobile/extensions/bitrix/disk/`;
	const downloadPath = "/mobile/ajax.php?mobile_action=disk_download_file&action=downloadFile&fileId=";

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
			this.entityType = params.entityType || "user";
			this.ownerId = params.ownerId || this.userId;
			this.list = params.list || null;
			if (params.title)
			{
				this.title = params.title;
			}
			this.items = [];
			this.folderId = params.folderId;
			this.storageId = params.folderId;
			this.request = new RequestExecutor("mobile.disk.folder.getchildren");
			this.request.handler = this.handler.bind(this);
			this.request.onCacheFetched = this.onCacheFetched.bind(this);

			if (!this.ownerId)
			{
				throw new Error("UserDisk: User identifier is not defined")
			}

			BX.onViewLoaded(() =>
			{
				this.list.setSections([
					{id: "list"},
					{id: "service"}
				]);
				this.list.setRightButtons([
					{
						type: "more",
						callback: () => this.popupMenu.show()

					}]);

				this.redrawMenu();

			});
		}

		init(folderId)
		{
			BX.onViewLoaded(
				() =>
				{
					if (folderId)
					{
						this.folderId = folderId;
					}

					this.showLoading();
					this.load(true);
				}
			);

		}

		get popupMenu()
		{
			if (!this._popupMenu)
			{
				this._popupMenu = typeof dialogs["createPopupMenu"] != "undefined"
					? dialogs.createPopupMenu()
					: dialogs.popupMenu;
			}

			return this._popupMenu;
		}

		set title(title = "")
		{
			this._title = title;
			if (this.list)
			{
				this.list.setTitle({text: this._title});
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
					this.list.setTitle({text: this.title});
				}

				let listener = (event, item) =>
				{
					if (event === "onViewRemoved")
					{
						this.destroy();
					}
					else if (event === "onItemSelected")
					{

						if (item.id === "more")
						{
							this.showLoading();
							this.request.callNext();
						}
						else if (item.params.type === "folder")
						{
							PageManager.openWidget(
								"list",
								{
									onReady: list =>
									{

										UserDisk.open({
											userId: this.userId,
											ownerId: this.ownerId,
											entityType: this.entityType,
											title: item.title,
											list: list,
											folderId: item.id
										})
									},
									title: item.title
								});
						}
						else if (item.params.type === "file")
						{
							dialogs.showActionSheet({
								callback: action =>
								{
									if (action.code === "public")
									{
										dialogs.showLoadingIndicator(
											{text: BX.message("USER_DISK_PUBLIC_LINK_GETTING")});
										UserDisk.getPublicLink(item.id, "file").then(
											link =>
											{
												dialogs.hideLoadingIndicator();
												Application.copyToClipboard(link);
												UserDisk.showMessage(BX.message("USER_DISK_LINK_COPIED"), link)

											}
										).catch(e =>
										{
											dialogs.hideLoadingIndicator();
											UserDisk.showMessage(BX.message("USER_DISK_LINK_COPIED_FAIL"), item.title)
										})
									}
									else
									{
										UserDisk.openFile(item.params.url, item.params.contentType, item.title);
									}

								},
								title: item.title,
								items: [
									{
										title: BX.message("USER_DISK_OPEN"),
										code: "open"
									},
									{
										title: BX.message("USER_DISK_GET_PUBLIC_LINK"),
										code: "public"
									},
								],
							});

						}

					}
					else if (event === "onRefresh")
					{
						this.refresh();
					}
					else if (event === "onItemAction")
					{
						let entity = item.item;
						let dialogTitle = entity.params.type === "file"
							? BX.message("USER_DISK_REMOVE_FILE_CONFIRM").replace("%@", entity.title)
							: BX.message("USER_DISK_REMOVE_FOLDER_CONFIRM").replace("%@", entity.title);

						dialogs.showActionSheet({
							title: dialogTitle,
							callback: action =>
							{
								if (action.code === "Y")
								{
									this.list.removeItem({id: entity.id});
									UserDisk.remove(entity.id, entity.params.type)
										.then(() =>
										{
											dialogs.showSnackbar(
												{
													title: BX.message("USER_DISK_ROLLBACK").replace("%@", entity.title),
													autoHide: false,
													showCloseButton: true,
													hideOnTap: true,
													backgroundColor: '#E3F8FF',
													textColor: '525C69'
												},
												event =>
												{
													if (event === "onClick")
													{
														UserDisk.restore(entity.id, entity.params.type)
															.then(() => this.refresh())
													}
												})
										})
									;
								}
							},
							items: [
								{title: BX.message("USER_DISK_CONFIRM_YES"), code: "Y"},
								{title: BX.message("USER_DISK_CONFIRM_NO"), code: "N"},
							],
						});

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
			setTimeout(() =>
			{
				this.resolvedFolderId();
				let sort = this.sortSettings();
				let order = {};
				if(!this.mixedSort())
					order["TYPE"] = "ASC";
				order[sort.field] = sort.direction;

				this.request.options = {
					entityId: this.ownerId,
					type:this.entityType,
					folderId:this.folderId,
					order: order
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
			if(typeof result == "object")
			{
				console.log("cache", result);
				let list = result.items;
				if (list && list.length > 0)
					BX.onViewLoaded(() => this.setItems(list.map(item => UserDisk.prepareItem(item))));
			}
		}

		redrawMenu()
		{
			let popupPoints = [
				{title: BX.message("USER_DISK_MENU_UPLOAD"), sectionCode: "usermenu", id: "upload"},
				{title: BX.message("USER_DISK_MENU_SORT_DATE_CREATE"), sectionCode: "sort", id: "UPDATE_TIME", iconUrl: UserDisk.pathToIcon("check.png")},
				{title: BX.message("USER_DISK_MENU_SORT_DATE_UPDATE"), sectionCode: "sort", id: "CREATE_TIME"},
				{title: BX.message("USER_DISK_MENU_SORT_TYPE"), sectionCode: "sort", id: "TYPE"},
				{title: BX.message("USER_DISK_MENU_SORT_NAME"), sectionCode: "sort", id: "NAME"},
				{title: BX.message("USER_DISK_MENU_SORT_MIX"), sectionCode: "mix_sort", id: "MIXSORT"}
			];

			if(this.ownerId != this.userId && this.entityType === "user")
			{
				popupPoints.shift();
			}

			let sortSettings = this.sortSettings();

			popupPoints = popupPoints.map( item =>{
				if(item.sectionCode === "sort")
				{
					if(sortSettings.field == item.id)
					{
						item.iconUrl = UserDisk.pathToIcon("check.png");
					}
					else
					{
						item.iconUrl = "";
					}
				}

				if(item.id === "MIXSORT")
				{
					item.iconUrl = this.mixedSort()? UserDisk.pathToIcon("check.png"): UserDisk.pathToIcon("noimage.png");
				}

				return item;
			});

			this.popupMenu.setData(popupPoints,
				[{id: "usermenu", title: ""}, {id: "sort", title: BX.message("USER_DISK_MENU_SORT")}, {id: "mix_sort", title: " "
			} ],
				(event, item) =>
				{
					if (event === "onItemSelected")
					{
						if (item.id === "upload")
						{
							bitrix24Disk.show({
								listener: () => this.refresh(),
								folderId: this.folderId,
								storageId: this.storageId,
								multipleUpload: true
							});
						}

						if (item.sectionCode === "sort")
						{
							this.sort(item.id);
							setTimeout(()=>this.redrawMenu(), 1000);
						}

						if (item.id === "MIXSORT")
						{
							this.setUseMixedSort(!this.mixedSort());
							dialogs.showLoadingIndicator();
							this.refresh();
							setTimeout(()=>this.redrawMenu(), 1000);
						}
					}
				});
		}

		resolvedFolderId()
		{
			this.storageId = Application.storage.get("user.storage_" + this.ownerId);
			this.rootFolderId = Application.storage.get("user.storage.root_" + this.ownerId);
			if (this.folderId)
			{
				return this.folderId;
			}
			else if (this.rootFolderId)
			{
				this.folderId = this.rootFolderId;
			}

			return this.folderId;
		}

		onError(result, error, more)
		{
			if((error instanceof Error) && error.code == 403)
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
			Application.storage.set("user.storage_" + this.ownerId, storageId);
			this.storageId = storageId;
		}

		onRootFolderIdUpdate(id = null)
		{
			Application.storage.set("user.storage.root_" + this.ownerId, id);
			this.rootFolderId = id;
		}

		onResult(result, more)
		{
			BX.onViewLoaded(
				()=>{
					if(result)
					{
						console.log(result);
						let items = result.items || [];

						if (this.request.hasNext())
						{
							this.showMore();
						}
						else
						{
							this.list.setSectionItems([], "service");
						}

						let files = items.map(item => UserDisk.prepareItem(item));
						let isEmptyList = this.items.length == 0;
						this.items = more ? this.items.concat(files) : files;
						if (this.items.length == 0)
						{
							this.showEmptyFolder();
						}
						else
						{
							if (isEmptyList)
							{
								this.setItems(this.items);
							}
							else
							{
								this.list.addItems(files);
							}
						}
					}
				}
			);

		}

		handler(result, more, error)
		{
			this.list.stopRefreshing();
			dialogs.hideLoadingIndicator();
			if(error)
				this.onError(result, more, error);

			if(result.storageId)
				this.onStorageDataUpdate(result.storageId);
			if(result.rootFolderId)
				this.onRootFolderIdUpdate(result.rootFolderId);

			if(this.folderId == null)
			{
				if(result.folderId)
					this.folderId = result.folderId;
			}


			if(result && result.items)
			{
				this.onResult(result,more)
			}
		}

		setItems(items = [])
		{
			this.list.setSectionItems(items, "list");
		}

		showLoading()
		{
			let loading = ListHolder.Loading;
			loading.id = "loading";
			loading["params"] = {};
			this.list.setSectionItems([loading], "service");
		}

		showMore()
		{
			let more = ListHolder.MoreButton;
			more.id = "more";
			more["params"] = {id: "more"};
			this.list.setSectionItems([more], "service");
		}

		showAccessDenied()
		{
			let access = ListHolder.MoreButton;
			access.id = "noaccess";
			access.title = BX.message("USER_DISK_ACCESS_DENIED");
			access["params"] = {id: "noaccess"};
			this.list.setSectionItems([access], "service");
		}

		showEmptyFolder()
		{
			this.list.setSectionItems([
				{
					title: BX.message("USER_DISK_EMPTY_FOLDER"),
					type: "button",
					styles: {
						title: {
							font: {
								size: 17,
								fontStyle: "medium"
							}
						}
					},
					unselectable: true
				}], "service");
		}

		showError()
		{
			this.list.setSectionItems([
				{
					title: BX.message("USER_DISK_ERROR"),
					type: "button",
					styles: {
						title: {
							font: {
								size: 17,
								fontStyle: "medium"
							}
						}
					},
					unselectable: true
				}], "service");
		}

		sort(sortField)
		{

			let currentSort = this.sortSettings();
			if (sortField == currentSort.field)
			{
				currentSort.direction = (currentSort.direction == "DESC" ? "ASC" : "DESC");
			}
			else
			{
				currentSort.field = sortField;
				currentSort.direction = "DESC";
			}

			this.setSortSettings(currentSort.field, currentSort.direction);
			this.request.options["order"] = {[currentSort.field]: currentSort.direction};
			dialogs.showLoadingIndicator();
			this.refresh();
		}

		setSortSettings(field = "UPDATE_TIME", direction = "DESC")
		{
			let key = "sort_disk_" + this.ownerId;
			Application.storage.setObject(key, {field, direction});
		}

		/**
		 * @return {{field:String, direction:String}} sort;
		 */
		sortSettings()
		{
			let key = "sort_disk_" + this.ownerId;
			return Application.storage.getObject(key, {
				field: "UPDATE_TIME",
				direction: "DESC"
			});
		}

		setUseMixedSort(mixed = true)
		{
			Application.storage.setBoolean("sort_mixed_"+this.ownerId, mixed);
		}

		mixedSort()
		{
			return Application.storage.getBoolean("sort_mixed_"+this.ownerId, false);
		}

		destroy()
		{
			this._popupMenu = null;
			this._list = null;
		}

		static prepareItem(item)
		{
			let preparedItem = {
				id: item["REAL_OBJECT_ID"],
				title: item["NAME"],
				sectionCode: "list",
				clientSort: {
					UPDATE_TIME: (new Date(item["UPDATE_TIME"])).getTime(),
					CREATE_TIME: (new Date(item["CREATE_TIME"])).getTime(),
					TYPE: item["TYPE"] == "file" ? 1 : 0,
					NAME: item["NAME"],
				},
				styles: {
					image: {
						image: {borderRadius: 0}
					},
					title: {
						font: {
							color: "#333333",
							size: 17,
							fontStyle: "medium"
						}
					}
				},
				actions: [
					{title: BX.message("USER_DISK_REMOVE"), color: "#FB5D54", identifier: "remove"}
				],
				params: {
					type: item["TYPE"],
				}
			};
			preparedItem.height = 64;

			if (item["TYPE"] == "folder")
			{
				preparedItem.imageUrl = `${pathToExtension}images/folder.png?2`;

			}
			else
			{
				preparedItem.type = "info";
				preparedItem.imageUrl = `${pathToExtension}/images/${UserDisk.iconByFileName(item["NAME"])}`;
				preparedItem.params.filename = item["NAME"];
				preparedItem.params.contentType = UserDisk.typeByFilename(item["NAME"]);
				preparedItem.params.url = downloadPath + item["ID"];

				let size = item["SIZE"] / 1024;
				size = size < 512 ? Math.ceil(size) + "KB" : (size / 1024).toFixed(1) + "MB";
				preparedItem.subtitle = `${size} ${(new Date(item["UPDATE_TIME"])).toLocaleString()}`
			}

			return preparedItem;
		}

		static remove(id = null, type = null)
		{
			return UserDisk.makeOperation("markdeleted", id, type);
		}

		static restore(id = null, type = null)
		{
			return UserDisk.makeOperation("restore", id, type);
		}

		static getPublicLink(id = null, type = null)
		{
			return UserDisk.makeOperation("getexternallink", id, type);
		}

		static makeOperation(operationName = "", id = "", entityType = "")
		{
			let restNamespace = {
				"file": "disk.file",
				"folder": "disk.folder",
			};

			return new Promise((resolve, reject) =>
			{

				if (restNamespace[entityType])
				{
					let method = restNamespace[entityType] + "." + operationName;
					BX.rest.callMethod(
						method,
						{
							id: id
						},
						function (result)
						{
							if (result.error())
							{
								reject(result.error());
							}
							else
							{
								resolve(result.data())
							}
						}
					);
				}
				else
				{
					reject({code: -1000, description: `Unknown type of entity (${entityType})`});
				}
			});
		}

		static openFile(url, type, name = "")
		{
			if (type == "video")
			{
				viewer.openVideo(url)
			}
			else if (type == "image")
			{
				viewer.openImage(url, name)
			}
			else
			{
				viewer.openDocument(url, name)
			}
		}

		static showMessage(message = "", title = "")
		{
			if (typeof InAppNotifier != "undefined")
			{
				InAppNotifier.showNotification({
					title: title,
					backgroundColor: "#075776",
					time: 2,
					blur: true,
					message: message
				})
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

		static iconByFileName(fileName = "")
		{
			let icons = {
				'pdf': 'pdf.png',
				'jpg': 'img.png',
				'png': 'img.png',
				'doc': 'doc.png',
				'docx': 'doc.png',
				'ppt': 'ppt.png',
				'pptx': 'ppt.png',
				'rar': 'rar.png',
				'xls': 'xls.png',
				'csv': 'xls.png',
				'xlsx': 'xls.png',
				'zip': 'zip.png',
				'txt': 'txt.png',
				'avi': 'movie.png',
				'mov': 'movie.png',
				'mpeg': 'movie.png',
				'mp4': 'movie.png',
			};

			var fileExt = fileName.split('.').pop();
			if (fileExt)
			{
				fileExt = fileExt.toLowerCase();
			}

			return icons[fileExt] ? icons[fileExt] + "?2" : "blank.png?21"
		}

		static typeByFilename(fileName = "")
		{
			const types = {
				'jpg': 'image',
				'jpeg': 'image',
				'png': 'image',
				'bmp': 'image',
				'avi': 'video',
				'mov': 'video',
				'mpeg': 'video',
				'mp4': 'video',
			};

			let fileExt = fileName.split('.').pop();
			fileExt = fileExt.toLowerCase();

			return types[fileExt] ? types[fileExt] : "document"
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

	this.UserDisk = UserDisk;
})();
/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Provider = this.BX.Tasks.V2.Provider || {};
(function (exports,main_core_events,ui_uploader_core,ui_uploader_vue,ui_vue3,ui_notification,disk_uploader_userFieldWidget,tasks_v2_const,tasks_v2_core,tasks_v2_lib_apiClient,main_core) {
	'use strict';

	function mapDtoToModel(fileDto) {
	  return {
	    serverFileId: fileDto.id,
	    serverId: fileDto.serverId,
	    type: fileDto.type,
	    name: fileDto.name,
	    size: fileDto.size,
	    width: fileDto.width,
	    height: fileDto.height,
	    isImage: fileDto.isImage,
	    isVideo: fileDto.isVideo,
	    treatImageAsFile: fileDto.treatImageAsFile,
	    downloadUrl: fileDto.downloadUrl,
	    serverPreviewUrl: fileDto.serverPreviewUrl,
	    serverPreviewWidth: fileDto.serverPreviewWidth,
	    serverPreviewHeight: fileDto.serverPreviewHeight,
	    customData: fileDto.customData,
	    viewerAttrs: fileDto.viewerAttrs
	  };
	}

	const processCheckListFileIds = fileIds => {
	  if (!Array.isArray(fileIds)) {
	    return [];
	  }
	  return fileIds.reduce((result, item) => {
	    if (main_core.Type.isObjectLike(item) && 'id' in item && 'fileId' in item) {
	      result.push({
	        id: item.id,
	        fileId: item.fileId
	      });
	    } else if (main_core.Type.isString(item) && item.startsWith('n')) {
	      result.push({
	        id: item,
	        fileId: item
	      });
	    }
	    return result;
	  }, []);
	};

	const EntityTypes = Object.freeze({
	  Task: 'task',
	  CheckListItem: 'checkListItem'
	});
	var _entityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _entityType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _menu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menu");
	var _loadedIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadedIds");
	var _objectsIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("objectsIds");
	var _promises = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("promises");
	var _adapter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adapter");
	var _fileBrowserClosed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fileBrowserClosed");
	var _filesToAttach = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filesToAttach");
	var _filesToDetach = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filesToDetach");
	var _isDetachedErrorMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDetachedErrorMode");
	var _saveAttachedFilesDebounced = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveAttachedFilesDebounced");
	var _saveDetachedFilesDebounced = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveDetachedFilesDebounced");
	var _browseElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("browseElement");
	var _addLoadedIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addLoadedIds");
	var _addLoadedObjectsIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addLoadedObjectsIds");
	var _removeLoadedObjectsIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeLoadedObjectsIds");
	var _getIdsByObjectId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIdsByObjectId");
	var _attachFiles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("attachFiles");
	var _detachFiles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("detachFiles");
	var _getEntityFileIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEntityFileIds");
	var _processCheckListFileIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("processCheckListFileIds");
	var _isRealId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRealId");
	var _isNewFile = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isNewFile");
	var _saveAttachedFiles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveAttachedFiles");
	var _saveDetachedFiles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveDetachedFiles");
	var _entityFileIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityFileIds");
	class FileService extends main_core_events.EventEmitter {
	  constructor(entityId, entityType = EntityTypes.Task, options = {}) {
	    super();
	    Object.defineProperty(this, _entityFileIds, {
	      get: _get_entityFileIds,
	      set: void 0
	    });
	    Object.defineProperty(this, _saveDetachedFiles, {
	      value: _saveDetachedFiles2
	    });
	    Object.defineProperty(this, _saveAttachedFiles, {
	      value: _saveAttachedFiles2
	    });
	    Object.defineProperty(this, _isNewFile, {
	      value: _isNewFile2
	    });
	    Object.defineProperty(this, _isRealId, {
	      value: _isRealId2
	    });
	    Object.defineProperty(this, _processCheckListFileIds, {
	      value: _processCheckListFileIds2
	    });
	    Object.defineProperty(this, _getEntityFileIds, {
	      value: _getEntityFileIds2
	    });
	    Object.defineProperty(this, _detachFiles, {
	      value: _detachFiles2
	    });
	    Object.defineProperty(this, _attachFiles, {
	      value: _attachFiles2
	    });
	    Object.defineProperty(this, _getIdsByObjectId, {
	      value: _getIdsByObjectId2
	    });
	    Object.defineProperty(this, _removeLoadedObjectsIds, {
	      value: _removeLoadedObjectsIds2
	    });
	    Object.defineProperty(this, _addLoadedObjectsIds, {
	      value: _addLoadedObjectsIds2
	    });
	    Object.defineProperty(this, _addLoadedIds, {
	      value: _addLoadedIds2
	    });
	    Object.defineProperty(this, _entityId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loadedIds, {
	      writable: true,
	      value: new Set()
	    });
	    Object.defineProperty(this, _objectsIds, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _promises, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _adapter, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _fileBrowserClosed, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _filesToAttach, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _filesToDetach, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _isDetachedErrorMode, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _saveAttachedFilesDebounced, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _saveDetachedFilesDebounced, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _browseElement, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('Tasks.V2.Provider.Service.FileService');
	    this.setEntityId(entityId, entityType);
	    this.initAdapter(entityId, entityType);
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _saveAttachedFilesDebounced)[_saveAttachedFilesDebounced] = main_core.Runtime.debounce(babelHelpers.classPrivateFieldLooseBase(this, _saveAttachedFiles)[_saveAttachedFiles], 3000, this);
	    babelHelpers.classPrivateFieldLooseBase(this, _saveDetachedFilesDebounced)[_saveDetachedFilesDebounced] = main_core.Runtime.debounce(babelHelpers.classPrivateFieldLooseBase(this, _saveDetachedFiles)[_saveDetachedFiles], 3000, this);
	  }
	  initAdapter(entityId, entityType) {
	    babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter] = new ui_uploader_vue.VueUploaderAdapter({
	      id: getServiceKey(entityId, entityType),
	      controller: 'disk.uf.integration.diskUploaderController',
	      imagePreviewHeight: 1200,
	      imagePreviewWidth: 1200,
	      imagePreviewQuality: 0.85,
	      ignoreUnknownImageTypes: true,
	      treatOversizeImageAsFile: true,
	      multiple: true,
	      maxFileSize: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].subscribeFromOptions({
	      'Item:onAdd': event => {
	        const {
	          item: file
	        } = event.getData();
	        babelHelpers.classPrivateFieldLooseBase(this, _addLoadedIds)[_addLoadedIds]([file.serverFileId]);
	        this.emit('onFileAdd');
	      },
	      'Item:onComplete': event => {
	        const {
	          item: file
	        } = event.getData();
	        babelHelpers.classPrivateFieldLooseBase(this, _addLoadedObjectsIds)[_addLoadedObjectsIds]([file]);
	        const fileIds = new Set(babelHelpers.classPrivateFieldLooseBase(this, _entityFileIds)[_entityFileIds]);
	        if (!babelHelpers.classPrivateFieldLooseBase(this, _getIdsByObjectId)[_getIdsByObjectId](file.customData.objectId).some(id => fileIds.has(id))) {
	          babelHelpers.classPrivateFieldLooseBase(this, _attachFiles)[_attachFiles]([...fileIds, file.serverFileId], file);
	        }
	        this.emit('onFileComplete', file);
	      },
	      'Item:onRemove': event => {
	        const {
	          item: file
	        } = event.getData();
	        const idsToRemove = new Set(babelHelpers.classPrivateFieldLooseBase(this, _getIdsByObjectId)[_getIdsByObjectId](file.customData.objectId));
	        babelHelpers.classPrivateFieldLooseBase(this, _removeLoadedObjectsIds)[_removeLoadedObjectsIds](idsToRemove);
	        babelHelpers.classPrivateFieldLooseBase(this, _detachFiles)[_detachFiles](babelHelpers.classPrivateFieldLooseBase(this, _getEntityFileIds)[_getEntityFileIds](idsToRemove), file);
	        this.emit('onFileRemove', {
	          file
	        });
	      }
	    });
	  }
	  setEntityId(entityId, entityType = EntityTypes.Task) {
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] = entityId;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] = entityType;
	  }
	  getEntityId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId];
	  }
	  getAdapter() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter];
	  }
	  getFiles() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getReactiveItems();
	  }
	  isUploading() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getItems().some(({
	      status
	    }) => [ui_uploader_core.FileStatus.UPLOADING, ui_uploader_core.FileStatus.LOADING].includes(status));
	  }
	  hasUploadingError() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getItems().some(({
	      status
	    }) => [ui_uploader_core.FileStatus.UPLOAD_FAILED, ui_uploader_core.FileStatus.LOAD_FAILED].includes(status));
	  }
	  browse(params) {
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu] = new disk_uploader_userFieldWidget.UserFieldMenu({
	      dialogId: 'task-card',
	      uploader: babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getUploader(),
	      compact: params.compact || false,
	      menuOptions: {
	        minWidth: 220,
	        animation: 'fading',
	        closeByEsc: true,
	        bindOptions: {
	          forceBindPosition: true,
	          forceTop: true,
	          position: 'top'
	        },
	        events: {
	          onPopupClose: () => {
	            params.onHideCallback == null ? void 0 : params.onHideCallback();
	          },
	          onPopupShow: () => {
	            params.onShowCallback == null ? void 0 : params.onShowCallback();
	          }
	        }
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].show(params.bindElement);
	  }
	  browseFiles() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _browseElement)[_browseElement]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _browseElement)[_browseElement] = document.createElement('div');
	      babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getUploader().assignBrowse(babelHelpers.classPrivateFieldLooseBase(this, _browseElement)[_browseElement]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _browseElement)[_browseElement].click();
	  }
	  browseMyDrive() {
	    disk_uploader_userFieldWidget.openDiskFileDialog({
	      dialogId: 'task-card',
	      uploader: babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getUploader()
	    });
	  }
	  setFileBrowserClosed(value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _fileBrowserClosed)[_fileBrowserClosed] = value;
	    this.emit('onFileBrowserClosed');
	  }
	  isFileBrowserClosed() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _fileBrowserClosed)[_fileBrowserClosed];
	  }
	  resetFileBrowserClosedState() {
	    babelHelpers.classPrivateFieldLooseBase(this, _fileBrowserClosed)[_fileBrowserClosed] = false;
	  }
	  destroy() {
	    babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].unsubscribeAll('Item:onAdd');
	    babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].unsubscribeAll('Item:onComplete');
	    babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].unsubscribeAll('Item:onRemove');
	    babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getUploader().destroy();
	  }
	  async sync(ids) {
	    if (!main_core.Type.isArrayFilled(ids)) {
	      return;
	    }
	    if (ids.every(id => babelHelpers.classPrivateFieldLooseBase(this, _loadedIds)[_loadedIds].has(id))) {
	      babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getItems().forEach(file => {
	        const uploaderIds = [file.serverFileId];
	        if (babelHelpers.classPrivateFieldLooseBase(this, _isNewFile)[_isNewFile](file.serverFileId)) {
	          const objectId = Number(file.serverFileId.slice(1));
	          uploaderIds.push(...babelHelpers.classPrivateFieldLooseBase(this, _getIdsByObjectId)[_getIdsByObjectId](objectId));
	        }
	        if (uploaderIds.some(id => ids.includes(id))) {
	          return;
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getUploader().removeFile(file.id);
	      });
	    }
	    await this.list(ids);
	  }
	  async list(ids) {
	    const unloadedIds = ids.filter(id => !babelHelpers.classPrivateFieldLooseBase(this, _isNewFile)[_isNewFile](id) && !babelHelpers.classPrivateFieldLooseBase(this, _loadedIds)[_loadedIds].has(id));
	    if (unloadedIds.length === 0) {
	      await Promise.all(babelHelpers.classPrivateFieldLooseBase(this, _promises)[_promises]);
	      return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getItems();
	    }
	    const promise = new Resolvable();
	    babelHelpers.classPrivateFieldLooseBase(this, _promises)[_promises].push(promise);
	    babelHelpers.classPrivateFieldLooseBase(this, _addLoadedIds)[_addLoadedIds](unloadedIds);
	    try {
	      let data = [];
	      if (babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] === EntityTypes.Task) {
	        data = await tasks_v2_lib_apiClient.apiClient.post('File.list', {
	          taskId: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	          ids: unloadedIds
	        });
	      } else if (babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] === EntityTypes.CheckListItem) {
	        data = await tasks_v2_lib_apiClient.apiClient.post('Task.CheckList.File.list', {
	          taskId: babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].parentEntityId,
	          ids: unloadedIds
	        });
	      }
	      const files = data.map(fileDto => mapDtoToModel(fileDto));
	      const objectsIds = new Set(Object.values(babelHelpers.classPrivateFieldLooseBase(this, _objectsIds)[_objectsIds]));
	      const newFiles = files.filter(({
	        customData
	      }) => !objectsIds.has(customData.objectId));
	      babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getUploader().addFiles(newFiles);
	      babelHelpers.classPrivateFieldLooseBase(this, _addLoadedObjectsIds)[_addLoadedObjectsIds](files);
	      promise.resolve();
	      await Promise.all(babelHelpers.classPrivateFieldLooseBase(this, _promises)[_promises]);
	      return babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getItems();
	    } catch (error) {
	      console.error('FileService: list error', error);
	      return [];
	    }
	  }
	  notifyError(content) {
	    ui_notification.UI.Notification.Center.notify({
	      content,
	      id: `file-service-error-${main_core.Text.getRandom()}`
	    });
	  }
	  get $store() {
	    return tasks_v2_core.Core.getStore();
	  }
	}
	function _addLoadedIds2(ids) {
	  ids.forEach(id => babelHelpers.classPrivateFieldLooseBase(this, _loadedIds)[_loadedIds].add(id));
	}
	function _addLoadedObjectsIds2(files) {
	  files.forEach(file => {
	    babelHelpers.classPrivateFieldLooseBase(this, _objectsIds)[_objectsIds][file.serverFileId] = file.customData.objectId;
	  });
	}
	function _removeLoadedObjectsIds2(ids) {
	  ids.forEach(id => {
	    delete babelHelpers.classPrivateFieldLooseBase(this, _objectsIds)[_objectsIds][id];
	  });
	}
	function _getIdsByObjectId2(objectIdToFind) {
	  return Object.entries(babelHelpers.classPrivateFieldLooseBase(this, _objectsIds)[_objectsIds]).filter(([, objectId]) => objectId === objectIdToFind).map(([id]) => babelHelpers.classPrivateFieldLooseBase(this, _isNewFile)[_isNewFile](id) ? id : Number(id));
	}
	function _attachFiles2(fileIds, attachedFile) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] === EntityTypes.Task) {
	    const id = babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId];
	    void this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	      id,
	      fields: {
	        fileIds
	      }
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isDetachedErrorMode)[_isDetachedErrorMode]) {
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isRealId)[_isRealId](id) && babelHelpers.classPrivateFieldLooseBase(this, _isNewFile)[_isNewFile](attachedFile.serverFileId)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _filesToAttach)[_filesToAttach].push(attachedFile);
	      babelHelpers.classPrivateFieldLooseBase(this, _saveAttachedFilesDebounced)[_saveAttachedFilesDebounced]();
	    }
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] === EntityTypes.CheckListItem) {
	    babelHelpers.classPrivateFieldLooseBase(this, _processCheckListFileIds)[_processCheckListFileIds](fileIds);
	  }
	}
	function _detachFiles2(fileIds, detachedFile) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] === EntityTypes.Task) {
	    const id = babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId];
	    void this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	      id,
	      fields: {
	        fileIds
	      }
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isRealId)[_isRealId](id)) {
	      const detachedId = detachedFile.serverFileId;
	      const attachedIndex = babelHelpers.classPrivateFieldLooseBase(this, _filesToAttach)[_filesToAttach].findIndex(file => file.serverFileId === detachedId);
	      if (attachedIndex === -1) {
	        babelHelpers.classPrivateFieldLooseBase(this, _filesToDetach)[_filesToDetach].push(detachedFile);
	        babelHelpers.classPrivateFieldLooseBase(this, _saveDetachedFilesDebounced)[_saveDetachedFilesDebounced]();
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _filesToAttach)[_filesToAttach].splice(attachedIndex, 1);
	      }
	    }
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] === EntityTypes.CheckListItem) {
	    babelHelpers.classPrivateFieldLooseBase(this, _processCheckListFileIds)[_processCheckListFileIds](fileIds);
	  }
	}
	function _getEntityFileIds2(excludedIds = new Set()) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] === EntityTypes.Task) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityFileIds)[_entityFileIds].filter(id => !excludedIds.has(id));
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] === EntityTypes.CheckListItem) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityFileIds)[_entityFileIds].filter(attach => !excludedIds.has(attach.id));
	  }
	  return [];
	}
	function _processCheckListFileIds2(fileIds) {
	  const attachments = processCheckListFileIds(fileIds);
	  void this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/update`, {
	    id: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	    fields: {
	      attachments
	    }
	  });
	}
	function _isRealId2(id) {
	  return Number.isInteger(id) && id > 0;
	}
	function _isNewFile2(id) {
	  return String(id).startsWith('n');
	}
	async function _saveAttachedFiles2() {
	  if (main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _filesToAttach)[_filesToAttach])) {
	    try {
	      const ids = babelHelpers.classPrivateFieldLooseBase(this, _filesToAttach)[_filesToAttach].map(file => file.serverFileId);
	      babelHelpers.classPrivateFieldLooseBase(this, _filesToAttach)[_filesToAttach] = [];
	      void tasks_v2_lib_apiClient.apiClient.post('File.attach', {
	        task: {
	          id: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId]
	        },
	        ids
	      });
	    } catch (error) {
	      console.error('FileService: saveAttachedFiles error', error);
	      this.notifyError(main_core.Loc.getMessage('TASKS_V2_NOTIFY_FILE_ATTACH_ERROR'));
	    }
	  }
	}
	async function _saveDetachedFiles2() {
	  if (main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _filesToDetach)[_filesToDetach])) {
	    const filesBeforeDetach = babelHelpers.classPrivateFieldLooseBase(this, _filesToDetach)[_filesToDetach];
	    try {
	      const ids = babelHelpers.classPrivateFieldLooseBase(this, _filesToDetach)[_filesToDetach].map(file => file.serverFileId);
	      babelHelpers.classPrivateFieldLooseBase(this, _filesToDetach)[_filesToDetach] = [];
	      await tasks_v2_lib_apiClient.apiClient.post('File.detach', {
	        task: {
	          id: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId]
	        },
	        ids
	      });
	    } catch (error) {
	      console.error('FileService: saveDetachedFiles error', error);
	      babelHelpers.classPrivateFieldLooseBase(this, _isDetachedErrorMode)[_isDetachedErrorMode] = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _adapter)[_adapter].getUploader().addFiles(filesBeforeDetach);
	      babelHelpers.classPrivateFieldLooseBase(this, _isDetachedErrorMode)[_isDetachedErrorMode] = false;
	      this.notifyError(main_core.Loc.getMessage('TASKS_V2_NOTIFY_FILE_DETACH_ERROR'));
	    }
	  }
	}
	function _get_entityFileIds() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] === EntityTypes.Task) {
	    return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId]).fileIds;
	  }
	  return this.$store.getters[`${tasks_v2_const.Model.CheckList}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId]).attachments;
	}
	const services = {};
	function getServiceKey(entityId, entityType) {
	  return `${entityType}:${entityId}`;
	}
	const fileService = {
	  get(entityId, entityType = EntityTypes.Task, options = {}) {
	    var _services$key;
	    const key = getServiceKey(entityId, entityType);
	    (_services$key = services[key]) != null ? _services$key : services[key] = new FileService(entityId, entityType, options);
	    return services[key];
	  },
	  replace(tempId, entityId, entityType = EntityTypes.Task) {
	    const oldKey = getServiceKey(tempId, entityType);
	    const newKey = getServiceKey(entityId, entityType);
	    services[newKey] = services[oldKey];
	    services[newKey].setEntityId(entityId, entityType);
	    delete services[oldKey];
	  },
	  delete(entityId, entityType = EntityTypes.Task) {
	    var _services$key2;
	    const key = getServiceKey(entityId, entityType);
	    (_services$key2 = services[key]) == null ? void 0 : _services$key2.destroy();
	    delete services[key];
	  }
	};
	function Resolvable() {
	  const promise = new Promise(resolve => {
	    this.resolve = resolve;
	  });
	  promise.resolve = this.resolve;
	  return promise;
	}

	exports.fileService = fileService;
	exports.EntityTypes = EntityTypes;

}((this.BX.Tasks.V2.Provider.Service = this.BX.Tasks.V2.Provider.Service || {}),BX.Event,BX.UI.Uploader,BX.UI.Uploader,BX.Vue3,BX,BX.Disk.Uploader,BX.Tasks.V2.Const,BX.Tasks.V2,BX.Tasks.V2.Lib,BX));
//# sourceMappingURL=file-service.bundle.js.map

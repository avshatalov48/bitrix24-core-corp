this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,ui_uploader_tileWidget) {
	'use strict';

	let _ = t => t,
	  _t;
	const MAX_UPLOAD_FILE_SIZE = 1024 * 1024 * 50; // 50M;
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _widget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("widget");
	var _assertValidParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("assertValidParams");
	class FileUploader {
	  constructor(_params) {
	    Object.defineProperty(this, _assertValidParams, {
	      value: _assertValidParams2
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _widget, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _assertValidParams)[_assertValidParams](_params);
	    babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget] = new ui_uploader_tileWidget.TileWidget({
	      controller: 'crm.fileUploader.todoActivityUploaderController',
	      controllerOptions: {
	        entityId: _params.ownerId,
	        entityTypeId: _params.ownerTypeId,
	        activityId: _params.activityId
	      },
	      files: main_core.Type.isArrayFilled(_params.files) ? _params.files : [],
	      events: main_core.Type.isPlainObject(_params.events) ? _params.events : {},
	      multiple: true,
	      autoUpload: true,
	      maxFileSize: MAX_UPLOAD_FILE_SIZE
	    });
	    if (main_core.Type.isDomNode(_params.baseContainer)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = main_core.Tag.render(_t || (_t = _`<div class="crm-activity__todo-editor-file-uploader-wrapper"></div>`));
	      const baseContainer = _params.baseContainer;
	      main_core.Dom.insertAfter(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], baseContainer);
	      babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget].renderTo(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	    }
	  }
	  getWidget() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget];
	  }
	  getContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  renderTo(container) {
	    if (!main_core.Type.isDomNode(container)) {
	      throw new Error('FileUploader container must be a DOM Node');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = container;
	    babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget].renderTo(container);
	  }
	  getFiles() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget].getUploader().getFiles();
	  }
	  getServerFileIds() {
	    const files = babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget].getUploader().getFiles();
	    if (files.length === 0) {
	      return [];
	    }
	    const completedFiles = files.filter(file => file.isComplete());
	    if (completedFiles.length === 0) {
	      return [];
	    }
	    return completedFiles.map(file => file.getServerId());
	  }
	}
	function _assertValidParams2(params) {
	  if (!main_core.Type.isPlainObject(params)) {
	    throw new Error('BX.Crm.Activity.FileUploader: The "params" argument must be object.');
	  }
	  if (!main_core.Type.isNumber(params.ownerId)) {
	    throw new Error('BX.Crm.Activity.FileUploader: The "ownerId" argument must be set.');
	  }
	  if (!main_core.Type.isNumber(params.ownerTypeId)) {
	    throw new Error('BX.Crm.Activity.FileUploader: The "ownerTypeId" argument must be set.');
	  }
	}

	exports.FileUploader = FileUploader;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX,BX.UI.Uploader));
//# sourceMappingURL=file-uploader.bundle.js.map

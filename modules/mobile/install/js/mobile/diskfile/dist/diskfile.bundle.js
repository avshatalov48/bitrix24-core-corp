this.BX = this.BX || {};
(function (exports,mobile_imageviewer,mobile_ajax,ui_vue_components_audioplayer,ui_vue,main_core) {
	'use strict';

	var ImageController = /*#__PURE__*/function () {
	  function ImageController(params) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, ImageController);
	    var imagesNode = params.imagesNode && main_core.Type.isDomNode(params.imagesNode) ? params.imagesNode : null,
	        moreFilesNode = params.moreFilesNode && main_core.Type.isDomNode(params.moreFilesNode) ? params.moreFilesNode : null,
	        toggleViewNode = params.toggleViewNode && main_core.Type.isDomNode(params.toggleViewNode) ? params.toggleViewNode : null,
	        imagesIdList = params.imagesIdList && main_core.Type.isArray(params.imagesIdList) ? params.imagesIdList : [];
	    this.signedParameters = params.signedParameters && main_core.Type.isStringFilled(params.signedParameters) ? params.signedParameters : '';

	    if (imagesIdList.length > 0) {
	      BitrixMobile.LazyLoad.registerImages(imagesIdList, typeof oMSL != 'undefined' ? oMSL.checkVisibility : false);
	    }

	    if (imagesNode) {
	      this.initViewer(imagesNode);
	    }

	    if (moreFilesNode) {
	      main_core.Event.bind(moreFilesNode, 'click', function (e) {
	        _this.showMoreDiskFiles(e.currentTarget);

	        e.preventDefault();
	      });
	    }

	    if (toggleViewNode) {
	      main_core.Event.bind(toggleViewNode, 'click', function (e) {
	        var viewType = e.currentTarget.getAttribute('data-bx-view-type'),
	            container = e.currentTarget.closest('.disk-ui-file-container');

	        if (container) {
	          _this.toggleViewType({
	            viewType: viewType,
	            container: container
	          });
	        }

	        e.preventDefault();
	      });
	    }
	  }

	  babelHelpers.createClass(ImageController, [{
	    key: "initViewer",
	    value: function initViewer(node) {
	      if (!main_core.Type.isDomNode(node)) {
	        return;
	      }

	      mobile_imageviewer.MobileImageViewer.viewImageBind(node, 'img[data-bx-image]');
	    }
	  }, {
	    key: "showMoreDiskFiles",
	    value: function showMoreDiskFiles(linkNode) {
	      if (!main_core.Type.isDomNode(linkNode)) {
	        return;
	      }

	      var filesBlock = linkNode.closest('.post-item-attached-file-wrap');

	      if (filesBlock) {
	        var filesList = filesBlock.querySelectorAll('.post-item-attached-file'),
	            moreBlock = filesBlock.querySelector('.post-item-attached-file-more');

	        for (var i = 0; i < filesList.length; i++) {
	          filesList[i].classList.remove('post-item-attached-file-hidden');
	        }

	        if (moreBlock) {
	          moreBlock.parentNode.removeChild(moreBlock);
	        }
	      }
	    }
	  }, {
	    key: "toggleViewType",
	    value: function toggleViewType(params) {
	      var container = params.container && main_core.Type.isDomNode(params.container) ? params.container : null;

	      if (!container) {
	        return;
	      }

	      app.showPopupLoader({
	        text: ''
	      });
	      mobile_ajax.Ajax.runComponentAction('bitrix:disk.uf.file', 'toggleViewType', {
	        mode: 'class',
	        signedParameters: this.signedParameters,
	        data: {
	          params: {
	            viewType: params.viewType
	          }
	        }
	      }).then(function (response) {
	        app.hidePopupLoader();
	        main_core.Dom.clean(container);
	        main_core.Runtime.html(container, response.data.html).then(function () {
	          BitrixMobile.LazyLoad.showImages();
	        });
	      }, function () {
	        app.hidePopupLoader();
	      });
	    }
	  }]);
	  return ImageController;
	}();

	var File = /*#__PURE__*/function () {
	  babelHelpers.createClass(File, null, [{
	    key: "checkForPaternity",
	    value: function checkForPaternity() {
	      return true;
	    }
	  }]);

	  function File(data, container, options) {
	    babelHelpers.classCallCheck(this, File);
	    this.id = data['id'];
	    this.data = data;
	    this.container = container;
	    this.options = options;
	  }

	  babelHelpers.createClass(File, [{
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      return this.container.querySelector("#wdif-doc-".concat(this.id));
	    }
	  }]);
	  return File;
	}();

	var Audio = /*#__PURE__*/function (_File) {
	  babelHelpers.inherits(Audio, _File);
	  babelHelpers.createClass(Audio, null, [{
	    key: "checkForPaternity",
	    value: function checkForPaternity(fileData) {
	      return fileData['extension'] === 'mp3';
	    }
	  }]);

	  function Audio(data, container, options) {
	    var _this;

	    babelHelpers.classCallCheck(this, Audio);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Audio).call(this, data, container, options));
	    setTimeout(_this.renderPlayer.bind(babelHelpers.assertThisInitialized(_this)), 10);
	    return _this;
	  }

	  babelHelpers.createClass(Audio, [{
	    key: "renderPlayer",
	    value: function renderPlayer() {
	      this.getNode().classList.add('post-item-attached-audio');
	      this.getNode().innerHTML = '';
	      ui_vue.Vue.create({
	        el: this.getNode().appendChild(document.createElement('DIV')),
	        template: "<bx-audioplayer src=\"".concat(this.data.downloadUrl, "\" background=\"dark\"/>")
	      });
	    }
	  }]);
	  return Audio;
	}(File);

	var Image = /*#__PURE__*/function (_File) {
	  babelHelpers.inherits(Image, _File);
	  babelHelpers.createClass(Image, null, [{
	    key: "checkForPaternity",
	    value: function checkForPaternity(fileData) {
	      return fileData['preview'] !== undefined;
	    }
	  }]);

	  function Image(fileData, container, options) {
	    var _this;

	    babelHelpers.classCallCheck(this, Image);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Image).call(this, fileData, container, options));
	    BitrixMobile.LazyLoad.registerImages([_this.id], typeof oMSL != 'undefined' ? oMSL.checkVisibility : false);
	    return _this;
	  }

	  return Image;
	}(File);

	var fileTypeMappings = [File, Image, Audio];

	var FileController = /*#__PURE__*/function () {
	  function FileController(_ref) {
	    var files = _ref.files,
	        container = _ref.container;
	    babelHelpers.classCallCheck(this, FileController);
	    this.container = container;

	    if (main_core.Type.isDomNode(this.container)) {
	      this.initFiles(files);
	      this.bindInterface();
	    }
	  }

	  babelHelpers.createClass(FileController, [{
	    key: "initFiles",
	    value: function initFiles(files) {
	      var _this = this;

	      if (files && files.length > 0) {
	        files.forEach(function (fileData) {
	          var fileTypeClass = File;
	          fileTypeMappings.forEach(function (altFileTypeClass) {
	            if (altFileTypeClass.checkForPaternity(fileData)) {
	              fileTypeClass = altFileTypeClass;
	            }
	          });
	          new fileTypeClass(fileData, _this.container);
	        });
	      }
	    }
	  }, {
	    key: "bindInterface",
	    value: function bindInterface() {
	      var moreBlock = this.container.querySelector('.post-item-attached-file-more');

	      if (main_core.Type.isDomNode(moreBlock)) {
	        main_core.Event.bindOnce(moreBlock, 'click', function () {
	          this.container.querySelectorAll('.post-item-attached-file').forEach(function (node) {
	            node.classList.remove('post-item-attached-file-hidden');
	          });
	          moreBlock.parentNode.removeChild(moreBlock);
	        }.bind(this));
	      }
	    }
	  }]);
	  return FileController;
	}();

	var DiskFile = function DiskFile(params) {
	  babelHelpers.classCallCheck(this, DiskFile);
	  this.signedParameters = main_core.Type.isStringFilled(params.signedParameters) ? params.signedParameters : '';

	  if (params.images) {
	    new ImageController(Object.assign({
	      signedParameters: this.signedParameters
	    }, params.images));
	    new FileController(params.files);
	  } else {
	    new ImageController(params);
	  }
	};

	exports.DiskFile = DiskFile;

}((this.BX.Mobile = this.BX.Mobile || {}),BX,BX.Mobile,window,BX,BX));
//# sourceMappingURL=diskfile.bundle.js.map

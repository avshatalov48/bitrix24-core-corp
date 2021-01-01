this.BX = this.BX || {};
(function (exports,main_core,mobile_imageviewer,mobile_ajax) {
	'use strict';

	var DiskFile = /*#__PURE__*/function () {
	  function DiskFile(params) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, DiskFile);
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

	  babelHelpers.createClass(DiskFile, [{
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
	  return DiskFile;
	}();

	exports.DiskFile = DiskFile;

}((this.BX.Mobile = this.BX.Mobile || {}),BX,BX,BX.Mobile));
//# sourceMappingURL=diskfile.bundle.js.map

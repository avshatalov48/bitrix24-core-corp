/* eslint-disable */
(function (exports,main_core) {
	'use strict';

	var ImageViewer = /*#__PURE__*/function () {
	  function ImageViewer() {
	    babelHelpers.classCallCheck(this, ImageViewer);
	  }
	  babelHelpers.createClass(ImageViewer, [{
	    key: "viewImageBind",
	    value: function viewImageBind(div, targetCriteria) {
	      if (main_core.Type.isStringFilled(div)) {
	        div = document.getElementById(div);
	      }
	      if (!main_core.Type.isDomNode(div)) {
	        return;
	      }
	      var targetSelector = '';
	      if (main_core.Type.isPlainObject(targetCriteria)) {
	        var tagString = '',
	          attrString = '';
	        for (var key in targetCriteria) {
	          if (!targetCriteria.hasOwnProperty(key)) {
	            continue;
	          }
	          switch (key) {
	            case 'tag':
	              tagString = targetCriteria[key];
	              break;
	            case 'attr':
	              attrString = targetCriteria[key];
	              break;
	            default:
	          }
	        }
	        targetSelector = (main_core.Type.isStringFilled(tagString) ? tagString : '') + (main_core.Type.isStringFilled(attrString) ? '[' + attrString + ']' : '');
	      } else if (main_core.Type.isStringFilled(targetCriteria)) {
	        targetSelector = targetCriteria;
	      }
	      if (!main_core.Type.isStringFilled(targetSelector)) {
	        return;
	      }
	      main_core.Event.bind(div, 'click', function (e) {
	        if (e.target.tagName.toUpperCase() === 'A') {
	          return;
	        }
	        var found = false;
	        var siblings = e.target.parentNode.querySelectorAll(targetSelector);
	        for (var i = 0; i < siblings.length; i++) {
	          if (siblings[i].parentNode === e.target.parentNode) {
	            found = true;
	            break;
	          }
	        }
	        if (!found) {
	          return;
	        }
	        var imgNodeList = e.currentTarget.querySelectorAll(targetSelector);
	        var imgList = [],
	          photosList = [],
	          currentImage = false,
	          currentPreview = false;
	        for (var _i = 0; _i < imgNodeList.length; _i++) {
	          currentImage = imgNodeList[_i].getAttribute('data-bx-image');
	          if (!imgList.includes(currentImage)) {
	            currentPreview = imgNodeList[_i].getAttribute('data-bx-preview');
	            imgList.push(imgNodeList[_i].getAttribute('data-bx-image'));
	            photosList.push({
	              url: currentImage,
	              preview: main_core.Type.isStringFilled(currentPreview) ? currentPreview : '',
	              description: ''
	            });
	          }
	        }
	        var viewerParams = {
	          photos: photosList
	        };
	        var target = null;
	        if (e.target.tagName.toUpperCase() == 'IMG') {
	          target = e.target;
	        } else {
	          var container = e.target.closest('[data-bx-disk-image-container]');
	          if (!container) {
	            container = e.target.closest('div');
	          }
	          if (container) {
	            target = container.querySelector('img');
	          }
	        }
	        if (target) {
	          currentImage = target.getAttribute('data-bx-image');
	          if (main_core.Type.isStringFilled(currentImage)) {
	            viewerParams.default_photo = currentImage;
	          }
	          currentPreview = target.getAttribute('data-bx-preview');
	          if (main_core.Type.isStringFilled(currentPreview)) {
	            viewerParams.default_preview = currentPreview;
	          }
	        }
	        BXMobileApp.UI.Photo.show(viewerParams);
	        e.stopPropagation();
	        return e.preventDefault();
	      });
	    }
	  }, {
	    key: "view",
	    value: function view(e) {
	      e.currentTarget;
	    }
	  }]);
	  return ImageViewer;
	}();
	var MobileImageViewer = new ImageViewer();

	exports.MobileImageViewer = MobileImageViewer;

}((this.BX = this.BX || {}),BX));
//# sourceMappingURL=imageviewer.bundle.js.map

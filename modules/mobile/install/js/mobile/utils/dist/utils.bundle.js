(function (exports,main_core) {
	'use strict';

	var Utils = /*#__PURE__*/function () {
	  function Utils() {
	    babelHelpers.classCallCheck(this, Utils);
	    babelHelpers.defineProperty(this, "getFileMimeType", function (fileType) {
	      fileType = fileType.toString().toLowerCase();

	      if (fileType.indexOf('/') !== -1) // iOS old form
	        {
	          return fileType;
	        }

	      var result = '';

	      switch (fileType) {
	        case 'png':
	          result = 'image/png';
	          break;

	        case 'gif':
	          result = 'image/gif';
	          break;

	        case 'jpg':
	        case 'jpeg':
	          result = 'image/jpeg';
	          break;

	        case 'heic':
	          result = 'image/heic';
	          break;

	        case 'mp3':
	          result = 'audio/mpeg';
	          break;

	        case 'mp4':
	          result = 'video/mp4';
	          break;

	        case 'mpeg':
	          result = 'video/mpeg';
	          break;

	        case 'ogg':
	          result = 'video/ogg';
	          break;

	        case 'mov':
	          result = 'video/quicktime';
	          break;

	        case 'zip':
	          result = 'application/zip';
	          break;

	        case 'php':
	          result = 'text/php';
	          break;

	        default:
	          result = '';
	      }

	      return result;
	    });
	    babelHelpers.defineProperty(this, "getType", function (mimeType) {
	      var result = mimeType.substring(0, mimeType.indexOf('/'));

	      if (result !== 'image' && result !== 'video' && result !== 'audio') {
	        result = 'file';
	      }

	      return result;
	    });
	    babelHelpers.defineProperty(this, "getResizeOptions", function (type) {
	      var mimeType = BX.MobileUtils.getFileMimeType(type),
	          fileType = BX.MobileUtils.getType(mimeType);
	      return fileType === 'image' && mimeType !== 'image/gif' || fileType === 'video' ? {
	        quality: 80,
	        width: 1920,
	        height: 1080
	      } : null;
	    });
	    babelHelpers.defineProperty(this, "getUploadFilename", function (filename, type) {
	      var mimeType = BX.MobileUtils.getFileMimeType(type),
	          fileType = BX.MobileUtils.getType(mimeType);

	      if (fileType === 'image' || fileType === 'video') {
	        var extension = filename.split('.').slice(-1)[0].toLowerCase();

	        if (mimeType === 'image/heic') {
	          extension = 'jpg';
	        }

	        filename = 'mobile_file_' + new Date().toJSON().slice(0, 19).replace('T', '_').split(':').join('-') + '.' + extension;
	      }

	      return filename;
	    });
	  }

	  babelHelpers.createClass(Utils, null, [{
	    key: "htmlWithInlineJS",
	    value: function htmlWithInlineJS(node, html) {
	      var params = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

	      function makeIterable(value) {
	        return main_core.Type.isArray(value) ? value : [value];
	      }

	      function externalStyles(acc, item) {
	        if (main_core.Type.isString(item) && item !== '') {
	          acc.push(item);
	        }

	        return acc;
	      }

	      function externalScripts(acc, item) {
	        if (!item.isInternal) {
	          acc.push(item.JS);
	        }

	        return acc;
	      }

	      function inlineScripts(acc, item) {
	        if (item.isInternal) {
	          acc.push(item.JS);
	        }

	        return acc;
	      }

	      function loadAll(items) {
	        var itemsList = makeIterable(items);

	        if (!itemsList.length) {
	          return Promise.resolve();
	        }

	        return new Promise(function (resolve) {
	          // eslint-disable-next-line
	          BX.load(itemsList, resolve);
	        });
	      }

	      if (main_core.Type.isNil(html) && main_core.Type.isDomNode(node)) {
	        return node.innerHTML;
	      } // eslint-disable-next-line


	      var parsedHtml = BX.processHTML(html);
	      var externalCss = parsedHtml.STYLE.reduce(externalStyles, []);
	      var externalJs = parsedHtml.SCRIPT.reduce(externalScripts, []);
	      var inlineJs = parsedHtml.SCRIPT.reduce(inlineScripts, []);
	      var inlineJsString = '',
	          inlineJsStringNode = ''; // eslint-disable-next-line

	      inlineJs.forEach(function (script) {
	        inlineJsString += "\n" + script;
	      });

	      if (inlineJsString.length > 0) {
	        inlineJsStringNode = "<span data-type=\"inline-script\"><script>".concat(inlineJsString, "</script></span>");
	      }

	      if (main_core.Type.isDomNode(node)) {
	        if (params.htmlFirst || !externalJs.length && !externalCss.length) {
	          node.innerHTML = parsedHtml.HTML + inlineJsStringNode;
	        }
	      }

	      return Promise.all([loadAll(externalJs), loadAll(externalCss)]).then(function () {
	        if (main_core.Type.isDomNode(node) && (externalJs.length > 0 || externalCss.length > 0)) {
	          node.innerHTML = parsedHtml.HTML + inlineJsStringNode;
	        }

	        BX.evalGlobal(inlineJsString);

	        if (main_core.Type.isFunction(params.callback)) {
	          params.callback();
	        }
	      });
	    }
	  }]);
	  return Utils;
	}();

	var MobileUtils = new Utils();

	exports.Utils = Utils;
	exports.MobileUtils = MobileUtils;

}((this.BX = this.BX || {}),BX));
//# sourceMappingURL=utils.bundle.js.map

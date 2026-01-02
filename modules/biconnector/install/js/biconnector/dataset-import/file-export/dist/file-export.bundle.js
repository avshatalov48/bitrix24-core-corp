/* eslint-disable */
this.BX = this.BX || {};
this.BX.BIConnector = this.BX.BIConnector || {};
(function (exports,main_core) {
	'use strict';

	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _exportDownloadLinks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("exportDownloadLinks");
	var _getDownloadLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDownloadLink");
	var _downloadDatasetFromLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("downloadDatasetFromLink");
	class FileExport {
	  constructor() {
	    Object.defineProperty(this, _downloadDatasetFromLink, {
	      value: _downloadDatasetFromLink2
	    });
	    Object.defineProperty(this, _getDownloadLink, {
	      value: _getDownloadLink2
	    });
	    Object.defineProperty(this, _exportDownloadLinks, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _exportDownloadLinks)[_exportDownloadLinks] = new Map();
	  }
	  static getInstance() {
	    if (!babelHelpers.classPrivateFieldLooseBase(FileExport, _instance)[_instance]) {
	      babelHelpers.classPrivateFieldLooseBase(FileExport, _instance)[_instance] = new FileExport();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(FileExport, _instance)[_instance];
	  }
	  downloadOnce(dataset) {
	    return new Promise((resolve, reject) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _getDownloadLink)[_getDownloadLink](dataset.id).then(link => {
	        babelHelpers.classPrivateFieldLooseBase(this, _downloadDatasetFromLink)[_downloadDatasetFromLink](link, dataset);
	        URL.revokeObjectURL(link);
	        babelHelpers.classPrivateFieldLooseBase(this, _exportDownloadLinks)[_exportDownloadLinks].delete(dataset.id);
	        resolve();
	      }).catch(error => {
	        reject(error);
	      });
	    });
	  }
	  download(dataset) {
	    return new Promise((resolve, reject) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _getDownloadLink)[_getDownloadLink](dataset.id).then(link => {
	        babelHelpers.classPrivateFieldLooseBase(this, _downloadDatasetFromLink)[_downloadDatasetFromLink](link, dataset);
	        resolve();
	      }).catch(error => {
	        reject(error);
	      });
	    });
	  }
	}
	function _getDownloadLink2(datasetId) {
	  return new Promise((resolve, reject) => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _exportDownloadLinks)[_exportDownloadLinks].has(datasetId)) {
	      resolve(babelHelpers.classPrivateFieldLooseBase(this, _exportDownloadLinks)[_exportDownloadLinks].get(datasetId));
	    }
	    main_core.ajax.runAction('biconnector.externalsource.dataset.export', {
	      data: {
	        id: datasetId,
	        exportFormat: 'csv'
	      }
	    }).then(response => {
	      const blob = new Blob([response.data], {
	        type: 'text/csv'
	      });
	      const link = URL.createObjectURL(blob);
	      babelHelpers.classPrivateFieldLooseBase(this, _exportDownloadLinks)[_exportDownloadLinks].set(datasetId, link);
	      resolve(link);
	    }).catch(error => {
	      reject(error);
	    });
	  });
	}
	function _downloadDatasetFromLink2(link, dataset) {
	  var _dataset$title;
	  const anchorElement = document.createElement('a');
	  anchorElement.href = link;
	  anchorElement.download = `${(_dataset$title = dataset.title) != null ? _dataset$title : 'csv_dataset'}.csv`;
	  main_core.Dom.append(anchorElement, document.body);
	  anchorElement.click();
	  main_core.Dom.remove(anchorElement);
	}
	Object.defineProperty(FileExport, _instance, {
	  writable: true,
	  value: void 0
	});

	exports.FileExport = FileExport;

}((this.BX.BIConnector.DatasetImport = this.BX.BIConnector.DatasetImport || {}),BX));
//# sourceMappingURL=file-export.bundle.js.map

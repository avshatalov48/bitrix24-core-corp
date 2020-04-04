;(function() {
	'use strict';

	BX.namespace("BX.Disk");

	BX.Disk.Page = (function ()
	{
		var storage = null;
		var folder = null;

		return {
			getStorage: function(){
				return storage;
			},
			getFolder: function(){
				return folder;
			},
			changeFolder: function(newFolder){
				BX.onCustomEvent("Disk.Page:onChangeFolder", [folder, newFolder]);
				folder = newFolder;
			},
			changeStorage: function(newStorage){
				BX.onCustomEvent("Disk.Page:onChangeStorage", [storage, newStorage]);
				storage = newStorage;
			}
		}
	})();

}());

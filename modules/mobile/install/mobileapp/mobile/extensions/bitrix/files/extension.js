(function () {
	BX.FileUtils = { fileForReading: jn.require("files/entry").getFile }
	this.FileProcessing = new (jn.require("files/converter").FileConverter)()
	this.TaskEventConsts = BX.FileUploadEvents = jn.require("uploader/const").Events
})();

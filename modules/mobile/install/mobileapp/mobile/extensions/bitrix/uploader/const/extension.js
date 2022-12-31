/**
 * @module uploader/const
 */
jn.define("uploader/const", (require, exports, module) => {
	const Events = {
		FILE_CREATED: "onfilecreated",
		FILE_CREATED_FAILED: "onerrorfilecreate",
		FILE_UPLOAD_PROGRESS: "onprogress",
		FILE_UPLOAD_START: "onloadstart",
		FILE_UPLOAD_FAILED: "onfileuploadfailed",
		FILE_READ_ERROR: "onfilereaderror",
		ALL_TASK_COMPLETED: "oncomplete",
		TASK_TOKEN_DEFINED: "ontasktokendefined",
		TASK_STARTED_FAILED: "onloadstartfailed",
		TASK_CREATED: "ontaskcreated",
		TASK_CANCELLED: "ontaskcancelled",
		TASK_NOT_FOUND: "ontasknotfound"
	}

	module.exports = { Events }
});

this.TaskEventConsts = BX.FileUploadEvents = jn.require("uploader/const").Events;

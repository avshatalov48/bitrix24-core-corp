/**
 * @module uploader/client
 */
jn.define("uploader/client", (require, exports, module) => {

	const eventMap = {
		ontaskcreated: "create",
		onloadstart: "start",
		onprogress: "progress",
		onfilecreated: "done",
		onfilereaderror: "error",
		onloadstartfailed: "error",
		onfileuploadfailed: "error",
		onerrorfilecreate: "error",
	}

	class UploaderClient {
		constructor(context = "common") {
			this.clientContext = context
			this.context = "background"
			this.emitter = new JNEventEmitter()
			this.tasks = []
			this.eventHandler = this.onTaskChanged.bind(this)
			BX.addCustomEvent('onFileUploadStatusChanged', this.eventHandler);
		}

		onTaskChanged(event, data, taskId) {
			if (taskId.startsWith(this.clientContext)) {
				taskId = taskId.replace(`${this.clientContext}-`, "");
				if (eventMap[event]) {
					let eventName = eventMap[event]
					this.emitter.emit(eventName, [taskId, data])
					if (eventName === "done" || eventName === "error") {
						this.tasks = this.tasks.filter( task => task.taskId !== taskId )
					}
				}
			}
		}

		on(event, func) {
			this.emitter.on(event, func)
			return this
		}

		addTask(task) {
			if (!task.taskId)
				throw Error("UploaderClient.addTask: property 'taskId' should be defined")
			task.taskId = this.clientContext + "-" +task.taskId
			this.tasks.push(task)
			BX.postComponentEvent("onFileUploadTaskReceived", [{files: [task]}], this.context)
		}

		cancelTask(taskId){
			taskId = this.clientContext + "-" + taskId;
			BX.postComponentEvent("onFileUploadTaskCancel", [{taskIds: [taskId]}], this.context)
		}

		destroy() {
			BX.removeCustomEvent("onFileUploadTaskReceived", this.eventHandler)
		}
	}

	module.exports = { UploaderClient }
});
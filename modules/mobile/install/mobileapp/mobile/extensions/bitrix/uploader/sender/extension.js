/**
 * @module uploader/sender
 */
jn.define('uploader/sender', (require, exports, module) => {
	/**
	 * @class BaseFileDataSender
	 * Event list:
	 * 	- progress
	 * 	- committed
	 *	- chunkUploaded
	 * 	- newToken
	 * 	- error
	 */

	class BaseFileDataSender
	{
		constructor({ content, size, name, start, end, token, type }) {
			this.token = token || '';
			this.isLastChunk = end === size;
			this.config = {
				binary: true,
				prepareData: false,
				headers: {
					'Content-Type': type,
					'X-Upload-Content-Name': encodeURI(name),
					'Content-Range': `bytes ${start}-${end - 1}/${size}`,
				},
				getParameters: {
					token,
				},
				onprogressupload: (data) => this.onProgress(data),
				data: content,
			};
			this.emmiter = new JNEventEmitter();
		}

		onProgress(event) {
			this.emmiter.emit('progress', [event]);
		}

		chunkUploaded(result) {
			const eventName = this.isLastChunk ? 'committed' : 'chunkUploaded';
			console.error(eventName);
			this.emmiter.emit(eventName, [result]);
		}

		on(event, func) {
			this.emmiter.on(event, func);

			return this;
		}

		send() {
			BX.ajax.runAction(this.methodName(), this.config)
				.then(
					(result) => {
						if (!result.status || result.status !== 'success')
						{
							this.emmiter.emit('error', [{ code: 0, message: 'wrong response', response: result }]);
						}
						else
						{
							if (result.data.token && (!this.token || this.token === ''))
							{
								this.token = result.data.token;
								this.emmiter.emit('newToken', [result.data.token]);
							}

							this.chunkUploaded(result);
						}
					},
				)
				.catch((data) => this.emmiter.emit('failed', [data]));
		}

		methodName() {
			throw new Error('methodName() must be override in subclass');
		}
	}

	/**
	 * @class ModernFileDataSender
	 */
	class ModernFileDataSender extends BaseFileDataSender
	{
		constructor(data)
		{
			super(data);
			this.controller = data.controller;
			this.controllerOptions = data.controllerOptions;
			this.config.getParameters.controller = data.controller;
			this.config.getParameters.controllerOptions = data.controllerOptions;
		}

		methodName() {
			return 'ui.fileuploader.upload';
		}
	}

	class DiskFileDataSender extends BaseFileDataSender
	{
		constructor(data)
		{
			super(data);
			this.folderId = data.folderId;
			this.filename = data.name;
			this.type = data.type;
			this.disableAutoCommit = data.disableAutoCommit;
			this.config.getParameters.filename = data.name;
			this.config.getParameters.folderId = data.folderId;
			// this.config.url = `/bitrix/services/main/ajax.php?action=${this.methodName()}&filename=${data.name}&token=${this.token}`
		}

		methodName() {
			return 'disk.api.content.upload';
		}

		chunkUploaded(result) {
			if (this.isLastChunk)
			{
				const body = '';
				const headers = { 'X-Upload-Content-Type': this.type };
				const commitConfig = {
					method: 'POST',
					dataType: 'json',
					prepareData: false,
					headers,
					data: body,
					uploadBinary: true,
					url: `/bitrix/services/main/ajax.php?action=disk.api.file.createByContent&filename=${
						this.filename
					}&folderId=${this.folderId
					}&contentId=${this.token
					}&generateUniqueName=Y`,
				};

				const rollbackConfig = {
					method: 'POST',
					dataType: 'json',
					prepareData: false,
					data: body,
					url: `/bitrix/services/main/ajax.php?action=disk.api.content.rollbackUpload&token=${this.token}`,
				};

				if (this.disableAutoCommit)
				{
					this.emmiter.emit('committed', [{
						commitConfig,
						rollbackConfig,
						token: this.token,
					}]);
				}
				else
				{
					BX.ajax(commitConfig).then((res) => {
						this.emmiter.emit('committed', [res]);
					}).catch((error) => {
						// this.status = Statuses.FAILED;
						// this.callListener(TaskEventConsts.FILE_CREATED_FAILED, {error: error});
						// resolve();
					});
				}
			}
			else
			{
				this.emmiter.emit('chunkUploaded', [result]);
			}
		}
	}

	module.exports = { ModernFileDataSender, DiskFileDataSender };
});

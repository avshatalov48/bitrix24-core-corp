/**
 * @module files/converter
 */
jn.define('files/converter', (require, exports, module) => {
	include('MediaConverter');

	class FileConverter
	{
		constructor()
		{
			this.promiseList = {};
			MediaConverter.setListener((event, data) => {
				if (this.promiseList[data.id])
				{
					this.promiseList[data.id](event, data);
					delete this.promiseList[data.id];
				}
			});
		}

		resize(taskId, params) {
			return new Promise((resolve, reject) => {
				this.promiseList[taskId] = (event, data) => {
					if (event === 'onSuccess')
					{
						if (!data.path.includes('file://'))
						{
							data.path = `file://${data.path}`;
						}
						resolve(data.path);
					}
					else
					{
						reject();
					}
				};

				MediaConverter.resize(taskId, params);
			});
		}

		cancel(id) {
			MediaConverter.cancel(id);
		}
	}

	module.exports = { FileConverter };
});

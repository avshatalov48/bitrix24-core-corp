/**
 * @module im/messenger/controller/dialog/lib/message-menu/src/saver/disk-saver
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/src/saver/disk-saver', (require, exports, module) => {
	const { FileDownloadType, Analytics } = require('im/messenger/const');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');
	const { BaseSaver } = require('im/messenger/controller/dialog/lib/message-menu/src/saver/base-saver');

	/**
	 * @class DiskSaver
	 */
	class DiskSaver extends BaseSaver
	{
		async save()
		{
			const filesIds = this.getFiles().map((file) => file.id);

			try
			{
				await this.dialogLocator.get('disk-service').save(filesIds);
				this.showSuccessSaveToast();
				this.sendAnalytics();
			}
			catch (error)
			{
				this.logger.error('DiskSaver.onDownloadToDisk.catch', error);
				this.downloadFailure(Analytics.DownloadErrorStatus.toDisk);
			}
		}

		getType()
		{
			return FileDownloadType.disk;
		}

		sendAnalytics()
		{
			AnalyticsService.getInstance().sendDownloadToDisk(this.getAnalyticsParams());
		}
	}

	module.exports = {
		/**
		 * @param {BaseSaver} props
		 * @returns {FileSaver}
		 */
		diskSaver: (props) => new DiskSaver(props),
	};
});

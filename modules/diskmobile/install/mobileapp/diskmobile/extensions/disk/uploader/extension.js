/**
 * @module disk/uploader
 */
jn.define('disk/uploader', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');
	const { Uuid } = require('utils/uuid');

	const { Defaults } = require('disk/uploader/src/config');
	const { DiskUploaderView } = require('disk/uploader/src/view');

	const isAirUploaderEnabled = Boolean(jnExtensionData?.get('disk:uploader')?.isAirUploaderEnabled);

	class DiskUploader
	{
		/**
		 * @public
		 * @param {DiskUploaderOptions} options
		 */
		static open(options)
		{
			if (isAirUploaderEnabled)
			{
				(new DiskUploader(options)).run();
			}
			else
			{
				// eslint-disable-next-line no-undef
				bitrix24Disk.show({
					listener: options.onCommit,
					folderId: String(options.folderId),
					storageId: String(options.storageId),
					multipleUpload: true,
				});
			}
		}

		/**
		 * @private
		 * @param {DiskUploaderOptions} options
		 */
		constructor(options)
		{
			/** @type {DiskUploaderOptions} */
			this.options = options;
		}

		run()
		{
			this.#pickFiles((files) => {
				const tasks = this.#prepareTasks(files);

				const bottomSheet = new BottomSheet({
					component: (layoutWidget) => new DiskUploaderView({
						tasks,
						layoutWidget,
						onCommit: this.options.onCommit,
					}),
				});

				bottomSheet
					.setParentWidget(this.#getParentWidget())
					.setBackgroundColor(Color.bgSecondary.toHex())
					.setNavigationBarColor(Color.bgContentPrimary.toHex())
					.enableForceDismissOnSwipeDown()
					.disableHorizontalSwipe()
					.enableSwipe()
					.enableResizeContent()
					.disableOnlyMediumPosition()
					.setTitleParams({
						text: Loc.getMessage('M_DISK_UPLOADER_TITLE'),
						type: 'dialog',
					});

				if (tasks.length > 6)
				{
					bottomSheet.showOnTop();
				}

				void bottomSheet.open();
			});
		}

		#getParentWidget()
		{
			return this.options.layoutWidget ?? PageManager;
		}

		#pickFiles(onFilesSelected)
		{
			const items = [
				{ id: 'mediateka' },
				{ id: 'camera' },
			];

			const settings = {
				resize: {
					targetWidth: -1,
					targetHeight: -1,
					sourceType: 1,
					encodingType: 0,
					mediaType: 2,
					allowsEdit: true,
					saveToPhotoAlbum: true,
					cameraDirection: 0,
				},
				maxAttachedFilesCount: Defaults.MAX_ATTACHED_FILES_COUNT,
				previewMaxWidth: Defaults.PREVIEW_MAX_WIDTH,
				previewMaxHeight: Defaults.PREVIEW_MAX_HEIGHT,
				attachButton: { items },
			};

			dialogs.showImagePicker({ settings }, onFilesSelected);
		}

		#prepareTasks(files = [])
		{
			return files.map((file) => {
				const task = {
					...file,
					taskId: Uuid.getV4(),
					folderId: this.options.folderId,
					chunk: Defaults.CHUNK_SIZE,
					params: {
						disableAutoCommit: true,
					},
				};

				const type = (file.type ? String(file.type) : '').toLowerCase();

				if (type === 'heic')
				{
					task.resize = {
						width: file.width ? (file.width * 2) : undefined,
						height: file.height ? (file.height * 2) : undefined,
						quality: 60,
					};
				}

				return task;
			});
		}
	}

	module.exports = { DiskUploader, isAirUploaderEnabled };
});

/**
 * @module disk/uploader/src/file-row
 */
jn.define('disk/uploader/src/file-row', (require, exports, module) => {
	const { Component, Indent, Color } = require('tokens');
	const { Text2 } = require('ui-system/typography/text');
	const { IconView } = require('ui-system/blocks/icon');
	const { Icon, resolveFileIcon } = require('assets/icons');
	const { SpinnerLoader, SpinnerDesign } = require('layout/ui/loaders/spinner');
	const { FilePreview } = require('ui-system/blocks/file/preview');
	const { getExtension, getNameWithoutExtension } = require('utils/file');
	const { DiskUploaderProgressBar } = require('disk/uploader/src/progress-bar');
	const { DiskFileRowPreviewOverlay } = require('disk/uploader/src/preview-overlay');
	const { UploadStatus } = require('disk/uploader/src/config');
	const { resolveFileTypeByExt, FileType } = require('disk/enum');

	const CANCEL_TASK_REGION_WIDTH = 68;

	class DiskUploaderFileRow extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {DiskUploaderProgressBar|null} */
			this.progressBarRef = null;

			this.state = {
				status: this.props.status,
				byteSent: this.props.byteSent,
				byteTotal: this.props.byteTotal,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				status: props.status,
				byteSent: props.byteSent,
				byteTotal: props.byteTotal,
			};
		}

		/**
		 * @public
		 * @param {number} byteSent
		 * @param {number} byteTotal
		 */
		setProgress({ byteSent, byteTotal })
		{
			const progress = {
				byteSent,
				byteTotal,
				status: UploadStatus.PROGRESS,
			};

			if (this.progressBarRef)
			{
				this.state = progress;
				this.progressBarRef.setProgress(progress);
			}
			else
			{
				this.setState(progress);
			}
		}

		#getTestId(suffix)
		{
			return `disk-uploader-file-row-${suffix}`;
		}

		#isStatus(status)
		{
			const currentStatus = this.state.status ?? UploadStatus.PROGRESS;

			return currentStatus === status;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						paddingTop: Indent.XL.toNumber(),
						paddingLeft: Component.paddingLr.toNumber(),
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				this.#renderPreview(),
				this.#renderNameWithProgress(),
				this.#renderRemoveTaskButton(),
			);
		}

		#renderPreview()
		{
			const ext = getExtension(this.props.name);
			const type = resolveFileTypeByExt(ext);
			const hasPreview = this.props.previewUrl
				&& (type === FileType.IMAGE || type === FileType.VIDEO);

			return View(
				{
					style: {
						maxWidth: 40,
						minWidth: 40,
						flexDirection: 'row',
						justifyContent: 'center',
					},
				},
				hasPreview
					? FilePreview({
						type,
						previewUrl: this.props.previewUrl,
						testId: this.#getTestId('file-preview'),
					})
					: IconView({
						icon: resolveFileIcon(ext, type),
						color: null,
						testId: this.#getTestId('file-icon'),
						size: 40,
					}),
				this.#renderLoader(),
				this.#renderRetryButton(),
			);
		}

		#renderNameWithProgress()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flexGrow: 1,
						flexShrink: 1,
						paddingBottom: Indent.XL.toNumber(),
						marginLeft: Indent.XL2.toNumber(),
						paddingRight: CANCEL_TASK_REGION_WIDTH,
						borderBottomWidth: 0.5,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
						},
					},
					View(
						{},
						Text2({
							size: 4,
							color: Color.base0,
							testId: this.#getTestId('name'),
							numberOfLines: 1,
							text: getNameWithoutExtension(this.props.name),
							ellipsize: 'middle',
						}),
					),
					new DiskUploaderProgressBar({
						ref: (ref) => {
							this.progressBarRef = ref;
						},
						testId: this.#getTestId('progress'),
						status: this.state.status,
						byteSent: this.state.byteSent,
						byteTotal: this.state.byteTotal,
					}),
				),
			);
		}

		#renderRemoveTaskButton()
		{
			if (this.#isStatus(UploadStatus.PROGRESS))
			{
				return null;
			}

			return View(
				{
					testId: this.#getTestId('remove-task-btn'),
					onClick: () => this.props.onRemoveTaskClick?.(this.props.taskId),
					style: {
						position: 'absolute',
						top: Indent.XL.toNumber(),
						right: 0,
						width: CANCEL_TASK_REGION_WIDTH,
						height: 38,
						flex: 1,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				IconView({
					testId: this.#getTestId('remove-task-btn-icon'),
					icon: Icon.TRASHCAN,
					color: Color.base3,
					size: 24,
				}),
			);
		}

		#renderLoader()
		{
			if (!this.#isStatus(UploadStatus.PROGRESS))
			{
				return null;
			}

			return DiskFileRowPreviewOverlay(
				{
					testId: this.#getTestId('cancel-task-btn'),
					onClick: () => this.props.onCancelTaskClick?.(this.props.taskId),
					backgroundColor: Color.base8.toHex(),
				},
				IconView({
					testId: this.#getTestId('cancel-task-btn-icon'),
					icon: Icon.CROSS,
					color: Color.accentMainPrimary,
					size: 22,
				}),
				SpinnerLoader({
					size: 24,
					design: SpinnerDesign.BLUE,
					style: {
						position: 'absolute',
					},
				}),
			);
		}

		#renderRetryButton()
		{
			const canRetry = this.#isStatus(UploadStatus.ERROR) || this.#isStatus(UploadStatus.CANCELED);

			if (!canRetry)
			{
				return null;
			}

			return DiskFileRowPreviewOverlay(
				{
					testId: this.#getTestId('retry-task-btn'),
					onClick: () => this.props.onRetryTaskClick?.(this.props.taskId),
					backgroundColor: Color.accentSoftRed3.toHex(),
				},
				IconView({
					testId: this.#getTestId('retry-task-btn-icon'),
					icon: Icon.REFRESH,
					color: Color.accentMainAlert,
					size: 22,
				}),
			);
		}
	}

	module.exports = { DiskUploaderFileRow };
});

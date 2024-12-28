/**
 * @module disk/uploader/src/progress-bar
 */
jn.define('disk/uploader/src/progress-bar', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');
	const { formatFileSize } = require('utils/file');
	const { UploadStatus } = require('disk/uploader/src/config');

	class DiskUploaderProgressBar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				byteSent: props.byteSent,
				byteTotal: props.byteTotal,
				status: props.status,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				byteSent: props.byteSent,
				byteTotal: props.byteTotal,
				status: props.status,
			};
		}

		/**
		 * @public
		 * @param {number} byteSent
		 * @param {number} byteTotal
		 * @param {string} status
		 */
		setProgress({ byteSent, byteTotal, status })
		{
			this.setState({ byteSent, byteTotal, status });
		}

		render()
		{
			return View(
				{},
				Text5({
					size: 4,
					color: this.#makeColor(),
					testId: this.props.testId,
					numberOfLines: 1,
					text: this.#makeText(),
				}),
			);
		}

		#makeColor()
		{
			if (this.state.status === UploadStatus.ERROR || this.state.status === UploadStatus.CANCELED)
			{
				return Color.accentMainAlert;
			}

			return Color.base3;
		}

		#makeText()
		{
			const { status, byteSent, byteTotal } = this.state;

			if (status === UploadStatus.ERROR)
			{
				return Loc.getMessage('M_DISK_UPLOADER_ERROR');
			}

			if (status === UploadStatus.CANCELED)
			{
				return Loc.getMessage('M_DISK_UPLOADER_CANCELED_BY_USER');
			}

			if (!byteTotal)
			{
				return '-- / --';
			}

			if (byteSent && byteSent === byteTotal)
			{
				return Loc.getMessage('M_DISK_UPLOADER_DONE');
			}

			return `${formatFileSize(byteSent)} / ${formatFileSize(byteTotal)}`;
		}
	}

	module.exports = { DiskUploaderProgressBar };
});

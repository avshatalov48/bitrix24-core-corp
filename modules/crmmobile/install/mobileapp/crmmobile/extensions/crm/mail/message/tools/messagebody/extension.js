/**
 * @module crm/mail/message/tools/messagebody
 */
jn.define('crm/mail/message/tools/messagebody', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { FileField } = require('layout/ui/fields/file');
	const {
		clone,
	} = require('utils/object');

	function FileGallery(props)
	{
		if (props.format === 'minimized' || !Array.isArray(props.files) || props.files.length === 0)
		{
			return null;
		}

		const galleryInfo = {};
		const galleryValue = props.files.map((file) => {
			if (file.id && Number.isInteger(parseInt(file.id)))
			{
				galleryInfo[file.id] = clone(file);
				return file.id;
			}
			return clone(file);
		});

		return View(
			{
				style: {
					marginLeft: 30,
					marginRight: 30,
					marginBottom: 10,
				},
			},
			FileField({
				testId: 'mail-message-quote-file-field',
				showTitle: false,
				multiple: true,
				value: galleryValue,
				config: {
					fileInfo: galleryInfo,
					mediaType: 'file',
				},
				readOnly: true,
			}),
		);
	}

	function Body(props)
	{
		if (props.format === 'minimized' || props.content === null || props.content === undefined)
		{
			return null;
		}

		return View(
			{
				style: {
					marginLeft: 12,
					marginRight: 12,
				},
			},
			WebView({
				style: {
					fontSize: 25,
				},
				scrollDisabled: true,
				data: {
					content: props.content,
					mimeType: 'text/html',
					charset: 'UTF-8',
				},
			}),
		);
	}

	function Border(props)
	{
		if (props.format === 'minimized')
		{
			return null;
		}

		return View({
			style: {
				height: 1,
				borderTopWidth: 1,
				borderTopColor: '#DBDDE0',
				marginLeft: 12,
				marginRight: 12,
			},
		});
	}

	class MessageBody extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.isHiddenField = this.props.isHiddenField;

			this.state = {
				isHide: true,
			};
		}

		renderMoreButton()
		{
			if (!this.isHiddenField || this.props.content === null || this.props.content === undefined)
			{
				return null;
			}

			return View(
				{
					testId: 'mail-message-quote-hide-show-btn',
					style: {
						alignSelf: 'center',
						padding: 2,
						marginTop: 10,
					},
					onClick: () => {
						if (this.state.isHide)
						{
							this.setState({
								isHide: false,
							});
						}
						else
						{
							this.setState({
								isHide: true,
							});
						}
					},
				},
				Image({
					style: {
						width: 41,
						height: 24,
					},
					svg: {
						content: `<svg width="41" height="24" viewBox="0 0 41 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<rect x="0.5" y="3.5" width="40" height="17" rx="8.5" fill="white" stroke="#D5D7DB"/>
								<path d="M15 14C16.1046 14 17 13.1046 17 12C17 10.8954 16.1046 10 15 10C13.8954 10 13 10.8954 13 12C13 13.1046 13.8954 14 15 14Z" fill="#A8ADB4"/>
								<path d="M21 14C22.1046 14 23 13.1046 23 12C23 10.8954 22.1046 10 21 10C19.8954 10 19 10.8954 19 12C19 13.1046 19.8954 14 21 14Z" fill="#A8ADB4"/>
								<path d="M29 12C29 13.1046 28.1046 14 27 14C25.8954 14 25 13.1046 25 12C25 10.8954 25.8954 10 27 10C28.1046 10 29 10.8954 29 12Z" fill="#A8ADB4"/>
							</svg>`,
					},
				}),
			);
		}

		renderBody()
		{
			if (!this.isHiddenField || !this.state.isHide)
			{
				return Body(this.props);
			}
		}

		renderFileGallery()
		{
			if (!this.isHiddenField || !this.state.isHide)
			{
				return FileGallery({
					format: this.props.format,
					files: this.props.files,
				});
			}

			return null;
		}

		render()
		{
			let subject = null;

			if (this.props.subject)
			{
				subject = View(
					{
						style: {
							paddingLeft: 20,
							paddingRight: 20,
							paddingTop: 15.5,
						},
					},
					Text({
						style: {
							fontSize: 16,
							fontWeight: '700',
							color: '#000000',
						},
						text: this.props.subject,
					}),
				);
			}

			let borderBeforeFiles = null;
			let borderBeforeMessage = null;

			if (this.props.content !== null && this.props.content !== undefined)
			{
				if (this.props.borderBeforeFiles)
				{
					borderBeforeFiles = Border({
						format: this.props.format,
					});
				}
				else
				{
					borderBeforeMessage = Border({
						format: this.props.format,
					});
				}
			}

			return View(
				{},
				borderBeforeFiles,
				this.renderMoreButton(),
				this.renderFileGallery(),
				borderBeforeMessage,
				subject,
				this.renderBody(),
			);
		}
	}

	module.exports = { MessageBody };
});

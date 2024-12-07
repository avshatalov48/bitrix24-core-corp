/**
 * @module text-editor/file-selector
 */
jn.define('text-editor/file-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { EventEmitter } = require('event-emitter');
	const { Attachment } = require('text-editor/entities/attachment');

	class FileSelector extends EventEmitter
	{
		/**
		 * @param props {{
		 * 		events: {
		 * 			[eventName]: () => void,
		 * 		},
		 *		imagePickerSettings?: {
		 *		    editingMediaFiles?: boolean,
		 *		    maxAttachedFilesCount?: number,
		 *		    previewMaxWidth?: number,
		 *		    previewMaxHeight?: number,
		 *		    callback?: (files) => void,
		 *		    closeCallback?: () => void,
		 *		    resize?: {
		 *				targetWidth?: number,
		 *				targetHeight?: number,
		 *				sourceType?: number,
		 *				encodingType?: number,
		 *				mediaType?: number,
		 *				allowsEdit?: boolean,
		 *				saveToPhotoAlbum?: boolean,
		 *				cameraDirection?: number,
		 *		    },
		 *		    attachButton?: {
		 *				items: Array<{
		 * 					id: string,
		 * 					name: string,
		 * 					dataSource?: {
		 * 						multiple?: boolean,
		 * 						url: string,
		 * 					},
		 * 		  		}>,
		 *		    },
		 *		},
		 * }}
		 */
		constructor(props = {})
		{
			super();
			/**
			 * @private
			 */
			this.props = { ...props };

			if (Type.isPlainObject(props.events))
			{
				Object.entries(props.events).forEach(([eventName, handler]) => {
					this.on(eventName, handler);
				});
			}
		}

		/**
		 * Shows file selector
		 * @returns {
		 * 		Promise<
		 * 			Array<Attachment>
		 * 		>
		 * }
		 */
		show()
		{
			return new Promise((resolve) => {
				dialogs.showImagePicker(
					{
						settings: {
							editingMediaFiles: false,
							maxAttachedFilesCount: 100,
							attachButton: {
								items: [
									{
										id: 'mediateka',
										name: Loc.getMessage('MOBILEAPP_TEXT_EDITOR_FILE_SELECTOR_MEDIATEKA_CAPTION'),
									},
									{
										id: 'camera',
										name: Loc.getMessage('MOBILEAPP_TEXT_EDITOR_FILE_SELECTOR_CAMERA_CAPTION'),
									},
									{
										id: 'disk',
										name: Loc.getMessage('MOBILEAPP_TEXT_EDITOR_FILE_SELECTOR_DISK_CAPTION'),
										dataSource: {
											multiple: true,
											url: this.getDiskUrl(),
										},
									},
								],
							},
						},
					},
					(files) => {
						resolve(
							files.map((file) => {
								return new Attachment({
									type: file.dataAttributes ? 'disk' : 'other',
									data: file,
								});
							}),
						);
					},
				);
			});
		}

		/**
		 * Gets disk url
		 * @private
		 * @returns {string}
		 */
		getDiskUrl()
		{
			return `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${env.userId}`;
		}
	}

	module.exports = {
		FileSelector,
	};
});

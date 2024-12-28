/**
 * @module im/messenger/controller/sidebar/chat/tabs/files/item
 */
jn.define('im/messenger/controller/sidebar/chat/tabs/files/item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Feature: MobileFeature } = require('feature');
	const { resolveFileIcon } = require('assets/icons');
	const { Moment } = require('utils/date');
	const { getExtension } = require('utils/file');
	const { dayMonth, shortTime } = require('utils/date/formats');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { EasyIcon } = require('layout/ui/file/icon');

	const { Theme } = require('im/lib/theme');
	const { ChatAvatar } = require('im/messenger/lib/element');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Avatar: MessengerAvatarLegacy } = require('im/messenger/lib/ui/base/avatar');
	const {
		formatFileSize,
		getFileIconTypeByExtension,
	} = require('im/messenger/lib/helper');
	const { FileContextMenu } = require('im/messenger/controller/sidebar/chat/tabs/files/context-menu');

	/**
	 * @class SidebarFilesItem
	 */
	class SidebarFilesItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.core = serviceLocator.get('core');
			this.store = this.core.getStore();
			this.file = this.store.getters['filesModel/getById'](props.fileId);
			this.author = this.store.getters['usersModel/getById'](props.authorId);
		}

		componentWillReceiveProps(props)
		{
			this.file = this.store.getters['filesModel/getById'](props.fileId);
			this.author = this.store.getters['usersModel/getById'](props.authorId);
		}

		render()
		{
			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.itemRef = ref;
						}
					},
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
						height: 56,
					},
					onClick: () => {
						this.openFile();
					},
					onLongClick: () => {
						const { messageId, dialogId, fileId } = this.props;

						this.onOpenFileMenu(fileId, dialogId, messageId, this.itemRef);
					},
				},
				this.renderIcon(),
				this.renderDescription(),
				this.renderEllipsisButton(),
			);
		}

		renderIcon()
		{
			return View(
				{
					style: {
						display: 'flex',
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'center',
						borderColor: Theme.colors.base7,
						borderWidth: 1,
						borderRadius: 8,
						width: 56,
						height: '100%',
					},
				},
				this.createIcon(),
			);
		}

		createIcon()
		{
			const { name } = this.file;
			const extension = getExtension(name);

			if (!MobileFeature.isAirStyleSupported())
			{
				return EasyIcon(extension, 24);
			}

			const fileIconType = getFileIconTypeByExtension(extension);
			const fileIcon = resolveFileIcon(extension, fileIconType);

			return IconView({
				size: 28,
				color: null,
				icon: fileIcon,
			});
		}

		renderDescription()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						alignItems: 'flex-start',
						justifyContent: 'center',
						height: '100%',
						marginHorizontal: 12,
					},
				},
				this.renderTitle(),
				this.renderSubTitle(),
			);
		}

		renderTitle()
		{
			const { name, size } = this.file;

			let fileExtension = '';
			let fileName = '';

			name.split('.').forEach((item, index, array) => {
				if (array.length - 1 === index)
				{
					fileExtension = `.${item}`;
				}
				else
				{
					fileName = fileName ? `${fileName}.${item}` : item;
				}
			});

			const formattedSize = formatFileSize(size);

			return View(
				{
					style: {
						flexDirection: 'row',
						flex: 1,
						maxWidth: '100%',
						width: '100%',
					},
				},
				Text({
					style: {
						color: Theme.colors.accentMainLinks,
						fontSize: 15,
						fontWeight: 500,
						flexShrink: 1,
					},
					text: fileName,
					ellipsize: 'end',
					numberOfLines: 1,
				}),
				Text({
					style: {
						color: Theme.colors.accentMainLinks,
						fontSize: 15,
						fontWeight: 500,
					},
					text: fileExtension,
					numberOfLines: 1,
				}),
				Text({
					style: {
						color: Theme.colors.base5,
						fontSize: 15,
						fontWeight: 500,
						marginLeft: 8,
					},
					text: formattedSize,
					numberOfLines: 1,
				}),
			);
		}

		renderSubTitle()
		{
			const { dateCreate } = this.props;
			const { id, avatar, name, color } = this.author;

			const date = new Moment(dateCreate).format(dayMonth);
			const time = new Moment(dateCreate).format(shortTime);

			const fullDate = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_FILES_DATE', {
				'#DATE#': date,
				'#TIME#': time,
			});

			let avatarView = null;
			if (MobileFeature.isNativeAvatarSupported())
			{
				avatarView = Avatar(ChatAvatar.createFromDialogId(id).getSidebarTabItemDescriptionAvatarProps());
			}
			else
			{
				avatarView = new MessengerAvatarLegacy({
					text: name,
					uri: avatar,
					color,
					size: 'S',
				});
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-start',
						flex: 1,
						height: 18,
						maxHeight: 18,
						marginTop: 4,
					},
				},
				avatarView,
				Text({
					style: {
						color: Theme.colors.base4,
						fontSize: 12,
						fontWeight: 400,
						height: 18,
						maxHeight: 18,
						marginLeft: 4,
					},
					text: fullDate,
					ellipsize: 'end',
					numberOfLines: 1,
				}),
			);
		}

		renderEllipsisButton()
		{
			const { messageId, dialogId, fileId } = this.props;

			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.viewRef = ref;
						}
					},
					style: {
						alignSelf: 'center',
					},
				},
				ImageButton({
					style: {
						width: 24,
						height: 24,
					},
					onClick: () => {
						this.onOpenFileMenu(fileId, dialogId, messageId, this.viewRef);
					},
					iconName: Icon.MORE.getIconName(),
					testId: 'ITEM_ELLIPSIS_BUTTON',
				}),
			);
		}

		/**
		 * @desc open file menu
		 * @param {number} id
		 * @param {string} dialogId
		 * @param {number} messageId
		 * @param {LayoutComponent} ref
		 * @private
		 */
		onOpenFileMenu(id, dialogId, messageId, ref)
		{
			const config = {
				fileId: id,
				dialogId,
				messageId,
				ref,
			};

			FileContextMenu
				.createByFileId(config)
				.open()
			;
		}

		openFile()
		{
			viewer.openDocument(this.file.urlShow, this.file.name);
		}
	}

	module.exports = { SidebarFilesItem };
});

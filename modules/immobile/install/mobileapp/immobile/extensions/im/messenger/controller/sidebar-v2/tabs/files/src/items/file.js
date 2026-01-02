/**
 * @module im/messenger/controller/sidebar-v2/tabs/files/src/items/file
 */

jn.define('im/messenger/controller/sidebar-v2/tabs/files/src/items/file', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text2, Text6, Text5 } = require('ui-system/typography/text');
	const { resolveFileIcon } = require('assets/icons');
	const { IconView } = require('ui-system/blocks/icon');
	const { Moment } = require('utils/date');
	const { shortTime, dayMonth } = require('utils/date/formats');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getFileIconTypeByExtension } = require('im/messenger/lib/helper');
	const { ChatAvatar } = require('im/messenger/lib/ui/avatar');
	const { ListItem } = require('im/messenger/controller/sidebar-v2/ui/layout/list-item');
	const { FileActionMenu } = require('im/messenger/controller/sidebar-v2/tabs/files/src/action-menu/file');

	class FileItem extends LayoutComponent
	{
		/**
		 * @param {object} props
		 * @param {number} props.dialogId
		 * @param {number} props.authorId
		 * @param {number} props.messageId
		 * @param {number} props.fileId
		 * @param {function} props.onRef
		 * @param {WidgetNavigator} props.widgetNavigator
		 */
		constructor(props)
		{
			super(props);

			this.ref = null;

			/**
			 * @protected
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();

			this.dialogId = props.dialogId;
			this.messageId = props.messageId;
			this.fileId = props.fileId;
			this.fileModel = this.store.getters['filesModel/getById'](this.fileId);
		}

		getId()
		{
			return this.fileModel.id;
		}

		/**
		 * @protected
		 * @param {string} suffix
		 * @returns {string}
		 */
		getTestId(suffix)
		{
			const prefix = 'sidebar-tab-file-item';

			return suffix ? `${prefix}-${suffix}` : prefix;
		}

		render()
		{
			return new ListItem({
				ref: this.onRef,
				testId: this.getTestId(),
				title: this.createTitle,
				subtitle: this.createSubtitle,
				leftIcon: this.createIcon,
				onClick: this.onClick,
				onLongClick: this.onLongClick,
				onShowMenu: this.showActionMenu,
			});
		}

		createIcon = () => {
			const extension = this.fileModel.extension;

			return IconView({
				icon: resolveFileIcon(extension, getFileIconTypeByExtension(extension)),
				color: null,
				testId: this.getTestId('icon'),
				size: 40,
			});
		};

		createTitle = () => {
			return Text2({
				text: this.fileModel.name,
				color: Color.base0,
				numberOfLines: 1,
				ellipsize: 'middle',
				testId: this.getTestId('title'),
			});
		};

		createSubtitle = () => {
			return View(
				{},
				this.createDate(),
				this.createOwnerView(),
			);
		};

		createDate = () => {
			const moment = new Moment(this.fileModel.date);

			const formattedDateTime = Loc.getMessage('IMMOBILE_SIDEBAR_V2_TAB_ITEM_DATE_AT_TIME', {
				'#DATE#': moment.format(dayMonth()),
				'#TIME#': moment.format(shortTime()),
			});

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						alignContent: 'center',
						justifyContent: 'flex-start',
					},
				},
				Text5({
					text: formattedDateTime,
					color: Color.base3,
				}),
			);
		};

		createOwnerView = () => {
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						alignContent: 'center',
						justifyContent: 'flex-start',
						marginTop: Indent.XS2.toNumber(),
					},
				},
				ChatAvatar({
					testId: this.getTestId('user-avatar'),
					dialogId: this.fileModel.authorId,
					size: 18,
					isNotes: false,
				}),
				Text6({
					testId: this.getTestId('user-name'),
					text: this.fileModel.authorName,
					color: Color.base4,
					ellipsize: 'end',
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
				}),
			);
		};

		onRef = (ref) => {
			this.ref = ref;
			if (this.props?.onRef)
			{
				this.props.onRef(ref);
			}
		};

		onClick = () => {
			viewer.openDocument(this.fileModel.urlShow, this.fileModel.name);
		};

		onLongClick = () => {
			if (this.ref)
			{
				this.showActionMenu({ ref: this.ref });
			}
		};

		showActionMenu = ({ ref: target }) => {
			(new FileActionMenu({
				fileId: this.fileModel.id,
				dialogId: this.dialogId,
				messageId: this.messageId,
				widgetNavigator: this.props.widgetNavigator,
			})).show(target);
		};
	}

	module.exports = {
		FileItem,
	};
});

/**
 * @module disk/simple-list/items/file-redux/file-content
 */
jn.define('disk/simple-list/items/file-redux/file-content', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');

	const { connect } = require('statemanager/redux/connect');
	const { selectById } = require('disk/statemanager/redux/slices/files/selector');

	const { Component, Indent, Color } = require('tokens');
	const { transition, pause, chain } = require('animation');
	const { getExtension, getNameWithoutExtension } = require('utils/file');
	const { DynamicDateFormatter } = require('utils/date/dynamic-date-formatter');
	const { date, dayShortMonth, shortTime } = require('utils/date/formats');
	const { Moment } = require('utils/date/moment');
	const { withPressed } = require('utils/color');
	const { Haptics } = require('haptics');

	const { resolveFolderIcon, resolveFileIcon } = require('assets/icons');

	const { Text5, Text6 } = require('ui-system/typography/text');
	const { BBCodeText } = require('ui-system/typography/bbcodetext');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { FilePreview } = require('ui-system/blocks/file/preview');
	const { Avatar, AvatarShape } = require('ui-system/blocks/avatar');

	const { ActionMenu } = require('disk/simple-list/items/file-redux/action-menu');

	const { selectById: selectUserById } = require('statemanager/redux/slices/users/selector');
	const { selectById: selectStorageById } = require('disk/statemanager/redux/slices/storages');
	const { selectShowFileExtension } = require('disk/statemanager/redux/slices/settings');
	const store = require('statemanager/redux/store');

	const IMAGE_SIZE = 40;

	/**
	 * @typedef {Object} ContentProps
	 * @property {boolean} isFolder - Indicates if the item is a folder.
	 * @property {string} name - The name of the file or folder.
	 * @property {boolean} showBorder - Whether to show a border.
	 * @property {string} testId - The test ID for the component.
	 * @property {string} [groupName] - The name of the group.
	 * @property {number} [updatedBy] - The ID of the user who created the item.
	 * @property {number} [updateTime] - The last update time of the item.
	 * @property {object} parentWidget
	 */

	/**
	 * @class Content
	 * @param {ContentProps} props
	 */

	class Content extends PureComponent
	{
		containerRef = null;
		isBlinking = false;

		get diskObject()
		{
			return this.props.diskObject;
		}

		getTestId(suffix)
		{
			const testId = this.props.testId ?? 'file-content';

			return suffix ? `${testId}-${suffix}` : testId;
		}

		get parentWidget()
		{
			return this.props.parentWidget ?? PageManager;
		}

		get isFolder()
		{
			return this.diskObject.isFolder;
		}

		get isFile()
		{
			return !this.isFolder;
		}

		get hasPreview()
		{
			return this.diskObject.hasPreview;
		}

		get previewUrl()
		{
			return this.diskObject?.links?.preview;
		}

		get extension()
		{
			return getExtension(this.diskObject.name);
		}

		get backgroundColor()
		{
			return Color.bgContentPrimary.toHex();
		}

		get objectName()
		{
			let { name } = this.diskObject;

			name = this.isFile ? getNameWithoutExtension(name) : name;

			if (this.isFile && this.showFileExtension && this.extension)
			{
				name += `[color=${Color.base5.toHex()}].${this.extension}[/color]`;
			}

			return name;
		}

		get objectNameForTestId()
		{
			let { name } = this.diskObject;

			if (this.isFile && !this.showFileExtension)
			{
				name = getNameWithoutExtension(name);
			}

			return name;
		}

		get showStorageName()
		{
			return this.props.showStorageName;
		}

		get storageName()
		{
			if (!this.showStorageName)
			{
				return null;
			}
			const storage = selectStorageById(store.getState(), this.diskObject.storageId);

			if (storage.type === 'user')
			{
				return null;
			}

			return storage?.name;
		}

		get showFileExtension()
		{
			return this.props.showFileExtension;
		}

		get needShowBadge()
		{
			return false;
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			if (this.diskObject.id !== nextProps.id)
			{
				return true;
			}

			return super.shouldComponentUpdate(nextProps, nextState);
		}

		componentDidUpdate(prevProps, prevState)
		{
			super.componentDidUpdate(prevProps, prevState);

			const propsToBlink = ['name', 'updateTime'];

			if (prevProps.diskObject.id !== this.diskObject.id)
			{
				return;
			}

			for (const prop of propsToBlink)
			{
				if (prevProps.diskObject[prop] !== this.diskObject[prop])
				{
					void this.blink();

					break;
				}
			}
		}

		render()
		{
			const { showBorder } = this.props;

			return View(
				{
					style: {
						position: 'relative',
						flexDirection: 'row',
						alignItems: 'center',
						alignContent: 'center',
						flexWrap: 'wrap',
						paddingVertical: Indent.XL.toNumber(),
						paddingHorizontal: Component.paddingLr.toNumber(),
						backgroundColor: withPressed(this.backgroundColor),
						minHeight: 76,
					},
					ref: (ref) => {
						this.containerRef = ref;
					},
					onLongClick: this.openActionMenu,
				},
				this.renderContent(),
				showBorder && this.renderDivider(),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flex: 1,
						alignItems: 'flex-start',
						alignContent: 'flex-start',
						position: 'relative',
					},
				},
				this.renderImage(),
				this.renderInfo(),
			);
		}

		renderDivider()
		{
			const left = Component.paddingLr.toNumber() + IMAGE_SIZE + Indent.XL2.toNumber();

			return View(
				{
					style: {
						position: 'absolute',
						left,
						bottom: 0,
						width: '100%',
						height: 0.5,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
						borderBottomWidth: 0.5,
					},
				},
			);
		}

		renderImage()
		{
			const Container = (imageComponent) => {
				return View(
					{
						style: {
							maxWidth: IMAGE_SIZE,
							minWidth: IMAGE_SIZE,
							display: 'flex',
							flexDirection: 'row',
							justifyContent: 'center',
						},
					},
					imageComponent,
				);
			};

			if (this.isFolder)
			{
				const { folderContextType } = this.diskObject;

				return Container(
					IconView({
						icon: resolveFolderIcon(folderContextType),
						size: 40,
						testId: this.getTestId('folder-icon'),
						color: null,
					}),
				);
			}

			if (this.hasPreview)
			{
				return Container(
					FilePreview({
						type: this.diskObject.typeFile,
						previewUrl: this.previewUrl,
						testId: this.getTestId('file-preview'),
					}),
				);
			}

			return Container(
				IconView({
					icon: resolveFileIcon(this.extension, this.diskObject.typeFile),
					color: null,
					testId: this.getTestId('file-icon'),
					size: 40,
				}),
			);
		}

		renderInfo()
		{
			return View(
				{
					style: {
						position: 'relative',
						flex: 1,
						flexDirection: 'row',
						paddingLeft: Indent.XL2.toNumber(),
						alignItems: 'center',
						alignContent: 'center',
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
							flexWrap: 'wrap',
							alignItems: 'center',
							alignContent: 'center',
						},
					},
					View(
						{
							style: {
								width: '100%',
								flexDirection: 'row',
							},
						},
						View(
							{
								style: {
									flex: 1,
									paddingRight: Indent.XL2.toNumber(),
									marginBottom: Indent.XS2.toNumber(),
								},
							},
							this.renderName(),
						),
						View(
							{
								style: {
									justifyContent: 'center',
								},
							},
							this.renderUpdateTime(),
						),
					),
					this.renderStorageName(),
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
								alignContent: 'center',
								width: '100%',
							},
						},
						this.renderUser(),
						View(
							{
								style: {
									marginTop: Indent.M.toNumber(),
								},
							},
							this.renderChatButton(),
						),
					),
				),
			);
		}

		renderName()
		{
			return BBCodeText({
				style: {
					width: '100%',
				},
				size: 2,
				color: Color.base0,
				testId: this.getTestId(`name-${this.objectNameForTestId}`),
				numberOfLines: 1,
				value: this.objectName,
				ellipsize: 'middle',
			});
		}

		renderStorageName()
		{
			if (!this.storageName)
			{
				return null;
			}

			return Text5({
				style: {
					width: '100%',
				},
				text: this.storageName,
				color: Color.base3,
				testId: this.getTestId('storage-name'),
			});
		}

		renderUser()
		{
			const { updatedBy } = this.diskObject;

			if (!updatedBy || !Number(updatedBy))
			{
				return null;
			}

			const { id, fullName } = selectUserById(store.getState(), updatedBy) || {};

			if (!id)
			{
				console.error(`The owner of file with id=${this.diskObject.id} was not found`);

				return null;
			}

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center',
						alignContent: 'center',
						height: 28,
						paddingTop: Indent.XS2.toNumber(),
					},
				},
				Avatar({
					id: updatedBy,
					size: 18,
					testId: this.getTestId('avatar'),
					shape: AvatarShape.CIRCLE,
					withRedux: true,
				}),
				Text6({
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text: fullName,
					color: Color.base4,
					testId: this.getTestId('user-name'),
				}),
			);
		}

		renderBadge()
		{
			return BadgeCounter({
				testId: this.getTestId('badge'),
				value: 1,
				design: BadgeCounterDesign.ALERT,
			});
		}

		renderChatButton()
		{
			if (true || this.isFolder)
			{
				return null;
			}

			return IconView({
				size: 24,
				icon: Icon.GO_TO_MESSAGE,
				color: Color.base4,
				onClick: this.openChat,
				testId: this.getTestId('chat-button'),
			});
		}

		renderUpdateTime()
		{
			const { updateTime } = this.diskObject;

			if (!updateTime)
			{
				return null;
			}

			const formatter = new DynamicDateFormatter({
				config: {
					[DynamicDateFormatter.periods.DAY]: shortTime(),
					[DynamicDateFormatter.deltas.WEEK]: 'E',
					[DynamicDateFormatter.periods.YEAR]: dayShortMonth(),
				},
				defaultFormat: date(),
			});

			const formattedTime = formatter.format(new Moment(updateTime * 1000));

			return Text5({
				testId: this.getTestId('last-activity-date'),
				text: formattedTime,
				color: Color.base3,
			});
		}

		async blink()
		{
			if (this.isBlinking)
			{
				return;
			}

			this.isBlinking = true;

			await chain(
				transition(this.containerRef, {
					duration: 500,
					backgroundColor: Color.accentSoftOrange3.toHex(),
				}),
				pause(500),
				transition(this.containerRef, {
					duration: 500,
					backgroundColor: this.backgroundColor,
				}),
			)();

			this.isBlinking = false;
		}

		openActionMenu = () => {
			Haptics.impactLight();
			void new ActionMenu(
				this.diskObject.id,
				this.props.order,
				this.props.context,
				this.parentWidget,
			).show(this.containerRef);
		};

		openChat = () => {
			console.log('open chat');
		};
	}

	const mapStateToProps = (state, ownProps) => {
		const diskObjectId = ownProps.id;
		const diskObject = selectById(state, diskObjectId);
		const showFileExtension = selectShowFileExtension(state);

		if (!diskObject)
		{
			return {};
		}

		const {
			id,
			name,
			createTime,
			updateTime,
			isFolder,
			typeFile,
			updatedBy,
			storageId,
			links,
			folderContextType,
		} = diskObject;

		return {
			diskObject: {
				id,
				name,
				createTime,
				updateTime,
				isFolder,
				typeFile,
				updatedBy,
				storageId,
				hasPreview: Boolean(links?.preview),
				links,
				folderContextType,
			},
			showFileExtension,
		};
	};

	module.exports = {
		FileContentView: connect(mapStateToProps)(Content),
	};
});

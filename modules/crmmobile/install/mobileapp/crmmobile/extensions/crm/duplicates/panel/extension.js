/**
 * @module crm/duplicates/panel
 */
jn.define('crm/duplicates/panel', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('crm/type');
	const AppTheme = require('apptheme');
	const { capitalize } = require('utils/string');
	const { ClientItem } = require('layout/ui/fields/client/elements');
	const { openCrmEntityInAppUrl } = require('crm/in-app-url/open');

	const IS_ANDROID = Application.getPlatform() === 'android';
	const BACKGROUND_COLOR = AppTheme.colors.bgSecondary;

	/**
	 * @class DuplicatesPanel
	 */
	class DuplicatesPanel extends LayoutComponent
	{
		/**
		 * @param {String} props.entityTypeName
		 * @param {Boolean} props.isAllowed
		 * @param {Object} props.duplicates
		 * @param {String} props.uid
		 * @param {Function} props.onUseContact
		 * @return DuplicatesPanel
		 */
		constructor(props)
		{
			super(props);

			const { duplicates } = props;
			this.sections = this.getSections(duplicates);
		}

		render()
		{
			return ScrollView(
				{
					style: {
						backgroundColor: BACKGROUND_COLOR,
					},
					safeArea: {
						bottom: true,
					},
				},
				View(
					{
						style: {
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
					},
					...this.renderItems(this.sections),
				),
			);
		}

		renderItems(sections)
		{
			const border = {
				borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
				borderBottomWidth: 1,
			};

			return sections.map((section) => View(
				{
					style: {
						width: '100%',
						marginBottom: 16,
						borderRadius: 12,
						paddingHorizontal: 16,
					},
				},
				...section.map((item, index) => View(
					{
						style: {
							paddingTop: 18,
							paddingBottom: 20,
							...(index === section.length - 1 ? {} : border),
						},
					},
					new ClientItem(item),
				)),
			));
		}

		prepareItem(item)
		{
			const { ENTITY_ID, ENTITY_TYPE_ID, PHONE, EMAIL, TITLE, POST, URL, RESPONSIBLE_PHOTO_URL } = item;
			const hidden = !URL.trim();

			const params = {
				id: ENTITY_ID,
				entityTypeId: ENTITY_TYPE_ID,
				type: Type.resolveNameById(ENTITY_TYPE_ID),
				title: TITLE,
				url: URL,
				responsiblePhotoUrl: RESPONSIBLE_PHOTO_URL,
				hidden,
			};

			return {
				...params,
				phone: PHONE,
				email: EMAIL,
				subtitle: POST,
				showClientInfo: true,
				showResponsiblePhoto: true,
				actionParams: !hidden && this.getDuplicateActionParams(params),
				onOpenBackdrop: () => {
					this.handleOpenBackdrop(params);
				},
			};
		}

		getSections({ ENTITIES })
		{
			const { entityTypeName } = this.props;
			if (!Array.isArray(ENTITIES))
			{
				return [];
			}

			const entityTypeId = Type.resolveIdByName(entityTypeName);
			const sectionsByEntity = {};

			ENTITIES
				.sort(({ ENTITY_TYPE_ID }) => (Number(entityTypeId) === Number(ENTITY_TYPE_ID) ? -1 : 1))
				.forEach((entity) => {
					const typeName = Type.resolveNameById(entity.ENTITY_TYPE_ID);
					const entityResult = sectionsByEntity[typeName] || [];
					const item = this.prepareItem(entity);
					entityResult.push(item);
					sectionsByEntity[typeName] = entityResult;
				});

			return Object.values(sectionsByEntity);
		}

		getDuplicateActionParams(params)
		{
			const { id, type, entityTypeId } = params;
			const { onUseContact } = this.props;
			const isAllowed = this.isAllowed(type);
			const text = Loc.getMessage(`MCRM_DUPLICATES_PANEL_CONTACT_${isAllowed ? 'USE' : 'OPEN_MSGVER_1'}`);
			const onClick = () => {
				if (isAllowed && onUseContact)
				{
					onUseContact(id, entityTypeId, params);
				}
				else
				{
					this.handleOpenBackdrop(params);
				}
			};

			return {
				onClick,
				element: Button({
					style: {
						color: AppTheme.colors.baseWhiteFixed,
						backgroundColor: AppTheme.colors.accentMainPrimaryalt,
						fontSize: 13,
						textAlign: 'center',
						marginTop: 12,
						width: 100,
						height: 22,
						borderRadius: 11,
					},
					onClick,
					text,
				}),
			};
		}

		handleOpenBackdrop(params)
		{
			if (!params.url)
			{
				return;
			}

			if (this.shouldCloseOnEntityOpen)
			{
				this.layoutWidget.close(() => {
					this.openCrmEntity(params);
				});
			}
			else
			{
				this.openCrmEntity(params);
			}
		}

		openCrmEntity(params)
		{
			const { uid } = this.props;
			const { url, type } = params;

			const payload = this.isAllowed(type)
				? {
					url,
					context: {
						useDuplicate: this.useDuplicateForEntityDetails,
						rightButtonName: capitalize(Loc.getMessage('MCRM_DUPLICATES_PANEL_CONTACT_USE')),
						uid,
						parentWidget: this.layoutWidget,
					},
				}
				: { url };

			openCrmEntityInAppUrl(payload);
		}

		isAllowed(duplicateEntityTypeName)
		{
			const { isAllowed, entityTypeName, isAllowedAnyEntityType } = this.props;

			return (
				isAllowed
				&& (
					isAllowedAnyEntityType
					|| entityTypeName === duplicateEntityTypeName
				)
			);
		}

		open(parentWidget = PageManager)
		{
			if (this.sections.length === 0)
			{
				return;
			}

			parentWidget.openWidget('layout', this.getWidgetParams())
				.then((layoutWidget) => {
					this.layoutWidget = layoutWidget;
					this.layoutWidget.enableNavigationBarBorder(false);
					layoutWidget.showComponent(this);
				})
				.catch(console.error);
		}

		getWidgetParams()
		{
			return {
				title: Loc.getMessage('MCRM_DUPLICATES_PANEL_TITLE_MSGVER_1'),
				backdrop: {
					forceDismissOnSwipeDown: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
					mediumPositionHeight: this.calcBackdropHeight(),
					navigationBarColor: BACKGROUND_COLOR,
					onlyMediumPosition: true,
					shouldResizeContent: true,
					swipeAllowed: true,
					swipeContentAllowed: false,
				},
			};
		}

		close(callback = () => {})
		{
			if (this.layoutWidget)
			{
				this.layoutWidget.close(callback);
			}
		}

		calcBackdropHeight()
		{
			const dividerHeight = this.sections.length * 15;
			const safeAreaAndroid = IS_ANDROID ? 38 : 0;
			let itemHeight = 78;

			return this.sections.flat().reduce((height, item) => {
				if (item.subtitle)
				{
					itemHeight += 20;
				}

				if (item.phone && item.phone.length > 0 && item.email && item.email.length > 0)
				{
					itemHeight += 20;
				}

				return height + itemHeight;
			}, 36) + dividerHeight + safeAreaAndroid;
		}

		get shouldCloseOnEntityOpen()
		{
			return BX.prop.getBoolean(this.props, 'shouldCloseOnEntityOpen', true);
		}

		get useDuplicateForEntityDetails()
		{
			return BX.prop.getBoolean(this.props, 'useDuplicateForEntityDetails', true);
		}
	}

	module.exports = { DuplicatesPanel };
});

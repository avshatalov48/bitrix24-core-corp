/**
 * @module crm/duplicates/panel
 */
jn.define('crm/duplicates/panel', (require, exports, module) => {
	const { ClientItem } = require('layout/ui/fields/client/elements');
	const { Loc } = require('loc');
	const { Type } = require('crm/type');
	const { capitalize } = require('utils/string');
	const { transparent } = require('utils/color');
	const { openCrmEntityInAppUrl } = require('crm/in-app-url/open');

	const BACKGROUND_COLOR = '#eef2f4';

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
					{},
					...this.renderItems(this.sections),
				),
			);
		}

		renderItems(sections)
		{
			const border = {
				borderBottomColor: '#e6e7e9',
				borderBottomWidth: 1,
			};

			return sections.map((section) => View(
				{
					style: {
						width: '100%',
						marginBottom: 16,
						backgroundColor: '#ffffff',
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
			const { ENTITY_ID, ENTITY_TYPE_ID, PHONE, EMAIL, TITLE, POST, URL } = item;
			const hidden = !URL.trim();

			const params = {
				id: ENTITY_ID,
				entityTypeId: ENTITY_TYPE_ID,
				type: Type.resolveNameById(ENTITY_TYPE_ID),
				title: TITLE,
				url: URL,
				hidden,
			};

			return {
				...params,
				phone: PHONE,
				email: EMAIL,
				subtitle: POST,
				showClientInfo: true,
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
			const text = Loc.getMessage(`MCRM_DUPLICATES_PANEL_CONTACT_${isAllowed ? 'USE' : 'OPEN'}`);

			return {
				onClick: () => {
					if (isAllowed && onUseContact)
					{
						onUseContact(id, entityTypeId, params);
					}
					else
					{
						this.handleOpenBackdrop(params);
					}
				},
				element: Text({
					style: {
						marginLeft: 8,
						marginTop: 2,
						color: '#2066b0',
						fontSize: 12,
						borderBottomWidth: 1,
						borderBottomColor: transparent('#2066b0', 0.4),
						borderStyle: 'dash',
						borderDashSegmentLength: 3,
						borderDashGapLength: 3,
					},
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

		open(layoutWidget = PageManager)
		{
			if (this.sections.length === 0)
			{
				return;
			}

			layoutWidget.openWidget('layout', this.getWidgetParams())
				.then((layoutWidget) => {
					this.layoutWidget = layoutWidget;
					this.layoutWidget.enableNavigationBarBorder(false);
					layoutWidget.showComponent(this);
				});
		}

		getWidgetParams()
		{
			return {
				title: Loc.getMessage('MCRM_DUPLICATES_PANEL_TITLE'),
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

			// eslint-disable-next-line unicorn/no-array-reduce
			return this.sections.flat().reduce((height, item) => {
				let itemHeight = 78;

				if (item.subtitle)
				{
					itemHeight += 20;
				}

				if (item.phone && item.phone.length > 0 && item.email && item.email.length > 0)
				{
					itemHeight += 20;
				}

				return height + itemHeight;
			}, 36) + dividerHeight;
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

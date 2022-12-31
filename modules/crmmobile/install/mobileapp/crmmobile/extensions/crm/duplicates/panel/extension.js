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

	const BACKGROUND_COLOR = '#EEF2F4';

	/**
	 * @class DuplicatesPanel
	 */
	class DuplicatesPanel extends LayoutComponent
	{
		/**
		 * @param {String} props.entityType
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
				borderBottomColor: '#e6e6e6',
				borderBottomWidth: 1,
			};

			return sections.map((section) =>
				View({
						style: {
							width: '100%',
							marginBottom: 16,
							backgroundColor: '#FFFFFF',
							borderRadius: 12,
							paddingHorizontal: 16,
						},
					},
					...section.map((item, index) =>
						View({
								style: {
									paddingTop: 18,
									paddingBottom: 20,
									...(index !== section.length - 1 ? border : {}),
								},
							},
							new ClientItem(item),
						),
					),
				),
			);
		}

		prepareItem(item)
		{
			const { ENTITY_ID, ENTITY_TYPE_ID, PHONE, EMAIL, TITLE, POST, URL } = item;
			const hidden = !Boolean(URL.trim());

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
			const { entityType } = this.props;
			if (!Array.isArray(ENTITIES))
			{
				return [];
			}

			const entityTypeId = Type.resolveIdByName(entityType);
			const sectionsByEntity = {};

			ENTITIES
				.sort(({ ENTITY_TYPE_ID }) => Number(entityTypeId) === Number(ENTITY_TYPE_ID) ? -1 : 1)
				.forEach((entity) => {
					const entityTypeName = Type.resolveNameById(entity.ENTITY_TYPE_ID);
					const entityResult = sectionsByEntity[entityTypeName] || [];
					const item = this.prepareItem(entity);
					entityResult.push(item);
					sectionsByEntity[entityTypeName] = entityResult;
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
						onUseContact(id, entityTypeId);
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
						color: '#2066B0',
						fontSize: 12,
						borderBottomWidth: 1,
						borderBottomColor: transparent('#2066B0', 0.4),
						borderStyle: 'dash',
						borderDashSegmentLength: 3,
						borderDashGapLength: 3
					}, text,
				}),
			};
		}

		handleOpenBackdrop({ type, url })
		{
			if (!url)
			{
				return;
			}

			this.layoutWidget.close(() => {
				const { uid } = this.props;

				const payload = !this.isAllowed(type)
					? { url }
					: {
						url,
						context: {
							useDuplicate: true,
							rightButtonName: capitalize(Loc.getMessage('MCRM_DUPLICATES_PANEL_CONTACT_USE')),
							uid,
						},
					};

				openCrmEntityInAppUrl(payload);
			});
		}

		isAllowed(duplicateEntityTypeName)
		{
			const { isAllowed, entityType } = this.props;

			return isAllowed && entityType === duplicateEntityTypeName;
		}

		open()
		{
			if (!this.sections.length)
			{
				return;
			}

			PageManager.openWidget('layout', this.getWidgetParams())
				.then((layoutWidget) => {
					this.layoutWidget = layoutWidget;
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

		calcBackdropHeight()
		{
			return this.sections
				.flat()
				.reduce((height, item) => height + (item.title ? 103 : 85), 36);
		}
	}

	module.exports = { DuplicatesPanel };
});
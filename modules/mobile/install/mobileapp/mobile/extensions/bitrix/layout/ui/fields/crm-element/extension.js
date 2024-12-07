/**
 * @module layout/ui/fields/crm-element
 */
jn.define('layout/ui/fields/crm-element', (require, exports, module) => {
	const { EntitySelectorFieldClass, EntitySelectorField, CastType } = require('layout/ui/fields/entity-selector');
	const { get, clone } = require('utils/object');
	const { stringify } = require('utils/string');
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { EntitySelectorFactory } = require('selector/widget/factory');
	const { Icon } = require('assets/icons');

	const DEFAULT_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields/crm-element/images/default-avatar.png';

	let Type = null;
	let TypeId = null;
	let openCrmEntityInAppUrl = null;

	const MAX_VISIBLE_ENTITY = 3;

	try
	{
		Type = require('crm/type').Type;
		TypeId = require('crm/type').TypeId;
		openCrmEntityInAppUrl = require('crm/in-app-url/open').openCrmEntityInAppUrl;
	}
	catch (e)
	{
		console.warn(e);

		return;
	}

	/**
	 * @class CrmElementField
	 */
	class CrmElementField extends EntitySelectorFieldClass
	{
		constructor(props)
		{
			super(props);

			this.state.showAll = false;

			this.handleUpdateEntity = this.handleUpdateEntity.bind(this);
		}

		componentDidMount()
		{
			super.componentDidMount();

			BX.addCustomEvent('DetailCard::onUpdate', this.handleUpdateEntity);
		}

		handleUpdateEntity(uid, { entityTypeId, entityId }, titleParams = {})
		{
			const text = titleParams && stringify(titleParams.text) || '';
			if (text === '')
			{
				return;
			}

			const entityList = clone(this.state.entityList);

			const entity = entityList.find(({ type, id }) => {
				type = Type.resolveIdByName(type);
				id = parseInt(id);

				return type === entityTypeId && id === entityId;
			});

			if (entity && entity.title !== text)
			{
				entity.title = text;
				this.setState({ entityList });
			}
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				selectorType: EntitySelectorFactory.Type.CRM_ELEMENT,
				castType: CastType.STRING,
			};
		}

		renderEmptyEntity()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Image({
					style: this.styles.entityImage,
					uri: this.getImageUrl(DEFAULT_AVATAR),
				}),
				Text({
					style: this.styles.emptyEntity,
					numberOfLines: 1,
					ellipsize: 'end',
					text: BX.message('FIELDS_CRM_ELEMENT_EMPTY'),
				}),
			);
		}

		getEntityTypeId({ type, id })
		{
			if (type === 'dynamic_multiple' && id)
			{
				return parseInt(id.split(':')[0], 10) || null;
			}

			return Type.resolveIdByName(type);
		}

		renderEntity(entity = {}, showPadding = false)
		{
			const { imageUrl, type } = entity;
			const onClick = this.openEntity.bind(this, entity);
			const subtitle = get(entity, ['customData', 'entityInfo', 'typeNameTitle'], null) || entity.subtitle;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingBottom: showPadding ? 5 : undefined,
					},
				},
				Image({
					style: this.styles.entityImage,
					uri: this.getImageUrl(imageUrl, type),
					onClick,
				}),
				View(
					{
						style: {
							flexDirection: 'column',
							flexShrink: 2,
						},
						onClick,
					},
					Text({
						style: this.styles.entityTitle(this.canOpenEntity(entity)),
						numberOfLines: 1,
						ellipsize: 'end',
						text: this.getEntityTitle(entity),
					}),
					subtitle && Text({
						style: this.styles.entitySubtitle,
						numberOfLines: 1,
						ellipsize: 'end',
						text: subtitle,
					}),
				),
			);
		}

		getEntityTitle(entity)
		{
			const { type, title, hidden } = entity;

			if (title && !hidden)
			{
				return title;
			}
			const messageId = `FIELDS_CRM_ELEMENT_HIDDEN_${type.toUpperCase()}`;

			return Loc.hasMessage(messageId) ? Loc.getMessage(messageId) : Loc.getMessage('FIELDS_CRM_ELEMENT_HIDDEN');
		}

		getImageUrl(imageUrl, type)
		{
			imageUrl = stringify(imageUrl);

			if (imageUrl === '')
			{
				const path = `/bitrix/mobileapp/mobile/extensions/bitrix/selector/providers/common/images/${type}.png`;

				return currentDomain + path;
			}

			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(String(currentDomain), '');
				imageUrl = (imageUrl.indexOf('http') === 0 ? imageUrl : `${currentDomain}${imageUrl}`);
				imageUrl = encodeURI(imageUrl);
			}

			return imageUrl;
		}

		openEntity(entity)
		{
			if (!this.canOpenEntity(entity))
			{
				return;
			}

			let entityTypeId = null;
			let entityId = null;

			const { type, id } = entity;

			if (type === 'dynamic_multiple' && id)
			{
				entityTypeId = parseInt(id.split(':')[0], 10) || null;
				entityId = parseInt(id.split(':')[1], 10) || null;
			}
			else
			{
				entityTypeId = Type.resolveIdByName(type);
				entityId = id;
			}

			openCrmEntityInAppUrl({ entityTypeId, entityId });
		}

		isEmpty()
		{
			return this.state.entityList.every((entity) => entity.hidden);
		}

		canOpenEntity(entity)
		{
			if (!entity)
			{
				return false;
			}

			const { hidden } = entity;
			if (hidden)
			{
				return false;
			}

			return Type.isEntitySupportedById(this.getEntityTypeId(entity));
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				emptyEntity: {
					...styles.emptyValue,
					flex: null,
				},
				entityContent: {
					...styles.entityContent,
					flexDirection: 'column',
					flexWrap: 'no-wrap',
				},
				entityImage: {
					width: 24,
					height: 24,
					borderRadius: 12,
					marginRight: 6,
				},
				entityTitle: (clickable) => ({
					color: clickable ? AppTheme.colors.accentMainLinks : AppTheme.colors.base1,
					fontSize: 16,
					flexShrink: 2,
				}),
				entitySubtitle: {
					color: AppTheme.colors.base4,
					fontSize: 12,
					flexShrink: 2,
				},
			};
		}

		getAddButtonText()
		{
			return BX.message('FIELDS_CRM_ELEMENT_EMPTY');
		}

		getDefaultLeftIcon()
		{
			return Icon.CRM;
		}
	}

	CrmElementField.propTypes = {
		...EntitySelectorFieldClass.propTypes,
	};

	CrmElementField.defaultProps = {
		...EntitySelectorFieldClass.defaultProps,
	};

	module.exports = {
		CrmElementType: 'crm',
		CrmElementFieldClass: CrmElementField,
		CrmElementField: (props) => new CrmElementField(props),
	};
});

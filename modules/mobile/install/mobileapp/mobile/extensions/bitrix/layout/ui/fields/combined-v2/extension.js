/**
 * @module layout/ui/fields/combined-v2
 */
jn.define('layout/ui/fields/combined-v2', (require, exports, module) => {

	const { isOpenLine, getOpenLineTitle } = require('communication/connection');
	const { CommunicationEvents } = require('communication/events');
	const { CombinedFieldClass } = require('layout/ui/fields/combined');
	const { ImType, ImFieldClass } = require('layout/ui/fields/im');
	const { PhoneType } = require('layout/ui/fields/phone');
	const { EmailType } = require('layout/ui/fields/email');
	const { WebType, WebFieldClass } = require('layout/ui/fields/web');
	const { get } = require('utils/object');
	const { getHttpPath } = require('utils/url');

	/**
	 * @class CombinedV2Field
	 */
	class CombinedV2Field extends CombinedFieldClass
	{
		constructor(props)
		{
			super(props);
			this.primaryHandleOnClick = this.handlePrimaryOnClick.bind(this);
		}

		needToValidateCurrentTick(newProps)
		{
			return false;
		}

		prepareFieldsConfig()
		{
			const fieldsConfig = super.prepareFieldsConfig();
			const { primaryField, secondaryField } = fieldsConfig;

			if (this.isLinkType(primaryField.type))
			{
				const { value } = this.props;
				const { items } = this.getConfig();
				const valueType = get(value, [secondaryField.id], null) || get(items, [0, primaryField.id], '');

				primaryField.valueLink = get(value, ['LINK'], '');
				primaryField.valueType = valueType.toLowerCase();
			}

			primaryField.onContentClick = this.primaryHandleOnClick;
			primaryField.config.deepMergeStyles = {
				...primaryField.config.deepMergeStyles,
				value: {
					color: '#2066b0',
				},
				wrapper: {
					paddingTop: 0,
					paddingBottom: 0,
				},
				readOnlyWrapper: {
					paddingTop: 0,
					paddingBottom: 0,
				},
			};

			secondaryField.readOnly = this.isReadOnly();
			secondaryField.config = this.prepareSecondaryConfig(secondaryField.config, primaryField.type);

			return fieldsConfig;
		}

		prepareSecondaryConfig(config, primaryType)
		{
			const getImages = this.getPathImagesPrimaryType(primaryType);

			const items = config.items.map((menuItem) => ({
				...menuItem,
				img: menuItem.id && getImages(menuItem.id.toLowerCase()),
			}));

			const deepMergeStyles = {
				...config.deepMergeStyles,
				wrapper: {
					paddingTop: 0,
					paddingBottom: 0,
				},
				readOnlyWrapper: {
					paddingTop: 0,
					paddingBottom: 0,
				},
			};

			return {
				...config,
				items,
				deepMergeStyles,
				shouldResizeContent: true,
				partiallyHidden: false,
				selectShowImages: !this.isReadOnly(),
			};
		}

		getPathImagesPrimaryType(primaryType)
		{
			const entityTypes = {
				[ImType]: ImFieldClass,
				[WebType]: WebFieldClass,
			};

			if (!entityTypes.hasOwnProperty(primaryType))
			{
				return () => {
				};
			}

			return (valueType) => entityTypes[primaryType].getImage({ valueType });
		}

		isReadOnly()
		{
			return super.isReadOnly() && !this.isNew();
		}

		handlePrimaryOnClick()
		{
			const primaryType = this.getPrimaryFieldType();
			const value = this.getPrimaryValue();

			CommunicationEvents.execute({ type: primaryType, props: value });
		}

		getPrimaryValue()
		{
			const { primaryField } = this.prepareFieldsConfig();
			const { VALUE, LINK, VALUE_TYPE } = this.getValue();
			const primaryType = primaryField.type;
			const primaryConfig = primaryField.config;

			switch (primaryType)
			{
				case ImType:
					let params;

					if (isOpenLine(LINK))
					{
						params = {
							titleParams: { name: getOpenLineTitle(LINK) },
							userCode: LINK,
						};
					}
					else
					{
						params = { value: LINK };
					}

					return {
						event: VALUE_TYPE,
						params,
					};

				case PhoneType:
					return {
						number: VALUE.phoneNumber || '',
						params: {
							'ENTITY_TYPE_NAME': primaryConfig.entityTypeName,
							'ENTITY_ID': primaryConfig.entityId,
						},
						alert: true,
					};
				case EmailType:
					const {
						entityTypeName,
						entityId
					} = primaryConfig;
					return {
						email: VALUE,
						params: {
							owner: {
								ownerType: entityTypeName,
								ownerId: entityId,
							}
						},
					};

				case WebType:
					return getHttpPath(VALUE);
			}

			return VALUE;
		}

		isLinkType(type)
		{
			const linkTypes = [
				ImType,
				WebType,
			];

			return linkTypes.includes(type);
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				primaryFieldTitle: {
					display: 'none',
					marginBottom: 0,
				},
				combinedContainer: {
					width: '100%',
					alignItems: 'center',
					flexDirection: 'row',
				},
				primaryFieldWrapper: {
					paddingTop: 0,
					paddingBottom: 0,
				},
				primaryFieldContainer: {
					flex: 2,
					marginRight: 0,
					paddingRight: 5,
				},
				secondaryFieldContainer: {
					flexDirection: 'row',
				},
				secondaryFieldWrapper: {
					flexDirection: 'row',
					paddingLeft: 6,
					paddingRight: 12,
					paddingTop: 0,
					paddingBottom: 0,
					marginTop: 0,
					marginBottom: 0,
				},
				secondaryFieldTitle: {
					display: 'none',
					marginBottom: 0,
				},
				secondaryFieldValue: {
					color: '#a8adb4',
					fontSize: 12,
				},
				secondaryArrowImage: {
					marginLeft: 0,
				},
			};
		}
	}

	module.exports = {
		CombinedV2Type: 'combined-v2',
		CombinedV2Field: (props) => new CombinedV2Field(props),
	};

});

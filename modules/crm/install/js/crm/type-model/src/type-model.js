import { Text, Type } from 'main.core';
import { Model } from 'crm.model';

export type TypeModelData = {
	id: ?number,
	entityTypeId: ?number,
	title: ?string,
	createdBy: ?number,
	isCategoriesEnabled?: boolean,
	isStagesEnabled?: boolean,
	isBeginCloseDatesEnabled?: boolean,
	isClientEnabled?: boolean,
	isLinkWithProductsEnabled?: boolean,
	isCrmTrackingEnabled?: boolean,
	isMycompanyEnabled?: boolean,
	isDocumentsEnabled?: boolean,
	isSourceEnabled?: boolean,
	isUseInUserfieldEnabled?: boolean,
	isObserversEnabled?: boolean,
	isRecyclebinEnabled?: boolean,
	isAutomationEnabled?: boolean,
	isBizProcEnabled?: boolean,
	isSetOpenPermissions?: boolean,
	conversionMap?: {
		//we can't send an empty array via ajax. therefore, it's stored as 'false'
		sourceTypes: false|number[],
		destinationTypes: false|number[]
	},
	linkedUserFields?: {[key: string]: Boolean},
	relations?: RelationsMap,
	customSectionId: ?number,
	customSections: CustomSection[],
	isExternal?: boolean,
	isSaveFromTypeDetail?: boolean,
};

declare type RelationsMap = {
	parent: Relation[],
	child: Relation[],
}

declare type Relation = {
	entityTypeId: number,
	isChildrenListEnabled?: boolean|null,
	isPredefined?: boolean,
}

export type ConversionMap = {
	sourceTypes: number[],
	destinationTypes: number[],
};

export type CustomSection = {
	id: number,
	title: string,
	isSelected: boolean,
};

/**
 * @memberOf BX.Crm.Models
 */
export class TypeModel extends Model
{
	constructor(data: TypeModelData, params: ?{})
	{
		super(data, params);
	}

	getModelName(): string
	{
		return 'type';
	}

	getData(): TypeModelData
	{
		const data = super.getData();
		if (!Type.isObject(data.linkedUserFields))
		{
			data.linkedUserFields = false;
		}
		data.relations = this.getRelations();
		data.customSections = this.getCustomSections();

		return data;
	}

	getTitle(): ?string
	{
		return this.data.title;
	}

	setTitle(title: string)
	{
		this.data.title = title;
	}

	getCreatedBy(): ?number
	{
		return this.data.createdBy;
	}

	getIsCategoriesEnabled(): boolean
	{
		return this.data.isCategoriesEnabled;
	}

	setIsCategoriesEnabled(isCategoriesEnabled: boolean)
	{
		this.data.isCategoriesEnabled = (isCategoriesEnabled === true);
	}

	getIsStagesEnabled(): boolean
	{
		return this.data.isStagesEnabled;
	}

	setIsStagesEnabled(isStagesEnabled: boolean)
	{
		this.data.isStagesEnabled = (isStagesEnabled === true);
	}

	getIsBeginCloseDatesEnabled(): boolean
	{
		return this.data.isBeginCloseDatesEnabled;
	}

	setIsBeginCloseDatesEnabled(isBeginCloseDatesEnabled: boolean)
	{
		this.data.isBeginCloseDatesEnabled = (isBeginCloseDatesEnabled === true);
	}

	getIsClientEnabled(): boolean
	{
		return this.data.isBeginCloseDatesEnabled;
	}

	setIsClientEnabled(isClientEnabled: boolean)
	{
		this.data.isClientEnabled = (isClientEnabled === true);
	}

	getIsLinkWithProductsEnabled(): boolean
	{
		return this.data.isLinkWithProductsEnabled;
	}

	setIsLinkWithProductsEnabled(isLinkWithProductsEnabled: boolean)
	{
		this.data.isLinkWithProductsEnabled = (isLinkWithProductsEnabled === true);
	}

	getIsCrmTrackingEnabled(): boolean
	{
		return this.data.isCrmTrackingEnabled;
	}

	setIsCrmTrackingEnabled(isCrmTrackingEnabled: boolean)
	{
		this.data.isCrmTrackingEnabled = (isCrmTrackingEnabled === true);
	}

	getIsMycompanyEnabled(): boolean
	{
		return this.data.isMycompanyEnabled;
	}

	setIsMycompanyEnabled(isMycompanyEnabled: boolean)
	{
		this.data.isMycompanyEnabled = (isMycompanyEnabled === true);
	}

	getIsDocumentsEnabled(): boolean
	{
		return this.data.isDocumentsEnabled;
	}

	setIsDocumentsEnabled(isDocumentsEnabled: boolean)
	{
		this.data.isDocumentsEnabled = (isDocumentsEnabled === true);
	}

	getIsSourceEnabled(): boolean
	{
		return this.data.isSourceEnabled;
	}

	setIsSourceEnabled(isSourceEnabled: boolean)
	{
		this.data.isSourceEnabled = (isSourceEnabled === true);
	}

	getIsUseInUserfieldEnabled(): boolean
	{
		return this.data.isUseInUserfieldEnabled;
	}

	setIsUseInUserfieldEnabled(isUseInUserfieldEnabled: boolean)
	{
		this.data.isUseInUserfieldEnabled = (isUseInUserfieldEnabled === true);
	}

	getIsObserversEnabled(): boolean
	{
		return this.data.isObserversEnabled;
	}

	setIsObserversEnabled(isObserversEnabled: boolean)
	{
		this.data.isObserversEnabled = (isObserversEnabled === true);
	}

	getIsRecyclebinEnabled(): boolean
	{
		return this.data.isRecyclebinEnabled;
	}

	setIsRecyclebinEnabled(isRecyclebinEnabled: boolean)
	{
		this.data.isRecyclebinEnabled = (isRecyclebinEnabled === true);
	}

	getIsAutomationEnabled(): boolean
	{
		return this.data.isAutomationEnabled;
	}

	setIsAutomationEnabled(isAutomationEnabled: boolean)
	{
		this.data.isAutomationEnabled = (isAutomationEnabled === true);
	}

	getIsBizProcEnabled(): boolean
	{
		return this.data.isBizProcEnabled;
	}

	setIsBizProcEnabled(isBizProcEnabled: boolean)
	{
		this.data.isBizProcEnabled = (isBizProcEnabled === true);
	}

	getIsSetOpenPermissions(): boolean
	{
		return this.data.isSetOpenPermissions;
	}

	setIsSetOpenPermissions(isSetOpenPermissions: boolean)
	{
		this.data.isSetOpenPermissions = (isSetOpenPermissions === true);
	}

	getLinkedUserFields(): {[key: string]: Boolean}|false
	{
		return this.data.linkedUserFields;
	}

	setLinkedUserFields(linkedUserFields: {[key: string]: Boolean})
	{
		this.data.linkedUserFields = linkedUserFields;
	}

	getCustomSectionId(): ?number
	{
		if (this.data.hasOwnProperty('customSectionId'))
		{
			return Text.toInteger(this.data.customSectionId);
		}

		return null;
	}

	setCustomSectionId(customSectionId: number)
	{
		this.data.customSectionId = customSectionId;
	}

	getCustomSections(): CustomSection[]|false
	{
		const customSections = this.data.customSections;
		if (Type.isArray(customSections) && customSections.length === 0)
		{
			return false;
		}

		return customSections;
	}

	setCustomSections(customSections: CustomSection[])
	{
		this.data.customSections = customSections;
	}

	setIsExternalDynamicalType(isExternal: boolean): void
	{
		this.data.isExternal = isExternal;
	}

	setIsSaveFromTypeDetail(isSaveFromTypeDetail: boolean): void
	{
		this.data.isSaveFromTypeDetail = isSaveFromTypeDetail;
	}

	setConversionMap({sourceTypes, destinationTypes}: ConversionMap)
	{
		if (!this.data.hasOwnProperty('conversionMap'))
		{
			this.data.conversionMap = {};
		}

		this.data.conversionMap.sourceTypes = this.normalizeTypes(sourceTypes);
		this.data.conversionMap.destinationTypes = this.normalizeTypes(destinationTypes);
	}

	getConversionMap(): undefined|ConversionMap
	{
		if (Type.isUndefined(this.data.conversionMap))
		{
			return undefined;
		}

		const conversionMap: ConversionMap = Object.assign({}, this.data.conversionMap);

		if (!conversionMap.sourceTypes)
		{
			conversionMap.sourceTypes = [];
		}
		if (!conversionMap.destinationTypes)
		{
			conversionMap.destinationTypes = [];
		}

		return conversionMap;
	}

	setRelations(relations: RelationsMap): void
	{
		this.data.relations = relations;
	}

	getRelations(): ?RelationsMap
	{
		if (!this.data.relations)
		{
			return null;
		}

		if (!Type.isArray(this.data.relations.parent) || !this.data.relations.parent.length)
		{
			this.data.relations.parent = false;
		}
		if (!Type.isArray(this.data.relations.child) || !this.data.relations.child.length)
		{
			this.data.relations.child = false;
		}

		return this.data.relations;
	}

	getIsCountersEnabled(): boolean {
		return this.data.isCountersEnabled
	}

	setIsCountersEnabled(isCountersEnabled: boolean){
		this.data.isCountersEnabled = isCountersEnabled
	}

	/**
	 * @protected
	 * @param types
	 * @return {false|number[]}
	 */
	normalizeTypes(types: []): false|number[]
	{
		if (!Type.isArrayFilled(types))
		{
			return false;
		}

		const arrayOfIntegers = types.map( (element) =>
		{
			return parseInt(element, 10);
		});

		return arrayOfIntegers.filter( (element) =>
		{
			return (element > 0);
		});
	}

	static getBooleanFieldNames(): string[]
	{
		return [
			'isCategoriesEnabled',
			'isStagesEnabled',
			'isBeginCloseDatesEnabled',
			'isClientEnabled',
			'isLinkWithProductsEnabled',
			'isCrmTrackingEnabled',
			'isMycompanyEnabled',
			'isDocumentsEnabled',
			'isSourceEnabled',
			'isUseInUserfieldEnabled',
			'isObserversEnabled',
			'isRecyclebinEnabled',
			'isAutomationEnabled',
			'isBizProcEnabled',
			'isSetOpenPermissions',
			'isCountersEnabled'
		];
	}
}

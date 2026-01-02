import type { DocumentInitiatedType, TemplateEntityType } from 'sign.type';
import { DocumentInitiated, TemplateEntity } from 'sign.type';

export type GridRow = BX.Grid.Row;

type Metadata = {
	id: number,
	buttonId: string,
	entityType: Exclude<TemplateEntity.multiple, TemplateEntityType>,
	initiatedByType: DocumentInitiatedType,
	canEdit: boolean,
	canDelete: boolean,
	isFolderEntityType(): boolean,
	isInitiatedByTypeisEmployee(): boolean,
	canEditAccess(): boolean,
	canDeleteAccess(): boolean,
};

function buildMetadataFromElement(element: HTMLElement): Metadata
{
	return {
		id: Number(element.dataset.id),
		entityType: element.dataset.entityType,
		initiatedByType: element.dataset.initiatedByType,
		canEdit: element.dataset.canEdit,
		canDelete: element.dataset.canDelete,
		isFolderEntityType(): boolean
		{
			return this.entityType === TemplateEntity.folder;
		},
		isInitiatedByTypeisEmployee(): boolean
		{
			return this.initiatedByType === DocumentInitiated.employee;
		},
		canEditAccess(): boolean
		{
			return this.canEdit;
		},
		canDeleteAccess(): boolean
		{
			return this.canDelete;
		},
	};
}

export function extractMetadataFromRow(row: GridRow): Metadata | null
{
	const cellWithMetadataElement = [...row.getCells()]
		.map((cell: HTMLElement) => cell.querySelector('.sign-grid-template__cell-metadata'))
		.find((element) => element)
	;
	if (!cellWithMetadataElement)
	{
		return null;
	}

	return buildMetadataFromElement(cellWithMetadataElement);
}

import { BaseField } from './base-field';
import { Tag, Dom, Loc } from 'main.core';
import 'ui.icon-set.main';
import { Dialog } from 'ui.entity-selector';
import { GridManager } from '../grid-manager';

export type DepartmentFieldType = {
	departments: Object,
	canEdit: boolean,
	selectedDepartment: number,
}

export class DepartmentField extends BaseField
{
	render(params: DepartmentFieldType): void
	{
		Dom.addClass(this.getFieldNode(), 'user-grid_department-container');

		if (params.departments.length === 0 && params.canEdit)
		{
			// TODO: add department button
			return;
			const onclick = () => {
				const dialog = new Dialog({
					width: 300,
					height: 300,
					targetNode: addButton,
					compactView: true,
					multiple: false,
					entities: [
						{
							id: 'department',
							options: {
								selectMode: 'departmentsOnly',
								allowSelectRootDepartment: true,
							},
						},
					],
				});

				dialog.show();
			};

			const addButton = Tag.render`
				<div class="user-grid_department-btn" onclick="${onclick}">
					<div class="user-grid_department-icon-container">
						<div class="ui-icon-set --plus-30" style="--ui-icon-set__icon-size: 18px; --ui-icon-set__icon-color: #2fc6f6;"></div>
					</div>
					<div class="user-grid_department-name-container">
						${Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_ADD_DEPARTMENT')}
					</div>
				</div>
			`;

			this.appendToFieldNode(addButton);
		}
		else
		{
			Object.values(params.departments).forEach((department) => {
				const isSelected = department.id === params.selectedDepartment;
				const onclick = () => {
					GridManager.setFilter({
						gridId: this.getGridId(),
						filter: {
							DEPARTMENT: isSelected ? '' : department.id,
							DEPARTMENT_label: isSelected ? '' : department.name,
						},
					});
				};

				const button = Tag.render`
					<div
						class="user-grid_department-btn ${isSelected ? '--selected' : ''}"
						onclick="${onclick}"
						>
						<div class="user-grid_department-name-container">
							${department.name}
						</div>
					</div>
				`;

				if (isSelected)
				{
					Dom.append(Tag.render`
						<div class="user-grid_department-btn-remove ui-icon-set --cross-60"></div>
					`, button);
				}

				this.appendToFieldNode(button);
			});
		}
	}
}

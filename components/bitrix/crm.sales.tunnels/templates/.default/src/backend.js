import {ajax} from 'main.core';
import type RequestResponse from './type/request-response';
import type CreateCategoryOptions from './type/create-category-options';
import type GetCategoryOptions from './type/get-category-options';
import type UpdateCategoryOptions from './type/update-category-options';
import type UpdateCategoryAccessOptions from "./type/update-category-access-options";
import type RemoveCategoryOptions from './type/remove-category-options';
import type CreateRobotOptions from './type/create-robot-options';
import type RemoveRobotOptions from './type/remove-robot-options';
import type RemoveStageOptions from './type/remove-stage-options';
import type UpdateStageOptions from './type/update-stage-options';
import type AddStageOptions from './type/add-stage-options';
import type CopyCategoryAccessOptions from "./type/copy-category-access-options";

export default class Backend
{
	static component: string = 'bitrix:crm.sales.tunnels';
	static entityTypeId: number = 2;

	static request({action, data, analyticsLabel}: RequestOptions): Promise<RequestResponse>
	{
		return new Promise((resolve, reject) => {
			ajax
				.runComponentAction(
					Backend.component,
					action,
					{
						mode: 'class',
						data: {
							data,
							entityTypeId: Backend.entityTypeId,
						},
						analyticsLabel,
					},
				)
				.then(resolve, reject);
		});
	}

	static createCategory(data: CreateCategoryOptions): Promise<RequestResponse>
	{
		return Backend.request({
			action: 'createCategory',
			analyticsLabel: {
				component: Backend.component,
				action: 'create.new.category',
			},
			data,
		});
	}

	static getCategory(data: GetCategoryOptions): Promise<RequestResponse>
	{
		return Backend.request({
			action: 'getCategory',
			analyticsLabel: {
				component: Backend.component,
				action: 'get.category',
			},
			data,
		});
	}

	static updateCategory(data: UpdateCategoryOptions): Promise<RequestResponse>
	{
		return Backend.request({
			action: 'updateCategory',
			analyticsLabel: {
				component: Backend.component,
				action: 'update.category',
			},
			data,
		});
	}

	static removeCategory(data: RemoveCategoryOptions): Promise<RequestResponse>
	{
		return Backend.request({
			action: 'removeCategory',
			analyticsLabel: {
				component: Backend.component,
				action: 'remove.category',
			},
			data,
		});
	}

	static accessCategory(data: UpdateCategoryAccessOptions): Promise<RequestResponse>
	{
		return Backend.request({
			action: 'accessCategory',
			analyticsLabel: {
				component: Backend.component,
				action: 'access.category',
			},
			data,
		});
	}

	static copyAccessCategory(data: CopyCategoryAccessOptions): Promise<RequestResponse>
	{
		return Backend.request({
			action: 'copyAccessCategory',
			analyticsLabel: {
				component: Backend.component,
				action: 'access.category',
			},
			data,
		});
	}

	static createRobot(data: CreateRobotOptions)
	{
		return Backend.request({
			action: 'createRobot',
			analyticsLabel: {
				component: Backend.component,
				action: 'create.robot',
			},
			data,
		});
	}

	static removeRobot(data: RemoveRobotOptions)
	{
		return Backend.request({
			action: 'removeRobot',
			analyticsLabel: {
				component: Backend.component,
				action: 'remove.robot',
			},
			data,
		});
	}

	static getRobotSettingsDialog(data: {[key: string]: any})
	{
		return Backend.request({
			action: 'getRobotSettingsDialog',
			analyticsLabel: {
				component: Backend.component,
				action: 'settings.robot',
			},
			data,
		});
	}

	static addStage(data: AddStageOptions)
	{
		return Backend.request({
			action: 'addStage',
			analyticsLabel: {
				component: Backend.component,
				action: 'add.stage',
			},
			data,
		});
	}

	static removeStage(data: RemoveStageOptions)
	{
		return Backend.request({
			action: 'removeStage',
			analyticsLabel: {
				component: Backend.component,
				action: 'remove.stage',
			},
			data,
		});
	}

	static updateStage(data: UpdateStageOptions)
	{
		return Backend.request({
			action: 'updateStage',
			analyticsLabel: {
				component: Backend.component,
				action: 'update.stage',
			},
			data,
		});
	}

	static updateStages(data: Array<UpdateStageOptions>)
	{
		return Backend.request({
			action: 'updateStages',
			analyticsLabel: {
				component: Backend.component,
				action: 'update.stages',
			},
			data,
		});
	}

	static getCategories()
	{
		return Backend.request({
			action: 'getCategories',
			analyticsLabel: {
				component: Backend.component,
				action: 'get.categories',
			},
		});
	}
}

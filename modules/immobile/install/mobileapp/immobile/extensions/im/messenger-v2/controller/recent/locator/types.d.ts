import { BaseList, JNEventEmitter } from '../../../../../../../../../../mobile/dev/janative/api';
import { IServiceLocator } from '../../../../messenger/lib/di/service-locator/types';
import { IQuickRecentService } from '../service/quick-recent/types';
import { IDatabaseLoadService } from '../service/database-load/types';
import { IServerLoadService } from '../service/server-load/types';
import { IFloatingButtonService } from '../service/floating-button/types';
import { IPaginationService } from '../service/pagination/types';
import { ISearchService } from '../service/search/types';
import { IRenderService } from '../service/render/types';
import { IVuexService } from '../service/vuex/types';
import { IActionService } from '../service/action/type';
import { ISelectService } from '../service/select/types';
import { IEmptyStateService } from '../service/empty-state/types';
import { IExternalService } from '../service/external/types';

declare type RecentLocatorServices = Partial<{
	'id': string,
	'ui': Promise<BaseList>,
	'quick-recent': IQuickRecentService,
	'database-load'?: IDatabaseLoadService,
	'server-load'?: IServerLoadService,
	'floating-button'?: IFloatingButtonService,
	'empty-state'?: IEmptyStateService,
	pagination?: IPaginationService,
	search?: ISearchService,
	render?: IRenderService,
	vuex?: IVuexService,
	action?: IActionService,
	select?: ISelectService,
	external?: IExternalService,
	emitter: JNEventEmitter,
}>

export type RecentLocator = IServiceLocator<RecentLocatorServices>;

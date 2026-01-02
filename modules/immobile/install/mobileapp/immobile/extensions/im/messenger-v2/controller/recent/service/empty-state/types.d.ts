import { IBaseRecentService } from '../base/type';

export interface IEmptyStateService extends IBaseRecentService
{
    show: () => Promise<void>;
    hide: () => Promise<void>;
}

declare type CommonEmptyStateServiceProps = {
    welcomeScreenExtension: string,
};

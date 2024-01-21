import { DetailInstance } from './detail-instance';
import type { DetailConfig } from './type/detail-config';
import type { SkeletonConfig } from './type/skeleton-config';
import { Skeleton } from './skeleton';

export class Detail
{
	static create(config: DetailConfig): DetailInstance
	{
		new DetailInstance(config);
	}

	static createSkeleton(config: SkeletonConfig): Skeleton
	{
		new Skeleton(config);
	}
}

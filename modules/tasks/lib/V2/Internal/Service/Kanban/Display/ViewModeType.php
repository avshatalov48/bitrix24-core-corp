<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display;

enum ViewModeType: string
{
	case Kanban = 'kanban';
	case Scrum = 'kanban_scrum';
	case KanbanTimeline = 'kanban_timeline';
	case KanbanPersonal = 'kanban_personal';
	case KanbanTimelinePersonal = 'kanban_timeline_personal';
}
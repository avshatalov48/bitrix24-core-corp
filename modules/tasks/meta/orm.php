<?php

/* ORMENTITYANNOTATION:Bitrix\Tasks\Integration\Report\Internals\TaskTable:tasks/lib/integration/report/internals/task.php */
namespace Bitrix\Tasks\Integration\Report\Internals {
	/**
	 * TaskObject
	 * @see \Bitrix\Tasks\Integration\Report\Internals\TaskTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\Tasks\Internals\TaskObject setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetTitle()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getDescription()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDescription()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDescription()
	 * @method \string fillDescription()
	 * @method \boolean getDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDescriptionInBbcode(\boolean|\Bitrix\Main\DB\SqlExpression $descriptionInBbcode)
	 * @method bool hasDescriptionInBbcode()
	 * @method bool isDescriptionInBbcodeFilled()
	 * @method bool isDescriptionInBbcodeChanged()
	 * @method \boolean remindActualDescriptionInBbcode()
	 * @method \boolean requireDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDescriptionInBbcode()
	 * @method \boolean fillDescriptionInBbcode()
	 * @method \string getPriority()
	 * @method \Bitrix\Tasks\Internals\TaskObject setPriority(\string|\Bitrix\Main\DB\SqlExpression $priority)
	 * @method bool hasPriority()
	 * @method bool isPriorityFilled()
	 * @method bool isPriorityChanged()
	 * @method \string remindActualPriority()
	 * @method \string requirePriority()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetPriority()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetPriority()
	 * @method \string fillPriority()
	 * @method \string getStatus()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStatus()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStatus()
	 * @method \string fillStatus()
	 * @method \int getStageId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStageId(\int|\Bitrix\Main\DB\SqlExpression $stageId)
	 * @method bool hasStageId()
	 * @method bool isStageIdFilled()
	 * @method bool isStageIdChanged()
	 * @method \int remindActualStageId()
	 * @method \int requireStageId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStageId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStageId()
	 * @method \int fillStageId()
	 * @method \int getResponsibleId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setResponsibleId(\int|\Bitrix\Main\DB\SqlExpression $responsibleId)
	 * @method bool hasResponsibleId()
	 * @method bool isResponsibleIdFilled()
	 * @method bool isResponsibleIdChanged()
	 * @method \int remindActualResponsibleId()
	 * @method \int requireResponsibleId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetResponsibleId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetResponsibleId()
	 * @method \int fillResponsibleId()
	 * @method \Bitrix\Main\Type\DateTime getDateStart()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDateStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStart)
	 * @method bool hasDateStart()
	 * @method bool isDateStartFilled()
	 * @method bool isDateStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStart()
	 * @method \Bitrix\Main\Type\DateTime requireDateStart()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDateStart()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDateStart()
	 * @method \Bitrix\Main\Type\DateTime fillDateStart()
	 * @method \int getDurationPlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDurationPlan(\int|\Bitrix\Main\DB\SqlExpression $durationPlan)
	 * @method bool hasDurationPlan()
	 * @method bool isDurationPlanFilled()
	 * @method bool isDurationPlanChanged()
	 * @method \int remindActualDurationPlan()
	 * @method \int requireDurationPlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDurationPlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDurationPlan()
	 * @method \int fillDurationPlan()
	 * @method \int getDurationFact()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDurationFact(\int|\Bitrix\Main\DB\SqlExpression $durationFact)
	 * @method bool hasDurationFact()
	 * @method bool isDurationFactFilled()
	 * @method bool isDurationFactChanged()
	 * @method \int remindActualDurationFact()
	 * @method \int requireDurationFact()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDurationFact()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDurationFact()
	 * @method \int fillDurationFact()
	 * @method \string getDurationType()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDurationType(\string|\Bitrix\Main\DB\SqlExpression $durationType)
	 * @method bool hasDurationType()
	 * @method bool isDurationTypeFilled()
	 * @method bool isDurationTypeChanged()
	 * @method \string remindActualDurationType()
	 * @method \string requireDurationType()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDurationType()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDurationType()
	 * @method \string fillDurationType()
	 * @method \int getTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setTimeEstimate(\int|\Bitrix\Main\DB\SqlExpression $timeEstimate)
	 * @method bool hasTimeEstimate()
	 * @method bool isTimeEstimateFilled()
	 * @method bool isTimeEstimateChanged()
	 * @method \int remindActualTimeEstimate()
	 * @method \int requireTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetTimeEstimate()
	 * @method \int fillTimeEstimate()
	 * @method \boolean getReplicate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setReplicate(\boolean|\Bitrix\Main\DB\SqlExpression $replicate)
	 * @method bool hasReplicate()
	 * @method bool isReplicateFilled()
	 * @method bool isReplicateChanged()
	 * @method \boolean remindActualReplicate()
	 * @method \boolean requireReplicate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetReplicate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetReplicate()
	 * @method \boolean fillReplicate()
	 * @method \Bitrix\Main\Type\DateTime getDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDeadline(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $deadline)
	 * @method bool hasDeadline()
	 * @method bool isDeadlineFilled()
	 * @method bool isDeadlineChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDeadline()
	 * @method \Bitrix\Main\Type\DateTime requireDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDeadline()
	 * @method \Bitrix\Main\Type\DateTime fillDeadline()
	 * @method \Bitrix\Main\Type\DateTime getStartDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStartDatePlan(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $startDatePlan)
	 * @method bool hasStartDatePlan()
	 * @method bool isStartDatePlanFilled()
	 * @method bool isStartDatePlanChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualStartDatePlan()
	 * @method \Bitrix\Main\Type\DateTime requireStartDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStartDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStartDatePlan()
	 * @method \Bitrix\Main\Type\DateTime fillStartDatePlan()
	 * @method \Bitrix\Main\Type\DateTime getEndDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject setEndDatePlan(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $endDatePlan)
	 * @method bool hasEndDatePlan()
	 * @method bool isEndDatePlanFilled()
	 * @method bool isEndDatePlanChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualEndDatePlan()
	 * @method \Bitrix\Main\Type\DateTime requireEndDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetEndDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetEndDatePlan()
	 * @method \Bitrix\Main\Type\DateTime fillEndDatePlan()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetCreatedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setCreatedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdDate)
	 * @method bool hasCreatedDate()
	 * @method bool isCreatedDateFilled()
	 * @method bool isCreatedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetCreatedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedDate()
	 * @method \int getChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject setChangedBy(\int|\Bitrix\Main\DB\SqlExpression $changedBy)
	 * @method bool hasChangedBy()
	 * @method bool isChangedByFilled()
	 * @method bool isChangedByChanged()
	 * @method \int remindActualChangedBy()
	 * @method \int requireChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetChangedBy()
	 * @method \int fillChangedBy()
	 * @method \Bitrix\Main\Type\DateTime getChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setChangedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $changedDate)
	 * @method bool hasChangedDate()
	 * @method bool isChangedDateFilled()
	 * @method bool isChangedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualChangedDate()
	 * @method \Bitrix\Main\Type\DateTime requireChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetChangedDate()
	 * @method \Bitrix\Main\Type\DateTime fillChangedDate()
	 * @method \int getStatusChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStatusChangedBy(\int|\Bitrix\Main\DB\SqlExpression $statusChangedBy)
	 * @method bool hasStatusChangedBy()
	 * @method bool isStatusChangedByFilled()
	 * @method bool isStatusChangedByChanged()
	 * @method \int remindActualStatusChangedBy()
	 * @method \int requireStatusChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStatusChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStatusChangedBy()
	 * @method \int fillStatusChangedBy()
	 * @method \Bitrix\Main\Type\DateTime getStatusChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStatusChangedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $statusChangedDate)
	 * @method bool hasStatusChangedDate()
	 * @method bool isStatusChangedDateFilled()
	 * @method bool isStatusChangedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualStatusChangedDate()
	 * @method \Bitrix\Main\Type\DateTime requireStatusChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStatusChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStatusChangedDate()
	 * @method \Bitrix\Main\Type\DateTime fillStatusChangedDate()
	 * @method \int getClosedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject setClosedBy(\int|\Bitrix\Main\DB\SqlExpression $closedBy)
	 * @method bool hasClosedBy()
	 * @method bool isClosedByFilled()
	 * @method bool isClosedByChanged()
	 * @method \int remindActualClosedBy()
	 * @method \int requireClosedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetClosedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetClosedBy()
	 * @method \int fillClosedBy()
	 * @method \Bitrix\Main\Type\DateTime getClosedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setClosedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $closedDate)
	 * @method bool hasClosedDate()
	 * @method bool isClosedDateFilled()
	 * @method bool isClosedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualClosedDate()
	 * @method \Bitrix\Main\Type\DateTime requireClosedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetClosedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetClosedDate()
	 * @method \Bitrix\Main\Type\DateTime fillClosedDate()
	 * @method \Bitrix\Main\Type\DateTime getActivityDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setActivityDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $activityDate)
	 * @method bool hasActivityDate()
	 * @method bool isActivityDateFilled()
	 * @method bool isActivityDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualActivityDate()
	 * @method \Bitrix\Main\Type\DateTime requireActivityDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetActivityDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetActivityDate()
	 * @method \Bitrix\Main\Type\DateTime fillActivityDate()
	 * @method \string getGuid()
	 * @method \Bitrix\Tasks\Internals\TaskObject setGuid(\string|\Bitrix\Main\DB\SqlExpression $guid)
	 * @method bool hasGuid()
	 * @method bool isGuidFilled()
	 * @method bool isGuidChanged()
	 * @method \string remindActualGuid()
	 * @method \string requireGuid()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetGuid()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetGuid()
	 * @method \string fillGuid()
	 * @method \string getXmlId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetXmlId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \string getMark()
	 * @method \Bitrix\Tasks\Internals\TaskObject setMark(\string|\Bitrix\Main\DB\SqlExpression $mark)
	 * @method bool hasMark()
	 * @method bool isMarkFilled()
	 * @method bool isMarkChanged()
	 * @method \string remindActualMark()
	 * @method \string requireMark()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMark()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMark()
	 * @method \string fillMark()
	 * @method \boolean getAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject setAllowChangeDeadline(\boolean|\Bitrix\Main\DB\SqlExpression $allowChangeDeadline)
	 * @method bool hasAllowChangeDeadline()
	 * @method bool isAllowChangeDeadlineFilled()
	 * @method bool isAllowChangeDeadlineChanged()
	 * @method \boolean remindActualAllowChangeDeadline()
	 * @method \boolean requireAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetAllowChangeDeadline()
	 * @method \boolean fillAllowChangeDeadline()
	 * @method \boolean getAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\TaskObject setAllowTimeTracking(\boolean|\Bitrix\Main\DB\SqlExpression $allowTimeTracking)
	 * @method bool hasAllowTimeTracking()
	 * @method bool isAllowTimeTrackingFilled()
	 * @method bool isAllowTimeTrackingChanged()
	 * @method \boolean remindActualAllowTimeTracking()
	 * @method \boolean requireAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetAllowTimeTracking()
	 * @method \boolean fillAllowTimeTracking()
	 * @method \boolean getTaskControl()
	 * @method \Bitrix\Tasks\Internals\TaskObject setTaskControl(\boolean|\Bitrix\Main\DB\SqlExpression $taskControl)
	 * @method bool hasTaskControl()
	 * @method bool isTaskControlFilled()
	 * @method bool isTaskControlChanged()
	 * @method \boolean remindActualTaskControl()
	 * @method \boolean requireTaskControl()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetTaskControl()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetTaskControl()
	 * @method \boolean fillTaskControl()
	 * @method \boolean getAddInReport()
	 * @method \Bitrix\Tasks\Internals\TaskObject setAddInReport(\boolean|\Bitrix\Main\DB\SqlExpression $addInReport)
	 * @method bool hasAddInReport()
	 * @method bool isAddInReportFilled()
	 * @method bool isAddInReportChanged()
	 * @method \boolean remindActualAddInReport()
	 * @method \boolean requireAddInReport()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetAddInReport()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetAddInReport()
	 * @method \boolean fillAddInReport()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetGroupId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \int getParentId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetParentId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetParentId()
	 * @method \int fillParentId()
	 * @method \int getForumTopicId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setForumTopicId(\int|\Bitrix\Main\DB\SqlExpression $forumTopicId)
	 * @method bool hasForumTopicId()
	 * @method bool isForumTopicIdFilled()
	 * @method bool isForumTopicIdChanged()
	 * @method \int remindActualForumTopicId()
	 * @method \int requireForumTopicId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetForumTopicId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetForumTopicId()
	 * @method \int fillForumTopicId()
	 * @method \boolean getMultitask()
	 * @method \Bitrix\Tasks\Internals\TaskObject setMultitask(\boolean|\Bitrix\Main\DB\SqlExpression $multitask)
	 * @method bool hasMultitask()
	 * @method bool isMultitaskFilled()
	 * @method bool isMultitaskChanged()
	 * @method \boolean remindActualMultitask()
	 * @method \boolean requireMultitask()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMultitask()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMultitask()
	 * @method \boolean fillMultitask()
	 * @method \string getSiteId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setSiteId(\string|\Bitrix\Main\DB\SqlExpression $siteId)
	 * @method bool hasSiteId()
	 * @method bool isSiteIdFilled()
	 * @method bool isSiteIdChanged()
	 * @method \string remindActualSiteId()
	 * @method \string requireSiteId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetSiteId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetSiteId()
	 * @method \string fillSiteId()
	 * @method \int getForkedByTemplateId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setForkedByTemplateId(\int|\Bitrix\Main\DB\SqlExpression $forkedByTemplateId)
	 * @method bool hasForkedByTemplateId()
	 * @method bool isForkedByTemplateIdFilled()
	 * @method bool isForkedByTemplateIdChanged()
	 * @method \int remindActualForkedByTemplateId()
	 * @method \int requireForkedByTemplateId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetForkedByTemplateId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetForkedByTemplateId()
	 * @method \int fillForkedByTemplateId()
	 * @method \boolean getZombie()
	 * @method \Bitrix\Tasks\Internals\TaskObject setZombie(\boolean|\Bitrix\Main\DB\SqlExpression $zombie)
	 * @method bool hasZombie()
	 * @method bool isZombieFilled()
	 * @method bool isZombieChanged()
	 * @method \boolean remindActualZombie()
	 * @method \boolean requireZombie()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetZombie()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetZombie()
	 * @method \boolean fillZombie()
	 * @method \boolean getMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\TaskObject setMatchWorkTime(\boolean|\Bitrix\Main\DB\SqlExpression $matchWorkTime)
	 * @method bool hasMatchWorkTime()
	 * @method bool isMatchWorkTimeFilled()
	 * @method bool isMatchWorkTimeChanged()
	 * @method \boolean remindActualMatchWorkTime()
	 * @method \boolean requireMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMatchWorkTime()
	 * @method \boolean fillMatchWorkTime()
	 * @method \boolean getIsRegular()
	 * @method \Bitrix\Tasks\Internals\TaskObject setIsRegular(\boolean|\Bitrix\Main\DB\SqlExpression $isRegular)
	 * @method bool hasIsRegular()
	 * @method bool isIsRegularFilled()
	 * @method bool isIsRegularChanged()
	 * @method \boolean remindActualIsRegular()
	 * @method \boolean requireIsRegular()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetIsRegular()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetIsRegular()
	 * @method \boolean fillIsRegular()
	 * @method \Bitrix\Main\EO_User getCreator()
	 * @method \Bitrix\Main\EO_User remindActualCreator()
	 * @method \Bitrix\Main\EO_User requireCreator()
	 * @method \Bitrix\Tasks\Internals\TaskObject setCreator(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetCreator()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetCreator()
	 * @method bool hasCreator()
	 * @method bool isCreatorFilled()
	 * @method bool isCreatorChanged()
	 * @method \Bitrix\Main\EO_User fillCreator()
	 * @method \Bitrix\Main\EO_User getResponsible()
	 * @method \Bitrix\Main\EO_User remindActualResponsible()
	 * @method \Bitrix\Main\EO_User requireResponsible()
	 * @method \Bitrix\Tasks\Internals\TaskObject setResponsible(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetResponsible()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetResponsible()
	 * @method bool hasResponsible()
	 * @method bool isResponsibleFilled()
	 * @method bool isResponsibleChanged()
	 * @method \Bitrix\Main\EO_User fillResponsible()
	 * @method \Bitrix\Tasks\Internals\TaskObject getParent()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualParent()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireParent()
	 * @method \Bitrix\Tasks\Internals\TaskObject setParent(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetParent()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetParent()
	 * @method bool hasParent()
	 * @method bool isParentFilled()
	 * @method bool isParentChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillParent()
	 * @method \Bitrix\Main\EO_Site getSite()
	 * @method \Bitrix\Main\EO_Site remindActualSite()
	 * @method \Bitrix\Main\EO_Site requireSite()
	 * @method \Bitrix\Tasks\Internals\TaskObject setSite(\Bitrix\Main\EO_Site $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetSite()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetSite()
	 * @method bool hasSite()
	 * @method bool isSiteFilled()
	 * @method bool isSiteChanged()
	 * @method \Bitrix\Main\EO_Site fillSite()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject getMembers()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject remindActualMembers()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject requireMembers()
	 * @method \Bitrix\Tasks\Internals\TaskObject setMembers(\Bitrix\Tasks\Internals\Task\MemberObject $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMembers()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMembers()
	 * @method bool hasMembers()
	 * @method bool isMembersFilled()
	 * @method bool isMembersChanged()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject fillMembers()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result getResults()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result remindActualResults()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result requireResults()
	 * @method \Bitrix\Tasks\Internals\TaskObject setResults(\Bitrix\Tasks\Internals\Task\Result\Result $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetResults()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetResults()
	 * @method bool hasResults()
	 * @method bool isResultsFilled()
	 * @method bool isResultsChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result fillResults()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario getScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario remindActualScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario requireScenario()
	 * @method \Bitrix\Tasks\Internals\TaskObject setScenario(\Bitrix\Tasks\Internals\Task\Scenario\Scenario $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetScenario()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetScenario()
	 * @method bool hasScenario()
	 * @method bool isScenarioFilled()
	 * @method bool isScenarioChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario fillScenario()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject getRegular()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject remindActualRegular()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject requireRegular()
	 * @method \Bitrix\Tasks\Internals\TaskObject setRegular(\Bitrix\Tasks\Internals\Task\RegularParametersObject $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetRegular()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetRegular()
	 * @method bool hasRegular()
	 * @method bool isRegularFilled()
	 * @method bool isRegularChanged()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject fillRegular()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity getGroup()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity remindActualGroup()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity requireGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject setGroup(\Bitrix\Socialnetwork\Internals\Group\GroupEntity $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetGroup()
	 * @method bool hasGroup()
	 * @method bool isGroupFilled()
	 * @method bool isGroupChanged()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity fillGroup()
	 * @method \int getOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\TaskObject setOutlookVersion(\int|\Bitrix\Main\DB\SqlExpression $outlookVersion)
	 * @method bool hasOutlookVersion()
	 * @method bool isOutlookVersionFilled()
	 * @method bool isOutlookVersionChanged()
	 * @method \int remindActualOutlookVersion()
	 * @method \int requireOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetOutlookVersion()
	 * @method \int fillOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection getMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection requireMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection fillMemberList()
	 * @method bool hasMemberList()
	 * @method bool isMemberListFilled()
	 * @method bool isMemberListChanged()
	 * @method void addToMemberList(\Bitrix\Tasks\Internals\Task\MemberObject $member)
	 * @method void removeFromMemberList(\Bitrix\Tasks\Internals\Task\MemberObject $member)
	 * @method void removeAllMemberList()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMemberList()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection getTagList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection requireTagList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection fillTagList()
	 * @method bool hasTagList()
	 * @method bool isTagListFilled()
	 * @method bool isTagListChanged()
	 * @method void addToTagList(\Bitrix\Tasks\Internals\Task\TagObject $label)
	 * @method void removeFromTagList(\Bitrix\Tasks\Internals\Task\TagObject $label)
	 * @method void removeAllTagList()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetTagList()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetTagList()
	 * @method \string getExchangeId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setExchangeId(\string|\Bitrix\Main\DB\SqlExpression $exchangeId)
	 * @method bool hasExchangeId()
	 * @method bool isExchangeIdFilled()
	 * @method bool isExchangeIdChanged()
	 * @method \string remindActualExchangeId()
	 * @method \string requireExchangeId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetExchangeId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetExchangeId()
	 * @method \string fillExchangeId()
	 * @method \string getExchangeModified()
	 * @method \Bitrix\Tasks\Internals\TaskObject setExchangeModified(\string|\Bitrix\Main\DB\SqlExpression $exchangeModified)
	 * @method bool hasExchangeModified()
	 * @method bool isExchangeModifiedFilled()
	 * @method bool isExchangeModifiedChanged()
	 * @method \string remindActualExchangeModified()
	 * @method \string requireExchangeModified()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetExchangeModified()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetExchangeModified()
	 * @method \string fillExchangeModified()
	 * @method \string getDeclineReason()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDeclineReason(\string|\Bitrix\Main\DB\SqlExpression $declineReason)
	 * @method bool hasDeclineReason()
	 * @method bool isDeclineReasonFilled()
	 * @method bool isDeclineReasonChanged()
	 * @method \string remindActualDeclineReason()
	 * @method \string requireDeclineReason()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDeclineReason()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDeclineReason()
	 * @method \string fillDeclineReason()
	 * @method \int getDeadlineCounted()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDeadlineCounted(\int|\Bitrix\Main\DB\SqlExpression $deadlineCounted)
	 * @method bool hasDeadlineCounted()
	 * @method bool isDeadlineCountedFilled()
	 * @method bool isDeadlineCountedChanged()
	 * @method \int remindActualDeadlineCounted()
	 * @method \int requireDeadlineCounted()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDeadlineCounted()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDeadlineCounted()
	 * @method \int fillDeadlineCounted()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask getUtsData()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask remindActualUtsData()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask requireUtsData()
	 * @method \Bitrix\Tasks\Internals\TaskObject setUtsData(\Bitrix\Tasks\Internals\Task\EO_UtsTasksTask $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetUtsData()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetUtsData()
	 * @method bool hasUtsData()
	 * @method bool isUtsDataFilled()
	 * @method bool isUtsDataChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask fillUtsData()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection getResult()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection requireResult()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection fillResult()
	 * @method bool hasResult()
	 * @method bool isResultFilled()
	 * @method bool isResultChanged()
	 * @method void addToResult(\Bitrix\Tasks\Internals\Task\Result\Result $result)
	 * @method void removeFromResult(\Bitrix\Tasks\Internals\Task\Result\Result $result)
	 * @method void removeAllResult()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetResult()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetResult()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList getChecklistData()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList remindActualChecklistData()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList requireChecklistData()
	 * @method \Bitrix\Tasks\Internals\TaskObject setChecklistData(\Bitrix\Tasks\Internals\Task\EO_CheckList $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetChecklistData()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetChecklistData()
	 * @method bool hasChecklistData()
	 * @method bool isChecklistDataFilled()
	 * @method bool isChecklistDataChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList fillChecklistData()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask getFlowTask()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask remindActualFlowTask()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask requireFlowTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject setFlowTask(\Bitrix\Tasks\Flow\Internal\EO_FlowTask $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetFlowTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetFlowTask()
	 * @method bool hasFlowTask()
	 * @method bool isFlowTaskFilled()
	 * @method bool isFlowTaskChanged()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask fillFlowTask()
	 * @method \string getDescriptionTr()
	 * @method \string remindActualDescriptionTr()
	 * @method \string requireDescriptionTr()
	 * @method bool hasDescriptionTr()
	 * @method bool isDescriptionTrFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDescriptionTr()
	 * @method \string fillDescriptionTr()
	 * @method \string getStatusPseudo()
	 * @method \string remindActualStatusPseudo()
	 * @method \string requireStatusPseudo()
	 * @method bool hasStatusPseudo()
	 * @method bool isStatusPseudoFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStatusPseudo()
	 * @method \string fillStatusPseudo()
	 * @method \Bitrix\Main\EO_User getCreatedByUser()
	 * @method \Bitrix\Main\EO_User remindActualCreatedByUser()
	 * @method \Bitrix\Main\EO_User requireCreatedByUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject setCreatedByUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetCreatedByUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetCreatedByUser()
	 * @method bool hasCreatedByUser()
	 * @method bool isCreatedByUserFilled()
	 * @method bool isCreatedByUserChanged()
	 * @method \Bitrix\Main\EO_User fillCreatedByUser()
	 * @method \Bitrix\Main\EO_User getChangedByUser()
	 * @method \Bitrix\Main\EO_User remindActualChangedByUser()
	 * @method \Bitrix\Main\EO_User requireChangedByUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject setChangedByUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetChangedByUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetChangedByUser()
	 * @method bool hasChangedByUser()
	 * @method bool isChangedByUserFilled()
	 * @method bool isChangedByUserChanged()
	 * @method \Bitrix\Main\EO_User fillChangedByUser()
	 * @method \Bitrix\Main\EO_User getStatusChangedByUser()
	 * @method \Bitrix\Main\EO_User remindActualStatusChangedByUser()
	 * @method \Bitrix\Main\EO_User requireStatusChangedByUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStatusChangedByUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStatusChangedByUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStatusChangedByUser()
	 * @method bool hasStatusChangedByUser()
	 * @method bool isStatusChangedByUserFilled()
	 * @method bool isStatusChangedByUserChanged()
	 * @method \Bitrix\Main\EO_User fillStatusChangedByUser()
	 * @method \Bitrix\Main\EO_User getClosedByUser()
	 * @method \Bitrix\Main\EO_User remindActualClosedByUser()
	 * @method \Bitrix\Main\EO_User requireClosedByUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject setClosedByUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetClosedByUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetClosedByUser()
	 * @method bool hasClosedByUser()
	 * @method bool isClosedByUserFilled()
	 * @method bool isClosedByUserChanged()
	 * @method \Bitrix\Main\EO_User fillClosedByUser()
	 * @method \int getTimeSpentInLogs()
	 * @method \int remindActualTimeSpentInLogs()
	 * @method \int requireTimeSpentInLogs()
	 * @method bool hasTimeSpentInLogs()
	 * @method bool isTimeSpentInLogsFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetTimeSpentInLogs()
	 * @method \int fillTimeSpentInLogs()
	 * @method \int getDuration()
	 * @method \int remindActualDuration()
	 * @method \int requireDuration()
	 * @method bool hasDuration()
	 * @method bool isDurationFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDuration()
	 * @method \int fillDuration()
	 * @method \int getDurationPlanMinutes()
	 * @method \int remindActualDurationPlanMinutes()
	 * @method \int requireDurationPlanMinutes()
	 * @method bool hasDurationPlanMinutes()
	 * @method bool isDurationPlanMinutesFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDurationPlanMinutes()
	 * @method \int fillDurationPlanMinutes()
	 * @method \int getDurationPlanHours()
	 * @method \int remindActualDurationPlanHours()
	 * @method \int requireDurationPlanHours()
	 * @method bool hasDurationPlanHours()
	 * @method bool isDurationPlanHoursFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDurationPlanHours()
	 * @method \int fillDurationPlanHours()
	 * @method \boolean getIsOverdue()
	 * @method \boolean remindActualIsOverdue()
	 * @method \boolean requireIsOverdue()
	 * @method bool hasIsOverdue()
	 * @method bool isIsOverdueFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetIsOverdue()
	 * @method \boolean fillIsOverdue()
	 * @method \int getIsOverduePrcnt()
	 * @method \int remindActualIsOverduePrcnt()
	 * @method \int requireIsOverduePrcnt()
	 * @method bool hasIsOverduePrcnt()
	 * @method bool isIsOverduePrcntFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetIsOverduePrcnt()
	 * @method \int fillIsOverduePrcnt()
	 * @method \boolean getIsMarked()
	 * @method \boolean remindActualIsMarked()
	 * @method \boolean requireIsMarked()
	 * @method bool hasIsMarked()
	 * @method bool isIsMarkedFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetIsMarked()
	 * @method \boolean fillIsMarked()
	 * @method \int getIsMarkedPrcnt()
	 * @method \int remindActualIsMarkedPrcnt()
	 * @method \int requireIsMarkedPrcnt()
	 * @method bool hasIsMarkedPrcnt()
	 * @method bool isIsMarkedPrcntFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetIsMarkedPrcnt()
	 * @method \int fillIsMarkedPrcnt()
	 * @method \boolean getIsEffective()
	 * @method \boolean remindActualIsEffective()
	 * @method \boolean requireIsEffective()
	 * @method bool hasIsEffective()
	 * @method bool isIsEffectiveFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetIsEffective()
	 * @method \boolean fillIsEffective()
	 * @method \int getIsEffectivePrcnt()
	 * @method \int remindActualIsEffectivePrcnt()
	 * @method \int requireIsEffectivePrcnt()
	 * @method bool hasIsEffectivePrcnt()
	 * @method bool isIsEffectivePrcntFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetIsEffectivePrcnt()
	 * @method \int fillIsEffectivePrcnt()
	 * @method \boolean getIsRunning()
	 * @method \boolean remindActualIsRunning()
	 * @method \boolean requireIsRunning()
	 * @method bool hasIsRunning()
	 * @method bool isIsRunningFilled()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetIsRunning()
	 * @method \boolean fillIsRunning()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\TaskObject set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\TaskObject reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\TaskObject unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\TaskObject wakeUp($data)
	 */
	class EO_Task {
		/* @var \Bitrix\Tasks\Integration\Report\Internals\TaskTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Report\Internals\TaskTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Integration\Report\Internals {
	/**
	 * TaskCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \boolean[] getDescriptionInBbcodeList()
	 * @method \boolean[] fillDescriptionInBbcode()
	 * @method \string[] getPriorityList()
	 * @method \string[] fillPriority()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \int[] getStageIdList()
	 * @method \int[] fillStageId()
	 * @method \int[] getResponsibleIdList()
	 * @method \int[] fillResponsibleId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStart()
	 * @method \int[] getDurationPlanList()
	 * @method \int[] fillDurationPlan()
	 * @method \int[] getDurationFactList()
	 * @method \int[] fillDurationFact()
	 * @method \string[] getDurationTypeList()
	 * @method \string[] fillDurationType()
	 * @method \int[] getTimeEstimateList()
	 * @method \int[] fillTimeEstimate()
	 * @method \boolean[] getReplicateList()
	 * @method \boolean[] fillReplicate()
	 * @method \Bitrix\Main\Type\DateTime[] getDeadlineList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDeadline()
	 * @method \Bitrix\Main\Type\DateTime[] getStartDatePlanList()
	 * @method \Bitrix\Main\Type\DateTime[] fillStartDatePlan()
	 * @method \Bitrix\Main\Type\DateTime[] getEndDatePlanList()
	 * @method \Bitrix\Main\Type\DateTime[] fillEndDatePlan()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedDate()
	 * @method \int[] getChangedByList()
	 * @method \int[] fillChangedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getChangedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillChangedDate()
	 * @method \int[] getStatusChangedByList()
	 * @method \int[] fillStatusChangedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getStatusChangedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillStatusChangedDate()
	 * @method \int[] getClosedByList()
	 * @method \int[] fillClosedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getClosedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillClosedDate()
	 * @method \Bitrix\Main\Type\DateTime[] getActivityDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillActivityDate()
	 * @method \string[] getGuidList()
	 * @method \string[] fillGuid()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 * @method \string[] getMarkList()
	 * @method \string[] fillMark()
	 * @method \boolean[] getAllowChangeDeadlineList()
	 * @method \boolean[] fillAllowChangeDeadline()
	 * @method \boolean[] getAllowTimeTrackingList()
	 * @method \boolean[] fillAllowTimeTracking()
	 * @method \boolean[] getTaskControlList()
	 * @method \boolean[] fillTaskControl()
	 * @method \boolean[] getAddInReportList()
	 * @method \boolean[] fillAddInReport()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \int[] getForumTopicIdList()
	 * @method \int[] fillForumTopicId()
	 * @method \boolean[] getMultitaskList()
	 * @method \boolean[] fillMultitask()
	 * @method \string[] getSiteIdList()
	 * @method \string[] fillSiteId()
	 * @method \int[] getForkedByTemplateIdList()
	 * @method \int[] fillForkedByTemplateId()
	 * @method \boolean[] getZombieList()
	 * @method \boolean[] fillZombie()
	 * @method \boolean[] getMatchWorkTimeList()
	 * @method \boolean[] fillMatchWorkTime()
	 * @method \boolean[] getIsRegularList()
	 * @method \boolean[] fillIsRegular()
	 * @method \Bitrix\Main\EO_User[] getCreatorList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getCreatorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreator()
	 * @method \Bitrix\Main\EO_User[] getResponsibleList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getResponsibleCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillResponsible()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getParentList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getParentCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillParent()
	 * @method \Bitrix\Main\EO_Site[] getSiteList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getSiteCollection()
	 * @method \Bitrix\Main\EO_Site_Collection fillSite()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject[] getMembersList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getMembersCollection()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection fillMembers()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result[] getResultsList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getResultsCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection fillResults()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario[] getScenarioList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getScenarioCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection fillScenario()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject[] getRegularList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getRegularCollection()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersCollection fillRegular()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity[] getGroupList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getGroupCollection()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntityCollection fillGroup()
	 * @method \int[] getOutlookVersionList()
	 * @method \int[] fillOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection[] getMemberListList()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection getMemberListCollection()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection fillMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection[] getTagListList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection getTagListCollection()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection fillTagList()
	 * @method \string[] getExchangeIdList()
	 * @method \string[] fillExchangeId()
	 * @method \string[] getExchangeModifiedList()
	 * @method \string[] fillExchangeModified()
	 * @method \string[] getDeclineReasonList()
	 * @method \string[] fillDeclineReason()
	 * @method \int[] getDeadlineCountedList()
	 * @method \int[] fillDeadlineCounted()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask[] getUtsDataList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getUtsDataCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection fillUtsData()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection[] getResultList()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection getResultCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection fillResult()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList[] getChecklistDataList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getChecklistDataCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection fillChecklistData()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask[] getFlowTaskList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getFlowTaskCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection fillFlowTask()
	 * @method \string[] getDescriptionTrList()
	 * @method \string[] fillDescriptionTr()
	 * @method \string[] getStatusPseudoList()
	 * @method \string[] fillStatusPseudo()
	 * @method \Bitrix\Main\EO_User[] getCreatedByUserList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getCreatedByUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreatedByUser()
	 * @method \Bitrix\Main\EO_User[] getChangedByUserList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getChangedByUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillChangedByUser()
	 * @method \Bitrix\Main\EO_User[] getStatusChangedByUserList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getStatusChangedByUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillStatusChangedByUser()
	 * @method \Bitrix\Main\EO_User[] getClosedByUserList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getClosedByUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillClosedByUser()
	 * @method \int[] getTimeSpentInLogsList()
	 * @method \int[] fillTimeSpentInLogs()
	 * @method \int[] getDurationList()
	 * @method \int[] fillDuration()
	 * @method \int[] getDurationPlanMinutesList()
	 * @method \int[] fillDurationPlanMinutes()
	 * @method \int[] getDurationPlanHoursList()
	 * @method \int[] fillDurationPlanHours()
	 * @method \boolean[] getIsOverdueList()
	 * @method \boolean[] fillIsOverdue()
	 * @method \int[] getIsOverduePrcntList()
	 * @method \int[] fillIsOverduePrcnt()
	 * @method \boolean[] getIsMarkedList()
	 * @method \boolean[] fillIsMarked()
	 * @method \int[] getIsMarkedPrcntList()
	 * @method \int[] fillIsMarkedPrcnt()
	 * @method \boolean[] getIsEffectiveList()
	 * @method \boolean[] fillIsEffective()
	 * @method \int[] getIsEffectivePrcntList()
	 * @method \int[] fillIsEffectivePrcnt()
	 * @method \boolean[] getIsRunningList()
	 * @method \boolean[] fillIsRunning()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method bool has(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\TaskObject getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\TaskCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\TaskObject current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\TaskCollection merge(?\Bitrix\Tasks\Internals\TaskCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Task_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Integration\Report\Internals\TaskTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Report\Internals\TaskTable';
	}
}
namespace Bitrix\Tasks\Integration\Report\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Task_Result exec()
	 * @method \Bitrix\Tasks\Internals\TaskObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fetchCollection()
	 */
	class EO_Task_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\TaskObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fetchCollection()
	 */
	class EO_Task_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\TaskObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\TaskCollection createCollection()
	 * @method \Bitrix\Tasks\Internals\TaskObject wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\TaskCollection wakeUpCollection($rows)
	 */
	class EO_Task_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\TaskTable:tasks/lib/internals/task.php */
namespace Bitrix\Tasks\Internals {
	/**
	 * TaskObject
	 * @see \Bitrix\Tasks\Internals\TaskTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\Tasks\Internals\TaskObject setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetTitle()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getDescription()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDescription()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDescription()
	 * @method \string fillDescription()
	 * @method \boolean getDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDescriptionInBbcode(\boolean|\Bitrix\Main\DB\SqlExpression $descriptionInBbcode)
	 * @method bool hasDescriptionInBbcode()
	 * @method bool isDescriptionInBbcodeFilled()
	 * @method bool isDescriptionInBbcodeChanged()
	 * @method \boolean remindActualDescriptionInBbcode()
	 * @method \boolean requireDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDescriptionInBbcode()
	 * @method \boolean fillDescriptionInBbcode()
	 * @method \string getPriority()
	 * @method \Bitrix\Tasks\Internals\TaskObject setPriority(\string|\Bitrix\Main\DB\SqlExpression $priority)
	 * @method bool hasPriority()
	 * @method bool isPriorityFilled()
	 * @method bool isPriorityChanged()
	 * @method \string remindActualPriority()
	 * @method \string requirePriority()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetPriority()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetPriority()
	 * @method \string fillPriority()
	 * @method \string getStatus()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStatus()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStatus()
	 * @method \string fillStatus()
	 * @method \int getStageId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStageId(\int|\Bitrix\Main\DB\SqlExpression $stageId)
	 * @method bool hasStageId()
	 * @method bool isStageIdFilled()
	 * @method bool isStageIdChanged()
	 * @method \int remindActualStageId()
	 * @method \int requireStageId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStageId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStageId()
	 * @method \int fillStageId()
	 * @method \int getResponsibleId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setResponsibleId(\int|\Bitrix\Main\DB\SqlExpression $responsibleId)
	 * @method bool hasResponsibleId()
	 * @method bool isResponsibleIdFilled()
	 * @method bool isResponsibleIdChanged()
	 * @method \int remindActualResponsibleId()
	 * @method \int requireResponsibleId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetResponsibleId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetResponsibleId()
	 * @method \int fillResponsibleId()
	 * @method \Bitrix\Main\Type\DateTime getDateStart()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDateStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStart)
	 * @method bool hasDateStart()
	 * @method bool isDateStartFilled()
	 * @method bool isDateStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStart()
	 * @method \Bitrix\Main\Type\DateTime requireDateStart()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDateStart()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDateStart()
	 * @method \Bitrix\Main\Type\DateTime fillDateStart()
	 * @method \int getDurationPlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDurationPlan(\int|\Bitrix\Main\DB\SqlExpression $durationPlan)
	 * @method bool hasDurationPlan()
	 * @method bool isDurationPlanFilled()
	 * @method bool isDurationPlanChanged()
	 * @method \int remindActualDurationPlan()
	 * @method \int requireDurationPlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDurationPlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDurationPlan()
	 * @method \int fillDurationPlan()
	 * @method \int getDurationFact()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDurationFact(\int|\Bitrix\Main\DB\SqlExpression $durationFact)
	 * @method bool hasDurationFact()
	 * @method bool isDurationFactFilled()
	 * @method bool isDurationFactChanged()
	 * @method \int remindActualDurationFact()
	 * @method \int requireDurationFact()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDurationFact()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDurationFact()
	 * @method \int fillDurationFact()
	 * @method \string getDurationType()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDurationType(\string|\Bitrix\Main\DB\SqlExpression $durationType)
	 * @method bool hasDurationType()
	 * @method bool isDurationTypeFilled()
	 * @method bool isDurationTypeChanged()
	 * @method \string remindActualDurationType()
	 * @method \string requireDurationType()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDurationType()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDurationType()
	 * @method \string fillDurationType()
	 * @method \int getTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setTimeEstimate(\int|\Bitrix\Main\DB\SqlExpression $timeEstimate)
	 * @method bool hasTimeEstimate()
	 * @method bool isTimeEstimateFilled()
	 * @method bool isTimeEstimateChanged()
	 * @method \int remindActualTimeEstimate()
	 * @method \int requireTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetTimeEstimate()
	 * @method \int fillTimeEstimate()
	 * @method \boolean getReplicate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setReplicate(\boolean|\Bitrix\Main\DB\SqlExpression $replicate)
	 * @method bool hasReplicate()
	 * @method bool isReplicateFilled()
	 * @method bool isReplicateChanged()
	 * @method \boolean remindActualReplicate()
	 * @method \boolean requireReplicate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetReplicate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetReplicate()
	 * @method \boolean fillReplicate()
	 * @method \Bitrix\Main\Type\DateTime getDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDeadline(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $deadline)
	 * @method bool hasDeadline()
	 * @method bool isDeadlineFilled()
	 * @method bool isDeadlineChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDeadline()
	 * @method \Bitrix\Main\Type\DateTime requireDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDeadline()
	 * @method \Bitrix\Main\Type\DateTime fillDeadline()
	 * @method \Bitrix\Main\Type\DateTime getStartDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStartDatePlan(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $startDatePlan)
	 * @method bool hasStartDatePlan()
	 * @method bool isStartDatePlanFilled()
	 * @method bool isStartDatePlanChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualStartDatePlan()
	 * @method \Bitrix\Main\Type\DateTime requireStartDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStartDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStartDatePlan()
	 * @method \Bitrix\Main\Type\DateTime fillStartDatePlan()
	 * @method \Bitrix\Main\Type\DateTime getEndDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject setEndDatePlan(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $endDatePlan)
	 * @method bool hasEndDatePlan()
	 * @method bool isEndDatePlanFilled()
	 * @method bool isEndDatePlanChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualEndDatePlan()
	 * @method \Bitrix\Main\Type\DateTime requireEndDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetEndDatePlan()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetEndDatePlan()
	 * @method \Bitrix\Main\Type\DateTime fillEndDatePlan()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetCreatedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setCreatedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdDate)
	 * @method bool hasCreatedDate()
	 * @method bool isCreatedDateFilled()
	 * @method bool isCreatedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetCreatedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedDate()
	 * @method \int getChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject setChangedBy(\int|\Bitrix\Main\DB\SqlExpression $changedBy)
	 * @method bool hasChangedBy()
	 * @method bool isChangedByFilled()
	 * @method bool isChangedByChanged()
	 * @method \int remindActualChangedBy()
	 * @method \int requireChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetChangedBy()
	 * @method \int fillChangedBy()
	 * @method \Bitrix\Main\Type\DateTime getChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setChangedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $changedDate)
	 * @method bool hasChangedDate()
	 * @method bool isChangedDateFilled()
	 * @method bool isChangedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualChangedDate()
	 * @method \Bitrix\Main\Type\DateTime requireChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetChangedDate()
	 * @method \Bitrix\Main\Type\DateTime fillChangedDate()
	 * @method \int getStatusChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStatusChangedBy(\int|\Bitrix\Main\DB\SqlExpression $statusChangedBy)
	 * @method bool hasStatusChangedBy()
	 * @method bool isStatusChangedByFilled()
	 * @method bool isStatusChangedByChanged()
	 * @method \int remindActualStatusChangedBy()
	 * @method \int requireStatusChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStatusChangedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStatusChangedBy()
	 * @method \int fillStatusChangedBy()
	 * @method \Bitrix\Main\Type\DateTime getStatusChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setStatusChangedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $statusChangedDate)
	 * @method bool hasStatusChangedDate()
	 * @method bool isStatusChangedDateFilled()
	 * @method bool isStatusChangedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualStatusChangedDate()
	 * @method \Bitrix\Main\Type\DateTime requireStatusChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetStatusChangedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetStatusChangedDate()
	 * @method \Bitrix\Main\Type\DateTime fillStatusChangedDate()
	 * @method \int getClosedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject setClosedBy(\int|\Bitrix\Main\DB\SqlExpression $closedBy)
	 * @method bool hasClosedBy()
	 * @method bool isClosedByFilled()
	 * @method bool isClosedByChanged()
	 * @method \int remindActualClosedBy()
	 * @method \int requireClosedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetClosedBy()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetClosedBy()
	 * @method \int fillClosedBy()
	 * @method \Bitrix\Main\Type\DateTime getClosedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setClosedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $closedDate)
	 * @method bool hasClosedDate()
	 * @method bool isClosedDateFilled()
	 * @method bool isClosedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualClosedDate()
	 * @method \Bitrix\Main\Type\DateTime requireClosedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetClosedDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetClosedDate()
	 * @method \Bitrix\Main\Type\DateTime fillClosedDate()
	 * @method \Bitrix\Main\Type\DateTime getActivityDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject setActivityDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $activityDate)
	 * @method bool hasActivityDate()
	 * @method bool isActivityDateFilled()
	 * @method bool isActivityDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualActivityDate()
	 * @method \Bitrix\Main\Type\DateTime requireActivityDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetActivityDate()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetActivityDate()
	 * @method \Bitrix\Main\Type\DateTime fillActivityDate()
	 * @method \string getGuid()
	 * @method \Bitrix\Tasks\Internals\TaskObject setGuid(\string|\Bitrix\Main\DB\SqlExpression $guid)
	 * @method bool hasGuid()
	 * @method bool isGuidFilled()
	 * @method bool isGuidChanged()
	 * @method \string remindActualGuid()
	 * @method \string requireGuid()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetGuid()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetGuid()
	 * @method \string fillGuid()
	 * @method \string getXmlId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetXmlId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \string getMark()
	 * @method \Bitrix\Tasks\Internals\TaskObject setMark(\string|\Bitrix\Main\DB\SqlExpression $mark)
	 * @method bool hasMark()
	 * @method bool isMarkFilled()
	 * @method bool isMarkChanged()
	 * @method \string remindActualMark()
	 * @method \string requireMark()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMark()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMark()
	 * @method \string fillMark()
	 * @method \boolean getAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject setAllowChangeDeadline(\boolean|\Bitrix\Main\DB\SqlExpression $allowChangeDeadline)
	 * @method bool hasAllowChangeDeadline()
	 * @method bool isAllowChangeDeadlineFilled()
	 * @method bool isAllowChangeDeadlineChanged()
	 * @method \boolean remindActualAllowChangeDeadline()
	 * @method \boolean requireAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetAllowChangeDeadline()
	 * @method \boolean fillAllowChangeDeadline()
	 * @method \boolean getAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\TaskObject setAllowTimeTracking(\boolean|\Bitrix\Main\DB\SqlExpression $allowTimeTracking)
	 * @method bool hasAllowTimeTracking()
	 * @method bool isAllowTimeTrackingFilled()
	 * @method bool isAllowTimeTrackingChanged()
	 * @method \boolean remindActualAllowTimeTracking()
	 * @method \boolean requireAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetAllowTimeTracking()
	 * @method \boolean fillAllowTimeTracking()
	 * @method \boolean getTaskControl()
	 * @method \Bitrix\Tasks\Internals\TaskObject setTaskControl(\boolean|\Bitrix\Main\DB\SqlExpression $taskControl)
	 * @method bool hasTaskControl()
	 * @method bool isTaskControlFilled()
	 * @method bool isTaskControlChanged()
	 * @method \boolean remindActualTaskControl()
	 * @method \boolean requireTaskControl()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetTaskControl()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetTaskControl()
	 * @method \boolean fillTaskControl()
	 * @method \boolean getAddInReport()
	 * @method \Bitrix\Tasks\Internals\TaskObject setAddInReport(\boolean|\Bitrix\Main\DB\SqlExpression $addInReport)
	 * @method bool hasAddInReport()
	 * @method bool isAddInReportFilled()
	 * @method bool isAddInReportChanged()
	 * @method \boolean remindActualAddInReport()
	 * @method \boolean requireAddInReport()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetAddInReport()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetAddInReport()
	 * @method \boolean fillAddInReport()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetGroupId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \int getParentId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetParentId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetParentId()
	 * @method \int fillParentId()
	 * @method \int getForumTopicId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setForumTopicId(\int|\Bitrix\Main\DB\SqlExpression $forumTopicId)
	 * @method bool hasForumTopicId()
	 * @method bool isForumTopicIdFilled()
	 * @method bool isForumTopicIdChanged()
	 * @method \int remindActualForumTopicId()
	 * @method \int requireForumTopicId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetForumTopicId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetForumTopicId()
	 * @method \int fillForumTopicId()
	 * @method \boolean getMultitask()
	 * @method \Bitrix\Tasks\Internals\TaskObject setMultitask(\boolean|\Bitrix\Main\DB\SqlExpression $multitask)
	 * @method bool hasMultitask()
	 * @method bool isMultitaskFilled()
	 * @method bool isMultitaskChanged()
	 * @method \boolean remindActualMultitask()
	 * @method \boolean requireMultitask()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMultitask()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMultitask()
	 * @method \boolean fillMultitask()
	 * @method \string getSiteId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setSiteId(\string|\Bitrix\Main\DB\SqlExpression $siteId)
	 * @method bool hasSiteId()
	 * @method bool isSiteIdFilled()
	 * @method bool isSiteIdChanged()
	 * @method \string remindActualSiteId()
	 * @method \string requireSiteId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetSiteId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetSiteId()
	 * @method \string fillSiteId()
	 * @method \int getForkedByTemplateId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setForkedByTemplateId(\int|\Bitrix\Main\DB\SqlExpression $forkedByTemplateId)
	 * @method bool hasForkedByTemplateId()
	 * @method bool isForkedByTemplateIdFilled()
	 * @method bool isForkedByTemplateIdChanged()
	 * @method \int remindActualForkedByTemplateId()
	 * @method \int requireForkedByTemplateId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetForkedByTemplateId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetForkedByTemplateId()
	 * @method \int fillForkedByTemplateId()
	 * @method \boolean getZombie()
	 * @method \Bitrix\Tasks\Internals\TaskObject setZombie(\boolean|\Bitrix\Main\DB\SqlExpression $zombie)
	 * @method bool hasZombie()
	 * @method bool isZombieFilled()
	 * @method bool isZombieChanged()
	 * @method \boolean remindActualZombie()
	 * @method \boolean requireZombie()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetZombie()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetZombie()
	 * @method \boolean fillZombie()
	 * @method \boolean getMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\TaskObject setMatchWorkTime(\boolean|\Bitrix\Main\DB\SqlExpression $matchWorkTime)
	 * @method bool hasMatchWorkTime()
	 * @method bool isMatchWorkTimeFilled()
	 * @method bool isMatchWorkTimeChanged()
	 * @method \boolean remindActualMatchWorkTime()
	 * @method \boolean requireMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMatchWorkTime()
	 * @method \boolean fillMatchWorkTime()
	 * @method \boolean getIsRegular()
	 * @method \Bitrix\Tasks\Internals\TaskObject setIsRegular(\boolean|\Bitrix\Main\DB\SqlExpression $isRegular)
	 * @method bool hasIsRegular()
	 * @method bool isIsRegularFilled()
	 * @method bool isIsRegularChanged()
	 * @method \boolean remindActualIsRegular()
	 * @method \boolean requireIsRegular()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetIsRegular()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetIsRegular()
	 * @method \boolean fillIsRegular()
	 * @method \Bitrix\Main\EO_User getCreator()
	 * @method \Bitrix\Main\EO_User remindActualCreator()
	 * @method \Bitrix\Main\EO_User requireCreator()
	 * @method \Bitrix\Tasks\Internals\TaskObject setCreator(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetCreator()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetCreator()
	 * @method bool hasCreator()
	 * @method bool isCreatorFilled()
	 * @method bool isCreatorChanged()
	 * @method \Bitrix\Main\EO_User fillCreator()
	 * @method \Bitrix\Main\EO_User getResponsible()
	 * @method \Bitrix\Main\EO_User remindActualResponsible()
	 * @method \Bitrix\Main\EO_User requireResponsible()
	 * @method \Bitrix\Tasks\Internals\TaskObject setResponsible(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetResponsible()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetResponsible()
	 * @method bool hasResponsible()
	 * @method bool isResponsibleFilled()
	 * @method bool isResponsibleChanged()
	 * @method \Bitrix\Main\EO_User fillResponsible()
	 * @method \Bitrix\Tasks\Internals\TaskObject getParent()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualParent()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireParent()
	 * @method \Bitrix\Tasks\Internals\TaskObject setParent(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetParent()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetParent()
	 * @method bool hasParent()
	 * @method bool isParentFilled()
	 * @method bool isParentChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillParent()
	 * @method \Bitrix\Main\EO_Site getSite()
	 * @method \Bitrix\Main\EO_Site remindActualSite()
	 * @method \Bitrix\Main\EO_Site requireSite()
	 * @method \Bitrix\Tasks\Internals\TaskObject setSite(\Bitrix\Main\EO_Site $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetSite()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetSite()
	 * @method bool hasSite()
	 * @method bool isSiteFilled()
	 * @method bool isSiteChanged()
	 * @method \Bitrix\Main\EO_Site fillSite()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject getMembers()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject remindActualMembers()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject requireMembers()
	 * @method \Bitrix\Tasks\Internals\TaskObject setMembers(\Bitrix\Tasks\Internals\Task\MemberObject $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMembers()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMembers()
	 * @method bool hasMembers()
	 * @method bool isMembersFilled()
	 * @method bool isMembersChanged()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject fillMembers()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result getResults()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result remindActualResults()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result requireResults()
	 * @method \Bitrix\Tasks\Internals\TaskObject setResults(\Bitrix\Tasks\Internals\Task\Result\Result $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetResults()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetResults()
	 * @method bool hasResults()
	 * @method bool isResultsFilled()
	 * @method bool isResultsChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result fillResults()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario getScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario remindActualScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario requireScenario()
	 * @method \Bitrix\Tasks\Internals\TaskObject setScenario(\Bitrix\Tasks\Internals\Task\Scenario\Scenario $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetScenario()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetScenario()
	 * @method bool hasScenario()
	 * @method bool isScenarioFilled()
	 * @method bool isScenarioChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario fillScenario()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject getRegular()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject remindActualRegular()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject requireRegular()
	 * @method \Bitrix\Tasks\Internals\TaskObject setRegular(\Bitrix\Tasks\Internals\Task\RegularParametersObject $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetRegular()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetRegular()
	 * @method bool hasRegular()
	 * @method bool isRegularFilled()
	 * @method bool isRegularChanged()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject fillRegular()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity getGroup()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity remindActualGroup()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity requireGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject setGroup(\Bitrix\Socialnetwork\Internals\Group\GroupEntity $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetGroup()
	 * @method bool hasGroup()
	 * @method bool isGroupFilled()
	 * @method bool isGroupChanged()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity fillGroup()
	 * @method \int getOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\TaskObject setOutlookVersion(\int|\Bitrix\Main\DB\SqlExpression $outlookVersion)
	 * @method bool hasOutlookVersion()
	 * @method bool isOutlookVersionFilled()
	 * @method bool isOutlookVersionChanged()
	 * @method \int remindActualOutlookVersion()
	 * @method \int requireOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetOutlookVersion()
	 * @method \int fillOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection getMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection requireMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection fillMemberList()
	 * @method bool hasMemberList()
	 * @method bool isMemberListFilled()
	 * @method bool isMemberListChanged()
	 * @method void addToMemberList(\Bitrix\Tasks\Internals\Task\MemberObject $member)
	 * @method void removeFromMemberList(\Bitrix\Tasks\Internals\Task\MemberObject $member)
	 * @method void removeAllMemberList()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMemberList()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection getTagList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection requireTagList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection fillTagList()
	 * @method bool hasTagList()
	 * @method bool isTagListFilled()
	 * @method bool isTagListChanged()
	 * @method void addToTagList(\Bitrix\Tasks\Internals\Task\TagObject $label)
	 * @method void removeFromTagList(\Bitrix\Tasks\Internals\Task\TagObject $label)
	 * @method void removeAllTagList()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetTagList()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetTagList()
	 * @method \string getExchangeId()
	 * @method \Bitrix\Tasks\Internals\TaskObject setExchangeId(\string|\Bitrix\Main\DB\SqlExpression $exchangeId)
	 * @method bool hasExchangeId()
	 * @method bool isExchangeIdFilled()
	 * @method bool isExchangeIdChanged()
	 * @method \string remindActualExchangeId()
	 * @method \string requireExchangeId()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetExchangeId()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetExchangeId()
	 * @method \string fillExchangeId()
	 * @method \string getExchangeModified()
	 * @method \Bitrix\Tasks\Internals\TaskObject setExchangeModified(\string|\Bitrix\Main\DB\SqlExpression $exchangeModified)
	 * @method bool hasExchangeModified()
	 * @method bool isExchangeModifiedFilled()
	 * @method bool isExchangeModifiedChanged()
	 * @method \string remindActualExchangeModified()
	 * @method \string requireExchangeModified()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetExchangeModified()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetExchangeModified()
	 * @method \string fillExchangeModified()
	 * @method \string getDeclineReason()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDeclineReason(\string|\Bitrix\Main\DB\SqlExpression $declineReason)
	 * @method bool hasDeclineReason()
	 * @method bool isDeclineReasonFilled()
	 * @method bool isDeclineReasonChanged()
	 * @method \string remindActualDeclineReason()
	 * @method \string requireDeclineReason()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDeclineReason()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDeclineReason()
	 * @method \string fillDeclineReason()
	 * @method \int getDeadlineCounted()
	 * @method \Bitrix\Tasks\Internals\TaskObject setDeadlineCounted(\int|\Bitrix\Main\DB\SqlExpression $deadlineCounted)
	 * @method bool hasDeadlineCounted()
	 * @method bool isDeadlineCountedFilled()
	 * @method bool isDeadlineCountedChanged()
	 * @method \int remindActualDeadlineCounted()
	 * @method \int requireDeadlineCounted()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetDeadlineCounted()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetDeadlineCounted()
	 * @method \int fillDeadlineCounted()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask getUtsData()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask remindActualUtsData()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask requireUtsData()
	 * @method \Bitrix\Tasks\Internals\TaskObject setUtsData(\Bitrix\Tasks\Internals\Task\EO_UtsTasksTask $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetUtsData()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetUtsData()
	 * @method bool hasUtsData()
	 * @method bool isUtsDataFilled()
	 * @method bool isUtsDataChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask fillUtsData()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection getResult()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection requireResult()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection fillResult()
	 * @method bool hasResult()
	 * @method bool isResultFilled()
	 * @method bool isResultChanged()
	 * @method void addToResult(\Bitrix\Tasks\Internals\Task\Result\Result $result)
	 * @method void removeFromResult(\Bitrix\Tasks\Internals\Task\Result\Result $result)
	 * @method void removeAllResult()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetResult()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetResult()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList getChecklistData()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList remindActualChecklistData()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList requireChecklistData()
	 * @method \Bitrix\Tasks\Internals\TaskObject setChecklistData(\Bitrix\Tasks\Internals\Task\EO_CheckList $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetChecklistData()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetChecklistData()
	 * @method bool hasChecklistData()
	 * @method bool isChecklistDataFilled()
	 * @method bool isChecklistDataChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList fillChecklistData()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask getFlowTask()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask remindActualFlowTask()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask requireFlowTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject setFlowTask(\Bitrix\Tasks\Flow\Internal\EO_FlowTask $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetFlowTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetFlowTask()
	 * @method bool hasFlowTask()
	 * @method bool isFlowTaskFilled()
	 * @method bool isFlowTaskChanged()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask fillFlowTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\TaskObject set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\TaskObject reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\TaskObject unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\TaskObject wakeUp($data)
	 */
	class EO_Task {
		/* @var \Bitrix\Tasks\Internals\TaskTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\TaskTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals {
	/**
	 * TaskCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \boolean[] getDescriptionInBbcodeList()
	 * @method \boolean[] fillDescriptionInBbcode()
	 * @method \string[] getPriorityList()
	 * @method \string[] fillPriority()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \int[] getStageIdList()
	 * @method \int[] fillStageId()
	 * @method \int[] getResponsibleIdList()
	 * @method \int[] fillResponsibleId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStart()
	 * @method \int[] getDurationPlanList()
	 * @method \int[] fillDurationPlan()
	 * @method \int[] getDurationFactList()
	 * @method \int[] fillDurationFact()
	 * @method \string[] getDurationTypeList()
	 * @method \string[] fillDurationType()
	 * @method \int[] getTimeEstimateList()
	 * @method \int[] fillTimeEstimate()
	 * @method \boolean[] getReplicateList()
	 * @method \boolean[] fillReplicate()
	 * @method \Bitrix\Main\Type\DateTime[] getDeadlineList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDeadline()
	 * @method \Bitrix\Main\Type\DateTime[] getStartDatePlanList()
	 * @method \Bitrix\Main\Type\DateTime[] fillStartDatePlan()
	 * @method \Bitrix\Main\Type\DateTime[] getEndDatePlanList()
	 * @method \Bitrix\Main\Type\DateTime[] fillEndDatePlan()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedDate()
	 * @method \int[] getChangedByList()
	 * @method \int[] fillChangedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getChangedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillChangedDate()
	 * @method \int[] getStatusChangedByList()
	 * @method \int[] fillStatusChangedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getStatusChangedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillStatusChangedDate()
	 * @method \int[] getClosedByList()
	 * @method \int[] fillClosedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getClosedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillClosedDate()
	 * @method \Bitrix\Main\Type\DateTime[] getActivityDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillActivityDate()
	 * @method \string[] getGuidList()
	 * @method \string[] fillGuid()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 * @method \string[] getMarkList()
	 * @method \string[] fillMark()
	 * @method \boolean[] getAllowChangeDeadlineList()
	 * @method \boolean[] fillAllowChangeDeadline()
	 * @method \boolean[] getAllowTimeTrackingList()
	 * @method \boolean[] fillAllowTimeTracking()
	 * @method \boolean[] getTaskControlList()
	 * @method \boolean[] fillTaskControl()
	 * @method \boolean[] getAddInReportList()
	 * @method \boolean[] fillAddInReport()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \int[] getForumTopicIdList()
	 * @method \int[] fillForumTopicId()
	 * @method \boolean[] getMultitaskList()
	 * @method \boolean[] fillMultitask()
	 * @method \string[] getSiteIdList()
	 * @method \string[] fillSiteId()
	 * @method \int[] getForkedByTemplateIdList()
	 * @method \int[] fillForkedByTemplateId()
	 * @method \boolean[] getZombieList()
	 * @method \boolean[] fillZombie()
	 * @method \boolean[] getMatchWorkTimeList()
	 * @method \boolean[] fillMatchWorkTime()
	 * @method \boolean[] getIsRegularList()
	 * @method \boolean[] fillIsRegular()
	 * @method \Bitrix\Main\EO_User[] getCreatorList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getCreatorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreator()
	 * @method \Bitrix\Main\EO_User[] getResponsibleList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getResponsibleCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillResponsible()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getParentList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getParentCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillParent()
	 * @method \Bitrix\Main\EO_Site[] getSiteList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getSiteCollection()
	 * @method \Bitrix\Main\EO_Site_Collection fillSite()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject[] getMembersList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getMembersCollection()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection fillMembers()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result[] getResultsList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getResultsCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection fillResults()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario[] getScenarioList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getScenarioCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection fillScenario()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject[] getRegularList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getRegularCollection()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersCollection fillRegular()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity[] getGroupList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getGroupCollection()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntityCollection fillGroup()
	 * @method \int[] getOutlookVersionList()
	 * @method \int[] fillOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection[] getMemberListList()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection getMemberListCollection()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection fillMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection[] getTagListList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection getTagListCollection()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection fillTagList()
	 * @method \string[] getExchangeIdList()
	 * @method \string[] fillExchangeId()
	 * @method \string[] getExchangeModifiedList()
	 * @method \string[] fillExchangeModified()
	 * @method \string[] getDeclineReasonList()
	 * @method \string[] fillDeclineReason()
	 * @method \int[] getDeadlineCountedList()
	 * @method \int[] fillDeadlineCounted()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask[] getUtsDataList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getUtsDataCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection fillUtsData()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection[] getResultList()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection getResultCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection fillResult()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList[] getChecklistDataList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getChecklistDataCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection fillChecklistData()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask[] getFlowTaskList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getFlowTaskCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection fillFlowTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method bool has(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\TaskObject getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\TaskCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\TaskObject current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\TaskCollection merge(?\Bitrix\Tasks\Internals\TaskCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Task_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\TaskTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\TaskTable';
	}
}
namespace Bitrix\Tasks\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Task_Result exec()
	 * @method \Bitrix\Tasks\Internals\TaskObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fetchCollection()
	 */
	class EO_Task_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\TaskObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fetchCollection()
	 */
	class EO_Task_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\TaskObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\TaskCollection createCollection()
	 * @method \Bitrix\Tasks\Internals\TaskObject wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\TaskCollection wakeUpCollection($rows)
	 */
	class EO_Task_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\LabelTable:tasks/lib/internals/task/label.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * TagObject
	 * @see \Bitrix\Tasks\Internals\Task\LabelTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject resetName()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject unsetName()
	 * @method \string fillName()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject resetGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\TagObject resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity getGroup()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity remindActualGroup()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity requireGroup()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject setGroup(\Bitrix\Socialnetwork\Internals\Group\GroupEntity $object)
	 * @method \Bitrix\Tasks\Internals\Task\TagObject resetGroup()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject unsetGroup()
	 * @method bool hasGroup()
	 * @method bool isGroupFilled()
	 * @method bool isGroupChanged()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity fillGroup()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag getTaskTag()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag remindActualTaskTag()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag requireTaskTag()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject setTaskTag(\Bitrix\Tasks\Internals\Task\EO_TaskTag $object)
	 * @method \Bitrix\Tasks\Internals\Task\TagObject resetTaskTag()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject unsetTaskTag()
	 * @method bool hasTaskTag()
	 * @method bool isTaskTagFilled()
	 * @method bool isTaskTagChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag fillTaskTag()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getTasks()
	 * @method \Bitrix\Tasks\Internals\TaskCollection requireTasks()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTasks()
	 * @method bool hasTasks()
	 * @method bool isTasksFilled()
	 * @method bool isTasksChanged()
	 * @method void addToTasks(\Bitrix\Tasks\Internals\TaskObject $task)
	 * @method void removeFromTasks(\Bitrix\Tasks\Internals\TaskObject $task)
	 * @method void removeAllTasks()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject resetTasks()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject unsetTasks()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\TagObject set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\TagObject reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\TagObject unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\TagObject wakeUp($data)
	 */
	class EO_Label {
		/* @var \Bitrix\Tasks\Internals\Task\LabelTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\LabelTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * TagCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity[] getGroupList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection getGroupCollection()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntityCollection fillGroup()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag[] getTaskTagList()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection getTaskTagCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection fillTaskTag()
	 * @method \Bitrix\Tasks\Internals\TaskCollection[] getTasksList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getTasksCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTasks()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\TagObject $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\TagObject $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\TagObject getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\TagObject[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\TagObject $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\TagCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\TagObject current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection merge(?\Bitrix\Tasks\Internals\Task\TagCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Label_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\LabelTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\LabelTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Label_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection fetchCollection()
	 */
	class EO_Label_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\TagObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection fetchCollection()
	 */
	class EO_Label_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\TagObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection wakeUpCollection($rows)
	 */
	class EO_Label_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\MemberTable:tasks/lib/internals/task/member.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * MemberObject
	 * @see \Bitrix\Tasks\Internals\Task\MemberTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \string getType()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTaskFollowed()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTaskFollowed()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTaskFollowed()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject setTaskFollowed(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject resetTaskFollowed()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject unsetTaskFollowed()
	 * @method bool hasTaskFollowed()
	 * @method bool isTaskFollowedFilled()
	 * @method bool isTaskFollowedChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTaskFollowed()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTaskCoworked()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTaskCoworked()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTaskCoworked()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject setTaskCoworked(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject resetTaskCoworked()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject unsetTaskCoworked()
	 * @method bool hasTaskCoworked()
	 * @method bool isTaskCoworkedFilled()
	 * @method bool isTaskCoworkedChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTaskCoworked()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\MemberObject wakeUp($data)
	 */
	class EO_Member {
		/* @var \Bitrix\Tasks\Internals\Task\MemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\MemberTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * MemberCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \int[] getUserIdList()
	 * @method \string[] getTypeList()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskFollowedList()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection getTaskFollowedCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTaskFollowed()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskCoworkedList()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection getTaskCoworkedCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTaskCoworked()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\MemberObject $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\MemberObject $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\MemberObject $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\MemberCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection merge(?\Bitrix\Tasks\Internals\Task\MemberCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Member_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\MemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\MemberTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Member_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection fetchCollection()
	 */
	class EO_Member_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection fetchCollection()
	 */
	class EO_Member_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection wakeUpCollection($rows)
	 */
	class EO_Member_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ElapsedTimeTable:tasks/lib/internals/task/elapsedtime.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_ElapsedTime
	 * @see \Bitrix\Tasks\Internals\Task\ElapsedTimeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getCreatedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setCreatedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdDate)
	 * @method bool hasCreatedDate()
	 * @method bool isCreatedDateFilled()
	 * @method bool isCreatedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetCreatedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime getDateStart()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setDateStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStart)
	 * @method bool hasDateStart()
	 * @method bool isDateStartFilled()
	 * @method bool isDateStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStart()
	 * @method \Bitrix\Main\Type\DateTime requireDateStart()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetDateStart()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetDateStart()
	 * @method \Bitrix\Main\Type\DateTime fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime getDateStop()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setDateStop(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStop)
	 * @method bool hasDateStop()
	 * @method bool isDateStopFilled()
	 * @method bool isDateStopChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStop()
	 * @method \Bitrix\Main\Type\DateTime requireDateStop()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetDateStop()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetDateStop()
	 * @method \Bitrix\Main\Type\DateTime fillDateStop()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getMinutes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setMinutes(\int|\Bitrix\Main\DB\SqlExpression $minutes)
	 * @method bool hasMinutes()
	 * @method bool isMinutesFilled()
	 * @method bool isMinutesChanged()
	 * @method \int remindActualMinutes()
	 * @method \int requireMinutes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetMinutes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetMinutes()
	 * @method \int fillMinutes()
	 * @method \int getSeconds()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setSeconds(\int|\Bitrix\Main\DB\SqlExpression $seconds)
	 * @method bool hasSeconds()
	 * @method bool isSecondsFilled()
	 * @method bool isSecondsChanged()
	 * @method \int remindActualSeconds()
	 * @method \int requireSeconds()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetSeconds()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetSeconds()
	 * @method \int fillSeconds()
	 * @method \int getSource()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setSource(\int|\Bitrix\Main\DB\SqlExpression $source)
	 * @method bool hasSource()
	 * @method bool isSourceFilled()
	 * @method bool isSourceChanged()
	 * @method \int remindActualSource()
	 * @method \int requireSource()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetSource()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetSource()
	 * @method \int fillSource()
	 * @method \string getCommentText()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setCommentText(\string|\Bitrix\Main\DB\SqlExpression $commentText)
	 * @method bool hasCommentText()
	 * @method bool isCommentTextFilled()
	 * @method bool isCommentTextChanged()
	 * @method \string remindActualCommentText()
	 * @method \string requireCommentText()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetCommentText()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetCommentText()
	 * @method \string fillCommentText()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime wakeUp($data)
	 */
	class EO_ElapsedTime {
		/* @var \Bitrix\Tasks\Internals\Task\ElapsedTimeTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ElapsedTimeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_ElapsedTime_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStopList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStop()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getMinutesList()
	 * @method \int[] fillMinutes()
	 * @method \int[] getSecondsList()
	 * @method \int[] fillSeconds()
	 * @method \int[] getSourceList()
	 * @method \int[] fillSource()
	 * @method \string[] getCommentTextList()
	 * @method \string[] fillCommentText()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_ElapsedTime $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_ElapsedTime $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_ElapsedTime $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ElapsedTime_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ElapsedTimeTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ElapsedTimeTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ElapsedTime_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection fetchCollection()
	 */
	class EO_ElapsedTime_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection fetchCollection()
	 */
	class EO_ElapsedTime_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection wakeUpCollection($rows)
	 */
	class EO_ElapsedTime_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\SortingTable:tasks/lib/internals/task/sorting.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Sorting
	 * @see \Bitrix\Tasks\Internals\Task\SortingTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \float getSort()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting setSort(\float|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \float remindActualSort()
	 * @method \float requireSort()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting resetSort()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting unsetSort()
	 * @method \float fillSort()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting resetGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \int getPrevTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting setPrevTaskId(\int|\Bitrix\Main\DB\SqlExpression $prevTaskId)
	 * @method bool hasPrevTaskId()
	 * @method bool isPrevTaskIdFilled()
	 * @method bool isPrevTaskIdChanged()
	 * @method \int remindActualPrevTaskId()
	 * @method \int requirePrevTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting resetPrevTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting unsetPrevTaskId()
	 * @method \int fillPrevTaskId()
	 * @method \int getNextTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting setNextTaskId(\int|\Bitrix\Main\DB\SqlExpression $nextTaskId)
	 * @method bool hasNextTaskId()
	 * @method bool isNextTaskIdFilled()
	 * @method bool isNextTaskIdChanged()
	 * @method \int remindActualNextTaskId()
	 * @method \int requireNextTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting resetNextTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting unsetNextTaskId()
	 * @method \int fillNextTaskId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Sorting wakeUp($data)
	 */
	class EO_Sorting {
		/* @var \Bitrix\Tasks\Internals\Task\SortingTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\SortingTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Sorting_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \float[] getSortList()
	 * @method \float[] fillSort()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \int[] getPrevTaskIdList()
	 * @method \int[] fillPrevTaskId()
	 * @method \int[] getNextTaskIdList()
	 * @method \int[] fillNextTaskId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Sorting $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Sorting $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Sorting $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Sorting_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Sorting_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\SortingTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\SortingTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Sorting_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection fetchCollection()
	 */
	class EO_Sorting_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection fetchCollection()
	 */
	class EO_Sorting_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection wakeUpCollection($rows)
	 */
	class EO_Sorting_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\FavoriteTable:tasks/lib/internals/task/favorite.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Favorite
	 * @see \Bitrix\Tasks\Internals\Task\FavoriteTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Favorite wakeUp($data)
	 */
	class EO_Favorite {
		/* @var \Bitrix\Tasks\Internals\Task\FavoriteTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\FavoriteTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Favorite_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \int[] getUserIdList()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Favorite $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Favorite $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Favorite $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Favorite_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Favorite_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\FavoriteTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\FavoriteTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Favorite_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection fetchCollection()
	 */
	class EO_Favorite_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection fetchCollection()
	 */
	class EO_Favorite_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection wakeUpCollection($rows)
	 */
	class EO_Favorite_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ProjectDependenceTable:tasks/lib/internals/task/projectdependence.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_ProjectDependence
	 * @see \Bitrix\Tasks\Internals\Task\ProjectDependenceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int getDependsOnId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setDependsOnId(\int|\Bitrix\Main\DB\SqlExpression $dependsOnId)
	 * @method bool hasDependsOnId()
	 * @method bool isDependsOnIdFilled()
	 * @method bool isDependsOnIdChanged()
	 * @method \int getType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setType(\int|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \int remindActualType()
	 * @method \int requireType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence resetType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unsetType()
	 * @method \int fillType()
	 * @method \int getCreatorId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setCreatorId(\int|\Bitrix\Main\DB\SqlExpression $creatorId)
	 * @method bool hasCreatorId()
	 * @method bool isCreatorIdFilled()
	 * @method bool isCreatorIdChanged()
	 * @method \int remindActualCreatorId()
	 * @method \int requireCreatorId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence resetCreatorId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unsetCreatorId()
	 * @method \int fillCreatorId()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject getDependsOn()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualDependsOn()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireDependsOn()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setDependsOn(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence resetDependsOn()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unsetDependsOn()
	 * @method bool hasDependsOn()
	 * @method bool isDependsOnFilled()
	 * @method bool isDependsOnChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillDependsOn()
	 * @method \boolean getDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setDirect(\boolean|\Bitrix\Main\DB\SqlExpression $direct)
	 * @method bool hasDirect()
	 * @method bool isDirectFilled()
	 * @method bool isDirectChanged()
	 * @method \boolean remindActualDirect()
	 * @method \boolean requireDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence resetDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unsetDirect()
	 * @method \boolean fillDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence getParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence remindActualParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence requireParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setParentNode(\Bitrix\Tasks\Internals\Task\EO_ProjectDependence $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence resetParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unsetParentNode()
	 * @method bool hasParentNode()
	 * @method bool isParentNodeFilled()
	 * @method bool isParentNodeChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence fillParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence getParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence remindActualParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence requireParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setParentNodes(\Bitrix\Tasks\Internals\Task\EO_ProjectDependence $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence resetParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unsetParentNodes()
	 * @method bool hasParentNodes()
	 * @method bool isParentNodesFilled()
	 * @method bool isParentNodesChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence fillParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence getChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence remindActualChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence requireChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setChildNodes(\Bitrix\Tasks\Internals\Task\EO_ProjectDependence $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence resetChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unsetChildNodes()
	 * @method bool hasChildNodes()
	 * @method bool isChildNodesFilled()
	 * @method bool isChildNodesChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence fillChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence getChildNodesDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence remindActualChildNodesDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence requireChildNodesDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setChildNodesDirect(\Bitrix\Tasks\Internals\Task\EO_ProjectDependence $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence resetChildNodesDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unsetChildNodesDirect()
	 * @method bool hasChildNodesDirect()
	 * @method bool isChildNodesDirectFilled()
	 * @method bool isChildNodesDirectChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence fillChildNodesDirect()
	 * @method \int getMpcity()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence setMpcity(\int|\Bitrix\Main\DB\SqlExpression $mpcity)
	 * @method bool hasMpcity()
	 * @method bool isMpcityFilled()
	 * @method bool isMpcityChanged()
	 * @method \int remindActualMpcity()
	 * @method \int requireMpcity()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence resetMpcity()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unsetMpcity()
	 * @method \int fillMpcity()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectDependence wakeUp($data)
	 */
	class EO_ProjectDependence {
		/* @var \Bitrix\Tasks\Internals\Task\ProjectDependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ProjectDependenceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_ProjectDependence_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \int[] getDependsOnIdList()
	 * @method \int[] getTypeList()
	 * @method \int[] fillType()
	 * @method \int[] getCreatorIdList()
	 * @method \int[] fillCreatorId()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getDependsOnList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection getDependsOnCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillDependsOn()
	 * @method \boolean[] getDirectList()
	 * @method \boolean[] fillDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence[] getParentNodeList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection getParentNodeCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection fillParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence[] getParentNodesList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection getParentNodesCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection fillParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence[] getChildNodesList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection getChildNodesCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection fillChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence[] getChildNodesDirectList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection getChildNodesDirectCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection fillChildNodesDirect()
	 * @method \int[] getMpcityList()
	 * @method \int[] fillMpcity()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_ProjectDependence $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_ProjectDependence $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_ProjectDependence $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ProjectDependence_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ProjectDependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ProjectDependenceTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ProjectDependence_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection fetchCollection()
	 */
	class EO_ProjectDependence_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection fetchCollection()
	 */
	class EO_ProjectDependence_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection wakeUpCollection($rows)
	 */
	class EO_ProjectDependence_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\TemplateTable:tasks/lib/internals/task/template.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * TemplateObject
	 * @see \Bitrix\Tasks\Internals\Task\TemplateTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetTitle()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getDescription()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetDescription()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetDescription()
	 * @method \string fillDescription()
	 * @method \boolean getDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setDescriptionInBbcode(\boolean|\Bitrix\Main\DB\SqlExpression $descriptionInBbcode)
	 * @method bool hasDescriptionInBbcode()
	 * @method bool isDescriptionInBbcodeFilled()
	 * @method bool isDescriptionInBbcodeChanged()
	 * @method \boolean remindActualDescriptionInBbcode()
	 * @method \boolean requireDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetDescriptionInBbcode()
	 * @method \boolean fillDescriptionInBbcode()
	 * @method \string getPriority()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setPriority(\string|\Bitrix\Main\DB\SqlExpression $priority)
	 * @method bool hasPriority()
	 * @method bool isPriorityFilled()
	 * @method bool isPriorityChanged()
	 * @method \string remindActualPriority()
	 * @method \string requirePriority()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetPriority()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetPriority()
	 * @method \string fillPriority()
	 * @method \string getStatus()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetStatus()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetStatus()
	 * @method \string fillStatus()
	 * @method \int getResponsibleId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setResponsibleId(\int|\Bitrix\Main\DB\SqlExpression $responsibleId)
	 * @method bool hasResponsibleId()
	 * @method bool isResponsibleIdFilled()
	 * @method bool isResponsibleIdChanged()
	 * @method \int remindActualResponsibleId()
	 * @method \int requireResponsibleId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetResponsibleId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetResponsibleId()
	 * @method \int fillResponsibleId()
	 * @method \int getTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setTimeEstimate(\int|\Bitrix\Main\DB\SqlExpression $timeEstimate)
	 * @method bool hasTimeEstimate()
	 * @method bool isTimeEstimateFilled()
	 * @method bool isTimeEstimateChanged()
	 * @method \int remindActualTimeEstimate()
	 * @method \int requireTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetTimeEstimate()
	 * @method \int fillTimeEstimate()
	 * @method \boolean getReplicate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setReplicate(\boolean|\Bitrix\Main\DB\SqlExpression $replicate)
	 * @method bool hasReplicate()
	 * @method bool isReplicateFilled()
	 * @method bool isReplicateChanged()
	 * @method \boolean remindActualReplicate()
	 * @method \boolean requireReplicate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetReplicate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetReplicate()
	 * @method \boolean fillReplicate()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \string getXmlId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetXmlId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \boolean getAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setAllowChangeDeadline(\boolean|\Bitrix\Main\DB\SqlExpression $allowChangeDeadline)
	 * @method bool hasAllowChangeDeadline()
	 * @method bool isAllowChangeDeadlineFilled()
	 * @method bool isAllowChangeDeadlineChanged()
	 * @method \boolean remindActualAllowChangeDeadline()
	 * @method \boolean requireAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetAllowChangeDeadline()
	 * @method \boolean fillAllowChangeDeadline()
	 * @method \boolean getAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setAllowTimeTracking(\boolean|\Bitrix\Main\DB\SqlExpression $allowTimeTracking)
	 * @method bool hasAllowTimeTracking()
	 * @method bool isAllowTimeTrackingFilled()
	 * @method bool isAllowTimeTrackingChanged()
	 * @method \boolean remindActualAllowTimeTracking()
	 * @method \boolean requireAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetAllowTimeTracking()
	 * @method \boolean fillAllowTimeTracking()
	 * @method \boolean getTaskControl()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setTaskControl(\boolean|\Bitrix\Main\DB\SqlExpression $taskControl)
	 * @method bool hasTaskControl()
	 * @method bool isTaskControlFilled()
	 * @method bool isTaskControlChanged()
	 * @method \boolean remindActualTaskControl()
	 * @method \boolean requireTaskControl()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetTaskControl()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetTaskControl()
	 * @method \boolean fillTaskControl()
	 * @method \boolean getAddInReport()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setAddInReport(\boolean|\Bitrix\Main\DB\SqlExpression $addInReport)
	 * @method bool hasAddInReport()
	 * @method bool isAddInReportFilled()
	 * @method bool isAddInReportChanged()
	 * @method \boolean remindActualAddInReport()
	 * @method \boolean requireAddInReport()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetAddInReport()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetAddInReport()
	 * @method \boolean fillAddInReport()
	 * @method \boolean getMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setMatchWorkTime(\boolean|\Bitrix\Main\DB\SqlExpression $matchWorkTime)
	 * @method bool hasMatchWorkTime()
	 * @method bool isMatchWorkTimeFilled()
	 * @method bool isMatchWorkTimeChanged()
	 * @method \boolean remindActualMatchWorkTime()
	 * @method \boolean requireMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetMatchWorkTime()
	 * @method \boolean fillMatchWorkTime()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \int getParentId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetParentId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetParentId()
	 * @method \int fillParentId()
	 * @method \boolean getMultitask()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setMultitask(\boolean|\Bitrix\Main\DB\SqlExpression $multitask)
	 * @method bool hasMultitask()
	 * @method bool isMultitaskFilled()
	 * @method bool isMultitaskChanged()
	 * @method \boolean remindActualMultitask()
	 * @method \boolean requireMultitask()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetMultitask()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetMultitask()
	 * @method \boolean fillMultitask()
	 * @method \string getSiteId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setSiteId(\string|\Bitrix\Main\DB\SqlExpression $siteId)
	 * @method bool hasSiteId()
	 * @method bool isSiteIdFilled()
	 * @method bool isSiteIdChanged()
	 * @method \string remindActualSiteId()
	 * @method \string requireSiteId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetSiteId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetSiteId()
	 * @method \string fillSiteId()
	 * @method \string getReplicateParams()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setReplicateParams(\string|\Bitrix\Main\DB\SqlExpression $replicateParams)
	 * @method bool hasReplicateParams()
	 * @method bool isReplicateParamsFilled()
	 * @method bool isReplicateParamsChanged()
	 * @method \string remindActualReplicateParams()
	 * @method \string requireReplicateParams()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetReplicateParams()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetReplicateParams()
	 * @method \string fillReplicateParams()
	 * @method \string getTags()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setTags(\string|\Bitrix\Main\DB\SqlExpression $tags)
	 * @method bool hasTags()
	 * @method bool isTagsFilled()
	 * @method bool isTagsChanged()
	 * @method \string remindActualTags()
	 * @method \string requireTags()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetTags()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetTags()
	 * @method \string fillTags()
	 * @method \string getAccomplices()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setAccomplices(\string|\Bitrix\Main\DB\SqlExpression $accomplices)
	 * @method bool hasAccomplices()
	 * @method bool isAccomplicesFilled()
	 * @method bool isAccomplicesChanged()
	 * @method \string remindActualAccomplices()
	 * @method \string requireAccomplices()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetAccomplices()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetAccomplices()
	 * @method \string fillAccomplices()
	 * @method \string getAuditors()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setAuditors(\string|\Bitrix\Main\DB\SqlExpression $auditors)
	 * @method bool hasAuditors()
	 * @method bool isAuditorsFilled()
	 * @method bool isAuditorsChanged()
	 * @method \string remindActualAuditors()
	 * @method \string requireAuditors()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetAuditors()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetAuditors()
	 * @method \string fillAuditors()
	 * @method \string getResponsibles()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setResponsibles(\string|\Bitrix\Main\DB\SqlExpression $responsibles)
	 * @method bool hasResponsibles()
	 * @method bool isResponsiblesFilled()
	 * @method bool isResponsiblesChanged()
	 * @method \string remindActualResponsibles()
	 * @method \string requireResponsibles()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetResponsibles()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetResponsibles()
	 * @method \string fillResponsibles()
	 * @method \string getDependsOn()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setDependsOn(\string|\Bitrix\Main\DB\SqlExpression $dependsOn)
	 * @method bool hasDependsOn()
	 * @method bool isDependsOnFilled()
	 * @method bool isDependsOnChanged()
	 * @method \string remindActualDependsOn()
	 * @method \string requireDependsOn()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetDependsOn()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetDependsOn()
	 * @method \string fillDependsOn()
	 * @method \int getDeadlineAfter()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setDeadlineAfter(\int|\Bitrix\Main\DB\SqlExpression $deadlineAfter)
	 * @method bool hasDeadlineAfter()
	 * @method bool isDeadlineAfterFilled()
	 * @method bool isDeadlineAfterChanged()
	 * @method \int remindActualDeadlineAfter()
	 * @method \int requireDeadlineAfter()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetDeadlineAfter()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetDeadlineAfter()
	 * @method \int fillDeadlineAfter()
	 * @method \int getStartDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setStartDatePlanAfter(\int|\Bitrix\Main\DB\SqlExpression $startDatePlanAfter)
	 * @method bool hasStartDatePlanAfter()
	 * @method bool isStartDatePlanAfterFilled()
	 * @method bool isStartDatePlanAfterChanged()
	 * @method \int remindActualStartDatePlanAfter()
	 * @method \int requireStartDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetStartDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetStartDatePlanAfter()
	 * @method \int fillStartDatePlanAfter()
	 * @method \int getEndDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setEndDatePlanAfter(\int|\Bitrix\Main\DB\SqlExpression $endDatePlanAfter)
	 * @method bool hasEndDatePlanAfter()
	 * @method bool isEndDatePlanAfterFilled()
	 * @method bool isEndDatePlanAfterChanged()
	 * @method \int remindActualEndDatePlanAfter()
	 * @method \int requireEndDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetEndDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetEndDatePlanAfter()
	 * @method \int fillEndDatePlanAfter()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getTparamType()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setTparamType(\int|\Bitrix\Main\DB\SqlExpression $tparamType)
	 * @method bool hasTparamType()
	 * @method bool isTparamTypeFilled()
	 * @method bool isTparamTypeChanged()
	 * @method \int remindActualTparamType()
	 * @method \int requireTparamType()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetTparamType()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetTparamType()
	 * @method \int fillTparamType()
	 * @method \int getTparamReplicationCount()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setTparamReplicationCount(\int|\Bitrix\Main\DB\SqlExpression $tparamReplicationCount)
	 * @method bool hasTparamReplicationCount()
	 * @method bool isTparamReplicationCountFilled()
	 * @method bool isTparamReplicationCountChanged()
	 * @method \int remindActualTparamReplicationCount()
	 * @method \int requireTparamReplicationCount()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetTparamReplicationCount()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetTparamReplicationCount()
	 * @method \int fillTparamReplicationCount()
	 * @method \string getZombie()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setZombie(\string|\Bitrix\Main\DB\SqlExpression $zombie)
	 * @method bool hasZombie()
	 * @method bool isZombieFilled()
	 * @method bool isZombieChanged()
	 * @method \string remindActualZombie()
	 * @method \string requireZombie()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetZombie()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetZombie()
	 * @method \string fillZombie()
	 * @method \string getFiles()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setFiles(\string|\Bitrix\Main\DB\SqlExpression $files)
	 * @method bool hasFiles()
	 * @method bool isFilesFilled()
	 * @method bool isFilesChanged()
	 * @method \string remindActualFiles()
	 * @method \string requireFiles()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetFiles()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetFiles()
	 * @method \string fillFiles()
	 * @method \Bitrix\Main\EO_User getCreator()
	 * @method \Bitrix\Main\EO_User remindActualCreator()
	 * @method \Bitrix\Main\EO_User requireCreator()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setCreator(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetCreator()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetCreator()
	 * @method bool hasCreator()
	 * @method bool isCreatorFilled()
	 * @method bool isCreatorChanged()
	 * @method \Bitrix\Main\EO_User fillCreator()
	 * @method \Bitrix\Main\EO_User getResponsible()
	 * @method \Bitrix\Main\EO_User remindActualResponsible()
	 * @method \Bitrix\Main\EO_User requireResponsible()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setResponsible(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetResponsible()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetResponsible()
	 * @method bool hasResponsible()
	 * @method bool isResponsibleFilled()
	 * @method bool isResponsibleChanged()
	 * @method \Bitrix\Main\EO_User fillResponsible()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection getMembers()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection requireMembers()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection fillMembers()
	 * @method bool hasMembers()
	 * @method bool isMembersFilled()
	 * @method bool isMembersChanged()
	 * @method void addToMembers(\Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject $templateMember)
	 * @method void removeFromMembers(\Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject $templateMember)
	 * @method void removeAllMembers()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetMembers()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetMembers()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection getTagList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection requireTagList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection fillTagList()
	 * @method bool hasTagList()
	 * @method bool isTagListFilled()
	 * @method bool isTagListChanged()
	 * @method void addToTagList(\Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag $templateTag)
	 * @method void removeFromTagList(\Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag $templateTag)
	 * @method void removeAllTagList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetTagList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetTagList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection getDependencies()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection requireDependencies()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection fillDependencies()
	 * @method bool hasDependencies()
	 * @method bool isDependenciesFilled()
	 * @method bool isDependenciesChanged()
	 * @method void addToDependencies(\Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence $templateDependence)
	 * @method void removeFromDependencies(\Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence $templateDependence)
	 * @method void removeAllDependencies()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetDependencies()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetDependencies()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario getScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario remindActualScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario requireScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setScenario(\Bitrix\Tasks\Internals\Task\Template\EO_Scenario $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetScenario()
	 * @method bool hasScenario()
	 * @method bool isScenarioFilled()
	 * @method bool isScenarioChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario fillScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList getChecklistData()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList remindActualChecklistData()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList requireChecklistData()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject setChecklistData(\Bitrix\Tasks\Internals\Task\Template\EO_CheckList $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject resetChecklistData()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unsetChecklistData()
	 * @method bool hasChecklistData()
	 * @method bool isChecklistDataFilled()
	 * @method bool isChecklistDataChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList fillChecklistData()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateObject wakeUp($data)
	 */
	class EO_Template {
		/* @var \Bitrix\Tasks\Internals\Task\TemplateTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TemplateTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * TemplateCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \boolean[] getDescriptionInBbcodeList()
	 * @method \boolean[] fillDescriptionInBbcode()
	 * @method \string[] getPriorityList()
	 * @method \string[] fillPriority()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \int[] getResponsibleIdList()
	 * @method \int[] fillResponsibleId()
	 * @method \int[] getTimeEstimateList()
	 * @method \int[] fillTimeEstimate()
	 * @method \boolean[] getReplicateList()
	 * @method \boolean[] fillReplicate()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \string[] getXmlIdList()
	 * @method \string[] fillXmlId()
	 * @method \boolean[] getAllowChangeDeadlineList()
	 * @method \boolean[] fillAllowChangeDeadline()
	 * @method \boolean[] getAllowTimeTrackingList()
	 * @method \boolean[] fillAllowTimeTracking()
	 * @method \boolean[] getTaskControlList()
	 * @method \boolean[] fillTaskControl()
	 * @method \boolean[] getAddInReportList()
	 * @method \boolean[] fillAddInReport()
	 * @method \boolean[] getMatchWorkTimeList()
	 * @method \boolean[] fillMatchWorkTime()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \boolean[] getMultitaskList()
	 * @method \boolean[] fillMultitask()
	 * @method \string[] getSiteIdList()
	 * @method \string[] fillSiteId()
	 * @method \string[] getReplicateParamsList()
	 * @method \string[] fillReplicateParams()
	 * @method \string[] getTagsList()
	 * @method \string[] fillTags()
	 * @method \string[] getAccomplicesList()
	 * @method \string[] fillAccomplices()
	 * @method \string[] getAuditorsList()
	 * @method \string[] fillAuditors()
	 * @method \string[] getResponsiblesList()
	 * @method \string[] fillResponsibles()
	 * @method \string[] getDependsOnList()
	 * @method \string[] fillDependsOn()
	 * @method \int[] getDeadlineAfterList()
	 * @method \int[] fillDeadlineAfter()
	 * @method \int[] getStartDatePlanAfterList()
	 * @method \int[] fillStartDatePlanAfter()
	 * @method \int[] getEndDatePlanAfterList()
	 * @method \int[] fillEndDatePlanAfter()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getTparamTypeList()
	 * @method \int[] fillTparamType()
	 * @method \int[] getTparamReplicationCountList()
	 * @method \int[] fillTparamReplicationCount()
	 * @method \string[] getZombieList()
	 * @method \string[] fillZombie()
	 * @method \string[] getFilesList()
	 * @method \string[] fillFiles()
	 * @method \Bitrix\Main\EO_User[] getCreatorList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection getCreatorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreator()
	 * @method \Bitrix\Main\EO_User[] getResponsibleList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection getResponsibleCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillResponsible()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection[] getMembersList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection getMembersCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection fillMembers()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection[] getTagListList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection getTagListCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection fillTagList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection[] getDependenciesList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection getDependenciesCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection fillDependencies()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario[] getScenarioList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection getScenarioCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario_Collection fillScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList[] getChecklistDataList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection getChecklistDataCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection fillChecklistData()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Template\TemplateObject $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Template\TemplateObject $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Template\TemplateObject $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection merge(?\Bitrix\Tasks\Internals\Task\Template\TemplateCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Template_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\TemplateTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TemplateTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Template_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection fetchCollection()
	 */
	class EO_Template_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection fetchCollection()
	 */
	class EO_Template_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection wakeUpCollection($rows)
	 */
	class EO_Template_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\DependenceTable:tasks/lib/internals/task/template/dependence.php */
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_Dependence
	 * @see \Bitrix\Tasks\Internals\Task\Template\DependenceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \int getParentTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setParentTemplateId(\int|\Bitrix\Main\DB\SqlExpression $parentTemplateId)
	 * @method bool hasParentTemplateId()
	 * @method bool isParentTemplateIdFilled()
	 * @method bool isParentTemplateIdChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject getTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject remindActualTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject requireTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setTemplate(\Bitrix\Tasks\Internals\Task\Template\TemplateObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence resetTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence unsetTemplate()
	 * @method bool hasTemplate()
	 * @method bool isTemplateFilled()
	 * @method bool isTemplateChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject fillTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject getParentTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject remindActualParentTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject requireParentTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setParentTemplate(\Bitrix\Tasks\Internals\Task\Template\TemplateObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence resetParentTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence unsetParentTemplate()
	 * @method bool hasParentTemplate()
	 * @method bool isParentTemplateFilled()
	 * @method bool isParentTemplateChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject fillParentTemplate()
	 * @method \boolean getDirect()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setDirect(\boolean|\Bitrix\Main\DB\SqlExpression $direct)
	 * @method bool hasDirect()
	 * @method bool isDirectFilled()
	 * @method bool isDirectChanged()
	 * @method \boolean remindActualDirect()
	 * @method \boolean requireDirect()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence resetDirect()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence unsetDirect()
	 * @method \boolean fillDirect()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence getParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence remindActualParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence requireParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setParentNode(\Bitrix\Tasks\Internals\Task\Template\EO_Dependence $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence resetParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence unsetParentNode()
	 * @method bool hasParentNode()
	 * @method bool isParentNodeFilled()
	 * @method bool isParentNodeChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence fillParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence getParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence remindActualParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence requireParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setParentNodes(\Bitrix\Tasks\Internals\Task\Template\EO_Dependence $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence resetParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence unsetParentNodes()
	 * @method bool hasParentNodes()
	 * @method bool isParentNodesFilled()
	 * @method bool isParentNodesChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence fillParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence getChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence remindActualChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence requireChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setChildNodes(\Bitrix\Tasks\Internals\Task\Template\EO_Dependence $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence resetChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence unsetChildNodes()
	 * @method bool hasChildNodes()
	 * @method bool isChildNodesFilled()
	 * @method bool isChildNodesChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence fillChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence getChildNodesDirect()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence remindActualChildNodesDirect()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence requireChildNodesDirect()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setChildNodesDirect(\Bitrix\Tasks\Internals\Task\Template\EO_Dependence $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence resetChildNodesDirect()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence unsetChildNodesDirect()
	 * @method bool hasChildNodesDirect()
	 * @method bool isChildNodesDirectFilled()
	 * @method bool isChildNodesDirectChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence fillChildNodesDirect()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Dependence wakeUp($data)
	 */
	class EO_Dependence {
		/* @var \Bitrix\Tasks\Internals\Task\Template\DependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\DependenceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_Dependence_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTemplateIdList()
	 * @method \int[] getParentTemplateIdList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject[] getTemplateList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection getTemplateCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection fillTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject[] getParentTemplateList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection getParentTemplateCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection fillParentTemplate()
	 * @method \boolean[] getDirectList()
	 * @method \boolean[] fillDirect()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence[] getParentNodeList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection getParentNodeCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection fillParentNode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence[] getParentNodesList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection getParentNodesCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection fillParentNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence[] getChildNodesList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection getChildNodesCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection fillChildNodes()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence[] getChildNodesDirectList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection getChildNodesDirectCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection fillChildNodesDirect()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Template\EO_Dependence $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Template\EO_Dependence $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Template\EO_Dependence $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection merge(?\Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Dependence_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\DependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\DependenceTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Dependence_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection fetchCollection()
	 */
	class EO_Dependence_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection fetchCollection()
	 */
	class EO_Dependence_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection wakeUpCollection($rows)
	 */
	class EO_Dependence_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\CheckListTable:tasks/lib/internals/task/template/checklist.php */
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_CheckList
	 * @see \Bitrix\Tasks\Internals\Task\Template\CheckListTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \int remindActualTemplateId()
	 * @method \int requireTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList resetTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList unsetTemplateId()
	 * @method \int fillTemplateId()
	 * @method \int getSort()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList resetSort()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList unsetSort()
	 * @method \int fillSort()
	 * @method \string getTitle()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList resetTitle()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList unsetTitle()
	 * @method \string fillTitle()
	 * @method \int getChecked()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList setChecked(\int|\Bitrix\Main\DB\SqlExpression $checked)
	 * @method bool hasChecked()
	 * @method bool isCheckedFilled()
	 * @method bool isCheckedChanged()
	 * @method \int remindActualChecked()
	 * @method \int requireChecked()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList resetChecked()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList unsetChecked()
	 * @method \int fillChecked()
	 * @method \boolean getIsImportant()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList setIsImportant(\boolean|\Bitrix\Main\DB\SqlExpression $isImportant)
	 * @method bool hasIsImportant()
	 * @method bool isIsImportantFilled()
	 * @method bool isIsImportantChanged()
	 * @method \boolean remindActualIsImportant()
	 * @method \boolean requireIsImportant()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList resetIsImportant()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList unsetIsImportant()
	 * @method \boolean fillIsImportant()
	 * @method \string getIsComplete()
	 * @method \string remindActualIsComplete()
	 * @method \string requireIsComplete()
	 * @method bool hasIsComplete()
	 * @method bool isIsCompleteFilled()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList unsetIsComplete()
	 * @method \string fillIsComplete()
	 * @method \int getSortIndex()
	 * @method \int remindActualSortIndex()
	 * @method \int requireSortIndex()
	 * @method bool hasSortIndex()
	 * @method bool isSortIndexFilled()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList unsetSortIndex()
	 * @method \int fillSortIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckList wakeUp($data)
	 */
	class EO_CheckList {
		/* @var \Bitrix\Tasks\Internals\Task\Template\CheckListTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\CheckListTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_CheckList_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTemplateIdList()
	 * @method \int[] fillTemplateId()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \int[] getCheckedList()
	 * @method \int[] fillChecked()
	 * @method \boolean[] getIsImportantList()
	 * @method \boolean[] fillIsImportant()
	 * @method \string[] getIsCompleteList()
	 * @method \string[] fillIsComplete()
	 * @method \int[] getSortIndexList()
	 * @method \int[] fillSortIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Template\EO_CheckList $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Template\EO_CheckList $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Template\EO_CheckList $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection merge(?\Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_CheckList_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\CheckListTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\CheckListTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CheckList_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection fetchCollection()
	 */
	class EO_CheckList_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection fetchCollection()
	 */
	class EO_CheckList_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection wakeUpCollection($rows)
	 */
	class EO_CheckList_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Notification\Task\ThrottleTable:tasks/lib/internals/notification/task/throttle.php */
namespace Bitrix\Tasks\Internals\Notification\Task {
	/**
	 * EO_Throttle
	 * @see \Bitrix\Tasks\Internals\Notification\Task\ThrottleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getAuthorId()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle setAuthorId(\int|\Bitrix\Main\DB\SqlExpression $authorId)
	 * @method bool hasAuthorId()
	 * @method bool isAuthorIdFilled()
	 * @method bool isAuthorIdChanged()
	 * @method \int remindActualAuthorId()
	 * @method \int requireAuthorId()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle resetAuthorId()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle unsetAuthorId()
	 * @method \int fillAuthorId()
	 * @method \string getInformAuthor()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle setInformAuthor(\string|\Bitrix\Main\DB\SqlExpression $informAuthor)
	 * @method bool hasInformAuthor()
	 * @method bool isInformAuthorFilled()
	 * @method bool isInformAuthorChanged()
	 * @method \string remindActualInformAuthor()
	 * @method \string requireInformAuthor()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle resetInformAuthor()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle unsetInformAuthor()
	 * @method \string fillInformAuthor()
	 * @method \string getStateOrig()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle setStateOrig(\string|\Bitrix\Main\DB\SqlExpression $stateOrig)
	 * @method bool hasStateOrig()
	 * @method bool isStateOrigFilled()
	 * @method bool isStateOrigChanged()
	 * @method \string remindActualStateOrig()
	 * @method \string requireStateOrig()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle resetStateOrig()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle unsetStateOrig()
	 * @method \string fillStateOrig()
	 * @method \string getStateLast()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle setStateLast(\string|\Bitrix\Main\DB\SqlExpression $stateLast)
	 * @method bool hasStateLast()
	 * @method bool isStateLastFilled()
	 * @method bool isStateLastChanged()
	 * @method \string remindActualStateLast()
	 * @method \string requireStateLast()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle resetStateLast()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle unsetStateLast()
	 * @method \string fillStateLast()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle wakeUp($data)
	 */
	class EO_Throttle {
		/* @var \Bitrix\Tasks\Internals\Notification\Task\ThrottleTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Notification\Task\ThrottleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Notification\Task {
	/**
	 * EO_Throttle_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getAuthorIdList()
	 * @method \int[] fillAuthorId()
	 * @method \string[] getInformAuthorList()
	 * @method \string[] fillInformAuthor()
	 * @method \string[] getStateOrigList()
	 * @method \string[] fillStateOrig()
	 * @method \string[] getStateLastList()
	 * @method \string[] fillStateLast()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Notification\Task\EO_Throttle $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Notification\Task\EO_Throttle $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Notification\Task\EO_Throttle $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection merge(?\Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Throttle_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Notification\Task\ThrottleTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Notification\Task\ThrottleTable';
	}
}
namespace Bitrix\Tasks\Internals\Notification\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Throttle_Result exec()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle fetchObject()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection fetchCollection()
	 */
	class EO_Throttle_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle fetchObject()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection fetchCollection()
	 */
	class EO_Throttle_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection wakeUpCollection($rows)
	 */
	class EO_Throttle_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Kanban\ProjectsTable:tasks/lib/kanban/projects.php */
namespace Bitrix\Tasks\Kanban {
	/**
	 * EO_Projects
	 * @see \Bitrix\Tasks\Kanban\ProjectsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Kanban\EO_Projects setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getOrderNewTask()
	 * @method \Bitrix\Tasks\Kanban\EO_Projects setOrderNewTask(\string|\Bitrix\Main\DB\SqlExpression $orderNewTask)
	 * @method bool hasOrderNewTask()
	 * @method bool isOrderNewTaskFilled()
	 * @method bool isOrderNewTaskChanged()
	 * @method \string remindActualOrderNewTask()
	 * @method \string requireOrderNewTask()
	 * @method \Bitrix\Tasks\Kanban\EO_Projects resetOrderNewTask()
	 * @method \Bitrix\Tasks\Kanban\EO_Projects unsetOrderNewTask()
	 * @method \string fillOrderNewTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Kanban\EO_Projects set($fieldName, $value)
	 * @method \Bitrix\Tasks\Kanban\EO_Projects reset($fieldName)
	 * @method \Bitrix\Tasks\Kanban\EO_Projects unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Kanban\EO_Projects wakeUp($data)
	 */
	class EO_Projects {
		/* @var \Bitrix\Tasks\Kanban\ProjectsTable */
		static public $dataClass = '\Bitrix\Tasks\Kanban\ProjectsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Kanban {
	/**
	 * EO_Projects_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getOrderNewTaskList()
	 * @method \string[] fillOrderNewTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Kanban\EO_Projects $object)
	 * @method bool has(\Bitrix\Tasks\Kanban\EO_Projects $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Kanban\EO_Projects getByPrimary($primary)
	 * @method \Bitrix\Tasks\Kanban\EO_Projects[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Kanban\EO_Projects $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Kanban\EO_Projects_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Kanban\EO_Projects current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Kanban\EO_Projects_Collection merge(?\Bitrix\Tasks\Kanban\EO_Projects_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Projects_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Kanban\ProjectsTable */
		static public $dataClass = '\Bitrix\Tasks\Kanban\ProjectsTable';
	}
}
namespace Bitrix\Tasks\Kanban {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Projects_Result exec()
	 * @method \Bitrix\Tasks\Kanban\EO_Projects fetchObject()
	 * @method \Bitrix\Tasks\Kanban\EO_Projects_Collection fetchCollection()
	 */
	class EO_Projects_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Kanban\EO_Projects fetchObject()
	 * @method \Bitrix\Tasks\Kanban\EO_Projects_Collection fetchCollection()
	 */
	class EO_Projects_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Kanban\EO_Projects createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Kanban\EO_Projects_Collection createCollection()
	 * @method \Bitrix\Tasks\Kanban\EO_Projects wakeUpObject($row)
	 * @method \Bitrix\Tasks\Kanban\EO_Projects_Collection wakeUpCollection($rows)
	 */
	class EO_Projects_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Marketing\MarketingTable:tasks/lib/internals/marketing/marketingtable.php */
namespace Bitrix\Tasks\Internals\Marketing {
	/**
	 * EO_Marketing
	 * @see \Bitrix\Tasks\Internals\Marketing\MarketingTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing resetUserId()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getEvent()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing setEvent(\string|\Bitrix\Main\DB\SqlExpression $event)
	 * @method bool hasEvent()
	 * @method bool isEventFilled()
	 * @method bool isEventChanged()
	 * @method \string remindActualEvent()
	 * @method \string requireEvent()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing resetEvent()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing unsetEvent()
	 * @method \string fillEvent()
	 * @method \int getDateCreated()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing setDateCreated(\int|\Bitrix\Main\DB\SqlExpression $dateCreated)
	 * @method bool hasDateCreated()
	 * @method bool isDateCreatedFilled()
	 * @method bool isDateCreatedChanged()
	 * @method \int remindActualDateCreated()
	 * @method \int requireDateCreated()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing resetDateCreated()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing unsetDateCreated()
	 * @method \int fillDateCreated()
	 * @method \int getDateSheduled()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing setDateSheduled(\int|\Bitrix\Main\DB\SqlExpression $dateSheduled)
	 * @method bool hasDateSheduled()
	 * @method bool isDateSheduledFilled()
	 * @method bool isDateSheduledChanged()
	 * @method \int remindActualDateSheduled()
	 * @method \int requireDateSheduled()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing resetDateSheduled()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing unsetDateSheduled()
	 * @method \int fillDateSheduled()
	 * @method \int getDateExecuted()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing setDateExecuted(\int|\Bitrix\Main\DB\SqlExpression $dateExecuted)
	 * @method bool hasDateExecuted()
	 * @method bool isDateExecutedFilled()
	 * @method bool isDateExecutedChanged()
	 * @method \int remindActualDateExecuted()
	 * @method \int requireDateExecuted()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing resetDateExecuted()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing unsetDateExecuted()
	 * @method \int fillDateExecuted()
	 * @method \string getParams()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing setParams(\string|\Bitrix\Main\DB\SqlExpression $params)
	 * @method bool hasParams()
	 * @method bool isParamsFilled()
	 * @method bool isParamsChanged()
	 * @method \string remindActualParams()
	 * @method \string requireParams()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing resetParams()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing unsetParams()
	 * @method \string fillParams()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing resetUser()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Marketing\EO_Marketing wakeUp($data)
	 */
	class EO_Marketing {
		/* @var \Bitrix\Tasks\Internals\Marketing\MarketingTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Marketing\MarketingTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Marketing {
	/**
	 * EO_Marketing_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getEventList()
	 * @method \string[] fillEvent()
	 * @method \int[] getDateCreatedList()
	 * @method \int[] fillDateCreated()
	 * @method \int[] getDateSheduledList()
	 * @method \int[] fillDateSheduled()
	 * @method \int[] getDateExecutedList()
	 * @method \int[] fillDateExecuted()
	 * @method \string[] getParamsList()
	 * @method \string[] fillParams()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Marketing\EO_Marketing $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Marketing\EO_Marketing $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Marketing\EO_Marketing $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Marketing\EO_Marketing_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing_Collection merge(?\Bitrix\Tasks\Internals\Marketing\EO_Marketing_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Marketing_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Marketing\MarketingTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Marketing\MarketingTable';
	}
}
namespace Bitrix\Tasks\Internals\Marketing {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Marketing_Result exec()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing fetchObject()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing_Collection fetchCollection()
	 */
	class EO_Marketing_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing fetchObject()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing_Collection fetchCollection()
	 */
	class EO_Marketing_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Marketing\EO_Marketing_Collection wakeUpCollection($rows)
	 */
	class EO_Marketing_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\SystemLogTable:tasks/lib/internals/systemlog.php */
namespace Bitrix\Tasks\Internals {
	/**
	 * EO_SystemLog
	 * @see \Bitrix\Tasks\Internals\SystemLogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getType()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog setType(\int|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \int remindActualType()
	 * @method \int requireType()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog resetType()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog unsetType()
	 * @method \int fillType()
	 * @method \Bitrix\Main\Type\DateTime getCreatedDate()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog setCreatedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdDate)
	 * @method bool hasCreatedDate()
	 * @method bool isCreatedDateFilled()
	 * @method bool isCreatedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedDate()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog resetCreatedDate()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog unsetCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedDate()
	 * @method \string getMessage()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog setMessage(\string|\Bitrix\Main\DB\SqlExpression $message)
	 * @method bool hasMessage()
	 * @method bool isMessageFilled()
	 * @method bool isMessageChanged()
	 * @method \string remindActualMessage()
	 * @method \string requireMessage()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog resetMessage()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog unsetMessage()
	 * @method \string fillMessage()
	 * @method \int getEntityId()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog resetEntityId()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \int getEntityType()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog setEntityType(\int|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \int remindActualEntityType()
	 * @method \int requireEntityType()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog resetEntityType()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog unsetEntityType()
	 * @method \int fillEntityType()
	 * @method \int getParamA()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog setParamA(\int|\Bitrix\Main\DB\SqlExpression $paramA)
	 * @method bool hasParamA()
	 * @method bool isParamAFilled()
	 * @method bool isParamAChanged()
	 * @method \int remindActualParamA()
	 * @method \int requireParamA()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog resetParamA()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog unsetParamA()
	 * @method \int fillParamA()
	 * @method \string getError()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog setError(\string|\Bitrix\Main\DB\SqlExpression $error)
	 * @method bool hasError()
	 * @method bool isErrorFilled()
	 * @method bool isErrorChanged()
	 * @method \string remindActualError()
	 * @method \string requireError()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog resetError()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog unsetError()
	 * @method \string fillError()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\EO_SystemLog wakeUp($data)
	 */
	class EO_SystemLog {
		/* @var \Bitrix\Tasks\Internals\SystemLogTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\SystemLogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals {
	/**
	 * EO_SystemLog_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTypeList()
	 * @method \int[] fillType()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedDate()
	 * @method \string[] getMessageList()
	 * @method \string[] fillMessage()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \int[] getEntityTypeList()
	 * @method \int[] fillEntityType()
	 * @method \int[] getParamAList()
	 * @method \int[] fillParamA()
	 * @method \string[] getErrorList()
	 * @method \string[] fillError()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\EO_SystemLog $object)
	 * @method bool has(\Bitrix\Tasks\Internals\EO_SystemLog $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\EO_SystemLog $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\EO_SystemLog_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog_Collection merge(?\Bitrix\Tasks\Internals\EO_SystemLog_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_SystemLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\SystemLogTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\SystemLogTable';
	}
}
namespace Bitrix\Tasks\Internals {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SystemLog_Result exec()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog fetchObject()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog_Collection fetchCollection()
	 */
	class EO_SystemLog_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog fetchObject()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog_Collection fetchCollection()
	 */
	class EO_SystemLog_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog_Collection wakeUpCollection($rows)
	 */
	class EO_SystemLog_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\SearchIndexTable:tasks/lib/internals/task/searchindex.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_SearchIndex
	 * @see \Bitrix\Tasks\Internals\Task\SearchIndexTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getMessageId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex setMessageId(\int|\Bitrix\Main\DB\SqlExpression $messageId)
	 * @method bool hasMessageId()
	 * @method bool isMessageIdFilled()
	 * @method bool isMessageIdChanged()
	 * @method \int remindActualMessageId()
	 * @method \int requireMessageId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex resetMessageId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex unsetMessageId()
	 * @method \int fillMessageId()
	 * @method \string getSearchIndex()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex setSearchIndex(\string|\Bitrix\Main\DB\SqlExpression $searchIndex)
	 * @method bool hasSearchIndex()
	 * @method bool isSearchIndexFilled()
	 * @method bool isSearchIndexChanged()
	 * @method \string remindActualSearchIndex()
	 * @method \string requireSearchIndex()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex resetSearchIndex()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex unsetSearchIndex()
	 * @method \string fillSearchIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex wakeUp($data)
	 */
	class EO_SearchIndex {
		/* @var \Bitrix\Tasks\Internals\Task\SearchIndexTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\SearchIndexTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_SearchIndex_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getMessageIdList()
	 * @method \int[] fillMessageId()
	 * @method \string[] getSearchIndexList()
	 * @method \string[] fillSearchIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_SearchIndex $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_SearchIndex $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_SearchIndex $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_SearchIndex_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\SearchIndexTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\SearchIndexTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SearchIndex_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection fetchCollection()
	 */
	class EO_SearchIndex_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection fetchCollection()
	 */
	class EO_SearchIndex_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection wakeUpCollection($rows)
	 */
	class EO_SearchIndex_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ViewedTable:tasks/lib/internals/task/viewed.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * View
	 * @see \Bitrix\Tasks\Internals\Task\ViewedTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\View setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\View setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getViewedDate()
	 * @method \Bitrix\Tasks\Internals\Task\View setViewedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $viewedDate)
	 * @method bool hasViewedDate()
	 * @method bool isViewedDateFilled()
	 * @method bool isViewedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualViewedDate()
	 * @method \Bitrix\Main\Type\DateTime requireViewedDate()
	 * @method \Bitrix\Tasks\Internals\Task\View resetViewedDate()
	 * @method \Bitrix\Tasks\Internals\Task\View unsetViewedDate()
	 * @method \Bitrix\Main\Type\DateTime fillViewedDate()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\View setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\View resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\View unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\View setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\View resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\View unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject getMembers()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject remindActualMembers()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject requireMembers()
	 * @method \Bitrix\Tasks\Internals\Task\View setMembers(\Bitrix\Tasks\Internals\Task\MemberObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\View resetMembers()
	 * @method \Bitrix\Tasks\Internals\Task\View unsetMembers()
	 * @method bool hasMembers()
	 * @method bool isMembersFilled()
	 * @method bool isMembersChanged()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject fillMembers()
	 * @method \boolean getIsRealView()
	 * @method \Bitrix\Tasks\Internals\Task\View setIsRealView(\boolean|\Bitrix\Main\DB\SqlExpression $isRealView)
	 * @method bool hasIsRealView()
	 * @method bool isIsRealViewFilled()
	 * @method bool isIsRealViewChanged()
	 * @method \boolean remindActualIsRealView()
	 * @method \boolean requireIsRealView()
	 * @method \Bitrix\Tasks\Internals\Task\View resetIsRealView()
	 * @method \Bitrix\Tasks\Internals\Task\View unsetIsRealView()
	 * @method \boolean fillIsRealView()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\View set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\View reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\View unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\View wakeUp($data)
	 */
	class EO_Viewed {
		/* @var \Bitrix\Tasks\Internals\Task\ViewedTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ViewedTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Viewed_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \int[] getUserIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getViewedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillViewedDate()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject[] getMembersList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection getMembersCollection()
	 * @method \Bitrix\Tasks\Internals\Task\MemberCollection fillMembers()
	 * @method \boolean[] getIsRealViewList()
	 * @method \boolean[] fillIsRealView()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\View $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\View $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\View getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\View[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\View $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\View current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Viewed_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Viewed_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ViewedTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ViewedTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Viewed_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\View fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection fetchCollection()
	 */
	class EO_Viewed_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\View fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection fetchCollection()
	 */
	class EO_Viewed_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\View createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\View wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection wakeUpCollection($rows)
	 */
	class EO_Viewed_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ParameterTable:tasks/lib/internals/task/parameter.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Parameter
	 * @see \Bitrix\Tasks\Internals\Task\ParameterTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getCode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter setCode(\int|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \int remindActualCode()
	 * @method \int requireCode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter resetCode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter unsetCode()
	 * @method \int fillCode()
	 * @method \string getValue()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter resetValue()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter unsetValue()
	 * @method \string fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Parameter wakeUp($data)
	 */
	class EO_Parameter {
		/* @var \Bitrix\Tasks\Internals\Task\ParameterTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ParameterTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Parameter_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getCodeList()
	 * @method \int[] fillCode()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Parameter $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Parameter $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Parameter $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Parameter_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Parameter_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ParameterTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ParameterTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Parameter_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection fetchCollection()
	 */
	class EO_Parameter_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection fetchCollection()
	 */
	class EO_Parameter_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection wakeUpCollection($rows)
	 */
	class EO_Parameter_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\TimerTable:tasks/lib/internals/task/timer.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Timer
	 * @see \Bitrix\Tasks\Internals\Task\TimerTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int getTimerStartedAt()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer setTimerStartedAt(\int|\Bitrix\Main\DB\SqlExpression $timerStartedAt)
	 * @method bool hasTimerStartedAt()
	 * @method bool isTimerStartedAtFilled()
	 * @method bool isTimerStartedAtChanged()
	 * @method \int remindActualTimerStartedAt()
	 * @method \int requireTimerStartedAt()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer resetTimerStartedAt()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer unsetTimerStartedAt()
	 * @method \int fillTimerStartedAt()
	 * @method \int getTimerAccumulator()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer setTimerAccumulator(\int|\Bitrix\Main\DB\SqlExpression $timerAccumulator)
	 * @method bool hasTimerAccumulator()
	 * @method bool isTimerAccumulatorFilled()
	 * @method bool isTimerAccumulatorChanged()
	 * @method \int remindActualTimerAccumulator()
	 * @method \int requireTimerAccumulator()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer resetTimerAccumulator()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer unsetTimerAccumulator()
	 * @method \int fillTimerAccumulator()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer wakeUp($data)
	 */
	class EO_Timer {
		/* @var \Bitrix\Tasks\Internals\Task\TimerTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TimerTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Timer_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getUserIdList()
	 * @method \int[] getTimerStartedAtList()
	 * @method \int[] fillTimerStartedAt()
	 * @method \int[] getTimerAccumulatorList()
	 * @method \int[] fillTimerAccumulator()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Timer $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Timer $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Timer $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Timer_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Timer_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\TimerTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TimerTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Timer_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer_Collection fetchCollection()
	 */
	class EO_Timer_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer_Collection fetchCollection()
	 */
	class EO_Timer_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer_Collection wakeUpCollection($rows)
	 */
	class EO_Timer_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\AccessTable:tasks/lib/internals/task/template/access.php */
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_Access
	 * @see \Bitrix\Tasks\Internals\Task\Template\AccessTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getGroupCode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access setGroupCode(\string|\Bitrix\Main\DB\SqlExpression $groupCode)
	 * @method bool hasGroupCode()
	 * @method bool isGroupCodeFilled()
	 * @method bool isGroupCodeChanged()
	 * @method \string remindActualGroupCode()
	 * @method \string requireGroupCode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access resetGroupCode()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access unsetGroupCode()
	 * @method \string fillGroupCode()
	 * @method \int getEntityId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access resetEntityId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access unsetTaskId()
	 * @method \int fillTaskId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access wakeUp($data)
	 */
	class EO_Access {
		/* @var \Bitrix\Tasks\Internals\Task\Template\AccessTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\AccessTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_Access_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getGroupCodeList()
	 * @method \string[] fillGroupCode()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Template\EO_Access $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Template\EO_Access $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Template\EO_Access $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection merge(?\Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Access_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\AccessTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\AccessTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Access_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection fetchCollection()
	 */
	class EO_Access_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection fetchCollection()
	 */
	class EO_Access_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection wakeUpCollection($rows)
	 */
	class EO_Access_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable:tasks/lib/internals/task/template/templatemembertable.php */
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * TemplateMemberObject
	 * @see \Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \int remindActualTemplateId()
	 * @method \int requireTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject resetTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject unsetTemplateId()
	 * @method \int fillTemplateId()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getType()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject resetType()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject unsetType()
	 * @method \string fillType()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject getTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject remindActualTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject requireTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject setTemplate(\Bitrix\Tasks\Internals\Task\Template\TemplateObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject resetTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject unsetTemplate()
	 * @method bool hasTemplate()
	 * @method bool isTemplateFilled()
	 * @method bool isTemplateChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject fillTemplate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject wakeUp($data)
	 */
	class EO_TemplateMember {
		/* @var \Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * TemplateMemberCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTemplateIdList()
	 * @method \int[] fillTemplateId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject[] getTemplateList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection getTemplateCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection fillTemplate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection merge(?\Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TemplateMember_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TemplateMember_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection fetchCollection()
	 */
	class EO_TemplateMember_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection fetchCollection()
	 */
	class EO_TemplateMember_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberObject wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateMemberCollection wakeUpCollection($rows)
	 */
	class EO_TemplateMember_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\CheckListTreeTable:tasks/lib/internals/task/template/checklisttree.php */
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_CheckListTree
	 * @see \Bitrix\Tasks\Internals\Task\Template\CheckListTreeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getParentId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int getChildId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree setChildId(\int|\Bitrix\Main\DB\SqlExpression $childId)
	 * @method bool hasChildId()
	 * @method bool isChildIdFilled()
	 * @method bool isChildIdChanged()
	 * @method \int getLevel()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree setLevel(\int|\Bitrix\Main\DB\SqlExpression $level)
	 * @method bool hasLevel()
	 * @method bool isLevelFilled()
	 * @method bool isLevelChanged()
	 * @method \int remindActualLevel()
	 * @method \int requireLevel()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree resetLevel()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree unsetLevel()
	 * @method \int fillLevel()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree wakeUp($data)
	 */
	class EO_CheckListTree {
		/* @var \Bitrix\Tasks\Internals\Task\Template\CheckListTreeTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\CheckListTreeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_CheckListTree_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getParentIdList()
	 * @method \int[] getChildIdList()
	 * @method \int[] getLevelList()
	 * @method \int[] fillLevel()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree_Collection merge(?\Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_CheckListTree_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\CheckListTreeTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\CheckListTreeTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CheckListTree_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree_Collection fetchCollection()
	 */
	class EO_CheckListTree_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree_Collection fetchCollection()
	 */
	class EO_CheckListTree_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree_Collection wakeUpCollection($rows)
	 */
	class EO_CheckListTree_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable:tasks/lib/internals/task/template/templatedependencetable.php */
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_TemplateDependence
	 * @see \Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \int remindActualTemplateId()
	 * @method \int requireTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence resetTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence unsetTemplateId()
	 * @method \int fillTemplateId()
	 * @method \int getDependsOnId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence setDependsOnId(\int|\Bitrix\Main\DB\SqlExpression $dependsOnId)
	 * @method bool hasDependsOnId()
	 * @method bool isDependsOnIdFilled()
	 * @method bool isDependsOnIdChanged()
	 * @method \int remindActualDependsOnId()
	 * @method \int requireDependsOnId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence resetDependsOnId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence unsetDependsOnId()
	 * @method \int fillDependsOnId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject getTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject remindActualTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject requireTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence setTemplate(\Bitrix\Tasks\Internals\Task\Template\TemplateObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence resetTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence unsetTemplate()
	 * @method bool hasTemplate()
	 * @method bool isTemplateFilled()
	 * @method bool isTemplateChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject fillTemplate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence wakeUp($data)
	 */
	class EO_TemplateDependence {
		/* @var \Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_TemplateDependence_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTemplateIdList()
	 * @method \int[] fillTemplateId()
	 * @method \int[] getDependsOnIdList()
	 * @method \int[] fillDependsOnId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject[] getTemplateList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection getTemplateCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection fillTemplate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection merge(?\Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TemplateDependence_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TemplateDependence_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection fetchCollection()
	 */
	class EO_TemplateDependence_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection fetchCollection()
	 */
	class EO_TemplateDependence_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateDependence_Collection wakeUpCollection($rows)
	 */
	class EO_TemplateDependence_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\CheckList\MemberTable:tasks/lib/internals/task/template/checklist/member.php */
namespace Bitrix\Tasks\Internals\Task\Template\CheckList {
	/**
	 * EO_Member
	 * @see \Bitrix\Tasks\Internals\Task\Template\CheckList\MemberTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getItemId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member setItemId(\int|\Bitrix\Main\DB\SqlExpression $itemId)
	 * @method bool hasItemId()
	 * @method bool isItemIdFilled()
	 * @method bool isItemIdChanged()
	 * @method \int remindActualItemId()
	 * @method \int requireItemId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member resetItemId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member unsetItemId()
	 * @method \int fillItemId()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getType()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member resetType()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member unsetType()
	 * @method \string fillType()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList getChecklistItem()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList remindActualChecklistItem()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList requireChecklistItem()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member setChecklistItem(\Bitrix\Tasks\Internals\Task\Template\EO_CheckList $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member resetChecklistItem()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member unsetChecklistItem()
	 * @method bool hasChecklistItem()
	 * @method bool isChecklistItemFilled()
	 * @method bool isChecklistItemChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList fillChecklistItem()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member wakeUp($data)
	 */
	class EO_Member {
		/* @var \Bitrix\Tasks\Internals\Task\Template\CheckList\MemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\CheckList\MemberTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\Template\CheckList {
	/**
	 * EO_Member_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getItemIdList()
	 * @method \int[] fillItemId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList[] getChecklistItemList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection getChecklistItemCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection fillChecklistItem()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection merge(?\Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Member_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\CheckList\MemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\CheckList\MemberTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template\CheckList {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Member_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection fetchCollection()
	 */
	class EO_Member_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection fetchCollection()
	 */
	class EO_Member_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection wakeUpCollection($rows)
	 */
	class EO_Member_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\TemplateTagTable:tasks/lib/internals/task/template/templatetagtable.php */
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_TemplateTag
	 * @see \Bitrix\Tasks\Internals\Task\Template\TemplateTagTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \int remindActualTemplateId()
	 * @method \int requireTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag resetTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag unsetTemplateId()
	 * @method \int fillTemplateId()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag resetName()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag unsetName()
	 * @method \string fillName()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject getTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject remindActualTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject requireTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag setTemplate(\Bitrix\Tasks\Internals\Task\Template\TemplateObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag resetTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag unsetTemplate()
	 * @method bool hasTemplate()
	 * @method bool isTemplateFilled()
	 * @method bool isTemplateChanged()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject fillTemplate()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \string getMaxId()
	 * @method \string remindActualMaxId()
	 * @method \string requireMaxId()
	 * @method bool hasMaxId()
	 * @method bool isMaxIdFilled()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag unsetMaxId()
	 * @method \string fillMaxId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag wakeUp($data)
	 */
	class EO_TemplateTag {
		/* @var \Bitrix\Tasks\Internals\Task\Template\TemplateTagTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\TemplateTagTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_TemplateTag_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTemplateIdList()
	 * @method \int[] fillTemplateId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateObject[] getTemplateList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection getTemplateCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\TemplateCollection fillTemplate()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \string[] getMaxIdList()
	 * @method \string[] fillMaxId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection merge(?\Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TemplateTag_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\TemplateTagTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\TemplateTagTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TemplateTag_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection fetchCollection()
	 */
	class EO_TemplateTag_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection fetchCollection()
	 */
	class EO_TemplateTag_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_TemplateTag_Collection wakeUpCollection($rows)
	 */
	class EO_TemplateTag_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\ScenarioTable:tasks/lib/internals/task/template/scenario.php */
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_Scenario
	 * @see \Bitrix\Tasks\Internals\Task\Template\ScenarioTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTemplateId()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \string getScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario setScenario(\string|\Bitrix\Main\DB\SqlExpression $scenario)
	 * @method bool hasScenario()
	 * @method bool isScenarioFilled()
	 * @method bool isScenarioChanged()
	 * @method \string remindActualScenario()
	 * @method \string requireScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario resetScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario unsetScenario()
	 * @method \string fillScenario()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Scenario wakeUp($data)
	 */
	class EO_Scenario {
		/* @var \Bitrix\Tasks\Internals\Task\Template\ScenarioTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\ScenarioTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * EO_Scenario_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTemplateIdList()
	 * @method \string[] getScenarioList()
	 * @method \string[] fillScenario()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Template\EO_Scenario $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Template\EO_Scenario $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Template\EO_Scenario $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Scenario_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario_Collection merge(?\Bitrix\Tasks\Internals\Task\Template\EO_Scenario_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Scenario_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\ScenarioTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\ScenarioTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Scenario_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario_Collection fetchCollection()
	 */
	class EO_Scenario_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario_Collection fetchCollection()
	 */
	class EO_Scenario_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Scenario_Collection wakeUpCollection($rows)
	 */
	class EO_Scenario_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\LogTable:tasks/lib/internals/task/log.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Log
	 * @see \Bitrix\Tasks\Internals\Task\LogTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getCreatedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log setCreatedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdDate)
	 * @method bool hasCreatedDate()
	 * @method bool isCreatedDateFilled()
	 * @method bool isCreatedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log resetCreatedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log unsetCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedDate()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \string getField()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log setField(\string|\Bitrix\Main\DB\SqlExpression $field)
	 * @method bool hasField()
	 * @method bool isFieldFilled()
	 * @method bool isFieldChanged()
	 * @method \string remindActualField()
	 * @method \string requireField()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log resetField()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log unsetField()
	 * @method \string fillField()
	 * @method \string getFromValue()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log setFromValue(\string|\Bitrix\Main\DB\SqlExpression $fromValue)
	 * @method bool hasFromValue()
	 * @method bool isFromValueFilled()
	 * @method bool isFromValueChanged()
	 * @method \string remindActualFromValue()
	 * @method \string requireFromValue()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log resetFromValue()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log unsetFromValue()
	 * @method \string fillFromValue()
	 * @method \string getToValue()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log setToValue(\string|\Bitrix\Main\DB\SqlExpression $toValue)
	 * @method bool hasToValue()
	 * @method bool isToValueFilled()
	 * @method bool isToValueChanged()
	 * @method \string remindActualToValue()
	 * @method \string requireToValue()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log resetToValue()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log unsetToValue()
	 * @method \string fillToValue()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Log wakeUp($data)
	 */
	class EO_Log {
		/* @var \Bitrix\Tasks\Internals\Task\LogTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\LogTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Log_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedDate()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \string[] getFieldList()
	 * @method \string[] fillField()
	 * @method \string[] getFromValueList()
	 * @method \string[] fillFromValue()
	 * @method \string[] getToValueList()
	 * @method \string[] fillToValue()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Log $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Log $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Log $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Log_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Log_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Log_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\LogTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\LogTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Log_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log_Collection fetchCollection()
	 */
	class EO_Log_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log_Collection fetchCollection()
	 */
	class EO_Log_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log_Collection wakeUpCollection($rows)
	 */
	class EO_Log_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ReminderTable:tasks/lib/internals/task/reminder.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Reminder
	 * @see \Bitrix\Tasks\Internals\Task\ReminderTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \Bitrix\Main\Type\DateTime getRemindDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder setRemindDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $remindDate)
	 * @method bool hasRemindDate()
	 * @method bool isRemindDateFilled()
	 * @method bool isRemindDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualRemindDate()
	 * @method \Bitrix\Main\Type\DateTime requireRemindDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder resetRemindDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder unsetRemindDate()
	 * @method \Bitrix\Main\Type\DateTime fillRemindDate()
	 * @method \string getType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder resetType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder unsetType()
	 * @method \string fillType()
	 * @method \string getTransport()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder setTransport(\string|\Bitrix\Main\DB\SqlExpression $transport)
	 * @method bool hasTransport()
	 * @method bool isTransportFilled()
	 * @method bool isTransportChanged()
	 * @method \string remindActualTransport()
	 * @method \string requireTransport()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder resetTransport()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder unsetTransport()
	 * @method \string fillTransport()
	 * @method \string getRecepientType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder setRecepientType(\string|\Bitrix\Main\DB\SqlExpression $recepientType)
	 * @method bool hasRecepientType()
	 * @method bool isRecepientTypeFilled()
	 * @method bool isRecepientTypeChanged()
	 * @method \string remindActualRecepientType()
	 * @method \string requireRecepientType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder resetRecepientType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder unsetRecepientType()
	 * @method \string fillRecepientType()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Reminder wakeUp($data)
	 */
	class EO_Reminder {
		/* @var \Bitrix\Tasks\Internals\Task\ReminderTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ReminderTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Reminder_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \Bitrix\Main\Type\DateTime[] getRemindDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillRemindDate()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getTransportList()
	 * @method \string[] fillTransport()
	 * @method \string[] getRecepientTypeList()
	 * @method \string[] fillRecepientType()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Reminder $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Reminder $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Reminder $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Reminder_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Reminder_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ReminderTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ReminderTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Reminder_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection fetchCollection()
	 */
	class EO_Reminder_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection fetchCollection()
	 */
	class EO_Reminder_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection wakeUpCollection($rows)
	 */
	class EO_Reminder_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\DependenceTable:tasks/lib/internals/task/dependence.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Dependence
	 * @see \Bitrix\Tasks\Internals\Task\DependenceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int getParentTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence setParentTaskId(\int|\Bitrix\Main\DB\SqlExpression $parentTaskId)
	 * @method bool hasParentTaskId()
	 * @method bool isParentTaskIdFilled()
	 * @method bool isParentTaskIdChanged()
	 * @method \int getDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence setDirect(\int|\Bitrix\Main\DB\SqlExpression $direct)
	 * @method bool hasDirect()
	 * @method bool isDirectFilled()
	 * @method bool isDirectChanged()
	 * @method \int remindActualDirect()
	 * @method \int requireDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence resetDirect()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence unsetDirect()
	 * @method \int fillDirect()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Dependence wakeUp($data)
	 */
	class EO_Dependence {
		/* @var \Bitrix\Tasks\Internals\Task\DependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\DependenceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Dependence_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \int[] getParentTaskIdList()
	 * @method \int[] getDirectList()
	 * @method \int[] fillDirect()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Dependence $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Dependence $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Dependence $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Dependence_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Dependence_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\DependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\DependenceTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Dependence_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection fetchCollection()
	 */
	class EO_Dependence_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection fetchCollection()
	 */
	class EO_Dependence_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection wakeUpCollection($rows)
	 */
	class EO_Dependence_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\CheckListTreeTable:tasks/lib/internals/task/checklisttree.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_CheckListTree
	 * @see \Bitrix\Tasks\Internals\Task\CheckListTreeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getParentId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int getChildId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree setChildId(\int|\Bitrix\Main\DB\SqlExpression $childId)
	 * @method bool hasChildId()
	 * @method bool isChildIdFilled()
	 * @method bool isChildIdChanged()
	 * @method \int getLevel()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree setLevel(\int|\Bitrix\Main\DB\SqlExpression $level)
	 * @method bool hasLevel()
	 * @method bool isLevelFilled()
	 * @method bool isLevelChanged()
	 * @method \int remindActualLevel()
	 * @method \int requireLevel()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree resetLevel()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree unsetLevel()
	 * @method \int fillLevel()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckListTree wakeUp($data)
	 */
	class EO_CheckListTree {
		/* @var \Bitrix\Tasks\Internals\Task\CheckListTreeTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\CheckListTreeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_CheckListTree_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getParentIdList()
	 * @method \int[] getChildIdList()
	 * @method \int[] getLevelList()
	 * @method \int[] fillLevel()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_CheckListTree $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_CheckListTree $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_CheckListTree $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_CheckListTree_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\CheckListTreeTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\CheckListTreeTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CheckListTree_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection fetchCollection()
	 */
	class EO_CheckListTree_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection fetchCollection()
	 */
	class EO_CheckListTree_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection wakeUpCollection($rows)
	 */
	class EO_CheckListTree_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\RegularParametersTable:tasks/lib/internals/task/regularparameterstable.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * RegularParametersObject
	 * @see \Bitrix\Tasks\Internals\Task\RegularParametersTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject unsetTaskId()
	 * @method \int fillTaskId()
	 * @method array getRegularParameters()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject setRegularParameters(array|\Bitrix\Main\DB\SqlExpression $regularParameters)
	 * @method bool hasRegularParameters()
	 * @method bool isRegularParametersFilled()
	 * @method bool isRegularParametersChanged()
	 * @method array remindActualRegularParameters()
	 * @method array requireRegularParameters()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject resetRegularParameters()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject unsetRegularParameters()
	 * @method array fillRegularParameters()
	 * @method \Bitrix\Main\Type\DateTime getStartTime()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject setStartTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $startTime)
	 * @method bool hasStartTime()
	 * @method bool isStartTimeFilled()
	 * @method bool isStartTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualStartTime()
	 * @method \Bitrix\Main\Type\DateTime requireStartTime()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject resetStartTime()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject unsetStartTime()
	 * @method \Bitrix\Main\Type\DateTime fillStartTime()
	 * @method \Bitrix\Main\Type\Date getStartDay()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject setStartDay(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $startDay)
	 * @method bool hasStartDay()
	 * @method bool isStartDayFilled()
	 * @method bool isStartDayChanged()
	 * @method \Bitrix\Main\Type\Date remindActualStartDay()
	 * @method \Bitrix\Main\Type\Date requireStartDay()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject resetStartDay()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject unsetStartDay()
	 * @method \Bitrix\Main\Type\Date fillStartDay()
	 * @method \boolean getNotificationSent()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject setNotificationSent(\boolean|\Bitrix\Main\DB\SqlExpression $notificationSent)
	 * @method bool hasNotificationSent()
	 * @method bool isNotificationSentFilled()
	 * @method bool isNotificationSentChanged()
	 * @method \boolean remindActualNotificationSent()
	 * @method \boolean requireNotificationSent()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject resetNotificationSent()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject unsetNotificationSent()
	 * @method \boolean fillNotificationSent()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\RegularParametersObject wakeUp($data)
	 */
	class EO_RegularParameters {
		/* @var \Bitrix\Tasks\Internals\Task\RegularParametersTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\RegularParametersTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * RegularParametersCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method array[] getRegularParametersList()
	 * @method array[] fillRegularParameters()
	 * @method \Bitrix\Main\Type\DateTime[] getStartTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillStartTime()
	 * @method \Bitrix\Main\Type\Date[] getStartDayList()
	 * @method \Bitrix\Main\Type\Date[] fillStartDay()
	 * @method \boolean[] getNotificationSentList()
	 * @method \boolean[] fillNotificationSent()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersCollection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\RegularParametersObject $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\RegularParametersObject $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\RegularParametersObject $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\RegularParametersCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersCollection merge(?\Bitrix\Tasks\Internals\Task\RegularParametersCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_RegularParameters_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\RegularParametersTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\RegularParametersTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_RegularParameters_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersCollection fetchCollection()
	 */
	class EO_RegularParameters_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersCollection fetchCollection()
	 */
	class EO_RegularParameters_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersCollection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersObject wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\RegularParametersCollection wakeUpCollection($rows)
	 */
	class EO_RegularParameters_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ScenarioTable:tasks/lib/internals/task/scenario.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Scenario
	 * @see \Bitrix\Tasks\Internals\Task\ScenarioTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \string getScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario setScenario(\string|\Bitrix\Main\DB\SqlExpression $scenario)
	 * @method bool hasScenario()
	 * @method bool isScenarioFilled()
	 * @method bool isScenarioChanged()
	 * @method \string remindActualScenario()
	 * @method \string requireScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario resetScenario()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario unsetScenario()
	 * @method \string fillScenario()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Scenario\Scenario wakeUp($data)
	 */
	class EO_Scenario {
		/* @var \Bitrix\Tasks\Internals\Task\ScenarioTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ScenarioTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Scenario_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \string[] getScenarioList()
	 * @method \string[] fillScenario()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Scenario\Scenario $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Scenario\Scenario $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Scenario\Scenario $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Scenario_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Scenario_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ScenarioTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ScenarioTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Scenario_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection fetchCollection()
	 */
	class EO_Scenario_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection fetchCollection()
	 */
	class EO_Scenario_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Scenario\Scenario wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection wakeUpCollection($rows)
	 */
	class EO_Scenario_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ProjectUserOptionTable:tasks/lib/internals/task/projectuseroption.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_ProjectUserOption
	 * @see \Bitrix\Tasks\Internals\Task\ProjectUserOptionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getProjectId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption setProjectId(\int|\Bitrix\Main\DB\SqlExpression $projectId)
	 * @method bool hasProjectId()
	 * @method bool isProjectIdFilled()
	 * @method bool isProjectIdChanged()
	 * @method \int remindActualProjectId()
	 * @method \int requireProjectId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption resetProjectId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption unsetProjectId()
	 * @method \int fillProjectId()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getOptionCode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption setOptionCode(\int|\Bitrix\Main\DB\SqlExpression $optionCode)
	 * @method bool hasOptionCode()
	 * @method bool isOptionCodeFilled()
	 * @method bool isOptionCodeChanged()
	 * @method \int remindActualOptionCode()
	 * @method \int requireOptionCode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption resetOptionCode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption unsetOptionCode()
	 * @method \int fillOptionCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption wakeUp($data)
	 */
	class EO_ProjectUserOption {
		/* @var \Bitrix\Tasks\Internals\Task\ProjectUserOptionTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ProjectUserOptionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_ProjectUserOption_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getProjectIdList()
	 * @method \int[] fillProjectId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getOptionCodeList()
	 * @method \int[] fillOptionCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_ProjectUserOption $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_ProjectUserOption $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_ProjectUserOption $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_ProjectUserOption_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ProjectUserOption_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ProjectUserOptionTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ProjectUserOptionTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ProjectUserOption_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption_Collection fetchCollection()
	 */
	class EO_ProjectUserOption_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption_Collection fetchCollection()
	 */
	class EO_ProjectUserOption_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectUserOption_Collection wakeUpCollection($rows)
	 */
	class EO_ProjectUserOption_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ProjectLastActivityTable:tasks/lib/internals/task/projectlastactivity.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_ProjectLastActivity
	 * @see \Bitrix\Tasks\Internals\Task\ProjectLastActivityTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getProjectId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity setProjectId(\int|\Bitrix\Main\DB\SqlExpression $projectId)
	 * @method bool hasProjectId()
	 * @method bool isProjectIdFilled()
	 * @method bool isProjectIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getActivityDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity setActivityDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $activityDate)
	 * @method bool hasActivityDate()
	 * @method bool isActivityDateFilled()
	 * @method bool isActivityDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualActivityDate()
	 * @method \Bitrix\Main\Type\DateTime requireActivityDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity resetActivityDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity unsetActivityDate()
	 * @method \Bitrix\Main\Type\DateTime fillActivityDate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity wakeUp($data)
	 */
	class EO_ProjectLastActivity {
		/* @var \Bitrix\Tasks\Internals\Task\ProjectLastActivityTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ProjectLastActivityTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_ProjectLastActivity_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getProjectIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getActivityDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillActivityDate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ProjectLastActivity_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ProjectLastActivityTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ProjectLastActivityTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ProjectLastActivity_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity_Collection fetchCollection()
	 */
	class EO_ProjectLastActivity_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity_Collection fetchCollection()
	 */
	class EO_ProjectLastActivity_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity_Collection wakeUpCollection($rows)
	 */
	class EO_ProjectLastActivity_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\CheckList\MemberTable:tasks/lib/internals/task/checklist/member.php */
namespace Bitrix\Tasks\Internals\Task\CheckList {
	/**
	 * EO_Member
	 * @see \Bitrix\Tasks\Internals\Task\CheckList\MemberTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getItemId()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member setItemId(\int|\Bitrix\Main\DB\SqlExpression $itemId)
	 * @method bool hasItemId()
	 * @method bool isItemIdFilled()
	 * @method bool isItemIdChanged()
	 * @method \int remindActualItemId()
	 * @method \int requireItemId()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member resetItemId()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member unsetItemId()
	 * @method \int fillItemId()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getType()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member resetType()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member unsetType()
	 * @method \string fillType()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList getChecklistItem()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList remindActualChecklistItem()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList requireChecklistItem()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member setChecklistItem(\Bitrix\Tasks\Internals\Task\EO_CheckList $object)
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member resetChecklistItem()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member unsetChecklistItem()
	 * @method bool hasChecklistItem()
	 * @method bool isChecklistItemFilled()
	 * @method bool isChecklistItemChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList fillChecklistItem()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\CheckList\EO_Member wakeUp($data)
	 */
	class EO_Member {
		/* @var \Bitrix\Tasks\Internals\Task\CheckList\MemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\CheckList\MemberTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\CheckList {
	/**
	 * EO_Member_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getItemIdList()
	 * @method \int[] fillItemId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList[] getChecklistItemList()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection getChecklistItemCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection fillChecklistItem()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\CheckList\EO_Member $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\CheckList\EO_Member $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\CheckList\EO_Member $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection merge(?\Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Member_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\CheckList\MemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\CheckList\MemberTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\CheckList {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Member_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection fetchCollection()
	 */
	class EO_Member_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection fetchCollection()
	 */
	class EO_Member_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection wakeUpCollection($rows)
	 */
	class EO_Member_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\TagTable:tasks/lib/internals/task/tag.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Tag
	 * @see \Bitrix\Tasks\Internals\Task\TagTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string getConverted()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag setConverted(\string|\Bitrix\Main\DB\SqlExpression $converted)
	 * @method bool hasConverted()
	 * @method bool isConvertedFilled()
	 * @method bool isConvertedChanged()
	 * @method \string remindActualConverted()
	 * @method \string requireConverted()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag resetConverted()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag unsetConverted()
	 * @method \string fillConverted()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Tag wakeUp($data)
	 */
	class EO_Tag {
		/* @var \Bitrix\Tasks\Internals\Task\TagTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TagTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Tag_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \int[] getUserIdList()
	 * @method \string[] getNameList()
	 * @method \string[] getConvertedList()
	 * @method \string[] fillConverted()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Tag $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Tag $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Tag $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Tag_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Tag_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Tag_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\TagTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TagTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Tag_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag_Collection fetchCollection()
	 */
	class EO_Tag_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag_Collection fetchCollection()
	 */
	class EO_Tag_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag_Collection wakeUpCollection($rows)
	 */
	class EO_Tag_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\UserOptionTable:tasks/lib/internals/task/useroption.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_UserOption
	 * @see \Bitrix\Tasks\Internals\Task\UserOptionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption resetUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getOptionCode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption setOptionCode(\int|\Bitrix\Main\DB\SqlExpression $optionCode)
	 * @method bool hasOptionCode()
	 * @method bool isOptionCodeFilled()
	 * @method bool isOptionCodeChanged()
	 * @method \int remindActualOptionCode()
	 * @method \int requireOptionCode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption resetOptionCode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption unsetOptionCode()
	 * @method \int fillOptionCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_UserOption wakeUp($data)
	 */
	class EO_UserOption {
		/* @var \Bitrix\Tasks\Internals\Task\UserOptionTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\UserOptionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_UserOption_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getOptionCodeList()
	 * @method \int[] fillOptionCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_UserOption $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_UserOption $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_UserOption $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_UserOption_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_UserOption_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\UserOptionTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\UserOptionTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UserOption_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection fetchCollection()
	 */
	class EO_UserOption_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection fetchCollection()
	 */
	class EO_UserOption_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection wakeUpCollection($rows)
	 */
	class EO_UserOption_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\UtsTasksTaskTable:tasks/lib/internals/task/utstaskstask.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_UtsTasksTask
	 * @see \Bitrix\Tasks\Internals\Task\UtsTasksTaskTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getValueId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask setValueId(\int|\Bitrix\Main\DB\SqlExpression $valueId)
	 * @method bool hasValueId()
	 * @method bool isValueIdFilled()
	 * @method bool isValueIdChanged()
	 * @method array getUfCrmTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask setUfCrmTask(array|\Bitrix\Main\DB\SqlExpression $ufCrmTask)
	 * @method bool hasUfCrmTask()
	 * @method bool isUfCrmTaskFilled()
	 * @method bool isUfCrmTaskChanged()
	 * @method array remindActualUfCrmTask()
	 * @method array requireUfCrmTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask resetUfCrmTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask unsetUfCrmTask()
	 * @method array fillUfCrmTask()
	 * @method array getUfTaskWebdavFiles()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask setUfTaskWebdavFiles(array|\Bitrix\Main\DB\SqlExpression $ufTaskWebdavFiles)
	 * @method bool hasUfTaskWebdavFiles()
	 * @method bool isUfTaskWebdavFilesFilled()
	 * @method bool isUfTaskWebdavFilesChanged()
	 * @method array remindActualUfTaskWebdavFiles()
	 * @method array requireUfTaskWebdavFiles()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask resetUfTaskWebdavFiles()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask unsetUfTaskWebdavFiles()
	 * @method array fillUfTaskWebdavFiles()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask wakeUp($data)
	 */
	class EO_UtsTasksTask {
		/* @var \Bitrix\Tasks\Internals\Task\UtsTasksTaskTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\UtsTasksTaskTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_UtsTasksTask_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getValueIdList()
	 * @method array[] getUfCrmTaskList()
	 * @method array[] fillUfCrmTask()
	 * @method array[] getUfTaskWebdavFilesList()
	 * @method array[] fillUfTaskWebdavFiles()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_UtsTasksTask $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_UtsTasksTask $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_UtsTasksTask $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_UtsTasksTask_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\UtsTasksTaskTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\UtsTasksTaskTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UtsTasksTask_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection fetchCollection()
	 */
	class EO_UtsTasksTask_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection fetchCollection()
	 */
	class EO_UtsTasksTask_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection wakeUpCollection($rows)
	 */
	class EO_UtsTasksTask_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\TaskTagTable:tasks/lib/internals/task/tasktag.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_TaskTag
	 * @see \Bitrix\Tasks\Internals\Task\TaskTagTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTagId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag setTagId(\int|\Bitrix\Main\DB\SqlExpression $tagId)
	 * @method bool hasTagId()
	 * @method bool isTagIdFilled()
	 * @method bool isTagIdChanged()
	 * @method \int remindActualTagId()
	 * @method \int requireTagId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag resetTagId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag unsetTagId()
	 * @method \int fillTagId()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject getTag()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject remindActualTag()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject requireTag()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag setTag(\Bitrix\Tasks\Internals\Task\TagObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag resetTag()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag unsetTag()
	 * @method bool hasTag()
	 * @method bool isTagFilled()
	 * @method bool isTagChanged()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject fillTag()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_TaskTag wakeUp($data)
	 */
	class EO_TaskTag {
		/* @var \Bitrix\Tasks\Internals\Task\TaskTagTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TaskTagTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_TaskTag_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTagIdList()
	 * @method \int[] fillTagId()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 * @method \Bitrix\Tasks\Internals\Task\TagObject[] getTagList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection getTagCollection()
	 * @method \Bitrix\Tasks\Internals\Task\TagCollection fillTag()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_TaskTag $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_TaskTag $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_TaskTag $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TaskTag_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\TaskTagTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TaskTagTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TaskTag_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection fetchCollection()
	 */
	class EO_TaskTag_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection fetchCollection()
	 */
	class EO_TaskTag_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection wakeUpCollection($rows)
	 */
	class EO_TaskTag_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Result\ResultTable:tasks/lib/internals/task/result/resulttable.php */
namespace Bitrix\Tasks\Internals\Task\Result {
	/**
	 * Result
	 * @see \Bitrix\Tasks\Internals\Task\Result\ResultTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getCommentId()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result setCommentId(\int|\Bitrix\Main\DB\SqlExpression $commentId)
	 * @method bool hasCommentId()
	 * @method bool isCommentIdFilled()
	 * @method bool isCommentIdChanged()
	 * @method \int remindActualCommentId()
	 * @method \int requireCommentId()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result resetCommentId()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result unsetCommentId()
	 * @method \int fillCommentId()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result resetCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime getCreatedAt()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result setCreatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdAt)
	 * @method bool hasCreatedAt()
	 * @method bool isCreatedAtFilled()
	 * @method bool isCreatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedAt()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result resetCreatedAt()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result unsetCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedAt()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result setUpdatedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedAt)
	 * @method bool hasUpdatedAt()
	 * @method bool isUpdatedAtFilled()
	 * @method bool isUpdatedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedAt()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result resetUpdatedAt()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result unsetUpdatedAt()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedAt()
	 * @method \string getText()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result setText(\string|\Bitrix\Main\DB\SqlExpression $text)
	 * @method bool hasText()
	 * @method bool isTextFilled()
	 * @method bool isTextChanged()
	 * @method \string remindActualText()
	 * @method \string requireText()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result resetText()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result unsetText()
	 * @method \string fillText()
	 * @method \int getStatus()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result setStatus(\int|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \int remindActualStatus()
	 * @method \int requireStatus()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result resetStatus()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result unsetStatus()
	 * @method \int fillStatus()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\Result\Result wakeUp($data)
	 */
	class EO_Result {
		/* @var \Bitrix\Tasks\Internals\Task\Result\ResultTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Result\ResultTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task\Result {
	/**
	 * EO_Result_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getCommentIdList()
	 * @method \int[] fillCommentId()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedAt()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedAt()
	 * @method \string[] getTextList()
	 * @method \string[] fillText()
	 * @method \int[] getStatusList()
	 * @method \int[] fillStatus()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\Result\Result $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\Result\Result $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\Result\Result $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection merge(?\Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Result_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Result\ResultTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Result\ResultTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Result {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Result_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection fetchCollection()
	 */
	class EO_Result_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection fetchCollection()
	 */
	class EO_Result_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\Result\Result wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\Result\EO_Result_Collection wakeUpCollection($rows)
	 */
	class EO_Result_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\RelatedTable:tasks/lib/internals/task/related.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Related
	 * @see \Bitrix\Tasks\Internals\Task\RelatedTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int getDependsOnId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related setDependsOnId(\int|\Bitrix\Main\DB\SqlExpression $dependsOnId)
	 * @method bool hasDependsOnId()
	 * @method bool isDependsOnIdFilled()
	 * @method bool isDependsOnIdChanged()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Related wakeUp($data)
	 */
	class EO_Related {
		/* @var \Bitrix\Tasks\Internals\Task\RelatedTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\RelatedTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Related_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \int[] getDependsOnIdList()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Related $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Related $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Related $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Related_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_Related_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Related_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\RelatedTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\RelatedTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Related_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related_Collection fetchCollection()
	 */
	class EO_Related_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related_Collection fetchCollection()
	 */
	class EO_Related_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related_Collection wakeUpCollection($rows)
	 */
	class EO_Related_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ViewedGroupTable:tasks/lib/internals/task/viewedgroup.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_ViewedGroup
	 * @see \Bitrix\Tasks\Internals\Task\ViewedGroupTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \string getMemberType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup setMemberType(\string|\Bitrix\Main\DB\SqlExpression $memberType)
	 * @method bool hasMemberType()
	 * @method bool isMemberTypeFilled()
	 * @method bool isMemberTypeChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int getTypeId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup setTypeId(\int|\Bitrix\Main\DB\SqlExpression $typeId)
	 * @method bool hasTypeId()
	 * @method bool isTypeIdFilled()
	 * @method bool isTypeIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getViewedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup setViewedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $viewedDate)
	 * @method bool hasViewedDate()
	 * @method bool isViewedDateFilled()
	 * @method bool isViewedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualViewedDate()
	 * @method \Bitrix\Main\Type\DateTime requireViewedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup resetViewedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup unsetViewedDate()
	 * @method \Bitrix\Main\Type\DateTime fillViewedDate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ViewedGroup wakeUp($data)
	 */
	class EO_ViewedGroup {
		/* @var \Bitrix\Tasks\Internals\Task\ViewedGroupTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ViewedGroupTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_ViewedGroup_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getGroupIdList()
	 * @method \string[] getMemberTypeList()
	 * @method \int[] getUserIdList()
	 * @method \int[] getTypeIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getViewedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillViewedDate()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_ViewedGroup $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_ViewedGroup $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_ViewedGroup $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ViewedGroup_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_ViewedGroup_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ViewedGroup_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ViewedGroupTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ViewedGroupTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ViewedGroup_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup_Collection fetchCollection()
	 */
	class EO_ViewedGroup_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup_Collection fetchCollection()
	 */
	class EO_ViewedGroup_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_ViewedGroup_Collection wakeUpCollection($rows)
	 */
	class EO_ViewedGroup_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\CheckListTable:tasks/lib/internals/task/checklist.php */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_CheckList
	 * @see \Bitrix\Tasks\Internals\Task\CheckListTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList resetCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getToggledBy()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList setToggledBy(\int|\Bitrix\Main\DB\SqlExpression $toggledBy)
	 * @method bool hasToggledBy()
	 * @method bool isToggledByFilled()
	 * @method bool isToggledByChanged()
	 * @method \int remindActualToggledBy()
	 * @method \int requireToggledBy()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList resetToggledBy()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList unsetToggledBy()
	 * @method \int fillToggledBy()
	 * @method \Bitrix\Main\Type\DateTime getToggledDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList setToggledDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $toggledDate)
	 * @method bool hasToggledDate()
	 * @method bool isToggledDateFilled()
	 * @method bool isToggledDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualToggledDate()
	 * @method \Bitrix\Main\Type\DateTime requireToggledDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList resetToggledDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList unsetToggledDate()
	 * @method \Bitrix\Main\Type\DateTime fillToggledDate()
	 * @method \string getTitle()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList resetTitle()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList unsetTitle()
	 * @method \string fillTitle()
	 * @method \boolean getIsComplete()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList setIsComplete(\boolean|\Bitrix\Main\DB\SqlExpression $isComplete)
	 * @method bool hasIsComplete()
	 * @method bool isIsCompleteFilled()
	 * @method bool isIsCompleteChanged()
	 * @method \boolean remindActualIsComplete()
	 * @method \boolean requireIsComplete()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList resetIsComplete()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList unsetIsComplete()
	 * @method \boolean fillIsComplete()
	 * @method \boolean getIsImportant()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList setIsImportant(\boolean|\Bitrix\Main\DB\SqlExpression $isImportant)
	 * @method bool hasIsImportant()
	 * @method bool isIsImportantFilled()
	 * @method bool isIsImportantChanged()
	 * @method \boolean remindActualIsImportant()
	 * @method \boolean requireIsImportant()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList resetIsImportant()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList unsetIsImportant()
	 * @method \boolean fillIsImportant()
	 * @method \int getSortIndex()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList setSortIndex(\int|\Bitrix\Main\DB\SqlExpression $sortIndex)
	 * @method bool hasSortIndex()
	 * @method bool isSortIndexFilled()
	 * @method bool isSortIndexChanged()
	 * @method \int remindActualSortIndex()
	 * @method \int requireSortIndex()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList resetSortIndex()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList unsetSortIndex()
	 * @method \int fillSortIndex()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree getTreeByChild()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree remindActualTreeByChild()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree requireTreeByChild()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList setTreeByChild(\Bitrix\Tasks\Internals\Task\EO_CheckListTree $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList resetTreeByChild()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList unsetTreeByChild()
	 * @method bool hasTreeByChild()
	 * @method bool isTreeByChildFilled()
	 * @method bool isTreeByChildChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree fillTreeByChild()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckList wakeUp($data)
	 */
	class EO_CheckList {
		/* @var \Bitrix\Tasks\Internals\Task\CheckListTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\CheckListTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_CheckList_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getToggledByList()
	 * @method \int[] fillToggledBy()
	 * @method \Bitrix\Main\Type\DateTime[] getToggledDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillToggledDate()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \boolean[] getIsCompleteList()
	 * @method \boolean[] fillIsComplete()
	 * @method \boolean[] getIsImportantList()
	 * @method \boolean[] fillIsImportant()
	 * @method \int[] getSortIndexList()
	 * @method \int[] fillSortIndex()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree[] getTreeByChildList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection getTreeByChildCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection fillTreeByChild()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_CheckList $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_CheckList $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_CheckList $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection merge(?\Bitrix\Tasks\Internals\Task\EO_CheckList_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_CheckList_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\CheckListTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\CheckListTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CheckList_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection fetchCollection()
	 */
	class EO_CheckList_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection fetchCollection()
	 */
	class EO_CheckList_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection wakeUpCollection($rows)
	 */
	class EO_CheckList_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Counter\Queue\QueueTable:tasks/lib/internals/counter/queue/queuetable.php */
namespace Bitrix\Tasks\Internals\Counter\Queue {
	/**
	 * EO_Queue
	 * @see \Bitrix\Tasks\Internals\Counter\Queue\QueueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue resetUserId()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getType()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue resetType()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue unsetType()
	 * @method \string fillType()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \Bitrix\Main\Type\DateTime getDatetime()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue setDatetime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $datetime)
	 * @method bool hasDatetime()
	 * @method bool isDatetimeFilled()
	 * @method bool isDatetimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDatetime()
	 * @method \Bitrix\Main\Type\DateTime requireDatetime()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue resetDatetime()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue unsetDatetime()
	 * @method \Bitrix\Main\Type\DateTime fillDatetime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue wakeUp($data)
	 */
	class EO_Queue {
		/* @var \Bitrix\Tasks\Internals\Counter\Queue\QueueTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Counter\Queue\QueueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Counter\Queue {
	/**
	 * EO_Queue_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \Bitrix\Main\Type\DateTime[] getDatetimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDatetime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Counter\Queue\EO_Queue $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Counter\Queue\EO_Queue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Counter\Queue\EO_Queue $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue_Collection merge(?\Bitrix\Tasks\Internals\Counter\Queue\EO_Queue_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Queue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Counter\Queue\QueueTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Counter\Queue\QueueTable';
	}
}
namespace Bitrix\Tasks\Internals\Counter\Queue {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Queue_Result exec()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue fetchObject()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue_Collection fetchCollection()
	 */
	class EO_Queue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue fetchObject()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue_Collection fetchCollection()
	 */
	class EO_Queue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Counter\Queue\EO_Queue_Collection wakeUpCollection($rows)
	 */
	class EO_Queue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Counter\CounterTable:tasks/lib/internals/counter/countertable.php */
namespace Bitrix\Tasks\Internals\Counter {
	/**
	 * EO_Counter
	 * @see \Bitrix\Tasks\Internals\Counter\CounterTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetUserId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetGroupId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \string getType()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetType()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetType()
	 * @method \string fillType()
	 * @method \int getValue()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setValue(\int|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \int remindActualValue()
	 * @method \int requireValue()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetValue()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetValue()
	 * @method \int fillValue()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetUser()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity getGroup()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity remindActualGroup()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity requireGroup()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setGroup(\Bitrix\Socialnetwork\Internals\Group\GroupEntity $object)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetGroup()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetGroup()
	 * @method bool hasGroup()
	 * @method bool isGroupFilled()
	 * @method bool isGroupChanged()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity fillGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetTask()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Counter wakeUp($data)
	 */
	class EO_Counter {
		/* @var \Bitrix\Tasks\Internals\Counter\CounterTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Counter\CounterTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Counter {
	/**
	 * EO_Counter_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getValueList()
	 * @method \int[] fillValue()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity[] getGroupList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection getGroupCollection()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntityCollection fillGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Counter\EO_Counter $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Counter\EO_Counter $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Counter\EO_Counter $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection merge(?\Bitrix\Tasks\Internals\Counter\EO_Counter_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Counter_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Counter\CounterTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Counter\CounterTable';
	}
}
namespace Bitrix\Tasks\Internals\Counter {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Counter_Result exec()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter fetchObject()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection fetchCollection()
	 */
	class EO_Counter_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter fetchObject()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection fetchCollection()
	 */
	class EO_Counter_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection wakeUpCollection($rows)
	 */
	class EO_Counter_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Counter\Event\EventTable:tasks/lib/internals/counter/event/eventtable.php */
namespace Bitrix\Tasks\Internals\Counter\Event {
	/**
	 * EO_Event
	 * @see \Bitrix\Tasks\Internals\Counter\Event\EventTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getHid()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event setHid(\string|\Bitrix\Main\DB\SqlExpression $hid)
	 * @method bool hasHid()
	 * @method bool isHidFilled()
	 * @method bool isHidChanged()
	 * @method \string remindActualHid()
	 * @method \string requireHid()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event resetHid()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event unsetHid()
	 * @method \string fillHid()
	 * @method \string getType()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event resetType()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event unsetType()
	 * @method \string fillType()
	 * @method \string getData()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event setData(\string|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method \string remindActualData()
	 * @method \string requireData()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event resetData()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event unsetData()
	 * @method \string fillData()
	 * @method \string getTaskData()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event setTaskData(\string|\Bitrix\Main\DB\SqlExpression $taskData)
	 * @method bool hasTaskData()
	 * @method bool isTaskDataFilled()
	 * @method bool isTaskDataChanged()
	 * @method \string remindActualTaskData()
	 * @method \string requireTaskData()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event resetTaskData()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event unsetTaskData()
	 * @method \string fillTaskData()
	 * @method \Bitrix\Main\Type\DateTime getCreated()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event setCreated(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $created)
	 * @method bool hasCreated()
	 * @method bool isCreatedFilled()
	 * @method bool isCreatedChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreated()
	 * @method \Bitrix\Main\Type\DateTime requireCreated()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event resetCreated()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event unsetCreated()
	 * @method \Bitrix\Main\Type\DateTime fillCreated()
	 * @method \Bitrix\Main\Type\DateTime getProcessed()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event setProcessed(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $processed)
	 * @method bool hasProcessed()
	 * @method bool isProcessedFilled()
	 * @method bool isProcessedChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualProcessed()
	 * @method \Bitrix\Main\Type\DateTime requireProcessed()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event resetProcessed()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event unsetProcessed()
	 * @method \Bitrix\Main\Type\DateTime fillProcessed()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Counter\Event\EO_Event wakeUp($data)
	 */
	class EO_Event {
		/* @var \Bitrix\Tasks\Internals\Counter\Event\EventTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Counter\Event\EventTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Counter\Event {
	/**
	 * EO_Event_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getHidList()
	 * @method \string[] fillHid()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \string[] getDataList()
	 * @method \string[] fillData()
	 * @method \string[] getTaskDataList()
	 * @method \string[] fillTaskData()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreated()
	 * @method \Bitrix\Main\Type\DateTime[] getProcessedList()
	 * @method \Bitrix\Main\Type\DateTime[] fillProcessed()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Counter\Event\EO_Event $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Counter\Event\EO_Event $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Counter\Event\EO_Event $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Counter\Event\EO_Event_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event_Collection merge(?\Bitrix\Tasks\Internals\Counter\Event\EO_Event_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Event_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Counter\Event\EventTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Counter\Event\EventTable';
	}
}
namespace Bitrix\Tasks\Internals\Counter\Event {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Event_Result exec()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event fetchObject()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event_Collection fetchCollection()
	 */
	class EO_Event_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event fetchObject()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event_Collection fetchCollection()
	 */
	class EO_Event_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Counter\Event\EO_Event_Collection wakeUpCollection($rows)
	 */
	class EO_Event_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Counter\EffectiveTable:tasks/lib/internals/counter/effective.php */
namespace Bitrix\Tasks\Internals\Counter {
	/**
	 * EO_Effective
	 * @see \Bitrix\Tasks\Internals\Counter\EffectiveTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getDatetime()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setDatetime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $datetime)
	 * @method bool hasDatetime()
	 * @method bool isDatetimeFilled()
	 * @method bool isDatetimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDatetime()
	 * @method \Bitrix\Main\Type\DateTime requireDatetime()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetDatetime()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetDatetime()
	 * @method \Bitrix\Main\Type\DateTime fillDatetime()
	 * @method \Bitrix\Main\Type\DateTime getDatetimeRepair()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setDatetimeRepair(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $datetimeRepair)
	 * @method bool hasDatetimeRepair()
	 * @method bool isDatetimeRepairFilled()
	 * @method bool isDatetimeRepairChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDatetimeRepair()
	 * @method \Bitrix\Main\Type\DateTime requireDatetimeRepair()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetDatetimeRepair()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetDatetimeRepair()
	 * @method \Bitrix\Main\Type\DateTime fillDatetimeRepair()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetUserId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getUserType()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setUserType(\string|\Bitrix\Main\DB\SqlExpression $userType)
	 * @method bool hasUserType()
	 * @method bool isUserTypeFilled()
	 * @method bool isUserTypeChanged()
	 * @method \string remindActualUserType()
	 * @method \string requireUserType()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetUserType()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetUserType()
	 * @method \string fillUserType()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetGroupId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \int getEffective()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setEffective(\int|\Bitrix\Main\DB\SqlExpression $effective)
	 * @method bool hasEffective()
	 * @method bool isEffectiveFilled()
	 * @method bool isEffectiveChanged()
	 * @method \int remindActualEffective()
	 * @method \int requireEffective()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetEffective()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetEffective()
	 * @method \int fillEffective()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \string getTaskTitle()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setTaskTitle(\string|\Bitrix\Main\DB\SqlExpression $taskTitle)
	 * @method bool hasTaskTitle()
	 * @method bool isTaskTitleFilled()
	 * @method bool isTaskTitleChanged()
	 * @method \string remindActualTaskTitle()
	 * @method \string requireTaskTitle()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetTaskTitle()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetTaskTitle()
	 * @method \string fillTaskTitle()
	 * @method \Bitrix\Main\Type\DateTime getTaskDeadline()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setTaskDeadline(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $taskDeadline)
	 * @method bool hasTaskDeadline()
	 * @method bool isTaskDeadlineFilled()
	 * @method bool isTaskDeadlineChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTaskDeadline()
	 * @method \Bitrix\Main\Type\DateTime requireTaskDeadline()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetTaskDeadline()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetTaskDeadline()
	 * @method \Bitrix\Main\Type\DateTime fillTaskDeadline()
	 * @method \string getIsViolation()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setIsViolation(\string|\Bitrix\Main\DB\SqlExpression $isViolation)
	 * @method bool hasIsViolation()
	 * @method bool isIsViolationFilled()
	 * @method bool isIsViolationChanged()
	 * @method \string remindActualIsViolation()
	 * @method \string requireIsViolation()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetIsViolation()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetIsViolation()
	 * @method \string fillIsViolation()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetUser()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity getGroup()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity remindActualGroup()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity requireGroup()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setGroup(\Bitrix\Socialnetwork\Internals\Group\GroupEntity $object)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetGroup()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetGroup()
	 * @method bool hasGroup()
	 * @method bool isGroupFilled()
	 * @method bool isGroupChanged()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity fillGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetTask()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity getRecycle()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity remindActualRecycle()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity requireRecycle()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setRecycle(\Bitrix\Recyclebin\Internals\Models\RecyclebinEntity $object)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetRecycle()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetRecycle()
	 * @method bool hasRecycle()
	 * @method bool isRecycleFilled()
	 * @method bool isRecycleChanged()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity fillRecycle()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Effective wakeUp($data)
	 */
	class EO_Effective {
		/* @var \Bitrix\Tasks\Internals\Counter\EffectiveTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Counter\EffectiveTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Internals\Counter {
	/**
	 * EO_Effective_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getDatetimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDatetime()
	 * @method \Bitrix\Main\Type\DateTime[] getDatetimeRepairList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDatetimeRepair()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getUserTypeList()
	 * @method \string[] fillUserType()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \int[] getEffectiveList()
	 * @method \int[] fillEffective()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \string[] getTaskTitleList()
	 * @method \string[] fillTaskTitle()
	 * @method \Bitrix\Main\Type\DateTime[] getTaskDeadlineList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTaskDeadline()
	 * @method \string[] getIsViolationList()
	 * @method \string[] fillIsViolation()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntity[] getGroupList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection getGroupCollection()
	 * @method \Bitrix\Socialnetwork\Internals\Group\GroupEntityCollection fillGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntity[] getRecycleList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection getRecycleCollection()
	 * @method \Bitrix\Recyclebin\Internals\Models\RecyclebinEntityCollection fillRecycle()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Counter\EO_Effective $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Counter\EO_Effective $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Counter\EO_Effective $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection merge(?\Bitrix\Tasks\Internals\Counter\EO_Effective_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Effective_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Counter\EffectiveTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Counter\EffectiveTable';
	}
}
namespace Bitrix\Tasks\Internals\Counter {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Effective_Result exec()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective fetchObject()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection fetchCollection()
	 */
	class EO_Effective_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective fetchObject()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection fetchCollection()
	 */
	class EO_Effective_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection wakeUpCollection($rows)
	 */
	class EO_Effective_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowCopilotCollectedDataTable:tasks/lib/Flow/Internal/FlowCopilotCollectedDataTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * FlowCopilotCollectedData
	 * @see \Bitrix\Tasks\Flow\Internal\FlowCopilotCollectedDataTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData setFlowId(\int|\Bitrix\Main\DB\SqlExpression $flowId)
	 * @method bool hasFlowId()
	 * @method bool isFlowIdFilled()
	 * @method bool isFlowIdChanged()
	 * @method array getData()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData setData(array|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method array remindActualData()
	 * @method array requireData()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData resetData()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData unsetData()
	 * @method array fillData()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity getFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity remindActualFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity requireFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData setFlow(\Bitrix\Tasks\Flow\Internal\Entity\FlowEntity $object)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData resetFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData unsetFlow()
	 * @method bool hasFlow()
	 * @method bool isFlowFilled()
	 * @method bool isFlowChanged()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity fillFlow()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData wakeUp($data)
	 */
	class EO_FlowCopilotCollectedData {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowCopilotCollectedDataTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowCopilotCollectedDataTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowCopilotCollectedData_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getFlowIdList()
	 * @method array[] getDataList()
	 * @method array[] fillData()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity[] getFlowList()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection getFlowCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection fillFlow()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection merge(?\Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FlowCopilotCollectedData_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowCopilotCollectedDataTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowCopilotCollectedDataTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FlowCopilotCollectedData_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection fetchCollection()
	 */
	class EO_FlowCopilotCollectedData_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection fetchCollection()
	 */
	class EO_FlowCopilotCollectedData_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection wakeUpCollection($rows)
	 */
	class EO_FlowCopilotCollectedData_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowTaskTable:tasks/lib/Flow/Internal/FlowTaskTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowTask
	 * @see \Bitrix\Tasks\Flow\Internal\FlowTaskTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask setFlowId(\int|\Bitrix\Main\DB\SqlExpression $flowId)
	 * @method bool hasFlowId()
	 * @method bool isFlowIdFilled()
	 * @method bool isFlowIdChanged()
	 * @method \int remindActualFlowId()
	 * @method \int requireFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask resetFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask unsetFlowId()
	 * @method \int fillFlowId()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask resetTaskId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getTask()
	 * @method \Bitrix\Tasks\Internals\TaskCollection requireTask()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method void addToTask(\Bitrix\Tasks\Internals\TaskObject $task)
	 * @method void removeFromTask(\Bitrix\Tasks\Internals\TaskObject $task)
	 * @method void removeAllTask()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask resetTask()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask unsetTask()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity getFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity remindActualFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity requireFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask setFlow(\Bitrix\Tasks\Flow\Internal\Entity\FlowEntity $object)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask resetFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask unsetFlow()
	 * @method bool hasFlow()
	 * @method bool isFlowFilled()
	 * @method bool isFlowChanged()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity fillFlow()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowTask wakeUp($data)
	 */
	class EO_FlowTask {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowTaskTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowTaskTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowTask_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFlowIdList()
	 * @method \int[] fillFlowId()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \Bitrix\Tasks\Internals\TaskCollection[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity[] getFlowList()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection getFlowCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection fillFlow()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\EO_FlowTask $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\EO_FlowTask $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\EO_FlowTask $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection merge(?\Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FlowTask_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowTaskTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowTaskTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FlowTask_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection fetchCollection()
	 */
	class EO_FlowTask_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection fetchCollection()
	 */
	class EO_FlowTask_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection wakeUpCollection($rows)
	 */
	class EO_FlowTask_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowMemberTable:tasks/lib/Flow/Internal/FlowMemberTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * FlowMember
	 * @see \Bitrix\Tasks\Flow\Internal\FlowMemberTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember setFlowId(\int|\Bitrix\Main\DB\SqlExpression $flowId)
	 * @method bool hasFlowId()
	 * @method bool isFlowIdFilled()
	 * @method bool isFlowIdChanged()
	 * @method \int remindActualFlowId()
	 * @method \int requireFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember resetFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember unsetFlowId()
	 * @method \int fillFlowId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember resetAccessCode()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \int getEntityId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember resetEntityId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember resetEntityType()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \string getRole()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember setRole(\string|\Bitrix\Main\DB\SqlExpression $role)
	 * @method bool hasRole()
	 * @method bool isRoleFilled()
	 * @method bool isRoleChanged()
	 * @method \string remindActualRole()
	 * @method \string requireRole()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember resetRole()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember unsetRole()
	 * @method \string fillRole()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity getFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity remindActualFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity requireFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember setFlow(\Bitrix\Tasks\Flow\Internal\Entity\FlowEntity $object)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember resetFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember unsetFlow()
	 * @method bool hasFlow()
	 * @method bool isFlowFilled()
	 * @method bool isFlowChanged()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity fillFlow()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember resetUser()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowMember wakeUp($data)
	 */
	class EO_FlowMember {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowMemberTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowMemberTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * FlowMemberCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFlowIdList()
	 * @method \int[] fillFlowId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \string[] getRoleList()
	 * @method \string[] fillRole()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity[] getFlowList()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection getFlowCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection fillFlow()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\Entity\FlowMember $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\Entity\FlowMember $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\Entity\FlowMember $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection merge(?\Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FlowMember_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowMemberTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowMemberTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FlowMember_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection fetchCollection()
	 */
	class EO_FlowMember_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection fetchCollection()
	 */
	class EO_FlowMember_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMember wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection wakeUpCollection($rows)
	 */
	class EO_FlowMember_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowTable:tasks/lib/Flow/Internal/FlowTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * FlowEntity
	 * @see \Bitrix\Tasks\Flow\Internal\FlowTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getCreatorId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setCreatorId(\int|\Bitrix\Main\DB\SqlExpression $creatorId)
	 * @method bool hasCreatorId()
	 * @method bool isCreatorIdFilled()
	 * @method bool isCreatorIdChanged()
	 * @method \int remindActualCreatorId()
	 * @method \int requireCreatorId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetCreatorId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetCreatorId()
	 * @method \int fillCreatorId()
	 * @method \int getOwnerId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setOwnerId(\int|\Bitrix\Main\DB\SqlExpression $ownerId)
	 * @method bool hasOwnerId()
	 * @method bool isOwnerIdFilled()
	 * @method bool isOwnerIdChanged()
	 * @method \int remindActualOwnerId()
	 * @method \int requireOwnerId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetOwnerId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetOwnerId()
	 * @method \int fillOwnerId()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetGroupId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \int getTemplateId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \int remindActualTemplateId()
	 * @method \int requireTemplateId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetTemplateId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetTemplateId()
	 * @method \int fillTemplateId()
	 * @method \int getEfficiency()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setEfficiency(\int|\Bitrix\Main\DB\SqlExpression $efficiency)
	 * @method bool hasEfficiency()
	 * @method bool isEfficiencyFilled()
	 * @method bool isEfficiencyChanged()
	 * @method \int remindActualEfficiency()
	 * @method \int requireEfficiency()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetEfficiency()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetEfficiency()
	 * @method \int fillEfficiency()
	 * @method \boolean getActive()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setActive(\boolean|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \boolean remindActualActive()
	 * @method \boolean requireActive()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetActive()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetActive()
	 * @method \boolean fillActive()
	 * @method \int getPlannedCompletionTime()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setPlannedCompletionTime(\int|\Bitrix\Main\DB\SqlExpression $plannedCompletionTime)
	 * @method bool hasPlannedCompletionTime()
	 * @method bool isPlannedCompletionTimeFilled()
	 * @method bool isPlannedCompletionTimeChanged()
	 * @method \int remindActualPlannedCompletionTime()
	 * @method \int requirePlannedCompletionTime()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetPlannedCompletionTime()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetPlannedCompletionTime()
	 * @method \int fillPlannedCompletionTime()
	 * @method \Bitrix\Main\Type\DateTime getActivity()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setActivity(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $activity)
	 * @method bool hasActivity()
	 * @method bool isActivityFilled()
	 * @method bool isActivityChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualActivity()
	 * @method \Bitrix\Main\Type\DateTime requireActivity()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetActivity()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetActivity()
	 * @method \Bitrix\Main\Type\DateTime fillActivity()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetName()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetName()
	 * @method \string fillName()
	 * @method \string getDescription()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetDescription()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetDescription()
	 * @method \string fillDescription()
	 * @method \string getDistributionType()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setDistributionType(\string|\Bitrix\Main\DB\SqlExpression $distributionType)
	 * @method bool hasDistributionType()
	 * @method bool isDistributionTypeFilled()
	 * @method bool isDistributionTypeChanged()
	 * @method \string remindActualDistributionType()
	 * @method \string requireDistributionType()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetDistributionType()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetDistributionType()
	 * @method \string fillDistributionType()
	 * @method \boolean getDemo()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity setDemo(\boolean|\Bitrix\Main\DB\SqlExpression $demo)
	 * @method bool hasDemo()
	 * @method bool isDemoFilled()
	 * @method bool isDemoChanged()
	 * @method \boolean remindActualDemo()
	 * @method \boolean requireDemo()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetDemo()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetDemo()
	 * @method \boolean fillDemo()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getTask()
	 * @method \Bitrix\Tasks\Internals\TaskCollection requireTask()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method void addToTask(\Bitrix\Tasks\Internals\TaskObject $task)
	 * @method void removeFromTask(\Bitrix\Tasks\Internals\TaskObject $task)
	 * @method void removeAllTask()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetTask()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetTask()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection getMembers()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection requireMembers()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection fillMembers()
	 * @method bool hasMembers()
	 * @method bool isMembersFilled()
	 * @method bool isMembersChanged()
	 * @method void addToMembers(\Bitrix\Tasks\Flow\Internal\Entity\FlowMember $flowMember)
	 * @method void removeFromMembers(\Bitrix\Tasks\Flow\Internal\Entity\FlowMember $flowMember)
	 * @method void removeAllMembers()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetMembers()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetMembers()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection getOptions()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection requireOptions()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection fillOptions()
	 * @method bool hasOptions()
	 * @method bool isOptionsFilled()
	 * @method bool isOptionsChanged()
	 * @method void addToOptions(\Bitrix\Tasks\Flow\Internal\Entity\FlowOption $flowOption)
	 * @method void removeFromOptions(\Bitrix\Tasks\Flow\Internal\Entity\FlowOption $flowOption)
	 * @method void removeAllOptions()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetOptions()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetOptions()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection getQueue()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection requireQueue()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection fillQueue()
	 * @method bool hasQueue()
	 * @method bool isQueueFilled()
	 * @method bool isQueueChanged()
	 * @method void addToQueue(\Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue $flowResponsibleQueue)
	 * @method void removeFromQueue(\Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue $flowResponsibleQueue)
	 * @method void removeAllQueue()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity resetQueue()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unsetQueue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity wakeUp($data)
	 */
	class EO_Flow {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * FlowEntityCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getCreatorIdList()
	 * @method \int[] fillCreatorId()
	 * @method \int[] getOwnerIdList()
	 * @method \int[] fillOwnerId()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \int[] getTemplateIdList()
	 * @method \int[] fillTemplateId()
	 * @method \int[] getEfficiencyList()
	 * @method \int[] fillEfficiency()
	 * @method \boolean[] getActiveList()
	 * @method \boolean[] fillActive()
	 * @method \int[] getPlannedCompletionTimeList()
	 * @method \int[] fillPlannedCompletionTime()
	 * @method \Bitrix\Main\Type\DateTime[] getActivityList()
	 * @method \Bitrix\Main\Type\DateTime[] fillActivity()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \string[] getDistributionTypeList()
	 * @method \string[] fillDistributionType()
	 * @method \boolean[] getDemoList()
	 * @method \boolean[] fillDemo()
	 * @method \Bitrix\Tasks\Internals\TaskCollection[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\TaskCollection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection[] getMembersList()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection getMembersCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection fillMembers()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection[] getOptionsList()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection getOptionsCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection fillOptions()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection[] getQueueList()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection getQueueCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection fillQueue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\Entity\FlowEntity $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\Entity\FlowEntity $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\Entity\FlowEntity $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection merge(?\Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Flow_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Flow_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection fetchCollection()
	 */
	class EO_Flow_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection fetchCollection()
	 */
	class EO_Flow_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection wakeUpCollection($rows)
	 */
	class EO_Flow_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowSearchIndexTable:tasks/lib/Flow/Internal/FlowSearchIndexTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowSearchIndex
	 * @see \Bitrix\Tasks\Flow\Internal\FlowSearchIndexTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex setFlowId(\int|\Bitrix\Main\DB\SqlExpression $flowId)
	 * @method bool hasFlowId()
	 * @method bool isFlowIdFilled()
	 * @method bool isFlowIdChanged()
	 * @method \int remindActualFlowId()
	 * @method \int requireFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex resetFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex unsetFlowId()
	 * @method \int fillFlowId()
	 * @method \string getSearchIndex()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex setSearchIndex(\string|\Bitrix\Main\DB\SqlExpression $searchIndex)
	 * @method bool hasSearchIndex()
	 * @method bool isSearchIndexFilled()
	 * @method bool isSearchIndexChanged()
	 * @method \string remindActualSearchIndex()
	 * @method \string requireSearchIndex()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex resetSearchIndex()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex unsetSearchIndex()
	 * @method \string fillSearchIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex wakeUp($data)
	 */
	class EO_FlowSearchIndex {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowSearchIndexTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowSearchIndexTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowSearchIndex_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFlowIdList()
	 * @method \int[] fillFlowId()
	 * @method \string[] getSearchIndexList()
	 * @method \string[] fillSearchIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex_Collection merge(?\Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FlowSearchIndex_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowSearchIndexTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowSearchIndexTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FlowSearchIndex_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex_Collection fetchCollection()
	 */
	class EO_FlowSearchIndex_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex_Collection fetchCollection()
	 */
	class EO_FlowSearchIndex_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex_Collection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex_Collection wakeUpCollection($rows)
	 */
	class EO_FlowSearchIndex_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowCopilotAdviceTable:tasks/lib/Flow/Internal/FlowCopilotAdviceTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * FlowCopilotAdvice
	 * @see \Bitrix\Tasks\Flow\Internal\FlowCopilotAdviceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice setFlowId(\int|\Bitrix\Main\DB\SqlExpression $flowId)
	 * @method bool hasFlowId()
	 * @method bool isFlowIdFilled()
	 * @method bool isFlowIdChanged()
	 * @method array getAdvice()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice setAdvice(array|\Bitrix\Main\DB\SqlExpression $advice)
	 * @method bool hasAdvice()
	 * @method bool isAdviceFilled()
	 * @method bool isAdviceChanged()
	 * @method array remindActualAdvice()
	 * @method array requireAdvice()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice resetAdvice()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice unsetAdvice()
	 * @method array fillAdvice()
	 * @method \Bitrix\Main\Type\DateTime getUpdatedDate()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice setUpdatedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $updatedDate)
	 * @method bool hasUpdatedDate()
	 * @method bool isUpdatedDateFilled()
	 * @method bool isUpdatedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualUpdatedDate()
	 * @method \Bitrix\Main\Type\DateTime requireUpdatedDate()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice resetUpdatedDate()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice unsetUpdatedDate()
	 * @method \Bitrix\Main\Type\DateTime fillUpdatedDate()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity getFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity remindActualFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity requireFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice setFlow(\Bitrix\Tasks\Flow\Internal\Entity\FlowEntity $object)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice resetFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice unsetFlow()
	 * @method bool hasFlow()
	 * @method bool isFlowFilled()
	 * @method bool isFlowChanged()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity fillFlow()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice wakeUp($data)
	 */
	class EO_FlowCopilotAdvice {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowCopilotAdviceTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowCopilotAdviceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowCopilotAdvice_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getFlowIdList()
	 * @method array[] getAdviceList()
	 * @method array[] fillAdvice()
	 * @method \Bitrix\Main\Type\DateTime[] getUpdatedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillUpdatedDate()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity[] getFlowList()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection getFlowCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection fillFlow()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection merge(?\Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FlowCopilotAdvice_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowCopilotAdviceTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowCopilotAdviceTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FlowCopilotAdvice_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection fetchCollection()
	 */
	class EO_FlowCopilotAdvice_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection fetchCollection()
	 */
	class EO_FlowCopilotAdvice_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection wakeUpCollection($rows)
	 */
	class EO_FlowCopilotAdvice_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowUserOptionTable:tasks/lib/Flow/Internal/FlowUserOptionTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowUserOption
	 * @see \Bitrix\Tasks\Flow\Internal\FlowUserOptionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption setFlowId(\int|\Bitrix\Main\DB\SqlExpression $flowId)
	 * @method bool hasFlowId()
	 * @method bool isFlowIdFilled()
	 * @method bool isFlowIdChanged()
	 * @method \int remindActualFlowId()
	 * @method \int requireFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption resetFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption unsetFlowId()
	 * @method \int fillFlowId()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption resetUserId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption unsetUserId()
	 * @method \int fillUserId()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption resetName()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption unsetName()
	 * @method \string fillName()
	 * @method \string getValue()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption resetValue()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption unsetValue()
	 * @method \string fillValue()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity getFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity remindActualFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity requireFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption setFlow(\Bitrix\Tasks\Flow\Internal\Entity\FlowEntity $object)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption resetFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption unsetFlow()
	 * @method bool hasFlow()
	 * @method bool isFlowFilled()
	 * @method bool isFlowChanged()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity fillFlow()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption resetUser()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption wakeUp($data)
	 */
	class EO_FlowUserOption {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowUserOptionTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowUserOptionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowUserOption_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFlowIdList()
	 * @method \int[] fillFlowId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity[] getFlowList()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection getFlowCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection fillFlow()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\EO_FlowUserOption $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\EO_FlowUserOption $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\EO_FlowUserOption $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection merge(?\Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FlowUserOption_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowUserOptionTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowUserOptionTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FlowUserOption_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection fetchCollection()
	 */
	class EO_FlowUserOption_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection fetchCollection()
	 */
	class EO_FlowUserOption_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection wakeUpCollection($rows)
	 */
	class EO_FlowUserOption_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowRobotTable:tasks/lib/Flow/Internal/FlowRobotTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * FlowRobot
	 * @see \Bitrix\Tasks\Flow\Internal\FlowRobotTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot setFlowId(\int|\Bitrix\Main\DB\SqlExpression $flowId)
	 * @method bool hasFlowId()
	 * @method bool isFlowIdFilled()
	 * @method bool isFlowIdChanged()
	 * @method \int remindActualFlowId()
	 * @method \int requireFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot resetFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot unsetFlowId()
	 * @method \int fillFlowId()
	 * @method \int getStageId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot setStageId(\int|\Bitrix\Main\DB\SqlExpression $stageId)
	 * @method bool hasStageId()
	 * @method bool isStageIdFilled()
	 * @method bool isStageIdChanged()
	 * @method \int remindActualStageId()
	 * @method \int requireStageId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot resetStageId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot unsetStageId()
	 * @method \int fillStageId()
	 * @method \int getBizProcTemplateId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot setBizProcTemplateId(\int|\Bitrix\Main\DB\SqlExpression $bizProcTemplateId)
	 * @method bool hasBizProcTemplateId()
	 * @method bool isBizProcTemplateIdFilled()
	 * @method bool isBizProcTemplateIdChanged()
	 * @method \int remindActualBizProcTemplateId()
	 * @method \int requireBizProcTemplateId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot resetBizProcTemplateId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot unsetBizProcTemplateId()
	 * @method \int fillBizProcTemplateId()
	 * @method \string getStageType()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot setStageType(\string|\Bitrix\Main\DB\SqlExpression $stageType)
	 * @method bool hasStageType()
	 * @method bool isStageTypeFilled()
	 * @method bool isStageTypeChanged()
	 * @method \string remindActualStageType()
	 * @method \string requireStageType()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot resetStageType()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot unsetStageType()
	 * @method \string fillStageType()
	 * @method \string getRobot()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot setRobot(\string|\Bitrix\Main\DB\SqlExpression $robot)
	 * @method bool hasRobot()
	 * @method bool isRobotFilled()
	 * @method bool isRobotChanged()
	 * @method \string remindActualRobot()
	 * @method \string requireRobot()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot resetRobot()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot unsetRobot()
	 * @method \string fillRobot()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot wakeUp($data)
	 */
	class EO_FlowRobot {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowRobotTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowRobotTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * FlowRobotCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFlowIdList()
	 * @method \int[] fillFlowId()
	 * @method \int[] getStageIdList()
	 * @method \int[] fillStageId()
	 * @method \int[] getBizProcTemplateIdList()
	 * @method \int[] fillBizProcTemplateId()
	 * @method \string[] getStageTypeList()
	 * @method \string[] fillStageType()
	 * @method \string[] getRobotList()
	 * @method \string[] fillRobot()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\Entity\FlowRobot $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\Entity\FlowRobot $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\Entity\FlowRobot $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection merge(?\Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FlowRobot_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowRobotTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowRobotTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FlowRobot_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection fetchCollection()
	 */
	class EO_FlowRobot_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection fetchCollection()
	 */
	class EO_FlowRobot_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection wakeUpCollection($rows)
	 */
	class EO_FlowRobot_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowResponsibleQueueTable:tasks/lib/Flow/Internal/FlowResponsibleQueueTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowResponsibleQueue
	 * @see \Bitrix\Tasks\Flow\Internal\FlowResponsibleQueueTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue setFlowId(\int|\Bitrix\Main\DB\SqlExpression $flowId)
	 * @method bool hasFlowId()
	 * @method bool isFlowIdFilled()
	 * @method bool isFlowIdChanged()
	 * @method \int remindActualFlowId()
	 * @method \int requireFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue resetFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue unsetFlowId()
	 * @method \int fillFlowId()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue resetUserId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getNextUserId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue setNextUserId(\int|\Bitrix\Main\DB\SqlExpression $nextUserId)
	 * @method bool hasNextUserId()
	 * @method bool isNextUserIdFilled()
	 * @method bool isNextUserIdChanged()
	 * @method \int remindActualNextUserId()
	 * @method \int requireNextUserId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue resetNextUserId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue unsetNextUserId()
	 * @method \int fillNextUserId()
	 * @method \int getSort()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue resetSort()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue unsetSort()
	 * @method \int fillSort()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity getFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity remindActualFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity requireFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue setFlow(\Bitrix\Tasks\Flow\Internal\Entity\FlowEntity $object)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue resetFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue unsetFlow()
	 * @method bool hasFlow()
	 * @method bool isFlowFilled()
	 * @method bool isFlowChanged()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity fillFlow()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue wakeUp($data)
	 */
	class EO_FlowResponsibleQueue {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowResponsibleQueueTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowResponsibleQueueTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowResponsibleQueue_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFlowIdList()
	 * @method \int[] fillFlowId()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getNextUserIdList()
	 * @method \int[] fillNextUserId()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity[] getFlowList()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection getFlowCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection fillFlow()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection merge(?\Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FlowResponsibleQueue_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowResponsibleQueueTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowResponsibleQueueTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FlowResponsibleQueue_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection fetchCollection()
	 */
	class EO_FlowResponsibleQueue_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection fetchCollection()
	 */
	class EO_FlowResponsibleQueue_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection wakeUpCollection($rows)
	 */
	class EO_FlowResponsibleQueue_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowNotificationTable:tasks/lib/Flow/Internal/FlowNotificationTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowNotification
	 * @see \Bitrix\Tasks\Flow\Internal\FlowNotificationTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification setFlowId(\int|\Bitrix\Main\DB\SqlExpression $flowId)
	 * @method bool hasFlowId()
	 * @method bool isFlowIdFilled()
	 * @method bool isFlowIdChanged()
	 * @method \int remindActualFlowId()
	 * @method \int requireFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification resetFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification unsetFlowId()
	 * @method \int fillFlowId()
	 * @method \int getIntegrationId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification setIntegrationId(\int|\Bitrix\Main\DB\SqlExpression $integrationId)
	 * @method bool hasIntegrationId()
	 * @method bool isIntegrationIdFilled()
	 * @method bool isIntegrationIdChanged()
	 * @method \int remindActualIntegrationId()
	 * @method \int requireIntegrationId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification resetIntegrationId()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification unsetIntegrationId()
	 * @method \int fillIntegrationId()
	 * @method \string getStatus()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification resetStatus()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification unsetStatus()
	 * @method \string fillStatus()
	 * @method \string getData()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification setData(\string|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method \string remindActualData()
	 * @method \string requireData()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification resetData()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification unsetData()
	 * @method \string fillData()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowNotification wakeUp($data)
	 */
	class EO_FlowNotification {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowNotificationTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowNotificationTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowNotification_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFlowIdList()
	 * @method \int[] fillFlowId()
	 * @method \int[] getIntegrationIdList()
	 * @method \int[] fillIntegrationId()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \string[] getDataList()
	 * @method \string[] fillData()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\EO_FlowNotification $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\EO_FlowNotification $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\EO_FlowNotification $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowNotification_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification_Collection merge(?\Bitrix\Tasks\Flow\Internal\EO_FlowNotification_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FlowNotification_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowNotificationTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowNotificationTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FlowNotification_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification_Collection fetchCollection()
	 */
	class EO_FlowNotification_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification_Collection fetchCollection()
	 */
	class EO_FlowNotification_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification_Collection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowNotification_Collection wakeUpCollection($rows)
	 */
	class EO_FlowNotification_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Flow\Internal\FlowOptionTable:tasks/lib/Flow/Internal/FlowOptionTable.php */
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * FlowOption
	 * @see \Bitrix\Tasks\Flow\Internal\FlowOptionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption setFlowId(\int|\Bitrix\Main\DB\SqlExpression $flowId)
	 * @method bool hasFlowId()
	 * @method bool isFlowIdFilled()
	 * @method bool isFlowIdChanged()
	 * @method \int remindActualFlowId()
	 * @method \int requireFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption resetFlowId()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption unsetFlowId()
	 * @method \int fillFlowId()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption resetName()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption unsetName()
	 * @method \string fillName()
	 * @method \string getValue()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption resetValue()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption unsetValue()
	 * @method \string fillValue()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity getFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity remindActualFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity requireFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption setFlow(\Bitrix\Tasks\Flow\Internal\Entity\FlowEntity $object)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption resetFlow()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption unsetFlow()
	 * @method bool hasFlow()
	 * @method bool isFlowFilled()
	 * @method bool isFlowChanged()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity fillFlow()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption set($fieldName, $value)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption reset($fieldName)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowOption wakeUp($data)
	 */
	class EO_FlowOption {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowOptionTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowOptionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * EO_FlowOption_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getFlowIdList()
	 * @method \int[] fillFlowId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity[] getFlowList()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection getFlowCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection fillFlow()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Flow\Internal\Entity\FlowOption $object)
	 * @method bool has(\Bitrix\Tasks\Flow\Internal\Entity\FlowOption $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption getByPrimary($primary)
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Flow\Internal\Entity\FlowOption $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection merge(?\Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_FlowOption_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Flow\Internal\FlowOptionTable */
		static public $dataClass = '\Bitrix\Tasks\Flow\Internal\FlowOptionTable';
	}
}
namespace Bitrix\Tasks\Flow\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_FlowOption_Result exec()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection fetchCollection()
	 */
	class EO_FlowOption_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption fetchObject()
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection fetchCollection()
	 */
	class EO_FlowOption_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection createCollection()
	 * @method \Bitrix\Tasks\Flow\Internal\Entity\FlowOption wakeUpObject($row)
	 * @method \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection wakeUpCollection($rows)
	 */
	class EO_FlowOption_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\TypeChecklistTable:tasks/lib/scrum/internal/typechecklisttable.php */
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_TypeChecklist
	 * @see \Bitrix\Tasks\Scrum\Internal\TypeChecklistTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getEntityId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist resetEntityId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist resetCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getToggledBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist setToggledBy(\int|\Bitrix\Main\DB\SqlExpression $toggledBy)
	 * @method bool hasToggledBy()
	 * @method bool isToggledByFilled()
	 * @method bool isToggledByChanged()
	 * @method \int remindActualToggledBy()
	 * @method \int requireToggledBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist resetToggledBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist unsetToggledBy()
	 * @method \int fillToggledBy()
	 * @method \Bitrix\Main\Type\DateTime getToggledDate()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist setToggledDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $toggledDate)
	 * @method bool hasToggledDate()
	 * @method bool isToggledDateFilled()
	 * @method bool isToggledDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualToggledDate()
	 * @method \Bitrix\Main\Type\DateTime requireToggledDate()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist resetToggledDate()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist unsetToggledDate()
	 * @method \Bitrix\Main\Type\DateTime fillToggledDate()
	 * @method \string getTitle()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist resetTitle()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist unsetTitle()
	 * @method \string fillTitle()
	 * @method \boolean getIsComplete()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist setIsComplete(\boolean|\Bitrix\Main\DB\SqlExpression $isComplete)
	 * @method bool hasIsComplete()
	 * @method bool isIsCompleteFilled()
	 * @method bool isIsCompleteChanged()
	 * @method \boolean remindActualIsComplete()
	 * @method \boolean requireIsComplete()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist resetIsComplete()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist unsetIsComplete()
	 * @method \boolean fillIsComplete()
	 * @method \boolean getIsImportant()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist setIsImportant(\boolean|\Bitrix\Main\DB\SqlExpression $isImportant)
	 * @method bool hasIsImportant()
	 * @method bool isIsImportantFilled()
	 * @method bool isIsImportantChanged()
	 * @method \boolean remindActualIsImportant()
	 * @method \boolean requireIsImportant()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist resetIsImportant()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist unsetIsImportant()
	 * @method \boolean fillIsImportant()
	 * @method \int getSortIndex()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist setSortIndex(\int|\Bitrix\Main\DB\SqlExpression $sortIndex)
	 * @method bool hasSortIndex()
	 * @method bool isSortIndexFilled()
	 * @method bool isSortIndexChanged()
	 * @method \int remindActualSortIndex()
	 * @method \int requireSortIndex()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist resetSortIndex()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist unsetSortIndex()
	 * @method \int fillSortIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist set($fieldName, $value)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist reset($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist wakeUp($data)
	 */
	class EO_TypeChecklist {
		/* @var \Bitrix\Tasks\Scrum\Internal\TypeChecklistTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\TypeChecklistTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_TypeChecklist_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getToggledByList()
	 * @method \int[] fillToggledBy()
	 * @method \Bitrix\Main\Type\DateTime[] getToggledDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillToggledDate()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \boolean[] getIsCompleteList()
	 * @method \boolean[] fillIsComplete()
	 * @method \boolean[] getIsImportantList()
	 * @method \boolean[] fillIsImportant()
	 * @method \int[] getSortIndexList()
	 * @method \int[] fillSortIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist $object)
	 * @method bool has(\Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist getByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist_Collection merge(?\Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TypeChecklist_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\TypeChecklistTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\TypeChecklistTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TypeChecklist_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist_Collection fetchCollection()
	 */
	class EO_TypeChecklist_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist_Collection fetchCollection()
	 */
	class EO_TypeChecklist_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist_Collection createCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist wakeUpObject($row)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklist_Collection wakeUpCollection($rows)
	 */
	class EO_TypeChecklist_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\EpicTable:tasks/lib/scrum/internal/epictable.php */
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_Epic
	 * @see \Bitrix\Tasks\Scrum\Internal\EpicTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic resetGroupId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic resetName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic unsetName()
	 * @method \string fillName()
	 * @method \string getDescription()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic resetDescription()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic unsetDescription()
	 * @method \string fillDescription()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic resetCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getModifiedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic setModifiedBy(\int|\Bitrix\Main\DB\SqlExpression $modifiedBy)
	 * @method bool hasModifiedBy()
	 * @method bool isModifiedByFilled()
	 * @method bool isModifiedByChanged()
	 * @method \int remindActualModifiedBy()
	 * @method \int requireModifiedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic resetModifiedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic unsetModifiedBy()
	 * @method \int fillModifiedBy()
	 * @method \string getColor()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic setColor(\string|\Bitrix\Main\DB\SqlExpression $color)
	 * @method bool hasColor()
	 * @method bool isColorFilled()
	 * @method bool isColorChanged()
	 * @method \string remindActualColor()
	 * @method \string requireColor()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic resetColor()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic unsetColor()
	 * @method \string fillColor()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic set($fieldName, $value)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic reset($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Epic wakeUp($data)
	 */
	class EO_Epic {
		/* @var \Bitrix\Tasks\Scrum\Internal\EpicTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\EpicTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_Epic_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getModifiedByList()
	 * @method \int[] fillModifiedBy()
	 * @method \string[] getColorList()
	 * @method \string[] fillColor()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Scrum\Internal\EO_Epic $object)
	 * @method bool has(\Bitrix\Tasks\Scrum\Internal\EO_Epic $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic getByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Scrum\Internal\EO_Epic $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Epic_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic_Collection merge(?\Bitrix\Tasks\Scrum\Internal\EO_Epic_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Epic_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\EpicTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\EpicTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Epic_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic_Collection fetchCollection()
	 */
	class EO_Epic_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic_Collection fetchCollection()
	 */
	class EO_Epic_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic_Collection createCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic wakeUpObject($row)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic_Collection wakeUpCollection($rows)
	 */
	class EO_Epic_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\TypeTable:tasks/lib/scrum/internal/typetable.php */
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_Type
	 * @see \Bitrix\Tasks\Scrum\Internal\TypeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getEntityId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type resetEntityId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type resetName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type unsetName()
	 * @method \string fillName()
	 * @method \int getSort()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type resetSort()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type unsetSort()
	 * @method \int fillSort()
	 * @method \string getDodRequired()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type setDodRequired(\string|\Bitrix\Main\DB\SqlExpression $dodRequired)
	 * @method bool hasDodRequired()
	 * @method bool isDodRequiredFilled()
	 * @method bool isDodRequiredChanged()
	 * @method \string remindActualDodRequired()
	 * @method \string requireDodRequired()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type resetDodRequired()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type unsetDodRequired()
	 * @method \string fillDodRequired()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection getParticipants()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection requireParticipants()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection fillParticipants()
	 * @method bool hasParticipants()
	 * @method bool isParticipantsFilled()
	 * @method bool isParticipantsChanged()
	 * @method void addToParticipants(\Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants $typeParticipants)
	 * @method void removeFromParticipants(\Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants $typeParticipants)
	 * @method void removeAllParticipants()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type resetParticipants()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type unsetParticipants()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type set($fieldName, $value)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type reset($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Type wakeUp($data)
	 */
	class EO_Type {
		/* @var \Bitrix\Tasks\Scrum\Internal\TypeTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\TypeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_Type_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \string[] getDodRequiredList()
	 * @method \string[] fillDodRequired()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection[] getParticipantsList()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection getParticipantsCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection fillParticipants()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Scrum\Internal\EO_Type $object)
	 * @method bool has(\Bitrix\Tasks\Scrum\Internal\EO_Type $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type getByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Scrum\Internal\EO_Type $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection merge(?\Bitrix\Tasks\Scrum\Internal\EO_Type_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Type_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\TypeTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\TypeTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Type_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection fetchCollection()
	 */
	class EO_Type_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection fetchCollection()
	 */
	class EO_Type_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection createCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type wakeUpObject($row)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection wakeUpCollection($rows)
	 */
	class EO_Type_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\TypeChecklistTreeTable:tasks/lib/scrum/internal/typechecklisttreetable.php */
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_TypeChecklistTree
	 * @see \Bitrix\Tasks\Scrum\Internal\TypeChecklistTreeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getParentId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int getChildId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree setChildId(\int|\Bitrix\Main\DB\SqlExpression $childId)
	 * @method bool hasChildId()
	 * @method bool isChildIdFilled()
	 * @method bool isChildIdChanged()
	 * @method \int getLevel()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree setLevel(\int|\Bitrix\Main\DB\SqlExpression $level)
	 * @method bool hasLevel()
	 * @method bool isLevelFilled()
	 * @method bool isLevelChanged()
	 * @method \int remindActualLevel()
	 * @method \int requireLevel()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree resetLevel()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree unsetLevel()
	 * @method \int fillLevel()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree set($fieldName, $value)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree reset($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree wakeUp($data)
	 */
	class EO_TypeChecklistTree {
		/* @var \Bitrix\Tasks\Scrum\Internal\TypeChecklistTreeTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\TypeChecklistTreeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_TypeChecklistTree_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getParentIdList()
	 * @method \int[] getChildIdList()
	 * @method \int[] getLevelList()
	 * @method \int[] fillLevel()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree $object)
	 * @method bool has(\Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree getByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree_Collection merge(?\Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TypeChecklistTree_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\TypeChecklistTreeTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\TypeChecklistTreeTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TypeChecklistTree_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree_Collection fetchCollection()
	 */
	class EO_TypeChecklistTree_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree_Collection fetchCollection()
	 */
	class EO_TypeChecklistTree_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree_Collection createCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree wakeUpObject($row)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeChecklistTree_Collection wakeUpCollection($rows)
	 */
	class EO_TypeChecklistTree_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\ItemTable:tasks/lib/scrum/internal/itemtable.php */
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_Item
	 * @see \Bitrix\Tasks\Scrum\Internal\ItemTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getEntityId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetEntityId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \int getTypeId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setTypeId(\int|\Bitrix\Main\DB\SqlExpression $typeId)
	 * @method bool hasTypeId()
	 * @method bool isTypeIdFilled()
	 * @method bool isTypeIdChanged()
	 * @method \int remindActualTypeId()
	 * @method \int requireTypeId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetTypeId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetTypeId()
	 * @method \int fillTypeId()
	 * @method \int getEpicId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setEpicId(\int|\Bitrix\Main\DB\SqlExpression $epicId)
	 * @method bool hasEpicId()
	 * @method bool isEpicIdFilled()
	 * @method bool isEpicIdChanged()
	 * @method \int remindActualEpicId()
	 * @method \int requireEpicId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetEpicId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetEpicId()
	 * @method \int fillEpicId()
	 * @method \string getActive()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setActive(\string|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \string remindActualActive()
	 * @method \string requireActive()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetActive()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetActive()
	 * @method \string fillActive()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetName()
	 * @method \string fillName()
	 * @method \string getDescription()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetDescription()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetDescription()
	 * @method \string fillDescription()
	 * @method \int getSort()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetSort()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetSort()
	 * @method \int fillSort()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getModifiedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setModifiedBy(\int|\Bitrix\Main\DB\SqlExpression $modifiedBy)
	 * @method bool hasModifiedBy()
	 * @method bool isModifiedByFilled()
	 * @method bool isModifiedByChanged()
	 * @method \int remindActualModifiedBy()
	 * @method \int requireModifiedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetModifiedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetModifiedBy()
	 * @method \int fillModifiedBy()
	 * @method \string getStoryPoints()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setStoryPoints(\string|\Bitrix\Main\DB\SqlExpression $storyPoints)
	 * @method bool hasStoryPoints()
	 * @method bool isStoryPointsFilled()
	 * @method bool isStoryPointsChanged()
	 * @method \string remindActualStoryPoints()
	 * @method \string requireStoryPoints()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetStoryPoints()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetStoryPoints()
	 * @method \string fillStoryPoints()
	 * @method \int getSourceId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setSourceId(\int|\Bitrix\Main\DB\SqlExpression $sourceId)
	 * @method bool hasSourceId()
	 * @method bool isSourceIdFilled()
	 * @method bool isSourceIdChanged()
	 * @method \int remindActualSourceId()
	 * @method \int requireSourceId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetSourceId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetSourceId()
	 * @method \int fillSourceId()
	 * @method \Bitrix\Tasks\Scrum\Form\ItemInfo getInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setInfo(\Bitrix\Tasks\Scrum\Form\ItemInfo|\Bitrix\Main\DB\SqlExpression $info)
	 * @method bool hasInfo()
	 * @method bool isInfoFilled()
	 * @method bool isInfoChanged()
	 * @method \Bitrix\Tasks\Scrum\Form\ItemInfo remindActualInfo()
	 * @method \Bitrix\Tasks\Scrum\Form\ItemInfo requireInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetInfo()
	 * @method \Bitrix\Tasks\Scrum\Form\ItemInfo fillInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity getEntity()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity remindActualEntity()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity requireEntity()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setEntity(\Bitrix\Tasks\Scrum\Internal\EO_Entity $object)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetEntity()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetEntity()
	 * @method bool hasEntity()
	 * @method bool isEntityFilled()
	 * @method bool isEntityChanged()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity fillEntity()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type getType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type remindActualType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type requireType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setType(\Bitrix\Tasks\Scrum\Internal\EO_Type $object)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetType()
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type fillType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic getEpic()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic remindActualEpic()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic requireEpic()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setEpic(\Bitrix\Tasks\Scrum\Internal\EO_Epic $object)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetEpic()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetEpic()
	 * @method bool hasEpic()
	 * @method bool isEpicFilled()
	 * @method bool isEpicChanged()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic fillEpic()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item set($fieldName, $value)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item reset($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item wakeUp($data)
	 */
	class EO_Item {
		/* @var \Bitrix\Tasks\Scrum\Internal\ItemTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\ItemTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_Item_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \int[] getTypeIdList()
	 * @method \int[] fillTypeId()
	 * @method \int[] getEpicIdList()
	 * @method \int[] fillEpicId()
	 * @method \string[] getActiveList()
	 * @method \string[] fillActive()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getModifiedByList()
	 * @method \int[] fillModifiedBy()
	 * @method \string[] getStoryPointsList()
	 * @method \string[] fillStoryPoints()
	 * @method \int[] getSourceIdList()
	 * @method \int[] fillSourceId()
	 * @method \Bitrix\Tasks\Scrum\Form\ItemInfo[] getInfoList()
	 * @method \Bitrix\Tasks\Scrum\Form\ItemInfo[] fillInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity[] getEntityList()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection getEntityCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection fillEntity()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type[] getTypeList()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection getTypeCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection fillType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic[] getEpicList()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection getEpicCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Epic_Collection fillEpic()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Scrum\Internal\EO_Item $object)
	 * @method bool has(\Bitrix\Tasks\Scrum\Internal\EO_Item $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item getByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Scrum\Internal\EO_Item $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection merge(?\Bitrix\Tasks\Scrum\Internal\EO_Item_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Item_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\ItemTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\ItemTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Item_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection fetchCollection()
	 */
	class EO_Item_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection fetchCollection()
	 */
	class EO_Item_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection createCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item wakeUpObject($row)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection wakeUpCollection($rows)
	 */
	class EO_Item_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\ItemChecklistTreeTable:tasks/lib/scrum/internal/itemchecklisttreetable.php */
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_ItemChecklistTree
	 * @see \Bitrix\Tasks\Scrum\Internal\ItemChecklistTreeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getParentId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int getChildId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree setChildId(\int|\Bitrix\Main\DB\SqlExpression $childId)
	 * @method bool hasChildId()
	 * @method bool isChildIdFilled()
	 * @method bool isChildIdChanged()
	 * @method \int getLevel()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree setLevel(\int|\Bitrix\Main\DB\SqlExpression $level)
	 * @method bool hasLevel()
	 * @method bool isLevelFilled()
	 * @method bool isLevelChanged()
	 * @method \int remindActualLevel()
	 * @method \int requireLevel()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree resetLevel()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree unsetLevel()
	 * @method \int fillLevel()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree set($fieldName, $value)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree reset($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree wakeUp($data)
	 */
	class EO_ItemChecklistTree {
		/* @var \Bitrix\Tasks\Scrum\Internal\ItemChecklistTreeTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\ItemChecklistTreeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_ItemChecklistTree_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getParentIdList()
	 * @method \int[] getChildIdList()
	 * @method \int[] getLevelList()
	 * @method \int[] fillLevel()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree $object)
	 * @method bool has(\Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree getByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree_Collection merge(?\Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ItemChecklistTree_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\ItemChecklistTreeTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\ItemChecklistTreeTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ItemChecklistTree_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree_Collection fetchCollection()
	 */
	class EO_ItemChecklistTree_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree_Collection fetchCollection()
	 */
	class EO_ItemChecklistTree_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree_Collection createCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree wakeUpObject($row)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree_Collection wakeUpCollection($rows)
	 */
	class EO_ItemChecklistTree_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\EntityTable:tasks/lib/scrum/internal/entitytable.php */
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_Entity
	 * @see \Bitrix\Tasks\Scrum\Internal\EntityTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetGroupId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetEntityType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetName()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetName()
	 * @method \string fillName()
	 * @method \int getSort()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetSort()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetSort()
	 * @method \int fillSort()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getModifiedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setModifiedBy(\int|\Bitrix\Main\DB\SqlExpression $modifiedBy)
	 * @method bool hasModifiedBy()
	 * @method bool isModifiedByFilled()
	 * @method bool isModifiedByChanged()
	 * @method \int remindActualModifiedBy()
	 * @method \int requireModifiedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetModifiedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetModifiedBy()
	 * @method \int fillModifiedBy()
	 * @method \Bitrix\Main\Type\DateTime getDateStart()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setDateStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStart)
	 * @method bool hasDateStart()
	 * @method bool isDateStartFilled()
	 * @method bool isDateStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStart()
	 * @method \Bitrix\Main\Type\DateTime requireDateStart()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetDateStart()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetDateStart()
	 * @method \Bitrix\Main\Type\DateTime fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime getDateEnd()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setDateEnd(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateEnd)
	 * @method bool hasDateEnd()
	 * @method bool isDateEndFilled()
	 * @method bool isDateEndChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateEnd()
	 * @method \Bitrix\Main\Type\DateTime requireDateEnd()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetDateEnd()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetDateEnd()
	 * @method \Bitrix\Main\Type\DateTime fillDateEnd()
	 * @method \string getDateStartTz()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setDateStartTz(\string|\Bitrix\Main\DB\SqlExpression $dateStartTz)
	 * @method bool hasDateStartTz()
	 * @method bool isDateStartTzFilled()
	 * @method bool isDateStartTzChanged()
	 * @method \string remindActualDateStartTz()
	 * @method \string requireDateStartTz()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetDateStartTz()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetDateStartTz()
	 * @method \string fillDateStartTz()
	 * @method \string getDateEndTz()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setDateEndTz(\string|\Bitrix\Main\DB\SqlExpression $dateEndTz)
	 * @method bool hasDateEndTz()
	 * @method bool isDateEndTzFilled()
	 * @method bool isDateEndTzChanged()
	 * @method \string remindActualDateEndTz()
	 * @method \string requireDateEndTz()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetDateEndTz()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetDateEndTz()
	 * @method \string fillDateEndTz()
	 * @method \string getStatus()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetStatus()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetStatus()
	 * @method \string fillStatus()
	 * @method \Bitrix\Tasks\Scrum\Form\EntityInfo getInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setInfo(\Bitrix\Tasks\Scrum\Form\EntityInfo|\Bitrix\Main\DB\SqlExpression $info)
	 * @method bool hasInfo()
	 * @method bool isInfoFilled()
	 * @method bool isInfoChanged()
	 * @method \Bitrix\Tasks\Scrum\Form\EntityInfo remindActualInfo()
	 * @method \Bitrix\Tasks\Scrum\Form\EntityInfo requireInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetInfo()
	 * @method \Bitrix\Tasks\Scrum\Form\EntityInfo fillInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection getItems()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection requireItems()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection fillItems()
	 * @method bool hasItems()
	 * @method bool isItemsFilled()
	 * @method bool isItemsChanged()
	 * @method void addToItems(\Bitrix\Tasks\Scrum\Internal\EO_Item $item)
	 * @method void removeFromItems(\Bitrix\Tasks\Scrum\Internal\EO_Item $item)
	 * @method void removeAllItems()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetItems()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetItems()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity set($fieldName, $value)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity reset($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity wakeUp($data)
	 */
	class EO_Entity {
		/* @var \Bitrix\Tasks\Scrum\Internal\EntityTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\EntityTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_Entity_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getModifiedByList()
	 * @method \int[] fillModifiedBy()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime[] getDateEndList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateEnd()
	 * @method \string[] getDateStartTzList()
	 * @method \string[] fillDateStartTz()
	 * @method \string[] getDateEndTzList()
	 * @method \string[] fillDateEndTz()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \Bitrix\Tasks\Scrum\Form\EntityInfo[] getInfoList()
	 * @method \Bitrix\Tasks\Scrum\Form\EntityInfo[] fillInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection[] getItemsList()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection getItemsCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection fillItems()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Scrum\Internal\EO_Entity $object)
	 * @method bool has(\Bitrix\Tasks\Scrum\Internal\EO_Entity $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity getByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Scrum\Internal\EO_Entity $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection merge(?\Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Entity_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\EntityTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\EntityTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Entity_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection fetchCollection()
	 */
	class EO_Entity_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection fetchCollection()
	 */
	class EO_Entity_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection createCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity wakeUpObject($row)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection wakeUpCollection($rows)
	 */
	class EO_Entity_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\ItemChecklistTable:tasks/lib/scrum/internal/itemchecklisttable.php */
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_ItemChecklist
	 * @see \Bitrix\Tasks\Scrum\Internal\ItemChecklistTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getItemId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist setItemId(\int|\Bitrix\Main\DB\SqlExpression $itemId)
	 * @method bool hasItemId()
	 * @method bool isItemIdFilled()
	 * @method bool isItemIdChanged()
	 * @method \int remindActualItemId()
	 * @method \int requireItemId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist resetItemId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist unsetItemId()
	 * @method \int fillItemId()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist resetCreatedBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \int getToggledBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist setToggledBy(\int|\Bitrix\Main\DB\SqlExpression $toggledBy)
	 * @method bool hasToggledBy()
	 * @method bool isToggledByFilled()
	 * @method bool isToggledByChanged()
	 * @method \int remindActualToggledBy()
	 * @method \int requireToggledBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist resetToggledBy()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist unsetToggledBy()
	 * @method \int fillToggledBy()
	 * @method \Bitrix\Main\Type\DateTime getToggledDate()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist setToggledDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $toggledDate)
	 * @method bool hasToggledDate()
	 * @method bool isToggledDateFilled()
	 * @method bool isToggledDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualToggledDate()
	 * @method \Bitrix\Main\Type\DateTime requireToggledDate()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist resetToggledDate()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist unsetToggledDate()
	 * @method \Bitrix\Main\Type\DateTime fillToggledDate()
	 * @method \string getTitle()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist resetTitle()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist unsetTitle()
	 * @method \string fillTitle()
	 * @method \boolean getIsComplete()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist setIsComplete(\boolean|\Bitrix\Main\DB\SqlExpression $isComplete)
	 * @method bool hasIsComplete()
	 * @method bool isIsCompleteFilled()
	 * @method bool isIsCompleteChanged()
	 * @method \boolean remindActualIsComplete()
	 * @method \boolean requireIsComplete()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist resetIsComplete()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist unsetIsComplete()
	 * @method \boolean fillIsComplete()
	 * @method \boolean getIsImportant()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist setIsImportant(\boolean|\Bitrix\Main\DB\SqlExpression $isImportant)
	 * @method bool hasIsImportant()
	 * @method bool isIsImportantFilled()
	 * @method bool isIsImportantChanged()
	 * @method \boolean remindActualIsImportant()
	 * @method \boolean requireIsImportant()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist resetIsImportant()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist unsetIsImportant()
	 * @method \boolean fillIsImportant()
	 * @method \int getSortIndex()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist setSortIndex(\int|\Bitrix\Main\DB\SqlExpression $sortIndex)
	 * @method bool hasSortIndex()
	 * @method bool isSortIndexFilled()
	 * @method bool isSortIndexChanged()
	 * @method \int remindActualSortIndex()
	 * @method \int requireSortIndex()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist resetSortIndex()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist unsetSortIndex()
	 * @method \int fillSortIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist set($fieldName, $value)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist reset($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist wakeUp($data)
	 */
	class EO_ItemChecklist {
		/* @var \Bitrix\Tasks\Scrum\Internal\ItemChecklistTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\ItemChecklistTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_ItemChecklist_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getItemIdList()
	 * @method \int[] fillItemId()
	 * @method \int[] getCreatedByList()
	 * @method \int[] fillCreatedBy()
	 * @method \int[] getToggledByList()
	 * @method \int[] fillToggledBy()
	 * @method \Bitrix\Main\Type\DateTime[] getToggledDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillToggledDate()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \boolean[] getIsCompleteList()
	 * @method \boolean[] fillIsComplete()
	 * @method \boolean[] getIsImportantList()
	 * @method \boolean[] fillIsImportant()
	 * @method \int[] getSortIndexList()
	 * @method \int[] fillSortIndex()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist $object)
	 * @method bool has(\Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist getByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist_Collection merge(?\Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ItemChecklist_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\ItemChecklistTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\ItemChecklistTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ItemChecklist_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist_Collection fetchCollection()
	 */
	class EO_ItemChecklist_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist_Collection fetchCollection()
	 */
	class EO_ItemChecklist_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist_Collection createCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist wakeUpObject($row)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklist_Collection wakeUpCollection($rows)
	 */
	class EO_ItemChecklist_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\TypeParticipantsTable:tasks/lib/scrum/internal/typeparticipantstable.php */
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_TypeParticipants
	 * @see \Bitrix\Tasks\Scrum\Internal\TypeParticipantsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTypeId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants setTypeId(\int|\Bitrix\Main\DB\SqlExpression $typeId)
	 * @method bool hasTypeId()
	 * @method bool isTypeIdFilled()
	 * @method bool isTypeIdChanged()
	 * @method \int remindActualTypeId()
	 * @method \int requireTypeId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants resetTypeId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants unsetTypeId()
	 * @method \int fillTypeId()
	 * @method \string getCode()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string remindActualCode()
	 * @method \string requireCode()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants resetCode()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants unsetCode()
	 * @method \string fillCode()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type getType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type remindActualType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type requireType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants setType(\Bitrix\Tasks\Scrum\Internal\EO_Type $object)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants resetType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants unsetType()
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type fillType()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants set($fieldName, $value)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants reset($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants wakeUp($data)
	 */
	class EO_TypeParticipants {
		/* @var \Bitrix\Tasks\Scrum\Internal\TypeParticipantsTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\TypeParticipantsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_TypeParticipants_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTypeIdList()
	 * @method \int[] fillTypeId()
	 * @method \string[] getCodeList()
	 * @method \string[] fillCode()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type[] getTypeList()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection getTypeCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Type_Collection fillType()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants $object)
	 * @method bool has(\Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants getByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection merge(?\Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TypeParticipants_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\TypeParticipantsTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\TypeParticipantsTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TypeParticipants_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection fetchCollection()
	 */
	class EO_TypeParticipants_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection fetchCollection()
	 */
	class EO_TypeParticipants_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection createCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants wakeUpObject($row)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection wakeUpCollection($rows)
	 */
	class EO_TypeParticipants_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\ChatTable:tasks/lib/scrum/internal/chattable.php */
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_Chat
	 * @see \Bitrix\Tasks\Scrum\Internal\ChatTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getChatId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat setChatId(\int|\Bitrix\Main\DB\SqlExpression $chatId)
	 * @method bool hasChatId()
	 * @method bool isChatIdFilled()
	 * @method bool isChatIdChanged()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat set($fieldName, $value)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat reset($fieldName)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Chat wakeUp($data)
	 */
	class EO_Chat {
		/* @var \Bitrix\Tasks\Scrum\Internal\ChatTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\ChatTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * EO_Chat_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getChatIdList()
	 * @method \int[] getGroupIdList()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Scrum\Internal\EO_Chat $object)
	 * @method bool has(\Bitrix\Tasks\Scrum\Internal\EO_Chat $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat getByPrimary($primary)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Scrum\Internal\EO_Chat $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Chat_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat_Collection merge(?\Bitrix\Tasks\Scrum\Internal\EO_Chat_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Chat_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\ChatTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\ChatTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Chat_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat_Collection fetchCollection()
	 */
	class EO_Chat_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat_Collection fetchCollection()
	 */
	class EO_Chat_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat_Collection createCollection()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat wakeUpObject($row)
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Chat_Collection wakeUpCollection($rows)
	 */
	class EO_Chat_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Kanban\TaskStageTable:tasks/lib/kanban/taskstage.php */
namespace Bitrix\Tasks\Kanban {
	/**
	 * EO_TaskStage
	 * @see \Bitrix\Tasks\Kanban\TaskStageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage resetTaskId()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getStageId()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage setStageId(\int|\Bitrix\Main\DB\SqlExpression $stageId)
	 * @method bool hasStageId()
	 * @method bool isStageIdFilled()
	 * @method bool isStageIdChanged()
	 * @method \int remindActualStageId()
	 * @method \int requireStageId()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage resetStageId()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage unsetStageId()
	 * @method \int fillStageId()
	 * @method \Bitrix\Tasks\Kanban\Stage getStage()
	 * @method \Bitrix\Tasks\Kanban\Stage remindActualStage()
	 * @method \Bitrix\Tasks\Kanban\Stage requireStage()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage setStage(\Bitrix\Tasks\Kanban\Stage $object)
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage resetStage()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage unsetStage()
	 * @method bool hasStage()
	 * @method bool isStageFilled()
	 * @method bool isStageChanged()
	 * @method \Bitrix\Tasks\Kanban\Stage fillStage()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage set($fieldName, $value)
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage reset($fieldName)
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Kanban\EO_TaskStage wakeUp($data)
	 */
	class EO_TaskStage {
		/* @var \Bitrix\Tasks\Kanban\TaskStageTable */
		static public $dataClass = '\Bitrix\Tasks\Kanban\TaskStageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Kanban {
	/**
	 * EO_TaskStage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getStageIdList()
	 * @method \int[] fillStageId()
	 * @method \Bitrix\Tasks\Kanban\Stage[] getStageList()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage_Collection getStageCollection()
	 * @method \Bitrix\Tasks\Kanban\StagesCollection fillStage()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Kanban\EO_TaskStage $object)
	 * @method bool has(\Bitrix\Tasks\Kanban\EO_TaskStage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage getByPrimary($primary)
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Kanban\EO_TaskStage $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Kanban\EO_TaskStage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage_Collection merge(?\Bitrix\Tasks\Kanban\EO_TaskStage_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TaskStage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Kanban\TaskStageTable */
		static public $dataClass = '\Bitrix\Tasks\Kanban\TaskStageTable';
	}
}
namespace Bitrix\Tasks\Kanban {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TaskStage_Result exec()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage fetchObject()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage_Collection fetchCollection()
	 */
	class EO_TaskStage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage fetchObject()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage_Collection fetchCollection()
	 */
	class EO_TaskStage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage_Collection createCollection()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage wakeUpObject($row)
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage_Collection wakeUpCollection($rows)
	 */
	class EO_TaskStage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Kanban\StagesTable:tasks/lib/kanban/stages.php */
namespace Bitrix\Tasks\Kanban {
	/**
	 * Stage
	 * @see \Bitrix\Tasks\Kanban\StagesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Kanban\Stage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\Tasks\Kanban\Stage setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Tasks\Kanban\Stage resetTitle()
	 * @method \Bitrix\Tasks\Kanban\Stage unsetTitle()
	 * @method \string fillTitle()
	 * @method \int getSort()
	 * @method \Bitrix\Tasks\Kanban\Stage setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Tasks\Kanban\Stage resetSort()
	 * @method \Bitrix\Tasks\Kanban\Stage unsetSort()
	 * @method \int fillSort()
	 * @method \string getColor()
	 * @method \Bitrix\Tasks\Kanban\Stage setColor(\string|\Bitrix\Main\DB\SqlExpression $color)
	 * @method bool hasColor()
	 * @method bool isColorFilled()
	 * @method bool isColorChanged()
	 * @method \string remindActualColor()
	 * @method \string requireColor()
	 * @method \Bitrix\Tasks\Kanban\Stage resetColor()
	 * @method \Bitrix\Tasks\Kanban\Stage unsetColor()
	 * @method \string fillColor()
	 * @method \string getSystemType()
	 * @method \Bitrix\Tasks\Kanban\Stage setSystemType(\string|\Bitrix\Main\DB\SqlExpression $systemType)
	 * @method bool hasSystemType()
	 * @method bool isSystemTypeFilled()
	 * @method bool isSystemTypeChanged()
	 * @method \string remindActualSystemType()
	 * @method \string requireSystemType()
	 * @method \Bitrix\Tasks\Kanban\Stage resetSystemType()
	 * @method \Bitrix\Tasks\Kanban\Stage unsetSystemType()
	 * @method \string fillSystemType()
	 * @method \int getEntityId()
	 * @method \Bitrix\Tasks\Kanban\Stage setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Tasks\Kanban\Stage resetEntityId()
	 * @method \Bitrix\Tasks\Kanban\Stage unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Tasks\Kanban\Stage setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Tasks\Kanban\Stage resetEntityType()
	 * @method \Bitrix\Tasks\Kanban\Stage unsetEntityType()
	 * @method \string fillEntityType()
	 * @method array getAdditionalFilter()
	 * @method \Bitrix\Tasks\Kanban\Stage setAdditionalFilter(array|\Bitrix\Main\DB\SqlExpression $additionalFilter)
	 * @method bool hasAdditionalFilter()
	 * @method bool isAdditionalFilterFilled()
	 * @method bool isAdditionalFilterChanged()
	 * @method array remindActualAdditionalFilter()
	 * @method array requireAdditionalFilter()
	 * @method \Bitrix\Tasks\Kanban\Stage resetAdditionalFilter()
	 * @method \Bitrix\Tasks\Kanban\Stage unsetAdditionalFilter()
	 * @method array fillAdditionalFilter()
	 * @method array getToUpdate()
	 * @method \Bitrix\Tasks\Kanban\Stage setToUpdate(array|\Bitrix\Main\DB\SqlExpression $toUpdate)
	 * @method bool hasToUpdate()
	 * @method bool isToUpdateFilled()
	 * @method bool isToUpdateChanged()
	 * @method array remindActualToUpdate()
	 * @method array requireToUpdate()
	 * @method \Bitrix\Tasks\Kanban\Stage resetToUpdate()
	 * @method \Bitrix\Tasks\Kanban\Stage unsetToUpdate()
	 * @method array fillToUpdate()
	 * @method \string getToUpdateAccess()
	 * @method \Bitrix\Tasks\Kanban\Stage setToUpdateAccess(\string|\Bitrix\Main\DB\SqlExpression $toUpdateAccess)
	 * @method bool hasToUpdateAccess()
	 * @method bool isToUpdateAccessFilled()
	 * @method bool isToUpdateAccessChanged()
	 * @method \string remindActualToUpdateAccess()
	 * @method \string requireToUpdateAccess()
	 * @method \Bitrix\Tasks\Kanban\Stage resetToUpdateAccess()
	 * @method \Bitrix\Tasks\Kanban\Stage unsetToUpdateAccess()
	 * @method \string fillToUpdateAccess()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Kanban\Stage set($fieldName, $value)
	 * @method \Bitrix\Tasks\Kanban\Stage reset($fieldName)
	 * @method \Bitrix\Tasks\Kanban\Stage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Kanban\Stage wakeUp($data)
	 */
	class EO_Stages {
		/* @var \Bitrix\Tasks\Kanban\StagesTable */
		static public $dataClass = '\Bitrix\Tasks\Kanban\StagesTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Kanban {
	/**
	 * StagesCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \string[] getColorList()
	 * @method \string[] fillColor()
	 * @method \string[] getSystemTypeList()
	 * @method \string[] fillSystemType()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method array[] getAdditionalFilterList()
	 * @method array[] fillAdditionalFilter()
	 * @method array[] getToUpdateList()
	 * @method array[] fillToUpdate()
	 * @method \string[] getToUpdateAccessList()
	 * @method \string[] fillToUpdateAccess()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Kanban\Stage $object)
	 * @method bool has(\Bitrix\Tasks\Kanban\Stage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Kanban\Stage getByPrimary($primary)
	 * @method \Bitrix\Tasks\Kanban\Stage[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Kanban\Stage $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Kanban\StagesCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Kanban\Stage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Kanban\StagesCollection merge(?\Bitrix\Tasks\Kanban\StagesCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Stages_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Kanban\StagesTable */
		static public $dataClass = '\Bitrix\Tasks\Kanban\StagesTable';
	}
}
namespace Bitrix\Tasks\Kanban {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Stages_Result exec()
	 * @method \Bitrix\Tasks\Kanban\Stage fetchObject()
	 * @method \Bitrix\Tasks\Kanban\StagesCollection fetchCollection()
	 */
	class EO_Stages_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Kanban\Stage fetchObject()
	 * @method \Bitrix\Tasks\Kanban\StagesCollection fetchCollection()
	 */
	class EO_Stages_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Kanban\Stage createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Kanban\StagesCollection createCollection()
	 * @method \Bitrix\Tasks\Kanban\Stage wakeUpObject($row)
	 * @method \Bitrix\Tasks\Kanban\StagesCollection wakeUpCollection($rows)
	 */
	class EO_Stages_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Access\Permission\TasksPermissionTable:tasks/lib/access/permission/taskspermissiontable.php */
namespace Bitrix\Tasks\Access\Permission {
	/**
	 * TasksPermission
	 * @see \Bitrix\Tasks\Access\Permission\TasksPermissionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission resetRoleId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getPermissionId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission setPermissionId(\string|\Bitrix\Main\DB\SqlExpression $permissionId)
	 * @method bool hasPermissionId()
	 * @method bool isPermissionIdFilled()
	 * @method bool isPermissionIdChanged()
	 * @method \string remindActualPermissionId()
	 * @method \string requirePermissionId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission resetPermissionId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission unsetPermissionId()
	 * @method \string fillPermissionId()
	 * @method \int getValue()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission setValue(\int|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \int remindActualValue()
	 * @method \int requireValue()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission resetValue()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission unsetValue()
	 * @method \int fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission set($fieldName, $value)
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission reset($fieldName)
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Access\Permission\TasksPermission wakeUp($data)
	 */
	class EO_TasksPermission {
		/* @var \Bitrix\Tasks\Access\Permission\TasksPermissionTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Permission\TasksPermissionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Access\Permission {
	/**
	 * EO_TasksPermission_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getPermissionIdList()
	 * @method \string[] fillPermissionId()
	 * @method \int[] getValueList()
	 * @method \int[] fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Access\Permission\TasksPermission $object)
	 * @method bool has(\Bitrix\Tasks\Access\Permission\TasksPermission $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission getByPrimary($primary)
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Access\Permission\TasksPermission $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection merge(?\Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TasksPermission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Access\Permission\TasksPermissionTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Permission\TasksPermissionTable';
	}
}
namespace Bitrix\Tasks\Access\Permission {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TasksPermission_Result exec()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission fetchObject()
	 * @method \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection fetchCollection()
	 */
	class EO_TasksPermission_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission fetchObject()
	 * @method \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection fetchCollection()
	 */
	class EO_TasksPermission_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection createCollection()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission wakeUpObject($row)
	 * @method \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection wakeUpCollection($rows)
	 */
	class EO_TasksPermission_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable:tasks/lib/access/permission/taskstemplatepermissiontable.php */
namespace Bitrix\Tasks\Access\Permission {
	/**
	 * TasksTemplatePermission
	 * @see \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getTemplateId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission setTemplateId(\int|\Bitrix\Main\DB\SqlExpression $templateId)
	 * @method bool hasTemplateId()
	 * @method bool isTemplateIdFilled()
	 * @method bool isTemplateIdChanged()
	 * @method \int remindActualTemplateId()
	 * @method \int requireTemplateId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission resetTemplateId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission unsetTemplateId()
	 * @method \int fillTemplateId()
	 * @method \string getAccessCode()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission setAccessCode(\string|\Bitrix\Main\DB\SqlExpression $accessCode)
	 * @method bool hasAccessCode()
	 * @method bool isAccessCodeFilled()
	 * @method bool isAccessCodeChanged()
	 * @method \string remindActualAccessCode()
	 * @method \string requireAccessCode()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission resetAccessCode()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission unsetAccessCode()
	 * @method \string fillAccessCode()
	 * @method \string getPermissionId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission setPermissionId(\string|\Bitrix\Main\DB\SqlExpression $permissionId)
	 * @method bool hasPermissionId()
	 * @method bool isPermissionIdFilled()
	 * @method bool isPermissionIdChanged()
	 * @method \string remindActualPermissionId()
	 * @method \string requirePermissionId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission resetPermissionId()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission unsetPermissionId()
	 * @method \string fillPermissionId()
	 * @method \int getValue()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission setValue(\int|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \int remindActualValue()
	 * @method \int requireValue()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission resetValue()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission unsetValue()
	 * @method \int fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission set($fieldName, $value)
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission reset($fieldName)
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Access\Permission\TasksTemplatePermission wakeUp($data)
	 */
	class EO_TasksTemplatePermission {
		/* @var \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Access\Permission {
	/**
	 * TasksTemplatePermissionCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getTemplateIdList()
	 * @method \int[] fillTemplateId()
	 * @method \string[] getAccessCodeList()
	 * @method \string[] fillAccessCode()
	 * @method \string[] getPermissionIdList()
	 * @method \string[] fillPermissionId()
	 * @method \int[] getValueList()
	 * @method \int[] fillValue()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Access\Permission\TasksTemplatePermission $object)
	 * @method bool has(\Bitrix\Tasks\Access\Permission\TasksTemplatePermission $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission getByPrimary($primary)
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Access\Permission\TasksTemplatePermission $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionCollection merge(?\Bitrix\Tasks\Access\Permission\TasksTemplatePermissionCollection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TasksTemplatePermission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable';
	}
}
namespace Bitrix\Tasks\Access\Permission {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TasksTemplatePermission_Result exec()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission fetchObject()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionCollection fetchCollection()
	 */
	class EO_TasksTemplatePermission_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission fetchObject()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionCollection fetchCollection()
	 */
	class EO_TasksTemplatePermission_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionCollection createCollection()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission wakeUpObject($row)
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionCollection wakeUpCollection($rows)
	 */
	class EO_TasksTemplatePermission_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Access\Role\TasksRoleTable:tasks/lib/access/role/tasksroletable.php */
namespace Bitrix\Tasks\Access\Role {
	/**
	 * TasksRole
	 * @see \Bitrix\Tasks\Access\Role\TasksRoleTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Access\Role\TasksRole setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Bitrix\Tasks\Access\Role\TasksRole setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Bitrix\Tasks\Access\Role\TasksRole resetName()
	 * @method \Bitrix\Tasks\Access\Role\TasksRole unsetName()
	 * @method \string fillName()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Access\Role\TasksRole set($fieldName, $value)
	 * @method \Bitrix\Tasks\Access\Role\TasksRole reset($fieldName)
	 * @method \Bitrix\Tasks\Access\Role\TasksRole unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Access\Role\TasksRole wakeUp($data)
	 */
	class EO_TasksRole {
		/* @var \Bitrix\Tasks\Access\Role\TasksRoleTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Role\TasksRoleTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Access\Role {
	/**
	 * EO_TasksRole_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Access\Role\TasksRole $object)
	 * @method bool has(\Bitrix\Tasks\Access\Role\TasksRole $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Access\Role\TasksRole getByPrimary($primary)
	 * @method \Bitrix\Tasks\Access\Role\TasksRole[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Access\Role\TasksRole $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Access\Role\TasksRole current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection merge(?\Bitrix\Tasks\Access\Role\EO_TasksRole_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TasksRole_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Access\Role\TasksRoleTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Role\TasksRoleTable';
	}
}
namespace Bitrix\Tasks\Access\Role {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TasksRole_Result exec()
	 * @method \Bitrix\Tasks\Access\Role\TasksRole fetchObject()
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection fetchCollection()
	 */
	class EO_TasksRole_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Access\Role\TasksRole fetchObject()
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection fetchCollection()
	 */
	class EO_TasksRole_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Access\Role\TasksRole createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection createCollection()
	 * @method \Bitrix\Tasks\Access\Role\TasksRole wakeUpObject($row)
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection wakeUpCollection($rows)
	 */
	class EO_TasksRole_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Access\Role\TasksRoleRelationTable:tasks/lib/access/role/tasksrolerelationtable.php */
namespace Bitrix\Tasks\Access\Role {
	/**
	 * TasksRoleRelation
	 * @see \Bitrix\Tasks\Access\Role\TasksRoleRelationTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getRoleId()
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation setRoleId(\int|\Bitrix\Main\DB\SqlExpression $roleId)
	 * @method bool hasRoleId()
	 * @method bool isRoleIdFilled()
	 * @method bool isRoleIdChanged()
	 * @method \int remindActualRoleId()
	 * @method \int requireRoleId()
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation resetRoleId()
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation unsetRoleId()
	 * @method \int fillRoleId()
	 * @method \string getRelation()
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation setRelation(\string|\Bitrix\Main\DB\SqlExpression $relation)
	 * @method bool hasRelation()
	 * @method bool isRelationFilled()
	 * @method bool isRelationChanged()
	 * @method \string remindActualRelation()
	 * @method \string requireRelation()
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation resetRelation()
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation unsetRelation()
	 * @method \string fillRelation()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation set($fieldName, $value)
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation reset($fieldName)
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Access\Role\TasksRoleRelation wakeUp($data)
	 */
	class EO_TasksRoleRelation {
		/* @var \Bitrix\Tasks\Access\Role\TasksRoleRelationTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Role\TasksRoleRelationTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Access\Role {
	/**
	 * EO_TasksRoleRelation_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getRoleIdList()
	 * @method \int[] fillRoleId()
	 * @method \string[] getRelationList()
	 * @method \string[] fillRelation()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Access\Role\TasksRoleRelation $object)
	 * @method bool has(\Bitrix\Tasks\Access\Role\TasksRoleRelation $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation getByPrimary($primary)
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Access\Role\TasksRoleRelation $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection merge(?\Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_TasksRoleRelation_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Access\Role\TasksRoleRelationTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Role\TasksRoleRelationTable';
	}
}
namespace Bitrix\Tasks\Access\Role {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TasksRoleRelation_Result exec()
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation fetchObject()
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection fetchCollection()
	 */
	class EO_TasksRoleRelation_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation fetchObject()
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection fetchCollection()
	 */
	class EO_TasksRoleRelation_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection createCollection()
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation wakeUpObject($row)
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection wakeUpCollection($rows)
	 */
	class EO_TasksRoleRelation_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtsIblockSectionTable:tasks/lib/integration/intranet/internals/runtime/userdepartment.php */
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime {
	/**
	 * EO_UtsIblockSection
	 * @see \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtsIblockSectionTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getValueId()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection setValueId(\int|\Bitrix\Main\DB\SqlExpression $valueId)
	 * @method bool hasValueId()
	 * @method bool isValueIdFilled()
	 * @method bool isValueIdChanged()
	 * @method \int getUfHead()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection setUfHead(\int|\Bitrix\Main\DB\SqlExpression $ufHead)
	 * @method bool hasUfHead()
	 * @method bool isUfHeadFilled()
	 * @method bool isUfHeadChanged()
	 * @method \int remindActualUfHead()
	 * @method \int requireUfHead()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection resetUfHead()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection unsetUfHead()
	 * @method \int fillUfHead()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection set($fieldName, $value)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection reset($fieldName)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection wakeUp($data)
	 */
	class EO_UtsIblockSection {
		/* @var \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtsIblockSectionTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtsIblockSectionTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime {
	/**
	 * EO_UtsIblockSection_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getValueIdList()
	 * @method \int[] getUfHeadList()
	 * @method \int[] fillUfHead()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection $object)
	 * @method bool has(\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection getByPrimary($primary)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection merge(?\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_UtsIblockSection_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtsIblockSectionTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtsIblockSectionTable';
	}
}
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UtsIblockSection_Result exec()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection fetchObject()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection fetchCollection()
	 */
	class EO_UtsIblockSection_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection fetchObject()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection fetchCollection()
	 */
	class EO_UtsIblockSection_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection createCollection()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection wakeUpObject($row)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection wakeUpCollection($rows)
	 */
	class EO_UtsIblockSection_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable:tasks/lib/integration/intranet/internals/runtime/utmuser.php */
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime {
	/**
	 * EO_UtmUser
	 * @see \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getValueId()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser setValueId(\int|\Bitrix\Main\DB\SqlExpression $valueId)
	 * @method bool hasValueId()
	 * @method bool isValueIdFilled()
	 * @method bool isValueIdChanged()
	 * @method \int remindActualValueId()
	 * @method \int requireValueId()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser resetValueId()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser unsetValueId()
	 * @method \int fillValueId()
	 * @method \int getFieldId()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser setFieldId(\int|\Bitrix\Main\DB\SqlExpression $fieldId)
	 * @method bool hasFieldId()
	 * @method bool isFieldIdFilled()
	 * @method bool isFieldIdChanged()
	 * @method \int remindActualFieldId()
	 * @method \int requireFieldId()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser resetFieldId()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser unsetFieldId()
	 * @method \int fillFieldId()
	 * @method \int getValueInt()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser setValueInt(\int|\Bitrix\Main\DB\SqlExpression $valueInt)
	 * @method bool hasValueInt()
	 * @method bool isValueIntFilled()
	 * @method bool isValueIntChanged()
	 * @method \int remindActualValueInt()
	 * @method \int requireValueInt()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser resetValueInt()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser unsetValueInt()
	 * @method \int fillValueInt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser set($fieldName, $value)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser reset($fieldName)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser wakeUp($data)
	 */
	class EO_UtmUser {
		/* @var \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime {
	/**
	 * EO_UtmUser_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getValueIdList()
	 * @method \int[] fillValueId()
	 * @method \int[] getFieldIdList()
	 * @method \int[] fillFieldId()
	 * @method \int[] getValueIntList()
	 * @method \int[] fillValueInt()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser $object)
	 * @method bool has(\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser getByPrimary($primary)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection merge(?\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_UtmUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable';
	}
}
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UtmUser_Result exec()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser fetchObject()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection fetchCollection()
	 */
	class EO_UtmUser_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser fetchObject()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection fetchCollection()
	 */
	class EO_UtmUser_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection createCollection()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser wakeUpObject($row)
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection wakeUpCollection($rows)
	 */
	class EO_UtmUser_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Integration\Rest\ElapsedTimeTable:tasks/lib/integration/rest/elapsedtime.php */
namespace Bitrix\Tasks\Integration\Rest {
	/**
	 * EO_ElapsedTime
	 * @see \Bitrix\Tasks\Integration\Rest\ElapsedTimeTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getCreatedDate()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setCreatedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $createdDate)
	 * @method bool hasCreatedDate()
	 * @method bool isCreatedDateFilled()
	 * @method bool isCreatedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime requireCreatedDate()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetCreatedDate()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime fillCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime getDateStart()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setDateStart(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStart)
	 * @method bool hasDateStart()
	 * @method bool isDateStartFilled()
	 * @method bool isDateStartChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStart()
	 * @method \Bitrix\Main\Type\DateTime requireDateStart()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetDateStart()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetDateStart()
	 * @method \Bitrix\Main\Type\DateTime fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime getDateStop()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setDateStop(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateStop)
	 * @method bool hasDateStop()
	 * @method bool isDateStopFilled()
	 * @method bool isDateStopChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateStop()
	 * @method \Bitrix\Main\Type\DateTime requireDateStop()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetDateStop()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetDateStop()
	 * @method \Bitrix\Main\Type\DateTime fillDateStop()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetUserId()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetUserId()
	 * @method \int fillUserId()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetTaskId()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getMinutes()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setMinutes(\int|\Bitrix\Main\DB\SqlExpression $minutes)
	 * @method bool hasMinutes()
	 * @method bool isMinutesFilled()
	 * @method bool isMinutesChanged()
	 * @method \int remindActualMinutes()
	 * @method \int requireMinutes()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetMinutes()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetMinutes()
	 * @method \int fillMinutes()
	 * @method \int getSeconds()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setSeconds(\int|\Bitrix\Main\DB\SqlExpression $seconds)
	 * @method bool hasSeconds()
	 * @method bool isSecondsFilled()
	 * @method bool isSecondsChanged()
	 * @method \int remindActualSeconds()
	 * @method \int requireSeconds()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetSeconds()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetSeconds()
	 * @method \int fillSeconds()
	 * @method \int getSource()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setSource(\int|\Bitrix\Main\DB\SqlExpression $source)
	 * @method bool hasSource()
	 * @method bool isSourceFilled()
	 * @method bool isSourceChanged()
	 * @method \int remindActualSource()
	 * @method \int requireSource()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetSource()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetSource()
	 * @method \int fillSource()
	 * @method \string getCommentText()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setCommentText(\string|\Bitrix\Main\DB\SqlExpression $commentText)
	 * @method bool hasCommentText()
	 * @method bool isCommentTextFilled()
	 * @method bool isCommentTextChanged()
	 * @method \string remindActualCommentText()
	 * @method \string requireCommentText()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetCommentText()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetCommentText()
	 * @method \string fillCommentText()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetUser()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime resetTask()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime set($fieldName, $value)
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime reset($fieldName)
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime wakeUp($data)
	 */
	class EO_ElapsedTime {
		/* @var \Bitrix\Tasks\Integration\Rest\ElapsedTimeTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Rest\ElapsedTimeTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Integration\Rest {
	/**
	 * EO_ElapsedTime_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreatedDate()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStartList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStart()
	 * @method \Bitrix\Main\Type\DateTime[] getDateStopList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateStop()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 * @method \int[] getTaskIdList()
	 * @method \int[] fillTaskId()
	 * @method \int[] getMinutesList()
	 * @method \int[] fillMinutes()
	 * @method \int[] getSecondsList()
	 * @method \int[] fillSeconds()
	 * @method \int[] getSourceList()
	 * @method \int[] fillSource()
	 * @method \string[] getCommentTextList()
	 * @method \string[] fillCommentText()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\TaskCollection fillTask()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Integration\Rest\EO_ElapsedTime $object)
	 * @method bool has(\Bitrix\Tasks\Integration\Rest\EO_ElapsedTime $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime getByPrimary($primary)
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Integration\Rest\EO_ElapsedTime $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection merge(?\Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ElapsedTime_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Integration\Rest\ElapsedTimeTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Rest\ElapsedTimeTable';
	}
}
namespace Bitrix\Tasks\Integration\Rest {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ElapsedTime_Result exec()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime fetchObject()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection fetchCollection()
	 */
	class EO_ElapsedTime_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime fetchObject()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection fetchCollection()
	 */
	class EO_ElapsedTime_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection createCollection()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime wakeUpObject($row)
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection wakeUpCollection($rows)
	 */
	class EO_ElapsedTime_Entity extends \Bitrix\Main\ORM\Entity {}
}
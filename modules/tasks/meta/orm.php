<?php

/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\TaskTable:tasks/lib/internals/task.php:71280d809f223c39631c2da840d8abdb */
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
	 * @method \Bitrix\Socialnetwork\EO_Workgroup getGroup()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup remindActualGroup()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup requireGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject setGroup(\Bitrix\Socialnetwork\EO_Workgroup $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetGroup()
	 * @method bool hasGroup()
	 * @method bool isGroupFilled()
	 * @method bool isGroupChanged()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup fillGroup()
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
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection getMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection requireMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection fillMemberList()
	 * @method bool hasMemberList()
	 * @method bool isMemberListFilled()
	 * @method bool isMemberListChanged()
	 * @method void addToMemberList(\Bitrix\Tasks\Internals\Task\MemberObject $member)
	 * @method void removeFromMemberList(\Bitrix\Tasks\Internals\Task\MemberObject $member)
	 * @method void removeAllMemberList()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMemberList()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMemberList()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * EO_Task_Collection
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
	 * @method \Bitrix\Main\EO_User[] getCreatorList()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection getCreatorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreator()
	 * @method \Bitrix\Main\EO_User[] getResponsibleList()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection getResponsibleCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillResponsible()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getParentList()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection getParentCollection()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillParent()
	 * @method \Bitrix\Main\EO_Site[] getSiteList()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection getSiteCollection()
	 * @method \Bitrix\Main\EO_Site_Collection fillSite()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject[] getMembersList()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection getMembersCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection fillMembers()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup[] getGroupList()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection getGroupCollection()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup_Collection fillGroup()
	 * @method \int[] getOutlookVersionList()
	 * @method \int[] fillOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection[] getMemberListList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection getMemberListCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection fillMemberList()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\EO_Task_Collection wakeUp($data)
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
	 */
	class EO_Task_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\TaskTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\TaskTable';
	}
}
namespace Bitrix\Tasks\Internals {
	/**
	 * @method static EO_Task_Query query()
	 * @method static EO_Task_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Task_Result getById($id)
	 * @method static EO_Task_Result getList(array $parameters = array())
	 * @method static EO_Task_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\TaskObject createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\EO_Task_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\TaskObject wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\EO_Task_Collection wakeUpCollection($rows)
	 */
	class TaskTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Task_Result exec()
	 * @method \Bitrix\Tasks\Internals\TaskObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Task_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\TaskObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fetchCollection()
	 */
	class EO_Task_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\TaskObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\TaskObject wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection wakeUpCollection($rows)
	 */
	class EO_Task_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Integration\Report\Internals\TaskTable:tasks/lib/integration/report/internals/task.php:11eaaa1bea7f437a540d63b273b4d631 */
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
	 * @method \Bitrix\Socialnetwork\EO_Workgroup getGroup()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup remindActualGroup()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup requireGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject setGroup(\Bitrix\Socialnetwork\EO_Workgroup $object)
	 * @method \Bitrix\Tasks\Internals\TaskObject resetGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetGroup()
	 * @method bool hasGroup()
	 * @method bool isGroupFilled()
	 * @method bool isGroupChanged()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup fillGroup()
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
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection getMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection requireMemberList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection fillMemberList()
	 * @method bool hasMemberList()
	 * @method bool isMemberListFilled()
	 * @method bool isMemberListChanged()
	 * @method void addToMemberList(\Bitrix\Tasks\Internals\Task\MemberObject $member)
	 * @method void removeFromMemberList(\Bitrix\Tasks\Internals\Task\MemberObject $member)
	 * @method void removeAllMemberList()
	 * @method \Bitrix\Tasks\Internals\TaskObject resetMemberList()
	 * @method \Bitrix\Tasks\Internals\TaskObject unsetMemberList()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * EO_Task_Collection
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
	 * @method \Bitrix\Main\EO_User[] getCreatorList()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection getCreatorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreator()
	 * @method \Bitrix\Main\EO_User[] getResponsibleList()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection getResponsibleCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillResponsible()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getParentList()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection getParentCollection()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection fillParent()
	 * @method \Bitrix\Main\EO_Site[] getSiteList()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection getSiteCollection()
	 * @method \Bitrix\Main\EO_Site_Collection fillSite()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject[] getMembersList()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection getMembersCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection fillMembers()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup[] getGroupList()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection getGroupCollection()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup_Collection fillGroup()
	 * @method \int[] getOutlookVersionList()
	 * @method \int[] fillOutlookVersion()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection[] getMemberListList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection getMemberListCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection fillMemberList()
	 * @method \string[] getDescriptionTrList()
	 * @method \string[] fillDescriptionTr()
	 * @method \string[] getStatusPseudoList()
	 * @method \string[] fillStatusPseudo()
	 * @method \Bitrix\Main\EO_User[] getCreatedByUserList()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection getCreatedByUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreatedByUser()
	 * @method \Bitrix\Main\EO_User[] getChangedByUserList()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection getChangedByUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillChangedByUser()
	 * @method \Bitrix\Main\EO_User[] getStatusChangedByUserList()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection getStatusChangedByUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillStatusChangedByUser()
	 * @method \Bitrix\Main\EO_User[] getClosedByUserList()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection getClosedByUserCollection()
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
	 * @method \string[] getDeclineReasonList()
	 * @method \string[] fillDeclineReason()
	 * @method \int[] getDeadlineCountedList()
	 * @method \int[] fillDeadlineCounted()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection wakeUp($data)
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
	 */
	class EO_Task_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Integration\Report\Internals\TaskTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Report\Internals\TaskTable';
	}
}
namespace Bitrix\Tasks\Integration\Report\Internals {
	/**
	 * @method static EO_Task_Query query()
	 * @method static EO_Task_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Task_Result getById($id)
	 * @method static EO_Task_Result getList(array $parameters = array())
	 * @method static EO_Task_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\TaskObject createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\TaskObject wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection wakeUpCollection($rows)
	 */
	class TaskTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Task_Result exec()
	 * @method \Bitrix\Tasks\Internals\TaskObject fetchObject()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Task_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\TaskObject fetchObject()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection fetchCollection()
	 */
	class EO_Task_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\TaskObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\TaskObject wakeUpObject($row)
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection wakeUpCollection($rows)
	 */
	class EO_Task_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\TagTable:tasks/lib/internals/task/tag.php:4d42cfd1c781cdf02a13fac3fa091798 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTask()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Tag_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\TagTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TagTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Tag_Query query()
	 * @method static EO_Tag_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Tag_Result getById($id)
	 * @method static EO_Tag_Result getList(array $parameters = array())
	 * @method static EO_Tag_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Tag createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Tag_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Tag wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Tag_Collection wakeUpCollection($rows)
	 */
	class TagTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Tag_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Tag_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\MemberTable:tasks/lib/internals/task/member.php:ea9220d6fef6a28ff2a767e68a1573b7 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * EO_Member_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getTaskIdList()
	 * @method \int[] getUserIdList()
	 * @method \string[] getTypeList()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskFollowedList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection getTaskFollowedCollection()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTaskFollowed()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskCoworkedList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection getTaskCoworkedCollection()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTaskCoworked()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Member_Collection wakeUp($data)
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
	 */
	class EO_Member_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\MemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\MemberTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Member_Query query()
	 * @method static EO_Member_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Member_Result getById($id)
	 * @method static EO_Member_Result getList(array $parameters = array())
	 * @method static EO_Member_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\MemberObject createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Member_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\MemberObject wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Member_Collection wakeUpCollection($rows)
	 */
	class MemberTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Member_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Member_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection fetchCollection()
	 */
	class EO_Member_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection wakeUpCollection($rows)
	 */
	class EO_Member_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ElapsedTimeTable:tasks/lib/internals/task/elapsedtime.php:de1831b08a515f64ce8c46472443d82d */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTask()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_ElapsedTime_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ElapsedTimeTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ElapsedTimeTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_ElapsedTime_Query query()
	 * @method static EO_ElapsedTime_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_ElapsedTime_Result getById($id)
	 * @method static EO_ElapsedTime_Result getList(array $parameters = array())
	 * @method static EO_ElapsedTime_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection wakeUpCollection($rows)
	 */
	class ElapsedTimeTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ElapsedTime_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\SortingTable:tasks/lib/internals/task/sorting.php:5ff75717153be14142d8301427abbb8e */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Sorting_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\SortingTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\SortingTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Sorting_Query query()
	 * @method static EO_Sorting_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Sorting_Result getById($id)
	 * @method static EO_Sorting_Result getList(array $parameters = array())
	 * @method static EO_Sorting_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Sorting createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Sorting wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection wakeUpCollection($rows)
	 */
	class SortingTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Sorting_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\FavoriteTable:tasks/lib/internals/task/favorite.php:a3dc3959a7bf720e2deab1a1270c1028 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTask()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Favorite_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\FavoriteTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\FavoriteTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Favorite_Query query()
	 * @method static EO_Favorite_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Favorite_Result getById($id)
	 * @method static EO_Favorite_Result getList(array $parameters = array())
	 * @method static EO_Favorite_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Favorite createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Favorite wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection wakeUpCollection($rows)
	 */
	class FavoriteTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Favorite_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Favorite_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ProjectDependenceTable:tasks/lib/internals/task/projectdependence.php:0f940065bd0bf4e9dfb7aa9e8351d208 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getDependsOnList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection getDependsOnCollection()
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillDependsOn()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_ProjectDependence_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ProjectDependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ProjectDependenceTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_ProjectDependence_Query query()
	 * @method static EO_ProjectDependence_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_ProjectDependence_Result getById($id)
	 * @method static EO_ProjectDependence_Result getList(array $parameters = array())
	 * @method static EO_ProjectDependence_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectDependence createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectDependence wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection wakeUpCollection($rows)
	 */
	class ProjectDependenceTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ProjectDependence_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_ProjectDependence_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\TemplateTable:tasks/lib/internals/task/template.php:cdb417387b0a7930d89662097b8f49ab */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Template
	 * @see \Bitrix\Tasks\Internals\Task\TemplateTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetTitle()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getDescription()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetDescription()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetDescription()
	 * @method \string fillDescription()
	 * @method \boolean getDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setDescriptionInBbcode(\boolean|\Bitrix\Main\DB\SqlExpression $descriptionInBbcode)
	 * @method bool hasDescriptionInBbcode()
	 * @method bool isDescriptionInBbcodeFilled()
	 * @method bool isDescriptionInBbcodeChanged()
	 * @method \boolean remindActualDescriptionInBbcode()
	 * @method \boolean requireDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetDescriptionInBbcode()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetDescriptionInBbcode()
	 * @method \boolean fillDescriptionInBbcode()
	 * @method \string getPriority()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setPriority(\string|\Bitrix\Main\DB\SqlExpression $priority)
	 * @method bool hasPriority()
	 * @method bool isPriorityFilled()
	 * @method bool isPriorityChanged()
	 * @method \string remindActualPriority()
	 * @method \string requirePriority()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetPriority()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetPriority()
	 * @method \string fillPriority()
	 * @method \string getStatus()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetStatus()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetStatus()
	 * @method \string fillStatus()
	 * @method \int getResponsibleId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setResponsibleId(\int|\Bitrix\Main\DB\SqlExpression $responsibleId)
	 * @method bool hasResponsibleId()
	 * @method bool isResponsibleIdFilled()
	 * @method bool isResponsibleIdChanged()
	 * @method \int remindActualResponsibleId()
	 * @method \int requireResponsibleId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetResponsibleId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetResponsibleId()
	 * @method \int fillResponsibleId()
	 * @method \int getTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setTimeEstimate(\int|\Bitrix\Main\DB\SqlExpression $timeEstimate)
	 * @method bool hasTimeEstimate()
	 * @method bool isTimeEstimateFilled()
	 * @method bool isTimeEstimateChanged()
	 * @method \int remindActualTimeEstimate()
	 * @method \int requireTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetTimeEstimate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetTimeEstimate()
	 * @method \int fillTimeEstimate()
	 * @method \boolean getReplicate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setReplicate(\boolean|\Bitrix\Main\DB\SqlExpression $replicate)
	 * @method bool hasReplicate()
	 * @method bool isReplicateFilled()
	 * @method bool isReplicateChanged()
	 * @method \boolean remindActualReplicate()
	 * @method \boolean requireReplicate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetReplicate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetReplicate()
	 * @method \boolean fillReplicate()
	 * @method \int getCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setCreatedBy(\int|\Bitrix\Main\DB\SqlExpression $createdBy)
	 * @method bool hasCreatedBy()
	 * @method bool isCreatedByFilled()
	 * @method bool isCreatedByChanged()
	 * @method \int remindActualCreatedBy()
	 * @method \int requireCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetCreatedBy()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetCreatedBy()
	 * @method \int fillCreatedBy()
	 * @method \string getXmlId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setXmlId(\string|\Bitrix\Main\DB\SqlExpression $xmlId)
	 * @method bool hasXmlId()
	 * @method bool isXmlIdFilled()
	 * @method bool isXmlIdChanged()
	 * @method \string remindActualXmlId()
	 * @method \string requireXmlId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetXmlId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetXmlId()
	 * @method \string fillXmlId()
	 * @method \boolean getAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setAllowChangeDeadline(\boolean|\Bitrix\Main\DB\SqlExpression $allowChangeDeadline)
	 * @method bool hasAllowChangeDeadline()
	 * @method bool isAllowChangeDeadlineFilled()
	 * @method bool isAllowChangeDeadlineChanged()
	 * @method \boolean remindActualAllowChangeDeadline()
	 * @method \boolean requireAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetAllowChangeDeadline()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetAllowChangeDeadline()
	 * @method \boolean fillAllowChangeDeadline()
	 * @method \boolean getAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setAllowTimeTracking(\boolean|\Bitrix\Main\DB\SqlExpression $allowTimeTracking)
	 * @method bool hasAllowTimeTracking()
	 * @method bool isAllowTimeTrackingFilled()
	 * @method bool isAllowTimeTrackingChanged()
	 * @method \boolean remindActualAllowTimeTracking()
	 * @method \boolean requireAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetAllowTimeTracking()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetAllowTimeTracking()
	 * @method \boolean fillAllowTimeTracking()
	 * @method \boolean getTaskControl()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setTaskControl(\boolean|\Bitrix\Main\DB\SqlExpression $taskControl)
	 * @method bool hasTaskControl()
	 * @method bool isTaskControlFilled()
	 * @method bool isTaskControlChanged()
	 * @method \boolean remindActualTaskControl()
	 * @method \boolean requireTaskControl()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetTaskControl()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetTaskControl()
	 * @method \boolean fillTaskControl()
	 * @method \boolean getAddInReport()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setAddInReport(\boolean|\Bitrix\Main\DB\SqlExpression $addInReport)
	 * @method bool hasAddInReport()
	 * @method bool isAddInReportFilled()
	 * @method bool isAddInReportChanged()
	 * @method \boolean remindActualAddInReport()
	 * @method \boolean requireAddInReport()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetAddInReport()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetAddInReport()
	 * @method \boolean fillAddInReport()
	 * @method \boolean getMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setMatchWorkTime(\boolean|\Bitrix\Main\DB\SqlExpression $matchWorkTime)
	 * @method bool hasMatchWorkTime()
	 * @method bool isMatchWorkTimeFilled()
	 * @method bool isMatchWorkTimeChanged()
	 * @method \boolean remindActualMatchWorkTime()
	 * @method \boolean requireMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetMatchWorkTime()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetMatchWorkTime()
	 * @method \boolean fillMatchWorkTime()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetGroupId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \int getParentId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetParentId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetParentId()
	 * @method \int fillParentId()
	 * @method \boolean getMultitask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setMultitask(\boolean|\Bitrix\Main\DB\SqlExpression $multitask)
	 * @method bool hasMultitask()
	 * @method bool isMultitaskFilled()
	 * @method bool isMultitaskChanged()
	 * @method \boolean remindActualMultitask()
	 * @method \boolean requireMultitask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetMultitask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetMultitask()
	 * @method \boolean fillMultitask()
	 * @method \string getSiteId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setSiteId(\string|\Bitrix\Main\DB\SqlExpression $siteId)
	 * @method bool hasSiteId()
	 * @method bool isSiteIdFilled()
	 * @method bool isSiteIdChanged()
	 * @method \string remindActualSiteId()
	 * @method \string requireSiteId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetSiteId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetSiteId()
	 * @method \string fillSiteId()
	 * @method \string getReplicateParams()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setReplicateParams(\string|\Bitrix\Main\DB\SqlExpression $replicateParams)
	 * @method bool hasReplicateParams()
	 * @method bool isReplicateParamsFilled()
	 * @method bool isReplicateParamsChanged()
	 * @method \string remindActualReplicateParams()
	 * @method \string requireReplicateParams()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetReplicateParams()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetReplicateParams()
	 * @method \string fillReplicateParams()
	 * @method \string getTags()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setTags(\string|\Bitrix\Main\DB\SqlExpression $tags)
	 * @method bool hasTags()
	 * @method bool isTagsFilled()
	 * @method bool isTagsChanged()
	 * @method \string remindActualTags()
	 * @method \string requireTags()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetTags()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetTags()
	 * @method \string fillTags()
	 * @method \string getAccomplices()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setAccomplices(\string|\Bitrix\Main\DB\SqlExpression $accomplices)
	 * @method bool hasAccomplices()
	 * @method bool isAccomplicesFilled()
	 * @method bool isAccomplicesChanged()
	 * @method \string remindActualAccomplices()
	 * @method \string requireAccomplices()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetAccomplices()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetAccomplices()
	 * @method \string fillAccomplices()
	 * @method \string getAuditors()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setAuditors(\string|\Bitrix\Main\DB\SqlExpression $auditors)
	 * @method bool hasAuditors()
	 * @method bool isAuditorsFilled()
	 * @method bool isAuditorsChanged()
	 * @method \string remindActualAuditors()
	 * @method \string requireAuditors()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetAuditors()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetAuditors()
	 * @method \string fillAuditors()
	 * @method \string getResponsibles()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setResponsibles(\string|\Bitrix\Main\DB\SqlExpression $responsibles)
	 * @method bool hasResponsibles()
	 * @method bool isResponsiblesFilled()
	 * @method bool isResponsiblesChanged()
	 * @method \string remindActualResponsibles()
	 * @method \string requireResponsibles()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetResponsibles()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetResponsibles()
	 * @method \string fillResponsibles()
	 * @method \string getDependsOn()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setDependsOn(\string|\Bitrix\Main\DB\SqlExpression $dependsOn)
	 * @method bool hasDependsOn()
	 * @method bool isDependsOnFilled()
	 * @method bool isDependsOnChanged()
	 * @method \string remindActualDependsOn()
	 * @method \string requireDependsOn()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetDependsOn()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetDependsOn()
	 * @method \string fillDependsOn()
	 * @method \int getDeadlineAfter()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setDeadlineAfter(\int|\Bitrix\Main\DB\SqlExpression $deadlineAfter)
	 * @method bool hasDeadlineAfter()
	 * @method bool isDeadlineAfterFilled()
	 * @method bool isDeadlineAfterChanged()
	 * @method \int remindActualDeadlineAfter()
	 * @method \int requireDeadlineAfter()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetDeadlineAfter()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetDeadlineAfter()
	 * @method \int fillDeadlineAfter()
	 * @method \int getStartDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setStartDatePlanAfter(\int|\Bitrix\Main\DB\SqlExpression $startDatePlanAfter)
	 * @method bool hasStartDatePlanAfter()
	 * @method bool isStartDatePlanAfterFilled()
	 * @method bool isStartDatePlanAfterChanged()
	 * @method \int remindActualStartDatePlanAfter()
	 * @method \int requireStartDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetStartDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetStartDatePlanAfter()
	 * @method \int fillStartDatePlanAfter()
	 * @method \int getEndDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setEndDatePlanAfter(\int|\Bitrix\Main\DB\SqlExpression $endDatePlanAfter)
	 * @method bool hasEndDatePlanAfter()
	 * @method bool isEndDatePlanAfterFilled()
	 * @method bool isEndDatePlanAfterChanged()
	 * @method \int remindActualEndDatePlanAfter()
	 * @method \int requireEndDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetEndDatePlanAfter()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetEndDatePlanAfter()
	 * @method \int fillEndDatePlanAfter()
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int remindActualTaskId()
	 * @method \int requireTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetTaskId()
	 * @method \int fillTaskId()
	 * @method \int getTparamType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setTparamType(\int|\Bitrix\Main\DB\SqlExpression $tparamType)
	 * @method bool hasTparamType()
	 * @method bool isTparamTypeFilled()
	 * @method bool isTparamTypeChanged()
	 * @method \int remindActualTparamType()
	 * @method \int requireTparamType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetTparamType()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetTparamType()
	 * @method \int fillTparamType()
	 * @method \int getTparamReplicationCount()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setTparamReplicationCount(\int|\Bitrix\Main\DB\SqlExpression $tparamReplicationCount)
	 * @method bool hasTparamReplicationCount()
	 * @method bool isTparamReplicationCountFilled()
	 * @method bool isTparamReplicationCountChanged()
	 * @method \int remindActualTparamReplicationCount()
	 * @method \int requireTparamReplicationCount()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetTparamReplicationCount()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetTparamReplicationCount()
	 * @method \int fillTparamReplicationCount()
	 * @method \string getZombie()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setZombie(\string|\Bitrix\Main\DB\SqlExpression $zombie)
	 * @method bool hasZombie()
	 * @method bool isZombieFilled()
	 * @method bool isZombieChanged()
	 * @method \string remindActualZombie()
	 * @method \string requireZombie()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetZombie()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetZombie()
	 * @method \string fillZombie()
	 * @method \string getFiles()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setFiles(\string|\Bitrix\Main\DB\SqlExpression $files)
	 * @method bool hasFiles()
	 * @method bool isFilesFilled()
	 * @method bool isFilesChanged()
	 * @method \string remindActualFiles()
	 * @method \string requireFiles()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetFiles()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetFiles()
	 * @method \string fillFiles()
	 * @method \Bitrix\Main\EO_User getCreator()
	 * @method \Bitrix\Main\EO_User remindActualCreator()
	 * @method \Bitrix\Main\EO_User requireCreator()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setCreator(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetCreator()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetCreator()
	 * @method bool hasCreator()
	 * @method bool isCreatorFilled()
	 * @method bool isCreatorChanged()
	 * @method \Bitrix\Main\EO_User fillCreator()
	 * @method \Bitrix\Main\EO_User getResponsible()
	 * @method \Bitrix\Main\EO_User remindActualResponsible()
	 * @method \Bitrix\Main\EO_User requireResponsible()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template setResponsible(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template resetResponsible()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unsetResponsible()
	 * @method bool hasResponsible()
	 * @method bool isResponsibleFilled()
	 * @method bool isResponsibleChanged()
	 * @method \Bitrix\Main\EO_User fillResponsible()
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
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Template wakeUp($data)
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
	 * EO_Template_Collection
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
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template_Collection getCreatorCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillCreator()
	 * @method \Bitrix\Main\EO_User[] getResponsibleList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template_Collection getResponsibleCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillResponsible()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Template $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Template $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Template $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Template_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Template_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\TemplateTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TemplateTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Template_Query query()
	 * @method static EO_Template_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Template_Result getById($id)
	 * @method static EO_Template_Result getList(array $parameters = array())
	 * @method static EO_Template_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Template createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Template_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Template wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Template_Collection wakeUpCollection($rows)
	 */
	class TemplateTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Template_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Template_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template_Collection fetchCollection()
	 */
	class EO_Template_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template_Collection wakeUpCollection($rows)
	 */
	class EO_Template_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\DependenceTable:tasks/lib/internals/task/template/dependence.php:d965a7624a5bb7c5c8bf90df3de0b63b */
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
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template getTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template remindActualTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template requireTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setTemplate(\Bitrix\Tasks\Internals\Task\EO_Template $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence resetTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence unsetTemplate()
	 * @method bool hasTemplate()
	 * @method bool isTemplateFilled()
	 * @method bool isTemplateChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template fillTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template getParentTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template remindActualParentTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template requireParentTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence setParentTemplate(\Bitrix\Tasks\Internals\Task\EO_Template $object)
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence resetParentTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence unsetParentTemplate()
	 * @method bool hasParentTemplate()
	 * @method bool isParentTemplateFilled()
	 * @method bool isParentTemplateChanged()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template fillParentTemplate()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template[] getTemplateList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection getTemplateCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template_Collection fillTemplate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template[] getParentTemplateList()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection getParentTemplateCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Template_Collection fillParentTemplate()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Dependence_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\DependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\DependenceTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * @method static EO_Dependence_Query query()
	 * @method static EO_Dependence_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Dependence_Result getById($id)
	 * @method static EO_Dependence_Result getList(array $parameters = array())
	 * @method static EO_Dependence_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Dependence createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Dependence wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection wakeUpCollection($rows)
	 */
	class DependenceTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Dependence_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Dependence_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\CheckListTable:tasks/lib/internals/task/template/checklist.php:9acd8887bad22de7f852d62b53d1f946 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_CheckList_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\CheckListTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\CheckListTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * @method static EO_CheckList_Query query()
	 * @method static EO_CheckList_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_CheckList_Result getById($id)
	 * @method static EO_CheckList_Result getList(array $parameters = array())
	 * @method static EO_CheckList_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckList createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckList wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection wakeUpCollection($rows)
	 */
	class CheckListTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CheckList_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Notification\Task\ThrottleTable:tasks/lib/internals/notification/task/throttle.php:2cc97a0d8e0386cb1a2dcc31f9bc9c0a */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Throttle_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Notification\Task\ThrottleTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Notification\Task\ThrottleTable';
	}
}
namespace Bitrix\Tasks\Internals\Notification\Task {
	/**
	 * @method static EO_Throttle_Query query()
	 * @method static EO_Throttle_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Throttle_Result getById($id)
	 * @method static EO_Throttle_Result getList(array $parameters = array())
	 * @method static EO_Throttle_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection wakeUpCollection($rows)
	 */
	class ThrottleTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Throttle_Result exec()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle fetchObject()
	 * @method \Bitrix\Tasks\Internals\Notification\Task\EO_Throttle_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Access\Permission\TasksPermissionTable:tasks/lib/access/permission/taskspermissiontable.php:51e6cc0f398fe83df5cde94bf09a506c */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_TasksPermission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Access\Permission\TasksPermissionTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Permission\TasksPermissionTable';
	}
}
namespace Bitrix\Tasks\Access\Permission {
	/**
	 * @method static EO_TasksPermission_Query query()
	 * @method static EO_TasksPermission_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_TasksPermission_Result getById($id)
	 * @method static EO_TasksPermission_Result getList(array $parameters = array())
	 * @method static EO_TasksPermission_Entity getEntity()
	 * @method static \Bitrix\Tasks\Access\Permission\TasksPermission createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection createCollection()
	 * @method static \Bitrix\Tasks\Access\Permission\TasksPermission wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection wakeUpCollection($rows)
	 */
	class TasksPermissionTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TasksPermission_Result exec()
	 * @method \Bitrix\Tasks\Access\Permission\TasksPermission fetchObject()
	 * @method \Bitrix\Tasks\Access\Permission\EO_TasksPermission_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable:tasks/lib/access/permission/taskstemplatepermissiontable.php:51bfca1b38a301e25c05f9b12ca70098 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * EO_TasksTemplatePermission_Collection
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Access\Permission\EO_TasksTemplatePermission_Collection wakeUp($data)
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
	 */
	class EO_TasksTemplatePermission_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable';
	}
}
namespace Bitrix\Tasks\Access\Permission {
	/**
	 * @method static EO_TasksTemplatePermission_Query query()
	 * @method static EO_TasksTemplatePermission_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_TasksTemplatePermission_Result getById($id)
	 * @method static EO_TasksTemplatePermission_Result getList(array $parameters = array())
	 * @method static EO_TasksTemplatePermission_Entity getEntity()
	 * @method static \Bitrix\Tasks\Access\Permission\TasksTemplatePermission createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Access\Permission\EO_TasksTemplatePermission_Collection createCollection()
	 * @method static \Bitrix\Tasks\Access\Permission\TasksTemplatePermission wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Access\Permission\EO_TasksTemplatePermission_Collection wakeUpCollection($rows)
	 */
	class TasksTemplatePermissionTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TasksTemplatePermission_Result exec()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission fetchObject()
	 * @method \Bitrix\Tasks\Access\Permission\EO_TasksTemplatePermission_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_TasksTemplatePermission_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission fetchObject()
	 * @method \Bitrix\Tasks\Access\Permission\EO_TasksTemplatePermission_Collection fetchCollection()
	 */
	class EO_TasksTemplatePermission_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Access\Permission\EO_TasksTemplatePermission_Collection createCollection()
	 * @method \Bitrix\Tasks\Access\Permission\TasksTemplatePermission wakeUpObject($row)
	 * @method \Bitrix\Tasks\Access\Permission\EO_TasksTemplatePermission_Collection wakeUpCollection($rows)
	 */
	class EO_TasksTemplatePermission_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Access\Role\TasksRoleTable:tasks/lib/access/role/tasksroletable.php:19f9cd31fed5458fcc5722a7e694db66 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_TasksRole_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Access\Role\TasksRoleTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Role\TasksRoleTable';
	}
}
namespace Bitrix\Tasks\Access\Role {
	/**
	 * @method static EO_TasksRole_Query query()
	 * @method static EO_TasksRole_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_TasksRole_Result getById($id)
	 * @method static EO_TasksRole_Result getList(array $parameters = array())
	 * @method static EO_TasksRole_Entity getEntity()
	 * @method static \Bitrix\Tasks\Access\Role\TasksRole createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection createCollection()
	 * @method static \Bitrix\Tasks\Access\Role\TasksRole wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection wakeUpCollection($rows)
	 */
	class TasksRoleTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TasksRole_Result exec()
	 * @method \Bitrix\Tasks\Access\Role\TasksRole fetchObject()
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRole_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Access\Role\TasksRoleRelationTable:tasks/lib/access/role/tasksrolerelationtable.php:33ead6725abd2f03756da86535ee0e06 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_TasksRoleRelation_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Access\Role\TasksRoleRelationTable */
		static public $dataClass = '\Bitrix\Tasks\Access\Role\TasksRoleRelationTable';
	}
}
namespace Bitrix\Tasks\Access\Role {
	/**
	 * @method static EO_TasksRoleRelation_Query query()
	 * @method static EO_TasksRoleRelation_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_TasksRoleRelation_Result getById($id)
	 * @method static EO_TasksRoleRelation_Result getList(array $parameters = array())
	 * @method static EO_TasksRoleRelation_Entity getEntity()
	 * @method static \Bitrix\Tasks\Access\Role\TasksRoleRelation createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection createCollection()
	 * @method static \Bitrix\Tasks\Access\Role\TasksRoleRelation wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection wakeUpCollection($rows)
	 */
	class TasksRoleRelationTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TasksRoleRelation_Result exec()
	 * @method \Bitrix\Tasks\Access\Role\TasksRoleRelation fetchObject()
	 * @method \Bitrix\Tasks\Access\Role\EO_TasksRoleRelation_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable:tasks/lib/integration/intranet/internals/runtime/userdepartment.php:50e85ee0c852126997bea7925df0b84c */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_UtmUser_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable';
	}
}
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime {
	/**
	 * @method static EO_UtmUser_Query query()
	 * @method static EO_UtmUser_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_UtmUser_Result getById($id)
	 * @method static EO_UtmUser_Result getList(array $parameters = array())
	 * @method static EO_UtmUser_Entity getEntity()
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection createCollection()
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection wakeUpCollection($rows)
	 */
	class UtmUserTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UtmUser_Result exec()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser fetchObject()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtsIblockSectionTable:tasks/lib/integration/intranet/internals/runtime/userdepartment.php:50e85ee0c852126997bea7925df0b84c */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_UtsIblockSection_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtsIblockSectionTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtsIblockSectionTable';
	}
}
namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime {
	/**
	 * @method static EO_UtsIblockSection_Query query()
	 * @method static EO_UtsIblockSection_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_UtsIblockSection_Result getById($id)
	 * @method static EO_UtsIblockSection_Result getList(array $parameters = array())
	 * @method static EO_UtsIblockSection_Entity getEntity()
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection createCollection()
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection wakeUpCollection($rows)
	 */
	class UtsIblockSectionTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UtsIblockSection_Result exec()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection fetchObject()
	 * @method \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtsIblockSection_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Integration\Rest\ElapsedTimeTable:tasks/lib/integration/rest/elapsedtime.php:4475cbba6b76ebc7359e273270ba225b */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTask()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_ElapsedTime_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Integration\Rest\ElapsedTimeTable */
		static public $dataClass = '\Bitrix\Tasks\Integration\Rest\ElapsedTimeTable';
	}
}
namespace Bitrix\Tasks\Integration\Rest {
	/**
	 * @method static EO_ElapsedTime_Query query()
	 * @method static EO_ElapsedTime_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_ElapsedTime_Result getById($id)
	 * @method static EO_ElapsedTime_Result getList(array $parameters = array())
	 * @method static EO_ElapsedTime_Entity getEntity()
	 * @method static \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection createCollection()
	 * @method static \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection wakeUpCollection($rows)
	 */
	class ElapsedTimeTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ElapsedTime_Result exec()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime fetchObject()
	 * @method \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Counter\CounterTable:tasks/lib/internals/counter/countertable.php:4cb0861df7d26444ca4d08be86f64762 */
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
	 * @method \int getOpened()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setOpened(\int|\Bitrix\Main\DB\SqlExpression $opened)
	 * @method bool hasOpened()
	 * @method bool isOpenedFilled()
	 * @method bool isOpenedChanged()
	 * @method \int remindActualOpened()
	 * @method \int requireOpened()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetOpened()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetOpened()
	 * @method \int fillOpened()
	 * @method \int getClosed()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setClosed(\int|\Bitrix\Main\DB\SqlExpression $closed)
	 * @method bool hasClosed()
	 * @method bool isClosedFilled()
	 * @method bool isClosedChanged()
	 * @method \int remindActualClosed()
	 * @method \int requireClosed()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetClosed()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetClosed()
	 * @method \int fillClosed()
	 * @method \int getExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setExpired(\int|\Bitrix\Main\DB\SqlExpression $expired)
	 * @method bool hasExpired()
	 * @method bool isExpiredFilled()
	 * @method bool isExpiredChanged()
	 * @method \int remindActualExpired()
	 * @method \int requireExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetExpired()
	 * @method \int fillExpired()
	 * @method \int getNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setNewComments(\int|\Bitrix\Main\DB\SqlExpression $newComments)
	 * @method bool hasNewComments()
	 * @method bool isNewCommentsFilled()
	 * @method bool isNewCommentsChanged()
	 * @method \int remindActualNewComments()
	 * @method \int requireNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetNewComments()
	 * @method \int fillNewComments()
	 * @method \int getMyExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setMyExpired(\int|\Bitrix\Main\DB\SqlExpression $myExpired)
	 * @method bool hasMyExpired()
	 * @method bool isMyExpiredFilled()
	 * @method bool isMyExpiredChanged()
	 * @method \int remindActualMyExpired()
	 * @method \int requireMyExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetMyExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetMyExpired()
	 * @method \int fillMyExpired()
	 * @method \int getMyExpiredSoon()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setMyExpiredSoon(\int|\Bitrix\Main\DB\SqlExpression $myExpiredSoon)
	 * @method bool hasMyExpiredSoon()
	 * @method bool isMyExpiredSoonFilled()
	 * @method bool isMyExpiredSoonChanged()
	 * @method \int remindActualMyExpiredSoon()
	 * @method \int requireMyExpiredSoon()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetMyExpiredSoon()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetMyExpiredSoon()
	 * @method \int fillMyExpiredSoon()
	 * @method \int getMyNotViewed()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setMyNotViewed(\int|\Bitrix\Main\DB\SqlExpression $myNotViewed)
	 * @method bool hasMyNotViewed()
	 * @method bool isMyNotViewedFilled()
	 * @method bool isMyNotViewedChanged()
	 * @method \int remindActualMyNotViewed()
	 * @method \int requireMyNotViewed()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetMyNotViewed()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetMyNotViewed()
	 * @method \int fillMyNotViewed()
	 * @method \int getMyWithoutDeadline()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setMyWithoutDeadline(\int|\Bitrix\Main\DB\SqlExpression $myWithoutDeadline)
	 * @method bool hasMyWithoutDeadline()
	 * @method bool isMyWithoutDeadlineFilled()
	 * @method bool isMyWithoutDeadlineChanged()
	 * @method \int remindActualMyWithoutDeadline()
	 * @method \int requireMyWithoutDeadline()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetMyWithoutDeadline()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetMyWithoutDeadline()
	 * @method \int fillMyWithoutDeadline()
	 * @method \int getMyNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setMyNewComments(\int|\Bitrix\Main\DB\SqlExpression $myNewComments)
	 * @method bool hasMyNewComments()
	 * @method bool isMyNewCommentsFilled()
	 * @method bool isMyNewCommentsChanged()
	 * @method \int remindActualMyNewComments()
	 * @method \int requireMyNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetMyNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetMyNewComments()
	 * @method \int fillMyNewComments()
	 * @method \int getOriginatorWithoutDeadline()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setOriginatorWithoutDeadline(\int|\Bitrix\Main\DB\SqlExpression $originatorWithoutDeadline)
	 * @method bool hasOriginatorWithoutDeadline()
	 * @method bool isOriginatorWithoutDeadlineFilled()
	 * @method bool isOriginatorWithoutDeadlineChanged()
	 * @method \int remindActualOriginatorWithoutDeadline()
	 * @method \int requireOriginatorWithoutDeadline()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetOriginatorWithoutDeadline()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetOriginatorWithoutDeadline()
	 * @method \int fillOriginatorWithoutDeadline()
	 * @method \int getOriginatorExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setOriginatorExpired(\int|\Bitrix\Main\DB\SqlExpression $originatorExpired)
	 * @method bool hasOriginatorExpired()
	 * @method bool isOriginatorExpiredFilled()
	 * @method bool isOriginatorExpiredChanged()
	 * @method \int remindActualOriginatorExpired()
	 * @method \int requireOriginatorExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetOriginatorExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetOriginatorExpired()
	 * @method \int fillOriginatorExpired()
	 * @method \int getOriginatorWaitCtrl()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setOriginatorWaitCtrl(\int|\Bitrix\Main\DB\SqlExpression $originatorWaitCtrl)
	 * @method bool hasOriginatorWaitCtrl()
	 * @method bool isOriginatorWaitCtrlFilled()
	 * @method bool isOriginatorWaitCtrlChanged()
	 * @method \int remindActualOriginatorWaitCtrl()
	 * @method \int requireOriginatorWaitCtrl()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetOriginatorWaitCtrl()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetOriginatorWaitCtrl()
	 * @method \int fillOriginatorWaitCtrl()
	 * @method \int getOriginatorNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setOriginatorNewComments(\int|\Bitrix\Main\DB\SqlExpression $originatorNewComments)
	 * @method bool hasOriginatorNewComments()
	 * @method bool isOriginatorNewCommentsFilled()
	 * @method bool isOriginatorNewCommentsChanged()
	 * @method \int remindActualOriginatorNewComments()
	 * @method \int requireOriginatorNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetOriginatorNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetOriginatorNewComments()
	 * @method \int fillOriginatorNewComments()
	 * @method \int getAuditorExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setAuditorExpired(\int|\Bitrix\Main\DB\SqlExpression $auditorExpired)
	 * @method bool hasAuditorExpired()
	 * @method bool isAuditorExpiredFilled()
	 * @method bool isAuditorExpiredChanged()
	 * @method \int remindActualAuditorExpired()
	 * @method \int requireAuditorExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetAuditorExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetAuditorExpired()
	 * @method \int fillAuditorExpired()
	 * @method \int getAuditorNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setAuditorNewComments(\int|\Bitrix\Main\DB\SqlExpression $auditorNewComments)
	 * @method bool hasAuditorNewComments()
	 * @method bool isAuditorNewCommentsFilled()
	 * @method bool isAuditorNewCommentsChanged()
	 * @method \int remindActualAuditorNewComments()
	 * @method \int requireAuditorNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetAuditorNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetAuditorNewComments()
	 * @method \int fillAuditorNewComments()
	 * @method \int getAccomplicesExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setAccomplicesExpired(\int|\Bitrix\Main\DB\SqlExpression $accomplicesExpired)
	 * @method bool hasAccomplicesExpired()
	 * @method bool isAccomplicesExpiredFilled()
	 * @method bool isAccomplicesExpiredChanged()
	 * @method \int remindActualAccomplicesExpired()
	 * @method \int requireAccomplicesExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetAccomplicesExpired()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetAccomplicesExpired()
	 * @method \int fillAccomplicesExpired()
	 * @method \int getAccomplicesExpiredSoon()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setAccomplicesExpiredSoon(\int|\Bitrix\Main\DB\SqlExpression $accomplicesExpiredSoon)
	 * @method bool hasAccomplicesExpiredSoon()
	 * @method bool isAccomplicesExpiredSoonFilled()
	 * @method bool isAccomplicesExpiredSoonChanged()
	 * @method \int remindActualAccomplicesExpiredSoon()
	 * @method \int requireAccomplicesExpiredSoon()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetAccomplicesExpiredSoon()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetAccomplicesExpiredSoon()
	 * @method \int fillAccomplicesExpiredSoon()
	 * @method \int getAccomplicesNotViewed()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setAccomplicesNotViewed(\int|\Bitrix\Main\DB\SqlExpression $accomplicesNotViewed)
	 * @method bool hasAccomplicesNotViewed()
	 * @method bool isAccomplicesNotViewedFilled()
	 * @method bool isAccomplicesNotViewedChanged()
	 * @method \int remindActualAccomplicesNotViewed()
	 * @method \int requireAccomplicesNotViewed()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetAccomplicesNotViewed()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetAccomplicesNotViewed()
	 * @method \int fillAccomplicesNotViewed()
	 * @method \int getAccomplicesNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setAccomplicesNewComments(\int|\Bitrix\Main\DB\SqlExpression $accomplicesNewComments)
	 * @method bool hasAccomplicesNewComments()
	 * @method bool isAccomplicesNewCommentsFilled()
	 * @method bool isAccomplicesNewCommentsChanged()
	 * @method \int remindActualAccomplicesNewComments()
	 * @method \int requireAccomplicesNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetAccomplicesNewComments()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetAccomplicesNewComments()
	 * @method \int fillAccomplicesNewComments()
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
	 * @method \Bitrix\Socialnetwork\EO_Workgroup getGroup()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup remindActualGroup()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup requireGroup()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter setGroup(\Bitrix\Socialnetwork\EO_Workgroup $object)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter resetGroup()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter unsetGroup()
	 * @method bool hasGroup()
	 * @method bool isGroupFilled()
	 * @method bool isGroupChanged()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup fillGroup()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \int[] getOpenedList()
	 * @method \int[] fillOpened()
	 * @method \int[] getClosedList()
	 * @method \int[] fillClosed()
	 * @method \int[] getExpiredList()
	 * @method \int[] fillExpired()
	 * @method \int[] getNewCommentsList()
	 * @method \int[] fillNewComments()
	 * @method \int[] getMyExpiredList()
	 * @method \int[] fillMyExpired()
	 * @method \int[] getMyExpiredSoonList()
	 * @method \int[] fillMyExpiredSoon()
	 * @method \int[] getMyNotViewedList()
	 * @method \int[] fillMyNotViewed()
	 * @method \int[] getMyWithoutDeadlineList()
	 * @method \int[] fillMyWithoutDeadline()
	 * @method \int[] getMyNewCommentsList()
	 * @method \int[] fillMyNewComments()
	 * @method \int[] getOriginatorWithoutDeadlineList()
	 * @method \int[] fillOriginatorWithoutDeadline()
	 * @method \int[] getOriginatorExpiredList()
	 * @method \int[] fillOriginatorExpired()
	 * @method \int[] getOriginatorWaitCtrlList()
	 * @method \int[] fillOriginatorWaitCtrl()
	 * @method \int[] getOriginatorNewCommentsList()
	 * @method \int[] fillOriginatorNewComments()
	 * @method \int[] getAuditorExpiredList()
	 * @method \int[] fillAuditorExpired()
	 * @method \int[] getAuditorNewCommentsList()
	 * @method \int[] fillAuditorNewComments()
	 * @method \int[] getAccomplicesExpiredList()
	 * @method \int[] fillAccomplicesExpired()
	 * @method \int[] getAccomplicesExpiredSoonList()
	 * @method \int[] fillAccomplicesExpiredSoon()
	 * @method \int[] getAccomplicesNotViewedList()
	 * @method \int[] fillAccomplicesNotViewed()
	 * @method \int[] getAccomplicesNewCommentsList()
	 * @method \int[] fillAccomplicesNewComments()
	 * @method \Bitrix\Main\EO_User[] getUserList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection getUserCollection()
	 * @method \Bitrix\Main\EO_User_Collection fillUser()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup[] getGroupList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection getGroupCollection()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup_Collection fillGroup()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Counter_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Counter\CounterTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Counter\CounterTable';
	}
}
namespace Bitrix\Tasks\Internals\Counter {
	/**
	 * @method static EO_Counter_Query query()
	 * @method static EO_Counter_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Counter_Result getById($id)
	 * @method static EO_Counter_Result getList(array $parameters = array())
	 * @method static EO_Counter_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Counter createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Counter wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection wakeUpCollection($rows)
	 */
	class CounterTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Counter_Result exec()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter fetchObject()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Counter_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Counter\EffectiveTable:tasks/lib/internals/counter/effective.php:af6c2431b5378b393e2dff74247605eb */
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
	 * @method \Bitrix\Socialnetwork\EO_Workgroup getGroup()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup remindActualGroup()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup requireGroup()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective setGroup(\Bitrix\Socialnetwork\EO_Workgroup $object)
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective resetGroup()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective unsetGroup()
	 * @method bool hasGroup()
	 * @method bool isGroupFilled()
	 * @method bool isGroupChanged()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup fillGroup()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \string[] getUserTypeList()
	 * @method \string[] fillUserType()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
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
	 * @method \Bitrix\Socialnetwork\EO_Workgroup[] getGroupList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection getGroupCollection()
	 * @method \Bitrix\Socialnetwork\EO_Workgroup_Collection fillGroup()
	 * @method \Bitrix\Tasks\Internals\TaskObject[] getTaskList()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection getTaskCollection()
	 * @method \Bitrix\Tasks\Integration\Report\Internals\EO_Task_Collection fillTask()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Effective_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Counter\EffectiveTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Counter\EffectiveTable';
	}
}
namespace Bitrix\Tasks\Internals\Counter {
	/**
	 * @method static EO_Effective_Query query()
	 * @method static EO_Effective_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Effective_Result getById($id)
	 * @method static EO_Effective_Result getList(array $parameters = array())
	 * @method static EO_Effective_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Effective createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Effective wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection wakeUpCollection($rows)
	 */
	class EffectiveTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Effective_Result exec()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective fetchObject()
	 * @method \Bitrix\Tasks\Internals\Counter\EO_Effective_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\SystemLogTable:tasks/lib/internals/systemlog.php:48f331669f30d65d698e1d8e775a8db8 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_SystemLog_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\SystemLogTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\SystemLogTable';
	}
}
namespace Bitrix\Tasks\Internals {
	/**
	 * @method static EO_SystemLog_Query query()
	 * @method static EO_SystemLog_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_SystemLog_Result getById($id)
	 * @method static EO_SystemLog_Result getList(array $parameters = array())
	 * @method static EO_SystemLog_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\EO_SystemLog createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\EO_SystemLog_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\EO_SystemLog wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\EO_SystemLog_Collection wakeUpCollection($rows)
	 */
	class SystemLogTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SystemLog_Result exec()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog fetchObject()
	 * @method \Bitrix\Tasks\Internals\EO_SystemLog_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\CheckList\MemberTable:tasks/lib/internals/task/checklist/member.php:76685124a0564aa511b78cf6fd179698 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Member_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\CheckList\MemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\CheckList\MemberTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\CheckList {
	/**
	 * @method static EO_Member_Query query()
	 * @method static EO_Member_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Member_Result getById($id)
	 * @method static EO_Member_Result getList(array $parameters = array())
	 * @method static EO_Member_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\CheckList\EO_Member createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\CheckList\EO_Member wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection wakeUpCollection($rows)
	 */
	class MemberTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Member_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\CheckListTable:tasks/lib/internals/task/checklist.php:156b64fcdd8f77e7c554b74c91573978 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_CheckList_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\CheckListTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\CheckListTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_CheckList_Query query()
	 * @method static EO_CheckList_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_CheckList_Result getById($id)
	 * @method static EO_CheckList_Result getList(array $parameters = array())
	 * @method static EO_CheckList_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckList createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckList wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection wakeUpCollection($rows)
	 */
	class CheckListTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CheckList_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\CheckListTreeTable:tasks/lib/internals/task/checklisttree.php:641387fea8fbcf5ef0e6b0694c566dbc */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_CheckListTree_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\CheckListTreeTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\CheckListTreeTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_CheckListTree_Query query()
	 * @method static EO_CheckListTree_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_CheckListTree_Result getById($id)
	 * @method static EO_CheckListTree_Result getList(array $parameters = array())
	 * @method static EO_CheckListTree_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckListTree createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckListTree wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection wakeUpCollection($rows)
	 */
	class CheckListTreeTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CheckListTree_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\DependenceTable:tasks/lib/internals/task/dependence.php:c17bb76de017110e47342fc842c284f5 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Dependence_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\DependenceTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\DependenceTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Dependence_Query query()
	 * @method static EO_Dependence_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Dependence_Result getById($id)
	 * @method static EO_Dependence_Result getList(array $parameters = array())
	 * @method static EO_Dependence_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Dependence createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Dependence wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection wakeUpCollection($rows)
	 */
	class DependenceTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Dependence_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\LogTable:tasks/lib/internals/task/log.php:acab4bc4f8042975f4a9c8157f9d1f96 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTask()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Log_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\LogTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\LogTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Log_Query query()
	 * @method static EO_Log_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Log_Result getById($id)
	 * @method static EO_Log_Result getList(array $parameters = array())
	 * @method static EO_Log_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Log createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Log_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Log wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Log_Collection wakeUpCollection($rows)
	 */
	class LogTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Log_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Log_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ParameterTable:tasks/lib/internals/task/parameter.php:816d938069019282ccde35c8503889a9 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Parameter_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ParameterTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ParameterTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Parameter_Query query()
	 * @method static EO_Parameter_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Parameter_Result getById($id)
	 * @method static EO_Parameter_Result getList(array $parameters = array())
	 * @method static EO_Parameter_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Parameter createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Parameter wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection wakeUpCollection($rows)
	 */
	class ParameterTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Parameter_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\RelatedTable:tasks/lib/internals/task/related.php:6e9df6b3c8a6007267d2ea8120a111e0 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Related_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\RelatedTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\RelatedTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Related_Query query()
	 * @method static EO_Related_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Related_Result getById($id)
	 * @method static EO_Related_Result getList(array $parameters = array())
	 * @method static EO_Related_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Related createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Related_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Related wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Related_Collection wakeUpCollection($rows)
	 */
	class RelatedTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Related_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Related_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ReminderTable:tasks/lib/internals/task/reminder.php:30234aa3a5f730dd4fcfce95a6346888 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTask()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Reminder_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ReminderTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ReminderTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Reminder_Query query()
	 * @method static EO_Reminder_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Reminder_Result getById($id)
	 * @method static EO_Reminder_Result getList(array $parameters = array())
	 * @method static EO_Reminder_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Reminder createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Reminder wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection wakeUpCollection($rows)
	 */
	class ReminderTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Reminder_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\SearchIndexTable:tasks/lib/internals/task/searchindex.php:3970c31ed24c42bb9f8ba78a7a263191 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_SearchIndex_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\SearchIndexTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\SearchIndexTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_SearchIndex_Query query()
	 * @method static EO_SearchIndex_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_SearchIndex_Result getById($id)
	 * @method static EO_SearchIndex_Result getList(array $parameters = array())
	 * @method static EO_SearchIndex_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection wakeUpCollection($rows)
	 */
	class SearchIndexTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_SearchIndex_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_SearchIndex_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\AccessTable:tasks/lib/internals/task/template/access.php:60f190fdba000b196f4c9ef795a12338 */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Access_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\AccessTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\AccessTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * @method static EO_Access_Query query()
	 * @method static EO_Access_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Access_Result getById($id)
	 * @method static EO_Access_Result getList(array $parameters = array())
	 * @method static EO_Access_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection wakeUpCollection($rows)
	 */
	class AccessTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Access_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\CheckList\MemberTable:tasks/lib/internals/task/template/checklist/member.php:6a1cda45d168fad279cae8a6fd36582e */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Member_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\CheckList\MemberTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\CheckList\MemberTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template\CheckList {
	/**
	 * @method static EO_Member_Query query()
	 * @method static EO_Member_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Member_Result getById($id)
	 * @method static EO_Member_Result getList(array $parameters = array())
	 * @method static EO_Member_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection wakeUpCollection($rows)
	 */
	class MemberTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Member_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\CheckList\EO_Member_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\Template\CheckListTreeTable:tasks/lib/internals/task/template/checklisttree.php:eec382a2442a4fa0e928c1dbe041aa1f */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_CheckListTree_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\Template\CheckListTreeTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\Template\CheckListTreeTable';
	}
}
namespace Bitrix\Tasks\Internals\Task\Template {
	/**
	 * @method static EO_CheckListTree_Query query()
	 * @method static EO_CheckListTree_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_CheckListTree_Result getById($id)
	 * @method static EO_CheckListTree_Result getList(array $parameters = array())
	 * @method static EO_CheckListTree_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree_Collection wakeUpCollection($rows)
	 */
	class CheckListTreeTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CheckListTree_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\Template\EO_CheckListTree_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\TimerTable:tasks/lib/internals/task/timer.php:85366fa7713fa0f1589e4dbe142899fc */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Timer_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\TimerTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\TimerTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Timer_Query query()
	 * @method static EO_Timer_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Timer_Result getById($id)
	 * @method static EO_Timer_Result getList(array $parameters = array())
	 * @method static EO_Timer_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer_Collection wakeUpCollection($rows)
	 */
	class TimerTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Timer_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Timer_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\UserOptionTable:tasks/lib/internals/task/useroption.php:70647d902d99562582889cfebe391ccd */
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_UserOption_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\UserOptionTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\UserOptionTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_UserOption_Query query()
	 * @method static EO_UserOption_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_UserOption_Result getById($id)
	 * @method static EO_UserOption_Result getList(array $parameters = array())
	 * @method static EO_UserOption_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_UserOption createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_UserOption wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection wakeUpCollection($rows)
	 */
	class UserOptionTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_UserOption_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_UserOption_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Internals\Task\ViewedTable:tasks/lib/internals/task/viewed.php:e197ca339cf3d36e937d2a1de51fe6d5 */
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * EO_Viewed
	 * @see \Bitrix\Tasks\Internals\Task\ViewedTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getTaskId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed setTaskId(\int|\Bitrix\Main\DB\SqlExpression $taskId)
	 * @method bool hasTaskId()
	 * @method bool isTaskIdFilled()
	 * @method bool isTaskIdChanged()
	 * @method \int getUserId()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getViewedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed setViewedDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $viewedDate)
	 * @method bool hasViewedDate()
	 * @method bool isViewedDateFilled()
	 * @method bool isViewedDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualViewedDate()
	 * @method \Bitrix\Main\Type\DateTime requireViewedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed resetViewedDate()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed unsetViewedDate()
	 * @method \Bitrix\Main\Type\DateTime fillViewedDate()
	 * @method \Bitrix\Main\EO_User getUser()
	 * @method \Bitrix\Main\EO_User remindActualUser()
	 * @method \Bitrix\Main\EO_User requireUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed setUser(\Bitrix\Main\EO_User $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed resetUser()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed unsetUser()
	 * @method bool hasUser()
	 * @method bool isUserFilled()
	 * @method bool isUserChanged()
	 * @method \Bitrix\Main\EO_User fillUser()
	 * @method \Bitrix\Tasks\Internals\TaskObject getTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject remindActualTask()
	 * @method \Bitrix\Tasks\Internals\TaskObject requireTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed setTask(\Bitrix\Tasks\Internals\TaskObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed resetTask()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed unsetTask()
	 * @method bool hasTask()
	 * @method bool isTaskFilled()
	 * @method bool isTaskChanged()
	 * @method \Bitrix\Tasks\Internals\TaskObject fillTask()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject getMembers()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject remindActualMembers()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject requireMembers()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed setMembers(\Bitrix\Tasks\Internals\Task\MemberObject $object)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed resetMembers()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed unsetMembers()
	 * @method bool hasMembers()
	 * @method bool isMembersFilled()
	 * @method bool isMembersChanged()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject fillMembers()
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
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed set($fieldName, $value)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed reset($fieldName)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Viewed wakeUp($data)
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
	 * @method \Bitrix\Tasks\Internals\EO_Task_Collection fillTask()
	 * @method \Bitrix\Tasks\Internals\Task\MemberObject[] getMembersList()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection getMembersCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Member_Collection fillMembers()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Internals\Task\EO_Viewed $object)
	 * @method bool has(\Bitrix\Tasks\Internals\Task\EO_Viewed $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed getByPrimary($primary)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Internals\Task\EO_Viewed $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Viewed_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Internals\Task\ViewedTable */
		static public $dataClass = '\Bitrix\Tasks\Internals\Task\ViewedTable';
	}
}
namespace Bitrix\Tasks\Internals\Task {
	/**
	 * @method static EO_Viewed_Query query()
	 * @method static EO_Viewed_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Viewed_Result getById($id)
	 * @method static EO_Viewed_Result getList(array $parameters = array())
	 * @method static EO_Viewed_Entity getEntity()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Viewed createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection createCollection()
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Viewed wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection wakeUpCollection($rows)
	 */
	class ViewedTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Viewed_Result exec()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Viewed_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed fetchObject()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection fetchCollection()
	 */
	class EO_Viewed_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection createCollection()
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed wakeUpObject($row)
	 * @method \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection wakeUpCollection($rows)
	 */
	class EO_Viewed_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Kanban\SprintTable:tasks/lib/kanban/sprint.php:1e223ee222f98ccdf8169665c87fa366 */
namespace Bitrix\Tasks\Kanban {
	/**
	 * EO_Sprint
	 * @see \Bitrix\Tasks\Kanban\SprintTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getGroupId()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint setGroupId(\int|\Bitrix\Main\DB\SqlExpression $groupId)
	 * @method bool hasGroupId()
	 * @method bool isGroupIdFilled()
	 * @method bool isGroupIdChanged()
	 * @method \int remindActualGroupId()
	 * @method \int requireGroupId()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint resetGroupId()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint unsetGroupId()
	 * @method \int fillGroupId()
	 * @method \int getCreatedById()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint setCreatedById(\int|\Bitrix\Main\DB\SqlExpression $createdById)
	 * @method bool hasCreatedById()
	 * @method bool isCreatedByIdFilled()
	 * @method bool isCreatedByIdChanged()
	 * @method \int remindActualCreatedById()
	 * @method \int requireCreatedById()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint resetCreatedById()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint unsetCreatedById()
	 * @method \int fillCreatedById()
	 * @method \int getModifiedById()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint setModifiedById(\int|\Bitrix\Main\DB\SqlExpression $modifiedById)
	 * @method bool hasModifiedById()
	 * @method bool isModifiedByIdFilled()
	 * @method bool isModifiedByIdChanged()
	 * @method \int remindActualModifiedById()
	 * @method \int requireModifiedById()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint resetModifiedById()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint unsetModifiedById()
	 * @method \int fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime getStartTime()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint setStartTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $startTime)
	 * @method bool hasStartTime()
	 * @method bool isStartTimeFilled()
	 * @method bool isStartTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualStartTime()
	 * @method \Bitrix\Main\Type\DateTime requireStartTime()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint resetStartTime()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint unsetStartTime()
	 * @method \Bitrix\Main\Type\DateTime fillStartTime()
	 * @method \Bitrix\Main\Type\DateTime getFinishTime()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint setFinishTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $finishTime)
	 * @method bool hasFinishTime()
	 * @method bool isFinishTimeFilled()
	 * @method bool isFinishTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualFinishTime()
	 * @method \Bitrix\Main\Type\DateTime requireFinishTime()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint resetFinishTime()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint unsetFinishTime()
	 * @method \Bitrix\Main\Type\DateTime fillFinishTime()
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
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint set($fieldName, $value)
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint reset($fieldName)
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Kanban\EO_Sprint wakeUp($data)
	 */
	class EO_Sprint {
		/* @var \Bitrix\Tasks\Kanban\SprintTable */
		static public $dataClass = '\Bitrix\Tasks\Kanban\SprintTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks\Kanban {
	/**
	 * EO_Sprint_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getGroupIdList()
	 * @method \int[] fillGroupId()
	 * @method \int[] getCreatedByIdList()
	 * @method \int[] fillCreatedById()
	 * @method \int[] getModifiedByIdList()
	 * @method \int[] fillModifiedById()
	 * @method \Bitrix\Main\Type\DateTime[] getStartTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillStartTime()
	 * @method \Bitrix\Main\Type\DateTime[] getFinishTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillFinishTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Tasks\Kanban\EO_Sprint $object)
	 * @method bool has(\Bitrix\Tasks\Kanban\EO_Sprint $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint getByPrimary($primary)
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Kanban\EO_Sprint $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Kanban\EO_Sprint_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Sprint_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Kanban\SprintTable */
		static public $dataClass = '\Bitrix\Tasks\Kanban\SprintTable';
	}
}
namespace Bitrix\Tasks\Kanban {
	/**
	 * @method static EO_Sprint_Query query()
	 * @method static EO_Sprint_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Sprint_Result getById($id)
	 * @method static EO_Sprint_Result getList(array $parameters = array())
	 * @method static EO_Sprint_Entity getEntity()
	 * @method static \Bitrix\Tasks\Kanban\EO_Sprint createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Kanban\EO_Sprint_Collection createCollection()
	 * @method static \Bitrix\Tasks\Kanban\EO_Sprint wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Kanban\EO_Sprint_Collection wakeUpCollection($rows)
	 */
	class SprintTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Sprint_Result exec()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint fetchObject()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Sprint_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint fetchObject()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint_Collection fetchCollection()
	 */
	class EO_Sprint_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint_Collection createCollection()
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint wakeUpObject($row)
	 * @method \Bitrix\Tasks\Kanban\EO_Sprint_Collection wakeUpCollection($rows)
	 */
	class EO_Sprint_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Kanban\StagesTable:tasks/lib/kanban/stages.php:4157a150cd7b052daefe22cbe75e8553 */
namespace Bitrix\Tasks\Kanban {
	/**
	 * EO_Stages
	 * @see \Bitrix\Tasks\Kanban\StagesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages resetTitle()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages unsetTitle()
	 * @method \string fillTitle()
	 * @method \int getSort()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages resetSort()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages unsetSort()
	 * @method \int fillSort()
	 * @method \string getColor()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages setColor(\string|\Bitrix\Main\DB\SqlExpression $color)
	 * @method bool hasColor()
	 * @method bool isColorFilled()
	 * @method bool isColorChanged()
	 * @method \string remindActualColor()
	 * @method \string requireColor()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages resetColor()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages unsetColor()
	 * @method \string fillColor()
	 * @method \string getSystemType()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages setSystemType(\string|\Bitrix\Main\DB\SqlExpression $systemType)
	 * @method bool hasSystemType()
	 * @method bool isSystemTypeFilled()
	 * @method bool isSystemTypeChanged()
	 * @method \string remindActualSystemType()
	 * @method \string requireSystemType()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages resetSystemType()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages unsetSystemType()
	 * @method \string fillSystemType()
	 * @method \int getEntityId()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages resetEntityId()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \string getEntityType()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages resetEntityType()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages unsetEntityType()
	 * @method \string fillEntityType()
	 * @method array getAdditionalFilter()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages setAdditionalFilter(array|\Bitrix\Main\DB\SqlExpression $additionalFilter)
	 * @method bool hasAdditionalFilter()
	 * @method bool isAdditionalFilterFilled()
	 * @method bool isAdditionalFilterChanged()
	 * @method array remindActualAdditionalFilter()
	 * @method array requireAdditionalFilter()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages resetAdditionalFilter()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages unsetAdditionalFilter()
	 * @method array fillAdditionalFilter()
	 * @method array getToUpdate()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages setToUpdate(array|\Bitrix\Main\DB\SqlExpression $toUpdate)
	 * @method bool hasToUpdate()
	 * @method bool isToUpdateFilled()
	 * @method bool isToUpdateChanged()
	 * @method array remindActualToUpdate()
	 * @method array requireToUpdate()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages resetToUpdate()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages unsetToUpdate()
	 * @method array fillToUpdate()
	 * @method \string getToUpdateAccess()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages setToUpdateAccess(\string|\Bitrix\Main\DB\SqlExpression $toUpdateAccess)
	 * @method bool hasToUpdateAccess()
	 * @method bool isToUpdateAccessFilled()
	 * @method bool isToUpdateAccessChanged()
	 * @method \string remindActualToUpdateAccess()
	 * @method \string requireToUpdateAccess()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages resetToUpdateAccess()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages unsetToUpdateAccess()
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
	 * @method \Bitrix\Tasks\Kanban\EO_Stages set($fieldName, $value)
	 * @method \Bitrix\Tasks\Kanban\EO_Stages reset($fieldName)
	 * @method \Bitrix\Tasks\Kanban\EO_Stages unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\Kanban\EO_Stages wakeUp($data)
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
	 * EO_Stages_Collection
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
	 * @method void add(\Bitrix\Tasks\Kanban\EO_Stages $object)
	 * @method bool has(\Bitrix\Tasks\Kanban\EO_Stages $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\Kanban\EO_Stages getByPrimary($primary)
	 * @method \Bitrix\Tasks\Kanban\EO_Stages[] getAll()
	 * @method bool remove(\Bitrix\Tasks\Kanban\EO_Stages $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\Kanban\EO_Stages_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\Kanban\EO_Stages current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Stages_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Kanban\StagesTable */
		static public $dataClass = '\Bitrix\Tasks\Kanban\StagesTable';
	}
}
namespace Bitrix\Tasks\Kanban {
	/**
	 * @method static EO_Stages_Query query()
	 * @method static EO_Stages_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Stages_Result getById($id)
	 * @method static EO_Stages_Result getList(array $parameters = array())
	 * @method static EO_Stages_Entity getEntity()
	 * @method static \Bitrix\Tasks\Kanban\EO_Stages createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Kanban\EO_Stages_Collection createCollection()
	 * @method static \Bitrix\Tasks\Kanban\EO_Stages wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Kanban\EO_Stages_Collection wakeUpCollection($rows)
	 */
	class StagesTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Stages_Result exec()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages fetchObject()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Stages_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\Kanban\EO_Stages fetchObject()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages_Collection fetchCollection()
	 */
	class EO_Stages_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\Kanban\EO_Stages createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\Kanban\EO_Stages_Collection createCollection()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages wakeUpObject($row)
	 * @method \Bitrix\Tasks\Kanban\EO_Stages_Collection wakeUpCollection($rows)
	 */
	class EO_Stages_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Kanban\TaskStageTable:tasks/lib/kanban/taskstage.php:db938e346d18a558c5ba85b145c840ed */
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
	 * @method \Bitrix\Tasks\Kanban\EO_Stages getStage()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages remindActualStage()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages requireStage()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage setStage(\Bitrix\Tasks\Kanban\EO_Stages $object)
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage resetStage()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage unsetStage()
	 * @method bool hasStage()
	 * @method bool isStageFilled()
	 * @method bool isStageChanged()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages fillStage()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \Bitrix\Tasks\Kanban\EO_Stages[] getStageList()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage_Collection getStageCollection()
	 * @method \Bitrix\Tasks\Kanban\EO_Stages_Collection fillStage()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_TaskStage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Kanban\TaskStageTable */
		static public $dataClass = '\Bitrix\Tasks\Kanban\TaskStageTable';
	}
}
namespace Bitrix\Tasks\Kanban {
	/**
	 * @method static EO_TaskStage_Query query()
	 * @method static EO_TaskStage_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_TaskStage_Result getById($id)
	 * @method static EO_TaskStage_Result getList(array $parameters = array())
	 * @method static EO_TaskStage_Entity getEntity()
	 * @method static \Bitrix\Tasks\Kanban\EO_TaskStage createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Kanban\EO_TaskStage_Collection createCollection()
	 * @method static \Bitrix\Tasks\Kanban\EO_TaskStage wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Kanban\EO_TaskStage_Collection wakeUpCollection($rows)
	 */
	class TaskStageTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_TaskStage_Result exec()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage fetchObject()
	 * @method \Bitrix\Tasks\Kanban\EO_TaskStage_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\ProjectsTable:tasks/lib/projects.php:a3c6b1d09a057faf48627932e027a5d9 */
namespace Bitrix\Tasks {
	/**
	 * EO_Projects
	 * @see \Bitrix\Tasks\ProjectsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Tasks\EO_Projects setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getOrderNewTask()
	 * @method \Bitrix\Tasks\EO_Projects setOrderNewTask(\string|\Bitrix\Main\DB\SqlExpression $orderNewTask)
	 * @method bool hasOrderNewTask()
	 * @method bool isOrderNewTaskFilled()
	 * @method bool isOrderNewTaskChanged()
	 * @method \string remindActualOrderNewTask()
	 * @method \string requireOrderNewTask()
	 * @method \Bitrix\Tasks\EO_Projects resetOrderNewTask()
	 * @method \Bitrix\Tasks\EO_Projects unsetOrderNewTask()
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
	 * @method \Bitrix\Tasks\EO_Projects set($fieldName, $value)
	 * @method \Bitrix\Tasks\EO_Projects reset($fieldName)
	 * @method \Bitrix\Tasks\EO_Projects unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Tasks\EO_Projects wakeUp($data)
	 */
	class EO_Projects {
		/* @var \Bitrix\Tasks\ProjectsTable */
		static public $dataClass = '\Bitrix\Tasks\ProjectsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Tasks {
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
	 * @method void add(\Bitrix\Tasks\EO_Projects $object)
	 * @method bool has(\Bitrix\Tasks\EO_Projects $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Tasks\EO_Projects getByPrimary($primary)
	 * @method \Bitrix\Tasks\EO_Projects[] getAll()
	 * @method bool remove(\Bitrix\Tasks\EO_Projects $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Tasks\EO_Projects_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Tasks\EO_Projects current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Projects_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\ProjectsTable */
		static public $dataClass = '\Bitrix\Tasks\ProjectsTable';
	}
}
namespace Bitrix\Tasks {
	/**
	 * @method static EO_Projects_Query query()
	 * @method static EO_Projects_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Projects_Result getById($id)
	 * @method static EO_Projects_Result getList(array $parameters = array())
	 * @method static EO_Projects_Entity getEntity()
	 * @method static \Bitrix\Tasks\EO_Projects createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\EO_Projects_Collection createCollection()
	 * @method static \Bitrix\Tasks\EO_Projects wakeUpObject($row)
	 * @method static \Bitrix\Tasks\EO_Projects_Collection wakeUpCollection($rows)
	 */
	class ProjectsTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Projects_Result exec()
	 * @method \Bitrix\Tasks\EO_Projects fetchObject()
	 * @method \Bitrix\Tasks\EO_Projects_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Projects_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Tasks\EO_Projects fetchObject()
	 * @method \Bitrix\Tasks\EO_Projects_Collection fetchCollection()
	 */
	class EO_Projects_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Tasks\EO_Projects createObject($setDefaultValues = true)
	 * @method \Bitrix\Tasks\EO_Projects_Collection createCollection()
	 * @method \Bitrix\Tasks\EO_Projects wakeUpObject($row)
	 * @method \Bitrix\Tasks\EO_Projects_Collection wakeUpCollection($rows)
	 */
	class EO_Projects_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\EntityTable:tasks/lib/scrum/internal/entitytable.php:3e281946927ba202a2881989311a72a9 */
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
	 * @method array getInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity setInfo(array|\Bitrix\Main\DB\SqlExpression $info)
	 * @method bool hasInfo()
	 * @method bool isInfoFilled()
	 * @method bool isInfoChanged()
	 * @method array remindActualInfo()
	 * @method array requireInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity resetInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity unsetInfo()
	 * @method array fillInfo()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method array[] getInfoList()
	 * @method array[] fillInfo()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Entity_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\EntityTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\EntityTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * @method static EO_Entity_Query query()
	 * @method static EO_Entity_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Entity_Result getById($id)
	 * @method static EO_Entity_Result getList(array $parameters = array())
	 * @method static EO_Entity_Entity getEntity()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection createCollection()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection wakeUpCollection($rows)
	 */
	class EntityTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Entity_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Entity_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
/* ORMENTITYANNOTATION:Bitrix\Tasks\Scrum\Internal\ItemTable:tasks/lib/scrum/internal/itemtable.php:f77f2ee6704496a72b41eb06a2d1ea6d */
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
	 * @method \string getItemType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setItemType(\string|\Bitrix\Main\DB\SqlExpression $itemType)
	 * @method bool hasItemType()
	 * @method bool isItemTypeFilled()
	 * @method bool isItemTypeChanged()
	 * @method \string remindActualItemType()
	 * @method \string requireItemType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetItemType()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetItemType()
	 * @method \string fillItemType()
	 * @method \int getParentId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetParentId()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetParentId()
	 * @method \int fillParentId()
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
	 * @method array getInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item setInfo(array|\Bitrix\Main\DB\SqlExpression $info)
	 * @method bool hasInfo()
	 * @method bool isInfoFilled()
	 * @method bool isInfoChanged()
	 * @method array remindActualInfo()
	 * @method array requireInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item resetInfo()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item unsetInfo()
	 * @method array fillInfo()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 * @method \string[] getActiveList()
	 * @method \string[] fillActive()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \string[] getItemTypeList()
	 * @method \string[] fillItemType()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
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
	 * @method array[] getInfoList()
	 * @method array[] fillInfo()
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
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
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
	 */
	class EO_Item_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Tasks\Scrum\Internal\ItemTable */
		static public $dataClass = '\Bitrix\Tasks\Scrum\Internal\ItemTable';
	}
}
namespace Bitrix\Tasks\Scrum\Internal {
	/**
	 * @method static EO_Item_Query query()
	 * @method static EO_Item_Result getByPrimary($primary, array $parameters = array())
	 * @method static EO_Item_Result getById($id)
	 * @method static EO_Item_Result getList(array $parameters = array())
	 * @method static EO_Item_Entity getEntity()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item createObject($setDefaultValues = true)
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection createCollection()
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item wakeUpObject($row)
	 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection wakeUpCollection($rows)
	 */
	class ItemTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Item_Result exec()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item fetchObject()
	 * @method \Bitrix\Tasks\Scrum\Internal\EO_Item_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
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
<?php
namespace Bitrix\Crm\Controller\Tracking\Ad;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Intranet;
use Bitrix\Bitrix24\Feature;
use Bitrix\Crm\Tracking;

/**
 * Class Report
 * @package Bitrix\Crm\Controller\Tracking\Ad
 */
class Report extends Main\Engine\JsonController
{
	/**
	 * Configure actions.
	 *
	 * @return array
	 */
	public function configureActions()
	{
		return [
			'getGrid' => [
				'-prefilters' => [
					Main\Engine\ActionFilter\ContentType::class,
				],
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
			'build' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
			'list' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
			'changeStatus' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
		];
	}

	/**
	 * Build action.
	 *
	 * @param Tracking\Ad\ReportBuilder $builder Builder.
	 * @return array
	 */
	public function buildAction(Tracking\Ad\ReportBuilder $builder)
	{
		if (Loader::includeModule('bitrix24') && !Feature::isFeatureEnabled('crm_tracking_reports'))
		{
			return [
				'complete' => true,
				'label' => '',
			];
		}

		$builder->run();
		$this->addErrors($builder->getErrorCollection()->toArray());

		return [
			'complete' => $builder->isComplete(),
			'label' => $builder->getCompleteLabel(),
		];
	}

	/**
	 * List action.
	 *
	 * @param Tracking\Ad\ReportBuilder $builder Builder.
	 * @param array $options
	 * @return array
	 */
	public function listAction(Tracking\Ad\ReportBuilder $builder, array $options = [])
	{
		return [
			'rows' => $builder->getRows($options['level'] ?? 0, $options['parentId'] ?? null)
		];
	}

	/**
	 * Get grid action.
	 *
	 * @param int $sourceId Source ID.
	 * @param Main\Type\Date $from Date from.
	 * @param Main\Type\Date $to Date from.
	 * @param int $parentId Parent ID.
	 * @param int $level Level.
	 * @param string $gridId Grid ID.
	 * @return Main\Engine\Response\Component|Main\HttpResponse
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 */
	public function getGridAction($sourceId, $from, $to, $parentId, $level, $gridId)
	{
		$isGridRequest = !$gridId;
		$component = new Main\Engine\Response\Component(
			'bitrix:crm.tracking.report.source',
			'',
			[
				'GRID_ID' => $gridId,
				'SOURCE_ID' => $sourceId,
				'LEVEL' => $level,
				'PARENT_ID' => $parentId,
				'PERIOD_FROM' => $from,
				'PERIOD_TO' => $to,
			]
		);

		if ($isGridRequest)
		{
			$response = new Main\HttpResponse();
			$content = Main\Web\Json::decode($component->getContent());
			$response->setContent($content['data']['html']);
			return $response;
		}

		return $component;
	}

	public function changeStatusAction($id, $status)
	{
		$result = Tracking\Source\Level\Status::change($id, $status);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	/**
	 * Get primary auto-wired parameter.
	 *
	 * @return Main\Engine\Autowire\ExactParameter|Main\Engine\AutoWire\Parameter|null
	 * @throws Main\Engine\AutoWire\BinderArgumentException
	 */
	public function getPrimaryAutoWiredParameter()
	{
		return new Main\Engine\Autowire\ExactParameter(
			Tracking\Ad\ReportBuilder::class,
			'builder',
			function($className, $sourceId, $from, $to)
			{
				$yesterday = new Main\Type\Date();
				$from = new Main\Type\Date($from);
				$to = new Main\Type\Date($to);
				
				if ($to->getTimestamp() > $yesterday->getTimestamp())
				{
					$to = (clone $yesterday);
				}
				if ($from->getTimestamp() > $to->getTimestamp())
				{
					$from = (clone $to);
				}

				/** @var Tracking\Ad\ReportBuilder $className */
				return (new $className())
					->setSourceId($sourceId)
					->setPeriod($from, $to);
			}
		);
	}
}

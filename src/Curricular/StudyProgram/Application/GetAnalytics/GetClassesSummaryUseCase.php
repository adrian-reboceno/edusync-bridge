<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\GetAnalytics;

use Curricular\StudyProgram\Domain\Ports\NeoClassAnalyticsRepositoryContract;

final readonly class GetClassesSummaryUseCase
{
    public function __construct(
        private NeoClassAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(): ClassesSummaryResult
    {
        return $this->repository->getSummary();
    }
}

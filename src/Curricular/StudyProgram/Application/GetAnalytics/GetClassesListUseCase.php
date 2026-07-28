<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\GetAnalytics;

use Curricular\StudyProgram\Domain\Ports\NeoClassAnalyticsRepositoryContract;

final readonly class GetClassesListUseCase
{
    public function __construct(
        private NeoClassAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(GetClassesListQuery $query): GetClassesListResult
    {
        $result = $this->repository->getClassesList(
            page: $query->page,
            perPage: $query->perPage,
        );

        return new GetClassesListResult(
            data: $result['data'],
            total: $result['total'],
            page: $query->page,
            perPage: $query->perPage,
        );
    }
}

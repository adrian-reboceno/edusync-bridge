<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\GetAnalytics;

final readonly class GetClassesListQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}

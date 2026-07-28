<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\GetAnalytics;

final readonly class ClassDetailResult
{
    public function __construct(
        public array $class,
        public array $summary,
        public array $teachers,
        public array $students,
    ) {}
}

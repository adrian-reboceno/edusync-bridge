<?php

declare(strict_types=1);

namespace Infrastructure\Providers;

use Academic\Student\Application\SyncNeoUsers\SyncNeoUsersUseCase;
use Academic\Student\Domain\Ports\NeoUserAnalyticsRepositoryContract;
use Academic\Student\Domain\Ports\NeoUserRepositoryContract;
use Academic\Student\Infrastructure\Persistence\Eloquent\EloquentNeoUserAnalyticsRepository;
use Academic\Student\Infrastructure\Persistence\Eloquent\EloquentNeoUserRepository;
use Curricular\CurriculumMap\Domain\Ports\NeoLessonAnalyticsRepositoryContract;
use Curricular\CurriculumMap\Domain\Ports\NeoLessonRepositoryContract;
use Curricular\CurriculumMap\Infrastructure\Persistence\Eloquent\EloquentNeoLessonAnalyticsRepository;
use Curricular\CurriculumMap\Infrastructure\Persistence\Eloquent\EloquentNeoLessonRepository;
use Curricular\StudyProgram\Domain\Ports\NeoClassAnalyticsRepositoryContract;
use Curricular\StudyProgram\Domain\Ports\NeoClassRepositoryContract;
use Curricular\StudyProgram\Infrastructure\Persistence\Eloquent\EloquentNeoClassAnalyticsRepository;
use Curricular\StudyProgram\Infrastructure\Persistence\Eloquent\EloquentNeoClassRepository;
use Enrollment\Enrollment\Domain\Ports\NeoClassTeacherRepositoryContract;
use Enrollment\Enrollment\Domain\Ports\NeoEnrollmentRepositoryContract;
use Enrollment\Enrollment\Infrastructure\Persistence\Eloquent\EloquentNeoClassTeacherRepository;
use Enrollment\Enrollment\Infrastructure\Persistence\Eloquent\EloquentNeoEnrollmentRepository;
use Grades\Grade\Domain\Ports\NeoAssignmentAnalyticsRepositoryContract;
use Grades\Grade\Domain\Ports\NeoAssignmentGradeAnalyticsRepositoryContract;
use Grades\Grade\Domain\Ports\NeoAssignmentGradeRepositoryContract;
use Grades\Grade\Domain\Ports\NeoAssignmentRepositoryContract;
use Grades\Grade\Domain\Ports\NeoAssignmentResultAnalyticsRepositoryContract;
use Grades\Grade\Domain\Ports\NeoAssignmentResultRepositoryContract;
use Grades\Grade\Infrastructure\Persistence\Eloquent\EloquentNeoAssignmentAnalyticsRepository;
use Grades\Grade\Infrastructure\Persistence\Eloquent\EloquentNeoAssignmentGradeAnalyticsRepository;
use Grades\Grade\Infrastructure\Persistence\Eloquent\EloquentNeoAssignmentGradeRepository;
use Grades\Grade\Infrastructure\Persistence\Eloquent\EloquentNeoAssignmentRepository;
use Grades\Grade\Infrastructure\Persistence\Eloquent\EloquentNeoAssignmentResultAnalyticsRepository;
use Grades\Grade\Infrastructure\Persistence\Eloquent\EloquentNeoAssignmentResultRepository;
use Illuminate\Support\ServiceProvider;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use NeoLms\NeoSync\Infrastructure\Http\NeoApiAdapter\NeoHttpAdapter;

final class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NeoLmsApiContract::class, function (): NeoHttpAdapter {
            return new NeoHttpAdapter(
                baseUrl: (string) config('integration.neo_lms.base_url'),
                apiKey: (string) config('integration.neo_lms.api_key'),
                timeout: (int) config('integration.neo_lms.timeout', 30),
                pageSize: (int) config('integration.neo_lms.page_size', 100),
                retryTimes: (int) config('integration.neo_lms.retry_times', 3),
                retrySleepMs: (int) config('integration.neo_lms.retry_sleep', 2000),
            );
        });

        $this->app->bind(NeoUserRepositoryContract::class, EloquentNeoUserRepository::class);
        $this->app->bind(SyncNeoUsersUseCase::class, SyncNeoUsersUseCase::class);
        $this->app->bind(NeoUserAnalyticsRepositoryContract::class, EloquentNeoUserAnalyticsRepository::class);

        $this->app->bind(NeoClassRepositoryContract::class, EloquentNeoClassRepository::class);
        $this->app->bind(NeoClassAnalyticsRepositoryContract::class, EloquentNeoClassAnalyticsRepository::class);
        $this->app->bind(NeoEnrollmentRepositoryContract::class, EloquentNeoEnrollmentRepository::class);
        $this->app->bind(NeoClassTeacherRepositoryContract::class, EloquentNeoClassTeacherRepository::class);

        $this->app->bind(NeoLessonRepositoryContract::class, EloquentNeoLessonRepository::class);
        $this->app->bind(NeoLessonAnalyticsRepositoryContract::class, EloquentNeoLessonAnalyticsRepository::class);
        $this->app->bind(NeoAssignmentRepositoryContract::class, EloquentNeoAssignmentRepository::class);
        $this->app->bind(NeoAssignmentAnalyticsRepositoryContract::class, EloquentNeoAssignmentAnalyticsRepository::class);
        $this->app->bind(NeoAssignmentGradeRepositoryContract::class, EloquentNeoAssignmentGradeRepository::class);
        $this->app->bind(NeoAssignmentGradeAnalyticsRepositoryContract::class, EloquentNeoAssignmentGradeAnalyticsRepository::class);
        $this->app->bind(NeoAssignmentResultRepositoryContract::class, EloquentNeoAssignmentResultRepository::class);
        $this->app->bind(NeoAssignmentResultAnalyticsRepositoryContract::class, EloquentNeoAssignmentResultAnalyticsRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            base_path('src/NeoLms/NeoSync/Infrastructure/Persistence/Migrations'),
        );
        $this->loadMigrationsFrom(
            base_path('src/Scheduler/JobScheduler/Infrastructure/Persistence/Migrations'),
        );
        $this->loadMigrationsFrom(
            base_path('src/Academic/Student/Infrastructure/Persistence/Migrations'),
        );
        $this->loadMigrationsFrom(
            base_path('src/Curricular/StudyProgram/Infrastructure/Persistence/Migrations'),
        );
        $this->loadMigrationsFrom(
            base_path('src/Curricular/CurriculumMap/Infrastructure/Persistence/Migrations'),
        );
        $this->loadMigrationsFrom(
            base_path('src/Enrollment/Enrollment/Infrastructure/Persistence/Migrations'),
        );
        $this->loadMigrationsFrom(
            base_path('src/Grades/Grade/Infrastructure/Persistence/Migrations'),
        );
    }
}

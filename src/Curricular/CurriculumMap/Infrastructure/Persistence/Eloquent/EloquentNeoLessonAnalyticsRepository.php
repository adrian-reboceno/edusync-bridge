<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Infrastructure\Persistence\Eloquent;

use Curricular\CurriculumMap\Domain\Ports\NeoLessonAnalyticsRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoLessonAnalyticsRepository implements NeoLessonAnalyticsRepositoryContract
{
    public function getClassLessons(int $classId): ?array
    {
        $class = DB::table('neo_classes')->where('neo_id', $classId)->first(['neo_id', 'name']);

        if ($class === null) {
            return null;
        }

        $lessonRows = DB::select(<<<'SQL'
            SELECT
                l.neo_id, l.name, l.position, l.start_at,
                l.begin_at, l.end_at, l.optional_for_completion,
                COUNT(s.neo_id) AS total_sections
            FROM neo_lessons l
            LEFT JOIN neo_sections s ON s.neo_lesson_id = l.neo_id
            WHERE l.neo_class_id = ?
            GROUP BY l.neo_id
            ORDER BY l.position
        SQL, [$classId]);

        $sectionRows = DB::select(<<<'SQL'
            SELECT neo_lesson_id, neo_id, name, type, position, optional_for_completion
            FROM neo_sections
            WHERE neo_class_id = ?
            ORDER BY neo_lesson_id, position
        SQL, [$classId]);

        $sectionsByLesson = [];
        foreach ($sectionRows as $row) {
            $sectionsByLesson[(int) $row->neo_lesson_id][] = [
                'neo_id' => (int) $row->neo_id,
                'name' => $row->name,
                'type' => $row->type,
                'position' => (int) $row->position,
                'optional_for_completion' => (bool) $row->optional_for_completion,
            ];
        }

        $lessons = array_map(static function (object $row) use ($sectionsByLesson): array {
            $sections = $sectionsByLesson[(int) $row->neo_id] ?? [];

            return [
                'neo_id' => (int) $row->neo_id,
                'name' => $row->name,
                'position' => (int) $row->position,
                'start_at' => $row->start_at,
                'end_at' => $row->end_at,
                'optional_for_completion' => (bool) $row->optional_for_completion,
                'total_sections' => count($sections),
                'sections' => $sections,
            ];
        }, $lessonRows);

        return [
            'class_id' => (int) $class->neo_id,
            'class_name' => $class->name,
            'total_lessons' => count($lessons),
            'total_sections' => count($sectionRows),
            'lessons' => $lessons,
        ];
    }
}

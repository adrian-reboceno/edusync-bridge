<?php

declare(strict_types=1);

namespace NeoLms\NeoSync\Domain\Ports;

interface NeoLmsApiContract
{
    // USUARIOS

    /** Lista todos con paginación automática. $filters = array para $filter JSON. $include = "organization,job_title" etc. */
    public function listUsers(array $filters = [], string $include = ''): array;

    public function getUser(int $neoId): array;

    /**
     * Lista sesiones de un usuario con paginación por cursor.
     * Usa $after para obtener solo sesiones nuevas desde la última conocida.
     *
     * @param int|null $after ID de la última sesión conocida (paginación incremental)
     * @return array<int, array{id:int, user_id:int, login_at:string, logout_at:string|null, ip_address:string|null}>
     */
    public function getUserSessions(int $neoUserId, ?int $after = null): array;

    /** Busca por sis_id (matrícula CE). Retorna null si no existe. */
    public function getUserBySisId(string $sisId): ?array;

      /** Busca por sis_id (matrícula CE). Retorna null si no existe. */
    public function getUserByUserId(string $userId): ?array;

    /** Crea usuario. Requerido: first_name, last_name, roles */
    public function createUser(array $data): array;

    public function updateUser(int $neoId, array $data): array;

    /** POST /users/batch. Retorna batch_id */
    public function createUsersBatch(array $users): int;

    /** PATCH /users/batch. Cada item: {id, attributes:{}}. Retorna batch_id */
    public function updateUsersBatch(array $users): int;

    // CLASS TEMPLATES

    public function listClassTemplates(array $filters = []): array;

    public function getClassTemplate(int $neoId): array;

    public function getClassTemplateBySisId(string $sisId): ?array;

    /** Requerido: name, style */
    public function createClassTemplate(array $data): array;

    public function updateClassTemplate(int $neoId, array $data): array;

    /** POST /class_templates/batch. Retorna batch_id */
    public function createClassTemplatesBatch(array $templates): int;

    /** PATCH /class_templates/batch. Cada item: {id, attributes:{}}. Retorna batch_id */
    public function updateClassTemplatesBatch(array $templates): int;

    public function getClassTemplateTeachers(int $classTemplateId): array;

    public function addClassTemplateTeacher(int $classTemplateId, int $userId): array;

    /** POST /class_templates/{id}/teachers/batch. Retorna batch_id */
    public function addClassTemplateTeachersBatch(int $classTemplateId, array $userIds): int;

    public function removeClassTemplateTeacher(int $classTemplateId, int $userId): void;

    // CLASSES

    public function listClasses(array $filters = []): array;

    public function getClass(int $neoId): array;

    public function getClassBySisId(string $sisId): ?array;

    /** Requerido: name, style. parent_id = class_template padre */
    public function createClass(array $data): array;

    public function updateClass(int $neoId, array $data): array;

    /** POST /classes/batch. Retorna batch_id */
    public function createClassesBatch(array $classes): int;

    /** PATCH /classes/batch. Cada item: {id, attributes:{}}. Retorna batch_id */
    public function updateClassesBatch(array $classes): int;

    // CLASS STUDENTS (inscripciones — no existe /enrollments en NEO)

    /** GET /class_students — TODOS del sistema. Usar $filters para acotar. */
    public function listAllClassStudents(array $filters = []): array;

    /** GET /classes/{id}/students */
    public function getClassStudents(int $classId, array $filters = []): array;

    /** GET /classes/{id}/students/{user_id} — incluye grade, percent, time_spent */
    public function getClassStudent(int $classId, int $userId): array;

    /** POST /classes/{id}/students. $options: send_notification, reenroll, increment_seats */
    public function enrollStudent(int $classId, int $userId, array $options = []): array;

    /** POST /classes/{id}/students/batch. Retorna batch_id */
    public function enrollStudentsBatch(int $classId, array $userIds, array $options = []): int;

    /** PATCH /classes/{id}/students/{user_id}. completed, unenrolled, override_percent... */
    public function updateClassStudent(int $classId, int $userId, array $data): array;

    /** PATCH /classes/{id}/students/batch. Cada item: {user_id, attributes:{}}. Retorna batch_id */
    public function updateClassStudentsBatch(int $classId, array $students): int;

    /** DELETE /classes/{id}/students/{user_id} */
    public function unenrollStudent(int $classId, int $userId): void;

    // CLASS TEACHERS

    public function getClassTeachers(int $classId): array;

    public function assignTeacher(int $classId, int $userId): array;

    /** POST /classes/{id}/teachers/batch. Retorna batch_id */
    public function assignTeachersBatch(int $classId, array $userIds): int;

    public function removeTeacher(int $classId, int $userId): void;

    // BATCHES

    /**
     * @return array{id:int, status:string, processed:int, successful:int, failed:int, results:array}
     */
    public function getBatch(int $batchId): array;

    /** Polling hasta status="Finished". Intervalo 2s. Lanza RuntimeException si timeout. */
    public function waitForBatch(int $batchId, int $timeoutSeconds = 120): array;

    public function listBatches(array $filters = []): array;

    // UTILIDADES

    /** GET /users?$limit=1. Retorna true si responde 200. */
    public function healthCheck(): bool;
}

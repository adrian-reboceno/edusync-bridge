<?php

declare(strict_types=1);

namespace NeoLms\NeoSync\Infrastructure\Http\NeoApiAdapter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use RuntimeException;
use Throwable;

final class NeoHttpAdapter implements NeoLmsApiContract
{
    private readonly Client $client;

    private readonly int $pageSize;

    public function __construct(
        string $baseUrl,
        string $apiKey,
        int $timeout = 30,
        int $pageSize = 100,
        private readonly int $retryTimes = 3,
        private readonly int $retrySleepMs = 2000,
    ) {
        $this->pageSize = min($pageSize, 100);
        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/').'/',
            'timeout' => $timeout,
            'headers' => [
                'X-Api-Key' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'curl' => [
                // 💡 ESTO RESUELVE EL TIMEOUT: Forza a cURL a resolver usando únicamente IPv4
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // USUARIOS
    // ─────────────────────────────────────────────────────────────

    public function listUsers(array $filters = [], string $include = ''): array
    {
        if ($include !== '') {
            $filters['$include'] = $include;
        }

        return $this->paginateAll('users', $filters);
    }

    public function getUser(int $neoId): array
    {
        return $this->get("users/{$neoId}");
    }

    public function getUserSessions(int $neoUserId, ?int $after = null): array
    {
        $params = ['$limit' => $this->pageSize];

        if ($after !== null) {
            $params['$after'] = $after;
        }

        $results = [];

        do {
            $page = $this->get("users/{$neoUserId}/sessions", $params);

            if ($page === []) {
                break;
            }

            array_push($results, ...$page);
            $params['$after'] = $page[array_key_last($page)]['id'] ?? null;
        } while (count($page) >= $this->pageSize && $params['$after'] !== null);

        return $results;
    }

    public function getUserByUserId(string $userId): ?array
    {
        return $this->findOneByUserId('users', $userId);
    }

    public function getUserBySisId(string $sisId): ?array
    {
        return $this->findOneBySisId('users', $sisId);
    }

    public function createUser(array $data): array
    {
        return $this->post('users', $data);
    }

    public function updateUser(int $neoId, array $data): array
    {
        return $this->patch("users/{$neoId}", $data);
    }

    public function createUsersBatch(array $users): int
    {
        return $this->extractBatchId($this->post('users/batch', $users));
    }

    public function updateUsersBatch(array $users): int
    {
        return $this->extractBatchId($this->patch('users/batch', $users));
    }

    // ─────────────────────────────────────────────────────────────
    // CLASS TEMPLATES
    // ─────────────────────────────────────────────────────────────

    public function listClassTemplates(array $filters = []): array
    {
        return $this->paginateAll('class_templates', $filters);
    }

    public function getClassTemplate(int $neoId): array
    {
        return $this->get("class_templates/{$neoId}");
    }

    public function getClassTemplateBySisId(string $sisId): ?array
    {
        return $this->findOneBySisId('class_templates', $sisId);
    }

    public function createClassTemplate(array $data): array
    {
        return $this->post('class_templates', $data);
    }

    public function updateClassTemplate(int $neoId, array $data): array
    {
        return $this->patch("class_templates/{$neoId}", $data);
    }

    public function createClassTemplatesBatch(array $templates): int
    {
        return $this->extractBatchId($this->post('class_templates/batch', $templates));
    }

    public function updateClassTemplatesBatch(array $templates): int
    {
        return $this->extractBatchId($this->patch('class_templates/batch', $templates));
    }

    public function getClassTemplateTeachers(int $classTemplateId): array
    {
        return $this->paginateAll("class_templates/{$classTemplateId}/teachers");
    }

    public function addClassTemplateTeacher(int $classTemplateId, int $userId): array
    {
        return $this->post("class_templates/{$classTemplateId}/teachers", ['user_id' => $userId]);
    }

    public function addClassTemplateTeachersBatch(int $classTemplateId, array $userIds): int
    {
        return $this->extractBatchId(
            $this->post("class_templates/{$classTemplateId}/teachers/batch", $this->toUserIdPayload($userIds))
        );
    }

    public function removeClassTemplateTeacher(int $classTemplateId, int $userId): void
    {
        $this->delete("class_templates/{$classTemplateId}/teachers/{$userId}");
    }

    // ─────────────────────────────────────────────────────────────
    // CLASSES
    // ─────────────────────────────────────────────────────────────

    public function listClasses(array $filters = []): array
    {
        return $this->paginateAll('classes', $filters);
    }

    public function getClass(int $neoId): array
    {
        return $this->get("classes/{$neoId}");
    }

    public function getClassBySisId(string $sisId): ?array
    {
        return $this->findOneBySisId('classes', $sisId);
    }

    public function createClass(array $data): array
    {
        return $this->post('classes', $data);
    }

    public function updateClass(int $neoId, array $data): array
    {
        return $this->patch("classes/{$neoId}", $data);
    }

    public function createClassesBatch(array $classes): int
    {
        return $this->extractBatchId($this->post('classes/batch', $classes));
    }

    public function updateClassesBatch(array $classes): int
    {
        return $this->extractBatchId($this->patch('classes/batch', $classes));
    }

    // ─────────────────────────────────────────────────────────────
    // CLASS STUDENTS (inscripciones — no existe /enrollments en NEO)
    // ─────────────────────────────────────────────────────────────

    public function listAllClassStudents(array $filters = []): array
    {
        return $this->paginateAll('class_students', $filters);
    }

    public function getClassStudents(int $classId, array $filters = []): array
    {
        return $this->paginateAll("classes/{$classId}/students", $filters);
    }

    public function getClassStudent(int $classId, int $userId): array
    {
        return $this->get("classes/{$classId}/students/{$userId}");
    }

    public function enrollStudent(int $classId, int $userId, array $options = []): array
    {
        return $this->post("classes/{$classId}/students", ['user_id' => $userId], $this->toOptionsQuery($options));
    }

    public function enrollStudentsBatch(int $classId, array $userIds, array $options = []): int
    {
        return $this->extractBatchId(
            $this->post(
                "classes/{$classId}/students/batch",
                $this->toUserIdPayload($userIds),
                $this->toOptionsQuery($options)
            )
        );
    }

    public function updateClassStudent(int $classId, int $userId, array $data): array
    {
        return $this->patch("classes/{$classId}/students/{$userId}", $data);
    }

    public function updateClassStudentsBatch(int $classId, array $students): int
    {
        return $this->extractBatchId($this->patch("classes/{$classId}/students/batch", $students));
    }

    public function unenrollStudent(int $classId, int $userId): void
    {
        $this->delete("classes/{$classId}/students/{$userId}");
    }

    // ─────────────────────────────────────────────────────────────
    // CLASS TEACHERS
    // ─────────────────────────────────────────────────────────────

    public function getClassTeachers(int $classId): array
    {
        return $this->paginateAll("classes/{$classId}/teachers");
    }

    public function assignTeacher(int $classId, int $userId): array
    {
        return $this->post("classes/{$classId}/teachers", ['user_id' => $userId]);
    }

    public function assignTeachersBatch(int $classId, array $userIds): int
    {
        return $this->extractBatchId(
            $this->post("classes/{$classId}/teachers/batch", $this->toUserIdPayload($userIds))
        );
    }

    public function removeTeacher(int $classId, int $userId): void
    {
        $this->delete("classes/{$classId}/teachers/{$userId}");
    }

    // ─────────────────────────────────────────────────────────────
    // BATCHES
    // ─────────────────────────────────────────────────────────────

    public function getBatch(int $batchId): array
    {
        return $this->get("batches/{$batchId}");
    }

    public function waitForBatch(int $batchId, int $timeoutSeconds = 120): array
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while (true) {
            $batch = $this->getBatch($batchId);

            if (($batch['status'] ?? null) === 'Finished') {
                return $batch;
            }

            if (microtime(true) >= $deadline) {
                throw new RuntimeException("Timeout esperando el batch {$batchId} de NEO LMS tras {$timeoutSeconds}s");
            }

            sleep(2);
        }
    }

    public function listBatches(array $filters = []): array
    {
        return $this->paginateAll('batches', $filters);
    }

    // ─────────────────────────────────────────────────────────────
    // UTILIDADES
    // ─────────────────────────────────────────────────────────────

    public function healthCheck(): bool
    {
        try {
            $this->get('users', ['$limit' => 1]);

            return true;
        } catch (Throwable $e) {
            Log::channel('sync')->error('NEO LMS health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HTTP core
    // ─────────────────────────────────────────────────────────────

    private function get(string $endpoint, array $query = []): array
    {
        return $this->requestWithRetry('GET', $endpoint, ['query' => $query]);
    }

    private function post(string $endpoint, array $body = [], array $query = []): array
    {
        $options = ['json' => $body];

        if ($query !== []) {
            $options['query'] = $query;
        }

        return $this->requestWithRetry('POST', $endpoint, $options);
    }

    private function patch(string $endpoint, array $body = []): array
    {
        return $this->requestWithRetry('PATCH', $endpoint, ['json' => $body]);
    }

    private function delete(string $endpoint): void
    {
        $this->requestWithRetry('DELETE', $endpoint, [], expectEmpty: true);
    }

    /**
     * Trae todas las páginas de un endpoint de listado usando el cursor $after.
     * Si el caller pasa un $limit explícito, se respeta como una única página
     * (no se auto-pagina). Si el caller pasa $order, se pagina con $offset ya
     * que $after no es compatible con $order.
     */
    private function paginateAll(string $endpoint, array $filters = []): array
    {
        $query = $this->normalizeParams($filters);

        if (array_key_exists('$limit', $query)) {
            return $this->get($endpoint, $query);
        }

        $query['$limit'] = $this->pageSize;
        $useOffset = array_key_exists('$order', $query);
        $offset = 0;
        $after = null;
        $results = [];

        while (true) {
            $page = $query;

            if ($useOffset) {
                $page['$offset'] = $offset;
            } elseif ($after !== null) {
                $page['$after'] = $after;
            }

            $batch = $this->get($endpoint, $page);

            if ($batch === []) {
                break;
            }

            array_push($results, ...$batch);

            if (count($batch) < $this->pageSize) {
                break;
            }

            if ($useOffset) {
                $offset += count($batch);
            } else {
                $after = $batch[array_key_last($batch)]['id'] ?? null;

                if ($after === null) {
                    break;
                }
            }
        }

        return $results;
    }

    private function requestWithRetry(string $method, string $endpoint, array $options, bool $expectEmpty = false): array
    {
        $endpoint = ltrim($endpoint, '/');
        $maxAttempts = max(1, $this->retryTimes);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $this->client->request($method, $endpoint, $options);

                if ($expectEmpty) {
                    return [];
                }

                $body = (string) $response->getBody();

                return $body === '' ? [] : json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (ClientException $e) {
                $status = $e->getResponse()?->getStatusCode();

                if ($status !== 429 || $attempt === $maxAttempts) {
                    $this->logFailure($method, $endpoint, $e, $attempt);

                    throw $e;
                }
            } catch (ServerException|ConnectException $e) {
                if ($attempt === $maxAttempts) {
                    $this->logFailure($method, $endpoint, $e, $attempt);

                    throw $e;
                }
            }

            $sleepMs = $this->retrySleepMs * (2 ** ($attempt - 1));

            Log::channel('sync')->warning('NEO LMS API retry', [
                'method' => $method,
                'endpoint' => $endpoint,
                'attempt' => $attempt,
                'sleep_ms' => $sleepMs,
            ]);

            usleep($sleepMs * 1000);
        }

        throw new RuntimeException("NEO LMS request failed: {$method} {$endpoint}");
    }

    private function logFailure(string $method, string $endpoint, Throwable $e, int $attempt): void
    {
        Log::channel('sync')->error('NEO LMS API request failed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'attempt' => $attempt,
            'error' => $e->getMessage(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function findOneBySisId(string $endpoint, string $sisId): ?array
    {
        $results = $this->get($endpoint, [
            '$filter' => json_encode(['sis_id' => $sisId], JSON_THROW_ON_ERROR),
            '$limit' => 1,
        ]);

        return $results[0] ?? null;
    }


    private function findOneByUserId(string $endpoint, string $userId): ?array
    {
        $results = $this->get($endpoint, [
            '$filter' => json_encode(['userid' => $userId], JSON_THROW_ON_ERROR),
            '$limit' => 1,
        ]);

        return $results[0] ?? null;
    }

    private function findOneByUId(string $endpoint, string $sisId): ?array
    {
        $results = $this->get($endpoint, [
            '$filter' => json_encode(['sis_id' => $sisId], JSON_THROW_ON_ERROR),
            '$limit' => 1,
        ]);

        return $results[0] ?? null;
    }

    /** Separa claves $-prefijadas (query params NEO) de campos de negocio ($filter JSON). */
    private function normalizeParams(array $filters): array
    {
        $query = [];
        $filterFields = [];

        foreach ($filters as $key => $value) {
            if (is_string($key) && str_starts_with($key, '$')) {
                $query[$key] = $value;

                continue;
            }

            $filterFields[$key] = $value;
        }

        if ($filterFields !== []) {
            $query['$filter'] = json_encode($filterFields, JSON_THROW_ON_ERROR);
        }

        return $query;
    }

    private function toUserIdPayload(array $userIds): array
    {
        return array_map(static fn (int $userId): array => ['user_id' => $userId], $userIds);
    }

    private function toOptionsQuery(array $options): array
    {
        return $options === [] ? [] : ['$options' => json_encode($options, JSON_THROW_ON_ERROR)];
    }

    private function extractBatchId(array $response): int
    {
        if (! isset($response['batch_id'])) {
            throw new RuntimeException('NEO LMS no devolvió batch_id en la respuesta');
        }

        return (int) $response['batch_id'];
    }
}

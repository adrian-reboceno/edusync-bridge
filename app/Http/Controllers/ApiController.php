<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="EduSync Bridge API",
 *     description="API de integración bidireccional entre Control Escolar (Tecnológico de Monterrey) y CYPHER Learning NEO LMS v3. Gestiona sincronización de alumnos, docentes, inscripciones, calificaciones y analítica académica.",
 *
 *     @OA\Contact(email="admin@edusync.edu")
 * )
 *
 * @OA\Server(url="http://localhost:8000", description="Local")
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(name="Auth", description="Autenticación, 2FA y gestión de sesiones")
 * @OA\Tag(name="Role", description="Gestión de roles RBAC2")
 * @OA\Tag(name="AuditLog", description="Registro de auditoría")
 * @OA\Tag(name="Analytics - Usuarios", description="Analítica de usuarios y sesiones")
 * @OA\Tag(name="Analytics - Clases", description="Analítica de clases e inscripciones")
 */
class ApiController extends Controller
{
    //
}

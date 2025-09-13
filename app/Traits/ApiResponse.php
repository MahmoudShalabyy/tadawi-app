<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

trait ApiResponse
{
    /**
     * Return a standardized API response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @param bool $success
     * @return JsonResponse
     */
    protected function respond($data = null, string $message = '', int $status = 200, bool $success = true): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'status' => $status
        ], $status);
    }

    /**
     * Return a success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function success($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return $this->respond($data, $message, $status, true);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int $status
     * @param mixed $data
     * @return JsonResponse
     */
    protected function error(string $message = 'Error occurred', int $status = 400, $data = null): JsonResponse
    {
        return $this->respond($data, $message, $status, false);
    }

    /**
     * Return a not found response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return a server error response.
     *
     * @param string $message
     * @param mixed $data
     * @return JsonResponse
     */
    protected function serverError(string $message = 'Internal server error', $data = null): JsonResponse
    {
        return $this->error($message, 500, $data);
    }

    /**
     * Return a validation error response.
     *
     * @param ValidationException $exception
     * @param string $message
     * @return JsonResponse
     */
    protected function validationError(ValidationException $exception, string $message = 'Validation failed'): JsonResponse
    {
        return $this->respond([
            'errors' => $exception->errors()
        ], $message, 422, false);
    }

    /**
     * Return an unauthorized response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return a forbidden response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }
}

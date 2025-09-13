<?php

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Paginate data with custom metadata and optional search functionality
 *
 * @param Builder $query The Eloquent query builder
 * @param int $perPage Number of items per page (default: 10)
 * @param string|null $searchTerm Optional search term to filter results
 * @param array $searchFields Fields to search in (default: ['name'])
 * @return array Paginated response with custom metadata
 */
function paginateData(Builder $query, int $perPage = 10, ?string $searchTerm = null, array $searchFields = ['name']): array
{
    // Apply search filter if search term is provided
    if ($searchTerm && !empty($searchFields)) {
        $query->where(function ($q) use ($searchTerm, $searchFields) {
            foreach ($searchFields as $index => $field) {
                if ($index === 0) {
                    $q->where($field, 'LIKE', "%{$searchTerm}%");
                } else {
                    $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                }
            }
        });
    }

    // Get paginated results
    $paginatedResults = $query->paginate($perPage);

    // Return custom paginated response with metadata
    return [
        'data' => $paginatedResults->items(),
        'pagination' => [
            'current_page' => $paginatedResults->currentPage(),
            'per_page' => $paginatedResults->perPage(),
            'total' => $paginatedResults->total(),
            'last_page' => $paginatedResults->lastPage(),
            'from' => $paginatedResults->firstItem(),
            'to' => $paginatedResults->lastItem(),
            'has_more_pages' => $paginatedResults->hasMorePages(),
        ],
        'search' => [
            'term' => $searchTerm,
            'fields' => $searchFields,
        ]
    ];
}

/**
 * Sanitize pagination parameters from request
 *
 * @param Request $request
 * @return array
 */
function sanitize_pagination_params(Request $request): array
{
    static $DEFAULT_PER_PAGE = 10;
    static $MAX_PER_PAGE = 100;
    static $MIN_PER_PAGE = 1;
    static $DEFAULT_PAGE = 1;

    $page = max($DEFAULT_PAGE, (int) $request->input('page', $DEFAULT_PAGE));
    $perPage = $request->input('per_page', $DEFAULT_PER_PAGE);
    $perPage = (int) $perPage;

    if ($perPage < $MIN_PER_PAGE || $perPage > $MAX_PER_PAGE) {
        $perPage = $DEFAULT_PER_PAGE;
    }

    return [
        'page' => $page,
        'per_page' => $perPage,
    ];
}

/**
 * Paginate query with sanitized parameters
 *
 * @param Builder $query
 * @param array $params
 * @return LengthAwarePaginator
 */
function paginate_query(Builder $query, array $params): LengthAwarePaginator
{
    return $query->paginate(
        $params['per_page'],
        ['*'],
        'page',
        $params['page']
    );
}

/**
 * Create paginated response from query and request
 *
 * @param Builder $query
 * @param Request $request
 * @return LengthAwarePaginator
 */
function create_paginated_response(Builder $query, Request $request): LengthAwarePaginator
{
    $params = sanitize_pagination_params($request);
    return paginate_query($query, $params);
}

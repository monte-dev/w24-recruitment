<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportResource;
use App\Repositories\ImportRepositoryInterface;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportRepositoryInterface $repository,
        private readonly ImportService $importService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return ImportResource::collection($this->repository->all());
    }

    public function store(StoreImportRequest $request): JsonResponse
    {
        $import = $this->importService->importFile($request->file('file'));

        return (new ImportResource($import))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): ImportResource
    {
        $import = $this->repository->findByIdWithLogs($id);

        abort_if(! $import, 404, 'Import not found.');

        return new ImportResource($import);
    }
}

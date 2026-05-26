<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PhysicalRecordMovementController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\AssessmentRecordController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\TaxDeclarationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => [
    'status' => 'ok',
    'service' => 'provincial-assessor-api',
    'time' => now()->toIso8601String(),
]);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', DashboardController::class);
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('role:admin,assessor,records_staff');
    Route::get('/activity-logs/summary', [ActivityLogController::class, 'summary'])->middleware('role:admin,assessor,records_staff');
    Route::get('/activity-logs/export.csv', [ActivityLogController::class, 'export'])->middleware('role:admin,assessor,records_staff');
    Route::get('/references', ReferenceController::class);
    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin');
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('role:admin');
    Route::put('/users/{user}/permissions', [UserController::class, 'updatePermissions'])->middleware('role:admin');
    Route::delete('/users/{user}/permissions', [UserController::class, 'resetPermissions'])->middleware('role:admin');
    Route::get('/auth/login-activity', [AuthController::class, 'loginActivity'])->middleware('role:admin');
    Route::get('/security/matrix', [\App\Http\Controllers\Api\SecurityController::class, 'matrix'])->middleware('role:admin');
    Route::get('/security/login-stats', [\App\Http\Controllers\Api\SecurityController::class, 'loginStats'])->middleware('role:admin');

    Route::get('/properties/export.csv', [ExportController::class, 'propertiesCsv']);
    Route::get('/backup/export', [ExportController::class, 'backup'])
        ->middleware('role:admin,assessor');

    Route::get('/properties/{property}/dossier', [PropertyController::class, 'dossier']);
    Route::get('/owners', [\App\Http\Controllers\Api\OwnerController::class, 'index']);
    Route::get('/owners/{owner}', [\App\Http\Controllers\Api\OwnerController::class, 'show']);
    Route::get('/properties/{property}/export', [ExportController::class, 'property']);
    Route::get('/properties/{property}/export/dossier', [ExportController::class, 'propertyDossier']);
    Route::get('/properties/{property}/export/activity.csv', [ExportController::class, 'propertyActivityCsv']);
    Route::post('/properties/{property}/approve', [PropertyController::class, 'approve'])
        ->middleware('role:admin,assessor');
    Route::apiResource('properties', PropertyController::class)->only(['index', 'show']);

    Route::middleware('role:admin,assessor,records_staff')->group(function () {
            Route::apiResource('properties', PropertyController::class)->only(['store', 'update']);

            Route::post('/properties/{property}/tax-declarations', [TaxDeclarationController::class, 'store']);
            Route::put('/properties/{property}/tax-declarations/{taxDeclaration}', [TaxDeclarationController::class, 'update']);
            Route::post('/properties/{property}/tax-declarations/{taxDeclaration}/approve', [TaxDeclarationController::class, 'approve'])
                ->middleware('role:admin,assessor');

            Route::post('/properties/{property}/tax-declarations/{taxDeclaration}/assessments', [AssessmentRecordController::class, 'store']);
            Route::put('/properties/{property}/tax-declarations/{taxDeclaration}/assessments/{assessmentRecord}', [AssessmentRecordController::class, 'update']);

            Route::post('/properties/{property}/documents', [DocumentController::class, 'store']);
            Route::post('/documents/{document}/digitize', [DocumentController::class, 'digitize']);
            Route::post('/documents/{document}/ocr', [DocumentController::class, 'storeOcr']);
            Route::put('/documents/{document}', [DocumentController::class, 'update']);
            Route::post('/properties/{property}/documents/{document}/movements', [PhysicalRecordMovementController::class, 'store']);
        });

        Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->middleware('role:admin');
        Route::delete('/properties/{property}/tax-declarations/{taxDeclaration}', [TaxDeclarationController::class, 'destroy'])->middleware('role:admin');
        Route::delete('/properties/{property}/tax-declarations/{taxDeclaration}/assessments/{assessmentRecord}', [AssessmentRecordController::class, 'destroy'])->middleware('role:admin');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->middleware('role:admin');
    Route::get('/documents/{document}/movements', [PhysicalRecordMovementController::class, 'index']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
});

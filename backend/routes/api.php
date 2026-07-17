<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChecklistCategoryController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\ChecklistItemController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\PerformanceReportController;
use App\Http\Controllers\Api\PluginRequestController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectPhaseController;
use App\Http\Controllers\Api\ProjectReportController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SeoAuditController;
use App\Http\Controllers\Api\QaReviewController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SignoffController;
use App\Http\Controllers\Api\ToolController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/tools', [ToolController::class, 'index']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    Route::get('/settings', [SettingController::class, 'show'])->middleware('permission:manage_settings');
    Route::put('/settings', [SettingController::class, 'update'])->middleware('permission:manage_settings');

    Route::get('/checklist-categories', [ChecklistCategoryController::class, 'index'])->middleware('permission:manage_checklist_template');
    Route::post('/checklist-categories', [ChecklistCategoryController::class, 'store'])->middleware('permission:manage_checklist_template');
    Route::patch('/checklist-categories/{checklistCategory}', [ChecklistCategoryController::class, 'update'])->middleware('permission:manage_checklist_template');
    Route::delete('/checklist-categories/{checklistCategory}', [ChecklistCategoryController::class, 'destroy'])->middleware('permission:manage_checklist_template');
    Route::post('/checklist-categories/{checklistCategory}/items', [ChecklistItemController::class, 'store'])->middleware('permission:manage_checklist_template');
    Route::patch('/checklist-items/{checklistItem}', [ChecklistItemController::class, 'update'])->middleware('permission:manage_checklist_template');
    Route::delete('/checklist-items/{checklistItem}', [ChecklistItemController::class, 'destroy'])->middleware('permission:manage_checklist_template');

    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:manage_roles');
    Route::patch('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:manage_roles');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:manage_roles');

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:manage_users');
    Route::patch('/users/{user}', [UserController::class, 'update'])->middleware('permission:manage_users');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:manage_users');

    Route::apiResource('projects', ProjectController::class);
    Route::get('/projects/{project}/report-pdf', [ProjectReportController::class, 'pdf']);

    Route::get('/projects/{project}/seo-audits', [SeoAuditController::class, 'index']);
    Route::post('/projects/{project}/seo-audits', [SeoAuditController::class, 'store']);

    Route::patch('/projects/{project}/phases/{phase}', [ProjectPhaseController::class, 'update']);

    Route::get('/projects/{project}/checklist', [ChecklistController::class, 'index']);
    Route::patch('/projects/{project}/checklist/{item}', [ChecklistController::class, 'update']);
    Route::post('/projects/{project}/checklist/{item}/evidence', [ChecklistController::class, 'storeEvidence']);
    Route::delete('/evidence/{evidence}', [ChecklistController::class, 'destroyEvidence']);

    Route::get('/projects/{project}/plugin-requests', [PluginRequestController::class, 'index']);
    Route::post('/projects/{project}/plugin-requests', [PluginRequestController::class, 'store']);
    Route::patch('/plugin-requests/{pluginRequest}/decide', [PluginRequestController::class, 'decide'])
        ->middleware('permission:decide_plugins');
    Route::delete('/plugin-requests/{pluginRequest}', [PluginRequestController::class, 'destroy'])
        ->middleware('permission:manage_projects');

    Route::get('/projects/{project}/licenses', [LicenseController::class, 'index']);
    Route::post('/projects/{project}/licenses', [LicenseController::class, 'store']);
    Route::patch('/projects/{project}/licenses/{license}', [LicenseController::class, 'update']);
    Route::delete('/projects/{project}/licenses/{license}', [LicenseController::class, 'destroy']);

    Route::get('/projects/{project}/performance-reports', [PerformanceReportController::class, 'index']);
    Route::post('/projects/{project}/performance-reports', [PerformanceReportController::class, 'store']);
    Route::post('/projects/{project}/performance-reports/run-pagespeed', [PerformanceReportController::class, 'runPageSpeed']);
    Route::delete('/projects/{project}/performance-reports/{performanceReport}', [PerformanceReportController::class, 'destroy'])
        ->middleware('permission:manage_projects');

    Route::get('/projects/{project}/qa-reviews', [QaReviewController::class, 'index']);
    Route::get('/projects/{project}/qa-reviews/latest', [QaReviewController::class, 'latest']);
    Route::post('/projects/{project}/qa-reviews', [QaReviewController::class, 'store'])
        ->middleware('permission:qa_review');
    Route::patch('/qa-reviews/{qaReview}/items/{qaReviewItem}', [QaReviewController::class, 'updateItem'])
        ->middleware('permission:qa_review');
    Route::post('/qa-reviews/{qaReview}/submit', [QaReviewController::class, 'submit'])
        ->middleware('permission:qa_review');

    Route::post('/projects/{project}/signoffs', [SignoffController::class, 'store'])
        ->middleware('permission:manage_projects');

    Route::post('/technical-analysis/scan', [QuotationController::class, 'scan']);
    Route::get('/quotations', [QuotationController::class, 'index']);
    Route::post('/quotations', [QuotationController::class, 'store']);
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show']);
    Route::patch('/quotations/{quotation}', [QuotationController::class, 'update']);
    Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy']);
    Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf']);
});

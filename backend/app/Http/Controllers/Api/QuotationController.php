<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Quotation;
use App\Services\SiteAnalyzerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use RuntimeException;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::query()->with('creator:id,name')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('url', 'like', $search)->orWhere('client_name', 'like', $search));
        }

        return $query->paginate(min($request->integer('per_page', 15), 100))->withQueryString();
    }

    public function scan(Request $request, SiteAnalyzerService $analyzer)
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        try {
            return $analyzer->analyze($data['url']);
        } catch (RuntimeException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $quotation = Quotation::create($data);

        ActivityLog::log(null, $request->user()->id, 'quotation_created', "إنشاء عرض سعر: {$quotation->url}");

        return response()->json($quotation->load('creator:id,name'), 201);
    }

    public function show(Quotation $quotation)
    {
        return $quotation->load('creator:id,name');
    }

    public function update(Request $request, Quotation $quotation)
    {
        $quotation->update($this->validated($request, $quotation));

        ActivityLog::log(null, $request->user()->id, 'quotation_updated', "تحديث عرض سعر: {$quotation->url}");

        return $quotation->load('creator:id,name');
    }

    public function destroy(Request $request, Quotation $quotation)
    {
        $quotation->delete();

        ActivityLog::log(null, $request->user()->id, 'quotation_deleted', "حذف عرض سعر: {$quotation->url}");

        return response()->noContent();
    }

    public function pdf(Quotation $quotation)
    {
        $logoBase64 = base64_encode(file_get_contents(resource_path('images/logo.png')));

        $pdf = Pdf::loadView('reports.quotation', [
            'quotation' => $quotation,
            'logoBase64' => $logoBase64,
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream("quotation-{$quotation->id}.pdf");
    }

    private function validated(Request $request, ?Quotation $quotation = null): array
    {
        $rules = [
            'url' => [$quotation ? 'sometimes' : 'required', 'string', 'max:2048'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,sent,accepted,rejected'],

            'detected_framework' => ['nullable', 'string', 'max:255'],
            'detected_signals' => ['nullable', 'array'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'business_summary' => ['nullable', 'string'],
            'infrastructure' => ['nullable', 'array'],
            'recommended_platform' => ['nullable', 'string', 'max:255'],
            'recommendation_reason' => ['nullable', 'string'],
            'crawl_summary' => ['nullable', 'array'],
            'proposed_pages' => ['nullable', 'array'],
            'proposed_pages.*' => ['string', 'max:255'],

            'project_summary' => ['nullable', 'string'],
            'technical_scope' => ['nullable', 'string'],
            'cost_items' => ['nullable', 'array'],
            'cost_items.*.name' => ['required_with:cost_items', 'string', 'max:255'],
            'cost_items.*.type' => ['nullable', 'string', 'max:100'],
            'cost_items.*.price' => ['required_with:cost_items', 'numeric', 'min:0'],
            'cost_items.*.cycle' => ['nullable', 'in:monthly,yearly,lifetime'],
            'currency' => ['nullable', 'string', 'max:10'],
            'domain_cost' => ['nullable', 'numeric', 'min:0'],
            'hosting_cost' => ['nullable', 'numeric', 'min:0'],
            'hosting_cycle' => ['nullable', 'in:monthly,yearly'],
            'support_months' => ['nullable', 'integer', 'min:0'],
        ];

        return $request->validate($rules);
    }
}

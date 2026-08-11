<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ProjectPriorityService;
use App\Services\ProjectReadinessService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(Request $request)
    {
        $key = Setting::get(Setting::KEY_PAGESPEED_API_KEY);

        return response()->json([
            'pagespeed_api_key_set' => ! empty($key),
            'pagespeed_api_key_preview' => $key ? '••••••'.substr($key, -4) : null,
            'priority_weights' => ProjectPriorityService::weights(),
            'project_type_requirements' => $this->allRequirementMaps(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'pagespeed_api_key' => ['nullable', 'string', 'max:255'],
            'priority_weights' => ['nullable', 'array'],
            'priority_weights.*' => ['integer', 'min:0', 'max:200'],
            'project_type_requirements' => ['nullable', 'array'],
            'project_type_requirements.*' => ['array'],
            'project_type_requirements.*.*' => ['string'],
        ]);

        if (array_key_exists('pagespeed_api_key', $data) && $data['pagespeed_api_key'] !== '') {
            Setting::set(Setting::KEY_PAGESPEED_API_KEY, $data['pagespeed_api_key']);
        }

        if (array_key_exists('priority_weights', $data)) {
            Setting::set(Setting::KEY_PRIORITY_WEIGHTS, json_encode(array_merge(
                ProjectPriorityService::DEFAULT_WEIGHTS,
                $data['priority_weights']
            )));
        }

        if (array_key_exists('project_type_requirements', $data)) {
            Setting::set(Setting::KEY_PROJECT_TYPE_REQUIREMENTS, json_encode(array_merge(
                ProjectReadinessService::DEFAULT_REQUIREMENTS,
                $data['project_type_requirements']
            )));
        }

        return $this->show($request);
    }

    private function allRequirementMaps(): array
    {
        $map = ProjectReadinessService::DEFAULT_REQUIREMENTS;
        $stored = Setting::get(Setting::KEY_PROJECT_TYPE_REQUIREMENTS);

        if ($stored) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded)) {
                $map = array_merge($map, $decoded);
            }
        }

        return $map;
    }
}

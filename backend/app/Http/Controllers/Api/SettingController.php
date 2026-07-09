<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(Request $request)
    {
        $key = Setting::get(Setting::KEY_PAGESPEED_API_KEY);

        return response()->json([
            'pagespeed_api_key_set' => ! empty($key),
            'pagespeed_api_key_preview' => $key ? '••••••'.substr($key, -4) : null,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'pagespeed_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('pagespeed_api_key', $data) && $data['pagespeed_api_key'] !== '') {
            Setting::set(Setting::KEY_PAGESPEED_API_KEY, $data['pagespeed_api_key']);
        }

        return $this->show($request);
    }
}

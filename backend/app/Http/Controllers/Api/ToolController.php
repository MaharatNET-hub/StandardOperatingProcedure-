<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tool;

class ToolController extends Controller
{
    public function index()
    {
        return Tool::orderBy('category')->orderBy('order')->get()->groupBy('category');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadApiStoreRequest;
use App\Models\Lead;

class LeadApiController extends Controller
{
    public function index()
    {
        return Lead::orderBy('created_at', 'desc')->paginate(10);
    }

    public function store(LeadApiStoreRequest $request) {}
}

<?php
namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\StoreChildRequest;
use App\Http\Requests\Api\V1\Rider\UpdateChildRequest;
use App\Http\Resources\Api\V1\ChildResource;
use App\Models\Child;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    public function index(Request $request) {
        $children = $request->user()->children;
        return ChildResource::collection($children);
    }

    public function store(StoreChildRequest $request) {
        $child = $request->user()->children()->create($request->validated());
        if ($request->hasFile('photo')) {
            $child->addMediaFromRequest('photo')->toMediaCollection('photo');
        }
        return new ChildResource($child);
    }

    public function show(Child $child) {
        return new ChildResource($child);
    }

    public function update(UpdateChildRequest $request, Child $child) {
        $child->update($request->validated());
        if ($request->hasFile('photo')) {
            $child->clearMediaCollection('photo');
            $child->addMediaFromRequest('photo')->toMediaCollection('photo');
        }
        return new ChildResource($child->fresh());
    }

    public function destroy(Child $child) {
        $child->delete();
        return response()->noContent();
    }
} 
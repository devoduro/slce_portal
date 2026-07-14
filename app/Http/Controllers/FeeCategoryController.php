<?php

namespace App\Http\Controllers;

use App\Models\FeeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeeCategoryController extends Controller
{
    /**
     * Add a new fee category (e.g. "Library Fee", "Hostel Fee") that fee structures and
     * student fee charges can then be billed under.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:fee_categories,name',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        FeeCategory::create([
            'name' => $request->name,
            'slug' => FeeCategory::slugFor($request->name),
        ]);

        return back()->with('success', "Category \"{$request->name}\" added.");
    }

    /**
     * Remove a fee category, as long as it isn't protected or currently in use by any
     * fee structure or student fee charge.
     */
    public function destroy(FeeCategory $category)
    {
        if ($category->is_protected) {
            return back()->with('error', "\"{$category->name}\" is a protected category and can't be removed.");
        }

        if ($category->isInUse()) {
            return back()->with('error', "\"{$category->name}\" is currently used by one or more fee structures/charges and can't be removed.");
        }

        $category->delete();

        return back()->with('success', "Category \"{$category->name}\" removed.");
    }
}

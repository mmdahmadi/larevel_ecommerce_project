<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Exception;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::latest()->paginate(20);
        return view('admin.categories.index' , compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parentCategories = Category::where('parent_id',0)->get();
        $attributes = Attribute::all();
        return view('admin.categories.create' , compact('parentCategories', 'attributes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required | unique:categories,slug',
            'parent_id' => 'required',
            'attribute_ids' => 'required',
            'attribute_ids.*' => 'exists:attributes,id',
            'attribute_is_filter_ids' => 'required',
            'attribute_is_filter_ids.*' => 'exists:attributes,id',
            'variation_id'=> 'required|exists:attributes,id'
        ]);
        try {

            DB::beginTransaction();

            $category = Category::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'parent_id' => $request->parent_id,
                'icon' => $request->icon,
                'description' => $request->description,
                'is_active' => $request->is_active
            ]);

            foreach ($request->attribute_ids as $attributeId){
                $attribute = Attribute::findOrFail($attributeId);
                $attribute->categories()->attach($category->id , [
                    'is_filter' => in_array($attributeId , $request->attribute_is_filter_ids) ? 1 : 0,
                    'is_variation' => $request->variation_id == $attributeId ? 1 : 0
                ]);

            }
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            alert()->error($e->getMessage(), 'مشکل در ایجاد دسته بندی')->persistent('حله');
            return redirect()->back();
        }
        alert()->success('دسته بندی مورد نظر ایجاد شد', 'با تشکر');
        return redirect()->route('admin.categories.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {

        return view('admin.categories.show' , compact('category'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        $parentCategories = Category::where('parent_id',0)->get();
        $attributes = Attribute::all();
        return view('admin.categories.edit',compact('category' , 'parentCategories' , 'attributes'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required | unique:categories,slug,'.$category->id,
            'parent_id' => 'required',
            'attribute_ids' => 'required',
            'attribute_ids.*' => 'exists:attributes,id',
            'attribute_is_filter_ids' => 'required',
            'attribute_is_filter_ids.*' => 'exists:attributes,id',
            'variation_id'=> 'required|exists:attributes,id',
        ]);
        try {

            DB::beginTransaction();

                $category->update([
                'name' => $request->name,
                'slug' => $request->slug,
                'parent_id' => $request->parent_id,
                'icon' => $request->icon,
                'description' => $request->description,
                'is_active' => $request->is_active
            ]);

                $category->attributes()->detach();
            foreach ($request->attribute_ids as $attributeId){
                $attribute = Attribute::findOrFail($attributeId);
                $attribute->categories()->attach($category->id , [
                    'is_filter' => in_array($attributeId , $request->attribute_is_filter_ids) ? 1 : 0,
                    'is_variation' => $request->variation_id == $attributeId ? 1 : 0
                ]);

            }
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            alert()->error($e->getMessage(), 'مشکل در ویرایش دسته بندی')->persistent('حله');
            return redirect()->back();
        }
        alert()->success('دسته بندی مورد نظر ویرایش شد', 'با تشکر');
        return redirect()->route('admin.categories.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function getCategoryAttributes(Category $category)
    {
        $attributes =$category->attributes()->wherePivot('is_variation' , 0)->get();
        $variation =$category->attributes()->wherePivot('is_variation' , 1)->first();
        return ['attributes' => $attributes , 'variation' => $variation];

    }
}

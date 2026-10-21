@extends('admin.layouts.app') @section('title',$category->exists?'Sửa danh mục':'Thêm danh mục') @section('heading',$category->exists?'Sửa danh mục':'Thêm danh mục')
@section('content')<div class="admin-page-head"><div><h1>{{ $category->exists?'Cập nhật '.$category->name:'Danh mục mới' }}</h1><p>Slug có thể để trống để hệ thống tự sinh.</p></div></div>
<form class="admin-form" method="post" action="{{ $category->exists?route('admin.categories.update',$category):route('admin.categories.store') }}">@csrf @if($category->exists)@method('put')@endif
<div class="field"><label for="name">Tên danh mục *</label><input id="name" name="name" value="{{ old('name',$category->name) }}" required>@error('name')<small>{{ $message }}</small>@enderror</div>
<div class="field"><label for="slug">Slug</label><input id="slug" name="slug" value="{{ old('slug',$category->slug) }}">@error('slug')<small>{{ $message }}</small>@enderror</div>
<div class="field"><label for="sort_order">Thứ tự</label><input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order',$category->sort_order??0) }}" required></div>
<div class="field wide"><label for="description">Mô tả</label><textarea id="description" name="description" rows="4">{{ old('description',$category->description) }}</textarea></div>
<label class="check wide"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$category->exists?$category->is_active:true))> Hiển thị danh mục</label>
<div class="form-actions wide"><a class="admin-btn" href="{{ route('admin.categories.index') }}">Hủy</a><button class="admin-btn primary">Lưu danh mục</button></div></form>@endsection

<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        return $teachers = Teacher::all();
    }

    public function add()
    {
        $item = new Teacher();
        $item->name = 'Teacher 1';
        $item->save();

        return 'Teacher added';
    }

    public function show($id)
    {
        $item = Teacher::findOrFail($id);
        return $item;
    }

    public function update($id)
    {
        $item=Teacher::findOrFail($id);
        $item->name = 'Teacher 2';

        $item->update();

        return 'Teacher updated';
    }

    public function delete($id)
    {
        $items=Teacher::findOrfail($id);
        $items->delete();

        return 'Teacher deleted';
    }
}

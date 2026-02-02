<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    public function addData()
    {

        $result = DB::table('students')->insert([
            [
                'name' => 'tester',
                'email' => 'cdsctester@gmail.com',
                'age' => 15,
                'date_of_birth' => '2000-01-01',
                'gender' => 'm',
            ],
            [
                'name' => 'testerwwww',
                'email' => 'wwwwcdsctester@gmail.com',
                'age' => 13,
                'date_of_birth' => '2010-01-01',
                'gender' => 'f',
            ],
        ]);

        return 'added successfully---';
    }
}

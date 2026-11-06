<?php

namespace App\Http\Controllers;

class UserGuideController extends Controller
{
    
    public function download()
    {
        $filePath = public_path('assets/pdf/user-guide.pdf');
        if (file_exists($filePath)) {
            return response()->download($filePath);
        }
        return redirect()->back()->with('error', 'User guide not found.');
    }
}

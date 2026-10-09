<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManualBookController extends Controller
{
    public function index()
    {
        $filePath = base_path('MANUAL_BOOK.md');
        $content = file_exists($filePath) ? file_get_contents($filePath) : '# Buku Panduan Belum Tersedia';
        $htmlContent = Str::markdown($content);

        return view('manual_book.index', compact('htmlContent'));
    }
}

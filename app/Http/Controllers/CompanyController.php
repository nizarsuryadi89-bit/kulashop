<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $company = $user->company;

        return view('company.settings', compact('company'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'font_family' => 'nullable|string|max:100',
            'sidebar_theme' => 'nullable|string|max:50',
            'sidebar_text_theme' => 'nullable|string|max:50',
        ]);

        $user = auth()->user();
        $company = $user->company;

        if (! $company) {
            $company = new Company;
        }

        $company->name = $request->name;
        $company->address = $request->address;
        $company->phone = $request->phone;
        $company->email = $request->email;
        $company->font_family = $request->font_family;
        $company->sidebar_theme = $request->sidebar_theme;
        $company->sidebar_text_theme = $request->sidebar_text_theme;

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::delete($company->logo);
            }
            $path = $request->file('logo')->store('companies', 'public');
            $company->logo = $path;
        }

        if ($request->hasFile('login_background')) {
            if ($company->login_background) {
                Storage::delete($company->login_background);
            }
            $bgPath = $request->file('login_background')->store('companies/backgrounds', 'public');
            $company->login_background = $bgPath;
        }

        $company->save();

        if (! $user->company_id) {
            $user->company_id = $company->id;
            $user->save();
        }

        return redirect()->back()->with('success', 'Profil perusahaan berhasil diperbarui.');
    }
}

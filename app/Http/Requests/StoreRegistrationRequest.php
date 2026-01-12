<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:registrations,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'resident_category_id' => ['required', 'exists:resident_categories,id'],
            'citizenship_status' => ['required', 'in:WNI,WNA'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'national_id' => ['required', 'string', 'max:255'],
            'student_id' => ['nullable', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:M,F'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'university_school' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone_number' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'preferred_dorm_id' => ['nullable', 'exists:dorms,id'],
            'preferred_room_type_id' => ['nullable', 'exists:room_types,id'],
            'planned_check_in_date' => ['nullable', 'date'],
            'agreed_to_policy' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'agreed_to_policy.required' => 'Anda harus menyetujui kebijakan dan ketentuan.',
            'agreed_to_policy.accepted' => 'Anda harus menyetujui kebijakan dan ketentuan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'name.required' => 'Nama wajib diisi.',
            'resident_category_id.required' => 'Kategori penghuni wajib dipilih.',
            'citizenship_status.required' => 'Status kewarganegaraan wajib dipilih.',
            'national_id.required' => 'NIK wajib diisi.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran file maksimal 2MB.',
        ];
    }
}
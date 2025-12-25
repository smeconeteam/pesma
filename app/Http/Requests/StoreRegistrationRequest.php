<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Akun:contentReference[oaicite:10]{index=10}
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('registrations', 'email'),
                // cegah daftar ulang jika sudah jadi user
                function ($attr, $value, $fail) {
                    if (User::query()->where('email', $value)->exists()) {
                        $fail('Email ini sudah aktif. Silakan login.');
                    }
                },
            ],
            'name'     => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],

            // Profil calon penghuni:contentReference[oaicite:11]{index=11}
            'resident_category_id' => ['required', 'integer', 'exists:resident_categories,id'],
            'full_name'            => ['required', 'string', 'max:255'],
            'gender'               => ['required', Rule::in(['M', 'F'])],
            'national_id'          => ['nullable', 'regex:/^\d+$/'],
            'student_id'           => ['nullable', 'string', 'max:255'],
            'birth_place'          => ['nullable', 'string', 'max:255'],
            'birth_date'           => ['nullable', 'date'],
            'university_school'    => ['nullable', 'string', 'max:255'],

            // Foto (public form pakai input name="photo", nanti disimpan ke photo_path):contentReference[oaicite:12]{index=12}
            'photo' => ['nullable', 'image', 'max:2048'],

            // Kewarganegaraan & kontak:contentReference[oaicite:13]{index=13}
            'citizenship_status'   => ['required', Rule::in(['WNI', 'WNA'])],
            'country_id'           => ['required', 'integer', 'exists:countries,id'],
            'phone_number'         => ['nullable', 'regex:/^\d+$/', 'max:30'],
            'guardian_name'        => ['nullable', 'string', 'max:255'],
            'guardian_phone_number'=> ['nullable', 'regex:/^\d+$/', 'max:30'],

            // Preferensi kamar:contentReference[oaicite:14]{index=14}
            'preferred_dorm_id'     => ['nullable', 'integer', 'exists:dorms,id'],
            'preferred_room_type_id'=> ['nullable', 'integer', 'exists:room_types,id'],
            'planned_check_in_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.regex' => 'NIK hanya boleh angka.',
            'phone_number.regex' => 'No. HP hanya boleh angka.',
            'guardian_phone_number.regex' => 'No. HP Wali hanya boleh angka.',
        ];
    }
}

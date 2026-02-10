<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Akun
            'email' => 'required|email|unique:registrations,email|unique:users,email',
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8',

            // Profil
            'resident_category_id' => 'required|exists:resident_categories,id',
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'national_id' => 'required|string|max:255',
            'student_id' => 'required|string|max:255',
            'birth_place' => 'required|string|max:255',
            'birth_date' => 'required|date|before_or_equal:' . now()->subYears(6)->format('Y-m-d'),
            'university_school' => 'required|string|max:255',
            'photo' => 'nullable|image|max:2048',

            // Kewarganegaraan & Kontak
            'citizenship_status' => 'required|in:WNI,WNA',
            'country_id' => 'required|exists:countries,id',
            'phone_number' => 'required|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',

            // Preferensi (sekarang optional)
            'preferred_dorm_id' => 'nullable|exists:dorms,id',
            'preferred_room_type_id' => 'nullable|exists:room_types,id',
            'planned_check_in_date' => 'nullable|date|after_or_equal:today',

            // Kebijakan
            'agreed_to_policy' => 'required|accepted',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'Email',
            'name' => 'Nama Panggilan',
            'password' => 'Password',
            'resident_category_id' => 'Kategori Penghuni',
            'full_name' => 'Nama Lengkap',
            'gender' => 'Jenis Kelamin',
            'national_id' => 'NIK',
            'student_id' => 'NIM/NIS',
            'birth_place' => 'Tempat Lahir',
            'birth_date' => 'Tanggal Lahir',
            'university_school' => 'Universitas/Sekolah',
            'photo' => 'Foto',
            'citizenship_status' => 'Kewarganegaraan',
            'country_id' => 'Asal Negara',
            'phone_number' => 'No. HP',
            'guardian_name' => 'Nama Wali',
            'guardian_phone_number' => 'No. HP Wali',
            'address' => 'Alamat',
            'preferred_dorm_id' => 'Cabang',
            'preferred_room_type_id' => 'Tipe Kamar',
            'planned_check_in_date' => 'Rencana Tanggal Masuk',
            'agreed_to_policy' => 'Persetujuan Kebijakan',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah terdaftar.',
            'birth_date.before_or_equal' => 'Usia minimal 6 tahun.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
            'planned_check_in_date.after_or_equal' => 'Tanggal masuk minimal hari ini.',
            'agreed_to_policy.required' => 'Anda harus menyetujui kebijakan dan ketentuan.',
            'agreed_to_policy.accepted' => 'Anda harus menyetujui kebijakan dan ketentuan.',
        ];
    }
}
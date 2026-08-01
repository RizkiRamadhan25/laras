<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:min_width=128,min_height=128,max_width=6000,max_height=6000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' =>
                'Pilih foto yang akan digunakan.',

            'photo.image' =>
                'File yang dipilih harus berupa gambar.',

            'photo.mimes' =>
                'Foto harus berformat JPG, JPEG, PNG, atau WebP.',

            'photo.max' =>
                'Ukuran foto maksimal 5 MB.',

            'photo.dimensions' =>
                'Resolusi foto minimal 128 × 128 dan maksimal 6000 × 6000 piksel.',
        ];
    }
}

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
                'extensions:jpg,jpeg,png,webp',
                'max:4096',
                'dimensions:min_width=128,min_height=128,max_width=4096,max_height=4096',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Pilih foto yang akan digunakan.',

            'photo.image' => 'File yang dipilih harus berupa gambar.',

            'photo.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WebP.',

            'photo.extensions' => 'Ekstensi nama file foto tidak sesuai.',

            'photo.max' => 'Ukuran foto maksimal 4 MB.',

            'photo.dimensions' => 'Resolusi foto minimal 128 × 128 dan maksimal 4096 × 4096 piksel.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('settings.index')
            .'#profile';
    }
}

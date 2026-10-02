<?php

namespace App\Actions\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentRules
{
    public const CORE_PAGES = ['home', 'about-us', 'services', 'contact'];

    public const RESERVED_ROOTS = ['home', 'about-us', 'services', 'contact', 'pages', 'inquiries', 'group', 'admin', 'login', 'logout', 'register', 'forgot-password', 'reset-password', 'user', 'two-factor-challenge', 'email', 'confirm-password', 'passkeys', 'up', 'storage', 'build', 'images', 'api'];

    /** @return array<int, mixed> */
    public static function slugRules(bool $company = false): array
    {
        return ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn($company ? self::RESERVED_ROOTS : ['inquiry'])];
    }

    public static function validateSlug(string $slug, bool $company = false): void
    {
        Validator::make(['slug' => $slug], ['slug' => self::slugRules($company)])->validate();
    }

    public static function immutable(Model $model, string $attribute): void
    {
        if ($model->exists && $model->isDirty($attribute)) {
            throw ValidationException::withMessages([$attribute => 'Identitas atau pemilik konten tidak dapat dipindahkan melalui edit biasa.']);
        }
    }
}

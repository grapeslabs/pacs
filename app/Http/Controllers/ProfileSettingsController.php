<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\MoonShineAuth;

class ProfileSettingsController
{
    public function personal(Request $request): RedirectResponse
    {
        $user = $this->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
        ], [], ['name' => 'Имя', 'username' => 'Логин']);

        $user->{moonshineConfig()->getUserField('name') ?: 'name'} = $data['name'];
        $user->{moonshineConfig()->getUserField('username') ?: 'email'} = $data['username'];
        $user->save();

        return back()->with('toast', ['type' => 'success', 'message' => 'Личные данные сохранены']);
    }

    public function password(Request $request): RedirectResponse
    {
        $user = $this->user();
        $passwordField = moonshineConfig()->getUserField('password') ?: 'password';

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.min' => 'Пароль должен содержать не менее 6 символов',
            'password.confirmed' => 'Пароли не совпадают',
        ], ['current_password' => 'Текущий пароль', 'password' => 'Новый пароль']);

        if (! Hash::check((string) $request->input('current_password'), (string) $user->{$passwordField})) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Неверный текущий пароль']);
        }

        $user->{$passwordField} = $request->input('password');
        $user->save();

        return back()->with('toast', ['type' => 'success', 'message' => 'Пароль изменён']);
    }

    public function avatar(Request $request): RedirectResponse
    {
        $user = $this->user();

        $request->validate([
            'avatar' => ['required', 'file', 'mimes:jpg,jpeg,png,gif', 'max:10240'],
        ], [], ['avatar' => 'Фото']);

        $avatarField = moonshineConfig()->getUserField('avatar') ?: 'avatar';
        $disk = moonshineConfig()->getDisk();

        $old = (string) ($user->{$avatarField} ?? '');
        $path = $request->file('avatar')->store('moonshine_users', $disk);

        $user->forceFill([$avatarField => $path])->save();

        if ($old !== '' && $old !== $path) {
            rescue(fn () => Storage::disk($disk)->delete($old), report: false);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Фото обновлено']);
    }

    public function avatarDelete(): RedirectResponse
    {
        $user = $this->user();
        $avatarField = moonshineConfig()->getUserField('avatar') ?: 'avatar';

        $old = (string) ($user->{$avatarField} ?? '');
        $user->forceFill([$avatarField => null])->save();

        if ($old !== '') {
            rescue(fn () => Storage::disk(moonshineConfig()->getDisk())->delete($old), report: false);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Фото удалено']);
    }

    private function user(): Authenticatable
    {
        return MoonShineAuth::getGuard()->user() ?? abort(403);
    }
}

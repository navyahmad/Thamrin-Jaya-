<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {--name=Administrator} {--generate : Generate a random password}';

    protected $description = 'Create an administrator without public registration or default credentials';

    public function handle(): int
    {
        $password = $this->option('generate') ? Str::password(24) : $this->secret('Password (minimal 12 karakter)');
        $data = ['email' => $this->argument('email'), 'name' => $this->option('name'), 'password' => $password];
        $validator = Validator::make($data, [
            'email' => ['required', 'email', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', Password::min(12)->letters()->numbers()],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        $user = new User($data);
        $user->is_admin = true;
        $user->save();
        $this->info('Administrator dibuat: '.$user->email);
        if ($this->option('generate')) {
            $this->line('Password: '.$password);
        }

        return self::SUCCESS;
    }
}

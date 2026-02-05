<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:reset-passwords
                            {--user= : Reset password for a specific user by email}
                            {--all : Reset passwords for all users}
                            {--password= : Set a specific password (default: generates random)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset user passwords (fixes double-hashed password issue)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('user') && !$this->option('all')) {
            $this->error('Please specify --user=email or --all');
            $this->line('');
            $this->line('Examples:');
            $this->line('  php artisan users:reset-passwords --user=admin@example.com');
            $this->line('  php artisan users:reset-passwords --user=admin@example.com --password=newpassword123');
            $this->line('  php artisan users:reset-passwords --all');
            return 1;
        }

        if ($this->option('all')) {
            return $this->resetAllPasswords();
        }

        return $this->resetSingleUser($this->option('user'));
    }

    /**
     * Reset password for a single user
     */
    private function resetSingleUser(string $email): int
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }

        $password = $this->option('password') ?: $this->generatePassword();

        // Update password directly in database to avoid any model casting
        User::where('id', $user->id)->update([
            'password' => Hash::make($password)
        ]);

        $this->info("Password reset for: {$user->email}");
        $this->line("New password: {$password}");
        $this->warn('Please save this password and share it securely with the user.');

        return 0;
    }

    /**
     * Reset passwords for all users
     */
    private function resetAllPasswords(): int
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->warn('No users found in the database.');
            return 0;
        }

        if (!$this->confirm("This will reset passwords for {$users->count()} user(s). Continue?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Resetting passwords...');
        $this->line('');

        $results = [];

        foreach ($users as $user) {
            $password = $this->option('password') ?: $this->generatePassword();

            // Update password directly in database to avoid any model casting
            User::where('id', $user->id)->update([
                'password' => Hash::make($password)
            ]);

            $results[] = [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $password,
            ];
        }

        $this->table(['Name', 'Email', 'New Password'], $results);

        $this->line('');
        $this->warn('Please save these passwords and share them securely with users.');

        return 0;
    }

    /**
     * Generate a random password
     */
    private function generatePassword(): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 12);
    }
}

<?php

namespace App\Providers;

use App\Http\Middleware\ExigirTrocaDeSenha;
use App\Http\Middleware\GarantirUsuarioAtivo;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Em desenvolvimento, falha alto se algum atributo não listado em $fillable for atribuído (anti mass assignment).
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventLazyLoading(! $this->app->isProduction());

        // RN-35: só o Admin Geral do Sistema vê/acessa a gestão de usuários (os casos de uso verificam de novo — RN-27).
        Gate::define('admin-geral', fn (User $user) => $user->ehAdminGeral());

        // As ações dentro das telas Livewire (/livewire/update) também passam por estas regras, não só a carga da página.
        Livewire::addPersistentMiddleware([GarantirUsuarioAtivo::class, ExigirTrocaDeSenha::class]);

        // RN-37: e-mail de redefinição de senha em português.
        ResetPassword::toMailUsing(fn (User $user, string $token) => (new MailMessage)
            ->subject('Redefinição de senha — '.config('app.name'))
            ->greeting("Olá, {$user->name}!")
            ->line('Recebemos um pedido para redefinir a sua senha.')
            ->action('Redefinir senha', route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->line('O link vale por '.config('auth.passwords.users.expire').' minutos.')
            ->line('Se você não pediu a redefinição, ignore este e-mail: sua senha continua a mesma.')
            ->salutation('— '.config('app.name')));
    }
}

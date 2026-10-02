<div class="mb-3">
    <label class="form-label" for="senhaAtual">{{ $rotuloAtual ?? 'Senha atual' }}</label>
    <input id="senhaAtual" type="password" class="form-control @error('senhaAtual') is-invalid @enderror" wire:model="senhaAtual" autocomplete="current-password" required>
    @error('senhaAtual') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label class="form-label" for="novaSenha">Nova senha</label>
    <input id="novaSenha" type="password" class="form-control @error('novaSenha') is-invalid @enderror" wire:model="novaSenha" autocomplete="new-password" required>
    @error('novaSenha') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label class="form-label" for="novaSenha_confirmation">Confirme a nova senha</label>
    <input id="novaSenha_confirmation" type="password" class="form-control" wire:model="novaSenha_confirmation" autocomplete="new-password" required>
</div>

<?php

namespace App\Livewire\Filament\Dashboard;

use App\Models\User;
use App\Services\TenantManager;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class Team extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $tenant = Filament::getTenant();

                return User::query()
                    ->whereHas('tenants', function (Builder $query) use ($tenant) {
                        $query->where('tenant_id', $tenant->id);
                    });
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->label(__('Remove User'))
                    ->color('danger')
                    ->requiresConfirmation(true)
                    ->visible(function (User $user, TenantManager $tenantManager) {
                        return $tenantManager->canRemoveUser(Filament::getTenant(), $user);
                    })
                    ->action(function (User $user, TenantManager $tenantManager) {
                        $result = $tenantManager->removeUser(Filament::getTenant(), $user);

                        if ($result) {
                            Notification::make()
                                ->title(__('User has been removed.'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('User could not be removed.'))
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.filament.dashboard.team');
    }
}

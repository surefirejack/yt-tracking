<?php

namespace App\Filament\Admin\Resources\TenantResource\RelationManagers;

use App\Constants\TenancyPermissionConstants;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use App\Services\TenantPermissionManager;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\SelectColumn::make('role')
                    ->getStateUsing(function (User $user, TenantPermissionManager $tenantPermissionManager) {
                        return $tenantPermissionManager->getTenantUserRoles($this->ownerRecord, $user)[0] ?? null;
                    })
                    ->options(function (TenantPermissionManager $tenantPermissionManager) {
                        return TenancyPermissionConstants::getRoles();
                    })
                    ->updateStateUsing(function (User $user, ?string $state, TenantPermissionManager $tenantPermissionManager) {
                        if ($state === null) {
                            return;
                        }

                        $tenantPermissionManager->assignTenantUserRole($this->ownerRecord, $user, $state);

                        Notification::make()
                            ->title(__('User role has been updated.'))
                            ->success()
                            ->send();
                    }),
                Tables\Columns\IconColumn::make('creator')
                    ->getStateUsing(function (User $user) {
                        return $user->id === $this->ownerRecord->created_by;
                    })
                    ->label(__('Is Creator'))
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->url(fn ($record) => EditUser::getUrl(['record' => $record]))
                    ->label(__('Edit'))
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

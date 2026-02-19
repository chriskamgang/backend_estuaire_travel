<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemUserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class SystemUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 98;

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('Utilisateurs Système');
    }

    public static function getModelLabel(): string
    {
        return __('Utilisateur Système');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Utilisateurs Système');
    }

    /**
     * Filter to only show users with roles (system/dashboard users)
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('roles');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_system_users');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_system_users');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('edit_system_users');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_system_users');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_system_users');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Informations générales'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Nom'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('Téléphone'))
                            ->tel()
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label(__('Mot de passe'))
                            ->password()
                            ->required(fn ($record) => $record === null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('Rôles et Permissions'))
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->label(__('Rôles'))
                            ->relationship('roles', 'name')
                            ->options(Role::all()->pluck('name', 'name'))
                            ->descriptions([
                                'super_admin' => __('Accès complet à toutes les fonctionnalités'),
                                'admin' => __('Accès à tout sauf les utilisateurs système'),
                                'manager' => __('Gestion des réservations et visualisation'),
                                'support' => __('Lecture seule et gestion limitée des réservations'),
                            ])
                            ->columns(2)
                            ->required(),
                    ]),

                // Hidden fields to ensure these are system users
                Forms\Components\Hidden::make('is_driver')->default(false),
                Forms\Components\Hidden::make('is_verified')->default(true),
                Forms\Components\Hidden::make('phone_verified')->default(true),
                Forms\Components\Hidden::make('email_verified')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Nom'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Téléphone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('Rôles'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'manager' => 'info',
                        'support' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => __('Super Admin'),
                        'admin' => __('Admin'),
                        'manager' => __('Manager'),
                        'support' => __('Support'),
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('email_verified')
                    ->label(__('Email vérifié'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Créé le'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Mis à jour le'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label(__('Rôle'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemUsers::route('/'),
            'create' => Pages\CreateSystemUser::route('/create'),
            'edit' => Pages\EditSystemUser::route('/{record}/edit'),
        ];
    }
}

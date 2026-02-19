<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\User;
use App\Models\Setting;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Clients');
    }

    public static function getModelLabel(): string
    {
        return __('Client');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Clients');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('wallet')
            ->where('is_driver', false)
            ->whereDoesntHave('roles', fn (Builder $q) => $q->whereIn('name', ['super_admin', 'admin', 'manager', 'support', 'driver']));
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_clients');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_clients');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('edit_clients');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_clients');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_clients');
    }

    public static function form(Form $form): Form
    {
        return $form
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
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('password')
                    ->label(__('Mot de passe'))
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
                Forms\Components\TextInput::make('avatar')
                    ->label(__('Avatar'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Toggle::make('is_verified')
                    ->label(__('Est vérifié'))
                    ->default(false),
                Forms\Components\Toggle::make('phone_verified')
                    ->label(__('Téléphone vérifié'))
                    ->default(false),
                Forms\Components\Toggle::make('email_verified')
                    ->label(__('Email vérifié'))
                    ->default(false),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label(__('Email vérifié le')),
                Forms\Components\Select::make('language')
                    ->label(__('Langue'))
                    ->options([
                        'fr' => 'Français',
                        'en' => 'English',
                    ])
                    ->default('fr'),
                Forms\Components\Select::make('theme')
                    ->label(__('Thème'))
                    ->options([
                        'light' => 'Light',
                        'dark' => 'Dark',
                    ])
                    ->default('light'),
                Forms\Components\TextInput::make('wallet_balance_display')
                    ->label(__('Solde Portefeuille'))
                    ->default(fn (?User $record) => $record ? number_format((float) ($record->wallet?->balance ?? 0), 0, ',', ' ') . ' FCFA' : '0 FCFA')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText(__('Solde actuel du portefeuille du client')),
                Forms\Components\TextInput::make('loyalty_points')
                    ->label(__('Points de fidélité'))
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('total_trips')
                    ->label(__('Total des voyages'))
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('free_trips_available')
                    ->label(__('Voyages gratuits disponibles'))
                    ->numeric()
                    ->default(0),
                Forms\Components\Hidden::make('is_driver')
                    ->default(false),
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
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label(__('Vérifié'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('wallet.balance')
                    ->label(__('Portefeuille'))
                    ->getStateUsing(fn (User $record) => $record->wallet?->balance ?? 0)
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' FCFA')
                    ->sortable(query: function ($query, string $direction) {
                        $query->leftJoin('wallets', 'users.id', '=', 'wallets.user_id')
                              ->orderBy('wallets.balance', $direction)
                              ->select('users.*');
                    })
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('loyalty_points')
                    ->label(__('Points'))
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('loyalty_status')
                    ->label(__('Statut'))
                    ->getStateUsing(fn (User $record) => Setting::getUserStatus($record->loyalty_points))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Platinum' => 'success',
                        'Gold' => 'warning',
                        'Silver' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_trips')
                    ->label(__('Voyages'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('free_trips_available')
                    ->label(__('Voyages gratuits'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Créé le'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}

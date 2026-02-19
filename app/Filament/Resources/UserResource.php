<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('avatar')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Toggle::make('is_driver')
                    ->required(),
                Forms\Components\Toggle::make('is_verified')
                    ->required(),
                Forms\Components\Toggle::make('phone_verified')
                    ->required(),
                Forms\Components\Toggle::make('email_verified')
                    ->required(),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('language')
                    ->required()
                    ->maxLength(5)
                    ->default('fr'),
                Forms\Components\Textarea::make('preferences')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('theme')
                    ->required()
                    ->maxLength(10)
                    ->default('light'),
                Forms\Components\TextInput::make('loyalty_points')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_trips')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('free_trips_available')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Nom'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->phone),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('Non renseigné'),
                Tables\Columns\TextColumn::make('bookings_count')
                    ->label(__('Réservations'))
                    ->counts('bookings')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_trips')
                    ->label(__('Voyages'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('loyalty_points')
                    ->label(__('Points'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label(__('Vérifié'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Inscrit le'))
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

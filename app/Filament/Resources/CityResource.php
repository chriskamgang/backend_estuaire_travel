<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Filament\Resources\CityResource\RelationManagers;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('Villes');
    }

    public static function getModelLabel(): string
    {
        return __('Ville');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Villes');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_cities');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_cities');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('edit_cities');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_cities');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_cities');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Nom'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('region')
                    ->label(__('Région'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('latitude')
                    ->label(__('Latitude'))
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('longitude')
                    ->label(__('Longitude'))
                    ->numeric()
                    ->default(null),
                Forms\Components\Toggle::make('is_main_city')
                    ->label(__('Est ville principale'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Nom'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('region')
                    ->label(__('Région'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->label(__('Latitude'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->label(__('Longitude'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_main_city')
                    ->label(__('Est ville principale'))
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
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}

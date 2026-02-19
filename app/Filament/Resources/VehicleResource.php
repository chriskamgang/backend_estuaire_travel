<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('Véhicules');
    }

    public static function getModelLabel(): string
    {
        return __('Véhicule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Véhicules');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_vehicles');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_vehicles');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('edit_vehicles');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_vehicles');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_vehicles');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label(__('Chauffeur'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('brand')
                    ->label(__('Marque'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('model')
                    ->label(__('Modèle'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('year')
                    ->label(__('Année'))
                    ->required()
                    ->maxLength(4),
                Forms\Components\TextInput::make('color')
                    ->label(__('Couleur'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('license_plate')
                    ->label(__('Plaque d\'immatriculation'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('seats')
                    ->label(__('Nombre de places'))
                    ->required()
                    ->numeric()
                    ->minValue(1),
                Forms\Components\Select::make('vehicle_type')
                    ->label(__('Type de véhicule'))
                    ->options([
                        'sedan'  => 'Berline',
                        'suv'    => 'SUV',
                        'van'    => 'Van / Minibus',
                        'pickup' => 'Pick-up',
                        'moto'   => 'Moto',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('has_ac')
                    ->label(__('Climatisation'))
                    ->default(true),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('Actif'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Chauffeur'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand')
                    ->label(__('Marque'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->label(__('Modèle'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('year')
                    ->label(__('Année'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('color')
                    ->label(__('Couleur'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('license_plate')
                    ->label(__('Plaque'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('seats')
                    ->label(__('Places'))
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sedan'  => 'Berline',
                        'suv'    => 'SUV',
                        'van'    => 'Van / Minibus',
                        'pickup' => 'Pick-up',
                        'moto'   => 'Moto',
                        default  => $state,
                    })
                    ->color('gray'),
                Tables\Columns\IconColumn::make('has_ac')
                    ->label(__('Clim'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Actif'))
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
